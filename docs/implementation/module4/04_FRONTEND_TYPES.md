# TaxLab Implementation - Module 4 Frontend Types

## Overview

This document defines the TypeScript type definitions for the Financial Data Entry module.

---

## Type Definitions

**File:** `resources/js/types/financial.ts`

```typescript
export type DataSource = 'audited' | 'management' | 'computed' | 'projected';

export type FinancialSection =
    | 'income'
    | 'balance_sheet'
    | 'employment'
    | 'vat'
    | 'capital'
    | 'individual'
    | 'partnership';

export interface DataSourceOption {
    value: DataSource;
    label: string;
    shortLabel: string;
    color: string;
}

export interface IncomeData {
    turnover?: number;
    costOfSales?: number;
    grossProfit?: number;
    operatingExpenses?: number;
    administrativeExpenses?: number;
    depreciation?: number;
    interestIncome?: number;
    interestExpense?: number;
    dividendIncome?: number;
    otherIncome?: number;
    profitBeforeTax?: number;
    taxExpense?: number;
    profitAfterTax?: number;
}

export interface BalanceSheetData {
    totalAssets?: number;
    fixedAssets?: number;
    currentAssets?: number;
    totalLiabilities?: number;
    longTermLiabilities?: number;
    currentLiabilities?: number;
    shareCapital?: number;
    retainedEarnings?: number;
    totalEquity?: number;
}

export interface EmploymentData {
    employeeCount?: number;
    totalPayroll?: number;
    payeRemitted?: number;
    pensionEmployer?: number;
    pensionEmployee?: number;
    nhfContributions?: number;
    nsitfContributions?: number;
    itfContributions?: number;
}

export interface VatData {
    vatableSales?: number;
    vatOutput?: number;
    exemptSales?: number;
    zeroRatedSales?: number;
    vatablePurchases?: number;
    vatInputGoods?: number;
    vatInputServices?: number;
    vatInputCapital?: number;
    vatRemitted?: number;
}

export interface CapitalData {
    assetDisposals?: number;
    assetDisposalCosts?: number;
    capitalGainsRealized?: number;
    capitalAllowancesClaimed?: number;
}

export interface IndividualData {
    grossSalary?: number;
    bonuses?: number;
    benefitsInKind?: number;
    employerPensionContribution?: number;
    employeePensionContribution?: number;
    payeDeducted?: number;
    rentalIncome?: number;
    rentalExpenses?: number;
    interestIncomePersonal?: number;
    dividendIncomePersonal?: number;
    businessIncome?: number;
    businessExpenses?: number;
    otherIncomePersonal?: number;
    rentPaid?: number;
    lifeInsurancePremium?: number;
    mortgageInterest?: number;
    numberOfDependents?: number;
    disabilityStatus?: boolean;
    medicalExpenses?: number;
}

export interface PartnershipData {
    numberOfPartners?: number;
    partnerProfitShares?: Record<string, number>;
    partnerCapitalAccounts?: Record<string, number>;
    partnerDrawings?: Record<string, number>;
}

export interface ClientFinancial {
    id: string;
    taxClientId: string;
    fiscalYear: number;
    fiscalYearStart: string;
    fiscalYearEnd: string;
    dataSource: DataSource;
    dataSourceLabel: string;
    incomeData: IncomeData;
    balanceSheetData: BalanceSheetData;
    employmentData: EmploymentData;
    vatData: VatData;
    capitalData: CapitalData;
    individualData?: IndividualData;
    partnershipData?: PartnershipData;
    isComplete: boolean;
    isLocked: boolean;
    lockedAt?: string;
    lockedBy?: string;
    version: number;
    verifiedAt?: string;
    verifiedBy?: string;
    completionPercentage: number;
    notes?: string;
    createdAt: string;
    updatedAt: string;
}

export interface FinancialVersion {
    version: number;
    changeSummary?: string;
    createdBy: string;
    createdAt: string;
}

export interface FinancialReadiness {
    isReady: boolean;
    completionPercentage: number;
    dataSource?: string;
    warningsCount: number;
    warnings: FinancialWarning[];
    errors: FinancialError[];
    isLocked: boolean;
    isVerified: boolean;
}

export interface FinancialWarning {
    type: 'validation' | 'reasonableness';
    section: FinancialSection;
    field: string;
    message: string;
    value?: string;
    severity: 'info' | 'warning' | 'error';
}

export interface FinancialError {
    section: FinancialSection;
    field: string;
    message: string;
    expected: number | string;
    actual: number | string;
}

export interface FinancialFormData {
    fiscalYear: number;
    fiscalYearStart: string;
    fiscalYearEnd: string;
    dataSource: DataSource;
    incomeData?: IncomeData;
    balanceSheetData?: BalanceSheetData;
    employmentData?: EmploymentData;
    vatData?: VatData;
    capitalData?: CapitalData;
    individualData?: IndividualData;
    partnershipData?: PartnershipData;
    notes?: string;
}

export interface FinancialFieldConfig {
    key: string;
    label: string;
    description?: string;
    type: 'currency' | 'number' | 'percentage' | 'boolean';
    required?: boolean;
    calculated?: boolean;
    calculationFormula?: string;
}

export interface FinancialSectionConfig {
    key: FinancialSection;
    label: string;
    icon: string;
    fields: FinancialFieldConfig[];
}

export interface ImportPreviewRow {
    row: number;
    fiscalYear: number | string;
    turnover: number;
    profitBeforeTax: number;
}

export interface ImportResult {
    success: number;
    failed: number;
    errors: { row: number; message: string }[];
    imported: { row: number; fiscalYear: number; id: string }[];
}

export interface ImportTargetField {
    key: string;
    label: string;
    required: boolean;
}
```

---

## Field Configuration Constants

**File:** `resources/js/config/financial-fields.ts`

```typescript
import type { FinancialFieldConfig, FinancialSectionConfig } from '@/types/financial';

export const incomeFields: FinancialFieldConfig[] = [
    {
        key: 'turnover',
        label: 'Turnover/Revenue',
        description: 'Total gross revenue from sales and services',
        type: 'currency',
        required: true,
    },
    {
        key: 'costOfSales',
        label: 'Cost of Sales',
        description: 'Direct costs of goods/services sold',
        type: 'currency',
    },
    {
        key: 'grossProfit',
        label: 'Gross Profit',
        description: 'Revenue minus cost of sales',
        type: 'currency',
        required: true,
        calculated: true,
        calculationFormula: 'turnover - costOfSales',
    },
    {
        key: 'operatingExpenses',
        label: 'Operating Expenses',
        description: 'Total operating costs',
        type: 'currency',
    },
    {
        key: 'administrativeExpenses',
        label: 'Administrative Expenses',
        description: 'Admin and overhead costs',
        type: 'currency',
    },
    {
        key: 'depreciation',
        label: 'Depreciation',
        description: 'Accounting depreciation on assets',
        type: 'currency',
    },
    {
        key: 'interestIncome',
        label: 'Interest Income',
        description: 'Interest received on investments',
        type: 'currency',
    },
    {
        key: 'interestExpense',
        label: 'Interest Expense',
        description: 'Interest paid on loans',
        type: 'currency',
    },
    {
        key: 'dividendIncome',
        label: 'Dividend Income',
        description: 'Dividends received from investments',
        type: 'currency',
    },
    {
        key: 'otherIncome',
        label: 'Other Income',
        description: 'Miscellaneous income',
        type: 'currency',
    },
    {
        key: 'profitBeforeTax',
        label: 'Profit Before Tax',
        description: 'Net profit before taxation',
        type: 'currency',
        required: true,
    },
    {
        key: 'taxExpense',
        label: 'Tax Expense',
        description: 'Tax paid or provided',
        type: 'currency',
    },
    {
        key: 'profitAfterTax',
        label: 'Profit After Tax',
        description: 'Net profit after taxation',
        type: 'currency',
    },
];

export const balanceSheetFields: FinancialFieldConfig[] = [
    {
        key: 'totalAssets',
        label: 'Total Assets',
        description: 'All assets of the entity',
        type: 'currency',
        required: true,
    },
    {
        key: 'fixedAssets',
        label: 'Fixed Assets',
        description: 'Non-current assets (property, plant, equipment)',
        type: 'currency',
        required: true,
    },
    {
        key: 'currentAssets',
        label: 'Current Assets',
        description: 'Short-term assets (cash, inventory, receivables)',
        type: 'currency',
    },
    {
        key: 'totalLiabilities',
        label: 'Total Liabilities',
        description: 'All liabilities of the entity',
        type: 'currency',
        required: true,
    },
    {
        key: 'longTermLiabilities',
        label: 'Long-term Liabilities',
        description: 'Non-current liabilities',
        type: 'currency',
    },
    {
        key: 'currentLiabilities',
        label: 'Current Liabilities',
        description: 'Short-term liabilities',
        type: 'currency',
    },
    {
        key: 'shareCapital',
        label: 'Share Capital',
        description: 'Issued share capital',
        type: 'currency',
    },
    {
        key: 'retainedEarnings',
        label: 'Retained Earnings',
        description: 'Accumulated profits',
        type: 'currency',
    },
    {
        key: 'totalEquity',
        label: 'Total Equity',
        description: 'Net assets (assets minus liabilities)',
        type: 'currency',
        required: true,
    },
];

export const employmentFields: FinancialFieldConfig[] = [
    {
        key: 'employeeCount',
        label: 'Number of Employees',
        description: 'Full-time equivalent employees',
        type: 'number',
        required: true,
    },
    {
        key: 'totalPayroll',
        label: 'Total Payroll Cost',
        description: 'All employee costs including benefits',
        type: 'currency',
        required: true,
    },
    {
        key: 'payeRemitted',
        label: 'Total PAYE Remitted',
        description: 'PAYE tax deducted and remitted to NRS',
        type: 'currency',
    },
    {
        key: 'pensionEmployer',
        label: 'Pension (Employer)',
        description: 'Employer pension contributions',
        type: 'currency',
    },
    {
        key: 'pensionEmployee',
        label: 'Pension (Employee)',
        description: 'Employee pension contributions',
        type: 'currency',
    },
    {
        key: 'nhfContributions',
        label: 'NHF Contributions',
        description: 'National Housing Fund contributions',
        type: 'currency',
    },
    {
        key: 'nsitfContributions',
        label: 'NSITF Contributions',
        description: 'National Social Insurance Trust Fund',
        type: 'currency',
    },
    {
        key: 'itfContributions',
        label: 'ITF Contributions',
        description: 'Industrial Training Fund contributions',
        type: 'currency',
    },
];

export const vatFields: FinancialFieldConfig[] = [
    {
        key: 'vatableSales',
        label: 'VATable Sales',
        description: 'Sales subject to VAT at 7.5%',
        type: 'currency',
        required: true,
    },
    {
        key: 'vatOutput',
        label: 'VAT Output',
        description: 'VAT charged on sales (7.5% of VATable sales)',
        type: 'currency',
        required: true,
    },
    {
        key: 'exemptSales',
        label: 'Exempt Sales',
        description: 'Sales exempt from VAT',
        type: 'currency',
    },
    {
        key: 'zeroRatedSales',
        label: 'Zero-Rated Sales',
        description: 'Exports and other zero-rated sales',
        type: 'currency',
    },
    {
        key: 'vatablePurchases',
        label: 'VATable Purchases',
        description: 'Purchases with VAT charged',
        type: 'currency',
        required: true,
    },
    {
        key: 'vatInputGoods',
        label: 'VAT Input (Goods)',
        description: 'Input VAT on goods purchases',
        type: 'currency',
        required: true,
    },
    {
        key: 'vatInputServices',
        label: 'VAT Input (Services)',
        description: 'Input VAT on services (recoverable under NTA 2025)',
        type: 'currency',
        required: true,
    },
    {
        key: 'vatInputCapital',
        label: 'VAT Input (Capital)',
        description: 'Input VAT on capital items',
        type: 'currency',
    },
    {
        key: 'vatRemitted',
        label: 'VAT Remitted',
        description: 'Net VAT paid to NRS',
        type: 'currency',
    },
];

export const capitalFields: FinancialFieldConfig[] = [
    {
        key: 'assetDisposals',
        label: 'Asset Disposals',
        description: 'Proceeds from sale of assets',
        type: 'currency',
    },
    {
        key: 'assetDisposalCosts',
        label: 'Asset Disposal Costs',
        description: 'Original cost of disposed assets',
        type: 'currency',
    },
    {
        key: 'capitalGainsRealized',
        label: 'Capital Gains Realized',
        description: 'Net capital gains from disposals',
        type: 'currency',
    },
    {
        key: 'capitalAllowancesClaimed',
        label: 'Capital Allowances Claimed',
        description: 'Tax depreciation claimed',
        type: 'currency',
    },
];

export const financialSections: FinancialSectionConfig[] = [
    {
        key: 'income',
        label: 'Income Statement',
        icon: 'trending-up',
        fields: incomeFields,
    },
    {
        key: 'balance_sheet',
        label: 'Balance Sheet',
        icon: 'scale',
        fields: balanceSheetFields,
    },
    {
        key: 'employment',
        label: 'Employment',
        icon: 'users',
        fields: employmentFields,
    },
    {
        key: 'vat',
        label: 'VAT',
        icon: 'receipt',
        fields: vatFields,
    },
    {
        key: 'capital',
        label: 'Capital Transactions',
        icon: 'building',
        fields: capitalFields,
    },
];

export const dataSourceOptions = [
    {
        value: 'audited' as const,
        label: 'Audited Financial Statements',
        shortLabel: 'Audited',
        color: 'green',
    },
    {
        value: 'management' as const,
        label: 'Management Accounts',
        shortLabel: 'Management',
        color: 'blue',
    },
    {
        value: 'computed' as const,
        label: 'Tax Computations',
        shortLabel: 'Computed',
        color: 'yellow',
    },
    {
        value: 'projected' as const,
        label: 'Projections/Estimates',
        shortLabel: 'Projected',
        color: 'gray',
    },
];
```

---

## Utility Functions

**File:** `resources/js/lib/financial-utils.ts`

```typescript
import type { IncomeData, BalanceSheetData, VatData } from '@/types/financial';

export function formatCurrency(value: number | undefined | null): string {
    if (value === undefined || value === null) {
        return '';
    }
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
}

export function formatNaira(value: number | undefined | null): string {
    if (value === undefined || value === null) {
        return '';
    }
    return '₦' + new Intl.NumberFormat('en-NG', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
}

export function parseCurrencyInput(value: string): number | null {
    if (!value) {
        return null;
    }
    const cleaned = value.replace(/[^0-9.-]/g, '');
    const parsed = parseFloat(cleaned);
    return isNaN(parsed) ? null : Math.round(parsed);
}

export function calculateGrossProfit(data: IncomeData): number | null {
    const turnover = data.turnover;
    const costOfSales = data.costOfSales;

    if (turnover === undefined || costOfSales === undefined) {
        return null;
    }

    return turnover - costOfSales;
}

export function calculateBalanceSheetEquity(data: BalanceSheetData): number | null {
    const totalAssets = data.totalAssets;
    const totalLiabilities = data.totalLiabilities;

    if (totalAssets === undefined || totalLiabilities === undefined) {
        return null;
    }

    return totalAssets - totalLiabilities;
}

export function calculateVatOutput(vatableSales: number | undefined): number | null {
    if (vatableSales === undefined) {
        return null;
    }
    return Math.round(vatableSales * 0.075);
}

export function calculateGrossMargin(data: IncomeData): number | null {
    const turnover = data.turnover;
    const grossProfit = data.grossProfit;

    if (!turnover || !grossProfit) {
        return null;
    }

    return (grossProfit / turnover) * 100;
}

export function calculateNetMargin(data: IncomeData): number | null {
    const turnover = data.turnover;
    const profitBeforeTax = data.profitBeforeTax;

    if (!turnover || profitBeforeTax === undefined) {
        return null;
    }

    return (profitBeforeTax / turnover) * 100;
}

export function isWithinTolerance(
    expected: number,
    actual: number,
    tolerance: number = 0.05
): boolean {
    if (expected === 0 && actual === 0) {
        return true;
    }
    if (expected === 0) {
        return Math.abs(actual) < 1000;
    }
    return Math.abs((actual - expected) / expected) <= tolerance;
}

export function getSeverityColor(severity: 'info' | 'warning' | 'error'): string {
    const colors = {
        info: 'text-blue-600 dark:text-blue-400',
        warning: 'text-yellow-600 dark:text-yellow-400',
        error: 'text-red-600 dark:text-red-400',
    };
    return colors[severity];
}

export function getSeverityBgColor(severity: 'info' | 'warning' | 'error'): string {
    const colors = {
        info: 'bg-blue-50 dark:bg-blue-900/20',
        warning: 'bg-yellow-50 dark:bg-yellow-900/20',
        error: 'bg-red-50 dark:bg-red-900/20',
    };
    return colors[severity];
}
```

---

## Hooks

**File:** `resources/js/hooks/use-financial.ts`

```typescript
import { useForm } from '@inertiajs/react';
import { useMemo, useCallback } from 'react';
import type {
    ClientFinancial,
    FinancialFormData,
    FinancialSection,
    IncomeData,
    BalanceSheetData,
} from '@/types/financial';
import {
    calculateGrossProfit,
    calculateBalanceSheetEquity,
    calculateVatOutput,
} from '@/lib/financial-utils';

export function useFinancialForm(financial?: ClientFinancial) {
    const form = useForm<FinancialFormData>({
        fiscalYear: financial?.fiscalYear ?? new Date().getFullYear(),
        fiscalYearStart: financial?.fiscalYearStart ?? `${new Date().getFullYear()}-01-01`,
        fiscalYearEnd: financial?.fiscalYearEnd ?? `${new Date().getFullYear()}-12-31`,
        dataSource: financial?.dataSource ?? 'management',
        incomeData: financial?.incomeData ?? {},
        balanceSheetData: financial?.balanceSheetData ?? {},
        employmentData: financial?.employmentData ?? {},
        vatData: financial?.vatData ?? {},
        capitalData: financial?.capitalData ?? {},
        notes: financial?.notes ?? '',
    });

    const updateSectionField = useCallback(
        (section: FinancialSection, field: string, value: number | null) => {
            const sectionKey = `${section}Data` as keyof FinancialFormData;
            const currentData = form.data[sectionKey] as Record<string, unknown> || {};

            form.setData(sectionKey, {
                ...currentData,
                [field]: value,
            });
        },
        [form]
    );

    const calculatedValues = useMemo(() => {
        const incomeData = form.data.incomeData || {};
        const balanceSheetData = form.data.balanceSheetData || {};
        const vatData = form.data.vatData || {};

        return {
            grossProfit: calculateGrossProfit(incomeData as IncomeData),
            totalEquity: calculateBalanceSheetEquity(balanceSheetData as BalanceSheetData),
            vatOutput: calculateVatOutput(vatData.vatableSales as number | undefined),
        };
    }, [form.data.incomeData, form.data.balanceSheetData, form.data.vatData]);

    return {
        form,
        updateSectionField,
        calculatedValues,
    };
}
```

**File:** `resources/js/hooks/use-financial-versions.ts`

```typescript
import { useState, useCallback } from 'react';
import { router } from '@inertiajs/react';
import type { FinancialVersion } from '@/types/financial';

export function useFinancialVersions(
    clientId: string,
    fiscalYear: number,
    versions: FinancialVersion[]
) {
    const [isRestoring, setIsRestoring] = useState(false);
    const [selectedVersion, setSelectedVersion] = useState<number | null>(null);

    const restoreVersion = useCallback(
        (version: number) => {
            setIsRestoring(true);
            router.post(
                route('app.clients.financials.restore', {
                    client: clientId,
                    year: fiscalYear,
                    version,
                }),
                {},
                {
                    onFinish: () => setIsRestoring(false),
                }
            );
        },
        [clientId, fiscalYear]
    );

    return {
        versions,
        selectedVersion,
        setSelectedVersion,
        restoreVersion,
        isRestoring,
    };
}
```

---

## Update Type Index

**File:** `resources/js/types/index.d.ts` (add exports)

```typescript
export * from './financial';
```

---

## Next Steps

Once types are defined, proceed to:
→ **05_FRONTEND_COMPONENTS.md** - React component implementations
