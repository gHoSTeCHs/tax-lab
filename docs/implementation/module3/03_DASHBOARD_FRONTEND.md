# TaxLab Implementation - Dashboard Frontend

## Overview

This document covers the frontend React components for the Firm Dashboard, including TypeScript types, page components, and reusable widgets.

Reference: `docs/05_FIRM_DASHBOARD.md`

---

## TypeScript Types

**File:** `resources/js/types/dashboard.ts`

```typescript
export type FirmRole = 'partner' | 'manager' | 'associate' | 'viewer';
export type Priority = 'high' | 'medium' | 'low';
export type MetricVariant = 'default' | 'success' | 'warning' | 'destructive';

export interface DashboardMetric {
    key: string;
    label: string;
    value: number | string;
    icon: string;
    href?: string;
    variant?: MetricVariant;
    trend?: {
        value: number;
        direction: 'up' | 'down' | 'neutral';
    };
}

export interface AttentionItem {
    id: string;
    type: string;
    priority: Priority;
    message: string;
    actionUrl: string;
    actionLabel: string;
    createdAt?: string;
}

export interface ActivityItem {
    id: string;
    userId: string;
    userName: string;
    userAvatar?: string;
    action: string;
    actionLabel: string;
    description: string;
    targetType: string;
    targetName: string;
    targetUrl?: string;
    createdAt: string;
    createdAtHuman: string;
}

export interface RecentClient {
    id: string;
    name: string;
    entityType: string;
    entityTypeLabel: string;
    entityTypeIcon: string;
    industry?: string;
    lastAccessedAt?: string;
    url: string;
}

export interface QuickLink {
    title: string;
    href: string;
    icon: string;
    description?: string;
    badge?: number;
}

export type EmptyStateType = 'new_firm' | 'new_user' | null;

export interface DashboardPageProps {
    firmRole: FirmRole;
    metrics: DashboardMetric[];
    attentionItems: AttentionItem[];
    quickLinks: QuickLink[];
    emptyState: EmptyStateType;
}

export interface PaginatedActivity {
    data: ActivityItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}
```

Update `resources/js/types/index.d.ts` to export:

```typescript
export * from './dashboard';
```

---

## Dashboard Page

**File:** `resources/js/pages/App/Dashboard/Index.tsx`

```tsx
import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { WelcomeSection } from './components/WelcomeSection';
import { MetricsGrid } from './components/MetricsGrid';
import { AttentionSection } from './components/AttentionSection';
import { ActivityFeed } from './components/ActivityFeed';
import { RecentClientsWidget } from './components/RecentClientsWidget';
import { QuickLinksWidget } from './components/QuickLinksWidget';
import { EmptyState } from './components/EmptyState';
import { FirmHealthWidget } from './components/FirmHealthWidget';
import { TeamOverviewWidget } from './components/TeamOverviewWidget';
import { TeamWorkloadWidget } from './components/TeamWorkloadWidget';
import { MyPerformanceWidget } from './components/MyPerformanceWidget';
import type { DashboardPageProps, SharedData } from '@/types';

export default function Dashboard({
    firmRole,
    metrics,
    attentionItems,
    quickLinks,
    emptyState,
}: DashboardPageProps) {
    const { auth } = usePage<SharedData>().props;

    if (emptyState) {
        return (
            <AppLayout>
                <Head title="Dashboard" />
                <EmptyState type={emptyState} userName={auth.user.name} />
            </AppLayout>
        );
    }

    return (
        <AppLayout>
            <Head title="Dashboard" />

            <div className="space-y-6">
                <WelcomeSection userName={auth.user.name} firmRole={firmRole} />

                {attentionItems.length > 0 && (
                    <AttentionSection items={attentionItems} />
                )}

                <MetricsGrid metrics={metrics} />

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div className="lg:col-span-2 space-y-6">
                        <ActivityFeed />

                        {firmRole === 'partner' && <FirmHealthWidget />}
                        {firmRole === 'manager' && <TeamWorkloadWidget />}
                        {firmRole === 'associate' && <MyPerformanceWidget />}
                    </div>

                    <div className="space-y-6">
                        <RecentClientsWidget />
                        <QuickLinksWidget links={quickLinks} />

                        {firmRole === 'partner' && <TeamOverviewWidget />}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
```

---

## Welcome Section Component

**File:** `resources/js/pages/App/Dashboard/components/WelcomeSection.tsx`

```tsx
import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Plus, Calculator } from 'lucide-react';
import { useGreeting } from '@/hooks/use-greeting';
import type { FirmRole } from '@/types';

interface WelcomeSectionProps {
    userName: string;
    firmRole: FirmRole;
}

export function WelcomeSection({ userName, firmRole }: WelcomeSectionProps) {
    const greeting = useGreeting();
    const firstName = userName.split(' ')[0];

    const canCreateClient = firmRole !== 'viewer';

    return (
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 className="text-2xl font-semibold text-foreground">
                    {greeting}, {firstName}
                </h1>
                <p className="text-sm text-muted-foreground">
                    {new Date().toLocaleDateString('en-NG', {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                    })}
                </p>
            </div>

            <div className="flex flex-wrap gap-2">
                {canCreateClient && (
                    <Button asChild>
                        <Link href="/app/clients/create">
                            <Plus className="mr-2 h-4 w-4" />
                            New Client
                        </Link>
                    </Button>
                )}
                <Button variant="outline" asChild>
                    <Link href="/app/calculations/new">
                        <Calculator className="mr-2 h-4 w-4" />
                        Run Calculation
                    </Link>
                </Button>
            </div>
        </div>
    );
}
```

---

## Metrics Grid Component

**File:** `resources/js/pages/App/Dashboard/components/MetricsGrid.tsx`

```tsx
import { MetricCard } from './MetricCard';
import type { DashboardMetric } from '@/types';

interface MetricsGridProps {
    metrics: DashboardMetric[];
}

export function MetricsGrid({ metrics }: MetricsGridProps) {
    return (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {metrics.map((metric) => (
                <MetricCard key={metric.key} metric={metric} />
            ))}
        </div>
    );
}
```

---

## Metric Card Component

**File:** `resources/js/pages/App/Dashboard/components/MetricCard.tsx`

```tsx
import { Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Users,
    FileText,
    Clock,
    Activity,
    Calculator,
    TrendingUp,
    TrendingDown,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import type { DashboardMetric } from '@/types';

interface MetricCardProps {
    metric: DashboardMetric;
}

const iconMap: Record<string, React.ComponentType<{ className?: string }>> = {
    users: Users,
    'file-text': FileText,
    clock: Clock,
    activity: Activity,
    calculator: Calculator,
};

export function MetricCard({ metric }: MetricCardProps) {
    const Icon = iconMap[metric.icon] || Activity;

    const content = (
        <Card
            className={cn(
                'transition-colors',
                metric.href && 'hover:bg-muted/50 cursor-pointer',
                metric.variant === 'warning' && 'border-yellow-500/50',
                metric.variant === 'destructive' && 'border-destructive/50'
            )}
        >
            <CardHeader className="flex flex-row items-center justify-between pb-2">
                <CardTitle className="text-sm font-medium text-muted-foreground">
                    {metric.label}
                </CardTitle>
                <Icon className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div className="flex items-baseline gap-2">
                    <span className="text-2xl font-bold">{metric.value}</span>
                    {metric.trend && (
                        <span
                            className={cn(
                                'flex items-center text-xs font-medium',
                                metric.trend.direction === 'up' && 'text-green-600 dark:text-green-400',
                                metric.trend.direction === 'down' && 'text-red-600 dark:text-red-400',
                                metric.trend.direction === 'neutral' && 'text-muted-foreground'
                            )}
                        >
                            {metric.trend.direction === 'up' && (
                                <TrendingUp className="h-3 w-3 mr-0.5" />
                            )}
                            {metric.trend.direction === 'down' && (
                                <TrendingDown className="h-3 w-3 mr-0.5" />
                            )}
                            {metric.trend.value}%
                        </span>
                    )}
                </div>
            </CardContent>
        </Card>
    );

    if (metric.href) {
        return <Link href={metric.href}>{content}</Link>;
    }

    return content;
}
```

---

## Attention Section Component

**File:** `resources/js/pages/App/Dashboard/components/AttentionSection.tsx`

```tsx
import { AttentionItem } from './AttentionItem';
import type { AttentionItem as AttentionItemType } from '@/types';

interface AttentionSectionProps {
    items: AttentionItemType[];
}

export function AttentionSection({ items }: AttentionSectionProps) {
    return (
        <div className="space-y-3">
            <h2 className="text-lg font-semibold">Attention Required</h2>
            <div className="space-y-2">
                {items.map((item) => (
                    <AttentionItem key={item.id} item={item} />
                ))}
            </div>
        </div>
    );
}
```

---

## Attention Item Component

**File:** `resources/js/pages/App/Dashboard/components/AttentionItem.tsx`

```tsx
import { Link } from '@inertiajs/react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { AlertCircle, AlertTriangle, Info, ChevronRight } from 'lucide-react';
import type { AttentionItem as AttentionItemType } from '@/types';

interface AttentionItemProps {
    item: AttentionItemType;
}

const iconMap = {
    high: AlertCircle,
    medium: AlertTriangle,
    low: Info,
};

const variantMap = {
    high: 'destructive' as const,
    medium: 'default' as const,
    low: 'default' as const,
};

export function AttentionItem({ item }: AttentionItemProps) {
    const Icon = iconMap[item.priority];

    return (
        <Alert variant={variantMap[item.priority]}>
            <Icon className="h-4 w-4" />
            <AlertTitle className="flex items-center justify-between">
                <span>{item.message}</span>
                <Link
                    href={item.actionUrl}
                    className="text-sm font-normal flex items-center gap-1 hover:underline"
                >
                    {item.actionLabel}
                    <ChevronRight className="h-3 w-3" />
                </Link>
            </AlertTitle>
        </Alert>
    );
}
```

---

## Activity Feed Component

**File:** `resources/js/pages/App/Dashboard/components/ActivityFeed.tsx`

```tsx
import { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { ActivityItem } from './ActivityItem';
import { useActivityFeed } from '@/hooks/use-activity-feed';

export function ActivityFeed() {
    const { activities, isLoading, hasMore, loadMore, isLoadingMore } = useActivityFeed();

    return (
        <Card>
            <CardHeader>
                <CardTitle>Recent Activity</CardTitle>
            </CardHeader>
            <CardContent>
                {isLoading ? (
                    <div className="space-y-4">
                        {[...Array(5)].map((_, i) => (
                            <div key={i} className="flex items-start gap-3">
                                <Skeleton className="h-8 w-8 rounded-full" />
                                <div className="flex-1 space-y-2">
                                    <Skeleton className="h-4 w-3/4" />
                                    <Skeleton className="h-3 w-1/4" />
                                </div>
                            </div>
                        ))}
                    </div>
                ) : activities.length === 0 ? (
                    <p className="text-sm text-muted-foreground text-center py-8">
                        No recent activity to display
                    </p>
                ) : (
                    <>
                        <div className="space-y-4">
                            {activities.map((activity) => (
                                <ActivityItem key={activity.id} activity={activity} />
                            ))}
                        </div>
                        {hasMore && (
                            <div className="mt-4 text-center">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={loadMore}
                                    disabled={isLoadingMore}
                                >
                                    {isLoadingMore ? 'Loading...' : 'Load more'}
                                </Button>
                            </div>
                        )}
                    </>
                )}
            </CardContent>
        </Card>
    );
}
```

---

## Activity Item Component

**File:** `resources/js/pages/App/Dashboard/components/ActivityItem.tsx`

```tsx
import { Link } from '@inertiajs/react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import type { ActivityItem as ActivityItemType } from '@/types';

interface ActivityItemProps {
    activity: ActivityItemType;
}

export function ActivityItem({ activity }: ActivityItemProps) {
    const initials = useInitials(activity.userName);

    return (
        <div className="flex items-start gap-3">
            <Avatar className="h-8 w-8">
                {activity.userAvatar && <AvatarImage src={activity.userAvatar} />}
                <AvatarFallback className="text-xs">{initials}</AvatarFallback>
            </Avatar>
            <div className="flex-1 min-w-0">
                <p className="text-sm">
                    <span className="font-medium">{activity.userName}</span>{' '}
                    <span className="text-muted-foreground">{activity.actionLabel}</span>{' '}
                    {activity.targetUrl ? (
                        <Link
                            href={activity.targetUrl}
                            className="font-medium hover:underline"
                        >
                            {activity.targetName}
                        </Link>
                    ) : (
                        <span className="font-medium">{activity.targetName}</span>
                    )}
                </p>
                <p className="text-xs text-muted-foreground">
                    {activity.createdAtHuman}
                </p>
            </div>
        </div>
    );
}
```

---

## Recent Clients Widget

**File:** `resources/js/pages/App/Dashboard/components/RecentClientsWidget.tsx`

```tsx
import { useState, useEffect } from 'react';
import { Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { Building2, User, Users, Briefcase, Landmark, ChevronRight } from 'lucide-react';
import type { RecentClient } from '@/types';

const iconMap: Record<string, React.ComponentType<{ className?: string }>> = {
    'building-2': Building2,
    user: User,
    users: Users,
    briefcase: Briefcase,
    landmark: Landmark,
};

export function RecentClientsWidget() {
    const [clients, setClients] = useState<RecentClient[]>([]);
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        fetch('/app/dashboard/recent-clients')
            .then((res) => res.json())
            .then((data) => {
                setClients(data.clients);
                setIsLoading(false);
            })
            .catch(() => setIsLoading(false));
    }, []);

    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between">
                <CardTitle>Recent Clients</CardTitle>
                <Button variant="ghost" size="sm" asChild>
                    <Link href="/app/clients">
                        View all
                        <ChevronRight className="ml-1 h-4 w-4" />
                    </Link>
                </Button>
            </CardHeader>
            <CardContent>
                {isLoading ? (
                    <div className="space-y-3">
                        {[...Array(5)].map((_, i) => (
                            <div key={i} className="flex items-center gap-3">
                                <Skeleton className="h-9 w-9 rounded" />
                                <div className="flex-1 space-y-1.5">
                                    <Skeleton className="h-4 w-3/4" />
                                    <Skeleton className="h-3 w-1/2" />
                                </div>
                            </div>
                        ))}
                    </div>
                ) : clients.length === 0 ? (
                    <p className="text-sm text-muted-foreground text-center py-4">
                        No recent clients
                    </p>
                ) : (
                    <div className="space-y-3">
                        {clients.map((client) => {
                            const Icon = iconMap[client.entityTypeIcon] || Building2;
                            return (
                                <Link
                                    key={client.id}
                                    href={client.url}
                                    className="flex items-center gap-3 p-2 -mx-2 rounded-md hover:bg-muted/50 transition-colors"
                                >
                                    <div className="flex h-9 w-9 items-center justify-center rounded bg-muted">
                                        <Icon className="h-4 w-4 text-muted-foreground" />
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <p className="text-sm font-medium truncate">
                                            {client.name}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {client.lastAccessedAt || client.entityTypeLabel}
                                        </p>
                                    </div>
                                </Link>
                            );
                        })}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
```

---

## Quick Links Widget

**File:** `resources/js/pages/App/Dashboard/components/QuickLinksWidget.tsx`

```tsx
import { Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
    BookOpen,
    HelpCircle,
    Clock,
    CreditCard,
    Settings,
    GraduationCap,
} from 'lucide-react';
import type { QuickLink } from '@/types';

interface QuickLinksWidgetProps {
    links: QuickLink[];
}

const iconMap: Record<string, React.ComponentType<{ className?: string }>> = {
    'book-open': BookOpen,
    'help-circle': HelpCircle,
    clock: Clock,
    'credit-card': CreditCard,
    settings: Settings,
    'graduation-cap': GraduationCap,
};

export function QuickLinksWidget({ links }: QuickLinksWidgetProps) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Quick Links</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="space-y-2">
                    {links.map((link) => {
                        const Icon = iconMap[link.icon] || BookOpen;
                        return (
                            <Link
                                key={link.href}
                                href={link.href}
                                className="flex items-center gap-3 p-2 -mx-2 rounded-md hover:bg-muted/50 transition-colors"
                            >
                                <Icon className="h-4 w-4 text-muted-foreground" />
                                <div className="flex-1 min-w-0">
                                    <div className="flex items-center gap-2">
                                        <span className="text-sm font-medium">
                                            {link.title}
                                        </span>
                                        {link.badge && (
                                            <Badge variant="secondary" className="text-xs">
                                                {link.badge}
                                            </Badge>
                                        )}
                                    </div>
                                    {link.description && (
                                        <p className="text-xs text-muted-foreground">
                                            {link.description}
                                        </p>
                                    )}
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

## Empty State Component

**File:** `resources/js/pages/App/Dashboard/components/EmptyState.tsx`

```tsx
import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Plus, Calculator, BookOpen, Settings } from 'lucide-react';
import type { EmptyStateType } from '@/types';

interface EmptyStateProps {
    type: EmptyStateType;
    userName: string;
}

export function EmptyState({ type, userName }: EmptyStateProps) {
    const firstName = userName.split(' ')[0];

    if (type === 'new_firm') {
        return (
            <div className="max-w-2xl mx-auto py-12">
                <div className="text-center mb-8">
                    <h1 className="text-3xl font-bold mb-2">
                        Welcome to TaxLab, {firstName}!
                    </h1>
                    <p className="text-lg text-muted-foreground">
                        Let's get your practice set up. Here's how to get started:
                    </p>
                </div>

                <div className="grid gap-4">
                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-start gap-4">
                                <div className="flex h-10 w-10 items-center justify-center rounded-full bg-primary text-primary-foreground font-semibold">
                                    1
                                </div>
                                <div className="flex-1">
                                    <h3 className="font-semibold mb-1">Add your first client</h3>
                                    <p className="text-sm text-muted-foreground mb-3">
                                        Create a client profile to start managing their tax affairs.
                                    </p>
                                    <Button asChild>
                                        <Link href="/app/clients/create">
                                            <Plus className="mr-2 h-4 w-4" />
                                            Add Client
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-start gap-4">
                                <div className="flex h-10 w-10 items-center justify-center rounded-full bg-muted text-muted-foreground font-semibold">
                                    2
                                </div>
                                <div className="flex-1">
                                    <h3 className="font-semibold mb-1">Explore training courses</h3>
                                    <p className="text-sm text-muted-foreground mb-3">
                                        Learn how to make the most of TaxLab with our CPD courses.
                                    </p>
                                    <Button variant="outline" asChild>
                                        <Link href="/app/training">
                                            <BookOpen className="mr-2 h-4 w-4" />
                                            Browse Courses
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-start gap-4">
                                <div className="flex h-10 w-10 items-center justify-center rounded-full bg-muted text-muted-foreground font-semibold">
                                    3
                                </div>
                                <div className="flex-1">
                                    <h3 className="font-semibold mb-1">Set up your branding</h3>
                                    <p className="text-sm text-muted-foreground mb-3">
                                        Customize reports with your firm's logo and colors.
                                    </p>
                                    <Button variant="outline" asChild>
                                        <Link href="/app/settings/branding">
                                            <Settings className="mr-2 h-4 w-4" />
                                            Customize
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        );
    }

    return (
        <div className="max-w-2xl mx-auto py-12">
            <div className="text-center mb-8">
                <h1 className="text-3xl font-bold mb-2">
                    Welcome, {firstName}!
                </h1>
                <p className="text-lg text-muted-foreground">
                    Here's how to get started with TaxLab:
                </p>
            </div>

            <div className="grid gap-4">
                <Card>
                    <CardContent className="p-6">
                        <div className="flex items-start gap-4">
                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-primary text-primary-foreground font-semibold">
                                1
                            </div>
                            <div className="flex-1">
                                <h3 className="font-semibold mb-1">View your assigned clients</h3>
                                <p className="text-sm text-muted-foreground mb-3">
                                    See the clients you've been assigned to work with.
                                </p>
                                <Button asChild>
                                    <Link href="/app/clients">View Clients</Link>
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-6">
                        <div className="flex items-start gap-4">
                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-muted text-muted-foreground font-semibold">
                                2
                            </div>
                            <div className="flex-1">
                                <h3 className="font-semibold mb-1">Run your first calculation</h3>
                                <p className="text-sm text-muted-foreground mb-3">
                                    Analyze a client's tax position with our calculation engine.
                                </p>
                                <Button variant="outline" asChild>
                                    <Link href="/app/calculations/new">
                                        <Calculator className="mr-2 h-4 w-4" />
                                        Run Calculation
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}
```

---

## Custom Hooks

### use-greeting Hook

**File:** `resources/js/hooks/use-greeting.ts`

```typescript
import { useMemo } from 'react';

export function useGreeting(): string {
    return useMemo(() => {
        const hour = new Date().getHours();

        if (hour >= 5 && hour < 12) {
            return 'Good morning';
        } else if (hour >= 12 && hour < 17) {
            return 'Good afternoon';
        } else if (hour >= 17 && hour < 21) {
            return 'Good evening';
        } else {
            return 'Good night';
        }
    }, []);
}
```

### use-activity-feed Hook

**File:** `resources/js/hooks/use-activity-feed.ts`

```typescript
import { useState, useEffect, useCallback } from 'react';
import type { ActivityItem, PaginatedActivity } from '@/types';

interface UseActivityFeedReturn {
    activities: ActivityItem[];
    isLoading: boolean;
    isLoadingMore: boolean;
    hasMore: boolean;
    loadMore: () => void;
    refresh: () => void;
}

export function useActivityFeed(): UseActivityFeedReturn {
    const [activities, setActivities] = useState<ActivityItem[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [isLoadingMore, setIsLoadingMore] = useState(false);
    const [currentPage, setCurrentPage] = useState(1);
    const [lastPage, setLastPage] = useState(1);

    const fetchActivity = useCallback(async (page: number, append = false) => {
        if (append) {
            setIsLoadingMore(true);
        } else {
            setIsLoading(true);
        }

        try {
            const response = await fetch(`/app/dashboard/activity?page=${page}`);
            const data: PaginatedActivity = await response.json();

            if (append) {
                setActivities((prev) => [...prev, ...data.data]);
            } else {
                setActivities(data.data);
            }

            setCurrentPage(data.current_page);
            setLastPage(data.last_page);
        } catch (error) {
            console.error('Failed to fetch activity:', error);
        } finally {
            setIsLoading(false);
            setIsLoadingMore(false);
        }
    }, []);

    useEffect(() => {
        fetchActivity(1);
    }, [fetchActivity]);

    const loadMore = useCallback(() => {
        if (currentPage < lastPage && !isLoadingMore) {
            fetchActivity(currentPage + 1, true);
        }
    }, [currentPage, lastPage, isLoadingMore, fetchActivity]);

    const refresh = useCallback(() => {
        fetchActivity(1);
    }, [fetchActivity]);

    return {
        activities,
        isLoading,
        isLoadingMore,
        hasMore: currentPage < lastPage,
        loadMore,
        refresh,
    };
}
```

---

## Role-Specific Widgets (Stubs)

### Firm Health Widget (Partner Only)

**File:** `resources/js/pages/App/Dashboard/components/FirmHealthWidget.tsx`

```tsx
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';

export function FirmHealthWidget() {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Firm Health</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
                <div>
                    <div className="flex justify-between text-sm mb-1">
                        <span>Subscription</span>
                        <span className="text-muted-foreground">Professional</span>
                    </div>
                    <div className="flex justify-between text-sm mb-1">
                        <span>Client Usage</span>
                        <span className="text-muted-foreground">47/100</span>
                    </div>
                    <Progress value={47} className="h-2" />
                </div>
                <div className="text-sm">
                    <span className="text-muted-foreground">Next billing: </span>
                    <span>January 15, 2025</span>
                </div>
            </CardContent>
        </Card>
    );
}
```

### Team Overview Widget (Partner Only)

**File:** `resources/js/pages/App/Dashboard/components/TeamOverviewWidget.tsx`

```tsx
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export function TeamOverviewWidget() {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Team Overview</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="space-y-2 text-sm">
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">Active this week</span>
                        <span className="font-medium">5 users</span>
                    </div>
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">Reports generated</span>
                        <span className="font-medium">23</span>
                    </div>
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">Calculations run</span>
                        <span className="font-medium">45</span>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
```

### Team Workload Widget (Manager Only)

**File:** `resources/js/pages/App/Dashboard/components/TeamWorkloadWidget.tsx`

```tsx
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export function TeamWorkloadWidget() {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Team Workload</CardTitle>
            </CardHeader>
            <CardContent>
                <p className="text-sm text-muted-foreground">
                    Workload distribution will appear here once team members are assigned clients.
                </p>
            </CardContent>
        </Card>
    );
}
```

### My Performance Widget (Associate Only)

**File:** `resources/js/pages/App/Dashboard/components/MyPerformanceWidget.tsx`

```tsx
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export function MyPerformanceWidget() {
    return (
        <Card>
            <CardHeader>
                <CardTitle>My Performance</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="space-y-2 text-sm">
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">Calculations this month</span>
                        <span className="font-medium">12</span>
                    </div>
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">Reports generated</span>
                        <span className="font-medium">8</span>
                    </div>
                    <div className="flex justify-between">
                        <span className="text-muted-foreground">Clients analyzed</span>
                        <span className="font-medium">15</span>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
```

---

## Next Steps

Once dashboard frontend is complete, proceed to:
→ **04_CLIENT_BACKEND.md** - Implement client management controllers and services
