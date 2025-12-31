# TaxLab Implementation - Module 5: Frontend Types

## Overview

This document defines the TypeScript type definitions for the tax calculation module, following the project's central type management approach.

---

## Type Index Update

**File:** `resources/js/types/index.ts`

Add export for calculation types:

```typescript
export * from './calculation';
```

---

## Calculation Types

**File:** `resources/js/types/calculation.ts`

```typescript
export type CalculationType =
    | 'full_analysis'
    | 'cit_only'
    | 'pit_only'
    | 'vat_only'
    | 'cgt_only'
    | 'paye_only'
    | 'quick_estimate';

export type CalculationStatus = 'completed' | 'error' | 'superseded';

export type TaxRegime = 'old' | 'new';

export type CompanyClassification = 'small' | 'medium' | 'large';

export type OptimizationType = 'timing' | 'structural' | 'compliance';

export type OptimizationPriority = 'high' | 'medium' | 'low';

export interface RegimeComparison {
    oldLiability: number;
    newLiability: number;
    difference: number;
    savings: number;
    increase: number;
    percentageChange: number;
    favors: 'old' | 'new' | 'neutral';
}

export interface TaxBandResult {
    band: string;
    rate: number;
    taxableAmount: number;
    tax: number;
}

export interface CITResult {
    classification: CompanyClassification;
    turnover: number;
    accountingProfit: number;
    adjustments: {
        addBacks: number;
        deductions: number;
        netAdjustment: number;
    };
    adjustedProfit: number;
    capitalAllowances: {
        assets: CapitalAllowanceAsset[];
        totalAllowance: number;
    };
    taxableProfit: number;
    taxRate: number;
    calculatedTax: number;
    minimumTax: number;
    minimumTaxApplies: boolean;
    taxLiability: number;
    effectiveRate: number;
}

export interface CapitalAllowanceAsset {
    class: string;
    cost: number;
    yearAcquired: number;
    currentYear: number;
    initialRate: number;
    annualRate: number;
    currentAllowance: number;
    cumulativeAllowance: number;
    writtenDownValue: number;
}

export interface PITResult {
    incomeBreakdown: {
        employment: number;
        rental: number;
        business: number;
        investment: number;
        other: number;
    };
    grossIncome: number;
    reliefs: {
        consolidatedRelief?: number;
        pensionRelief?: number;
        nhfRelief?: number;
        lifeInsurance?: number;
        exemption?: number;
        total: number;
        breakdown: Array<{ type: string; amount: number }>;
    };
    taxableIncome: number;
    taxByBand: TaxBandResult[];
    taxLiability: number;
    effectiveRate: number;
}

export interface VATResult {
    sales: {
        vatable: number;
        exempt: number;
        zeroRated: number;
        total: number;
    };
    outputVat: number;
    inputVat: {
        goods: VATInputCategory;
        services: VATInputCategory;
        capital: VATInputCategory;
    };
    totalInputVat: number;
    totalRecoverable: number;
    nonRecoverable: number;
    netVatPayable: number;
    vatRefundable: number;
    taxLiability: number;
}

export interface VATInputCategory {
    total: number;
    recoverable: number;
    recoveryAllowed: boolean;
}

export interface CGTResult {
    entityType: string;
    disposals: CGTDisposal[];
    totalProceeds: number;
    totalCost: number;
    totalGain: number;
    totalExemptions: number;
    taxableGain: number;
    rate: number;
    taxLiability: number;
}

export interface CGTDisposal {
    asset: string;
    assetType: string;
    proceeds: number;
    acquisitionCost: number;
    improvementCost: number;
    incidentalCost: number;
    totalCost: number;
    gain: number;
    exemption: number;
}

export interface DevelopmentLevyResult {
    inputValues: {
        assessableProfit: number;
        profitBeforeTax: number;
        netProfit: number;
    };
    levies: Record<string, LevyItem>;
    totalLevy: number;
    combinedRate: number;
    taxLiability: number;
}

export interface LevyItem {
    baseType: string;
    baseAmount: number;
    rate: number;
    amount: number;
}

export interface PAYEResult {
    employeeCount: number;
    totalGrossSalaries: number;
    totalPayeDue: number;
    averageTaxRate: number;
    employees: PAYEEmployeeResult[];
    taxLiability: number;
}

export interface PAYEEmployeeResult {
    employeeId?: string;
    name: string;
    grossSalary: number;
    taxableIncome: number;
    payeDue: number;
    effectiveRate: number;
}

export interface TaxTypeResult<T> {
    type: string;
    oldRegime: T;
    newRegime: T;
    comparison: RegimeComparison & Record<string, unknown>;
}

export interface CalculationResults {
    cit?: TaxTypeResult<CITResult>;
    pit?: TaxTypeResult<PITResult>;
    vat?: TaxTypeResult<VATResult>;
    cgt?: TaxTypeResult<CGTResult>;
    developmentLevy?: TaxTypeResult<DevelopmentLevyResult>;
    paye?: TaxTypeResult<PAYEResult>;
}

export interface RegimeTotals {
    cit?: number;
    pit?: number;
    vat?: number;
    cgt?: number;
    developmentLevy?: number;
    paye?: number;
    total: number;
}

export interface OverallComparison {
    oldTotal: number;
    newTotal: number;
    netSavings: number;
    netIncrease: number;
    savingsPercentage: number;
    favors: 'old_regime' | 'new_regime' | 'neutral';
}

export interface PrimaryDriver {
    taxType: string;
    impact: number;
    direction: 'savings' | 'increase';
    description: string;
}

export interface CalculationSummary {
    oldRegimeTotal: RegimeTotals;
    newRegimeTotal: RegimeTotals;
    overallComparison: OverallComparison;
    primaryDrivers: PrimaryDriver[];
    taxTypeCount: number;
    generatedAt: string;
}

export interface Optimization {
    type: OptimizationType;
    title: string;
    description: string;
    estimatedSavings: number | string;
    priority: OptimizationPriority;
    actionRequired: string;
}

export interface Insight {
    type: 'savings' | 'opportunity' | 'warning' | 'summary';
    category: string;
    title: string;
    description: string;
}

export interface Calculation {
    id: string;
    taxClientId: string;
    firmId: string;
    fiscalYear: number;
    calculationType: CalculationType;
    rulesVersion: string;
    financialVersion: number;
    inputs: Record<string, unknown>;
    results: CalculationResults;
    summary: CalculationSummary;
    optimizations: Optimization[];
    status: CalculationStatus;
    errorMessage?: string;
    performedBy: string;
    calculationTimeMs: number;
    createdAt: string;
}

export interface CalculationListItem {
    id: string;
    fiscalYear: number;
    calculationType: CalculationType;
    status: CalculationStatus;
    oldTotal: number;
    newTotal: number;
    savings: number;
    performedBy: string;
    performedByName?: string;
    createdAt: string;
}

export interface CalculationTypeOption {
    value: CalculationType;
    label: string;
    description: string;
}

export interface FinancialYearOption {
    id: string;
    fiscalYear: number;
    isComplete: boolean;
    dataSource: string;
    version: number;
}
```

---

## Utility Functions

**File:** `resources/js/utils/calculation.ts`

```typescript
import type {
    CalculationType,
    CalculationStatus,
    CompanyClassification,
    OptimizationPriority,
    TaxRegime,
} from '@/types/calculation';

export function formatCurrency(value: number): string {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
}

export function formatPercentage(value: number): string {
    return `${(value * 100).toFixed(2)}%`;
}

export function formatCalculationType(type: CalculationType): string {
    const labels: Record<CalculationType, string> = {
        full_analysis: 'Full Analysis',
        cit_only: 'CIT Only',
        pit_only: 'PIT Only',
        vat_only: 'VAT Only',
        cgt_only: 'CGT Only',
        paye_only: 'PAYE Only',
        quick_estimate: 'Quick Estimate',
    };
    return labels[type] || type;
}

export function formatCalculationStatus(status: CalculationStatus): string {
    const labels: Record<CalculationStatus, string> = {
        completed: 'Completed',
        error: 'Error',
        superseded: 'Superseded',
    };
    return labels[status] || status;
}

export function getStatusColor(status: CalculationStatus): string {
    const colors: Record<CalculationStatus, string> = {
        completed: 'text-green-600 dark:text-green-400',
        error: 'text-red-600 dark:text-red-400',
        superseded: 'text-gray-500 dark:text-gray-400',
    };
    return colors[status] || '';
}

export function formatClassification(classification: CompanyClassification): string {
    const labels: Record<CompanyClassification, string> = {
        small: 'Small Company',
        medium: 'Medium Company',
        large: 'Large Company',
    };
    return labels[classification] || classification;
}

export function formatRegime(regime: TaxRegime): string {
    const labels: Record<TaxRegime, string> = {
        old: 'Old Regime',
        new: 'New Regime (NTA 2025)',
    };
    return labels[regime] || regime;
}

export function getPriorityColor(priority: OptimizationPriority): string {
    const colors: Record<OptimizationPriority, string> = {
        high: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        medium: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        low: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    };
    return colors[priority] || '';
}

export function formatTaxType(taxType: string): string {
    const labels: Record<string, string> = {
        cit: 'Company Income Tax',
        pit: 'Personal Income Tax',
        vat: 'Value Added Tax',
        cgt: 'Capital Gains Tax',
        development_levy: 'Development Levy',
        developmentLevy: 'Development Levy',
        paye: 'PAYE',
    };
    return labels[taxType] || taxType;
}

export function getSavingsIndicator(savings: number): {
    text: string;
    color: string;
    icon: 'up' | 'down' | 'neutral';
} {
    if (savings > 0) {
        return {
            text: `Save ${formatCurrency(savings)}`,
            color: 'text-green-600 dark:text-green-400',
            icon: 'down',
        };
    }
    if (savings < 0) {
        return {
            text: `Increase ${formatCurrency(Math.abs(savings))}`,
            color: 'text-red-600 dark:text-red-400',
            icon: 'up',
        };
    }
    return {
        text: 'No change',
        color: 'text-gray-600 dark:text-gray-400',
        icon: 'neutral',
    };
}

export function calculateSavingsPercentage(oldValue: number, newValue: number): number {
    if (oldValue === 0) return 0;
    return ((oldValue - newValue) / oldValue) * 100;
}
```

---

## Chart Data Helpers

**File:** `resources/js/utils/chartHelpers.ts`

```typescript
import type { CalculationResults, RegimeTotals } from '@/types/calculation';
import { formatTaxType, formatCurrency } from './calculation';

export interface BarChartData {
    name: string;
    oldRegime: number;
    newRegime: number;
}

export interface PieChartData {
    name: string;
    value: number;
    color: string;
}

const TAX_TYPE_COLORS: Record<string, string> = {
    cit: '#3b82f6',
    pit: '#8b5cf6',
    vat: '#10b981',
    cgt: '#f59e0b',
    developmentLevy: '#ef4444',
    paye: '#6366f1',
};

export function prepareComparisonBarData(results: CalculationResults): BarChartData[] {
    const data: BarChartData[] = [];

    Object.entries(results).forEach(([key, result]) => {
        if (result) {
            data.push({
                name: formatTaxType(key),
                oldRegime: result.oldRegime?.taxLiability || 0,
                newRegime: result.newRegime?.taxLiability || 0,
            });
        }
    });

    return data;
}

export function prepareCompositionPieData(
    totals: RegimeTotals,
    regime: 'old' | 'new'
): PieChartData[] {
    const data: PieChartData[] = [];

    const entries = Object.entries(totals).filter(([key]) => key !== 'total');

    entries.forEach(([key, value]) => {
        if (typeof value === 'number' && value > 0) {
            data.push({
                name: formatTaxType(key),
                value,
                color: TAX_TYPE_COLORS[key] || '#9ca3af',
            });
        }
    });

    return data;
}

export interface WaterfallChartData {
    name: string;
    value: number;
    isTotal?: boolean;
    isPositive?: boolean;
}

export function prepareWaterfallData(results: CalculationResults): WaterfallChartData[] {
    const data: WaterfallChartData[] = [];
    let runningTotal = 0;

    Object.entries(results).forEach(([key, result]) => {
        if (result?.comparison) {
            const diff = (result.comparison.oldLiability || 0) - (result.comparison.newLiability || 0);
            if (diff !== 0) {
                data.push({
                    name: formatTaxType(key),
                    value: diff,
                    isPositive: diff > 0,
                });
                runningTotal += diff;
            }
        }
    });

    data.push({
        name: 'Net Impact',
        value: runningTotal,
        isTotal: true,
        isPositive: runningTotal > 0,
    });

    return data;
}

export function formatTooltipValue(value: number): string {
    return formatCurrency(value);
}
```

---

## Custom Hooks

**File:** `resources/js/hooks/useCalculation.ts`

```typescript
import { useState, useCallback } from 'react';
import { router } from '@inertiajs/react';
import type { CalculationType } from '@/types/calculation';

interface UseCalculationOptions {
    clientId: string;
}

interface RunCalculationParams {
    fiscalYear: number;
    calculationType: CalculationType;
}

export function useCalculation({ clientId }: UseCalculationOptions) {
    const [isCalculating, setIsCalculating] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const runCalculation = useCallback(
        ({ fiscalYear, calculationType }: RunCalculationParams) => {
            setIsCalculating(true);
            setError(null);

            router.post(
                route('app.clients.calculations.store', { client: clientId }),
                {
                    fiscal_year: fiscalYear,
                    calculation_type: calculationType,
                },
                {
                    onSuccess: () => {
                        setIsCalculating(false);
                    },
                    onError: (errors) => {
                        setIsCalculating(false);
                        setError(Object.values(errors).flat().join(', '));
                    },
                }
            );
        },
        [clientId]
    );

    return {
        isCalculating,
        error,
        runCalculation,
    };
}
```

**File:** `resources/js/hooks/useCalculationHistory.ts`

```typescript
import { useMemo } from 'react';
import type { Calculation, CalculationListItem } from '@/types/calculation';

interface UseCalculationHistoryOptions {
    calculations: Calculation[];
}

export function useCalculationHistory({ calculations }: UseCalculationHistoryOptions) {
    const listItems = useMemo<CalculationListItem[]>(() => {
        return calculations.map((calc) => ({
            id: calc.id,
            fiscalYear: calc.fiscalYear,
            calculationType: calc.calculationType,
            status: calc.status,
            oldTotal: calc.summary.oldRegimeTotal.total,
            newTotal: calc.summary.newRegimeTotal.total,
            savings: calc.summary.overallComparison.netSavings,
            performedBy: calc.performedBy,
            createdAt: calc.createdAt,
        }));
    }, [calculations]);

    const byFiscalYear = useMemo(() => {
        const grouped: Record<number, CalculationListItem[]> = {};
        listItems.forEach((item) => {
            if (!grouped[item.fiscalYear]) {
                grouped[item.fiscalYear] = [];
            }
            grouped[item.fiscalYear].push(item);
        });
        return grouped;
    }, [listItems]);

    const latestByYear = useMemo(() => {
        const latest: Record<number, CalculationListItem> = {};
        Object.entries(byFiscalYear).forEach(([year, items]) => {
            const completed = items.filter((i) => i.status === 'completed');
            if (completed.length > 0) {
                latest[Number(year)] = completed[0];
            }
        });
        return latest;
    }, [byFiscalYear]);

    return {
        listItems,
        byFiscalYear,
        latestByYear,
        isEmpty: listItems.length === 0,
    };
}
```

---

## Props Interfaces

**File:** `resources/js/types/props/calculation.ts`

```typescript
import type {
    Calculation,
    CalculationListItem,
    CalculationTypeOption,
    FinancialYearOption,
    Insight,
    Optimization,
    TaxTypeResult,
    CITResult,
    PITResult,
    VATResult,
    CGTResult,
    DevelopmentLevyResult,
    PAYEResult,
} from '@/types/calculation';
import type { TaxClient } from '@/types/client';

export interface CalculationIndexProps {
    client: TaxClient;
    calculations: Calculation[];
}

export interface CalculationCreateProps {
    client: TaxClient;
    financials: FinancialYearOption[];
    calculationTypes: CalculationTypeOption[];
}

export interface CalculationShowProps {
    client: TaxClient;
    calculation: Calculation;
    insights: Insight[];
}

export interface CalculationHeaderProps {
    calculation: Calculation;
}

export interface SavingsBannerProps {
    oldTotal: number;
    newTotal: number;
    savingsPercentage: number;
}

export interface ResultsTabsProps {
    results: Calculation['results'];
    activeTab: string;
    onTabChange: (tab: string) => void;
}

export interface CITResultsPanelProps {
    result: TaxTypeResult<CITResult>;
}

export interface PITResultsPanelProps {
    result: TaxTypeResult<PITResult>;
}

export interface VATResultsPanelProps {
    result: TaxTypeResult<VATResult>;
}

export interface CGTResultsPanelProps {
    result: TaxTypeResult<CGTResult>;
}

export interface DevelopmentLevyPanelProps {
    result: TaxTypeResult<DevelopmentLevyResult>;
}

export interface PAYEPanelProps {
    result: TaxTypeResult<PAYEResult>;
}

export interface OptimizationsPanelProps {
    optimizations: Optimization[];
}

export interface InsightsListProps {
    insights: Insight[];
}

export interface ComparisonBarChartProps {
    results: Calculation['results'];
    height?: number;
}

export interface TaxCompositionPieProps {
    totals: Calculation['summary']['oldRegimeTotal'] | Calculation['summary']['newRegimeTotal'];
    title: string;
    height?: number;
}

export interface CalculationListProps {
    items: CalculationListItem[];
    clientId: string;
}
```

---

## Type Summary

| Type/Interface | Purpose |
|----------------|---------|
| `CalculationType` | Calculation type enum |
| `CalculationStatus` | Calculation status enum |
| `TaxRegime` | Old vs New regime |
| `Calculation` | Full calculation entity |
| `CalculationResults` | All tax type results |
| `CalculationSummary` | Aggregated summary |
| `Optimization` | Optimization opportunity |
| `Insight` | Generated insight |
| `CITResult` | Company Income Tax result |
| `PITResult` | Personal Income Tax result |
| `VATResult` | VAT analysis result |
| `CGTResult` | Capital Gains Tax result |
| `DevelopmentLevyResult` | Development levy result |
| `PAYEResult` | PAYE analysis result |

---

## Next Steps

Once types are defined, proceed to:
→ **06_FRONTEND_COMPONENTS.md** - React component implementations
