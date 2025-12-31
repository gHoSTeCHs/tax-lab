# TaxLab Implementation - Module 5: Tax Calculators

## Overview

This document defines the individual tax calculators that perform specific tax type calculations for both old and new (NTA 2025) regimes. Each calculator follows a consistent interface and produces structured results.

---

## Calculator Interface

**File:** `app/Services/Calculation/Contracts/TaxCalculatorInterface.php`

```php
<?php

namespace App\Services\Calculation\Contracts;

use App\Enums\TaxRegime;

interface TaxCalculatorInterface
{
    public function calculate(array $data, TaxRegime $regime): array;

    public function calculateBothRegimes(array $data): array;

    public function getType(): string;
}
```

---

## Base Calculator

**File:** `app/Services/Calculation/Calculators/BaseTaxCalculator.php`

```php
<?php

namespace App\Services\Calculation\Calculators;

use App\Enums\TaxRegime;
use App\Services\Calculation\Contracts\TaxCalculatorInterface;
use App\Services\Calculation\TaxRulesRepository;

abstract class BaseTaxCalculator implements TaxCalculatorInterface
{
    public function __construct(
        protected TaxRulesRepository $rulesRepository
    ) {}

    public function calculateBothRegimes(array $data): array
    {
        $oldResult = $this->calculate($data, TaxRegime::OLD);
        $newResult = $this->calculate($data, TaxRegime::NEW);

        return [
            'type' => $this->getType(),
            'old_regime' => $oldResult,
            'new_regime' => $newResult,
            'comparison' => $this->compareResults($oldResult, $newResult),
        ];
    }

    protected function compareResults(array $oldResult, array $newResult): array
    {
        $oldLiability = $oldResult['tax_liability'] ?? 0;
        $newLiability = $newResult['tax_liability'] ?? 0;
        $difference = $oldLiability - $newLiability;

        return [
            'old_liability' => $oldLiability,
            'new_liability' => $newLiability,
            'difference' => abs($difference),
            'savings' => $difference > 0 ? $difference : 0,
            'increase' => $difference < 0 ? abs($difference) : 0,
            'percentage_change' => $oldLiability > 0
                ? round((($newLiability - $oldLiability) / $oldLiability) * 100, 2)
                : 0,
            'favors' => $difference > 0 ? 'new' : ($difference < 0 ? 'old' : 'neutral'),
        ];
    }

    protected function round(float $value): float
    {
        return round($value, 2);
    }
}
```

---

## CIT Calculator

**File:** `app/Services/Calculation/Calculators/CITCalculator.php`

```php
<?php

namespace App\Services\Calculation\Calculators;

use App\Enums\TaxRegime;
use App\Enums\CompanyClassification;
use App\Services\Calculation\TaxConstants;
use App\Services\Calculation\CapitalAllowanceCalculator;

class CITCalculator extends BaseTaxCalculator
{
    public function __construct(
        protected \App\Services\Calculation\TaxRulesRepository $rulesRepository,
        protected CapitalAllowanceCalculator $capitalAllowanceCalculator
    ) {
        parent::__construct($rulesRepository);
    }

    public function getType(): string
    {
        return 'company_income_tax';
    }

    public function calculate(array $data, TaxRegime $regime): array
    {
        $turnover = $data['turnover'] ?? 0;
        $accountingProfit = $data['accounting_profit'] ?? 0;
        $addBacks = $data['add_backs'] ?? 0;
        $deductions = $data['deductions'] ?? 0;
        $assets = $data['assets'] ?? [];

        $classification = TaxConstants::classifyCompany($regime, $turnover);
        $taxRate = TaxConstants::getCITRate($regime, $classification);

        $capitalAllowances = $this->calculateCapitalAllowances($assets);

        $adjustedProfit = $accountingProfit + $addBacks - $deductions;
        $taxableProfit = max(0, $adjustedProfit - $capitalAllowances['total_allowance']);

        $calculatedTax = $taxableProfit * $taxRate;
        $minimumTax = $this->calculateMinimumTax($turnover, $taxableProfit, $classification);
        $taxLiability = max($calculatedTax, $minimumTax);

        $effectiveRate = $accountingProfit > 0
            ? $this->round($taxLiability / $accountingProfit)
            : 0;

        return [
            'classification' => $classification->value,
            'turnover' => $this->round($turnover),
            'accounting_profit' => $this->round($accountingProfit),
            'adjustments' => [
                'add_backs' => $this->round($addBacks),
                'deductions' => $this->round($deductions),
                'net_adjustment' => $this->round($addBacks - $deductions),
            ],
            'adjusted_profit' => $this->round($adjustedProfit),
            'capital_allowances' => $capitalAllowances,
            'taxable_profit' => $this->round($taxableProfit),
            'tax_rate' => $taxRate,
            'calculated_tax' => $this->round($calculatedTax),
            'minimum_tax' => $this->round($minimumTax),
            'minimum_tax_applies' => $minimumTax > $calculatedTax,
            'tax_liability' => $this->round($taxLiability),
            'effective_rate' => $effectiveRate,
        ];
    }

    protected function calculateCapitalAllowances(array $assets): array
    {
        if (empty($assets)) {
            return ['assets' => [], 'total_allowance' => 0];
        }

        return $this->capitalAllowanceCalculator->calculate($assets);
    }

    protected function calculateMinimumTax(
        float $turnover,
        float $taxableProfit,
        CompanyClassification $classification
    ): float {
        if ($classification === CompanyClassification::SMALL) {
            return 0;
        }

        if ($taxableProfit <= 0) {
            return 0;
        }

        return $turnover * TaxConstants::MINIMUM_TAX_RATE;
    }
}
```

---

## PIT Calculator

**File:** `app/Services/Calculation/Calculators/PITCalculator.php`

```php
<?php

namespace App\Services\Calculation\Calculators;

use App\Enums\TaxRegime;
use App\Services\Calculation\TaxConstants;

class PITCalculator extends BaseTaxCalculator
{
    public function getType(): string
    {
        return 'personal_income_tax';
    }

    public function calculate(array $data, TaxRegime $regime): array
    {
        $employmentIncome = $data['employment_income'] ?? 0;
        $rentalIncome = $data['rental_income'] ?? 0;
        $businessIncome = $data['business_income'] ?? 0;
        $investmentIncome = $data['investment_income'] ?? 0;
        $otherIncome = $data['other_income'] ?? 0;

        $grossIncome = $employmentIncome + $rentalIncome + $businessIncome
            + $investmentIncome + $otherIncome;

        $reliefs = $this->calculateReliefs($data, $regime, $grossIncome);
        $taxableIncome = max(0, $grossIncome - $reliefs['total']);

        $taxByBand = $this->applyTaxBands($taxableIncome, $regime);
        $taxLiability = array_sum(array_column($taxByBand, 'tax'));

        $effectiveRate = $grossIncome > 0
            ? $this->round($taxLiability / $grossIncome)
            : 0;

        return [
            'income_breakdown' => [
                'employment' => $this->round($employmentIncome),
                'rental' => $this->round($rentalIncome),
                'business' => $this->round($businessIncome),
                'investment' => $this->round($investmentIncome),
                'other' => $this->round($otherIncome),
            ],
            'gross_income' => $this->round($grossIncome),
            'reliefs' => $reliefs,
            'taxable_income' => $this->round($taxableIncome),
            'tax_by_band' => $taxByBand,
            'tax_liability' => $this->round($taxLiability),
            'effective_rate' => $effectiveRate,
        ];
    }

    protected function calculateReliefs(array $data, TaxRegime $regime, float $grossIncome): array
    {
        if ($regime === TaxRegime::NEW) {
            $exemption = TaxConstants::PIT_EXEMPTION[TaxRegime::NEW->value];
            return [
                'exemption' => $exemption,
                'total' => $exemption,
                'breakdown' => [
                    ['type' => 'Tax-Free Threshold', 'amount' => $exemption],
                ],
            ];
        }

        $consolidatedRelief = TaxConstants::calculateConsolidatedRelief($grossIncome);
        $pensionRelief = $data['pension_contribution'] ?? 0;
        $nhfRelief = $data['nhf_contribution'] ?? 0;
        $lifeInsurance = $data['life_insurance'] ?? 0;

        $totalReliefs = $consolidatedRelief + $pensionRelief + $nhfRelief + $lifeInsurance;

        return [
            'consolidated_relief' => $this->round($consolidatedRelief),
            'pension_relief' => $this->round($pensionRelief),
            'nhf_relief' => $this->round($nhfRelief),
            'life_insurance' => $this->round($lifeInsurance),
            'total' => $this->round($totalReliefs),
            'breakdown' => [
                ['type' => 'Consolidated Relief', 'amount' => $this->round($consolidatedRelief)],
                ['type' => 'Pension Contribution', 'amount' => $this->round($pensionRelief)],
                ['type' => 'NHF Contribution', 'amount' => $this->round($nhfRelief)],
                ['type' => 'Life Insurance', 'amount' => $this->round($lifeInsurance)],
            ],
        ];
    }

    protected function applyTaxBands(float $taxableIncome, TaxRegime $regime): array
    {
        $bands = TaxConstants::getPITBands($regime);
        $results = [];
        $remainingIncome = $taxableIncome;

        foreach ($bands as $band) {
            if ($remainingIncome <= 0) {
                break;
            }

            $bandSize = $band['max'] - $band['min'] + 1;
            $taxableInBand = min($remainingIncome, $bandSize);
            $tax = $taxableInBand * $band['rate'];

            $results[] = [
                'band' => $this->formatBandLabel($band),
                'rate' => $band['rate'],
                'taxable_amount' => $this->round($taxableInBand),
                'tax' => $this->round($tax),
            ];

            $remainingIncome -= $taxableInBand;
        }

        return $results;
    }

    protected function formatBandLabel(array $band): string
    {
        if ($band['max'] >= PHP_INT_MAX - 1000000) {
            return 'Above ₦' . number_format($band['min'] - 1);
        }

        return '₦' . number_format($band['min']) . ' - ₦' . number_format($band['max']);
    }
}
```

---

## VAT Calculator

**File:** `app/Services/Calculation/Calculators/VATCalculator.php`

```php
<?php

namespace App\Services\Calculation\Calculators;

use App\Enums\TaxRegime;
use App\Services\Calculation\TaxConstants;

class VATCalculator extends BaseTaxCalculator
{
    public function getType(): string
    {
        return 'vat_analysis';
    }

    public function calculate(array $data, TaxRegime $regime): array
    {
        $vatableSales = $data['vatable_sales'] ?? 0;
        $exemptSales = $data['exempt_sales'] ?? 0;
        $zeroRatedSales = $data['zero_rated_sales'] ?? 0;

        $vatOnGoods = $data['vat_on_goods'] ?? 0;
        $vatOnServices = $data['vat_on_services'] ?? 0;
        $vatOnCapital = $data['vat_on_capital'] ?? 0;

        $outputVat = $vatableSales * TaxConstants::VAT_RATE;
        $inputRecovery = TaxConstants::getVATInputRecovery($regime);

        $recoverableGoods = $inputRecovery['goods'] ? $vatOnGoods : 0;
        $recoverableServices = $inputRecovery['services'] ? $vatOnServices : 0;
        $recoverableCapital = $inputRecovery['capital'] ? $vatOnCapital : 0;

        $totalRecoverable = $recoverableGoods + $recoverableServices + $recoverableCapital;
        $nonRecoverable = ($vatOnGoods + $vatOnServices + $vatOnCapital) - $totalRecoverable;
        $netVatPayable = max(0, $outputVat - $totalRecoverable);
        $vatRefundable = max(0, $totalRecoverable - $outputVat);

        return [
            'sales' => [
                'vatable' => $this->round($vatableSales),
                'exempt' => $this->round($exemptSales),
                'zero_rated' => $this->round($zeroRatedSales),
                'total' => $this->round($vatableSales + $exemptSales + $zeroRatedSales),
            ],
            'output_vat' => $this->round($outputVat),
            'input_vat' => [
                'goods' => [
                    'total' => $this->round($vatOnGoods),
                    'recoverable' => $this->round($recoverableGoods),
                    'recovery_allowed' => $inputRecovery['goods'],
                ],
                'services' => [
                    'total' => $this->round($vatOnServices),
                    'recoverable' => $this->round($recoverableServices),
                    'recovery_allowed' => $inputRecovery['services'],
                ],
                'capital' => [
                    'total' => $this->round($vatOnCapital),
                    'recoverable' => $this->round($recoverableCapital),
                    'recovery_allowed' => $inputRecovery['capital'],
                ],
            ],
            'total_input_vat' => $this->round($vatOnGoods + $vatOnServices + $vatOnCapital),
            'total_recoverable' => $this->round($totalRecoverable),
            'non_recoverable' => $this->round($nonRecoverable),
            'net_vat_payable' => $this->round($netVatPayable),
            'vat_refundable' => $this->round($vatRefundable),
            'tax_liability' => $this->round($netVatPayable),
        ];
    }

    protected function compareResults(array $oldResult, array $newResult): array
    {
        $comparison = parent::compareResults($oldResult, $newResult);

        $additionalRecovery = ($newResult['total_recoverable'] ?? 0) - ($oldResult['total_recoverable'] ?? 0);

        $comparison['additional_recovery'] = $this->round($additionalRecovery);
        $comparison['recovery_breakdown'] = [
            'services_now_recoverable' => $newResult['input_vat']['services']['recoverable'] ?? 0,
            'capital_now_recoverable' => $newResult['input_vat']['capital']['recoverable'] ?? 0,
        ];

        return $comparison;
    }
}
```

---

## CGT Calculator

**File:** `app/Services/Calculation/Calculators/CGTCalculator.php`

```php
<?php

namespace App\Services\Calculation\Calculators;

use App\Enums\TaxRegime;
use App\Services\Calculation\TaxConstants;

class CGTCalculator extends BaseTaxCalculator
{
    public function getType(): string
    {
        return 'capital_gains_tax';
    }

    public function calculate(array $data, TaxRegime $regime): array
    {
        $entityType = $data['entity_type'] ?? 'company';
        $disposals = $data['disposals'] ?? [];

        $processedDisposals = [];
        $totalGain = 0;
        $totalExemptions = 0;

        foreach ($disposals as $disposal) {
            $processed = $this->processDisposal($disposal);
            $processedDisposals[] = $processed;
            $totalGain += $processed['gain'];
            $totalExemptions += $processed['exemption'];
        }

        $taxableGain = max(0, $totalGain - $totalExemptions);
        $rate = TaxConstants::getCGTRate($regime, $entityType);
        $taxLiability = $taxableGain * $rate;

        return [
            'entity_type' => $entityType,
            'disposals' => $processedDisposals,
            'total_proceeds' => $this->round(array_sum(array_column($processedDisposals, 'proceeds'))),
            'total_cost' => $this->round(array_sum(array_column($processedDisposals, 'total_cost'))),
            'total_gain' => $this->round($totalGain),
            'total_exemptions' => $this->round($totalExemptions),
            'taxable_gain' => $this->round($taxableGain),
            'rate' => $rate,
            'tax_liability' => $this->round($taxLiability),
        ];
    }

    protected function processDisposal(array $disposal): array
    {
        $proceeds = $disposal['proceeds'] ?? 0;
        $acquisitionCost = $disposal['acquisition_cost'] ?? 0;
        $improvementCost = $disposal['improvement_cost'] ?? 0;
        $incidentalCost = $disposal['incidental_cost'] ?? 0;
        $exemption = $disposal['exemption'] ?? 0;

        $totalCost = $acquisitionCost + $improvementCost + $incidentalCost;
        $gain = max(0, $proceeds - $totalCost);

        return [
            'asset' => $disposal['asset'] ?? 'Unknown Asset',
            'asset_type' => $disposal['asset_type'] ?? 'other',
            'proceeds' => $this->round($proceeds),
            'acquisition_cost' => $this->round($acquisitionCost),
            'improvement_cost' => $this->round($improvementCost),
            'incidental_cost' => $this->round($incidentalCost),
            'total_cost' => $this->round($totalCost),
            'gain' => $this->round($gain),
            'exemption' => $this->round($exemption),
        ];
    }

    protected function compareResults(array $oldResult, array $newResult): array
    {
        $comparison = parent::compareResults($oldResult, $newResult);

        $comparison['rate_change'] = [
            'old_rate' => $oldResult['rate'] ?? 0,
            'new_rate' => $newResult['rate'] ?? 0,
            'percentage_point_change' => ($newResult['rate'] ?? 0) - ($oldResult['rate'] ?? 0),
        ];

        if ($comparison['favors'] === 'old') {
            $comparison['note'] = 'CGT rate increase under new regime results in higher liability';
        }

        return $comparison;
    }
}
```

---

## Development Levy Calculator

**File:** `app/Services/Calculation/Calculators/DevelopmentLevyCalculator.php`

```php
<?php

namespace App\Services\Calculation\Calculators;

use App\Enums\TaxRegime;
use App\Services\Calculation\TaxConstants;

class DevelopmentLevyCalculator extends BaseTaxCalculator
{
    public function getType(): string
    {
        return 'development_levy';
    }

    public function calculate(array $data, TaxRegime $regime): array
    {
        $assessableProfit = $data['assessable_profit'] ?? 0;
        $profitBeforeTax = $data['profit_before_tax'] ?? 0;
        $netProfit = $data['net_profit'] ?? 0;

        $levyConfig = TaxConstants::getDevelopmentLevyConfig($regime);
        $levies = [];
        $totalLevy = 0;

        foreach ($levyConfig as $levyName => $config) {
            $base = $this->getBase($config['base'], $assessableProfit, $profitBeforeTax, $netProfit);
            $amount = $base * $config['rate'];

            $levies[$levyName] = [
                'base_type' => $config['base'],
                'base_amount' => $this->round($base),
                'rate' => $config['rate'],
                'amount' => $this->round($amount),
            ];

            $totalLevy += $amount;
        }

        return [
            'input_values' => [
                'assessable_profit' => $this->round($assessableProfit),
                'profit_before_tax' => $this->round($profitBeforeTax),
                'net_profit' => $this->round($netProfit),
            ],
            'levies' => $levies,
            'total_levy' => $this->round($totalLevy),
            'tax_liability' => $this->round($totalLevy),
            'combined_rate' => $assessableProfit > 0
                ? $this->round($totalLevy / $assessableProfit)
                : 0,
        ];
    }

    protected function getBase(string $baseType, float $assessable, float $pbt, float $net): float
    {
        return match ($baseType) {
            'assessable_profit' => $assessable,
            'profit_before_tax' => $pbt,
            'net_profit' => $net,
            default => $assessable,
        };
    }

    protected function compareResults(array $oldResult, array $newResult): array
    {
        $comparison = parent::compareResults($oldResult, $newResult);

        $comparison['structure_change'] = [
            'old_levies_count' => count($oldResult['levies'] ?? []),
            'new_levies_count' => count($newResult['levies'] ?? []),
            'note' => 'Multiple levies consolidated into single Development Levy',
        ];

        return $comparison;
    }
}
```

---

## PAYE Calculator

**File:** `app/Services/Calculation/Calculators/PAYECalculator.php`

```php
<?php

namespace App\Services\Calculation\Calculators;

use App\Enums\TaxRegime;
use App\Services\Calculation\TaxConstants;

class PAYECalculator extends BaseTaxCalculator
{
    public function getType(): string
    {
        return 'paye_analysis';
    }

    public function calculate(array $data, TaxRegime $regime): array
    {
        $employees = $data['employees'] ?? [];
        $employeeResults = [];
        $totalPayeTax = 0;
        $totalGrossSalaries = 0;

        foreach ($employees as $employee) {
            $result = $this->calculateForEmployee($employee, $regime);
            $employeeResults[] = $result;
            $totalPayeTax += $result['paye_due'];
            $totalGrossSalaries += $result['gross_salary'];
        }

        return [
            'employee_count' => count($employees),
            'total_gross_salaries' => $this->round($totalGrossSalaries),
            'total_paye_due' => $this->round($totalPayeTax),
            'average_tax_rate' => $totalGrossSalaries > 0
                ? $this->round($totalPayeTax / $totalGrossSalaries)
                : 0,
            'employees' => $employeeResults,
            'tax_liability' => $this->round($totalPayeTax),
        ];
    }

    protected function calculateForEmployee(array $employee, TaxRegime $regime): array
    {
        $grossSalary = $employee['gross_salary'] ?? 0;
        $pensionContribution = $employee['pension_contribution'] ?? 0;
        $nhfContribution = $employee['nhf_contribution'] ?? 0;

        $bands = TaxConstants::getPITBands($regime);
        $exemption = TaxConstants::PIT_EXEMPTION[$regime->value];

        if ($regime === TaxRegime::NEW) {
            $taxableIncome = max(0, $grossSalary - $exemption);
        } else {
            $consolidatedRelief = TaxConstants::calculateConsolidatedRelief($grossSalary);
            $totalReliefs = $consolidatedRelief + $pensionContribution + $nhfContribution;
            $taxableIncome = max(0, $grossSalary - $totalReliefs);
        }

        $payeTax = $this->applyBands($taxableIncome, $bands);

        return [
            'employee_id' => $employee['id'] ?? null,
            'name' => $employee['name'] ?? 'Employee',
            'gross_salary' => $this->round($grossSalary),
            'taxable_income' => $this->round($taxableIncome),
            'paye_due' => $this->round($payeTax),
            'effective_rate' => $grossSalary > 0
                ? $this->round($payeTax / $grossSalary)
                : 0,
        ];
    }

    protected function applyBands(float $taxableIncome, array $bands): float
    {
        $tax = 0;
        $remainingIncome = $taxableIncome;

        foreach ($bands as $band) {
            if ($remainingIncome <= 0) {
                break;
            }

            $bandSize = $band['max'] - $band['min'] + 1;
            $taxableInBand = min($remainingIncome, $bandSize);
            $tax += $taxableInBand * $band['rate'];
            $remainingIncome -= $taxableInBand;
        }

        return $tax;
    }
}
```

---

## Result DTOs

### CIT Result

**File:** `app/Services/Calculation/Results/CITResult.php`

```php
<?php

namespace App\Services\Calculation\Results;

readonly class CITResult
{
    public function __construct(
        public string $classification,
        public float $turnover,
        public float $accountingProfit,
        public array $adjustments,
        public float $adjustedProfit,
        public array $capitalAllowances,
        public float $taxableProfit,
        public float $taxRate,
        public float $calculatedTax,
        public float $minimumTax,
        public bool $minimumTaxApplies,
        public float $taxLiability,
        public float $effectiveRate
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            classification: $data['classification'],
            turnover: $data['turnover'],
            accountingProfit: $data['accounting_profit'],
            adjustments: $data['adjustments'],
            adjustedProfit: $data['adjusted_profit'],
            capitalAllowances: $data['capital_allowances'],
            taxableProfit: $data['taxable_profit'],
            taxRate: $data['tax_rate'],
            calculatedTax: $data['calculated_tax'],
            minimumTax: $data['minimum_tax'],
            minimumTaxApplies: $data['minimum_tax_applies'],
            taxLiability: $data['tax_liability'],
            effectiveRate: $data['effective_rate']
        );
    }

    public function toArray(): array
    {
        return [
            'classification' => $this->classification,
            'turnover' => $this->turnover,
            'accounting_profit' => $this->accountingProfit,
            'adjustments' => $this->adjustments,
            'adjusted_profit' => $this->adjustedProfit,
            'capital_allowances' => $this->capitalAllowances,
            'taxable_profit' => $this->taxableProfit,
            'tax_rate' => $this->taxRate,
            'calculated_tax' => $this->calculatedTax,
            'minimum_tax' => $this->minimumTax,
            'minimum_tax_applies' => $this->minimumTaxApplies,
            'tax_liability' => $this->taxLiability,
            'effective_rate' => $this->effectiveRate,
        ];
    }
}
```

### PIT Result

**File:** `app/Services/Calculation/Results/PITResult.php`

```php
<?php

namespace App\Services\Calculation\Results;

readonly class PITResult
{
    public function __construct(
        public array $incomeBreakdown,
        public float $grossIncome,
        public array $reliefs,
        public float $taxableIncome,
        public array $taxByBand,
        public float $taxLiability,
        public float $effectiveRate
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            incomeBreakdown: $data['income_breakdown'],
            grossIncome: $data['gross_income'],
            reliefs: $data['reliefs'],
            taxableIncome: $data['taxable_income'],
            taxByBand: $data['tax_by_band'],
            taxLiability: $data['tax_liability'],
            effectiveRate: $data['effective_rate']
        );
    }

    public function toArray(): array
    {
        return [
            'income_breakdown' => $this->incomeBreakdown,
            'gross_income' => $this->grossIncome,
            'reliefs' => $this->reliefs,
            'taxable_income' => $this->taxableIncome,
            'tax_by_band' => $this->taxByBand,
            'tax_liability' => $this->taxLiability,
            'effective_rate' => $this->effectiveRate,
        ];
    }
}
```

### VAT Result

**File:** `app/Services/Calculation/Results/VATResult.php`

```php
<?php

namespace App\Services\Calculation\Results;

readonly class VATResult
{
    public function __construct(
        public array $sales,
        public float $outputVat,
        public array $inputVat,
        public float $totalInputVat,
        public float $totalRecoverable,
        public float $nonRecoverable,
        public float $netVatPayable,
        public float $vatRefundable
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            sales: $data['sales'],
            outputVat: $data['output_vat'],
            inputVat: $data['input_vat'],
            totalInputVat: $data['total_input_vat'],
            totalRecoverable: $data['total_recoverable'],
            nonRecoverable: $data['non_recoverable'],
            netVatPayable: $data['net_vat_payable'],
            vatRefundable: $data['vat_refundable']
        );
    }

    public function toArray(): array
    {
        return [
            'sales' => $this->sales,
            'output_vat' => $this->outputVat,
            'input_vat' => $this->inputVat,
            'total_input_vat' => $this->totalInputVat,
            'total_recoverable' => $this->totalRecoverable,
            'non_recoverable' => $this->nonRecoverable,
            'net_vat_payable' => $this->netVatPayable,
            'vat_refundable' => $this->vatRefundable,
        ];
    }
}
```

### CGT Result

**File:** `app/Services/Calculation/Results/CGTResult.php`

```php
<?php

namespace App\Services\Calculation\Results;

readonly class CGTResult
{
    public function __construct(
        public string $entityType,
        public array $disposals,
        public float $totalProceeds,
        public float $totalCost,
        public float $totalGain,
        public float $totalExemptions,
        public float $taxableGain,
        public float $rate,
        public float $taxLiability
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            entityType: $data['entity_type'],
            disposals: $data['disposals'],
            totalProceeds: $data['total_proceeds'],
            totalCost: $data['total_cost'],
            totalGain: $data['total_gain'],
            totalExemptions: $data['total_exemptions'],
            taxableGain: $data['taxable_gain'],
            rate: $data['rate'],
            taxLiability: $data['tax_liability']
        );
    }

    public function toArray(): array
    {
        return [
            'entity_type' => $this->entityType,
            'disposals' => $this->disposals,
            'total_proceeds' => $this->totalProceeds,
            'total_cost' => $this->totalCost,
            'total_gain' => $this->totalGain,
            'total_exemptions' => $this->totalExemptions,
            'taxable_gain' => $this->taxableGain,
            'rate' => $this->rate,
            'tax_liability' => $this->taxLiability,
        ];
    }
}
```

### Development Levy Result

**File:** `app/Services/Calculation/Results/DevelopmentLevyResult.php`

```php
<?php

namespace App\Services\Calculation\Results;

readonly class DevelopmentLevyResult
{
    public function __construct(
        public array $inputValues,
        public array $levies,
        public float $totalLevy,
        public float $combinedRate
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            inputValues: $data['input_values'],
            levies: $data['levies'],
            totalLevy: $data['total_levy'],
            combinedRate: $data['combined_rate']
        );
    }

    public function toArray(): array
    {
        return [
            'input_values' => $this->inputValues,
            'levies' => $this->levies,
            'total_levy' => $this->totalLevy,
            'combined_rate' => $this->combinedRate,
        ];
    }
}
```

### PAYE Analysis Result

**File:** `app/Services/Calculation/Results/PAYEAnalysisResult.php`

```php
<?php

namespace App\Services\Calculation\Results;

readonly class PAYEAnalysisResult
{
    public function __construct(
        public int $employeeCount,
        public float $totalGrossSalaries,
        public float $totalPayeDue,
        public float $averageTaxRate,
        public array $employees
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            employeeCount: $data['employee_count'],
            totalGrossSalaries: $data['total_gross_salaries'],
            totalPayeDue: $data['total_paye_due'],
            averageTaxRate: $data['average_tax_rate'],
            employees: $data['employees']
        );
    }

    public function toArray(): array
    {
        return [
            'employee_count' => $this->employeeCount,
            'total_gross_salaries' => $this->totalGrossSalaries,
            'total_paye_due' => $this->totalPayeDue,
            'average_tax_rate' => $this->averageTaxRate,
            'employees' => $this->employees,
        ];
    }
}
```

---

## Calculator Summary

| Calculator | Tax Type | Entity | Key Features |
|------------|----------|--------|--------------|
| CITCalculator | Company Income Tax | Company | Classification, capital allowances, minimum tax |
| PITCalculator | Personal Income Tax | Individual | Tax bands, reliefs, consolidated relief |
| VATCalculator | Value Added Tax | Both | Input/output VAT, recovery rules |
| CGTCalculator | Capital Gains Tax | Both | Disposal processing, exemptions |
| DevelopmentLevyCalculator | Levies | Company | Multiple vs unified levy |
| PAYECalculator | Payroll Tax | Company | Employee-by-employee analysis |

---

## Next Steps

Once calculators are implemented, proceed to:
→ **04_ORCHESTRATION.md** - Service orchestration and results aggregation
