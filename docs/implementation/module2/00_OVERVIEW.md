# TaxLab Implementation Plan - Module 2: Platform Admin Panel

## Overview

This module implements the Platform Admin section - the control center for operating TaxLab as a business. It builds upon the authentication guards and multi-tenant architecture established in Module 1.

## Prerequisites from Module 1

| Component | Status | Required For |
|-----------|--------|--------------|
| Admin guard authentication | Required | All admin routes |
| AdminUser model with roles | Required | Role-based access |
| AdminRole enum | Required | Permission checks |
| Firm model | Required | Tenant management |
| Plan model | Required | Plan management |
| FirmUser model | Required | User listings |

## What This Module Covers

Based on specifications from:
- `docs/03_AUTH_AND_ACCESS_CONTROL.md` - Admin authentication & permissions
- `docs/04_PLATFORM_ADMIN_MODULES.md` - Admin module features

### Phase 2.1: Admin Authentication Enhancement
- [x] Admin guard configured (Module 1)
- [ ] Admin login page at `/admin/login`
- [ ] Mandatory 2FA enforcement for admin users
- [ ] Admin session management (60-min lifetime, 15-min idle timeout)
- [ ] Single session enforcement
- [ ] Enhanced audit logging for admin actions

### Phase 2.2: Admin Dashboard
- [ ] Dashboard layout and navigation
- [ ] Key metrics cards (Active Tenants, MRR, Trial Conversions, etc.)
- [ ] Revenue chart (MRR over 12 months)
- [ ] Tenant funnel visualization
- [ ] Recent activity feed
- [ ] Attention required items panel
- [ ] Dashboard data caching strategy

### Phase 2.3: Tenant Management
- [ ] Tenant list view with filtering and sorting
- [ ] Tenant detail view (overview, subscription, usage, users, activity, billing)
- [ ] Tenant actions (edit, change plan, extend trial, suspend, reactivate)
- [ ] Tenant notes system for support team
- [ ] Impersonation feature (Super Admin only)
- [ ] Tenant data export

### Phase 2.4: Subscription Plan Management
- [ ] Plan list view
- [ ] Plan CRUD operations
- [ ] Plan limits configuration
- [ ] Plan features configuration
- [ ] Plan deprecation workflow

## Module Structure

```
module2/
├── 00_OVERVIEW.md              (this file)
├── 01_ADMIN_DASHBOARD.md       Dashboard implementation
├── 02_TENANT_MANAGEMENT.md     Tenant CRUD and actions
├── 03_PLAN_MANAGEMENT.md       Subscription plans
├── 04_ADDITIONAL_MIGRATIONS.md New database tables
├── 05_FRONTEND_COMPONENTS.md   Admin React components
└── 06_TESTING_STRATEGY.md      Testing approach
```

## Route Structure

All admin routes use the `admin` guard and require authentication.

```
/admin
├── /login                      Admin login page
├── /two-factor                 2FA challenge
├── /dashboard                  Main dashboard
├── /tenants                    Tenant list
│   ├── /{tenant}               Tenant detail
│   ├── /{tenant}/edit          Edit tenant
│   ├── /{tenant}/users         Tenant users
│   ├── /{tenant}/activity      Tenant activity
│   ├── /{tenant}/billing       Billing history
│   ├── /{tenant}/notes         Support notes
│   └── /{tenant}/impersonate   Start impersonation
├── /plans                      Plan list
│   ├── /create                 Create plan
│   ├── /{plan}                 Plan detail
│   └── /{plan}/edit            Edit plan
└── /settings                   Admin settings (personal)
```

## Controller Structure

```
app/Http/Controllers/Admin/
├── Auth/
│   ├── LoginController.php
│   ├── TwoFactorController.php
│   └── LogoutController.php
├── DashboardController.php
├── TenantController.php
├── TenantUserController.php
├── TenantNoteController.php
├── TenantActivityController.php
├── ImpersonationController.php
├── PlanController.php
└── SettingsController.php
```

## Service Layer

```
app/Services/Admin/
├── DashboardService.php        Dashboard metrics & data
├── TenantService.php           Tenant operations
├── TenantStatsService.php      Tenant statistics
├── ImpersonationService.php    Impersonation logic
├── PlanService.php             Plan operations
└── AdminAuditService.php       Audit logging
```

## Frontend Structure

```
resources/js/
├── layouts/
│   └── AdminLayout.tsx         Admin shell with sidebar
├── pages/Admin/
│   ├── Auth/
│   │   ├── Login.tsx
│   │   └── TwoFactor.tsx
│   ├── Dashboard/
│   │   ├── Index.tsx
│   │   └── components/
│   │       ├── MetricsCards.tsx
│   │       ├── RevenueChart.tsx
│   │       ├── TenantFunnel.tsx
│   │       ├── ActivityFeed.tsx
│   │       └── AttentionItems.tsx
│   ├── Tenants/
│   │   ├── Index.tsx
│   │   ├── Show.tsx
│   │   ├── Edit.tsx
│   │   └── components/
│   │       ├── TenantTable.tsx
│   │       ├── TenantFilters.tsx
│   │       ├── TenantOverview.tsx
│   │       ├── TenantSubscription.tsx
│   │       ├── TenantUsage.tsx
│   │       ├── TenantUsers.tsx
│   │       ├── TenantActivity.tsx
│   │       ├── TenantBilling.tsx
│   │       ├── TenantNotes.tsx
│   │       └── TenantActions.tsx
│   └── Plans/
│       ├── Index.tsx
│       ├── Create.tsx
│       ├── Show.tsx
│       ├── Edit.tsx
│       └── components/
│           ├── PlanTable.tsx
│           ├── PlanForm.tsx
│           ├── PlanLimits.tsx
│           └── PlanFeatures.tsx
└── types/
    └── admin.ts                Admin-specific types
```

## Security Requirements (from Spec Section 2.4)

### Password Policy for Admins
- Minimum 16 characters
- Must contain: uppercase, lowercase, number, symbol
- Cannot match previous 5 passwords
- Maximum age: 90 days
- Forced change on first login

### Two-Factor Authentication
- Mandatory for all admin accounts
- TOTP-based (RFC 6238)
- 8 recovery codes generated at setup
- 2FA required before accessing any protected route

### Session Management
- 60-minute session lifetime
- 15-minute idle timeout
- Single session per user (new login invalidates old)
- Sessions invalidated on password change
- Session bound to IP (optional, configurable)

### Login Security
- 5 failed attempts triggers 15-minute lockout
- Lockout escalates: 15min → 1hr → 24hr
- Suspicious login notifications
- Concurrent session limit: 1

## Permission Checks

All controller actions must verify admin permissions using the AdminRole enum:

```php
public function index()
{
    $this->authorize('viewAny', Firm::class);

}

public function impersonate(Firm $firm, FirmUser $user)
{
    if (!auth('admin')->user()->role->hasPermission('impersonate_firm_user')) {
        abort(403);
    }

}
```

## Success Criteria

### Module 2 Complete When:

1. [ ] Admin can log in at `/admin/login` with 2FA
2. [ ] Admin dashboard displays real metrics
3. [ ] Tenant list shows all firms with filtering
4. [ ] Tenant detail shows complete information
5. [ ] Tenant actions work (edit, suspend, reactivate)
6. [ ] Super Admin can impersonate firm users
7. [ ] Plan CRUD operations work
8. [ ] Plan limits and features configurable
9. [ ] All admin actions are audit logged
10. [ ] Admin sessions enforce security policies
11. [ ] Permission checks enforced on all routes
12. [ ] Dark mode works across admin layout
13. [ ] Tests written and passing

## Dependencies

### Backend Packages
No additional packages required beyond Module 1.

### Frontend Packages
```bash
npm install recharts              # For charts
npm install @tanstack/react-table # For data tables (optional)
```

## Next Modules Preview

- **Module 3**: Tax Rules & Legislation Management
- **Module 4**: CPD Content Management
- **Module 5**: Knowledge Base Management
- **Module 6**: Support Tools & Analytics
