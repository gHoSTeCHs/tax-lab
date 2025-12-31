# TaxLab Implementation - Module 3 Database Schema

## Overview

This document defines the database migrations, Eloquent models, enums, and seeders for the Firm Dashboard and Client Management module.

## Migration Order

Migrations must be created in dependency order:

1. `create_industries_table`
2. `create_tax_clients_table`
3. `create_client_user_assignments_table`
4. `create_client_notes_table`
5. `create_activity_logs_table`
6. `create_client_access_logs_table`

---

## Migration 1: Industries

**File:** `database/migrations/xxxx_xx_xx_000100_create_industries_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('industries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('sector', 100);
            $table->string('name', 255);
            $table->string('code', 10)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['sector', 'is_active']);
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('industries');
    }
};
```

---

## Migration 2: Tax Clients

**File:** `database/migrations/xxxx_xx_xx_000110_create_tax_clients_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_clients', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('firm_id')->constrained()->cascadeOnDelete();

            $table->enum('entity_type', [
                'company_llc',
                'company_plc',
                'partnership',
                'sole_proprietorship',
                'individual',
                'trust_estate',
            ]);

            $table->string('name', 255);
            $table->string('trading_name', 255)->nullable();

            $table->string('tin', 20)->nullable();
            $table->string('nin', 11)->nullable();
            $table->string('cac_number', 20)->nullable();

            $table->date('incorporation_date')->nullable();
            $table->string('fiscal_year_end', 5)->nullable();

            $table->foreignUlid('industry_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('company_size', ['micro', 'small', 'medium', 'large'])->nullable();
            $table->enum('partnership_type', ['general', 'limited', 'llp'])->nullable();
            $table->unsignedSmallInteger('number_of_partners')->nullable();

            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable();
            $table->enum('employment_status', ['employed', 'self_employed', 'unemployed', 'retired'])->nullable();
            $table->date('date_of_birth')->nullable();

            $table->string('email', 255)->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('state', 50)->nullable();
            $table->string('lga', 100)->nullable();

            $table->enum('status', ['active', 'archived'])->default('active');
            $table->boolean('portal_enabled')->default(false);

            $table->foreignUlid('created_by')->constrained('firm_users')->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['firm_id', 'status']);
            $table->index(['firm_id', 'entity_type']);
            $table->index(['firm_id', 'industry_id']);
            $table->index(['tin']);
            $table->index(['cac_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_clients');
    }
};
```

---

## Migration 3: Client User Assignments

**File:** `database/migrations/xxxx_xx_xx_000115_create_client_user_assignments_table.php`

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
            $table->foreignUlid('tax_client_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('firm_user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('assigned_by')->nullable()->constrained('firm_users')->nullOnDelete();
            $table->timestamp('assigned_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tax_client_id', 'firm_user_id']);
            $table->index(['firm_user_id', 'assigned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_user_assignments');
    }
};
```

---

## Migration 4: Client Notes

**File:** `database/migrations/xxxx_xx_xx_000125_create_client_notes_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_notes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tax_client_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('firm_user_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tax_client_id', 'is_pinned', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_notes');
    }
};
```

---

## Migration 5: Activity Logs

**File:** `database/migrations/xxxx_xx_xx_000130_create_activity_logs_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('firm_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('firm_user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('loggable_type', 100);
            $table->ulid('loggable_id');
            $table->string('action', 50);
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');

            $table->index(['firm_id', 'created_at']);
            $table->index(['loggable_type', 'loggable_id']);
            $table->index(['firm_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
```

---

## Migration 6: Client Access Logs

**File:** `database/migrations/xxxx_xx_xx_000140_create_client_access_logs_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_access_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tax_client_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('firm_user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('accessed_at');

            $table->index(['firm_user_id', 'accessed_at']);
            $table->index(['tax_client_id', 'accessed_at']);
            $table->unique(['tax_client_id', 'firm_user_id', 'accessed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_access_logs');
    }
};
```

---

## Enums

### EntityType

**File:** `app/Enums/EntityType.php`

```php
<?php

namespace App\Enums;

enum EntityType: string
{
    case COMPANY_LLC = 'company_llc';
    case COMPANY_PLC = 'company_plc';
    case PARTNERSHIP = 'partnership';
    case SOLE_PROPRIETORSHIP = 'sole_proprietorship';
    case INDIVIDUAL = 'individual';
    case TRUST_ESTATE = 'trust_estate';

    public function label(): string
    {
        return match ($this) {
            self::COMPANY_LLC => 'Company (Limited Liability)',
            self::COMPANY_PLC => 'Company (Public Limited)',
            self::PARTNERSHIP => 'Partnership',
            self::SOLE_PROPRIETORSHIP => 'Sole Proprietorship',
            self::INDIVIDUAL => 'Individual (High Net Worth)',
            self::TRUST_ESTATE => 'Trust/Estate',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::COMPANY_LLC, self::COMPANY_PLC => 'building-2',
            self::PARTNERSHIP => 'users',
            self::SOLE_PROPRIETORSHIP => 'briefcase',
            self::INDIVIDUAL => 'user',
            self::TRUST_ESTATE => 'landmark',
        };
    }

    public function requiresTin(): bool
    {
        return match ($this) {
            self::COMPANY_LLC, self::COMPANY_PLC, self::PARTNERSHIP, self::SOLE_PROPRIETORSHIP => true,
            self::INDIVIDUAL, self::TRUST_ESTATE => false,
        };
    }

    public function requiresNin(): bool
    {
        return $this === self::INDIVIDUAL;
    }

    public function requiresCac(): bool
    {
        return match ($this) {
            self::COMPANY_LLC, self::COMPANY_PLC => true,
            default => false,
        };
    }

    public function isCompany(): bool
    {
        return in_array($this, [self::COMPANY_LLC, self::COMPANY_PLC]);
    }

    public function isIndividual(): bool
    {
        return $this === self::INDIVIDUAL;
    }

    public function isPartnership(): bool
    {
        return $this === self::PARTNERSHIP;
    }

    public static function options(): array
    {
        return array_map(
            fn (self $type) => [
                'value' => $type->value,
                'label' => $type->label(),
                'icon' => $type->icon(),
            ],
            self::cases()
        );
    }
}
```

### ClientStatus

**File:** `app/Enums/ClientStatus.php`

```php
<?php

namespace App\Enums;

enum ClientStatus: string
{
    case ACTIVE = 'active';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::ARCHIVED => 'Archived',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'green',
            self::ARCHIVED => 'gray',
        };
    }
}
```

### CompanySize

**File:** `app/Enums/CompanySize.php`

```php
<?php

namespace App\Enums;

enum CompanySize: string
{
    case MICRO = 'micro';
    case SMALL = 'small';
    case MEDIUM = 'medium';
    case LARGE = 'large';

    public function label(): string
    {
        return match ($this) {
            self::MICRO => 'Micro (< 10 employees)',
            self::SMALL => 'Small (10-49 employees)',
            self::MEDIUM => 'Medium (50-199 employees)',
            self::LARGE => 'Large (200+ employees)',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn (self $size) => ['value' => $size->value, 'label' => $size->label()],
            self::cases()
        );
    }
}
```

### PartnershipType

**File:** `app/Enums/PartnershipType.php`

```php
<?php

namespace App\Enums;

enum PartnershipType: string
{
    case GENERAL = 'general';
    case LIMITED = 'limited';
    case LLP = 'llp';

    public function label(): string
    {
        return match ($this) {
            self::GENERAL => 'General Partnership',
            self::LIMITED => 'Limited Partnership',
            self::LLP => 'Limited Liability Partnership',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn (self $type) => ['value' => $type->value, 'label' => $type->label()],
            self::cases()
        );
    }
}
```

### MaritalStatus

**File:** `app/Enums/MaritalStatus.php`

```php
<?php

namespace App\Enums;

enum MaritalStatus: string
{
    case SINGLE = 'single';
    case MARRIED = 'married';
    case DIVORCED = 'divorced';
    case WIDOWED = 'widowed';

    public function label(): string
    {
        return match ($this) {
            self::SINGLE => 'Single',
            self::MARRIED => 'Married',
            self::DIVORCED => 'Divorced',
            self::WIDOWED => 'Widowed',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn (self $status) => ['value' => $status->value, 'label' => $status->label()],
            self::cases()
        );
    }
}
```

### EmploymentStatus

**File:** `app/Enums/EmploymentStatus.php`

```php
<?php

namespace App\Enums;

enum EmploymentStatus: string
{
    case EMPLOYED = 'employed';
    case SELF_EMPLOYED = 'self_employed';
    case UNEMPLOYED = 'unemployed';
    case RETIRED = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::EMPLOYED => 'Employed',
            self::SELF_EMPLOYED => 'Self-Employed',
            self::UNEMPLOYED => 'Unemployed',
            self::RETIRED => 'Retired',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn (self $status) => ['value' => $status->value, 'label' => $status->label()],
            self::cases()
        );
    }
}
```

### ActivityAction

**File:** `app/Enums/ActivityAction.php`

```php
<?php

namespace App\Enums;

enum ActivityAction: string
{
    case CREATED = 'created';
    case UPDATED = 'updated';
    case DELETED = 'deleted';
    case ARCHIVED = 'archived';
    case RESTORED = 'restored';
    case VIEWED = 'viewed';
    case ASSIGNED = 'assigned';
    case UNASSIGNED = 'unassigned';
    case IMPORTED = 'imported';
    case EXPORTED = 'exported';
    case CALCULATED = 'calculated';
    case REPORTED = 'reported';

    public function label(): string
    {
        return match ($this) {
            self::CREATED => 'created',
            self::UPDATED => 'updated',
            self::DELETED => 'deleted',
            self::ARCHIVED => 'archived',
            self::RESTORED => 'restored',
            self::VIEWED => 'viewed',
            self::ASSIGNED => 'assigned',
            self::UNASSIGNED => 'unassigned',
            self::IMPORTED => 'imported',
            self::EXPORTED => 'exported',
            self::CALCULATED => 'ran calculation for',
            self::REPORTED => 'generated report for',
        };
    }

    public function pastTense(): string
    {
        return $this->label();
    }
}
```

---

## Models

### Industry Model

**File:** `app/Models/Industry.php`

```php
<?php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Industry extends Model
{
    use HasFactory, HasUlid;

    protected $fillable = [
        'sector',
        'name',
        'code',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function taxClients(): HasMany
    {
        return $this->hasMany(TaxClient::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeBySector(Builder $query, string $sector): Builder
    {
        return $query->where('sector', $sector);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sector')->orderBy('sort_order')->orderBy('name');
    }

    public static function getSectors(): array
    {
        return self::active()
            ->distinct()
            ->orderBy('sector')
            ->pluck('sector')
            ->toArray();
    }
}
```

### TaxClient Model

**File:** `app/Models/TaxClient.php`

```php
<?php

namespace App\Models;

use App\Enums\ClientStatus;
use App\Enums\CompanySize;
use App\Enums\EmploymentStatus;
use App\Enums\EntityType;
use App\Enums\MaritalStatus;
use App\Enums\PartnershipType;
use App\Models\Concerns\BelongsToFirm;
use App\Models\Concerns\HasUlid;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Crypt;

class TaxClient extends Model
{
    use BelongsToFirm, HasFactory, HasUlid, SoftDeletes;

    protected $fillable = [
        'firm_id',
        'entity_type',
        'name',
        'trading_name',
        'tin',
        'nin',
        'cac_number',
        'incorporation_date',
        'fiscal_year_end',
        'industry_id',
        'company_size',
        'partnership_type',
        'number_of_partners',
        'marital_status',
        'employment_status',
        'date_of_birth',
        'email',
        'phone',
        'address',
        'state',
        'lga',
        'status',
        'portal_enabled',
        'created_by',
    ];

    protected $hidden = [
        'tin',
        'nin',
    ];

    protected function casts(): array
    {
        return [
            'entity_type' => EntityType::class,
            'status' => ClientStatus::class,
            'company_size' => CompanySize::class,
            'partnership_type' => PartnershipType::class,
            'marital_status' => MaritalStatus::class,
            'employment_status' => EmploymentStatus::class,
            'incorporation_date' => 'date',
            'date_of_birth' => 'date',
            'portal_enabled' => 'boolean',
            'number_of_partners' => 'integer',
        ];
    }

    /**
     * Encrypt TIN when setting.
     */
    protected function tin(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function (?string $value): ?string {
                if ($value === null) {
                    return null;
                }
                try {
                    return Crypt::decryptString($value);
                } catch (DecryptException) {
                    return $value;
                }
            },
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    /**
     * Encrypt NIN when setting.
     */
    protected function nin(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function (?string $value): ?string {
                if ($value === null) {
                    return null;
                }
                try {
                    return Crypt::decryptString($value);
                } catch (DecryptException) {
                    return $value;
                }
            },
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(FirmUser::class, 'created_by');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ClientNote::class);
    }

    public function pinnedNotes(): HasMany
    {
        return $this->notes()->where('is_pinned', true)->latest();
    }

    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(FirmUser::class, 'client_user_assignments')
            ->withPivot(['assigned_by', 'assigned_at', 'notes'])
            ->withTimestamps();
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'loggable');
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(ClientAccessLog::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ClientStatus::ACTIVE);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', ClientStatus::ARCHIVED);
    }

    public function scopeForEntityType(Builder $query, EntityType $type): Builder
    {
        return $query->where('entity_type', $type);
    }

    public function scopeAssignedTo(Builder $query, FirmUser $user): Builder
    {
        return $query->whereHas('assignedUsers', fn ($q) => $q->where('firm_users.id', $user->id));
    }

    public function scopeAccessibleBy(Builder $query, FirmUser $user): Builder
    {
        if ($user->canViewAllClients()) {
            return $query;
        }

        return $query->assignedTo($user);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('trading_name', 'like', "%{$term}%")
                ->orWhere('tin', 'like', "%{$term}%")
                ->orWhere('nin', 'like', "%{$term}%")
                ->orWhere('cac_number', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%");
        });
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->trading_name ?? $this->name;
    }

    public function isActive(): bool
    {
        return $this->status === ClientStatus::ACTIVE;
    }

    public function isArchived(): bool
    {
        return $this->status === ClientStatus::ARCHIVED;
    }

    public function isCompany(): bool
    {
        return $this->entity_type->isCompany();
    }

    public function isIndividual(): bool
    {
        return $this->entity_type->isIndividual();
    }

    public function isAssignedTo(FirmUser $user): bool
    {
        return $this->assignedUsers()->where('firm_users.id', $user->id)->exists();
    }

    public function getMaskedTinAttribute(): ?string
    {
        if (!$this->tin) {
            return null;
        }

        $length = strlen($this->tin);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', $length - 4) . substr($this->tin, -4);
    }
}
```

### ClientNote Model

**File:** `app/Models/ClientNote.php`

```php
<?php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class ClientNote extends Model
{
    use HasFactory, HasUlid, SoftDeletes;

    protected $fillable = [
        'tax_client_id',
        'firm_user_id',
        'content',
        'is_pinned',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
        ];
    }

    public function taxClient(): BelongsTo
    {
        return $this->belongsTo(TaxClient::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(FirmUser::class, 'firm_user_id');
    }

    public function scopePinned(Builder $query): Builder
    {
        return $query->where('is_pinned', true);
    }

    public function scopeUnpinned(Builder $query): Builder
    {
        return $query->where('is_pinned', false);
    }

    public function togglePin(): void
    {
        $this->update(['is_pinned' => !$this->is_pinned]);
    }
}
```

### ActivityLog Model

**File:** `app/Models/ActivityLog.php`

```php
<?php

namespace App\Models;

use App\Enums\ActivityAction;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;

class ActivityLog extends Model
{
    use HasFactory, HasUlid;

    public $timestamps = false;

    protected $fillable = [
        'firm_id',
        'firm_user_id',
        'loggable_type',
        'loggable_id',
        'action',
        'description',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'action' => ActivityAction::class,
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(FirmUser::class, 'firm_user_id');
    }

    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeForUser(Builder $query, FirmUser $user): Builder
    {
        return $query->where('firm_user_id', $user->id);
    }

    public function scopeForLoggable(Builder $query, Model $model): Builder
    {
        return $query
            ->where('loggable_type', $model->getMorphClass())
            ->where('loggable_id', $model->getKey());
    }

    public function scopeForAction(Builder $query, ActivityAction $action): Builder
    {
        return $query->where('action', $action);
    }

    public function getFormattedDescriptionAttribute(): string
    {
        $userName = $this->user?->name ?? 'System';
        $action = $this->action->pastTense();
        $target = $this->loggable?->name ?? $this->description ?? 'item';

        return "{$userName} {$action} {$target}";
    }
}
```

### ClientAccessLog Model

**File:** `app/Models/ClientAccessLog.php`

```php
<?php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ClientAccessLog extends Model
{
    use HasFactory, HasUlid;

    public $timestamps = false;

    protected $fillable = [
        'tax_client_id',
        'firm_user_id',
        'accessed_at',
    ];

    protected function casts(): array
    {
        return [
            'accessed_at' => 'datetime',
        ];
    }

    public function taxClient(): BelongsTo
    {
        return $this->belongsTo(TaxClient::class);
    }

    public function firmUser(): BelongsTo
    {
        return $this->belongsTo(FirmUser::class);
    }

    public function scopeForUser(Builder $query, FirmUser $user): Builder
    {
        return $query->where('firm_user_id', $user->id);
    }

    public function scopeRecent(Builder $query, int $limit = 5): Builder
    {
        return $query->orderByDesc('accessed_at')->limit($limit);
    }

    public static function recordAccess(TaxClient $client, FirmUser $user): self
    {
        return self::updateOrCreate(
            [
                'tax_client_id' => $client->id,
                'firm_user_id' => $user->id,
            ],
            [
                'accessed_at' => now(),
            ]
        );
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
use Illuminate\Database\Eloquent\Builder;

class ClientUserAssignment extends Model
{
    use HasFactory, HasUlid;

    protected $fillable = [
        'tax_client_id',
        'firm_user_id',
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

    public function taxClient(): BelongsTo
    {
        return $this->belongsTo(TaxClient::class);
    }

    public function firmUser(): BelongsTo
    {
        return $this->belongsTo(FirmUser::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(FirmUser::class, 'assigned_by');
    }

    public function scopeForClient(Builder $query, TaxClient $client): Builder
    {
        return $query->where('tax_client_id', $client->id);
    }

    public function scopeForUser(Builder $query, FirmUser $user): Builder
    {
        return $query->where('firm_user_id', $user->id);
    }

    public static function assign(TaxClient $client, FirmUser $user, ?FirmUser $assigner = null, ?string $notes = null): self
    {
        return self::create([
            'tax_client_id' => $client->id,
            'firm_user_id' => $user->id,
            'assigned_by' => $assigner?->id,
            'assigned_at' => now(),
            'notes' => $notes,
        ]);
    }
}
```

---

## Seeders

### IndustrySeeder

**File:** `database/seeders/IndustrySeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Models\Industry;
use Illuminate\Database\Seeder;

class IndustrySeeder extends Seeder
{
    public function run(): void
    {
        $industries = [
            'Agriculture, Forestry & Fishing' => [
                ['name' => 'Crop Production', 'code' => '01'],
                ['name' => 'Animal Production', 'code' => '02'],
                ['name' => 'Forestry & Logging', 'code' => '03'],
                ['name' => 'Fishing & Aquaculture', 'code' => '04'],
            ],
            'Mining & Quarrying' => [
                ['name' => 'Oil & Gas Extraction', 'code' => '05'],
                ['name' => 'Metal Ore Mining', 'code' => '06'],
                ['name' => 'Quarrying & Other Mining', 'code' => '07'],
            ],
            'Manufacturing' => [
                ['name' => 'Food Products', 'code' => '10'],
                ['name' => 'Beverages', 'code' => '11'],
                ['name' => 'Textiles', 'code' => '13'],
                ['name' => 'Wearing Apparel', 'code' => '14'],
                ['name' => 'Leather & Related Products', 'code' => '15'],
                ['name' => 'Wood Products', 'code' => '16'],
                ['name' => 'Paper Products', 'code' => '17'],
                ['name' => 'Printing & Recorded Media', 'code' => '18'],
                ['name' => 'Chemicals & Chemical Products', 'code' => '20'],
                ['name' => 'Pharmaceuticals', 'code' => '21'],
                ['name' => 'Rubber & Plastics', 'code' => '22'],
                ['name' => 'Non-Metallic Minerals', 'code' => '23'],
                ['name' => 'Basic Metals', 'code' => '24'],
                ['name' => 'Fabricated Metal Products', 'code' => '25'],
                ['name' => 'Electronics & Optical', 'code' => '26'],
                ['name' => 'Electrical Equipment', 'code' => '27'],
                ['name' => 'Machinery & Equipment', 'code' => '28'],
                ['name' => 'Motor Vehicles', 'code' => '29'],
                ['name' => 'Other Transport Equipment', 'code' => '30'],
                ['name' => 'Furniture', 'code' => '31'],
                ['name' => 'Other Manufacturing', 'code' => '32'],
            ],
            'Electricity, Gas, Steam & Air Conditioning' => [
                ['name' => 'Electric Power Generation', 'code' => '35'],
                ['name' => 'Gas Distribution', 'code' => '35'],
            ],
            'Water Supply & Waste Management' => [
                ['name' => 'Water Collection & Supply', 'code' => '36'],
                ['name' => 'Sewerage', 'code' => '37'],
                ['name' => 'Waste Collection & Disposal', 'code' => '38'],
            ],
            'Construction' => [
                ['name' => 'Building Construction', 'code' => '41'],
                ['name' => 'Civil Engineering', 'code' => '42'],
                ['name' => 'Specialized Construction', 'code' => '43'],
            ],
            'Wholesale & Retail Trade' => [
                ['name' => 'Motor Vehicle Trade & Repair', 'code' => '45'],
                ['name' => 'Wholesale Trade', 'code' => '46'],
                ['name' => 'Retail Trade', 'code' => '47'],
            ],
            'Transportation & Storage' => [
                ['name' => 'Land Transport', 'code' => '49'],
                ['name' => 'Water Transport', 'code' => '50'],
                ['name' => 'Air Transport', 'code' => '51'],
                ['name' => 'Warehousing & Storage', 'code' => '52'],
                ['name' => 'Postal & Courier Activities', 'code' => '53'],
            ],
            'Accommodation & Food Services' => [
                ['name' => 'Accommodation', 'code' => '55'],
                ['name' => 'Food & Beverage Services', 'code' => '56'],
            ],
            'Information & Communication' => [
                ['name' => 'Publishing', 'code' => '58'],
                ['name' => 'Motion Picture & Sound', 'code' => '59'],
                ['name' => 'Broadcasting', 'code' => '60'],
                ['name' => 'Telecommunications', 'code' => '61'],
                ['name' => 'Computer Programming & IT', 'code' => '62'],
                ['name' => 'Information Services', 'code' => '63'],
            ],
            'Financial & Insurance Services' => [
                ['name' => 'Banking & Financial Services', 'code' => '64'],
                ['name' => 'Insurance & Pension', 'code' => '65'],
                ['name' => 'Investment & Fund Management', 'code' => '66'],
            ],
            'Real Estate' => [
                ['name' => 'Real Estate Activities', 'code' => '68'],
            ],
            'Professional, Scientific & Technical' => [
                ['name' => 'Legal Services', 'code' => '69'],
                ['name' => 'Accounting & Auditing', 'code' => '69'],
                ['name' => 'Management Consulting', 'code' => '70'],
                ['name' => 'Architecture & Engineering', 'code' => '71'],
                ['name' => 'Scientific Research', 'code' => '72'],
                ['name' => 'Advertising & Market Research', 'code' => '73'],
                ['name' => 'Other Professional Services', 'code' => '74'],
                ['name' => 'Veterinary Activities', 'code' => '75'],
            ],
            'Administrative & Support Services' => [
                ['name' => 'Rental & Leasing', 'code' => '77'],
                ['name' => 'Employment Activities', 'code' => '78'],
                ['name' => 'Travel & Tour Services', 'code' => '79'],
                ['name' => 'Security & Investigation', 'code' => '80'],
                ['name' => 'Facility Management', 'code' => '81'],
                ['name' => 'Office Support Activities', 'code' => '82'],
            ],
            'Education' => [
                ['name' => 'Primary Education', 'code' => '85'],
                ['name' => 'Secondary Education', 'code' => '85'],
                ['name' => 'Higher Education', 'code' => '85'],
                ['name' => 'Other Education', 'code' => '85'],
            ],
            'Healthcare & Social Work' => [
                ['name' => 'Hospital Activities', 'code' => '86'],
                ['name' => 'Medical & Dental Practice', 'code' => '86'],
                ['name' => 'Residential Care', 'code' => '87'],
                ['name' => 'Social Work', 'code' => '88'],
            ],
            'Arts, Entertainment & Recreation' => [
                ['name' => 'Creative Arts & Entertainment', 'code' => '90'],
                ['name' => 'Libraries & Museums', 'code' => '91'],
                ['name' => 'Sports & Amusement', 'code' => '93'],
            ],
            'Other Services' => [
                ['name' => 'Membership Organizations', 'code' => '94'],
                ['name' => 'Repair of Goods', 'code' => '95'],
                ['name' => 'Personal Services', 'code' => '96'],
            ],
        ];

        $sortOrder = 0;

        foreach ($industries as $sector => $items) {
            foreach ($items as $item) {
                Industry::create([
                    'sector' => $sector,
                    'name' => $item['name'],
                    'code' => $item['code'],
                    'is_active' => true,
                    'sort_order' => $sortOrder++,
                ]);
            }
        }
    }
}
```

---

## Factories

### TaxClientFactory

**File:** `database/factories/TaxClientFactory.php`

```php
<?php

namespace Database\Factories;

use App\Enums\ClientStatus;
use App\Enums\CompanySize;
use App\Enums\EmploymentStatus;
use App\Enums\EntityType;
use App\Enums\MaritalStatus;
use App\Enums\PartnershipType;
use App\Models\Firm;
use App\Models\FirmUser;
use App\Models\Industry;
use App\Models\TaxClient;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxClientFactory extends Factory
{
    protected $model = TaxClient::class;

    public function definition(): array
    {
        $entityType = $this->faker->randomElement(EntityType::cases());

        return [
            'firm_id' => Firm::factory(),
            'entity_type' => $entityType,
            'name' => $this->generateName($entityType),
            'trading_name' => $this->faker->optional(0.3)->company(),
            'tin' => $this->faker->optional(0.8)->numerify('##########-####'),
            'email' => $this->faker->optional(0.9)->companyEmail(),
            'phone' => $this->faker->optional(0.8)->phoneNumber(),
            'address' => $this->faker->optional(0.7)->address(),
            'state' => $this->faker->randomElement($this->nigerianStates()),
            'status' => ClientStatus::ACTIVE,
            'portal_enabled' => false,
            'created_by' => FirmUser::factory(),
        ];
    }

    private function generateName(EntityType $type): string
    {
        return match ($type) {
            EntityType::COMPANY_LLC, EntityType::COMPANY_PLC => $this->faker->company() . ' ' . $this->faker->companySuffix(),
            EntityType::PARTNERSHIP => $this->faker->lastName() . ' & ' . $this->faker->lastName() . ' Partners',
            EntityType::SOLE_PROPRIETORSHIP => $this->faker->firstName() . ' ' . $this->faker->lastName() . ' Enterprises',
            EntityType::INDIVIDUAL => $this->faker->firstName() . ' ' . $this->faker->lastName(),
            EntityType::TRUST_ESTATE => 'The ' . $this->faker->lastName() . ' ' . $this->faker->randomElement(['Family Trust', 'Estate', 'Foundation']),
        };
    }

    private function nigerianStates(): array
    {
        return [
            'Lagos', 'Abuja FCT', 'Rivers', 'Kano', 'Oyo', 'Kaduna', 'Delta', 'Ogun',
            'Anambra', 'Enugu', 'Edo', 'Imo', 'Kwara', 'Plateau', 'Cross River',
        ];
    }

    public function company(): static
    {
        return $this->state(fn (array $attrs) => [
            'entity_type' => EntityType::COMPANY_LLC,
            'cac_number' => 'RC' . $this->faker->numerify('######'),
            'incorporation_date' => $this->faker->dateTimeBetween('-20 years', '-1 year'),
            'fiscal_year_end' => '12-31',
            'industry_id' => Industry::inRandomOrder()->first()?->id,
            'company_size' => $this->faker->randomElement(CompanySize::cases()),
        ]);
    }

    public function individual(): static
    {
        return $this->state(fn (array $attrs) => [
            'entity_type' => EntityType::INDIVIDUAL,
            'name' => $this->faker->firstName() . ' ' . $this->faker->lastName(),
            'nin' => $this->faker->numerify('###########'),
            'date_of_birth' => $this->faker->dateTimeBetween('-70 years', '-25 years'),
            'marital_status' => $this->faker->randomElement(MaritalStatus::cases()),
            'employment_status' => $this->faker->randomElement(EmploymentStatus::cases()),
        ]);
    }

    public function partnership(): static
    {
        return $this->state(fn (array $attrs) => [
            'entity_type' => EntityType::PARTNERSHIP,
            'partnership_type' => $this->faker->randomElement(PartnershipType::cases()),
            'number_of_partners' => $this->faker->numberBetween(2, 10),
            'incorporation_date' => $this->faker->dateTimeBetween('-15 years', '-6 months'),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attrs) => [
            'status' => ClientStatus::ARCHIVED,
        ]);
    }
}
```

---

## Next Steps

Once migrations are created and run, proceed to:
→ **02_DASHBOARD_BACKEND.md** - Implement dashboard controller and services
