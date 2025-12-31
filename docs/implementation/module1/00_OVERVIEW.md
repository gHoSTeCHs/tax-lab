# TaxLab Implementation Plan - Module 1: Foundation

## Overview

This module establishes the core infrastructure for TaxLab's multi-tenant architecture, building upon the existing Laravel 12 + React 19 + Inertia setup.

## Current State Assessment

### Already Implemented

| Component | Status | Details |
|-----------|--------|---------|
| Laravel 12 | Done | Fresh installation with standard config |
| React 19 + TypeScript | Done | Full setup with Vite 7 |
| Inertia.js | Done | v2.1.4 with SSR support |
| Tailwind CSS 4 | Done | With dark mode support |
| shadcn/ui Components | Done | 30+ components installed |
| Basic Auth (Fortify) | Done | Single user type with 2FA |
| User Settings | Done | Profile, password, appearance, 2FA |
| Dashboard Shell | Done | Sidebar/header layouts ready |
| Wayfinder | Done | Type-safe route generation |

### Technology Stack

| Layer | Technology | Version | Status |
|-------|------------|---------|--------|
| Backend Framework | Laravel | 12.x | Installed |
| Frontend Framework | React | 19.x | Installed |
| Language (Backend) | PHP | 8.3+ | Installed |
| Language (Frontend) | TypeScript | 5.7 | Installed |
| CSS Framework | Tailwind CSS | 4.0 | Installed |
| Database | MySQL | 8.x | **Needs Config** |
| Cache | Database | - | Already configured |
| Session | Database | - | Already configured |
| Queue | Database | - | Already configured |

### Existing Code Structure

```
app/
├── Actions/Fortify/           User creation, password reset
├── Http/
│   ├── Controllers/Settings/  Profile, Password, 2FA controllers
│   ├── Middleware/            Inertia, Appearance handlers
│   └── Requests/Settings/     Profile, 2FA form requests
├── Models/User.php            Single user model with 2FA
└── Providers/                 App, Fortify providers

resources/js/
├── components/ui/             shadcn components (30+)
├── components/                App shell, nav, user components
├── hooks/                     Appearance, clipboard, mobile, 2FA
├── layouts/                   App, Auth, Settings layouts
├── pages/                     Auth pages, settings, dashboard
└── types/                     Base TypeScript definitions
```

## What Needs To Be Built

### Phase 1.1: Database & Environment Setup
- [ ] Configure MySQL connection (replace SQLite)
- [ ] Verify database cache/session/queue tables exist
- [ ] Set up database backup strategy

> **Note:** Redis and Laravel Horizon can be added later for improved performance. Database drivers are sufficient for initial development.

### Phase 1.2: Multi-Tenant Database Schema
- [ ] Admin users table (platform administrators)
- [ ] Firms table (tenants)
- [ ] Firm users table (practitioners - replaces/extends current User)
- [ ] Firm invitations table
- [ ] Plans and plan features tables
- [ ] Subscriptions table
- [ ] Client portal users table

### Phase 1.3: Multi-Tenancy Architecture
- [ ] Tenant context service
- [ ] BelongsToFirm model trait
- [ ] Tenant resolution middleware
- [ ] Global query scopes for tenant isolation
- [ ] Tenant-aware caching strategy

### Phase 1.4: Three-Guard Authentication
- [ ] Admin guard (admin_users table, /admin/* routes)
- [ ] Firm guard (firm_users table, /app/* routes)
- [ ] Client guard (client_portal_users table, /portal/* routes)
- [ ] Separate session management per guard
- [ ] Role-based permissions per guard

### Phase 1.5: Service Layer Architecture
- [ ] Base service class with common patterns
- [ ] Auth services (AdminAuth, FirmAuth, ClientAuth)
- [ ] Tenant service
- [ ] User management services
- [ ] Service provider registration

### Phase 1.6: Frontend Enhancements
- [ ] Extend type definitions for multi-tenancy
- [ ] Admin layout and pages structure
- [ ] Portal layout and pages structure
- [ ] Guard-aware navigation components

## Module Structure

```
module1/
├── 00_OVERVIEW.md              (this file)
├── 01_PROJECT_SETUP.md         Database & Redis configuration
├── 02_DATABASE_SCHEMA.md       Core migrations for multi-tenancy
├── 03_MULTI_TENANCY.md         Tenant isolation implementation
├── 04_AUTHENTICATION.md        Three-guard auth system
├── 05_SERVICE_LAYER.md         Service layer patterns
├── 06_FRONTEND_ADDITIONS.md    Type system & layout additions
└── 07_TESTING_STRATEGY.md      Testing approach
```

## Architectural Decisions

### Service Layer Architecture

Controllers remain thin - they handle:
- Request validation (via Form Requests)
- Calling appropriate services
- Returning Inertia responses

Services handle:
- Business logic
- Data transformation
- Cross-cutting concerns
- External service integration

```
Request → Controller → FormRequest → Service → Model → Response
```

### Multi-Tenancy Strategy

**Approach:** Single Database with `firm_id` Column

- All tenant-specific tables include `firm_id` foreign key
- Global query scopes automatically filter by authenticated user's firm
- Platform admin bypasses tenant scoping
- Client portal scoped to their linked firm

### Authentication Domains

```
┌─────────────────────────────────────────────────────────────┐
│  ADMIN DOMAIN                                                │
│  Guard: admin | Table: admin_users | Routes: /admin/*       │
│  2FA: Mandatory | Session: admin_session                     │
├─────────────────────────────────────────────────────────────┤
│  FIRM DOMAIN                                                 │
│  Guard: firm | Table: firm_users | Routes: /app/*           │
│  2FA: Optional | Session: firm_session                       │
├─────────────────────────────────────────────────────────────┤
│  CLIENT PORTAL DOMAIN                                        │
│  Guard: client | Table: client_portal_users | Routes: /portal/*│
│  2FA: Optional | Session: portal_session                     │
└─────────────────────────────────────────────────────────────┘
```

### Proposed Code Organization

```
app/
├── Actions/                    Single-purpose action classes
│   ├── Admin/
│   ├── Firm/
│   └── Portal/
├── DTOs/                       Data Transfer Objects
├── Enums/                      PHP enums (roles, statuses)
├── Exceptions/                 Custom exceptions
├── Http/
│   ├── Controllers/
│   │   ├── Admin/              Platform admin controllers
│   │   ├── App/                Firm user controllers
│   │   ├── Portal/             Client portal controllers
│   │   └── Settings/           (existing)
│   ├── Middleware/
│   │   ├── HandleInertiaRequests.php (existing)
│   │   ├── HandleAppearance.php (existing)
│   │   ├── ResolveTenant.php   (new)
│   │   ├── EnsureAdminAuthenticated.php (new)
│   │   └── EnsurePortalAuthenticated.php (new)
│   └── Requests/
│       ├── Admin/              Admin form requests
│       ├── App/                Firm form requests
│       ├── Portal/             Portal form requests
│       └── Settings/           (existing)
├── Models/
│   ├── Concerns/
│   │   ├── BelongsToFirm.php   Tenant scoping trait
│   │   └── HasUlid.php         ULID primary key trait
│   ├── AdminUser.php
│   ├── Firm.php
│   ├── FirmUser.php
│   ├── FirmInvitation.php
│   ├── ClientPortalUser.php
│   ├── Plan.php
│   └── Subscription.php
├── Policies/
│   ├── Admin/
│   ├── Firm/
│   └── Portal/
├── Services/
│   ├── Auth/
│   │   ├── AdminAuthService.php
│   │   ├── FirmAuthService.php
│   │   └── PortalAuthService.php
│   ├── Tenancy/
│   │   ├── TenantService.php
│   │   └── TenantContext.php
│   └── User/
│       ├── AdminUserService.php
│       ├── FirmUserService.php
│       └── PortalUserService.php
└── Support/                    Helper classes
```

### Frontend Type System

```
resources/js/types/
├── index.ts                    Base types, re-exports
├── auth.ts                     Auth types (user, session, guards)
├── admin.ts                    Admin-specific types
├── firm.ts                     Firm/tenant types
├── portal.ts                   Portal user types
├── plan.ts                     Plans and subscriptions
└── api.ts                      API response types
```

## Dependencies to Add

### Backend Packages

```bash
composer require spatie/laravel-permission
```

> **Future:** Add `laravel/horizon` when migrating to Redis for queue management.

### Frontend Packages (if needed)

Current setup is comprehensive. May add:
```bash
npm install @tanstack/react-query  # For data fetching (optional)
```

## Success Criteria

### Module 1 Complete When:

1. ✅ Laravel 12 project running (already done)
2. [ ] MySQL database configured and connected
3. [ ] Database cache/session/queue tables verified
4. [ ] Core multi-tenant migrations run successfully
5. [ ] Three authentication guards functional
6. [ ] Admin can log in at /admin/login
7. [ ] Firm user can log in at /app/login
8. [ ] Portal user can log in at /portal/login
9. [ ] Tenant isolation verified (firm users see only their data)
10. [ ] Service layer pattern established with base classes
11. [ ] All guard-specific layouts render correctly
12. [ ] Dark mode works across all layouts
13. [ ] Tests written and passing
14. [ ] Code quality checks passing (Pint, ESLint)

## Migration Strategy

### Handling Existing User Model

The current `User` model will be transformed:

1. Rename `users` table to `firm_users`
2. Add `firm_id` column to `firm_users`
3. Create `admin_users` table (fresh)
4. Create `client_portal_users` table (fresh)
5. Update auth config with three guards
6. Update Fortify to work with firm guard

This approach preserves any existing user data while enabling multi-tenancy.

## Next Modules Preview

- **Module 2**: Platform Admin Panel (tenant management, plans, tax rules)
- **Module 3**: Firm Dashboard & Client Management
- **Module 4**: Financial Data Entry
- **Module 5**: Tax Calculation Engine
- **Module 6**: Reports & Scenarios
- **Module 7**: Client Portal
- **Module 8**: Knowledge Base & CPD Training
