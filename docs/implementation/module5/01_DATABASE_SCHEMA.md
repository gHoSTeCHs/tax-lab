# TaxLab Implementation - Module 5 Database Schema

## Overview

This document defines the database migrations, Eloquent models, and enums for the Tax Calculation Engine module.

---

## Migration: Calculations

**File:** `database/migrations/xxxx_xx_xx_000300_create_calculations_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calculations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tax_client_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('firm_id')->constrained()->cascadeOnDelete();

            $table->unsignedSmallInteger('fiscal_year');

            $table->enum('calculation_type', [
                'full_analysis',
                'cit_only',
                'pit_only',
                'vat_only',
                'cgt_only',
                'paye_only',
                'quick_estimate',
            ])->default('full_analysis');

            $table->string('rules_version', 20);
            $table->unsignedInteger('financial_version');

            $table->json('inputs');
            $table->json('results');
            $table->json('summary');
            $table->json('optimizations')->nullable();

            $table->enum('status', ['completed', 'error', 'superseded'])
                ->default('completed');
            $table->text('error_message')->nullable();

            $table->foreignUlid('performed_by')->constrained('firm_users')->cascadeOnDelete();
            $table->unsignedInteger('calculation_time_ms')->nullable();

            $table->timestamp('created_at');

            $table->index(['tax_client_id', 'fiscal_year', 'created_at']);
            $table->index(['firm_id', 'created_at']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calculations');
    }
};
```

---

## Enums

### CalculationType

**File:** `app/Enums/CalculationType.php`

```php
<?php

namespace App\Enums;

enum CalculationType: string
{
    case FULL_ANALYSIS = 'full_analysis';
    case CIT_ONLY = 'cit_only';
    case PIT_ONLY = 'pit_only';
    case VAT_ONLY = 'vat_only';
    case CGT_ONLY = 'cgt_only';
    case PAYE_ONLY = 'paye_only';
    case QUICK_ESTIMATE = 'quick_estimate';

    public function label(): string
    {
        return match ($this) {
            self::FULL_ANALYSIS => 'Full Analysis',
            self::CIT_ONLY => 'Company Income Tax Only',
            self::PIT_ONLY => 'Personal Income Tax Only',
            self::VAT_ONLY => 'VAT Analysis Only',
            self::CGT_ONLY => 'Capital Gains Tax Only',
            self::PAYE_ONLY => 'PAYE Analysis Only',
            self::QUICK_ESTIMATE => 'Quick Estimate',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::FULL_ANALYSIS => 'Comprehensive calculation of all applicable tax types',
            self::CIT_ONLY => 'Company income tax calculation only',
            self::PIT_ONLY => 'Personal income tax calculation for individuals',
            self::VAT_ONLY => 'VAT position and input recovery analysis',
            self::CGT_ONLY => 'Capital gains tax on asset disposals',
            self::PAYE_ONLY => 'Payroll tax analysis',
            self::QUICK_ESTIMATE => 'Simplified estimate using minimal inputs',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::FULL_ANALYSIS => 'calculator',
            self::CIT_ONLY => 'building',
            self::PIT_ONLY => 'user',
            self::VAT_ONLY => 'receipt',
            self::CGT_ONLY => 'trending-up',
            self::PAYE_ONLY => 'users',
            self::QUICK_ESTIMATE => 'zap',
        };
    }

    public function applicableToEntityType(EntityType $entityType): bool
    {
        return match ($this) {
            self::FULL_ANALYSIS, self::QUICK_ESTIMATE => true,
            self::CIT_ONLY => $entityType->isCompany(),
            self::PIT_ONLY => $entityType->isIndividual(),
            self::VAT_ONLY => $entityType->isCompany(),
            self::CGT_ONLY => true,
            self::PAYE_ONLY => $entityType->isCompany(),
        };
    }

    public static function options(): array
    {
        return array_map(
            fn (self $type) => [
                'value' => $type->value,
                'label' => $type->label(),
                'description' => $type->description(),
                'icon' => $type->icon(),
            ],
            self::cases()
        );
    }

    public static function forEntityType(EntityType $entityType): array
    {
        return array_filter(
            self::cases(),
            fn (self $type) => $type->applicableToEntityType($entityType)
        );
    }
}
```

### CalculationStatus

**File:** `app/Enums/CalculationStatus.php`

```php
<?php

namespace App\Enums;

enum CalculationStatus: string
{
    case COMPLETED = 'completed';
    case ERROR = 'error';
    case SUPERSEDED = 'superseded';

    public function label(): string
    {
        return match ($this) {
            self::COMPLETED => 'Completed',
            self::ERROR => 'Error',
            self::SUPERSEDED => 'Superseded',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::COMPLETED => 'green',
            self::ERROR => 'red',
            self::SUPERSEDED => 'gray',
        };
    }
}
```

### TaxRegime

**File:** `app/Enums/TaxRegime.php`

```php
<?php

namespace App\Enums;

enum TaxRegime: string
{
    case OLD = 'old';
    case NEW = 'new';

    public function label(): string
    {
        return match ($this) {
            self::OLD => 'Old Regime (Pre-NTA 2025)',
            self::NEW => 'New Regime (NTA 2025)',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::OLD => 'Old',
            self::NEW => 'New (NTA 2025)',
        };
    }
}
```

### CompanyClassification

**File:** `app/Enums/CompanyClassification.php`

```php
<?php

namespace App\Enums;

enum CompanyClassification: string
{
    case SMALL = 'small';
    case MEDIUM = 'medium';
    case LARGE = 'large';

    public function label(): string
    {
        return match ($this) {
            self::SMALL => 'Small Company',
            self::MEDIUM => 'Medium Company',
            self::LARGE => 'Large Company',
        };
    }

    public function getCitRate(TaxRegime $regime): float
    {
        return match ($this) {
            self::SMALL => 0.0,
            self::MEDIUM => 0.20,
            self::LARGE => $regime === TaxRegime::OLD ? 0.30 : 0.25,
        };
    }

    public static function fromTurnover(float $turnover, TaxRegime $regime): self
    {
        $smallThreshold = $regime === TaxRegime::OLD ? 25_000_000 : 50_000_000;
        $mediumThreshold = 100_000_000;

        if ($turnover <= $smallThreshold) {
            return self::SMALL;
        }

        if ($turnover <= $mediumThreshold) {
            return self::MEDIUM;
        }

        return self::LARGE;
    }
}
```

### OptimizationType

**File:** `app/Enums/OptimizationType.php`

```php
<?php

namespace App\Enums;

enum OptimizationType: string
{
    case TIMING = 'timing';
    case STRUCTURAL = 'structural';
    case COMPLIANCE = 'compliance';

    public function label(): string
    {
        return match ($this) {
            self::TIMING => 'Timing Opportunity',
            self::STRUCTURAL => 'Structural Opportunity',
            self::COMPLIANCE => 'Compliance Opportunity',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::TIMING => 'Opportunities related to timing of income or expenses',
            self::STRUCTURAL => 'Opportunities through business restructuring',
            self::COMPLIANCE => 'Missed deductions or credits that can be claimed',
        };
    }
}
```

### OptimizationPriority

**File:** `app/Enums/OptimizationPriority.php`

```php
<?php

namespace App\Enums;

enum OptimizationPriority: string
{
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';

    public function label(): string
    {
        return match ($this) {
            self::HIGH => 'High Priority',
            self::MEDIUM => 'Medium Priority',
            self::LOW => 'Low Priority',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::HIGH => 'red',
            self::MEDIUM => 'yellow',
            self::LOW => 'blue',
        };
    }
}
```

---

## Model

### Calculation Model

**File:** `app/Models/Calculation.php`

```php
<?php

namespace App\Models;

use App\Enums\CalculationStatus;
use App\Enums\CalculationType;
use App\Models\Concerns\BelongsToFirm;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Calculation extends Model
{
    use BelongsToFirm, HasFactory, HasUlid;

    public $timestamps = false;

    protected $fillable = [
        'tax_client_id',
        'firm_id',
        'fiscal_year',
        'calculation_type',
        'rules_version',
        'financial_version',
        'inputs',
        'results',
        'summary',
        'optimizations',
        'status',
        'error_message',
        'performed_by',
        'calculation_time_ms',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year' => 'integer',
            'calculation_type' => CalculationType::class,
            'financial_version' => 'integer',
            'inputs' => 'array',
            'results' => 'array',
            'summary' => 'array',
            'optimizations' => 'array',
            'status' => CalculationStatus::class,
            'calculation_time_ms' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function taxClient(): BelongsTo
    {
        return $this->belongsTo(TaxClient::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(FirmUser::class, 'performed_by');
    }

    public function scopeForClient(Builder $query, TaxClient $client): Builder
    {
        return $query->where('tax_client_id', $client->id);
    }

    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->where('fiscal_year', $year);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', CalculationStatus::COMPLETED);
    }

    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderByDesc('created_at');
    }

    public function getOldRegimeTotalAttribute(): float
    {
        return $this->summary['old_regime_total']['total_tax_liability'] ?? 0;
    }

    public function getNewRegimeTotalAttribute(): float
    {
        return $this->summary['new_regime_total']['total_tax_liability'] ?? 0;
    }

    public function getNetSavingsAttribute(): float
    {
        return $this->old_regime_total - $this->new_regime_total;
    }

    public function getSavingsPercentageAttribute(): float
    {
        if ($this->old_regime_total === 0.0) {
            return 0;
        }

        return ($this->net_savings / $this->old_regime_total) * 100;
    }

    public function hasSavings(): bool
    {
        return $this->net_savings > 0;
    }

    public function isCompleted(): bool
    {
        return $this->status === CalculationStatus::COMPLETED;
    }

    public function hasError(): bool
    {
        return $this->status === CalculationStatus::ERROR;
    }

    public function markAsSuperseded(): void
    {
        $this->update(['status' => CalculationStatus::SUPERSEDED]);
    }

    public function getResultForTaxType(string $taxType): ?array
    {
        return $this->results[$taxType] ?? null;
    }

    public function getCitResult(): ?array
    {
        return $this->getResultForTaxType('company_income_tax');
    }

    public function getPitResult(): ?array
    {
        return $this->getResultForTaxType('personal_income_tax');
    }

    public function getVatResult(): ?array
    {
        return $this->getResultForTaxType('vat_analysis');
    }

    public function getCgtResult(): ?array
    {
        return $this->getResultForTaxType('capital_gains_tax');
    }

    public function getDevelopmentLevyResult(): ?array
    {
        return $this->getResultForTaxType('development_levy');
    }

    public function getOptimizationCount(): int
    {
        return count($this->optimizations ?? []);
    }

    public function getHighPriorityOptimizations(): array
    {
        return array_filter(
            $this->optimizations ?? [],
            fn ($opt) => ($opt['priority'] ?? '') === 'high'
        );
    }
}
```

---

## Update TaxClient Model

Add relationship to TaxClient model:

**File:** `app/Models/TaxClient.php` (add to existing)

```php
public function calculations(): HasMany
{
    return $this->hasMany(Calculation::class);
}

public function getLatestCalculation(?int $year = null): ?Calculation
{
    $query = $this->calculations()->completed()->latest();

    if ($year !== null) {
        $query->forYear($year);
    }

    return $query->first();
}

public function getCalculationsForYear(int $year): Collection
{
    return $this->calculations()
        ->forYear($year)
        ->latest()
        ->get();
}
```

---

## Factory

### CalculationFactory

**File:** `database/factories/CalculationFactory.php`

```php
<?php

namespace Database\Factories;

use App\Enums\CalculationStatus;
use App\Enums\CalculationType;
use App\Models\Calculation;
use App\Models\Firm;
use App\Models\FirmUser;
use App\Models\TaxClient;
use Illuminate\Database\Eloquent\Factories\Factory;

class CalculationFactory extends Factory
{
    protected $model = Calculation::class;

    public function definition(): array
    {
        $oldTotal = $this->faker->numberBetween(5_000_000, 20_000_000);
        $newTotal = (int) ($oldTotal * $this->faker->randomFloat(2, 0.7, 1.1));

        return [
            'tax_client_id' => TaxClient::factory(),
            'firm_id' => Firm::factory(),
            'fiscal_year' => $this->faker->numberBetween(2020, 2024),
            'calculation_type' => CalculationType::FULL_ANALYSIS,
            'rules_version' => 'v1.0',
            'financial_version' => 1,
            'inputs' => [
                'turnover' => $this->faker->numberBetween(50_000_000, 500_000_000),
                'profit_before_tax' => $this->faker->numberBetween(5_000_000, 50_000_000),
            ],
            'results' => [
                'company_income_tax' => [
                    'old_regime' => ['tax_liability' => $oldTotal * 0.4],
                    'new_regime' => ['tax_liability' => $newTotal * 0.35],
                ],
            ],
            'summary' => [
                'old_regime_total' => ['total_tax_liability' => $oldTotal],
                'new_regime_total' => ['total_tax_liability' => $newTotal],
            ],
            'optimizations' => [],
            'status' => CalculationStatus::COMPLETED,
            'performed_by' => FirmUser::factory(),
            'calculation_time_ms' => $this->faker->numberBetween(100, 5000),
            'created_at' => now(),
        ];
    }

    public function fullAnalysis(): static
    {
        return $this->state(fn (array $attrs) => [
            'calculation_type' => CalculationType::FULL_ANALYSIS,
        ]);
    }

    public function citOnly(): static
    {
        return $this->state(fn (array $attrs) => [
            'calculation_type' => CalculationType::CIT_ONLY,
        ]);
    }

    public function withError(): static
    {
        return $this->state(fn (array $attrs) => [
            'status' => CalculationStatus::ERROR,
            'error_message' => 'Insufficient financial data for calculation',
            'results' => [],
            'summary' => [],
        ]);
    }

    public function superseded(): static
    {
        return $this->state(fn (array $attrs) => [
            'status' => CalculationStatus::SUPERSEDED,
        ]);
    }

    public function forYear(int $year): static
    {
        return $this->state(fn (array $attrs) => [
            'fiscal_year' => $year,
        ]);
    }

    public function withOptimizations(): static
    {
        return $this->state(fn (array $attrs) => [
            'optimizations' => [
                [
                    'type' => 'timing',
                    'title' => 'Defer Revenue',
                    'description' => 'Consider deferring revenue to next fiscal year',
                    'estimated_savings' => 500_000,
                    'priority' => 'high',
                    'action_required' => 'Review contracts',
                ],
                [
                    'type' => 'compliance',
                    'title' => 'Unclaimed Capital Allowances',
                    'description' => 'Capital allowances may not be fully utilized',
                    'estimated_savings' => 200_000,
                    'priority' => 'medium',
                    'action_required' => 'Review fixed asset register',
                ],
            ],
        ]);
    }
}
```

---

## Next Steps

Once migrations and models are created, proceed to:
→ **02_TAX_RULES.md** - Tax constants and rules repository
