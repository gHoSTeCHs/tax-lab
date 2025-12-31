# TaxLab — Report System

## Document Information

| Item | Detail |
|------|--------|
| Document | Report Generation System |
| Version | 1.0 |
| Last Updated | December 2024 |
| Audience | Developers, Product Team |

---

## 1. Overview

The Report System generates professional documents that practitioners deliver to their clients. Reports transform calculation data into polished, branded deliverables that demonstrate value and support advisory conversations.

### 1.1 Access

- **URL:** `/app/clients/{id}/reports`
- **Guard:** firm
- **Roles:** Partner, Manager, Associate (assigned clients), Viewer (read-only)

### 1.2 Key Principles

**Professional Quality:** Reports should match Big 4 standards
**Full White-Label:** Every report shows firm branding, not TaxLab
**Flexible Output:** Support PDF and Word formats
**Efficient Generation:** Quick turnaround for standard reports

---

## 2. Report Types

### 2.1 Tax Impact Analysis Report

**Purpose:** Comprehensive analysis of NTA 2025 impact on client

**Typical Length:** 12-20 pages

**Sections:**
1. Cover Page
2. Executive Summary
3. Client Overview
4. Old Regime Tax Position
5. New Regime Tax Position
6. Comparative Analysis
7. Impact by Tax Type
8. Key Drivers of Change
9. Optimization Opportunities
10. Recommendations
11. Appendices (detailed calculations)

**Use Case:** Primary deliverable for client engagement

### 2.2 Advisory Letter

**Purpose:** Concise summary for executive review

**Typical Length:** 2-4 pages

**Sections:**
1. Cover Page
2. Key Findings (bullet points)
3. Summary Table
4. Recommended Actions
5. Next Steps

**Use Case:** Quick communication to busy executives

### 2.3 Scenario Comparison Report

**Purpose:** Compare multiple scenarios side-by-side

**Typical Length:** 5-10 pages

**Sections:**
1. Cover Page
2. Scenario Overview
3. Comparison Table
4. Charts and Visualizations
5. Analysis and Insights
6. Recommendations

**Use Case:** Support strategic decision-making

### 2.4 Tax Computation Report

**Purpose:** Detailed calculation breakdown for technical review

**Typical Length:** 8-15 pages

**Sections:**
1. Cover Page
2. Computation Summary
3. Income Statement Adjustments
4. Capital Allowances Schedule
5. Tax Liability Computation
6. Supporting Schedules
7. Notes and Assumptions

**Use Case:** Audit documentation, technical discussions

### 2.5 Quarterly/Annual Summary

**Purpose:** Period-end tax position summary

**Typical Length:** 4-8 pages

**Sections:**
1. Cover Page
2. Period Summary
3. Tax Payments vs. Liability
4. Compliance Status
5. Upcoming Deadlines
6. Recommendations

**Use Case:** Regular client updates

---

## 3. Report Architecture

### 3.1 Section-Based Composition

Reports are built from reusable sections:

```
Report
├── Section: CoverPage
├── Section: TableOfContents
├── Section: ExecutiveSummary
├── Section: ClientOverview
├── Section: CompanyIncomeTaxAnalysis
├── Section: VATAnalysis
├── Section: CapitalGainsTaxAnalysis
├── Section: DevelopmentLevyAnalysis
├── Section: ComparisonCharts
├── Section: Optimizations
├── Section: Recommendations
└── Section: Appendices
```

### 3.2 Section Interface

Each section implements:

```
interface ReportSection
{
    shouldInclude(client, options): boolean
    getTitle(): string
    getEstimatedPages(): number
    render(data, branding, format): string
}
```

### 3.3 Report Builder Service

```
ReportBuilder
    ->forClient($client)
    ->withCalculation($calculation)
    ->ofType('impact_analysis')
    ->withOptions([
        'include_scenarios' => true,
        'include_appendices' => true,
        'detail_level' => 'full'
    ])
    ->withBranding($firmBranding)
    ->generate('pdf')
```

---

## 4. White-Label Branding

### 4.1 Branding Elements

| Element | Usage |
|---------|-------|
| Firm Logo | Cover page, header |
| Firm Name | Cover page, footer |
| Firm Address | Cover page |
| Firm Contact | Cover page, footer |
| Primary Color | Headers, accents |
| Secondary Color | Charts, highlights |
| Footer Text | Custom footer message |

### 4.2 Branding Configuration

```json
{
  "logo": {
    "path": "/firms/123/branding/logo.png",
    "width": 200,
    "height": 60
  },
  "colors": {
    "primary": "#1E40AF",
    "secondary": "#3B82F6",
    "accent": "#60A5FA"
  },
  "text": {
    "firm_name": "Acme Tax Consultants",
    "address": "123 Marina Street, Lagos",
    "phone": "+234 1 234 5678",
    "email": "info@acmetax.com",
    "website": "www.acmetax.com",
    "footer": "Confidential - Prepared for [Client Name]"
  },
  "style": {
    "font_family": "Inter, sans-serif",
    "header_style": "modern"
  }
}
```

### 4.3 No TaxLab Branding

Reports contain NO reference to TaxLab:
- No "Powered by" text
- No TaxLab logo
- No TaxLab URL
- Fully branded as firm's work product

---

## 5. Report Generation Process

### 5.1 Generation Flow

```
User Clicks "Generate Report"
              │
              ▼
┌────────────────────────┐
│ Report Configuration   │
│ • Select type          │
│ • Choose sections      │
│ • Set options          │
└───────────┬────────────┘
            │
            ▼
┌────────────────────────┐
│ Preview (optional)     │
│ • Estimated pages      │
│ • Section list         │
└───────────┬────────────┘
            │
            ▼
┌────────────────────────┐      ┌────────────────────────┐
│ Quick Generation       │  OR  │ Queue for Background   │
│ (< 30 seconds)         │      │ (large reports)        │
└───────────┬────────────┘      └───────────┬────────────┘
            │                               │
            │                               ▼
            │                   ┌────────────────────────┐
            │                   │ GenerateReportJob      │
            │                   │ • Process sections     │
            │                   │ • Generate output      │
            │                   │ • Store file           │
            │                   │ • Notify user          │
            │                   └───────────┬────────────┘
            │                               │
            └───────────────────────────────┘
                              │
                              ▼
                ┌────────────────────────┐
                │ Report Ready           │
                │ • Download available   │
                │ • Approval workflow    │
                └────────────────────────┘
```

### 5.2 Generation Methods

**Synchronous (Quick):**
- Simple reports < 10 pages
- Advisory letters
- User waits for result
- Timeout: 30 seconds

**Asynchronous (Queued):**
- Complex reports > 10 pages
- Multiple scenarios
- User receives notification
- No timeout

### 5.3 Output Formats

**PDF:**
- Primary format
- Print-ready quality
- Fixed layout
- Digital signatures (future)

**DOCX (Word):**
- Editable format
- Practitioners can customize
- Add personal notes
- Reformat if needed

---

## 6. Section Details

### 6.1 Cover Page

```
┌─────────────────────────────────────────────────────────────────────────┐
│                                                                          │
│                              [FIRM LOGO]                                 │
│                                                                          │
│                                                                          │
│                                                                          │
│                    ═══════════════════════════════                       │
│                                                                          │
│                      TAX IMPACT ANALYSIS REPORT                          │
│                                                                          │
│                      Nigeria Tax Act 2025 (NTA)                          │
│                                                                          │
│                    ═══════════════════════════════                       │
│                                                                          │
│                                                                          │
│                          Prepared for:                                   │
│                      ABC TRADING LIMITED                                 │
│                                                                          │
│                         Fiscal Year 2024                                 │
│                                                                          │
│                                                                          │
│                                                                          │
│                        December 2024                                     │
│                                                                          │
│                                                                          │
│  ─────────────────────────────────────────────────────────────────────  │
│  Acme Tax Consultants                                                    │
│  123 Marina Street, Lagos                                                │
│  +234 1 234 5678 | info@acmetax.com                                     │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 6.2 Executive Summary

**Content:**
- 1-page maximum
- Key findings in bullet points
- Summary metrics table
- Primary recommendation

**Example Content:**
```
EXECUTIVE SUMMARY

The Nigeria Tax Act 2025 (NTA) introduces significant changes that will 
affect ABC Trading Limited's tax position for fiscal year 2024 and beyond.

KEY FINDINGS:

• Overall tax burden DECREASES by ₦1,159,433 (9.1%) under the new regime
• Company Income Tax reduces by ₦900,000 due to rate cut from 30% to 25%
• VAT burden decreases by ₦2,625,000 through expanded input recovery
• Capital Gains Tax increases by ₦2,250,000 due to rate alignment
• Company maintains "Large Company" classification under both regimes

┌─────────────────┬─────────────┬─────────────┬────────────┐
│                 │ Old Regime  │ New Regime  │   Change   │
├─────────────────┼─────────────┼─────────────┼────────────┤
│ Total Tax       │ ₦12,754,433 │ ₦11,595,000 │ -₦1,159,433│
│ Effective Rate  │ 63.77%      │ 57.98%      │ -5.79 pts  │
└─────────────────┴─────────────┴─────────────┴────────────┘

RECOMMENDATION:

We recommend implementing the optimization strategies outlined in Section 9 
to maximize the benefits of the new tax regime, with potential additional 
savings of up to ₦450,000 annually.
```

### 6.3 Comparison Charts

**Chart Types Generated:**

1. **Side-by-Side Bar Chart:** Old vs. New by tax type
2. **Waterfall Chart:** Drivers of change
3. **Pie Charts:** Tax composition old and new
4. **Line Chart:** Effective rate comparison

**Chart Generation:**
- Server-side rendering (QuickChart.io or Chart.js)
- Embedded as images in PDF
- High-resolution for print

### 6.4 Detailed Tax Analysis Sections

Each tax type section includes:
- Applicable rates and rules
- Calculation breakdown
- Old vs. new comparison
- Impact explanation
- Relevant opportunities

### 6.5 Appendices

**Appendix A:** Detailed Calculation Workings
**Appendix B:** Source Financial Data
**Appendix C:** Applicable Tax Rules Referenced
**Appendix D:** Glossary of Terms
**Appendix E:** Disclaimer and Limitations

---

## 7. Report Configuration

### 7.1 Configuration Interface

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Generate Report                                                         │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  Client: ABC Trading Limited                                             │
│  Calculation: FY 2024 Full Analysis (Dec 15, 2024)                      │
│                                                                          │
│  Report Type: [Tax Impact Analysis ▼]                                   │
│                                                                          │
│  Output Format:  ○ PDF (recommended)  ○ Word Document                   │
│                                                                          │
│  ─────────────────────────────────────────────────────────────────────  │
│                                                                          │
│  SECTIONS                                              Est. Pages: 15   │
│                                                                          │
│  ☑ Cover Page                                                    1      │
│  ☑ Table of Contents                                             1      │
│  ☑ Executive Summary                                             1      │
│  ☑ Client Overview                                               1      │
│  ☑ Company Income Tax Analysis                                   2      │
│  ☑ VAT Analysis                                                  2      │
│  ☐ Capital Gains Tax Analysis (no disposals)                     —      │
│  ☑ Development Levy Analysis                                     1      │
│  ☑ Comparison Charts                                             2      │
│  ☑ Optimization Opportunities                                    2      │
│  ☑ Recommendations                                               1      │
│  ☐ Appendices                                                    5      │
│                                                                          │
│  ─────────────────────────────────────────────────────────────────────  │
│                                                                          │
│  OPTIONS                                                                 │
│                                                                          │
│  Detail Level:    ○ Summary  ● Standard  ○ Detailed                     │
│  Include Scenarios: ☐ (0 scenarios available)                           │
│  Add Cover Letter: ☐                                                    │
│                                                                          │
│                                                                          │
│                                  [Cancel]  [Preview]  [Generate Report] │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 7.2 Smart Section Selection

System automatically determines which sections apply:
- No CGT section if no capital disposals
- No VAT section for exempt entities
- No partnership section for companies
- Add scenarios section if scenarios exist

### 7.3 Configurable Options

| Option | Values | Effect |
|--------|--------|--------|
| Detail Level | Summary, Standard, Detailed | Content depth |
| Include Scenarios | Yes/No | Add scenario comparison |
| Include Appendices | Yes/No | Add detailed workings |
| Cover Letter | Yes/No | Add personalized letter |
| Confidentiality Notice | Standard, Custom, None | Footer notice |

---

## 8. Approval Workflow

### 8.1 Workflow Overview

```
Report Generated
       │
       ▼
┌─────────────────┐     Approval Required?     ┌─────────────────┐
│  Check Firm     │ ───── No ─────────────────▶│ Status: Final   │
│  Settings       │                            └─────────────────┘
└────────┬────────┘
         │ Yes
         ▼
┌─────────────────┐
│ Status: Pending │
│ Approval        │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Notify          │
│ Approvers       │
└────────┬────────┘
         │
         ├─────────────────────────────────┐
         ▼                                 ▼
┌─────────────────┐              ┌─────────────────┐
│ Approved        │              │ Rejected        │
│ Status: Final   │              │ Status: Draft   │
│ Ready to Share  │              │ With Comments   │
└─────────────────┘              └────────┬────────┘
                                          │
                                          ▼
                               ┌─────────────────┐
                               │ Author Revises  │
                               │ & Resubmits     │
                               └─────────────────┘
```

### 8.2 Approval Configuration

Firm-level setting:
- Enable/disable approval workflow
- Who can approve (Partners, Managers)
- Auto-approve for certain users
- Required comments on rejection

### 8.3 Approval Interface

```
┌─────────────────────────────────────────────────────────────────────────┐
│  Reports Pending Approval                                                │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────────┐│
│  │ Client          Report Type         Generated By   Date     Actions ││
│  ├─────────────────────────────────────────────────────────────────────┤│
│  │ ABC Trading     Impact Analysis     Sarah J.       Dec 15   [Review]││
│  │ XYZ Mfg         Advisory Letter     John D.        Dec 14   [Review]││
│  └─────────────────────────────────────────────────────────────────────┘│
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

**Review Actions:**
- View full report
- Download draft
- Approve (with optional comment)
- Reject (comment required)
- Request changes (specific feedback)

---

## 9. Report Storage

### 9.1 Storage Structure

```
storage/app/firms/{firm_id}/reports/{year}/
├── {report_id}.pdf
├── {report_id}.docx
├── {report_id}_draft.pdf
└── {report_id}_metadata.json
```

### 9.2 Metadata Stored

```json
{
  "report_id": "abc123",
  "client_id": "client456",
  "calculation_id": "calc789",
  "type": "impact_analysis",
  "format": "pdf",
  "config": {
    "sections": [...],
    "options": {...}
  },
  "branding_version": "v2",
  "rules_version": "v1.2",
  "page_count": 15,
  "file_size_bytes": 2457600,
  "generated_by": "user123",
  "generated_at": "2024-12-15T10:30:00Z",
  "approved_by": "user456",
  "approved_at": "2024-12-15T11:00:00Z",
  "status": "final"
}
```

---

## 10. Data Model

### 10.1 Reports Table

**Table: reports**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| tax_client_id | ULID | Parent client |
| firm_id | ULID | Tenant (denormalized) |
| calculation_id | ULID | Source calculation |
| scenario_ids | jsonb | Included scenarios |
| type | varchar(50) | Report type |
| format | enum | pdf, docx |
| config | jsonb | Generation configuration |
| branding_version | varchar(20) | Branding snapshot version |
| rules_version | varchar(20) | Tax rules version |
| file_path | varchar(255) | Storage path |
| file_size | integer | Size in bytes |
| page_count | integer | Number of pages |
| status | enum | generating, draft, pending_approval, approved, rejected, final |
| approval_status | enum | pending, approved, rejected, not_required |
| approval_comment | text | Approver comment |
| generated_by | ULID | Creator |
| approved_by | ULID | Approver |
| approved_at | timestamp | Approval timestamp |
| published_to_portal | boolean | Shared with client |
| published_at | timestamp | When published |
| generation_time_ms | integer | How long to generate |
| created_at | timestamp | Creation date |
| updated_at | timestamp | Last modified |

### 10.2 Report Versions Table

**Table: report_versions**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| report_id | ULID | Parent report |
| version | integer | Version number |
| file_path | varchar(255) | Storage path |
| status | varchar(50) | Status at this version |
| created_by | ULID | Who created version |
| created_at | timestamp | When created |

---

## 11. PDF Generation Technical Details

### 11.1 Technology Stack

**Primary: Browsershot (Puppeteer)**
- Render HTML to PDF
- Full CSS support
- JavaScript rendering
- High-quality output

**Fallback: DomPDF**
- Pure PHP solution
- Limited CSS support
- No JavaScript
- Faster, lower quality

### 11.2 Template Structure

```
resources/views/reports/
├── layouts/
│   └── report.blade.php          # Base layout
├── sections/
│   ├── cover-page.blade.php
│   ├── executive-summary.blade.php
│   ├── client-overview.blade.php
│   ├── cit-analysis.blade.php
│   ├── vat-analysis.blade.php
│   └── ...
├── components/
│   ├── header.blade.php
│   ├── footer.blade.php
│   ├── table.blade.php
│   ├── chart.blade.php
│   └── ...
└── styles/
    └── report.css                # Print styles
```

### 11.3 Print CSS Considerations

```css
/* Page setup */
@page {
  size: A4;
  margin: 2.5cm 2cm;
}

/* Page breaks */
.section {
  page-break-before: always;
}

.no-break {
  page-break-inside: avoid;
}

/* Headers/Footers */
@page {
  @top-right {
    content: "Confidential";
  }
  @bottom-center {
    content: counter(page) " of " counter(pages);
  }
}
```

### 11.4 Chart Generation

**Option 1: QuickChart.io**
- External API
- Simple implementation
- No server dependencies
- Limited customization

**Option 2: Server-Side Chart.js**
- Node.js canvas rendering
- Full Chart.js features
- No external dependency
- More complex setup

---

## 12. Word Document Generation

### 12.1 Technology: PHPWord

**Features Used:**
- Template-based generation
- Style inheritance
- Tables and formatting
- Headers and footers
- Page numbering

### 12.2 Template Approach

```php
$templateProcessor = new TemplateProcessor('template.docx');
$templateProcessor->setValue('client_name', $client->name);
$templateProcessor->setValue('report_date', now()->format('F Y'));
$templateProcessor->cloneRow('tax_item', count($taxItems));
// ...
$templateProcessor->saveAs($outputPath);
```

---

## 13. API Endpoints

### 13.1 Report Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/clients/{id}/reports | List reports |
| POST | /api/clients/{id}/reports | Generate report |
| GET | /api/clients/{id}/reports/{rid} | Get report details |
| GET | /api/clients/{id}/reports/{rid}/download | Download file |
| DELETE | /api/clients/{id}/reports/{rid} | Delete report |
| POST | /api/clients/{id}/reports/{rid}/regenerate | Regenerate |
| POST | /api/clients/{id}/reports/{rid}/approve | Approve |
| POST | /api/clients/{id}/reports/{rid}/reject | Reject |
| POST | /api/clients/{id}/reports/{rid}/publish | Publish to portal |

### 13.2 Report Types Endpoint

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/report-types | List available report types |

---

## 14. Implementation Checklist

### Phase 1: Core Reports

- [ ] Report model and migrations
- [ ] Basic PDF generation (Browsershot)
- [ ] Cover page template
- [ ] Executive summary section
- [ ] Tax analysis sections
- [ ] Report storage and retrieval

### Phase 2: Full Report Types

- [ ] Impact Analysis report
- [ ] Advisory Letter report
- [ ] Tax Computation report
- [ ] Chart generation
- [ ] All sections implemented

### Phase 3: Advanced Features

- [ ] Word document generation
- [ ] Approval workflow
- [ ] Report versioning
- [ ] Portal publishing
- [ ] Scenario reports

### Phase 4: Polish

- [ ] Print optimization
- [ ] Template refinement
- [ ] Performance optimization
- [ ] Queue handling for large reports

---

*This document should be updated as report system requirements evolve during development.*
