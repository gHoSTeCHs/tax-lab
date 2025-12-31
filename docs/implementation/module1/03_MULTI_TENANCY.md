# TaxLab Implementation - Multi-Tenancy Architecture

## Overview

TaxLab uses a single-database multi-tenancy approach where all tenant data resides in one database with a `firm_id` column for isolation. This approach balances simplicity with proper data separation.

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                         REQUEST FLOW                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Request → Authenticate → ResolveTenant → ApplyScopes → Handle  │
│                                                                  │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐       │
│  │ Auth Guard  │────▶│ Tenant      │────▶│ Global      │       │
│  │ Determines  │     │ Context     │     │ Query       │       │
│  │ User Type   │     │ Set         │     │ Scopes      │       │
│  └─────────────┘     └─────────────┘     └─────────────┘       │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## Core Components

### 1. Tenant Context Service

**File:** `app/Services/Tenancy/TenantContext.php`

```php
<?php

namespace App\Services\Tenancy;

use App\Models\Firm;

class TenantContext
{
    private static ?Firm $currentFirm = null;
    private static bool $bypassScopes = false;

    public static function set(Firm $firm): void
    {
        self::$currentFirm = $firm;
    }

    public static function get(): ?Firm
    {
        return self::$currentFirm;
    }

    public static function id(): ?string
    {
        return self::$currentFirm?->id;
    }

    public static function clear(): void
    {
        self::$currentFirm = null;
    }

    public static function check(): bool
    {
        return self::$currentFirm !== null;
    }

    public static function bypass(callable $callback): mixed
    {
        self::$bypassScopes = true;

        try {
            return $callback();
        } finally {
            self::$bypassScopes = false;
        }
    }

    public static function isBypassed(): bool
    {
        return self::$bypassScopes;
    }
}
```

---

### 2. BelongsToFirm Trait

**File:** `app/Models/Concerns/BelongsToFirm.php`

```php
<?php

namespace App\Models\Concerns;

use App\Models\Firm;
use App\Models\Scopes\FirmScope;
use App\Services\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToFirm
{
    public static function bootBelongsToFirm(): void
    {
        static::addGlobalScope(new FirmScope());

        static::creating(function ($model) {
            if (empty($model->firm_id) && TenantContext::check()) {
                $model->firm_id = TenantContext::id();
            }
        });
    }

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }

    public function scopeForFirm($query, string $firmId)
    {
        return $query->where('firm_id', $firmId);
    }
}
```

---

### 3. Firm Scope (Global Query Scope)

**File:** `app/Models/Scopes/FirmScope.php`

```php
<?php

namespace App\Models\Scopes;

use App\Services\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class FirmScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (TenantContext::isBypassed()) {
            return;
        }

        if (TenantContext::check()) {
            $builder->where($model->getTable() . '.firm_id', TenantContext::id());
        }
    }

    public function extend(Builder $builder): void
    {
        $builder->macro('withoutFirmScope', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });

        $builder->macro('forAllFirms', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }
}
```

---

### 4. Tenant Resolution Middleware

**File:** `app/Http/Middleware/ResolveTenant.php`

```php
<?php

namespace App\Http\Middleware;

use App\Services\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('firm');

        if ($user && $user->firm) {
            TenantContext::set($user->firm);

            if (!$user->firm->isActive() && !$user->firm->isOnTrial()) {
                return $this->handleInactiveFirm($request, $user->firm);
            }
        }

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        TenantContext::clear();
    }

    protected function handleInactiveFirm(Request $request, $firm): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Your account has been suspended.',
                'reason' => $firm->status,
            ], 403);
        }

        return redirect()->route('firm.suspended');
    }
}
```

---

### 5. Register Middleware

**File:** Update `bootstrap/app.php`

```php
<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'tenant' => ResolveTenant::class,
        ]);

        $middleware->group('firm', [
            'web',
            'auth:firm',
            ResolveTenant::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

---

## Usage Patterns

### Automatic Scoping

All models using `BelongsToFirm` trait are automatically scoped:

```php
<?php

use App\Models\TaxClient;

$clients = TaxClient::all();
```

### Bypassing Tenant Scope (Admin Operations)

```php
<?php

use App\Services\Tenancy\TenantContext;
use App\Models\TaxClient;

$allClients = TenantContext::bypass(function () {
    return TaxClient::all();
});

$allClients = TaxClient::forAllFirms()->get();
```

### Setting Tenant Context Manually

```php
<?php

use App\Models\Firm;
use App\Services\Tenancy\TenantContext;

$firm = Firm::find($firmId);
TenantContext::set($firm);

$clients = TaxClient::all();

TenantContext::clear();
```

---

## Tenant-Scoped Tables

The following tables include `firm_id` and use the `BelongsToFirm` trait:

| Table | Model | Description |
|-------|-------|-------------|
| firm_users | FirmUser | Practitioners at firms |
| firm_invitations | FirmInvitation | Pending team invites |
| client_portal_users | ClientPortalUser | Portal access for clients |
| tax_clients | TaxClient | Tax clients of the firm |
| client_financials | ClientFinancial | Financial data entries |
| calculations | Calculation | Tax calculations |
| scenarios | Scenario | What-if analyses |
| reports | Report | Generated reports |
| optimizations | Optimization | Tax optimization opportunities |
| activity_logs | ActivityLog | Audit trail |
| notifications | Notification | User notifications |

---

## Global Tables (No Tenant Scope)

| Table | Model | Description |
|-------|-------|-------------|
| admin_users | AdminUser | Platform administrators |
| firms | Firm | Tenant entities |
| plans | Plan | Subscription plans |
| plan_features | PlanFeature | Plan feature flags |
| subscriptions | Subscription | Firm subscriptions |
| tax_legislation | TaxLegislation | Tax law references |
| tax_rules | TaxRule | Calculation rules |
| kb_categories | KnowledgeBaseCategory | KB categories |
| kb_articles | KnowledgeBaseArticle | KB articles |
| cpd_courses | CpdCourse | Training courses |
| cpd_modules | CpdModule | Course modules |

---

## Tenant Context in Services

### Base Service with Tenant Awareness

**File:** `app/Services/BaseService.php`

```php
<?php

namespace App\Services;

use App\Models\Firm;
use App\Services\Tenancy\TenantContext;

abstract class BaseService
{
    protected function firm(): ?Firm
    {
        return TenantContext::get();
    }

    protected function firmId(): ?string
    {
        return TenantContext::id();
    }

    protected function ensureTenant(): void
    {
        if (!TenantContext::check()) {
            throw new \RuntimeException('No tenant context set.');
        }
    }
}
```

### Using in Services

```php
<?php

namespace App\Services\Client;

use App\Models\TaxClient;
use App\Services\BaseService;

class TaxClientService extends BaseService
{
    public function create(array $data): TaxClient
    {
        $this->ensureTenant();

        return TaxClient::create($data);
    }

    public function listForCurrentFirm(): Collection
    {
        return TaxClient::query()
            ->orderBy('name')
            ->get();
    }
}
```

---

## Caching with Tenant Awareness

### Cache Key Pattern

```php
<?php

namespace App\Services;

trait UsesTenantCache
{
    protected function tenantCacheKey(string $key): string
    {
        $firmId = TenantContext::id() ?? 'global';
        return "firm:{$firmId}:{$key}";
    }

    protected function cacheTenant(string $key, $value, int $ttl = 3600): void
    {
        cache()->put($this->tenantCacheKey($key), $value, $ttl);
    }

    protected function getCachedTenant(string $key, callable $default = null): mixed
    {
        $cacheKey = $this->tenantCacheKey($key);

        if ($default) {
            return cache()->remember($cacheKey, 3600, $default);
        }

        return cache()->get($cacheKey);
    }

    protected function forgetTenantCache(string $key): void
    {
        cache()->forget($this->tenantCacheKey($key));
    }
}
```

### Usage Example

```php
<?php

namespace App\Services\Client;

use App\Services\BaseService;
use App\Services\UsesTenantCache;

class TaxClientService extends BaseService
{
    use UsesTenantCache;

    public function getActiveCount(): int
    {
        return $this->getCachedTenant('clients:active_count', function () {
            return TaxClient::where('status', 'active')->count();
        });
    }

    public function clearCountCache(): void
    {
        $this->forgetTenantCache('clients:active_count');
    }
}
```

---

## Testing Multi-Tenancy

### Test Helper Trait

**File:** `tests/Traits/WithTenancy.php`

```php
<?php

namespace Tests\Traits;

use App\Models\Firm;
use App\Models\FirmUser;
use App\Services\Tenancy\TenantContext;

trait WithTenancy
{
    protected Firm $firm;
    protected FirmUser $firmUser;

    protected function setUpTenancy(): void
    {
        $this->firm = Firm::factory()->create(['status' => 'active']);
        $this->firmUser = FirmUser::factory()
            ->for($this->firm)
            ->create(['status' => 'active', 'role' => 'partner']);

        TenantContext::set($this->firm);
    }

    protected function tearDownTenancy(): void
    {
        TenantContext::clear();
    }

    protected function actingAsFirmUser(?FirmUser $user = null): static
    {
        $user ??= $this->firmUser;
        TenantContext::set($user->firm);
        return $this->actingAs($user, 'firm');
    }

    protected function createFirmWithUser(string $role = 'partner'): array
    {
        $firm = Firm::factory()->create(['status' => 'active']);
        $user = FirmUser::factory()
            ->for($firm)
            ->create(['status' => 'active', 'role' => $role]);

        return [$firm, $user];
    }
}
```

### Test Example

```php
<?php

namespace Tests\Feature;

use App\Models\TaxClient;
use Tests\TestCase;
use Tests\Traits\WithTenancy;

class TenantIsolationTest extends TestCase
{
    use WithTenancy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenancy();
    }

    protected function tearDown(): void
    {
        $this->tearDownTenancy();
        parent::tearDown();
    }

    public function test_firm_can_only_see_own_clients(): void
    {
        $ownClient = TaxClient::factory()->for($this->firm)->create();

        [$otherFirm, $otherUser] = $this->createFirmWithUser();
        $otherClient = TaxClient::factory()->for($otherFirm)->create();

        $clients = TaxClient::all();

        $this->assertCount(1, $clients);
        $this->assertTrue($clients->contains($ownClient));
        $this->assertFalse($clients->contains($otherClient));
    }

    public function test_new_records_automatically_get_firm_id(): void
    {
        $client = TaxClient::create([
            'name' => 'Test Company',
            'entity_type' => 'company',
        ]);

        $this->assertEquals($this->firm->id, $client->firm_id);
    }
}
```

---

## Security Considerations

### 1. Always Verify Tenant Context

```php
public function show(TaxClient $client)
{
    $this->authorize('view', $client);
}
```

### 2. Policy Checks

**File:** `app/Policies/TaxClientPolicy.php`

```php
<?php

namespace App\Policies;

use App\Models\FirmUser;
use App\Models\TaxClient;

class TaxClientPolicy
{
    public function view(FirmUser $user, TaxClient $client): bool
    {
        return $user->firm_id === $client->firm_id;
    }

    public function update(FirmUser $user, TaxClient $client): bool
    {
        return $user->firm_id === $client->firm_id
            && $user->hasPermission('edit_clients');
    }

    public function delete(FirmUser $user, TaxClient $client): bool
    {
        return $user->firm_id === $client->firm_id
            && $user->hasPermission('delete_clients');
    }
}
```

### 3. Never Trust Route Model Binding Alone

The global scope protects route model binding, but always use policies as defense in depth.

---

## Next Steps

Once multi-tenancy is implemented, proceed to:
→ **04_AUTHENTICATION.md** - Implement three-guard authentication
