# TaxLab Implementation - Plan Management

## Overview

Allow administrators to configure subscription plans with flexible pricing, limits, and feature toggles.

Reference: `docs/04_PLATFORM_ADMIN_MODULES.md` Section 4

---

## Features

### Plan List View
- Display all plans with key info
- Reorder plans (display order)
- Quick status toggle

### Plan Configuration
- Basic information (name, pricing, description)
- Usage limits (clients, users, reports, storage)
- Feature toggles (scenario modeling, client portal, etc.)
- Deprecation workflow

---

## Data Model

### Plan Limits

**File:** `database/migrations/xxxx_xx_xx_create_plan_limits_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_limits', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('plan_id')->constrained()->cascadeOnDelete();
            $table->string('limit_key', 50);
            $table->integer('limit_value')->default(-1);
            $table->timestamps();

            $table->unique(['plan_id', 'limit_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_limits');
    }
};
```

### Limit Keys

| Key | Description | -1 means |
|-----|-------------|----------|
| `max_clients` | Maximum tax clients | Unlimited |
| `max_users` | Maximum firm users | Unlimited |
| `max_reports_monthly` | Reports per month | Unlimited |
| `max_storage_mb` | File storage in MB | Unlimited |

### Feature Keys

| Key | Description |
|-----|-------------|
| `scenario_modeling` | Access to scenario features |
| `advanced_scenarios` | Multi-parameter scenarios |
| `report_approval_workflow` | Approval before finalization |
| `client_portal` | Enable client-facing portal |
| `api_access` | API key generation |
| `priority_support` | Faster response SLA |
| `cpd_access` | Access to training courses |
| `knowledge_base` | Access to documentation |
| `analytics_dashboard` | Firm-level analytics |
| `bulk_import` | CSV/Excel client import |
| `custom_report_templates` | Create custom templates |

---

## Backend Implementation

### Plan Controller

**File:** `app/Http/Controllers/Admin/PlanController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePlanRequest;
use App\Http\Requests\Admin\UpdatePlanRequest;
use App\Models\Plan;
use App\Services\Admin\PlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public function __construct(
        private PlanService $planService
    ) {}

    public function index(): Response
    {
        $plans = Plan::withCount('firms')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Admin/Plans/Index', [
            'plans' => $plans,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Plans/Create', [
            'limitKeys' => $this->planService->getLimitKeys(),
            'featureKeys' => $this->planService->getFeatureKeys(),
        ]);
    }

    public function store(StorePlanRequest $request): RedirectResponse
    {
        $plan = $this->planService->createPlan($request->validated());

        return redirect()
            ->route('admin.plans.show', $plan)
            ->with('success', 'Plan created successfully.');
    }

    public function show(Plan $plan): Response
    {
        $plan->load(['limits', 'features']);
        $plan->loadCount('firms');

        return Inertia::render('Admin/Plans/Show', [
            'plan' => $plan,
            'activeTenants' => $plan->firms()->where('status', 'active')->count(),
        ]);
    }

    public function edit(Plan $plan): Response
    {
        $plan->load(['limits', 'features']);

        return Inertia::render('Admin/Plans/Edit', [
            'plan' => $plan,
            'limitKeys' => $this->planService->getLimitKeys(),
            'featureKeys' => $this->planService->getFeatureKeys(),
        ]);
    }

    public function update(UpdatePlanRequest $request, Plan $plan): RedirectResponse
    {
        $this->planService->updatePlan($plan, $request->validated());

        return redirect()
            ->route('admin.plans.show', $plan)
            ->with('success', 'Plan updated successfully.');
    }

    public function deprecate(Plan $plan): RedirectResponse
    {
        $this->authorize('deprecate', $plan);

        $this->planService->deprecatePlan($plan);

        return back()->with('success', 'Plan deprecated. No new signups allowed.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $request->validate([
            'plans' => ['required', 'array'],
            'plans.*.id' => ['required', 'exists:plans,id'],
            'plans.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $this->planService->reorderPlans($request->array('plans'));

        return back()->with('success', 'Plan order updated.');
    }
}
```

### Plan Service

**File:** `app/Services/Admin/PlanService.php`

```php
<?php

namespace App\Services\Admin;

use App\Models\Plan;
use App\Models\PlanLimit;
use App\Models\PlanFeature;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlanService
{
    public function getLimitKeys(): array
    {
        return [
            ['key' => 'max_clients', 'label' => 'Maximum Clients', 'type' => 'number'],
            ['key' => 'max_users', 'label' => 'Maximum Users', 'type' => 'number'],
            ['key' => 'max_reports_monthly', 'label' => 'Reports Per Month', 'type' => 'number'],
            ['key' => 'max_storage_mb', 'label' => 'Storage (MB)', 'type' => 'number'],
        ];
    }

    public function getFeatureKeys(): array
    {
        return [
            ['key' => 'scenario_modeling', 'label' => 'Scenario Modeling', 'description' => 'Access to scenario features'],
            ['key' => 'advanced_scenarios', 'label' => 'Advanced Scenarios', 'description' => 'Multi-parameter scenarios'],
            ['key' => 'report_approval_workflow', 'label' => 'Report Approval Workflow', 'description' => 'Approval before finalization'],
            ['key' => 'client_portal', 'label' => 'Client Portal', 'description' => 'Enable client-facing portal'],
            ['key' => 'api_access', 'label' => 'API Access', 'description' => 'API key generation'],
            ['key' => 'priority_support', 'label' => 'Priority Support', 'description' => 'Faster response SLA'],
            ['key' => 'cpd_access', 'label' => 'CPD Access', 'description' => 'Access to training courses'],
            ['key' => 'knowledge_base', 'label' => 'Knowledge Base', 'description' => 'Access to documentation'],
            ['key' => 'analytics_dashboard', 'label' => 'Analytics Dashboard', 'description' => 'Firm-level analytics'],
            ['key' => 'bulk_import', 'label' => 'Bulk Import', 'description' => 'CSV/Excel client import'],
            ['key' => 'custom_report_templates', 'label' => 'Custom Report Templates', 'description' => 'Create custom templates'],
        ];
    }

    public function createPlan(array $data): Plan
    {
        return DB::transaction(function () use ($data) {
            $plan = Plan::create([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? Str::slug($data['name']),
                'description' => $data['description'] ?? null,
                'monthly_price' => $data['monthly_price'],
                'annual_price' => $data['annual_price'],
                'is_active' => $data['is_active'] ?? true,
                'is_featured' => $data['is_featured'] ?? false,
                'sort_order' => $data['sort_order'] ?? Plan::max('sort_order') + 1,
            ]);

            $this->syncLimits($plan, $data['limits'] ?? []);
            $this->syncFeatures($plan, $data['features'] ?? []);

            return $plan;
        });
    }

    public function updatePlan(Plan $plan, array $data): Plan
    {
        return DB::transaction(function () use ($plan, $data) {
            $plan->update([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? $plan->slug,
                'description' => $data['description'] ?? null,
                'monthly_price' => $data['monthly_price'],
                'annual_price' => $data['annual_price'],
                'is_active' => $data['is_active'] ?? $plan->is_active,
                'is_featured' => $data['is_featured'] ?? false,
                'sort_order' => $data['sort_order'] ?? $plan->sort_order,
            ]);

            if (isset($data['limits'])) {
                $this->syncLimits($plan, $data['limits']);
            }

            if (isset($data['features'])) {
                $this->syncFeatures($plan, $data['features']);
            }

            return $plan->fresh();
        });
    }

    private function syncLimits(Plan $plan, array $limits): void
    {
        $plan->limits()->delete();

        foreach ($limits as $limit) {
            if (isset($limit['key']) && isset($limit['value'])) {
                PlanLimit::create([
                    'plan_id' => $plan->id,
                    'limit_key' => $limit['key'],
                    'limit_value' => $limit['value'],
                ]);
            }
        }
    }

    private function syncFeatures(Plan $plan, array $features): void
    {
        $plan->features()->delete();

        foreach ($features as $feature) {
            if (isset($feature['key'])) {
                PlanFeature::create([
                    'plan_id' => $plan->id,
                    'feature_key' => $feature['key'],
                    'is_enabled' => $feature['enabled'] ?? true,
                ]);
            }
        }
    }

    public function deprecatePlan(Plan $plan): void
    {
        $plan->update(['is_active' => false]);
    }

    public function reorderPlans(array $plans): void
    {
        foreach ($plans as $planData) {
            Plan::where('id', $planData['id'])
                ->update(['sort_order' => $planData['sort_order']]);
        }
    }

    public function getPlanUsageForFirm(Plan $plan, string $firmId): array
    {
        return [];
    }
}
```

### Plan Limit Model

**File:** `app/Models/PlanLimit.php`

```php
<?php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanLimit extends Model
{
    use HasFactory, HasUlid;

    protected $fillable = [
        'plan_id',
        'limit_key',
        'limit_value',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isUnlimited(): bool
    {
        return $this->limit_value === -1;
    }
}
```

### Plan Feature Model

**File:** `app/Models/PlanFeature.php`

```php
<?php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanFeature extends Model
{
    use HasFactory, HasUlid;

    protected $fillable = [
        'plan_id',
        'feature_key',
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
```

### Update Plan Model

**File:** `app/Models/Plan.php` (additions)

```php
public function limits(): HasMany
{
    return $this->hasMany(PlanLimit::class);
}

public function features(): HasMany
{
    return $this->hasMany(PlanFeature::class);
}

public function getLimit(string $key): int
{
    return $this->limits->firstWhere('limit_key', $key)?->limit_value ?? -1;
}

public function hasFeature(string $key): bool
{
    return $this->features->firstWhere('feature_key', $key)?->is_enabled ?? false;
}

public function isUnlimited(string $key): bool
{
    return $this->getLimit($key) === -1;
}
```

---

## Form Requests

### Store Plan Request

**File:** `app/Http/Requests/Admin/StorePlanRequest.php`

```php
<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')->role->hasPermission('manage_subscription_plans');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:50', 'unique:plans,slug'],
            'description' => ['nullable', 'string', 'max:500'],
            'monthly_price' => ['required', 'integer', 'min:0'],
            'annual_price' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'limits' => ['nullable', 'array'],
            'limits.*.key' => ['required_with:limits', 'string'],
            'limits.*.value' => ['required_with:limits', 'integer', 'min:-1'],
            'features' => ['nullable', 'array'],
            'features.*.key' => ['required_with:features', 'string'],
            'features.*.enabled' => ['boolean'],
        ];
    }
}
```

### Update Plan Request

**File:** `app/Http/Requests/Admin/UpdatePlanRequest.php`

```php
<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin')->role->hasPermission('manage_subscription_plans');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:50', Rule::unique('plans')->ignore($this->plan)],
            'description' => ['nullable', 'string', 'max:500'],
            'monthly_price' => ['required', 'integer', 'min:0'],
            'annual_price' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'limits' => ['nullable', 'array'],
            'limits.*.key' => ['required_with:limits', 'string'],
            'limits.*.value' => ['required_with:limits', 'integer', 'min:-1'],
            'features' => ['nullable', 'array'],
            'features.*.key' => ['required_with:features', 'string'],
            'features.*.enabled' => ['boolean'],
        ];
    }
}
```

---

## Routes

**File:** `routes/admin.php` (additions)

```php
use App\Http\Controllers\Admin\PlanController;

Route::middleware(['auth:admin', 'verified', 'two-factor'])->group(function () {
    Route::resource('plans', PlanController::class);
    Route::post('plans/{plan}/deprecate', [PlanController::class, 'deprecate'])->name('admin.plans.deprecate');
    Route::post('plans/reorder', [PlanController::class, 'reorder'])->name('admin.plans.reorder');
});
```

---

## Policy

**File:** `app/Policies/Admin/PlanPolicy.php`

```php
<?php

namespace App\Policies\Admin;

use App\Models\AdminUser;
use App\Models\Plan;

class PlanPolicy
{
    public function viewAny(AdminUser $admin): bool
    {
        return $admin->role->hasPermission('view_platform_dashboard');
    }

    public function view(AdminUser $admin, Plan $plan): bool
    {
        return $admin->role->hasPermission('view_platform_dashboard');
    }

    public function create(AdminUser $admin): bool
    {
        return $admin->role->hasPermission('manage_subscription_plans');
    }

    public function update(AdminUser $admin, Plan $plan): bool
    {
        return $admin->role->hasPermission('manage_subscription_plans');
    }

    public function deprecate(AdminUser $admin, Plan $plan): bool
    {
        return $admin->role->hasPermission('manage_subscription_plans');
    }

    public function delete(AdminUser $admin, Plan $plan): bool
    {
        return $admin->isSuperAdmin() && $plan->firms()->count() === 0;
    }
}
```

---

## Frontend Components

### Plan List Page

**File:** `resources/js/pages/Admin/Plans/Index.tsx`

```tsx
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/layouts/AdminLayout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { GripVertical, Plus } from 'lucide-react';

interface Plan {
    id: string;
    name: string;
    slug: string;
    monthly_price: number;
    annual_price: number;
    is_active: boolean;
    is_featured: boolean;
    sort_order: number;
    firms_count: number;
}

interface Props {
    plans: Plan[];
}

export default function PlansIndex({ plans }: Props) {
    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('en-NG', {
            style: 'currency',
            currency: 'NGN',
            minimumFractionDigits: 0,
        }).format(amount / 100);
    };

    return (
        <AdminLayout>
            <Head title="Subscription Plans" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Subscription Plans</h1>
                    <Link href="/admin/plans/create">
                        <Button>
                            <Plus className="h-4 w-4 mr-2" />
                            Create Plan
                        </Button>
                    </Link>
                </div>

                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead className="w-12"></TableHead>
                                <TableHead>Plan</TableHead>
                                <TableHead>Monthly</TableHead>
                                <TableHead>Annual</TableHead>
                                <TableHead>Tenants</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {plans.map((plan) => (
                                <TableRow key={plan.id}>
                                    <TableCell>
                                        <GripVertical className="h-4 w-4 text-gray-400 cursor-grab" />
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex items-center gap-2">
                                            <span className="font-medium">{plan.name}</span>
                                            {plan.is_featured && (
                                                <Badge variant="secondary">Featured</Badge>
                                            )}
                                        </div>
                                    </TableCell>
                                    <TableCell>{formatCurrency(plan.monthly_price)}</TableCell>
                                    <TableCell>{formatCurrency(plan.annual_price)}</TableCell>
                                    <TableCell>{plan.firms_count}</TableCell>
                                    <TableCell>
                                        <Badge variant={plan.is_active ? 'default' : 'secondary'}>
                                            {plan.is_active ? 'Active' : 'Deprecated'}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <Link href={`/admin/plans/${plan.id}`}>
                                            <Button variant="ghost" size="sm">
                                                View
                                            </Button>
                                        </Link>
                                        <Link href={`/admin/plans/${plan.id}/edit`}>
                                            <Button variant="ghost" size="sm">
                                                Edit
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

### Plan Form Component

**File:** `resources/js/pages/Admin/Plans/components/PlanForm.tsx`

```tsx
import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';

interface LimitKey {
    key: string;
    label: string;
    type: string;
}

interface FeatureKey {
    key: string;
    label: string;
    description: string;
}

interface Props {
    plan?: {
        id: string;
        name: string;
        slug: string;
        description?: string;
        monthly_price: number;
        annual_price: number;
        is_active: boolean;
        is_featured: boolean;
        limits: Array<{ limit_key: string; limit_value: number }>;
        features: Array<{ feature_key: string; is_enabled: boolean }>;
    };
    limitKeys: LimitKey[];
    featureKeys: FeatureKey[];
}

export default function PlanForm({ plan, limitKeys, featureKeys }: Props) {
    const isEditing = !!plan;

    const initialLimits = limitKeys.map((lk) => ({
        key: lk.key,
        value: plan?.limits.find((l) => l.limit_key === lk.key)?.limit_value ?? -1,
    }));

    const initialFeatures = featureKeys.map((fk) => ({
        key: fk.key,
        enabled: plan?.features.find((f) => f.feature_key === fk.key)?.is_enabled ?? false,
    }));

    const form = useForm({
        name: plan?.name ?? '',
        slug: plan?.slug ?? '',
        description: plan?.description ?? '',
        monthly_price: plan?.monthly_price ?? 0,
        annual_price: plan?.annual_price ?? 0,
        is_active: plan?.is_active ?? true,
        is_featured: plan?.is_featured ?? false,
        limits: initialLimits,
        features: initialFeatures,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEditing) {
            form.put(`/admin/plans/${plan.id}`);
        } else {
            form.post('/admin/plans');
        }
    };

    const updateLimit = (key: string, value: number) => {
        const limits = form.data.limits.map((l) =>
            l.key === key ? { ...l, value } : l
        );
        form.setData('limits', limits);
    };

    const toggleFeature = (key: string) => {
        const features = form.data.features.map((f) =>
            f.key === key ? { ...f, enabled: !f.enabled } : f
        );
        form.setData('features', features);
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Basic Information</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="name">Plan Name</Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                                placeholder="e.g., Professional"
                            />
                            {form.errors.name && (
                                <p className="text-sm text-red-500">{form.errors.name}</p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="slug">Slug</Label>
                            <Input
                                id="slug"
                                value={form.data.slug}
                                onChange={(e) => form.setData('slug', e.target.value)}
                                placeholder="professional"
                            />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="description">Description</Label>
                        <Textarea
                            id="description"
                            value={form.data.description}
                            onChange={(e) => form.setData('description', e.target.value)}
                            placeholder="Brief description for marketing..."
                        />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="monthly_price">Monthly Price (kobo)</Label>
                            <Input
                                id="monthly_price"
                                type="number"
                                value={form.data.monthly_price}
                                onChange={(e) => form.setData('monthly_price', parseInt(e.target.value) || 0)}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="annual_price">Annual Price (kobo)</Label>
                            <Input
                                id="annual_price"
                                type="number"
                                value={form.data.annual_price}
                                onChange={(e) => form.setData('annual_price', parseInt(e.target.value) || 0)}
                            />
                        </div>
                    </div>

                    <div className="flex items-center gap-6">
                        <div className="flex items-center gap-2">
                            <Switch
                                checked={form.data.is_active}
                                onCheckedChange={(checked) => form.setData('is_active', checked)}
                            />
                            <Label>Active (available for signups)</Label>
                        </div>
                        <div className="flex items-center gap-2">
                            <Switch
                                checked={form.data.is_featured}
                                onCheckedChange={(checked) => form.setData('is_featured', checked)}
                            />
                            <Label>Featured (highlighted in UI)</Label>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Usage Limits</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid grid-cols-2 gap-4">
                        {limitKeys.map((lk) => {
                            const limit = form.data.limits.find((l) => l.key === lk.key);
                            return (
                                <div key={lk.key} className="space-y-2">
                                    <Label>{lk.label}</Label>
                                    <Input
                                        type="number"
                                        value={limit?.value ?? -1}
                                        onChange={(e) => updateLimit(lk.key, parseInt(e.target.value) || -1)}
                                        min={-1}
                                    />
                                    <p className="text-xs text-gray-500">-1 = unlimited</p>
                                </div>
                            );
                        })}
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Features</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="space-y-4">
                        {featureKeys.map((fk) => {
                            const feature = form.data.features.find((f) => f.key === fk.key);
                            return (
                                <div key={fk.key} className="flex items-center justify-between">
                                    <div>
                                        <div className="font-medium">{fk.label}</div>
                                        <div className="text-sm text-gray-500">{fk.description}</div>
                                    </div>
                                    <Switch
                                        checked={feature?.enabled ?? false}
                                        onCheckedChange={() => toggleFeature(fk.key)}
                                    />
                                </div>
                            );
                        })}
                    </div>
                </CardContent>
            </Card>

            <div className="flex justify-end gap-4">
                <Button type="button" variant="outline" onClick={() => window.history.back()}>
                    Cancel
                </Button>
                <Button type="submit" disabled={form.processing}>
                    {isEditing ? 'Update Plan' : 'Create Plan'}
                </Button>
            </div>
        </form>
    );
}
```

---

## Testing

```php
test('super admin can create plan', function () {
    $admin = AdminUser::factory()->create(['role' => 'super_admin']);

    $this->actingAs($admin, 'admin')
        ->post('/admin/plans', [
            'name' => 'Enterprise',
            'monthly_price' => 10000000,
            'annual_price' => 100000000,
            'limits' => [
                ['key' => 'max_clients', 'value' => -1],
                ['key' => 'max_users', 'value' => 50],
            ],
            'features' => [
                ['key' => 'scenario_modeling', 'enabled' => true],
                ['key' => 'client_portal', 'enabled' => true],
            ],
        ])
        ->assertRedirect();

    expect(Plan::where('name', 'Enterprise')->exists())->toBeTrue();
});

test('admin cannot create plan', function () {
    $admin = AdminUser::factory()->create(['role' => 'admin']);

    $this->actingAs($admin, 'admin')
        ->post('/admin/plans', [
            'name' => 'Test',
            'monthly_price' => 1000,
            'annual_price' => 10000,
        ])
        ->assertForbidden();
});

test('plan limits are stored correctly', function () {
    $admin = AdminUser::factory()->create(['role' => 'super_admin']);

    $this->actingAs($admin, 'admin')
        ->post('/admin/plans', [
            'name' => 'Starter',
            'monthly_price' => 1500000,
            'annual_price' => 15000000,
            'limits' => [
                ['key' => 'max_clients', 'value' => 30],
                ['key' => 'max_users', 'value' => 1],
            ],
        ])
        ->assertRedirect();

    $plan = Plan::where('name', 'Starter')->first();
    expect($plan->getLimit('max_clients'))->toBe(30);
    expect($plan->getLimit('max_users'))->toBe(1);
    expect($plan->isUnlimited('max_reports_monthly'))->toBeTrue();
});

test('plan features are stored correctly', function () {
    $admin = AdminUser::factory()->create(['role' => 'super_admin']);

    $this->actingAs($admin, 'admin')
        ->post('/admin/plans', [
            'name' => 'Pro',
            'monthly_price' => 4500000,
            'annual_price' => 45000000,
            'features' => [
                ['key' => 'scenario_modeling', 'enabled' => true],
                ['key' => 'client_portal', 'enabled' => false],
            ],
        ])
        ->assertRedirect();

    $plan = Plan::where('name', 'Pro')->first();
    expect($plan->hasFeature('scenario_modeling'))->toBeTrue();
    expect($plan->hasFeature('client_portal'))->toBeFalse();
});
```
