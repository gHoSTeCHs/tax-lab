# TaxLab Implementation - Module 5: Service Orchestration

## Overview

This document defines the orchestration layer that coordinates tax calculations, aggregates results, analyzes optimization opportunities, and manages the calculation workflow.

---

## Tax Calculation Service (Orchestrator)

**File:** `app/Services/Calculation/TaxCalculationService.php`

```php
<?php

namespace App\Services\Calculation;

use App\Enums\CalculationType;
use App\Enums\CalculationStatus;
use App\Enums\TaxRegime;
use App\Models\Calculation;
use App\Models\ClientFinancial;
use App\Models\TaxClient;
use App\Models\FirmUser;
use App\Services\Calculation\Calculators\CITCalculator;
use App\Services\Calculation\Calculators\PITCalculator;
use App\Services\Calculation\Calculators\VATCalculator;
use App\Services\Calculation\Calculators\CGTCalculator;
use App\Services\Calculation\Calculators\DevelopmentLevyCalculator;
use App\Services\Calculation\Calculators\PAYECalculator;
use Illuminate\Support\Facades\DB;

class TaxCalculationService
{
    public function __construct(
        protected TaxRulesRepository $rulesRepository,
        protected CITCalculator $citCalculator,
        protected PITCalculator $pitCalculator,
        protected VATCalculator $vatCalculator,
        protected CGTCalculator $cgtCalculator,
        protected DevelopmentLevyCalculator $developmentLevyCalculator,
        protected PAYECalculator $payeCalculator,
        protected OptimizationAnalyzer $optimizationAnalyzer,
        protected ResultsAggregator $resultsAggregator
    ) {}

    public function runCalculation(
        TaxClient $client,
        ClientFinancial $financial,
        CalculationType $type,
        FirmUser $performedBy
    ): Calculation {
        $startTime = microtime(true);

        return DB::transaction(function () use ($client, $financial, $type, $performedBy, $startTime) {
            $this->markPreviousAsSuperseded($client, $financial->fiscal_year);

            $inputs = $this->prepareInputs($financial, $client);
            $results = $this->executeCalculations($type, $inputs, $client->entity_type);
            $summary = $this->resultsAggregator->aggregate($results);
            $optimizations = $this->optimizationAnalyzer->analyze($results, $inputs);

            $calculationTime = (int) ((microtime(true) - $startTime) * 1000);

            return Calculation::create([
                'tax_client_id' => $client->id,
                'firm_id' => $client->firm_id,
                'fiscal_year' => $financial->fiscal_year,
                'calculation_type' => $type,
                'rules_version' => $this->rulesRepository->getVersion(),
                'financial_version' => $financial->version,
                'inputs' => $inputs,
                'results' => $results,
                'summary' => $summary,
                'optimizations' => $optimizations,
                'status' => CalculationStatus::COMPLETED,
                'performed_by' => $performedBy->id,
                'calculation_time_ms' => $calculationTime,
            ]);
        });
    }

    public function runQuickEstimate(array $basicInputs, string $entityType): array
    {
        $results = [];

        if ($entityType === 'company') {
            $results['cit'] = $this->citCalculator->calculateBothRegimes([
                'turnover' => $basicInputs['turnover'] ?? 0,
                'accounting_profit' => $basicInputs['profit'] ?? 0,
                'add_backs' => 0,
                'deductions' => 0,
                'assets' => [],
            ]);
        } else {
            $results['pit'] = $this->pitCalculator->calculateBothRegimes([
                'employment_income' => $basicInputs['income'] ?? 0,
            ]);
        }

        return $this->resultsAggregator->aggregate($results);
    }

    protected function prepareInputs(ClientFinancial $financial, TaxClient $client): array
    {
        $incomeData = $financial->income_data ?? [];
        $balanceSheetData = $financial->balance_sheet_data ?? [];
        $employmentData = $financial->employment_data ?? [];
        $vatData = $financial->vat_data ?? [];
        $capitalData = $financial->capital_data ?? [];
        $individualData = $financial->individual_data ?? [];

        return [
            'entity_type' => $client->entity_type,
            'fiscal_year' => $financial->fiscal_year,
            'cit_inputs' => [
                'turnover' => $incomeData['turnover'] ?? 0,
                'accounting_profit' => $incomeData['profit_before_tax'] ?? 0,
                'add_backs' => $this->calculateAddBacks($incomeData),
                'deductions' => $this->calculateDeductions($incomeData),
                'assets' => $this->prepareAssets($balanceSheetData),
            ],
            'pit_inputs' => [
                'employment_income' => $individualData['employment_income'] ?? 0,
                'rental_income' => $individualData['rental_income'] ?? 0,
                'business_income' => $individualData['business_income'] ?? 0,
                'investment_income' => $individualData['investment_income'] ?? 0,
                'other_income' => $individualData['other_income'] ?? 0,
                'pension_contribution' => $individualData['pension_contribution'] ?? 0,
                'nhf_contribution' => $individualData['nhf_contribution'] ?? 0,
                'life_insurance' => $individualData['life_insurance'] ?? 0,
            ],
            'vat_inputs' => [
                'vatable_sales' => $vatData['vatable_sales'] ?? 0,
                'exempt_sales' => $vatData['exempt_sales'] ?? 0,
                'zero_rated_sales' => $vatData['zero_rated_sales'] ?? 0,
                'vat_on_goods' => $vatData['vat_on_goods'] ?? 0,
                'vat_on_services' => $vatData['vat_on_services'] ?? 0,
                'vat_on_capital' => $vatData['vat_on_capital'] ?? 0,
            ],
            'cgt_inputs' => [
                'entity_type' => $client->entity_type,
                'disposals' => $capitalData['disposals'] ?? [],
            ],
            'development_levy_inputs' => [
                'assessable_profit' => $incomeData['taxable_profit'] ?? $incomeData['profit_before_tax'] ?? 0,
                'profit_before_tax' => $incomeData['profit_before_tax'] ?? 0,
                'net_profit' => $incomeData['profit_after_tax'] ?? 0,
            ],
            'paye_inputs' => [
                'employees' => $employmentData['employees'] ?? [],
            ],
        ];
    }

    protected function executeCalculations(CalculationType $type, array $inputs, string $entityType): array
    {
        $results = [];

        $calculators = $this->getCalculatorsForType($type, $entityType);

        foreach ($calculators as $key => $inputKey) {
            $calculator = $this->getCalculator($key);
            if ($calculator && isset($inputs[$inputKey])) {
                $results[$key] = $calculator->calculateBothRegimes($inputs[$inputKey]);
            }
        }

        return $results;
    }

    protected function getCalculatorsForType(CalculationType $type, string $entityType): array
    {
        return match ($type) {
            CalculationType::FULL_ANALYSIS => $this->getFullAnalysisCalculators($entityType),
            CalculationType::CIT_ONLY => ['cit' => 'cit_inputs'],
            CalculationType::PIT_ONLY => ['pit' => 'pit_inputs'],
            CalculationType::VAT_ONLY => ['vat' => 'vat_inputs'],
            CalculationType::CGT_ONLY => ['cgt' => 'cgt_inputs'],
            CalculationType::PAYE_ONLY => ['paye' => 'paye_inputs'],
            CalculationType::QUICK_ESTIMATE => $entityType === 'company'
                ? ['cit' => 'cit_inputs']
                : ['pit' => 'pit_inputs'],
        };
    }

    protected function getFullAnalysisCalculators(string $entityType): array
    {
        if ($entityType === 'company') {
            return [
                'cit' => 'cit_inputs',
                'vat' => 'vat_inputs',
                'cgt' => 'cgt_inputs',
                'development_levy' => 'development_levy_inputs',
                'paye' => 'paye_inputs',
            ];
        }

        return [
            'pit' => 'pit_inputs',
            'cgt' => 'cgt_inputs',
        ];
    }

    protected function getCalculator(string $key): ?object
    {
        return match ($key) {
            'cit' => $this->citCalculator,
            'pit' => $this->pitCalculator,
            'vat' => $this->vatCalculator,
            'cgt' => $this->cgtCalculator,
            'development_levy' => $this->developmentLevyCalculator,
            'paye' => $this->payeCalculator,
            default => null,
        };
    }

    protected function calculateAddBacks(array $incomeData): float
    {
        $addBacks = 0;
        $addBacks += $incomeData['depreciation'] ?? 0;
        $addBacks += $incomeData['general_provisions'] ?? 0;
        $addBacks += $incomeData['penalties_fines'] ?? 0;
        $addBacks += $incomeData['donations_above_limit'] ?? 0;
        $addBacks += $incomeData['non_deductible_entertainment'] ?? 0;
        $addBacks += $incomeData['non_deductible_interest'] ?? 0;

        return $addBacks;
    }

    protected function calculateDeductions(array $incomeData): float
    {
        $deductions = 0;
        $deductions += $incomeData['exempt_income'] ?? 0;
        $deductions += $incomeData['tax_exempt_dividends'] ?? 0;

        return $deductions;
    }

    protected function prepareAssets(array $balanceSheetData): array
    {
        return $balanceSheetData['fixed_assets'] ?? [];
    }

    protected function markPreviousAsSuperseded(TaxClient $client, int $fiscalYear): void
    {
        Calculation::where('tax_client_id', $client->id)
            ->where('fiscal_year', $fiscalYear)
            ->where('status', CalculationStatus::COMPLETED)
            ->update(['status' => CalculationStatus::SUPERSEDED]);
    }

    public function getHistory(TaxClient $client, ?int $fiscalYear = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Calculation::where('tax_client_id', $client->id)
            ->orderBy('created_at', 'desc');

        if ($fiscalYear) {
            $query->where('fiscal_year', $fiscalYear);
        }

        return $query->get();
    }

    public function getLatest(TaxClient $client, int $fiscalYear): ?Calculation
    {
        return Calculation::where('tax_client_id', $client->id)
            ->where('fiscal_year', $fiscalYear)
            ->where('status', CalculationStatus::COMPLETED)
            ->latest()
            ->first();
    }
}
```

---

## Results Aggregator

**File:** `app/Services/Calculation/ResultsAggregator.php`

```php
<?php

namespace App\Services\Calculation;

class ResultsAggregator
{
    public function aggregate(array $results): array
    {
        $oldRegimeTotal = $this->sumLiabilities($results, 'old_regime');
        $newRegimeTotal = $this->sumLiabilities($results, 'new_regime');

        $difference = $oldRegimeTotal - $newRegimeTotal;

        return [
            'old_regime_total' => $this->breakdownByTaxType($results, 'old_regime'),
            'new_regime_total' => $this->breakdownByTaxType($results, 'new_regime'),
            'overall_comparison' => [
                'old_total' => round($oldRegimeTotal, 2),
                'new_total' => round($newRegimeTotal, 2),
                'net_savings' => $difference > 0 ? round($difference, 2) : 0,
                'net_increase' => $difference < 0 ? round(abs($difference), 2) : 0,
                'savings_percentage' => $oldRegimeTotal > 0
                    ? round(($difference / $oldRegimeTotal) * 100, 2)
                    : 0,
                'favors' => $difference > 0 ? 'new_regime' : ($difference < 0 ? 'old_regime' : 'neutral'),
            ],
            'primary_drivers' => $this->identifyPrimaryDrivers($results),
            'tax_type_count' => count($results),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    protected function sumLiabilities(array $results, string $regime): float
    {
        $total = 0;

        foreach ($results as $taxType => $result) {
            $total += $result[$regime]['tax_liability'] ?? 0;
        }

        return $total;
    }

    protected function breakdownByTaxType(array $results, string $regime): array
    {
        $breakdown = [];
        $total = 0;

        foreach ($results as $taxType => $result) {
            $liability = $result[$regime]['tax_liability'] ?? 0;
            $breakdown[$taxType] = round($liability, 2);
            $total += $liability;
        }

        $breakdown['total'] = round($total, 2);

        return $breakdown;
    }

    protected function identifyPrimaryDrivers(array $results): array
    {
        $drivers = [];

        foreach ($results as $taxType => $result) {
            $comparison = $result['comparison'] ?? [];
            $difference = ($comparison['old_liability'] ?? 0) - ($comparison['new_liability'] ?? 0);

            if (abs($difference) > 0) {
                $drivers[] = [
                    'tax_type' => $this->formatTaxTypeName($taxType),
                    'impact' => round($difference, 2),
                    'direction' => $difference > 0 ? 'savings' : 'increase',
                    'description' => $this->generateDriverDescription($taxType, $comparison),
                ];
            }
        }

        usort($drivers, fn($a, $b) => abs($b['impact']) <=> abs($a['impact']));

        return array_slice($drivers, 0, 5);
    }

    protected function formatTaxTypeName(string $taxType): string
    {
        return match ($taxType) {
            'cit' => 'Company Income Tax',
            'pit' => 'Personal Income Tax',
            'vat' => 'VAT',
            'cgt' => 'Capital Gains Tax',
            'development_levy' => 'Development Levy',
            'paye' => 'PAYE',
            default => ucfirst(str_replace('_', ' ', $taxType)),
        };
    }

    protected function generateDriverDescription(string $taxType, array $comparison): string
    {
        $difference = abs(($comparison['old_liability'] ?? 0) - ($comparison['new_liability'] ?? 0));
        $formatted = '₦' . number_format($difference);

        return match ($taxType) {
            'cit' => "CIT rate reduction saves {$formatted}",
            'vat' => isset($comparison['additional_recovery'])
                ? "Enhanced VAT input recovery saves {$formatted}"
                : "VAT position change of {$formatted}",
            'cgt' => "CGT rate alignment increases by {$formatted}",
            'development_levy' => "Unified levy adjustment of {$formatted}",
            'pit' => "PIT band restructuring saves {$formatted}",
            'paye' => "PAYE recalculation saves {$formatted}",
            default => "Tax change of {$formatted}",
        };
    }

    public function generateInsights(array $results, array $summary): array
    {
        $insights = [];

        if (isset($results['cit'])) {
            $citComparison = $results['cit']['comparison'] ?? [];
            if (($citComparison['savings'] ?? 0) > 0) {
                $insights[] = [
                    'type' => 'savings',
                    'category' => 'cit',
                    'title' => 'CIT Rate Reduction Benefit',
                    'description' => sprintf(
                        'The reduced CIT rate from 30%% to 25%% for large companies saves ₦%s.',
                        number_format($citComparison['savings'])
                    ),
                ];
            }
        }

        if (isset($results['vat'])) {
            $vatComparison = $results['vat']['comparison'] ?? [];
            if (($vatComparison['additional_recovery'] ?? 0) > 0) {
                $insights[] = [
                    'type' => 'opportunity',
                    'category' => 'vat',
                    'title' => 'Expanded VAT Input Recovery',
                    'description' => sprintf(
                        'Under NTA 2025, services and capital VAT are now recoverable, saving ₦%s.',
                        number_format($vatComparison['additional_recovery'])
                    ),
                ];
            }
        }

        if (isset($results['cgt'])) {
            $cgtComparison = $results['cgt']['comparison'] ?? [];
            if (($cgtComparison['increase'] ?? 0) > 0) {
                $insights[] = [
                    'type' => 'warning',
                    'category' => 'cgt',
                    'title' => 'CGT Rate Increase',
                    'description' => sprintf(
                        'Capital gains are now taxed at up to 25%% (from 10%%), increasing liability by ₦%s.',
                        number_format($cgtComparison['increase'])
                    ),
                ];
            }
        }

        $overallComparison = $summary['overall_comparison'] ?? [];
        if (($overallComparison['net_savings'] ?? 0) > 0) {
            $insights[] = [
                'type' => 'summary',
                'category' => 'overall',
                'title' => 'Net Tax Position Improved',
                'description' => sprintf(
                    'Overall, you save ₦%s (%.1f%%) under the new NTA 2025 regime.',
                    number_format($overallComparison['net_savings']),
                    $overallComparison['savings_percentage']
                ),
            ];
        }

        return $insights;
    }
}
```

---

## Optimization Analyzer

**File:** `app/Services/Calculation/OptimizationAnalyzer.php`

```php
<?php

namespace App\Services\Calculation;

use App\Enums\OptimizationType;
use App\Enums\OptimizationPriority;

class OptimizationAnalyzer
{
    public function analyze(array $results, array $inputs): array
    {
        $optimizations = [];

        $optimizations = array_merge(
            $optimizations,
            $this->analyzeTimingOpportunities($results, $inputs)
        );

        $optimizations = array_merge(
            $optimizations,
            $this->analyzeStructuralOpportunities($results, $inputs)
        );

        $optimizations = array_merge(
            $optimizations,
            $this->analyzeComplianceOpportunities($results, $inputs)
        );

        usort($optimizations, function ($a, $b) {
            $priorityOrder = ['high' => 0, 'medium' => 1, 'low' => 2];
            return ($priorityOrder[$a['priority']] ?? 3) <=> ($priorityOrder[$b['priority']] ?? 3);
        });

        return $optimizations;
    }

    protected function analyzeTimingOpportunities(array $results, array $inputs): array
    {
        $opportunities = [];

        if (isset($results['cit'])) {
            $turnover = $inputs['cit_inputs']['turnover'] ?? 0;
            $classification = $results['cit']['new_regime']['classification'] ?? '';

            if ($classification === 'medium' && $turnover > 45_000_000 && $turnover <= 55_000_000) {
                $opportunities[] = [
                    'type' => OptimizationType::TIMING->value,
                    'title' => 'Revenue Deferral Opportunity',
                    'description' => 'Turnover is close to the small company threshold (₦50M). Consider deferring some revenue to qualify for 0% CIT rate.',
                    'estimated_savings' => $this->estimateThresholdSavings($turnover, 50_000_000, 0.20),
                    'priority' => OptimizationPriority::HIGH->value,
                    'action_required' => 'Review contracts for revenue recognition timing flexibility.',
                ];
            }
        }

        if (isset($results['cgt'])) {
            $cgtOld = $results['cgt']['old_regime']['tax_liability'] ?? 0;
            $cgtNew = $results['cgt']['new_regime']['tax_liability'] ?? 0;

            if ($cgtNew > $cgtOld && $cgtNew > 500_000) {
                $opportunities[] = [
                    'type' => OptimizationType::TIMING->value,
                    'title' => 'Consider Timing of Asset Disposals',
                    'description' => 'CGT rates have increased under NTA 2025. Consider whether some disposals could have been made earlier under the old regime.',
                    'estimated_savings' => 'Variable based on disposal timing',
                    'priority' => OptimizationPriority::MEDIUM->value,
                    'action_required' => 'Review planned disposals and assess timing implications.',
                ];
            }
        }

        return $opportunities;
    }

    protected function analyzeStructuralOpportunities(array $results, array $inputs): array
    {
        $opportunities = [];

        if (isset($results['cit'])) {
            $turnover = $inputs['cit_inputs']['turnover'] ?? 0;

            if ($turnover > 100_000_000 && $turnover < 200_000_000) {
                $opportunities[] = [
                    'type' => OptimizationType::STRUCTURAL->value,
                    'title' => 'Consider Business Restructuring',
                    'description' => 'With turnover above ₦100M, you are classified as a large company. Consider whether splitting into smaller entities could reduce overall CIT burden.',
                    'estimated_savings' => 'Requires detailed analysis',
                    'priority' => OptimizationPriority::MEDIUM->value,
                    'action_required' => 'Consult with tax advisor on restructuring feasibility.',
                ];
            }
        }

        return $opportunities;
    }

    protected function analyzeComplianceOpportunities(array $results, array $inputs): array
    {
        $opportunities = [];

        if (isset($results['cit'])) {
            $capitalAllowances = $results['cit']['old_regime']['capital_allowances']['total_allowance'] ?? 0;

            if ($capitalAllowances === 0 && ($inputs['cit_inputs']['accounting_profit'] ?? 0) > 0) {
                $opportunities[] = [
                    'type' => OptimizationType::COMPLIANCE->value,
                    'title' => 'Review Capital Allowances',
                    'description' => 'No capital allowances claimed. Ensure all qualifying assets are included in the capital allowance schedule.',
                    'estimated_savings' => 'Depends on asset base',
                    'priority' => OptimizationPriority::HIGH->value,
                    'action_required' => 'Review fixed asset register for qualifying assets.',
                ];
            }
        }

        if (isset($results['vat'])) {
            $oldRecoverable = $results['vat']['old_regime']['total_recoverable'] ?? 0;
            $newRecoverable = $results['vat']['new_regime']['total_recoverable'] ?? 0;
            $additionalRecovery = $newRecoverable - $oldRecoverable;

            if ($additionalRecovery > 0) {
                $opportunities[] = [
                    'type' => OptimizationType::COMPLIANCE->value,
                    'title' => 'Claim Expanded VAT Input Recovery',
                    'description' => sprintf(
                        'Under NTA 2025, you can now recover VAT on services and capital items. This represents ₦%s in additional recoverable input VAT.',
                        number_format($additionalRecovery)
                    ),
                    'estimated_savings' => $additionalRecovery,
                    'priority' => OptimizationPriority::HIGH->value,
                    'action_required' => 'Ensure VAT returns reflect expanded input recovery rules.',
                ];
            }
        }

        if (isset($results['pit'])) {
            $reliefs = $results['pit']['old_regime']['reliefs'] ?? [];

            if (($reliefs['pension_relief'] ?? 0) === 0) {
                $opportunities[] = [
                    'type' => OptimizationType::COMPLIANCE->value,
                    'title' => 'Maximize Pension Contributions',
                    'description' => 'No pension contribution relief claimed. Pension contributions provide tax relief under the old regime.',
                    'estimated_savings' => 'Variable based on contribution',
                    'priority' => OptimizationPriority::MEDIUM->value,
                    'action_required' => 'Review pension contribution status.',
                ];
            }
        }

        return $opportunities;
    }

    protected function estimateThresholdSavings(float $current, float $threshold, float $rate): float
    {
        $excess = $current - $threshold;
        if ($excess <= 0) {
            return 0;
        }

        return round($excess * $rate, 2);
    }
}
```

---

## Controller

**File:** `app/Http/Controllers/App/CalculationController.php`

```php
<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\Calculation\RunCalculationRequest;
use App\Models\Calculation;
use App\Models\ClientFinancial;
use App\Models\TaxClient;
use App\Services\Calculation\TaxCalculationService;
use App\Services\Calculation\ResultsAggregator;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CalculationController extends Controller
{
    public function __construct(
        protected TaxCalculationService $calculationService,
        protected ResultsAggregator $resultsAggregator
    ) {}

    public function index(TaxClient $client): Response
    {
        $calculations = $this->calculationService->getHistory($client);

        return Inertia::render('App/Clients/Calculations/Index', [
            'client' => $client->load('firm'),
            'calculations' => $calculations,
        ]);
    }

    public function create(TaxClient $client): Response
    {
        $financials = ClientFinancial::where('tax_client_id', $client->id)
            ->orderBy('fiscal_year', 'desc')
            ->get(['id', 'fiscal_year', 'is_complete', 'data_source', 'version']);

        return Inertia::render('App/Clients/Calculations/Create', [
            'client' => $client,
            'financials' => $financials,
            'calculationTypes' => $this->getCalculationTypeOptions($client->entity_type),
        ]);
    }

    public function store(RunCalculationRequest $request, TaxClient $client): RedirectResponse
    {
        $financial = ClientFinancial::where('tax_client_id', $client->id)
            ->where('fiscal_year', $request->fiscal_year)
            ->firstOrFail();

        $calculation = $this->calculationService->runCalculation(
            $client,
            $financial,
            $request->calculation_type,
            $request->user()->firmUser
        );

        return redirect()
            ->route('app.clients.calculations.show', [$client, $calculation])
            ->with('success', 'Calculation completed successfully.');
    }

    public function show(TaxClient $client, Calculation $calculation): Response
    {
        $insights = $this->resultsAggregator->generateInsights(
            $calculation->results,
            $calculation->summary
        );

        return Inertia::render('App/Clients/Calculations/Show', [
            'client' => $client,
            'calculation' => $calculation,
            'insights' => $insights,
        ]);
    }

    protected function getCalculationTypeOptions(string $entityType): array
    {
        $options = [
            ['value' => 'full_analysis', 'label' => 'Full Analysis', 'description' => 'Comprehensive calculation of all applicable taxes'],
        ];

        if ($entityType === 'company') {
            $options[] = ['value' => 'cit_only', 'label' => 'CIT Only', 'description' => 'Company Income Tax calculation only'];
            $options[] = ['value' => 'vat_only', 'label' => 'VAT Only', 'description' => 'VAT analysis only'];
            $options[] = ['value' => 'paye_only', 'label' => 'PAYE Only', 'description' => 'Payroll tax analysis'];
        } else {
            $options[] = ['value' => 'pit_only', 'label' => 'PIT Only', 'description' => 'Personal Income Tax calculation only'];
        }

        $options[] = ['value' => 'cgt_only', 'label' => 'CGT Only', 'description' => 'Capital Gains Tax calculation only'];

        return $options;
    }
}
```

---

## Form Request

**File:** `app/Http/Requests/Calculation/RunCalculationRequest.php`

```php
<?php

namespace App\Http\Requests\Calculation;

use App\Enums\CalculationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RunCalculationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fiscal_year' => ['required', 'integer', 'min:2000', 'max:2099'],
            'calculation_type' => ['required', Rule::enum(CalculationType::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'fiscal_year.required' => 'Please select a fiscal year.',
            'calculation_type.required' => 'Please select a calculation type.',
        ];
    }
}
```

---

## Routes

Add to `routes/web.php`:

```php
use App\Http\Controllers\App\CalculationController;

Route::middleware(['auth', 'verified', 'firm'])->prefix('app')->name('app.')->group(function () {
    Route::prefix('clients/{client}')->name('clients.')->group(function () {
        Route::prefix('calculations')->name('calculations.')->group(function () {
            Route::get('/', [CalculationController::class, 'index'])->name('index');
            Route::get('/create', [CalculationController::class, 'create'])->name('create');
            Route::post('/', [CalculationController::class, 'store'])->name('store');
            Route::get('/{calculation}', [CalculationController::class, 'show'])->name('show');
        });
    });
});
```

---

## Service Provider Update

Update `app/Providers/TaxCalculationServiceProvider.php`:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Calculation\TaxRulesRepository;
use App\Services\Calculation\CapitalAllowanceCalculator;
use App\Services\Calculation\TaxCalculationService;
use App\Services\Calculation\OptimizationAnalyzer;
use App\Services\Calculation\ResultsAggregator;
use App\Services\Calculation\Calculators\CITCalculator;
use App\Services\Calculation\Calculators\PITCalculator;
use App\Services\Calculation\Calculators\VATCalculator;
use App\Services\Calculation\Calculators\CGTCalculator;
use App\Services\Calculation\Calculators\DevelopmentLevyCalculator;
use App\Services\Calculation\Calculators\PAYECalculator;

class TaxCalculationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TaxRulesRepository::class);
        $this->app->singleton(CapitalAllowanceCalculator::class);
        $this->app->singleton(ResultsAggregator::class);
        $this->app->singleton(OptimizationAnalyzer::class);

        $this->app->singleton(CITCalculator::class, function ($app) {
            return new CITCalculator(
                $app->make(TaxRulesRepository::class),
                $app->make(CapitalAllowanceCalculator::class)
            );
        });

        $this->app->singleton(PITCalculator::class, function ($app) {
            return new PITCalculator($app->make(TaxRulesRepository::class));
        });

        $this->app->singleton(VATCalculator::class, function ($app) {
            return new VATCalculator($app->make(TaxRulesRepository::class));
        });

        $this->app->singleton(CGTCalculator::class, function ($app) {
            return new CGTCalculator($app->make(TaxRulesRepository::class));
        });

        $this->app->singleton(DevelopmentLevyCalculator::class, function ($app) {
            return new DevelopmentLevyCalculator($app->make(TaxRulesRepository::class));
        });

        $this->app->singleton(PAYECalculator::class, function ($app) {
            return new PAYECalculator($app->make(TaxRulesRepository::class));
        });

        $this->app->singleton(TaxCalculationService::class, function ($app) {
            return new TaxCalculationService(
                $app->make(TaxRulesRepository::class),
                $app->make(CITCalculator::class),
                $app->make(PITCalculator::class),
                $app->make(VATCalculator::class),
                $app->make(CGTCalculator::class),
                $app->make(DevelopmentLevyCalculator::class),
                $app->make(PAYECalculator::class),
                $app->make(OptimizationAnalyzer::class),
                $app->make(ResultsAggregator::class)
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
```

---

## Next Steps

Once orchestration is implemented, proceed to:
→ **05_FRONTEND_TYPES.md** - TypeScript type definitions
