# TaxLab Implementation - Module 5: Frontend Components

## Overview

This document defines the React components for the tax calculation module, including pages, result displays, and visualizations. All components support dark mode and follow the established design system.

---

## Page Components

### Calculation Index Page

**File:** `resources/js/pages/App/Clients/Calculations/Index.tsx`

```tsx
import { Head, Link } from '@inertiajs/react';
import { AppLayout } from '@/layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CalculationList } from './components/CalculationList';
import { useCalculationHistory } from '@/hooks/useCalculationHistory';
import { Plus } from 'lucide-react';
import type { CalculationIndexProps } from '@/types/props/calculation';

export default function Index({ client, calculations }: CalculationIndexProps) {
    const { listItems, isEmpty } = useCalculationHistory({ calculations });

    return (
        <AppLayout>
            <Head title={`Calculations - ${client.name}`} />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                            Tax Calculations
                        </h1>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            {client.name} - Calculation History
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={route('app.clients.calculations.create', { client: client.id })}>
                            <Plus className="mr-2 h-4 w-4" />
                            New Calculation
                        </Link>
                    </Button>
                </div>

                {isEmpty ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <p className="text-gray-500 dark:text-gray-400">
                                No calculations have been run for this client yet.
                            </p>
                            <Button asChild className="mt-4">
                                <Link href={route('app.clients.calculations.create', { client: client.id })}>
                                    Run First Calculation
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <CalculationList items={listItems} clientId={client.id} />
                )}
            </div>
        </AppLayout>
    );
}
```

### Calculation Create Page

**File:** `resources/js/pages/App/Clients/Calculations/Create.tsx`

```tsx
import { useState } from 'react';
import { Head } from '@inertiajs/react';
import { AppLayout } from '@/layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { useCalculation } from '@/hooks/useCalculation';
import { Calculator, AlertCircle, CheckCircle } from 'lucide-react';
import type { CalculationCreateProps } from '@/types/props/calculation';
import type { CalculationType } from '@/types/calculation';

export default function Create({ client, financials, calculationTypes }: CalculationCreateProps) {
    const [fiscalYear, setFiscalYear] = useState<number | null>(null);
    const [calculationType, setCalculationType] = useState<CalculationType>('full_analysis');
    const { isCalculating, error, runCalculation } = useCalculation({ clientId: client.id });

    const selectedFinancial = financials.find((f) => f.fiscalYear === fiscalYear);
    const canCalculate = fiscalYear && selectedFinancial?.isComplete;

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (fiscalYear && calculationType) {
            runCalculation({ fiscalYear, calculationType });
        }
    };

    return (
        <AppLayout>
            <Head title={`Run Calculation - ${client.name}`} />

            <div className="mx-auto max-w-2xl space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                        Run Tax Calculation
                    </h1>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {client.name}
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Calculation Settings</CardTitle>
                        <CardDescription>
                            Select the fiscal year and calculation type to analyze.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="space-y-2">
                                <Label htmlFor="fiscalYear">Fiscal Year</Label>
                                <Select
                                    value={fiscalYear?.toString() ?? ''}
                                    onValueChange={(v) => setFiscalYear(Number(v))}
                                >
                                    <SelectTrigger id="fiscalYear">
                                        <SelectValue placeholder="Select fiscal year" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {financials.map((f) => (
                                            <SelectItem key={f.id} value={f.fiscalYear.toString()}>
                                                <span className="flex items-center gap-2">
                                                    {f.fiscalYear}
                                                    {f.isComplete ? (
                                                        <CheckCircle className="h-4 w-4 text-green-500" />
                                                    ) : (
                                                        <AlertCircle className="h-4 w-4 text-yellow-500" />
                                                    )}
                                                </span>
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {selectedFinancial && !selectedFinancial.isComplete && (
                                    <p className="text-sm text-yellow-600 dark:text-yellow-400">
                                        Financial data is incomplete. Complete all required fields before calculating.
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="calculationType">Calculation Type</Label>
                                <Select
                                    value={calculationType}
                                    onValueChange={(v) => setCalculationType(v as CalculationType)}
                                >
                                    <SelectTrigger id="calculationType">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {calculationTypes.map((type) => (
                                            <SelectItem key={type.value} value={type.value}>
                                                <div>
                                                    <div>{type.label}</div>
                                                    <div className="text-xs text-gray-500">
                                                        {type.description}
                                                    </div>
                                                </div>
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            {error && (
                                <Alert variant="destructive">
                                    <AlertCircle className="h-4 w-4" />
                                    <AlertDescription>{error}</AlertDescription>
                                </Alert>
                            )}

                            <Button
                                type="submit"
                                disabled={!canCalculate || isCalculating}
                                className="w-full"
                            >
                                {isCalculating ? (
                                    <>Calculating...</>
                                ) : (
                                    <>
                                        <Calculator className="mr-2 h-4 w-4" />
                                        Run Calculation
                                    </>
                                )}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
```

### Calculation Show Page

**File:** `resources/js/pages/App/Clients/Calculations/Show.tsx`

```tsx
import { useState } from 'react';
import { Head } from '@inertiajs/react';
import { AppLayout } from '@/layouts/AppLayout';
import { CalculationHeader } from './components/CalculationHeader';
import { SavingsBanner } from './components/SavingsBanner';
import { ResultsTabs } from './components/ResultsTabs';
import { CITResultsPanel } from './components/CITResultsPanel';
import { PITResultsPanel } from './components/PITResultsPanel';
import { VATResultsPanel } from './components/VATResultsPanel';
import { CGTResultsPanel } from './components/CGTResultsPanel';
import { DevelopmentLevyPanel } from './components/DevelopmentLevyPanel';
import { OptimizationsPanel } from './components/OptimizationsPanel';
import { InsightsList } from './components/InsightsList';
import { ComparisonBarChart } from './components/ComparisonBarChart';
import { TaxCompositionPie } from './components/TaxCompositionPie';
import type { CalculationShowProps } from '@/types/props/calculation';

export default function Show({ client, calculation, insights }: CalculationShowProps) {
    const [activeTab, setActiveTab] = useState('summary');
    const { results, summary, optimizations } = calculation;

    return (
        <AppLayout>
            <Head title={`Calculation Results - ${client.name}`} />

            <div className="space-y-6">
                <CalculationHeader calculation={calculation} />

                <SavingsBanner
                    oldTotal={summary.overallComparison.oldTotal}
                    newTotal={summary.overallComparison.newTotal}
                    savingsPercentage={summary.overallComparison.savingsPercentage}
                />

                <ResultsTabs
                    results={results}
                    activeTab={activeTab}
                    onTabChange={setActiveTab}
                />

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="lg:col-span-2">
                        {activeTab === 'summary' && (
                            <div className="space-y-6">
                                <ComparisonBarChart results={results} />
                                <InsightsList insights={insights} />
                            </div>
                        )}
                        {activeTab === 'cit' && results.cit && (
                            <CITResultsPanel result={results.cit} />
                        )}
                        {activeTab === 'pit' && results.pit && (
                            <PITResultsPanel result={results.pit} />
                        )}
                        {activeTab === 'vat' && results.vat && (
                            <VATResultsPanel result={results.vat} />
                        )}
                        {activeTab === 'cgt' && results.cgt && (
                            <CGTResultsPanel result={results.cgt} />
                        )}
                        {activeTab === 'developmentLevy' && results.developmentLevy && (
                            <DevelopmentLevyPanel result={results.developmentLevy} />
                        )}
                        {activeTab === 'optimizations' && (
                            <OptimizationsPanel optimizations={optimizations} />
                        )}
                    </div>

                    <div className="space-y-6">
                        <TaxCompositionPie
                            totals={summary.oldRegimeTotal}
                            title="Old Regime Composition"
                        />
                        <TaxCompositionPie
                            totals={summary.newRegimeTotal}
                            title="New Regime Composition"
                        />
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
```

---

## Display Components

### Calculation Header

**File:** `resources/js/pages/App/Clients/Calculations/components/CalculationHeader.tsx`

```tsx
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { formatCalculationType, formatCalculationStatus, getStatusColor, formatCurrency } from '@/utils/calculation';
import { Calendar, Clock, FileText } from 'lucide-react';
import type { CalculationHeaderProps } from '@/types/props/calculation';

export function CalculationHeader({ calculation }: CalculationHeaderProps) {
    const { summary } = calculation;

    return (
        <Card>
            <CardContent className="pt-6">
                <div className="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                    <div className="space-y-1">
                        <div className="flex items-center gap-2">
                            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                                Tax Calculation Results
                            </h1>
                            <Badge className={getStatusColor(calculation.status)}>
                                {formatCalculationStatus(calculation.status)}
                            </Badge>
                        </div>
                        <div className="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                            <span className="flex items-center gap-1">
                                <Calendar className="h-4 w-4" />
                                FY {calculation.fiscalYear}
                            </span>
                            <span className="flex items-center gap-1">
                                <FileText className="h-4 w-4" />
                                {formatCalculationType(calculation.calculationType)}
                            </span>
                            <span className="flex items-center gap-1">
                                <Clock className="h-4 w-4" />
                                {calculation.calculationTimeMs}ms
                            </span>
                        </div>
                    </div>

                    <div className="flex gap-6">
                        <div className="text-center">
                            <p className="text-sm text-gray-600 dark:text-gray-400">Old Regime</p>
                            <p className="text-xl font-bold text-gray-900 dark:text-white">
                                {formatCurrency(summary.overallComparison.oldTotal)}
                            </p>
                        </div>
                        <div className="text-center">
                            <p className="text-sm text-gray-600 dark:text-gray-400">New Regime</p>
                            <p className="text-xl font-bold text-gray-900 dark:text-white">
                                {formatCurrency(summary.overallComparison.newTotal)}
                            </p>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
```

### Savings Banner

**File:** `resources/js/pages/App/Clients/Calculations/components/SavingsBanner.tsx`

```tsx
import { Card, CardContent } from '@/components/ui/card';
import { formatCurrency } from '@/utils/calculation';
import { TrendingDown, TrendingUp, Minus } from 'lucide-react';
import type { SavingsBannerProps } from '@/types/props/calculation';

export function SavingsBanner({ oldTotal, newTotal, savingsPercentage }: SavingsBannerProps) {
    const difference = oldTotal - newTotal;
    const isSaving = difference > 0;
    const isIncrease = difference < 0;

    if (difference === 0) {
        return (
            <Card className="border-gray-300 bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
                <CardContent className="flex items-center justify-center gap-3 py-4">
                    <Minus className="h-6 w-6 text-gray-500" />
                    <span className="text-lg font-medium text-gray-700 dark:text-gray-300">
                        No difference between regimes
                    </span>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card
            className={
                isSaving
                    ? 'border-green-300 bg-green-50 dark:border-green-800 dark:bg-green-950'
                    : 'border-red-300 bg-red-50 dark:border-red-800 dark:bg-red-950'
            }
        >
            <CardContent className="flex items-center justify-center gap-3 py-4">
                {isSaving ? (
                    <>
                        <TrendingDown className="h-6 w-6 text-green-600 dark:text-green-400" />
                        <span className="text-lg font-medium text-green-700 dark:text-green-300">
                            You SAVE {formatCurrency(difference)} ({savingsPercentage.toFixed(1)}%) under NTA 2025
                        </span>
                    </>
                ) : (
                    <>
                        <TrendingUp className="h-6 w-6 text-red-600 dark:text-red-400" />
                        <span className="text-lg font-medium text-red-700 dark:text-red-300">
                            Tax increases by {formatCurrency(Math.abs(difference))} ({Math.abs(savingsPercentage).toFixed(1)}%) under NTA 2025
                        </span>
                    </>
                )}
            </CardContent>
        </Card>
    );
}
```

### Results Tabs

**File:** `resources/js/pages/App/Clients/Calculations/components/ResultsTabs.tsx`

```tsx
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { formatTaxType } from '@/utils/calculation';
import type { ResultsTabsProps } from '@/types/props/calculation';

export function ResultsTabs({ results, activeTab, onTabChange }: ResultsTabsProps) {
    const availableTabs = [
        { value: 'summary', label: 'Summary' },
        ...Object.keys(results).map((key) => ({
            value: key,
            label: formatTaxType(key),
        })),
        { value: 'optimizations', label: 'Optimizations' },
    ];

    return (
        <Tabs value={activeTab} onValueChange={onTabChange}>
            <TabsList className="flex-wrap">
                {availableTabs.map((tab) => (
                    <TabsTrigger key={tab.value} value={tab.value}>
                        {tab.label}
                    </TabsTrigger>
                ))}
            </TabsList>
        </Tabs>
    );
}
```

### CIT Results Panel

**File:** `resources/js/pages/App/Clients/Calculations/components/CITResultsPanel.tsx`

```tsx
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { formatCurrency, formatPercentage, formatClassification, getSavingsIndicator } from '@/utils/calculation';
import type { CITResultsPanelProps } from '@/types/props/calculation';

export function CITResultsPanel({ result }: CITResultsPanelProps) {
    const { oldRegime, newRegime, comparison } = result;
    const savingsInfo = getSavingsIndicator(comparison.savings - comparison.increase);

    return (
        <div className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center justify-between">
                        Company Income Tax Comparison
                        <Badge className={savingsInfo.color}>{savingsInfo.text}</Badge>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Item</TableHead>
                                <TableHead className="text-right">Old Regime</TableHead>
                                <TableHead className="text-right">New Regime</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow>
                                <TableCell>Classification</TableCell>
                                <TableCell className="text-right">
                                    {formatClassification(oldRegime.classification)}
                                </TableCell>
                                <TableCell className="text-right">
                                    {formatClassification(newRegime.classification)}
                                </TableCell>
                            </TableRow>
                            <TableRow>
                                <TableCell>Turnover</TableCell>
                                <TableCell className="text-right">{formatCurrency(oldRegime.turnover)}</TableCell>
                                <TableCell className="text-right">{formatCurrency(newRegime.turnover)}</TableCell>
                            </TableRow>
                            <TableRow>
                                <TableCell>Accounting Profit</TableCell>
                                <TableCell className="text-right">{formatCurrency(oldRegime.accountingProfit)}</TableCell>
                                <TableCell className="text-right">{formatCurrency(newRegime.accountingProfit)}</TableCell>
                            </TableRow>
                            <TableRow>
                                <TableCell>Adjustments (Net)</TableCell>
                                <TableCell className="text-right">{formatCurrency(oldRegime.adjustments.netAdjustment)}</TableCell>
                                <TableCell className="text-right">{formatCurrency(newRegime.adjustments.netAdjustment)}</TableCell>
                            </TableRow>
                            <TableRow>
                                <TableCell>Capital Allowances</TableCell>
                                <TableCell className="text-right">{formatCurrency(oldRegime.capitalAllowances.totalAllowance)}</TableCell>
                                <TableCell className="text-right">{formatCurrency(newRegime.capitalAllowances.totalAllowance)}</TableCell>
                            </TableRow>
                            <TableRow>
                                <TableCell>Taxable Profit</TableCell>
                                <TableCell className="text-right">{formatCurrency(oldRegime.taxableProfit)}</TableCell>
                                <TableCell className="text-right">{formatCurrency(newRegime.taxableProfit)}</TableCell>
                            </TableRow>
                            <TableRow>
                                <TableCell>Tax Rate</TableCell>
                                <TableCell className="text-right">{formatPercentage(oldRegime.taxRate)}</TableCell>
                                <TableCell className="text-right">{formatPercentage(newRegime.taxRate)}</TableCell>
                            </TableRow>
                            <TableRow className="font-bold">
                                <TableCell>Tax Liability</TableCell>
                                <TableCell className="text-right">{formatCurrency(oldRegime.taxLiability)}</TableCell>
                                <TableCell className="text-right">{formatCurrency(newRegime.taxLiability)}</TableCell>
                            </TableRow>
                            <TableRow>
                                <TableCell>Effective Rate</TableCell>
                                <TableCell className="text-right">{formatPercentage(oldRegime.effectiveRate)}</TableCell>
                                <TableCell className="text-right">{formatPercentage(newRegime.effectiveRate)}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    );
}
```

### Optimizations Panel

**File:** `resources/js/pages/App/Clients/Calculations/components/OptimizationsPanel.tsx`

```tsx
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { getPriorityColor, formatCurrency } from '@/utils/calculation';
import { Lightbulb, Clock, Building, FileCheck } from 'lucide-react';
import type { OptimizationsPanelProps } from '@/types/props/calculation';
import type { OptimizationType } from '@/types/calculation';

const typeIcons: Record<OptimizationType, React.ReactNode> = {
    timing: <Clock className="h-5 w-5" />,
    structural: <Building className="h-5 w-5" />,
    compliance: <FileCheck className="h-5 w-5" />,
};

export function OptimizationsPanel({ optimizations }: OptimizationsPanelProps) {
    if (optimizations.length === 0) {
        return (
            <Card>
                <CardContent className="flex flex-col items-center justify-center py-12">
                    <Lightbulb className="h-12 w-12 text-gray-400" />
                    <p className="mt-4 text-gray-500 dark:text-gray-400">
                        No optimization opportunities identified.
                    </p>
                </CardContent>
            </Card>
        );
    }

    return (
        <div className="space-y-4">
            {optimizations.map((opt, index) => (
                <Card key={index}>
                    <CardHeader>
                        <div className="flex items-start justify-between">
                            <div className="flex items-center gap-3">
                                <div className="text-gray-500 dark:text-gray-400">
                                    {typeIcons[opt.type]}
                                </div>
                                <div>
                                    <CardTitle className="text-lg">{opt.title}</CardTitle>
                                    <CardDescription className="capitalize">{opt.type}</CardDescription>
                                </div>
                            </div>
                            <Badge className={getPriorityColor(opt.priority)}>
                                {opt.priority} priority
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <p className="text-gray-700 dark:text-gray-300">{opt.description}</p>
                        <div className="flex items-center justify-between rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                            <span className="text-sm text-gray-600 dark:text-gray-400">
                                Estimated Savings
                            </span>
                            <span className="font-semibold text-green-600 dark:text-green-400">
                                {typeof opt.estimatedSavings === 'number'
                                    ? formatCurrency(opt.estimatedSavings)
                                    : opt.estimatedSavings}
                            </span>
                        </div>
                        <div className="rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-950">
                            <p className="text-sm text-blue-700 dark:text-blue-300">
                                <strong>Action Required:</strong> {opt.actionRequired}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}
```

### Insights List

**File:** `resources/js/pages/App/Clients/Calculations/components/InsightsList.tsx`

```tsx
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { TrendingDown, Lightbulb, AlertTriangle, Info } from 'lucide-react';
import type { InsightsListProps } from '@/types/props/calculation';
import type { Insight } from '@/types/calculation';

const insightIcons: Record<Insight['type'], React.ReactNode> = {
    savings: <TrendingDown className="h-5 w-5 text-green-500" />,
    opportunity: <Lightbulb className="h-5 w-5 text-yellow-500" />,
    warning: <AlertTriangle className="h-5 w-5 text-red-500" />,
    summary: <Info className="h-5 w-5 text-blue-500" />,
};

const insightStyles: Record<Insight['type'], string> = {
    savings: 'border-l-green-500 bg-green-50 dark:bg-green-950',
    opportunity: 'border-l-yellow-500 bg-yellow-50 dark:bg-yellow-950',
    warning: 'border-l-red-500 bg-red-50 dark:bg-red-950',
    summary: 'border-l-blue-500 bg-blue-50 dark:bg-blue-950',
};

export function InsightsList({ insights }: InsightsListProps) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Key Insights</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
                {insights.map((insight, index) => (
                    <div
                        key={index}
                        className={`flex gap-3 rounded-lg border-l-4 p-4 ${insightStyles[insight.type]}`}
                    >
                        <div className="flex-shrink-0">{insightIcons[insight.type]}</div>
                        <div>
                            <h4 className="font-medium text-gray-900 dark:text-white">
                                {insight.title}
                            </h4>
                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                {insight.description}
                            </p>
                        </div>
                    </div>
                ))}
            </CardContent>
        </Card>
    );
}
```

---

## Chart Components

### Comparison Bar Chart

**File:** `resources/js/pages/App/Clients/Calculations/components/ComparisonBarChart.tsx`

```tsx
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
    ResponsiveContainer,
} from 'recharts';
import { prepareComparisonBarData, formatTooltipValue } from '@/utils/chartHelpers';
import type { ComparisonBarChartProps } from '@/types/props/calculation';

export function ComparisonBarChart({ results, height = 300 }: ComparisonBarChartProps) {
    const data = prepareComparisonBarData(results);

    return (
        <Card>
            <CardHeader>
                <CardTitle>Tax Liability by Type</CardTitle>
            </CardHeader>
            <CardContent>
                <ResponsiveContainer width="100%" height={height}>
                    <BarChart data={data} margin={{ top: 20, right: 30, left: 20, bottom: 5 }}>
                        <CartesianGrid strokeDasharray="3 3" className="stroke-gray-200 dark:stroke-gray-700" />
                        <XAxis
                            dataKey="name"
                            tick={{ fill: 'currentColor' }}
                            className="text-gray-600 dark:text-gray-400"
                        />
                        <YAxis
                            tickFormatter={(value) => `₦${(value / 1000000).toFixed(1)}M`}
                            tick={{ fill: 'currentColor' }}
                            className="text-gray-600 dark:text-gray-400"
                        />
                        <Tooltip
                            formatter={(value: number) => formatTooltipValue(value)}
                            contentStyle={{
                                backgroundColor: 'var(--background)',
                                borderColor: 'var(--border)',
                            }}
                        />
                        <Legend />
                        <Bar dataKey="oldRegime" name="Old Regime" fill="#6b7280" />
                        <Bar dataKey="newRegime" name="New Regime (NTA 2025)" fill="#3b82f6" />
                    </BarChart>
                </ResponsiveContainer>
            </CardContent>
        </Card>
    );
}
```

### Tax Composition Pie

**File:** `resources/js/pages/App/Clients/Calculations/components/TaxCompositionPie.tsx`

```tsx
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { PieChart, Pie, Cell, ResponsiveContainer, Tooltip, Legend } from 'recharts';
import { prepareCompositionPieData, formatTooltipValue } from '@/utils/chartHelpers';
import type { TaxCompositionPieProps } from '@/types/props/calculation';

export function TaxCompositionPie({ totals, title, height = 250 }: TaxCompositionPieProps) {
    const data = prepareCompositionPieData(totals, 'old');

    if (data.length === 0) {
        return null;
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-base">{title}</CardTitle>
            </CardHeader>
            <CardContent>
                <ResponsiveContainer width="100%" height={height}>
                    <PieChart>
                        <Pie
                            data={data}
                            cx="50%"
                            cy="50%"
                            innerRadius={40}
                            outerRadius={80}
                            paddingAngle={2}
                            dataKey="value"
                            nameKey="name"
                        >
                            {data.map((entry, index) => (
                                <Cell key={`cell-${index}`} fill={entry.color} />
                            ))}
                        </Pie>
                        <Tooltip
                            formatter={(value: number) => formatTooltipValue(value)}
                            contentStyle={{
                                backgroundColor: 'var(--background)',
                                borderColor: 'var(--border)',
                            }}
                        />
                        <Legend />
                    </PieChart>
                </ResponsiveContainer>
            </CardContent>
        </Card>
    );
}
```

### Calculation List

**File:** `resources/js/pages/App/Clients/Calculations/components/CalculationList.tsx`

```tsx
import { Link } from '@inertiajs/react';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import {
    formatCalculationType,
    formatCalculationStatus,
    getStatusColor,
    formatCurrency,
    getSavingsIndicator,
} from '@/utils/calculation';
import { format } from 'date-fns';
import { ChevronRight } from 'lucide-react';
import type { CalculationListProps } from '@/types/props/calculation';

export function CalculationList({ items, clientId }: CalculationListProps) {
    return (
        <Card>
            <CardContent className="p-0">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Fiscal Year</TableHead>
                            <TableHead>Type</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead className="text-right">Old Regime</TableHead>
                            <TableHead className="text-right">New Regime</TableHead>
                            <TableHead className="text-right">Impact</TableHead>
                            <TableHead>Date</TableHead>
                            <TableHead></TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {items.map((item) => {
                            const savingsInfo = getSavingsIndicator(item.savings);
                            return (
                                <TableRow key={item.id}>
                                    <TableCell className="font-medium">{item.fiscalYear}</TableCell>
                                    <TableCell>{formatCalculationType(item.calculationType)}</TableCell>
                                    <TableCell>
                                        <Badge className={getStatusColor(item.status)}>
                                            {formatCalculationStatus(item.status)}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {formatCurrency(item.oldTotal)}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {formatCurrency(item.newTotal)}
                                    </TableCell>
                                    <TableCell className={`text-right ${savingsInfo.color}`}>
                                        {savingsInfo.text}
                                    </TableCell>
                                    <TableCell>
                                        {format(new Date(item.createdAt), 'MMM d, yyyy')}
                                    </TableCell>
                                    <TableCell>
                                        <Link
                                            href={route('app.clients.calculations.show', {
                                                client: clientId,
                                                calculation: item.id,
                                            })}
                                            className="flex items-center text-blue-600 hover:text-blue-800 dark:text-blue-400"
                                        >
                                            View
                                            <ChevronRight className="h-4 w-4" />
                                        </Link>
                                    </TableCell>
                                </TableRow>
                            );
                        })}
                    </TableBody>
                </Table>
            </CardContent>
        </Card>
    );
}
```

---

## Component Summary

| Component | Purpose |
|-----------|---------|
| `Index` | Calculation history list page |
| `Create` | Run calculation form page |
| `Show` | Results display page |
| `CalculationHeader` | Summary header with totals |
| `SavingsBanner` | Net savings/increase highlight |
| `ResultsTabs` | Tab navigation for tax types |
| `CITResultsPanel` | Company Income Tax details |
| `PITResultsPanel` | Personal Income Tax details |
| `VATResultsPanel` | VAT analysis details |
| `CGTResultsPanel` | Capital Gains Tax details |
| `DevelopmentLevyPanel` | Development levy details |
| `OptimizationsPanel` | Optimization opportunities |
| `InsightsList` | Key insights display |
| `ComparisonBarChart` | Old vs new regime bar chart |
| `TaxCompositionPie` | Tax composition pie chart |
| `CalculationList` | Calculation history table |

---

## Dependencies

Install recharts for visualizations:

```bash
npm install recharts
```

---

## Next Steps

Once components are implemented, proceed to:
→ **07_TESTING_STRATEGY.md** - Testing approach for calculations
