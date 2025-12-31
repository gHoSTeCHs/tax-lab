# TaxLab Implementation Plan - Module 5: Tax Calculation Engine

## Overview

This module implements the Tax Calculation Engine - the core of TaxLab. It applies Nigerian tax rules to client financial data and produces comprehensive tax liability calculations under both the old regime (pre-NTA 2025) and the new regime (NTA 2025).

## Prerequisites from Module 4

| Component | Status | Required For |
|-----------|--------|--------------|
| ClientFinancial model | Required | Input data for calculations |
| Financial data entry | Required | Data to calculate |
| JSONB data structure | Required | Accessing financial figures |
| Completion percentage | Required | Readiness validation |

## What This Module Covers

Based on specifications from:
- `docs/08_TAX_CALCULATION_ENGINE.md` - Calculation logic and tax rules

### Phase 5.1: Database Schema
- [ ] Calculations table
- [ ] CalculationType enum
- [ ] CalculationStatus enum
- [ ] TaxRegime enum
- [ ] CompanyClassification enum

### Phase 5.2: Tax Rules & Constants
- [ ] TaxConstants class with rates and thresholds
- [ ] TaxRulesRepository for versioned rules
- [ ] Capital allowance rates

### Phase 5.3: Calculator Architecture
- [ ] TaxCalculationService (orchestrator)
- [ ] CITCalculator (Company Income Tax)
- [ ] PITCalculator (Personal Income Tax)
- [ ] VATCalculator (Value Added Tax)
- [ ] CGTCalculator (Capital Gains Tax)
- [ ] DevelopmentLevyCalculator
- [ ] PAYECalculator

### Phase 5.4: Results & Aggregation
- [ ] Result DTOs for each tax type
- [ ] ResultsAggregator
- [ ] OptimizationAnalyzer

### Phase 5.5: Controllers & Routes
- [ ] CalculationController
- [ ] RunCalculationRequest

### Phase 5.6: Frontend Types & Components
- [ ] Calculation types
- [ ] Results display components
- [ ] Comparison visualizations

---

## Module Structure

```
module5/
├── 00_OVERVIEW.md              (this file)
├── 01_DATABASE_SCHEMA.md       Migrations, models, enums
├── 02_TAX_RULES.md             Constants and rules repository
├── 03_CALCULATORS.md           Individual tax calculators
├── 04_ORCHESTRATION.md         Service orchestration
├── 05_FRONTEND_TYPES.md        TypeScript definitions
├── 06_FRONTEND_COMPONENTS.md   React components
└── 07_TESTING_STRATEGY.md      Testing approach
```

---

## Route Structure

All routes use the `firm` guard and require authentication.

```
/app/clients/{client}/calculations
├── /                           GET  List calculation history
├── /create                     GET  Show calculation form
├── /                           POST Run calculation
├── /{calculation}              GET  View calculation results
```

---

## Controller Structure

```
app/Http/Controllers/App/
└── CalculationController.php
```

---

## Service Layer Structure

```
app/Services/Calculation/
├── TaxCalculationService.php      Orchestrator
├── TaxRulesRepository.php         Rules access
├── TaxConstants.php               Tax rates/thresholds
├── OptimizationAnalyzer.php       Opportunity detection
├── ResultsAggregator.php          Result compilation
├── Calculators/
│   ├── CITCalculator.php
│   ├── PITCalculator.php
│   ├── VATCalculator.php
│   ├── CGTCalculator.php
│   ├── DevelopmentLevyCalculator.php
│   └── PAYECalculator.php
└── Results/
    ├── CITResult.php
    ├── PITResult.php
    ├── VATResult.php
    ├── CGTResult.php
    ├── DevelopmentLevyResult.php
    ├── PAYEAnalysisResult.php
    └── AggregatedResults.php
```

---

## Frontend Structure

```
resources/js/
├── pages/App/Clients/Calculations/
│   ├── Index.tsx               Calculation history
│   ├── Create.tsx              Run calculation form
│   ├── Show.tsx                Results display
│   └── components/
│       ├── CalculationHeader.tsx
│       ├── SavingsBanner.tsx
│       ├── ResultsTabs.tsx
│       ├── CITResultsPanel.tsx
│       ├── PITResultsPanel.tsx
│       ├── VATResultsPanel.tsx
│       ├── CGTResultsPanel.tsx
│       ├── DevelopmentLevyPanel.tsx
│       ├── OptimizationsPanel.tsx
│       ├── ComparisonBarChart.tsx
│       ├── TaxCompositionPie.tsx
│       └── InsightsList.tsx
└── types/
    └── calculation.ts
```

---

## Tax Calculation Overview

### Company Income Tax (CIT)

**Old Regime:**
| Classification | Turnover | Rate |
|----------------|----------|------|
| Small | ≤ ₦25M | 0% |
| Medium | ₦25M - ₦100M | 20% |
| Large | > ₦100M | 30% |

**New Regime (NTA 2025):**
| Classification | Turnover | Rate |
|----------------|----------|------|
| Small | ≤ ₦50M | 0% |
| Medium | ₦50M - ₦100M | 20% |
| Large | > ₦100M | 25% |

### Personal Income Tax (PIT)

Progressive bands differ between old and new regimes, with ₦800,000 exemption in new regime.

### VAT

7.5% rate unchanged, but input recovery expanded under new regime:
- Services VAT now recoverable
- Capital items VAT now recoverable

### Development Levy

**Old Regime:** Multiple levies (~3.255% combined)
- Education Tax: 2%
- NITDF: 1%
- NASENI: 0.25%
- Police Trust Fund: 0.005%

**New Regime:** Unified 4% Development Levy

### Capital Gains Tax

**Old Regime:** 10% flat rate
**New Regime:** Aligned with income tax rates (up to 25%)

---

## Dependencies

### Backend Packages

No additional packages required.

### Frontend Packages

```bash
npm install recharts  # For visualization charts
```

---

## Success Criteria

### Module 5 Complete When:

1. [ ] Full analysis calculates all tax types
2. [ ] Component calculations work (CIT only, PIT only, etc.)
3. [ ] Old vs new regime comparison accurate
4. [ ] Company classification follows thresholds
5. [ ] PIT bands apply correctly per regime
6. [ ] VAT input recovery differs by regime
7. [ ] Development levy consolidation works
8. [ ] CGT rates apply correctly
9. [ ] Optimization opportunities identified
10. [ ] Results stored with audit trail
11. [ ] Visualizations render correctly
12. [ ] Key insights extracted and displayed
13. [ ] Historical calculations retrievable
14. [ ] Dark mode works across components
15. [ ] Tests written and passing

---

## Next Steps

Once tax calculation engine is complete, proceed to:
→ **Module 6**: Reports & Scenarios
