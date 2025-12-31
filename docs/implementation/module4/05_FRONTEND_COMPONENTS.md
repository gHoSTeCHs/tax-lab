# TaxLab Implementation - Module 4 Frontend Components

## Overview

This document defines the React components for the Financial Data Entry module.

---

## Page Components

### Financials Index Page

**File:** `resources/js/pages/App/Clients/Financials/Index.tsx`

```tsx
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Plus, FileSpreadsheet, Lock, Unlock } from 'lucide-react';
import { formatNaira } from '@/lib/financial-utils';
import type { ClientFinancial } from '@/types/financial';

interface Props {
    client: {
        id: string;
        name: string;
        tradingName?: string;
        entityType: string;
    };
    financials: {
        data: ClientFinancial[];
        links: unknown;
        meta: unknown;
    };
}

export default function FinancialsIndex({ client, financials }: Props) {
    return (
        <AppLayout>
            <Head title={`Financials - ${client.name}`} />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-foreground">
                            Financial Data
                        </h1>
                        <p className="text-muted-foreground">
                            {client.tradingName || client.name}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link
                                href={route('app.clients.financials.import', client.id)}
                            >
                                <FileSpreadsheet className="mr-2 h-4 w-4" />
                                Import
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link
                                href={route('app.clients.financials.create', client.id)}
                            >
                                <Plus className="mr-2 h-4 w-4" />
                                Add Fiscal Year
                            </Link>
                        </Button>
                    </div>
                </div>

                {financials.data.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <FileSpreadsheet className="h-12 w-12 text-muted-foreground mb-4" />
                            <h3 className="text-lg font-semibold mb-2">
                                No Financial Data
                            </h3>
                            <p className="text-muted-foreground text-center mb-4">
                                Add financial data for this client to enable tax calculations.
                            </p>
                            <Button asChild>
                                <Link
                                    href={route('app.clients.financials.create', client.id)}
                                >
                                    <Plus className="mr-2 h-4 w-4" />
                                    Add First Fiscal Year
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4">
                        {financials.data.map((financial) => (
                            <FinancialCard
                                key={financial.id}
                                financial={financial}
                                clientId={client.id}
                            />
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

function FinancialCard({
    financial,
    clientId,
}: {
    financial: ClientFinancial;
    clientId: string;
}) {
    return (
        <Card className="hover:bg-muted/50 transition-colors">
            <Link
                href={route('app.clients.financials.show', [clientId, financial.fiscalYear])}
            >
                <CardHeader className="flex flex-row items-center justify-between pb-2">
                    <CardTitle className="text-lg">
                        FY {financial.fiscalYear}
                    </CardTitle>
                    <div className="flex items-center gap-2">
                        {financial.isLocked ? (
                            <Badge variant="secondary">
                                <Lock className="mr-1 h-3 w-3" />
                                Locked
                            </Badge>
                        ) : (
                            <Badge variant="outline">
                                <Unlock className="mr-1 h-3 w-3" />
                                Unlocked
                            </Badge>
                        )}
                        <Badge
                            variant={financial.isComplete ? 'default' : 'secondary'}
                        >
                            {financial.completionPercentage}% Complete
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent>
                    <div className="grid grid-cols-4 gap-4 text-sm">
                        <div>
                            <p className="text-muted-foreground">Turnover</p>
                            <p className="font-medium">
                                {formatNaira(financial.incomeData?.turnover)}
                            </p>
                        </div>
                        <div>
                            <p className="text-muted-foreground">Profit Before Tax</p>
                            <p className="font-medium">
                                {formatNaira(financial.incomeData?.profitBeforeTax)}
                            </p>
                        </div>
                        <div>
                            <p className="text-muted-foreground">Data Source</p>
                            <p className="font-medium">{financial.dataSourceLabel}</p>
                        </div>
                        <div>
                            <p className="text-muted-foreground">Version</p>
                            <p className="font-medium">v{financial.version}</p>
                        </div>
                    </div>
                </CardContent>
            </Link>
        </Card>
    );
}
```

### Financials Show Page

**File:** `resources/js/pages/App/Clients/Financials/Show.tsx`

```tsx
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/layouts/AppLayout';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Lock, Unlock, History, Calculator, Save } from 'lucide-react';
import { IncomeSection } from './components/IncomeSection';
import { BalanceSheetSection } from './components/BalanceSheetSection';
import { EmploymentSection } from './components/EmploymentSection';
import { VatSection } from './components/VatSection';
import { CapitalSection } from './components/CapitalSection';
import { SummarySection } from './components/SummarySection';
import { DataQualityIndicator } from './components/DataQualityIndicator';
import { LockStatusBadge } from './components/LockStatusBadge';
import { VersionHistoryDialog } from './components/VersionHistoryDialog';
import { useFinancialForm } from '@/hooks/use-financial';
import type { ClientFinancial, FinancialReadiness } from '@/types/financial';

interface Props {
    client: {
        id: string;
        name: string;
        tradingName?: string;
        entityType: string;
    };
    financial: ClientFinancial;
    readiness: FinancialReadiness;
    availableYears: number[];
}

export default function FinancialsShow({ client, financial, readiness, availableYears }: Props) {
    const [activeTab, setActiveTab] = useState('income');
    const [showVersionHistory, setShowVersionHistory] = useState(false);
    const { form, updateSectionField, calculatedValues } = useFinancialForm(financial);

    const handleSave = () => {
        form.put(
            route('app.clients.financials.update', [client.id, financial.fiscalYear]),
            {
                preserveScroll: true,
            }
        );
    };

    const handleLock = () => {
        router.post(
            route('app.clients.financials.lock', [client.id, financial.fiscalYear])
        );
    };

    const handleUnlock = () => {
        router.post(
            route('app.clients.financials.unlock', [client.id, financial.fiscalYear])
        );
    };

    return (
        <AppLayout>
            <Head title={`FY ${financial.fiscalYear} - ${client.name}`} />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-foreground">
                            FY {financial.fiscalYear} Financial Data
                        </h1>
                        <p className="text-muted-foreground">
                            {client.tradingName || client.name}
                        </p>
                    </div>
                    <div className="flex items-center gap-4">
                        <DataQualityIndicator
                            percentage={readiness.completionPercentage}
                            warningsCount={readiness.warningsCount}
                        />
                        <LockStatusBadge
                            isLocked={financial.isLocked}
                            lockedBy={financial.lockedBy}
                            lockedAt={financial.lockedAt}
                        />
                    </div>
                </div>

                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Badge variant="outline">{financial.dataSourceLabel}</Badge>
                        <Badge variant="secondary">v{financial.version}</Badge>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setShowVersionHistory(true)}
                        >
                            <History className="mr-2 h-4 w-4" />
                            History
                        </Button>
                        {financial.isLocked ? (
                            <Button variant="outline" size="sm" onClick={handleUnlock}>
                                <Unlock className="mr-2 h-4 w-4" />
                                Unlock
                            </Button>
                        ) : (
                            <>
                                <Button variant="outline" size="sm" onClick={handleLock}>
                                    <Lock className="mr-2 h-4 w-4" />
                                    Lock
                                </Button>
                                <Button
                                    size="sm"
                                    onClick={handleSave}
                                    disabled={form.processing}
                                >
                                    <Save className="mr-2 h-4 w-4" />
                                    Save
                                </Button>
                            </>
                        )}
                        {readiness.isReady && (
                            <Button size="sm" asChild>
                                <a
                                    href={route('app.clients.calculations.create', [
                                        client.id,
                                        { year: financial.fiscalYear },
                                    ])}
                                >
                                    <Calculator className="mr-2 h-4 w-4" />
                                    Calculate Tax
                                </a>
                            </Button>
                        )}
                    </div>
                </div>

                <Tabs value={activeTab} onValueChange={setActiveTab}>
                    <TabsList className="grid w-full grid-cols-6">
                        <TabsTrigger value="income">Income</TabsTrigger>
                        <TabsTrigger value="balance_sheet">Balance Sheet</TabsTrigger>
                        <TabsTrigger value="employment">Employment</TabsTrigger>
                        <TabsTrigger value="vat">VAT</TabsTrigger>
                        <TabsTrigger value="capital">Capital</TabsTrigger>
                        <TabsTrigger value="summary">Summary</TabsTrigger>
                    </TabsList>

                    <TabsContent value="income">
                        <IncomeSection
                            data={form.data.incomeData || {}}
                            onChange={(field, value) => updateSectionField('income', field, value)}
                            calculatedValues={calculatedValues}
                            disabled={financial.isLocked}
                            warnings={readiness.warnings.filter((w) => w.section === 'income')}
                        />
                    </TabsContent>

                    <TabsContent value="balance_sheet">
                        <BalanceSheetSection
                            data={form.data.balanceSheetData || {}}
                            onChange={(field, value) =>
                                updateSectionField('balance_sheet', field, value)
                            }
                            calculatedValues={calculatedValues}
                            disabled={financial.isLocked}
                            warnings={readiness.warnings.filter(
                                (w) => w.section === 'balance_sheet'
                            )}
                        />
                    </TabsContent>

                    <TabsContent value="employment">
                        <EmploymentSection
                            data={form.data.employmentData || {}}
                            onChange={(field, value) =>
                                updateSectionField('employment', field, value)
                            }
                            disabled={financial.isLocked}
                            warnings={readiness.warnings.filter((w) => w.section === 'employment')}
                        />
                    </TabsContent>

                    <TabsContent value="vat">
                        <VatSection
                            data={form.data.vatData || {}}
                            onChange={(field, value) => updateSectionField('vat', field, value)}
                            calculatedValues={calculatedValues}
                            disabled={financial.isLocked}
                            warnings={readiness.warnings.filter((w) => w.section === 'vat')}
                        />
                    </TabsContent>

                    <TabsContent value="capital">
                        <CapitalSection
                            data={form.data.capitalData || {}}
                            onChange={(field, value) => updateSectionField('capital', field, value)}
                            disabled={financial.isLocked}
                            warnings={readiness.warnings.filter((w) => w.section === 'capital')}
                        />
                    </TabsContent>

                    <TabsContent value="summary">
                        <SummarySection
                            financial={financial}
                            readiness={readiness}
                        />
                    </TabsContent>
                </Tabs>
            </div>

            <VersionHistoryDialog
                open={showVersionHistory}
                onOpenChange={setShowVersionHistory}
                clientId={client.id}
                fiscalYear={financial.fiscalYear}
                currentVersion={financial.version}
                isLocked={financial.isLocked}
            />
        </AppLayout>
    );
}
```

---

## Section Components

### CurrencyInput Component

**File:** `resources/js/pages/App/Clients/Financials/components/CurrencyInput.tsx`

```tsx
import { useState, useEffect } from 'react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { InfoIcon } from 'lucide-react';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { formatNaira, parseCurrencyInput } from '@/lib/financial-utils';
import { cn } from '@/lib/utils';

interface CurrencyInputProps {
    label: string;
    description?: string;
    value: number | undefined;
    onChange: (value: number | null) => void;
    required?: boolean;
    disabled?: boolean;
    hasWarning?: boolean;
    warningMessage?: string;
    calculated?: boolean;
    calculatedValue?: number | null;
    onUseCalculated?: () => void;
}

export function CurrencyInput({
    label,
    description,
    value,
    onChange,
    required = false,
    disabled = false,
    hasWarning = false,
    warningMessage,
    calculated = false,
    calculatedValue,
    onUseCalculated,
}: CurrencyInputProps) {
    const [displayValue, setDisplayValue] = useState('');
    const [isFocused, setIsFocused] = useState(false);

    useEffect(() => {
        if (!isFocused) {
            setDisplayValue(value !== undefined ? value.toString() : '');
        }
    }, [value, isFocused]);

    const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const raw = e.target.value;
        setDisplayValue(raw);
        const parsed = parseCurrencyInput(raw);
        onChange(parsed);
    };

    const handleBlur = () => {
        setIsFocused(false);
        if (value !== undefined) {
            setDisplayValue(value.toString());
        }
    };

    const handleFocus = () => {
        setIsFocused(true);
    };

    const showCalculatedHint =
        calculated && calculatedValue !== null && calculatedValue !== value;

    return (
        <div className="space-y-2">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <Label
                        className={cn(
                            required && 'after:content-["*"] after:ml-0.5 after:text-red-500'
                        )}
                    >
                        {label}
                    </Label>
                    {description && (
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <InfoIcon className="h-4 w-4 text-muted-foreground cursor-help" />
                            </TooltipTrigger>
                            <TooltipContent>
                                <p className="max-w-xs">{description}</p>
                            </TooltipContent>
                        </Tooltip>
                    )}
                </div>
                {showCalculatedHint && (
                    <button
                        type="button"
                        onClick={onUseCalculated}
                        className="text-xs text-primary hover:underline"
                    >
                        Use calculated: {formatNaira(calculatedValue)}
                    </button>
                )}
            </div>

            <div className="relative">
                <span className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">
                    ₦
                </span>
                <Input
                    type="text"
                    value={isFocused ? displayValue : formatNaira(value).replace('₦', '')}
                    onChange={handleChange}
                    onFocus={handleFocus}
                    onBlur={handleBlur}
                    disabled={disabled}
                    className={cn(
                        'pl-7',
                        hasWarning && 'border-yellow-500 focus:ring-yellow-500'
                    )}
                    placeholder="0"
                />
            </div>

            {hasWarning && warningMessage && (
                <p className="text-xs text-yellow-600 dark:text-yellow-400">
                    {warningMessage}
                </p>
            )}
        </div>
    );
}
```

### Income Section

**File:** `resources/js/pages/App/Clients/Financials/components/IncomeSection.tsx`

```tsx
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CurrencyInput } from './CurrencyInput';
import { incomeFields } from '@/config/financial-fields';
import type { IncomeData, FinancialWarning } from '@/types/financial';

interface Props {
    data: IncomeData;
    onChange: (field: string, value: number | null) => void;
    calculatedValues: {
        grossProfit: number | null;
    };
    disabled: boolean;
    warnings: FinancialWarning[];
}

export function IncomeSection({
    data,
    onChange,
    calculatedValues,
    disabled,
    warnings,
}: Props) {
    const getWarning = (field: string) => {
        return warnings.find((w) => w.field === field);
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>Income Statement</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {incomeFields.map((field) => {
                        const warning = getWarning(field.key);
                        const isGrossProfit = field.key === 'grossProfit';

                        return (
                            <CurrencyInput
                                key={field.key}
                                label={field.label}
                                description={field.description}
                                value={data[field.key as keyof IncomeData]}
                                onChange={(value) => onChange(field.key, value)}
                                required={field.required}
                                disabled={disabled}
                                hasWarning={!!warning}
                                warningMessage={warning?.message}
                                calculated={isGrossProfit}
                                calculatedValue={
                                    isGrossProfit ? calculatedValues.grossProfit : null
                                }
                                onUseCalculated={
                                    isGrossProfit
                                        ? () => onChange('grossProfit', calculatedValues.grossProfit)
                                        : undefined
                                }
                            />
                        );
                    })}
                </div>
            </CardContent>
        </Card>
    );
}
```

### Data Quality Indicator

**File:** `resources/js/pages/App/Clients/Financials/components/DataQualityIndicator.tsx`

```tsx
import { Progress } from '@/components/ui/progress';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { AlertTriangle, CheckCircle } from 'lucide-react';
import { cn } from '@/lib/utils';

interface Props {
    percentage: number;
    warningsCount: number;
}

export function DataQualityIndicator({ percentage, warningsCount }: Props) {
    const isComplete = percentage === 100;

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <div className="flex items-center gap-2 cursor-help">
                    <Progress
                        value={percentage}
                        className={cn(
                            'w-24 h-2',
                            isComplete ? 'bg-green-100' : 'bg-muted'
                        )}
                    />
                    <span
                        className={cn(
                            'text-sm font-medium',
                            isComplete
                                ? 'text-green-600 dark:text-green-400'
                                : 'text-muted-foreground'
                        )}
                    >
                        {percentage}%
                    </span>
                    {warningsCount > 0 && (
                        <div className="flex items-center gap-1 text-yellow-600">
                            <AlertTriangle className="h-4 w-4" />
                            <span className="text-sm">{warningsCount}</span>
                        </div>
                    )}
                    {isComplete && warningsCount === 0 && (
                        <CheckCircle className="h-4 w-4 text-green-600" />
                    )}
                </div>
            </TooltipTrigger>
            <TooltipContent>
                <p>
                    Data Quality: {percentage}% complete
                    {warningsCount > 0 && `, ${warningsCount} warning(s)`}
                </p>
            </TooltipContent>
        </Tooltip>
    );
}
```

### Lock Status Badge

**File:** `resources/js/pages/App/Clients/Financials/components/LockStatusBadge.tsx`

```tsx
import { Badge } from '@/components/ui/badge';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { Lock, Unlock } from 'lucide-react';

interface Props {
    isLocked: boolean;
    lockedBy?: string;
    lockedAt?: string;
}

export function LockStatusBadge({ isLocked, lockedBy, lockedAt }: Props) {
    if (!isLocked) {
        return (
            <Badge variant="outline" className="gap-1">
                <Unlock className="h-3 w-3" />
                Unlocked
            </Badge>
        );
    }

    const formattedDate = lockedAt
        ? new Date(lockedAt).toLocaleDateString('en-NG', {
              day: 'numeric',
              month: 'short',
              year: 'numeric',
              hour: '2-digit',
              minute: '2-digit',
          })
        : 'Unknown';

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Badge variant="secondary" className="gap-1 cursor-help">
                    <Lock className="h-3 w-3" />
                    Locked
                </Badge>
            </TooltipTrigger>
            <TooltipContent>
                <p>
                    Locked by {lockedBy || 'Unknown'}
                    <br />
                    on {formattedDate}
                </p>
            </TooltipContent>
        </Tooltip>
    );
}
```

### Version History Dialog

**File:** `resources/js/pages/App/Clients/Financials/components/VersionHistoryDialog.tsx`

```tsx
import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { RotateCcw, Clock, User } from 'lucide-react';
import type { FinancialVersion } from '@/types/financial';

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    clientId: string;
    fiscalYear: number;
    currentVersion: number;
    isLocked: boolean;
}

export function VersionHistoryDialog({
    open,
    onOpenChange,
    clientId,
    fiscalYear,
    currentVersion,
    isLocked,
}: Props) {
    const [versions, setVersions] = useState<FinancialVersion[]>([]);
    const [isLoading, setIsLoading] = useState(false);
    const [isRestoring, setIsRestoring] = useState(false);

    useEffect(() => {
        if (open) {
            loadVersions();
        }
    }, [open]);

    const loadVersions = async () => {
        setIsLoading(true);
        try {
            const response = await fetch(
                route('app.clients.financials.versions', [clientId, fiscalYear])
            );
            const data = await response.json();
            setVersions(data.versions);
        } catch (error) {
            console.error('Failed to load versions:', error);
        } finally {
            setIsLoading(false);
        }
    };

    const handleRestore = (version: number) => {
        if (isLocked) {
            return;
        }

        setIsRestoring(true);
        router.post(
            route('app.clients.financials.restore', [clientId, fiscalYear, version]),
            {},
            {
                onFinish: () => {
                    setIsRestoring(false);
                    onOpenChange(false);
                },
            }
        );
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Version History</DialogTitle>
                </DialogHeader>

                {isLoading ? (
                    <div className="flex items-center justify-center py-8">
                        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary" />
                    </div>
                ) : (
                    <div className="space-y-4 max-h-96 overflow-y-auto">
                        {versions.map((version) => (
                            <div
                                key={version.version}
                                className="flex items-center justify-between p-4 border rounded-lg"
                            >
                                <div className="space-y-1">
                                    <div className="flex items-center gap-2">
                                        <span className="font-medium">
                                            Version {version.version}
                                        </span>
                                        {version.version === currentVersion && (
                                            <Badge>Current</Badge>
                                        )}
                                    </div>
                                    {version.changeSummary && (
                                        <p className="text-sm text-muted-foreground">
                                            {version.changeSummary}
                                        </p>
                                    )}
                                    <div className="flex items-center gap-4 text-xs text-muted-foreground">
                                        <span className="flex items-center gap-1">
                                            <User className="h-3 w-3" />
                                            {version.createdBy}
                                        </span>
                                        <span className="flex items-center gap-1">
                                            <Clock className="h-3 w-3" />
                                            {new Date(version.createdAt).toLocaleString()}
                                        </span>
                                    </div>
                                </div>

                                {version.version !== currentVersion && (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => handleRestore(version.version)}
                                        disabled={isLocked || isRestoring}
                                    >
                                        <RotateCcw className="mr-2 h-4 w-4" />
                                        Restore
                                    </Button>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </DialogContent>
        </Dialog>
    );
}
```

---

## Summary Section

**File:** `resources/js/pages/App/Clients/Financials/components/SummarySection.tsx`

```tsx
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { CheckCircle, AlertTriangle, XCircle, Info } from 'lucide-react';
import { formatNaira } from '@/lib/financial-utils';
import { getSeverityColor, getSeverityBgColor } from '@/lib/financial-utils';
import type { ClientFinancial, FinancialReadiness } from '@/types/financial';

interface Props {
    financial: ClientFinancial;
    readiness: FinancialReadiness;
}

export function SummarySection({ financial, readiness }: Props) {
    return (
        <div className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Data Readiness</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 md:grid-cols-3">
                        <div className="flex items-center gap-4">
                            {readiness.isReady ? (
                                <CheckCircle className="h-8 w-8 text-green-500" />
                            ) : (
                                <AlertTriangle className="h-8 w-8 text-yellow-500" />
                            )}
                            <div>
                                <p className="font-medium">
                                    {readiness.isReady
                                        ? 'Ready for Calculation'
                                        : 'Not Ready'}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    {readiness.completionPercentage}% complete
                                </p>
                            </div>
                        </div>

                        <div>
                            <p className="text-sm text-muted-foreground">Data Source</p>
                            <Badge variant="outline">{readiness.dataSource}</Badge>
                        </div>

                        <div>
                            <p className="text-sm text-muted-foreground">Status</p>
                            <div className="flex gap-2">
                                {readiness.isLocked && (
                                    <Badge variant="secondary">Locked</Badge>
                                )}
                                {readiness.isVerified && (
                                    <Badge variant="default">Verified</Badge>
                                )}
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Key Figures</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <p className="text-sm text-muted-foreground">Turnover</p>
                            <p className="text-2xl font-bold">
                                {formatNaira(financial.incomeData?.turnover)}
                            </p>
                        </div>
                        <div>
                            <p className="text-sm text-muted-foreground">
                                Profit Before Tax
                            </p>
                            <p className="text-2xl font-bold">
                                {formatNaira(financial.incomeData?.profitBeforeTax)}
                            </p>
                        </div>
                        <div>
                            <p className="text-sm text-muted-foreground">Total Assets</p>
                            <p className="text-2xl font-bold">
                                {formatNaira(financial.balanceSheetData?.totalAssets)}
                            </p>
                        </div>
                        <div>
                            <p className="text-sm text-muted-foreground">Employees</p>
                            <p className="text-2xl font-bold">
                                {financial.employmentData?.employeeCount ?? '-'}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {readiness.warnings.length > 0 && (
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <AlertTriangle className="h-5 w-5 text-yellow-500" />
                            Warnings ({readiness.warnings.length})
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {readiness.warnings.map((warning, index) => (
                            <Alert
                                key={index}
                                className={getSeverityBgColor(warning.severity)}
                            >
                                <Info className="h-4 w-4" />
                                <AlertTitle className={getSeverityColor(warning.severity)}>
                                    {warning.section}: {warning.field}
                                </AlertTitle>
                                <AlertDescription>{warning.message}</AlertDescription>
                            </Alert>
                        ))}
                    </CardContent>
                </Card>
            )}

            {readiness.errors.length > 0 && (
                <Card className="border-red-200 dark:border-red-900">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-red-600 dark:text-red-400">
                            <XCircle className="h-5 w-5" />
                            Validation Errors ({readiness.errors.length})
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {readiness.errors.map((error, index) => (
                            <Alert key={index} variant="destructive">
                                <XCircle className="h-4 w-4" />
                                <AlertTitle>
                                    {error.section}: {error.field}
                                </AlertTitle>
                                <AlertDescription>
                                    {error.message}
                                    <br />
                                    Expected: {error.expected}, Actual: {error.actual}
                                </AlertDescription>
                            </Alert>
                        ))}
                    </CardContent>
                </Card>
            )}
        </div>
    );
}
```

---

## Additional Section Components

The following section components follow the same pattern as IncomeSection:

- **BalanceSheetSection.tsx** - Uses `balanceSheetFields` config
- **EmploymentSection.tsx** - Uses `employmentFields` config
- **VatSection.tsx** - Uses `vatFields` config
- **CapitalSection.tsx** - Uses `capitalFields` config

Each maps over the field configuration and renders `CurrencyInput` components.

---

## Next Steps

Once components are implemented, proceed to:
→ **06_TESTING_STRATEGY.md** - Testing approach for Module 4
