<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    TrendingUp,
    TrendingDown,
    Wallet,
    CreditCard,
    ArrowUpRight,
    ArrowDownRight,
    PlusCircle,
    ArrowRight,
    PiggyBank,
    Building2,
    Calendar,
} from '@lucide/vue';
import dayjs from 'dayjs';
import AssetAllocationDonutChart from '@/components/AssetAllocationDonutChart.vue';
import CashFlowBarChart from '@/components/CashFlowBarChart.vue';
import CategoryIcon from '@/components/CategoryIcon.vue';
import EmptyState from '@/components/EmptyState.vue';
import MoneyDisplay from '@/components/MoneyDisplay.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: '/dashboard',
            },
        ],
    },
});

const props = defineProps<{
    netWorth: {
        net_worth: number;
        total_assets: number;
        total_cash: number;
        total_investments: number;
        total_liabilities: number;
        change_amount: number;
        change_percentage: number;
        is_positive_change: boolean;
    };
    cashFlow: {
        period: string;
        period_label: string;
        income: number;
        expense: number;
        net: number;
        savings_rate: number;
        chart: {
            labels: string[];
            income: number[];
            expense: number[];
            net: number[];
        };
    };
    assetAllocation: {
        labels: string[];
        values: number[];
        colors: string[];
        items: Array<{
            label: string;
            value: number;
            color: string;
            percentage: number;
        }>;
        total: number;
    };
    recentTransactions: any[];
    period: string;
}>();

const switchPeriod = (newPeriod: 'this_month' | 'last_month') => {
    if (newPeriod === props.period) {
        return;
    }

    router.get(
        '/dashboard',
        { period: newPeriod },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const formatDate = (dateStr: string) => {
    return dayjs(dateStr).format('D MMM YYYY');
};

const getTransactionAccountName = (tx: any) => {
    if (tx.type === 'transfer') {
        const from = tx.entries?.find((e: any) => e.entry_type === 'debit')
            ?.account?.name;
        const to = tx.entries?.find((e: any) => e.entry_type === 'credit')
            ?.account?.name;

        return `${from || 'Account'} → ${to || 'Account'}`;
    }

    const entry = tx.entries?.find((e: any) => e.account_id !== null);

    return entry?.account?.name || 'Account';
};
</script>

<template>
    <Head title="Financial Dashboard" />

    <div
        class="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8"
    >
        <!-- Header & Quick Actions -->
        <div
            class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center"
        >
            <div>
                <h1 class="text-3xl font-bold tracking-tight">
                    Financial Overview
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Monitor your net worth, cash flow, and asset distribution in
                    real-time.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <Link
                    href="/transactions/create"
                    class="inline-flex h-9 items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                >
                    <PlusCircle class="h-4 w-4" />
                    Record Transaction
                </Link>
                <Link
                    href="/accounts/create"
                    class="inline-flex h-9 items-center justify-center gap-2 rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                >
                    <Wallet class="h-4 w-4 text-muted-foreground" />
                    Add Account
                </Link>
            </div>
        </div>

        <!-- 1. Hero: Net Worth Card -->
        <div
            class="relative overflow-hidden rounded-2xl border bg-gradient-to-br from-card via-card to-primary/5 p-6 text-card-foreground shadow-sm md:p-8"
        >
            <div
                class="pointer-events-none absolute -top-16 -right-16 h-64 w-64 rounded-full bg-primary/10 blur-3xl"
            ></div>

            <div
                class="relative z-10 flex flex-col justify-between gap-6 md:flex-row md:items-end"
            >
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <span
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            Total Net Worth
                        </span>
                        <!-- Change badge -->
                        <span
                            v-if="netWorth.change_percentage !== 0"
                            :class="[
                                'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium',
                                netWorth.is_positive_change
                                    ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                                    : 'bg-rose-500/10 text-rose-600 dark:text-rose-400',
                            ]"
                        >
                            <component
                                :is="
                                    netWorth.is_positive_change
                                        ? TrendingUp
                                        : TrendingDown
                                "
                                class="h-3 w-3"
                            />
                            {{ netWorth.is_positive_change ? '+' : ''
                            }}{{ netWorth.change_percentage }}% vs last month
                        </span>
                    </div>

                    <h2
                        class="text-4xl font-extrabold tracking-tight sm:text-5xl"
                    >
                        <MoneyDisplay :amount="netWorth.net_worth" />
                    </h2>
                </div>

                <!-- Breakdown Pills -->
                <div
                    class="grid grid-cols-3 gap-3 border-t pt-4 sm:flex sm:items-center sm:gap-6 md:border-t-0 md:pt-0"
                >
                    <div>
                        <p class="text-xs font-medium text-muted-foreground">
                            Liquid Cash
                        </p>
                        <p class="mt-1 text-sm font-bold text-foreground">
                            <MoneyDisplay :amount="netWorth.total_cash" />
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-muted-foreground">
                            Investments
                        </p>
                        <p class="mt-1 text-sm font-bold text-foreground">
                            <MoneyDisplay
                                :amount="netWorth.total_investments"
                            />
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-muted-foreground">
                            Liabilities
                        </p>
                        <p
                            class="mt-1 text-sm font-bold text-rose-600 dark:text-rose-400"
                        >
                            <MoneyDisplay
                                :amount="netWorth.total_liabilities"
                            />
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Quick Stats Row -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <!-- Total Assets -->
            <div
                class="flex items-center justify-between rounded-xl border bg-card p-5 text-card-foreground shadow-sm"
            >
                <div>
                    <p class="text-xs font-medium text-muted-foreground">
                        Total Assets
                    </p>
                    <p class="mt-1 text-2xl font-bold tracking-tight">
                        <MoneyDisplay :amount="netWorth.total_assets" />
                    </p>
                </div>
                <div
                    class="rounded-xl bg-emerald-500/10 p-3 text-emerald-600 dark:text-emerald-400"
                >
                    <TrendingUp class="h-5 w-5" />
                </div>
            </div>

            <!-- Total Liabilities -->
            <div
                class="flex items-center justify-between rounded-xl border bg-card p-5 text-card-foreground shadow-sm"
            >
                <div>
                    <p class="text-xs font-medium text-muted-foreground">
                        Total Liabilities
                    </p>
                    <p
                        class="mt-1 text-2xl font-bold tracking-tight text-rose-600 dark:text-rose-400"
                    >
                        <MoneyDisplay :amount="netWorth.total_liabilities" />
                    </p>
                </div>
                <div
                    class="rounded-xl bg-rose-500/10 p-3 text-rose-600 dark:text-rose-400"
                >
                    <CreditCard class="h-5 w-5" />
                </div>
            </div>

            <!-- Savings Rate -->
            <div
                class="flex items-center justify-between rounded-xl border bg-card p-5 text-card-foreground shadow-sm sm:col-span-2 lg:col-span-1"
            >
                <div>
                    <p class="text-xs font-medium text-muted-foreground">
                        Savings Rate ({{ cashFlow.period_label }})
                    </p>
                    <div class="mt-1 flex items-baseline gap-2">
                        <span class="text-2xl font-bold tracking-tight"
                            >{{ cashFlow.savings_rate }}%</span
                        >
                        <span class="text-xs text-muted-foreground"
                            >of income saved</span
                        >
                    </div>
                </div>
                <div class="rounded-xl bg-primary/10 p-3 text-primary">
                    <PiggyBank class="h-5 w-5" />
                </div>
            </div>
        </div>

        <!-- 3. Monthly Cash Flow with Bar Chart -->
        <div
            class="rounded-xl border bg-card p-6 text-card-foreground shadow-sm"
        >
            <div
                class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
            >
                <div>
                    <h3 class="text-lg font-semibold tracking-tight">
                        Cash Flow Overview
                    </h3>
                    <p class="text-xs text-muted-foreground">
                        Comparing income, expenses, and net savings over time.
                    </p>
                </div>

                <!-- Period Selector -->
                <div
                    class="flex items-center rounded-lg border bg-muted/50 p-1 text-xs"
                >
                    <button
                        type="button"
                        @click="switchPeriod('this_month')"
                        :class="[
                            'rounded-md px-3 py-1.5 font-medium transition-all',
                            period === 'this_month'
                                ? 'bg-background text-foreground shadow-sm'
                                : 'text-muted-foreground hover:text-foreground',
                        ]"
                    >
                        This Month
                    </button>
                    <button
                        type="button"
                        @click="switchPeriod('last_month')"
                        :class="[
                            'rounded-md px-3 py-1.5 font-medium transition-all',
                            period === 'last_month'
                                ? 'bg-background text-foreground shadow-sm'
                                : 'text-muted-foreground hover:text-foreground',
                        ]"
                    >
                        Last Month
                    </button>
                </div>
            </div>

            <!-- Cash Flow KPI Stats Row -->
            <div
                class="mb-6 grid grid-cols-1 gap-4 rounded-xl border bg-muted/20 p-4 sm:grid-cols-3"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"
                    >
                        <ArrowUpRight class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-muted-foreground">
                            Total Income
                        </p>
                        <p
                            class="text-lg font-bold text-emerald-600 dark:text-emerald-400"
                        >
                            <MoneyDisplay :amount="cashFlow.income" />
                        </p>
                    </div>
                </div>

                <div
                    class="flex items-center gap-3 border-t pt-3 sm:border-t-0 sm:border-l sm:pt-0 sm:pl-4"
                >
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-rose-500/10 text-rose-600 dark:text-rose-400"
                    >
                        <ArrowDownRight class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-muted-foreground">
                            Total Expenses
                        </p>
                        <p
                            class="text-lg font-bold text-rose-600 dark:text-rose-400"
                        >
                            <MoneyDisplay :amount="cashFlow.expense" />
                        </p>
                    </div>
                </div>

                <div
                    class="flex items-center gap-3 border-t pt-3 sm:border-t-0 sm:border-l sm:pt-0 sm:pl-4"
                >
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                    >
                        <Wallet class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-muted-foreground">
                            Net Cash Flow
                        </p>
                        <p
                            class="text-lg font-bold"
                            :class="
                                cashFlow.net >= 0
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-rose-600 dark:text-rose-400'
                            "
                        >
                            <MoneyDisplay :amount="cashFlow.net" />
                        </p>
                    </div>
                </div>
            </div>

            <!-- Savings Progress Indicator -->
            <div class="mb-6 space-y-1.5">
                <div class="flex justify-between text-xs text-muted-foreground">
                    <span>Monthly Savings Target</span>
                    <span class="font-semibold text-foreground"
                        >{{ cashFlow.savings_rate }}% Saved</span
                    >
                </div>
                <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                    <div
                        class="h-full rounded-full bg-emerald-500 transition-all duration-500"
                        :style="{
                            width: `${Math.min(100, Math.max(0, cashFlow.savings_rate))}%`,
                        }"
                    ></div>
                </div>
            </div>

            <!-- Chart -->
            <div class="mt-4">
                <p class="mb-2 text-xs font-medium text-muted-foreground">
                    6-Month Trend (Income vs Expense)
                </p>
                <CashFlowBarChart :chart-data="cashFlow.chart" />
            </div>
        </div>

        <!-- 4. Asset Allocation & Recent Transactions Grid -->
        <div class="grid gap-6 lg:grid-cols-2">
            <!-- Left: Asset Allocation Card -->
            <div
                class="flex flex-col rounded-xl border bg-card p-6 text-card-foreground shadow-sm"
            >
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold tracking-tight">
                            Asset Allocation
                        </h3>
                        <p class="text-xs text-muted-foreground">
                            Distribution across cash and investment classes.
                        </p>
                    </div>
                    <Link
                        href="/accounts"
                        class="text-xs font-medium text-primary hover:underline"
                    >
                        Manage Accounts →
                    </Link>
                </div>

                <div class="my-auto">
                    <AssetAllocationDonutChart :allocation="assetAllocation" />
                </div>
            </div>

            <!-- Right: Recent Transactions Card -->
            <div
                class="flex flex-col rounded-xl border bg-card p-6 text-card-foreground shadow-sm"
            >
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold tracking-tight">
                            Recent Transactions
                        </h3>
                        <p class="text-xs text-muted-foreground">
                            Latest financial activities across all accounts.
                        </p>
                    </div>
                    <Link
                        href="/transactions"
                        class="inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline"
                    >
                        View All
                        <ArrowRight class="h-3.5 w-3.5" />
                    </Link>
                </div>

                <!-- Transaction List -->
                <div
                    v-if="recentTransactions.length > 0"
                    class="divide-y divide-border"
                >
                    <div
                        v-for="tx in recentTransactions"
                        :key="tx.id"
                        class="flex items-center justify-between rounded-lg px-2 py-3 transition-colors hover:bg-muted/40"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <!-- Category / Type Icon -->
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                                :style="{
                                    backgroundColor:
                                        (tx.category?.color || '#3b82f6') +
                                        '20',
                                    color: tx.category?.color || '#3b82f6',
                                }"
                            >
                                <CategoryIcon
                                    :icon="
                                        tx.category?.icon ||
                                        (tx.type === 'transfer'
                                            ? 'arrow-right-left'
                                            : 'banknote')
                                    "
                                    class="h-5 w-5"
                                />
                            </div>

                            <div class="min-w-0">
                                <Link
                                    :href="`/transactions/${tx.id}`"
                                    class="block truncate text-sm font-semibold hover:underline"
                                >
                                    {{ tx.description }}
                                </Link>
                                <p
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ tx.category?.name || 'Uncategorized' }} •
                                    {{ getTransactionAccountName(tx) }}
                                </p>
                            </div>
                        </div>

                        <div class="ml-4 shrink-0 text-right">
                            <p
                                class="text-sm font-bold"
                                :class="{
                                    'text-emerald-600 dark:text-emerald-400':
                                        tx.type === 'income',
                                    'text-rose-600 dark:text-rose-400':
                                        tx.type === 'expense',
                                    'text-foreground':
                                        tx.type === 'transfer' ||
                                        tx.type === 'adjustment',
                                }"
                            >
                                <span v-if="tx.type === 'income'">+</span>
                                <span v-else-if="tx.type === 'expense'">-</span>
                                <MoneyDisplay :amount="tx.amount" />
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ formatDate(tx.date) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="my-auto py-8">
                    <EmptyState
                        :icon="Calendar"
                        title="No transactions yet"
                        description="Your recent financial transactions will show up here once you record your first income or expense."
                        action-text="Record Transaction"
                        action-href="/transactions/create"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
