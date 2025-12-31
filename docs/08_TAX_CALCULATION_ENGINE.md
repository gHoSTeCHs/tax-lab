# TaxLab — Tax Calculation Engine

## Document Information

| Item | Detail |
|------|--------|
| Document | Tax Calculation Engine |
| Version | 1.0 |
| Last Updated | December 2024 |
| Audience | Developers, Product Team |

---

## 1. Overview

The Tax Calculation Engine is the core of TaxLab. It applies Nigerian tax rules to client financial data and produces comprehensive tax liability calculations under both the old regime (pre-NTA 2025) and the new regime (NTA 2025).

### 1.1 Access

- **URL:** `/app/clients/{id}/calculate`
- **Guard:** firm
- **Roles:** Partner, Manager, Associate (assigned clients)

### 1.2 Key Principles

**Accuracy:** Calculations must precisely follow tax legislation
**Transparency:** Every figure traceable to source data and rules
**Reproducibility:** Historical calculations reproducible with original rules
**Comparison:** Always show old vs. new regime impact

---

## 2. Calculation Types

### 2.1 Full Analysis

Comprehensive calculation covering all applicable tax types:
- Company Income Tax (CIT)
- Personal Income Tax (PIT)
- Value Added Tax (VAT)
- Capital Gains Tax (CGT)
- Development Levy
- Withholding Tax Analysis
- PAYE Analysis

**Output:** Complete tax position with optimization opportunities

### 2.2 Component Calculations

Individual tax type calculations:

| Type | Entity | Description |
|------|--------|-------------|
| CIT Only | Company | Company income tax calculation |
| PIT Only | Individual | Personal income tax calculation |
| VAT Only | Company | VAT position and recovery |
| CGT Only | Both | Capital gains tax on disposals |
| PAYE Only | Company | Payroll tax analysis |

**Use Case:** Quick analysis of specific tax type

### 2.3 Quick Estimate

Simplified calculation using minimal inputs:
- Revenue and profit only
- High-level estimate
- Useful for prospects or early discussions

---

## 3. Calculation Architecture

### 3.1 System Flow

```
┌─────────────────┐
│  Calculation    │
│  Request        │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Load Client    │
│  Financial Data │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Load Tax Rules │
│  (versioned)    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Determine      │
│  Applicable     │
│  Calculations   │
└────────┬────────┘
         │
         ├──────────────┬──────────────┬──────────────┐
         ▼              ▼              ▼              ▼
┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐
│  CIT Calc   │  │  VAT Calc   │  │  CGT Calc   │  │  Other...   │
│  (old+new)  │  │  (old+new)  │  │  (old+new)  │  │             │
└──────┬──────┘  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘
       │                │                │                │
       └────────────────┴────────────────┴────────────────┘
                                │
                                ▼
                    ┌─────────────────────┐
                    │  Aggregate Results  │
                    │  • Total Old Regime │
                    │  • Total New Regime │
                    │  • Net Impact       │
                    └──────────┬──────────┘
                               │
                               ▼
                    ┌─────────────────────┐
                    │  Identify           │
                    │  Optimizations      │
                    └──────────┬──────────┘
                               │
                               ▼
                    ┌─────────────────────┐
                    │  Store Results      │
                    │  (with rule version)│
                    └─────────────────────┘
```

### 3.2 Calculator Service Architecture

```
TaxCalculationService
├── CITCalculator
│   ├── OldRegimeCITCalculator
│   └── NewRegimeCITCalculator
├── PITCalculator
│   ├── OldRegimePITCalculator
│   └── NewRegimePITCalculator
├── VATCalculator
│   ├── OldRegimeVATCalculator
│   └── NewRegimeVATCalculator
├── CGTCalculator
│   ├── OldRegimeCGTCalculator
│   └── NewRegimeCGTCalculator
├── DevelopmentLevyCalculator
├── PAYECalculator
├── OptimizationAnalyzer
└── ResultsAggregator
```

### 3.3 Rule Engine Integration

Each calculator retrieves rules from the Tax Rules repository:

```
Calculator needs PIT rates
         │
         ▼
┌─────────────────────────┐
│  TaxRulesRepository     │
│  getRules(              │
│    type: 'pit_rates',   │
│    applies_to: 'indiv', │
│    effective: '2025-01' │
│    regime: 'new'        │
│  )                      │
└────────────┬────────────┘
             │
             ▼
┌─────────────────────────┐
│  Returns versioned      │
│  rule parameters        │
│  {                      │
│    version: 'v2.1',     │
│    bands: [...],        │
│    exemption: 800000    │
│  }                      │
└─────────────────────────┘
```

---

## 4. Company Income Tax (CIT)

### 4.1 Classification

First, determine company classification:

**Old Regime:**
| Classification | Turnover | Rate |
|----------------|----------|------|
| Small Company | ≤ ₦25M | 0% |
| Medium Company | ₦25M - ₦100M | 20% |
| Large Company | > ₦100M | 30% |

**New Regime (NTA 2025):**
| Classification | Turnover | Rate |
|----------------|----------|------|
| Small Company | ≤ ₦50M | 0% |
| Medium Company | ₦50M - ₦100M | 20% |
| Large Company | > ₦100M | 25% |

### 4.2 Calculation Steps

**Step 1: Determine Taxable Profit**
```
Accounting Profit
+ Disallowable Expenses
- Non-Taxable Income
- Capital Allowances
= Taxable Profit (or Adjusted Loss)
```

**Step 2: Apply Company Classification**
- Check turnover against thresholds
- Determine applicable rate

**Step 3: Calculate Tax Liability**
```
If Small Company: Tax = 0
If Medium/Large: Tax = Taxable Profit × Rate
```

**Step 4: Minimum Tax Check (Large Companies)**
```
Old Regime: Minimum Tax = 0.5% of Turnover
New Regime: Minimum Tax = 0.5% of Turnover (similar)

If Tax < Minimum Tax AND Taxable Profit > 0:
    Tax = Minimum Tax
```

**Step 5: Apply Reliefs and Credits**
- R&D tax credit
- Pioneer status relief
- Export incentives
- Other applicable incentives

### 4.3 Disallowable Expenses

Common items added back:
- Depreciation (replaced by capital allowances)
- Provisions (general provisions)
- Penalties and fines
- Donations above limits
- Entertainment expenses (portion)
- Non-deductible interest

### 4.4 Capital Allowances

Replace accounting depreciation with tax depreciation:

| Asset Class | Initial Allowance | Annual Allowance |
|-------------|------------------|------------------|
| Building | 15% | 10% |
| Plant & Machinery | 50% | 25% |
| Furniture & Fittings | 25% | 20% |
| Motor Vehicles | 50% | 25% |
| Computer Equipment | 50% | 25% |

### 4.5 Output Structure

```json
{
  "company_income_tax": {
    "old_regime": {
      "classification": "large",
      "turnover": 150000000,
      "accounting_profit": 20000000,
      "adjustments": {
        "add_backs": 3500000,
        "deductions": 1000000
      },
      "taxable_profit": 22500000,
      "capital_allowances": 4500000,
      "final_taxable_profit": 18000000,
      "tax_rate": 0.30,
      "calculated_tax": 5400000,
      "minimum_tax": 750000,
      "tax_liability": 5400000,
      "effective_rate": 0.27
    },
    "new_regime": {
      "classification": "large",
      "turnover": 150000000,
      "taxable_profit": 18000000,
      "tax_rate": 0.25,
      "calculated_tax": 4500000,
      "tax_liability": 4500000,
      "effective_rate": 0.225
    },
    "comparison": {
      "old_liability": 5400000,
      "new_liability": 4500000,
      "savings": 900000,
      "savings_percentage": 16.67
    }
  }
}
```

---

## 5. Personal Income Tax (PIT)

### 5.1 Tax Bands

**Old Regime:**
| Income Range | Rate |
|--------------|------|
| First ₦300,000 | 7% |
| Next ₦300,000 | 11% |
| Next ₦500,000 | 15% |
| Next ₦500,000 | 19% |
| Next ₦1,600,000 | 21% |
| Above ₦3,200,000 | 24% |

**New Regime (NTA 2025):**
| Income Range | Rate |
|--------------|------|
| First ₦800,000 | 0% |
| ₦800,001 - ₦2,800,000 | 7% |
| ₦2,800,001 - ₦5,200,000 | 11% |
| ₦5,200,001 - ₦10,400,000 | 15% |
| ₦10,400,001 - ₦50,000,000 | 19% |
| Above ₦50,000,000 | 25% |

### 5.2 Calculation Steps

**Step 1: Determine Gross Income**
```
Employment Income
+ Rental Income (net)
+ Business Income (net)
+ Investment Income
+ Other Income
= Gross Income
```

**Step 2: Calculate Reliefs (Old Regime)**
```
Consolidated Relief Allowance:
- Higher of ₦200,000 or 1% of Gross Income
- Plus 20% of Gross Income

Additional Reliefs:
- Pension Contribution (tax-free portion)
- Life Insurance Premium
- NHF Contribution
- Gratuity (within limits)
```

**Step 3: Calculate Taxable Income**
```
Gross Income - Total Reliefs = Taxable Income
```

**Step 4: Apply Tax Bands**
```
For each band:
  If remaining income > band_size:
    Tax += band_size × band_rate
    remaining income -= band_size
  Else:
    Tax += remaining_income × band_rate
    Break
```

### 5.3 Output Structure

```json
{
  "personal_income_tax": {
    "old_regime": {
      "gross_income": 15000000,
      "reliefs": {
        "consolidated_relief": 3200000,
        "pension_relief": 1200000,
        "nhf_relief": 375000,
        "total": 4775000
      },
      "taxable_income": 10225000,
      "tax_by_band": [
        {"band": "₦0 - ₦300,000", "rate": 0.07, "tax": 21000},
        {"band": "₦300,001 - ₦600,000", "rate": 0.11, "tax": 33000},
        {"band": "₦600,001 - ₦1,100,000", "rate": 0.15, "tax": 75000},
        {"band": "₦1,100,001 - ₦1,600,000", "rate": 0.19, "tax": 95000},
        {"band": "₦1,600,001 - ₦3,200,000", "rate": 0.21, "tax": 336000},
        {"band": "Above ₦3,200,000", "rate": 0.24, "tax": 1686000}
      ],
      "tax_liability": 2246000,
      "effective_rate": 0.1497
    },
    "new_regime": {
      "gross_income": 15000000,
      "exemption": 800000,
      "taxable_income": 14200000,
      "tax_by_band": [...],
      "tax_liability": 1858000,
      "effective_rate": 0.1239
    },
    "comparison": {
      "old_liability": 2246000,
      "new_liability": 1858000,
      "savings": 388000,
      "savings_percentage": 17.27
    }
  }
}
```

---

## 6. Value Added Tax (VAT)

### 6.1 VAT Calculation

**Both Regimes (Rate unchanged at 7.5%):**

```
VAT Output (collected from customers)
- VAT Input (paid to suppliers)
= VAT Payable (or Refundable)
```

### 6.2 Key Difference: Input Recovery

**Old Regime:**
- Input VAT on goods generally recoverable
- Input VAT on services NOT recoverable
- Input VAT on capital assets limited

**New Regime (NTA 2025):**
- Input VAT on goods recoverable
- Input VAT on services NOW recoverable
- Input VAT on capital assets NOW recoverable

### 6.3 Calculation Steps

**Step 1: Calculate Output VAT**
```
VATable Sales × 7.5% = Output VAT
(Exempt and zero-rated excluded)
```

**Step 2: Calculate Input VAT (Old Regime)**
```
VAT on Goods Purchases = Recoverable
VAT on Services = NOT Recoverable
VAT on Capital = Limited Recovery
```

**Step 3: Calculate Input VAT (New Regime)**
```
VAT on Goods Purchases = Recoverable
VAT on Services = Recoverable (NEW)
VAT on Capital = Recoverable (EXPANDED)
```

**Step 4: Net Position**
```
Output VAT - Recoverable Input VAT = Net Payable
```

### 6.4 Output Structure

```json
{
  "vat_analysis": {
    "output_vat": {
      "vatable_sales": 140000000,
      "exempt_sales": 10000000,
      "zero_rated_sales": 5000000,
      "output_vat": 10500000
    },
    "old_regime": {
      "input_goods": 5250000,
      "input_services": 0,
      "input_capital": 0,
      "total_recoverable": 5250000,
      "net_vat_payable": 5250000
    },
    "new_regime": {
      "input_goods": 5250000,
      "input_services": 1875000,
      "input_capital": 750000,
      "total_recoverable": 7875000,
      "net_vat_payable": 2625000
    },
    "comparison": {
      "old_payable": 5250000,
      "new_payable": 2625000,
      "savings": 2625000,
      "savings_percentage": 50.0,
      "note": "Additional input recovery on services and capital"
    }
  }
}
```

---

## 7. Capital Gains Tax (CGT)

### 7.1 Rate Changes

**Old Regime:**
- Flat 10% on gains

**New Regime (NTA 2025):**
- Individuals: Aligned with income tax rates (up to 25%)
- Companies: Aligned with CIT rate (25%)

### 7.2 Calculation Steps

**Step 1: Calculate Gain**
```
Disposal Proceeds
- Acquisition Cost
- Improvement Costs
- Incidental Costs (legal, etc.)
= Capital Gain
```

**Step 2: Apply Exemptions**
- Main residence exemption (individuals)
- Rollover relief conditions
- Small disposal exemption

**Step 3: Apply Rate**
```
Old: Gain × 10%
New: Gain × Applicable Rate (based on entity type)
```

### 7.3 Output Structure

```json
{
  "capital_gains_tax": {
    "disposals": [
      {
        "asset": "Investment Property",
        "proceeds": 50000000,
        "cost": 30000000,
        "improvements": 5000000,
        "gain": 15000000
      }
    ],
    "total_gain": 15000000,
    "exemptions": 0,
    "taxable_gain": 15000000,
    "old_regime": {
      "rate": 0.10,
      "tax": 1500000
    },
    "new_regime": {
      "rate": 0.25,
      "tax": 3750000
    },
    "comparison": {
      "old_tax": 1500000,
      "new_tax": 3750000,
      "increase": 2250000,
      "increase_percentage": 150.0,
      "note": "Significant increase due to rate alignment with CIT"
    }
  }
}
```

---

## 8. Development Levy

### 8.1 Old Regime (Multiple Levies)

| Levy | Rate | Base |
|------|------|------|
| Education Tax | 2% | Assessable Profit |
| NITDF | 1% | Profit Before Tax |
| NASENI | 0.25% | Profit Before Tax |
| Police Trust Fund | 0.005% | Net Profit |

Total: ~3.255% combined

### 8.2 New Regime (Unified Levy)

| Levy | Rate | Base |
|------|------|------|
| Development Levy | 4% | Assessable Profit |

### 8.3 Output Structure

```json
{
  "development_levy": {
    "old_regime": {
      "education_tax": {
        "base": 18000000,
        "rate": 0.02,
        "amount": 360000
      },
      "nitdf": {
        "base": 19500000,
        "rate": 0.01,
        "amount": 195000
      },
      "naseni": {
        "base": 19500000,
        "rate": 0.0025,
        "amount": 48750
      },
      "police_fund": {
        "base": 13650000,
        "rate": 0.00005,
        "amount": 683
      },
      "total": 604433
    },
    "new_regime": {
      "development_levy": {
        "base": 18000000,
        "rate": 0.04,
        "amount": 720000
      },
      "total": 720000
    },
    "comparison": {
      "old_total": 604433,
      "new_total": 720000,
      "increase": 115567,
      "increase_percentage": 19.12
    }
  }
}
```

---

## 9. Results Aggregation

### 9.1 Total Tax Position

```json
{
  "summary": {
    "client_name": "ABC Trading Limited",
    "fiscal_year": 2024,
    "entity_type": "company",
    "calculation_date": "2024-12-15T10:30:00Z",
    "rules_version": "v2.1",
    
    "old_regime_total": {
      "company_income_tax": 5400000,
      "vat_payable": 5250000,
      "capital_gains_tax": 1500000,
      "development_levy": 604433,
      "total_tax_liability": 12754433
    },
    
    "new_regime_total": {
      "company_income_tax": 4500000,
      "vat_payable": 2625000,
      "capital_gains_tax": 3750000,
      "development_levy": 720000,
      "total_tax_liability": 11595000
    },
    
    "overall_comparison": {
      "old_total": 12754433,
      "new_total": 11595000,
      "net_savings": 1159433,
      "savings_percentage": 9.09,
      "primary_drivers": [
        "CIT rate reduction (₦900,000 savings)",
        "Enhanced VAT input recovery (₦2,625,000 savings)",
        "CGT rate increase (₦2,250,000 increase)",
        "Development levy increase (₦115,567 increase)"
      ]
    }
  }
}
```

### 9.2 Visualization Data

Generate data for charts:
- Comparison bar chart (old vs. new by tax type)
- Pie chart (tax composition)
- Waterfall chart (showing drivers of change)

---

## 10. Optimization Analysis

### 10.1 Automatic Detection

After calculation, analyze for opportunities:

**Timing Opportunities:**
- Defer income to benefit from lower rates
- Accelerate expenses before year-end
- Time asset disposals strategically

**Structural Opportunities:**
- Small company threshold proximity
- Entity restructuring benefits
- Group relief possibilities

**Compliance Opportunities:**
- Unclaimed capital allowances
- Missed VAT input recovery
- Available reliefs not utilized

### 10.2 Optimization Output

```json
{
  "optimizations": [
    {
      "type": "timing",
      "title": "Defer Revenue to Next Year",
      "description": "₦5M revenue deferral would reduce current year CIT",
      "estimated_savings": 1250000,
      "priority": "high",
      "action_required": "Review contracts for deferral opportunity"
    },
    {
      "type": "structural",
      "title": "Consider Company Split",
      "description": "Turnover close to medium company threshold",
      "estimated_savings": "Variable",
      "priority": "medium",
      "action_required": "Analyze feasibility of business restructuring"
    }
  ]
}
```

---

## 11. Calculation Storage

### 11.1 Data Model

**Table: calculations**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| tax_client_id | ULID | Parent client |
| firm_id | ULID | Tenant (denormalized) |
| fiscal_year | integer | Year calculated |
| calculation_type | enum | full_analysis, cit_only, pit_only, etc. |
| rules_version | varchar(20) | Tax rules version used |
| financial_version | integer | Financial data version used |
| inputs | jsonb | Snapshot of input data |
| results | jsonb | Complete calculation results |
| summary | jsonb | High-level summary |
| optimizations | jsonb | Identified opportunities |
| status | enum | completed, error, superseded |
| performed_by | ULID | User who ran |
| calculation_time_ms | integer | Processing time |
| created_at | timestamp | When calculated |

### 11.2 Audit Requirements

Every calculation stores:
- Exact inputs used
- Rules version applied
- Complete results
- User who performed
- Timestamp

Enables:
- Reproducibility
- Audit trail
- Historical comparison
- Debugging

---

## 12. User Interface

### 12.1 Calculation Trigger

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Run Tax Calculation                                                     │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  Client: ABC Trading Limited                                             │
│  Fiscal Year: 2024                                                       │
│                                                                          │
│  Calculation Type:                                                       │
│  ○ Full Analysis (recommended)                                           │
│  ○ Company Income Tax Only                                               │
│  ○ VAT Analysis Only                                                     │
│  ○ Capital Gains Tax Only                                                │
│                                                                          │
│  Data Source: Audited Financial Statements                               │
│  Data Status: ✓ Complete (15/15 required fields)                        │
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ ⚠ 2 warnings acknowledged in financial data                         ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│                                         [Cancel]  [Run Calculation]      │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 12.2 Results Display

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Tax Calculation Results                                                 │
│  ABC Trading Limited • FY 2024 • Calculated Dec 15, 2024                │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌────────────────────────┐  ┌────────────────────────┐                 │
│  │  OLD REGIME            │  │  NEW REGIME (NTA 2025) │                 │
│  │  ₦12,754,433           │  │  ₦11,595,000           │                 │
│  │  Total Tax             │  │  Total Tax             │                 │
│  └────────────────────────┘  └────────────────────────┘                 │
│                                                                          │
│            You SAVE ₦1,159,433 (9.1%) under NTA 2025                    │
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ [Summary] [CIT] [VAT] [CGT] [Development Levy] [Optimizations]     ││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
│  COMPARISON BY TAX TYPE                                                  │
│                                                                          │
│  [Bar Chart: Old vs New by tax type]                                    │
│                                                                          │
│  KEY INSIGHTS                                                            │
│  • CIT reduced by ₦900,000 due to rate cut from 30% to 25%              │
│  • VAT burden reduced by ₦2,625,000 through expanded input recovery     │
│  • CGT increased by ₦2,250,000 due to rate alignment                    │
│                                                                          │
│  [Generate Report]  [Save Scenario]  [Share]                            │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 13. Implementation Checklist

### Phase 1: Core Calculators

- [ ] Calculator service architecture
- [ ] CIT calculator (old and new regime)
- [ ] PIT calculator (old and new regime)
- [ ] Results aggregation
- [ ] Calculation storage

### Phase 2: Additional Tax Types

- [ ] VAT calculator with input recovery
- [ ] CGT calculator
- [ ] Development levy calculator
- [ ] PAYE analysis

### Phase 3: Advanced Features

- [ ] Optimization analyzer
- [ ] Comparison visualizations
- [ ] Historical calculation comparison
- [ ] Quick estimate calculator

### Phase 4: Polish

- [ ] Performance optimization
- [ ] Comprehensive testing
- [ ] Edge case handling
- [ ] Error recovery

---

*This document should be updated as calculation requirements evolve during development.*
