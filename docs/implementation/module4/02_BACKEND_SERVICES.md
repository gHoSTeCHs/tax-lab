# TaxLab Implementation - Module 4 Backend Services

## Overview

This document defines the service layer for the Financial Data Entry module, including the main financial service, validation service, and import service.

---

## Service 1: FinancialService

**File:** `app/Services/Financial/FinancialService.php`

```php
<?php

namespace App\Services\Financial;

use App\Enums\ActivityAction;
use App\Enums\DataSource;
use App\Enums\FinancialSection;
use App\Models\ClientFinancial;
use App\Models\ClientFinancialVersion;
use App\Models\FirmUser;
use App\Models\TaxClient;
use App\Services\Activity\ActivityService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinancialService
{
    public function __construct(
        private ActivityService $activityService,
        private FinancialValidationService $validationService
    ) {}

    public function getForClient(TaxClient $client, ?int $year = null): Collection|ClientFinancial|null
    {
        if ($year !== null) {
            return $client->financials()->forYear($year)->first();
        }

        return $client->financials()
            ->orderByDesc('fiscal_year')
            ->get();
    }

    public function getPaginated(TaxClient $client, int $perPage = 10): LengthAwarePaginator
    {
        return $client->financials()
            ->orderByDesc('fiscal_year')
            ->paginate($perPage);
    }

    public function create(TaxClient $client, FirmUser $user, array $data): ClientFinancial
    {
        return DB::transaction(function () use ($client, $user, $data) {
            $financial = ClientFinancial::create([
                'tax_client_id' => $client->id,
                'firm_id' => $client->firm_id,
                'fiscal_year' => $data['fiscal_year'],
                'fiscal_year_start' => $data['fiscal_year_start'],
                'fiscal_year_end' => $data['fiscal_year_end'],
                'data_source' => $data['data_source'] ?? DataSource::MANAGEMENT,
                'income_data' => $data['income_data'] ?? null,
                'balance_sheet_data' => $data['balance_sheet_data'] ?? null,
                'employment_data' => $data['employment_data'] ?? null,
                'vat_data' => $data['vat_data'] ?? null,
                'capital_data' => $data['capital_data'] ?? null,
                'individual_data' => $data['individual_data'] ?? null,
                'partnership_data' => $data['partnership_data'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            $financial->updateCompletionStatus();
            $financial->createVersion($user, 'Initial creation');

            $this->activityService->log(
                $client,
                ActivityAction::CREATED,
                "Created financial data for FY {$financial->fiscal_year}",
                $user,
                ['fiscal_year' => $financial->fiscal_year]
            );

            return $financial;
        });
    }

    public function update(ClientFinancial $financial, FirmUser $user, array $data): array
    {
        if ($financial->isLocked()) {
            throw new \Exception('Cannot update locked financial data');
        }

        return DB::transaction(function () use ($financial, $user, $data) {
            $changes = $this->detectChanges($financial, $data);

            if (empty($changes)) {
                return ['changes' => [], 'warnings' => []];
            }

            $financial->createVersion($user, $this->summarizeChanges($changes));

            $updateData = [];

            if (isset($data['data_source'])) {
                $updateData['data_source'] = $data['data_source'];
            }

            foreach (FinancialSection::cases() as $section) {
                $key = $section->dataColumn();
                if (isset($data[$key])) {
                    $updateData[$key] = array_merge(
                        $financial->$key ?? [],
                        $data[$key]
                    );
                }
            }

            if (isset($data['notes'])) {
                $updateData['notes'] = $data['notes'];
            }

            $financial->update($updateData);
            $financial->incrementVersion();
            $financial->updateCompletionStatus();

            $warnings = $this->validationService->getWarnings($financial);

            $this->activityService->log(
                $financial->taxClient,
                ActivityAction::UPDATED,
                "Updated financial data for FY {$financial->fiscal_year}",
                $user,
                [
                    'fiscal_year' => $financial->fiscal_year,
                    'sections_updated' => array_keys($changes),
                ]
            );

            return [
                'changes' => $changes,
                'warnings' => $warnings,
            ];
        });
    }

    public function updateSection(
        ClientFinancial $financial,
        FinancialSection $section,
        array $data,
        FirmUser $user
    ): array {
        if ($financial->isLocked()) {
            throw new \Exception('Cannot update locked financial data');
        }

        return DB::transaction(function () use ($financial, $section, $data, $user) {
            $oldData = $financial->getSectionData($section);
            $newData = array_merge($oldData, $data);

            $financial->createVersion($user, "Updated {$section->label()}");
            $financial->setSectionData($section, $newData);
            $financial->incrementVersion();
            $financial->updateCompletionStatus();

            $warnings = $this->validationService->validateSection($financial, $section);

            return [
                'section' => $section->value,
                'warnings' => $warnings,
            ];
        });
    }

    public function lock(ClientFinancial $financial, FirmUser $user): void
    {
        if ($financial->isLocked()) {
            throw new \Exception('Financial data is already locked');
        }

        $financial->lock($user);

        $this->activityService->log(
            $financial->taxClient,
            ActivityAction::UPDATED,
            "Locked financial data for FY {$financial->fiscal_year}",
            $user,
            ['action' => 'lock', 'fiscal_year' => $financial->fiscal_year]
        );
    }

    public function unlock(ClientFinancial $financial, FirmUser $user): void
    {
        if (!$financial->isLocked()) {
            throw new \Exception('Financial data is not locked');
        }

        $financial->unlock();

        $this->activityService->log(
            $financial->taxClient,
            ActivityAction::UPDATED,
            "Unlocked financial data for FY {$financial->fiscal_year}",
            $user,
            ['action' => 'unlock', 'fiscal_year' => $financial->fiscal_year]
        );
    }

    public function getVersions(ClientFinancial $financial): Collection
    {
        return $financial->versions()
            ->with('creator:id,name')
            ->get();
    }

    public function restoreVersion(
        ClientFinancial $financial,
        int $version,
        FirmUser $user
    ): ClientFinancial {
        if ($financial->isLocked()) {
            throw new \Exception('Cannot restore locked financial data');
        }

        $versionRecord = $financial->versions()
            ->where('version', $version)
            ->firstOrFail();

        $versionRecord->restore($user);

        $this->activityService->log(
            $financial->taxClient,
            ActivityAction::RESTORED,
            "Restored financial data to version {$version} for FY {$financial->fiscal_year}",
            $user,
            ['restored_version' => $version, 'fiscal_year' => $financial->fiscal_year]
        );

        return $financial->fresh();
    }

    public function copyFromPriorYear(
        TaxClient $client,
        int $fromYear,
        int $toYear,
        FirmUser $user,
        ?float $adjustmentPercent = null
    ): ClientFinancial {
        $sourceFinancial = $client->getFinancialForYear($fromYear);

        if (!$sourceFinancial) {
            throw new \Exception("No financial data found for year {$fromYear}");
        }

        $existingTarget = $client->getFinancialForYear($toYear);
        if ($existingTarget) {
            throw new \Exception("Financial data already exists for year {$toYear}");
        }

        $data = [
            'fiscal_year' => $toYear,
            'fiscal_year_start' => "{$toYear}-01-01",
            'fiscal_year_end' => "{$toYear}-12-31",
            'data_source' => DataSource::PROJECTED,
            'income_data' => $this->applyAdjustment($sourceFinancial->income_data, $adjustmentPercent),
            'balance_sheet_data' => $this->applyAdjustment($sourceFinancial->balance_sheet_data, $adjustmentPercent),
            'employment_data' => $sourceFinancial->employment_data,
            'vat_data' => $this->applyAdjustment($sourceFinancial->vat_data, $adjustmentPercent),
            'capital_data' => null,
            'notes' => "Copied from FY {$fromYear}" . ($adjustmentPercent ? " with {$adjustmentPercent}% adjustment" : ''),
        ];

        return $this->create($client, $user, $data);
    }

    public function delete(ClientFinancial $financial, FirmUser $user): void
    {
        if ($financial->isLocked()) {
            throw new \Exception('Cannot delete locked financial data');
        }

        $fiscalYear = $financial->fiscal_year;
        $client = $financial->taxClient;

        $financial->versions()->delete();
        $financial->delete();

        $this->activityService->log(
            $client,
            ActivityAction::DELETED,
            "Deleted financial data for FY {$fiscalYear}",
            $user,
            ['fiscal_year' => $fiscalYear]
        );
    }

    public function getReadinessStatus(ClientFinancial $financial): array
    {
        $completionPercentage = $financial->getCompletionPercentage();
        $warnings = $this->validationService->getWarnings($financial);
        $crossFieldErrors = $this->validationService->checkCrossFieldConsistency($financial);

        return [
            'is_ready' => $completionPercentage === 100 && empty($crossFieldErrors),
            'completion_percentage' => $completionPercentage,
            'data_source' => $financial->data_source?->label(),
            'warnings_count' => count($warnings),
            'warnings' => $warnings,
            'errors' => $crossFieldErrors,
            'is_locked' => $financial->isLocked(),
            'is_verified' => $financial->isVerified(),
        ];
    }

    private function detectChanges(ClientFinancial $financial, array $data): array
    {
        $changes = [];

        foreach (FinancialSection::cases() as $section) {
            $key = $section->dataColumn();
            if (isset($data[$key])) {
                $oldData = $financial->$key ?? [];
                $newData = $data[$key];

                $diff = array_diff_assoc($newData, $oldData);
                if (!empty($diff)) {
                    $changes[$section->value] = [
                        'fields' => array_keys($diff),
                        'count' => count($diff),
                    ];
                }
            }
        }

        return $changes;
    }

    private function summarizeChanges(array $changes): string
    {
        $parts = [];
        foreach ($changes as $section => $change) {
            $parts[] = "{$section}: {$change['count']} field(s)";
        }
        return 'Updated ' . implode(', ', $parts);
    }

    private function applyAdjustment(?array $data, ?float $percent): ?array
    {
        if ($data === null || $percent === null) {
            return $data;
        }

        $multiplier = 1 + ($percent / 100);

        return array_map(function ($value) use ($multiplier) {
            if (is_numeric($value)) {
                return (int) round($value * $multiplier);
            }
            return $value;
        }, $data);
    }
}
```

---

## Service 2: FinancialValidationService

**File:** `app/Services/Financial/FinancialValidationService.php`

```php
<?php

namespace App\Services\Financial;

use App\Enums\FinancialSection;
use App\Models\ClientFinancial;

class FinancialValidationService
{
    private const VAT_RATE = 0.075;
    private const TOLERANCE = 0.05;

    public function validateSection(ClientFinancial $financial, FinancialSection $section): array
    {
        return match ($section) {
            FinancialSection::INCOME => $this->validateIncomeData($financial),
            FinancialSection::BALANCE_SHEET => $this->validateBalanceSheet($financial),
            FinancialSection::EMPLOYMENT => $this->validateEmploymentData($financial),
            FinancialSection::VAT => $this->validateVatData($financial),
            FinancialSection::CAPITAL => $this->validateCapitalData($financial),
            default => [],
        };
    }

    public function getWarnings(ClientFinancial $financial): array
    {
        $warnings = [];

        $warnings = array_merge($warnings, $this->validateIncomeData($financial));
        $warnings = array_merge($warnings, $this->validateBalanceSheet($financial));
        $warnings = array_merge($warnings, $this->validateEmploymentData($financial));
        $warnings = array_merge($warnings, $this->validateVatData($financial));
        $warnings = array_merge($warnings, $this->getReasonablenessWarnings($financial));

        return $warnings;
    }

    public function checkCrossFieldConsistency(ClientFinancial $financial): array
    {
        $errors = [];

        $income = $financial->income_data ?? [];
        if ($this->hasValues($income, ['turnover', 'costOfSales', 'grossProfit'])) {
            $calculated = ($income['turnover'] ?? 0) - ($income['costOfSales'] ?? 0);
            $actual = $income['grossProfit'] ?? 0;

            if (!$this->isWithinTolerance($calculated, $actual)) {
                $errors[] = [
                    'section' => 'income',
                    'field' => 'grossProfit',
                    'message' => 'Gross Profit should equal Revenue minus Cost of Sales',
                    'expected' => $calculated,
                    'actual' => $actual,
                ];
            }
        }

        $balanceSheet = $financial->balance_sheet_data ?? [];
        if ($this->hasValues($balanceSheet, ['totalAssets', 'totalLiabilities', 'totalEquity'])) {
            $calculated = ($balanceSheet['totalLiabilities'] ?? 0) + ($balanceSheet['totalEquity'] ?? 0);
            $actual = $balanceSheet['totalAssets'] ?? 0;

            if (!$this->isWithinTolerance($calculated, $actual)) {
                $errors[] = [
                    'section' => 'balance_sheet',
                    'field' => 'totalAssets',
                    'message' => 'Total Assets should equal Total Liabilities plus Total Equity',
                    'expected' => $calculated,
                    'actual' => $actual,
                ];
            }
        }

        $vat = $financial->vat_data ?? [];
        if ($this->hasValues($vat, ['vatableSales', 'vatOutput'])) {
            $calculated = ($vat['vatableSales'] ?? 0) * self::VAT_RATE;
            $actual = $vat['vatOutput'] ?? 0;

            if (!$this->isWithinTolerance($calculated, $actual, 0.1)) {
                $errors[] = [
                    'section' => 'vat',
                    'field' => 'vatOutput',
                    'message' => 'VAT Output should approximately equal VATable Sales × 7.5%',
                    'expected' => $calculated,
                    'actual' => $actual,
                ];
            }
        }

        $employment = $financial->employment_data ?? [];
        if ($this->hasValues($employment, ['totalPayroll', 'payeRemitted'])) {
            if (($employment['payeRemitted'] ?? 0) > ($employment['totalPayroll'] ?? 0)) {
                $errors[] = [
                    'section' => 'employment',
                    'field' => 'payeRemitted',
                    'message' => 'PAYE Remitted cannot exceed Total Payroll',
                    'expected' => 'Less than or equal to ' . $employment['totalPayroll'],
                    'actual' => $employment['payeRemitted'],
                ];
            }
        }

        return $errors;
    }

    public function getReasonablenessWarnings(ClientFinancial $financial): array
    {
        $warnings = [];
        $income = $financial->income_data ?? [];

        if ($this->hasValues($income, ['turnover', 'grossProfit'])) {
            $turnover = $income['turnover'];
            $grossProfit = $income['grossProfit'];

            if ($turnover > 0) {
                $grossMargin = ($grossProfit / $turnover) * 100;

                if ($grossMargin < 0) {
                    $warnings[] = [
                        'type' => 'reasonableness',
                        'section' => 'income',
                        'field' => 'grossProfit',
                        'message' => 'Negative gross margin detected',
                        'value' => round($grossMargin, 2) . '%',
                        'severity' => 'warning',
                    ];
                } elseif ($grossMargin > 90) {
                    $warnings[] = [
                        'type' => 'reasonableness',
                        'section' => 'income',
                        'field' => 'grossProfit',
                        'message' => 'Unusually high gross margin (> 90%)',
                        'value' => round($grossMargin, 2) . '%',
                        'severity' => 'info',
                    ];
                }
            }
        }

        if ($this->hasValues($income, ['turnover', 'profitBeforeTax'])) {
            $turnover = $income['turnover'];
            $pbt = $income['profitBeforeTax'];

            if ($turnover > 0) {
                $netMargin = ($pbt / $turnover) * 100;

                if ($netMargin < -50) {
                    $warnings[] = [
                        'type' => 'reasonableness',
                        'section' => 'income',
                        'field' => 'profitBeforeTax',
                        'message' => 'Very high losses detected (< -50% margin)',
                        'value' => round($netMargin, 2) . '%',
                        'severity' => 'warning',
                    ];
                } elseif ($netMargin > 50) {
                    $warnings[] = [
                        'type' => 'reasonableness',
                        'section' => 'income',
                        'field' => 'profitBeforeTax',
                        'message' => 'Unusually high net margin (> 50%)',
                        'value' => round($netMargin, 2) . '%',
                        'severity' => 'info',
                    ];
                }
            }
        }

        $employment = $financial->employment_data ?? [];
        if ($this->hasValues($income, ['turnover']) && $this->hasValues($employment, ['totalPayroll'])) {
            $turnover = $income['turnover'];
            $payroll = $employment['totalPayroll'];

            if ($turnover > 0) {
                $payrollRatio = ($payroll / $turnover) * 100;

                if ($payrollRatio < 5) {
                    $warnings[] = [
                        'type' => 'reasonableness',
                        'section' => 'employment',
                        'field' => 'totalPayroll',
                        'message' => 'Very low payroll to revenue ratio (< 5%)',
                        'value' => round($payrollRatio, 2) . '%',
                        'severity' => 'info',
                    ];
                } elseif ($payrollRatio > 80) {
                    $warnings[] = [
                        'type' => 'reasonableness',
                        'section' => 'employment',
                        'field' => 'totalPayroll',
                        'message' => 'Very high payroll to revenue ratio (> 80%)',
                        'value' => round($payrollRatio, 2) . '%',
                        'severity' => 'warning',
                    ];
                }
            }
        }

        return $warnings;
    }

    private function validateIncomeData(ClientFinancial $financial): array
    {
        $warnings = [];
        $data = $financial->income_data ?? [];

        if (isset($data['costOfSales']) && $data['costOfSales'] < 0) {
            $warnings[] = [
                'section' => 'income',
                'field' => 'costOfSales',
                'message' => 'Cost of Sales should not be negative',
                'severity' => 'error',
            ];
        }

        if (isset($data['turnover']) && isset($data['costOfSales'])) {
            if ($data['costOfSales'] > $data['turnover']) {
                $warnings[] = [
                    'section' => 'income',
                    'field' => 'costOfSales',
                    'message' => 'Cost of Sales exceeds Revenue',
                    'severity' => 'warning',
                ];
            }
        }

        return $warnings;
    }

    private function validateBalanceSheet(ClientFinancial $financial): array
    {
        $warnings = [];
        $data = $financial->balance_sheet_data ?? [];

        if (isset($data['fixedAssets']) && isset($data['currentAssets']) && isset($data['totalAssets'])) {
            $sum = ($data['fixedAssets'] ?? 0) + ($data['currentAssets'] ?? 0);
            if (!$this->isWithinTolerance($sum, $data['totalAssets'])) {
                $warnings[] = [
                    'section' => 'balance_sheet',
                    'field' => 'totalAssets',
                    'message' => 'Total Assets should equal Fixed Assets plus Current Assets',
                    'severity' => 'warning',
                ];
            }
        }

        return $warnings;
    }

    private function validateEmploymentData(ClientFinancial $financial): array
    {
        $warnings = [];
        $data = $financial->employment_data ?? [];

        if (isset($data['employeeCount']) && $data['employeeCount'] < 0) {
            $warnings[] = [
                'section' => 'employment',
                'field' => 'employeeCount',
                'message' => 'Employee count cannot be negative',
                'severity' => 'error',
            ];
        }

        if (isset($data['employeeCount']) && isset($data['totalPayroll']) && $data['employeeCount'] > 0) {
            $avgSalary = $data['totalPayroll'] / $data['employeeCount'];
            $monthlyAvg = $avgSalary / 12;

            if ($monthlyAvg < 30000) {
                $warnings[] = [
                    'section' => 'employment',
                    'field' => 'totalPayroll',
                    'message' => 'Average monthly salary seems very low (below minimum wage)',
                    'severity' => 'warning',
                ];
            }
        }

        return $warnings;
    }

    private function validateVatData(ClientFinancial $financial): array
    {
        $warnings = [];
        $data = $financial->vat_data ?? [];

        if (isset($data['vatInputServices']) && $data['vatInputServices'] > 0) {
            $warnings[] = [
                'section' => 'vat',
                'field' => 'vatInputServices',
                'message' => 'Note: Input VAT on services is only recoverable under the new regime (NTA 2025)',
                'severity' => 'info',
            ];
        }

        return $warnings;
    }

    private function validateCapitalData(ClientFinancial $financial): array
    {
        $warnings = [];
        $data = $financial->capital_data ?? [];

        if (isset($data['assetDisposals']) && isset($data['assetDisposalCosts'])) {
            if ($data['assetDisposalCosts'] > $data['assetDisposals']) {
                $warnings[] = [
                    'section' => 'capital',
                    'field' => 'capitalGainsRealized',
                    'message' => 'Capital loss detected (disposal costs exceed proceeds)',
                    'severity' => 'info',
                ];
            }
        }

        return $warnings;
    }

    private function hasValues(array $data, array $keys): bool
    {
        foreach ($keys as $key) {
            if (!isset($data[$key]) || $data[$key] === null) {
                return false;
            }
        }
        return true;
    }

    private function isWithinTolerance(float $expected, float $actual, float $tolerance = null): bool
    {
        $tolerance = $tolerance ?? self::TOLERANCE;

        if ($expected == 0 && $actual == 0) {
            return true;
        }

        if ($expected == 0) {
            return abs($actual) < 1000;
        }

        return abs(($actual - $expected) / $expected) <= $tolerance;
    }
}
```

---

## Service 3: FinancialImportService

**File:** `app/Services/Financial/FinancialImportService.php`

```php
<?php

namespace App\Services\Financial;

use App\Enums\EntityType;
use App\Models\ClientFinancial;
use App\Models\FirmUser;
use App\Models\TaxClient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialImportService
{
    private const TEMPLATE_PATH = 'templates/financial';

    public function generateTemplate(EntityType $entityType, string $format = 'xlsx'): StreamedResponse
    {
        $headers = $this->getTemplateHeaders($entityType);
        $exampleData = $this->getExampleData($entityType);

        return Excel::download(
            new FinancialTemplateExport($headers, $exampleData),
            "financial_import_template_{$entityType->value}.{$format}"
        );
    }

    public function parseFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, ['xlsx', 'xls', 'csv'])) {
            throw new \Exception('Unsupported file format. Please use XLSX, XLS, or CSV.');
        }

        $data = Excel::toArray(new FinancialImport(), $file);

        if (empty($data) || empty($data[0])) {
            throw new \Exception('File is empty or could not be parsed.');
        }

        return [
            'headers' => array_keys($data[0][0] ?? []),
            'rows' => $data[0],
            'row_count' => count($data[0]),
        ];
    }

    public function validateMapping(array $headers, array $targetFields): array
    {
        $errors = [];
        $mapped = [];

        $requiredFields = ['fiscal_year', 'turnover'];

        foreach ($requiredFields as $field) {
            if (!in_array($field, $targetFields)) {
                $errors[] = "Required field '{$field}' is not mapped";
            }
        }

        foreach ($targetFields as $index => $field) {
            if ($field && isset($headers[$index])) {
                $mapped[$field] = $headers[$index];
            }
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'mapping' => $mapped,
        ];
    }

    public function import(
        TaxClient $client,
        array $rows,
        array $mapping,
        FirmUser $user
    ): array {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
            'imported' => [],
        ];

        foreach ($rows as $index => $row) {
            try {
                $data = $this->mapRowToData($row, $mapping);

                $existing = $client->getFinancialForYear($data['fiscal_year']);
                if ($existing) {
                    throw new \Exception("Financial data already exists for FY {$data['fiscal_year']}");
                }

                $financial = app(FinancialService::class)->create($client, $user, $data);

                $results['success']++;
                $results['imported'][] = [
                    'row' => $index + 1,
                    'fiscal_year' => $data['fiscal_year'],
                    'id' => $financial->id,
                ];
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'row' => $index + 1,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    public function previewImport(array $rows, array $mapping): array
    {
        $preview = [];

        foreach (array_slice($rows, 0, 5) as $index => $row) {
            $data = $this->mapRowToData($row, $mapping);
            $preview[] = [
                'row' => $index + 1,
                'fiscal_year' => $data['fiscal_year'] ?? 'N/A',
                'turnover' => $data['income_data']['turnover'] ?? 0,
                'profit_before_tax' => $data['income_data']['profitBeforeTax'] ?? 0,
            ];
        }

        return $preview;
    }

    private function getTemplateHeaders(EntityType $entityType): array
    {
        $headers = [
            'fiscal_year',
            'fiscal_year_start',
            'fiscal_year_end',
            'turnover',
            'cost_of_sales',
            'gross_profit',
            'operating_expenses',
            'administrative_expenses',
            'depreciation',
            'interest_income',
            'interest_expense',
            'profit_before_tax',
            'total_assets',
            'fixed_assets',
            'current_assets',
            'total_liabilities',
            'total_equity',
            'employee_count',
            'total_payroll',
            'paye_remitted',
            'vatable_sales',
            'vat_output',
            'vatable_purchases',
            'vat_input_goods',
            'vat_input_services',
        ];

        if ($entityType->isIndividual()) {
            $headers = array_merge($headers, [
                'gross_salary',
                'bonuses',
                'benefits_in_kind',
                'rental_income',
                'interest_income_personal',
                'dividend_income',
            ]);
        }

        if ($entityType->isPartnership()) {
            $headers = array_merge($headers, [
                'number_of_partners',
            ]);
        }

        return $headers;
    }

    private function getExampleData(EntityType $entityType): array
    {
        return [
            [
                'fiscal_year' => 2024,
                'fiscal_year_start' => '2024-01-01',
                'fiscal_year_end' => '2024-12-31',
                'turnover' => 150000000,
                'cost_of_sales' => 90000000,
                'gross_profit' => 60000000,
                'operating_expenses' => 25000000,
                'administrative_expenses' => 10000000,
                'depreciation' => 5000000,
                'interest_income' => 500000,
                'interest_expense' => 2000000,
                'profit_before_tax' => 18500000,
                'total_assets' => 200000000,
                'fixed_assets' => 120000000,
                'current_assets' => 80000000,
                'total_liabilities' => 80000000,
                'total_equity' => 120000000,
                'employee_count' => 45,
                'total_payroll' => 36000000,
                'paye_remitted' => 7200000,
                'vatable_sales' => 140000000,
                'vat_output' => 10500000,
                'vatable_purchases' => 70000000,
                'vat_input_goods' => 5250000,
                'vat_input_services' => 1875000,
            ],
        ];
    }

    private function mapRowToData(array $row, array $mapping): array
    {
        $reverseMapping = array_flip($mapping);

        $getValue = function (string $field) use ($row, $reverseMapping) {
            if (!isset($reverseMapping[$field])) {
                return null;
            }
            $header = $reverseMapping[$field];
            return $row[$header] ?? null;
        };

        return [
            'fiscal_year' => (int) $getValue('fiscal_year'),
            'fiscal_year_start' => $getValue('fiscal_year_start') ?: "{$getValue('fiscal_year')}-01-01",
            'fiscal_year_end' => $getValue('fiscal_year_end') ?: "{$getValue('fiscal_year')}-12-31",
            'income_data' => [
                'turnover' => $this->parseNumber($getValue('turnover')),
                'costOfSales' => $this->parseNumber($getValue('cost_of_sales')),
                'grossProfit' => $this->parseNumber($getValue('gross_profit')),
                'operatingExpenses' => $this->parseNumber($getValue('operating_expenses')),
                'administrativeExpenses' => $this->parseNumber($getValue('administrative_expenses')),
                'depreciation' => $this->parseNumber($getValue('depreciation')),
                'interestIncome' => $this->parseNumber($getValue('interest_income')),
                'interestExpense' => $this->parseNumber($getValue('interest_expense')),
                'profitBeforeTax' => $this->parseNumber($getValue('profit_before_tax')),
            ],
            'balance_sheet_data' => [
                'totalAssets' => $this->parseNumber($getValue('total_assets')),
                'fixedAssets' => $this->parseNumber($getValue('fixed_assets')),
                'currentAssets' => $this->parseNumber($getValue('current_assets')),
                'totalLiabilities' => $this->parseNumber($getValue('total_liabilities')),
                'totalEquity' => $this->parseNumber($getValue('total_equity')),
            ],
            'employment_data' => [
                'employeeCount' => (int) $getValue('employee_count'),
                'totalPayroll' => $this->parseNumber($getValue('total_payroll')),
                'payeRemitted' => $this->parseNumber($getValue('paye_remitted')),
            ],
            'vat_data' => [
                'vatableSales' => $this->parseNumber($getValue('vatable_sales')),
                'vatOutput' => $this->parseNumber($getValue('vat_output')),
                'vatablePurchases' => $this->parseNumber($getValue('vatable_purchases')),
                'vatInputGoods' => $this->parseNumber($getValue('vat_input_goods')),
                'vatInputServices' => $this->parseNumber($getValue('vat_input_services')),
            ],
        ];
    }

    private function parseNumber($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $cleaned = preg_replace('/[^0-9.-]/', '', (string) $value);
        return (int) round((float) $cleaned);
    }
}
```

---

## Service Provider Registration

Add to `app/Providers/AppServiceProvider.php`:

```php
use App\Services\Financial\FinancialService;
use App\Services\Financial\FinancialValidationService;
use App\Services\Financial\FinancialImportService;

public function register(): void
{
    $this->app->singleton(FinancialValidationService::class);
    $this->app->singleton(FinancialImportService::class);
    $this->app->singleton(FinancialService::class);
}
```

---

## Next Steps

Once services are implemented, proceed to:
→ **03_CONTROLLERS.md** - Implement controllers and form requests
