# TaxLab — Optimization Tracker

## Document Information

| Item | Detail |
|------|--------|
| Document | Optimization Tracker Module |
| Version | 1.0 |
| Last Updated | December 2024 |
| Audience | Developers, Product Team |

---

## 1. Overview

The Optimization Tracker helps practitioners identify, track, and implement tax-saving opportunities for their clients. It transforms tax analysis from a compliance exercise into a value-adding advisory service.

### 1.1 Access

- **URL:** `/app/clients/{id}/optimizations`
- **Guard:** firm
- **Roles:** Partner, Manager, Associate (assigned clients), Viewer (read-only)

### 1.2 Value Proposition

**For Practitioners:**
- Identify advisory opportunities
- Track implementation progress
- Demonstrate value to clients
- Upsell advisory services

**For Clients:**
- Clear savings opportunities
- Actionable recommendations
- Progress visibility
- Measurable results

---

## 2. Optimization Types

### 2.1 Timing Optimizations

Opportunities related to when transactions occur:

| Type | Description | Example |
|------|-------------|---------|
| Income Deferral | Move income to lower-rate period | Defer invoice to next fiscal year |
| Expense Acceleration | Bring forward deductible expenses | Prepay allowable expenses |
| Asset Disposal Timing | Optimize CGT by timing sales | Sell asset after year-end |
| Payment Timing | Optimize cash flow vs. deductions | Time pension contributions |

### 2.2 Structural Optimizations

Opportunities related to business structure:

| Type | Description | Example |
|------|-------------|---------|
| Entity Restructuring | Change legal structure | Convert sole prop to company |
| Company Split | Separate operations | Create subsidiary for new business |
| Group Relief | Utilize group losses | Transfer losses between entities |
| Holding Structure | Optimize dividend flows | Create holding company |

### 2.3 Classification Optimizations

Opportunities related to tax classifications:

| Type | Description | Example |
|------|-------------|---------|
| Small Company Status | Stay below threshold | Monitor turnover carefully |
| SME Incentives | Qualify for reliefs | Apply for pioneer status |
| Exempt Income | Reclassify income types | Structure as tax-exempt |
| Zero-Rating | Qualify for VAT zero-rate | Export documentation |

### 2.4 Compliance Optimizations

Opportunities from better compliance:

| Type | Description | Example |
|------|-------------|---------|
| Unclaimed Allowances | Claim missed deductions | Review capital allowances |
| VAT Recovery | Recover missed input VAT | Audit supplier invoices |
| Relief Claims | File for available reliefs | R&D tax credits |
| Correct Classification | Fix misclassifications | Reclassify expenses |

---

## 3. Optimization Detection

### 3.1 Automatic Detection

System analyzes calculations to identify opportunities:

```
Calculation Complete
        │
        ▼
┌───────────────────────┐
│ Optimization Analyzer │
└───────────┬───────────┘
            │
    ┌───────┼───────┬───────────┐
    ▼       ▼       ▼           ▼
┌───────┐ ┌───────┐ ┌───────┐ ┌───────┐
│Timing │ │Struct.│ │Class. │ │Compli.│
│Rules  │ │Rules  │ │Rules  │ │Rules  │
└───┬───┘ └───┬───┘ └───┬───┘ └───┬───┘
    │         │         │         │
    └─────────┴─────────┴─────────┘
                    │
                    ▼
           Optimizations Found
```

### 3.2 Detection Rules

**Threshold Proximity:**
```
IF turnover >= (small_company_threshold * 0.8)
AND turnover < small_company_threshold
THEN flag "Approaching Small Company Threshold"
```

**VAT Recovery Gap:**
```
IF input_vat_services > 0
AND regime = 'old'
THEN flag "VAT on Services Now Recoverable"
     estimated_savings = input_vat_services
```

**Capital Allowance Review:**
```
IF depreciation > claimed_capital_allowances
THEN flag "Potential Unclaimed Capital Allowances"
     estimated_savings = (depreciation - CA) * tax_rate
```

**CGT Timing:**
```
IF capital_gains > 0
AND effective_date > fiscal_year_end
THEN flag "Consider Disposal Timing"
     estimated_impact = gains * (new_rate - old_rate)
```

### 3.3 Detection Output

Each detected optimization includes:
- Type and category
- Description
- Estimated impact (savings or cost)
- Confidence level
- Data points that triggered detection
- Recommended actions

---

## 4. User Interface

### 4.1 Optimization Dashboard

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Optimizations                                                           │
│  ABC Trading Limited                                                     │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌────────────────────────────────────────────────────────────────────┐ │
│  │ POTENTIAL SAVINGS                                                   │ │
│  │                                                                     │ │
│  │ ₦3,450,000          ₦1,200,000         ₦2,250,000                  │ │
│  │ Total Identified    Implemented        Remaining                    │ │
│  └────────────────────────────────────────────────────────────────────┘ │
│                                                                          │
│  Filter: [All ▼]  Status: [All ▼]  Priority: [All ▼]    [+ Add Manual] │
│                                                                          │
│  ┌────────────────────────────────────────────────────────────────────┐ │
│  │ HIGH PRIORITY                                                       │ │
│  ├────────────────────────────────────────────────────────────────────┤ │
│  │ ⚡ VAT Input Recovery on Services                                   │ │
│  │    Est. Savings: ₦1,875,000 | Status: Identified                   │ │
│  │    Review supplier invoices for VAT on professional services        │ │
│  │    [View Details] [Start Implementation]                            │ │
│  ├────────────────────────────────────────────────────────────────────┤ │
│  │ ⚡ Small Company Threshold Management                               │ │
│  │    Est. Savings: ₦1,200,000 | Status: In Progress                  │ │
│  │    Current turnover ₦48M, threshold ₦50M                            │ │
│  │    [View Details] [Update Progress]                                 │ │
│  └────────────────────────────────────────────────────────────────────┘ │
│                                                                          │
│  ┌────────────────────────────────────────────────────────────────────┐ │
│  │ MEDIUM PRIORITY                                                     │ │
│  ├────────────────────────────────────────────────────────────────────┤ │
│  │ 📊 Capital Allowances Review                                        │ │
│  │    Est. Savings: ₦375,000 | Status: Identified                     │ │
│  │    ...                                                              │ │
│  └────────────────────────────────────────────────────────────────────┘ │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 4.2 Optimization Detail View

```
┌─────────────────────────────────────────────────────────────────────────┐
│  VAT Input Recovery on Services                                          │
│  High Priority | Identified                                              │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  SUMMARY                                                                 │
│                                                                          │
│  Under the old VAT regime, input VAT on services was not recoverable.   │
│  NTA 2025 now allows recovery of VAT paid on professional services,     │
│  consulting fees, and other service inputs.                             │
│                                                                          │
│  ESTIMATED IMPACT                                                        │
│                                                                          │
│  ┌──────────────────────┬──────────────────────────────────────────────┐│
│  │ VAT on Services      │ ₦25,000,000 annual service purchases         ││
│  │ VAT Rate             │ 7.5%                                         ││
│  │ Recoverable VAT      │ ₦1,875,000                                   ││
│  │ Confidence           │ High (based on financial data)               ││
│  └──────────────────────┴──────────────────────────────────────────────┘│
│                                                                          │
│  RECOMMENDED ACTIONS                                                     │
│                                                                          │
│  1. Review all service supplier invoices from current period            │
│  2. Ensure VAT is properly documented on invoices                       │
│  3. Update VAT returns to claim input VAT on services                   │
│  4. Establish process for ongoing service VAT recovery                  │
│                                                                          │
│  DETECTION BASIS                                                         │
│                                                                          │
│  • Calculation: FY 2024 Full Analysis (Dec 15)                          │
│  • Data Point: Operating expenses include ₦25M in services              │
│  • Rule: vat_service_recovery_nta2025                                   │
│                                                                          │
│  ─────────────────────────────────────────────────────────────────────  │
│                                                                          │
│  STATUS HISTORY                                                          │
│                                                                          │
│  Dec 15, 2024 - Identified automatically from calculation               │
│                                                                          │
│  NOTES                                                                   │
│  [Add internal notes about this optimization...]                        │
│                                                                          │
│  [Mark as In Progress]  [Mark as Implemented]  [Dismiss]                │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 5. Optimization Lifecycle

### 5.1 Status Flow

```
     ┌─────────────┐
     │ Identified  │ ◄──── Automatic detection or manual entry
     └──────┬──────┘
            │
            │ User starts working on it
            ▼
     ┌─────────────┐
     │ In Progress │ ◄──── Actively being implemented
     └──────┬──────┘
            │
    ┌───────┴───────┐
    │               │
    ▼               ▼
┌─────────┐   ┌──────────┐
│Implement│   │ Dismissed│ ◄── Not viable or not pursued
│   ed    │   └──────────┘
└────┬────┘
     │
     │ Verified in next calculation
     ▼
┌─────────────┐
│  Verified   │ ◄──── Savings confirmed in subsequent period
└─────────────┘
```

### 5.2 Status Definitions

| Status | Description |
|--------|-------------|
| Identified | Newly detected, not yet acted upon |
| In Progress | Being actively implemented |
| Implemented | Actions completed, awaiting verification |
| Verified | Savings confirmed in subsequent calculation |
| Dismissed | Deemed not viable, with reason documented |

### 5.3 Status Transitions

**Identified → In Progress:**
- User clicks "Start Implementation"
- Optional: Add target completion date
- Optional: Assign to team member

**In Progress → Implemented:**
- User clicks "Mark as Implemented"
- Required: Add implementation notes
- Optional: Record actual vs. estimated savings

**Implemented → Verified:**
- Automatic: Next period calculation confirms savings
- Or manual: User verifies with evidence

**Any → Dismissed:**
- User clicks "Dismiss"
- Required: Reason for dismissal
- Retains in history for audit

---

## 6. Manual Optimizations

### 6.1 Adding Manual Optimization

Practitioners can add optimizations not automatically detected:

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Add Optimization                                                        │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  Type: [Timing ▼]                                                       │
│                                                                          │
│  Title: [Invoice Deferral for Q4 Sales                    ]             │
│                                                                          │
│  Description:                                                            │
│  [Defer ₦5M contract invoice to January to reduce current year CIT   ]  │
│                                                                          │
│  Estimated Savings: ₦ [1,250,000        ]                               │
│                                                                          │
│  Priority: ○ High  ● Medium  ○ Low                                      │
│                                                                          │
│  Recommended Actions:                                                    │
│  [+ Add Action]                                                         │
│  1. [Review contract terms for flexibility                         ] [×]│
│  2. [Discuss with client operations team                           ] [×]│
│  3. [Prepare revised invoice schedule                              ] [×]│
│                                                                          │
│                                              [Cancel]  [Save]           │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 6.2 Manual vs. Automatic

| Aspect | Automatic | Manual |
|--------|-----------|--------|
| Source | System detection | Practitioner input |
| Estimation | Calculated | User-provided |
| Confidence | Based on data | User judgment |
| Actions | Template-based | Custom |
| Recurrence | Re-detected each calc | One-time unless recurring |

---

## 7. Portfolio View

### 7.1 Firm-Wide Optimizations

Partners and Managers see aggregated view:

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Optimization Portfolio                                                  │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌────────────────────────────────────────────────────────────────────┐ │
│  │ PORTFOLIO SUMMARY                                                   │ │
│  │                                                                     │ │
│  │ ₦45,600,000       ₦18,200,000       ₦27,400,000      67 Clients    │ │
│  │ Total Identified  Implemented       Remaining       With Opps.      │ │
│  └────────────────────────────────────────────────────────────────────┘ │
│                                                                          │
│  BY TYPE                                       BY STATUS                 │
│  ┌────────────────────────────────┐  ┌────────────────────────────────┐ │
│  │ VAT Recovery      ₦12.5M      │  │ Identified    45  (₦27.4M)     │ │
│  │ Threshold Mgmt    ₦8.2M       │  │ In Progress   12  (₦9.8M)      │ │
│  │ Timing            ₦6.8M       │  │ Implemented   23  (₦8.4M)      │ │
│  │ Compliance        ₦5.1M       │  │ Verified      15  (₦9.8M)      │ │
│  │ Other             ₦4.0M       │  │ Dismissed     8                │ │
│  └────────────────────────────────┘  └────────────────────────────────┘ │
│                                                                          │
│  TOP OPPORTUNITIES                                                       │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ Client           Optimization              Est. Value   Status      ││
│  ├─────────────────────────────────────────────────────────────────────┤│
│  │ Mega Corp Ltd    Entity Restructuring      ₦5,200,000   Identified ││
│  │ ABC Trading      VAT Recovery              ₦1,875,000   In Progress││
│  │ XYZ Mfg          Capital Allowances        ₦1,450,000   Identified ││
│  │ ...                                                                 ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 7.2 Filtering and Sorting

**Filters:**
- By client
- By type
- By status
- By priority
- By assigned user
- By date range

**Sorting:**
- Estimated value (highest first)
- Priority
- Status
- Date identified
- Client name

---

## 8. Reporting Integration

### 8.1 Optimizations in Reports

Optimizations included in Tax Impact Analysis reports:
- List of identified opportunities
- Estimated total savings
- Recommended priority order
- Implementation guidance

### 8.2 Optimization Summary Report

Standalone report showing:
- All optimizations for client
- Implementation status
- Realized vs. estimated savings
- Action items with owners

---

## 9. Data Model

### 9.1 Optimizations Table

**Table: optimizations**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| tax_client_id | ULID | Parent client |
| firm_id | ULID | Tenant (denormalized) |
| calculation_id | ULID | Source calculation (if auto-detected) |
| type | varchar(50) | Optimization type |
| category | varchar(50) | Category (timing, structural, etc.) |
| title | varchar(255) | Short title |
| description | text | Full description |
| estimated_savings | decimal(15,2) | Estimated savings amount |
| actual_savings | decimal(15,2) | Realized savings (after verification) |
| confidence | enum | high, medium, low |
| priority | enum | high, medium, low |
| status | enum | identified, in_progress, implemented, verified, dismissed |
| source | enum | automatic, manual |
| detection_rule | varchar(100) | Rule that detected (if auto) |
| detection_data | jsonb | Data points that triggered |
| recommended_actions | jsonb | List of recommended actions |
| implementation_notes | text | Notes on implementation |
| dismissal_reason | text | Reason if dismissed |
| assigned_to | ULID | Assigned user |
| target_date | date | Target completion |
| implemented_at | timestamp | When marked implemented |
| verified_at | timestamp | When verified |
| created_by | ULID | Creator |
| created_at | timestamp | Creation date |
| updated_at | timestamp | Last modified |

### 9.2 Optimization History Table

**Table: optimization_history**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| optimization_id | ULID | Parent optimization |
| status | varchar(50) | Status at this point |
| notes | text | Transition notes |
| changed_by | ULID | User who changed |
| changed_at | timestamp | When changed |

---

## 10. API Endpoints

### 10.1 Optimization Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/clients/{id}/optimizations | List optimizations |
| POST | /api/clients/{id}/optimizations | Create manual optimization |
| GET | /api/clients/{id}/optimizations/{oid} | Get details |
| PUT | /api/clients/{id}/optimizations/{oid} | Update optimization |
| DELETE | /api/clients/{id}/optimizations/{oid} | Delete |
| POST | /api/clients/{id}/optimizations/{oid}/status | Update status |

### 10.2 Portfolio Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/optimizations/portfolio | Firm-wide summary |
| GET | /api/optimizations/portfolio/export | Export portfolio |

---

## 11. Implementation Checklist

### Phase 1: Core Tracking

- [ ] Optimization model and migrations
- [ ] Manual optimization CRUD
- [ ] Status management
- [ ] Client optimization list view

### Phase 2: Automatic Detection

- [ ] Detection rule engine
- [ ] VAT recovery detection
- [ ] Threshold proximity detection
- [ ] Capital allowance detection
- [ ] Integration with calculation

### Phase 3: Advanced Features

- [ ] Portfolio view
- [ ] Assignment and tracking
- [ ] Verification workflow
- [ ] Report integration

### Phase 4: Intelligence

- [ ] Additional detection rules
- [ ] Learning from implementations
- [ ] Benchmarking against similar clients

---

*This document should be updated as optimization tracker requirements evolve during development.*
