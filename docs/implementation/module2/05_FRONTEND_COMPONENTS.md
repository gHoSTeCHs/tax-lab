# Module 2: Frontend Components

## Overview

This document covers the React/TypeScript frontend components for the Platform Admin Panel using React 19, Inertia.js, and shadcn/ui.

---

## Admin Layout

### AdminLayout.tsx

```tsx
import { PropsWithChildren, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import {
    LayoutDashboard,
    Building2,
    CreditCard,
    Settings,
    LogOut,
    Menu,
    X,
    ChevronDown,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';

interface AdminUser {
    id: string;
    name: string;
    email: string;
    role: string;
}

interface PageProps {
    auth: {
        admin: AdminUser;
    };
}

const navigation = [
    { name: 'Dashboard', href: '/admin/dashboard', icon: LayoutDashboard },
    { name: 'Tenants', href: '/admin/tenants', icon: Building2 },
    { name: 'Plans', href: '/admin/plans', icon: CreditCard },
    { name: 'Settings', href: '/admin/settings', icon: Settings },
];

export default function AdminLayout({ children }: PropsWithChildren) {
    const { auth } = usePage<PageProps>().props;
    const [sidebarOpen, setSidebarOpen] = useState(false);

    return (
        <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
            {/* Mobile sidebar overlay */}
            {sidebarOpen && (
                <div
                    className="fixed inset-0 z-40 bg-black/50 lg:hidden"
                    onClick={() => setSidebarOpen(false)}
                />
            )}

            {/* Sidebar */}
            <aside
                className={cn(
                    'fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 transform transition-transform lg:translate-x-0',
                    sidebarOpen ? 'translate-x-0' : '-translate-x-full'
                )}
            >
                <div className="flex h-16 items-center justify-between px-6 border-b border-gray-200 dark:border-gray-700">
                    <Link href="/admin/dashboard" className="text-xl font-bold text-gray-900 dark:text-white">
                        TaxLab Admin
                    </Link>
                    <Button
                        variant="ghost"
                        size="icon"
                        className="lg:hidden"
                        onClick={() => setSidebarOpen(false)}
                    >
                        <X className="h-5 w-5" />
                    </Button>
                </div>

                <nav className="flex-1 space-y-1 px-3 py-4">
                    {navigation.map((item) => {
                        const isActive = window.location.pathname.startsWith(item.href);
                        return (
                            <Link
                                key={item.name}
                                href={item.href}
                                className={cn(
                                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                                    isActive
                                        ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white'
                                        : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50'
                                )}
                            >
                                <item.icon className="h-5 w-5" />
                                {item.name}
                            </Link>
                        );
                    })}
                </nav>
            </aside>

            {/* Main content */}
            <div className="lg:pl-64">
                {/* Top header */}
                <header className="sticky top-0 z-30 h-16 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <div className="flex h-full items-center justify-between px-4 sm:px-6">
                        <Button
                            variant="ghost"
                            size="icon"
                            className="lg:hidden"
                            onClick={() => setSidebarOpen(true)}
                        >
                            <Menu className="h-5 w-5" />
                        </Button>

                        <div className="flex-1" />

                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" className="flex items-center gap-2">
                                    <Avatar className="h-8 w-8">
                                        <AvatarFallback>
                                            {auth.admin.name.charAt(0).toUpperCase()}
                                        </AvatarFallback>
                                    </Avatar>
                                    <span className="hidden sm:inline-block text-sm font-medium">
                                        {auth.admin.name}
                                    </span>
                                    <ChevronDown className="h-4 w-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-56">
                                <div className="px-2 py-1.5">
                                    <p className="text-sm font-medium">{auth.admin.name}</p>
                                    <p className="text-xs text-gray-500">{auth.admin.email}</p>
                                </div>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem asChild>
                                    <Link href="/admin/settings">
                                        <Settings className="mr-2 h-4 w-4" />
                                        Settings
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem asChild>
                                    <Link href="/admin/logout" method="post" as="button" className="w-full">
                                        <LogOut className="mr-2 h-4 w-4" />
                                        Log out
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </header>

                {/* Page content */}
                <main className="p-4 sm:p-6 lg:p-8">
                    {children}
                </main>
            </div>
        </div>
    );
}
```

---

## TypeScript Types

### types/admin.ts

```tsx
export interface AdminUser {
    id: string;
    name: string;
    email: string;
    role: 'super_admin' | 'admin' | 'support' | 'content_manager';
    two_factor_enabled: boolean;
    created_at: string;
}

export interface Tenant {
    id: string;
    name: string;
    slug: string;
    email: string;
    phone: string | null;
    status: 'trial' | 'active' | 'suspended' | 'churned';
    plan: Plan;
    subscription_status: 'trialing' | 'active' | 'past_due' | 'cancelled';
    trial_ends_at: string | null;
    users_count: number;
    clients_count: number;
    created_at: string;
    last_activity_at: string | null;
}

export interface TenantDetails extends Tenant {
    address: string | null;
    city: string | null;
    state: string | null;
    country: string;
    postal_code: string | null;
    tax_number: string | null;
    mrr: number;
    storage_used_mb: number;
    settings: Record<string, unknown>;
}

export interface Plan {
    id: string;
    name: string;
    slug: string;
    description: string | null;
    price_monthly: number;
    price_yearly: number;
    is_active: boolean;
    is_featured: boolean;
    sort_order: number;
    limits: PlanLimit[];
    features: PlanFeature[];
}

export interface ImpersonationData {
    session_id: string;
    admin_id: string;
    admin_name: string;
    user_id: string;
    user_name: string;
    firm_id: string;
    firm_name: string;
    started_at: string;
    expires_at: string;
}

export interface PlanLimit {
    id: string;
    key: string;
    value: number;
    description: string | null;
}

export interface PlanFeature {
    id: string;
    key: string;
    enabled: boolean;
    description: string | null;
}

export interface TenantNote {
    id: string;
    content: string;
    is_pinned: boolean;
    category: 'general' | 'support' | 'billing' | 'technical' | 'compliance' | null;
    admin_user: {
        id: string;
        name: string;
    };
    created_at: string;
}

export interface ActivityLog {
    id: string;
    action: string;
    resource_type: string;
    resource_id: string | null;
    metadata: Record<string, unknown>;
    created_at: string;
    admin_user?: {
        id: string;
        name: string;
    };
}

export interface DashboardMetrics {
    active_tenants: number;
    active_tenants_change: number;
    mrr: number;
    mrr_change: number;
    trial_conversions: number;
    trial_conversion_rate: number;
    churn_rate: number;
    churn_change: number;
}

export interface RevenueDataPoint {
    month: string;
    mrr: number;
    new_mrr: number;
    churned_mrr: number;
}

export interface FunnelData {
    trials: number;
    active: number;
    churned: number;
}

export interface AttentionItem {
    id: string;
    type: 'trial_expiring' | 'payment_failed' | 'high_usage' | 'inactive';
    tenant: {
        id: string;
        name: string;
    };
    message: string;
    priority: 'high' | 'medium' | 'low';
    created_at: string;
}

export interface PaginatedResponse<T> {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        path: string;
        per_page: number;
        to: number | null;
        total: number;
    };
}
```

---

## Dashboard Components

### pages/Admin/Dashboard/Index.tsx

```tsx
import AdminLayout from '@/layouts/AdminLayout';
import { Head } from '@inertiajs/react';
import { MetricsCards } from './components/MetricsCards';
import { RevenueChart } from './components/RevenueChart';
import { TenantFunnel } from './components/TenantFunnel';
import { ActivityFeed } from './components/ActivityFeed';
import { AttentionItems } from './components/AttentionItems';
import type {
    DashboardMetrics,
    RevenueDataPoint,
    FunnelData,
    ActivityLog,
    AttentionItem,
} from '@/types/admin';

interface Props {
    metrics: DashboardMetrics;
    revenueData: RevenueDataPoint[];
    funnelData: FunnelData;
    recentActivity: ActivityLog[];
    attentionItems: AttentionItem[];
}

export default function DashboardIndex({
    metrics,
    revenueData,
    funnelData,
    recentActivity,
    attentionItems,
}: Props) {
    return (
        <AdminLayout>
            <Head title="Dashboard" />

            <div className="space-y-6">
                <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                    Dashboard
                </h1>

                <MetricsCards metrics={metrics} />

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div className="lg:col-span-2">
                        <RevenueChart data={revenueData} />
                    </div>
                    <div>
                        <TenantFunnel data={funnelData} />
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <AttentionItems items={attentionItems} />
                    <ActivityFeed activities={recentActivity} />
                </div>
            </div>
        </AdminLayout>
    );
}
```

### MetricsCards.tsx

```tsx
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Building2, DollarSign, TrendingUp, TrendingDown, UserMinus } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { DashboardMetrics } from '@/types/admin';

interface Props {
    metrics: DashboardMetrics;
}

export function MetricsCards({ metrics }: Props) {
    const cards = [
        {
            title: 'Active Tenants',
            value: metrics.active_tenants.toLocaleString(),
            change: metrics.active_tenants_change,
            icon: Building2,
        },
        {
            title: 'Monthly Recurring Revenue',
            value: `$${metrics.mrr.toLocaleString()}`,
            change: metrics.mrr_change,
            icon: DollarSign,
        },
        {
            title: 'Trial Conversion Rate',
            value: `${metrics.trial_conversion_rate.toFixed(1)}%`,
            change: null,
            subtitle: `${metrics.trial_conversions} converted this month`,
            icon: TrendingUp,
        },
        {
            title: 'Churn Rate',
            value: `${metrics.churn_rate.toFixed(1)}%`,
            change: -metrics.churn_change,
            icon: UserMinus,
        },
    ];

    return (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {cards.map((card) => (
                <Card key={card.title}>
                    <CardHeader className="flex flex-row items-center justify-between pb-2">
                        <CardTitle className="text-sm font-medium text-gray-500 dark:text-gray-400">
                            {card.title}
                        </CardTitle>
                        <card.icon className="h-4 w-4 text-gray-400" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">{card.value}</div>
                        {card.change !== null && (
                            <p
                                className={cn(
                                    'text-xs mt-1',
                                    card.change >= 0 ? 'text-green-600' : 'text-red-600'
                                )}
                            >
                                {card.change >= 0 ? '+' : ''}
                                {card.change.toFixed(1)}% from last month
                            </p>
                        )}
                        {card.subtitle && (
                            <p className="text-xs text-gray-500 mt-1">{card.subtitle}</p>
                        )}
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}
```

### RevenueChart.tsx

```tsx
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    LineChart,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer,
    Legend,
} from 'recharts';
import type { RevenueDataPoint } from '@/types/admin';

interface Props {
    data: RevenueDataPoint[];
}

export function RevenueChart({ data }: Props) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Revenue Over Time</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="h-80">
                    <ResponsiveContainer width="100%" height="100%">
                        <LineChart data={data}>
                            <CartesianGrid strokeDasharray="3 3" />
                            <XAxis dataKey="month" />
                            <YAxis tickFormatter={(value) => `$${value.toLocaleString()}`} />
                            <Tooltip
                                formatter={(value: number) => [`$${value.toLocaleString()}`, '']}
                            />
                            <Legend />
                            <Line
                                type="monotone"
                                dataKey="mrr"
                                name="MRR"
                                stroke="#3b82f6"
                                strokeWidth={2}
                            />
                            <Line
                                type="monotone"
                                dataKey="new_mrr"
                                name="New MRR"
                                stroke="#22c55e"
                                strokeWidth={2}
                            />
                            <Line
                                type="monotone"
                                dataKey="churned_mrr"
                                name="Churned MRR"
                                stroke="#ef4444"
                                strokeWidth={2}
                            />
                        </LineChart>
                    </ResponsiveContainer>
                </div>
            </CardContent>
        </Card>
    );
}
```

### AttentionItems.tsx

```tsx
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Link } from '@inertiajs/react';
import { AlertTriangle, Clock, CreditCard, Activity } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { AttentionItem } from '@/types/admin';

interface Props {
    items: AttentionItem[];
}

const iconMap = {
    trial_expiring: Clock,
    payment_failed: CreditCard,
    high_usage: Activity,
    inactive: AlertTriangle,
};

const priorityColors = {
    high: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    medium: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    low: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
};

export function AttentionItems({ items }: Props) {
    if (items.length === 0) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle>Attention Required</CardTitle>
                </CardHeader>
                <CardContent>
                    <p className="text-gray-500 text-center py-8">
                        No items require attention
                    </p>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle>Attention Required</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="space-y-3">
                    {items.map((item) => {
                        const Icon = iconMap[item.type];
                        return (
                            <Link
                                key={item.id}
                                href={`/admin/tenants/${item.tenant.id}`}
                                className="flex items-start gap-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                            >
                                <div
                                    className={cn(
                                        'p-2 rounded-full',
                                        priorityColors[item.priority]
                                    )}
                                >
                                    <Icon className="h-4 w-4" />
                                </div>
                                <div className="flex-1 min-w-0">
                                    <p className="text-sm font-medium text-gray-900 dark:text-white">
                                        {item.tenant.name}
                                    </p>
                                    <p className="text-sm text-gray-500 truncate">
                                        {item.message}
                                    </p>
                                </div>
                            </Link>
                        );
                    })}
                </div>
            </CardContent>
        </Card>
    );
}
```

---

## Tenant Components

### pages/Admin/Tenants/Index.tsx

```tsx
import AdminLayout from '@/layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { TenantTable } from './components/TenantTable';
import { Download, Search } from 'lucide-react';
import type { Tenant, Plan, PaginatedResponse } from '@/types/admin';

interface Props {
    tenants: PaginatedResponse<Tenant>;
    plans: Plan[];
    filters: {
        search: string;
        status: string;
        plan: string;
        subscription: string;
    };
}

export default function TenantsIndex({ tenants, plans, filters }: Props) {
    const [search, setSearch] = useState(filters.search);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/admin/tenants', { ...filters, search }, { preserveState: true });
    };

    const handleFilterChange = (key: string, value: string) => {
        router.get(
            '/admin/tenants',
            { ...filters, [key]: value || undefined },
            { preserveState: true }
        );
    };

    return (
        <AdminLayout>
            <Head title="Tenants" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                        Tenants
                    </h1>
                    <Button variant="outline" asChild>
                        <Link href="/admin/tenants/export">
                            <Download className="mr-2 h-4 w-4" />
                            Export
                        </Link>
                    </Button>
                </div>

                {/* Filters */}
                <div className="flex flex-wrap gap-4">
                    <form onSubmit={handleSearch} className="flex-1 min-w-[200px]">
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <Input
                                placeholder="Search tenants..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="pl-10"
                            />
                        </div>
                    </form>

                    <Select
                        value={filters.status}
                        onValueChange={(value) => handleFilterChange('status', value)}
                    >
                        <SelectTrigger className="w-[150px]">
                            <SelectValue placeholder="Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Statuses</SelectItem>
                            <SelectItem value="active">Active</SelectItem>
                            <SelectItem value="suspended">Suspended</SelectItem>
                            <SelectItem value="cancelled">Cancelled</SelectItem>
                        </SelectContent>
                    </Select>

                    <Select
                        value={filters.plan}
                        onValueChange={(value) => handleFilterChange('plan', value)}
                    >
                        <SelectTrigger className="w-[150px]">
                            <SelectValue placeholder="Plan" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Plans</SelectItem>
                            {plans.map((plan) => (
                                <SelectItem key={plan.id} value={plan.id}>
                                    {plan.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <Select
                        value={filters.subscription}
                        onValueChange={(value) => handleFilterChange('subscription', value)}
                    >
                        <SelectTrigger className="w-[150px]">
                            <SelectValue placeholder="Subscription" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All</SelectItem>
                            <SelectItem value="trialing">Trialing</SelectItem>
                            <SelectItem value="active">Active</SelectItem>
                            <SelectItem value="past_due">Past Due</SelectItem>
                            <SelectItem value="cancelled">Cancelled</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <TenantTable tenants={tenants} />
            </div>
        </AdminLayout>
    );
}
```

### TenantTable.tsx

```tsx
import { Link } from '@inertiajs/react';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Pagination } from '@/components/ui/pagination';
import { formatDistanceToNow } from 'date-fns';
import { Eye, MoreHorizontal } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { Tenant, PaginatedResponse } from '@/types/admin';

interface Props {
    tenants: PaginatedResponse<Tenant>;
}

const statusColors = {
    active: 'bg-green-100 text-green-800',
    suspended: 'bg-red-100 text-red-800',
    cancelled: 'bg-gray-100 text-gray-800',
};

const subscriptionColors = {
    trialing: 'bg-blue-100 text-blue-800',
    active: 'bg-green-100 text-green-800',
    past_due: 'bg-yellow-100 text-yellow-800',
    cancelled: 'bg-gray-100 text-gray-800',
};

export function TenantTable({ tenants }: Props) {
    return (
        <div className="space-y-4">
            <div className="rounded-lg border bg-white dark:bg-gray-800">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Plan</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Subscription</TableHead>
                            <TableHead>Users</TableHead>
                            <TableHead>Last Activity</TableHead>
                            <TableHead className="w-[50px]"></TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {tenants.data.map((tenant) => (
                            <TableRow key={tenant.id}>
                                <TableCell>
                                    <Link
                                        href={`/admin/tenants/${tenant.id}`}
                                        className="font-medium hover:underline"
                                    >
                                        {tenant.name}
                                    </Link>
                                    <p className="text-sm text-gray-500">{tenant.email}</p>
                                </TableCell>
                                <TableCell>{tenant.plan.name}</TableCell>
                                <TableCell>
                                    <Badge className={statusColors[tenant.status]}>
                                        {tenant.status}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <Badge className={subscriptionColors[tenant.subscription_status]}>
                                        {tenant.subscription_status}
                                    </Badge>
                                </TableCell>
                                <TableCell>{tenant.users_count}</TableCell>
                                <TableCell>
                                    {tenant.last_activity_at
                                        ? formatDistanceToNow(new Date(tenant.last_activity_at), {
                                              addSuffix: true,
                                          })
                                        : 'Never'}
                                </TableCell>
                                <TableCell>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button variant="ghost" size="icon">
                                                <MoreHorizontal className="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem asChild>
                                                <Link href={`/admin/tenants/${tenant.id}`}>
                                                    <Eye className="mr-2 h-4 w-4" />
                                                    View Details
                                                </Link>
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            <Pagination meta={tenants.meta} links={tenants.links} />
        </div>
    );
}
```

### pages/Admin/Tenants/Show.tsx

```tsx
import AdminLayout from '@/layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Button } from '@/components/ui/button';
import { TenantOverview } from './components/TenantOverview';
import { TenantSubscription } from './components/TenantSubscription';
import { TenantUsage } from './components/TenantUsage';
import { TenantUsers } from './components/TenantUsers';
import { TenantActivity } from './components/TenantActivity';
import { TenantNotes } from './components/TenantNotes';
import { TenantActions } from './components/TenantActions';
import { ArrowLeft } from 'lucide-react';
import type { TenantDetails, TenantNote, ActivityLog, Plan } from '@/types/admin';

interface Props {
    tenant: TenantDetails;
    notes: TenantNote[];
    activities: ActivityLog[];
    plans: Plan[];
    usage: {
        users: { current: number; limit: number };
        clients: { current: number; limit: number };
        storage: { current: number; limit: number };
    };
}

export default function TenantShow({ tenant, notes, activities, plans, usage }: Props) {
    return (
        <AdminLayout>
            <Head title={`Tenant: ${tenant.name}`} />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href="/admin/tenants">
                                <ArrowLeft className="h-4 w-4" />
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                                {tenant.name}
                            </h1>
                            <p className="text-sm text-gray-500">{tenant.email}</p>
                        </div>
                    </div>
                    <TenantActions tenant={tenant} plans={plans} />
                </div>

                <Tabs defaultValue="overview">
                    <TabsList>
                        <TabsTrigger value="overview">Overview</TabsTrigger>
                        <TabsTrigger value="subscription">Subscription</TabsTrigger>
                        <TabsTrigger value="usage">Usage</TabsTrigger>
                        <TabsTrigger value="users">Users</TabsTrigger>
                        <TabsTrigger value="activity">Activity</TabsTrigger>
                        <TabsTrigger value="notes">Notes</TabsTrigger>
                    </TabsList>

                    <TabsContent value="overview" className="mt-6">
                        <TenantOverview tenant={tenant} />
                    </TabsContent>

                    <TabsContent value="subscription" className="mt-6">
                        <TenantSubscription tenant={tenant} plans={plans} />
                    </TabsContent>

                    <TabsContent value="usage" className="mt-6">
                        <TenantUsage usage={usage} />
                    </TabsContent>

                    <TabsContent value="users" className="mt-6">
                        <TenantUsers tenantId={tenant.id} />
                    </TabsContent>

                    <TabsContent value="activity" className="mt-6">
                        <TenantActivity activities={activities} />
                    </TabsContent>

                    <TabsContent value="notes" className="mt-6">
                        <TenantNotes tenantId={tenant.id} notes={notes} />
                    </TabsContent>
                </Tabs>
            </div>
        </AdminLayout>
    );
}
```

---

## Plan Components

### pages/Admin/Plans/Index.tsx

```tsx
import AdminLayout from '@/layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { PlanTable } from './components/PlanTable';
import { Plus } from 'lucide-react';
import type { Plan } from '@/types/admin';

interface Props {
    plans: Plan[];
}

export default function PlansIndex({ plans }: Props) {
    return (
        <AdminLayout>
            <Head title="Plans" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                        Subscription Plans
                    </h1>
                    <Button asChild>
                        <Link href="/admin/plans/create">
                            <Plus className="mr-2 h-4 w-4" />
                            Create Plan
                        </Link>
                    </Button>
                </div>

                <PlanTable plans={plans} />
            </div>
        </AdminLayout>
    );
}
```

### PlanForm.tsx

```tsx
import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { PlanLimitsForm } from './PlanLimitsForm';
import { PlanFeaturesForm } from './PlanFeaturesForm';
import type { Plan, PlanLimit, PlanFeature } from '@/types/admin';

interface Props {
    plan?: Plan;
}

interface FormData {
    name: string;
    slug: string;
    description: string;
    price_monthly: number;
    price_yearly: number;
    is_active: boolean;
    is_featured: boolean;
    limits: Partial<PlanLimit>[];
    features: Partial<PlanFeature>[];
}

export function PlanForm({ plan }: Props) {
    const { data, setData, post, put, processing, errors } = useForm<FormData>({
        name: plan?.name ?? '',
        slug: plan?.slug ?? '',
        description: plan?.description ?? '',
        price_monthly: plan?.price_monthly ?? 0,
        price_yearly: plan?.price_yearly ?? 0,
        is_active: plan?.is_active ?? true,
        is_featured: plan?.is_featured ?? false,
        limits: plan?.limits ?? [],
        features: plan?.features ?? [],
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (plan) {
            put(`/admin/plans/${plan.id}`);
        } else {
            post('/admin/plans');
        }
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Plan Details</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                            />
                            {errors.name && (
                                <p className="text-sm text-red-500">{errors.name}</p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="slug">Slug</Label>
                            <Input
                                id="slug"
                                value={data.slug}
                                onChange={(e) => setData('slug', e.target.value)}
                            />
                            {errors.slug && (
                                <p className="text-sm text-red-500">{errors.slug}</p>
                            )}
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="description">Description</Label>
                        <Textarea
                            id="description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                        />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="price_monthly">Monthly Price ($)</Label>
                            <Input
                                id="price_monthly"
                                type="number"
                                step="0.01"
                                value={data.price_monthly}
                                onChange={(e) =>
                                    setData('price_monthly', parseFloat(e.target.value))
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="price_yearly">Yearly Price ($)</Label>
                            <Input
                                id="price_yearly"
                                type="number"
                                step="0.01"
                                value={data.price_yearly}
                                onChange={(e) =>
                                    setData('price_yearly', parseFloat(e.target.value))
                                }
                            />
                        </div>
                    </div>

                    <div className="flex gap-6">
                        <div className="flex items-center gap-2">
                            <Switch
                                id="is_active"
                                checked={data.is_active}
                                onCheckedChange={(checked) => setData('is_active', checked)}
                            />
                            <Label htmlFor="is_active">Active</Label>
                        </div>
                        <div className="flex items-center gap-2">
                            <Switch
                                id="is_public"
                                checked={data.is_public}
                                onCheckedChange={(checked) => setData('is_public', checked)}
                            />
                            <Label htmlFor="is_public">Publicly Visible</Label>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <PlanLimitsForm
                limits={data.limits}
                onChange={(limits) => setData('limits', limits)}
            />

            <PlanFeaturesForm
                features={data.features}
                onChange={(features) => setData('features', features)}
            />

            <div className="flex justify-end gap-4">
                <Button type="button" variant="outline" asChild>
                    <a href="/admin/plans">Cancel</a>
                </Button>
                <Button type="submit" disabled={processing}>
                    {plan ? 'Update Plan' : 'Create Plan'}
                </Button>
            </div>
        </form>
    );
}
```

---

## Shared Components

### Pagination.tsx

```tsx
import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { ChevronLeft, ChevronRight } from 'lucide-react';

interface PaginationMeta {
    current_page: number;
    from: number | null;
    last_page: number;
    per_page: number;
    to: number | null;
    total: number;
}

interface PaginationLinks {
    prev: string | null;
    next: string | null;
}

interface Props {
    meta: PaginationMeta;
    links: PaginationLinks;
}

export function Pagination({ meta, links }: Props) {
    if (meta.last_page <= 1) return null;

    return (
        <div className="flex items-center justify-between">
            <p className="text-sm text-gray-500">
                Showing {meta.from} to {meta.to} of {meta.total} results
            </p>
            <div className="flex gap-2">
                <Button variant="outline" size="sm" disabled={!links.prev} asChild={!!links.prev}>
                    {links.prev ? (
                        <Link href={links.prev}>
                            <ChevronLeft className="h-4 w-4 mr-1" />
                            Previous
                        </Link>
                    ) : (
                        <>
                            <ChevronLeft className="h-4 w-4 mr-1" />
                            Previous
                        </>
                    )}
                </Button>
                <Button variant="outline" size="sm" disabled={!links.next} asChild={!!links.next}>
                    {links.next ? (
                        <Link href={links.next}>
                            Next
                            <ChevronRight className="h-4 w-4 ml-1" />
                        </Link>
                    ) : (
                        <>
                            Next
                            <ChevronRight className="h-4 w-4 ml-1" />
                        </>
                    )}
                </Button>
            </div>
        </div>
    );
}
```

### ConfirmDialog.tsx

```tsx
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description: string;
    confirmText?: string;
    cancelText?: string;
    variant?: 'default' | 'destructive';
    onConfirm: () => void;
}

export function ConfirmDialog({
    open,
    onOpenChange,
    title,
    description,
    confirmText = 'Confirm',
    cancelText = 'Cancel',
    variant = 'default',
    onConfirm,
}: Props) {
    return (
        <AlertDialog open={open} onOpenChange={onOpenChange}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{title}</AlertDialogTitle>
                    <AlertDialogDescription>{description}</AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>{cancelText}</AlertDialogCancel>
                    <AlertDialogAction
                        onClick={onConfirm}
                        className={
                            variant === 'destructive'
                                ? 'bg-red-600 hover:bg-red-700'
                                : undefined
                        }
                    >
                        {confirmText}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
```

### ImpersonationBanner.tsx

This component displays a clear visual indicator when an admin is impersonating a firm user, with an always-visible exit button.

```tsx
import { router, usePage } from '@inertiajs/react';
import { AlertTriangle, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { formatDistanceToNow } from 'date-fns';
import type { ImpersonationData } from '@/types/admin';

interface PageProps {
    impersonation?: ImpersonationData | null;
}

export function ImpersonationBanner() {
    const { impersonation } = usePage<PageProps>().props;

    if (!impersonation) {
        return null;
    }

    const handleStopImpersonation = () => {
        router.post('/impersonation/stop');
    };

    const timeRemaining = formatDistanceToNow(new Date(impersonation.expires_at), {
        addSuffix: false,
    });

    return (
        <div className="fixed top-0 left-0 right-0 z-[100] bg-amber-500 text-black">
            <div className="max-w-7xl mx-auto px-4 py-2 flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <AlertTriangle className="h-5 w-5" />
                    <span className="font-medium">
                        Impersonating {impersonation.user_name} at {impersonation.firm_name}
                    </span>
                    <span className="text-sm opacity-75">
                        (Session expires in {timeRemaining})
                    </span>
                </div>
                <Button
                    variant="secondary"
                    size="sm"
                    onClick={handleStopImpersonation}
                    className="bg-white hover:bg-gray-100"
                >
                    <X className="h-4 w-4 mr-1" />
                    Exit Impersonation
                </Button>
            </div>
        </div>
    );
}
```

Usage in FirmLayout (the layout used when impersonating):

```tsx
import { ImpersonationBanner } from '@/components/ImpersonationBanner';

export default function FirmLayout({ children }: PropsWithChildren) {
    return (
        <>
            <ImpersonationBanner />
            <div className="pt-[var(--impersonation-banner-height, 0)]">
                {/* Rest of layout */}
            </div>
        </>
    );
}
```

Add CSS variable in your global styles when impersonation is active:

```css
:root {
    --impersonation-banner-height: 0;
}

body.impersonating {
    --impersonation-banner-height: 48px;
}
```

---

## File Structure Summary

```
resources/js/
├── layouts/
│   └── AdminLayout.tsx
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
│   │       ├── TenantNotes.tsx
│   │       └── TenantActions.tsx
│   ├── Plans/
│   │   ├── Index.tsx
│   │   ├── Create.tsx
│   │   ├── Show.tsx
│   │   ├── Edit.tsx
│   │   └── components/
│   │       ├── PlanTable.tsx
│   │       ├── PlanForm.tsx
│   │       ├── PlanLimitsForm.tsx
│   │       └── PlanFeaturesForm.tsx
│   └── Settings/
│       └── Index.tsx
├── components/
│   └── admin/
│       ├── Pagination.tsx
│       └── ConfirmDialog.tsx
└── types/
    └── admin.ts
```
