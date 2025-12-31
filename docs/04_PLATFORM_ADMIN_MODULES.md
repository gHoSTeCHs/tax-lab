# TaxLab — Platform Admin Modules

## Document Information

| Item | Detail |
|------|--------|
| Document | Platform Admin Modules |
| Version | 1.0 |
| Last Updated | December 2024 |
| Audience | Developers, Product Team |

---

## 1. Overview

The Platform Admin section is the control center for operating TaxLab as a business. It provides tools for the internal team to manage tenants (subscribing firms), configure subscription plans, maintain tax legislation and rules, publish educational content, and monitor platform health.

### 1.1 Access

- **URL:** `/admin/*`
- **Guard:** admin
- **Users:** Platform administrators only
- **Security:** 2FA mandatory, enhanced logging

### 1.2 Module Summary

| Module | Purpose |
|--------|---------|
| Dashboard | Platform overview and key metrics |
| Tenant Management | Manage subscribing firms |
| Subscription Plans | Configure pricing and features |
| Tax Rules Management | Maintain tax legislation and rules |
| CPD Content Management | Create and publish training courses |
| Knowledge Base Management | Maintain reference documentation |
| Support Tools | Help resolve customer issues |
| Platform Analytics | Business intelligence and reporting |
| System Settings | Platform configuration |

---

## 2. Admin Dashboard

### 2.1 Purpose

Provide platform administrators with an at-a-glance view of business health, recent activity, and items requiring attention.

### 2.2 Dashboard Components

**Key Metrics Cards**

| Metric | Description | Visual |
|--------|-------------|--------|
| Active Tenants | Firms on paid plans | Number + trend |
| Monthly Recurring Revenue | Total MRR | Currency + trend |
| Trial Conversions (30d) | Trials converted to paid | Percentage + count |
| Active Users (7d) | Unique firm users active | Number |
| Reports Generated (7d) | Total reports created | Number |
| Support Tickets Open | Unresolved tickets | Number with priority breakdown |

**Revenue Chart**

- Line chart showing MRR over past 12 months
- Breakdown by plan tier
- Comparison to previous period
- Annotations for significant events

**Tenant Funnel**

Visual funnel showing:
- Total signups (30d)
- Completed onboarding
- First calculation run
- First report generated
- Trial converted

**Recent Activity Feed**

Chronological list of significant events:
- New tenant signups
- Plan upgrades/downgrades
- Tenant cancellations
- Tax rule publications
- CPD course launches
- System alerts

**Attention Required**

Items needing admin action:
- Failed payments requiring follow-up
- Tenants approaching trial expiry
- Support tickets awaiting response
- Tax rules pending review
- System health warnings

### 2.3 Filters & Controls

- Date range selector (default: last 30 days)
- Refresh button (data auto-refreshes every 5 minutes)
- Export dashboard data as CSV
- Customize visible widgets (per admin preference)

### 2.4 Data Sources

| Widget | Data Source | Refresh Rate |
|--------|-------------|--------------|
| Metrics cards | Aggregated queries with caching | 5 minutes |
| Revenue chart | Billing records | 1 hour |
| Tenant funnel | Event tracking | 15 minutes |
| Activity feed | Activity log | Real-time |
| Attention items | Multiple sources | 5 minutes |

---

## 3. Tenant Management

### 3.1 Purpose

Enable administrators to view, manage, and support subscribing firms throughout their lifecycle.

### 3.2 Tenant List View

**Display Columns:**
- Firm name
- Primary contact email
- Plan name
- Status (trial, active, suspended, churned)
- Client count / limit
- User count / limit
- Created date
- Last activity

**Filtering Options:**
- Status filter (multi-select)
- Plan filter (multi-select)
- Created date range
- Last activity range
- Search by name or email

**Sorting Options:**
- Any column ascending/descending
- Default: Last activity descending

**Bulk Actions:**
- Export selected to CSV
- Send bulk communication
- (No bulk status changes for safety)

### 3.3 Tenant Detail View

**Overview Section**

| Field | Description |
|-------|-------------|
| Firm Name | Legal/trading name |
| Slug | URL-friendly identifier |
| Primary Contact | Name and email of account owner |
| Phone | Contact phone |
| Address | Business address |
| Registration Number | CAC or professional registration |
| Created | Account creation date |
| Status | Current status with history |

**Subscription Section**

| Field | Description |
|-------|-------------|
| Current Plan | Plan name and tier |
| Billing Cycle | Monthly or annual |
| Price | Current billing amount |
| Next Billing Date | When next payment due |
| Payment Method | Card type and last 4 digits |
| Payment Status | Current, overdue, failed |

**Usage Section**

| Metric | Current | Limit |
|--------|---------|-------|
| Tax Clients | X | Y |
| Users | X | Y |
| Reports (this month) | X | Y or Unlimited |
| Storage Used | X MB | Y MB |

Visual progress bars showing usage against limits.

**Users Section**

Table of firm users:
- Name
- Email
- Role
- Status
- Last login
- Actions (view, impersonate if permitted)

**Activity Section**

Recent activity log for this tenant:
- User actions
- Calculations performed
- Reports generated
- Login events
- Settings changes

**Billing History**

List of invoices and payments:
- Invoice date
- Amount
- Status (paid, pending, failed)
- Download invoice PDF

**Notes Section**

Internal notes from support team:
- Timestamped notes
- Added by (admin name)
- Categorized (support, sales, technical)
- Add new note form

### 3.4 Tenant Actions

**Available Actions:**

| Action | Description | Who Can Perform |
|--------|-------------|-----------------|
| Edit Details | Modify firm information | Admin, Super Admin |
| Change Plan | Upgrade or downgrade subscription | Super Admin |
| Extend Trial | Add days to trial period | Admin, Super Admin |
| Apply Credit | Add billing credit | Super Admin |
| Suspend Account | Temporarily disable access | Admin, Super Admin |
| Reactivate Account | Re-enable suspended account | Admin, Super Admin |
| Impersonate User | Log in as firm user | Super Admin |
| Export Data | Download all tenant data | Super Admin |
| Delete Account | Permanently remove (with confirmation) | Super Admin |

**Action Confirmation:**

All significant actions require:
- Confirmation modal
- Reason entry (logged)
- Password re-entry for destructive actions

### 3.5 Impersonation Feature

Allows Super Admins to access a tenant's account as if they were a firm user.

**Controls:**
- Select which firm user to impersonate
- Clear visual indicator showing impersonation active
- All actions logged as "Admin (impersonating User X)"
- Exit button always visible
- Session timeout: 30 minutes
- Cannot change passwords or billing while impersonating

**Audit Trail:**
- Impersonation start logged
- All actions during impersonation tagged
- Impersonation end logged
- Reason for impersonation required

### 3.6 Data Model

**Table: firms**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| name | varchar(255) | Firm name |
| slug | varchar(100) | URL identifier |
| email | varchar(255) | Primary contact email |
| phone | varchar(50) | Contact phone |
| address | text | Business address |
| registration_number | varchar(100) | CAC or professional registration |
| plan_id | ULID | Foreign key to plans |
| billing_cycle | enum | monthly, annual |
| status | enum | trial, active, suspended, churned |
| trial_ends_at | timestamp | When trial expires |
| subscription_starts_at | timestamp | When paid subscription began |
| subscription_ends_at | timestamp | When subscription expires |
| settings | jsonb | Firm-specific settings |
| created_at | timestamp | Creation date |
| updated_at | timestamp | Last modified |
| deleted_at | timestamp | Soft delete |

---

## 4. Subscription Plans Management

### 4.1 Purpose

Allow administrators to configure subscription plans with flexible pricing, limits, and feature toggles.

### 4.2 Plan List View

**Display:**
- Plan name
- Monthly price
- Annual price
- Active tenants count
- Status (active, deprecated)
- Display order

**Actions:**
- Create new plan
- Edit plan
- Deprecate plan (no new signups)
- Reorder plans

### 4.3 Plan Configuration

**Basic Information**

| Field | Type | Description |
|-------|------|-------------|
| Name | text | Display name (e.g., "Professional") |
| Slug | text | Internal identifier |
| Description | textarea | Brief description for marketing |
| Monthly Price | currency | Price in Naira |
| Annual Price | currency | Price in Naira (typically discounted) |
| Is Active | boolean | Available for new signups |
| Display Order | number | Order in pricing table |
| Is Featured | boolean | Highlight on pricing page |

**Usage Limits**

| Limit | Type | Description |
|-------|------|-------------|
| Max Clients | number / unlimited | Maximum tax clients |
| Max Users | number / unlimited | Maximum firm users |
| Max Reports Per Month | number / unlimited | Monthly report limit |
| Max Storage (MB) | number / unlimited | File storage limit |

**Feature Toggles**

| Feature | Type | Description |
|---------|------|-------------|
| Scenario Modeling | boolean | Access to scenario features |
| Advanced Scenarios | boolean | Multi-parameter scenarios |
| Report Approval Workflow | boolean | Approval before finalization |
| Client Portal | boolean | Enable client-facing portal |
| Custom Branding | boolean | Always enabled (all plans) |
| API Access | boolean | API key generation |
| Priority Support | boolean | Faster response SLA |
| CPD Access | boolean | Access to training courses |
| Knowledge Base | boolean | Access to documentation |
| Analytics Dashboard | boolean | Firm-level analytics |
| Bulk Import | boolean | CSV/Excel client import |
| Custom Report Templates | boolean | Create custom templates |

### 4.4 Plan Change Rules

**Upgrades:**
- Immediate effect
- Pro-rated billing
- New limits apply instantly
- Features unlocked immediately

**Downgrades:**
- Effective at next billing cycle
- Must be within new plan limits
- Warning if over limits
- Features locked at cycle end

### 4.5 Data Model

**Table: plans**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| name | varchar(100) | Display name |
| slug | varchar(50) | Internal identifier |
| description | text | Marketing description |
| monthly_price | decimal(10,2) | Monthly price (Naira) |
| annual_price | decimal(10,2) | Annual price (Naira) |
| is_active | boolean | Available for signup |
| display_order | integer | Sort order |
| is_featured | boolean | Highlight in UI |
| created_at | timestamp | Creation date |
| updated_at | timestamp | Last modified |

**Table: plan_limits**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| plan_id | ULID | Foreign key to plans |
| limit_key | varchar(50) | Limit identifier |
| limit_value | integer | Value (-1 for unlimited) |

**Table: plan_features**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| plan_id | ULID | Foreign key to plans |
| feature_key | varchar(50) | Feature identifier |
| is_enabled | boolean | Feature available |

---

## 5. Tax Rules Management

### 5.1 Purpose

Maintain the tax legislation and rules that power all calculations. This is a critical module as accuracy directly impacts platform value.

### 5.2 Legislation Management

**Legislation List**

| Field | Description |
|-------|-------------|
| Name | Full name of legislation |
| Short Name | Abbreviated reference |
| Year | Year of enactment |
| Effective Date | When legislation takes effect |
| Status | draft, current, superseded |
| Version | Version number |

**Actions:**
- Create new legislation
- Edit details
- View associated rules
- Supersede (mark as replaced)

### 5.3 Tax Rule Categories

Rules organized by tax type:

**Personal Income Tax**
- Tax bands and rates
- Relief allowances
- Exemption thresholds
- Residency rules
- Specific deductions

**Company Income Tax**
- Standard rates
- Small company threshold
- Medium company threshold
- Minimum tax rules
- Exemptions

**Value Added Tax**
- Standard rate
- Zero-rated items
- Exempt items
- Input recovery rules
- Registration thresholds

**Capital Gains Tax**
- Rates for individuals
- Rates for companies
- Exemption thresholds
- Rollover relief rules

**Development Levy**
- Rate
- Applicable entities
- Exemptions

**Withholding Tax**
- Rates by payment type
- Thresholds
- Exemptions

### 5.4 Rule Configuration

**Rule Definition**

| Field | Type | Description |
|-------|------|-------------|
| Legislation | reference | Parent legislation |
| Category | enum | Tax type category |
| Rule Type | enum | rate, threshold, allowance, exemption, formula |
| Name | text | Descriptive name |
| Description | textarea | Plain-English explanation |
| Applies To | enum | company, individual, both |
| Effective From | date | When rule starts |
| Effective To | date | When rule ends (null = ongoing) |
| Version | number | Rule version |
| Status | enum | draft, published, deprecated |

**Rule Parameters**

Flexible structure based on rule type:

*Rate Table Example (PIT):*
```json
{
  "bands": [
    {"min": 0, "max": 800000, "rate": 0},
    {"min": 800001, "max": 2800000, "rate": 7},
    {"min": 2800001, "max": 5200000, "rate": 11},
    {"min": 5200001, "max": 10400000, "rate": 15},
    {"min": 10400001, "max": 50000000, "rate": 19},
    {"min": 50000001, "max": null, "rate": 25}
  ],
  "currency": "NGN"
}
```

*Threshold Example:*
```json
{
  "threshold": 50000000,
  "comparison": "less_than_or_equal",
  "applies_when": "annual_turnover",
  "effect": "qualifies_as_small_company"
}
```

*Allowance Example:*
```json
{
  "name": "Rent Relief",
  "calculation": "percentage_of_value",
  "percentage": 20,
  "maximum": 500000,
  "value_source": "annual_rent_paid"
}
```

### 5.5 Rule Versioning

**Why Versioning Matters:**
- Historical calculations must remain reproducible
- Legislation changes over time
- Audit trail requirements

**Version Control:**
- Each rule change creates new version
- Old versions retained indefinitely
- Calculations reference specific version used
- Can view calculation with original rules

**Publication Workflow:**

```
Draft → Review → Published
         ↓
      Rejected → Draft (with notes)
```

1. Content Manager or Admin creates rule in draft
2. Admin reviews for accuracy
3. Super Admin publishes (makes live)
4. Published rules used in new calculations
5. Old published rules remain for historical reference

### 5.6 Rule Testing

Before publishing, administrators can:
- Enter sample financial data
- Run calculation with new rules
- Compare results to expected values
- Compare results to old rules
- Document test cases

**Test Case Management:**
- Save test cases per rule
- Run all tests when rule changes
- Flag if results change unexpectedly
- Require passing tests before publication

### 5.7 Data Model

**Table: tax_legislations**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| name | varchar(255) | Full name |
| short_name | varchar(50) | Abbreviated name |
| year | integer | Year of enactment |
| effective_date | date | When effective |
| status | enum | draft, current, superseded |
| superseded_by | ULID | Reference to replacing legislation |
| created_at | timestamp | Creation date |
| updated_at | timestamp | Last modified |

**Table: tax_rules**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| legislation_id | ULID | Foreign key to legislation |
| category | varchar(50) | Tax category |
| rule_type | varchar(50) | Type of rule |
| name | varchar(255) | Rule name |
| description | text | Plain-English description |
| applies_to | enum | company, individual, both |
| parameters | jsonb | Rule parameters |
| effective_from | date | Start date |
| effective_to | date | End date |
| version | integer | Version number |
| status | enum | draft, published, deprecated |
| published_at | timestamp | When published |
| published_by | ULID | Admin who published |
| created_at | timestamp | Creation date |
| updated_at | timestamp | Last modified |

**Table: tax_rule_test_cases**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| rule_id | ULID | Foreign key to rule |
| name | varchar(255) | Test case name |
| inputs | jsonb | Test input data |
| expected_output | jsonb | Expected result |
| last_run_at | timestamp | When last tested |
| last_run_passed | boolean | Test result |
| created_at | timestamp | Creation date |

---

## 6. CPD Content Management

### 6.1 Purpose

Create and manage continuing professional development courses to help practitioners understand and apply NTA 2025.

### 6.2 Course Structure

```
Course
├── Title & Description
├── Metadata (duration, level, CPD hours)
├── Modules
│   ├── Module 1
│   │   ├── Lessons
│   │   │   ├── Lesson 1 (text, video, quiz)
│   │   │   └── Lesson 2
│   │   └── Module Quiz
│   └── Module 2
│       └── ...
├── Final Assessment
└── Certificate Template
```

### 6.3 Course Management

**Course List View**

| Field | Description |
|-------|-------------|
| Title | Course name |
| Status | draft, published, archived |
| Duration | Total hours |
| CPD Hours | Accredited hours |
| Enrollments | Total enrolled users |
| Completion Rate | % who completed |
| Last Updated | Most recent change |

**Course Editor**

*Basic Information:*
- Title
- Short description
- Full description
- Category (e.g., "NTA 2025", "VAT", "Tax Planning")
- Difficulty level (beginner, intermediate, advanced)
- Estimated duration
- CPD accreditation hours
- Prerequisites (other courses)
- Featured image

*Module Builder:*
- Add/remove/reorder modules
- Module title and description
- Module completion requirements

*Lesson Editor:*
- Lesson title
- Content type (text, video, interactive)
- Rich text content with formatting
- Embedded media (video URL, images)
- Downloadable resources (PDFs)
- Completion criteria (viewed, time spent, quiz passed)

*Quiz Builder:*
- Question types: multiple choice, true/false, multi-select
- Question text with optional image
- Answer options
- Correct answer(s)
- Explanation for correct answer
- Points value
- Passing threshold

*Assessment Builder:*
- Final exam questions
- Time limit (optional)
- Passing score
- Retake policy
- Certificate trigger

### 6.4 Publication Workflow

```
Draft → Ready for Review → Published
            ↓
         Needs Work → Draft
```

**Draft:** Course being created/edited
**Ready for Review:** Content complete, awaiting approval
**Published:** Live and available to users
**Archived:** No longer available but retained

### 6.5 Certificate Management

**Certificate Template:**
- Firm branding integration
- Course name and description
- Completion date
- CPD hours awarded
- Unique verification code
- Digital signature

**Verification System:**
- Public verification URL
- Enter certificate code
- Displays certificate validity

### 6.6 Data Model

**Table: cpd_courses**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| title | varchar(255) | Course title |
| slug | varchar(100) | URL identifier |
| short_description | text | Brief description |
| full_description | text | Full description |
| category | varchar(100) | Course category |
| difficulty | enum | beginner, intermediate, advanced |
| duration_hours | decimal(4,1) | Estimated hours |
| cpd_hours | decimal(4,1) | Accredited hours |
| featured_image | varchar(255) | Image path |
| status | enum | draft, review, published, archived |
| published_at | timestamp | Publication date |
| created_at | timestamp | Creation date |
| updated_at | timestamp | Last modified |

**Table: cpd_modules**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| course_id | ULID | Foreign key to course |
| title | varchar(255) | Module title |
| description | text | Module description |
| sort_order | integer | Display order |
| created_at | timestamp | Creation date |
| updated_at | timestamp | Last modified |

**Table: cpd_lessons**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| module_id | ULID | Foreign key to module |
| title | varchar(255) | Lesson title |
| content_type | enum | text, video, interactive |
| content | text | Lesson content |
| video_url | varchar(255) | Video embed URL |
| duration_minutes | integer | Lesson duration |
| sort_order | integer | Display order |
| created_at | timestamp | Creation date |
| updated_at | timestamp | Last modified |

---

## 7. Knowledge Base Management

### 7.1 Purpose

Maintain searchable reference documentation explaining tax legislation in plain language.

### 7.2 Content Structure

```
Knowledge Base
├── Categories
│   ├── NTA 2025 Overview
│   │   └── Articles
│   ├── Company Income Tax
│   │   └── Articles
│   ├── Personal Income Tax
│   │   └── Articles
│   ├── VAT
│   │   └── Articles
│   └── ...
└── Search Index
```

### 7.3 Article Editor

**Article Fields:**

| Field | Type | Description |
|-------|------|-------------|
| Title | text | Article title |
| Slug | text | URL identifier |
| Category | reference | Parent category |
| Summary | textarea | Brief summary for listings |
| Content | rich text | Full article content |
| Related Legislation | references | Links to tax rules |
| Related Articles | references | Cross-references |
| Tags | multi-select | Searchable tags |
| Status | enum | draft, published, archived |
| Author | reference | Admin who wrote |
| Last Reviewed | date | Quality review date |

**Rich Text Features:**
- Headings (H2, H3, H4)
- Paragraphs and lists
- Tables
- Code blocks (for formulas)
- Callout boxes (info, warning, example)
- Internal links (to other articles)
- External links
- Images with captions
- Embedded calculation examples

### 7.4 Search Configuration

**Indexed Fields:**
- Title (boosted weight)
- Summary (boosted weight)
- Content
- Tags
- Category name

**Search Features:**
- Full-text search
- Fuzzy matching
- Highlighted excerpts
- Filter by category
- Filter by tag
- Sort by relevance or date

### 7.5 Data Model

**Table: kb_categories**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| name | varchar(100) | Category name |
| slug | varchar(50) | URL identifier |
| description | text | Category description |
| icon | varchar(50) | Icon identifier |
| sort_order | integer | Display order |
| parent_id | ULID | Parent category (for nesting) |
| created_at | timestamp | Creation date |
| updated_at | timestamp | Last modified |

**Table: kb_articles**

| Column | Type | Description |
|--------|------|-------------|
| id | ULID | Primary key |
| category_id | ULID | Foreign key to category |
| title | varchar(255) | Article title |
| slug | varchar(100) | URL identifier |
| summary | text | Brief summary |
| content | text | Full content |
| status | enum | draft, published, archived |
| author_id | ULID | Admin who created |
| published_at | timestamp | Publication date |
| last_reviewed_at | date | Last review date |
| view_count | integer | Total views |
| helpful_count | integer | "Was this helpful?" yes |
| created_at | timestamp | Creation date |
| updated_at | timestamp | Last modified |

---

## 8. Support Tools

### 8.1 Purpose

Help support staff efficiently resolve customer issues.

### 8.2 Tenant Lookup

Quick search to find tenant by:
- Firm name
- Email address
- Domain/slug
- Any user email

Results show tenant card with key info and quick actions.

### 8.3 Calculation Debugger

When a tenant reports calculation issues:

1. Search by tenant and client
2. View calculation history
3. Select specific calculation
4. See full input data
5. See tax rules version used
6. Re-run calculation step-by-step
7. Compare expected vs. actual results
8. Export debug information

### 8.4 Activity Viewer

For specific tenant:
- All user activity
- Filterable by user, action type, date
- Expandable detail view
- Export for analysis

### 8.5 System Health

**Monitoring Dashboard:**
- Queue status (jobs pending, processing, failed)
- Database connection health
- Cache hit rates
- Storage usage
- Error rates (last 24h)
- Slow queries

**Alert Management:**
- Active alerts
- Alert history
- Acknowledge alerts
- Escalation status

### 8.6 Support Notes

Per-tenant notes system:
- Add timestamped notes
- Categorize (billing, technical, general)
- Search across notes
- Flag for follow-up

---

## 9. Platform Analytics

### 9.1 Purpose

Provide business intelligence for strategic decision-making.

### 9.2 Available Reports

**Acquisition Report**
- New signups by source
- Trial starts
- Conversion funnel
- Time to conversion
- Acquisition cost (if tracked)

**Revenue Report**
- MRR by plan
- MRR growth rate
- Churn revenue
- Net revenue
- Cohort analysis

**Engagement Report**
- Daily/weekly/monthly active users
- Feature usage breakdown
- Calculations per tenant
- Reports per tenant
- Training completion rates

**Retention Report**
- Tenant retention curves
- Churn analysis
- At-risk tenant identification
- Reactivation tracking

**Product Report**
- Most used features
- Least used features
- Error rates by feature
- Performance by feature

### 9.3 Custom Reports

Build custom reports by:
- Selecting metrics
- Defining dimensions
- Applying filters
- Choosing visualization
- Scheduling delivery

### 9.4 Data Export

Export capabilities:
- CSV download
- Scheduled email reports
- API access (for BI tools)

---

## 10. System Settings

### 10.1 Purpose

Configure platform-wide settings that affect all tenants.

### 10.2 Configuration Areas

**General Settings**
- Platform name
- Support email
- Default currency
- Default timezone
- Maintenance mode toggle

**Email Settings**
- From name and address
- Reply-to address
- Email footer text
- Template customization

**Security Settings**
- Password policies
- Session timeouts
- 2FA requirements
- IP whitelist (for admin)
- Rate limits

**Feature Flags**
- Enable/disable features platform-wide
- Beta feature toggles
- Maintenance feature locks

**Integration Settings**
- Payment gateway configuration
- Email service configuration
- Storage configuration
- Analytics tracking IDs

### 10.3 Configuration Audit

All setting changes logged:
- What changed
- Old value
- New value
- Who changed
- When changed

---

## 11. Implementation Checklist

### Phase 1: Core Admin

- [ ] Admin authentication and 2FA
- [ ] Admin dashboard with basic metrics
- [ ] Tenant list and detail views
- [ ] Basic tenant management actions
- [ ] Plan management CRUD

### Phase 2: Content Management

- [ ] Tax legislation management
- [ ] Tax rules CRUD with versioning
- [ ] Rule testing framework
- [ ] Publication workflow
- [ ] Knowledge base management

### Phase 3: Enhanced Features

- [ ] Impersonation feature
- [ ] CPD content management
- [ ] Support tools
- [ ] Platform analytics
- [ ] System settings management

### Phase 4: Polish

- [ ] Advanced analytics and custom reports
- [ ] Automated alerts and monitoring
- [ ] Bulk operations
- [ ] API for external integrations

---

*This document should be updated as admin requirements evolve during development.*
