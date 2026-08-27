<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowRight,
    Calendar,
    Plus,
    TrendingDown,
    TrendingUp,
} from '@lucide/vue';
import dayjs from 'dayjs';
import { computed } from 'vue';
import AiQuickInput from '@/components/AiQuickInput.vue';
import AssetAllocationDonutChart from '@/components/AssetAllocationDonutChart.vue';
import CashFlowBarChart from '@/components/CashFlowBarChart.vue';
import CategoryIcon from '@/components/CategoryIcon.vue';
import BudgetSummaryCard from '@/components/Dashboard/BudgetSummaryCard.vue';
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
    budgetSummary?: any;
    recentTransactions?: any;
    accounts?: Array<{ id: string; name: string; type?: string }>;
    categories?: Array<{
        id: string;
        name: string;
        slug?: string;
        type?: string;
    }>;
    period: string;
}>();

const normalizedRecentTransactions = computed<any[]>(() => {
    if (Array.isArray(props.recentTransactions)) {
        return props.recentTransactions;
    }

    if (
        props.recentTransactions &&
        typeof props.recentTransactions === 'object'
    ) {
        return Object.values(props.recentTransactions);
    }

    return [];
});

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
        class="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-5 p-4 md:p-6 lg:p-8"
    >
        <!-- Header & Quick Actions -->
        <div
            class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center"
        >
            <h1 class="text-2xl font-semibold tracking-tight">
                Financial Overview
            </h1>
            <div class="grid w-full grid-cols-2 gap-2 sm:flex sm:w-auto">
                <Link
                    href="/transactions/create"
                    class="inline-flex h-10 items-center justify-center gap-1.5 rounded-lg bg-primary px-4 text-sm font-medium whitespace-nowrap text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:ring-offset-2 focus-visible:outline-none active:scale-[0.98]"
                >
                    <Plus class="size-4" aria-hidden="true" />
                    Record Transaction
                </Link>
                <Link
                    href="/accounts/create"
                    class="inline-flex h-10 items-center justify-center rounded-lg border border-input bg-background px-4 text-sm font-medium whitespace-nowrap transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:ring-offset-2 focus-visible:outline-none active:scale-[0.98]"
                >
                    Add Account
                </Link>
            </div>
        </div>

        <!-- Quick text input -->
        <AiQuickInput
            :accounts="props.accounts"
            :categories="props.categories"
        />

        <!-- 1. Net worth -->
        <section
            aria-label="Net worth"
            class="rounded-xl bg-brand-600 p-5 text-white sm:p-6"
        >
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                <h2 class="text-sm font-medium text-white/75">Net Worth</h2>
                <span
                    v-if="netWorth.change_percentage !== 0"
                    class="inline-flex items-center gap-1 text-xs font-medium text-white/90 tabular-nums"
                >
                    <component
                        :is="
                            netWorth.is_positive_change
                                ? TrendingUp
                                : TrendingDown
                        "
                        class="size-3.5"
                        aria-hidden="true"
                    />
                    {{ netWorth.is_positive_change ? '+' : ''
                    }}{{ netWorth.change_percentage }}% vs last month
                </span>
            </div>

            <p
                class="mt-1 text-3xl font-semibold tracking-tight tabular-nums sm:text-4xl"
            >
                <MoneyDisplay :amount="netWorth.net_worth" />
            </p>

            <dl
                class="mt-5 grid grid-cols-3 divide-x divide-white/20 border-t border-white/20 pt-4"
            >
                <div class="min-w-0 pr-3">
                    <dt class="text-xs text-white/70">Cash &amp; Bank</dt>
                    <dd
                        class="mt-0.5 text-sm font-semibold tabular-nums sm:text-base"
                    >
                        <MoneyDisplay :amount="netWorth.total_cash" />
                    </dd>
                </div>
                <div class="min-w-0 px-3 sm:px-5">
                    <dt class="text-xs text-white/70">Investments</dt>
                    <dd
                        class="mt-0.5 text-sm font-semibold tabular-nums sm:text-base"
                    >
                        <MoneyDisplay :amount="netWorth.total_investments" />
                    </dd>
                </div>
                <div class="min-w-0 pl-3 sm:pl-5">
                    <dt class="text-xs text-white/70">Liabilities</dt>
                    <dd
                        class="mt-0.5 text-sm font-semibold tabular-nums sm:text-base"
                    >
                        <MoneyDisplay :amount="netWorth.total_liabilities" />
                    </dd>
                </div>
            </dl>
        </section>

        <!-- Budget summary -->
        <BudgetSummaryCard :budget-summary="props.budgetSummary" />

        <!-- 3. Cash flow -->
        <section
            aria-labelledby="cash-flow-title"
            class="rounded-xl border bg-card p-4 sm:p-6"
        >
            <div
                class="mb-4 flex flex-col justify-between gap-3 sm:flex-row sm:items-center"
            >
                <h2
                    id="cash-flow-title"
                    class="text-base font-semibold tracking-tight"
                >
                    Cash Flow
                    <span class="font-normal text-muted-foreground">
                        · {{ cashFlow.period_label }}
                    </span>
                </h2>

                <div
                    role="group"
                    aria-label="Period"
                    class="flex w-full items-center rounded-lg bg-muted p-0.5 text-xs sm:w-auto"
                >
                    <button
                        type="button"
                        @click="switchPeriod('this_month')"
                        :aria-pressed="period === 'this_month'"
                        :class="[
                            'h-8 flex-1 rounded-md px-3 font-medium transition-colors sm:flex-none',
                            period === 'this_month'
                                ? 'bg-background text-foreground shadow-xs'
                                : 'text-muted-foreground hover:text-foreground',
                        ]"
                    >
                        This Month
                    </button>
                    <button
                        type="button"
                        @click="switchPeriod('last_month')"
                        :aria-pressed="period === 'last_month'"
                        :class="[
                            'h-8 flex-1 rounded-md px-3 font-medium transition-colors sm:flex-none',
                            period === 'last_month'
                                ? 'bg-background text-foreground shadow-xs'
                                : 'text-muted-foreground hover:text-foreground',
                        ]"
                    >
                        Last Month
                    </button>
                </div>
            </div>

            <dl
                class="mb-5 grid grid-cols-2 divide-x divide-y rounded-lg border sm:grid-cols-4 sm:divide-y-0"
            >
                <div class="min-w-0 px-3 py-3 sm:px-4">
                    <dt class="text-xs text-muted-foreground">Income</dt>
                    <dd
                        class="mt-0.5 text-sm font-semibold tabular-nums sm:text-lg"
                        :class="
                            cashFlow.income > 0
                                ? 'text-emerald-600 dark:text-emerald-400'
                                : ''
                        "
                    >
                        <MoneyDisplay :amount="cashFlow.income" />
                    </dd>
                </div>
                <div class="min-w-0 px-3 py-3 sm:px-4">
                    <dt class="text-xs text-muted-foreground">Expenses</dt>
                    <dd
                        class="mt-0.5 text-sm font-semibold tabular-nums sm:text-lg"
                    >
                        <MoneyDisplay :amount="cashFlow.expense" />
                    </dd>
                </div>
                <div class="min-w-0 px-3 py-3 sm:px-4">
                    <dt class="text-xs text-muted-foreground">Net</dt>
                    <dd
                        class="mt-0.5 text-sm font-semibold tabular-nums sm:text-lg"
                        :class="
                            cashFlow.net > 0
                                ? 'text-emerald-600 dark:text-emerald-400'
                                : cashFlow.net < 0
                                  ? 'text-rose-600 dark:text-rose-400'
                                  : ''
                        "
                    >
                        <MoneyDisplay :amount="cashFlow.net" />
                    </dd>
                </div>
                <div class="min-w-0 px-3 py-3 sm:px-4">
                    <dt class="text-xs text-muted-foreground">Savings Rate</dt>
                    <dd
                        class="mt-0.5 text-sm font-semibold tabular-nums sm:text-lg"
                    >
                        {{ cashFlow.savings_rate }}%
                    </dd>
                </div>
            </dl>

            <CashFlowBarChart :chart-data="cashFlow.chart" />
        </section>

        <!-- 4. Asset allocation & recent transactions -->
        <div class="grid gap-5 lg:grid-cols-2">
            <section
                aria-labelledby="allocation-title"
                class="flex flex-col rounded-xl border bg-card p-4 sm:p-6"
            >
                <div class="mb-5 flex items-center justify-between gap-3">
                    <h2
                        id="allocation-title"
                        class="text-base font-semibold tracking-tight"
                    >
                        Asset Allocation
                    </h2>
                    <Link
                        href="/assets"
                        class="-mr-2 inline-flex h-8 items-center gap-1 rounded-md px-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                    >
                        Portfolio
                        <ArrowRight class="size-4" aria-hidden="true" />
                    </Link>
                </div>

                <div class="my-auto">
                    <AssetAllocationDonutChart :allocation="assetAllocation" />
                </div>
            </section>

            <section
                aria-labelledby="recent-title"
                class="flex flex-col rounded-xl border bg-card p-4 sm:p-6"
            >
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h2
                        id="recent-title"
                        class="text-base font-semibold tracking-tight"
                    >
                        Recent Transactions
                    </h2>
                    <Link
                        href="/transactions"
                        class="-mr-2 inline-flex h-8 items-center gap-1 rounded-md px-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                    >
                        View all
                        <ArrowRight class="size-4" aria-hidden="true" />
                    </Link>
                </div>

                <ul
                    v-if="normalizedRecentTransactions.length > 0"
                    class="divide-y divide-border"
                >
                    <li
                        v-for="tx in normalizedRecentTransactions"
                        :key="tx.id"
                        class="flex items-center justify-between gap-4 py-3"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <div
                                class="flex size-10 shrink-0 items-center justify-center rounded-xl"
                                :style="{
                                    backgroundColor:
                                        (tx.category?.color || '#3b82f6') +
                                        '20',
                                    color: tx.category?.color || '#3b82f6',
                                }"
                                aria-hidden="true"
                            >
                                <CategoryIcon
                                    :icon="
                                        tx.category?.icon ||
                                        (tx.type === 'transfer'
                                            ? 'arrow-right-left'
                                            : 'banknote')
                                    "
                                    class="size-5"
                                />
                            </div>

                            <div class="min-w-0">
                                <Link
                                    :href="`/transactions/${tx.id}`"
                                    class="block truncate text-sm font-medium hover:underline"
                                >
                                    {{ tx.description }}
                                </Link>
                                <p
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ tx.category?.name || 'Uncategorized' }}
                                    · {{ getTransactionAccountName(tx) }}
                                </p>
                            </div>
                        </div>

                        <div class="shrink-0 text-right tabular-nums">
                            <p
                                class="text-sm font-semibold"
                                :class="{
                                    'text-emerald-600 dark:text-emerald-400':
                                        tx.type === 'income',
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
                    </li>
                </ul>

                <div v-else class="my-auto py-8">
                    <EmptyState
                        :icon="Calendar"
                        title="No transactions yet"
                        description="Your recent financial transactions will show up here once you record your first income or expense."
                        action-text="Record Transaction"
                        action-href="/transactions/create"
                    />
                </div>
            </section>
        </div>
    </div>
</template>
