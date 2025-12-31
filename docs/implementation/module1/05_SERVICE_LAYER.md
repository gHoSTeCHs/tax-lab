# TaxLab Implementation - Service Layer Architecture

## Overview

The service layer architecture keeps controllers thin by delegating business logic to dedicated service classes. This promotes single responsibility, testability, and reusability.

## Architecture Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                       REQUEST LIFECYCLE                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  HTTP Request                                                    │
│       │                                                          │
│       ▼                                                          │
│  ┌─────────────┐                                                │
│  │ Controller  │  Receives request, validates via FormRequest   │
│  └──────┬──────┘                                                │
│         │                                                        │
│         ▼                                                        │
│  ┌─────────────┐                                                │
│  │  Service    │  Contains business logic, orchestrates ops     │
│  └──────┬──────┘                                                │
│         │                                                        │
│    ┌────┴────┐                                                  │
│    ▼         ▼                                                  │
│ ┌──────┐ ┌──────┐                                               │
│ │Model │ │Action│  Data access / Single-purpose operations      │
│ └──────┘ └──────┘                                               │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## Directory Structure

```
app/
├── Services/
│   ├── BaseService.php           Base class with common utilities
│   ├── Auth/
│   │   ├── AdminAuthService.php
│   │   ├── FirmAuthService.php
│   │   └── PortalAuthService.php
│   ├── Tenancy/
│   │   ├── TenantContext.php
│   │   └── TenantService.php
│   ├── User/
│   │   ├── AdminUserService.php
│   │   ├── FirmUserService.php
│   │   └── PortalUserService.php
│   ├── Firm/
│   │   ├── FirmService.php
│   │   ├── FirmInvitationService.php
│   │   └── FirmBrandingService.php
│   ├── Subscription/
│   │   └── SubscriptionService.php
│   └── ...
├── Actions/                      Single-purpose action classes
│   ├── Firm/
│   │   ├── CreateFirmAction.php
│   │   └── ActivateFirmAction.php
│   └── ...
└── DTOs/                         Data Transfer Objects
    ├── FirmData.php
    ├── FirmUserData.php
    └── ...
```

---

## Base Service Class

**File:** `app/Services/BaseService.php`

```php
<?php

namespace App\Services;

use App\Models\Firm;
use App\Services\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    protected function transaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }

    protected function handleException(\Throwable $e, string $message = 'Operation failed'): never
    {
        report($e);
        throw new \RuntimeException($message, 0, $e);
    }
}
```

---

## Service Implementation Patterns

### Pattern 1: CRUD Service

**File:** `app/Services/Firm/FirmUserService.php`

```php
<?php

namespace App\Services\Firm;

use App\DTOs\FirmUserData;
use App\Models\FirmUser;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class FirmUserService extends BaseService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $this->ensureTenant();

        $query = FirmUser::query()
            ->orderBy('name');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('email', 'like', "%{$filters['search']}%");
            });
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function find(string $id): ?FirmUser
    {
        return FirmUser::find($id);
    }

    public function create(FirmUserData $data): FirmUser
    {
        $this->ensureTenant();

        return $this->transaction(function () use ($data) {
            return FirmUser::create([
                'firm_id' => $this->firmId(),
                'name' => $data->name,
                'email' => $data->email,
                'password' => Hash::make($data->password),
                'role' => $data->role,
                'job_title' => $data->jobTitle,
                'phone' => $data->phone,
                'status' => 'active',
            ]);
        });
    }

    public function update(FirmUser $user, FirmUserData $data): FirmUser
    {
        return $this->transaction(function () use ($user, $data) {
            $user->update([
                'name' => $data->name,
                'email' => $data->email,
                'role' => $data->role,
                'job_title' => $data->jobTitle,
                'phone' => $data->phone,
            ]);

            if ($data->password) {
                $user->update(['password' => Hash::make($data->password)]);
            }

            return $user->fresh();
        });
    }

    public function updateStatus(FirmUser $user, string $status): FirmUser
    {
        $user->update(['status' => $status]);
        return $user->fresh();
    }

    public function delete(FirmUser $user): bool
    {
        return $user->delete();
    }

    public function getActiveCount(): int
    {
        $this->ensureTenant();
        return FirmUser::where('status', 'active')->count();
    }
}
```

---

### Pattern 2: Service with Actions

**File:** `app/Services/Firm/FirmService.php`

```php
<?php

namespace App\Services\Firm;

use App\Actions\Firm\ActivateFirmAction;
use App\Actions\Firm\CreateFirmAction;
use App\DTOs\FirmData;
use App\Models\Firm;
use App\Services\BaseService;

class FirmService extends BaseService
{
    public function __construct(
        private CreateFirmAction $createFirmAction,
        private ActivateFirmAction $activateFirmAction,
    ) {}

    public function create(FirmData $data): Firm
    {
        return $this->createFirmAction->execute($data);
    }

    public function activate(Firm $firm): Firm
    {
        return $this->activateFirmAction->execute($firm);
    }

    public function find(string $id): ?Firm
    {
        return Firm::with(['plan', 'branding'])->find($id);
    }

    public function findBySlug(string $slug): ?Firm
    {
        return Firm::where('slug', $slug)->first();
    }

    public function update(Firm $firm, FirmData $data): Firm
    {
        return $this->transaction(function () use ($firm, $data) {
            $firm->update([
                'name' => $data->name,
                'email' => $data->email,
                'phone' => $data->phone,
                'address' => $data->address,
                'city' => $data->city,
                'state' => $data->state,
                'rc_number' => $data->rcNumber,
                'tin' => $data->tin,
            ]);

            return $firm->fresh();
        });
    }

    public function updateSettings(Firm $firm, array $settings): Firm
    {
        $firm->update([
            'settings' => array_merge($firm->settings ?? [], $settings),
        ]);

        return $firm->fresh();
    }
}
```

---

### Pattern 3: Action Class

**File:** `app/Actions/Firm/CreateFirmAction.php`

```php
<?php

namespace App\Actions\Firm;

use App\DTOs\FirmData;
use App\Models\Firm;
use App\Models\FirmBranding;
use App\Models\FirmUser;
use App\Models\Plan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateFirmAction
{
    public function execute(FirmData $data): Firm
    {
        return DB::transaction(function () use ($data) {
            $firm = Firm::create([
                'name' => $data->name,
                'slug' => Str::slug($data->name),
                'email' => $data->email,
                'phone' => $data->phone,
                'status' => 'trial',
                'plan_id' => $this->getDefaultPlanId(),
                'trial_ends_at' => now()->addDays(14),
            ]);

            FirmBranding::create([
                'firm_id' => $firm->id,
            ]);

            FirmUser::create([
                'firm_id' => $firm->id,
                'name' => $data->ownerName,
                'email' => $data->email,
                'password' => Hash::make($data->password),
                'role' => 'partner',
                'status' => 'active',
                'email_verified_at' => now(),
            ]);

            return $firm;
        });
    }

    private function getDefaultPlanId(): ?string
    {
        return Plan::where('slug', 'starter')
            ->where('is_active', true)
            ->value('id');
    }
}
```

---

## Data Transfer Objects (DTOs)

**File:** `app/DTOs/FirmData.php`

```php
<?php

namespace App\DTOs;

readonly class FirmData
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $rcNumber = null,
        public ?string $tin = null,
        public ?string $ownerName = null,
        public ?string $password = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            phone: $data['phone'] ?? null,
            address: $data['address'] ?? null,
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
            rcNumber: $data['rc_number'] ?? null,
            tin: $data['tin'] ?? null,
            ownerName: $data['owner_name'] ?? null,
            password: $data['password'] ?? null,
        );
    }
}
```

**File:** `app/DTOs/FirmUserData.php`

```php
<?php

namespace App\DTOs;

readonly class FirmUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $role,
        public ?string $password = null,
        public ?string $jobTitle = null,
        public ?string $phone = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            role: $data['role'],
            password: $data['password'] ?? null,
            jobTitle: $data['job_title'] ?? null,
            phone: $data['phone'] ?? null,
        );
    }
}
```

---

## Controller Implementation

**File:** `app/Http/Controllers/App/FirmUserController.php`

```php
<?php

namespace App\Http\Controllers\App;

use App\DTOs\FirmUserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\FirmUser\StoreFirmUserRequest;
use App\Http\Requests\App\FirmUser\UpdateFirmUserRequest;
use App\Models\FirmUser;
use App\Services\Firm\FirmUserService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class FirmUserController extends Controller
{
    public function __construct(
        private FirmUserService $firmUserService
    ) {}

    public function index(): Response
    {
        $users = $this->firmUserService->list(request()->all());

        return Inertia::render('App/Team/Index', [
            'users' => $users,
            'filters' => request()->only(['status', 'role', 'search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('App/Team/Create');
    }

    public function store(StoreFirmUserRequest $request): RedirectResponse
    {
        $data = FirmUserData::fromRequest($request->validated());
        $user = $this->firmUserService->create($data);

        return redirect()
            ->route('app.team.show', $user)
            ->with('success', 'Team member added successfully.');
    }

    public function show(FirmUser $user): Response
    {
        return Inertia::render('App/Team/Show', [
            'user' => $user,
        ]);
    }

    public function edit(FirmUser $user): Response
    {
        return Inertia::render('App/Team/Edit', [
            'user' => $user,
        ]);
    }

    public function update(UpdateFirmUserRequest $request, FirmUser $user): RedirectResponse
    {
        $data = FirmUserData::fromRequest($request->validated());
        $this->firmUserService->update($user, $data);

        return redirect()
            ->route('app.team.show', $user)
            ->with('success', 'Team member updated successfully.');
    }

    public function destroy(FirmUser $user): RedirectResponse
    {
        $this->firmUserService->delete($user);

        return redirect()
            ->route('app.team.index')
            ->with('success', 'Team member removed.');
    }
}
```

---

## Form Requests

**File:** `app/Http/Requests/App/FirmUser/StoreFirmUserRequest.php`

```php
<?php

namespace App\Http\Requests\App\FirmUser;

use App\Enums\FirmRole;
use App\Models\FirmUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreFirmUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->canManageTeam();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(FirmUser::class, 'email'),
            ],
            'role' => ['required', 'string', Rule::in(FirmRole::values())],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
        ];
    }
}
```

**File:** `app/Http/Requests/App/FirmUser/UpdateFirmUserRequest.php`

```php
<?php

namespace App\Http\Requests\App\FirmUser;

use App\Enums\FirmRole;
use App\Models\FirmUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateFirmUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->canManageTeam();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(FirmUser::class, 'email')
                    ->ignore($this->route('user')),
            ],
            'role' => ['required', 'string', Rule::in(FirmRole::values())],
            'password' => ['nullable', 'string', Password::defaults(), 'confirmed'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
        ];
    }
}
```

---

## Service Provider Registration

**File:** `app/Providers/AppServiceProvider.php`

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\Tenancy\TenantContext::class);
    }

    public function boot(): void
    {
        //
    }
}
```

---

## Testing Services

**File:** `tests/Unit/Services/FirmUserServiceTest.php`

```php
<?php

namespace Tests\Unit\Services;

use App\DTOs\FirmUserData;
use App\Models\Firm;
use App\Models\FirmUser;
use App\Services\Firm\FirmUserService;
use App\Services\Tenancy\TenantContext;
use Tests\TestCase;

class FirmUserServiceTest extends TestCase
{
    private FirmUserService $service;
    private Firm $firm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->firm = Firm::factory()->create(['status' => 'active']);
        TenantContext::set($this->firm);

        $this->service = app(FirmUserService::class);
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_create_user(): void
    {
        $data = new FirmUserData(
            name: 'John Doe',
            email: 'john@example.com',
            role: 'associate',
            password: 'password123',
        );

        $user = $this->service->create($data);

        $this->assertInstanceOf(FirmUser::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals($this->firm->id, $user->firm_id);
    }

    public function test_list_returns_only_firm_users(): void
    {
        FirmUser::factory()->count(3)->for($this->firm)->create();

        $otherFirm = Firm::factory()->create();
        FirmUser::factory()->count(2)->for($otherFirm)->create();

        $users = $this->service->list();

        $this->assertCount(3, $users);
    }

    public function test_list_filters_by_status(): void
    {
        FirmUser::factory()->for($this->firm)->create(['status' => 'active']);
        FirmUser::factory()->for($this->firm)->create(['status' => 'inactive']);

        $users = $this->service->list(['status' => 'active']);

        $this->assertCount(1, $users);
    }
}
```

---

## Best Practices Summary

### Controllers Should:
- Accept Form Requests for validation
- Call services for business logic
- Return Inertia responses
- Remain under 10-15 lines per method

### Services Should:
- Extend BaseService for tenant context
- Use DTOs for complex input data
- Handle transactions for multi-model operations
- Delegate complex single-purpose operations to Actions

### Actions Should:
- Perform one specific operation
- Be invokable or have a single `execute` method
- Be reusable across different services

### Form Requests Should:
- Handle authorization checks
- Validate input data
- Use Rule objects for complex validation

---

## Next Steps

Once service layer is established, proceed to:
→ **06_FRONTEND_ADDITIONS.md** - Frontend type system and layout additions
