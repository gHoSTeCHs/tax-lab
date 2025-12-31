# TaxLab Implementation - Testing Strategy

## Overview

TaxLab uses Pest PHP for backend testing and Vitest for frontend testing. This document outlines the testing strategy, patterns, and requirements for the multi-tenant application.

## Testing Stack

| Layer | Tool | Purpose |
|-------|------|---------|
| Backend Unit | Pest PHP | Service, action, model tests |
| Backend Feature | Pest PHP | HTTP, controller, integration tests |
| Frontend Unit | Vitest | Component, hook tests |
| E2E (Future) | Playwright | Full user journey tests |

---

## Directory Structure

```
tests/
├── Feature/
│   ├── Admin/
│   │   ├── Auth/
│   │   │   └── LoginTest.php
│   │   ├── TenantManagementTest.php
│   │   └── PlanManagementTest.php
│   ├── App/
│   │   ├── Auth/
│   │   │   ├── LoginTest.php
│   │   │   └── RegistrationTest.php
│   │   ├── ClientManagementTest.php
│   │   └── TeamManagementTest.php
│   ├── Portal/
│   │   ├── Auth/
│   │   │   └── LoginTest.php
│   │   └── ReportAccessTest.php
│   └── Tenancy/
│       └── TenantIsolationTest.php
├── Unit/
│   ├── Services/
│   │   ├── FirmServiceTest.php
│   │   ├── FirmUserServiceTest.php
│   │   └── TenantContextTest.php
│   ├── Models/
│   │   ├── FirmTest.php
│   │   └── FirmUserTest.php
│   └── Actions/
│       └── CreateFirmActionTest.php
├── Traits/
│   ├── WithTenancy.php
│   └── AuthenticatesUsers.php
├── Pest.php
└── TestCase.php
```

---

## Test Traits

### WithTenancy Trait

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

    protected function createFirm(array $attributes = []): Firm
    {
        return Firm::factory()->create(array_merge(
            ['status' => 'active'],
            $attributes
        ));
    }

    protected function createFirmWithUser(string $role = 'partner', array $firmAttributes = []): array
    {
        $firm = $this->createFirm($firmAttributes);
        $user = FirmUser::factory()
            ->for($firm)
            ->create(['status' => 'active', 'role' => $role]);

        return [$firm, $user];
    }
}
```

### AuthenticatesUsers Trait

**File:** `tests/Traits/AuthenticatesUsers.php`

```php
<?php

namespace Tests\Traits;

use App\Models\AdminUser;
use App\Models\ClientPortalUser;
use App\Models\Firm;
use App\Models\FirmUser;

trait AuthenticatesUsers
{
    protected function actingAsAdmin(?AdminUser $admin = null): static
    {
        $admin ??= AdminUser::factory()->create([
            'status' => 'active',
            'two_factor_confirmed_at' => now(),
        ]);

        return $this->actingAs($admin, 'admin');
    }

    protected function actingAsFirmPartner(?FirmUser $user = null, ?Firm $firm = null): static
    {
        $firm ??= Firm::factory()->create(['status' => 'active']);
        $user ??= FirmUser::factory()
            ->for($firm)
            ->create(['status' => 'active', 'role' => 'partner']);

        return $this->actingAs($user, 'firm');
    }

    protected function actingAsFirmAssociate(?Firm $firm = null): static
    {
        $firm ??= Firm::factory()->create(['status' => 'active']);
        $user = FirmUser::factory()
            ->for($firm)
            ->create(['status' => 'active', 'role' => 'associate']);

        return $this->actingAs($user, 'firm');
    }

    protected function actingAsPortalUser(?ClientPortalUser $user = null): static
    {
        $user ??= ClientPortalUser::factory()->create(['status' => 'active']);

        return $this->actingAs($user, 'client');
    }

    protected function createAdminUser(array $attributes = []): AdminUser
    {
        return AdminUser::factory()->create(array_merge([
            'status' => 'active',
            'two_factor_confirmed_at' => now(),
        ], $attributes));
    }
}
```

---

## Pest Configuration

**File:** `tests/Pest.php`

```php
<?php

use Tests\TestCase;
use Tests\Traits\AuthenticatesUsers;
use Tests\Traits\WithTenancy;

pest()->extend(TestCase::class)
    ->use(AuthenticatesUsers::class)
    ->use(WithTenancy::class)
    ->in('Feature', 'Unit');

expect()->extend('toBeActiveFirm', function () {
    return $this->status->toBe('active');
});

expect()->extend('toBelongToFirm', function (string $firmId) {
    return $this->firm_id->toBe($firmId);
});
```

---

## Feature Tests

### Tenant Isolation Test

**File:** `tests/Feature/Tenancy/TenantIsolationTest.php`

```php
<?php

use App\Models\Firm;
use App\Models\FirmUser;
use App\Models\TaxClient;
use App\Services\Tenancy\TenantContext;

beforeEach(function () {
    $this->setUpTenancy();
});

afterEach(function () {
    $this->tearDownTenancy();
});

test('firm user can only see own clients', function () {
    $ownClient = TaxClient::factory()->for($this->firm)->create();

    [$otherFirm, $otherUser] = $this->createFirmWithUser();
    TenantContext::set($otherFirm);
    $otherClient = TaxClient::factory()->for($otherFirm)->create();

    TenantContext::set($this->firm);

    $clients = TaxClient::all();

    expect($clients)->toHaveCount(1)
        ->and($clients->first()->id)->toBe($ownClient->id);
});

test('new records automatically get firm_id', function () {
    $client = TaxClient::create([
        'name' => 'Test Company',
        'entity_type' => 'company',
    ]);

    expect($client->firm_id)->toBe($this->firm->id);
});

test('admin can bypass tenant scope', function () {
    TaxClient::factory()->for($this->firm)->create();

    [$otherFirm] = $this->createFirmWithUser();
    TenantContext::set($otherFirm);
    TaxClient::factory()->for($otherFirm)->create();

    $allClients = TenantContext::bypass(fn () => TaxClient::all());

    expect($allClients)->toHaveCount(2);
});

test('tenant scope is applied via middleware', function () {
    $ownClient = TaxClient::factory()->for($this->firm)->create();

    [$otherFirm, $otherUser] = $this->createFirmWithUser();
    TaxClient::factory()->for($otherFirm)->create();

    $this->actingAsFirmUser()
        ->get('/app/clients')
        ->assertOk()
        ->assertInertia(fn ($page) =>
            $page->has('clients.data', 1)
                ->where('clients.data.0.id', $ownClient->id)
        );
});
```

### Multi-Guard Auth Test

**File:** `tests/Feature/Auth/MultiGuardAuthTest.php`

```php
<?php

use App\Models\AdminUser;
use App\Models\ClientPortalUser;
use App\Models\Firm;
use App\Models\FirmUser;

test('admin can login with valid credentials', function () {
    $admin = $this->createAdminUser();

    $this->post('/admin/login', [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect('/admin/dashboard');

    $this->assertAuthenticatedAs($admin, 'admin');
});

test('admin without 2fa cannot login', function () {
    $admin = AdminUser::factory()->create([
        'status' => 'active',
        'two_factor_confirmed_at' => null,
    ]);

    $this->post('/admin/login', [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest('admin');
});

test('firm user can login with valid credentials', function () {
    $firm = Firm::factory()->create(['status' => 'active']);
    $user = FirmUser::factory()->for($firm)->create(['status' => 'active']);

    $this->post('/app/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect('/app/dashboard');

    $this->assertAuthenticatedAs($user, 'firm');
});

test('firm user of inactive firm cannot login', function () {
    $firm = Firm::factory()->create(['status' => 'suspended']);
    $user = FirmUser::factory()->for($firm)->create(['status' => 'active']);

    $this->post('/app/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest('firm');
});

test('portal user can login with valid credentials', function () {
    $firm = Firm::factory()->create(['status' => 'active']);
    $user = ClientPortalUser::factory()->for($firm)->create(['status' => 'active']);

    $this->post('/portal/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect('/portal/dashboard');

    $this->assertAuthenticatedAs($user, 'client');
});

test('guards are isolated from each other', function () {
    $admin = $this->createAdminUser();

    $this->actingAs($admin, 'admin');

    expect(auth('admin')->check())->toBeTrue()
        ->and(auth('firm')->check())->toBeFalse()
        ->and(auth('client')->check())->toBeFalse();
});
```

### Role-Based Access Test

**File:** `tests/Feature/App/TeamManagementTest.php`

```php
<?php

use App\Models\Firm;
use App\Models\FirmUser;

beforeEach(function () {
    $this->setUpTenancy();
});

afterEach(function () {
    $this->tearDownTenancy();
});

test('partner can view team members', function () {
    FirmUser::factory()->count(3)->for($this->firm)->create();

    $this->actingAsFirmUser($this->firmUser)
        ->get('/app/team')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('users.data', 4));
});

test('partner can invite new team member', function () {
    $this->actingAsFirmUser($this->firmUser)
        ->post('/app/team', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'role' => 'associate',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('firm_users', [
        'email' => 'new@example.com',
        'firm_id' => $this->firm->id,
    ]);
});

test('associate cannot manage team', function () {
    $associate = FirmUser::factory()
        ->for($this->firm)
        ->create(['status' => 'active', 'role' => 'associate']);

    $this->actingAsFirmUser($associate)
        ->post('/app/team', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'role' => 'associate',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertForbidden();
});

test('viewer has read-only access', function () {
    $viewer = FirmUser::factory()
        ->for($this->firm)
        ->create(['status' => 'active', 'role' => 'viewer']);

    $this->actingAsFirmUser($viewer)
        ->get('/app/team')
        ->assertOk();

    $this->actingAsFirmUser($viewer)
        ->post('/app/team', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'role' => 'associate',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertForbidden();
});
```

---

## Unit Tests

### Service Test

**File:** `tests/Unit/Services/FirmUserServiceTest.php`

```php
<?php

use App\DTOs\FirmUserData;
use App\Models\Firm;
use App\Models\FirmUser;
use App\Services\Firm\FirmUserService;
use App\Services\Tenancy\TenantContext;

beforeEach(function () {
    $this->firm = Firm::factory()->create(['status' => 'active']);
    TenantContext::set($this->firm);
    $this->service = app(FirmUserService::class);
});

afterEach(function () {
    TenantContext::clear();
});

test('creates firm user with correct firm_id', function () {
    $data = new FirmUserData(
        name: 'John Doe',
        email: 'john@example.com',
        role: 'associate',
        password: 'password123',
    );

    $user = $this->service->create($data);

    expect($user)
        ->toBeInstanceOf(FirmUser::class)
        ->name->toBe('John Doe')
        ->email->toBe('john@example.com')
        ->firm_id->toBe($this->firm->id)
        ->role->toBe('associate')
        ->status->toBe('active');
});

test('list returns only current firm users', function () {
    FirmUser::factory()->count(3)->for($this->firm)->create();

    $otherFirm = Firm::factory()->create();
    FirmUser::factory()->count(2)->for($otherFirm)->create();

    $users = $this->service->list();

    expect($users)->toHaveCount(3);
});

test('list filters by status', function () {
    FirmUser::factory()->for($this->firm)->create(['status' => 'active']);
    FirmUser::factory()->for($this->firm)->create(['status' => 'inactive']);
    FirmUser::factory()->for($this->firm)->create(['status' => 'pending']);

    $activeUsers = $this->service->list(['status' => 'active']);

    expect($activeUsers)->toHaveCount(1);
});

test('list filters by role', function () {
    FirmUser::factory()->for($this->firm)->create(['role' => 'partner']);
    FirmUser::factory()->for($this->firm)->create(['role' => 'manager']);
    FirmUser::factory()->for($this->firm)->create(['role' => 'associate']);

    $managers = $this->service->list(['role' => 'manager']);

    expect($managers)->toHaveCount(1);
});

test('update modifies user correctly', function () {
    $user = FirmUser::factory()->for($this->firm)->create();

    $data = new FirmUserData(
        name: 'Updated Name',
        email: 'updated@example.com',
        role: 'manager',
    );

    $updated = $this->service->update($user, $data);

    expect($updated)
        ->name->toBe('Updated Name')
        ->email->toBe('updated@example.com')
        ->role->toBe('manager');
});

test('throws exception without tenant context', function () {
    TenantContext::clear();

    $data = new FirmUserData(
        name: 'John Doe',
        email: 'john@example.com',
        role: 'associate',
        password: 'password123',
    );

    $this->service->create($data);
})->throws(RuntimeException::class, 'No tenant context set.');
```

### Model Test

**File:** `tests/Unit/Models/FirmTest.php`

```php
<?php

use App\Models\Firm;
use App\Models\FirmBranding;
use App\Models\FirmUser;
use App\Models\Plan;

test('firm has users relationship', function () {
    $firm = Firm::factory()->create();
    FirmUser::factory()->count(3)->for($firm)->create();

    expect($firm->users)->toHaveCount(3);
});

test('firm has branding relationship', function () {
    $firm = Firm::factory()->create();
    FirmBranding::factory()->for($firm)->create();

    expect($firm->branding)->toBeInstanceOf(FirmBranding::class);
});

test('firm has plan relationship', function () {
    $plan = Plan::factory()->create();
    $firm = Firm::factory()->for($plan)->create();

    expect($firm->plan)->toBeInstanceOf(Plan::class);
});

test('isActive returns correct status', function () {
    $activeFirm = Firm::factory()->create(['status' => 'active']);
    $inactiveFirm = Firm::factory()->create(['status' => 'inactive']);

    expect($activeFirm->isActive())->toBeTrue()
        ->and($inactiveFirm->isActive())->toBeFalse();
});

test('isOnTrial returns correct status', function () {
    $trialFirm = Firm::factory()->create([
        'status' => 'trial',
        'trial_ends_at' => now()->addDays(7),
    ]);

    $expiredTrial = Firm::factory()->create([
        'status' => 'trial',
        'trial_ends_at' => now()->subDays(1),
    ]);

    expect($trialFirm->isOnTrial())->toBeTrue()
        ->and($expiredTrial->isOnTrial())->toBeFalse();
});
```

---

## Running Tests

### Commands

```bash
php artisan test

php artisan test --filter=TenantIsolationTest

php artisan test --testsuite=Feature

php artisan test --testsuite=Unit

php artisan test --parallel
```

### CI/CD Integration

**File:** `.github/workflows/tests.yml`

```yaml
name: Tests

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main, develop]

jobs:
  tests:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: taxlab_test
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

      redis:
        image: redis:7
        ports:
          - 6379:6379
        options: --health-cmd="redis-cli ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: mbstring, pdo, pdo_mysql, redis
          coverage: xdebug

      - name: Install Composer dependencies
        run: composer install --prefer-dist --no-interaction

      - name: Copy environment file
        run: cp .env.testing .env

      - name: Generate application key
        run: php artisan key:generate

      - name: Run migrations
        run: php artisan migrate --force

      - name: Run tests
        run: php artisan test --parallel

      - name: Run Pint
        run: ./vendor/bin/pint --test
```

---

## Test Coverage Requirements

### Minimum Coverage Targets

| Component | Target |
|-----------|--------|
| Services | 90% |
| Actions | 90% |
| Controllers | 80% |
| Models | 70% |
| Middleware | 80% |
| Overall | 80% |

### Generate Coverage Report

```bash
php artisan test --coverage --min=80

php artisan test --coverage-html=coverage-report
```

---

## Best Practices

1. **Use traits for common setup** - WithTenancy, AuthenticatesUsers
2. **Test in isolation** - Each test should set up and tear down its own data
3. **Use factories** - Create test data consistently
4. **Test edge cases** - Invalid inputs, missing data, boundary conditions
5. **Test authorization** - Ensure role-based access works correctly
6. **Test tenant isolation** - Critical for multi-tenant security
7. **Keep tests fast** - Use RefreshDatabase trait, avoid external services

---

## Module 1 Completion Checklist

With testing strategy in place, Module 1 is complete when:

- [ ] All migrations run successfully
- [ ] Three auth guards configured and tested
- [ ] Tenant isolation verified with tests
- [ ] Service layer pattern established
- [ ] Form requests validating input
- [ ] Frontend types defined
- [ ] Layouts for all guards created
- [ ] CI/CD pipeline running tests
- [ ] Code coverage meets targets
