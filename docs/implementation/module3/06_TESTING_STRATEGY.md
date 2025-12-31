# Module 3: Testing Strategy

## Overview

This document outlines the testing approach for Module 3 (Firm Dashboard & Client Management), covering unit tests, feature tests, and browser tests.

---

## Test Structure

```
tests/
├── Feature/App/
│   ├── DashboardTest.php
│   ├── DashboardMetricsTest.php
│   ├── DashboardActivityTest.php
│   ├── ClientTest.php
│   ├── ClientAssignmentTest.php
│   ├── ClientNoteTest.php
│   └── ClientImportTest.php
├── Unit/App/
│   ├── Services/
│   │   ├── DashboardServiceTest.php
│   │   ├── DashboardMetricsServiceTest.php
│   │   ├── ClientServiceTest.php
│   │   └── ActivityServiceTest.php
│   └── Policies/
│       └── TaxClientPolicyTest.php
└── Browser/App/
    ├── DashboardTest.php
    ├── ClientListTest.php
    └── ClientWizardTest.php
```

---

## Feature Tests

### DashboardTest.php

```php
<?php

namespace Tests\Feature\App;

use App\Models\Firm;
use App\Models\FirmUser;
use App\Models\TaxClient;
use App\Enums\FirmRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected Firm $firm;
    protected FirmUser $partner;
    protected FirmUser $associate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->firm = Firm::factory()->create();
        $this->partner = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::PARTNER,
        ]);
        $this->associate = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::ASSOCIATE,
        ]);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get('/app/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_partner_can_view_dashboard(): void
    {
        $response = $this->actingAs($this->partner, 'firm')
            ->get('/app/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('App/Dashboard/Index')
            ->has('user')
            ->has('metrics')
            ->has('attentionItems')
            ->has('recentActivity')
            ->has('recentClients')
            ->has('quickLinks')
        );
    }

    public function test_associate_can_view_dashboard(): void
    {
        $response = $this->actingAs($this->associate, 'firm')
            ->get('/app/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('App/Dashboard/Index')
        );
    }

    public function test_dashboard_shows_role_appropriate_metrics(): void
    {
        TaxClient::factory()->count(10)->create(['firm_id' => $this->firm->id]);

        $response = $this->actingAs($this->partner, 'firm')
            ->get('/app/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->where('metrics.activeClients.value', 10)
        );
    }

    public function test_dashboard_shows_empty_state_for_new_firm(): void
    {
        $newFirm = Firm::factory()->create();
        $newUser = FirmUser::factory()->create([
            'firm_id' => $newFirm->id,
            'role' => FirmRole::PARTNER,
        ]);

        $response = $this->actingAs($newUser, 'firm')
            ->get('/app/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->where('emptyState.type', 'new_firm')
        );
    }

    public function test_dashboard_shows_empty_state_for_new_associate(): void
    {
        TaxClient::factory()->count(5)->create(['firm_id' => $this->firm->id]);
        $newAssociate = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::ASSOCIATE,
        ]);

        $response = $this->actingAs($newAssociate, 'firm')
            ->get('/app/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->where('emptyState.type', 'no_assignments')
        );
    }

    public function test_dashboard_displays_greeting_based_on_time(): void
    {
        $response = $this->actingAs($this->partner, 'firm')
            ->get('/app/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->has('user.name')
        );
    }
}
```

### DashboardMetricsTest.php

```php
<?php

namespace Tests\Feature\App;

use App\Models\Firm;
use App\Models\FirmUser;
use App\Models\TaxClient;
use App\Models\ClientUserAssignment;
use App\Enums\FirmRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    protected Firm $firm;
    protected FirmUser $partner;
    protected FirmUser $manager;
    protected FirmUser $associate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->firm = Firm::factory()->create();
        $this->partner = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::PARTNER,
        ]);
        $this->manager = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::MANAGER,
        ]);
        $this->associate = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::ASSOCIATE,
        ]);
    }

    public function test_metrics_endpoint_returns_json(): void
    {
        $response = $this->actingAs($this->partner, 'firm')
            ->getJson('/app/dashboard/metrics');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'activeClients',
            'reportsThisMonth',
            'pendingApprovals',
            'teamActivity',
        ]);
    }

    public function test_partner_sees_firm_wide_metrics(): void
    {
        TaxClient::factory()->count(15)->create([
            'firm_id' => $this->firm->id,
            'status' => 'active',
        ]);
        TaxClient::factory()->count(5)->create([
            'firm_id' => $this->firm->id,
            'status' => 'archived',
        ]);

        $response = $this->actingAs($this->partner, 'firm')
            ->getJson('/app/dashboard/metrics');

        $response->assertJson([
            'activeClients' => [
                'value' => 15,
            ],
        ]);
    }

    public function test_associate_sees_assigned_clients_only(): void
    {
        $assignedClients = TaxClient::factory()->count(3)->create([
            'firm_id' => $this->firm->id,
        ]);
        TaxClient::factory()->count(7)->create([
            'firm_id' => $this->firm->id,
        ]);

        foreach ($assignedClients as $client) {
            ClientUserAssignment::factory()->create([
                'tax_client_id' => $client->id,
                'firm_user_id' => $this->associate->id,
            ]);
        }

        $response = $this->actingAs($this->associate, 'firm')
            ->getJson('/app/dashboard/metrics');

        $response->assertJson([
            'myClients' => [
                'value' => 3,
            ],
        ]);
    }

    public function test_metrics_include_trend_indicators(): void
    {
        TaxClient::factory()->count(5)->create([
            'firm_id' => $this->firm->id,
            'created_at' => now()->subMonth(),
        ]);
        TaxClient::factory()->count(3)->create([
            'firm_id' => $this->firm->id,
        ]);

        $response = $this->actingAs($this->partner, 'firm')
            ->getJson('/app/dashboard/metrics');

        $response->assertJsonStructure([
            'activeClients' => [
                'value',
                'trend',
                'trendValue',
            ],
        ]);
    }

    public function test_manager_sees_team_metrics(): void
    {
        $teamMember = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::ASSOCIATE,
        ]);

        $clients = TaxClient::factory()->count(5)->create([
            'firm_id' => $this->firm->id,
        ]);

        foreach ($clients as $client) {
            ClientUserAssignment::factory()->create([
                'tax_client_id' => $client->id,
                'firm_user_id' => $teamMember->id,
            ]);
        }

        $response = $this->actingAs($this->manager, 'firm')
            ->getJson('/app/dashboard/metrics');

        $response->assertJsonStructure([
            'activeClients',
            'myTeamsReports',
            'pendingApprovals',
            'calculationsThisWeek',
        ]);
    }
}
```

### DashboardActivityTest.php

```php
<?php

namespace Tests\Feature\App;

use App\Models\Firm;
use App\Models\FirmUser;
use App\Models\TaxClient;
use App\Models\ActivityLog;
use App\Enums\FirmRole;
use App\Enums\ActivityAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardActivityTest extends TestCase
{
    use RefreshDatabase;

    protected Firm $firm;
    protected FirmUser $partner;
    protected FirmUser $associate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->firm = Firm::factory()->create();
        $this->partner = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::PARTNER,
        ]);
        $this->associate = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::ASSOCIATE,
        ]);
    }

    public function test_activity_feed_endpoint_returns_paginated_results(): void
    {
        $client = TaxClient::factory()->create(['firm_id' => $this->firm->id]);

        ActivityLog::factory()->count(25)->create([
            'firm_id' => $this->firm->id,
            'firm_user_id' => $this->partner->id,
            'loggable_type' => TaxClient::class,
            'loggable_id' => $client->id,
        ]);

        $response = $this->actingAs($this->partner, 'firm')
            ->getJson('/app/dashboard/activity');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'action',
                    'description',
                    'user',
                    'subject',
                    'created_at',
                ],
            ],
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ],
        ]);
    }

    public function test_partner_sees_all_firm_activity(): void
    {
        $client = TaxClient::factory()->create(['firm_id' => $this->firm->id]);

        ActivityLog::factory()->create([
            'firm_id' => $this->firm->id,
            'firm_user_id' => $this->partner->id,
            'loggable_type' => TaxClient::class,
            'loggable_id' => $client->id,
            'action' => ActivityAction::CLIENT_CREATED,
        ]);
        ActivityLog::factory()->create([
            'firm_id' => $this->firm->id,
            'firm_user_id' => $this->associate->id,
            'loggable_type' => TaxClient::class,
            'loggable_id' => $client->id,
            'action' => ActivityAction::CLIENT_UPDATED,
        ]);

        $response = $this->actingAs($this->partner, 'firm')
            ->getJson('/app/dashboard/activity');

        $response->assertJsonCount(2, 'data');
    }

    public function test_associate_sees_limited_activity(): void
    {
        $assignedClient = TaxClient::factory()->create(['firm_id' => $this->firm->id]);
        $otherClient = TaxClient::factory()->create(['firm_id' => $this->firm->id]);

        ClientUserAssignment::factory()->create([
            'tax_client_id' => $assignedClient->id,
            'firm_user_id' => $this->associate->id,
        ]);

        ActivityLog::factory()->create([
            'firm_id' => $this->firm->id,
            'loggable_type' => TaxClient::class,
            'loggable_id' => $assignedClient->id,
        ]);
        ActivityLog::factory()->create([
            'firm_id' => $this->firm->id,
            'loggable_type' => TaxClient::class,
            'loggable_id' => $otherClient->id,
        ]);

        $response = $this->actingAs($this->associate, 'firm')
            ->getJson('/app/dashboard/activity');

        $response->assertJsonCount(1, 'data');
    }

    public function test_activity_feed_supports_cursor_pagination(): void
    {
        $client = TaxClient::factory()->create(['firm_id' => $this->firm->id]);

        ActivityLog::factory()->count(30)->create([
            'firm_id' => $this->firm->id,
            'loggable_type' => TaxClient::class,
            'loggable_id' => $client->id,
        ]);

        $response = $this->actingAs($this->partner, 'firm')
            ->getJson('/app/dashboard/activity?cursor=1');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'next_cursor',
            'prev_cursor',
        ]);
    }

    public function test_activity_tracks_client_creation(): void
    {
        $response = $this->actingAs($this->partner, 'firm')
            ->post('/app/clients', [
                'entity_type' => 'company_llc',
                'name' => 'Test Company',
                'email' => 'test@example.com',
            ]);

        $this->assertDatabaseHas('activity_logs', [
            'firm_id' => $this->firm->id,
            'firm_user_id' => $this->partner->id,
            'action' => ActivityAction::CLIENT_CREATED->value,
        ]);
    }
}
```

### ClientTest.php

```php
<?php

namespace Tests\Feature\App;

use App\Models\Firm;
use App\Models\FirmUser;
use App\Models\TaxClient;
use App\Models\Industry;
use App\Models\ClientUserAssignment;
use App\Enums\FirmRole;
use App\Enums\EntityType;
use App\Enums\ClientStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    protected Firm $firm;
    protected FirmUser $partner;
    protected FirmUser $associate;
    protected FirmUser $viewer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->firm = Firm::factory()->create();
        $this->partner = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::PARTNER,
        ]);
        $this->associate = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::ASSOCIATE,
        ]);
        $this->viewer = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::VIEWER,
        ]);
    }

    public function test_client_list_requires_authentication(): void
    {
        $response = $this->get('/app/clients');

        $response->assertRedirect('/login');
    }

    public function test_partner_can_list_all_clients(): void
    {
        TaxClient::factory()->count(5)->create(['firm_id' => $this->firm->id]);

        $response = $this->actingAs($this->partner, 'firm')
            ->get('/app/clients');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('App/Clients/Index')
            ->has('clients.data', 5)
        );
    }

    public function test_associate_only_sees_assigned_clients(): void
    {
        $assignedClients = TaxClient::factory()->count(3)->create([
            'firm_id' => $this->firm->id,
        ]);
        TaxClient::factory()->count(5)->create(['firm_id' => $this->firm->id]);

        foreach ($assignedClients as $client) {
            ClientUserAssignment::factory()->create([
                'tax_client_id' => $client->id,
                'firm_user_id' => $this->associate->id,
            ]);
        }

        $response = $this->actingAs($this->associate, 'firm')
            ->get('/app/clients');

        $response->assertInertia(fn ($page) => $page
            ->has('clients.data', 3)
        );
    }

    public function test_client_list_can_be_filtered_by_status(): void
    {
        TaxClient::factory()->count(3)->create([
            'firm_id' => $this->firm->id,
            'status' => ClientStatus::ACTIVE,
        ]);
        TaxClient::factory()->count(2)->create([
            'firm_id' => $this->firm->id,
            'status' => ClientStatus::ARCHIVED,
        ]);

        $response = $this->actingAs($this->partner, 'firm')
            ->get('/app/clients?status=active');

        $response->assertInertia(fn ($page) => $page
            ->has('clients.data', 3)
        );
    }

    public function test_client_list_can_be_filtered_by_entity_type(): void
    {
        TaxClient::factory()->count(4)->create([
            'firm_id' => $this->firm->id,
            'entity_type' => EntityType::COMPANY_LLC,
        ]);
        TaxClient::factory()->count(2)->create([
            'firm_id' => $this->firm->id,
            'entity_type' => EntityType::INDIVIDUAL,
        ]);

        $response = $this->actingAs($this->partner, 'firm')
            ->get('/app/clients?entity_type=company_llc');

        $response->assertInertia(fn ($page) => $page
            ->has('clients.data', 4)
        );
    }

    public function test_client_list_can_be_searched(): void
    {
        TaxClient::factory()->create([
            'firm_id' => $this->firm->id,
            'name' => 'Alpha Corporation Ltd',
        ]);
        TaxClient::factory()->create([
            'firm_id' => $this->firm->id,
            'name' => 'Beta Industries',
        ]);

        $response = $this->actingAs($this->partner, 'firm')
            ->get('/app/clients?search=Alpha');

        $response->assertInertia(fn ($page) => $page
            ->has('clients.data', 1)
            ->where('clients.data.0.name', 'Alpha Corporation Ltd')
        );
    }

    public function test_client_list_can_be_sorted(): void
    {
        TaxClient::factory()->create([
            'firm_id' => $this->firm->id,
            'name' => 'Zebra Inc',
        ]);
        TaxClient::factory()->create([
            'firm_id' => $this->firm->id,
            'name' => 'Alpha Ltd',
        ]);

        $response = $this->actingAs($this->partner, 'firm')
            ->get('/app/clients?sort=name&direction=asc');

        $response->assertInertia(fn ($page) => $page
            ->where('clients.data.0.name', 'Alpha Ltd')
            ->where('clients.data.1.name', 'Zebra Inc')
        );
    }

    public function test_client_create_page_is_displayed(): void
    {
        $response = $this->actingAs($this->partner, 'firm')
            ->get('/app/clients/create');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('App/Clients/Create')
            ->has('entityTypes')
            ->has('industries')
            ->has('users')
        );
    }

    public function test_viewer_cannot_access_create_page(): void
    {
        $response = $this->actingAs($this->viewer, 'firm')
            ->get('/app/clients/create');

        $response->assertStatus(403);
    }

    public function test_partner_can_create_company_client(): void
    {
        $industry = Industry::factory()->create();

        $response = $this->actingAs($this->partner, 'firm')
            ->post('/app/clients', [
                'entity_type' => 'company_llc',
                'name' => 'Test Company Ltd',
                'trading_name' => 'TestCo',
                'tin' => '1234567890',
                'cac_number' => 'RC123456',
                'incorporation_date' => '2020-01-15',
                'fiscal_year_end' => '12-31',
                'industry_id' => $industry->id,
                'company_size' => 'small',
                'email' => 'info@testcompany.com',
                'phone' => '+2341234567890',
                'address' => '123 Test Street, Lagos',
                'state' => 'Lagos',
                'lga' => 'Victoria Island',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tax_clients', [
            'firm_id' => $this->firm->id,
            'name' => 'Test Company Ltd',
            'entity_type' => 'company_llc',
            'created_by' => $this->partner->id,
        ]);
    }

    public function test_partner_can_create_individual_client(): void
    {
        $response = $this->actingAs($this->partner, 'firm')
            ->post('/app/clients', [
                'entity_type' => 'individual',
                'name' => 'John Doe',
                'tin' => '1234567890',
                'nin' => '12345678901',
                'date_of_birth' => '1985-05-15',
                'marital_status' => 'married',
                'employment_status' => 'employed',
                'email' => 'john@example.com',
                'phone' => '+2341234567890',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tax_clients', [
            'firm_id' => $this->firm->id,
            'name' => 'John Doe',
            'entity_type' => 'individual',
            'marital_status' => 'married',
        ]);
    }

    public function test_client_creation_validates_required_fields(): void
    {
        $response = $this->actingAs($this->partner, 'firm')
            ->post('/app/clients', []);

        $response->assertSessionHasErrors(['entity_type', 'name']);
    }

    public function test_client_creation_validates_entity_specific_fields(): void
    {
        $response = $this->actingAs($this->partner, 'firm')
            ->post('/app/clients', [
                'entity_type' => 'company_llc',
                'name' => 'Test Company',
            ]);

        $response->assertSessionHasErrors(['cac_number']);
    }

    public function test_partner_can_view_client_detail(): void
    {
        $client = TaxClient::factory()->create(['firm_id' => $this->firm->id]);

        $response = $this->actingAs($this->partner, 'firm')
            ->get("/app/clients/{$client->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('App/Clients/Show')
            ->where('client.id', $client->id)
            ->has('client.assignments')
            ->has('client.notes')
            ->has('tabs')
        );
    }

    public function test_associate_cannot_view_unassigned_client(): void
    {
        $client = TaxClient::factory()->create(['firm_id' => $this->firm->id]);

        $response = $this->actingAs($this->associate, 'firm')
            ->get("/app/clients/{$client->id}");

        $response->assertStatus(403);
    }

    public function test_partner_can_update_client(): void
    {
        $client = TaxClient::factory()->create([
            'firm_id' => $this->firm->id,
            'entity_type' => EntityType::COMPANY_LLC,
        ]);

        $response = $this->actingAs($this->partner, 'firm')
            ->put("/app/clients/{$client->id}", [
                'entity_type' => 'company_llc',
                'name' => 'Updated Company Name',
                'email' => 'updated@example.com',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tax_clients', [
            'id' => $client->id,
            'name' => 'Updated Company Name',
        ]);
    }

    public function test_viewer_cannot_update_client(): void
    {
        $client = TaxClient::factory()->create(['firm_id' => $this->firm->id]);

        $response = $this->actingAs($this->viewer, 'firm')
            ->put("/app/clients/{$client->id}", [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(403);
    }

    public function test_partner_can_archive_client(): void
    {
        $client = TaxClient::factory()->create([
            'firm_id' => $this->firm->id,
            'status' => ClientStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->partner, 'firm')
            ->delete("/app/clients/{$client->id}");

        $response->assertRedirect();
        $this->assertDatabaseHas('tax_clients', [
            'id' => $client->id,
            'status' => 'archived',
        ]);
    }

    public function test_associate_cannot_archive_client(): void
    {
        $client = TaxClient::factory()->create(['firm_id' => $this->firm->id]);
        ClientUserAssignment::factory()->create([
            'tax_client_id' => $client->id,
            'firm_user_id' => $this->associate->id,
        ]);

        $response = $this->actingAs($this->associate, 'firm')
            ->delete("/app/clients/{$client->id}");

        $response->assertStatus(403);
    }

    public function test_partner_can_restore_archived_client(): void
    {
        $client = TaxClient::factory()->create([
            'firm_id' => $this->firm->id,
            'status' => ClientStatus::ARCHIVED,
        ]);

        $response = $this->actingAs($this->partner, 'firm')
            ->post("/app/clients/{$client->id}/restore");

        $response->assertRedirect();
        $this->assertDatabaseHas('tax_clients', [
            'id' => $client->id,
            'status' => 'active',
        ]);
    }

    public function test_bulk_archive_clients(): void
    {
        $clients = TaxClient::factory()->count(3)->create([
            'firm_id' => $this->firm->id,
            'status' => ClientStatus::ACTIVE,
        ]);

        $response = $this->actingAs($this->partner, 'firm')
            ->post('/app/clients/bulk', [
                'action' => 'archive',
                'ids' => $clients->pluck('id')->toArray(),
            ]);

        $response->assertRedirect();
        foreach ($clients as $client) {
            $this->assertDatabaseHas('tax_clients', [
                'id' => $client->id,
                'status' => 'archived',
            ]);
        }
    }

    public function test_client_access_is_logged(): void
    {
        $client = TaxClient::factory()->create(['firm_id' => $this->firm->id]);

        $this->actingAs($this->partner, 'firm')
            ->get("/app/clients/{$client->id}");

        $this->assertDatabaseHas('client_access_logs', [
            'tax_client_id' => $client->id,
            'firm_user_id' => $this->partner->id,
        ]);
    }
}
```

### ClientAssignmentTest.php

```php
<?php

namespace Tests\Feature\App;

use App\Models\Firm;
use App\Models\FirmUser;
use App\Models\TaxClient;
use App\Models\ClientUserAssignment;
use App\Enums\FirmRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected Firm $firm;
    protected FirmUser $partner;
    protected FirmUser $manager;
    protected FirmUser $associate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->firm = Firm::factory()->create();
        $this->partner = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::PARTNER,
        ]);
        $this->manager = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::MANAGER,
        ]);
        $this->associate = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::ASSOCIATE,
        ]);
    }

    public function test_partner_can_view_client_assignments(): void
    {
        $client = TaxClient::factory()->create(['firm_id' => $this->firm->id]);
        ClientUserAssignment::factory()->create([
            'tax_client_id' => $client->id,
            'firm_user_id' => $this->associate->id,
        ]);

        $response = $this->actingAs($this->partner, 'firm')
            ->get("/app/clients/{$client->id}/assignments");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'assignments' => [
                '*' => ['id', 'user', 'assigned_at'],
            ],
            'available_users',
        ]);
    }

    public function test_partner_can_assign_user_to_client(): void
    {
        $client = TaxClient::factory()->create(['firm_id' => $this->firm->id]);

        $response = $this->actingAs($this->partner, 'firm')
            ->post("/app/clients/{$client->id}/assignments", [
                'user_id' => $this->associate->id,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('client_user_assignments', [
            'tax_client_id' => $client->id,
            'firm_user_id' => $this->associate->id,
        ]);
    }

    public function test_manager_can_assign_user_to_client(): void
    {
        $client = TaxClient::factory()->create(['firm_id' => $this->firm->id]);

        $response = $this->actingAs($this->manager, 'firm')
            ->post("/app/clients/{$client->id}/assignments", [
                'user_id' => $this->associate->id,
            ]);

        $response->assertStatus(201);
    }

    public function test_associate_cannot_assign_users(): void
    {
        $client = TaxClient::factory()->create(['firm_id' => $this->firm->id]);
        ClientUserAssignment::factory()->create([
            'tax_client_id' => $client->id,
            'firm_user_id' => $this->associate->id,
        ]);

        $anotherAssociate = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::ASSOCIATE,
        ]);

        $response = $this->actingAs($this->associate, 'firm')
            ->post("/app/clients/{$client->id}/assignments", [
                'user_id' => $anotherAssociate->id,
            ]);

        $response->assertStatus(403);
    }

    public function test_cannot_assign_same_user_twice(): void
    {
        $client = TaxClient::factory()->create(['firm_id' => $this->firm->id]);
        ClientUserAssignment::factory()->create([
            'tax_client_id' => $client->id,
            'firm_user_id' => $this->associate->id,
        ]);

        $response = $this->actingAs($this->partner, 'firm')
            ->post("/app/clients/{$client->id}/assignments", [
                'user_id' => $this->associate->id,
            ]);

        $response->assertSessionHasErrors('user_id');
    }

    public function test_partner_can_remove_assignment(): void
    {
        $client = TaxClient::factory()->create(['firm_id' => $this->firm->id]);
        $assignment = ClientUserAssignment::factory()->create([
            'tax_client_id' => $client->id,
            'firm_user_id' => $this->associate->id,
        ]);

        $response = $this->actingAs($this->partner, 'firm')
            ->delete("/app/clients/{$client->id}/assignments/{$assignment->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('client_user_assignments', [
            'id' => $assignment->id,
        ]);
    }

    public function test_bulk_assign_multiple_users(): void
    {
        $client = TaxClient::factory()->create(['firm_id' => $this->firm->id]);
        $associates = FirmUser::factory()->count(3)->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::ASSOCIATE,
        ]);

        $response = $this->actingAs($this->partner, 'firm')
            ->post("/app/clients/{$client->id}/assignments/bulk", [
                'user_ids' => $associates->pluck('id')->toArray(),
            ]);

        $response->assertStatus(201);
        $this->assertEquals(3, $client->assignments()->count());
    }

    public function test_bulk_assign_from_client_list(): void
    {
        $clients = TaxClient::factory()->count(3)->create(['firm_id' => $this->firm->id]);

        $response = $this->actingAs($this->partner, 'firm')
            ->post('/app/clients/bulk', [
                'action' => 'assign',
                'ids' => $clients->pluck('id')->toArray(),
                'user_id' => $this->associate->id,
            ]);

        $response->assertRedirect();
        foreach ($clients as $client) {
            $this->assertDatabaseHas('client_user_assignments', [
                'tax_client_id' => $client->id,
                'firm_user_id' => $this->associate->id,
            ]);
        }
    }

    public function test_assignment_creates_activity_log(): void
    {
        $client = TaxClient::factory()->create(['firm_id' => $this->firm->id]);

        $this->actingAs($this->partner, 'firm')
            ->post("/app/clients/{$client->id}/assignments", [
                'user_id' => $this->associate->id,
            ]);

        $this->assertDatabaseHas('activity_logs', [
            'firm_id' => $this->firm->id,
            'action' => 'user_assigned',
            'loggable_type' => TaxClient::class,
            'loggable_id' => $client->id,
        ]);
    }
}
```

### ClientNoteTest.php

```php
<?php

namespace Tests\Feature\App;

use App\Models\Firm;
use App\Models\FirmUser;
use App\Models\TaxClient;
use App\Models\ClientNote;
use App\Models\ClientUserAssignment;
use App\Enums\FirmRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientNoteTest extends TestCase
{
    use RefreshDatabase;

    protected Firm $firm;
    protected FirmUser $partner;
    protected FirmUser $associate;
    protected TaxClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->firm = Firm::factory()->create();
        $this->partner = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::PARTNER,
        ]);
        $this->associate = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::ASSOCIATE,
        ]);
        $this->client = TaxClient::factory()->create(['firm_id' => $this->firm->id]);
    }

    public function test_partner_can_list_client_notes(): void
    {
        ClientNote::factory()->count(5)->create([
            'tax_client_id' => $this->client->id,
            'firm_user_id' => $this->partner->id,
        ]);

        $response = $this->actingAs($this->partner, 'firm')
            ->get("/app/clients/{$this->client->id}/notes");

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'notes');
    }

    public function test_notes_are_ordered_by_pinned_and_date(): void
    {
        ClientNote::factory()->create([
            'tax_client_id' => $this->client->id,
            'firm_user_id' => $this->partner->id,
            'is_pinned' => false,
            'created_at' => now(),
        ]);
        $pinnedNote = ClientNote::factory()->create([
            'tax_client_id' => $this->client->id,
            'firm_user_id' => $this->partner->id,
            'is_pinned' => true,
            'created_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->partner, 'firm')
            ->getJson("/app/clients/{$this->client->id}/notes");

        $response->assertJson([
            'notes' => [
                ['id' => $pinnedNote->id],
            ],
        ]);
    }

    public function test_partner_can_add_note(): void
    {
        $response = $this->actingAs($this->partner, 'firm')
            ->post("/app/clients/{$this->client->id}/notes", [
                'content' => 'This is a test note.',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('client_notes', [
            'tax_client_id' => $this->client->id,
            'firm_user_id' => $this->partner->id,
            'content' => 'This is a test note.',
        ]);
    }

    public function test_assigned_associate_can_add_note(): void
    {
        ClientUserAssignment::factory()->create([
            'tax_client_id' => $this->client->id,
            'firm_user_id' => $this->associate->id,
        ]);

        $response = $this->actingAs($this->associate, 'firm')
            ->post("/app/clients/{$this->client->id}/notes", [
                'content' => 'Associate note.',
            ]);

        $response->assertStatus(201);
    }

    public function test_unassigned_associate_cannot_add_note(): void
    {
        $response = $this->actingAs($this->associate, 'firm')
            ->post("/app/clients/{$this->client->id}/notes", [
                'content' => 'This should fail.',
            ]);

        $response->assertStatus(403);
    }

    public function test_note_author_can_update_note(): void
    {
        $note = ClientNote::factory()->create([
            'tax_client_id' => $this->client->id,
            'firm_user_id' => $this->partner->id,
            'content' => 'Original content',
        ]);

        $response = $this->actingAs($this->partner, 'firm')
            ->put("/app/clients/{$this->client->id}/notes/{$note->id}", [
                'content' => 'Updated content',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('client_notes', [
            'id' => $note->id,
            'content' => 'Updated content',
        ]);
    }

    public function test_other_user_cannot_update_note(): void
    {
        ClientUserAssignment::factory()->create([
            'tax_client_id' => $this->client->id,
            'firm_user_id' => $this->associate->id,
        ]);

        $note = ClientNote::factory()->create([
            'tax_client_id' => $this->client->id,
            'firm_user_id' => $this->partner->id,
        ]);

        $response = $this->actingAs($this->associate, 'firm')
            ->put("/app/clients/{$this->client->id}/notes/{$note->id}", [
                'content' => 'Trying to update',
            ]);

        $response->assertStatus(403);
    }

    public function test_partner_can_toggle_note_pin(): void
    {
        $note = ClientNote::factory()->create([
            'tax_client_id' => $this->client->id,
            'firm_user_id' => $this->associate->id,
            'is_pinned' => false,
        ]);

        $response = $this->actingAs($this->partner, 'firm')
            ->post("/app/clients/{$this->client->id}/notes/{$note->id}/toggle-pin");

        $response->assertStatus(200);
        $this->assertDatabaseHas('client_notes', [
            'id' => $note->id,
            'is_pinned' => true,
        ]);
    }

    public function test_note_author_can_delete_note(): void
    {
        $note = ClientNote::factory()->create([
            'tax_client_id' => $this->client->id,
            'firm_user_id' => $this->partner->id,
        ]);

        $response = $this->actingAs($this->partner, 'firm')
            ->delete("/app/clients/{$this->client->id}/notes/{$note->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('client_notes', ['id' => $note->id]);
    }

    public function test_note_content_is_required(): void
    {
        $response = $this->actingAs($this->partner, 'firm')
            ->post("/app/clients/{$this->client->id}/notes", [
                'content' => '',
            ]);

        $response->assertSessionHasErrors('content');
    }
}
```

### ClientImportTest.php

```php
<?php

namespace Tests\Feature\App;

use App\Models\Firm;
use App\Models\FirmUser;
use App\Enums\FirmRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClientImportTest extends TestCase
{
    use RefreshDatabase;

    protected Firm $firm;
    protected FirmUser $partner;
    protected FirmUser $associate;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->firm = Firm::factory()->create();
        $this->partner = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::PARTNER,
        ]);
        $this->associate = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::ASSOCIATE,
        ]);
    }

    public function test_import_page_is_displayed(): void
    {
        $response = $this->actingAs($this->partner, 'firm')
            ->get('/app/clients/import');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('App/Clients/Import')
        );
    }

    public function test_associate_cannot_access_import(): void
    {
        $response = $this->actingAs($this->associate, 'firm')
            ->get('/app/clients/import');

        $response->assertStatus(403);
    }

    public function test_can_upload_csv_file(): void
    {
        $csv = "name,email,entity_type,tin\n";
        $csv .= "Test Company,test@example.com,company_llc,1234567890\n";
        $csv .= "John Doe,john@example.com,individual,0987654321\n";

        $file = UploadedFile::fake()->createWithContent('clients.csv', $csv);

        $response = $this->actingAs($this->partner, 'firm')
            ->post('/app/clients/import/upload', [
                'file' => $file,
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'import_id',
            'columns',
            'sample_data',
            'total_rows',
        ]);
    }

    public function test_can_upload_xlsx_file(): void
    {
        $file = UploadedFile::fake()->create('clients.xlsx', 100, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $response = $this->actingAs($this->partner, 'firm')
            ->post('/app/clients/import/upload', [
                'file' => $file,
            ]);

        $response->assertStatus(200);
    }

    public function test_rejects_invalid_file_type(): void
    {
        $file = UploadedFile::fake()->create('clients.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->partner, 'firm')
            ->post('/app/clients/import/upload', [
                'file' => $file,
            ]);

        $response->assertSessionHasErrors('file');
    }

    public function test_rejects_oversized_file(): void
    {
        $file = UploadedFile::fake()->create('clients.csv', 6000);

        $response = $this->actingAs($this->partner, 'firm')
            ->post('/app/clients/import/upload', [
                'file' => $file,
            ]);

        $response->assertSessionHasErrors('file');
    }

    public function test_can_validate_column_mapping(): void
    {
        $csv = "company_name,contact_email,type,tax_id\n";
        $csv .= "Test Company,test@example.com,company_llc,1234567890\n";

        $file = UploadedFile::fake()->createWithContent('clients.csv', $csv);

        $uploadResponse = $this->actingAs($this->partner, 'firm')
            ->post('/app/clients/import/upload', ['file' => $file]);

        $importId = $uploadResponse->json('import_id');

        $response = $this->actingAs($this->partner, 'firm')
            ->post('/app/clients/import/mapping', [
                'import_id' => $importId,
                'mapping' => [
                    'company_name' => 'name',
                    'contact_email' => 'email',
                    'type' => 'entity_type',
                    'tax_id' => 'tin',
                ],
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'valid',
            'errors',
            'warnings',
        ]);
    }

    public function test_can_preview_import(): void
    {
        $csv = "name,email,entity_type,tin\n";
        $csv .= "Test Company,test@example.com,company_llc,1234567890\n";
        $csv .= "Invalid Entry,,unknown,\n";

        $file = UploadedFile::fake()->createWithContent('clients.csv', $csv);

        $uploadResponse = $this->actingAs($this->partner, 'firm')
            ->post('/app/clients/import/upload', ['file' => $file]);

        $importId = $uploadResponse->json('import_id');

        $this->actingAs($this->partner, 'firm')
            ->post('/app/clients/import/mapping', [
                'import_id' => $importId,
                'mapping' => [
                    'name' => 'name',
                    'email' => 'email',
                    'entity_type' => 'entity_type',
                    'tin' => 'tin',
                ],
            ]);

        $response = $this->actingAs($this->partner, 'firm')
            ->get("/app/clients/import/{$importId}/preview");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'valid_rows',
            'invalid_rows',
            'total_rows',
            'preview',
        ]);
    }

    public function test_can_process_import(): void
    {
        $csv = "name,email,entity_type,tin\n";
        $csv .= "Test Company,test@example.com,company_llc,1234567890\n";
        $csv .= "Another Company,another@example.com,company_llc,0987654321\n";

        $file = UploadedFile::fake()->createWithContent('clients.csv', $csv);

        $uploadResponse = $this->actingAs($this->partner, 'firm')
            ->post('/app/clients/import/upload', ['file' => $file]);

        $importId = $uploadResponse->json('import_id');

        $this->actingAs($this->partner, 'firm')
            ->post('/app/clients/import/mapping', [
                'import_id' => $importId,
                'mapping' => [
                    'name' => 'name',
                    'email' => 'email',
                    'entity_type' => 'entity_type',
                    'tin' => 'tin',
                ],
            ]);

        $response = $this->actingAs($this->partner, 'firm')
            ->post("/app/clients/import/{$importId}/process");

        $response->assertStatus(200);
        $response->assertJson([
            'imported' => 2,
            'failed' => 0,
        ]);

        $this->assertDatabaseHas('tax_clients', [
            'firm_id' => $this->firm->id,
            'name' => 'Test Company',
        ]);
        $this->assertDatabaseHas('tax_clients', [
            'firm_id' => $this->firm->id,
            'name' => 'Another Company',
        ]);
    }

    public function test_import_skips_duplicates(): void
    {
        TaxClient::factory()->create([
            'firm_id' => $this->firm->id,
            'tin' => '1234567890',
        ]);

        $csv = "name,email,entity_type,tin\n";
        $csv .= "Duplicate Company,dup@example.com,company_llc,1234567890\n";

        $file = UploadedFile::fake()->createWithContent('clients.csv', $csv);

        $uploadResponse = $this->actingAs($this->partner, 'firm')
            ->post('/app/clients/import/upload', ['file' => $file]);

        $importId = $uploadResponse->json('import_id');

        $this->actingAs($this->partner, 'firm')
            ->post('/app/clients/import/mapping', [
                'import_id' => $importId,
                'mapping' => [
                    'name' => 'name',
                    'email' => 'email',
                    'entity_type' => 'entity_type',
                    'tin' => 'tin',
                ],
            ]);

        $response = $this->actingAs($this->partner, 'firm')
            ->post("/app/clients/import/{$importId}/process");

        $response->assertJson([
            'imported' => 0,
            'failed' => 1,
        ]);
    }

    public function test_can_download_import_template(): void
    {
        $response = $this->actingAs($this->partner, 'firm')
            ->get('/app/clients/import/template');

        $response->assertStatus(200);
        $response->assertDownload('client_import_template.csv');
    }

    public function test_import_creates_activity_log(): void
    {
        $csv = "name,email,entity_type,tin\n";
        $csv .= "Test Company,test@example.com,company_llc,1234567890\n";

        $file = UploadedFile::fake()->createWithContent('clients.csv', $csv);

        $uploadResponse = $this->actingAs($this->partner, 'firm')
            ->post('/app/clients/import/upload', ['file' => $file]);

        $importId = $uploadResponse->json('import_id');

        $this->actingAs($this->partner, 'firm')
            ->post('/app/clients/import/mapping', [
                'import_id' => $importId,
                'mapping' => [
                    'name' => 'name',
                    'email' => 'email',
                    'entity_type' => 'entity_type',
                    'tin' => 'tin',
                ],
            ]);

        $this->actingAs($this->partner, 'firm')
            ->post("/app/clients/import/{$importId}/process");

        $this->assertDatabaseHas('activity_logs', [
            'firm_id' => $this->firm->id,
            'firm_user_id' => $this->partner->id,
            'action' => 'clients_imported',
        ]);
    }
}
```

---

## Unit Tests

### DashboardServiceTest.php

```php
<?php

namespace Tests\Unit\App\Services;

use App\Models\Firm;
use App\Models\FirmUser;
use App\Models\TaxClient;
use App\Enums\FirmRole;
use App\Services\Dashboard\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DashboardService $service;
    protected Firm $firm;
    protected FirmUser $partner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DashboardService::class);
        $this->firm = Firm::factory()->create();
        $this->partner = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::PARTNER,
        ]);
    }

    public function test_get_dashboard_data_returns_correct_structure(): void
    {
        $data = $this->service->getDashboardData($this->partner);

        $this->assertArrayHasKey('metrics', $data);
        $this->assertArrayHasKey('attentionItems', $data);
        $this->assertArrayHasKey('recentActivity', $data);
        $this->assertArrayHasKey('recentClients', $data);
        $this->assertArrayHasKey('quickLinks', $data);
    }

    public function test_returns_empty_state_for_new_firm(): void
    {
        $data = $this->service->getDashboardData($this->partner);

        $this->assertArrayHasKey('emptyState', $data);
        $this->assertEquals('new_firm', $data['emptyState']['type']);
    }

    public function test_returns_no_empty_state_for_firm_with_clients(): void
    {
        TaxClient::factory()->count(5)->create(['firm_id' => $this->firm->id]);

        $data = $this->service->getDashboardData($this->partner);

        $this->assertNull($data['emptyState']);
    }

    public function test_get_quick_links_returns_role_appropriate_links(): void
    {
        TaxClient::factory()->create(['firm_id' => $this->firm->id]);

        $links = $this->service->getQuickLinks($this->partner);

        $this->assertIsArray($links);
        $this->assertNotEmpty($links);

        $linkLabels = array_column($links, 'label');
        $this->assertContains('Add New Client', $linkLabels);
    }

    public function test_get_attention_items_prioritizes_correctly(): void
    {
        $items = $this->service->getAttentionItems($this->partner);

        if (!empty($items)) {
            $priorities = array_column($items, 'priority');
            $priorityOrder = ['high' => 1, 'medium' => 2, 'low' => 3];

            for ($i = 1; $i < count($items); $i++) {
                $this->assertLessThanOrEqual(
                    $priorityOrder[$priorities[$i]],
                    $priorityOrder[$priorities[$i - 1]]
                );
            }
        }

        $this->assertTrue(true);
    }

    public function test_dashboard_data_is_cached(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn([]);

        $this->service->getMetrics($this->partner);
    }
}
```

### DashboardMetricsServiceTest.php

```php
<?php

namespace Tests\Unit\App\Services;

use App\Models\Firm;
use App\Models\FirmUser;
use App\Models\TaxClient;
use App\Models\ClientUserAssignment;
use App\Enums\FirmRole;
use App\Services\Dashboard\DashboardMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMetricsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DashboardMetricsService $service;
    protected Firm $firm;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DashboardMetricsService::class);
        $this->firm = Firm::factory()->create();
    }

    public function test_partner_metrics_include_all_clients(): void
    {
        $partner = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::PARTNER,
        ]);

        TaxClient::factory()->count(10)->create([
            'firm_id' => $this->firm->id,
            'status' => 'active',
        ]);

        $metrics = $this->service->getMetrics($partner);

        $this->assertEquals(10, $metrics['activeClients']['value']);
    }

    public function test_associate_metrics_include_only_assigned_clients(): void
    {
        $associate = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::ASSOCIATE,
        ]);

        $assignedClients = TaxClient::factory()->count(3)->create([
            'firm_id' => $this->firm->id,
        ]);
        TaxClient::factory()->count(7)->create([
            'firm_id' => $this->firm->id,
        ]);

        foreach ($assignedClients as $client) {
            ClientUserAssignment::factory()->create([
                'tax_client_id' => $client->id,
                'firm_user_id' => $associate->id,
            ]);
        }

        $metrics = $this->service->getMetrics($associate);

        $this->assertEquals(3, $metrics['myClients']['value']);
    }

    public function test_calculates_trend_correctly(): void
    {
        $partner = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::PARTNER,
        ]);

        TaxClient::factory()->count(5)->create([
            'firm_id' => $this->firm->id,
            'created_at' => now()->subMonth(),
        ]);
        TaxClient::factory()->count(3)->create([
            'firm_id' => $this->firm->id,
        ]);

        $metrics = $this->service->getMetrics($partner);

        $this->assertArrayHasKey('trend', $metrics['activeClients']);
    }

    public function test_viewer_sees_limited_metrics(): void
    {
        $viewer = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::VIEWER,
        ]);

        $metrics = $this->service->getMetrics($viewer);

        $this->assertArrayHasKey('assignedClients', $metrics);
        $this->assertArrayNotHasKey('pendingApprovals', $metrics);
    }

    public function test_metrics_keys_match_role(): void
    {
        $roleMetricKeys = [
            FirmRole::PARTNER => ['activeClients', 'reportsThisMonth', 'pendingApprovals', 'teamActivity'],
            FirmRole::MANAGER => ['activeClients', 'myTeamsReports', 'pendingApprovals', 'calculationsThisWeek'],
            FirmRole::ASSOCIATE => ['myClients', 'myReportsThisMonth', 'pendingReview', 'calculationsThisWeek'],
            FirmRole::VIEWER => ['assignedClients', 'recentReports'],
        ];

        foreach ($roleMetricKeys as $role => $expectedKeys) {
            $user = FirmUser::factory()->create([
                'firm_id' => $this->firm->id,
                'role' => $role,
            ]);

            $metrics = $this->service->getMetrics($user);

            foreach ($expectedKeys as $key) {
                $this->assertArrayHasKey($key, $metrics, "Missing metric key '{$key}' for role {$role->value}");
            }
        }
    }
}
```

### ClientServiceTest.php

```php
<?php

namespace Tests\Unit\App\Services;

use App\Models\Firm;
use App\Models\FirmUser;
use App\Models\TaxClient;
use App\Models\Industry;
use App\Models\ClientUserAssignment;
use App\Enums\FirmRole;
use App\Enums\EntityType;
use App\Enums\ClientStatus;
use App\Services\Client\ClientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ClientService $service;
    protected Firm $firm;
    protected FirmUser $partner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ClientService::class);
        $this->firm = Firm::factory()->create();
        $this->partner = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::PARTNER,
        ]);
    }

    public function test_search_returns_paginated_results(): void
    {
        TaxClient::factory()->count(25)->create(['firm_id' => $this->firm->id]);

        $result = $this->service->search($this->partner, []);

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('meta', $result);
        $this->assertCount(15, $result['data']);
    }

    public function test_search_filters_by_status(): void
    {
        TaxClient::factory()->count(5)->create([
            'firm_id' => $this->firm->id,
            'status' => ClientStatus::ACTIVE,
        ]);
        TaxClient::factory()->count(3)->create([
            'firm_id' => $this->firm->id,
            'status' => ClientStatus::ARCHIVED,
        ]);

        $result = $this->service->search($this->partner, ['status' => 'active']);

        $this->assertCount(5, $result['data']);
    }

    public function test_search_filters_by_entity_type(): void
    {
        TaxClient::factory()->count(4)->create([
            'firm_id' => $this->firm->id,
            'entity_type' => EntityType::COMPANY_LLC,
        ]);
        TaxClient::factory()->count(2)->create([
            'firm_id' => $this->firm->id,
            'entity_type' => EntityType::INDIVIDUAL,
        ]);

        $result = $this->service->search($this->partner, ['entity_type' => 'company_llc']);

        $this->assertCount(4, $result['data']);
    }

    public function test_search_filters_by_industry(): void
    {
        $industry = Industry::factory()->create();

        TaxClient::factory()->count(3)->create([
            'firm_id' => $this->firm->id,
            'industry_id' => $industry->id,
        ]);
        TaxClient::factory()->count(2)->create([
            'firm_id' => $this->firm->id,
            'industry_id' => null,
        ]);

        $result = $this->service->search($this->partner, ['industry_id' => $industry->id]);

        $this->assertCount(3, $result['data']);
    }

    public function test_search_filters_for_associate(): void
    {
        $associate = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::ASSOCIATE,
        ]);

        $assignedClients = TaxClient::factory()->count(3)->create([
            'firm_id' => $this->firm->id,
        ]);
        TaxClient::factory()->count(5)->create(['firm_id' => $this->firm->id]);

        foreach ($assignedClients as $client) {
            ClientUserAssignment::factory()->create([
                'tax_client_id' => $client->id,
                'firm_user_id' => $associate->id,
            ]);
        }

        $result = $this->service->search($associate, []);

        $this->assertCount(3, $result['data']);
    }

    public function test_create_sets_firm_and_creator(): void
    {
        $data = [
            'entity_type' => 'company_llc',
            'name' => 'Test Company',
            'cac_number' => 'RC123456',
            'email' => 'test@example.com',
        ];

        $client = $this->service->create($this->partner, $data);

        $this->assertEquals($this->firm->id, $client->firm_id);
        $this->assertEquals($this->partner->id, $client->created_by);
    }

    public function test_update_tracks_changes(): void
    {
        $client = TaxClient::factory()->create([
            'firm_id' => $this->firm->id,
            'name' => 'Original Name',
        ]);

        $this->service->update($client, ['name' => 'Updated Name'], $this->partner);

        $this->assertDatabaseHas('activity_logs', [
            'loggable_type' => TaxClient::class,
            'loggable_id' => $client->id,
            'action' => 'client_updated',
        ]);
    }

    public function test_archive_changes_status(): void
    {
        $client = TaxClient::factory()->create([
            'firm_id' => $this->firm->id,
            'status' => ClientStatus::ACTIVE,
        ]);

        $this->service->archive($client, $this->partner);

        $this->assertEquals(ClientStatus::ARCHIVED, $client->fresh()->status);
    }

    public function test_restore_changes_status(): void
    {
        $client = TaxClient::factory()->create([
            'firm_id' => $this->firm->id,
            'status' => ClientStatus::ARCHIVED,
        ]);

        $this->service->restore($client, $this->partner);

        $this->assertEquals(ClientStatus::ACTIVE, $client->fresh()->status);
    }

    public function test_get_recently_accessed_returns_correct_clients(): void
    {
        $clients = TaxClient::factory()->count(10)->create([
            'firm_id' => $this->firm->id,
        ]);

        foreach ($clients->take(5) as $client) {
            $this->service->logAccess($client, $this->partner);
        }

        $recent = $this->service->getRecentlyAccessed($this->partner, 5);

        $this->assertCount(5, $recent);
    }
}
```

### ActivityServiceTest.php

```php
<?php

namespace Tests\Unit\App\Services;

use App\Models\Firm;
use App\Models\FirmUser;
use App\Models\TaxClient;
use App\Models\ActivityLog;
use App\Enums\FirmRole;
use App\Enums\ActivityAction;
use App\Services\Activity\ActivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ActivityService $service;
    protected Firm $firm;
    protected FirmUser $partner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ActivityService::class);
        $this->firm = Firm::factory()->create();
        $this->partner = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::PARTNER,
        ]);
    }

    public function test_log_creates_activity_record(): void
    {
        $client = TaxClient::factory()->create(['firm_id' => $this->firm->id]);

        $this->service->log(
            $this->partner,
            ActivityAction::CLIENT_CREATED,
            $client,
            'Created new client'
        );

        $this->assertDatabaseHas('activity_logs', [
            'firm_id' => $this->firm->id,
            'firm_user_id' => $this->partner->id,
            'action' => ActivityAction::CLIENT_CREATED->value,
            'loggable_type' => TaxClient::class,
            'loggable_id' => $client->id,
        ]);
    }

    public function test_log_stores_metadata(): void
    {
        $client = TaxClient::factory()->create(['firm_id' => $this->firm->id]);

        $this->service->log(
            $this->partner,
            ActivityAction::CLIENT_UPDATED,
            $client,
            'Updated client',
            ['changed_fields' => ['name', 'email']]
        );

        $log = ActivityLog::first();
        $this->assertEquals(['changed_fields' => ['name', 'email']], $log->metadata);
    }

    public function test_get_recent_returns_ordered_results(): void
    {
        $client = TaxClient::factory()->create(['firm_id' => $this->firm->id]);

        $this->service->log($this->partner, ActivityAction::CLIENT_CREATED, $client);
        sleep(1);
        $this->service->log($this->partner, ActivityAction::CLIENT_UPDATED, $client);

        $recent = $this->service->getRecent($this->partner, 10);

        $this->assertEquals(ActivityAction::CLIENT_UPDATED->value, $recent[0]['action']);
    }

    public function test_get_recent_respects_role_visibility(): void
    {
        $associate = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::ASSOCIATE,
        ]);

        $assignedClient = TaxClient::factory()->create(['firm_id' => $this->firm->id]);
        $otherClient = TaxClient::factory()->create(['firm_id' => $this->firm->id]);

        ClientUserAssignment::factory()->create([
            'tax_client_id' => $assignedClient->id,
            'firm_user_id' => $associate->id,
        ]);

        $this->service->log($this->partner, ActivityAction::CLIENT_CREATED, $assignedClient);
        $this->service->log($this->partner, ActivityAction::CLIENT_CREATED, $otherClient);

        $recent = $this->service->getRecent($associate, 10);

        $this->assertCount(1, $recent);
    }

    public function test_get_for_subject_returns_filtered_results(): void
    {
        $client1 = TaxClient::factory()->create(['firm_id' => $this->firm->id]);
        $client2 = TaxClient::factory()->create(['firm_id' => $this->firm->id]);

        $this->service->log($this->partner, ActivityAction::CLIENT_CREATED, $client1);
        $this->service->log($this->partner, ActivityAction::CLIENT_UPDATED, $client1);
        $this->service->log($this->partner, ActivityAction::CLIENT_CREATED, $client2);

        $logs = $this->service->getForSubject($client1);

        $this->assertCount(2, $logs);
    }

    public function test_bulk_log_creates_multiple_records(): void
    {
        $clients = TaxClient::factory()->count(3)->create(['firm_id' => $this->firm->id]);

        $this->service->bulkLog(
            $this->partner,
            ActivityAction::CLIENT_ARCHIVED,
            $clients,
            'Bulk archived clients'
        );

        $this->assertEquals(3, ActivityLog::count());
    }
}
```

---

## Policy Tests

### TaxClientPolicyTest.php

```php
<?php

namespace Tests\Unit\App\Policies;

use App\Models\Firm;
use App\Models\FirmUser;
use App\Models\TaxClient;
use App\Models\ClientUserAssignment;
use App\Enums\FirmRole;
use App\Policies\TaxClientPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxClientPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected TaxClientPolicy $policy;
    protected Firm $firm;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new TaxClientPolicy();
        $this->firm = Firm::factory()->create();
    }

    public function test_partner_can_view_any_client(): void
    {
        $partner = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::PARTNER,
        ]);

        $this->assertTrue($this->policy->viewAny($partner));
    }

    public function test_all_roles_can_view_any(): void
    {
        foreach (FirmRole::cases() as $role) {
            $user = FirmUser::factory()->create([
                'firm_id' => $this->firm->id,
                'role' => $role,
            ]);

            $this->assertTrue($this->policy->viewAny($user));
        }
    }

    public function test_partner_can_view_any_client_in_firm(): void
    {
        $partner = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::PARTNER,
        ]);
        $client = TaxClient::factory()->create(['firm_id' => $this->firm->id]);

        $this->assertTrue($this->policy->view($partner, $client));
    }

    public function test_manager_can_view_any_client_in_firm(): void
    {
        $manager = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::MANAGER,
        ]);
        $client = TaxClient::factory()->create(['firm_id' => $this->firm->id]);

        $this->assertTrue($this->policy->view($manager, $client));
    }

    public function test_associate_can_view_assigned_client(): void
    {
        $associate = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::ASSOCIATE,
        ]);
        $client = TaxClient::factory()->create(['firm_id' => $this->firm->id]);

        ClientUserAssignment::factory()->create([
            'tax_client_id' => $client->id,
            'firm_user_id' => $associate->id,
        ]);

        $this->assertTrue($this->policy->view($associate, $client));
    }

    public function test_associate_cannot_view_unassigned_client(): void
    {
        $associate = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::ASSOCIATE,
        ]);
        $client = TaxClient::factory()->create(['firm_id' => $this->firm->id]);

        $this->assertFalse($this->policy->view($associate, $client));
    }

    public function test_user_cannot_view_client_from_other_firm(): void
    {
        $partner = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::PARTNER,
        ]);

        $otherFirm = Firm::factory()->create();
        $client = TaxClient::factory()->create(['firm_id' => $otherFirm->id]);

        $this->assertFalse($this->policy->view($partner, $client));
    }

    public function test_partner_can_create_client(): void
    {
        $partner = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::PARTNER,
        ]);

        $this->assertTrue($this->policy->create($partner));
    }

    public function test_manager_can_create_client(): void
    {
        $manager = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::MANAGER,
        ]);

        $this->assertTrue($this->policy->create($manager));
    }

    public function test_associate_can_create_client(): void
    {
        $associate = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::ASSOCIATE,
        ]);

        $this->assertTrue($this->policy->create($associate));
    }

    public function test_viewer_cannot_create_client(): void
    {
        $viewer = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::VIEWER,
        ]);

        $this->assertFalse($this->policy->create($viewer));
    }

    public function test_partner_can_update_client(): void
    {
        $partner = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::PARTNER,
        ]);
        $client = TaxClient::factory()->create(['firm_id' => $this->firm->id]);

        $this->assertTrue($this->policy->update($partner, $client));
    }

    public function test_associate_can_update_assigned_client(): void
    {
        $associate = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::ASSOCIATE,
        ]);
        $client = TaxClient::factory()->create(['firm_id' => $this->firm->id]);

        ClientUserAssignment::factory()->create([
            'tax_client_id' => $client->id,
            'firm_user_id' => $associate->id,
        ]);

        $this->assertTrue($this->policy->update($associate, $client));
    }

    public function test_viewer_cannot_update_client(): void
    {
        $viewer = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::VIEWER,
        ]);
        $client = TaxClient::factory()->create(['firm_id' => $this->firm->id]);

        ClientUserAssignment::factory()->create([
            'tax_client_id' => $client->id,
            'firm_user_id' => $viewer->id,
        ]);

        $this->assertFalse($this->policy->update($viewer, $client));
    }

    public function test_partner_can_delete_client(): void
    {
        $partner = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::PARTNER,
        ]);
        $client = TaxClient::factory()->create(['firm_id' => $this->firm->id]);

        $this->assertTrue($this->policy->delete($partner, $client));
    }

    public function test_manager_can_delete_client(): void
    {
        $manager = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::MANAGER,
        ]);
        $client = TaxClient::factory()->create(['firm_id' => $this->firm->id]);

        $this->assertTrue($this->policy->delete($manager, $client));
    }

    public function test_associate_cannot_delete_client(): void
    {
        $associate = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::ASSOCIATE,
        ]);
        $client = TaxClient::factory()->create(['firm_id' => $this->firm->id]);

        $this->assertFalse($this->policy->delete($associate, $client));
    }

    public function test_partner_can_assign_users(): void
    {
        $partner = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::PARTNER,
        ]);
        $client = TaxClient::factory()->create(['firm_id' => $this->firm->id]);

        $this->assertTrue($this->policy->assign($partner, $client));
    }

    public function test_manager_can_assign_users(): void
    {
        $manager = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::MANAGER,
        ]);
        $client = TaxClient::factory()->create(['firm_id' => $this->firm->id]);

        $this->assertTrue($this->policy->assign($manager, $client));
    }

    public function test_associate_cannot_assign_users(): void
    {
        $associate = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::ASSOCIATE,
        ]);
        $client = TaxClient::factory()->create(['firm_id' => $this->firm->id]);

        $this->assertFalse($this->policy->assign($associate, $client));
    }
}
```

---

## Test Factories

### ActivityLogFactory.php

```php
<?php

namespace Database\Factories;

use App\Models\Firm;
use App\Models\FirmUser;
use App\Models\TaxClient;
use App\Enums\ActivityAction;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityLogFactory extends Factory
{
    public function definition(): array
    {
        $firm = Firm::factory()->create();
        $user = FirmUser::factory()->create(['firm_id' => $firm->id]);
        $client = TaxClient::factory()->create(['firm_id' => $firm->id]);

        return [
            'firm_id' => $firm->id,
            'firm_user_id' => $user->id,
            'loggable_type' => TaxClient::class,
            'loggable_id' => $client->id,
            'action' => fake()->randomElement(ActivityAction::cases()),
            'description' => fake()->sentence(),
            'metadata' => null,
            'created_at' => fake()->dateTimeThisMonth(),
        ];
    }

    public function forFirm(Firm $firm): static
    {
        return $this->state(fn (array $attributes) => [
            'firm_id' => $firm->id,
        ]);
    }

    public function byUser(FirmUser $user): static
    {
        return $this->state(fn (array $attributes) => [
            'firm_id' => $user->firm_id,
            'firm_user_id' => $user->id,
        ]);
    }

    public function forClient(TaxClient $client): static
    {
        return $this->state(fn (array $attributes) => [
            'firm_id' => $client->firm_id,
            'loggable_type' => TaxClient::class,
            'loggable_id' => $client->id,
        ]);
    }
}
```

### ClientUserAssignmentFactory.php

```php
<?php

namespace Database\Factories;

use App\Models\TaxClient;
use App\Models\FirmUser;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientUserAssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tax_client_id' => TaxClient::factory(),
            'firm_user_id' => FirmUser::factory(),
            'assigned_at' => now(),
            'assigned_by' => null,
        ];
    }

    public function forClient(TaxClient $client): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_client_id' => $client->id,
        ]);
    }

    public function forUser(FirmUser $user): static
    {
        return $this->state(fn (array $attributes) => [
            'firm_user_id' => $user->id,
        ]);
    }

    public function assignedBy(FirmUser $assigner): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_by' => $assigner->id,
        ]);
    }
}
```

### ClientNoteFactory.php

```php
<?php

namespace Database\Factories;

use App\Models\TaxClient;
use App\Models\FirmUser;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientNoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tax_client_id' => TaxClient::factory(),
            'firm_user_id' => FirmUser::factory(),
            'content' => fake()->paragraphs(2, true),
            'is_pinned' => false,
        ];
    }

    public function pinned(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_pinned' => true,
        ]);
    }

    public function forClient(TaxClient $client): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_client_id' => $client->id,
        ]);
    }

    public function byUser(FirmUser $user): static
    {
        return $this->state(fn (array $attributes) => [
            'firm_user_id' => $user->id,
        ]);
    }
}
```

---

## Browser Tests (Dusk)

### DashboardTest.php

```php
<?php

namespace Tests\Browser\App;

use App\Models\Firm;
use App\Models\FirmUser;
use App\Models\TaxClient;
use App\Enums\FirmRole;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DashboardTest extends DuskTestCase
{
    public function test_dashboard_displays_metrics_cards(): void
    {
        $firm = Firm::factory()->create();
        $partner = FirmUser::factory()->create([
            'firm_id' => $firm->id,
            'role' => FirmRole::PARTNER,
        ]);
        TaxClient::factory()->count(5)->create(['firm_id' => $firm->id]);

        $this->browse(function (Browser $browser) use ($partner) {
            $browser->loginAs($partner, 'firm')
                ->visit('/app/dashboard')
                ->assertSee('Active Clients')
                ->assertSee('5');
        });
    }

    public function test_dashboard_displays_greeting(): void
    {
        $firm = Firm::factory()->create();
        $partner = FirmUser::factory()->create([
            'firm_id' => $firm->id,
            'role' => FirmRole::PARTNER,
            'name' => 'John Doe',
        ]);

        $this->browse(function (Browser $browser) use ($partner) {
            $browser->loginAs($partner, 'firm')
                ->visit('/app/dashboard')
                ->assertSee('John');
        });
    }

    public function test_dashboard_empty_state_for_new_firm(): void
    {
        $firm = Firm::factory()->create();
        $partner = FirmUser::factory()->create([
            'firm_id' => $firm->id,
            'role' => FirmRole::PARTNER,
        ]);

        $this->browse(function (Browser $browser) use ($partner) {
            $browser->loginAs($partner, 'firm')
                ->visit('/app/dashboard')
                ->assertSee('Welcome to TaxLab')
                ->assertSee('Add Your First Client');
        });
    }

    public function test_quick_links_navigate_correctly(): void
    {
        $firm = Firm::factory()->create();
        $partner = FirmUser::factory()->create([
            'firm_id' => $firm->id,
            'role' => FirmRole::PARTNER,
        ]);
        TaxClient::factory()->create(['firm_id' => $firm->id]);

        $this->browse(function (Browser $browser) use ($partner) {
            $browser->loginAs($partner, 'firm')
                ->visit('/app/dashboard')
                ->clickLink('Add New Client')
                ->assertPathIs('/app/clients/create');
        });
    }
}
```

### ClientListTest.php

```php
<?php

namespace Tests\Browser\App;

use App\Models\Firm;
use App\Models\FirmUser;
use App\Models\TaxClient;
use App\Enums\FirmRole;
use App\Enums\EntityType;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ClientListTest extends DuskTestCase
{
    public function test_client_list_displays_clients(): void
    {
        $firm = Firm::factory()->create();
        $partner = FirmUser::factory()->create([
            'firm_id' => $firm->id,
            'role' => FirmRole::PARTNER,
        ]);
        TaxClient::factory()->count(5)->create([
            'firm_id' => $firm->id,
            'entity_type' => EntityType::COMPANY_LLC,
        ]);

        $this->browse(function (Browser $browser) use ($partner) {
            $browser->loginAs($partner, 'firm')
                ->visit('/app/clients')
                ->assertSee('Clients')
                ->waitFor('table')
                ->assertPresent('table tbody tr');
        });
    }

    public function test_client_list_search_works(): void
    {
        $firm = Firm::factory()->create();
        $partner = FirmUser::factory()->create([
            'firm_id' => $firm->id,
            'role' => FirmRole::PARTNER,
        ]);
        TaxClient::factory()->create([
            'firm_id' => $firm->id,
            'name' => 'Alpha Corporation',
        ]);
        TaxClient::factory()->create([
            'firm_id' => $firm->id,
            'name' => 'Beta Industries',
        ]);

        $this->browse(function (Browser $browser) use ($partner) {
            $browser->loginAs($partner, 'firm')
                ->visit('/app/clients')
                ->type('input[placeholder*="Search"]', 'Alpha')
                ->pause(500)
                ->assertSee('Alpha Corporation')
                ->assertDontSee('Beta Industries');
        });
    }

    public function test_client_list_filter_by_entity_type(): void
    {
        $firm = Firm::factory()->create();
        $partner = FirmUser::factory()->create([
            'firm_id' => $firm->id,
            'role' => FirmRole::PARTNER,
        ]);
        TaxClient::factory()->create([
            'firm_id' => $firm->id,
            'name' => 'Company Client',
            'entity_type' => EntityType::COMPANY_LLC,
        ]);
        TaxClient::factory()->create([
            'firm_id' => $firm->id,
            'name' => 'Individual Client',
            'entity_type' => EntityType::INDIVIDUAL,
        ]);

        $this->browse(function (Browser $browser) use ($partner) {
            $browser->loginAs($partner, 'firm')
                ->visit('/app/clients')
                ->select('select[name="entity_type"]', 'company_llc')
                ->pause(500)
                ->assertSee('Company Client')
                ->assertDontSee('Individual Client');
        });
    }

    public function test_client_row_click_navigates_to_profile(): void
    {
        $firm = Firm::factory()->create();
        $partner = FirmUser::factory()->create([
            'firm_id' => $firm->id,
            'role' => FirmRole::PARTNER,
        ]);
        $client = TaxClient::factory()->create([
            'firm_id' => $firm->id,
            'name' => 'Test Client',
        ]);

        $this->browse(function (Browser $browser) use ($partner, $client) {
            $browser->loginAs($partner, 'firm')
                ->visit('/app/clients')
                ->waitFor('table tbody tr')
                ->click('table tbody tr:first-child')
                ->assertPathIs("/app/clients/{$client->id}");
        });
    }
}
```

### ClientWizardTest.php

```php
<?php

namespace Tests\Browser\App;

use App\Models\Firm;
use App\Models\FirmUser;
use App\Models\Industry;
use App\Enums\FirmRole;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ClientWizardTest extends DuskTestCase
{
    public function test_client_creation_wizard_step_1(): void
    {
        $firm = Firm::factory()->create();
        $partner = FirmUser::factory()->create([
            'firm_id' => $firm->id,
            'role' => FirmRole::PARTNER,
        ]);

        $this->browse(function (Browser $browser) use ($partner) {
            $browser->loginAs($partner, 'firm')
                ->visit('/app/clients/create')
                ->assertSee('Create New Client')
                ->assertSee('Entity Type')
                ->click('[data-entity-type="company_llc"]')
                ->assertPresent('[data-entity-type="company_llc"].selected');
        });
    }

    public function test_client_creation_wizard_completes_for_company(): void
    {
        $firm = Firm::factory()->create();
        $partner = FirmUser::factory()->create([
            'firm_id' => $firm->id,
            'role' => FirmRole::PARTNER,
        ]);
        $industry = Industry::factory()->create();

        $this->browse(function (Browser $browser) use ($partner, $industry) {
            $browser->loginAs($partner, 'firm')
                ->visit('/app/clients/create')
                ->click('[data-entity-type="company_llc"]')
                ->click('button:contains("Next")')
                ->type('input[name="name"]', 'Test Company Ltd')
                ->type('input[name="cac_number"]', 'RC123456')
                ->type('input[name="tin"]', '1234567890')
                ->click('button:contains("Next")')
                ->type('input[name="email"]', 'test@company.com')
                ->type('input[name="phone"]', '+2341234567890')
                ->click('button:contains("Next")')
                ->select('select[name="industry_id"]', $industry->id)
                ->click('button:contains("Next")')
                ->click('button:contains("Create Client")')
                ->assertPathIs('/app/clients/*')
                ->assertSee('Test Company Ltd');
        });
    }

    public function test_client_creation_wizard_validation(): void
    {
        $firm = Firm::factory()->create();
        $partner = FirmUser::factory()->create([
            'firm_id' => $firm->id,
            'role' => FirmRole::PARTNER,
        ]);

        $this->browse(function (Browser $browser) use ($partner) {
            $browser->loginAs($partner, 'firm')
                ->visit('/app/clients/create')
                ->click('[data-entity-type="company_llc"]')
                ->click('button:contains("Next")')
                ->click('button:contains("Next")')
                ->assertSee('The name field is required')
                ->assertSee('The CAC number field is required');
        });
    }

    public function test_client_creation_wizard_step_navigation(): void
    {
        $firm = Firm::factory()->create();
        $partner = FirmUser::factory()->create([
            'firm_id' => $firm->id,
            'role' => FirmRole::PARTNER,
        ]);

        $this->browse(function (Browser $browser) use ($partner) {
            $browser->loginAs($partner, 'firm')
                ->visit('/app/clients/create')
                ->click('[data-entity-type="company_llc"]')
                ->click('button:contains("Next")')
                ->type('input[name="name"]', 'Test Company')
                ->type('input[name="cac_number"]', 'RC123456')
                ->click('button:contains("Next")')
                ->assertSee('Contact Information')
                ->click('button:contains("Previous")')
                ->assertSee('Basic Information')
                ->assertInputValue('input[name="name"]', 'Test Company');
        });
    }
}
```

---

## Test Helpers

### AppTestCase.php

```php
<?php

namespace Tests;

use App\Models\Firm;
use App\Models\FirmUser;
use App\Models\TaxClient;
use App\Enums\FirmRole;

abstract class AppTestCase extends TestCase
{
    protected Firm $firm;
    protected FirmUser $partner;
    protected FirmUser $manager;
    protected FirmUser $associate;
    protected FirmUser $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->firm = Firm::factory()->create();
        $this->partner = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::PARTNER,
        ]);
        $this->manager = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::MANAGER,
        ]);
        $this->associate = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::ASSOCIATE,
        ]);
        $this->viewer = FirmUser::factory()->create([
            'firm_id' => $this->firm->id,
            'role' => FirmRole::VIEWER,
        ]);
    }

    protected function actingAsPartner(): static
    {
        $this->actingAs($this->partner, 'firm');
        return $this;
    }

    protected function actingAsManager(): static
    {
        $this->actingAs($this->manager, 'firm');
        return $this;
    }

    protected function actingAsAssociate(): static
    {
        $this->actingAs($this->associate, 'firm');
        return $this;
    }

    protected function actingAsViewer(): static
    {
        $this->actingAs($this->viewer, 'firm');
        return $this;
    }

    protected function createClient(array $attributes = []): TaxClient
    {
        return TaxClient::factory()->create(array_merge([
            'firm_id' => $this->firm->id,
        ], $attributes));
    }

    protected function createClients(int $count, array $attributes = []): \Illuminate\Database\Eloquent\Collection
    {
        return TaxClient::factory()->count($count)->create(array_merge([
            'firm_id' => $this->firm->id,
        ], $attributes));
    }

    protected function assignClientToUser(TaxClient $client, FirmUser $user): void
    {
        ClientUserAssignment::factory()->create([
            'tax_client_id' => $client->id,
            'firm_user_id' => $user->id,
        ]);
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

---

## Running Tests

```bash
# Run all app tests
php artisan test --filter=App

# Run feature tests only
php artisan test --testsuite=Feature --filter=App

# Run unit tests only
php artisan test --testsuite=Unit --filter=App

# Run specific test file
php artisan test tests/Feature/App/ClientTest.php

# Run browser tests
php artisan dusk --filter=App

# Run with coverage
php artisan test --coverage --min=80

# Run parallel tests
php artisan test --parallel --filter=App
```

---

## Continuous Integration

### GitHub Actions Configuration

```yaml
name: Module 3 Tests

on:
  push:
    paths:
      - 'app/Http/Controllers/App/**'
      - 'app/Services/Dashboard/**'
      - 'app/Services/Client/**'
      - 'app/Services/Activity/**'
      - 'app/Policies/TaxClientPolicy.php'
      - 'resources/js/pages/App/**'
      - 'tests/Feature/App/**'
      - 'tests/Unit/App/**'

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          coverage: xdebug

      - name: Install dependencies
        run: composer install --no-interaction

      - name: Run tests
        run: php artisan test --filter=App --coverage --min=80
```

---

## Next Steps

Once testing strategy is complete:
→ Begin implementation following the order in **00_OVERVIEW.md**
→ Start with database migrations and seeders
→ Implement backend services and controllers
→ Build frontend components
→ Run tests to verify implementation
