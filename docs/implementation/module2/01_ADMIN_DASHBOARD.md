# TaxLab Implementation - Admin Dashboard

## Overview

The Admin Dashboard provides platform administrators with an at-a-glance view of business health, recent activity, and items requiring attention.

Reference: `docs/04_PLATFORM_ADMIN_MODULES.md` Section 2

---

## Dashboard Components

### Key Metrics Cards

| Metric | Description | Data Source |
|--------|-------------|-------------|
| Active Tenants | Firms on paid plans | `firms.status = 'active'` |
| Monthly Recurring Revenue | Total MRR | Sum of active subscriptions |
| Trial Conversions (30d) | Trials converted to paid | Status change events |
| Active Users (7d) | Unique firm users active | Login events |
| Reports Generated (7d) | Total reports created | Report creation logs |
| Support Tickets Open | Unresolved tickets | Ticket system (future) |

### Revenue Chart

- Line chart showing MRR over past 12 months
- Breakdown by plan tier (Starter, Professional, Enterprise)
- Comparison to previous period (percentage change)
- Annotations for significant events (optional, future)

### Tenant Funnel

Visual funnel showing (30-day window):
1. Total signups
2. Completed onboarding
3. First calculation run
4. First report generated
5. Trial converted

### Recent Activity Feed

Chronological list of significant events:
- New tenant signups
- Plan upgrades/downgrades
- Tenant cancellations
- Tenant suspensions/reactivations
- System alerts (health warnings, failed jobs)

### Attention Required

Items needing admin action:
- Failed payments requiring follow-up
- Tenants approaching trial expiry (7 days)
- Tenants with overdue payments
- System health warnings (disk space, queue backlog, failed jobs)

---

## Backend Implementation

### Dashboard Controller

**File:** `app/Http/Controllers/Admin/DashboardController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Dashboard/Index', [
            'metrics' => $this->dashboardService->getKeyMetrics(),
            'revenueChart' => $this->dashboardService->getRevenueChartData(),
            'tenantFunnel' => $this->dashboardService->getTenantFunnelData(),
            'recentActivity' => $this->dashboardService->getRecentActivity(),
            'attentionItems' => $this->dashboardService->getAttentionItems(),
        ]);
    }

    public function metrics(): array
    {
        return $this->dashboardService->getKeyMetrics();
    }
}
```

### Dashboard Service

**File:** `app/Services/Admin/DashboardService.php`

```php
<?php

namespace App\Services\Admin;

use App\Models\Firm;
use App\Models\FirmUser;
use App\Models\Subscription;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getKeyMetrics(): array
    {
        return Cache::remember('admin.dashboard.metrics', 300, function () {
            return [
                'activeTenants' => $this->getActiveTenantCount(),
                'trialTenants' => $this->getTrialTenantCount(),
                'mrr' => $this->calculateMRR(),
                'trialConversions' => $this->getTrialConversions(30),
                'activeUsers' => $this->getActiveUserCount(7),
                'reportsGenerated' => $this->getReportsGeneratedCount(7),
            ];
        });
    }

    private function getActiveTenantCount(): int
    {
        return Firm::where('status', 'active')->count();
    }

    private function getTrialTenantCount(): int
    {
        return Firm::where('status', 'trial')
            ->where('trial_ends_at', '>', now())
            ->count();
    }

    private function calculateMRR(): int
    {
        return Subscription::where('status', 'active')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->selectRaw('SUM(CASE
                WHEN subscriptions.billing_cycle = "annual" THEN plans.annual_price / 12
                ELSE plans.monthly_price
            END) as mrr')
            ->value('mrr') ?? 0;
    }

    private function getTrialConversions(int $days): array
    {
        $converted = Firm::where('status', 'active')
            ->where('subscription_starts_at', '>=', now()->subDays($days))
            ->whereNotNull('trial_ends_at')
            ->count();

        $totalTrials = Firm::where('created_at', '>=', now()->subDays($days * 2))
            ->where('created_at', '<', now()->subDays($days))
            ->count();

        return [
            'count' => $converted,
            'rate' => $totalTrials > 0 ? round(($converted / $totalTrials) * 100, 1) : 0,
        ];
    }

    private function getActiveUserCount(int $days): int
    {
        return FirmUser::where('last_login_at', '>=', now()->subDays($days))
            ->distinct('id')
            ->count();
    }

    private function getReportsGeneratedCount(int $days): int
    {
        return 0;
    }

    public function getRevenueChartData(): array
    {
        return Cache::remember('admin.dashboard.revenue_chart', 3600, function () {
            $months = collect();
            $previousYearData = [];

            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $previousDate = $date->copy()->subYear();

                $currentMrr = $this->getMRRForMonth($date);
                $previousMrr = $this->getMRRForMonth($previousDate);
                $mrrByPlan = $this->getMRRByPlanForMonth($date);

                $months->push([
                    'month' => $date->format('M Y'),
                    'mrr' => $currentMrr,
                    'previousMrr' => $previousMrr,
                    'change' => $previousMrr > 0
                        ? round((($currentMrr - $previousMrr) / $previousMrr) * 100, 1)
                        : 0,
                    'byPlan' => $mrrByPlan,
                ]);
            }

            return $months->toArray();
        });
    }

    private function getMRRForMonth(\Carbon\Carbon $date): int
    {
        return Subscription::where('status', 'active')
            ->where('created_at', '<=', $date->endOfMonth())
            ->where(function ($query) use ($date) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', $date->endOfMonth());
            })
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->selectRaw('SUM(CASE
                WHEN subscriptions.billing_cycle = "annual" THEN plans.annual_price / 12
                ELSE plans.monthly_price
            END) as mrr')
            ->value('mrr') ?? 0;
    }

    private function getMRRByPlanForMonth(\Carbon\Carbon $date): array
    {
        return Subscription::where('subscriptions.status', 'active')
            ->where('subscriptions.created_at', '<=', $date->endOfMonth())
            ->where(function ($query) use ($date) {
                $query->whereNull('subscriptions.ends_at')
                    ->orWhere('subscriptions.ends_at', '>', $date->endOfMonth());
            })
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->selectRaw('plans.name as plan_name, plans.slug as plan_slug, SUM(CASE
                WHEN subscriptions.billing_cycle = "annual" THEN plans.annual_price / 12
                ELSE plans.monthly_price
            END) as mrr')
            ->groupBy('plans.id', 'plans.name', 'plans.slug')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->plan_slug => $item->mrr])
            ->toArray();
    }

    public function getTenantFunnelData(): array
    {
        $days = 30;
        $startDate = now()->subDays($days);

        return Cache::remember('admin.dashboard.funnel', 900, function () use ($startDate) {
            $totalSignups = Firm::where('created_at', '>=', $startDate)->count();

            $completedOnboarding = Firm::where('created_at', '>=', $startDate)
                ->whereNotNull('settings->onboarding_completed_at')
                ->count();

            $converted = Firm::where('created_at', '>=', $startDate)
                ->where('status', 'active')
                ->count();

            return [
                ['stage' => 'Signups', 'count' => $totalSignups],
                ['stage' => 'Onboarded', 'count' => $completedOnboarding],
                ['stage' => 'First Calculation', 'count' => 0],
                ['stage' => 'First Report', 'count' => 0],
                ['stage' => 'Converted', 'count' => $converted],
            ];
        });
    }

    public function getRecentActivity(int $limit = 20): array
    {
        return Cache::remember('admin.dashboard.activity', 60, function () use ($limit) {
            $activities = collect();

            $recentFirms = Firm::latest()
                ->take(10)
                ->get(['id', 'name', 'status', 'created_at']);

            foreach ($recentFirms as $firm) {
                $activities->push([
                    'type' => 'tenant_signup',
                    'message' => "New tenant: {$firm->name}",
                    'timestamp' => $firm->created_at->toIso8601String(),
                    'metadata' => ['firm_id' => $firm->id],
                ]);
            }

            $recentPlanChanges = Subscription::with(['firm', 'plan'])
                ->where('updated_at', '>=', now()->subDays(7))
                ->whereColumn('plan_id', '!=', DB::raw('original_plan_id'))
                ->latest('updated_at')
                ->take(10)
                ->get();

            foreach ($recentPlanChanges as $subscription) {
                $activities->push([
                    'type' => 'plan_change',
                    'message' => "{$subscription->firm->name} changed to {$subscription->plan->name}",
                    'timestamp' => $subscription->updated_at->toIso8601String(),
                    'metadata' => [
                        'firm_id' => $subscription->firm_id,
                        'plan_id' => $subscription->plan_id,
                    ],
                ]);
            }

            $recentCancellations = Firm::where('status', 'churned')
                ->where('updated_at', '>=', now()->subDays(7))
                ->latest('updated_at')
                ->take(10)
                ->get(['id', 'name', 'updated_at']);

            foreach ($recentCancellations as $firm) {
                $activities->push([
                    'type' => 'tenant_cancelled',
                    'message' => "Tenant cancelled: {$firm->name}",
                    'timestamp' => $firm->updated_at->toIso8601String(),
                    'metadata' => ['firm_id' => $firm->id],
                ]);
            }

            $recentSuspensions = Firm::where('status', 'suspended')
                ->where('updated_at', '>=', now()->subDays(7))
                ->latest('updated_at')
                ->take(10)
                ->get(['id', 'name', 'updated_at']);

            foreach ($recentSuspensions as $firm) {
                $activities->push([
                    'type' => 'tenant_suspended',
                    'message' => "Tenant suspended: {$firm->name}",
                    'timestamp' => $firm->updated_at->toIso8601String(),
                    'metadata' => ['firm_id' => $firm->id],
                ]);
            }

            $failedJobs = DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subHours(24))
                ->latest('failed_at')
                ->take(5)
                ->get(['id', 'queue', 'failed_at']);

            foreach ($failedJobs as $job) {
                $activities->push([
                    'type' => 'system_alert',
                    'message' => "Failed job in queue: {$job->queue}",
                    'timestamp' => $job->failed_at,
                    'metadata' => ['job_id' => $job->id],
                ]);
            }

            return $activities
                ->sortByDesc('timestamp')
                ->take($limit)
                ->values()
                ->toArray();
        });
    }

    public function getAttentionItems(): array
    {
        return Cache::remember('admin.dashboard.attention', 300, function () {
            $items = [];

            $expiringTrials = Firm::where('status', 'trial')
                ->whereBetween('trial_ends_at', [now(), now()->addDays(7)])
                ->count();

            if ($expiringTrials > 0) {
                $items[] = [
                    'type' => 'warning',
                    'title' => 'Expiring Trials',
                    'message' => "{$expiringTrials} trials expiring in next 7 days",
                    'action' => '/admin/tenants?status=trial&expiring=7',
                ];
            }

            $overduePayments = Subscription::where('status', 'past_due')->count();

            if ($overduePayments > 0) {
                $items[] = [
                    'type' => 'error',
                    'title' => 'Overdue Payments',
                    'message' => "{$overduePayments} tenants with overdue payments",
                    'action' => '/admin/tenants?payment_status=overdue',
                ];
            }

            $failedPayments = Subscription::where('status', 'payment_failed')
                ->where('updated_at', '>=', now()->subDays(7))
                ->count();

            if ($failedPayments > 0) {
                $items[] = [
                    'type' => 'error',
                    'title' => 'Failed Payments',
                    'message' => "{$failedPayments} failed payments requiring follow-up",
                    'action' => '/admin/tenants?payment_status=failed',
                ];
            }

            $failedJobsCount = DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subHours(24))
                ->count();

            if ($failedJobsCount > 0) {
                $items[] = [
                    'type' => 'warning',
                    'title' => 'System Health',
                    'message' => "{$failedJobsCount} failed jobs in the last 24 hours",
                    'action' => '/admin/system/failed-jobs',
                ];
            }

            $queueSize = DB::table('jobs')->count();

            if ($queueSize > 1000) {
                $items[] = [
                    'type' => 'warning',
                    'title' => 'Queue Backlog',
                    'message' => "{$queueSize} jobs pending in queue",
                    'action' => '/admin/system/queues',
                ];
            }

            return $items;
        });
    }

    public function clearCache(): void
    {
        Cache::forget('admin.dashboard.metrics');
        Cache::forget('admin.dashboard.revenue_chart');
        Cache::forget('admin.dashboard.funnel');
        Cache::forget('admin.dashboard.activity');
        Cache::forget('admin.dashboard.attention');
    }
}
```

---

## Routes

**File:** `routes/admin.php`

```php
<?php

use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:admin', 'verified', 'two-factor'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/dashboard/metrics', [DashboardController::class, 'metrics'])->name('admin.dashboard.metrics');
});
```

---

## Frontend Implementation

### Dashboard Page

**File:** `resources/js/pages/Admin/Dashboard/Index.tsx`

```tsx
import { Head } from '@inertiajs/react';
import AdminLayout from '@/layouts/AdminLayout';
import MetricsCards from './components/MetricsCards';
import RevenueChart from './components/RevenueChart';
import TenantFunnel from './components/TenantFunnel';
import ActivityFeed from './components/ActivityFeed';
import AttentionItems from './components/AttentionItems';

interface DashboardProps {
    metrics: {
        activeTenants: number;
        trialTenants: number;
        mrr: number;
        trialConversions: { count: number; rate: number };
        activeUsers: number;
        reportsGenerated: number;
    };
    revenueChart: Array<{ month: string; mrr: number }>;
    tenantFunnel: Array<{ stage: string; count: number }>;
    recentActivity: Array<{
        type: string;
        message: string;
        timestamp: string;
        metadata: Record<string, unknown>;
    }>;
    attentionItems: Array<{
        type: 'warning' | 'error' | 'info';
        title: string;
        message: string;
        action: string;
    }>;
}

export default function Dashboard({
    metrics,
    revenueChart,
    tenantFunnel,
    recentActivity,
    attentionItems,
}: DashboardProps) {
    return (
        <AdminLayout>
            <Head title="Admin Dashboard" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">
                        Dashboard
                    </h1>
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Platform overview and key metrics
                    </p>
                </div>

                {attentionItems.length > 0 && (
                    <AttentionItems items={attentionItems} />
                )}

                <MetricsCards metrics={metrics} />

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <RevenueChart data={revenueChart} />
                    <TenantFunnel data={tenantFunnel} />
                </div>

                <ActivityFeed activities={recentActivity} />
            </div>
        </AdminLayout>
    );
}
```

### Metrics Cards Component

**File:** `resources/js/pages/Admin/Dashboard/components/MetricsCards.tsx`

```tsx
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Building2, Users, TrendingUp, FileText, CreditCard, AlertCircle } from 'lucide-react';

interface MetricsCardsProps {
    metrics: {
        activeTenants: number;
        trialTenants: number;
        mrr: number;
        trialConversions: { count: number; rate: number };
        activeUsers: number;
        reportsGenerated: number;
    };
}

export default function MetricsCards({ metrics }: MetricsCardsProps) {
    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('en-NG', {
            style: 'currency',
            currency: 'NGN',
            minimumFractionDigits: 0,
        }).format(amount / 100);
    };

    const cards = [
        {
            title: 'Active Tenants',
            value: metrics.activeTenants,
            subtitle: `${metrics.trialTenants} on trial`,
            icon: Building2,
        },
        {
            title: 'Monthly Recurring Revenue',
            value: formatCurrency(metrics.mrr),
            icon: CreditCard,
        },
        {
            title: 'Trial Conversions (30d)',
            value: `${metrics.trialConversions.rate}%`,
            subtitle: `${metrics.trialConversions.count} converted`,
            icon: TrendingUp,
        },
        {
            title: 'Active Users (7d)',
            value: metrics.activeUsers,
            icon: Users,
        },
        {
            title: 'Reports Generated (7d)',
            value: metrics.reportsGenerated,
            icon: FileText,
        },
    ];

    return (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
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
                        {card.subtitle && (
                            <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {card.subtitle}
                            </p>
                        )}
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}
```

### Revenue Chart Component

**File:** `resources/js/pages/Admin/Dashboard/components/RevenueChart.tsx`

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
} from 'recharts';

interface RevenueChartProps {
    data: Array<{ month: string; mrr: number }>;
}

export default function RevenueChart({ data }: RevenueChartProps) {
    const formatCurrency = (value: number) => {
        if (value >= 1000000) {
            return `₦${(value / 1000000).toFixed(1)}M`;
        }
        if (value >= 1000) {
            return `₦${(value / 1000).toFixed(0)}K`;
        }
        return `₦${value}`;
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>Monthly Recurring Revenue</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="h-[300px]">
                    <ResponsiveContainer width="100%" height="100%">
                        <LineChart data={data}>
                            <CartesianGrid strokeDasharray="3 3" className="stroke-gray-200 dark:stroke-gray-700" />
                            <XAxis
                                dataKey="month"
                                tick={{ fontSize: 12 }}
                                tickLine={false}
                                axisLine={false}
                            />
                            <YAxis
                                tickFormatter={formatCurrency}
                                tick={{ fontSize: 12 }}
                                tickLine={false}
                                axisLine={false}
                            />
                            <Tooltip
                                formatter={(value: number) => [formatCurrency(value), 'MRR']}
                                contentStyle={{
                                    backgroundColor: 'var(--background)',
                                    border: '1px solid var(--border)',
                                    borderRadius: '8px',
                                }}
                            />
                            <Line
                                type="monotone"
                                dataKey="mrr"
                                stroke="#2563eb"
                                strokeWidth={2}
                                dot={false}
                                activeDot={{ r: 4 }}
                            />
                        </LineChart>
                    </ResponsiveContainer>
                </div>
            </CardContent>
        </Card>
    );
}
```

### Attention Items Component

**File:** `resources/js/pages/Admin/Dashboard/components/AttentionItems.tsx`

```tsx
import { Link } from '@inertiajs/react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { AlertCircle, AlertTriangle, Info, ChevronRight } from 'lucide-react';

interface AttentionItem {
    type: 'warning' | 'error' | 'info';
    title: string;
    message: string;
    action: string;
}

interface AttentionItemsProps {
    items: AttentionItem[];
}

export default function AttentionItems({ items }: AttentionItemsProps) {
    const getIcon = (type: AttentionItem['type']) => {
        switch (type) {
            case 'error':
                return AlertCircle;
            case 'warning':
                return AlertTriangle;
            default:
                return Info;
        }
    };

    const getVariant = (type: AttentionItem['type']) => {
        switch (type) {
            case 'error':
                return 'destructive';
            case 'warning':
                return 'default';
            default:
                return 'default';
        }
    };

    return (
        <div className="space-y-3">
            {items.map((item, index) => {
                const Icon = getIcon(item.type);
                return (
                    <Alert key={index} variant={getVariant(item.type)}>
                        <Icon className="h-4 w-4" />
                        <AlertTitle className="flex items-center justify-between">
                            {item.title}
                            <Link
                                href={item.action}
                                className="text-sm font-normal flex items-center gap-1 hover:underline"
                            >
                                View <ChevronRight className="h-3 w-3" />
                            </Link>
                        </AlertTitle>
                        <AlertDescription>{item.message}</AlertDescription>
                    </Alert>
                );
            })}
        </div>
    );
}
```

---

## Data Caching Strategy

| Data | Cache Key | TTL | Invalidation |
|------|-----------|-----|--------------|
| Key Metrics | `admin.dashboard.metrics` | 5 min | Manual/Event |
| Revenue Chart | `admin.dashboard.revenue_chart` | 1 hour | Manual/Event |
| Tenant Funnel | `admin.dashboard.funnel` | 15 min | Manual/Event |
| Activity Feed | `admin.dashboard.activity` | 1 min | Real-time preferred |
| Attention Items | `admin.dashboard.attention` | 5 min | Manual/Event |

### Cache Invalidation Events

```php
Firm::created(fn () => Cache::forget('admin.dashboard.metrics'));
Firm::updated(fn () => Cache::forget('admin.dashboard.metrics'));
Subscription::created(fn () => Cache::forget('admin.dashboard.metrics'));
Subscription::updated(fn () => Cache::forget('admin.dashboard.metrics'));
```

---

## API Endpoints (for real-time updates)

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/admin/dashboard/metrics` | GET | Refresh metrics only |
| `/admin/dashboard/activity` | GET | Fetch latest activity |

---

## Testing

```php
test('admin can view dashboard', function () {
    $admin = AdminUser::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'admin')
        ->get('/admin/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard/Index')
            ->has('metrics')
            ->has('revenueChart')
            ->has('tenantFunnel')
            ->has('recentActivity')
            ->has('attentionItems')
        );
});

test('dashboard metrics are cached', function () {
    $admin = AdminUser::factory()->create();
    Firm::factory()->count(5)->create(['status' => 'active']);

    Cache::shouldReceive('remember')
        ->once()
        ->andReturn(['activeTenants' => 5]);

    $this->actingAs($admin, 'admin')
        ->get('/admin/dashboard/metrics');
});
```
