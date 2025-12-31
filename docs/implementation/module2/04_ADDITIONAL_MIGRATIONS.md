# Module 2: Additional Migrations

## Overview

This document covers additional database tables required for Module 2 that were not part of the core Module 1 schema.

## New Tables

### 1. Tenant Notes Table

Internal notes system for admin team to track support interactions and important information about tenants.

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_notes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('firm_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('admin_user_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->boolean('is_pinned')->default(false);
            $table->string('category')->nullable();
            $table->timestamps();

            $table->index(['firm_id', 'is_pinned', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_notes');
    }
};
```

### 2. Admin Audit Log Table

Enhanced audit logging for all admin actions per spec Section 2.4.

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_audit_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('admin_user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('resource_type');
            $table->ulid('resource_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at');

            $table->index(['admin_user_id', 'created_at']);
            $table->index(['resource_type', 'resource_id']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audit_logs');
    }
};
```

### 3. Impersonation Sessions Table

Tracks active impersonation sessions for security auditing.

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('impersonation_sessions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('admin_user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('firm_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('firm_user_id')->constrained()->cascadeOnDelete();
            $table->string('reason');
            $table->string('ip_address', 45);
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration_seconds')->nullable();

            $table->index(['admin_user_id', 'started_at']);
            $table->index(['firm_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('impersonation_sessions');
    }
};
```

### 4. Plan Limits Table

Configurable limits per subscription plan.

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_limits', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('plan_id')->constrained()->cascadeOnDelete();
            $table->string('limit_key', 50);
            $table->integer('limit_value')->default(-1);
            $table->timestamps();

            $table->unique(['plan_id', 'limit_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_limits');
    }
};
```

### 5. Plan Features Table

Feature flags per subscription plan.

**Note:** The `custom_branding` feature is always enabled for all plans per spec. This should be handled in the Plan model's `hasFeature()` method rather than stored in the database.

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_features', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('plan_id')->constrained()->cascadeOnDelete();
            $table->string('feature_key', 50);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['plan_id', 'feature_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_features');
    }
};
```

---

## Models

### TenantNote Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantNote extends Model
{
    use HasUlids;

    protected $fillable = [
        'firm_id',
        'admin_user_id',
        'content',
        'is_pinned',
        'category',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
    ];

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class);
    }
}
```

### AdminAuditLog Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminAuditLog extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'admin_user_id',
        'action',
        'resource_type',
        'resource_id',
        'old_values',
        'new_values',
        'metadata',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class);
    }

    public function getResourceAttribute(): ?Model
    {
        if (!$this->resource_type || !$this->resource_id) {
            return null;
        }

        return app($this->resource_type)->find($this->resource_id);
    }
}
```

### ImpersonationSession Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImpersonationSession extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'admin_user_id',
        'firm_id',
        'firm_user_id',
        'reason',
        'ip_address',
        'started_at',
        'ended_at',
        'duration_seconds',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class);
    }

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }

    public function firmUser(): BelongsTo
    {
        return $this->belongsTo(FirmUser::class);
    }

    public function isActive(): bool
    {
        return $this->ended_at === null;
    }
}
```

### PlanLimit Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanLimit extends Model
{
    use HasUlids;

    protected $fillable = [
        'plan_id',
        'limit_key',
        'limit_value',
    ];

    protected $casts = [
        'limit_value' => 'integer',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isUnlimited(): bool
    {
        return $this->limit_value === -1;
    }
}
```

### PlanFeature Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanFeature extends Model
{
    use HasUlids;

    protected $fillable = [
        'plan_id',
        'feature_key',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
```

---

## Update Existing Plan Model

Add relationships to the Plan model from Module 1:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    private const ALWAYS_ENABLED_FEATURES = ['custom_branding'];

    public function limits(): HasMany
    {
        return $this->hasMany(PlanLimit::class);
    }

    public function features(): HasMany
    {
        return $this->hasMany(PlanFeature::class);
    }

    public function getLimit(string $key): int
    {
        return $this->limits->firstWhere('limit_key', $key)?->limit_value ?? -1;
    }

    public function hasFeature(string $key): bool
    {
        if (in_array($key, self::ALWAYS_ENABLED_FEATURES)) {
            return true;
        }

        $feature = $this->features->firstWhere('feature_key', $key);
        return $feature?->is_enabled ?? false;
    }

    public function isUnlimited(string $key): bool
    {
        return $this->getLimit($key) === -1;
    }
}
```

---

## Migration Order

Run migrations in this order:

1. `create_plan_limits_table`
2. `create_plan_features_table`
3. `create_tenant_notes_table`
4. `create_admin_audit_logs_table`
5. `create_impersonation_sessions_table`

---

## Seeders

### PlanLimitsSeeder

```php
<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanLimitsSeeder extends Seeder
{
    public function run(): void
    {
        $limits = [
            'starter' => [
                'max_users' => 3,
                'max_clients' => 50,
                'max_reports_monthly' => 100,
                'max_storage_mb' => 5120,
            ],
            'professional' => [
                'max_users' => 10,
                'max_clients' => 250,
                'max_reports_monthly' => -1,
                'max_storage_mb' => 25600,
            ],
            'enterprise' => [
                'max_users' => -1,
                'max_clients' => -1,
                'max_reports_monthly' => -1,
                'max_storage_mb' => 102400,
            ],
        ];

        foreach ($limits as $planSlug => $planLimits) {
            $plan = Plan::where('slug', $planSlug)->first();
            if ($plan) {
                foreach ($planLimits as $key => $value) {
                    $plan->limits()->updateOrCreate(
                        ['limit_key' => $key],
                        ['limit_value' => $value]
                    );
                }
            }
        }
    }
}
```

### PlanFeaturesSeeder

Note: `custom_branding` is not seeded as it's always enabled (handled in Plan model).

```php
<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanFeaturesSeeder extends Seeder
{
    public function run(): void
    {
        $features = [
            'starter' => [
                'scenario_modeling' => true,
                'advanced_scenarios' => false,
                'report_approval_workflow' => false,
                'client_portal' => true,
                'api_access' => false,
                'priority_support' => false,
                'cpd_access' => false,
                'knowledge_base' => false,
                'analytics_dashboard' => false,
                'bulk_import' => false,
                'custom_report_templates' => false,
            ],
            'professional' => [
                'scenario_modeling' => true,
                'advanced_scenarios' => true,
                'report_approval_workflow' => true,
                'client_portal' => true,
                'api_access' => true,
                'priority_support' => false,
                'cpd_access' => true,
                'knowledge_base' => true,
                'analytics_dashboard' => true,
                'bulk_import' => true,
                'custom_report_templates' => false,
            ],
            'enterprise' => [
                'scenario_modeling' => true,
                'advanced_scenarios' => true,
                'report_approval_workflow' => true,
                'client_portal' => true,
                'api_access' => true,
                'priority_support' => true,
                'cpd_access' => true,
                'knowledge_base' => true,
                'analytics_dashboard' => true,
                'bulk_import' => true,
                'custom_report_templates' => true,
            ],
        ];

        foreach ($features as $planSlug => $planFeatures) {
            $plan = Plan::where('slug', $planSlug)->first();
            if ($plan) {
                foreach ($planFeatures as $key => $enabled) {
                    $plan->features()->updateOrCreate(
                        ['feature_key' => $key],
                        ['is_enabled' => $enabled]
                    );
                }
            }
        }
    }
}
```

---

## Note Category Enum

```php
<?php

namespace App\Enums;

enum TenantNoteCategory: string
{
    case GENERAL = 'general';
    case SUPPORT = 'support';
    case BILLING = 'billing';
    case TECHNICAL = 'technical';
    case COMPLIANCE = 'compliance';

    public function label(): string
    {
        return match($this) {
            self::GENERAL => 'General',
            self::SUPPORT => 'Support',
            self::BILLING => 'Billing',
            self::TECHNICAL => 'Technical',
            self::COMPLIANCE => 'Compliance',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::GENERAL => 'gray',
            self::SUPPORT => 'blue',
            self::BILLING => 'green',
            self::TECHNICAL => 'purple',
            self::COMPLIANCE => 'orange',
        };
    }
}
```

---

## Admin Action Enum

```php
<?php

namespace App\Enums;

enum AdminAction: string
{
    case LOGIN = 'login';
    case LOGOUT = 'logout';
    case LOGIN_FAILED = 'login_failed';
    case VIEW = 'view';
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case SUSPEND = 'suspend';
    case REACTIVATE = 'reactivate';
    case IMPERSONATE_START = 'impersonate_start';
    case IMPERSONATE_END = 'impersonate_end';
    case EXPORT = 'export';
    case CHANGE_PLAN = 'change_plan';
    case EXTEND_TRIAL = 'extend_trial';

    public function label(): string
    {
        return match($this) {
            self::LOGIN => 'Logged In',
            self::LOGOUT => 'Logged Out',
            self::LOGIN_FAILED => 'Login Failed',
            self::VIEW => 'Viewed',
            self::CREATE => 'Created',
            self::UPDATE => 'Updated',
            self::DELETE => 'Deleted',
            self::SUSPEND => 'Suspended',
            self::REACTIVATE => 'Reactivated',
            self::IMPERSONATE_START => 'Started Impersonation',
            self::IMPERSONATE_END => 'Ended Impersonation',
            self::EXPORT => 'Exported Data',
            self::CHANGE_PLAN => 'Changed Plan',
            self::EXTEND_TRIAL => 'Extended Trial',
        };
    }

    public function severity(): string
    {
        return match($this) {
            self::LOGIN_FAILED, self::DELETE, self::SUSPEND => 'warning',
            self::IMPERSONATE_START, self::IMPERSONATE_END => 'info',
            default => 'default',
        };
    }
}
```
