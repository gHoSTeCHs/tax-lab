# TaxLab Implementation Plan - Module 3: Firm Dashboard & Client Management

## Overview

This module implements the Firm Dashboard and Client Management features - the core practitioner-facing functionality of TaxLab. It builds upon the multi-tenant architecture and authentication system established in Module 1, and the admin panel from Module 2.

## Prerequisites from Module 1 & 2

| Component | Status | Required For |
|-----------|--------|--------------|
| Firm model with multi-tenancy | Required | All tenant-scoped data |
| FirmUser model with roles | Required | Role-based access control |
| FirmRole enum with permissions | Required | Permission checks |
| Firm guard authentication | Required | `/app/*` routes |
| BelongsToFirm trait | Required | Tenant isolation |
| TenantContext service | Required | Current firm resolution |

## What This Module Covers

Based on specifications from:
- `docs/05_FIRM_DASHBOARD.md` - Dashboard features and layout
- `docs/06_CLIENT_MANAGEMENT.md` - Client CRUD and management

### Phase 3.1: Additional Database Schema
- [ ] Industries table (Nigerian industry classifications)
- [ ] Tax clients table (full entity with all fields)
- [ ] Client user assignments table (M:N relationship)
- [ ] Client notes table (with pin support)
- [ ] Activity logs table (polymorphic)
- [ ] Client access tracking table
- [ ] TIN/NIN encryption in TaxClient model

### Phase 3.2: Firm Dashboard Backend
- [ ] Dashboard controller at `/app/dashboard`
- [ ] Dashboard metrics service with role-based calculations
- [ ] Activity service for logging and retrieval
- [ ] Attention items logic (prioritized alerts)
- [ ] Global search controller at `/app/search`
- [ ] Search service with recent searches
- [ ] Caching strategy implementation
- [ ] Cache invalidation listeners

### Phase 3.3: Firm Dashboard Frontend
- [ ] Dashboard page with role-aware layout
- [ ] Welcome section with time-based greeting
- [ ] Key metrics cards (role-specific)
- [ ] Attention items panel (High/Medium/Low priority)
- [ ] Activity feed widget with lazy loading
- [ ] Recent clients widget
- [ ] Quick links widget
- [ ] Empty states (new firm, new user onboarding)
- [ ] Role-specific widgets (Firm Health, Team Overview, etc.)
- [ ] Global search modal with keyboard shortcut (/)

### Phase 3.4: Client Management Backend
- [ ] Client CRUD controller
- [ ] Client assignment controller
- [ ] Client notes controller
- [ ] Client import controller (CSV/XLSX)
- [ ] Client service layer
- [ ] Client access policies (role-based)
- [ ] Duplicate detection service (TIN, NIN, CAC, name similarity)
- [ ] Validation rules (TIN, NIN, CAC format)

### Phase 3.5: Client Management Frontend
- [ ] Client list with DataTable, filters, sorting
- [ ] Client creation wizard (multi-step)
- [ ] Entity type-specific form fields
- [ ] Duplicate warning dialog
- [ ] Client profile with tabbed navigation
- [ ] Client import wizard (upload, mapping, preview, process)
- [ ] Assignment dialogs
- [ ] Bulk actions toolbar

---

## Module Structure

```
module3/
├── 00_OVERVIEW.md              (this file)
├── 01_DATABASE_SCHEMA.md       Migrations, models, enums, seeders
├── 02_DASHBOARD_BACKEND.md     Dashboard controller and services
├── 03_DASHBOARD_FRONTEND.md    Dashboard React components
├── 04_CLIENT_BACKEND.md        Client controllers and services
├── 05_CLIENT_FRONTEND.md       Client React components
└── 06_TESTING_STRATEGY.md      Testing approach and examples
```

---

## Route Structure

All firm routes use the `firm` guard and require authentication.

```
/app
├── /dashboard                  Main dashboard
├── /dashboard/metrics          AJAX endpoint for metrics refresh
├── /dashboard/activity         AJAX endpoint for activity feed
├── /clients                    Client list
│   ├── /create                 Create client wizard
│   ├── /{client}               Client profile (overview tab)
│   ├── /{client}/edit          Edit client
│   ├── /{client}/financials    Financials tab (future module)
│   ├── /{client}/calculations  Calculations tab (future module)
│   ├── /{client}/reports       Reports tab (future module)
│   ├── /{client}/scenarios     Scenarios tab (future module)
│   ├── /{client}/portal        Portal settings tab
│   ├── /{client}/notes         Client notes API
│   ├── /{client}/assignments   Client assignments API
│   ├── /{client}/restore       Restore archived client
│   └── /bulk-action            Bulk operations
├── /clients/import             Import wizard
│   ├── /upload                 Upload file
│   ├── /validate               Validate mapping
│   ├── /preview                Preview import
│   ├── /process                Execute import
│   └── /template               Download template
└── /search                     Global search
```

---

## Controller Structure

```
app/Http/Controllers/App/
├── DashboardController.php
├── ClientController.php
├── ClientAssignmentController.php
├── ClientNoteController.php
├── ClientImportController.php
└── SearchController.php
```

---

## Service Layer Structure

```
app/Services/
├── Dashboard/
│   ├── DashboardService.php
│   └── DashboardMetricsService.php
├── Client/
│   ├── ClientService.php
│   ├── ClientAccessService.php
│   ├── ClientImportService.php
│   └── DuplicateDetectionService.php
├── Activity/
│   └── ActivityService.php
└── Search/
    └── SearchService.php
```

---

## Frontend Structure

```
resources/js/
├── pages/App/
│   ├── Dashboard/
│   │   ├── Index.tsx
│   │   └── components/
│   │       ├── WelcomeSection.tsx
│   │       ├── MetricsGrid.tsx
│   │       ├── MetricCard.tsx
│   │       ├── AttentionSection.tsx
│   │       ├── AttentionItem.tsx
│   │       ├── ActivityFeed.tsx
│   │       ├── ActivityItem.tsx
│   │       ├── RecentClientsWidget.tsx
│   │       ├── QuickLinksWidget.tsx
│   │       ├── EmptyState.tsx
│   │       ├── FirmHealthWidget.tsx
│   │       ├── TeamOverviewWidget.tsx
│   │       ├── TeamWorkloadWidget.tsx
│   │       └── MyPerformanceWidget.tsx
│   └── Clients/
│       ├── Index.tsx
│       ├── Create.tsx
│       ├── Show.tsx
│       ├── Edit.tsx
│       ├── Import.tsx
│       └── components/
│           ├── ClientListTable.tsx
│           ├── ClientFilters.tsx
│           ├── ClientSearchInput.tsx
│           ├── ClientBulkActions.tsx
│           ├── ClientRowActions.tsx
│           ├── ClientCreateWizard.tsx
│           ├── EntityTypeStep.tsx
│           ├── BasicInfoStep.tsx
│           ├── ContactStep.tsx
│           ├── IndustryStep.tsx
│           ├── AssignmentStep.tsx
│           ├── ClientProfileHeader.tsx
│           ├── ClientProfileTabs.tsx
│           ├── ClientOverviewTab.tsx
│           ├── ClientDetailsCard.tsx
│           ├── ClientQuickStats.tsx
│           ├── ClientTaxPositionSummary.tsx
│           ├── ClientNotesSection.tsx
│           ├── ClientNoteItem.tsx
│           ├── ClientAssignmentDialog.tsx
│           ├── AssignedUsersList.tsx
│           ├── ClientImportWizard.tsx
│           ├── ImportUploadStep.tsx
│           ├── ImportMappingStep.tsx
│           ├── ImportPreviewStep.tsx
│           ├── ImportResultStep.tsx
│           ├── ClientEmptyState.tsx
│           └── ClientArchivedBanner.tsx
├── components/ui/
│   ├── data-table.tsx
│   ├── data-table-column-header.tsx
│   ├── data-table-pagination.tsx
│   ├── data-table-toolbar.tsx
│   ├── data-table-faceted-filter.tsx
│   ├── multi-step-form.tsx
│   ├── step-indicator.tsx
│   ├── file-upload.tsx
│   ├── combobox.tsx
│   └── date-picker.tsx
├── hooks/
│   ├── use-greeting.ts
│   ├── use-dashboard-metrics.ts
│   ├── use-activity-feed.ts
│   ├── use-clients.ts
│   ├── use-client.ts
│   ├── use-client-filters.ts
│   ├── use-client-bulk-actions.ts
│   └── use-client-import.ts
└── types/
    ├── dashboard.ts
    └── client.ts
```

---

## Role-Based Access Matrix

### Dashboard Metrics by Role

| Role | Metrics Displayed |
|------|------------------|
| Partner | Active Clients, Reports This Month, Pending Approvals, Team Activity |
| Manager | Active Clients, My Team's Reports, Pending Approvals, Calculations This Week |
| Associate | My Clients, My Reports This Month, Pending Review, Calculations This Week |
| Viewer | Assigned Clients, Recent Reports |

### Client Access by Role

| Action | Partner | Manager | Associate | Viewer |
|--------|---------|---------|-----------|--------|
| View all clients | ✅ | ✅ | ❌ | ❌ |
| View assigned clients | ✅ | ✅ | ✅ | ✅ |
| Create clients | ✅ | ✅ | ✅ | ❌ |
| Edit clients | ✅ | ✅ | Assigned only | ❌ |
| Delete clients | ✅ | ✅ | ❌ | ❌ |
| Archive/Restore | ✅ | ✅ | ❌ | ❌ |
| Assign users | ✅ | ✅ | ❌ | ❌ |
| Import clients | ✅ | ✅ | ❌ | ❌ |
| Add notes | ✅ | ✅ | ✅ | ❌ |

---

## Data Flow

### Dashboard Data Loading

```
1. Initial page load (SSR)
   └── Inertia renders dashboard with:
       ├── Key metrics (cached)
       ├── Attention items (cached)
       └── Quick links (computed)

2. Deferred loading (client-side)
   ├── Activity feed (paginated API call)
   └── Recent clients (API call)

3. Refresh cycle
   └── SWR revalidates metrics every 5 minutes
```

### Client List Data Loading

```
1. Initial page load
   └── Inertia renders list with:
       ├── Paginated clients (15 per page)
       ├── Filter options (entity types, industries)
       └── Current filters from URL

2. User interactions
   ├── Search: Debounced API call
   ├── Filter change: URL update + reload
   └── Sort change: URL update + reload

3. Bulk actions
   └── Selected IDs sent to bulk action endpoint
```

---

## Caching Strategy

| Data | Cache Key | TTL | Invalidation |
|------|-----------|-----|--------------|
| Dashboard metrics | `firm:{id}:dashboard:metrics:{role}` | 5 min | Client/Report CRUD |
| Attention items | `firm:{id}:dashboard:attention` | 5 min | Client/Report/Sub changes |
| Recent activity | `firm:{id}:dashboard:activity` | 2 min | Any logged activity |
| Recent clients | `user:{id}:recent_clients` | 5 min | Client access |
| Client count | `firm:{id}:client_count` | 10 min | Client create/delete |
| Industry list | `industries:active` | 1 hour | Industry changes |

---

## Dependencies

### Backend Packages

```bash
composer require maatwebsite/excel
```

### Frontend Packages

```bash
npm install @tanstack/react-table date-fns swr
```

---

## Success Criteria

### Module 3 Complete When:

**Dashboard Features:**
1. [ ] Dashboard renders with role-appropriate layout
2. [ ] Key metrics display correct data per role (Partner/Manager/Associate/Viewer)
3. [ ] Attention items prioritized (High/Medium/Low) with action links
4. [ ] Activity feed shows relevant actions with lazy loading/pagination
5. [ ] Recent clients widget functional (5 most recent)
6. [ ] Quick links widget shows role-appropriate shortcuts
7. [ ] Empty states render for new firm/new user with onboarding CTAs
8. [ ] Role-specific widgets render (Firm Health, Team Overview, etc.)
9. [ ] Global search works with keyboard shortcut (/)

**Client Management Features:**
10. [ ] Client list displays with search, filters, sorting, pagination
11. [ ] Client creation wizard works for all 6 entity types
12. [ ] Entity-specific validation (TIN, NIN, CAC formats)
13. [ ] Duplicate detection warns before creating similar clients
14. [ ] Client profile renders with tabbed navigation (Overview, Notes)
15. [ ] Client assignment works for Associates/Viewers
16. [ ] Client import handles CSV/XLSX with validation and preview
17. [ ] Client notes CRUD functional with pin feature
18. [ ] Bulk actions (archive, assign) work correctly
19. [ ] Archive/Restore functionality works

**Security & Data Protection:**
20. [ ] TIN/NIN encrypted at rest
21. [ ] All policies enforced (role-based access)
22. [ ] Associates/Viewers only see assigned clients

**Performance & UX:**
23. [ ] Caching implemented with proper invalidation
24. [ ] Dark mode works across all components
25. [ ] Responsive design works on mobile/tablet

**Quality:**
26. [ ] Tests written and passing (80%+ coverage)
27. [ ] No TypeScript errors in frontend

---

## Next Modules Preview

- **Module 4**: Financial Data Entry (income, deductions, assets)
- **Module 5**: Tax Calculation Engine (CIT, WHT, VAT analysis)
- **Module 6**: Reports & Scenarios (impact analysis, what-if)
- **Module 7**: Client Portal (external client access)
- **Module 8**: Knowledge Base & CPD Training
