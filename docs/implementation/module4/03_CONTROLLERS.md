# TaxLab Implementation - Module 4 Controllers

## Overview

This document defines the HTTP controllers and form requests for the Financial Data Entry module.

---

## Controller 1: ClientFinancialController

**File:** `app/Http/Controllers/App/ClientFinancialController.php`

```php
<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\Financial\StoreFinancialRequest;
use App\Http\Requests\Financial\UpdateFinancialRequest;
use App\Models\ClientFinancial;
use App\Models\TaxClient;
use App\Services\Financial\FinancialService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientFinancialController extends Controller
{
    public function __construct(
        private FinancialService $financialService
    ) {}

    public function index(Request $request, TaxClient $client): Response
    {
        $this->authorize('view', $client);

        $financials = $this->financialService->getPaginated($client);

        return Inertia::render('App/Clients/Financials/Index', [
            'client' => $client->only(['id', 'name', 'trading_name', 'entity_type']),
            'financials' => $financials,
        ]);
    }

    public function show(Request $request, TaxClient $client, int $year): Response
    {
        $this->authorize('view', $client);

        $financial = $this->financialService->getForClient($client, $year);

        if (!$financial) {
            abort(404, "No financial data found for fiscal year {$year}");
        }

        $readiness = $this->financialService->getReadinessStatus($financial);

        return Inertia::render('App/Clients/Financials/Show', [
            'client' => $client->only(['id', 'name', 'trading_name', 'entity_type']),
            'financial' => $this->transformFinancial($financial),
            'readiness' => $readiness,
            'availableYears' => $client->financials()->pluck('fiscal_year')->toArray(),
        ]);
    }

    public function create(Request $request, TaxClient $client): Response
    {
        $this->authorize('update', $client);

        $existingYears = $client->financials()->pluck('fiscal_year')->toArray();

        return Inertia::render('App/Clients/Financials/Create', [
            'client' => $client->only(['id', 'name', 'trading_name', 'entity_type']),
            'existingYears' => $existingYears,
            'dataSources' => \App\Enums\DataSource::options(),
        ]);
    }

    public function store(StoreFinancialRequest $request, TaxClient $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $financial = $this->financialService->create(
            $client,
            $request->user('firm'),
            $request->validated()
        );

        return redirect()
            ->route('app.clients.financials.show', [$client, $financial->fiscal_year])
            ->with('success', "Financial data created for FY {$financial->fiscal_year}");
    }

    public function update(UpdateFinancialRequest $request, TaxClient $client, int $year): RedirectResponse
    {
        $this->authorize('update', $client);

        $financial = $this->financialService->getForClient($client, $year);

        if (!$financial) {
            abort(404);
        }

        if ($financial->isLocked()) {
            return back()->with('error', 'Cannot update locked financial data');
        }

        $result = $this->financialService->update(
            $financial,
            $request->user('firm'),
            $request->validated()
        );

        $message = 'Financial data updated successfully';
        if (!empty($result['warnings'])) {
            $message .= '. ' . count($result['warnings']) . ' warning(s) detected.';
        }

        return back()->with('success', $message);
    }

    public function destroy(Request $request, TaxClient $client, int $year): RedirectResponse
    {
        $this->authorize('update', $client);

        $financial = $this->financialService->getForClient($client, $year);

        if (!$financial) {
            abort(404);
        }

        if ($financial->isLocked()) {
            return back()->with('error', 'Cannot delete locked financial data');
        }

        $this->financialService->delete($financial, $request->user('firm'));

        return redirect()
            ->route('app.clients.financials.index', $client)
            ->with('success', "Financial data for FY {$year} has been deleted");
    }

    public function lock(Request $request, TaxClient $client, int $year): RedirectResponse
    {
        $this->authorize('update', $client);

        $financial = $this->financialService->getForClient($client, $year);

        if (!$financial) {
            abort(404);
        }

        try {
            $this->financialService->lock($financial, $request->user('firm'));
            return back()->with('success', 'Financial data has been locked');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function unlock(Request $request, TaxClient $client, int $year): RedirectResponse
    {
        $this->authorize('update', $client);

        $financial = $this->financialService->getForClient($client, $year);

        if (!$financial) {
            abort(404);
        }

        try {
            $this->financialService->unlock($financial, $request->user('firm'));
            return back()->with('success', 'Financial data has been unlocked');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function versions(Request $request, TaxClient $client, int $year): Response
    {
        $this->authorize('view', $client);

        $financial = $this->financialService->getForClient($client, $year);

        if (!$financial) {
            abort(404);
        }

        $versions = $this->financialService->getVersions($financial);

        return Inertia::render('App/Clients/Financials/Versions', [
            'client' => $client->only(['id', 'name']),
            'fiscalYear' => $year,
            'currentVersion' => $financial->version,
            'versions' => $versions->map(fn ($v) => [
                'version' => $v->version,
                'changeSummary' => $v->change_summary,
                'createdBy' => $v->creator?->name,
                'createdAt' => $v->created_at->toISOString(),
            ]),
        ]);
    }

    public function restore(Request $request, TaxClient $client, int $year, int $version): RedirectResponse
    {
        $this->authorize('update', $client);

        $financial = $this->financialService->getForClient($client, $year);

        if (!$financial) {
            abort(404);
        }

        if ($financial->isLocked()) {
            return back()->with('error', 'Cannot restore locked financial data');
        }

        try {
            $this->financialService->restoreVersion($financial, $version, $request->user('firm'));
            return back()->with('success', "Restored to version {$version}");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function copyFromPriorYear(Request $request, TaxClient $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $request->validate([
            'from_year' => 'required|integer',
            'to_year' => 'required|integer|different:from_year',
            'adjustment_percent' => 'nullable|numeric|min:-100|max:100',
        ]);

        try {
            $financial = $this->financialService->copyFromPriorYear(
                $client,
                $request->from_year,
                $request->to_year,
                $request->user('firm'),
                $request->adjustment_percent
            );

            return redirect()
                ->route('app.clients.financials.show', [$client, $financial->fiscal_year])
                ->with('success', "Financial data copied from FY {$request->from_year} to FY {$request->to_year}");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function transformFinancial(ClientFinancial $financial): array
    {
        return [
            'id' => $financial->id,
            'fiscalYear' => $financial->fiscal_year,
            'fiscalYearStart' => $financial->fiscal_year_start->format('Y-m-d'),
            'fiscalYearEnd' => $financial->fiscal_year_end->format('Y-m-d'),
            'dataSource' => $financial->data_source?->value,
            'dataSourceLabel' => $financial->data_source?->label(),
            'incomeData' => $financial->income_data ?? [],
            'balanceSheetData' => $financial->balance_sheet_data ?? [],
            'employmentData' => $financial->employment_data ?? [],
            'vatData' => $financial->vat_data ?? [],
            'capitalData' => $financial->capital_data ?? [],
            'individualData' => $financial->individual_data ?? [],
            'partnershipData' => $financial->partnership_data ?? [],
            'isComplete' => $financial->is_complete,
            'isLocked' => $financial->is_locked,
            'lockedAt' => $financial->locked_at?->toISOString(),
            'lockedBy' => $financial->lockedByUser?->name,
            'version' => $financial->version,
            'verifiedAt' => $financial->verified_at?->toISOString(),
            'verifiedBy' => $financial->verifiedByUser?->name,
            'completionPercentage' => $financial->getCompletionPercentage(),
            'notes' => $financial->notes,
            'createdAt' => $financial->created_at->toISOString(),
            'updatedAt' => $financial->updated_at->toISOString(),
        ];
    }
}
```

---

## Controller 2: FinancialImportController

**File:** `app/Http/Controllers/App/FinancialImportController.php`

```php
<?php

namespace App\Http\Controllers\App;

use App\Enums\EntityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Financial\ImportFinancialRequest;
use App\Models\TaxClient;
use App\Services\Financial\FinancialImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialImportController extends Controller
{
    public function __construct(
        private FinancialImportService $importService
    ) {}

    public function template(Request $request, string $entityType): StreamedResponse
    {
        $type = EntityType::tryFrom($entityType);

        if (!$type) {
            abort(400, 'Invalid entity type');
        }

        $format = $request->query('format', 'xlsx');

        return $this->importService->generateTemplate($type, $format);
    }

    public function showImport(Request $request, TaxClient $client): Response
    {
        $this->authorize('update', $client);

        return Inertia::render('App/Clients/Financials/Import', [
            'client' => $client->only(['id', 'name', 'entity_type']),
            'templateUrl' => route('app.financials.template', $client->entity_type->value),
        ]);
    }

    public function upload(Request $request, TaxClient $client): Response
    {
        $this->authorize('update', $client);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $parseResult = $this->importService->parseFile($request->file('file'));

            $request->session()->put('import_data', $parseResult);

            return Inertia::render('App/Clients/Financials/ImportMapping', [
                'client' => $client->only(['id', 'name']),
                'headers' => $parseResult['headers'],
                'rowCount' => $parseResult['row_count'],
                'sampleData' => array_slice($parseResult['rows'], 0, 3),
                'targetFields' => $this->getTargetFields($client->entity_type),
            ]);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function preview(Request $request, TaxClient $client): Response
    {
        $this->authorize('update', $client);

        $request->validate([
            'mapping' => 'required|array',
        ]);

        $importData = $request->session()->get('import_data');

        if (!$importData) {
            return redirect()
                ->route('app.clients.financials.import', $client)
                ->with('error', 'Import session expired. Please upload the file again.');
        }

        $mappingResult = $this->importService->validateMapping(
            $importData['headers'],
            $request->mapping
        );

        if (!$mappingResult['is_valid']) {
            return back()->with('error', implode(', ', $mappingResult['errors']));
        }

        $preview = $this->importService->previewImport(
            $importData['rows'],
            $mappingResult['mapping']
        );

        $request->session()->put('import_mapping', $mappingResult['mapping']);

        return Inertia::render('App/Clients/Financials/ImportPreview', [
            'client' => $client->only(['id', 'name']),
            'preview' => $preview,
            'totalRows' => $importData['row_count'],
            'mapping' => $mappingResult['mapping'],
        ]);
    }

    public function process(Request $request, TaxClient $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $importData = $request->session()->get('import_data');
        $mapping = $request->session()->get('import_mapping');

        if (!$importData || !$mapping) {
            return redirect()
                ->route('app.clients.financials.import', $client)
                ->with('error', 'Import session expired. Please start over.');
        }

        $result = $this->importService->import(
            $client,
            $importData['rows'],
            $mapping,
            $request->user('firm')
        );

        $request->session()->forget(['import_data', 'import_mapping']);

        if ($result['failed'] > 0 && $result['success'] === 0) {
            return redirect()
                ->route('app.clients.financials.import', $client)
                ->with('error', 'Import failed. ' . $result['errors'][0]['message'] ?? 'Unknown error');
        }

        $message = "{$result['success']} fiscal year(s) imported successfully.";
        if ($result['failed'] > 0) {
            $message .= " {$result['failed']} row(s) failed.";
        }

        return redirect()
            ->route('app.clients.financials.index', $client)
            ->with('success', $message);
    }

    private function getTargetFields(EntityType $entityType): array
    {
        $fields = [
            ['key' => 'fiscal_year', 'label' => 'Fiscal Year', 'required' => true],
            ['key' => 'fiscal_year_start', 'label' => 'Fiscal Year Start', 'required' => false],
            ['key' => 'fiscal_year_end', 'label' => 'Fiscal Year End', 'required' => false],
            ['key' => 'turnover', 'label' => 'Turnover/Revenue', 'required' => true],
            ['key' => 'cost_of_sales', 'label' => 'Cost of Sales', 'required' => false],
            ['key' => 'gross_profit', 'label' => 'Gross Profit', 'required' => false],
            ['key' => 'operating_expenses', 'label' => 'Operating Expenses', 'required' => false],
            ['key' => 'administrative_expenses', 'label' => 'Administrative Expenses', 'required' => false],
            ['key' => 'depreciation', 'label' => 'Depreciation', 'required' => false],
            ['key' => 'interest_income', 'label' => 'Interest Income', 'required' => false],
            ['key' => 'interest_expense', 'label' => 'Interest Expense', 'required' => false],
            ['key' => 'profit_before_tax', 'label' => 'Profit Before Tax', 'required' => false],
            ['key' => 'total_assets', 'label' => 'Total Assets', 'required' => false],
            ['key' => 'fixed_assets', 'label' => 'Fixed Assets', 'required' => false],
            ['key' => 'current_assets', 'label' => 'Current Assets', 'required' => false],
            ['key' => 'total_liabilities', 'label' => 'Total Liabilities', 'required' => false],
            ['key' => 'total_equity', 'label' => 'Total Equity', 'required' => false],
            ['key' => 'employee_count', 'label' => 'Employee Count', 'required' => false],
            ['key' => 'total_payroll', 'label' => 'Total Payroll', 'required' => false],
            ['key' => 'paye_remitted', 'label' => 'PAYE Remitted', 'required' => false],
            ['key' => 'vatable_sales', 'label' => 'VATable Sales', 'required' => false],
            ['key' => 'vat_output', 'label' => 'VAT Output', 'required' => false],
            ['key' => 'vatable_purchases', 'label' => 'VATable Purchases', 'required' => false],
            ['key' => 'vat_input_goods', 'label' => 'VAT Input (Goods)', 'required' => false],
            ['key' => 'vat_input_services', 'label' => 'VAT Input (Services)', 'required' => false],
        ];

        return $fields;
    }
}
```

---

## Form Requests

### StoreFinancialRequest

**File:** `app/Http/Requests/Financial/StoreFinancialRequest.php`

```php
<?php

namespace App\Http\Requests\Financial;

use App\Enums\DataSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFinancialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $client = $this->route('client');

        return [
            'fiscal_year' => [
                'required',
                'integer',
                'min:2000',
                'max:2050',
                Rule::unique('client_financials')
                    ->where('tax_client_id', $client->id),
            ],
            'fiscal_year_start' => 'required|date',
            'fiscal_year_end' => 'required|date|after:fiscal_year_start',
            'data_source' => ['required', Rule::enum(DataSource::class)],

            'income_data' => 'nullable|array',
            'income_data.turnover' => 'nullable|numeric|min:0',
            'income_data.costOfSales' => 'nullable|numeric|min:0',
            'income_data.grossProfit' => 'nullable|numeric',
            'income_data.operatingExpenses' => 'nullable|numeric|min:0',
            'income_data.administrativeExpenses' => 'nullable|numeric|min:0',
            'income_data.depreciation' => 'nullable|numeric|min:0',
            'income_data.interestIncome' => 'nullable|numeric|min:0',
            'income_data.interestExpense' => 'nullable|numeric|min:0',
            'income_data.dividendIncome' => 'nullable|numeric|min:0',
            'income_data.otherIncome' => 'nullable|numeric',
            'income_data.profitBeforeTax' => 'nullable|numeric',
            'income_data.taxExpense' => 'nullable|numeric|min:0',
            'income_data.profitAfterTax' => 'nullable|numeric',

            'balance_sheet_data' => 'nullable|array',
            'balance_sheet_data.totalAssets' => 'nullable|numeric|min:0',
            'balance_sheet_data.fixedAssets' => 'nullable|numeric|min:0',
            'balance_sheet_data.currentAssets' => 'nullable|numeric|min:0',
            'balance_sheet_data.totalLiabilities' => 'nullable|numeric|min:0',
            'balance_sheet_data.longTermLiabilities' => 'nullable|numeric|min:0',
            'balance_sheet_data.currentLiabilities' => 'nullable|numeric|min:0',
            'balance_sheet_data.shareCapital' => 'nullable|numeric|min:0',
            'balance_sheet_data.retainedEarnings' => 'nullable|numeric',
            'balance_sheet_data.totalEquity' => 'nullable|numeric',

            'employment_data' => 'nullable|array',
            'employment_data.employeeCount' => 'nullable|integer|min:0',
            'employment_data.totalPayroll' => 'nullable|numeric|min:0',
            'employment_data.payeRemitted' => 'nullable|numeric|min:0',
            'employment_data.pensionEmployer' => 'nullable|numeric|min:0',
            'employment_data.pensionEmployee' => 'nullable|numeric|min:0',
            'employment_data.nhfContributions' => 'nullable|numeric|min:0',
            'employment_data.nsitfContributions' => 'nullable|numeric|min:0',
            'employment_data.itfContributions' => 'nullable|numeric|min:0',

            'vat_data' => 'nullable|array',
            'vat_data.vatableSales' => 'nullable|numeric|min:0',
            'vat_data.vatOutput' => 'nullable|numeric|min:0',
            'vat_data.exemptSales' => 'nullable|numeric|min:0',
            'vat_data.zeroRatedSales' => 'nullable|numeric|min:0',
            'vat_data.vatablePurchases' => 'nullable|numeric|min:0',
            'vat_data.vatInputGoods' => 'nullable|numeric|min:0',
            'vat_data.vatInputServices' => 'nullable|numeric|min:0',
            'vat_data.vatInputCapital' => 'nullable|numeric|min:0',
            'vat_data.vatRemitted' => 'nullable|numeric',

            'capital_data' => 'nullable|array',
            'capital_data.assetDisposals' => 'nullable|numeric|min:0',
            'capital_data.assetDisposalCosts' => 'nullable|numeric|min:0',
            'capital_data.capitalGainsRealized' => 'nullable|numeric',
            'capital_data.capitalAllowancesClaimed' => 'nullable|numeric|min:0',

            'individual_data' => 'nullable|array',
            'partnership_data' => 'nullable|array',

            'notes' => 'nullable|string|max:5000',
        ];
    }

    public function messages(): array
    {
        return [
            'fiscal_year.unique' => 'Financial data already exists for this fiscal year.',
            'fiscal_year_end.after' => 'The fiscal year end must be after the start date.',
        ];
    }
}
```

### UpdateFinancialRequest

**File:** `app/Http/Requests/Financial/UpdateFinancialRequest.php`

```php
<?php

namespace App\Http\Requests\Financial;

use App\Enums\DataSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFinancialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'data_source' => ['sometimes', Rule::enum(DataSource::class)],

            'income_data' => 'sometimes|array',
            'income_data.turnover' => 'nullable|numeric|min:0',
            'income_data.costOfSales' => 'nullable|numeric|min:0',
            'income_data.grossProfit' => 'nullable|numeric',
            'income_data.operatingExpenses' => 'nullable|numeric|min:0',
            'income_data.administrativeExpenses' => 'nullable|numeric|min:0',
            'income_data.depreciation' => 'nullable|numeric|min:0',
            'income_data.interestIncome' => 'nullable|numeric|min:0',
            'income_data.interestExpense' => 'nullable|numeric|min:0',
            'income_data.dividendIncome' => 'nullable|numeric|min:0',
            'income_data.otherIncome' => 'nullable|numeric',
            'income_data.profitBeforeTax' => 'nullable|numeric',
            'income_data.taxExpense' => 'nullable|numeric|min:0',
            'income_data.profitAfterTax' => 'nullable|numeric',

            'balance_sheet_data' => 'sometimes|array',
            'balance_sheet_data.totalAssets' => 'nullable|numeric|min:0',
            'balance_sheet_data.fixedAssets' => 'nullable|numeric|min:0',
            'balance_sheet_data.currentAssets' => 'nullable|numeric|min:0',
            'balance_sheet_data.totalLiabilities' => 'nullable|numeric|min:0',
            'balance_sheet_data.longTermLiabilities' => 'nullable|numeric|min:0',
            'balance_sheet_data.currentLiabilities' => 'nullable|numeric|min:0',
            'balance_sheet_data.shareCapital' => 'nullable|numeric|min:0',
            'balance_sheet_data.retainedEarnings' => 'nullable|numeric',
            'balance_sheet_data.totalEquity' => 'nullable|numeric',

            'employment_data' => 'sometimes|array',
            'employment_data.employeeCount' => 'nullable|integer|min:0',
            'employment_data.totalPayroll' => 'nullable|numeric|min:0',
            'employment_data.payeRemitted' => 'nullable|numeric|min:0',
            'employment_data.pensionEmployer' => 'nullable|numeric|min:0',
            'employment_data.pensionEmployee' => 'nullable|numeric|min:0',
            'employment_data.nhfContributions' => 'nullable|numeric|min:0',
            'employment_data.nsitfContributions' => 'nullable|numeric|min:0',
            'employment_data.itfContributions' => 'nullable|numeric|min:0',

            'vat_data' => 'sometimes|array',
            'vat_data.vatableSales' => 'nullable|numeric|min:0',
            'vat_data.vatOutput' => 'nullable|numeric|min:0',
            'vat_data.exemptSales' => 'nullable|numeric|min:0',
            'vat_data.zeroRatedSales' => 'nullable|numeric|min:0',
            'vat_data.vatablePurchases' => 'nullable|numeric|min:0',
            'vat_data.vatInputGoods' => 'nullable|numeric|min:0',
            'vat_data.vatInputServices' => 'nullable|numeric|min:0',
            'vat_data.vatInputCapital' => 'nullable|numeric|min:0',
            'vat_data.vatRemitted' => 'nullable|numeric',

            'capital_data' => 'sometimes|array',
            'capital_data.assetDisposals' => 'nullable|numeric|min:0',
            'capital_data.assetDisposalCosts' => 'nullable|numeric|min:0',
            'capital_data.capitalGainsRealized' => 'nullable|numeric',
            'capital_data.capitalAllowancesClaimed' => 'nullable|numeric|min:0',

            'individual_data' => 'sometimes|array',
            'partnership_data' => 'sometimes|array',

            'notes' => 'nullable|string|max:5000',
        ];
    }
}
```

---

## Routes

**File:** `routes/app.php` (add to existing)

```php
use App\Http\Controllers\App\ClientFinancialController;
use App\Http\Controllers\App\FinancialImportController;

Route::middleware(['auth:firm', 'verified'])->prefix('app')->group(function () {

    Route::get('financials/template/{entityType}', [FinancialImportController::class, 'template'])
        ->name('app.financials.template');

    Route::prefix('clients/{client}/financials')->group(function () {
        Route::get('/', [ClientFinancialController::class, 'index'])
            ->name('app.clients.financials.index');

        Route::get('/create', [ClientFinancialController::class, 'create'])
            ->name('app.clients.financials.create');

        Route::post('/', [ClientFinancialController::class, 'store'])
            ->name('app.clients.financials.store');

        Route::post('/copy', [ClientFinancialController::class, 'copyFromPriorYear'])
            ->name('app.clients.financials.copy');

        Route::get('/import', [FinancialImportController::class, 'showImport'])
            ->name('app.clients.financials.import');

        Route::post('/import/upload', [FinancialImportController::class, 'upload'])
            ->name('app.clients.financials.import.upload');

        Route::post('/import/preview', [FinancialImportController::class, 'preview'])
            ->name('app.clients.financials.import.preview');

        Route::post('/import/process', [FinancialImportController::class, 'process'])
            ->name('app.clients.financials.import.process');

        Route::get('/{year}', [ClientFinancialController::class, 'show'])
            ->name('app.clients.financials.show')
            ->whereNumber('year');

        Route::put('/{year}', [ClientFinancialController::class, 'update'])
            ->name('app.clients.financials.update')
            ->whereNumber('year');

        Route::delete('/{year}', [ClientFinancialController::class, 'destroy'])
            ->name('app.clients.financials.destroy')
            ->whereNumber('year');

        Route::post('/{year}/lock', [ClientFinancialController::class, 'lock'])
            ->name('app.clients.financials.lock')
            ->whereNumber('year');

        Route::post('/{year}/unlock', [ClientFinancialController::class, 'unlock'])
            ->name('app.clients.financials.unlock')
            ->whereNumber('year');

        Route::get('/{year}/versions', [ClientFinancialController::class, 'versions'])
            ->name('app.clients.financials.versions')
            ->whereNumber('year');

        Route::post('/{year}/restore/{version}', [ClientFinancialController::class, 'restore'])
            ->name('app.clients.financials.restore')
            ->whereNumber('year')
            ->whereNumber('version');
    });
});
```

---

## Next Steps

Once controllers are implemented, proceed to:
→ **04_FRONTEND_TYPES.md** - TypeScript type definitions
