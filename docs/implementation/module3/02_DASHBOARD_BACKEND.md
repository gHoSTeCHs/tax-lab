# TaxLab Implementation - Dashboard Backend

## Overview

This document covers the backend implementation for the Firm Dashboard, including controllers, services, and caching strategies.

Reference: `docs/05_FIRM_DASHBOARD.md`

---

## Dashboard Controller

**File:** `app/Http/Controllers/App/DashboardController.php`

```php
<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    public function index(Request $request): Response
    {
        $firmUser = $request->user();

        return Inertia::render('App/Dashboard/Index', [
            'firmRole' => $firmUser->role->value,
            'metrics' => $this->dashboardService->getMetricsForUser($firmUser),
            'attentionItems' => $this->dashboardService->getAttentionItems($firmUser),
            'quickLinks' => $this->dashboardService->getQuickLinks($firmUser),
            'emptyState' => $this->dashboardService->getEmptyState($firmUser),
        ]);
    }

    public function metrics(Request $request): JsonResponse
    {
        $firmUser = $request->user();

        return response()->json([
            'metrics' => $this->dashboardService->getMetricsForUser($firmUser, fresh: true),
        ]);
    }

    public function activity(Request $request): JsonResponse
    {
        $firmUser = $request->user();
        $page = $request->integer('page', 1);
        $perPage = $request->integer('per_page', 10);

        $activity = $this->dashboardService->getRecentActivity($firmUser, $page, $perPage);

        return response()->json($activity);
    }

    public function recentClients(Request $request): JsonResponse
    {
        $firmUser = $request->user();
        $limit = $request->integer('limit', 5);

        $clients = $this->dashboardService->getRecentClients($firmUser, $limit);

        return response()->json([
            'clients' => $clients,
        ]);
    }
}
```

---

## Dashboard Service

**File:** `app/Services/Dashboard/DashboardService.php`

```php
<?php

namespace App\Services\Dashboard;

use App\Enums\FirmRole;
use App\Models\FirmUser;
use App\Models\TaxClient;
use App\Services\Activity\ActivityService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    public function __construct(
        private DashboardMetricsService $metricsService,
        private ActivityService $activityService
    ) {}

    public function getMetricsForUser(FirmUser $user, bool $fresh = false): array
    {
        $cacheKey = "firm:{$user->firm_id}:dashboard:metrics:{$user->role->value}:{$user->id}";

        if ($fresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, 300, function () use ($user) {
            return $this->metricsService->calculateMetrics($user);
        });
    }

    public function getAttentionItems(FirmUser $user): array
    {
        $cacheKey = "firm:{$user->firm_id}:dashboard:attention:{$user->id}";

        return Cache::remember($cacheKey, 300, function () use ($user) {
            return $this->buildAttentionItems($user);
        });
    }

    public function getQuickLinks(FirmUser $user): array
    {
        $links = [
            [
                'title' => 'Knowledge Base',
                'href' => '/app/knowledge-base',
                'icon' => 'book-open',
                'description' => 'Browse articles and guides',
            ],
            [
                'title' => 'Help & Support',
                'href' => '/app/help',
                'icon' => 'help-circle',
                'description' => 'Get assistance',
            ],
        ];

        if ($user->role->canManageTeam()) {
            $pendingApprovals = $this->metricsService->getPendingApprovalsCount($user);
            if ($pendingApprovals > 0) {
                array_unshift($links, [
                    'title' => 'Pending Approvals',
                    'href' => '/app/reports?status=pending',
                    'icon' => 'clock',
                    'description' => "{$pendingApprovals} reports awaiting approval",
                    'badge' => $pendingApprovals,
                ]);
            }
        }

        if ($user->isPartner()) {
            $links[] = [
                'title' => 'Billing',
                'href' => '/app/settings/billing',
                'icon' => 'credit-card',
                'description' => 'Manage subscription',
            ];
            $links[] = [
                'title' => 'Firm Settings',
                'href' => '/app/settings/firm',
                'icon' => 'settings',
                'description' => 'Configure firm',
            ];
        }

        return $links;
    }

    public function getEmptyState(FirmUser $user): ?string
    {
        $clientCount = TaxClient::where('firm_id', $user->firm_id)
            ->active()
            ->count();

        if ($clientCount === 0) {
            return 'new_firm';
        }

        $hasActivity = $this->activityService->hasUserActivity($user);

        if (!$hasActivity) {
            return 'new_user';
        }

        return null;
    }

    public function getRecentActivity(FirmUser $user, int $page = 1, int $perPage = 10): LengthAwarePaginator
    {
        return $this->activityService->getRecentForUser($user, $page, $perPage);
    }

    public function getRecentClients(FirmUser $user, int $limit = 5): Collection
    {
        $cacheKey = "user:{$user->id}:recent_clients";

        return Cache::remember($cacheKey, 300, function () use ($user, $limit) {
            return $user->firm
                ->taxClients()
                ->accessibleBy($user)
                ->active()
                ->with(['industry:id,name'])
                ->withMax('accessLogs as last_accessed_at', 'accessed_at')
                ->orderByDesc('last_accessed_at')
                ->limit($limit)
                ->get()
                ->map(fn (TaxClient $client) => [
                    'id' => $client->id,
                    'name' => $client->display_name,
                    'entityType' => $client->entity_type->value,
                    'entityTypeLabel' => $client->entity_type->label(),
                    'entityTypeIcon' => $client->entity_type->icon(),
                    'industry' => $client->industry?->name,
                    'lastAccessedAt' => $client->last_accessed_at?->diffForHumans(),
                    'url' => route('clients.show', $client),
                ]);
        });
    }

    private function buildAttentionItems(FirmUser $user): array
    {
        $items = [];
        $firm = $user->firm;

        if ($user->isPartner()) {
            if ($firm->status === 'trial' && $firm->trial_ends_at?->diffInDays(now()) <= 3) {
                $items[] = [
                    'id' => 'trial_expiring',
                    'type' => 'trial_expiring',
                    'priority' => 'high',
                    'message' => "Trial expires in {$firm->trial_ends_at->diffForHumans()}",
                    'actionUrl' => '/app/settings/billing',
                    'actionLabel' => 'Upgrade now',
                ];
            }
        }

        $rejectedReports = $this->metricsService->getRejectedReportsCount($user);
        if ($rejectedReports > 0) {
            $items[] = [
                'id' => 'rejected_reports',
                'type' => 'rejected_reports',
                'priority' => 'high',
                'message' => "{$rejectedReports} report(s) rejected - need revision",
                'actionUrl' => '/app/reports?status=rejected',
                'actionLabel' => 'View reports',
            ];
        }

        $clientsWithoutAnalysis = $this->metricsService->getClientsWithoutCurrentAnalysis($user);
        if ($clientsWithoutAnalysis > 0) {
            $items[] = [
                'id' => 'clients_need_analysis',
                'type' => 'clients_need_analysis',
                'priority' => 'medium',
                'message' => "{$clientsWithoutAnalysis} client(s) need current year analysis",
                'actionUrl' => '/app/clients?needs_analysis=1',
                'actionLabel' => 'View clients',
            ];
        }

        $pendingReports = $this->metricsService->getReportsPendingLong($user);
        if ($pendingReports > 0 && $user->role->canApproveReports()) {
            $items[] = [
                'id' => 'pending_reports_long',
                'type' => 'pending_reports_long',
                'priority' => 'medium',
                'message' => "{$pendingReports} report(s) pending approval for 3+ days",
                'actionUrl' => '/app/reports?status=pending&days=3',
                'actionLabel' => 'Review now',
            ];
        }

        usort($items, function ($a, $b) {
            $priorityOrder = ['high' => 0, 'medium' => 1, 'low' => 2];
            return $priorityOrder[$a['priority']] <=> $priorityOrder[$b['priority']];
        });

        return array_slice($items, 0, 5);
    }

    public function clearCacheForFirm(string $firmId): void
    {
        $patterns = [
            "firm:{$firmId}:dashboard:*",
        ];

        foreach ($patterns as $pattern) {
            $keys = Cache::getStore()->many([$pattern]);
            foreach (array_keys($keys) as $key) {
                Cache::forget($key);
            }
        }
    }

    public function clearCacheForUser(FirmUser $user): void
    {
        Cache::forget("user:{$user->id}:recent_clients");
        Cache::forget("firm:{$user->firm_id}:dashboard:metrics:{$user->role->value}:{$user->id}");
        Cache::forget("firm:{$user->firm_id}:dashboard:attention:{$user->id}");
    }
}
```

---

## Dashboard Metrics Service

**File:** `app/Services/Dashboard/DashboardMetricsService.php`

```php
<?php

namespace App\Services\Dashboard;

use App\Enums\FirmRole;
use App\Models\FirmUser;
use App\Models\TaxClient;

class DashboardMetricsService
{
    public function calculateMetrics(FirmUser $user): array
    {
        return match ($user->role) {
            FirmRole::PARTNER => $this->getPartnerMetrics($user),
            FirmRole::MANAGER => $this->getManagerMetrics($user),
            FirmRole::ASSOCIATE => $this->getAssociateMetrics($user),
            FirmRole::VIEWER => $this->getViewerMetrics($user),
        };
    }

    private function getPartnerMetrics(FirmUser $user): array
    {
        $firmId = $user->firm_id;

        return [
            [
                'key' => 'active_clients',
                'label' => 'Active Clients',
                'value' => TaxClient::where('firm_id', $firmId)->active()->count(),
                'icon' => 'users',
                'href' => '/app/clients',
            ],
            [
                'key' => 'reports_this_month',
                'label' => 'Reports This Month',
                'value' => $this->getReportsThisMonth($firmId),
                'icon' => 'file-text',
                'href' => '/app/reports',
            ],
            [
                'key' => 'pending_approvals',
                'label' => 'Pending Approvals',
                'value' => $this->getPendingApprovalsCount($user),
                'icon' => 'clock',
                'href' => '/app/reports?status=pending',
                'variant' => $this->getPendingApprovalsCount($user) > 0 ? 'warning' : 'default',
            ],
            [
                'key' => 'team_activity',
                'label' => 'Active Users (7d)',
                'value' => $this->getActiveUsersCount($firmId),
                'icon' => 'activity',
            ],
        ];
    }

    private function getManagerMetrics(FirmUser $user): array
    {
        $firmId = $user->firm_id;

        return [
            [
                'key' => 'active_clients',
                'label' => 'Active Clients',
                'value' => TaxClient::where('firm_id', $firmId)->active()->count(),
                'icon' => 'users',
                'href' => '/app/clients',
            ],
            [
                'key' => 'team_reports',
                'label' => "Team's Reports",
                'value' => $this->getTeamReportsThisMonth($user),
                'icon' => 'file-text',
                'href' => '/app/reports',
            ],
            [
                'key' => 'pending_approvals',
                'label' => 'Pending Approvals',
                'value' => $this->getPendingApprovalsCount($user),
                'icon' => 'clock',
                'href' => '/app/reports?status=pending',
                'variant' => $this->getPendingApprovalsCount($user) > 0 ? 'warning' : 'default',
            ],
            [
                'key' => 'calculations_week',
                'label' => 'Calculations (7d)',
                'value' => $this->getCalculationsThisWeek($firmId),
                'icon' => 'calculator',
            ],
        ];
    }

    private function getAssociateMetrics(FirmUser $user): array
    {
        return [
            [
                'key' => 'my_clients',
                'label' => 'My Clients',
                'value' => $user->assignedClients()->active()->count(),
                'icon' => 'users',
                'href' => '/app/clients',
            ],
            [
                'key' => 'my_reports',
                'label' => 'My Reports This Month',
                'value' => $this->getMyReportsThisMonth($user),
                'icon' => 'file-text',
                'href' => '/app/reports',
            ],
            [
                'key' => 'pending_review',
                'label' => 'Pending Review',
                'value' => $this->getMyPendingReviewCount($user),
                'icon' => 'clock',
                'href' => '/app/reports?status=pending',
            ],
            [
                'key' => 'my_calculations',
                'label' => 'Calculations (7d)',
                'value' => $this->getMyCalculationsThisWeek($user),
                'icon' => 'calculator',
            ],
        ];
    }

    private function getViewerMetrics(FirmUser $user): array
    {
        return [
            [
                'key' => 'assigned_clients',
                'label' => 'Assigned Clients',
                'value' => $user->assignedClients()->active()->count(),
                'icon' => 'users',
                'href' => '/app/clients',
            ],
            [
                'key' => 'recent_reports',
                'label' => 'Recent Reports',
                'value' => $this->getRecentReportsForViewer($user),
                'icon' => 'file-text',
                'href' => '/app/reports',
            ],
        ];
    }

    public function getPendingApprovalsCount(FirmUser $user): int
    {
        return 0;
    }

    public function getRejectedReportsCount(FirmUser $user): int
    {
        return 0;
    }

    public function getClientsWithoutCurrentAnalysis(FirmUser $user): int
    {
        $currentYear = now()->year;

        $query = TaxClient::where('firm_id', $user->firm_id)
            ->active();

        if ($user->requiresClientAssignment()) {
            $query->assignedTo($user);
        }

        return $query->count();
    }

    public function getReportsPendingLong(FirmUser $user): int
    {
        return 0;
    }

    private function getReportsThisMonth(string $firmId): int
    {
        return 0;
    }

    private function getTeamReportsThisMonth(FirmUser $user): int
    {
        return 0;
    }

    private function getMyReportsThisMonth(FirmUser $user): int
    {
        return 0;
    }

    private function getMyPendingReviewCount(FirmUser $user): int
    {
        return 0;
    }

    private function getCalculationsThisWeek(string $firmId): int
    {
        return 0;
    }

    private function getMyCalculationsThisWeek(FirmUser $user): int
    {
        return 0;
    }

    private function getRecentReportsForViewer(FirmUser $user): int
    {
        return 0;
    }

    private function getActiveUsersCount(string $firmId): int
    {
        return FirmUser::where('firm_id', $firmId)
            ->where('last_login_at', '>=', now()->subDays(7))
            ->count();
    }
}
```

---

## Activity Service

**File:** `app/Services/Activity/ActivityService.php`

```php
<?php

namespace App\Services\Activity;

use App\Enums\ActivityAction;
use App\Enums\FirmRole;
use App\Models\ActivityLog;
use App\Models\FirmUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ActivityService
{
    public function log(
        Model $model,
        ActivityAction $action,
        FirmUser $user,
        ?string $description = null,
        ?array $metadata = null
    ): ActivityLog {
        $log = ActivityLog::create([
            'firm_id' => $user->firm_id,
            'firm_user_id' => $user->id,
            'loggable_type' => $model->getMorphClass(),
            'loggable_id' => $model->getKey(),
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);

        Cache::forget("firm:{$user->firm_id}:dashboard:activity");

        return $log;
    }

    public function getRecentForUser(FirmUser $user, int $page = 1, int $perPage = 10): LengthAwarePaginator
    {
        $query = ActivityLog::where('firm_id', $user->firm_id)
            ->with(['user:id,name,email', 'loggable'])
            ->orderByDesc('created_at');

        if ($user->role === FirmRole::ASSOCIATE) {
            $assignedClientIds = $user->assignedClients()->pluck('tax_clients.id');
            $query->where(function ($q) use ($user, $assignedClientIds) {
                $q->where('firm_user_id', $user->id)
                    ->orWhere(function ($q2) use ($assignedClientIds) {
                        $q2->where('loggable_type', 'App\\Models\\TaxClient')
                            ->whereIn('loggable_id', $assignedClientIds);
                    });
            });
        }

        if ($user->role === FirmRole::VIEWER) {
            $assignedClientIds = $user->assignedClients()->pluck('tax_clients.id');
            $query->where('loggable_type', 'App\\Models\\TaxClient')
                ->whereIn('loggable_id', $assignedClientIds);
        }

        return $query->paginate($perPage, ['*'], 'page', $page)
            ->through(fn (ActivityLog $log) => [
                'id' => $log->id,
                'userId' => $log->firm_user_id,
                'userName' => $log->user?->name ?? 'System',
                'userAvatar' => null,
                'action' => $log->action->value,
                'actionLabel' => $log->action->label(),
                'description' => $log->formatted_description,
                'targetType' => class_basename($log->loggable_type),
                'targetName' => $log->loggable?->name ?? $log->description,
                'targetUrl' => $this->getTargetUrl($log),
                'createdAt' => $log->created_at->toIso8601String(),
                'createdAtHuman' => $log->created_at->diffForHumans(),
            ]);
    }

    public function hasUserActivity(FirmUser $user): bool
    {
        return ActivityLog::where('firm_user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->exists();
    }

    public function getForModel(Model $model, int $limit = 20): array
    {
        return ActivityLog::forLoggable($model)
            ->with(['user:id,name,email'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (ActivityLog $log) => [
                'id' => $log->id,
                'userName' => $log->user?->name ?? 'System',
                'action' => $log->action->label(),
                'createdAt' => $log->created_at->diffForHumans(),
            ])
            ->toArray();
    }

    private function getTargetUrl(ActivityLog $log): ?string
    {
        if ($log->loggable_type === 'App\\Models\\TaxClient' && $log->loggable) {
            return route('clients.show', $log->loggable);
        }

        return null;
    }
}
```

---

## Routes

**File:** `routes/app.php`

```php
<?php

use App\Http\Controllers\App\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:firm', 'verified'])->prefix('app')->name('app.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/metrics', [DashboardController::class, 'metrics'])->name('dashboard.metrics');
    Route::get('/dashboard/activity', [DashboardController::class, 'activity'])->name('dashboard.activity');
    Route::get('/dashboard/recent-clients', [DashboardController::class, 'recentClients'])->name('dashboard.recent-clients');
});
```

Add to `routes/web.php`:

```php
require __DIR__.'/app.php';
```

---

## Cache Keys Reference

| Cache Key | TTL | Description | Invalidation |
|-----------|-----|-------------|--------------|
| `firm:{id}:dashboard:metrics:{role}:{userId}` | 5 min | User's dashboard metrics | Client CRUD, Report CRUD |
| `firm:{id}:dashboard:attention:{userId}` | 5 min | Attention items | Various triggers |
| `user:{id}:recent_clients` | 5 min | Recent client list | Client access |
| `firm:{id}:dashboard:activity` | 2 min | Activity feed | Any logged activity |

---

## Event Listeners for Cache Invalidation

**File:** `app/Listeners/InvalidateDashboardCache.php`

```php
<?php

namespace App\Listeners;

use App\Services\Dashboard\DashboardService;
use Illuminate\Contracts\Queue\ShouldQueue;

class InvalidateDashboardCache implements ShouldQueue
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    public function handle(object $event): void
    {
        if (property_exists($event, 'firmId')) {
            $this->dashboardService->clearCacheForFirm($event->firmId);
        }

        if (property_exists($event, 'user')) {
            $this->dashboardService->clearCacheForUser($event->user);
        }
    }
}
```

---

## Global Search Controller

**File:** `app/Http/Controllers/App/SearchController.php`

```php
<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\TaxClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
            'limit' => ['integer', 'min:1', 'max:20'],
        ]);

        $user = $request->user();
        $query = $request->input('q');
        $limit = $request->integer('limit', 10);

        $results = [
            'clients' => $this->searchClients($user, $query, $limit),
            'reports' => [],
            'knowledgeBase' => [],
        ];

        return response()->json($results);
    }

    private function searchClients($user, string $query, int $limit): array
    {
        return TaxClient::where('firm_id', $user->firm_id)
            ->accessibleBy($user)
            ->active()
            ->search($query)
            ->with(['industry:id,name'])
            ->limit($limit)
            ->get()
            ->map(fn (TaxClient $client) => [
                'id' => $client->id,
                'name' => $client->display_name,
                'entityType' => $client->entity_type->label(),
                'entityTypeIcon' => $client->entity_type->icon(),
                'industry' => $client->industry?->name,
                'tin' => $client->masked_tin,
                'url' => route('clients.show', $client),
                'type' => 'client',
            ])
            ->toArray();
    }
}
```

---

## Search Routes

Add to `routes/app.php`:

```php
Route::get('/search', SearchController::class)->name('search');
```

---

## Search Service (Optional Extended Implementation)

**File:** `app/Services/Search/SearchService.php`

```php
<?php

namespace App\Services\Search;

use App\Models\FirmUser;
use App\Models\TaxClient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SearchService
{
    /**
     * Perform a global search across multiple entity types.
     */
    public function search(FirmUser $user, string $query, int $limit = 10): array
    {
        return [
            'clients' => $this->searchClients($user, $query, $limit),
            'reports' => $this->searchReports($user, $query, $limit),
            'knowledgeBase' => $this->searchKnowledgeBase($query, $limit),
        ];
    }

    private function searchClients(FirmUser $user, string $query, int $limit): Collection
    {
        return TaxClient::where('firm_id', $user->firm_id)
            ->accessibleBy($user)
            ->active()
            ->search($query)
            ->with(['industry:id,name'])
            ->limit($limit)
            ->get()
            ->map(fn (TaxClient $client) => [
                'id' => $client->id,
                'name' => $client->display_name,
                'entityType' => $client->entity_type->label(),
                'icon' => $client->entity_type->icon(),
                'industry' => $client->industry?->name,
                'url' => route('clients.show', $client),
                'type' => 'client',
            ]);
    }

    private function searchReports(FirmUser $user, string $query, int $limit): Collection
    {
        return collect();
    }

    private function searchKnowledgeBase(string $query, int $limit): Collection
    {
        return collect();
    }

    /**
     * Get recent searches for user.
     */
    public function getRecentSearches(FirmUser $user, int $limit = 5): array
    {
        $cacheKey = "user:{$user->id}:recent_searches";

        return Cache::get($cacheKey, []);
    }

    /**
     * Store a recent search for user.
     */
    public function storeRecentSearch(FirmUser $user, string $query): void
    {
        $cacheKey = "user:{$user->id}:recent_searches";
        $searches = $this->getRecentSearches($user, 10);

        $searches = array_filter($searches, fn ($s) => $s !== $query);
        array_unshift($searches, $query);
        $searches = array_slice($searches, 0, 10);

        Cache::put($cacheKey, $searches, now()->addDays(30));
    }
}
```

---

## Next Steps

Once dashboard backend is complete, proceed to:
→ **03_DASHBOARD_FRONTEND.md** - Implement dashboard React components
