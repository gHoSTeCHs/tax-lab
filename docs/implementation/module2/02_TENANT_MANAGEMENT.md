# TaxLab Implementation - Tenant Management

## Overview

Enable administrators to view, manage, and support subscribing firms throughout their lifecycle.

Reference: `docs/04_PLATFORM_ADMIN_MODULES.md` Section 3

---

## Features

### Tenant List View
- Filterable, sortable list of all firms
- Filters: status, plan, created date range, last activity range, search
- Columns: firm name, primary contact email, plan, status, client count, user count, created date, last activity
- Default sort: last activity descending
- Quick actions from list
- Bulk export (selected to CSV)
- Bulk communication (send email to selected)

### Tenant Detail View
- Overview with key information (name, slug, email, phone, address, registration)
- Subscription details:
  - Current plan name
  - Billing cycle (monthly/annual)
  - Current price
  - Next billing date
  - Payment method (card type, last 4 digits)
  - Payment status (current, overdue, failed)
- Usage statistics (clients, users, reports vs limits)
- User management (list with impersonation)
- Activity log (recent actions)
- Billing history (invoices, payments)
- Internal notes (timestamped, categorized)

### Tenant Actions
- Edit firm details
- Change subscription plan
- Extend trial period
- Apply billing credit
- Suspend/reactivate account
- Impersonate users (Super Admin)
- Export tenant data
- Delete account (with confirmation)

---

## Backend Implementation

### Tenant Controller

**File:** `app/Http/Controllers/Admin/TenantController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateTenantRequest;
use App\Http\Requests\Admin\TenantActionRequest;
use App\Models\Firm;
use App\Models\Plan;
use App\Services\Admin\TenantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    public function __construct(
        private TenantService $tenantService
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only([
            'status', 'plan', 'search', 'sort', 'direction',
            'created_from', 'created_to', 'activity_from', 'activity_to',
            'selected',
        ]);

        $tenants = $this->tenantService->getPaginatedTenants($filters);

        return Inertia::render('Admin/Tenants/Index', [
            'tenants' => $tenants,
            'filters' => $filters,
            'plans' => Plan::where('is_active', true)->get(['id', 'name']),
            'statuses' => ['trial', 'active', 'suspended', 'churned'],
        ]);
    }

    public function bulkExport(Request $request)
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:firms,id'],
        ]);

        return $this->tenantService->exportTenantsCsv($request->array('ids'));
    }

    public function bulkCommunicate(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:firms,id'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $this->tenantService->sendBulkCommunication(
            $request->array('ids'),
            $request->string('subject'),
            $request->string('message')
        );

        return back()->with('success', 'Communication sent successfully.');
    }

    public function show(Firm $tenant): Response
    {
        $tenant->load(['plan', 'users', 'activeSubscription', 'activeSubscription.paymentMethod']);

        return Inertia::render('Admin/Tenants/Show', [
            'tenant' => $tenant,
            'usage' => $this->tenantService->getTenantUsage($tenant),
            'stats' => $this->tenantService->getTenantStats($tenant),
            'subscription' => $this->tenantService->getSubscriptionDetails($tenant),
            'recentActivity' => $this->tenantService->getRecentActivity($tenant),
            'billingHistory' => $this->tenantService->getBillingHistory($tenant),
            'notes' => $this->tenantService->getNotes($tenant),
        ]);
    }

    public function edit(Firm $tenant): Response
    {
        return Inertia::render('Admin/Tenants/Edit', [
            'tenant' => $tenant,
            'plans' => Plan::where('is_active', true)->get(),
        ]);
    }

    public function update(UpdateTenantRequest $request, Firm $tenant): RedirectResponse
    {
        $this->tenantService->updateTenant($tenant, $request->validated());

        return redirect()
            ->route('admin.tenants.show', $tenant)
            ->with('success', 'Tenant updated successfully.');
    }

    public function suspend(TenantActionRequest $request, Firm $tenant): RedirectResponse
    {
        $this->authorize('suspend', $tenant);

        $this->tenantService->suspendTenant($tenant, $request->validated('reason'));

        return redirect()
            ->route('admin.tenants.show', $tenant)
            ->with('success', 'Tenant suspended successfully.');
    }

    public function reactivate(TenantActionRequest $request, Firm $tenant): RedirectResponse
    {
        $this->authorize('reactivate', $tenant);

        $this->tenantService->reactivateTenant($tenant, $request->validated('reason'));

        return redirect()
            ->route('admin.tenants.show', $tenant)
            ->with('success', 'Tenant reactivated successfully.');
    }

    public function extendTrial(Request $request, Firm $tenant): RedirectResponse
    {
        $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:30'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $this->tenantService->extendTrial($tenant, $request->integer('days'), $request->string('reason'));

        return redirect()
            ->route('admin.tenants.show', $tenant)
            ->with('success', "Trial extended by {$request->days} days.");
    }

    public function changePlan(Request $request, Firm $tenant): RedirectResponse
    {
        $this->authorize('changePlan', $tenant);

        $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $this->tenantService->changePlan($tenant, $request->string('plan_id'), $request->string('reason'));

        return redirect()
            ->route('admin.tenants.show', $tenant)
            ->with('success', 'Plan changed successfully.');
    }

    public function applyCredit(Request $request, Firm $tenant): RedirectResponse
    {
        $this->authorize('applyCredit', $tenant);

        $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $this->tenantService->applyCredit($tenant, $request->integer('amount'), $request->string('reason'));

        return redirect()
            ->route('admin.tenants.show', $tenant)
            ->with('success', 'Credit applied successfully.');
    }

    public function export(Firm $tenant)
    {
        $this->authorize('export', $tenant);

        return $this->tenantService->exportTenantData($tenant);
    }

    public function destroy(Request $request, Firm $tenant): RedirectResponse
    {
        $this->authorize('delete', $tenant);

        $request->validate([
            'confirmation' => ['required', 'in:DELETE'],
            'reason' => ['required', 'string', 'max:500'],
            'password' => ['required', 'current_password:admin'],
        ]);

        $this->tenantService->deleteTenant($tenant, $request->string('reason'));

        return redirect()
            ->route('admin.tenants.index')
            ->with('success', 'Tenant deleted successfully.');
    }
}
```

### Tenant Service

**File:** `app/Services/Admin/TenantService.php`

```php
<?php

namespace App\Services\Admin;

use App\Models\Firm;
use App\Models\FirmUser;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\TenantNote;
use App\Models\AdminAuditLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TenantService
{
    public function getPaginatedTenants(array $filters): LengthAwarePaginator
    {
        $query = Firm::with(['plan', 'activeSubscription'])
            ->withCount(['users', 'taxClients'])
            ->addSelect([
                'last_activity_at' => FirmUser::selectRaw('MAX(last_login_at)')
                    ->whereColumn('firm_id', 'firms.id')
            ]);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['plan'])) {
            $query->where('plan_id', $filters['plan']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['created_from'])) {
            $query->where('created_at', '>=', $filters['created_from']);
        }

        if (!empty($filters['created_to'])) {
            $query->where('created_at', '<=', $filters['created_to']);
        }

        if (!empty($filters['activity_from'])) {
            $query->having('last_activity_at', '>=', $filters['activity_from']);
        }

        if (!empty($filters['activity_to'])) {
            $query->having('last_activity_at', '<=', $filters['activity_to']);
        }

        $sortColumn = $filters['sort'] ?? 'last_activity_at';
        $sortDirection = $filters['direction'] ?? 'desc';

        if ($sortColumn === 'last_activity_at') {
            $query->orderByRaw('last_activity_at IS NULL, last_activity_at ' . $sortDirection);
        } else {
            $query->orderBy($sortColumn, $sortDirection);
        }

        return $query->paginate(20)->withQueryString();
    }

    public function exportTenantsCsv(array $ids): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $tenants = Firm::with(['plan', 'activeSubscription'])
            ->withCount(['users', 'taxClients'])
            ->whereIn('id', $ids)
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="tenants-export.csv"',
        ];

        return response()->stream(function () use ($tenants) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Name', 'Email', 'Plan', 'Status', 'Clients', 'Users', 'Created']);

            foreach ($tenants as $tenant) {
                fputcsv($handle, [
                    $tenant->name,
                    $tenant->email,
                    $tenant->plan?->name ?? '-',
                    $tenant->status,
                    $tenant->tax_clients_count,
                    $tenant->users_count,
                    $tenant->created_at->toDateString(),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function sendBulkCommunication(array $ids, string $subject, string $message): void
    {
        $tenants = Firm::whereIn('id', $ids)->get();

        foreach ($tenants as $tenant) {
            Mail::to($tenant->email)->queue(new \App\Mail\AdminBulkCommunication($subject, $message, $tenant));
        }

        $this->logAction('tenants.bulk_communication', null, [
            'tenant_ids' => $ids,
            'subject' => $subject,
        ]);
    }

    public function getTenantUsage(Firm $tenant): array
    {
        $plan = $tenant->plan;

        return [
            'clients' => [
                'current' => $tenant->taxClients()->count(),
                'limit' => $plan?->client_limit ?? 0,
            ],
            'users' => [
                'current' => $tenant->users()->count(),
                'limit' => $plan?->user_limit ?? 0,
            ],
            'reports' => [
                'current' => 0,
                'limit' => $plan?->report_limit ?? 0,
            ],
        ];
    }

    public function getTenantStats(Firm $tenant): array
    {
        return [
            'lastActivity' => $tenant->users()->max('last_login_at'),
            'totalCalculations' => 0,
            'totalReports' => 0,
            'totalLogins' => $tenant->users()->whereNotNull('last_login_at')->count(),
        ];
    }

    public function getRecentActivity(Firm $tenant, int $limit = 20): array
    {
        return [];
    }

    public function getBillingHistory(Firm $tenant): array
    {
        return $tenant->subscriptions()
            ->with('payments')
            ->latest()
            ->take(10)
            ->get()
            ->flatMap(fn ($sub) => $sub->payments)
            ->map(fn ($payment) => [
                'id' => $payment->id,
                'date' => $payment->created_at->toDateString(),
                'amount' => $payment->amount,
                'status' => $payment->status,
                'reference' => $payment->payment_reference,
            ])
            ->toArray();
    }

    public function getNotes(Firm $tenant): array
    {
        return $tenant->notes()
            ->with('admin:id,name')
            ->latest()
            ->take(20)
            ->get()
            ->toArray();
    }

    public function updateTenant(Firm $tenant, array $data): Firm
    {
        $tenant->update($data);

        $this->logAction('tenant.updated', $tenant, $data);

        return $tenant->fresh();
    }

    public function suspendTenant(Firm $tenant, string $reason): void
    {
        DB::transaction(function () use ($tenant, $reason) {
            $tenant->update(['status' => 'suspended']);

            $tenant->users()->update(['status' => 'suspended']);

            $this->logAction('tenant.suspended', $tenant, ['reason' => $reason]);
        });
    }

    public function reactivateTenant(Firm $tenant, string $reason): void
    {
        DB::transaction(function () use ($tenant, $reason) {
            $tenant->update(['status' => 'active']);

            $tenant->users()->where('status', 'suspended')->update(['status' => 'active']);

            $this->logAction('tenant.reactivated', $tenant, ['reason' => $reason]);
        });
    }

    public function extendTrial(Firm $tenant, int $days, string $reason): void
    {
        $oldDate = $tenant->trial_ends_at;
        $newDate = $tenant->trial_ends_at?->addDays($days) ?? now()->addDays($days);

        $tenant->update(['trial_ends_at' => $newDate]);

        $this->logAction('tenant.trial_extended', $tenant, [
            'days' => $days,
            'old_date' => $oldDate?->toDateString(),
            'new_date' => $newDate->toDateString(),
            'reason' => $reason,
        ]);
    }

    public function changePlan(Firm $tenant, string $planId, string $reason): void
    {
        $oldPlan = $tenant->plan;
        $newPlan = Plan::findOrFail($planId);

        DB::transaction(function () use ($tenant, $newPlan, $oldPlan, $reason) {
            $tenant->update(['plan_id' => $newPlan->id]);

            if ($tenant->activeSubscription) {
                $tenant->activeSubscription->update(['plan_id' => $newPlan->id]);
            }

            $this->logAction('tenant.plan_changed', $tenant, [
                'old_plan' => $oldPlan?->name,
                'new_plan' => $newPlan->name,
                'reason' => $reason,
            ]);
        });
    }

    public function applyCredit(Firm $tenant, int $amount, string $reason): void
    {
        DB::transaction(function () use ($tenant, $amount, $reason) {
            $tenant->credits()->create([
                'amount' => $amount,
                'reason' => $reason,
                'applied_by' => auth('admin')->id(),
            ]);

            $this->logAction('tenant.credit_applied', $tenant, [
                'amount' => $amount,
                'reason' => $reason,
            ]);
        });
    }

    public function getSubscriptionDetails(Firm $tenant): array
    {
        $subscription = $tenant->activeSubscription;

        if (!$subscription) {
            return [
                'plan_name' => $tenant->plan?->name ?? 'No Plan',
                'billing_cycle' => null,
                'current_price' => null,
                'next_billing_date' => null,
                'payment_method' => null,
                'payment_status' => null,
            ];
        }

        return [
            'plan_name' => $subscription->plan->name,
            'billing_cycle' => $subscription->billing_cycle,
            'current_price' => $subscription->billing_cycle === 'annual'
                ? $subscription->plan->annual_price
                : $subscription->plan->monthly_price,
            'next_billing_date' => $subscription->next_billing_at?->toDateString(),
            'payment_method' => $subscription->paymentMethod ? [
                'type' => $subscription->paymentMethod->type,
                'last_four' => $subscription->paymentMethod->last_four,
            ] : null,
            'payment_status' => $subscription->payment_status,
        ];
    }

    public function exportTenantData(Firm $tenant)
    {
        $this->logAction('tenant.data_exported', $tenant);

        $data = [
            'firm' => $tenant->toArray(),
            'users' => $tenant->users->toArray(),
            'tax_clients' => $tenant->taxClients->toArray(),
        ];

        return response()->json($data)
            ->header('Content-Disposition', "attachment; filename={$tenant->slug}-export.json");
    }

    public function deleteTenant(Firm $tenant, string $reason): void
    {
        $this->logAction('tenant.deleted', $tenant, ['reason' => $reason]);

        $tenant->delete();
    }

    private function logAction(string $action, Firm $tenant, array $metadata = []): void
    {
        AdminAuditLog::create([
            'admin_user_id' => auth('admin')->id(),
            'action' => $action,
            'auditable_type' => Firm::class,
            'auditable_id' => $tenant->id,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
```

### Tenant Notes Controller

**File:** `app/Http/Controllers/Admin/TenantNoteController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Firm;
use App\Models\TenantNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TenantNoteController extends Controller
{
    public function store(Request $request, Firm $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
            'category' => ['required', 'in:general,billing,technical,support'],
        ]);

        TenantNote::create([
            'firm_id' => $tenant->id,
            'admin_user_id' => auth('admin')->id(),
            'content' => $validated['content'],
            'category' => $validated['category'],
        ]);

        return back()->with('success', 'Note added successfully.');
    }

    public function destroy(Firm $tenant, TenantNote $note): RedirectResponse
    {
        $this->authorize('delete', $note);

        $note->delete();

        return back()->with('success', 'Note deleted.');
    }
}
```

### Impersonation Controller

**File:** `app/Http/Controllers/Admin/ImpersonationController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Firm;
use App\Models\FirmUser;
use App\Models\AdminAuditLog;
use App\Services\Admin\ImpersonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    public function __construct(
        private ImpersonationService $impersonationService
    ) {}

    public function start(Request $request, Firm $tenant, FirmUser $user): RedirectResponse
    {
        $admin = auth('admin')->user();

        if (!$admin->role->hasPermission('impersonate_firm_user')) {
            abort(403, 'You do not have permission to impersonate users.');
        }

        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $this->impersonationService->start($admin, $user, $request->string('reason'));

        return redirect()->route('app.dashboard');
    }

    public function stop(): RedirectResponse
    {
        $this->impersonationService->stop();

        return redirect()->route('admin.dashboard');
    }
}
```

### Impersonation Service

**File:** `app/Services/Admin/ImpersonationService.php`

```php
<?php

namespace App\Services\Admin;

use App\Models\AdminAuditLog;
use App\Models\AdminUser;
use App\Models\FirmUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ImpersonationService
{
    private const SESSION_KEY = 'impersonation';
    private const TIMEOUT_MINUTES = 30;

    public function start(AdminUser $admin, FirmUser $user, string $reason): void
    {
        $session = ImpersonationSession::create([
            'admin_user_id' => $admin->id,
            'firm_id' => $user->firm_id,
            'firm_user_id' => $user->id,
            'reason' => $reason,
            'ip_address' => request()->ip(),
            'started_at' => now(),
        ]);

        Session::put(self::SESSION_KEY, [
            'session_id' => $session->id,
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'firm_id' => $user->firm_id,
            'firm_name' => $user->firm->name,
            'started_at' => now()->toIso8601String(),
            'expires_at' => now()->addMinutes(self::TIMEOUT_MINUTES)->toIso8601String(),
            'reason' => $reason,
        ]);

        AdminAuditLog::create([
            'admin_user_id' => $admin->id,
            'action' => 'impersonation.started',
            'auditable_type' => FirmUser::class,
            'auditable_id' => $user->id,
            'metadata' => [
                'firm_id' => $user->firm_id,
                'reason' => $reason,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        Auth::guard('admin')->logout();
        Auth::guard('firm')->login($user);
    }

    public function stop(): void
    {
        $impersonation = Session::get(self::SESSION_KEY);

        if (!$impersonation) {
            return;
        }

        $session = ImpersonationSession::find($impersonation['session_id']);
        if ($session) {
            $session->update([
                'ended_at' => now(),
                'duration_seconds' => now()->diffInSeconds($impersonation['started_at']),
            ]);
        }

        AdminAuditLog::create([
            'admin_user_id' => $impersonation['admin_id'],
            'action' => 'impersonation.ended',
            'auditable_type' => FirmUser::class,
            'auditable_id' => $impersonation['user_id'],
            'metadata' => [
                'duration_seconds' => now()->diffInSeconds($impersonation['started_at']),
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        Auth::guard('firm')->logout();
        Session::forget(self::SESSION_KEY);

        $admin = AdminUser::find($impersonation['admin_id']);
        Auth::guard('admin')->login($admin);
    }

    public function isImpersonating(): bool
    {
        if (!Session::has(self::SESSION_KEY)) {
            return false;
        }

        $data = Session::get(self::SESSION_KEY);
        if (Carbon::parse($data['expires_at'])->isPast()) {
            $this->stop();
            return false;
        }

        return true;
    }

    public function getImpersonationData(): ?array
    {
        if (!$this->isImpersonating()) {
            return null;
        }
        return Session::get(self::SESSION_KEY);
    }

    public function refreshTimeout(): void
    {
        if (!Session::has(self::SESSION_KEY)) {
            return;
        }

        $data = Session::get(self::SESSION_KEY);
        $data['expires_at'] = now()->addMinutes(self::TIMEOUT_MINUTES)->toIso8601String();
        Session::put(self::SESSION_KEY, $data);
    }
}
```

### Impersonation Restriction Middleware

**File:** `app/Http/Middleware/BlockDuringImpersonation.php`

```php
<?php

namespace App\Http\Middleware;

use App\Services\Admin\ImpersonationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockDuringImpersonation
{
    public function __construct(
        private ImpersonationService $impersonationService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->impersonationService->isImpersonating()) {
            abort(403, 'This action is not allowed while impersonating a user.');
        }

        return $next($request);
    }
}
```

Apply this middleware to routes that should be blocked during impersonation:

```php
Route::middleware(['auth:firm', 'block.impersonation'])->group(function () {
    Route::put('profile/password', [PasswordController::class, 'update']);
    Route::put('profile/two-factor', [TwoFactorController::class, 'update']);
    Route::resource('billing', BillingController::class);
    Route::post('subscription/change-plan', [SubscriptionController::class, 'changePlan']);
    Route::post('subscription/cancel', [SubscriptionController::class, 'cancel']);
});
```

---

## Form Requests

### Update Tenant Request

**File:** `app/Http/Requests/Admin/UpdateTenantRequest.php`

```php
<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')->role->hasPermission('modify_tenant_settings');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'registration_number' => ['nullable', 'string', 'max:100'],
        ];
    }
}
```

### Tenant Action Request

**File:** `app/Http/Requests/Admin/TenantActionRequest.php`

```php
<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TenantActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')->role->hasPermission('suspend_activate_tenant');
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
```

---

## Routes

**File:** `routes/admin.php` (additions)

```php
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\TenantNoteController;
use App\Http\Controllers\Admin\ImpersonationController;

Route::middleware(['auth:admin', 'verified', 'two-factor'])->group(function () {
    Route::resource('tenants', TenantController::class)->except(['create', 'store']);

    Route::prefix('tenants')->name('admin.tenants.')->group(function () {
        Route::post('bulk-export', [TenantController::class, 'bulkExport'])->name('bulk-export');
        Route::post('bulk-communicate', [TenantController::class, 'bulkCommunicate'])->name('bulk-communicate');
    });

    Route::prefix('tenants/{tenant}')->name('admin.tenants.')->group(function () {
        Route::post('suspend', [TenantController::class, 'suspend'])->name('suspend');
        Route::post('reactivate', [TenantController::class, 'reactivate'])->name('reactivate');
        Route::post('extend-trial', [TenantController::class, 'extendTrial'])->name('extend-trial');
        Route::post('change-plan', [TenantController::class, 'changePlan'])->name('change-plan');
        Route::post('apply-credit', [TenantController::class, 'applyCredit'])->name('apply-credit');
        Route::get('export', [TenantController::class, 'export'])->name('export');

        Route::post('notes', [TenantNoteController::class, 'store'])->name('notes.store');
        Route::delete('notes/{note}', [TenantNoteController::class, 'destroy'])->name('notes.destroy');

        Route::post('users/{user}/impersonate', [ImpersonationController::class, 'start'])
            ->name('impersonate');
    });

    Route::post('impersonation/stop', [ImpersonationController::class, 'stop'])
        ->name('admin.impersonation.stop');
});
```

---

## Policy

**File:** `app/Policies/Admin/FirmPolicy.php`

```php
<?php

namespace App\Policies\Admin;

use App\Models\AdminUser;
use App\Models\Firm;

class FirmPolicy
{
    public function viewAny(AdminUser $admin): bool
    {
        return $admin->role->hasPermission('view_tenant_list');
    }

    public function view(AdminUser $admin, Firm $firm): bool
    {
        return $admin->role->hasPermission('view_tenant_details');
    }

    public function update(AdminUser $admin, Firm $firm): bool
    {
        return $admin->role->hasPermission('modify_tenant_settings');
    }

    public function suspend(AdminUser $admin, Firm $firm): bool
    {
        return $admin->role->hasPermission('suspend_activate_tenant');
    }

    public function reactivate(AdminUser $admin, Firm $firm): bool
    {
        return $admin->role->hasPermission('suspend_activate_tenant');
    }

    public function changePlan(AdminUser $admin, Firm $firm): bool
    {
        return $admin->role->hasPermission('manage_subscription_plans');
    }

    public function applyCredit(AdminUser $admin, Firm $firm): bool
    {
        return $admin->role->hasPermission('manage_billing');
    }

    public function export(AdminUser $admin, Firm $firm): bool
    {
        return $admin->role->hasPermission('export_data');
    }

    public function delete(AdminUser $admin, Firm $firm): bool
    {
        return $admin->isSuperAdmin();
    }
}
```

---

## Frontend Components

### Tenant List Page

**File:** `resources/js/pages/Admin/Tenants/Index.tsx`

```tsx
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/layouts/AdminLayout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { useState } from 'react';
import { useDebouncedCallback } from 'use-debounce';

interface Tenant {
    id: string;
    name: string;
    email: string;
    status: 'trial' | 'active' | 'suspended' | 'churned';
    plan?: { name: string };
    users_count: number;
    tax_clients_count: number;
    created_at: string;
    last_activity?: string;
}

interface Props {
    tenants: {
        data: Tenant[];
        meta: { current_page: number; last_page: number; total: number };
    };
    filters: Record<string, string>;
    plans: Array<{ id: string; name: string }>;
    statuses: string[];
}

export default function TenantsIndex({ tenants, filters, plans, statuses }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    const debouncedSearch = useDebouncedCallback((value: string) => {
        router.get('/admin/tenants', { ...filters, search: value }, { preserveState: true });
    }, 300);

    const handleSearch = (value: string) => {
        setSearch(value);
        debouncedSearch(value);
    };

    const handleFilter = (key: string, value: string) => {
        router.get('/admin/tenants', { ...filters, [key]: value }, { preserveState: true });
    };

    const getStatusBadge = (status: Tenant['status']) => {
        const variants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
            active: 'default',
            trial: 'secondary',
            suspended: 'destructive',
            churned: 'outline',
        };
        return <Badge variant={variants[status]}>{status}</Badge>;
    };

    return (
        <AdminLayout>
            <Head title="Tenants" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Tenants</h1>
                </div>

                <div className="flex gap-4">
                    <Input
                        placeholder="Search by name or email..."
                        value={search}
                        onChange={(e) => handleSearch(e.target.value)}
                        className="max-w-sm"
                    />

                    <Select
                        value={filters.status ?? ''}
                        onValueChange={(value) => handleFilter('status', value)}
                    >
                        <SelectTrigger className="w-[150px]">
                            <SelectValue placeholder="Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Statuses</SelectItem>
                            {statuses.map((status) => (
                                <SelectItem key={status} value={status}>
                                    {status}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <Select
                        value={filters.plan ?? ''}
                        onValueChange={(value) => handleFilter('plan', value)}
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
                </div>

                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Firm</TableHead>
                                <TableHead>Plan</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Clients</TableHead>
                                <TableHead>Users</TableHead>
                                <TableHead>Created</TableHead>
                                <TableHead></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {tenants.data.map((tenant) => (
                                <TableRow key={tenant.id}>
                                    <TableCell>
                                        <div>
                                            <div className="font-medium">{tenant.name}</div>
                                            <div className="text-sm text-gray-500">{tenant.email}</div>
                                        </div>
                                    </TableCell>
                                    <TableCell>{tenant.plan?.name ?? '-'}</TableCell>
                                    <TableCell>{getStatusBadge(tenant.status)}</TableCell>
                                    <TableCell>{tenant.tax_clients_count}</TableCell>
                                    <TableCell>{tenant.users_count}</TableCell>
                                    <TableCell>
                                        {new Date(tenant.created_at).toLocaleDateString()}
                                    </TableCell>
                                    <TableCell>
                                        <Link href={`/admin/tenants/${tenant.id}`}>
                                            <Button variant="ghost" size="sm">
                                                View
                                            </Button>
                                        </Link>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </AdminLayout>
    );
}
```

### Tenant Detail Page

**File:** `resources/js/pages/Admin/Tenants/Show.tsx`

```tsx
import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '@/layouts/AdminLayout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { useState } from 'react';

interface Props {
    tenant: {
        id: string;
        name: string;
        email: string;
        phone?: string;
        address?: string;
        registration_number?: string;
        status: string;
        plan?: { id: string; name: string };
        trial_ends_at?: string;
        subscription_ends_at?: string;
        created_at: string;
        users: Array<{
            id: string;
            name: string;
            email: string;
            role: string;
            status: string;
            last_login_at?: string;
        }>;
    };
    usage: {
        clients: { current: number; limit: number };
        users: { current: number; limit: number };
        reports: { current: number; limit: number };
    };
    stats: Record<string, unknown>;
    recentActivity: Array<unknown>;
    billingHistory: Array<unknown>;
    notes: Array<{
        id: string;
        content: string;
        category: string;
        admin: { name: string };
        created_at: string;
    }>;
}

export default function TenantShow({ tenant, usage, stats, notes }: Props) {
    const [actionReason, setActionReason] = useState('');

    const suspendForm = useForm({ reason: '' });
    const reactivateForm = useForm({ reason: '' });

    const handleSuspend = () => {
        suspendForm.post(`/admin/tenants/${tenant.id}/suspend`);
    };

    const handleReactivate = () => {
        reactivateForm.post(`/admin/tenants/${tenant.id}/reactivate`);
    };

    return (
        <AdminLayout>
            <Head title={`Tenant: ${tenant.name}`} />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">{tenant.name}</h1>
                        <p className="text-gray-500">{tenant.email}</p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Badge>{tenant.status}</Badge>

                        <Link href={`/admin/tenants/${tenant.id}/edit`}>
                            <Button variant="outline">Edit</Button>
                        </Link>

                        {tenant.status === 'active' && (
                            <AlertDialog>
                                <AlertDialogTrigger asChild>
                                    <Button variant="destructive">Suspend</Button>
                                </AlertDialogTrigger>
                                <AlertDialogContent>
                                    <AlertDialogHeader>
                                        <AlertDialogTitle>Suspend Tenant</AlertDialogTitle>
                                        <AlertDialogDescription>
                                            This will suspend access for all users in this firm.
                                        </AlertDialogDescription>
                                    </AlertDialogHeader>
                                    <div className="py-4">
                                        <Label>Reason for suspension</Label>
                                        <Textarea
                                            value={suspendForm.data.reason}
                                            onChange={(e) => suspendForm.setData('reason', e.target.value)}
                                            placeholder="Enter reason..."
                                        />
                                    </div>
                                    <AlertDialogFooter>
                                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                                        <AlertDialogAction onClick={handleSuspend}>
                                            Suspend
                                        </AlertDialogAction>
                                    </AlertDialogFooter>
                                </AlertDialogContent>
                            </AlertDialog>
                        )}

                        {tenant.status === 'suspended' && (
                            <AlertDialog>
                                <AlertDialogTrigger asChild>
                                    <Button>Reactivate</Button>
                                </AlertDialogTrigger>
                                <AlertDialogContent>
                                    <AlertDialogHeader>
                                        <AlertDialogTitle>Reactivate Tenant</AlertDialogTitle>
                                    </AlertDialogHeader>
                                    <div className="py-4">
                                        <Label>Reason for reactivation</Label>
                                        <Textarea
                                            value={reactivateForm.data.reason}
                                            onChange={(e) => reactivateForm.setData('reason', e.target.value)}
                                            placeholder="Enter reason..."
                                        />
                                    </div>
                                    <AlertDialogFooter>
                                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                                        <AlertDialogAction onClick={handleReactivate}>
                                            Reactivate
                                        </AlertDialogAction>
                                    </AlertDialogFooter>
                                </AlertDialogContent>
                            </AlertDialog>
                        )}
                    </div>
                </div>

                <Tabs defaultValue="overview">
                    <TabsList>
                        <TabsTrigger value="overview">Overview</TabsTrigger>
                        <TabsTrigger value="users">Users</TabsTrigger>
                        <TabsTrigger value="billing">Billing</TabsTrigger>
                        <TabsTrigger value="notes">Notes</TabsTrigger>
                    </TabsList>

                    <TabsContent value="overview" className="space-y-6">
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-sm">Clients</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-2xl font-bold">
                                        {usage.clients.current} / {usage.clients.limit || '∞'}
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-sm">Users</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-2xl font-bold">
                                        {usage.users.current} / {usage.users.limit || '∞'}
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-sm">Reports This Month</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-2xl font-bold">
                                        {usage.reports.current} / {usage.reports.limit || '∞'}
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <Card>
                            <CardHeader>
                                <CardTitle>Details</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <dl className="grid grid-cols-2 gap-4">
                                    <div>
                                        <dt className="text-sm text-gray-500">Plan</dt>
                                        <dd>{tenant.plan?.name ?? 'No plan'}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm text-gray-500">Registration Number</dt>
                                        <dd>{tenant.registration_number ?? '-'}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm text-gray-500">Phone</dt>
                                        <dd>{tenant.phone ?? '-'}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm text-gray-500">Created</dt>
                                        <dd>{new Date(tenant.created_at).toLocaleDateString()}</dd>
                                    </div>
                                </dl>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="users">
                        <Card>
                            <CardHeader>
                                <CardTitle>Firm Users</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    {tenant.users.map((user) => (
                                        <div
                                            key={user.id}
                                            className="flex items-center justify-between border-b pb-4"
                                        >
                                            <div>
                                                <div className="font-medium">{user.name}</div>
                                                <div className="text-sm text-gray-500">{user.email}</div>
                                            </div>
                                            <div className="flex items-center gap-4">
                                                <Badge variant="outline">{user.role}</Badge>
                                                <Badge
                                                    variant={user.status === 'active' ? 'default' : 'secondary'}
                                                >
                                                    {user.status}
                                                </Badge>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="billing">
                        <Card>
                            <CardHeader>
                                <CardTitle>Billing History</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-gray-500">No billing history available.</p>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="notes">
                        <Card>
                            <CardHeader>
                                <CardTitle>Internal Notes</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    {notes.map((note) => (
                                        <div key={note.id} className="border-b pb-4">
                                            <div className="flex items-center justify-between mb-2">
                                                <Badge variant="outline">{note.category}</Badge>
                                                <span className="text-sm text-gray-500">
                                                    {note.admin.name} -{' '}
                                                    {new Date(note.created_at).toLocaleString()}
                                                </span>
                                            </div>
                                            <p>{note.content}</p>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </AdminLayout>
    );
}
```

---

## Testing

```php
test('admin can view tenant list', function () {
    $admin = AdminUser::factory()->create(['role' => 'admin']);
    Firm::factory()->count(5)->create();

    $this->actingAs($admin, 'admin')
        ->get('/admin/tenants')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Tenants/Index')
            ->has('tenants.data', 5)
        );
});

test('admin can view tenant details', function () {
    $admin = AdminUser::factory()->create(['role' => 'admin']);
    $tenant = Firm::factory()->create();

    $this->actingAs($admin, 'admin')
        ->get("/admin/tenants/{$tenant->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Tenants/Show')
            ->where('tenant.id', $tenant->id)
        );
});

test('admin can suspend tenant', function () {
    $admin = AdminUser::factory()->create(['role' => 'admin']);
    $tenant = Firm::factory()->create(['status' => 'active']);

    $this->actingAs($admin, 'admin')
        ->post("/admin/tenants/{$tenant->id}/suspend", [
            'reason' => 'Non-payment',
        ])
        ->assertRedirect();

    expect($tenant->fresh()->status)->toBe('suspended');
});

test('support cannot suspend tenant', function () {
    $support = AdminUser::factory()->create(['role' => 'support']);
    $tenant = Firm::factory()->create(['status' => 'active']);

    $this->actingAs($support, 'admin')
        ->post("/admin/tenants/{$tenant->id}/suspend", [
            'reason' => 'Test',
        ])
        ->assertForbidden();
});

test('only super admin can impersonate users', function () {
    $admin = AdminUser::factory()->create(['role' => 'admin']);
    $superAdmin = AdminUser::factory()->create(['role' => 'super_admin']);
    $tenant = Firm::factory()->create();
    $user = FirmUser::factory()->create(['firm_id' => $tenant->id]);

    $this->actingAs($admin, 'admin')
        ->post("/admin/tenants/{$tenant->id}/users/{$user->id}/impersonate", [
            'reason' => 'Support request',
        ])
        ->assertForbidden();

    $this->actingAs($superAdmin, 'admin')
        ->post("/admin/tenants/{$tenant->id}/users/{$user->id}/impersonate", [
            'reason' => 'Support request',
        ])
        ->assertRedirect('/app/dashboard');
});
```
