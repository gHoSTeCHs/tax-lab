# TaxLab Implementation - Database Schema

## Overview

This document defines the core database migrations for TaxLab's multi-tenant architecture. All tables use ULIDs as primary keys for better distribution and URL-safety.

## Migration Order

Migrations must be created in dependency order:

1. `create_admin_users_table`
2. `create_plans_table`
3. `create_firms_table`
4. `rename_users_to_firm_users_table`
5. `create_firm_invitations_table`
6. `create_client_portal_users_table`
7. `create_subscriptions_table`
8. `create_client_user_assignments_table`

---

## Migration 1: Admin Users

**File:** `database/migrations/xxxx_xx_xx_000010_create_admin_users_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_users', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['super_admin', 'admin', 'support', 'content_manager'])->default('admin');
            $table->enum('status', ['active', 'suspended'])->default('active');
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->timestamp('password_changed_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index(['status', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_users');
    }
};
```

---

## Migration 2: Plans

**File:** `database/migrations/xxxx_xx_xx_000020_create_plans_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('monthly_price')->default(0);
            $table->unsignedInteger('annual_price')->default(0);
            $table->unsignedInteger('client_limit')->nullable();
            $table->unsignedInteger('user_limit')->nullable();
            $table->unsignedInteger('report_limit')->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('plan_features', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('plan_id')->constrained()->cascadeOnDelete();
            $table->string('feature_key');
            $table->string('value')->nullable();
            $table->timestamps();

            $table->unique(['plan_id', 'feature_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('plans');
    }
};
```

---

## Migration 3: Firms (Tenants)

**File:** `database/migrations/xxxx_xx_xx_000030_create_firms_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firms', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('email');
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('rc_number')->nullable();
            $table->string('tin')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended', 'trial'])->default('trial');
            $table->foreignUlid('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('billing_cycle', ['monthly', 'annual'])->default('monthly');
            $table->date('trial_ends_at')->nullable();
            $table->date('subscription_ends_at')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status']);
            $table->index(['plan_id']);
        });

        Schema::create('firm_branding', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('firm_id')->constrained()->cascadeOnDelete();
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->string('primary_color', 7)->default('#3B82F6');
            $table->string('secondary_color', 7)->default('#1E40AF');
            $table->text('email_header')->nullable();
            $table->text('email_footer')->nullable();
            $table->text('report_footer')->nullable();
            $table->timestamps();

            $table->unique('firm_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firm_branding');
        Schema::dropIfExists('firms');
    }
};
```

---

## Migration 4: Transform Users to Firm Users

**File:** `database/migrations/xxxx_xx_xx_000040_rename_users_to_firm_users_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('users', 'firm_users');

        Schema::table('firm_users', function (Blueprint $table) {
            $table->foreignUlid('firm_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['partner', 'manager', 'associate', 'viewer'])->default('associate')->after('email');
            $table->enum('status', ['invited', 'active', 'suspended'])->default('invited')->after('role');
            $table->string('job_title')->nullable()->after('name');
            $table->string('phone', 20)->nullable()->after('job_title');
            $table->timestamp('last_login_at')->nullable()->after('two_factor_confirmed_at');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->json('notification_preferences')->nullable()->after('last_login_ip');
            $table->foreignUlid('invited_by')->nullable()->after('notification_preferences')->constrained('firm_users')->nullOnDelete();
            $table->timestamp('invited_at')->nullable()->after('invited_by');

            $table->index(['firm_id', 'status']);
            $table->index(['firm_id', 'role']);
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->renameColumn('user_id', 'firm_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->renameColumn('firm_user_id', 'user_id');
        });

        Schema::table('firm_users', function (Blueprint $table) {
            $table->dropForeign(['firm_id']);
            $table->dropForeign(['invited_by']);
            $table->dropColumn([
                'firm_id',
                'role',
                'status',
                'job_title',
                'phone',
                'last_login_at',
                'last_login_ip',
                'notification_preferences',
                'invited_by',
                'invited_at',
            ]);
        });

        Schema::rename('firm_users', 'users');
    }
};
```

---

## Migration 5: Firm Invitations

**File:** `database/migrations/xxxx_xx_xx_000050_create_firm_invitations_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firm_invitations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('firm_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('invited_by')->constrained('firm_users')->cascadeOnDelete();
            $table->string('email');
            $table->string('name')->nullable();
            $table->enum('role', ['partner', 'manager', 'associate', 'viewer'])->default('associate');
            $table->string('token', 64)->unique();
            $table->enum('status', ['pending', 'accepted', 'expired', 'cancelled'])->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['firm_id', 'status']);
            $table->index(['email', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firm_invitations');
    }
};
```

---

## Migration 6: Client Portal Users

**File:** `database/migrations/xxxx_xx_xx_000060_create_client_portal_users_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_portal_users', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tax_client_id')->constrained('tax_clients')->cascadeOnDelete();
            $table->foreignUlid('firm_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone', 20)->nullable();
            $table->enum('role', ['primary', 'viewer'])->default('viewer');
            $table->enum('status', ['invited', 'active', 'suspended'])->default('invited');
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->foreignUlid('invited_by')->nullable()->constrained('firm_users')->nullOnDelete();
            $table->rememberToken();
            $table->timestamps();

            $table->unique(['tax_client_id', 'email']);
            $table->index(['firm_id', 'status']);
            $table->index(['tax_client_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_portal_users');
    }
};
```

---

## Migration 7: Subscriptions

**File:** `database/migrations/xxxx_xx_xx_000070_create_subscriptions_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('firm_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('plan_id')->constrained()->restrictOnDelete();
            $table->enum('status', ['active', 'cancelled', 'past_due', 'trialing'])->default('trialing');
            $table->enum('billing_cycle', ['monthly', 'annual'])->default('monthly');
            $table->unsignedInteger('amount');
            $table->date('current_period_start');
            $table->date('current_period_end');
            $table->date('trial_ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['firm_id', 'status']);
        });

        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('firm_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->string('currency', 3)->default('NGN');
            $table->enum('status', ['pending', 'successful', 'failed', 'refunded'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('gateway_reference')->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['firm_id', 'status']);
            $table->index('payment_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('subscriptions');
    }
};
```

---

## Migration 8: Client User Assignments

**File:** `database/migrations/xxxx_xx_xx_000080_create_client_user_assignments_table.php`

This migration creates the assignment table for linking Associates and Viewers to specific tax clients they can access.

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_user_assignments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('firm_user_id')->constrained('firm_users')->cascadeOnDelete();
            $table->foreignUlid('tax_client_id')->constrained('tax_clients')->cascadeOnDelete();
            $table->foreignUlid('assigned_by')->constrained('firm_users')->cascadeOnDelete();
            $table->timestamp('assigned_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['firm_user_id', 'tax_client_id']);
            $table->index(['tax_client_id']);
            $table->index(['assigned_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_user_assignments');
    }
};
```

---

## Model Definitions

### AdminUser Model

**File:** `app/Models/AdminUser.php`

```php
<?php

namespace App\Models;

use App\Enums\AdminRole;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class AdminUser extends Authenticatable
{
    use HasFactory, HasUlid, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => AdminRole::class,
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === AdminRole::SUPER_ADMIN;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function hasPermission(string $permission): bool
    {
        return $this->role->hasPermission($permission);
    }

    public function canAccessTenants(): bool
    {
        return $this->role->canAccessTenants();
    }

    public function canModifyData(): bool
    {
        return $this->role->canModifyData();
    }
}
```

### Firm Model

**File:** `app/Models/Firm.php`

```php
<?php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Firm extends Model
{
    use HasFactory, HasUlid, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'rc_number',
        'tin',
        'status',
        'plan_id',
        'billing_cycle',
        'trial_ends_at',
        'subscription_ends_at',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'date',
            'subscription_ends_at' => 'date',
            'settings' => 'array',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(FirmUser::class);
    }

    public function branding(): HasOne
    {
        return $this->hasOne(FirmBranding::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(FirmInvitation::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->where('status', 'active');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isOnTrial(): bool
    {
        return $this->status === 'trial' && $this->trial_ends_at?->isFuture();
    }
}
```

### FirmUser Model

**File:** `app/Models/FirmUser.php`

```php
<?php

namespace App\Models;

use App\Enums\FirmRole;
use App\Models\Concerns\BelongsToFirm;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class FirmUser extends Authenticatable
{
    use BelongsToFirm, HasFactory, HasUlid, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'firm_id',
        'name',
        'email',
        'password',
        'role',
        'status',
        'job_title',
        'phone',
        'notification_preferences',
        'invited_by',
        'invited_at',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'invited_at' => 'datetime',
            'password' => 'hashed',
            'notification_preferences' => 'array',
            'role' => FirmRole::class,
        ];
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(FirmUser::class, 'invited_by');
    }

    public function assignedClients(): BelongsToMany
    {
        return $this->belongsToMany(TaxClient::class, 'client_user_assignments')
            ->withPivot(['assigned_by', 'assigned_at', 'notes'])
            ->withTimestamps();
    }

    public function isPartner(): bool
    {
        return $this->role === FirmRole::PARTNER;
    }

    public function isManager(): bool
    {
        return $this->role === FirmRole::MANAGER;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isInvited(): bool
    {
        return $this->status === 'invited';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function requiresClientAssignment(): bool
    {
        return $this->role->requiresClientAssignment();
    }

    public function canViewAllClients(): bool
    {
        return $this->role->canViewAllClients();
    }

    public function canApproveReports(): bool
    {
        return $this->role->canApproveReports();
    }

    public function canManageTeam(): bool
    {
        return $this->role->canManageTeam();
    }

    public function canModifyData(): bool
    {
        return $this->role->canModifyData();
    }

    public function hasPermission(string $permission): bool
    {
        return $this->role->hasPermission($permission);
    }

    public function hasAccessToClient(TaxClient $client): bool
    {
        if ($this->canViewAllClients()) {
            return $this->firm_id === $client->firm_id;
        }

        return $this->assignedClients()->where('tax_clients.id', $client->id)->exists();
    }
}
```

### Plan Model

**File:** `app/Models/Plan.php`

```php
<?php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory, HasUlid;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'monthly_price',
        'annual_price',
        'client_limit',
        'user_limit',
        'report_limit',
        'features',
        'is_active',
        'is_featured',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'monthly_price' => 'integer',
            'annual_price' => 'integer',
            'client_limit' => 'integer',
            'user_limit' => 'integer',
            'report_limit' => 'integer',
            'features' => 'array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function planFeatures(): HasMany
    {
        return $this->hasMany(PlanFeature::class);
    }

    public function firms(): HasMany
    {
        return $this->hasMany(Firm::class);
    }

    public function hasFeature(string $key): bool
    {
        return $this->planFeatures()->where('feature_key', $key)->exists();
    }

    public function getFeatureValue(string $key): ?string
    {
        return $this->planFeatures()->where('feature_key', $key)->value('value');
    }
}
```

### ClientPortalUser Model

**File:** `app/Models/ClientPortalUser.php`

```php
<?php

namespace App\Models;

use App\Enums\PortalRole;
use App\Models\Concerns\BelongsToFirm;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class ClientPortalUser extends Authenticatable
{
    use BelongsToFirm, HasFactory, HasUlid, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'tax_client_id',
        'firm_id',
        'name',
        'email',
        'password',
        'phone',
        'role',
        'status',
        'invited_by',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => PortalRole::class,
        ];
    }

    public function taxClient(): BelongsTo
    {
        return $this->belongsTo(TaxClient::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(FirmUser::class, 'invited_by');
    }

    public function isPrimary(): bool
    {
        return $this->role === PortalRole::PRIMARY;
    }

    public function isViewer(): bool
    {
        return $this->role === PortalRole::VIEWER;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isInvited(): bool
    {
        return $this->status === 'invited';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function canInviteOthers(): bool
    {
        return $this->role->canInviteOthers();
    }

    public function canSendMessages(): bool
    {
        return $this->role->canSendMessages();
    }

    public function hasPermission(string $permission): bool
    {
        return $this->role->hasPermission($permission);
    }
}
```

### ClientUserAssignment Model

**File:** `app/Models/ClientUserAssignment.php`

```php
<?php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientUserAssignment extends Model
{
    use HasFactory, HasUlid;

    protected $fillable = [
        'firm_user_id',
        'tax_client_id',
        'assigned_by',
        'assigned_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
        ];
    }

    public function firmUser(): BelongsTo
    {
        return $this->belongsTo(FirmUser::class);
    }

    public function taxClient(): BelongsTo
    {
        return $this->belongsTo(TaxClient::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(FirmUser::class, 'assigned_by');
    }
}
```

---

## Seeders

### AdminUserSeeder

**File:** `database/seeders/AdminUserSeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        AdminUser::create([
            'name' => 'Super Admin',
            'email' => 'admin@taxlab.ng',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }
}
```

### PlanSeeder

**File:** `database/seeders/PlanSeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Perfect for solo practitioners',
                'monthly_price' => 1500000,
                'annual_price' => 15000000,
                'client_limit' => 30,
                'user_limit' => 1,
                'report_limit' => 100,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'For growing practices',
                'monthly_price' => 4500000,
                'annual_price' => 45000000,
                'client_limit' => 100,
                'user_limit' => 3,
                'report_limit' => 500,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'description' => 'For established firms',
                'monthly_price' => 12000000,
                'annual_price' => 120000000,
                'client_limit' => 300,
                'user_limit' => 10,
                'report_limit' => null,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Custom solution for large firms',
                'monthly_price' => 0,
                'annual_price' => 0,
                'client_limit' => null,
                'user_limit' => null,
                'report_limit' => null,
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::create($plan);
        }
    }
}
```

---

## Next Steps

Once migrations are created and run, proceed to:
→ **03_MULTI_TENANCY.md** - Implement tenant isolation
