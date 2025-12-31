# TaxLab Implementation - Module 5: Testing Strategy

## Overview

This document defines the testing approach for the Tax Calculation Engine. Given the critical nature of tax calculations, comprehensive testing is essential to ensure accuracy and compliance with Nigerian tax legislation.

---

## Test Categories

| Category | Purpose | Location |
|----------|---------|----------|
| Unit Tests | Individual calculator logic | `tests/Unit/Services/Calculation/` |
| Integration Tests | Calculator combinations | `tests/Integration/Calculation/` |
| Feature Tests | API endpoints | `tests/Feature/Calculation/` |
| Validation Tests | Tax rule accuracy | `tests/Unit/Services/Calculation/Validation/` |
| React Component Tests | Frontend rendering | `resources/js/__tests__/` |

---

## Unit Tests

### CIT Calculator Tests

**File:** `tests/Unit/Services/Calculation/CITCalculatorTest.php`

```php
<?php

namespace Tests\Unit\Services\Calculation;

use App\Enums\TaxRegime;
use App\Enums\CompanyClassification;
use App\Services\Calculation\Calculators\CITCalculator;
use App\Services\Calculation\TaxRulesRepository;
use App\Services\Calculation\CapitalAllowanceCalculator;
use Tests\TestCase;

class CITCalculatorTest extends TestCase
{
    private CITCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new CITCalculator(
            new TaxRulesRepository(),
            new CapitalAllowanceCalculator()
        );
    }

    public function test_classifies_small_company_correctly_old_regime(): void
    {
        $result = $this->calculator->calculate([
            'turnover' => 20_000_000,
            'accounting_profit' => 5_000_000,
            'add_backs' => 0,
            'deductions' => 0,
            'assets' => [],
        ], TaxRegime::OLD);

        $this->assertEquals('small', $result['classification']);
        $this->assertEquals(0, $result['tax_rate']);
        $this->assertEquals(0, $result['tax_liability']);
    }

    public function test_classifies_small_company_correctly_new_regime(): void
    {
        $result = $this->calculator->calculate([
            'turnover' => 45_000_000,
            'accounting_profit' => 10_000_000,
            'add_backs' => 0,
            'deductions' => 0,
            'assets' => [],
        ], TaxRegime::NEW);

        $this->assertEquals('small', $result['classification']);
        $this->assertEquals(0, $result['tax_rate']);
        $this->assertEquals(0, $result['tax_liability']);
    }

    public function test_classifies_medium_company_old_regime(): void
    {
        $result = $this->calculator->calculate([
            'turnover' => 50_000_000,
            'accounting_profit' => 10_000_000,
            'add_backs' => 0,
            'deductions' => 0,
            'assets' => [],
        ], TaxRegime::OLD);

        $this->assertEquals('medium', $result['classification']);
        $this->assertEquals(0.20, $result['tax_rate']);
    }

    public function test_classifies_large_company_old_regime(): void
    {
        $result = $this->calculator->calculate([
            'turnover' => 150_000_000,
            'accounting_profit' => 20_000_000,
            'add_backs' => 0,
            'deductions' => 0,
            'assets' => [],
        ], TaxRegime::OLD);

        $this->assertEquals('large', $result['classification']);
        $this->assertEquals(0.30, $result['tax_rate']);
    }

    public function test_classifies_large_company_new_regime(): void
    {
        $result = $this->calculator->calculate([
            'turnover' => 150_000_000,
            'accounting_profit' => 20_000_000,
            'add_backs' => 0,
            'deductions' => 0,
            'assets' => [],
        ], TaxRegime::NEW);

        $this->assertEquals('large', $result['classification']);
        $this->assertEquals(0.25, $result['tax_rate']);
    }

    public function test_applies_minimum_tax_when_applicable(): void
    {
        $result = $this->calculator->calculate([
            'turnover' => 150_000_000,
            'accounting_profit' => 100_000,
            'add_backs' => 0,
            'deductions' => 0,
            'assets' => [],
        ], TaxRegime::OLD);

        $this->assertTrue($result['minimum_tax_applies']);
        $this->assertEquals(750_000, $result['minimum_tax']);
        $this->assertEquals(750_000, $result['tax_liability']);
    }

    public function test_calculates_add_backs_correctly(): void
    {
        $result = $this->calculator->calculate([
            'turnover' => 150_000_000,
            'accounting_profit' => 10_000_000,
            'add_backs' => 2_000_000,
            'deductions' => 500_000,
            'assets' => [],
        ], TaxRegime::OLD);

        $this->assertEquals(11_500_000, $result['adjusted_profit']);
    }

    public function test_applies_capital_allowances(): void
    {
        $result = $this->calculator->calculate([
            'turnover' => 150_000_000,
            'accounting_profit' => 20_000_000,
            'add_backs' => 0,
            'deductions' => 0,
            'assets' => [
                ['class' => 'plant_machinery', 'cost' => 10_000_000, 'year_acquired' => 1, 'current_year' => 1],
            ],
        ], TaxRegime::OLD);

        $this->assertGreaterThan(0, $result['capital_allowances']['total_allowance']);
        $this->assertLessThan(20_000_000, $result['taxable_profit']);
    }

    public function test_compares_both_regimes(): void
    {
        $result = $this->calculator->calculateBothRegimes([
            'turnover' => 150_000_000,
            'accounting_profit' => 20_000_000,
            'add_backs' => 0,
            'deductions' => 0,
            'assets' => [],
        ]);

        $this->assertArrayHasKey('old_regime', $result);
        $this->assertArrayHasKey('new_regime', $result);
        $this->assertArrayHasKey('comparison', $result);
        $this->assertGreaterThan($result['new_regime']['tax_liability'], $result['old_regime']['tax_liability']);
    }
}
```

### PIT Calculator Tests

**File:** `tests/Unit/Services/Calculation/PITCalculatorTest.php`

```php
<?php

namespace Tests\Unit\Services\Calculation;

use App\Enums\TaxRegime;
use App\Services\Calculation\Calculators\PITCalculator;
use App\Services\Calculation\TaxRulesRepository;
use Tests\TestCase;

class PITCalculatorTest extends TestCase
{
    private PITCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new PITCalculator(new TaxRulesRepository());
    }

    public function test_applies_first_band_correctly_old_regime(): void
    {
        $result = $this->calculator->calculate([
            'employment_income' => 300_000,
        ], TaxRegime::OLD);

        $this->assertCount(1, $result['tax_by_band']);
        $this->assertEquals(0.07, $result['tax_by_band'][0]['rate']);
    }

    public function test_exempts_first_800k_new_regime(): void
    {
        $result = $this->calculator->calculate([
            'employment_income' => 800_000,
        ], TaxRegime::NEW);

        $this->assertEquals(0, $result['tax_liability']);
    }

    public function test_calculates_progressive_tax_old_regime(): void
    {
        $result = $this->calculator->calculate([
            'employment_income' => 5_000_000,
        ], TaxRegime::OLD);

        $this->assertGreaterThan(0, $result['tax_liability']);
        $this->assertCount(6, $result['tax_by_band']);
    }

    public function test_calculates_progressive_tax_new_regime(): void
    {
        $result = $this->calculator->calculate([
            'employment_income' => 15_000_000,
        ], TaxRegime::NEW);

        $expectedBandsWithTax = array_filter($result['tax_by_band'], fn($b) => $b['tax'] > 0);
        $this->assertGreaterThanOrEqual(4, count($expectedBandsWithTax));
    }

    public function test_calculates_consolidated_relief_old_regime(): void
    {
        $grossIncome = 10_000_000;
        $result = $this->calculator->calculate([
            'employment_income' => $grossIncome,
        ], TaxRegime::OLD);

        $expectedMinimum = max(200_000, $grossIncome * 0.01);
        $expectedAdditional = $grossIncome * 0.20;
        $expectedRelief = $expectedMinimum + $expectedAdditional;

        $this->assertEquals($expectedRelief, $result['reliefs']['consolidated_relief']);
    }

    public function test_includes_pension_relief(): void
    {
        $result = $this->calculator->calculate([
            'employment_income' => 5_000_000,
            'pension_contribution' => 400_000,
        ], TaxRegime::OLD);

        $this->assertEquals(400_000, $result['reliefs']['pension_relief']);
    }

    public function test_new_regime_produces_lower_tax_for_moderate_income(): void
    {
        $income = 5_000_000;

        $oldResult = $this->calculator->calculate([
            'employment_income' => $income,
        ], TaxRegime::OLD);

        $newResult = $this->calculator->calculate([
            'employment_income' => $income,
        ], TaxRegime::NEW);

        $this->assertLessThan($oldResult['tax_liability'], $newResult['tax_liability']);
    }
}
```

### VAT Calculator Tests

**File:** `tests/Unit/Services/Calculation/VATCalculatorTest.php`

```php
<?php

namespace Tests\Unit\Services\Calculation;

use App\Enums\TaxRegime;
use App\Services\Calculation\Calculators\VATCalculator;
use App\Services\Calculation\TaxRulesRepository;
use Tests\TestCase;

class VATCalculatorTest extends TestCase
{
    private VATCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new VATCalculator(new TaxRulesRepository());
    }

    public function test_calculates_output_vat_at_7_5_percent(): void
    {
        $result = $this->calculator->calculate([
            'vatable_sales' => 100_000_000,
            'exempt_sales' => 0,
            'zero_rated_sales' => 0,
            'vat_on_goods' => 0,
            'vat_on_services' => 0,
            'vat_on_capital' => 0,
        ], TaxRegime::OLD);

        $this->assertEquals(7_500_000, $result['output_vat']);
    }

    public function test_old_regime_does_not_recover_services_vat(): void
    {
        $result = $this->calculator->calculate([
            'vatable_sales' => 100_000_000,
            'exempt_sales' => 0,
            'zero_rated_sales' => 0,
            'vat_on_goods' => 2_000_000,
            'vat_on_services' => 1_000_000,
            'vat_on_capital' => 500_000,
        ], TaxRegime::OLD);

        $this->assertEquals(0, $result['input_vat']['services']['recoverable']);
        $this->assertEquals(0, $result['input_vat']['capital']['recoverable']);
        $this->assertEquals(2_000_000, $result['total_recoverable']);
    }

    public function test_new_regime_recovers_all_input_vat(): void
    {
        $result = $this->calculator->calculate([
            'vatable_sales' => 100_000_000,
            'exempt_sales' => 0,
            'zero_rated_sales' => 0,
            'vat_on_goods' => 2_000_000,
            'vat_on_services' => 1_000_000,
            'vat_on_capital' => 500_000,
        ], TaxRegime::NEW);

        $this->assertEquals(1_000_000, $result['input_vat']['services']['recoverable']);
        $this->assertEquals(500_000, $result['input_vat']['capital']['recoverable']);
        $this->assertEquals(3_500_000, $result['total_recoverable']);
    }

    public function test_calculates_net_vat_payable(): void
    {
        $result = $this->calculator->calculate([
            'vatable_sales' => 100_000_000,
            'exempt_sales' => 0,
            'zero_rated_sales' => 0,
            'vat_on_goods' => 5_000_000,
            'vat_on_services' => 0,
            'vat_on_capital' => 0,
        ], TaxRegime::OLD);

        $this->assertEquals(2_500_000, $result['net_vat_payable']);
    }

    public function test_comparison_shows_recovery_difference(): void
    {
        $result = $this->calculator->calculateBothRegimes([
            'vatable_sales' => 100_000_000,
            'exempt_sales' => 0,
            'zero_rated_sales' => 0,
            'vat_on_goods' => 2_000_000,
            'vat_on_services' => 1_500_000,
            'vat_on_capital' => 750_000,
        ]);

        $this->assertArrayHasKey('additional_recovery', $result['comparison']);
        $this->assertEquals(2_250_000, $result['comparison']['additional_recovery']);
    }
}
```

### Development Levy Calculator Tests

**File:** `tests/Unit/Services/Calculation/DevelopmentLevyCalculatorTest.php`

```php
<?php

namespace Tests\Unit\Services\Calculation;

use App\Enums\TaxRegime;
use App\Services\Calculation\Calculators\DevelopmentLevyCalculator;
use App\Services\Calculation\TaxRulesRepository;
use Tests\TestCase;

class DevelopmentLevyCalculatorTest extends TestCase
{
    private DevelopmentLevyCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new DevelopmentLevyCalculator(new TaxRulesRepository());
    }

    public function test_old_regime_has_multiple_levies(): void
    {
        $result = $this->calculator->calculate([
            'assessable_profit' => 10_000_000,
            'profit_before_tax' => 12_000_000,
            'net_profit' => 8_000_000,
        ], TaxRegime::OLD);

        $this->assertArrayHasKey('education_tax', $result['levies']);
        $this->assertArrayHasKey('nitdf', $result['levies']);
        $this->assertArrayHasKey('naseni', $result['levies']);
        $this->assertArrayHasKey('police_trust_fund', $result['levies']);
    }

    public function test_new_regime_has_unified_levy(): void
    {
        $result = $this->calculator->calculate([
            'assessable_profit' => 10_000_000,
            'profit_before_tax' => 12_000_000,
            'net_profit' => 8_000_000,
        ], TaxRegime::NEW);

        $this->assertArrayHasKey('unified_levy', $result['levies']);
        $this->assertCount(1, $result['levies']);
    }

    public function test_new_regime_unified_levy_is_4_percent(): void
    {
        $assessableProfit = 10_000_000;

        $result = $this->calculator->calculate([
            'assessable_profit' => $assessableProfit,
            'profit_before_tax' => 12_000_000,
            'net_profit' => 8_000_000,
        ], TaxRegime::NEW);

        $this->assertEquals(400_000, $result['total_levy']);
    }

    public function test_old_regime_combined_rate_approximately_3_26_percent(): void
    {
        $result = $this->calculator->calculate([
            'assessable_profit' => 10_000_000,
            'profit_before_tax' => 10_000_000,
            'net_profit' => 10_000_000,
        ], TaxRegime::OLD);

        $this->assertGreaterThan(0.032, $result['combined_rate']);
        $this->assertLessThan(0.034, $result['combined_rate']);
    }
}
```

---

## Feature Tests

### Calculation Controller Tests

**File:** `tests/Feature/Calculation/CalculationControllerTest.php`

```php
<?php

namespace Tests\Feature\Calculation;

use App\Models\Calculation;
use App\Models\ClientFinancial;
use App\Models\Firm;
use App\Models\FirmUser;
use App\Models\TaxClient;
use App\Models\User;
use App\Enums\CalculationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalculationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private FirmUser $firmUser;
    private TaxClient $client;
    private ClientFinancial $financial;

    protected function setUp(): void
    {
        parent::setUp();

        $firm = Firm::factory()->create();
        $this->user = User::factory()->create();
        $this->firmUser = FirmUser::factory()->create([
            'firm_id' => $firm->id,
            'user_id' => $this->user->id,
        ]);
        $this->client = TaxClient::factory()->create([
            'firm_id' => $firm->id,
            'entity_type' => 'company',
        ]);
        $this->financial = ClientFinancial::factory()->complete()->create([
            'tax_client_id' => $this->client->id,
            'firm_id' => $firm->id,
            'fiscal_year' => 2024,
        ]);
    }

    public function test_index_displays_calculation_history(): void
    {
        Calculation::factory()->count(3)->create([
            'tax_client_id' => $this->client->id,
            'firm_id' => $this->client->firm_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('app.clients.calculations.index', $this->client));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('App/Clients/Calculations/Index')
                ->has('calculations', 3)
            );
    }

    public function test_create_shows_calculation_form(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('app.clients.calculations.create', $this->client));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('App/Clients/Calculations/Create')
                ->has('financials')
                ->has('calculationTypes')
            );
    }

    public function test_store_runs_full_analysis(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('app.clients.calculations.store', $this->client), [
                'fiscal_year' => 2024,
                'calculation_type' => 'full_analysis',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('calculations', [
            'tax_client_id' => $this->client->id,
            'fiscal_year' => 2024,
            'calculation_type' => CalculationType::FULL_ANALYSIS->value,
            'status' => 'completed',
        ]);
    }

    public function test_store_runs_cit_only(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('app.clients.calculations.store', $this->client), [
                'fiscal_year' => 2024,
                'calculation_type' => 'cit_only',
            ]);

        $response->assertRedirect();

        $calculation = Calculation::latest()->first();
        $this->assertEquals('cit_only', $calculation->calculation_type->value);
        $this->assertArrayHasKey('cit', $calculation->results);
    }

    public function test_store_requires_complete_financial_data(): void
    {
        $incompleteFinancial = ClientFinancial::factory()->create([
            'tax_client_id' => $this->client->id,
            'firm_id' => $this->client->firm_id,
            'fiscal_year' => 2023,
            'is_complete' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('app.clients.calculations.store', $this->client), [
                'fiscal_year' => 2023,
                'calculation_type' => 'full_analysis',
            ]);

        $response->assertSessionHasErrors();
    }

    public function test_show_displays_calculation_results(): void
    {
        $calculation = Calculation::factory()->create([
            'tax_client_id' => $this->client->id,
            'firm_id' => $this->client->firm_id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('app.clients.calculations.show', [$this->client, $calculation]));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('App/Clients/Calculations/Show')
                ->has('calculation')
                ->has('insights')
            );
    }

    public function test_new_calculation_supersedes_previous(): void
    {
        $oldCalculation = Calculation::factory()->create([
            'tax_client_id' => $this->client->id,
            'firm_id' => $this->client->firm_id,
            'fiscal_year' => 2024,
            'status' => 'completed',
        ]);

        $this->actingAs($this->user)
            ->post(route('app.clients.calculations.store', $this->client), [
                'fiscal_year' => 2024,
                'calculation_type' => 'full_analysis',
            ]);

        $this->assertDatabaseHas('calculations', [
            'id' => $oldCalculation->id,
            'status' => 'superseded',
        ]);
    }
}
```

---

## Tax Rule Validation Tests

**File:** `tests/Unit/Services/Calculation/Validation/TaxRuleValidationTest.php`

```php
<?php

namespace Tests\Unit\Services\Calculation\Validation;

use App\Enums\TaxRegime;
use App\Services\Calculation\TaxConstants;
use Tests\TestCase;

class TaxRuleValidationTest extends TestCase
{
    public function test_cit_small_threshold_old_regime_is_25m(): void
    {
        $thresholds = TaxConstants::CIT_THRESHOLDS[TaxRegime::OLD->value];
        $this->assertEquals(25_000_000, $thresholds['small_max']);
    }

    public function test_cit_small_threshold_new_regime_is_50m(): void
    {
        $thresholds = TaxConstants::CIT_THRESHOLDS[TaxRegime::NEW->value];
        $this->assertEquals(50_000_000, $thresholds['small_max']);
    }

    public function test_cit_large_rate_old_regime_is_30_percent(): void
    {
        $rates = TaxConstants::CIT_RATES[TaxRegime::OLD->value];
        $this->assertEquals(0.30, $rates['large']);
    }

    public function test_cit_large_rate_new_regime_is_25_percent(): void
    {
        $rates = TaxConstants::CIT_RATES[TaxRegime::NEW->value];
        $this->assertEquals(0.25, $rates['large']);
    }

    public function test_pit_old_regime_has_correct_bands(): void
    {
        $bands = TaxConstants::PIT_BANDS[TaxRegime::OLD->value];

        $this->assertEquals(300_000, $bands[0]['max']);
        $this->assertEquals(0.07, $bands[0]['rate']);

        $this->assertEquals(0.24, $bands[5]['rate']);
    }

    public function test_pit_new_regime_has_800k_exemption(): void
    {
        $bands = TaxConstants::PIT_BANDS[TaxRegime::NEW->value];

        $this->assertEquals(800_000, $bands[0]['max']);
        $this->assertEquals(0.00, $bands[0]['rate']);
    }

    public function test_pit_new_regime_top_rate_is_25_percent(): void
    {
        $bands = TaxConstants::PIT_BANDS[TaxRegime::NEW->value];
        $lastBand = end($bands);
        $this->assertEquals(0.25, $lastBand['rate']);
    }

    public function test_vat_rate_is_7_5_percent(): void
    {
        $this->assertEquals(0.075, TaxConstants::VAT_RATE);
    }

    public function test_old_regime_does_not_allow_services_recovery(): void
    {
        $recovery = TaxConstants::VAT_INPUT_RECOVERY[TaxRegime::OLD->value];
        $this->assertFalse($recovery['services']);
    }

    public function test_new_regime_allows_services_recovery(): void
    {
        $recovery = TaxConstants::VAT_INPUT_RECOVERY[TaxRegime::NEW->value];
        $this->assertTrue($recovery['services']);
    }

    public function test_cgt_old_regime_is_10_percent(): void
    {
        $rates = TaxConstants::CGT_RATES[TaxRegime::OLD->value];
        $this->assertEquals(0.10, $rates['company']);
        $this->assertEquals(0.10, $rates['individual']);
    }

    public function test_cgt_new_regime_is_25_percent(): void
    {
        $rates = TaxConstants::CGT_RATES[TaxRegime::NEW->value];
        $this->assertEquals(0.25, $rates['company']);
        $this->assertEquals(0.25, $rates['individual']);
    }

    public function test_development_levy_new_regime_is_4_percent(): void
    {
        $levies = TaxConstants::DEVELOPMENT_LEVY[TaxRegime::NEW->value];
        $this->assertArrayHasKey('unified_levy', $levies);
        $this->assertEquals(0.04, $levies['unified_levy']['rate']);
    }
}
```

---

## Integration Tests

**File:** `tests/Integration/Calculation/TaxCalculationServiceTest.php`

```php
<?php

namespace Tests\Integration\Calculation;

use App\Enums\CalculationType;
use App\Models\ClientFinancial;
use App\Models\Firm;
use App\Models\FirmUser;
use App\Models\TaxClient;
use App\Models\User;
use App\Services\Calculation\TaxCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    private TaxCalculationService $service;
    private TaxClient $client;
    private ClientFinancial $financial;
    private FirmUser $firmUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(TaxCalculationService::class);

        $firm = Firm::factory()->create();
        $user = User::factory()->create();
        $this->firmUser = FirmUser::factory()->create([
            'firm_id' => $firm->id,
            'user_id' => $user->id,
        ]);

        $this->client = TaxClient::factory()->create([
            'firm_id' => $firm->id,
            'entity_type' => 'company',
        ]);

        $this->financial = ClientFinancial::factory()->create([
            'tax_client_id' => $this->client->id,
            'firm_id' => $firm->id,
            'fiscal_year' => 2024,
            'is_complete' => true,
            'income_data' => [
                'turnover' => 150_000_000,
                'profit_before_tax' => 20_000_000,
                'profit_after_tax' => 14_000_000,
            ],
            'vat_data' => [
                'vatable_sales' => 140_000_000,
                'vat_on_goods' => 5_000_000,
                'vat_on_services' => 2_000_000,
            ],
        ]);
    }

    public function test_full_analysis_includes_all_tax_types_for_company(): void
    {
        $calculation = $this->service->runCalculation(
            $this->client,
            $this->financial,
            CalculationType::FULL_ANALYSIS,
            $this->firmUser
        );

        $this->assertArrayHasKey('cit', $calculation->results);
        $this->assertArrayHasKey('vat', $calculation->results);
        $this->assertArrayHasKey('development_levy', $calculation->results);
    }

    public function test_calculation_stores_rules_version(): void
    {
        $calculation = $this->service->runCalculation(
            $this->client,
            $this->financial,
            CalculationType::FULL_ANALYSIS,
            $this->firmUser
        );

        $this->assertNotNull($calculation->rules_version);
        $this->assertMatchesRegularExpression('/^v\d+\.\d+\.\d+$/', $calculation->rules_version);
    }

    public function test_calculation_stores_financial_version(): void
    {
        $calculation = $this->service->runCalculation(
            $this->client,
            $this->financial,
            CalculationType::FULL_ANALYSIS,
            $this->firmUser
        );

        $this->assertEquals($this->financial->version, $calculation->financial_version);
    }

    public function test_summary_includes_overall_comparison(): void
    {
        $calculation = $this->service->runCalculation(
            $this->client,
            $this->financial,
            CalculationType::FULL_ANALYSIS,
            $this->firmUser
        );

        $this->assertArrayHasKey('overall_comparison', $calculation->summary);
        $this->assertArrayHasKey('old_total', $calculation->summary['overall_comparison']);
        $this->assertArrayHasKey('new_total', $calculation->summary['overall_comparison']);
    }

    public function test_identifies_optimization_opportunities(): void
    {
        $calculation = $this->service->runCalculation(
            $this->client,
            $this->financial,
            CalculationType::FULL_ANALYSIS,
            $this->firmUser
        );

        $this->assertIsArray($calculation->optimizations);
    }

    public function test_records_calculation_time(): void
    {
        $calculation = $this->service->runCalculation(
            $this->client,
            $this->financial,
            CalculationType::FULL_ANALYSIS,
            $this->firmUser
        );

        $this->assertGreaterThan(0, $calculation->calculation_time_ms);
    }
}
```

---

## React Component Tests

**File:** `resources/js/__tests__/components/SavingsBanner.test.tsx`

```tsx
import { render, screen } from '@testing-library/react';
import { SavingsBanner } from '@/pages/App/Clients/Calculations/components/SavingsBanner';

describe('SavingsBanner', () => {
    it('displays savings message when new regime is cheaper', () => {
        render(
            <SavingsBanner
                oldTotal={10000000}
                newTotal={8000000}
                savingsPercentage={20}
            />
        );

        expect(screen.getByText(/SAVE/)).toBeInTheDocument();
        expect(screen.getByText(/₦2,000,000/)).toBeInTheDocument();
    });

    it('displays increase message when new regime is more expensive', () => {
        render(
            <SavingsBanner
                oldTotal={8000000}
                newTotal={10000000}
                savingsPercentage={-25}
            />
        );

        expect(screen.getByText(/increases/)).toBeInTheDocument();
    });

    it('displays neutral message when no difference', () => {
        render(
            <SavingsBanner
                oldTotal={5000000}
                newTotal={5000000}
                savingsPercentage={0}
            />
        );

        expect(screen.getByText(/No difference/)).toBeInTheDocument();
    });
});
```

---

## Test Data Fixtures

**File:** `tests/Fixtures/CalculationFixtures.php`

```php
<?php

namespace Tests\Fixtures;

class CalculationFixtures
{
    public static function largeCompanyInputs(): array
    {
        return [
            'turnover' => 150_000_000,
            'accounting_profit' => 20_000_000,
            'add_backs' => 3_000_000,
            'deductions' => 1_000_000,
            'assets' => [
                ['class' => 'plant_machinery', 'cost' => 10_000_000, 'year_acquired' => 1, 'current_year' => 1],
            ],
        ];
    }

    public static function smallCompanyInputsOldRegime(): array
    {
        return [
            'turnover' => 20_000_000,
            'accounting_profit' => 5_000_000,
            'add_backs' => 0,
            'deductions' => 0,
            'assets' => [],
        ];
    }

    public static function highIncomeIndividualInputs(): array
    {
        return [
            'employment_income' => 50_000_000,
            'rental_income' => 5_000_000,
            'pension_contribution' => 4_000_000,
            'nhf_contribution' => 1_250_000,
        ];
    }
}
```

---

## Running Tests

```bash
php artisan test --filter=CITCalculatorTest
php artisan test --filter=TaxRuleValidationTest
php artisan test --filter=CalculationControllerTest
php artisan test tests/Unit/Services/Calculation/
php artisan test tests/Feature/Calculation/

npm run test -- --testPathPattern=Calculation
```

---

## Test Coverage Goals

| Component | Target Coverage |
|-----------|----------------|
| TaxConstants | 100% |
| CITCalculator | 95% |
| PITCalculator | 95% |
| VATCalculator | 95% |
| CGTCalculator | 90% |
| DevelopmentLevyCalculator | 90% |
| TaxCalculationService | 85% |
| CalculationController | 80% |

---

## Module 5 Complete

With the testing strategy in place, Module 5 implementation documentation is complete.

### Summary of Documents Created

1. **00_OVERVIEW.md** - Module overview and structure
2. **01_DATABASE_SCHEMA.md** - Migrations, models, enums
3. **02_TAX_RULES.md** - Tax constants and rules repository
4. **03_CALCULATORS.md** - Individual tax calculators
5. **04_ORCHESTRATION.md** - Service orchestration
6. **05_FRONTEND_TYPES.md** - TypeScript definitions
7. **06_FRONTEND_COMPONENTS.md** - React components
8. **07_TESTING_STRATEGY.md** - Testing approach (this document)

### Next Module

Once Module 5 is implemented and tested, proceed to:
→ **Module 6**: Reports & Scenarios
