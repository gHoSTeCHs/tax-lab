# TaxLab — Technical Architecture

## Document Information

| Item | Detail |
|------|--------|
| Document | Technical Architecture Overview |
| Version | 1.0 |
| Last Updated | December 2024 |
| Audience | Developers, Technical Stakeholders |

---

## 1. Technology Stack

### 1.1 Core Technologies

| Layer | Technology | Version | Rationale |
|-------|------------|---------|-----------|
| Backend Framework | Laravel | 11.x | Mature ecosystem, excellent for SaaS, strong ORM, built-in auth |
| Frontend Framework | React | 19.x | Component-based, rich ecosystem, TypeScript support |
| Language (Backend) | PHP | 8.3+ | Modern PHP features, performance improvements |
| Language (Frontend) | TypeScript | 5.x | Type safety, better tooling, reduced runtime errors |
| CSS Framework | Tailwind CSS | 3.x | Utility-first, consistent styling, rapid development |
| Database | PostgreSQL | 16.x | Robust, excellent JSON support, better data integrity than MySQL |
| Cache | Redis | 7.x | Session management, queue backend, application caching |
| Search | PostgreSQL Full-Text | Built-in | Sufficient for initial scale, avoids additional infrastructure |

### 1.2 Supporting Technologies

| Purpose | Technology | Rationale |
|---------|------------|-----------|
| Build Tool | Vite | Fast HMR, native ESM, Laravel integration |
| State Management | TanStack Query | Server state management, caching, synchronization |
| UI Components | Tailadmin + Custom | Professional dashboard foundation, customizable |
| Forms | React Hook Form | Performance, validation integration |
| Validation | Zod | TypeScript-first schema validation |
| PDF Generation | Browsershot (Puppeteer) | High-quality output, CSS support |
| DOCX Generation | PHPWord | Native PHP, no external dependencies |
| Charts | Chart.js + QuickChart | Client-side rendering + server-side for reports |
| Email | Native Laravel Mail | Start simple, swap provider later |
| Queue | Redis + Laravel Horizon | Reliable job processing, monitoring dashboard |

### 1.3 Development Tools

| Purpose | Tool |
|---------|------|
| Code Quality | Laravel Pint (PHP), ESLint + Prettier (TS) |
| Testing (Backend) | PHPUnit, Pest |
| Testing (Frontend) | Vitest, Testing Library |
| API Documentation | Scramble or Scribe |
| Database Migrations | Laravel Migrations |
| Version Control | Git |
| CI/CD | GitHub Actions |

---

## 2. Architecture Overview

### 2.1 High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                              CLIENTS                                     │
├─────────────────────────────────────────────────────────────────────────┤
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐    │
│  │   Browser   │  │   Browser   │  │   Browser   │  │   Browser   │    │
│  │  (Admin)    │  │   (Firm)    │  │  (Client)   │  │  (Future    │    │
│  │             │  │             │  │   Portal)   │  │   Mobile)   │    │
│  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘    │
└─────────┼────────────────┼────────────────┼────────────────┼────────────┘
          │                │                │                │
          └────────────────┴────────────────┴────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                           LOAD BALANCER                                  │
│                        (Nginx / Cloud LB)                               │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                    ┌───────────────┼───────────────┐
                    ▼               ▼               ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                          APPLICATION TIER                                │
├─────────────────────────────────────────────────────────────────────────┤
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐         │
│  │   Web Server    │  │   Web Server    │  │  Queue Workers  │         │
│  │   (Laravel)     │  │   (Laravel)     │  │   (Horizon)     │         │
│  │                 │  │                 │  │                 │         │
│  │  • API Routes   │  │  • API Routes   │  │  • Reports      │         │
│  │  • Web Routes   │  │  • Web Routes   │  │  • Emails       │         │
│  │  • Auth         │  │  • Auth         │  │  • Calculations │         │
│  └────────┬────────┘  └────────┬────────┘  └────────┬────────┘         │
└───────────┼────────────────────┼────────────────────┼───────────────────┘
            │                    │                    │
            └────────────────────┴────────────────────┘
                                 │
                    ┌────────────┼────────────┐
                    ▼            ▼            ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                            DATA TIER                                     │
├─────────────────────────────────────────────────────────────────────────┤
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐         │
│  │   PostgreSQL    │  │     Redis       │  │  File Storage   │         │
│  │                 │  │                 │  │                 │         │
│  │  • All app data │  │  • Sessions     │  │  • Reports      │         │
│  │  • Multi-tenant │  │  • Cache        │  │  • Uploads      │         │
│  │  • Audit logs   │  │  • Queues       │  │  • Branding     │         │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 2.2 Application Structure

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        LARAVEL APPLICATION                               │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────┐    │
│  │                      PRESENTATION LAYER                          │    │
│  │  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐               │    │
│  │  │  Inertia    │ │    API      │ │    Blade    │               │    │
│  │  │ Controllers │ │ Controllers │ │   (Emails)  │               │    │
│  │  └─────────────┘ └─────────────┘ └─────────────┘               │    │
│  └─────────────────────────────────────────────────────────────────┘    │
│                                 │                                        │
│                                 ▼                                        │
│  ┌─────────────────────────────────────────────────────────────────┐    │
│  │                       SERVICE LAYER                              │    │
│  │  ┌───────────────┐ ┌───────────────┐ ┌───────────────┐         │    │
│  │  │ TaxCalculation│ │    Report     │ │  Subscription │         │    │
│  │  │   Service     │ │   Service     │ │    Service    │         │    │
│  │  └───────────────┘ └───────────────┘ └───────────────┘         │    │
│  │  ┌───────────────┐ ┌───────────────┐ ┌───────────────┐         │    │
│  │  │   Scenario    │ │ Optimization  │ │   Tenancy     │         │    │
│  │  │   Service     │ │   Service     │ │   Service     │         │    │
│  │  └───────────────┘ └───────────────┘ └───────────────┘         │    │
│  └─────────────────────────────────────────────────────────────────┘    │
│                                 │                                        │
│                                 ▼                                        │
│  ┌─────────────────────────────────────────────────────────────────┐    │
│  │                       DOMAIN LAYER                               │    │
│  │  ┌───────────────┐ ┌───────────────┐ ┌───────────────┐         │    │
│  │  │    Models     │ │    Events     │ │   Policies    │         │    │
│  │  └───────────────┘ └───────────────┘ └───────────────┘         │    │
│  │  ┌───────────────┐ ┌───────────────┐ ┌───────────────┐         │    │
│  │  │    Enums      │ │    Actions    │ │     DTOs      │         │    │
│  │  └───────────────┘ └───────────────┘ └───────────────┘         │    │
│  └─────────────────────────────────────────────────────────────────┘    │
│                                 │                                        │
│                                 ▼                                        │
│  ┌─────────────────────────────────────────────────────────────────┐    │
│  │                    INFRASTRUCTURE LAYER                          │    │
│  │  ┌───────────────┐ ┌───────────────┐ ┌───────────────┐         │    │
│  │  │  Repositories │ │   External    │ │    Queue      │         │    │
│  │  │  (Eloquent)   │ │   Services    │ │    Jobs       │         │    │
│  │  └───────────────┘ └───────────────┘ └───────────────┘         │    │
│  └─────────────────────────────────────────────────────────────────┘    │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Multi-Tenancy Architecture

### 3.1 Tenancy Strategy

**Approach:** Single Database with Tenant Column

All tenant-specific tables include a `firm_id` foreign key. Global query scopes automatically filter data by the authenticated user's firm.

**Why This Approach:**

| Factor | Single DB + Column | Separate Databases |
|--------|-------------------|-------------------|
| Setup Complexity | Simple | Complex |
| Maintenance | Single backup, single migration | Per-tenant operations |
| Cross-tenant Queries | Easy (for admin analytics) | Requires connection switching |
| Resource Usage | Efficient | Higher overhead |
| Data Isolation | Application-enforced | Database-enforced |
| Scale Limit | ~1000s of tenants | Unlimited |
| Best For | Our expected scale | Enterprise multi-region |

For TaxLab's projected scale (hundreds of firms), single database is appropriate and significantly simpler.

### 3.2 Tenant Resolution Flow

```
Request Received
       │
       ▼
┌──────────────────┐
│  Authenticate    │
│  User            │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐     ┌──────────────────┐
│  Is Admin User?  │────▶│  Set Admin       │
│                  │ Yes │  Context         │
└────────┬─────────┘     │  (No tenant      │
         │ No            │   scope)         │
         ▼               └──────────────────┘
┌──────────────────┐
│  Load User's     │
│  Firm            │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│  Set Tenant      │
│  Context         │
│  (firm_id)       │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│  Apply Global    │
│  Scopes to       │
│  All Queries     │
└────────┬─────────┘
         │
         ▼
   Continue Request
```

### 3.3 Data Scoping Categories

**Global Tables (No Tenant Scope)**
- admin_users
- plans
- plan_features
- tax_legislation
- tax_rules
- cpd_courses
- cpd_modules
- knowledge_base_articles
- system_settings

**Tenant Tables (Scoped by firm_id)**
- firms (tenant itself)
- firm_users
- firm_invitations
- tax_clients
- client_financials
- calculations
- scenarios
- reports
- optimizations
- activity_logs
- firm_settings
- firm_branding

**Cross-Reference Tables**
- client_user_assignments (links firm_users to tax_clients)
- cpd_enrollments (links firm_users to cpd_courses)
- cpd_progress (links firm_users to cpd_modules)

### 3.4 Tenant Isolation Implementation

**Global Scope on Models:**

Every tenant-scoped model uses a `BelongsToFirm` trait that:
1. Automatically adds `firm_id` to all queries
2. Automatically sets `firm_id` on model creation
3. Prevents cross-tenant data access at the query level

**Middleware Stack:**

```
1. Authentication (verify user identity)
2. TenantResolver (load firm context)
3. TenantScope (apply global scopes)
4. Authorization (check permissions)
5. Controller Action
```

**Additional Safeguards:**
- Foreign key constraints at database level
- Policy checks before any model access
- Audit logging of all data access
- Periodic integrity checks for orphaned records

---

## 4. Database Architecture

### 4.1 Schema Overview

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         PLATFORM DOMAIN                                  │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐               │
│  │ admin_users │     │    plans    │     │plan_features│               │
│  ├─────────────┤     ├─────────────┤     ├─────────────┤               │
│  │ id          │     │ id          │     │ id          │               │
│  │ email       │     │ name        │     │ plan_id     │───┐           │
│  │ password    │     │ slug        │     │ feature_key │   │           │
│  │ role        │     │ description │◄────│ value       │   │           │
│  │ status      │     │ monthly_... │     └─────────────┘   │           │
│  │ 2fa_...     │     │ annual_...  │                       │           │
│  └─────────────┘     │ limits      │◄──────────────────────┘           │
│                      │ is_active   │                                    │
│                      └─────────────┘                                    │
│                                                                          │
│  ┌─────────────┐     ┌─────────────┐                                    │
│  │    tax_     │     │  tax_rules  │                                    │
│  │ legislation │     ├─────────────┤                                    │
│  ├─────────────┤     │ id          │                                    │
│  │ id          │◄────│ legislation_│                                    │
│  │ name        │     │ rule_type   │                                    │
│  │ year        │     │ applies_to  │                                    │
│  │ effective   │     │ parameters  │ (JSON)                             │
│  │ status      │     │ effective_  │                                    │
│  │ version     │     │ version     │                                    │
│  └─────────────┘     └─────────────┘                                    │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                          TENANT DOMAIN                                   │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌─────────────┐     ┌─────────────┐     ┌─────────────┐               │
│  │    firms    │     │ firm_users  │     │firm_invites │               │
│  ├─────────────┤     ├─────────────┤     ├─────────────┤               │
│  │ id          │◄────│ firm_id     │     │ id          │               │
│  │ name        │     │ id          │     │ firm_id     │───┐           │
│  │ slug        │     │ email       │     │ email       │   │           │
│  │ plan_id     │──┐  │ password    │     │ role        │   │           │
│  │ status      │  │  │ role        │     │ token       │   │           │
│  │ trial_ends  │  │  │ status      │     │ expires_at  │   │           │
│  │ settings    │  │  │ last_login  │     │ status      │   │           │
│  └─────────────┘  │  └─────────────┘     └─────────────┘   │           │
│        │          │         │                              │           │
│        │          │         │                              │           │
│        ▼          │         ▼                              │           │
│  ┌─────────────┐  │  ┌─────────────────┐                   │           │
│  │firm_branding│  │  │client_user_     │                   │           │
│  ├─────────────┤  │  │assignments      │                   │           │
│  │ firm_id     │──┘  ├─────────────────┤                   │           │
│  │ logo_path   │     │ firm_user_id    │                   │           │
│  │ colors      │     │ tax_client_id   │                   │           │
│  │ footer_text │     │ assigned_at     │                   │           │
│  └─────────────┘     └─────────────────┘                   │           │
│                              │                              │           │
└──────────────────────────────┼──────────────────────────────┼───────────┘
                               │                              │
┌──────────────────────────────┼──────────────────────────────┼───────────┐
│                        OPERATIONAL DOMAIN                    │           │
├──────────────────────────────┼──────────────────────────────┼───────────┤
│                              │                              │           │
│  ┌─────────────┐             │         ┌─────────────┐      │           │
│  │ tax_clients │◄────────────┴─────────│  client_    │◄─────┘           │
│  ├─────────────┤                       │ financials  │                  │
│  │ id          │                       ├─────────────┤                  │
│  │ firm_id     │───────────────────────│ client_id   │                  │
│  │ entity_type │                       │ fiscal_year │                  │
│  │ name        │                       │ turnover    │                  │
│  │ tin_nin     │                       │ profit      │                  │
│  │ industry    │                       │ assets      │                  │
│  │ status      │                       │ employees   │                  │
│  └─────────────┘                       │ details     │ (JSON)           │
│        │                               └─────────────┘                  │
│        │                                     │                          │
│        ▼                                     ▼                          │
│  ┌─────────────┐     ┌─────────────┐   ┌─────────────┐                 │
│  │calculations │     │  scenarios  │   │   reports   │                 │
│  ├─────────────┤     ├─────────────┤   ├─────────────┤                 │
│  │ id          │◄────│ base_calc_id│   │ id          │                 │
│  │ client_id   │     │ id          │   │ client_id   │                 │
│  │ fiscal_year │     │ client_id   │   │ calc_id     │                 │
│  │ type        │     │ name        │   │ type        │                 │
│  │ rules_ver   │     │ parameters  │   │ format      │                 │
│  │ inputs      │     │ results     │   │ config      │                 │
│  │ results     │     │ created_by  │   │ file_path   │                 │
│  │ performed_by│     │ status      │   │ status      │                 │
│  └─────────────┘     └─────────────┘   │ approval_   │                 │
│        │                               │ generated_by│                 │
│        ▼                               └─────────────┘                 │
│  ┌─────────────┐                                                       │
│  │optimizations│                                                       │
│  ├─────────────┤                                                       │
│  │ id          │                                                       │
│  │ client_id   │                                                       │
│  │ calc_id     │                                                       │
│  │ type        │                                                       │
│  │ description │                                                       │
│  │ impact      │                                                       │
│  │ priority    │                                                       │
│  │ status      │                                                       │
│  └─────────────┘                                                       │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 4.2 Key Design Decisions

**JSON Columns for Flexible Data**

Several tables use JSON columns for data that varies by context:

| Table | Column | Purpose |
|-------|--------|---------|
| tax_rules | parameters | Store rate tables, thresholds, formulas |
| client_financials | details | Industry-specific financial line items |
| calculations | inputs, results | Full calculation data for audit trail |
| scenarios | parameters, results | Modified inputs and outcomes |
| firms | settings | Firm-specific configuration |
| reports | config | Report generation options |

**Versioning for Tax Rules**

Tax rules include version numbers to ensure historical calculations remain reproducible. When rules are updated:
- New version created (old version remains)
- New calculations use latest version
- Old calculations reference their original version
- Can regenerate old calculations with original rules

**Soft Deletes**

The following tables use soft deletes (deleted_at timestamp):
- tax_clients (archived, not deleted)
- firm_users (can be reactivated)
- reports (audit trail preservation)

### 4.3 Indexing Strategy

**Primary Indexes (Automatic)**
- All primary keys
- All foreign keys

**Composite Indexes**
- `(firm_id, created_at)` on tenant tables — filtering + sorting
- `(firm_id, status)` on tax_clients — active client queries
- `(client_id, fiscal_year)` on financials — year lookups
- `(firm_id, entity_type)` on tax_clients — filtered lists

**Partial Indexes**
- Active calculations only (where status = 'completed')
- Pending reports only (where status = 'pending_approval')

**Full-Text Search Indexes**
- Knowledge base articles (title, content)
- Tax clients (name, tin_nin)

---

## 5. Authentication Architecture

### 5.1 Authentication Domains

The system has three separate authentication domains:

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        AUTHENTICATION DOMAINS                            │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────┐    │
│  │                    PLATFORM ADMIN DOMAIN                         │    │
│  │                                                                   │    │
│  │  Users: Platform administrators                                   │    │
│  │  Table: admin_users                                               │    │
│  │  Guard: admin                                                     │    │
│  │  Routes: /admin/*                                                 │    │
│  │  Session: Separate session driver                                 │    │
│  │  2FA: Mandatory                                                   │    │
│  └─────────────────────────────────────────────────────────────────┘    │
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────┐    │
│  │                      FIRM USER DOMAIN                            │    │
│  │                                                                   │    │
│  │  Users: Practitioners at subscribing firms                        │    │
│  │  Table: firm_users                                                │    │
│  │  Guard: firm                                                      │    │
│  │  Routes: /app/*                                                   │    │
│  │  Session: Default web session                                     │    │
│  │  2FA: Optional (firm configurable)                                │    │
│  └─────────────────────────────────────────────────────────────────┘    │
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────┐    │
│  │                    CLIENT PORTAL DOMAIN                          │    │
│  │                                                                   │    │
│  │  Users: Tax clients of firms                                      │    │
│  │  Table: client_portal_users                                       │    │
│  │  Guard: client                                                    │    │
│  │  Routes: /portal/*                                                │    │
│  │  Session: Separate session driver                                 │    │
│  │  2FA: Optional                                                    │    │
│  └─────────────────────────────────────────────────────────────────┘    │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 5.2 Session Management

**Session Configuration:**
- Driver: Redis (shared cache infrastructure)
- Lifetime: 120 minutes (configurable per domain)
- Idle Timeout: 30 minutes (configurable)
- Secure: HTTPS only in production
- Same-Site: Strict

**Session Isolation:**
- Each auth domain uses different session cookie names
- Admin sessions isolated from firm sessions
- Client portal sessions isolated from firm sessions
- Prevents session confusion attacks

### 5.3 Password Requirements

| Domain | Min Length | Requirements | Max Age |
|--------|-----------|--------------|---------|
| Admin | 16 | Upper, lower, number, symbol | 90 days |
| Firm | 10 | Upper, lower, number | Optional |
| Client | 8 | Basic complexity | None |

### 5.4 Two-Factor Authentication

**Admin Users:**
- Mandatory for all admin accounts
- TOTP-based (Google Authenticator, Authy)
- Recovery codes generated at setup
- Enforced at login

**Firm Users:**
- Optional by default
- Firm can mandate for all users
- Individual users can enable voluntarily
- TOTP-based

**Client Portal Users:**
- Optional
- Simple email-based OTP alternative
- TOTP available for security-conscious clients

---

## 6. Caching Strategy

### 6.1 Cache Layers

```
┌─────────────────────────────────────────────────────────────────────────┐
│                          CACHE HIERARCHY                                 │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────┐    │
│  │  LAYER 1: Application Cache (Redis)                              │    │
│  │                                                                   │    │
│  │  • Tax rules (by version) — TTL: 24 hours                        │    │
│  │  • Plan details — TTL: 1 hour                                    │    │
│  │  • Knowledge base articles — TTL: 6 hours                        │    │
│  │  • Firm settings — TTL: 15 minutes                               │    │
│  │  • User permissions — TTL: 5 minutes                             │    │
│  └─────────────────────────────────────────────────────────────────┘    │
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────┐    │
│  │  LAYER 2: Query Cache                                            │    │
│  │                                                                   │    │
│  │  • Dashboard statistics — TTL: 5 minutes                         │    │
│  │  • Client lists — TTL: 1 minute (invalidated on change)          │    │
│  │  • Report lists — TTL: 1 minute (invalidated on change)          │    │
│  └─────────────────────────────────────────────────────────────────┘    │
│                                                                          │
│  ┌─────────────────────────────────────────────────────────────────┐    │
│  │  LAYER 3: Computed Results Cache                                 │    │
│  │                                                                   │    │
│  │  • Calculation results — Permanent until inputs change           │    │
│  │  • Charts — Regenerated on calculation change                    │    │
│  │  • Optimization suggestions — Tied to calculation version        │    │
│  └─────────────────────────────────────────────────────────────────┘    │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 6.2 Cache Invalidation Rules

| Data Type | Invalidation Trigger |
|-----------|---------------------|
| Tax rules | Admin publishes new version |
| Plan details | Admin updates plan |
| Firm settings | Partner updates settings |
| Client list | Any client created, updated, archived in firm |
| Calculation | Never (immutable once completed) |
| Dashboard stats | Time-based (5 min) or manual refresh |

### 6.3 Cache Key Naming Convention

```
{domain}:{tenant}:{resource}:{identifier}:{version}

Examples:
- platform:global:tax_rules:pit:v2.1
- firm:42:settings:branding
- firm:42:clients:list:active
- firm:42:client:156:calculation:2024:latest
```

---

## 7. Queue Architecture

### 7.1 Queue Configuration

**Driver:** Redis (via Laravel Horizon)

**Queue Definitions:**

| Queue | Purpose | Workers | Timeout |
|-------|---------|---------|---------|
| default | General jobs | 2 | 60s |
| reports | PDF/DOCX generation | 2 | 300s |
| calculations | Heavy tax calculations | 2 | 120s |
| emails | Email delivery | 1 | 30s |
| notifications | In-app notifications | 1 | 10s |

### 7.2 Job Types

**Report Generation Jobs**
- Input: Report configuration, client ID
- Process: Render sections, generate PDF/DOCX
- Output: File path stored in database
- Notification: User notified when complete

**Bulk Calculation Jobs**
- Input: Client IDs, calculation type
- Process: Run calculations for each client
- Output: Individual calculation records
- Notification: Summary when batch complete

**Email Jobs**
- Input: Recipient, template, data
- Process: Render template, send via mail driver
- Output: Delivery status logged
- Retry: 3 attempts with exponential backoff

### 7.3 Job Failure Handling

```
Job Execution
     │
     ▼
┌─────────┐    Success    ┌─────────────┐
│   Run   │──────────────▶│   Complete  │
│   Job   │               │   Log       │
└────┬────┘               └─────────────┘
     │
     │ Failure
     ▼
┌─────────────┐    Retry < Max    ┌─────────────┐
│   Catch     │──────────────────▶│   Delay &   │
│   Error     │                   │   Retry     │
└──────┬──────┘                   └─────────────┘
       │
       │ Retry >= Max
       ▼
┌─────────────┐
│   Move to   │
│   Failed    │
│   Jobs      │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│   Notify    │
│   Admin     │
└─────────────┘
```

---

## 8. File Storage Architecture

### 8.1 Storage Structure

```
storage/
├── app/
│   ├── firms/
│   │   └── {firm_id}/
│   │       ├── branding/
│   │       │   └── logo.png
│   │       ├── reports/
│   │       │   └── {year}/
│   │       │       └── {report_id}.pdf
│   │       └── imports/
│   │           └── {import_id}.csv
│   │
│   ├── platform/
│   │   ├── cpd/
│   │   │   └── {course_id}/
│   │   │       └── materials/
│   │   └── knowledge-base/
│   │       └── attachments/
│   │
│   └── temp/
│       └── {job_id}/
│
└── logs/
    └── laravel.log
```

### 8.2 File Security

**Access Control:**
- All files served through application (no direct public access)
- Firm files only accessible by authenticated firm users
- Tenant isolation enforced at file path level
- Signed URLs for temporary access (downloads)

**Upload Validation:**
- File type whitelist per context
- Size limits enforced
- Virus scanning (future enhancement)
- Image processing for logos (resize, optimize)

### 8.3 Future Migration Path

Current: Local filesystem storage
Future: S3-compatible object storage (DigitalOcean Spaces, AWS S3)

Migration approach:
1. Implement filesystem abstraction (Laravel's Storage facade)
2. All file operations through abstraction
3. Switch driver configuration when ready
4. Migrate existing files via background job

---

## 9. Deployment Architecture

### 9.1 Infrastructure Diagram

```
┌─────────────────────────────────────────────────────────────────────────┐
│                              INTERNET                                    │
└───────────────────────────────────┬─────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                           CLOUDFLARE                                     │
│                    (DNS, CDN, DDoS Protection)                          │
└───────────────────────────────────┬─────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                              VPS HOST                                    │
│                    (DigitalOcean / Vultr / Hetzner)                     │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌──────────────────────────────────────────────────────────────────┐   │
│  │                           NGINX                                   │   │
│  │               (Reverse Proxy, SSL Termination)                    │   │
│  └────────────────────────────┬─────────────────────────────────────┘   │
│                               │                                          │
│              ┌────────────────┼────────────────┐                        │
│              ▼                ▼                ▼                        │
│  ┌───────────────┐  ┌───────────────┐  ┌───────────────┐               │
│  │   PHP-FPM     │  │   PHP-FPM     │  │   Horizon     │               │
│  │   (Web App)   │  │   (Web App)   │  │   (Workers)   │               │
│  └───────┬───────┘  └───────┬───────┘  └───────┬───────┘               │
│          │                  │                  │                        │
│          └──────────────────┴──────────────────┘                        │
│                             │                                            │
│              ┌──────────────┼──────────────┐                            │
│              ▼              ▼              ▼                            │
│  ┌───────────────┐  ┌───────────────┐  ┌───────────────┐               │
│  │  PostgreSQL   │  │    Redis      │  │  Local Files  │               │
│  │               │  │               │  │               │               │
│  └───────────────┘  └───────────────┘  └───────────────┘               │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 9.2 Recommended Server Specifications

**Initial Launch (MVP):**

| Component | Specification |
|-----------|---------------|
| VPS Type | Single server |
| CPU | 4 vCPU |
| RAM | 8 GB |
| Storage | 160 GB NVMe SSD |
| Bandwidth | 4 TB |
| Estimated Cost | ~$50-80/month |

**Growth Phase:**

| Component | Specification |
|-----------|---------------|
| Web Server | 2x 2vCPU / 4GB |
| Database | Managed PostgreSQL (4GB) |
| Redis | Managed Redis (1GB) |
| Load Balancer | Cloud LB |
| Storage | Object storage |
| Estimated Cost | ~$150-250/month |

### 9.3 Deployment Process

```
Developer Machine
       │
       │ git push
       ▼
┌─────────────┐
│   GitHub    │
│   Actions   │
└──────┬──────┘
       │
       │ 1. Run tests
       │ 2. Build assets
       │ 3. Create release
       │
       ▼
┌─────────────┐
│   Deploy    │
│   Script    │
└──────┬──────┘
       │
       │ 1. Pull latest code
       │ 2. Install dependencies
       │ 3. Run migrations
       │ 4. Clear/warm caches
       │ 5. Restart workers
       │ 6. Notify complete
       │
       ▼
┌─────────────┐
│ Production  │
│   Server    │
└─────────────┘
```

---

## 10. Monitoring & Observability

### 10.1 Monitoring Stack

| Purpose | Tool | Rationale |
|---------|------|-----------|
| Application Monitoring | Laravel Telescope (dev), Custom dashboard (prod) | Built-in, low overhead |
| Error Tracking | Sentry or Flare | Exception tracking with context |
| Uptime Monitoring | UptimeRobot or Better Stack | External availability checks |
| Log Aggregation | Native Laravel logs (initially) | Simple start, can add ELK later |
| Queue Monitoring | Laravel Horizon | Built-in dashboard for queue health |

### 10.2 Key Metrics to Track

**Application Health:**
- Response time (p50, p95, p99)
- Error rate (4xx, 5xx responses)
- Request throughput
- Memory usage
- CPU usage

**Business Metrics:**
- Active users (DAU, WAU, MAU)
- Calculations performed
- Reports generated
- New firm signups
- Trial conversions

**Queue Health:**
- Queue depth per queue
- Job processing time
- Failed job count
- Worker availability

### 10.3 Alerting Rules

| Condition | Severity | Response |
|-----------|----------|----------|
| Error rate > 1% | Warning | Investigate |
| Error rate > 5% | Critical | Immediate attention |
| Response time p95 > 3s | Warning | Performance review |
| Queue depth > 100 | Warning | Scale workers |
| Failed jobs > 10/hour | Warning | Investigate failures |
| Disk usage > 80% | Warning | Cleanup or expand |
| Server unreachable | Critical | Immediate investigation |

---

## 11. Security Architecture

### 11.1 Security Layers

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         SECURITY LAYERS                                  │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌───────────────────────────────────────────────────────────────────┐  │
│  │  LAYER 1: Network Security                                         │  │
│  │  • Cloudflare DDoS protection                                      │  │
│  │  • TLS 1.3 encryption                                              │  │
│  │  • Firewall (only 80/443 exposed)                                  │  │
│  │  • SSH key-only authentication                                     │  │
│  └───────────────────────────────────────────────────────────────────┘  │
│                                                                          │
│  ┌───────────────────────────────────────────────────────────────────┐  │
│  │  LAYER 2: Application Security                                     │  │
│  │  • CSRF protection                                                 │  │
│  │  • XSS prevention (output encoding)                                │  │
│  │  • SQL injection prevention (parameterized queries)                │  │
│  │  • Rate limiting on sensitive endpoints                            │  │
│  │  • Security headers (CSP, HSTS, etc.)                              │  │
│  └───────────────────────────────────────────────────────────────────┘  │
│                                                                          │
│  ┌───────────────────────────────────────────────────────────────────┐  │
│  │  LAYER 3: Authentication & Authorization                           │  │
│  │  • Bcrypt password hashing (cost 12)                               │  │
│  │  • Two-factor authentication                                       │  │
│  │  • Role-based access control                                       │  │
│  │  • Policy-based resource authorization                             │  │
│  │  • Session management and timeout                                  │  │
│  └───────────────────────────────────────────────────────────────────┘  │
│                                                                          │
│  ┌───────────────────────────────────────────────────────────────────┐  │
│  │  LAYER 4: Data Security                                            │  │
│  │  • Tenant isolation (query scopes)                                 │  │
│  │  • Sensitive data encryption at rest                               │  │
│  │  • Audit logging of all access                                     │  │
│  │  • Automatic backup encryption                                     │  │
│  └───────────────────────────────────────────────────────────────────┘  │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 11.2 Sensitive Data Handling

**Encrypted at Rest:**
- Tax Identification Numbers (TIN)
- National Identification Numbers (NIN)
- Bank account details (if collected)
- Financial figures (optional, configurable)

**Encryption Method:**
- Laravel's built-in encryption (AES-256-CBC)
- Application-level encryption
- Key rotation capability

### 11.3 Compliance Considerations

**NDPR (Nigeria Data Protection Regulation):**
- Privacy policy required
- Consent collection for data processing
- Data subject access request capability
- Data deletion capability
- Breach notification process

**Implementation:**
- Data export feature for firms
- Account deletion with data purge
- Audit trail preservation for legal requirements
- Clear data retention policies

---

## 12. Performance Targets

### 12.1 Response Time Targets

| Operation | Target | Maximum |
|-----------|--------|---------|
| Page load (dashboard) | < 1.0s | 2.0s |
| Page load (client list) | < 1.0s | 2.0s |
| Simple calculation | < 2.0s | 5.0s |
| Full analysis | < 5.0s | 15.0s |
| Report generation (10pg) | < 30s | 60s |
| Search results | < 500ms | 1.0s |
| File upload (5MB) | < 3.0s | 10s |

### 12.2 Scalability Targets

| Metric | Initial Capacity | Growth Target |
|--------|-----------------|---------------|
| Concurrent users | 100 | 500 |
| Total firms | 500 | 2,000 |
| Total clients | 25,000 | 100,000 |
| Reports/day | 500 | 5,000 |
| Calculations/day | 1,000 | 10,000 |

### 12.3 Availability Target

| Metric | Target |
|--------|--------|
| Uptime | 99.5% |
| Planned maintenance window | Sunday 2-4 AM WAT |
| Maximum unplanned downtime | 4 hours/month |
| Recovery Time Objective (RTO) | 2 hours |
| Recovery Point Objective (RPO) | 1 hour |

---

## 13. Development Standards

### 13.1 Code Organization

```
app/
├── Actions/                    # Single-purpose action classes
├── Console/
│   └── Commands/
├── DTOs/                       # Data Transfer Objects
├── Enums/                      # PHP 8.1+ enums
├── Events/
├── Exceptions/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/              # Platform admin controllers
│   │   ├── App/                # Firm user controllers
│   │   └── Portal/             # Client portal controllers
│   ├── Middleware/
│   ├── Requests/               # Form requests
│   └── Resources/              # API resources
├── Jobs/
├── Listeners/
├── Mail/
├── Models/
│   ├── Concerns/               # Model traits
│   └── Scopes/                 # Query scopes
├── Notifications/
├── Policies/
├── Providers/
├── Rules/                      # Custom validation rules
└── Services/                   # Business logic services
    ├── Tax/                    # Tax calculation services
    ├── Reports/                # Report generation services
    ├── Scenarios/              # Scenario modeling services
    └── Tenancy/                # Multi-tenancy services
```

### 13.2 Naming Conventions

| Type | Convention | Example |
|------|------------|---------|
| Controllers | Singular, PascalCase | TaxClientController |
| Models | Singular, PascalCase | TaxClient |
| Tables | Plural, snake_case | tax_clients |
| Columns | Singular, snake_case | fiscal_year |
| Routes | Plural, kebab-case | /tax-clients |
| Components | PascalCase | ClientCard.tsx |
| Services | PascalCase, suffixed | TaxCalculationService |
| Jobs | PascalCase, suffixed | GenerateReportJob |
| Events | Past tense, PascalCase | ReportGenerated |

### 13.3 Documentation Requirements

| Item | Documentation Standard |
|------|----------------------|
| Complex services | PHPDoc on class and public methods |
| API endpoints | OpenAPI/Swagger documentation |
| React components | TypeScript interfaces for props |
| Database changes | Migration files with descriptive names |
| Configuration | Comments in config files |
| Business logic | Inline explanation of tax rules applied |

---

## Appendix A: Technology Decision Log

| Decision | Options Considered | Choice | Rationale |
|----------|-------------------|--------|-----------|
| Framework | Laravel, Symfony, Node | Laravel | Team expertise, ecosystem, rapid development |
| Frontend | React, Vue, Livewire | React | Complex UI needs, TypeScript support |
| Database | PostgreSQL, MySQL | PostgreSQL | JSON support, data integrity, future scalability |
| Tenancy | Package vs custom | Custom | Simpler, full control, specific requirements |
| PDF | DomPDF, Snappy, Browsershot | Browsershot | Best quality, CSS support, reliable |
| Queue | Database, Redis, SQS | Redis | Performance, Horizon integration |
| Hosting | VPS vs PaaS | VPS | Cost control, Nigerian regulations, flexibility |

---

## Appendix B: External Dependencies

| Dependency | Purpose | Criticality | Fallback |
|------------|---------|-------------|----------|
| PostgreSQL | Primary database | Critical | None (core) |
| Redis | Cache, queues, sessions | Critical | Database driver (degraded) |
| Puppeteer/Chrome | PDF generation | High | DomPDF (lower quality) |
| Cloudflare | CDN, security | Medium | Direct access (less protected) |
| Email provider | Notifications | Medium | Queued retry, alternative provider |

---

*This document should be reviewed and updated as architecture decisions evolve during development.*
