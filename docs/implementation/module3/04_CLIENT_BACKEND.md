# TaxLab Implementation - Client Management Backend

## Overview

This document covers the backend implementation for Client Management, including controllers, services, form requests, policies, and import functionality.

Reference: `docs/06_CLIENT_MANAGEMENT.md`

---

## Client Controller

**File:** `app/Http/Controllers/App/ClientController.php`

```php
<?php

namespace App\Http\Controllers\App;

use App\Enums\ActivityAction;
use App\Enums\ClientStatus;
use App\Enums\EntityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\BulkClientActionRequest;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Models\Industry;
use App\Models\TaxClient;
use App\Services\Activity\ActivityService;
use App\Services\Client\ClientAccessService;
use App\Services\Client\ClientService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function __construct(
        private ClientService $clientService,
        private ClientAccessService $accessService,
        private ActivityService $activityService
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $filters = $request->only([
            'search',
            'entity_type',
            'industry_id',
            'status',
            'assigned_to',
            'sort_by',
            'sort_dir',
        ]);

        $clients = $this->clientService->search($user, $filters);

        return Inertia::render('App/Clients/Index', [
            'clients' => $clients,
            'filters' => $filters,
            'entityTypes' => EntityType::options(),
            'industries' => Industry::active()->ordered()->get(['id', 'sector', 'name']),
            'teamMembers' => $this->getTeamMembersForFilter($user),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', TaxClient::class);

        $user = $request->user();

        return Inertia::render('App/Clients/Create', [
            'entityTypes' => EntityType::options(),
            'industries' => Industry::active()->ordered()->get(['id', 'sector', 'name']),
            'companySizes' => \App\Enums\CompanySize::options(),
            'partnershipTypes' => \App\Enums\PartnershipType::options(),
            'maritalStatuses' => \App\Enums\MaritalStatus::options(),
            'employmentStatuses' => \App\Enums\EmploymentStatus::options(),
            'nigerianStates' => $this->getNigerianStates(),
            'teamMembers' => $this->getTeamMembersForAssignment($user),
        ]);
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $this->authorize('create', TaxClient::class);

        $user = $request->user();
        $client = $this->clientService->create($user, $request->validated());

        $this->activityService->log(
            $client,
            ActivityAction::CREATED,
            $user,
            "Created client: {$client->name}"
        );

        return redirect()
            ->route('clients.show', $client)
            ->with('success', 'Client created successfully.');
    }

    public function show(Request $request, TaxClient $client): Response
    {
        $this->authorize('view', $client);

        $user = $request->user();
        $this->accessService->recordAccess($client, $user);

        $client->load([
            'industry:id,name,sector',
            'creator:id,name',
            'assignedUsers:id,name,email,role',
            'pinnedNotes.author:id,name',
        ]);

        $stats = $this->clientService->getClientStats($client);

        return Inertia::render('App/Clients/Show', [
            'client' => $client,
            'stats' => $stats,
            'notes' => $client->notes()
                ->with('author:id,name')
                ->orderByDesc('is_pinned')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get(),
            'recentActivity' => $this->activityService->getForModel($client, 10),
        ]);
    }

    public function edit(Request $request, TaxClient $client): Response
    {
        $this->authorize('update', $client);

        $user = $request->user();

        return Inertia::render('App/Clients/Edit', [
            'client' => $client->load('industry:id,name'),
            'entityTypes' => EntityType::options(),
            'industries' => Industry::active()->ordered()->get(['id', 'sector', 'name']),
            'companySizes' => \App\Enums\CompanySize::options(),
            'partnershipTypes' => \App\Enums\PartnershipType::options(),
            'maritalStatuses' => \App\Enums\MaritalStatus::options(),
            'employmentStatuses' => \App\Enums\EmploymentStatus::options(),
            'nigerianStates' => $this->getNigerianStates(),
        ]);
    }

    public function update(UpdateClientRequest $request, TaxClient $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $user = $request->user();
        $changes = $this->clientService->update($client, $request->validated());

        if (!empty($changes)) {
            $this->activityService->log(
                $client,
                ActivityAction::UPDATED,
                $user,
                "Updated client: {$client->name}",
                ['changes' => $changes]
            );
        }

        return redirect()
            ->route('clients.show', $client)
            ->with('success', 'Client updated successfully.');
    }

    public function destroy(Request $request, TaxClient $client): RedirectResponse
    {
        $this->authorize('delete', $client);

        $user = $request->user();

        $this->clientService->archive($client);

        $this->activityService->log(
            $client,
            ActivityAction::ARCHIVED,
            $user,
            "Archived client: {$client->name}"
        );

        return redirect()
            ->route('clients.index')
            ->with('success', 'Client archived successfully.');
    }

    public function restore(Request $request, TaxClient $client): RedirectResponse
    {
        $this->authorize('restore', $client);

        $user = $request->user();

        $this->clientService->restore($client);

        $this->activityService->log(
            $client,
            ActivityAction::RESTORED,
            $user,
            "Restored client: {$client->name}"
        );

        return redirect()
            ->route('clients.show', $client)
            ->with('success', 'Client restored successfully.');
    }

    public function bulkAction(BulkClientActionRequest $request): RedirectResponse
    {
        $this->authorize('bulkAction', TaxClient::class);

        $user = $request->user();
        $action = $request->input('action');
        $clientIds = $request->input('client_ids');

        $result = $this->clientService->bulkAction($user, $action, $clientIds);

        return redirect()
            ->back()
            ->with('success', $result['message']);
    }

    private function getTeamMembersForFilter($user): array
    {
        if (!$user->role->canManageTeam()) {
            return [];
        }

        return $user->firm
            ->users()
            ->where('status', 'active')
            ->get(['id', 'name', 'role'])
            ->toArray();
    }

    private function getTeamMembersForAssignment($user): array
    {
        if (!$user->role->canManageTeam()) {
            return [];
        }

        return $user->firm
            ->users()
            ->where('status', 'active')
            ->whereIn('role', ['associate', 'viewer'])
            ->get(['id', 'name', 'email', 'role'])
            ->toArray();
    }

    private function getNigerianStates(): array
    {
        return [
            ['value' => 'abia', 'label' => 'Abia'],
            ['value' => 'adamawa', 'label' => 'Adamawa'],
            ['value' => 'akwa_ibom', 'label' => 'Akwa Ibom'],
            ['value' => 'anambra', 'label' => 'Anambra'],
            ['value' => 'bauchi', 'label' => 'Bauchi'],
            ['value' => 'bayelsa', 'label' => 'Bayelsa'],
            ['value' => 'benue', 'label' => 'Benue'],
            ['value' => 'borno', 'label' => 'Borno'],
            ['value' => 'cross_river', 'label' => 'Cross River'],
            ['value' => 'delta', 'label' => 'Delta'],
            ['value' => 'ebonyi', 'label' => 'Ebonyi'],
            ['value' => 'edo', 'label' => 'Edo'],
            ['value' => 'ekiti', 'label' => 'Ekiti'],
            ['value' => 'enugu', 'label' => 'Enugu'],
            ['value' => 'fct', 'label' => 'FCT - Abuja'],
            ['value' => 'gombe', 'label' => 'Gombe'],
            ['value' => 'imo', 'label' => 'Imo'],
            ['value' => 'jigawa', 'label' => 'Jigawa'],
            ['value' => 'kaduna', 'label' => 'Kaduna'],
            ['value' => 'kano', 'label' => 'Kano'],
            ['value' => 'katsina', 'label' => 'Katsina'],
            ['value' => 'kebbi', 'label' => 'Kebbi'],
            ['value' => 'kogi', 'label' => 'Kogi'],
            ['value' => 'kwara', 'label' => 'Kwara'],
            ['value' => 'lagos', 'label' => 'Lagos'],
            ['value' => 'nasarawa', 'label' => 'Nasarawa'],
            ['value' => 'niger', 'label' => 'Niger'],
            ['value' => 'ogun', 'label' => 'Ogun'],
            ['value' => 'ondo', 'label' => 'Ondo'],
            ['value' => 'osun', 'label' => 'Osun'],
            ['value' => 'oyo', 'label' => 'Oyo'],
            ['value' => 'plateau', 'label' => 'Plateau'],
            ['value' => 'rivers', 'label' => 'Rivers'],
            ['value' => 'sokoto', 'label' => 'Sokoto'],
            ['value' => 'taraba', 'label' => 'Taraba'],
            ['value' => 'yobe', 'label' => 'Yobe'],
            ['value' => 'zamfara', 'label' => 'Zamfara'],
        ];
    }
}
```

---

## Client Assignment Controller

**File:** `app/Http/Controllers/App/ClientAssignmentController.php`

```php
<?php

namespace App\Http\Controllers\App;

use App\Enums\ActivityAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\AssignClientRequest;
use App\Http\Requests\Client\BulkAssignRequest;
use App\Models\FirmUser;
use App\Models\TaxClient;
use App\Services\Activity\ActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClientAssignmentController extends Controller
{
    public function __construct(
        private ActivityService $activityService
    ) {}

    public function index(Request $request, TaxClient $client): JsonResponse
    {
        $this->authorize('view', $client);

        $assignments = $client->assignedUsers()
            ->get(['firm_users.id', 'name', 'email', 'role'])
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'roleLabel' => $user->role->label(),
                'assignedAt' => $user->pivot->assigned_at,
            ]);

        return response()->json([
            'assignments' => $assignments,
        ]);
    }

    public function store(AssignClientRequest $request, TaxClient $client): RedirectResponse
    {
        $this->authorize('assign', $client);

        $user = $request->user();
        $userIds = $request->input('user_ids');

        $existingIds = $client->assignedUsers()->pluck('firm_users.id')->toArray();
        $newIds = array_diff($userIds, $existingIds);

        foreach ($newIds as $userId) {
            $client->assignedUsers()->attach($userId, [
                'assigned_by' => $user->id,
                'assigned_at' => now(),
            ]);

            $assignedUser = FirmUser::find($userId);
            $this->activityService->log(
                $client,
                ActivityAction::ASSIGNED,
                $user,
                "Assigned {$assignedUser->name} to client"
            );
        }

        return redirect()
            ->back()
            ->with('success', count($newIds) . ' user(s) assigned successfully.');
    }

    public function destroy(Request $request, TaxClient $client, FirmUser $firmUser): RedirectResponse
    {
        $this->authorize('assign', $client);

        $user = $request->user();

        $client->assignedUsers()->detach($firmUser->id);

        $this->activityService->log(
            $client,
            ActivityAction::UNASSIGNED,
            $user,
            "Unassigned {$firmUser->name} from client"
        );

        return redirect()
            ->back()
            ->with('success', 'User unassigned successfully.');
    }

    public function bulkAssign(BulkAssignRequest $request): RedirectResponse
    {
        $this->authorize('bulkAssign', TaxClient::class);

        $user = $request->user();
        $clientIds = $request->input('client_ids');
        $userIds = $request->input('user_ids');

        $clients = TaxClient::whereIn('id', $clientIds)
            ->where('firm_id', $user->firm_id)
            ->get();

        $assignedCount = 0;

        foreach ($clients as $client) {
            foreach ($userIds as $userId) {
                if (!$client->assignedUsers()->where('firm_users.id', $userId)->exists()) {
                    $client->assignedUsers()->attach($userId, [
                        'assigned_by' => $user->id,
                        'assigned_at' => now(),
                    ]);
                    $assignedCount++;
                }
            }
        }

        return redirect()
            ->back()
            ->with('success', "{$assignedCount} assignment(s) created successfully.");
    }
}
```

---

## Client Note Controller

**File:** `app/Http/Controllers/App/ClientNoteController.php`

```php
<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreNoteRequest;
use App\Http\Requests\Client\UpdateNoteRequest;
use App\Models\ClientNote;
use App\Models\TaxClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClientNoteController extends Controller
{
    public function index(Request $request, TaxClient $client): JsonResponse
    {
        $this->authorize('view', $client);

        $notes = $client->notes()
            ->with('author:id,name')
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($notes);
    }

    public function store(StoreNoteRequest $request, TaxClient $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $user = $request->user();

        $client->notes()->create([
            'firm_user_id' => $user->id,
            'content' => $request->input('content'),
            'is_pinned' => $request->boolean('is_pinned', false),
        ]);

        return redirect()
            ->back()
            ->with('success', 'Note added successfully.');
    }

    public function update(UpdateNoteRequest $request, ClientNote $note): RedirectResponse
    {
        $this->authorize('update', $note->taxClient);

        $note->update([
            'content' => $request->input('content'),
        ]);

        return redirect()
            ->back()
            ->with('success', 'Note updated successfully.');
    }

    public function destroy(Request $request, ClientNote $note): RedirectResponse
    {
        $this->authorize('update', $note->taxClient);

        $note->delete();

        return redirect()
            ->back()
            ->with('success', 'Note deleted successfully.');
    }

    public function togglePin(Request $request, ClientNote $note): RedirectResponse
    {
        $this->authorize('update', $note->taxClient);

        $note->togglePin();

        return redirect()
            ->back()
            ->with('success', $note->is_pinned ? 'Note pinned.' : 'Note unpinned.');
    }
}
```

---

## Client Import Controller

**File:** `app/Http/Controllers/App/ClientImportController.php`

```php
<?php

namespace App\Http\Controllers\App;

use App\Enums\EntityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Import\ProcessImportRequest;
use App\Http\Requests\Import\UploadImportRequest;
use App\Http\Requests\Import\ValidateMappingRequest;
use App\Models\Industry;
use App\Services\Client\ClientImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientImportController extends Controller
{
    public function __construct(
        private ClientImportService $importService
    ) {}

    public function showUpload(): Response
    {
        $this->authorize('import', \App\Models\TaxClient::class);

        return Inertia::render('App/Clients/Import', [
            'step' => 'upload',
            'entityTypes' => EntityType::options(),
        ]);
    }

    public function upload(UploadImportRequest $request): Response
    {
        $this->authorize('import', \App\Models\TaxClient::class);

        $file = $request->file('file');
        $result = $this->importService->parseFile($file);

        session(['import_data' => $result['rows']]);
        session(['import_headers' => $result['headers']]);

        return Inertia::render('App/Clients/Import', [
            'step' => 'mapping',
            'headers' => $result['headers'],
            'previewRows' => array_slice($result['rows'], 0, 5),
            'totalRows' => count($result['rows']),
            'suggestedMapping' => $this->importService->suggestMapping($result['headers']),
        ]);
    }

    public function validateMapping(ValidateMappingRequest $request): Response
    {
        $this->authorize('import', \App\Models\TaxClient::class);

        $mapping = $request->input('mapping');
        $rows = session('import_data', []);

        $validation = $this->importService->validateRows($rows, $mapping);

        session(['import_mapping' => $mapping]);

        return Inertia::render('App/Clients/Import', [
            'step' => 'preview',
            'mapping' => $mapping,
            'validRows' => $validation['valid'],
            'invalidRows' => $validation['invalid'],
            'totalRows' => count($rows),
            'validCount' => count($validation['valid']),
            'invalidCount' => count($validation['invalid']),
        ]);
    }

    public function process(ProcessImportRequest $request): RedirectResponse
    {
        $this->authorize('import', \App\Models\TaxClient::class);

        $user = $request->user();
        $rows = session('import_data', []);
        $mapping = session('import_mapping', []);
        $skipInvalid = $request->boolean('skip_invalid', true);

        $result = $this->importService->import($user, $rows, $mapping, $skipInvalid);

        session()->forget(['import_data', 'import_headers', 'import_mapping']);

        return redirect()
            ->route('clients.index')
            ->with('success', "{$result['imported']} client(s) imported successfully.");
    }

    public function downloadTemplate(Request $request): StreamedResponse
    {
        $format = $request->input('format', 'csv');

        return $this->importService->generateTemplate($format);
    }
}
```

---

## Form Request Classes

### StoreClientRequest

**File:** `app/Http/Requests/Client/StoreClientRequest.php`

```php
<?php

namespace App\Http\Requests\Client;

use App\Enums\CompanySize;
use App\Enums\EmploymentStatus;
use App\Enums\EntityType;
use App\Enums\MaritalStatus;
use App\Enums\PartnershipType;
use App\Models\TaxClient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', TaxClient::class);
    }

    public function rules(): array
    {
        $entityType = $this->input('entity_type');

        $rules = [
            'entity_type' => ['required', new Enum(EntityType::class)],
            'name' => ['required', 'string', 'max:255'],
            'trading_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'state' => ['nullable', 'string', 'max:50'],
            'lga' => ['nullable', 'string', 'max:100'],
            'industry_id' => ['nullable', 'exists:industries,id'],
            'assigned_users' => ['nullable', 'array'],
            'assigned_users.*' => ['exists:firm_users,id'],
        ];

        if (in_array($entityType, ['company_llc', 'company_plc', 'partnership', 'sole_proprietorship'])) {
            $rules['tin'] = [
                'required',
                'string',
                'regex:/^[0-9]{10,11}(-[0-9]{4})?$/',
                $this->uniqueTinRule(),
            ];
        }

        if (in_array($entityType, ['company_llc', 'company_plc'])) {
            $rules['cac_number'] = [
                'required',
                'string',
                'regex:/^(RC|BN|IT|LP)[0-9]+$/i',
                $this->uniqueCacRule(),
            ];
            $rules['incorporation_date'] = ['required', 'date', 'before_or_equal:today'];
            $rules['fiscal_year_end'] = ['required', 'string', 'regex:/^(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$/'];
            $rules['company_size'] = ['required', new Enum(CompanySize::class)];
        }

        if ($entityType === 'partnership') {
            $rules['partnership_type'] = ['required', new Enum(PartnershipType::class)];
            $rules['number_of_partners'] = ['required', 'integer', 'min:2'];
            $rules['incorporation_date'] = ['required', 'date', 'before_or_equal:today'];
        }

        if ($entityType === 'individual') {
            $rules['nin'] = [
                'required',
                'string',
                'size:11',
                'regex:/^[0-9]{11}$/',
                $this->uniqueNinRule(),
            ];
            $rules['date_of_birth'] = ['required', 'date', 'before:-18 years'];
            $rules['marital_status'] = ['nullable', new Enum(MaritalStatus::class)];
            $rules['employment_status'] = ['required', new Enum(EmploymentStatus::class)];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'tin.regex' => 'TIN must be 10-11 digits, optionally followed by -XXXX.',
            'cac_number.regex' => 'CAC number must start with RC, BN, IT, or LP followed by numbers.',
            'nin.size' => 'NIN must be exactly 11 digits.',
            'nin.regex' => 'NIN must contain only numbers.',
            'date_of_birth.before' => 'Individual must be at least 18 years old.',
            'fiscal_year_end.regex' => 'Fiscal year end must be in MM-DD format.',
        ];
    }

    private function uniqueTinRule()
    {
        return Rule::unique('tax_clients', 'tin')
            ->where('firm_id', $this->user()->firm_id)
            ->whereNull('deleted_at');
    }

    private function uniqueCacRule()
    {
        return Rule::unique('tax_clients', 'cac_number')
            ->where('firm_id', $this->user()->firm_id)
            ->whereNull('deleted_at');
    }

    private function uniqueNinRule()
    {
        return Rule::unique('tax_clients', 'nin')
            ->where('firm_id', $this->user()->firm_id)
            ->whereNull('deleted_at');
    }
}
```

### UpdateClientRequest

**File:** `app/Http/Requests/Client/UpdateClientRequest.php`

```php
<?php

namespace App\Http\Requests\Client;

use App\Enums\CompanySize;
use App\Enums\EmploymentStatus;
use App\Enums\MaritalStatus;
use App\Enums\PartnershipType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('client'));
    }

    public function rules(): array
    {
        $client = $this->route('client');
        $entityType = $client->entity_type->value;

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'trading_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'state' => ['nullable', 'string', 'max:50'],
            'lga' => ['nullable', 'string', 'max:100'],
            'industry_id' => ['nullable', 'exists:industries,id'],
        ];

        if (in_array($entityType, ['company_llc', 'company_plc'])) {
            $rules['incorporation_date'] = ['required', 'date', 'before_or_equal:today'];
            $rules['fiscal_year_end'] = ['required', 'string', 'regex:/^(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$/'];
            $rules['company_size'] = ['required', new Enum(CompanySize::class)];
        }

        if ($entityType === 'partnership') {
            $rules['partnership_type'] = ['required', new Enum(PartnershipType::class)];
            $rules['number_of_partners'] = ['required', 'integer', 'min:2'];
        }

        if ($entityType === 'individual') {
            $rules['date_of_birth'] = ['required', 'date', 'before:-18 years'];
            $rules['marital_status'] = ['nullable', new Enum(MaritalStatus::class)];
            $rules['employment_status'] = ['required', new Enum(EmploymentStatus::class)];
        }

        return $rules;
    }
}
```

### BulkClientActionRequest

**File:** `app/Http/Requests/Client/BulkClientActionRequest.php`

```php
<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkClientActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role->canManageTeam();
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['archive', 'delete', 'export'])],
            'client_ids' => ['required', 'array', 'min:1'],
            'client_ids.*' => ['required', 'exists:tax_clients,id'],
        ];
    }
}
```

---

## Client Service

**File:** `app/Services/Client/ClientService.php`

```php
<?php

namespace App\Services\Client;

use App\Enums\ActivityAction;
use App\Enums\ClientStatus;
use App\Models\FirmUser;
use App\Models\TaxClient;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ClientService
{
    public function search(FirmUser $user, array $filters): LengthAwarePaginator
    {
        $query = TaxClient::where('firm_id', $user->firm_id)
            ->accessibleBy($user)
            ->with(['industry:id,name', 'creator:id,name']);

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['entity_type'])) {
            $types = is_array($filters['entity_type'])
                ? $filters['entity_type']
                : [$filters['entity_type']];
            $query->whereIn('entity_type', $types);
        }

        if (!empty($filters['industry_id'])) {
            $query->where('industry_id', $filters['industry_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->active();
        }

        if (!empty($filters['assigned_to']) && $user->role->canManageTeam()) {
            $query->assignedTo(FirmUser::find($filters['assigned_to']));
        }

        $sortBy = $filters['sort_by'] ?? 'name';
        $sortDir = $filters['sort_dir'] ?? 'asc';

        $sortableColumns = ['name', 'entity_type', 'created_at', 'updated_at'];
        if (in_array($sortBy, $sortableColumns)) {
            $query->orderBy($sortBy, $sortDir);
        }

        return $query->paginate(15)->withQueryString();
    }

    public function create(FirmUser $user, array $data): TaxClient
    {
        return DB::transaction(function () use ($user, $data) {
            $assignedUsers = $data['assigned_users'] ?? [];
            unset($data['assigned_users']);

            $client = TaxClient::create([
                ...$data,
                'firm_id' => $user->firm_id,
                'created_by' => $user->id,
                'status' => ClientStatus::ACTIVE,
            ]);

            if (!empty($assignedUsers)) {
                foreach ($assignedUsers as $userId) {
                    $client->assignedUsers()->attach($userId, [
                        'assigned_by' => $user->id,
                        'assigned_at' => now(),
                    ]);
                }
            }

            Cache::forget("firm:{$user->firm_id}:client_count");

            return $client;
        });
    }

    public function update(TaxClient $client, array $data): array
    {
        $original = $client->getAttributes();

        $client->update($data);

        $changes = [];
        foreach ($data as $key => $value) {
            if (isset($original[$key]) && $original[$key] != $value) {
                $changes[$key] = [
                    'from' => $original[$key],
                    'to' => $value,
                ];
            }
        }

        return $changes;
    }

    public function archive(TaxClient $client): void
    {
        $client->update(['status' => ClientStatus::ARCHIVED]);
    }

    public function restore(TaxClient $client): void
    {
        $client->update(['status' => ClientStatus::ACTIVE]);
    }

    public function bulkAction(FirmUser $user, string $action, array $clientIds): array
    {
        $clients = TaxClient::whereIn('id', $clientIds)
            ->where('firm_id', $user->firm_id)
            ->get();

        $count = 0;

        foreach ($clients as $client) {
            if ($action === 'archive') {
                $this->archive($client);
                $count++;
            } elseif ($action === 'delete' && $user->isPartner()) {
                $client->delete();
                $count++;
            }
        }

        return [
            'message' => "{$count} client(s) {$action}d successfully.",
            'count' => $count,
        ];
    }

    public function getClientStats(TaxClient $client): array
    {
        return [
            'calculationsCount' => 0,
            'reportsCount' => 0,
            'scenariosCount' => 0,
            'lastCalculationAt' => null,
            'lastReportAt' => null,
        ];
    }

    public function getRecentlyAccessed(FirmUser $user, int $limit = 5): array
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
                ->toArray();
        });
    }

    /**
     * Check for potential duplicate clients before creation.
     * Returns array of potential matches with similarity info.
     */
    public function checkForDuplicates(string $firmId, array $data): array
    {
        $potentialDuplicates = [];

        if (!empty($data['tin'])) {
            $tinMatches = TaxClient::where('firm_id', $firmId)
                ->where('tin', $data['tin'])
                ->withTrashed()
                ->get(['id', 'name', 'tin', 'status', 'deleted_at']);

            foreach ($tinMatches as $match) {
                $potentialDuplicates[] = [
                    'id' => $match->id,
                    'name' => $match->name,
                    'matchType' => 'tin',
                    'matchValue' => $match->masked_tin,
                    'isArchived' => $match->status === 'archived',
                    'isDeleted' => $match->deleted_at !== null,
                    'confidence' => 'exact',
                ];
            }
        }

        if (!empty($data['nin'])) {
            $ninMatches = TaxClient::where('firm_id', $firmId)
                ->where('nin', $data['nin'])
                ->withTrashed()
                ->get(['id', 'name', 'nin', 'status', 'deleted_at']);

            foreach ($ninMatches as $match) {
                $potentialDuplicates[] = [
                    'id' => $match->id,
                    'name' => $match->name,
                    'matchType' => 'nin',
                    'matchValue' => '***' . substr($match->nin, -4),
                    'isArchived' => $match->status === 'archived',
                    'isDeleted' => $match->deleted_at !== null,
                    'confidence' => 'exact',
                ];
            }
        }

        if (!empty($data['cac_number'])) {
            $cacMatches = TaxClient::where('firm_id', $firmId)
                ->where('cac_number', $data['cac_number'])
                ->withTrashed()
                ->get(['id', 'name', 'cac_number', 'status', 'deleted_at']);

            foreach ($cacMatches as $match) {
                $potentialDuplicates[] = [
                    'id' => $match->id,
                    'name' => $match->name,
                    'matchType' => 'cac_number',
                    'matchValue' => $match->cac_number,
                    'isArchived' => $match->status === 'archived',
                    'isDeleted' => $match->deleted_at !== null,
                    'confidence' => 'exact',
                ];
            }
        }

        if (!empty($data['name'])) {
            $nameMatches = TaxClient::where('firm_id', $firmId)
                ->where(function ($query) use ($data) {
                    $query->where('name', 'like', $data['name'])
                        ->orWhereRaw('SOUNDEX(name) = SOUNDEX(?)', [$data['name']]);
                })
                ->withTrashed()
                ->get(['id', 'name', 'status', 'deleted_at']);

            foreach ($nameMatches as $match) {
                $existingIds = array_column($potentialDuplicates, 'id');
                if (!in_array($match->id, $existingIds)) {
                    $similarity = similar_text(strtolower($data['name']), strtolower($match->name), $percent);
                    if ($percent >= 80) {
                        $potentialDuplicates[] = [
                            'id' => $match->id,
                            'name' => $match->name,
                            'matchType' => 'name',
                            'matchValue' => $match->name,
                            'isArchived' => $match->status === 'archived',
                            'isDeleted' => $match->deleted_at !== null,
                            'confidence' => $percent >= 95 ? 'high' : 'medium',
                            'similarityPercent' => round($percent),
                        ];
                    }
                }
            }
        }

        return $potentialDuplicates;
    }

    /**
     * Check if specific identifier already exists for this firm.
     */
    public function identifierExists(string $firmId, string $type, string $value, ?string $excludeClientId = null): bool
    {
        $query = TaxClient::where('firm_id', $firmId)
            ->where($type, $value);

        if ($excludeClientId) {
            $query->where('id', '!=', $excludeClientId);
        }

        return $query->exists();
    }
}
```

---

## Duplicate Detection Service

**File:** `app/Services/Client/DuplicateDetectionService.php`

```php
<?php

namespace App\Services\Client;

use App\Models\TaxClient;
use Illuminate\Support\Collection;

class DuplicateDetectionService
{
    /**
     * Check for duplicates before client creation.
     */
    public function check(string $firmId, array $data): Collection
    {
        $matches = collect();

        $matches = $matches->merge($this->checkExactMatches($firmId, $data));
        $matches = $matches->merge($this->checkFuzzyNameMatches($firmId, $data));

        return $matches->unique('id')->values();
    }

    private function checkExactMatches(string $firmId, array $data): Collection
    {
        $matches = collect();

        $identifiers = [
            'tin' => $data['tin'] ?? null,
            'nin' => $data['nin'] ?? null,
            'cac_number' => $data['cac_number'] ?? null,
        ];

        foreach ($identifiers as $field => $value) {
            if (empty($value)) {
                continue;
            }

            $existing = TaxClient::where('firm_id', $firmId)
                ->where($field, $value)
                ->withTrashed()
                ->first();

            if ($existing) {
                $matches->push($this->formatMatch($existing, $field, 'exact'));
            }
        }

        return $matches;
    }

    private function checkFuzzyNameMatches(string $firmId, array $data): Collection
    {
        if (empty($data['name'])) {
            return collect();
        }

        $matches = collect();
        $searchName = $data['name'];

        $candidates = TaxClient::where('firm_id', $firmId)
            ->where(function ($query) use ($searchName) {
                $query->where('name', 'like', "%{$searchName}%")
                    ->orWhere('trading_name', 'like', "%{$searchName}%")
                    ->orWhereRaw('SOUNDEX(name) = SOUNDEX(?)', [$searchName]);
            })
            ->withTrashed()
            ->limit(10)
            ->get();

        foreach ($candidates as $candidate) {
            $similarityToName = 0;
            $similarityToTrading = 0;

            similar_text(strtolower($searchName), strtolower($candidate->name), $similarityToName);

            if ($candidate->trading_name) {
                similar_text(strtolower($searchName), strtolower($candidate->trading_name), $similarityToTrading);
            }

            $maxSimilarity = max($similarityToName, $similarityToTrading);

            if ($maxSimilarity >= 75) {
                $confidence = match (true) {
                    $maxSimilarity >= 95 => 'high',
                    $maxSimilarity >= 85 => 'medium',
                    default => 'low',
                };

                $matches->push(array_merge(
                    $this->formatMatch($candidate, 'name', $confidence),
                    ['similarityPercent' => round($maxSimilarity)]
                ));
            }
        }

        return $matches;
    }

    private function formatMatch(TaxClient $client, string $matchType, string $confidence): array
    {
        return [
            'id' => $client->id,
            'name' => $client->name,
            'tradingName' => $client->trading_name,
            'entityType' => $client->entity_type->label(),
            'matchType' => $matchType,
            'confidence' => $confidence,
            'isArchived' => $client->isArchived(),
            'isDeleted' => $client->trashed(),
            'url' => route('clients.show', $client),
        ];
    }

    /**
     * Validate that identifier is unique within firm.
     */
    public function validateUnique(
        string $firmId,
        string $field,
        string $value,
        ?string $excludeId = null
    ): bool {
        $query = TaxClient::where('firm_id', $firmId)
            ->where($field, $value);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return !$query->exists();
    }
}
```

---

## Client Policy

**File:** `app/Policies/TaxClientPolicy.php`

```php
<?php

namespace App\Policies;

use App\Enums\FirmRole;
use App\Models\FirmUser;
use App\Models\TaxClient;

class TaxClientPolicy
{
    public function viewAny(FirmUser $user): bool
    {
        return $user->isActive() && $user->firm_id !== null;
    }

    public function view(FirmUser $user, TaxClient $client): bool
    {
        if ($user->firm_id !== $client->firm_id) {
            return false;
        }

        if ($user->canViewAllClients()) {
            return true;
        }

        return $client->isAssignedTo($user);
    }

    public function create(FirmUser $user): bool
    {
        return $user->isActive() && $user->role !== FirmRole::VIEWER;
    }

    public function update(FirmUser $user, TaxClient $client): bool
    {
        if ($user->firm_id !== $client->firm_id) {
            return false;
        }

        if ($user->canViewAllClients()) {
            return true;
        }

        if ($user->role === FirmRole::ASSOCIATE) {
            return $client->isAssignedTo($user);
        }

        return false;
    }

    public function delete(FirmUser $user, TaxClient $client): bool
    {
        return $user->firm_id === $client->firm_id
            && $user->role->canManageTeam();
    }

    public function restore(FirmUser $user, TaxClient $client): bool
    {
        return $this->delete($user, $client);
    }

    public function assign(FirmUser $user, TaxClient $client): bool
    {
        return $user->firm_id === $client->firm_id
            && $user->role->canManageTeam();
    }

    public function bulkAction(FirmUser $user): bool
    {
        return $user->role->canManageTeam();
    }

    public function bulkAssign(FirmUser $user): bool
    {
        return $user->role->canManageTeam();
    }

    public function import(FirmUser $user): bool
    {
        return $user->role->canManageTeam();
    }
}
```

---

## Routes

Add to **`routes/app.php`**:

```php
<?php

use App\Http\Controllers\App\ClientAssignmentController;
use App\Http\Controllers\App\ClientController;
use App\Http\Controllers\App\ClientImportController;
use App\Http\Controllers\App\ClientNoteController;

Route::middleware(['auth:firm', 'verified'])->prefix('app')->name('app.')->group(function () {
    Route::resource('clients', ClientController::class);
    Route::post('clients/{client}/restore', [ClientController::class, 'restore'])->name('clients.restore');
    Route::post('clients/bulk-action', [ClientController::class, 'bulkAction'])->name('clients.bulk-action');

    Route::get('clients/{client}/assignments', [ClientAssignmentController::class, 'index'])->name('clients.assignments.index');
    Route::post('clients/{client}/assignments', [ClientAssignmentController::class, 'store'])->name('clients.assignments.store');
    Route::delete('clients/{client}/assignments/{firmUser}', [ClientAssignmentController::class, 'destroy'])->name('clients.assignments.destroy');
    Route::post('clients/bulk-assign', [ClientAssignmentController::class, 'bulkAssign'])->name('clients.bulk-assign');

    Route::get('clients/{client}/notes', [ClientNoteController::class, 'index'])->name('clients.notes.index');
    Route::post('clients/{client}/notes', [ClientNoteController::class, 'store'])->name('clients.notes.store');
    Route::put('notes/{note}', [ClientNoteController::class, 'update'])->name('notes.update');
    Route::delete('notes/{note}', [ClientNoteController::class, 'destroy'])->name('notes.destroy');
    Route::post('notes/{note}/toggle-pin', [ClientNoteController::class, 'togglePin'])->name('notes.toggle-pin');

    Route::get('clients-import', [ClientImportController::class, 'showUpload'])->name('clients.import');
    Route::post('clients-import/upload', [ClientImportController::class, 'upload'])->name('clients.import.upload');
    Route::post('clients-import/validate', [ClientImportController::class, 'validateMapping'])->name('clients.import.validate');
    Route::post('clients-import/process', [ClientImportController::class, 'process'])->name('clients.import.process');
    Route::get('clients-import/template', [ClientImportController::class, 'downloadTemplate'])->name('clients.import.template');
});
```

---

## Next Steps

Once client backend is complete, proceed to:
→ **05_CLIENT_FRONTEND.md** - Implement client management React components
