# TaxLab — Scenario Modeling

## Document Information

| Item | Detail |
|------|--------|
| Document | Scenario Modeling Module |
| Version | 1.0 |
| Last Updated | December 2024 |
| Audience | Developers, Product Team |

---

## 1. Overview

Scenario Modeling enables practitioners to explore "what-if" questions for their clients. By adjusting parameters and instantly seeing tax implications, practitioners can provide strategic advice and help clients make informed decisions.

### 1.1 Access

- **URL:** `/app/clients/{id}/scenarios`
- **Guard:** firm
- **Roles:** Partner, Manager, Associate (assigned clients), Viewer (read-only)

### 1.2 Value Proposition

**For Practitioners:**
- Demonstrate value beyond compliance
- Support advisory conversations
- Justify restructuring recommendations

**For Clients:**
- Understand decision implications
- Compare strategic options
- Plan with confidence

---

## 2. Scenario Types

### 2.1 Revenue Scenarios

Model changes to top-line revenue:

| Parameter | Description |
|-----------|-------------|
| Revenue Growth/Decline | % change from baseline |
| Revenue Threshold | Target specific revenue level |
| Revenue Mix | Shift between VATable/exempt |

**Use Cases:**
- "What if revenue grows 20% next year?"
- "What if we lose our biggest client?"
- "What if we increase exempt services?"

### 2.2 Profitability Scenarios

Model changes to margins and costs:

| Parameter | Description |
|-----------|-------------|
| Gross Margin Change | % point change |
| Operating Cost Change | % change |
| Specific Cost Adjustment | Modify individual costs |

**Use Cases:**
- "What if we improve margins by 5%?"
- "What if we hire 10 more staff?"
- "What if raw material costs increase?"

### 2.3 Structural Scenarios

Model business structure changes:

| Parameter | Description |
|-----------|-------------|
| Company Split | Divide into multiple entities |
| Merger/Consolidation | Combine entities |
| Entity Type Change | Individual to company, etc. |

**Use Cases:**
- "What if we split into two companies?"
- "What if we incorporate our sole proprietorship?"
- "What if we merge our subsidiaries?"

### 2.4 Timing Scenarios

Model timing of transactions:

| Parameter | Description |
|-----------|-------------|
| Income Deferral | Move income to next period |
| Expense Acceleration | Move expenses to current period |
| Asset Disposal Timing | Change when assets sold |

**Use Cases:**
- "What if we defer this contract to January?"
- "What if we accelerate equipment purchases?"
- "What if we sell the property next year?"

### 2.5 Investment Scenarios

Model capital decisions:

| Parameter | Description |
|-----------|-------------|
| Capital Expenditure | New asset purchases |
| Asset Disposals | Sale of existing assets |
| Investment Income | Changes to investment returns |

**Use Cases:**
- "What if we buy new machinery for ₦50M?"
- "What if we sell our investment property?"
- "What if dividends increase?"

---

## 3. Scenario Builder Interface

### 3.1 Create Scenario Flow

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Create New Scenario                                                     │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  Scenario Name: [Revenue Growth 20%                    ]                │
│                                                                          │
│  Base Calculation: [FY 2024 Full Analysis - Dec 15 ▼]                   │
│                                                                          │
│  ─────────────────────────────────────────────────────────────────────  │
│                                                                          │
│  ADJUSTMENTS                                                 [+ Add]    │
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ 📈 Revenue Change                                              [×] ││
│  │                                                                     ││
│  │ Adjustment Type: [Percentage Change ▼]                              ││
│  │ Value: [+20] %                                                      ││
│  │                                                                     ││
│  │ Applied to: ○ Total Revenue  ○ VATable Only  ○ Exempt Only         ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ 💰 Cost Adjustment                                             [×] ││
│  │                                                                     ││
│  │ Adjustment Type: [Proportional to Revenue ▼]                        ││
│  │ Variable Cost Ratio: [60] %                                         ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│                               [Cancel]  [Preview Results]  [Save]       │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 3.2 Adjustment Types

**Revenue Adjustments:**
- Percentage change (+/-)
- Absolute change (+/- ₦)
- Set specific value

**Cost Adjustments:**
- Percentage change
- Absolute change
- Proportional to revenue
- Fixed increase

**Employee Adjustments:**
- Headcount change
- Salary level change
- New hires with cost

**Asset Adjustments:**
- New purchase (with capital allowances)
- Disposal (with CGT implications)
- Revaluation

### 3.3 Parameter Constraints

**Validation Rules:**
- Revenue cannot be negative
- Costs cannot exceed revenue by unreasonable margin
- Employee count minimum 0
- Tax rates within legal bounds

**Warnings:**
- Unrealistic growth rates (>100%)
- Negative profitability
- Threshold proximity alerts

---

## 4. Scenario Calculation

### 4.1 Calculation Process

```
Load Base Calculation
         │
         ▼
┌─────────────────────┐
│ Apply Adjustments   │
│ to Financial Data   │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ Recalculate Derived │
│ Values              │
│ • Gross Profit      │
│ • Net Profit        │
│ • Taxable Income    │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ Run Tax             │
│ Calculation         │
│ (Old + New Regime)  │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ Compare to Base     │
│ Calculate Deltas    │
└──────────┬──────────┘
           │
           ▼
    Scenario Results
```

### 4.2 Derived Value Recalculation

When inputs change, recalculate:
- Gross profit (if revenue or COGS changed)
- Operating profit
- Profit before tax
- Capital allowances (if assets changed)
- VAT position (if sales mix changed)

### 4.3 Threshold Impact Detection

Automatically detect if scenario crosses thresholds:
- Small company threshold (₦50M)
- Medium company threshold (₦100M)
- VAT registration threshold
- PAYE registration requirements

---

## 5. Results Display

### 5.1 Scenario Results View

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Scenario: Revenue Growth 20%                                            │
│  Base: FY 2024 Full Analysis                                            │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌─────────────────────────────┐  ┌─────────────────────────────┐       │
│  │  BASE CASE                  │  │  SCENARIO                   │       │
│  │  Revenue: ₦150,000,000      │  │  Revenue: ₦180,000,000      │       │
│  │  Profit: ₦20,000,000        │  │  Profit: ₦28,000,000        │       │
│  │  Tax (New): ₦11,595,000     │  │  Tax (New): ₦14,400,000     │       │
│  └─────────────────────────────┘  └─────────────────────────────┘       │
│                                                                          │
│                        IMPACT ANALYSIS                                   │
│                                                                          │
│  Revenue Increase:     +₦30,000,000 (+20%)                              │
│  Profit Increase:      +₦8,000,000 (+40%)                               │
│  Tax Increase (New):   +₦2,805,000 (+24.2%)                             │
│                                                                          │
│  Effective Tax Rate:   Base: 57.98%  →  Scenario: 51.43%                │
│                        (Improved due to higher profit margin)            │
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │                     [Comparison Chart]                              ││
│  │   Base    ████████████░░░░░░░░░░░░  ₦11.6M                         ││
│  │   Scenario ████████████████░░░░░░░░  ₦14.4M                         ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  KEY INSIGHTS                                                            │
│  • Higher revenue increases CIT liability proportionally                 │
│  • Improved effective rate due to economies of scale                    │
│  • Still within Large Company classification                            │
│                                                                          │
│  [Edit Scenario]  [Duplicate]  [Generate Report]  [Share to Portal]    │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 5.2 Comparison View

Side-by-side comparison of multiple scenarios:

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Compare Scenarios                                                       │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌───────────┬───────────┬───────────┬───────────┬───────────┐          │
│  │           │   Base    │Growth 10% │Growth 20% │Growth 30% │          │
│  ├───────────┼───────────┼───────────┼───────────┼───────────┤          │
│  │ Revenue   │  ₦150M    │  ₦165M    │  ₦180M    │  ₦195M    │          │
│  │ Profit    │  ₦20M     │  ₦24M     │  ₦28M     │  ₦32M     │          │
│  │ CIT       │  ₦4.5M    │  ₦5.4M    │  ₦6.3M    │  ₦7.2M    │          │
│  │ VAT       │  ₦2.6M    │  ₦3.0M    │  ₦3.4M    │  ₦3.8M    │          │
│  │ Total Tax │  ₦11.6M   │  ₦12.9M   │  ₦14.4M   │  ₦15.8M   │          │
│  │ Eff. Rate │  58.0%    │  53.8%    │  51.4%    │  49.4%    │          │
│  └───────────┴───────────┴───────────┴───────────┴───────────┘          │
│                                                                          │
│  [Line chart showing tax vs. revenue relationship]                       │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 6. Advanced Scenarios

### 6.1 Multi-Parameter Scenarios

Combine multiple adjustments:

**Example: "Expansion Scenario"**
- Revenue +30%
- New equipment purchase ₦25M
- 15 new employees
- Operating costs +20%

System calculates compound effects:
- Higher revenue → more tax
- Capital allowances → tax reduction
- Higher payroll → PAYE increase, but more deductions
- Net effect computed

### 6.2 Sensitivity Analysis

Automatic sensitivity testing:

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Sensitivity Analysis: Revenue Impact                                    │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  Revenue Change    Tax Impact    Threshold Crossed?                      │
│  ───────────────────────────────────────────────────                    │
│  -20%              -₦3.2M        No                                      │
│  -10%              -₦1.6M        No                                      │
│  Baseline          ₦0            —                                       │
│  +10%              +₦1.4M        No                                      │
│  +20%              +₦2.8M        No                                      │
│  +30%              +₦4.2M        No                                      │
│                                                                          │
│  [Sensitivity Chart]                                                     │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 6.3 Threshold Analysis

Special scenario for threshold proximity:

**Small Company Threshold Scenario:**
- Current turnover: ₦48M
- Threshold: ₦50M
- Show impact of crossing threshold

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Threshold Analysis: Small Company Status                               │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  Current Turnover: ₦48,000,000                                          │
│  Threshold: ₦50,000,000                                                 │
│  Headroom: ₦2,000,000                                                   │
│                                                                          │
│  IF TURNOVER STAYS BELOW ₦50M:                                          │
│  • CIT Rate: 0%                                                         │
│  • CIT Liability: ₦0                                                    │
│                                                                          │
│  IF TURNOVER EXCEEDS ₦50M (e.g., ₦52M):                                 │
│  • CIT Rate: 20% (Medium Company)                                       │
│  • Estimated CIT: ₦1,200,000                                            │
│                                                                          │
│  ⚠️ RECOMMENDATION                                                       │
│  Consider deferring ₦2M+ revenue to next fiscal year to maintain        │
│  Small Company status and avoid ₦1.2M+ in CIT.                          │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 7. Scenario Management

### 7.1 Scenario List

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Scenarios for ABC Trading Limited                    [+ New Scenario]  │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ Name              Base          Created      Status     Actions     ││
│  ├─────────────────────────────────────────────────────────────────────┤│
│  │ Revenue +20%      FY2024        Dec 15       Draft      ⋮           ││
│  │ Expansion Plan    FY2024        Dec 12       Shared     ⋮           ││
│  │ Cost Reduction    FY2024        Dec 10       Draft      ⋮           ││
│  │ Company Split     FY2024        Dec 8        Archived   ⋮           ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 7.2 Scenario Status

| Status | Description |
|--------|-------------|
| Draft | Work in progress, not shared |
| Active | Finalized, available for reports |
| Shared | Published to client portal |
| Archived | Historical, no longer active |

### 7.3 Scenario Actions

| Action | Description |
|--------|-------------|
| Edit | Modify parameters |
| Duplicate | Copy to new scenario |
| Compare | Side-by-side comparison |
| Generate Report | Include in report |
| Share to Portal | Make visible to client |
| Archive | Move to archived status |
| Delete | Remove scenario |

---

## 8. Client Portal Integration

### 8.1 Sharing Scenarios

Practitioners can share specific scenarios to the client portal:

1. Select scenario
2. Click "Share to Portal"
3. Choose what to show:
   - Summary only
   - Detailed breakdown
   - Comparison view
4. Add practitioner notes
5. Confirm sharing

### 8.2 Client View

What clients see:
- Scenario name and description
- Key metrics comparison
- Visual charts
- Practitioner recommendations
- Read-only (cannot edit)

What clients don't see:
- Detailed calculation mechanics
- Internal notes
- Draft scenarios
- Optimization suggestions (unless shared)

---

## 9. Data Model

### 9.1 Scenarios Table

**Table: scenarios**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| tax_client_id | ULID | Parent client |
| firm_id | ULID | Tenant (denormalized) |
| base_calculation_id | ULID | Source calculation |
| name | varchar(255) | Scenario name |
| description | text | Scenario description |
| parameters | jsonb | Adjustment parameters |
| results | jsonb | Calculated results |
| comparison | jsonb | Base vs. scenario comparison |
| status | enum | draft, active, shared, archived |
| shared_at | timestamp | When shared to portal |
| shared_by | ULID | User who shared |
| portal_config | jsonb | What's visible in portal |
| created_by | ULID | Creator |
| created_at | timestamp | Creation date |
| updated_at | timestamp | Last modified |

### 9.2 Parameters JSON Structure

```json
{
  "adjustments": [
    {
      "type": "revenue_change",
      "method": "percentage",
      "value": 20,
      "applies_to": "total"
    },
    {
      "type": "cost_change",
      "method": "proportional",
      "variable_ratio": 60,
      "fixed_change": 0
    },
    {
      "type": "asset_purchase",
      "asset_class": "plant_machinery",
      "value": 25000000
    }
  ],
  "notes": "Growth scenario assuming expansion into new market"
}
```

### 9.3 Results JSON Structure

```json
{
  "adjusted_financials": {
    "revenue": 180000000,
    "cost_of_sales": 108000000,
    "gross_profit": 72000000,
    "profit_before_tax": 28000000
  },
  "old_regime": {
    "cit": 7560000,
    "vat": 6300000,
    "total": 18900000
  },
  "new_regime": {
    "cit": 6300000,
    "vat": 3150000,
    "total": 14400000
  },
  "comparison_to_base": {
    "revenue_change": 30000000,
    "profit_change": 8000000,
    "tax_change_old": 6145567,
    "tax_change_new": 2805000
  }
}
```

---

## 10. API Endpoints

### 10.1 Scenario Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/clients/{id}/scenarios | List scenarios |
| POST | /api/clients/{id}/scenarios | Create scenario |
| GET | /api/clients/{id}/scenarios/{sid} | Get scenario |
| PUT | /api/clients/{id}/scenarios/{sid} | Update scenario |
| DELETE | /api/clients/{id}/scenarios/{sid} | Delete scenario |
| POST | /api/clients/{id}/scenarios/{sid}/calculate | Recalculate |
| POST | /api/clients/{id}/scenarios/{sid}/share | Share to portal |
| POST | /api/clients/{id}/scenarios/{sid}/duplicate | Duplicate |

### 10.2 Comparison Endpoint

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /api/clients/{id}/scenarios/compare | Compare multiple |

Request body:
```json
{
  "scenario_ids": ["sid1", "sid2", "sid3"],
  "include_base": true
}
```

---

## 11. Implementation Checklist

### Phase 1: Basic Scenarios

- [ ] Scenario model and migrations
- [ ] Create scenario from calculation
- [ ] Revenue adjustment parameter
- [ ] Cost adjustment parameter
- [ ] Scenario calculation engine
- [ ] Basic results display

### Phase 2: Advanced Scenarios

- [ ] Multi-parameter scenarios
- [ ] Asset purchase adjustments
- [ ] Employee adjustments
- [ ] Structural scenarios
- [ ] Threshold analysis

### Phase 3: Enhanced Features

- [ ] Scenario comparison view
- [ ] Sensitivity analysis
- [ ] Portal sharing
- [ ] Scenario management (status, archive)

### Phase 4: Polish

- [ ] Visualization improvements
- [ ] Guided scenario builder
- [ ] Scenario templates
- [ ] Batch scenarios

---

*This document should be updated as scenario modeling requirements evolve during development.*
