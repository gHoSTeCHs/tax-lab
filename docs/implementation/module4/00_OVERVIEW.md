# TaxLab Implementation Plan - Module 4: Financial Data Entry

## Overview

This module implements the Financial Data Entry functionality - capturing the financial information necessary to perform accurate tax calculations. It builds upon the Client Management system established in Module 3.

## Prerequisites from Module 3

| Component | Status | Required For |
|-----------|--------|--------------|
| TaxClient model | Required | Parent relationship for financials |
| BelongsToFirm trait | Required | Multi-tenant isolation |
| FirmUser model | Required | Creator/locker/verifier tracking |
| EntityType enum | Required | Entity-specific data handling |
| Client CRUD | Required | Navigation context |

## What This Module Covers

Based on specifications from:
- `docs/07_FINANCIAL_DATA_ENTRY.md` - Financial data structure and UI

### Phase 4.1: Database Schema
- [ ] Client financials table (with JSONB columns)
- [ ] Client financial versions table
- [ ] DataSource enum
- [ ] FinancialSection enum

### Phase 4.2: Models
- [ ] ClientFinancial model with JSONB casting
- [ ] ClientFinancialVersion model
- [ ] Version management methods
- [ ] Completion percentage calculation

### Phase 4.3: Service Layer
- [ ] FinancialService (CRUD operations)
- [ ] FinancialValidationService (cross-field validation)
- [ ] FinancialImportService (Excel/CSV import)

### Phase 4.4: Controllers & Form Requests
- [ ] ClientFinancialController
- [ ] FinancialImportController
- [ ] StoreFinancialRequest
- [ ] UpdateFinancialRequest

### Phase 4.5: Frontend Types
- [ ] Financial data interfaces
- [ ] DataSource types
- [ ] Version types

### Phase 4.6: Frontend Components
- [ ] Financial entry sections (Income, Balance Sheet, Employment, VAT, Capital)
- [ ] CurrencyInput component
- [ ] CalculatedField component
- [ ] Version history dialog
- [ ] Lock/unlock functionality

---

## Module Structure

```
module4/
├── 00_OVERVIEW.md              (this file)
├── 01_DATABASE_SCHEMA.md       Migrations, models, enums
├── 02_BACKEND_SERVICES.md      Services and validation
├── 03_CONTROLLERS.md           Controllers and form requests
├── 04_FRONTEND_TYPES.md        TypeScript definitions
├── 05_FRONTEND_COMPONENTS.md   React components
└── 06_TESTING_STRATEGY.md      Testing approach
```

---

## Route Structure

All routes use the `firm` guard and require authentication.

```
/app/clients/{client}/financials
├── /                           GET  List fiscal years
├── /                           POST Create new fiscal year
├── /{year}                     GET  View/edit fiscal year data
├── /{year}                     PUT  Update fiscal year data
├── /{year}                     DELETE Delete fiscal year
├── /{year}/lock                POST Lock data
├── /{year}/unlock              POST Unlock data
├── /{year}/versions            GET  List versions
├── /{year}/restore/{version}   POST Restore version

/app/financials
├── /template/{entityType}      GET  Download import template
├── /import                     POST Import from file
```

---

## Controller Structure

```
app/Http/Controllers/App/
├── ClientFinancialController.php
└── FinancialImportController.php
```

---

## Service Layer Structure

```
app/Services/Financial/
├── FinancialService.php
├── FinancialValidationService.php
└── FinancialImportService.php
```

---

## Frontend Structure

```
resources/js/
├── pages/App/Clients/Financials/
│   ├── Index.tsx               Fiscal year list
│   ├── Show.tsx                Tabbed entry form
│   └── components/
│       ├── IncomeSection.tsx
│       ├── BalanceSheetSection.tsx
│       ├── EmploymentSection.tsx
│       ├── VatSection.tsx
│       ├── CapitalSection.tsx
│       ├── SummarySection.tsx
│       ├── CurrencyInput.tsx
│       ├── CalculatedField.tsx
│       ├── DataQualityIndicator.tsx
│       ├── VersionHistoryDialog.tsx
│       └── LockStatusBadge.tsx
└── types/
    └── financial.ts
```

---

## Data Structure

### Financial Data Hierarchy

```
Tax Client
└── Financial Periods
    └── FY 2024
        ├── Income Statement Data
        ├── Balance Sheet Data
        ├── Employee Data
        ├── VAT Data
        ├── Capital Transactions
        └── Metadata (source, version, lock status)
    └── FY 2023
        └── ...
```

### JSONB Column Structure

Each section is stored as JSONB for flexibility:

**income_data:**
- turnover, costOfSales, grossProfit
- operatingExpenses, administrativeExpenses, depreciation
- interestIncome, interestExpense, dividendIncome, otherIncome
- profitBeforeTax, taxExpense, profitAfterTax

**balance_sheet_data:**
- totalAssets, fixedAssets, currentAssets
- totalLiabilities, longTermLiabilities, currentLiabilities
- shareCapital, retainedEarnings, totalEquity

**employment_data:**
- employeeCount, totalPayroll, payeRemitted
- pensionEmployer, pensionEmployee
- nhfContributions, nsitfContributions, itfContributions

**vat_data:**
- vatableSales, vatOutput, exemptSales, zeroRatedSales
- vatablePurchases, vatInputGoods, vatInputServices, vatInputCapital
- vatRemitted

**capital_data:**
- assetDisposals, assetDisposalCosts
- capitalGainsRealized, capitalAllowancesClaimed

---

## Validation Rules

### Cross-Field Validation

| Rule | Description |
|------|-------------|
| Gross Profit = Revenue - Cost of Sales | If both entered, should match |
| Total Assets = Total Liabilities + Equity | Balance sheet balance |
| VAT Output ≈ VATable Sales × VAT Rate | Within tolerance |
| PAYE ≤ Gross Payroll | Cannot exceed payroll |

### Reasonableness Checks

| Check | Warning Threshold |
|-------|-------------------|
| Gross Margin | < 0% or > 90% |
| Net Margin | < -50% or > 50% |
| Employee Cost Ratio | Outside 20-80% of revenue |

---

## Dependencies

### Backend Packages

```bash
composer require maatwebsite/excel  # Already installed for Module 3
```

### Frontend Packages

No additional packages required.

---

## Success Criteria

### Module 4 Complete When:

1. [ ] Financial data can be created for any fiscal year
2. [ ] All sections have working entry forms
3. [ ] Currency inputs format with ₦ prefix and thousand separators
4. [ ] Calculated fields auto-compute and allow override
5. [ ] Cross-field validation works with warnings
6. [ ] Data quality indicator shows completion percentage
7. [ ] Version history saves on each update
8. [ ] Lock/unlock functionality works
9. [ ] Version restore creates new version from historical
10. [ ] Import from Excel/CSV works
11. [ ] Dark mode works across all components
12. [ ] Role-based access enforced
13. [ ] Tests written and passing

---

## Next Steps

Once financial data entry is complete, proceed to:
→ **Module 5**: Tax Calculation Engine
