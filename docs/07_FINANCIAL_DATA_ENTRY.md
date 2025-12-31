# TaxLab — Financial Data Entry

## Document Information

| Item | Detail |
|------|--------|
| Document | Financial Data Entry Module |
| Version | 1.0 |
| Last Updated | December 2024 |
| Audience | Developers, Product Team |

---

## 1. Overview

The Financial Data Entry module captures the financial information necessary to perform accurate tax calculations. Data quality directly impacts calculation accuracy, so this module emphasizes validation, guidance, and structured input.

### 1.1 Access

- **URL:** `/app/clients/{id}/financials`
- **Guard:** firm
- **Roles:** Partner, Manager, Associate (assigned clients), Viewer (read-only)

### 1.2 Key Concepts

**Fiscal Year:** The 12-month accounting period for the client, not necessarily calendar year.

**Financial Period:** A specific fiscal year's data (e.g., "FY 2024" ending December 2024).

**Data Sources:** Where financial data comes from (audited accounts, management accounts, projections).

---

## 2. Financial Data Structure

### 2.1 Data Hierarchy

```
Tax Client
└── Financial Periods
    └── FY 2024
        ├── Income Statement Data
        ├── Balance Sheet Data
        ├── Employee Data
        ├── VAT Data
        ├── Capital Transactions
        └── Metadata
    └── FY 2023
        └── ...
```

### 2.2 Company Financial Data

**Income Statement Section**

| Field | Type | Description | Required |
|-------|------|-------------|----------|
| Turnover/Revenue | currency | Total gross revenue | Yes |
| Cost of Sales | currency | Direct costs | No |
| Gross Profit | currency | Calculated or entered | Yes |
| Operating Expenses | currency | Total operating costs | No |
| Administrative Expenses | currency | Admin costs | No |
| Depreciation | currency | Accounting depreciation | No |
| Interest Income | currency | Interest received | No |
| Interest Expense | currency | Interest paid | No |
| Dividend Income | currency | Dividends received | No |
| Other Income | currency | Miscellaneous income | No |
| Profit Before Tax | currency | Calculated or entered | Yes |
| Tax Expense | currency | Tax paid/provided | No |
| Profit After Tax | currency | Calculated or entered | No |

**Balance Sheet Section**

| Field | Type | Description | Required |
|-------|------|-------------|----------|
| Total Assets | currency | All assets | Yes |
| Fixed Assets | currency | Non-current assets | Yes |
| Current Assets | currency | Short-term assets | No |
| Total Liabilities | currency | All liabilities | Yes |
| Long-term Liabilities | currency | Non-current liabilities | No |
| Current Liabilities | currency | Short-term liabilities | No |
| Share Capital | currency | Issued share capital | No |
| Retained Earnings | currency | Accumulated profits | No |
| Total Equity | currency | Net assets | Yes |

**Employment Section**

| Field | Type | Description | Required |
|-------|------|-------------|----------|
| Number of Employees | integer | Full-time equivalent | Yes |
| Total Payroll Cost | currency | All employee costs | Yes |
| Total PAYE Remitted | currency | PAYE deducted and remitted | No |
| Pension Contributions | currency | Employer pension | No |
| NHF Contributions | currency | Housing fund | No |
| NSITF Contributions | currency | Insurance trust fund | No |
| ITF Contributions | currency | Industrial training | No |

**VAT Section**

| Field | Type | Description | Required |
|-------|------|-------------|----------|
| VATable Sales | currency | Sales subject to VAT | Yes |
| VAT Output | currency | VAT charged on sales | Yes |
| Exempt Sales | currency | VAT exempt sales | No |
| Zero-Rated Sales | currency | Zero-rated sales | No |
| VATable Purchases | currency | Purchases with VAT | Yes |
| VAT Input (Goods) | currency | Input VAT on goods | Yes |
| VAT Input (Services) | currency | Input VAT on services | Yes |
| VAT Input (Capital) | currency | Input VAT on capital items | No |
| VAT Remitted | currency | Net VAT paid to NRS | No |

**Capital Transactions Section**

| Field | Type | Description | Required |
|-------|------|-------------|----------|
| Asset Disposals | currency | Proceeds from asset sales | No |
| Asset Disposal Costs | currency | Cost of assets sold | No |
| Capital Gains Realized | currency | Net capital gains | No |
| Capital Allowances Claimed | currency | Tax depreciation | No |

### 2.3 Individual Financial Data

**Employment Income Section**

| Field | Type | Description | Required |
|-------|------|-------------|----------|
| Gross Salary | currency | Annual gross salary | Yes |
| Bonuses | currency | Annual bonuses | No |
| Benefits in Kind | currency | Non-cash benefits | No |
| Employer Pension Contribution | currency | Employer's contribution | No |
| Employee Pension Contribution | currency | Employee's contribution | No |
| PAYE Deducted | currency | Tax withheld by employer | No |

**Other Income Section**

| Field | Type | Description | Required |
|-------|------|-------------|----------|
| Rental Income | currency | Gross rental income | No |
| Rental Expenses | currency | Allowable expenses | No |
| Interest Income | currency | Interest received | No |
| Dividend Income | currency | Dividends received | No |
| Business Income | currency | Self-employment income | No |
| Business Expenses | currency | Business costs | No |
| Other Income | currency | Any other income | No |

**Relief-Related Data**

| Field | Type | Description | Required |
|-------|------|-------------|----------|
| Rent Paid | currency | Annual rent for housing | No |
| Life Insurance Premium | currency | Life insurance paid | No |
| Mortgage Interest | currency | Interest on home loan | No |
| Number of Dependents | integer | Children under 18 | No |
| Disability Status | boolean | Registered disability | No |
| Medical Expenses | currency | Unreimbursed medical | No |

**Capital Transactions**

| Field | Type | Description | Required |
|-------|------|-------------|----------|
| Asset Disposals | currency | Sale of shares, property | No |
| Acquisition Costs | currency | Original cost + improvements | No |
| Holding Period | months | How long held | No |

### 2.4 Partnership Financial Data

Similar structure to company data, with additions:

| Field | Type | Description | Required |
|-------|------|-------------|----------|
| Number of Partners | integer | Total partners | Yes |
| Partner Profit Shares | json | Allocation percentages | Yes |
| Partner Capital Accounts | json | Individual capital | No |
| Partner Drawings | json | Amounts withdrawn | No |

---

## 3. Data Entry Interface

### 3.1 Financials Tab Layout

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Financials                                                              │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  Fiscal Year: [2024 ▼]  [+ Add Year]               Source: [Audited ▼]  │
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ [Income] [Balance Sheet] [Employment] [VAT] [Capital] [Summary]    ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │                                                                      ││
│  │   INCOME STATEMENT                               Data Quality: 85%  ││
│  │                                                                      ││
│  │   Revenue / Turnover *                     ₦ [____________]         ││
│  │   ℹ️ Total sales and service revenue                                 ││
│  │                                                                      ││
│  │   Cost of Sales                            ₦ [____________]         ││
│  │   ℹ️ Direct costs of goods/services sold                             ││
│  │                                                                      ││
│  │   Gross Profit *                           ₦ [____________]  🔄     ││
│  │   ℹ️ Revenue minus cost of sales                                     ││
│  │                                                                      ││
│  │   ...                                                                ││
│  │                                                                      ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  [Save Draft]                                    [Save & Continue ▶]    │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 3.2 Input Features

**Field Components:**
- Label with required indicator (*)
- Info tooltip with field description
- Currency input with ₦ prefix
- Thousand separators auto-formatted
- Validation feedback inline

**Calculated Fields:**
- Auto-calculate option (🔄 icon)
- Can override if actual differs
- Show formula used

**Data Source Selector:**
- Audited Financial Statements
- Management Accounts
- Tax Computations
- Projections/Estimates
- Prior Year Adjusted

**Progress Indicator:**
- Data quality percentage
- Missing required fields highlighted
- Completeness by section

### 3.3 Guided Entry Mode

For users unfamiliar with financial data:

**Step-by-Step Wizard:**
1. Income basics (revenue, profit)
2. Costs breakdown
3. Balance sheet summary
4. Employee information
5. VAT data
6. Review and save

Each step has:
- Contextual help
- Examples
- Links to knowledge base

### 3.4 Validation Rules

**Cross-Field Validation:**

| Rule | Description |
|------|-------------|
| Gross Profit = Revenue - Cost of Sales | If both entered, should match |
| Total Assets = Total Liabilities + Equity | Balance sheet balance |
| VAT Output ≈ VATable Sales × VAT Rate | Within tolerance |
| PAYE ≤ Gross Payroll | Cannot exceed payroll |

**Reasonableness Checks:**

| Check | Warning Threshold |
|-------|-------------------|
| Gross Margin | < 0% or > 90% |
| Net Margin | < -50% or > 50% |
| Asset/Revenue Ratio | Outside industry norms |
| Employee Cost Ratio | Outside 20-80% of revenue |

**Warning Display:**
- Yellow highlight for questionable values
- Explanation of concern
- "Confirm" button to acknowledge
- Entry allowed after confirmation

---

## 4. Historical Data Management

### 4.1 Multiple Years

Support for multiple fiscal years:
- Current year for analysis
- Prior years for comparison
- Historical data for trend analysis

**Year Navigation:**
- Dropdown to switch years
- Quick compare view
- Copy from prior year option

### 4.2 Copy From Prior Year

When adding new fiscal year:
- Option to copy prior year data
- Adjustable copy (percentage increase/decrease)
- Clear starting point

### 4.3 Year-over-Year Comparison

Side-by-side view:
- Two years compared
- Variance calculated
- Percentage change shown
- Significant changes highlighted

---

## 5. Data Import

### 5.1 Import from File

**Supported Formats:**
- Excel template (XLSX)
- CSV with specific format
- PDF extraction (future)

**Process:**
1. Download template for entity type
2. Fill in data
3. Upload file
4. Review mapped data
5. Confirm import

### 5.2 Import from Accounting Software (Future)

Planned integrations:
- Sage
- QuickBooks
- Zoho Books

API-based import with:
- OAuth connection
- Field mapping
- Scheduled sync option

### 5.3 Bulk Financial Import

For importing financials for multiple clients:
- Multi-client template
- Client identifier column
- Validation per client
- Batch processing

---

## 6. Data Versioning

### 6.1 Version Control

Each save creates a version:
- Version number
- Timestamp
- User who saved
- Change summary

**Version History Table:**

| Version | Date | User | Changes | Actions |
|---------|------|------|---------|---------|
| v3 | 2024-12-15 | Sarah | Updated VAT data | View, Restore |
| v2 | 2024-12-10 | John | Corrected revenue | View, Restore |
| v1 | 2024-12-05 | John | Initial entry | View, Restore |

### 6.2 Restore Previous Version

- Select version to restore
- Preview differences
- Confirm restoration
- Creates new version (not destructive)

### 6.3 Lock Financial Data

After calculation:
- Option to lock data
- Prevents accidental changes
- Unlock requires confirmation
- Audit trail for lock/unlock

---

## 7. Calculation Readiness

### 7.1 Readiness Check

Before calculation, verify:
- All required fields populated
- No validation errors
- Data source identified
- No unconfirmed warnings

**Readiness Display:**
```
✓ Required fields complete (15/15)
✓ Validation passed
✓ Data source: Audited Accounts
⚠ 2 warnings acknowledged

[Ready for Calculation]
```

### 7.2 Missing Data Handling

If optional data missing:
- Calculation proceeds with defaults
- Assumptions documented
- Results marked as estimates

If required data missing:
- Calculation blocked
- Missing fields highlighted
- Guidance to complete

---

## 8. Data Model

### 8.1 Client Financials Table

**Table: client_financials**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| tax_client_id | ULID | Parent client |
| fiscal_year | integer | Year (e.g., 2024) |
| fiscal_year_start | date | Period start date |
| fiscal_year_end | date | Period end date |
| data_source | enum | audited, management, computed, projected |
| income_data | jsonb | Income statement fields |
| balance_sheet_data | jsonb | Balance sheet fields |
| employment_data | jsonb | Employee/payroll fields |
| vat_data | jsonb | VAT-related fields |
| capital_data | jsonb | Capital transaction fields |
| individual_data | jsonb | Individual-specific (if applicable) |
| is_complete | boolean | All required fields present |
| is_locked | boolean | Locked for changes |
| locked_at | timestamp | When locked |
| locked_by | ULID | User who locked |
| version | integer | Current version number |
| verified_by | ULID | User who verified |
| verified_at | timestamp | When verified |
| notes | text | Internal notes |
| created_by | ULID | User who created |
| created_at | timestamp | Creation date |
| updated_at | timestamp | Last modified |

### 8.2 Financial Versions Table

**Table: client_financial_versions**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| client_financial_id | ULID | Parent financial record |
| version | integer | Version number |
| data_snapshot | jsonb | Complete data at this version |
| change_summary | text | Description of changes |
| created_by | ULID | User who saved |
| created_at | timestamp | When saved |

### 8.3 JSON Data Structures

**Income Data Example:**
```json
{
  "turnover": 150000000,
  "cost_of_sales": 90000000,
  "gross_profit": 60000000,
  "operating_expenses": 25000000,
  "administrative_expenses": 10000000,
  "depreciation": 5000000,
  "interest_income": 500000,
  "interest_expense": 2000000,
  "other_income": 1000000,
  "profit_before_tax": 19500000,
  "tax_expense": 5850000,
  "profit_after_tax": 13650000
}
```

**Employment Data Example:**
```json
{
  "employee_count": 45,
  "total_payroll": 36000000,
  "paye_remitted": 7200000,
  "pension_employer": 2880000,
  "pension_employee": 2880000,
  "nhf_contributions": 900000,
  "nsitf_contributions": 360000,
  "itf_contributions": 360000
}
```

---

## 9. API Endpoints

### 9.1 Financial Data Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/clients/{id}/financials | List all fiscal years |
| GET | /api/clients/{id}/financials/{year} | Get specific year |
| POST | /api/clients/{id}/financials | Create new fiscal year |
| PUT | /api/clients/{id}/financials/{year} | Update financial data |
| DELETE | /api/clients/{id}/financials/{year} | Delete fiscal year |
| POST | /api/clients/{id}/financials/{year}/lock | Lock data |
| POST | /api/clients/{id}/financials/{year}/unlock | Unlock data |

### 9.2 Version Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/clients/{id}/financials/{year}/versions | List versions |
| GET | /api/clients/{id}/financials/{year}/versions/{v} | Get version |
| POST | /api/clients/{id}/financials/{year}/restore/{v} | Restore version |

### 9.3 Import Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/financials/template/{entityType} | Download template |
| POST | /api/clients/{id}/financials/import | Import from file |

---

## 10. Implementation Checklist

### Phase 1: Core Entry

- [ ] Financial data model and migrations
- [ ] Basic entry form for companies
- [ ] Required field validation
- [ ] Save and retrieve functionality
- [ ] Year selector and management

### Phase 2: Enhanced Entry

- [ ] Individual financial data forms
- [ ] Partnership financial data forms
- [ ] Calculated fields
- [ ] Cross-field validation
- [ ] Reasonableness warnings

### Phase 3: Advanced Features

- [ ] Data versioning
- [ ] Lock/unlock functionality
- [ ] Import from Excel
- [ ] Year-over-year comparison
- [ ] Guided entry wizard

### Phase 4: Integration

- [ ] Readiness check integration
- [ ] Calculation trigger
- [ ] Accounting software import (future)

---

*This document should be updated as financial data entry requirements evolve during development.*
