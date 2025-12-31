# TaxLab Implementation - Client Management Frontend

## Overview

This document covers the frontend React components for Client Management, including TypeScript types, page components, and reusable widgets.

Reference: `docs/06_CLIENT_MANAGEMENT.md`

---

## TypeScript Types

**File:** `resources/js/types/client.ts`

```typescript
export type EntityType =
    | 'company_llc'
    | 'company_plc'
    | 'partnership'
    | 'sole_proprietorship'
    | 'individual'
    | 'trust_estate';

export type ClientStatus = 'active' | 'archived';
export type CompanySize = 'micro' | 'small' | 'medium' | 'large';
export type PartnershipType = 'general' | 'limited' | 'llp';
export type MaritalStatus = 'single' | 'married' | 'divorced' | 'widowed';
export type EmploymentStatus = 'employed' | 'self_employed' | 'unemployed' | 'retired';

export interface EntityTypeOption {
    value: EntityType;
    label: string;
    icon: string;
}

export interface Industry {
    id: string;
    sector: string;
    name: string;
}

export interface EnumOption {
    value: string;
    label: string;
}

export interface TaxClient {
    id: string;
    firmId: string;
    entityType: EntityType;
    name: string;
    tradingName?: string;
    tin?: string;
    maskedTin?: string;
    nin?: string;
    cacNumber?: string;
    incorporationDate?: string;
    fiscalYearEnd?: string;
    industry?: Industry;
    companySize?: CompanySize;
    partnershipType?: PartnershipType;
    numberOfPartners?: number;
    maritalStatus?: MaritalStatus;
    employmentStatus?: EmploymentStatus;
    dateOfBirth?: string;
    email?: string;
    phone?: string;
    address?: string;
    state?: string;
    lga?: string;
    status: ClientStatus;
    portalEnabled: boolean;
    createdAt: string;
    updatedAt: string;
    creator?: {
        id: string;
        name: string;
    };
    assignedUsers?: AssignedUser[];
}

export interface AssignedUser {
    id: string;
    name: string;
    email: string;
    role: string;
    roleLabel: string;
    assignedAt: string;
}

export interface ClientNote {
    id: string;
    content: string;
    isPinned: boolean;
    author: {
        id: string;
        name: string;
    };
    createdAt: string;
    updatedAt: string;
}

export interface ClientFilters {
    search?: string;
    entity_type?: EntityType | EntityType[];
    industry_id?: string;
    status?: ClientStatus;
    assigned_to?: string;
    sort_by?: 'name' | 'entity_type' | 'created_at' | 'updated_at';
    sort_dir?: 'asc' | 'desc';
}

export interface ClientStats {
    calculationsCount: number;
    reportsCount: number;
    scenariosCount: number;
    lastCalculationAt?: string;
    lastReportAt?: string;
}

export interface TeamMember {
    id: string;
    name: string;
    email?: string;
    role: string;
}

export interface PaginatedClients {
    data: TaxClient[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
}

export interface ClientListPageProps {
    clients: PaginatedClients;
    filters: ClientFilters;
    entityTypes: EntityTypeOption[];
    industries: Industry[];
    teamMembers: TeamMember[];
}

export interface ClientCreatePageProps {
    entityTypes: EntityTypeOption[];
    industries: Industry[];
    companySizes: EnumOption[];
    partnershipTypes: EnumOption[];
    maritalStatuses: EnumOption[];
    employmentStatuses: EnumOption[];
    nigerianStates: EnumOption[];
    teamMembers: TeamMember[];
}

export interface ClientShowPageProps {
    client: TaxClient;
    stats: ClientStats;
    notes: ClientNote[];
    recentActivity: ActivityItem[];
}

export interface ClientEditPageProps {
    client: TaxClient;
    entityTypes: EntityTypeOption[];
    industries: Industry[];
    companySizes: EnumOption[];
    partnershipTypes: EnumOption[];
    maritalStatuses: EnumOption[];
    employmentStatuses: EnumOption[];
    nigerianStates: EnumOption[];
}
```

Update `resources/js/types/index.d.ts`:

```typescript
export * from './client';
```

---

## Client List Page

**File:** `resources/js/pages/App/Clients/Index.tsx`

```tsx
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Plus, Upload, Search } from 'lucide-react';
import { ClientListTable } from './components/ClientListTable';
import { ClientFilters } from './components/ClientFilters';
import { ClientBulkActions } from './components/ClientBulkActions';
import { useDebounce } from '@/hooks/use-debounce';
import type { ClientListPageProps } from '@/types';

export default function ClientsIndex({
    clients,
    filters,
    entityTypes,
    industries,
    teamMembers,
}: ClientListPageProps) {
    const [searchTerm, setSearchTerm] = useState(filters.search ?? '');
    const [selectedIds, setSelectedIds] = useState<string[]>([]);
    const debouncedSearch = useDebounce(searchTerm, 300);

    const handleSearch = (value: string) => {
        setSearchTerm(value);
        router.get(
            '/app/clients',
            { ...filters, search: value || undefined, page: 1 },
            { preserveState: true, replace: true }
        );
    };

    const handleFilterChange = (newFilters: Partial<typeof filters>) => {
        router.get(
            '/app/clients',
            { ...filters, ...newFilters, page: 1 },
            { preserveState: true }
        );
    };

    const handleClearFilters = () => {
        router.get('/app/clients', {}, { preserveState: true });
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Dashboard', href: '/app/dashboard' },
                { title: 'Clients', href: '/app/clients' },
            ]}
        >
            <Head title="Clients" />

            <div className="space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold">Clients</h1>
                        <p className="text-sm text-muted-foreground">
                            {clients.total} client{clients.total !== 1 ? 's' : ''} total
                        </p>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/app/clients-import">
                                <Upload className="mr-2 h-4 w-4" />
                                Import
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href="/app/clients/create">
                                <Plus className="mr-2 h-4 w-4" />
                                New Client
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="flex flex-col sm:flex-row gap-4">
                    <div className="relative flex-1">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        <Input
                            placeholder="Search clients..."
                            value={searchTerm}
                            onChange={(e) => handleSearch(e.target.value)}
                            className="pl-9"
                        />
                    </div>

                    <ClientFilters
                        filters={filters}
                        entityTypes={entityTypes}
                        industries={industries}
                        teamMembers={teamMembers}
                        onFilterChange={handleFilterChange}
                        onClearFilters={handleClearFilters}
                    />
                </div>

                {selectedIds.length > 0 && (
                    <ClientBulkActions
                        selectedCount={selectedIds.length}
                        selectedIds={selectedIds}
                        onClear={() => setSelectedIds([])}
                    />
                )}

                <ClientListTable
                    clients={clients}
                    selectedIds={selectedIds}
                    onSelectionChange={setSelectedIds}
                />
            </div>
        </AppLayout>
    );
}
```

---

## Client List Table Component

**File:** `resources/js/pages/App/Clients/components/ClientListTable.tsx`

```tsx
import { Link, router } from '@inertiajs/react';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Building2,
    User,
    Users,
    Briefcase,
    Landmark,
    MoreHorizontal,
    Eye,
    Pencil,
    Calculator,
    FileText,
    Archive,
    UserPlus,
} from 'lucide-react';
import { DataTablePagination } from '@/components/ui/data-table-pagination';
import type { PaginatedClients, TaxClient } from '@/types';

interface ClientListTableProps {
    clients: PaginatedClients;
    selectedIds: string[];
    onSelectionChange: (ids: string[]) => void;
}

const iconMap: Record<string, React.ComponentType<{ className?: string }>> = {
    'building-2': Building2,
    user: User,
    users: Users,
    briefcase: Briefcase,
    landmark: Landmark,
};

const entityTypeLabels: Record<string, string> = {
    company_llc: 'Company (LLC)',
    company_plc: 'Company (PLC)',
    partnership: 'Partnership',
    sole_proprietorship: 'Sole Prop.',
    individual: 'Individual',
    trust_estate: 'Trust/Estate',
};

export function ClientListTable({
    clients,
    selectedIds,
    onSelectionChange,
}: ClientListTableProps) {
    const allSelected = clients.data.length > 0 && selectedIds.length === clients.data.length;

    const toggleAll = () => {
        if (allSelected) {
            onSelectionChange([]);
        } else {
            onSelectionChange(clients.data.map((c) => c.id));
        }
    };

    const toggleOne = (id: string) => {
        if (selectedIds.includes(id)) {
            onSelectionChange(selectedIds.filter((i) => i !== id));
        } else {
            onSelectionChange([...selectedIds, id]);
        }
    };

    const handleArchive = (client: TaxClient) => {
        if (confirm(`Are you sure you want to archive "${client.name}"?`)) {
            router.delete(`/app/clients/${client.id}`);
        }
    };

    return (
        <div className="space-y-4">
            <div className="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead className="w-12">
                                <Checkbox
                                    checked={allSelected}
                                    onCheckedChange={toggleAll}
                                    aria-label="Select all"
                                />
                            </TableHead>
                            <TableHead>Client Name</TableHead>
                            <TableHead>Type</TableHead>
                            <TableHead className="hidden md:table-cell">Industry</TableHead>
                            <TableHead className="hidden lg:table-cell">Status</TableHead>
                            <TableHead className="w-12"></TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {clients.data.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={6} className="h-24 text-center">
                                    No clients found.
                                </TableCell>
                            </TableRow>
                        ) : (
                            clients.data.map((client) => (
                                <ClientRow
                                    key={client.id}
                                    client={client}
                                    isSelected={selectedIds.includes(client.id)}
                                    onToggle={() => toggleOne(client.id)}
                                    onArchive={() => handleArchive(client)}
                                />
                            ))
                        )}
                    </TableBody>
                </Table>
            </div>

            <DataTablePagination
                currentPage={clients.current_page}
                lastPage={clients.last_page}
                perPage={clients.per_page}
                total={clients.total}
            />
        </div>
    );
}

interface ClientRowProps {
    client: TaxClient;
    isSelected: boolean;
    onToggle: () => void;
    onArchive: () => void;
}

function ClientRow({ client, isSelected, onToggle, onArchive }: ClientRowProps) {
    const Icon = iconMap[getEntityIcon(client.entityType)] || Building2;

    return (
        <TableRow className={isSelected ? 'bg-muted/50' : undefined}>
            <TableCell>
                <Checkbox
                    checked={isSelected}
                    onCheckedChange={onToggle}
                    aria-label={`Select ${client.name}`}
                />
            </TableCell>
            <TableCell>
                <Link
                    href={`/app/clients/${client.id}`}
                    className="flex items-center gap-3 hover:underline"
                >
                    <div className="flex h-9 w-9 items-center justify-center rounded bg-muted">
                        <Icon className="h-4 w-4 text-muted-foreground" />
                    </div>
                    <div>
                        <p className="font-medium">{client.name}</p>
                        {client.tradingName && (
                            <p className="text-sm text-muted-foreground">
                                t/a {client.tradingName}
                            </p>
                        )}
                    </div>
                </Link>
            </TableCell>
            <TableCell>
                <span className="text-sm">
                    {entityTypeLabels[client.entityType] || client.entityType}
                </span>
            </TableCell>
            <TableCell className="hidden md:table-cell">
                <span className="text-sm text-muted-foreground">
                    {client.industry?.name || '—'}
                </span>
            </TableCell>
            <TableCell className="hidden lg:table-cell">
                <Badge variant={client.status === 'active' ? 'default' : 'secondary'}>
                    {client.status}
                </Badge>
            </TableCell>
            <TableCell>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="icon">
                            <MoreHorizontal className="h-4 w-4" />
                            <span className="sr-only">Actions</span>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem asChild>
                            <Link href={`/app/clients/${client.id}`}>
                                <Eye className="mr-2 h-4 w-4" />
                                View
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem asChild>
                            <Link href={`/app/clients/${client.id}/edit`}>
                                <Pencil className="mr-2 h-4 w-4" />
                                Edit
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem asChild>
                            <Link href={`/app/calculations/new?client=${client.id}`}>
                                <Calculator className="mr-2 h-4 w-4" />
                                Run Calculation
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem asChild>
                            <Link href={`/app/reports/new?client=${client.id}`}>
                                <FileText className="mr-2 h-4 w-4" />
                                Generate Report
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem asChild>
                            <Link href={`/app/clients/${client.id}#assignments`}>
                                <UserPlus className="mr-2 h-4 w-4" />
                                Assign Users
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            onClick={onArchive}
                            className="text-destructive focus:text-destructive"
                        >
                            <Archive className="mr-2 h-4 w-4" />
                            Archive
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </TableCell>
        </TableRow>
    );
}

function getEntityIcon(entityType: string): string {
    const iconMap: Record<string, string> = {
        company_llc: 'building-2',
        company_plc: 'building-2',
        partnership: 'users',
        sole_proprietorship: 'briefcase',
        individual: 'user',
        trust_estate: 'landmark',
    };
    return iconMap[entityType] || 'building-2';
}
```

---

## Client Filters Component

**File:** `resources/js/pages/App/Clients/components/ClientFilters.tsx`

```tsx
import { Button } from '@/components/ui/button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Filter, X } from 'lucide-react';
import type { ClientFilters as FiltersType, EntityTypeOption, Industry, TeamMember } from '@/types';

interface ClientFiltersProps {
    filters: FiltersType;
    entityTypes: EntityTypeOption[];
    industries: Industry[];
    teamMembers: TeamMember[];
    onFilterChange: (filters: Partial<FiltersType>) => void;
    onClearFilters: () => void;
}

export function ClientFilters({
    filters,
    entityTypes,
    industries,
    teamMembers,
    onFilterChange,
    onClearFilters,
}: ClientFiltersProps) {
    const activeFilterCount = Object.values(filters).filter(
        (v) => v !== undefined && v !== ''
    ).length;

    const sectors = [...new Set(industries.map((i) => i.sector))];

    return (
        <Popover>
            <PopoverTrigger asChild>
                <Button variant="outline" className="gap-2">
                    <Filter className="h-4 w-4" />
                    Filters
                    {activeFilterCount > 0 && (
                        <Badge variant="secondary" className="ml-1">
                            {activeFilterCount}
                        </Badge>
                    )}
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-80" align="end">
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <h4 className="font-medium">Filters</h4>
                        {activeFilterCount > 0 && (
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={onClearFilters}
                                className="h-auto py-1 px-2"
                            >
                                <X className="mr-1 h-3 w-3" />
                                Clear all
                            </Button>
                        )}
                    </div>

                    <div className="space-y-3">
                        <div>
                            <label className="text-sm font-medium mb-1.5 block">
                                Entity Type
                            </label>
                            <Select
                                value={filters.entity_type as string || ''}
                                onValueChange={(value) =>
                                    onFilterChange({ entity_type: value || undefined })
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="All types" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">All types</SelectItem>
                                    {entityTypes.map((type) => (
                                        <SelectItem key={type.value} value={type.value}>
                                            {type.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <label className="text-sm font-medium mb-1.5 block">
                                Industry
                            </label>
                            <Select
                                value={filters.industry_id || ''}
                                onValueChange={(value) =>
                                    onFilterChange({ industry_id: value || undefined })
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="All industries" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">All industries</SelectItem>
                                    {sectors.map((sector) => (
                                        <optgroup key={sector} label={sector}>
                                            {industries
                                                .filter((i) => i.sector === sector)
                                                .map((industry) => (
                                                    <SelectItem
                                                        key={industry.id}
                                                        value={industry.id}
                                                    >
                                                        {industry.name}
                                                    </SelectItem>
                                                ))}
                                        </optgroup>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <label className="text-sm font-medium mb-1.5 block">
                                Status
                            </label>
                            <Select
                                value={filters.status || ''}
                                onValueChange={(value) =>
                                    onFilterChange({ status: value as 'active' | 'archived' || undefined })
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Active" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">Active</SelectItem>
                                    <SelectItem value="active">Active</SelectItem>
                                    <SelectItem value="archived">Archived</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        {teamMembers.length > 0 && (
                            <div>
                                <label className="text-sm font-medium mb-1.5 block">
                                    Assigned To
                                </label>
                                <Select
                                    value={filters.assigned_to || ''}
                                    onValueChange={(value) =>
                                        onFilterChange({ assigned_to: value || undefined })
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="All users" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">All users</SelectItem>
                                        {teamMembers.map((member) => (
                                            <SelectItem key={member.id} value={member.id}>
                                                {member.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        )}

                        <div>
                            <label className="text-sm font-medium mb-1.5 block">
                                Sort By
                            </label>
                            <Select
                                value={`${filters.sort_by || 'name'}-${filters.sort_dir || 'asc'}`}
                                onValueChange={(value) => {
                                    const [sortBy, sortDir] = value.split('-');
                                    onFilterChange({
                                        sort_by: sortBy as 'name' | 'created_at',
                                        sort_dir: sortDir as 'asc' | 'desc',
                                    });
                                }}
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="name-asc">Name (A-Z)</SelectItem>
                                    <SelectItem value="name-desc">Name (Z-A)</SelectItem>
                                    <SelectItem value="created_at-desc">Newest first</SelectItem>
                                    <SelectItem value="created_at-asc">Oldest first</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>
            </PopoverContent>
        </Popover>
    );
}
```

---

## Client Create Page (Wizard)

**File:** `resources/js/pages/App/Clients/Create.tsx`

```tsx
import { Head } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { ClientCreateWizard } from './components/ClientCreateWizard';
import type { ClientCreatePageProps } from '@/types';

export default function ClientsCreate(props: ClientCreatePageProps) {
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Dashboard', href: '/app/dashboard' },
                { title: 'Clients', href: '/app/clients' },
                { title: 'New Client', href: '/app/clients/create' },
            ]}
        >
            <Head title="New Client" />

            <div className="max-w-3xl mx-auto">
                <div className="mb-6">
                    <h1 className="text-2xl font-semibold">Create New Client</h1>
                    <p className="text-sm text-muted-foreground">
                        Add a new client to your practice.
                    </p>
                </div>

                <ClientCreateWizard {...props} />
            </div>
        </AppLayout>
    );
}
```

---

## Client Create Wizard Component

**File:** `resources/js/pages/App/Clients/components/ClientCreateWizard.tsx`

```tsx
import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { StepIndicator } from '@/components/ui/step-indicator';
import { EntityTypeStep } from './EntityTypeStep';
import { BasicInfoStep } from './BasicInfoStep';
import { ContactStep } from './ContactStep';
import { IndustryStep } from './IndustryStep';
import { AssignmentStep } from './AssignmentStep';
import type { ClientCreatePageProps, EntityType } from '@/types';

const steps = [
    { id: 'type', label: 'Entity Type' },
    { id: 'basic', label: 'Basic Info' },
    { id: 'contact', label: 'Contact' },
    { id: 'industry', label: 'Industry' },
    { id: 'assignment', label: 'Assignment' },
];

export function ClientCreateWizard({
    entityTypes,
    industries,
    companySizes,
    partnershipTypes,
    maritalStatuses,
    employmentStatuses,
    nigerianStates,
    teamMembers,
}: ClientCreatePageProps) {
    const [currentStep, setCurrentStep] = useState(0);

    const form = useForm({
        entity_type: '' as EntityType,
        name: '',
        trading_name: '',
        tin: '',
        nin: '',
        cac_number: '',
        incorporation_date: '',
        fiscal_year_end: '',
        company_size: '',
        partnership_type: '',
        number_of_partners: '',
        marital_status: '',
        employment_status: '',
        date_of_birth: '',
        email: '',
        phone: '',
        address: '',
        state: '',
        lga: '',
        industry_id: '',
        assigned_users: [] as string[],
    });

    const canProceed = () => {
        switch (currentStep) {
            case 0:
                return !!form.data.entity_type;
            case 1:
                return !!form.data.name;
            default:
                return true;
        }
    };

    const handleNext = () => {
        if (currentStep < steps.length - 1) {
            setCurrentStep(currentStep + 1);
        }
    };

    const handleBack = () => {
        if (currentStep > 0) {
            setCurrentStep(currentStep - 1);
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/app/clients', {
            preserveScroll: true,
        });
    };

    return (
        <div className="space-y-6">
            <StepIndicator steps={steps} currentStep={currentStep} />

            <Card>
                <CardContent className="pt-6">
                    <form onSubmit={handleSubmit}>
                        {currentStep === 0 && (
                            <EntityTypeStep
                                entityTypes={entityTypes}
                                value={form.data.entity_type}
                                onChange={(value) => form.setData('entity_type', value)}
                                error={form.errors.entity_type}
                            />
                        )}

                        {currentStep === 1 && (
                            <BasicInfoStep
                                entityType={form.data.entity_type}
                                data={form.data}
                                errors={form.errors}
                                setData={form.setData}
                                companySizes={companySizes}
                                partnershipTypes={partnershipTypes}
                                maritalStatuses={maritalStatuses}
                                employmentStatuses={employmentStatuses}
                            />
                        )}

                        {currentStep === 2 && (
                            <ContactStep
                                data={form.data}
                                errors={form.errors}
                                setData={form.setData}
                                nigerianStates={nigerianStates}
                            />
                        )}

                        {currentStep === 3 && (
                            <IndustryStep
                                industries={industries}
                                value={form.data.industry_id}
                                onChange={(value) => form.setData('industry_id', value)}
                                error={form.errors.industry_id}
                            />
                        )}

                        {currentStep === 4 && (
                            <AssignmentStep
                                teamMembers={teamMembers}
                                selectedIds={form.data.assigned_users}
                                onChange={(ids) => form.setData('assigned_users', ids)}
                            />
                        )}

                        <div className="flex justify-between mt-8">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={handleBack}
                                disabled={currentStep === 0}
                            >
                                Back
                            </Button>

                            <div className="flex gap-2">
                                {currentStep < steps.length - 1 ? (
                                    <Button
                                        type="button"
                                        onClick={handleNext}
                                        disabled={!canProceed()}
                                    >
                                        Next
                                    </Button>
                                ) : (
                                    <Button
                                        type="submit"
                                        disabled={form.processing}
                                    >
                                        {form.processing ? 'Creating...' : 'Create Client'}
                                    </Button>
                                )}
                            </div>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    );
}
```

---

## Entity Type Step Component

**File:** `resources/js/pages/App/Clients/components/EntityTypeStep.tsx`

```tsx
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';
import { Building2, User, Users, Briefcase, Landmark } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { EntityType, EntityTypeOption } from '@/types';

interface EntityTypeStepProps {
    entityTypes: EntityTypeOption[];
    value: EntityType;
    onChange: (value: EntityType) => void;
    error?: string;
}

const iconMap: Record<string, React.ComponentType<{ className?: string }>> = {
    'building-2': Building2,
    user: User,
    users: Users,
    briefcase: Briefcase,
    landmark: Landmark,
};

export function EntityTypeStep({
    entityTypes,
    value,
    onChange,
    error,
}: EntityTypeStepProps) {
    return (
        <div className="space-y-4">
            <div>
                <h3 className="text-lg font-medium">Select Entity Type</h3>
                <p className="text-sm text-muted-foreground">
                    Choose the type of entity for this client.
                </p>
            </div>

            <RadioGroup
                value={value}
                onValueChange={(v) => onChange(v as EntityType)}
                className="grid grid-cols-1 sm:grid-cols-2 gap-4"
            >
                {entityTypes.map((type) => {
                    const Icon = iconMap[type.icon] || Building2;
                    return (
                        <Label
                            key={type.value}
                            htmlFor={type.value}
                            className={cn(
                                'flex items-center gap-4 p-4 border rounded-lg cursor-pointer transition-colors',
                                value === type.value
                                    ? 'border-primary bg-primary/5'
                                    : 'hover:bg-muted/50'
                            )}
                        >
                            <RadioGroupItem
                                value={type.value}
                                id={type.value}
                                className="sr-only"
                            />
                            <div
                                className={cn(
                                    'flex h-10 w-10 items-center justify-center rounded-full',
                                    value === type.value
                                        ? 'bg-primary text-primary-foreground'
                                        : 'bg-muted'
                                )}
                            >
                                <Icon className="h-5 w-5" />
                            </div>
                            <span className="font-medium">{type.label}</span>
                        </Label>
                    );
                })}
            </RadioGroup>

            {error && <p className="text-sm text-destructive">{error}</p>}
        </div>
    );
}
```

---

## Client Profile Page

**File:** `resources/js/pages/App/Clients/Show.tsx`

```tsx
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { ClientProfileHeader } from './components/ClientProfileHeader';
import { ClientProfileTabs } from './components/ClientProfileTabs';
import { ClientOverviewTab } from './components/ClientOverviewTab';
import { ClientArchivedBanner } from './components/ClientArchivedBanner';
import type { ClientShowPageProps } from '@/types';

export default function ClientsShow({
    client,
    stats,
    notes,
    recentActivity,
}: ClientShowPageProps) {
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Dashboard', href: '/app/dashboard' },
                { title: 'Clients', href: '/app/clients' },
                { title: client.name, href: `/app/clients/${client.id}` },
            ]}
        >
            <Head title={client.name} />

            <div className="space-y-6">
                {client.status === 'archived' && (
                    <ClientArchivedBanner clientId={client.id} />
                )}

                <ClientProfileHeader client={client} />

                <ClientProfileTabs activeTab="overview" clientId={client.id} />

                <ClientOverviewTab
                    client={client}
                    stats={stats}
                    notes={notes}
                    recentActivity={recentActivity}
                />
            </div>
        </AppLayout>
    );
}
```

---

## Client Profile Header Component

**File:** `resources/js/pages/App/Clients/components/ClientProfileHeader.tsx`

```tsx
import { Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Building2,
    User,
    Users,
    Briefcase,
    Landmark,
    Pencil,
    Calculator,
    FileText,
    MoreHorizontal,
    Archive,
    RotateCcw,
    Trash2,
} from 'lucide-react';
import type { TaxClient } from '@/types';

interface ClientProfileHeaderProps {
    client: TaxClient;
}

const iconMap: Record<string, React.ComponentType<{ className?: string }>> = {
    company_llc: Building2,
    company_plc: Building2,
    partnership: Users,
    sole_proprietorship: Briefcase,
    individual: User,
    trust_estate: Landmark,
};

const entityTypeLabels: Record<string, string> = {
    company_llc: 'Company (LLC)',
    company_plc: 'Company (PLC)',
    partnership: 'Partnership',
    sole_proprietorship: 'Sole Proprietorship',
    individual: 'Individual',
    trust_estate: 'Trust/Estate',
};

export function ClientProfileHeader({ client }: ClientProfileHeaderProps) {
    const Icon = iconMap[client.entityType] || Building2;

    const handleArchive = () => {
        if (confirm(`Are you sure you want to archive "${client.name}"?`)) {
            router.delete(`/app/clients/${client.id}`);
        }
    };

    const handleRestore = () => {
        router.post(`/app/clients/${client.id}/restore`);
    };

    return (
        <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div className="flex items-start gap-4">
                <div className="flex h-14 w-14 items-center justify-center rounded-lg bg-muted">
                    <Icon className="h-7 w-7 text-muted-foreground" />
                </div>
                <div>
                    <div className="flex items-center gap-2">
                        <h1 className="text-2xl font-semibold">{client.name}</h1>
                        <Badge variant={client.status === 'active' ? 'default' : 'secondary'}>
                            {client.status}
                        </Badge>
                    </div>
                    <div className="flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted-foreground mt-1">
                        <span>{entityTypeLabels[client.entityType]}</span>
                        {client.industry && <span>• {client.industry.name}</span>}
                        {client.maskedTin && <span>• TIN: {client.maskedTin}</span>}
                        {client.cacNumber && <span>• CAC: {client.cacNumber}</span>}
                    </div>
                </div>
            </div>

            <div className="flex flex-wrap gap-2">
                <Button variant="outline" asChild>
                    <Link href={`/app/clients/${client.id}/edit`}>
                        <Pencil className="mr-2 h-4 w-4" />
                        Edit
                    </Link>
                </Button>
                <Button variant="outline" asChild>
                    <Link href={`/app/calculations/new?client=${client.id}`}>
                        <Calculator className="mr-2 h-4 w-4" />
                        Calculate
                    </Link>
                </Button>
                <Button asChild>
                    <Link href={`/app/reports/new?client=${client.id}`}>
                        <FileText className="mr-2 h-4 w-4" />
                        Report
                    </Link>
                </Button>

                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="outline" size="icon">
                            <MoreHorizontal className="h-4 w-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        {client.status === 'archived' ? (
                            <DropdownMenuItem onClick={handleRestore}>
                                <RotateCcw className="mr-2 h-4 w-4" />
                                Restore
                            </DropdownMenuItem>
                        ) : (
                            <DropdownMenuItem
                                onClick={handleArchive}
                                className="text-destructive focus:text-destructive"
                            >
                                <Archive className="mr-2 h-4 w-4" />
                                Archive
                            </DropdownMenuItem>
                        )}
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>
    );
}
```

---

## Step Indicator UI Component

**File:** `resources/js/components/ui/step-indicator.tsx`

```tsx
import { cn } from '@/lib/utils';
import { Check } from 'lucide-react';

interface Step {
    id: string;
    label: string;
}

interface StepIndicatorProps {
    steps: Step[];
    currentStep: number;
}

export function StepIndicator({ steps, currentStep }: StepIndicatorProps) {
    return (
        <nav aria-label="Progress">
            <ol className="flex items-center">
                {steps.map((step, index) => (
                    <li
                        key={step.id}
                        className={cn(
                            'relative',
                            index !== steps.length - 1 ? 'flex-1 pr-8 sm:pr-20' : ''
                        )}
                    >
                        <div className="flex items-center">
                            <div
                                className={cn(
                                    'flex h-8 w-8 items-center justify-center rounded-full text-sm font-medium',
                                    index < currentStep
                                        ? 'bg-primary text-primary-foreground'
                                        : index === currentStep
                                          ? 'border-2 border-primary text-primary'
                                          : 'border-2 border-muted text-muted-foreground'
                                )}
                            >
                                {index < currentStep ? (
                                    <Check className="h-4 w-4" />
                                ) : (
                                    index + 1
                                )}
                            </div>
                            <span
                                className={cn(
                                    'ml-3 text-sm font-medium hidden sm:block',
                                    index <= currentStep
                                        ? 'text-foreground'
                                        : 'text-muted-foreground'
                                )}
                            >
                                {step.label}
                            </span>
                        </div>

                        {index !== steps.length - 1 && (
                            <div
                                className={cn(
                                    'absolute top-4 left-8 -ml-px h-0.5 w-full sm:w-[calc(100%-5rem)]',
                                    index < currentStep ? 'bg-primary' : 'bg-muted'
                                )}
                            />
                        )}
                    </li>
                ))}
            </ol>
        </nav>
    );
}
```

---

## Data Table Pagination Component

**File:** `resources/js/components/ui/data-table-pagination.tsx`

```tsx
import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { ChevronLeft, ChevronRight } from 'lucide-react';

interface DataTablePaginationProps {
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
}

export function DataTablePagination({
    currentPage,
    lastPage,
    perPage,
    total,
}: DataTablePaginationProps) {
    const from = (currentPage - 1) * perPage + 1;
    const to = Math.min(currentPage * perPage, total);

    return (
        <div className="flex items-center justify-between">
            <p className="text-sm text-muted-foreground">
                Showing {from} to {to} of {total} results
            </p>

            <div className="flex items-center gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    disabled={currentPage === 1}
                    asChild={currentPage !== 1}
                >
                    {currentPage === 1 ? (
                        <span>
                            <ChevronLeft className="h-4 w-4" />
                        </span>
                    ) : (
                        <Link href={`?page=${currentPage - 1}`} preserveState>
                            <ChevronLeft className="h-4 w-4" />
                        </Link>
                    )}
                </Button>

                <span className="text-sm">
                    Page {currentPage} of {lastPage}
                </span>

                <Button
                    variant="outline"
                    size="sm"
                    disabled={currentPage === lastPage}
                    asChild={currentPage !== lastPage}
                >
                    {currentPage === lastPage ? (
                        <span>
                            <ChevronRight className="h-4 w-4" />
                        </span>
                    ) : (
                        <Link href={`?page=${currentPage + 1}`} preserveState>
                            <ChevronRight className="h-4 w-4" />
                        </Link>
                    )}
                </Button>
            </div>
        </div>
    );
}
```

---

## use-debounce Hook

**File:** `resources/js/hooks/use-debounce.ts`

```typescript
import { useState, useEffect } from 'react';

export function useDebounce<T>(value: T, delay: number): T {
    const [debouncedValue, setDebouncedValue] = useState<T>(value);

    useEffect(() => {
        const handler = setTimeout(() => {
            setDebouncedValue(value);
        }, delay);

        return () => {
            clearTimeout(handler);
        };
    }, [value, delay]);

    return debouncedValue;
}
```

---

## Next Steps

Once client frontend is complete, proceed to:
→ **06_TESTING_STRATEGY.md** - Implement tests for dashboard and client management
