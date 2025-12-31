# TaxLab Implementation - Module 4 Database Schema

## Overview

This document defines the database migrations, Eloquent models, and enums for the Financial Data Entry module.

## Migration Order

Migrations must be created in dependency order:

1. `create_client_financials_table`
2. `create_client_financial_versions_table`

---

## Migration 1: Client Financials

**File:** `database/migrations/xxxx_xx_xx_000200_create_client_financials_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_financials', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tax_client_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('firm_id')->constrained()->cascadeOnDelete();

            $table->unsignedSmallInteger('fiscal_year');
            $table->date('fiscal_year_start');
            $table->date('fiscal_year_end');

            $table->enum('data_source', ['audited', 'management', 'computed', 'projected'])
                ->default('management');

            $table->json('income_data')->nullable();
            $table->json('balance_sheet_data')->nullable();
            $table->json('employment_data')->nullable();
            $table->json('vat_data')->nullable();
            $table->json('capital_data')->nullable();
            $table->json('individual_data')->nullable();
            $table->json('partnership_data')->nullable();

            $table->boolean('is_complete')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->timestamp('locked_at')->nullable();
            $table->foreignUlid('locked_by')->nullable()->constrained('firm_users')->nullOnDelete();

            $table->unsignedInteger('version')->default(1);
            $table->foreignUlid('verified_by')->nullable()->constrained('firm_users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();

            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->constrained('firm_users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tax_client_id', 'fiscal_year']);
            $table->index(['firm_id', 'fiscal_year']);
            $table->index(['tax_client_id', 'is_locked']);
            $table->index(['firm_id', 'is_complete']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_financials');
    }
};
```

---

## Migration 2: Client Financial Versions

**File:** `database/migrations/xxxx_xx_xx_000210_create_client_financial_versions_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_financial_versions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('client_financial_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('data_snapshot');
            $table->text('change_summary')->nullable();
            $table->foreignUlid('created_by')->constrained('firm_users')->cascadeOnDelete();
            $table->timestamp('created_at');

            $table->unique(['client_financial_id', 'version']);
            $table->index(['client_financial_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_financial_versions');
    }
};
```

---

## Enums

### DataSource

**File:** `app/Enums/DataSource.php`

```php
<?php

namespace App\Enums;

enum DataSource: string
{
    case AUDITED = 'audited';
    case MANAGEMENT = 'management';
    case COMPUTED = 'computed';
    case PROJECTED = 'projected';

    public function label(): string
    {
        return match ($this) {
            self::AUDITED => 'Audited Financial Statements',
            self::MANAGEMENT => 'Management Accounts',
            self::COMPUTED => 'Tax Computations',
            self::PROJECTED => 'Projections/Estimates',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::AUDITED => 'Audited',
            self::MANAGEMENT => 'Management',
            self::COMPUTED => 'Computed',
            self::PROJECTED => 'Projected',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::AUDITED => 'green',
            self::MANAGEMENT => 'blue',
            self::COMPUTED => 'yellow',
            self::PROJECTED => 'gray',
        };
    }

    public function reliability(): int
    {
        return match ($this) {
            self::AUDITED => 100,
            self::MANAGEMENT => 80,
            self::COMPUTED => 60,
            self::PROJECTED => 40,
        };
    }

    public static function options(): array
    {
        return array_map(
            fn (self $source) => [
                'value' => $source->value,
                'label' => $source->label(),
                'shortLabel' => $source->shortLabel(),
                'color' => $source->color(),
            ],
            self::cases()
        );
    }
}
```

### FinancialSection

**File:** `app/Enums/FinancialSection.php`

```php
<?php

namespace App\Enums;

enum FinancialSection: string
{
    case INCOME = 'income';
    case BALANCE_SHEET = 'balance_sheet';
    case EMPLOYMENT = 'employment';
    case VAT = 'vat';
    case CAPITAL = 'capital';
    case INDIVIDUAL = 'individual';
    case PARTNERSHIP = 'partnership';

    public function label(): string
    {
        return match ($this) {
            self::INCOME => 'Income Statement',
            self::BALANCE_SHEET => 'Balance Sheet',
            self::EMPLOYMENT => 'Employment',
            self::VAT => 'VAT',
            self::CAPITAL => 'Capital Transactions',
            self::INDIVIDUAL => 'Individual Data',
            self::PARTNERSHIP => 'Partnership Data',
        };
    }

    public function dataColumn(): string
    {
        return match ($this) {
            self::INCOME => 'income_data',
            self::BALANCE_SHEET => 'balance_sheet_data',
            self::EMPLOYMENT => 'employment_data',
            self::VAT => 'vat_data',
            self::CAPITAL => 'capital_data',
            self::INDIVIDUAL => 'individual_data',
            self::PARTNERSHIP => 'partnership_data',
        };
    }

    public function requiredFields(EntityType $entityType): array
    {
        return match ($this) {
            self::INCOME => ['turnover', 'grossProfit', 'profitBeforeTax'],
            self::BALANCE_SHEET => ['totalAssets', 'fixedAssets', 'totalLiabilities', 'totalEquity'],
            self::EMPLOYMENT => ['employeeCount', 'totalPayroll'],
            self::VAT => ['vatableSales', 'vatOutput', 'vatablePurchases', 'vatInputGoods', 'vatInputServices'],
            self::CAPITAL => [],
            self::INDIVIDUAL => $entityType->isIndividual() ? ['grossSalary'] : [],
            self::PARTNERSHIP => $entityType->isPartnership() ? ['numberOfPartners', 'partnerProfitShares'] : [],
        };
    }

    public static function forEntityType(EntityType $entityType): array
    {
        $sections = [
            self::INCOME,
            self::BALANCE_SHEET,
            self::EMPLOYMENT,
            self::VAT,
            self::CAPITAL,
        ];

        if ($entityType->isIndividual()) {
            $sections[] = self::INDIVIDUAL;
        }

        if ($entityType->isPartnership()) {
            $sections[] = self::PARTNERSHIP;
        }

        return $sections;
    }
}
```

---

## Models

### ClientFinancial Model

**File:** `app/Models/ClientFinancial.php`

```php
<?php

namespace App\Models;

use App\Enums\DataSource;
use App\Enums\FinancialSection;
use App\Models\Concerns\BelongsToFirm;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class ClientFinancial extends Model
{
    use BelongsToFirm, HasFactory, HasUlid;

    protected $fillable = [
        'tax_client_id',
        'firm_id',
        'fiscal_year',
        'fiscal_year_start',
        'fiscal_year_end',
        'data_source',
        'income_data',
        'balance_sheet_data',
        'employment_data',
        'vat_data',
        'capital_data',
        'individual_data',
        'partnership_data',
        'is_complete',
        'is_locked',
        'locked_at',
        'locked_by',
        'version',
        'verified_by',
        'verified_at',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year' => 'integer',
            'fiscal_year_start' => 'date',
            'fiscal_year_end' => 'date',
            'data_source' => DataSource::class,
            'income_data' => 'array',
            'balance_sheet_data' => 'array',
            'employment_data' => 'array',
            'vat_data' => 'array',
            'capital_data' => 'array',
            'individual_data' => 'array',
            'partnership_data' => 'array',
            'is_complete' => 'boolean',
            'is_locked' => 'boolean',
            'locked_at' => 'datetime',
            'version' => 'integer',
            'verified_at' => 'datetime',
        ];
    }

    public function taxClient(): BelongsTo
    {
        return $this->belongsTo(TaxClient::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(FirmUser::class, 'created_by');
    }

    public function lockedByUser(): BelongsTo
    {
        return $this->belongsTo(FirmUser::class, 'locked_by');
    }

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(FirmUser::class, 'verified_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ClientFinancialVersion::class)->orderByDesc('version');
    }

    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->where('fiscal_year', $year);
    }

    public function scopeLocked(Builder $query): Builder
    {
        return $query->where('is_locked', true);
    }

    public function scopeUnlocked(Builder $query): Builder
    {
        return $query->where('is_locked', false);
    }

    public function scopeComplete(Builder $query): Builder
    {
        return $query->where('is_complete', true);
    }

    public function scopeIncomplete(Builder $query): Builder
    {
        return $query->where('is_complete', false);
    }

    public function createVersion(FirmUser $user, ?string $changeSummary = null): ClientFinancialVersion
    {
        return $this->versions()->create([
            'version' => $this->version,
            'data_snapshot' => $this->getDataSnapshot(),
            'change_summary' => $changeSummary,
            'created_by' => $user->id,
            'created_at' => now(),
        ]);
    }

    public function getDataSnapshot(): array
    {
        return [
            'data_source' => $this->data_source?->value,
            'income_data' => $this->income_data,
            'balance_sheet_data' => $this->balance_sheet_data,
            'employment_data' => $this->employment_data,
            'vat_data' => $this->vat_data,
            'capital_data' => $this->capital_data,
            'individual_data' => $this->individual_data,
            'partnership_data' => $this->partnership_data,
        ];
    }

    public function restoreFromSnapshot(array $snapshot): void
    {
        $this->update([
            'data_source' => $snapshot['data_source'] ?? $this->data_source,
            'income_data' => $snapshot['income_data'] ?? null,
            'balance_sheet_data' => $snapshot['balance_sheet_data'] ?? null,
            'employment_data' => $snapshot['employment_data'] ?? null,
            'vat_data' => $snapshot['vat_data'] ?? null,
            'capital_data' => $snapshot['capital_data'] ?? null,
            'individual_data' => $snapshot['individual_data'] ?? null,
            'partnership_data' => $snapshot['partnership_data'] ?? null,
        ]);
    }

    public function getCompletionPercentage(): int
    {
        $entityType = $this->taxClient->entity_type;
        $sections = FinancialSection::forEntityType($entityType);

        $totalRequired = 0;
        $totalFilled = 0;

        foreach ($sections as $section) {
            $requiredFields = $section->requiredFields($entityType);
            $data = $this->{$section->dataColumn()} ?? [];

            $totalRequired += count($requiredFields);
            foreach ($requiredFields as $field) {
                if (isset($data[$field]) && $data[$field] !== null && $data[$field] !== '') {
                    $totalFilled++;
                }
            }
        }

        if ($totalRequired === 0) {
            return 100;
        }

        return (int) round(($totalFilled / $totalRequired) * 100);
    }

    public function updateCompletionStatus(): void
    {
        $this->update(['is_complete' => $this->getCompletionPercentage() === 100]);
    }

    public function lock(FirmUser $user): void
    {
        $this->update([
            'is_locked' => true,
            'locked_at' => now(),
            'locked_by' => $user->id,
        ]);
    }

    public function unlock(): void
    {
        $this->update([
            'is_locked' => false,
            'locked_at' => null,
            'locked_by' => null,
        ]);
    }

    public function verify(FirmUser $user): void
    {
        $this->update([
            'verified_by' => $user->id,
            'verified_at' => now(),
        ]);
    }

    public function incrementVersion(): void
    {
        $this->increment('version');
    }

    public function isLocked(): bool
    {
        return $this->is_locked;
    }

    public function isComplete(): bool
    {
        return $this->is_complete;
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function canBeEdited(): bool
    {
        return !$this->is_locked;
    }

    public function getSectionData(FinancialSection $section): array
    {
        return $this->{$section->dataColumn()} ?? [];
    }

    public function setSectionData(FinancialSection $section, array $data): void
    {
        $this->update([$section->dataColumn() => $data]);
    }

    public function getTurnover(): ?float
    {
        return $this->income_data['turnover'] ?? null;
    }

    public function getProfitBeforeTax(): ?float
    {
        return $this->income_data['profitBeforeTax'] ?? null;
    }

    public function getTotalAssets(): ?float
    {
        return $this->balance_sheet_data['totalAssets'] ?? null;
    }

    public function getEmployeeCount(): ?int
    {
        return $this->employment_data['employeeCount'] ?? null;
    }
}
```

### ClientFinancialVersion Model

**File:** `app/Models/ClientFinancialVersion.php`

```php
<?php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ClientFinancialVersion extends Model
{
    use HasFactory, HasUlid;

    public $timestamps = false;

    protected $fillable = [
        'client_financial_id',
        'version',
        'data_snapshot',
        'change_summary',
        'created_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'data_snapshot' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function clientFinancial(): BelongsTo
    {
        return $this->belongsTo(ClientFinancial::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(FirmUser::class, 'created_by');
    }

    public function scopeForFinancial(Builder $query, ClientFinancial $financial): Builder
    {
        return $query->where('client_financial_id', $financial->id);
    }

    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderByDesc('version');
    }

    public function restore(FirmUser $user): void
    {
        $financial = $this->clientFinancial;

        $financial->createVersion($user, "Restored from version {$this->version}");

        $financial->restoreFromSnapshot($this->data_snapshot);

        $financial->incrementVersion();
        $financial->updateCompletionStatus();
    }

    public function getDataDifference(ClientFinancialVersion $other): array
    {
        $diff = [];
        $sections = ['income_data', 'balance_sheet_data', 'employment_data', 'vat_data', 'capital_data'];

        foreach ($sections as $section) {
            $thisData = $this->data_snapshot[$section] ?? [];
            $otherData = $other->data_snapshot[$section] ?? [];

            if ($thisData !== $otherData) {
                $diff[$section] = [
                    'before' => $otherData,
                    'after' => $thisData,
                ];
            }
        }

        return $diff;
    }
}
```

---

## Update TaxClient Model

Add relationship to TaxClient model:

**File:** `app/Models/TaxClient.php` (add to existing)

```php
public function financials(): HasMany
{
    return $this->hasMany(ClientFinancial::class);
}

public function getLatestFinancial(): ?ClientFinancial
{
    return $this->financials()->orderByDesc('fiscal_year')->first();
}

public function getFinancialForYear(int $year): ?ClientFinancial
{
    return $this->financials()->forYear($year)->first();
}
```

---

## Factories

### ClientFinancialFactory

**File:** `database/factories/ClientFinancialFactory.php`

```php
<?php

namespace Database\Factories;

use App\Enums\DataSource;
use App\Models\ClientFinancial;
use App\Models\Firm;
use App\Models\FirmUser;
use App\Models\TaxClient;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFinancialFactory extends Factory
{
    protected $model = ClientFinancial::class;

    public function definition(): array
    {
        $fiscalYear = $this->faker->numberBetween(2020, 2024);
        $turnover = $this->faker->numberBetween(10_000_000, 500_000_000);
        $costOfSales = (int) ($turnover * $this->faker->randomFloat(2, 0.4, 0.7));
        $grossProfit = $turnover - $costOfSales;
        $operatingExpenses = (int) ($grossProfit * $this->faker->randomFloat(2, 0.3, 0.6));
        $profitBeforeTax = $grossProfit - $operatingExpenses;

        return [
            'tax_client_id' => TaxClient::factory(),
            'firm_id' => Firm::factory(),
            'fiscal_year' => $fiscalYear,
            'fiscal_year_start' => "{$fiscalYear}-01-01",
            'fiscal_year_end' => "{$fiscalYear}-12-31",
            'data_source' => $this->faker->randomElement(DataSource::cases()),
            'income_data' => [
                'turnover' => $turnover,
                'costOfSales' => $costOfSales,
                'grossProfit' => $grossProfit,
                'operatingExpenses' => $operatingExpenses,
                'profitBeforeTax' => $profitBeforeTax,
            ],
            'balance_sheet_data' => [
                'totalAssets' => $this->faker->numberBetween(50_000_000, 1_000_000_000),
                'fixedAssets' => $this->faker->numberBetween(20_000_000, 500_000_000),
                'currentAssets' => $this->faker->numberBetween(10_000_000, 200_000_000),
                'totalLiabilities' => $this->faker->numberBetween(20_000_000, 400_000_000),
                'totalEquity' => $this->faker->numberBetween(30_000_000, 600_000_000),
            ],
            'employment_data' => [
                'employeeCount' => $this->faker->numberBetween(5, 500),
                'totalPayroll' => $this->faker->numberBetween(10_000_000, 200_000_000),
            ],
            'is_complete' => false,
            'is_locked' => false,
            'version' => 1,
            'created_by' => FirmUser::factory(),
        ];
    }

    public function complete(): static
    {
        return $this->state(fn (array $attrs) => [
            'is_complete' => true,
        ]);
    }

    public function locked(): static
    {
        return $this->state(fn (array $attrs) => [
            'is_locked' => true,
            'locked_at' => now(),
            'locked_by' => FirmUser::factory(),
        ]);
    }

    public function audited(): static
    {
        return $this->state(fn (array $attrs) => [
            'data_source' => DataSource::AUDITED,
        ]);
    }

    public function forYear(int $year): static
    {
        return $this->state(fn (array $attrs) => [
            'fiscal_year' => $year,
            'fiscal_year_start' => "{$year}-01-01",
            'fiscal_year_end' => "{$year}-12-31",
        ]);
    }
}
```

---

## Next Steps

Once migrations are created and run, proceed to:
→ **02_BACKEND_SERVICES.md** - Implement services and validation
