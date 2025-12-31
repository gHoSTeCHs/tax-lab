# TaxLab Implementation - Module 4 Testing Strategy

## Overview

This document defines the testing approach for the Financial Data Entry module, including unit tests, feature tests, and component tests.

---

## Test Structure

```
tests/
├── Feature/
│   └── Financial/
│       ├── ClientFinancialControllerTest.php
│       ├── FinancialImportControllerTest.php
│       └── FinancialVersionTest.php
├── Unit/
│   └── Services/
│       └── Financial/
│           ├── FinancialServiceTest.php
│           ├── FinancialValidationServiceTest.php
│           └── FinancialImportServiceTest.php
```

---

## Feature Tests

### ClientFinancialControllerTest

**File:** `tests/Feature/Financial/ClientFinancialControllerTest.php`

```php
<?php

namespace Tests\Feature\Financial;

use App\Enums\DataSource;
use App\Enums\FirmRole;
use App\Models\ClientFinancial;
use App\Models\Firm;
use App\Models\FirmUser;
use App\Models\TaxClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientFinancialControllerTest extends TestCase
{
    use RefreshDatabase;

    private Firm $firm;
    private FirmUser $user;
    private TaxClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->firm = Firm::factory()->create();
        $this->user = FirmUser::factory()->for($this->firm)->create([
            'role' => FirmRole::PARTNER,
        ]);
        $this->client = TaxClient::factory()->for($this->firm)->create([
            'created_by' => $this->user->id,
        ]);
    }

    public function test_can_view_financials_index(): void
    {
        $financial = ClientFinancial::factory()
            ->for($this->client, 'taxClient')
            ->for($this->firm)
            ->create();

        $response = $this->actingAs($this->user, 'firm')
            ->get(route('app.clients.financials.index', $this->client));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('App/Clients/Financials/Index')
            ->has('financials.data', 1)
        );
    }

    public function test_can_create_financial_year(): void
    {
        $data = [
            'fiscal_year' => 2024,
            'fiscal_year_start' => '2024-01-01',
            'fiscal_year_end' => '2024-12-31',
            'data_source' => 'management',
            'income_data' => [
                'turnover' => 150000000,
                'profitBeforeTax' => 20000000,
            ],
        ];

        $response = $this->actingAs($this->user, 'firm')
            ->post(route('app.clients.financials.store', $this->client), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('client_financials', [
            'tax_client_id' => $this->client->id,
            'fiscal_year' => 2024,
        ]);
    }

    public function test_cannot_create_duplicate_fiscal_year(): void
    {
        ClientFinancial::factory()
            ->for($this->client, 'taxClient')
            ->for($this->firm)
            ->forYear(2024)
            ->create();

        $data = [
            'fiscal_year' => 2024,
            'fiscal_year_start' => '2024-01-01',
            'fiscal_year_end' => '2024-12-31',
            'data_source' => 'management',
        ];

        $response = $this->actingAs($this->user, 'firm')
            ->post(route('app.clients.financials.store', $this->client), $data);

        $response->assertSessionHasErrors('fiscal_year');
    }

    public function test_can_update_financial_data(): void
    {
        $financial = ClientFinancial::factory()
            ->for($this->client, 'taxClient')
            ->for($this->firm)
            ->forYear(2024)
            ->create([
                'created_by' => $this->user->id,
            ]);

        $data = [
            'income_data' => [
                'turnover' => 200000000,
                'profitBeforeTax' => 30000000,
            ],
        ];

        $response = $this->actingAs($this->user, 'firm')
            ->put(route('app.clients.financials.update', [$this->client, 2024]), $data);

        $response->assertRedirect();
        $financial->refresh();
        $this->assertEquals(200000000, $financial->income_data['turnover']);
    }

    public function test_cannot_update_locked_financial(): void
    {
        $financial = ClientFinancial::factory()
            ->for($this->client, 'taxClient')
            ->for($this->firm)
            ->forYear(2024)
            ->locked()
            ->create([
                'created_by' => $this->user->id,
                'locked_by' => $this->user->id,
            ]);

        $data = [
            'income_data' => [
                'turnover' => 200000000,
            ],
        ];

        $response = $this->actingAs($this->user, 'firm')
            ->put(route('app.clients.financials.update', [$this->client, 2024]), $data);

        $response->assertSessionHas('error');
    }

    public function test_can_lock_financial(): void
    {
        $financial = ClientFinancial::factory()
            ->for($this->client, 'taxClient')
            ->for($this->firm)
            ->forYear(2024)
            ->create([
                'created_by' => $this->user->id,
            ]);

        $response = $this->actingAs($this->user, 'firm')
            ->post(route('app.clients.financials.lock', [$this->client, 2024]));

        $response->assertRedirect();
        $financial->refresh();
        $this->assertTrue($financial->is_locked);
        $this->assertEquals($this->user->id, $financial->locked_by);
    }

    public function test_can_unlock_financial(): void
    {
        $financial = ClientFinancial::factory()
            ->for($this->client, 'taxClient')
            ->for($this->firm)
            ->forYear(2024)
            ->locked()
            ->create([
                'created_by' => $this->user->id,
                'locked_by' => $this->user->id,
            ]);

        $response = $this->actingAs($this->user, 'firm')
            ->post(route('app.clients.financials.unlock', [$this->client, 2024]));

        $response->assertRedirect();
        $financial->refresh();
        $this->assertFalse($financial->is_locked);
    }

    public function test_creates_version_on_update(): void
    {
        $financial = ClientFinancial::factory()
            ->for($this->client, 'taxClient')
            ->for($this->firm)
            ->forYear(2024)
            ->create([
                'created_by' => $this->user->id,
            ]);

        $data = [
            'income_data' => [
                'turnover' => 200000000,
            ],
        ];

        $this->actingAs($this->user, 'firm')
            ->put(route('app.clients.financials.update', [$this->client, 2024]), $data);

        $this->assertDatabaseHas('client_financial_versions', [
            'client_financial_id' => $financial->id,
            'version' => 1,
        ]);

        $financial->refresh();
        $this->assertEquals(2, $financial->version);
    }

    public function test_can_restore_version(): void
    {
        $financial = ClientFinancial::factory()
            ->for($this->client, 'taxClient')
            ->for($this->firm)
            ->forYear(2024)
            ->create([
                'created_by' => $this->user->id,
                'income_data' => ['turnover' => 100000000],
            ]);

        $financial->createVersion($this->user, 'Initial');

        $financial->update([
            'income_data' => ['turnover' => 200000000],
            'version' => 2,
        ]);

        $response = $this->actingAs($this->user, 'firm')
            ->post(route('app.clients.financials.restore', [$this->client, 2024, 1]));

        $response->assertRedirect();
        $financial->refresh();
        $this->assertEquals(100000000, $financial->income_data['turnover']);
    }
}
```

---

## Unit Tests

### FinancialValidationServiceTest

**File:** `tests/Unit/Services/Financial/FinancialValidationServiceTest.php`

```php
<?php

namespace Tests\Unit\Services\Financial;

use App\Models\ClientFinancial;
use App\Models\Firm;
use App\Models\FirmUser;
use App\Models\TaxClient;
use App\Services\Financial\FinancialValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    private FinancialValidationService $service;
    private ClientFinancial $financial;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FinancialValidationService::class);

        $firm = Firm::factory()->create();
        $user = FirmUser::factory()->for($firm)->create();
        $client = TaxClient::factory()->for($firm)->create([
            'created_by' => $user->id,
        ]);
        $this->financial = ClientFinancial::factory()
            ->for($client, 'taxClient')
            ->for($firm)
            ->create([
                'created_by' => $user->id,
            ]);
    }

    public function test_validates_gross_profit_calculation(): void
    {
        $this->financial->update([
            'income_data' => [
                'turnover' => 100000000,
                'costOfSales' => 60000000,
                'grossProfit' => 40000000,
            ],
        ]);

        $errors = $this->service->checkCrossFieldConsistency($this->financial);

        $this->assertEmpty($errors);
    }

    public function test_detects_gross_profit_mismatch(): void
    {
        $this->financial->update([
            'income_data' => [
                'turnover' => 100000000,
                'costOfSales' => 60000000,
                'grossProfit' => 50000000,
            ],
        ]);

        $errors = $this->service->checkCrossFieldConsistency($this->financial);

        $this->assertNotEmpty($errors);
        $this->assertEquals('grossProfit', $errors[0]['field']);
    }

    public function test_validates_balance_sheet_equation(): void
    {
        $this->financial->update([
            'balance_sheet_data' => [
                'totalAssets' => 200000000,
                'totalLiabilities' => 80000000,
                'totalEquity' => 120000000,
            ],
        ]);

        $errors = $this->service->checkCrossFieldConsistency($this->financial);

        $this->assertEmpty(array_filter($errors, fn($e) => $e['section'] === 'balance_sheet'));
    }

    public function test_detects_balance_sheet_imbalance(): void
    {
        $this->financial->update([
            'balance_sheet_data' => [
                'totalAssets' => 200000000,
                'totalLiabilities' => 80000000,
                'totalEquity' => 100000000,
            ],
        ]);

        $errors = $this->service->checkCrossFieldConsistency($this->financial);

        $balanceSheetErrors = array_filter($errors, fn($e) => $e['section'] === 'balance_sheet');
        $this->assertNotEmpty($balanceSheetErrors);
    }

    public function test_detects_high_gross_margin_warning(): void
    {
        $this->financial->update([
            'income_data' => [
                'turnover' => 100000000,
                'grossProfit' => 95000000,
            ],
        ]);

        $warnings = $this->service->getReasonablenessWarnings($this->financial);

        $marginWarnings = array_filter(
            $warnings,
            fn($w) => $w['field'] === 'grossProfit' && str_contains($w['message'], 'high')
        );
        $this->assertNotEmpty($marginWarnings);
    }

    public function test_detects_paye_exceeding_payroll(): void
    {
        $this->financial->update([
            'employment_data' => [
                'totalPayroll' => 50000000,
                'payeRemitted' => 60000000,
            ],
        ]);

        $errors = $this->service->checkCrossFieldConsistency($this->financial);

        $payrollErrors = array_filter($errors, fn($e) => $e['field'] === 'payeRemitted');
        $this->assertNotEmpty($payrollErrors);
    }

    public function test_validates_vat_output_calculation(): void
    {
        $this->financial->update([
            'vat_data' => [
                'vatableSales' => 100000000,
                'vatOutput' => 7500000,
            ],
        ]);

        $errors = $this->service->checkCrossFieldConsistency($this->financial);

        $vatErrors = array_filter($errors, fn($e) => $e['section'] === 'vat');
        $this->assertEmpty($vatErrors);
    }
}
```

### FinancialServiceTest

**File:** `tests/Unit/Services/Financial/FinancialServiceTest.php`

```php
<?php

namespace Tests\Unit\Services\Financial;

use App\Enums\DataSource;
use App\Models\ClientFinancial;
use App\Models\Firm;
use App\Models\FirmUser;
use App\Models\TaxClient;
use App\Services\Financial\FinancialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialServiceTest extends TestCase
{
    use RefreshDatabase;

    private FinancialService $service;
    private Firm $firm;
    private FirmUser $user;
    private TaxClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FinancialService::class);

        $this->firm = Firm::factory()->create();
        $this->user = FirmUser::factory()->for($this->firm)->create();
        $this->client = TaxClient::factory()->for($this->firm)->create([
            'created_by' => $this->user->id,
        ]);
    }

    public function test_creates_financial_with_version(): void
    {
        $data = [
            'fiscal_year' => 2024,
            'fiscal_year_start' => '2024-01-01',
            'fiscal_year_end' => '2024-12-31',
            'data_source' => DataSource::MANAGEMENT,
            'income_data' => ['turnover' => 100000000],
        ];

        $financial = $this->service->create($this->client, $this->user, $data);

        $this->assertDatabaseHas('client_financials', [
            'id' => $financial->id,
            'fiscal_year' => 2024,
        ]);

        $this->assertDatabaseHas('client_financial_versions', [
            'client_financial_id' => $financial->id,
            'version' => 1,
        ]);
    }

    public function test_update_creates_new_version(): void
    {
        $financial = ClientFinancial::factory()
            ->for($this->client, 'taxClient')
            ->for($this->firm)
            ->create(['created_by' => $this->user->id]);

        $result = $this->service->update($financial, $this->user, [
            'income_data' => ['turnover' => 200000000],
        ]);

        $this->assertNotEmpty($result['changes']);
        $this->assertEquals(2, $financial->fresh()->version);
    }

    public function test_copy_from_prior_year(): void
    {
        ClientFinancial::factory()
            ->for($this->client, 'taxClient')
            ->for($this->firm)
            ->forYear(2023)
            ->create([
                'created_by' => $this->user->id,
                'income_data' => ['turnover' => 100000000],
            ]);

        $newFinancial = $this->service->copyFromPriorYear(
            $this->client,
            2023,
            2024,
            $this->user,
            10
        );

        $this->assertEquals(2024, $newFinancial->fiscal_year);
        $this->assertEquals(110000000, $newFinancial->income_data['turnover']);
        $this->assertEquals(DataSource::PROJECTED, $newFinancial->data_source);
    }

    public function test_cannot_update_locked_financial(): void
    {
        $financial = ClientFinancial::factory()
            ->for($this->client, 'taxClient')
            ->for($this->firm)
            ->locked()
            ->create([
                'created_by' => $this->user->id,
                'locked_by' => $this->user->id,
            ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot update locked financial data');

        $this->service->update($financial, $this->user, [
            'income_data' => ['turnover' => 200000000],
        ]);
    }

    public function test_lock_and_unlock(): void
    {
        $financial = ClientFinancial::factory()
            ->for($this->client, 'taxClient')
            ->for($this->firm)
            ->create(['created_by' => $this->user->id]);

        $this->service->lock($financial, $this->user);
        $this->assertTrue($financial->fresh()->is_locked);

        $this->service->unlock($financial, $this->user);
        $this->assertFalse($financial->fresh()->is_locked);
    }

    public function test_get_readiness_status(): void
    {
        $financial = ClientFinancial::factory()
            ->for($this->client, 'taxClient')
            ->for($this->firm)
            ->create([
                'created_by' => $this->user->id,
                'income_data' => [
                    'turnover' => 100000000,
                    'grossProfit' => 40000000,
                    'profitBeforeTax' => 20000000,
                ],
            ]);

        $status = $this->service->getReadinessStatus($financial);

        $this->assertArrayHasKey('is_ready', $status);
        $this->assertArrayHasKey('completion_percentage', $status);
        $this->assertArrayHasKey('warnings', $status);
    }
}
```

---

## Component Tests (React Testing Library)

### CurrencyInput.test.tsx

**File:** `resources/js/pages/App/Clients/Financials/components/__tests__/CurrencyInput.test.tsx`

```tsx
import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { CurrencyInput } from '../CurrencyInput';

describe('CurrencyInput', () => {
    it('renders with label', () => {
        render(
            <CurrencyInput
                label="Turnover"
                value={undefined}
                onChange={() => {}}
            />
        );

        expect(screen.getByLabelText(/Turnover/i)).toBeInTheDocument();
    });

    it('displays formatted value when not focused', () => {
        render(
            <CurrencyInput
                label="Turnover"
                value={1000000}
                onChange={() => {}}
            />
        );

        expect(screen.getByDisplayValue('1,000,000')).toBeInTheDocument();
    });

    it('allows numeric input', async () => {
        const onChange = jest.fn();
        render(
            <CurrencyInput
                label="Turnover"
                value={undefined}
                onChange={onChange}
            />
        );

        const input = screen.getByRole('textbox');
        await userEvent.type(input, '5000000');

        expect(onChange).toHaveBeenCalledWith(5000000);
    });

    it('shows required indicator', () => {
        render(
            <CurrencyInput
                label="Turnover"
                value={undefined}
                onChange={() => {}}
                required
            />
        );

        expect(screen.getByText('*')).toBeInTheDocument();
    });

    it('shows warning message', () => {
        render(
            <CurrencyInput
                label="Turnover"
                value={1000}
                onChange={() => {}}
                hasWarning
                warningMessage="Value seems too low"
            />
        );

        expect(screen.getByText('Value seems too low')).toBeInTheDocument();
    });

    it('is disabled when prop is set', () => {
        render(
            <CurrencyInput
                label="Turnover"
                value={1000000}
                onChange={() => {}}
                disabled
            />
        );

        expect(screen.getByRole('textbox')).toBeDisabled();
    });

    it('shows calculated value hint', () => {
        render(
            <CurrencyInput
                label="Gross Profit"
                value={30000000}
                onChange={() => {}}
                calculated
                calculatedValue={40000000}
            />
        );

        expect(screen.getByText(/Use calculated/i)).toBeInTheDocument();
    });
});
```

---

## Test Data Setup

### TestCase Base Class Enhancement

**File:** `tests/TestCase.php` (add helper methods)

```php
<?php

namespace Tests;

use App\Enums\FirmRole;
use App\Models\Firm;
use App\Models\FirmUser;
use App\Models\TaxClient;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function createFirmWithUser(FirmRole $role = FirmRole::PARTNER): array
    {
        $firm = Firm::factory()->create();
        $user = FirmUser::factory()->for($firm)->create(['role' => $role]);

        return [$firm, $user];
    }

    protected function createClientWithFinancial(
        Firm $firm,
        FirmUser $user,
        int $year = 2024
    ): array {
        $client = TaxClient::factory()
            ->for($firm)
            ->create(['created_by' => $user->id]);

        $financial = \App\Models\ClientFinancial::factory()
            ->for($client, 'taxClient')
            ->for($firm)
            ->forYear($year)
            ->create(['created_by' => $user->id]);

        return [$client, $financial];
    }
}
```

---

## Running Tests

```bash
php artisan test --filter=Financial

php artisan test tests/Feature/Financial

php artisan test tests/Unit/Services/Financial

npm run test -- --testPathPattern=Financials
```

---

## Test Coverage Goals

| Component | Target Coverage |
|-----------|----------------|
| FinancialService | 90% |
| FinancialValidationService | 95% |
| FinancialImportService | 85% |
| Controllers | 80% |
| React Components | 75% |

---

## Next Steps

Module 4 implementation is now complete. Proceed to:
→ **Module 5**: Tax Calculation Engine
