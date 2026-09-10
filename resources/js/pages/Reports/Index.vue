<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    ChartColumn,
    TrendingUp,
    TrendingDown,
    ChartPie,
    ChartLine,
    Coins,
    Calendar,
    Filter,
    Percent,
    ArrowUpRight,
} from '@lucide/vue';
import { ref } from 'vue';
import AssetAllocationDonutChart from '@/components/AssetAllocationDonutChart.vue';
import MoneyDisplay from '@/components/MoneyDisplay.vue';
import CashFlowChart from '@/components/ReportCharts/CashFlowChart.vue';
import ExpenseCategoryChart from '@/components/ReportCharts/ExpenseCategoryChart.vue';
import IncomeExpenseChart from '@/components/ReportCharts/IncomeExpenseChart.vue';
import NetWorthTrendChart from '@/components/ReportCharts/NetWorthTrendChart.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Reports',
                href: '/reports',
            },
        ],
    },
});

const props = defineProps<{
    activeType:
        | 'income_expense'
        | 'cash_flow'
        | 'expense_category'
        | 'net_worth'
        | 'asset_allocation';
    reportData: any;
    filters: {
        type: string;
        period: string;
        start_date: string | null;
        end_date: string | null;
        account_id: string | null;
    };
    accounts: Array<{
        id: string;
        name: string;
        type: string;
        current_balance: number;
    }>;
}>();

const selectedType = ref(props.activeType);
const selectedPeriod = ref(props.filters.period || 'last_6_months');
const customStart = ref(props.filters.start_date || '');
const customEnd = ref(props.filters.end_date || '');
const selectedAccount = ref(props.filters.account_id || '');

const applyFilters = (typeOverride?: string) => {
    const type = typeOverride || selectedType.value;

    router.get(
        '/reports',
        {
            type,
            period: selectedPeriod.value,
            start_date:
                selectedPeriod.value === 'custom'
                    ? customStart.value
                    : undefined,
            end_date:
                selectedPeriod.value === 'custom' ? customEnd.value : undefined,
            account_id: selectedAccount.value || undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const switchTab = (type: any) => {
    selectedType.value = type;
    applyFilters(type);
};

const periods = [
    { value: 'this_month', label: 'This Month' },
    { value: 'last_month', label: 'Last Month' },
    { value: 'last_3_months', label: 'Last 3 Months' },
    { value: 'last_6_months', label: 'Last 6 Months' },
    { value: 'ytd', label: 'Year to Date' },
    { value: 'all_time', label: 'All Time' },
    { value: 'custom', label: 'Custom' },
];

const reportTabs = [
    { value: 'income_expense', label: 'Income & Expense', icon: ChartColumn },
    { value: 'cash_flow', label: 'Cash Flow', icon: TrendingUp },
    { value: 'expense_category', label: 'Category Expense', icon: ChartPie },
    { value: 'net_worth', label: 'Net Worth Trend', icon: ChartLine },
    { value: 'asset_allocation', label: 'Asset Allocation', icon: Coins },
];
</script>

<template>
    <Head title="Financial Reports" />

    <div
        class="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8"
    >
        <!-- Header -->
        <div class="flex flex-col gap-1">
            <h1 class="text-3xl font-bold tracking-tight">Financial Reports</h1>
            <p class="text-sm text-muted-foreground">
                In-depth analytics on your cash flow, category breakdowns,
                savings trajectory, and portfolio wealth.
            </p>
        </div>

        <!-- Report Type Tabs -->
        <div
            class="flex scrollbar-none overflow-x-auto rounded-2xl border bg-muted/40 p-1.5 shadow-sm"
        >
            <button
                v-for="tab in reportTabs"
                :key="tab.value"
                type="button"
                :class="[
                    'flex shrink-0 items-center gap-2 rounded-xl px-4 py-2 text-xs font-semibold transition-all',
                    selectedType === tab.value
                        ? 'bg-background text-foreground shadow-sm'
                        : 'text-muted-foreground hover:text-foreground',
                ]"
                @click="switchTab(tab.value)"
            >
                <component :is="tab.icon" class="h-4 w-4" />
                <span>{{ tab.label }}</span>
            </button>
        </div>

        <!-- Filter Controls (for reports with date/account filters) -->
        <div
            v-if="selectedType !== 'asset_allocation'"
            class="flex flex-col gap-3 rounded-2xl border bg-card p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex flex-wrap items-center gap-2">
                <span
                    class="flex items-center gap-1.5 text-xs font-semibold text-muted-foreground"
                >
                    <Calendar class="h-3.5 w-3.5" />
                    Period:
                </span>
                <select
                    v-model="selectedPeriod"
                    class="h-8 rounded-lg border border-input bg-background px-2.5 text-xs font-medium shadow-sm focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                    @change="applyFilters()"
                >
                    <option
                        v-for="p in periods"
                        :key="p.value"
                        :value="p.value"
                    >
                        {{ p.label }}
                    </option>
                </select>

                <!-- Custom Range Inputs -->
                <div
                    v-if="selectedPeriod === 'custom'"
                    class="flex items-center gap-2"
                >
                    <Input
                        v-model="customStart"
                        type="date"
                        class="h-8 text-xs"
                    />
                    <span class="text-xs text-muted-foreground">to</span>
                    <Input
                        v-model="customEnd"
                        type="date"
                        class="h-8 text-xs"
                    />
                    <Button
                        size="sm"
                        variant="secondary"
                        class="h-8 text-xs"
                        @click="applyFilters()"
                    >
                        Apply
                    </Button>
                </div>
            </div>

            <!-- Account Filter -->
            <div
                v-if="selectedType !== 'net_worth'"
                class="flex items-center gap-2"
            >
                <span
                    class="flex items-center gap-1.5 text-xs font-semibold text-muted-foreground"
                >
                    <Filter class="h-3.5 w-3.5" />
                    Account:
                </span>
                <select
                    v-model="selectedAccount"
                    class="h-8 rounded-lg border border-input bg-background px-2.5 text-xs font-medium shadow-sm focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                    @change="applyFilters()"
                >
                    <option value="">All Accounts</option>
                    <option
                        v-for="acc in accounts"
                        :key="acc.id"
                        :value="acc.id"
                    >
                        {{ acc.name }}
                    </option>
                </select>
            </div>
        </div>

        <!-- 1. Income vs Expense Report Content -->
        <template v-if="selectedType === 'income_expense'">
            <!-- Summary Stats -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <span
                        class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >Total Income</span
                    >
                    <p
                        class="mt-2 text-2xl font-extrabold tracking-tight text-emerald-600 dark:text-emerald-400"
                    >
                        <MoneyDisplay
                            :amount="reportData.summary.total_income"
                        />
                    </p>
                    <span class="mt-3 block text-xs text-muted-foreground">
                        Avg.
                        <MoneyDisplay
                            :amount="reportData.summary.avg_monthly_income"
                        />
                        / mo
                    </span>
                </div>

                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <span
                        class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >Total Expense</span
                    >
                    <p
                        class="mt-2 text-2xl font-extrabold tracking-tight text-rose-600 dark:text-rose-400"
                    >
                        <MoneyDisplay
                            :amount="reportData.summary.total_expense"
                        />
                    </p>
                    <span class="mt-3 block text-xs text-muted-foreground">
                        Avg.
                        <MoneyDisplay
                            :amount="reportData.summary.avg_monthly_expense"
                        />
                        / mo
                    </span>
                </div>

                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <span
                        class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >Net Savings</span
                    >
                    <p
                        :class="[
                            'mt-2 text-2xl font-extrabold tracking-tight',
                            reportData.summary.net_savings >= 0
                                ? 'text-emerald-600 dark:text-emerald-400'
                                : 'text-rose-600 dark:text-rose-400',
                        ]"
                    >
                        <MoneyDisplay
                            :amount="reportData.summary.net_savings"
                        />
                    </p>
                    <span class="mt-3 block text-xs text-muted-foreground">
                        Income minus expense
                    </span>
                </div>

                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <span
                        class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >Savings Rate</span
                    >
                    <p
                        class="mt-2 text-2xl font-extrabold tracking-tight text-foreground"
                    >
                        {{ reportData.summary.savings_rate }}%
                    </p>
                    <span class="mt-3 block text-xs text-muted-foreground">
                        Percentage of income saved
                    </span>
                </div>
            </div>

            <!-- Chart Card -->
            <div class="rounded-2xl border bg-card p-6 shadow-sm">
                <div
                    class="flex items-center justify-between border-b border-border/60 pb-4"
                >
                    <div>
                        <h3 class="text-base font-bold tracking-tight">
                            Income vs Expense Comparison
                        </h3>
                        <p class="text-xs text-muted-foreground">
                            {{ reportData.period.label }}
                        </p>
                    </div>
                </div>
                <div class="mt-4">
                    <IncomeExpenseChart :chart-data="reportData.chart" />
                </div>
            </div>

            <!-- Table Card -->
            <div class="rounded-2xl border bg-card p-6 shadow-sm">
                <h3
                    class="border-b border-border/60 pb-4 text-base font-bold tracking-tight"
                >
                    Monthly Breakdown
                </h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b text-muted-foreground">
                                <th class="pb-2 font-semibold">Month</th>
                                <th class="pb-2 text-right font-semibold">
                                    Income
                                </th>
                                <th class="pb-2 text-right font-semibold">
                                    Expense
                                </th>
                                <th class="pb-2 text-right font-semibold">
                                    Net Flow
                                </th>
                                <th class="pb-2 text-right font-semibold">
                                    Savings Rate
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/40">
                            <tr
                                v-for="row in reportData.table"
                                :key="row.month"
                                class="hover:bg-muted/30"
                            >
                                <td class="py-3 font-medium text-foreground">
                                    {{ row.label }}
                                </td>
                                <td
                                    class="py-3 text-right font-semibold text-emerald-600 dark:text-emerald-400"
                                >
                                    <MoneyDisplay :amount="row.income" />
                                </td>
                                <td
                                    class="py-3 text-right font-semibold text-rose-600 dark:text-rose-400"
                                >
                                    <MoneyDisplay :amount="row.expense" />
                                </td>
                                <td
                                    :class="[
                                        'py-3 text-right font-bold',
                                        row.net >= 0
                                            ? 'text-emerald-600 dark:text-emerald-400'
                                            : 'text-rose-600 dark:text-rose-400',
                                    ]"
                                >
                                    <MoneyDisplay :amount="row.net" />
                                </td>
                                <td
                                    class="py-3 text-right font-semibold text-muted-foreground tabular-nums"
                                >
                                    {{ row.savings_rate }}%
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>

        <!-- 2. Cash Flow Report Content -->
        <template v-else-if="selectedType === 'cash_flow'">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <span
                        class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >Total Inflow</span
                    >
                    <p
                        class="mt-2 text-2xl font-extrabold tracking-tight text-emerald-600 dark:text-emerald-400"
                    >
                        <MoneyDisplay
                            :amount="reportData.summary.total_inflow"
                        />
                    </p>
                </div>
                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <span
                        class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >Total Outflow</span
                    >
                    <p
                        class="mt-2 text-2xl font-extrabold tracking-tight text-rose-600 dark:text-rose-400"
                    >
                        <MoneyDisplay
                            :amount="reportData.summary.total_outflow"
                        />
                    </p>
                </div>
                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <span
                        class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >Net Cash Flow</span
                    >
                    <p
                        :class="[
                            'mt-2 text-2xl font-extrabold tracking-tight',
                            reportData.summary.net_cash_flow >= 0
                                ? 'text-emerald-600 dark:text-emerald-400'
                                : 'text-rose-600 dark:text-rose-400',
                        ]"
                    >
                        <MoneyDisplay
                            :amount="reportData.summary.net_cash_flow"
                        />
                    </p>
                </div>
            </div>

            <div class="rounded-2xl border bg-card p-6 shadow-sm">
                <div
                    class="flex items-center justify-between border-b border-border/60 pb-4"
                >
                    <div>
                        <h3 class="text-base font-bold tracking-tight">
                            Net &amp; Cumulative Cash Flow
                        </h3>
                        <p class="text-xs text-muted-foreground">
                            Trend of net liquidity additions over time.
                        </p>
                    </div>
                </div>
                <div class="mt-4">
                    <CashFlowChart :chart-data="reportData.chart" />
                </div>
            </div>

            <div class="rounded-2xl border bg-card p-6 shadow-sm">
                <h3
                    class="border-b border-border/60 pb-4 text-base font-bold tracking-tight"
                >
                    Cash Flow History
                </h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b text-muted-foreground">
                                <th class="pb-2 font-semibold">Month</th>
                                <th class="pb-2 text-right font-semibold">
                                    Inflow
                                </th>
                                <th class="pb-2 text-right font-semibold">
                                    Outflow
                                </th>
                                <th class="pb-2 text-right font-semibold">
                                    Net Flow
                                </th>
                                <th class="pb-2 text-right font-semibold">
                                    Cumulative
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/40">
                            <tr
                                v-for="row in reportData.table"
                                :key="row.month"
                                class="hover:bg-muted/30"
                            >
                                <td class="py-3 font-medium text-foreground">
                                    {{ row.label }}
                                </td>
                                <td
                                    class="py-3 text-right font-semibold text-emerald-600 dark:text-emerald-400"
                                >
                                    <MoneyDisplay :amount="row.inflow" />
                                </td>
                                <td
                                    class="py-3 text-right font-semibold text-rose-600 dark:text-rose-400"
                                >
                                    <MoneyDisplay :amount="row.outflow" />
                                </td>
                                <td
                                    :class="[
                                        'py-3 text-right font-bold',
                                        row.net_flow >= 0
                                            ? 'text-emerald-600 dark:text-emerald-400'
                                            : 'text-rose-600 dark:text-rose-400',
                                    ]"
                                >
                                    <MoneyDisplay :amount="row.net_flow" />
                                </td>
                                <td
                                    class="py-3 text-right font-bold text-foreground"
                                >
                                    <MoneyDisplay
                                        :amount="row.cumulative_flow"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>

        <!-- 3. Expense by Category Content -->
        <template v-else-if="selectedType === 'expense_category'">
            <div class="rounded-2xl border bg-card p-6 shadow-sm">
                <div
                    class="flex flex-col gap-2 border-b border-border/60 pb-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h3 class="text-base font-bold tracking-tight">
                            Spending by Category
                        </h3>
                        <p class="text-xs text-muted-foreground">
                            {{ reportData.period.label }}
                        </p>
                    </div>
                    <div
                        class="rounded-xl bg-rose-500/10 px-3 py-1.5 text-xs font-bold text-rose-600 dark:text-rose-400"
                    >
                        Total:
                        <MoneyDisplay
                            :amount="reportData.summary.total_expense"
                        />
                    </div>
                </div>

                <div
                    class="mt-6 grid grid-cols-1 items-center gap-8 lg:grid-cols-12"
                >
                    <div class="flex justify-center lg:col-span-5">
                        <ExpenseCategoryChart :chart-data="reportData.chart" />
                    </div>

                    <div class="space-y-3 lg:col-span-7">
                        <div
                            v-for="cat in reportData.table"
                            :key="cat.id"
                            class="rounded-xl border border-border/50 bg-muted/20 p-3.5 transition-colors hover:bg-muted/40"
                        >
                            <div
                                class="flex items-center justify-between text-xs"
                            >
                                <div class="flex items-center gap-2.5">
                                    <span
                                        class="h-3 w-3 shrink-0 rounded-full shadow-sm"
                                        :style="{ backgroundColor: cat.color }"
                                    />
                                    <span
                                        class="text-sm font-bold text-foreground"
                                        >{{ cat.name }}</span
                                    >
                                    <span
                                        class="text-[11px] text-muted-foreground"
                                        >({{ cat.count }} tx)</span
                                    >
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="font-bold text-foreground">
                                        <MoneyDisplay :amount="cat.amount" />
                                    </span>
                                    <span
                                        class="rounded-md px-2 py-0.5 text-xs font-bold tabular-nums"
                                        :style="{
                                            backgroundColor: `${cat.color}15`,
                                            color: cat.color,
                                        }"
                                    >
                                        {{ cat.percentage }}%
                                    </span>
                                </div>
                            </div>
                            <div
                                class="mt-2.5 h-1.5 w-full overflow-hidden rounded-full bg-muted"
                            >
                                <div
                                    class="h-full rounded-full"
                                    :style="{
                                        width: `${cat.percentage}%`,
                                        backgroundColor: cat.color,
                                    }"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- 4. Net Worth Trend Content -->
        <template v-else-if="selectedType === 'net_worth'">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <span
                        class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >Current Net Worth</span
                    >
                    <p
                        class="mt-2 text-2xl font-extrabold tracking-tight text-emerald-600 dark:text-emerald-400"
                    >
                        <MoneyDisplay
                            :amount="reportData.summary.current_net_worth"
                        />
                    </p>
                </div>
                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <span
                        class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >Total Assets</span
                    >
                    <p
                        class="mt-2 text-2xl font-extrabold tracking-tight text-foreground"
                    >
                        <MoneyDisplay
                            :amount="reportData.summary.total_assets"
                        />
                    </p>
                </div>
                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <span
                        class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >Total Liabilities</span
                    >
                    <p
                        class="mt-2 text-2xl font-extrabold tracking-tight text-rose-600 dark:text-rose-400"
                    >
                        <MoneyDisplay
                            :amount="reportData.summary.total_liabilities"
                        />
                    </p>
                </div>
            </div>

            <div class="rounded-2xl border bg-card p-6 shadow-sm">
                <div
                    class="flex items-center justify-between border-b border-border/60 pb-4"
                >
                    <div>
                        <h3 class="text-base font-bold tracking-tight">
                            Net Worth &amp; Balance Trend
                        </h3>
                        <p class="text-xs text-muted-foreground">
                            Wealth accumulation across recorded dates.
                        </p>
                    </div>
                </div>
                <div class="mt-4">
                    <NetWorthTrendChart :chart-data="reportData.chart" />
                </div>
            </div>

            <div class="rounded-2xl border bg-card p-6 shadow-sm">
                <h3
                    class="border-b border-border/60 pb-4 text-base font-bold tracking-tight"
                >
                    Snapshot Records
                </h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b text-muted-foreground">
                                <th class="pb-2 font-semibold">Date</th>
                                <th class="pb-2 text-right font-semibold">
                                    Liquid Cash
                                </th>
                                <th class="pb-2 text-right font-semibold">
                                    Investments
                                </th>
                                <th class="pb-2 text-right font-semibold">
                                    Liabilities
                                </th>
                                <th class="pb-2 text-right font-semibold">
                                    Net Worth
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/40">
                            <tr
                                v-for="row in reportData.table"
                                :key="row.date"
                                class="hover:bg-muted/30"
                            >
                                <td class="py-3 font-medium text-foreground">
                                    {{ row.label }}
                                </td>
                                <td
                                    class="py-3 text-right font-semibold text-foreground"
                                >
                                    <MoneyDisplay :amount="row.total_cash" />
                                </td>
                                <td
                                    class="py-3 text-right font-semibold text-foreground"
                                >
                                    <MoneyDisplay
                                        :amount="row.total_investments"
                                    />
                                </td>
                                <td
                                    class="py-3 text-right font-semibold text-rose-600 dark:text-rose-400"
                                >
                                    <MoneyDisplay
                                        :amount="row.total_liabilities"
                                    />
                                </td>
                                <td
                                    class="py-3 text-right font-bold text-emerald-600 dark:text-emerald-400"
                                >
                                    <MoneyDisplay :amount="row.net_worth" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>

        <!-- 5. Asset Allocation Content -->
        <template v-else-if="selectedType === 'asset_allocation'">
            <div class="rounded-2xl border bg-card p-6 shadow-sm">
                <div
                    class="flex items-center justify-between border-b border-border/60 pb-4"
                >
                    <div>
                        <h3 class="text-base font-bold tracking-tight">
                            Portfolio Allocation
                        </h3>
                        <p class="text-xs text-muted-foreground">
                            Weighted diversification across all asset classes.
                        </p>
                    </div>
                    <div
                        class="rounded-xl bg-primary/10 px-3 py-1.5 text-xs font-bold text-primary"
                    >
                        Total Portfolio:
                        <MoneyDisplay
                            :amount="reportData.metrics.total_value"
                        />
                    </div>
                </div>

                <div
                    class="mt-6 grid grid-cols-1 items-center gap-8 lg:grid-cols-12"
                >
                    <div class="flex justify-center lg:col-span-5">
                        <AssetAllocationDonutChart
                            :allocation="reportData.allocation"
                            :show-legend="false"
                        />
                    </div>
                    <div class="space-y-3.5 lg:col-span-7">
                        <div
                            v-for="item in reportData.allocation.items"
                            :key="item.type"
                            class="rounded-xl border border-border/50 bg-muted/20 p-4"
                        >
                            <div
                                class="flex items-center justify-between text-xs font-medium"
                            >
                                <div class="flex items-center gap-2.5">
                                    <span
                                        class="h-3 w-3 shrink-0 rounded-full"
                                        :style="{ backgroundColor: item.color }"
                                    />
                                    <span
                                        class="text-sm font-bold text-foreground"
                                        >{{ item.label }}</span
                                    >
                                    <span
                                        class="text-[10px] text-muted-foreground"
                                        >({{ item.count }} assets)</span
                                    >
                                </div>
                                <div class="flex items-center gap-3">
                                    <span
                                        class="text-sm font-semibold text-foreground"
                                    >
                                        <MoneyDisplay :amount="item.value" />
                                    </span>
                                    <span
                                        class="rounded-md px-2 py-0.5 text-xs font-bold tabular-nums"
                                        :style="{
                                            backgroundColor: `${item.color}15`,
                                            color: item.color,
                                        }"
                                    >
                                        {{ item.percentage }}%
                                    </span>
                                </div>
                            </div>
                            <div
                                class="mt-3 h-2 w-full overflow-hidden rounded-full bg-muted"
                            >
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    :style="{
                                        width: `${item.percentage}%`,
                                        backgroundColor: item.color,
                                    }"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
