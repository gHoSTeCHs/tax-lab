# Module 2: Testing Strategy

## Overview

This document outlines the testing approach for Module 2 (Platform Admin Panel), covering unit tests, feature tests, and browser tests.

---

## Test Structure

```
tests/
├── Feature/Admin/
│   ├── Auth/
│   │   ├── AdminLoginTest.php
│   │   ├── AdminTwoFactorTest.php
│   │   └── AdminLogoutTest.php
│   ├── DashboardTest.php
│   ├── TenantTest.php
│   ├── TenantNoteTest.php
│   ├── TenantActionTest.php
│   ├── ImpersonationTest.php
│   └── PlanTest.php
├── Unit/Admin/
│   ├── Services/
│   │   ├── DashboardServiceTest.php
│   │   ├── TenantServiceTest.php
│   │   ├── ImpersonationServiceTest.php
│   │   ├── PlanServiceTest.php
│   │   └── AdminAuditServiceTest.php
│   └── Policies/
│       ├── FirmPolicyTest.php
│       └── PlanPolicyTest.php
└── Browser/Admin/
    ├── DashboardTest.php
    ├── TenantManagementTest.php
    └── PlanManagementTest.php
```

---

## Feature Tests

### AdminLoginTest.php

```php
<?php

namespace Tests\Feature\Admin\Auth;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_page_is_displayed(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/Auth/Login'));
    }

    public function test_admin_can_login_with_valid_credentials(): void
    {
        $admin = AdminUser::factory()->create([
            'password' => bcrypt('password123'),
            'two_factor_enabled' => false,
        ]);

        $response = $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        $this->assertAuthenticated('admin');
        $response->assertRedirect('/admin/dashboard');
    }

    public function test_admin_cannot_login_with_invalid_credentials(): void
    {
        $admin = AdminUser::factory()->create();

        $response = $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest('admin');
        $response->assertSessionHasErrors('email');
    }

    public function test_admin_is_redirected_to_2fa_when_enabled(): void
    {
        $admin = AdminUser::factory()->create([
            'password' => bcrypt('password123'),
            'two_factor_enabled' => true,
            'two_factor_secret' => encrypt('secret'),
        ]);

        $response = $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin/two-factor');
    }

    public function test_admin_account_is_locked_after_failed_attempts(): void
    {
        $admin = AdminUser::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', [
                'email' => $admin->email,
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'correct-password',
        ]);

        $response->assertSessionHas('error');
    }

    public function test_suspended_admin_cannot_login(): void
    {
        $admin = AdminUser::factory()->create([
            'password' => bcrypt('password123'),
            'status' => 'suspended',
        ]);

        $response = $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        $this->assertGuest('admin');
        $response->assertSessionHasErrors('email');
    }
}
```

### DashboardTest.php

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use App\Models\Firm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = AdminUser::factory()->create();
    }

    public function test_dashboard_page_requires_authentication(): void
    {
        $response = $this->get('/admin/dashboard');

        $response->assertRedirect('/admin/login');
    }

    public function test_dashboard_page_is_displayed(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard/Index')
            ->has('metrics')
            ->has('revenueData')
            ->has('funnelData')
            ->has('recentActivity')
            ->has('attentionItems')
        );
    }

    public function test_dashboard_displays_correct_metrics(): void
    {
        Firm::factory()->count(5)->create(['status' => 'active']);
        Firm::factory()->count(2)->create(['status' => 'suspended']);

        $response = $this->actingAs($this->admin, 'admin')
            ->get('/admin/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->where('metrics.active_tenants', 5)
        );
    }

    public function test_metrics_endpoint_returns_json(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->getJson('/admin/api/dashboard/metrics');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'active_tenants',
            'mrr',
            'trial_conversions',
            'churn_rate',
        ]);
    }
}
```

### TenantTest.php

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use App\Models\Firm;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantTest extends TestCase
{
    use RefreshDatabase;

    protected AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = AdminUser::factory()->create();
    }

    public function test_tenant_list_is_displayed(): void
    {
        Firm::factory()->count(3)->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->get('/admin/tenants');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Tenants/Index')
            ->has('tenants.data', 3)
        );
    }

    public function test_tenant_list_can_be_filtered_by_status(): void
    {
        Firm::factory()->count(3)->create(['status' => 'active']);
        Firm::factory()->count(2)->create(['status' => 'suspended']);

        $response = $this->actingAs($this->admin, 'admin')
            ->get('/admin/tenants?status=active');

        $response->assertInertia(fn ($page) => $page
            ->has('tenants.data', 3)
        );
    }

    public function test_tenant_list_can_be_searched(): void
    {
        Firm::factory()->create(['name' => 'Alpha Tax']);
        Firm::factory()->create(['name' => 'Beta Accounting']);

        $response = $this->actingAs($this->admin, 'admin')
            ->get('/admin/tenants?search=Alpha');

        $response->assertInertia(fn ($page) => $page
            ->has('tenants.data', 1)
            ->where('tenants.data.0.name', 'Alpha Tax')
        );
    }

    public function test_tenant_detail_is_displayed(): void
    {
        $firm = Firm::factory()->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->get("/admin/tenants/{$firm->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Tenants/Show')
            ->where('tenant.id', $firm->id)
        );
    }

    public function test_tenant_can_be_updated(): void
    {
        $firm = Firm::factory()->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->put("/admin/tenants/{$firm->id}", [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('firms', [
            'id' => $firm->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_tenant_export_returns_csv(): void
    {
        Firm::factory()->count(5)->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->get('/admin/tenants/export');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }
}
```

### TenantActionTest.php

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use App\Models\Firm;
use App\Models\Plan;
use App\Enums\AdminRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantActionTest extends TestCase
{
    use RefreshDatabase;

    protected AdminUser $admin;
    protected AdminUser $supportAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = AdminUser::factory()->create(['role' => AdminRole::SUPER_ADMIN]);
        $this->supportAdmin = AdminUser::factory()->create(['role' => AdminRole::SUPPORT]);
    }

    public function test_tenant_can_be_suspended(): void
    {
        $firm = Firm::factory()->create(['status' => 'active']);

        $response = $this->actingAs($this->admin, 'admin')
            ->post("/admin/tenants/{$firm->id}/suspend", [
                'reason' => 'Violation of terms',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('firms', [
            'id' => $firm->id,
            'status' => 'suspended',
        ]);
    }

    public function test_tenant_can_be_reactivated(): void
    {
        $firm = Firm::factory()->create(['status' => 'suspended']);

        $response = $this->actingAs($this->admin, 'admin')
            ->post("/admin/tenants/{$firm->id}/reactivate");

        $response->assertRedirect();
        $this->assertDatabaseHas('firms', [
            'id' => $firm->id,
            'status' => 'active',
        ]);
    }

    public function test_trial_can_be_extended(): void
    {
        $firm = Firm::factory()->create([
            'trial_ends_at' => now()->addDays(3),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->post("/admin/tenants/{$firm->id}/extend-trial", [
                'days' => 14,
            ]);

        $response->assertRedirect();
        $firm->refresh();
        $this->assertTrue($firm->trial_ends_at->isAfter(now()->addDays(15)));
    }

    public function test_plan_can_be_changed(): void
    {
        $oldPlan = Plan::factory()->create();
        $newPlan = Plan::factory()->create();
        $firm = Firm::factory()->create(['plan_id' => $oldPlan->id]);

        $response = $this->actingAs($this->admin, 'admin')
            ->post("/admin/tenants/{$firm->id}/change-plan", [
                'plan_id' => $newPlan->id,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('firms', [
            'id' => $firm->id,
            'plan_id' => $newPlan->id,
        ]);
    }

    public function test_support_admin_cannot_change_plan(): void
    {
        $newPlan = Plan::factory()->create();
        $firm = Firm::factory()->create();

        $response = $this->actingAs($this->supportAdmin, 'admin')
            ->post("/admin/tenants/{$firm->id}/change-plan", [
                'plan_id' => $newPlan->id,
            ]);

        $response->assertStatus(403);
    }

    public function test_actions_are_audit_logged(): void
    {
        $firm = Firm::factory()->create(['status' => 'active']);

        $this->actingAs($this->admin, 'admin')
            ->post("/admin/tenants/{$firm->id}/suspend", [
                'reason' => 'Test suspension',
            ]);

        $this->assertDatabaseHas('admin_audit_logs', [
            'admin_user_id' => $this->admin->id,
            'action' => 'suspend',
            'resource_type' => Firm::class,
            'resource_id' => $firm->id,
        ]);
    }
}
```

### ImpersonationTest.php

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use App\Models\Firm;
use App\Models\FirmUser;
use App\Enums\AdminRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    protected AdminUser $superAdmin;
    protected AdminUser $regularAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->superAdmin = AdminUser::factory()->create(['role' => AdminRole::SUPER_ADMIN]);
        $this->regularAdmin = AdminUser::factory()->create(['role' => AdminRole::ADMIN]);
    }

    public function test_super_admin_can_impersonate_firm_user(): void
    {
        $firm = Firm::factory()->create();
        $firmUser = FirmUser::factory()->create(['firm_id' => $firm->id]);

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->post("/admin/tenants/{$firm->id}/impersonate/{$firmUser->id}", [
                'reason' => 'Support request #123',
            ]);

        $response->assertRedirect();
        $this->assertAuthenticated('firm');
    }

    public function test_regular_admin_cannot_impersonate(): void
    {
        $firm = Firm::factory()->create();
        $firmUser = FirmUser::factory()->create(['firm_id' => $firm->id]);

        $response = $this->actingAs($this->regularAdmin, 'admin')
            ->post("/admin/tenants/{$firm->id}/impersonate/{$firmUser->id}", [
                'reason' => 'Support request #123',
            ]);

        $response->assertStatus(403);
    }

    public function test_impersonation_requires_reason(): void
    {
        $firm = Firm::factory()->create();
        $firmUser = FirmUser::factory()->create(['firm_id' => $firm->id]);

        $response = $this->actingAs($this->superAdmin, 'admin')
            ->post("/admin/tenants/{$firm->id}/impersonate/{$firmUser->id}");

        $response->assertSessionHasErrors('reason');
    }

    public function test_impersonation_creates_session_record(): void
    {
        $firm = Firm::factory()->create();
        $firmUser = FirmUser::factory()->create(['firm_id' => $firm->id]);

        $this->actingAs($this->superAdmin, 'admin')
            ->post("/admin/tenants/{$firm->id}/impersonate/{$firmUser->id}", [
                'reason' => 'Support request #123',
            ]);

        $this->assertDatabaseHas('impersonation_sessions', [
            'admin_user_id' => $this->superAdmin->id,
            'firm_id' => $firm->id,
            'firm_user_id' => $firmUser->id,
        ]);
    }

    public function test_impersonation_can_be_ended(): void
    {
        $firm = Firm::factory()->create();
        $firmUser = FirmUser::factory()->create(['firm_id' => $firm->id]);

        $this->actingAs($this->superAdmin, 'admin')
            ->post("/admin/tenants/{$firm->id}/impersonate/{$firmUser->id}", [
                'reason' => 'Support request',
            ]);

        $response = $this->post('/firm/impersonation/stop');

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticated('admin');
    }
}
```

### PlanTest.php

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use App\Models\Plan;
use App\Enums\AdminRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanTest extends TestCase
{
    use RefreshDatabase;

    protected AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = AdminUser::factory()->create(['role' => AdminRole::SUPER_ADMIN]);
    }

    public function test_plan_list_is_displayed(): void
    {
        Plan::factory()->count(3)->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->get('/admin/plans');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Plans/Index')
            ->has('plans', 3)
        );
    }

    public function test_plan_can_be_created(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->post('/admin/plans', [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'For growing firms',
                'price_monthly' => 99.00,
                'price_yearly' => 990.00,
                'is_active' => true,
                'is_public' => true,
                'limits' => [
                    ['key' => 'max_users', 'value' => 10],
                    ['key' => 'max_clients', 'value' => 250],
                ],
                'features' => [
                    ['key' => 'tax_returns', 'enabled' => true],
                    ['key' => 'cpd_tracking', 'enabled' => true],
                ],
            ]);

        $response->assertRedirect('/admin/plans');
        $this->assertDatabaseHas('plans', ['slug' => 'professional']);
        $this->assertDatabaseHas('plan_limits', ['key' => 'max_users', 'value' => 10]);
        $this->assertDatabaseHas('plan_features', ['key' => 'tax_returns', 'enabled' => true]);
    }

    public function test_plan_can_be_updated(): void
    {
        $plan = Plan::factory()->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->put("/admin/plans/{$plan->id}", [
                'name' => 'Updated Plan',
                'slug' => $plan->slug,
                'price_monthly' => 149.00,
                'price_yearly' => 1490.00,
                'is_active' => true,
                'is_public' => true,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'name' => 'Updated Plan',
            'price_monthly' => 149.00,
        ]);
    }

    public function test_plan_slug_must_be_unique(): void
    {
        Plan::factory()->create(['slug' => 'starter']);

        $response = $this->actingAs($this->admin, 'admin')
            ->post('/admin/plans', [
                'name' => 'Another Starter',
                'slug' => 'starter',
                'price_monthly' => 29.00,
                'price_yearly' => 290.00,
            ]);

        $response->assertSessionHasErrors('slug');
    }

    public function test_plan_can_be_deprecated(): void
    {
        $plan = Plan::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->admin, 'admin')
            ->post("/admin/plans/{$plan->id}/deprecate");

        $response->assertRedirect();
        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'is_active' => false,
            'is_public' => false,
        ]);
    }

    public function test_plan_order_can_be_updated(): void
    {
        $plans = Plan::factory()->count(3)->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->post('/admin/plans/reorder', [
                'order' => [
                    $plans[2]->id => 1,
                    $plans[0]->id => 2,
                    $plans[1]->id => 3,
                ],
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('plans', [
            'id' => $plans[2]->id,
            'sort_order' => 1,
        ]);
    }

    public function test_billing_admin_cannot_create_plans(): void
    {
        $billingAdmin = AdminUser::factory()->create(['role' => AdminRole::BILLING]);

        $response = $this->actingAs($billingAdmin, 'admin')
            ->post('/admin/plans', [
                'name' => 'New Plan',
                'slug' => 'new-plan',
                'price_monthly' => 50.00,
                'price_yearly' => 500.00,
            ]);

        $response->assertStatus(403);
    }
}
```

---

## Unit Tests

### DashboardServiceTest.php

```php
<?php

namespace Tests\Unit\Admin\Services;

use App\Models\Firm;
use App\Services\Admin\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DashboardService::class);
    }

    public function test_get_key_metrics_returns_correct_structure(): void
    {
        Firm::factory()->count(5)->create(['status' => 'active']);

        $metrics = $this->service->getKeyMetrics();

        $this->assertArrayHasKey('active_tenants', $metrics);
        $this->assertArrayHasKey('mrr', $metrics);
        $this->assertArrayHasKey('trial_conversions', $metrics);
        $this->assertArrayHasKey('churn_rate', $metrics);
    }

    public function test_get_key_metrics_counts_active_tenants(): void
    {
        Firm::factory()->count(10)->create(['status' => 'active']);
        Firm::factory()->count(3)->create(['status' => 'suspended']);

        $metrics = $this->service->getKeyMetrics();

        $this->assertEquals(10, $metrics['active_tenants']);
    }

    public function test_get_revenue_chart_data_returns_12_months(): void
    {
        $data = $this->service->getRevenueChartData();

        $this->assertCount(12, $data);
        $this->assertArrayHasKey('month', $data[0]);
        $this->assertArrayHasKey('mrr', $data[0]);
    }

    public function test_metrics_are_cached(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(['active_tenants' => 5]);

        $this->service->getKeyMetrics();
    }

    public function test_get_attention_items_returns_trials_expiring(): void
    {
        Firm::factory()->create([
            'trial_ends_at' => now()->addDays(2),
            'subscription_status' => 'trialing',
        ]);

        $items = $this->service->getAttentionItems();

        $this->assertCount(1, $items);
        $this->assertEquals('trial_expiring', $items[0]['type']);
    }
}
```

### TenantServiceTest.php

```php
<?php

namespace Tests\Unit\Admin\Services;

use App\Models\Firm;
use App\Models\Plan;
use App\Services\Admin\TenantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TenantService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TenantService::class);
    }

    public function test_suspend_tenant_changes_status(): void
    {
        $firm = Firm::factory()->create(['status' => 'active']);

        $result = $this->service->suspendTenant($firm, 'Violation of terms');

        $this->assertTrue($result);
        $this->assertEquals('suspended', $firm->fresh()->status);
    }

    public function test_suspend_tenant_sets_reason(): void
    {
        $firm = Firm::factory()->create(['status' => 'active']);

        $this->service->suspendTenant($firm, 'Payment issues');

        $this->assertEquals('Payment issues', $firm->fresh()->suspension_reason);
    }

    public function test_reactivate_tenant_changes_status(): void
    {
        $firm = Firm::factory()->create(['status' => 'suspended']);

        $result = $this->service->reactivateTenant($firm);

        $this->assertTrue($result);
        $this->assertEquals('active', $firm->fresh()->status);
    }

    public function test_extend_trial_adds_days(): void
    {
        $originalEnd = now()->addDays(5);
        $firm = Firm::factory()->create(['trial_ends_at' => $originalEnd]);

        $this->service->extendTrial($firm, 14);

        $newEnd = $firm->fresh()->trial_ends_at;
        $this->assertTrue($newEnd->isAfter($originalEnd));
        $this->assertEquals(14, $originalEnd->diffInDays($newEnd));
    }

    public function test_change_plan_updates_firm(): void
    {
        $oldPlan = Plan::factory()->create();
        $newPlan = Plan::factory()->create();
        $firm = Firm::factory()->create(['plan_id' => $oldPlan->id]);

        $this->service->changePlan($firm, $newPlan);

        $this->assertEquals($newPlan->id, $firm->fresh()->plan_id);
    }

    public function test_get_tenant_usage_returns_correct_structure(): void
    {
        $plan = Plan::factory()->create();
        $firm = Firm::factory()->create(['plan_id' => $plan->id]);

        $usage = $this->service->getTenantUsage($firm);

        $this->assertArrayHasKey('users', $usage);
        $this->assertArrayHasKey('clients', $usage);
        $this->assertArrayHasKey('storage', $usage);
        $this->assertArrayHasKey('current', $usage['users']);
        $this->assertArrayHasKey('limit', $usage['users']);
    }
}
```

### AdminAuditServiceTest.php

```php
<?php

namespace Tests\Unit\Admin\Services;

use App\Models\AdminUser;
use App\Models\AdminAuditLog;
use App\Models\Firm;
use App\Enums\AdminAction;
use App\Services\Admin\AdminAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuditServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AdminAuditService $service;
    protected AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AdminAuditService::class);
        $this->admin = AdminUser::factory()->create();
    }

    public function test_log_creates_audit_record(): void
    {
        $firm = Firm::factory()->create();

        $this->service->log(
            $this->admin,
            AdminAction::UPDATE,
            $firm,
            ['old' => 'value'],
            ['new' => 'value']
        );

        $this->assertDatabaseHas('admin_audit_logs', [
            'admin_user_id' => $this->admin->id,
            'action' => AdminAction::UPDATE->value,
            'resource_type' => Firm::class,
            'resource_id' => $firm->id,
        ]);
    }

    public function test_log_stores_old_and_new_values(): void
    {
        $firm = Firm::factory()->create();

        $this->service->log(
            $this->admin,
            AdminAction::UPDATE,
            $firm,
            ['name' => 'Old Name'],
            ['name' => 'New Name']
        );

        $log = AdminAuditLog::first();
        $this->assertEquals(['name' => 'Old Name'], $log->old_values);
        $this->assertEquals(['name' => 'New Name'], $log->new_values);
    }

    public function test_log_captures_request_metadata(): void
    {
        $firm = Firm::factory()->create();

        $this->service->log($this->admin, AdminAction::VIEW, $firm);

        $log = AdminAuditLog::first();
        $this->assertNotNull($log->ip_address);
    }

    public function test_get_logs_for_resource_returns_filtered_results(): void
    {
        $firm = Firm::factory()->create();
        $otherFirm = Firm::factory()->create();

        $this->service->log($this->admin, AdminAction::VIEW, $firm);
        $this->service->log($this->admin, AdminAction::UPDATE, $firm);
        $this->service->log($this->admin, AdminAction::VIEW, $otherFirm);

        $logs = $this->service->getLogsForResource($firm);

        $this->assertCount(2, $logs);
    }
}
```

---

## Test Factories

### AdminUserFactory.php

```php
<?php

namespace Database\Factories;

use App\Enums\AdminRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'role' => AdminRole::ADMIN,
            'status' => 'active',
            'two_factor_enabled' => false,
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => AdminRole::SUPER_ADMIN,
        ]);
    }

    public function support(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => AdminRole::SUPPORT,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }

    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_enabled' => true,
            'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        ]);
    }
}
```

---

## Browser Tests (Dusk)

### DashboardTest.php

```php
<?php

namespace Tests\Browser\Admin;

use App\Models\AdminUser;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DashboardTest extends DuskTestCase
{
    public function test_dashboard_displays_metrics_cards(): void
    {
        $admin = AdminUser::factory()->create();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/dashboard')
                ->assertSee('Active Tenants')
                ->assertSee('Monthly Recurring Revenue')
                ->assertSee('Trial Conversion Rate')
                ->assertSee('Churn Rate');
        });
    }

    public function test_dashboard_revenue_chart_loads(): void
    {
        $admin = AdminUser::factory()->create();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/dashboard')
                ->waitFor('.recharts-wrapper')
                ->assertPresent('.recharts-wrapper');
        });
    }
}
```

---

## Test Helpers

### AdminTestCase.php

```php
<?php

namespace Tests;

use App\Models\AdminUser;
use App\Enums\AdminRole;

abstract class AdminTestCase extends TestCase
{
    protected AdminUser $superAdmin;
    protected AdminUser $admin;
    protected AdminUser $support;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = AdminUser::factory()->create(['role' => AdminRole::SUPER_ADMIN]);
        $this->admin = AdminUser::factory()->create(['role' => AdminRole::ADMIN]);
        $this->support = AdminUser::factory()->create(['role' => AdminRole::SUPPORT]);
    }

    protected function actingAsSuperAdmin(): static
    {
        $this->actingAs($this->superAdmin, 'admin');
        return $this;
    }

    protected function actingAsAdmin(): static
    {
        $this->actingAs($this->admin, 'admin');
        return $this;
    }

    protected function actingAsSupport(): static
    {
        $this->actingAs($this->support, 'admin');
        return $this;
    }
}
```

---

## Coverage Requirements

| Category | Minimum Coverage |
|----------|------------------|
| Controllers | 80% |
| Services | 90% |
| Policies | 100% |
| Models | 70% |

## Running Tests

```bash
php artisan test --filter=Admin

php artisan test --testsuite=Feature --filter=Admin

php artisan test --testsuite=Unit --filter=Admin

php artisan dusk --filter=Admin

php artisan test --coverage --min=80
```
