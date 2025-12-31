# TaxLab — Product Vision Document

## Document Information

| Item | Detail |
|------|--------|
| Product Name | TaxLab (Placeholder) |
| Version | 1.0 |
| Last Updated | December 2024 |
| Status | Planning Phase |

---

## Executive Summary

TaxLab is a comprehensive tax analysis and reporting platform designed specifically for Nigerian tax practitioners navigating the Nigeria Tax Act 2025 (NTA 2025). The platform enables accounting firms, tax consultants, and independent practitioners to efficiently analyze how the new tax reforms impact their clients, model different scenarios, generate professional reports, and deliver actionable recommendations.

The NTA 2025 represents Nigeria's most significant tax overhaul in decades, consolidating multiple tax laws and introducing substantial changes to rates, thresholds, and administrative requirements. Tax practitioners face an unprecedented challenge: analyzing the impact for each of their clients while the reforms take effect on January 1, 2026.

TaxLab addresses this challenge by providing an intelligent, automated analysis engine wrapped in an intuitive interface that makes complex tax calculations accessible and reportable.

---

## The Problem

### Market Context

On June 26, 2025, President Bola Ahmed Tinubu signed four landmark tax reform bills into law:

1. **Nigeria Tax Act (NTA)** — Consolidates company income tax, personal income tax, VAT, capital gains tax, and stamp duties into a single act
2. **Nigeria Tax Administration Act (NTAA)** — Unified administrative framework with new compliance requirements
3. **Nigeria Revenue Service (Establishment) Act** — Transitions FIRS to the new NRS with expanded powers
4. **Joint Revenue Board (Establishment) Act** — Harmonizes federal, state, and local revenue administration

These reforms introduce sweeping changes:

- New personal income tax bands with ₦800,000 annual exemption
- Corporate income tax reduction from 30% to 25%
- Unified 4% development levy replacing multiple earmarked taxes
- Expanded VAT input recovery (now includes services and capital assets)
- Small company threshold increased from ₦25M to ₦50M turnover
- Capital gains tax aligned with income tax rates (up to 30% for companies)
- Mandatory e-invoicing for large taxpayers
- NIN as automatic Tax ID for individuals

### Practitioner Pain Points

Nigerian tax practitioners currently face:

**Time Pressure**
- Hundreds of clients need analysis before January 1, 2026
- Each analysis requires manual calculation across multiple tax types
- No standardized tools exist for the new regime
- Excel-based approaches are error-prone and time-consuming

**Complexity**
- Multiple interacting tax types with cascading effects
- Old regime vs. new regime comparisons needed for every client
- Edge cases and exemptions vary by industry, size, and structure
- Frequent NRS guidance updates require recalculation

**Client Communication**
- Clients demand clear explanations of how reforms affect them
- Professional reports expected, currently produced manually
- Optimization opportunities often missed due to analysis time constraints
- No efficient way to model "what-if" scenarios

**Compliance Risk**
- Miscalculations expose both practitioner and client to penalties
- Audit trail requirements for professional liability
- New e-invoicing and reporting deadlines to track

### Current Solutions (And Why They Fail)

| Solution | Limitation |
|----------|------------|
| Microsoft Excel | Manual, error-prone, no collaboration, no report generation |
| Generic Accounting Software | Not updated for NTA 2025, Nigerian-specific features lacking |
| Manual Calculation | Time-consuming, doesn't scale, inconsistent quality |
| Big 4 Internal Tools | Proprietary, not available to smaller practices |

---

## The Solution

### What is TaxLab?

TaxLab is a multi-tenant SaaS platform that automates tax impact analysis, scenario modeling, and report generation for Nigerian tax practitioners under the NTA 2025 regime.

### Core Value Proposition

**For Tax Practitioners:**
"Analyze any client's NTA 2025 impact in minutes, not hours. Generate professional reports instantly. Never miss an optimization opportunity."

**For Their Clients:**
"Understand exactly how the tax reforms affect your business, with clear numbers and actionable recommendations from your trusted advisor."

### Key Differentiators

1. **Purpose-Built for NTA 2025** — Not a generic tax tool adapted for Nigeria; built from the ground up around the new legislation

2. **Comparison Engine** — Automatically calculates old regime vs. new regime for every analysis, showing exact impact

3. **Full White-Label Branding** — Every report, email, and client-facing element shows the practitioner's brand, not ours

4. **Client Portal** — Practitioners can give their clients secure access to view reports and track their tax position

5. **Scenario Modeling** — "What if we restructure? What if revenue grows 20%? What if we defer this transaction?"

6. **Optimization Intelligence** — Automatically identifies tax-saving opportunities based on client profile

7. **Always Current** — Tax rules updated centrally when NRS issues new guidance; all users benefit immediately

8. **Training Included** — Built-in CPD-accredited courses on the new tax laws

---

## Target Users

### Primary: Tax Practitioners

**Solo Practitioners**
- Independent tax consultants
- Freelance accountants with tax practice
- Typically 10-30 clients
- Need efficiency and professional presentation
- Price-sensitive but value time savings

**Small-to-Medium Firms**
- 2-15 staff members
- 50-300 clients
- Need collaboration features
- Require consistent quality across team
- Value client management capabilities

**Mid-Tier Accounting Firms**
- 15-50 staff members
- 200-1000+ clients
- Need workflow management
- Require approval processes
- Value analytics and insights
- May have multiple offices

### Secondary: Tax Clients (Via Client Portal)

**SME Business Owners**
- Want to understand their tax position
- Prefer self-service access to reports
- Value transparency from their accountant

**Finance Directors / CFOs**
- Need data for financial planning
- Want scenario comparisons for decision-making
- Require audit-ready documentation

### Excluded from Initial Scope

- Individual taxpayers doing their own taxes (B2C)
- Big 4 / large international firms (they build in-house)
- Government / regulatory bodies
- Non-Nigerian tax jurisdictions

---

## User Personas

### Persona 1: Adaeze — Solo Tax Consultant

**Background**
- 38 years old, CITN member
- Former Big 4 senior, started own practice 5 years ago
- 45 active clients (mix of companies and high-net-worth individuals)
- Works from home office with one part-time assistant

**Goals**
- Deliver the same quality as large firms
- Maximize billable time, minimize admin work
- Build reputation through professional deliverables
- Retain clients during transition period

**Pain Points**
- Spends weekends doing manual calculations
- Reports take 3-4 hours each to produce
- Worried about errors affecting proessional reputation
- Can't afford expensive enterprise software

**TaxLab Value**
- Full analysis in 10 minutes instead of 3 hours
- Professional reports generated instantly
- Confident accuracy with auditable calculations
- Affordable pricing that pays for itself in time saved

---

### Persona 2: Emeka — Partner at Mid-Size Firm

**Background**
- 52 years old, ICAN Fellow
- Partner at 25-person accounting firm in Lagos
- Oversees tax practice with 4 managers and 8 associates
- 400+ tax clients across various industries

**Goals**
- Ensure consistent quality across all team members
- Meet January 2026 deadline for all clients
- Identify upselling opportunities (advisory, restructuring)
- Maintain firm's reputation as tax experts

**Pain Points**
- Associates have varying skill levels
- Review process is bottleneck
- No visibility into team workload
- Difficult to track which clients have been analyzed

**TaxLab Value**
- Standardized calculations eliminate quality variance
- Approval workflow ensures partner oversight
- Dashboard shows progress across entire portfolio
- Optimization tracker identifies advisory opportunities

---

### Persona 3: Funke — Finance Director at Manufacturing Company

**Background**
- 44 years old, client of a tax firm
- FD at mid-size manufacturing company
- Reports to CEO and board on financial matters
- Relies on external tax advisors but wants to understand implications

**Goals**
- Understand tax impact for budget planning
- Get board-ready summaries from tax advisor
- Model different business scenarios
- Ensure company is compliant and optimized

**Pain Points**
- Tax reports from advisor are dense and confusing
- Has to schedule calls to ask basic questions
- Can't easily compare different strategic options
- Doesn't know what she doesn't know

**TaxLab Value (via Client Portal)**
- Access reports anytime without calling advisor
- Clear executive summaries written for non-accountants
- Can view scenario comparisons shared by advisor
- Transparency builds trust in advisory relationship

---

## Feature Overview

### Core Platform Features

| Feature | Description |
|---------|-------------|
| Multi-Tenant Architecture | Each firm operates in isolated environment with own branding, users, and data |
| Role-Based Access | Granular permissions for Partners, Managers, Associates, and Viewers |
| Client Management | Full CRM for tax clients with profile, history, and document management |
| Financial Data Entry | Structured input for annual financials with validation and import capabilities |
| Tax Calculation Engine | Automated calculation across all tax types with old vs. new regime comparison |
| Scenario Modeling | "What-if" analysis with adjustable parameters and side-by-side comparison |
| Report Generation | Professional PDF/Word reports with configurable sections and full branding |
| Optimization Tracker | Automatic identification and tracking of tax-saving opportunities |
| Client Portal | Secure client-facing access to view reports and tax position |
| Knowledge Base | Searchable reference for NTA 2025 legislation with plain-English explanations |
| CPD Training | Accredited courses on new tax laws with progress tracking and certificates |
| Audit Trail | Complete logging of all calculations, changes, and access for compliance |

### Platform Administration Features

| Feature | Description |
|---------|-------------|
| Tenant Management | Onboard, configure, and manage subscribing firms |
| Subscription Management | Flexible plan configuration with customizable pricing and limits |
| Tax Rules Engine | Centralized management of tax legislation with versioning |
| Content Management | Create and publish CPD courses and knowledge base content |
| Platform Analytics | Cross-tenant metrics for business intelligence |
| Support Tools | Tenant impersonation and debugging capabilities for support team |

---

## User Journeys

### Journey 1: New Firm Onboarding

```
Day 1: Discovery & Signup
├── Practitioner discovers TaxLab (referral, search, conference)
├── Visits website, reviews features and pricing
├── Signs up for trial account
├── Verifies email
└── Logs in for first time

Day 1-3: Setup
├── Completes firm profile (name, address, registration)
├── Uploads logo and configures branding
├── Invites team members (if applicable)
└── Explores platform with sample data

Day 3-7: First Value
├── Creates first real client
├── Enters client's financial data
├── Runs first tax analysis
├── Reviews old vs. new comparison
├── Generates first report
└── "Aha moment" — realizes time saved

Day 7-14: Adoption
├── Adds more clients
├── Team members start using platform
├── Completes CPD course on NTA 2025
├── Shares report with actual client
└── Client feedback reinforces value

Day 14+: Conversion
├── Approaches trial end
├── Reviews usage and value delivered
├── Selects appropriate plan
├── Enters payment details
└── Continues as paying customer
```

### Journey 2: Client Analysis Workflow

```
Preparation
├── Select or create tax client
├── Confirm client details and entity type
└── Check for existing financial data

Data Entry
├── Enter current year financials
├── System validates completeness
├── Flag any unusual entries for review
└── Save and confirm data

Analysis
├── Click "Run Full Analysis"
├── System calculates all applicable taxes
├── Old regime vs. new regime compared
├── Results displayed in dashboard view
└── Optimization opportunities highlighted

Review
├── Drill into specific tax components
├── Review calculation methodology
├── Check legislative references
├── Note any client-specific considerations
└── Mark analysis as reviewed

Scenario Modeling (Optional)
├── Create "what-if" scenario
├── Adjust parameters (revenue, structure, timing)
├── Compare scenario results
├── Save useful scenarios for discussion
└── Include in report if relevant

Reporting
├── Open report generator
├── Select report type and format
├── Choose sections to include
├── Preview report
├── Generate final document
└── Submit for approval (if required)

Delivery
├── Download report or send via platform
├── Client receives notification (if portal enabled)
├── Client views report in portal
├── Practitioner follows up with advisory call
└── Mark engagement as complete
```

### Journey 3: Client Portal Experience

```
Invitation
├── Practitioner enables portal access for client
├── System sends branded invitation email
├── Client clicks link and creates password
└── Client logs into their portal

Dashboard
├── Sees company name and practitioner branding
├── Views current tax year summary
├── Sees key metrics (total tax liability, change from old regime)
└── Notices any alerts or recommendations

Reports
├── Accesses list of available reports
├── Views executive summary
├── Downloads full PDF if needed
├── Reviews historical reports
└── No editing capability — read-only

Scenarios (if shared)
├── Practitioner shares specific scenarios
├── Client views comparison table
├── Understands options for discussion
└── Can comment or request meeting

Communication
├── Views practitioner contact details
├── Can send message through portal
├── Receives notifications of new reports
└── All communication logged
```

---

## Success Metrics

### Business Metrics

| Metric | Definition | Target (Year 1) |
|--------|------------|-----------------|
| Registered Firms | Firms that complete signup | 500 |
| Paying Customers | Firms on paid plans | 150 |
| Monthly Recurring Revenue | Total monthly subscription revenue | ₦15,000,000 |
| Trial Conversion Rate | % of trials converting to paid | 30% |
| Monthly Churn Rate | % of paying customers cancelling | < 5% |
| Net Promoter Score | Customer satisfaction metric | > 40 |

### Product Metrics

| Metric | Definition | Target |
|--------|------------|--------|
| Clients Analyzed | Total tax clients with completed analysis | 10,000+ |
| Reports Generated | Total reports produced | 25,000+ |
| Average Analysis Time | Time from data entry to completed analysis | < 15 minutes |
| Report Generation Time | Time to produce standard report | < 60 seconds |
| Platform Uptime | System availability | 99.5% |
| Support Response Time | Average first response to tickets | < 4 hours |

### Engagement Metrics

| Metric | Definition | Healthy Range |
|--------|------------|---------------|
| Weekly Active Users | Unique users per week | > 60% of total |
| Analyses per Firm per Month | Average client analyses | 10-50 |
| Reports per Firm per Month | Average reports generated | 15-75 |
| CPD Course Completion | % who complete at least one course | > 50% |
| Client Portal Adoption | % of firms using client portal | > 30% |

---

## Competitive Landscape

### Direct Competitors

Currently, no direct competitor exists that specifically addresses NTA 2025 analysis for Nigerian practitioners. The closest alternatives are:

**Generic Tax Software (TaxPro, etc.)**
- Not updated for NTA 2025
- Designed for filing, not analysis
- No comparison or scenario features
- Limited reporting capabilities

**Accounting Software with Tax Modules (Sage, QuickBooks)**
- Nigerian versions lag on legislative updates
- Tax features are secondary, not core
- No practitioner workflow support
- No client portal concept

**Excel / Manual Methods**
- Currently the dominant approach
- Error-prone and time-consuming
- No collaboration or audit trail
- Doesn't scale

### Indirect Competitors

**Big 4 Internal Tools**
- Only available to their staff
- Not sold externally
- Our opportunity: serve the rest of the market

**International Tax Platforms (Thomson Reuters, Wolters Kluwer)**
- Not focused on Nigeria
- Extremely expensive
- Overkill for local practitioners

### Competitive Positioning

```
                    │
  High Price        │    International Platforms
                    │         (Thomson, Wolters)
                    │
                    │                    ┌─────────┐
                    │                    │ TaxLab  │
                    │                    └─────────┘
                    │
                    │    Generic Tax Software
                    │
  Low Price         │    Excel / Manual
                    │
                    └────────────────────────────────
                    Low Functionality    High Functionality
```

TaxLab positions as:
- More functional than generic tools
- More affordable than enterprise solutions
- Specifically built for Nigerian NTA 2025 context
- Focused on practitioner workflow, not just compliance

---

## Business Model

### Revenue Streams

**Primary: Subscription Plans**

Platform administrators can configure plans with custom:
- Naming and descriptions
- Monthly and annual pricing
- Client limits
- User limits
- Report limits
- Feature toggles

Default suggested structure:

| Plan | Suggested Monthly Price | Client Limit | User Limit |
|------|------------------------|--------------|------------|
| Starter | ₦15,000 | 30 | 1 |
| Professional | ₦45,000 | 100 | 3 |
| Business | ₦120,000 | 300 | 10 |
| Enterprise | Custom | Unlimited | Unlimited |

All plans include:
- Full white-label branding
- Report approval workflow
- Client portal access
- CPD training courses
- Knowledge base access

**Secondary: Implementation Services (Future)**
- Bulk data migration assistance
- Custom training sessions
- Integration development

**Tertiary: Transaction Fees (Future)**
- Per-report charges beyond plan limits
- Premium report templates
- API call fees when launched

### Pricing Philosophy

1. **Value-Based** — Priced against time saved, not cost of delivery
2. **Transparent** — No hidden fees, clear feature inclusions
3. **Flexible** — Platform admin can adjust pricing as market evolves
4. **Growth-Aligned** — Plans scale with firm size, not punitive for success

### Unit Economics Target

| Metric | Target |
|--------|--------|
| Customer Acquisition Cost (CAC) | < ₦50,000 |
| Lifetime Value (LTV) | > ₦500,000 |
| LTV:CAC Ratio | > 10:1 |
| Payback Period | < 3 months |
| Gross Margin | > 80% |

---

## Roadmap Overview

### Phase 1: Foundation (MVP)

**Timeline:** 8-10 weeks

**Goal:** Launch core platform with essential features for tax analysis and reporting

**Includes:**
- Multi-tenant architecture with firm onboarding
- User management with roles (Partner, Manager, Associate, Viewer)
- Client management (CRUD, assignment)
- Financial data entry
- Tax calculation engine (CIT, PIT, VAT, CGT, Development Levy)
- Old vs. new regime comparison
- Basic scenario modeling
- Report generation (PDF)
- Knowledge base with NTA 2025 reference
- Platform admin panel (tenant and subscription management)

### Phase 2: Enhanced Experience

**Timeline:** 4-6 weeks after MVP

**Goal:** Improve usability and add practitioner-requested features

**Includes:**
- Client portal
- Advanced scenario modeling (multiple parameters, comparison views)
- Optimization tracker
- Report approval workflow
- Word document export
- Email notifications
- CPD training module (initial courses)
- Bulk client import

### Phase 3: Scale & Intelligence

**Timeline:** 6-8 weeks after Phase 2

**Goal:** Add advanced features and prepare for growth

**Includes:**
- Advanced analytics dashboard
- Automated optimization suggestions
- Custom report templates
- Platform-wide analytics for admin
- Enhanced CPD with certificates
- Mobile-responsive improvements
- Performance optimization

### Phase 4: Ecosystem (Future)

**Timeline:** Beyond initial release

**Goal:** Build integrations and expand platform capabilities

**Includes:**
- API access for third-party integrations
- Accounting software imports (Sage, QuickBooks)
- NRS e-filing integration (when available)
- Additional tax types and jurisdictions
- AI-powered insights and recommendations

---

## Risks & Mitigations

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Legislative changes after launch | High | Medium | Versioned tax rules engine allows rapid updates without code changes |
| Slow practitioner adoption | Medium | High | Strong onboarding, free trial, training resources, white-label appeal |
| Calculation errors | Medium | High | Extensive testing, audit trails, legislative references shown, professional review process |
| Competitor entry | Medium | Medium | First-mover advantage, deep feature set, community building |
| Technical scalability issues | Low | High | Standard Laravel patterns, queue-based processing, monitoring from day one |
| Payment processing challenges | Medium | Medium | Integrate established Nigerian gateways (Paystack, Flutterwave) |
| Support overwhelm at launch | Medium | Medium | Comprehensive help docs, CPD training reduces questions, hire support early |

---

## Conclusion

TaxLab addresses a clear, time-sensitive market need: Nigerian tax practitioners require efficient tools to navigate the NTA 2025 reforms for their clients. The platform combines deep domain expertise with modern SaaS design to deliver significant value.

The opportunity is substantial:
- 40,000+ potential users (ICAN and CITN members)
- No direct competitor in the Nigerian market
- Urgent deadline creates immediate demand
- Recurring revenue model with strong unit economics
- Platform extensible to additional tax services over time

Success depends on:
- Launching before January 2026 with core functionality
- Accurate, trustworthy calculations
- Exceptional user experience for both practitioners and their clients
- Strong onboarding and training resources
- Responsive updates as NRS issues guidance

TaxLab is positioned to become the essential platform for Nigerian tax practice in the NTA 2025 era and beyond.

---

## Appendix: Glossary

| Term | Definition |
|------|------------|
| NTA 2025 | Nigeria Tax Act 2025, the primary legislation reforming taxation |
| NTAA 2025 | Nigeria Tax Administration Act 2025, administrative framework |
| NRS | Nigeria Revenue Service, replacing FIRS |
| FIRS | Federal Inland Revenue Service, predecessor to NRS |
| CIT | Company Income Tax |
| PIT | Personal Income Tax |
| VAT | Value Added Tax |
| CGT | Capital Gains Tax |
| PAYE | Pay As You Earn, employer withholding system |
| TIN | Tax Identification Number |
| NIN | National Identification Number (now serves as Tax ID for individuals) |
| CAC | Corporate Affairs Commission |
| ICAN | Institute of Chartered Accountants of Nigeria |
| CITN | Chartered Institute of Taxation of Nigeria |
| CPD | Continuing Professional Development |
| Tenant | A subscribing firm using the platform |
| Tax Client | A business or individual that the firm provides tax services to |
| White-Label | Branding customization that shows the firm's brand instead of platform brand |
