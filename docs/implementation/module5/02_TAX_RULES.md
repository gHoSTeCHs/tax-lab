# TaxLab Implementation - Module 5: Tax Rules & Constants

## Overview

This document defines the tax constants, rates, thresholds, and the rules repository system that provides versioned access to Nigerian tax legislation parameters for both old and new (NTA 2025) regimes.

---

## Tax Constants Class

**File:** `app/Services/Calculation/TaxConstants.php`

```php
<?php

namespace App\Services\Calculation;

use App\Enums\TaxRegime;
use App\Enums\CompanyClassification;

final class TaxConstants
{
    public const RULES_VERSION = 'v1.0.0';

    public const CIT_THRESHOLDS = [
        TaxRegime::OLD->value => [
            'small_max' => 25_000_000,
            'medium_max' => 100_000_000,
        ],
        TaxRegime::NEW->value => [
            'small_max' => 50_000_000,
            'medium_max' => 100_000_000,
        ],
    ];

    public const CIT_RATES = [
        TaxRegime::OLD->value => [
            CompanyClassification::SMALL->value => 0.00,
            CompanyClassification::MEDIUM->value => 0.20,
            CompanyClassification::LARGE->value => 0.30,
        ],
        TaxRegime::NEW->value => [
            CompanyClassification::SMALL->value => 0.00,
            CompanyClassification::MEDIUM->value => 0.20,
            CompanyClassification::LARGE->value => 0.25,
        ],
    ];

    public const MINIMUM_TAX_RATE = 0.005;

    public const PIT_BANDS = [
        TaxRegime::OLD->value => [
            ['min' => 0, 'max' => 300_000, 'rate' => 0.07],
            ['min' => 300_001, 'max' => 600_000, 'rate' => 0.11],
            ['min' => 600_001, 'max' => 1_100_000, 'rate' => 0.15],
            ['min' => 1_100_001, 'max' => 1_600_000, 'rate' => 0.19],
            ['min' => 1_600_001, 'max' => 3_200_000, 'rate' => 0.21],
            ['min' => 3_200_001, 'max' => PHP_INT_MAX, 'rate' => 0.24],
        ],
        TaxRegime::NEW->value => [
            ['min' => 0, 'max' => 800_000, 'rate' => 0.00],
            ['min' => 800_001, 'max' => 2_800_000, 'rate' => 0.07],
            ['min' => 2_800_001, 'max' => 5_200_000, 'rate' => 0.11],
            ['min' => 5_200_001, 'max' => 10_400_000, 'rate' => 0.15],
            ['min' => 10_400_001, 'max' => 50_000_000, 'rate' => 0.19],
            ['min' => 50_000_001, 'max' => PHP_INT_MAX, 'rate' => 0.25],
        ],
    ];

    public const PIT_EXEMPTION = [
        TaxRegime::OLD->value => 0,
        TaxRegime::NEW->value => 800_000,
    ];

    public const CONSOLIDATED_RELIEF = [
        'minimum' => 200_000,
        'percentage_of_gross' => 0.01,
        'additional_percentage' => 0.20,
    ];

    public const VAT_RATE = 0.075;

    public const VAT_INPUT_RECOVERY = [
        TaxRegime::OLD->value => [
            'goods' => true,
            'services' => false,
            'capital' => false,
        ],
        TaxRegime::NEW->value => [
            'goods' => true,
            'services' => true,
            'capital' => true,
        ],
    ];

    public const CGT_RATES = [
        TaxRegime::OLD->value => [
            'individual' => 0.10,
            'company' => 0.10,
        ],
        TaxRegime::NEW->value => [
            'individual' => 0.25,
            'company' => 0.25,
        ],
    ];

    public const DEVELOPMENT_LEVY = [
        TaxRegime::OLD->value => [
            'education_tax' => ['rate' => 0.02, 'base' => 'assessable_profit'],
            'nitdf' => ['rate' => 0.01, 'base' => 'profit_before_tax'],
            'naseni' => ['rate' => 0.0025, 'base' => 'profit_before_tax'],
            'police_trust_fund' => ['rate' => 0.00005, 'base' => 'net_profit'],
        ],
        TaxRegime::NEW->value => [
            'unified_levy' => ['rate' => 0.04, 'base' => 'assessable_profit'],
        ],
    ];

    public const CAPITAL_ALLOWANCES = [
        'building' => ['initial' => 0.15, 'annual' => 0.10],
        'plant_machinery' => ['initial' => 0.50, 'annual' => 0.25],
        'furniture_fittings' => ['initial' => 0.25, 'annual' => 0.20],
        'motor_vehicles' => ['initial' => 0.50, 'annual' => 0.25],
        'computer_equipment' => ['initial' => 0.50, 'annual' => 0.25],
    ];

    public static function getCITRate(TaxRegime $regime, CompanyClassification $classification): float
    {
        return self::CIT_RATES[$regime->value][$classification->value];
    }

    public static function classifyCompany(TaxRegime $regime, float $turnover): CompanyClassification
    {
        $thresholds = self::CIT_THRESHOLDS[$regime->value];

        if ($turnover <= $thresholds['small_max']) {
            return CompanyClassification::SMALL;
        }

        if ($turnover <= $thresholds['medium_max']) {
            return CompanyClassification::MEDIUM;
        }

        return CompanyClassification::LARGE;
    }

    public static function getPITBands(TaxRegime $regime): array
    {
        return self::PIT_BANDS[$regime->value];
    }

    public static function getVATInputRecovery(TaxRegime $regime): array
    {
        return self::VAT_INPUT_RECOVERY[$regime->value];
    }

    public static function getCGTRate(TaxRegime $regime, string $entityType): float
    {
        return self::CGT_RATES[$regime->value][$entityType];
    }

    public static function getDevelopmentLevyConfig(TaxRegime $regime): array
    {
        return self::DEVELOPMENT_LEVY[$regime->value];
    }

    public static function getCapitalAllowance(string $assetClass): array
    {
        return self::CAPITAL_ALLOWANCES[$assetClass] ?? ['initial' => 0, 'annual' => 0];
    }

    public static function calculateConsolidatedRelief(float $grossIncome): float
    {
        $minimumRelief = max(
            self::CONSOLIDATED_RELIEF['minimum'],
            $grossIncome * self::CONSOLIDATED_RELIEF['percentage_of_gross']
        );

        $additionalRelief = $grossIncome * self::CONSOLIDATED_RELIEF['additional_percentage'];

        return $minimumRelief + $additionalRelief;
    }
}
```

---

## Tax Rules Repository

**File:** `app/Services/Calculation/TaxRulesRepository.php`

```php
<?php

namespace App\Services\Calculation;

use App\Enums\TaxRegime;
use App\Enums\CompanyClassification;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TaxRulesRepository
{
    protected string $currentVersion;

    public function __construct()
    {
        $this->currentVersion = TaxConstants::RULES_VERSION;
    }

    public function getVersion(): string
    {
        return $this->currentVersion;
    }

    public function getCITRules(TaxRegime $regime, ?Carbon $effectiveDate = null): array
    {
        return [
            'version' => $this->currentVersion,
            'regime' => $regime->value,
            'thresholds' => TaxConstants::CIT_THRESHOLDS[$regime->value],
            'rates' => TaxConstants::CIT_RATES[$regime->value],
            'minimum_tax_rate' => TaxConstants::MINIMUM_TAX_RATE,
            'effective_date' => $effectiveDate?->toDateString() ?? now()->toDateString(),
        ];
    }

    public function getPITRules(TaxRegime $regime, ?Carbon $effectiveDate = null): array
    {
        return [
            'version' => $this->currentVersion,
            'regime' => $regime->value,
            'bands' => TaxConstants::PIT_BANDS[$regime->value],
            'exemption' => TaxConstants::PIT_EXEMPTION[$regime->value],
            'consolidated_relief' => TaxConstants::CONSOLIDATED_RELIEF,
            'effective_date' => $effectiveDate?->toDateString() ?? now()->toDateString(),
        ];
    }

    public function getVATRules(TaxRegime $regime, ?Carbon $effectiveDate = null): array
    {
        return [
            'version' => $this->currentVersion,
            'regime' => $regime->value,
            'rate' => TaxConstants::VAT_RATE,
            'input_recovery' => TaxConstants::VAT_INPUT_RECOVERY[$regime->value],
            'effective_date' => $effectiveDate?->toDateString() ?? now()->toDateString(),
        ];
    }

    public function getCGTRules(TaxRegime $regime, ?Carbon $effectiveDate = null): array
    {
        return [
            'version' => $this->currentVersion,
            'regime' => $regime->value,
            'rates' => TaxConstants::CGT_RATES[$regime->value],
            'effective_date' => $effectiveDate?->toDateString() ?? now()->toDateString(),
        ];
    }

    public function getDevelopmentLevyRules(TaxRegime $regime, ?Carbon $effectiveDate = null): array
    {
        return [
            'version' => $this->currentVersion,
            'regime' => $regime->value,
            'levies' => TaxConstants::DEVELOPMENT_LEVY[$regime->value],
            'effective_date' => $effectiveDate?->toDateString() ?? now()->toDateString(),
        ];
    }

    public function getCapitalAllowanceRules(?Carbon $effectiveDate = null): array
    {
        return [
            'version' => $this->currentVersion,
            'allowances' => TaxConstants::CAPITAL_ALLOWANCES,
            'effective_date' => $effectiveDate?->toDateString() ?? now()->toDateString(),
        ];
    }

    public function getAllRules(TaxRegime $regime, ?Carbon $effectiveDate = null): array
    {
        return [
            'version' => $this->currentVersion,
            'regime' => $regime->value,
            'effective_date' => $effectiveDate?->toDateString() ?? now()->toDateString(),
            'cit' => $this->getCITRules($regime, $effectiveDate),
            'pit' => $this->getPITRules($regime, $effectiveDate),
            'vat' => $this->getVATRules($regime, $effectiveDate),
            'cgt' => $this->getCGTRules($regime, $effectiveDate),
            'development_levy' => $this->getDevelopmentLevyRules($regime, $effectiveDate),
            'capital_allowances' => $this->getCapitalAllowanceRules($effectiveDate),
        ];
    }

    public function compareRegimes(?Carbon $effectiveDate = null): array
    {
        return [
            'version' => $this->currentVersion,
            'effective_date' => $effectiveDate?->toDateString() ?? now()->toDateString(),
            'old_regime' => $this->getAllRules(TaxRegime::OLD, $effectiveDate),
            'new_regime' => $this->getAllRules(TaxRegime::NEW, $effectiveDate),
            'key_differences' => $this->getKeyDifferences(),
        ];
    }

    protected function getKeyDifferences(): array
    {
        return [
            'cit' => [
                'small_company_threshold' => [
                    'old' => '≤ ₦25M',
                    'new' => '≤ ₦50M',
                    'impact' => 'More companies qualify for 0% rate',
                ],
                'large_company_rate' => [
                    'old' => '30%',
                    'new' => '25%',
                    'impact' => '5 percentage points reduction',
                ],
            ],
            'pit' => [
                'exemption' => [
                    'old' => '₦0',
                    'new' => '₦800,000',
                    'impact' => 'First ₦800K tax-free under new regime',
                ],
                'top_rate' => [
                    'old' => '24%',
                    'new' => '25%',
                    'impact' => '1 percentage point increase at top end',
                ],
            ],
            'vat' => [
                'input_recovery' => [
                    'old' => 'Goods only',
                    'new' => 'Goods, services, and capital',
                    'impact' => 'Expanded input VAT recovery',
                ],
            ],
            'cgt' => [
                'rate' => [
                    'old' => '10% flat',
                    'new' => 'Up to 25%',
                    'impact' => 'Aligned with income tax rates',
                ],
            ],
            'development_levy' => [
                'structure' => [
                    'old' => 'Multiple levies (~3.26%)',
                    'new' => 'Unified 4%',
                    'impact' => 'Simplified but slightly higher',
                ],
            ],
        ];
    }
}
```

---

## Capital Allowance Calculator

**File:** `app/Services/Calculation/CapitalAllowanceCalculator.php`

```php
<?php

namespace App\Services\Calculation;

class CapitalAllowanceCalculator
{
    public function calculate(array $assets): array
    {
        $results = [];
        $totalAllowance = 0;

        foreach ($assets as $asset) {
            $assetClass = $asset['class'] ?? 'plant_machinery';
            $cost = $asset['cost'] ?? 0;
            $yearAcquired = $asset['year_acquired'] ?? 1;
            $currentYear = $asset['current_year'] ?? 1;

            $allowance = $this->calculateForAsset($assetClass, $cost, $yearAcquired, $currentYear);
            $results[] = array_merge($asset, $allowance);
            $totalAllowance += $allowance['current_allowance'];
        }

        return [
            'assets' => $results,
            'total_allowance' => $totalAllowance,
        ];
    }

    protected function calculateForAsset(
        string $assetClass,
        float $cost,
        int $yearAcquired,
        int $currentYear
    ): array {
        $rates = TaxConstants::getCapitalAllowance($assetClass);
        $initialRate = $rates['initial'];
        $annualRate = $rates['annual'];

        $yearsHeld = $currentYear - $yearAcquired + 1;

        if ($yearsHeld === 1) {
            $currentAllowance = $cost * $initialRate;
            $cumulativeAllowance = $currentAllowance;
        } else {
            $initialAllowance = $cost * $initialRate;
            $residualValue = $cost - $initialAllowance;
            $annualAllowancePerYear = $residualValue * $annualRate;
            $yearsOfAnnual = $yearsHeld - 1;

            $cumulativeAnnual = min(
                $annualAllowancePerYear * $yearsOfAnnual,
                $residualValue
            );

            $currentAllowance = ($yearsOfAnnual === $yearsHeld - 1)
                ? min($annualAllowancePerYear, $residualValue - ($annualAllowancePerYear * ($yearsOfAnnual - 1)))
                : 0;

            $cumulativeAllowance = $initialAllowance + $cumulativeAnnual;
        }

        $writtenDownValue = max(0, $cost - $cumulativeAllowance);

        return [
            'initial_rate' => $initialRate,
            'annual_rate' => $annualRate,
            'current_allowance' => round($currentAllowance, 2),
            'cumulative_allowance' => round($cumulativeAllowance, 2),
            'written_down_value' => round($writtenDownValue, 2),
        ];
    }
}
```

---

## Service Provider Registration

**File:** `app/Providers/TaxCalculationServiceProvider.php`

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Calculation\TaxRulesRepository;
use App\Services\Calculation\CapitalAllowanceCalculator;

class TaxCalculationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TaxRulesRepository::class, function ($app) {
            return new TaxRulesRepository();
        });

        $this->app->singleton(CapitalAllowanceCalculator::class, function ($app) {
            return new CapitalAllowanceCalculator();
        });
    }

    public function boot(): void
    {
        //
    }
}
```

Update `config/app.php` to register the provider:

```php
'providers' => ServiceProvider::defaultProviders()->merge([
    // ...
    App\Providers\TaxCalculationServiceProvider::class,
])->toArray(),
```

---

## Tax Rate Summary Reference

### Company Income Tax (CIT)

| Regime | Classification | Turnover Threshold | Rate |
|--------|---------------|-------------------|------|
| Old | Small | ≤ ₦25,000,000 | 0% |
| Old | Medium | ₦25,000,001 - ₦100,000,000 | 20% |
| Old | Large | > ₦100,000,000 | 30% |
| New | Small | ≤ ₦50,000,000 | 0% |
| New | Medium | ₦50,000,001 - ₦100,000,000 | 20% |
| New | Large | > ₦100,000,000 | 25% |

### Personal Income Tax (PIT)

**Old Regime:**

| Band | Income Range | Rate |
|------|-------------|------|
| 1 | ₦0 - ₦300,000 | 7% |
| 2 | ₦300,001 - ₦600,000 | 11% |
| 3 | ₦600,001 - ₦1,100,000 | 15% |
| 4 | ₦1,100,001 - ₦1,600,000 | 19% |
| 5 | ₦1,600,001 - ₦3,200,000 | 21% |
| 6 | Above ₦3,200,000 | 24% |

**New Regime (NTA 2025):**

| Band | Income Range | Rate |
|------|-------------|------|
| 1 | ₦0 - ₦800,000 | 0% |
| 2 | ₦800,001 - ₦2,800,000 | 7% |
| 3 | ₦2,800,001 - ₦5,200,000 | 11% |
| 4 | ₦5,200,001 - ₦10,400,000 | 15% |
| 5 | ₦10,400,001 - ₦50,000,000 | 19% |
| 6 | Above ₦50,000,000 | 25% |

### Value Added Tax (VAT)

- Rate: 7.5% (unchanged)
- Input recovery expanded under new regime

### Capital Gains Tax (CGT)

| Regime | Individual Rate | Company Rate |
|--------|----------------|--------------|
| Old | 10% | 10% |
| New | Up to 25% | 25% |

### Development Levy

**Old Regime (Multiple):**
- Education Tax: 2% of assessable profit
- NITDF: 1% of profit before tax
- NASENI: 0.25% of profit before tax
- Police Trust Fund: 0.005% of net profit
- **Combined: ~3.26%**

**New Regime (Unified):**
- Development Levy: 4% of assessable profit

### Capital Allowances

| Asset Class | Initial Allowance | Annual Allowance |
|-------------|------------------|------------------|
| Building | 15% | 10% |
| Plant & Machinery | 50% | 25% |
| Furniture & Fittings | 25% | 20% |
| Motor Vehicles | 50% | 25% |
| Computer Equipment | 50% | 25% |

---

## Next Steps

Once tax rules and constants are defined, proceed to:
→ **03_CALCULATORS.md** - Individual tax calculators implementation
