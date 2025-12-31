# TaxLab Implementation - Frontend Additions

## Overview

Building on the existing React 19 + TypeScript + Inertia setup with shadcn/ui components, this document covers the additional frontend structure needed for multi-tenant, multi-guard authentication.

## Current Setup Summary

Already in place:
- React 19.2 with TypeScript 5.7
- Inertia.js 2.1.4
- shadcn/ui components (30+)
- Tailwind CSS 4.0 with dark mode
- App layout with sidebar/header variants
- Auth pages (login, register, password reset, 2FA)
- Settings pages (profile, password, appearance)
- Wayfinder for type-safe routes

---

## Type System Structure

### Directory Organization

```
resources/js/types/
├── index.ts              Re-exports all types
├── auth.ts               Authentication types
├── admin.ts              Admin panel types
├── firm.ts               Firm/tenant types
├── portal.ts             Client portal types
├── plan.ts               Plans and subscriptions
├── client.ts             Tax client types
├── calculation.ts        Calculation types
├── report.ts             Report types
└── api.ts                API response types
```

---

### Core Type Definitions

**File:** `resources/js/types/index.ts`

```typescript
export * from './auth';
export * from './admin';
export * from './firm';
export * from './portal';
export * from './plan';
export * from './api';

export type Nullable<T> = T | null;

export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

export interface SelectOption {
    value: string;
    label: string;
}
```

**File:** `resources/js/types/auth.ts`

```typescript
export type AuthGuard = 'admin' | 'firm' | 'client';

export interface BaseUser {
    id: string;
    name: string;
    email: string;
    email_verified_at: string | null;
    two_factor_confirmed_at: string | null;
    created_at: string;
    updated_at: string;
}

export type AdminRole = 'super_admin' | 'admin' | 'support' | 'content_manager';
export type FirmRole = 'partner' | 'manager' | 'associate' | 'viewer';
export type PortalRole = 'primary' | 'viewer';

export type AdminStatus = 'active' | 'suspended';
export type FirmUserStatus = 'invited' | 'active' | 'suspended';
export type PortalUserStatus = 'invited' | 'active' | 'suspended';

export interface AdminUser extends BaseUser {
    role: AdminRole;
    status: AdminStatus;
    password_changed_at: string | null;
    last_login_at: string | null;
    last_login_ip: string | null;
}

export interface FirmUser extends BaseUser {
    firm_id: string;
    role: FirmRole;
    status: FirmUserStatus;
    job_title: string | null;
    phone: string | null;
    last_login_at: string | null;
    last_login_ip: string | null;
    notification_preferences: Record<string, unknown> | null;
    invited_by: string | null;
    invited_at: string | null;
}

export interface PortalUser extends BaseUser {
    tax_client_id: string;
    firm_id: string;
    role: PortalRole;
    status: PortalUserStatus;
    phone: string | null;
    last_login_at: string | null;
    last_login_ip: string | null;
    invited_by: string | null;
}

export interface AuthState {
    user: AdminUser | FirmUser | PortalUser | null;
    guard: AuthGuard | null;
}

export interface PageProps {
    auth: AuthState;
    firm: FirmContext | null;
    flash: FlashMessages;
    errors: Record<string, string>;
}

export interface FirmContext {
    id: string;
    name: string;
    status: string;
}

export interface FlashMessages {
    success?: string;
    error?: string;
    warning?: string;
    info?: string;
}
```

**File:** `resources/js/types/firm.ts`

```typescript
import type { Plan } from './plan';

export type FirmStatus = 'active' | 'inactive' | 'suspended' | 'trial';

export interface Firm {
    id: string;
    name: string;
    slug: string;
    email: string;
    phone: string | null;
    address: string | null;
    city: string | null;
    state: string | null;
    rc_number: string | null;
    tin: string | null;
    status: FirmStatus;
    plan_id: string | null;
    billing_cycle: 'monthly' | 'annual';
    trial_ends_at: string | null;
    subscription_ends_at: string | null;
    settings: FirmSettings | null;
    created_at: string;
    updated_at: string;
    plan?: Plan;
    branding?: FirmBranding;
}

export interface FirmBranding {
    id: string;
    firm_id: string;
    logo_path: string | null;
    favicon_path: string | null;
    primary_color: string;
    secondary_color: string;
    email_header: string | null;
    email_footer: string | null;
    report_footer: string | null;
}

export interface FirmSettings {
    require_2fa?: boolean;
    require_report_approval?: boolean;
    default_report_format?: 'pdf' | 'docx';
    timezone?: string;
    date_format?: string;
}

export interface FirmInvitation {
    id: string;
    firm_id: string;
    email: string;
    name: string | null;
    role: FirmRole;
    status: 'pending' | 'accepted' | 'expired' | 'cancelled';
    expires_at: string;
    accepted_at: string | null;
    created_at: string;
}
```

**File:** `resources/js/types/plan.ts`

```typescript
export interface Plan {
    id: string;
    name: string;
    slug: string;
    description: string | null;
    monthly_price: number;
    annual_price: number;
    client_limit: number | null;
    user_limit: number | null;
    report_limit: number | null;
    features: string[] | null;
    is_active: boolean;
    is_featured: boolean;
    sort_order: number;
}

export interface Subscription {
    id: string;
    firm_id: string;
    plan_id: string;
    status: 'active' | 'cancelled' | 'past_due' | 'trialing';
    billing_cycle: 'monthly' | 'annual';
    amount: number;
    current_period_start: string;
    current_period_end: string;
    trial_ends_at: string | null;
    cancelled_at: string | null;
    plan?: Plan;
}
```

**File:** `resources/js/types/api.ts`

```typescript
import type { PageProps } from './auth';

export interface ApiResponse<T = unknown> {
    data: T;
    message?: string;
}

export interface ApiError {
    message: string;
    errors?: Record<string, string[]>;
}

export interface InertiaPageProps<T = Record<string, unknown>> extends PageProps {
    [key: string]: unknown;
}
```

---

## Layout System

### Layout Structure

```
resources/js/layouts/
├── app-layout.tsx            Existing - firm user layout
├── auth-layout.tsx           Existing - auth pages
├── admin/
│   ├── admin-layout.tsx      Admin panel layout
│   └── admin-sidebar.tsx     Admin navigation
├── portal/
│   ├── portal-layout.tsx     Client portal layout
│   └── portal-header.tsx     Portal navigation
└── settings/
    └── layout.tsx            Existing - settings pages
```

---

### Admin Layout

**File:** `resources/js/layouts/admin/admin-layout.tsx`

```tsx
import { PropsWithChildren } from 'react';
import { AdminSidebar } from './admin-sidebar';
import { AppShell } from '@/components/app-shell';
import { useAppearance } from '@/hooks/use-appearance';

interface AdminLayoutProps extends PropsWithChildren {
    title?: string;
}

export function AdminLayout({ children, title }: AdminLayoutProps) {
    const { appearance } = useAppearance();

    return (
        <div className={appearance}>
            <AppShell variant="sidebar">
                <AdminSidebar />
                <main className="flex-1 overflow-auto">
                    {title && (
                        <header className="border-b bg-background px-6 py-4">
                            <h1 className="text-2xl font-semibold">{title}</h1>
                        </header>
                    )}
                    <div className="p-6">{children}</div>
                </main>
            </AppShell>
        </div>
    );
}
```

**File:** `resources/js/layouts/admin/admin-sidebar.tsx`

```tsx
import { Link, usePage } from '@inertiajs/react';
import {
    Building2,
    CreditCard,
    FileText,
    GraduationCap,
    LayoutDashboard,
    Scale,
    Settings,
    Users,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { AppLogo } from '@/components/app-logo';

const navigation = [
    { name: 'Dashboard', href: '/admin/dashboard', icon: LayoutDashboard },
    { name: 'Tenants', href: '/admin/tenants', icon: Building2 },
    { name: 'Plans', href: '/admin/plans', icon: CreditCard },
    { name: 'Tax Rules', href: '/admin/tax-rules', icon: Scale },
    { name: 'Knowledge Base', href: '/admin/knowledge-base', icon: FileText },
    { name: 'CPD Courses', href: '/admin/courses', icon: GraduationCap },
    { name: 'Admin Users', href: '/admin/users', icon: Users },
    { name: 'Settings', href: '/admin/settings', icon: Settings },
];

export function AdminSidebar() {
    const { url } = usePage();

    return (
        <aside className="flex h-full w-64 flex-col border-r bg-sidebar">
            <div className="flex h-16 items-center border-b px-6">
                <AppLogo />
                <span className="ml-2 text-xs font-medium text-muted-foreground">
                    Admin
                </span>
            </div>

            <nav className="flex-1 space-y-1 p-4">
                {navigation.map((item) => {
                    const isActive = url.startsWith(item.href);
                    return (
                        <Link
                            key={item.name}
                            href={item.href}
                            className={cn(
                                'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                                isActive
                                    ? 'bg-sidebar-accent text-sidebar-accent-foreground'
                                    : 'text-sidebar-foreground hover:bg-sidebar-accent/50'
                            )}
                        >
                            <item.icon className="h-5 w-5" />
                            {item.name}
                        </Link>
                    );
                })}
            </nav>
        </aside>
    );
}
```

---

### Portal Layout

**File:** `resources/js/layouts/portal/portal-layout.tsx`

```tsx
import { PropsWithChildren } from 'react';
import { PortalHeader } from './portal-header';
import { useAppearance } from '@/hooks/use-appearance';

interface PortalLayoutProps extends PropsWithChildren {
    title?: string;
}

export function PortalLayout({ children, title }: PortalLayoutProps) {
    const { appearance } = useAppearance();

    return (
        <div className={cn('min-h-screen bg-background', appearance)}>
            <PortalHeader />
            <main className="container mx-auto px-4 py-8">
                {title && (
                    <h1 className="mb-6 text-2xl font-semibold">{title}</h1>
                )}
                {children}
            </main>
        </div>
    );
}
```

**File:** `resources/js/layouts/portal/portal-header.tsx`

```tsx
import { Link, usePage } from '@inertiajs/react';
import { FileText, Home, LogOut, Settings, User } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import type { PortalUser } from '@/types';

export function PortalHeader() {
    const { auth, firm } = usePage().props;
    const user = auth.user as PortalUser;
    const initials = useInitials(user?.name);

    return (
        <header className="border-b bg-background">
            <div className="container mx-auto flex h-16 items-center justify-between px-4">
                <div className="flex items-center gap-6">
                    <Link href="/portal/dashboard" className="font-semibold">
                        {firm?.name ?? 'Client Portal'}
                    </Link>

                    <nav className="hidden items-center gap-4 md:flex">
                        <Link
                            href="/portal/dashboard"
                            className="flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
                        >
                            <Home className="h-4 w-4" />
                            Dashboard
                        </Link>
                        <Link
                            href="/portal/reports"
                            className="flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
                        >
                            <FileText className="h-4 w-4" />
                            Reports
                        </Link>
                    </nav>
                </div>

                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="ghost" className="relative h-8 w-8 rounded-full">
                            <Avatar className="h-8 w-8">
                                <AvatarFallback>{initials}</AvatarFallback>
                            </Avatar>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <div className="px-2 py-1.5">
                            <p className="text-sm font-medium">{user?.name}</p>
                            <p className="text-xs text-muted-foreground">{user?.email}</p>
                        </div>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem asChild>
                            <Link href="/portal/settings">
                                <Settings className="mr-2 h-4 w-4" />
                                Settings
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem asChild>
                            <Link href="/portal/logout" method="post" as="button">
                                <LogOut className="mr-2 h-4 w-4" />
                                Log out
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </header>
    );
}
```

---

## Page Structure

### Directory Organization

```
resources/js/pages/
├── auth/                     Existing auth pages
├── settings/                 Existing settings pages
├── dashboard.tsx             Existing (rename to app dashboard)
├── welcome.tsx               Existing landing page
├── Admin/
│   ├── Auth/
│   │   └── Login.tsx
│   ├── Dashboard.tsx
│   ├── Tenants/
│   │   ├── Index.tsx
│   │   ├── Show.tsx
│   │   └── Create.tsx
│   ├── Plans/
│   │   ├── Index.tsx
│   │   └── Edit.tsx
│   └── Users/
│       └── Index.tsx
├── App/
│   ├── Dashboard.tsx
│   ├── Clients/
│   │   ├── Index.tsx
│   │   ├── Show.tsx
│   │   ├── Create.tsx
│   │   └── Edit.tsx
│   └── Team/
│       ├── Index.tsx
│       ├── Create.tsx
│       └── Edit.tsx
└── Portal/
    ├── Auth/
    │   └── Login.tsx
    ├── Dashboard.tsx
    └── Reports/
        ├── Index.tsx
        └── Show.tsx
```

---

### Example Admin Page

**File:** `resources/js/pages/Admin/Dashboard.tsx`

```tsx
import { Head } from '@inertiajs/react';
import { AdminLayout } from '@/layouts/admin/admin-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Building2, CreditCard, TrendingUp, Users } from 'lucide-react';

interface DashboardProps {
    stats: {
        total_firms: number;
        active_firms: number;
        total_revenue: number;
        new_signups_this_month: number;
    };
}

export default function Dashboard({ stats }: DashboardProps) {
    const cards = [
        {
            title: 'Total Firms',
            value: stats.total_firms.toLocaleString(),
            icon: Building2,
        },
        {
            title: 'Active Firms',
            value: stats.active_firms.toLocaleString(),
            icon: Users,
        },
        {
            title: 'Monthly Revenue',
            value: `₦${(stats.total_revenue / 100).toLocaleString()}`,
            icon: CreditCard,
        },
        {
            title: 'New Signups',
            value: stats.new_signups_this_month.toLocaleString(),
            icon: TrendingUp,
        },
    ];

    return (
        <AdminLayout title="Dashboard">
            <Head title="Admin Dashboard" />

            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                {cards.map((card) => (
                    <Card key={card.title}>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                {card.title}
                            </CardTitle>
                            <card.icon className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-bold">{card.value}</p>
                        </CardContent>
                    </Card>
                ))}
            </div>
        </AdminLayout>
    );
}
```

---

### Example Portal Page

**File:** `resources/js/pages/Portal/Dashboard.tsx`

```tsx
import { Head } from '@inertiajs/react';
import { PortalLayout } from '@/layouts/portal/portal-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { FileText, TrendingDown, TrendingUp } from 'lucide-react';

interface DashboardProps {
    summary: {
        total_tax_liability: number;
        change_from_old_regime: number;
        pending_reports: number;
    };
    recent_reports: Array<{
        id: string;
        title: string;
        created_at: string;
    }>;
}

export default function Dashboard({ summary, recent_reports }: DashboardProps) {
    const isPositiveChange = summary.change_from_old_regime > 0;

    return (
        <PortalLayout title="Dashboard">
            <Head title="Portal Dashboard" />

            <div className="grid gap-6 md:grid-cols-3">
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">
                            Total Tax Liability
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="text-2xl font-bold">
                            ₦{(summary.total_tax_liability / 100).toLocaleString()}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">
                            Change from Old Regime
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="flex items-center gap-2">
                        {isPositiveChange ? (
                            <TrendingUp className="h-5 w-5 text-destructive" />
                        ) : (
                            <TrendingDown className="h-5 w-5 text-green-600" />
                        )}
                        <p className="text-2xl font-bold">
                            {isPositiveChange ? '+' : ''}
                            {summary.change_from_old_regime.toFixed(1)}%
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">
                            Available Reports
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="flex items-center gap-2">
                        <FileText className="h-5 w-5 text-muted-foreground" />
                        <p className="text-2xl font-bold">{summary.pending_reports}</p>
                    </CardContent>
                </Card>
            </div>

            <Card className="mt-6">
                <CardHeader>
                    <CardTitle>Recent Reports</CardTitle>
                </CardHeader>
                <CardContent>
                    {recent_reports.length === 0 ? (
                        <p className="text-muted-foreground">No reports available yet.</p>
                    ) : (
                        <ul className="space-y-2">
                            {recent_reports.map((report) => (
                                <li key={report.id} className="flex items-center justify-between">
                                    <span>{report.title}</span>
                                    <span className="text-sm text-muted-foreground">
                                        {new Date(report.created_at).toLocaleDateString()}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </CardContent>
            </Card>
        </PortalLayout>
    );
}
```

---

## Auth Guard Hook

**File:** `resources/js/hooks/use-auth.ts`

```typescript
import { usePage } from '@inertiajs/react';
import type { AuthState, AdminUser, FirmUser, PortalUser, FirmRole } from '@/types';

export function useAuth() {
    const { auth } = usePage().props as { auth: AuthState };

    const isAdmin = auth.guard === 'admin';
    const isFirmUser = auth.guard === 'firm';
    const isPortalUser = auth.guard === 'client';

    return {
        user: auth.user,
        guard: auth.guard,
        isAdmin,
        isFirmUser,
        isPortalUser,
        isAuthenticated: auth.user !== null,
    };
}

export function useAdminUser(): AdminUser | null {
    const { user, isAdmin } = useAuth();
    return isAdmin ? (user as AdminUser) : null;
}

export function useFirmUser(): FirmUser | null {
    const { user, isFirmUser } = useAuth();
    return isFirmUser ? (user as FirmUser) : null;
}

export function usePortalUser(): PortalUser | null {
    const { user, isPortalUser } = useAuth();
    return isPortalUser ? (user as PortalUser) : null;
}

export function useFirmRole(): FirmRole | null {
    const user = useFirmUser();
    return user?.role ?? null;
}

export function useCanManageTeam(): boolean {
    const role = useFirmRole();
    return role === 'partner';
}

export function useCanApproveReports(): boolean {
    const role = useFirmRole();
    return role === 'partner' || role === 'manager';
}
```

---

## Form Components with Validation

**File:** `resources/js/components/forms/form-field.tsx`

```tsx
import { ReactNode } from 'react';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

interface FormFieldProps {
    label: string;
    htmlFor: string;
    error?: string;
    required?: boolean;
    children: ReactNode;
    className?: string;
}

export function FormField({
    label,
    htmlFor,
    error,
    required,
    children,
    className,
}: FormFieldProps) {
    return (
        <div className={cn('space-y-2', className)}>
            <Label htmlFor={htmlFor}>
                {label}
                {required && <span className="text-destructive">*</span>}
            </Label>
            {children}
            {error && (
                <p className="text-sm text-destructive">{error}</p>
            )}
        </div>
    );
}
```

---

## Dark Mode Support

All layouts and components already support dark mode through Tailwind CSS classes and the existing `useAppearance` hook. Ensure all new components use semantic color tokens:

```tsx
className="bg-background text-foreground"
className="border-border"
className="text-muted-foreground"
className="bg-card text-card-foreground"
className="bg-destructive text-destructive-foreground"
```

---

## Next Steps

Once frontend additions are complete, proceed to:
→ **07_TESTING_STRATEGY.md** - Testing approach for the application
