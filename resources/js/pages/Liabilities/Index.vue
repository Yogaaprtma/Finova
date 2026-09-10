<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    PlusCircle,
    CreditCard,
    Calendar,
    Percent,
    Receipt,
    Banknote,
    Clock,
    Search,
    ArrowUpRight,
    ShieldCheck,
    Coins,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import MoneyDisplay from '@/components/MoneyDisplay.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Liabilities & Debt',
                href: '/liabilities',
            },
        ],
    },
});

interface LiabilityItem {
    id: string;
    name: string;
    type: string;
    currency: string;
    initial_balance: number;
    current_balance: number;
    credit_limit: number | null;
    interest_rate: number | null;
    minimum_payment: number | null;
    due_date_day: number | null;
    start_date: string | null;
    end_date: string | null;
    remaining_terms: number | null;
    is_active: boolean;
    notes: string | null;
    transactions_count: number;
}

const props = defineProps<{
    overview: {
        metrics: {
            total_debt: number;
            total_original: number;
            total_monthly_obligations: number;
            total_credit_limit: number;
            total_credit_used: number;
            credit_utilization: number;
            liabilities_count: number;
            credit_cards_count: number;
        };
        breakdown: {
            items: Array<{
                type: string;
                label: string;
                color: string;
                value: number;
                count: number;
                percentage: number;
            }>;
            labels: string[];
            values: number[];
            colors: string[];
            total: number;
        };
        liabilities: LiabilityItem[];
    };
}>();

const activeTab = ref<'all' | 'credit_card' | 'loan_installment'>('all');
const searchQuery = ref('');

const filteredLiabilities = computed(() => {
    let list = props.overview.liabilities;

    if (activeTab.value === 'credit_card') {
        list = list.filter((l) => l.type === 'credit_card');
    } else if (activeTab.value === 'loan_installment') {
        list = list.filter(
            (l) => l.type === 'personal_loan' || l.type === 'installment',
        );
    }

    if (!searchQuery.value.trim()) {
        return list;
    }

    const q = searchQuery.value.toLowerCase().trim();

    return list.filter((l) => {
        return (
            (l.name && l.name.toLowerCase().includes(q)) ||
            (l.type && l.type.toLowerCase().includes(q)) ||
            (l.notes && l.notes.toLowerCase().includes(q))
        );
    });
});

const getLiabilityIcon = (type: string) => {
    switch (type) {
        case 'credit_card':
            return CreditCard;
        case 'personal_loan':
            return Banknote;
        case 'installment':
            return Calendar;
        default:
            return Receipt;
    }
};

const getLiabilityBadgeClass = (type: string) => {
    switch (type) {
        case 'credit_card':
            return 'bg-rose-500/10 text-rose-600 dark:text-rose-400';
        case 'personal_loan':
            return 'bg-amber-500/10 text-amber-600 dark:text-amber-400';
        case 'installment':
            return 'bg-purple-500/10 text-purple-600 dark:text-purple-400';
        default:
            return 'bg-slate-500/10 text-slate-600 dark:text-slate-400';
    }
};

const formatLiabilityType = (type: string) => {
    switch (type) {
        case 'credit_card':
            return 'Credit Card';
        case 'personal_loan':
            return 'Personal Loan';
        case 'installment':
            return 'Installment';
        default:
            return 'Other Debt';
    }
};
</script>

<template>
    <Head title="Liabilities & Debt" />

    <div
        class="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8"
    >
        <!-- Header -->
        <div
            class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center"
        >
            <div>
                <h1 class="text-3xl font-bold tracking-tight">
                    Liabilities &amp; Debt
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Monitor outstanding balances, credit utilization, and
                    repayment schedules.
                </p>
            </div>
            <Link
                href="/liabilities/create"
                class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow transition-colors hover:bg-primary/90 sm:w-auto"
            >
                <PlusCircle class="h-4 w-4" />
                Add Liability
            </Link>
        </div>

        <!-- Metrics Overview Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Total Debt Card -->
            <div
                class="relative flex flex-col justify-between overflow-hidden rounded-2xl border bg-card p-5 text-card-foreground shadow-sm"
            >
                <div>
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            Total Debt
                        </span>
                        <div
                            class="rounded-xl bg-rose-500/10 p-2 text-rose-600 dark:text-rose-400"
                        >
                            <CreditCard class="h-4 w-4" />
                        </div>
                    </div>
                    <div
                        class="mt-2 text-2xl font-extrabold tracking-tight text-rose-600 dark:text-rose-400"
                    >
                        <MoneyDisplay :amount="overview.metrics.total_debt" />
                    </div>
                </div>
                <div
                    class="mt-4 flex items-center justify-between text-xs text-muted-foreground"
                >
                    <span
                        >{{ overview.metrics.liabilities_count }} active
                        obligations</span
                    >
                    <span
                        v-if="overview.metrics.total_debt === 0"
                        class="font-semibold text-emerald-600 dark:text-emerald-400"
                    >
                        Debt Free 🎉
                    </span>
                </div>
            </div>

            <!-- Monthly Obligations Card -->
            <div
                class="relative flex flex-col justify-between overflow-hidden rounded-2xl border bg-card p-5 text-card-foreground shadow-sm"
            >
                <div>
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            Monthly Repayments
                        </span>
                        <div
                            class="rounded-xl bg-amber-500/10 p-2 text-amber-600 dark:text-amber-400"
                        >
                            <Calendar class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-2 text-2xl font-extrabold tracking-tight">
                        <MoneyDisplay
                            :amount="overview.metrics.total_monthly_obligations"
                        />
                    </div>
                </div>
                <div class="mt-4 text-xs font-medium text-muted-foreground">
                    Estimated monthly minimums
                </div>
            </div>

            <!-- Credit Utilization Card -->
            <div
                class="relative flex flex-col justify-between overflow-hidden rounded-2xl border bg-card p-5 text-card-foreground shadow-sm"
            >
                <div>
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            Credit Utilization
                        </span>
                        <div
                            class="rounded-xl bg-purple-500/10 p-2 text-purple-600 dark:text-purple-400"
                        >
                            <Percent class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-extrabold tracking-tight">
                            {{ overview.metrics.credit_utilization }}%
                        </span>
                        <span class="text-xs text-muted-foreground">
                            of limit
                        </span>
                    </div>
                </div>
                <div
                    class="mt-4 flex items-center justify-between text-xs text-muted-foreground"
                >
                    <span>
                        <MoneyDisplay
                            :amount="overview.metrics.total_credit_used"
                        />
                    </span>
                    <span>
                        limit:
                        <MoneyDisplay
                            :amount="overview.metrics.total_credit_limit"
                        />
                    </span>
                </div>
            </div>

            <!-- Original Borrowed Capital -->
            <div
                class="relative flex flex-col justify-between overflow-hidden rounded-2xl border bg-card p-5 text-card-foreground shadow-sm"
            >
                <div>
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            Initial Debt Total
                        </span>
                        <div
                            class="rounded-xl bg-blue-500/10 p-2 text-blue-600 dark:text-blue-400"
                        >
                            <Coins class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-2 text-2xl font-extrabold tracking-tight">
                        <MoneyDisplay
                            :amount="overview.metrics.total_original"
                        />
                    </div>
                </div>
                <div class="mt-4 text-xs font-medium text-muted-foreground">
                    Cumulative principal recorded
                </div>
            </div>
        </div>

        <!-- Debt Category Breakdown Card -->
        <div
            v-if="
                overview.metrics.liabilities_count > 0 &&
                overview.breakdown.items.length > 0
            "
            class="rounded-2xl border bg-card p-6 shadow-sm"
        >
            <div
                class="flex flex-col gap-2 border-b border-border/60 pb-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h3 class="text-base font-bold tracking-tight">
                        Debt Breakdown
                    </h3>
                    <p class="text-xs text-muted-foreground">
                        Distribution across credit cards, loans, and
                        installments
                    </p>
                </div>
                <div
                    class="flex items-center gap-2 self-start rounded-xl bg-muted/50 px-3 py-1.5 text-xs font-medium sm:self-auto"
                >
                    <span class="text-muted-foreground">Total Balance:</span>
                    <span class="font-bold text-rose-600 dark:text-rose-400">
                        <MoneyDisplay :amount="overview.metrics.total_debt" />
                    </span>
                </div>
            </div>

            <div class="mt-5 space-y-3.5">
                <div
                    v-for="item in overview.breakdown.items"
                    :key="item.type"
                    class="rounded-xl border border-border/50 bg-muted/20 p-4 transition-colors hover:bg-muted/40"
                >
                    <div
                        class="flex items-center justify-between text-xs font-medium"
                    >
                        <div class="flex items-center gap-2.5">
                            <span
                                class="inline-block h-3 w-3 shrink-0 rounded-full shadow-sm"
                                :style="{ backgroundColor: item.color }"
                            />
                            <span class="text-sm font-bold text-foreground">
                                {{ item.label }}
                            </span>
                            <span
                                class="rounded-full bg-muted px-2 py-0.5 text-[10px] font-medium text-muted-foreground"
                            >
                                {{ item.count }}
                                {{ item.count === 1 ? 'account' : 'accounts' }}
                            </span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-semibold text-foreground">
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

                    <!-- Progress Bar -->
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

        <!-- Filter Tabs & Search Bar -->
        <div
            class="flex flex-col items-stretch justify-between gap-3 sm:flex-row sm:items-center"
        >
            <div class="flex rounded-xl border bg-muted/50 p-1">
                <button
                    type="button"
                    :class="[
                        'rounded-lg px-3 py-1.5 text-xs font-semibold transition-all',
                        activeTab === 'all'
                            ? 'bg-background text-foreground shadow-sm'
                            : 'text-muted-foreground hover:text-foreground',
                    ]"
                    @click="activeTab = 'all'"
                >
                    All ({{ overview.metrics.liabilities_count }})
                </button>
                <button
                    type="button"
                    :class="[
                        'rounded-lg px-3 py-1.5 text-xs font-semibold transition-all',
                        activeTab === 'credit_card'
                            ? 'bg-background text-foreground shadow-sm'
                            : 'text-muted-foreground hover:text-foreground',
                    ]"
                    @click="activeTab = 'credit_card'"
                >
                    Credit Cards ({{ overview.metrics.credit_cards_count }})
                </button>
                <button
                    type="button"
                    :class="[
                        'rounded-lg px-3 py-1.5 text-xs font-semibold transition-all',
                        activeTab === 'loan_installment'
                            ? 'bg-background text-foreground shadow-sm'
                            : 'text-muted-foreground hover:text-foreground',
                    ]"
                    @click="activeTab = 'loan_installment'"
                >
                    Loans &amp; Installments
                </button>
            </div>

            <div class="relative w-full sm:w-64">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search liabilities..."
                    class="h-9 w-full rounded-xl border border-input bg-background pr-3 pl-9 text-xs shadow-sm transition-colors focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                />
            </div>
        </div>

        <!-- Liabilities Grid -->
        <div
            v-if="filteredLiabilities.length > 0"
            class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3"
        >
            <Link
                v-for="liability in filteredLiabilities"
                :key="liability.id"
                :href="`/liabilities/${liability.id}`"
                class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border bg-card p-5 text-card-foreground shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/50 hover:shadow-md"
            >
                <div>
                    <!-- Header -->
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div
                                :class="[
                                    'flex h-11 w-11 shrink-0 items-center justify-center rounded-xl transition-transform group-hover:scale-105',
                                    getLiabilityBadgeClass(liability.type),
                                ]"
                            >
                                <component
                                    :is="getLiabilityIcon(liability.type)"
                                    class="h-5 w-5"
                                />
                            </div>
                            <div>
                                <div class="flex items-center gap-1.5">
                                    <h4
                                        class="text-sm font-bold tracking-tight text-foreground transition-colors group-hover:text-primary"
                                    >
                                        {{ liability.name }}
                                    </h4>
                                    <ArrowUpRight
                                        class="h-3.5 w-3.5 text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100"
                                    />
                                </div>
                                <div
                                    class="flex items-center gap-1.5 text-xs text-muted-foreground"
                                >
                                    <span
                                        v-if="liability.due_date_day"
                                        class="flex items-center gap-1"
                                    >
                                        <Clock class="h-3 w-3" />
                                        Due on day {{ liability.due_date_day }}
                                    </span>
                                    <span
                                        v-if="
                                            liability.due_date_day &&
                                            liability.remaining_terms
                                        "
                                        >•</span
                                    >
                                    <span v-if="liability.remaining_terms">
                                        {{ liability.remaining_terms }} terms
                                        left
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Type Pill -->
                        <span
                            :class="[
                                'rounded-full px-2.5 py-0.5 text-[10px] font-bold tracking-wider uppercase',
                                getLiabilityBadgeClass(liability.type),
                            ]"
                        >
                            {{ formatLiabilityType(liability.type) }}
                        </span>
                    </div>

                    <!-- Details Box -->
                    <div
                        class="mt-4 grid grid-cols-2 gap-2 rounded-xl bg-muted/40 p-3 text-xs"
                    >
                        <div>
                            <span
                                class="text-[10px] font-medium tracking-wider text-muted-foreground uppercase"
                            >
                                Minimum Payment
                            </span>
                            <p class="mt-0.5 font-bold text-foreground">
                                <MoneyDisplay
                                    v-if="liability.minimum_payment"
                                    :amount="liability.minimum_payment"
                                />
                                <span
                                    v-else
                                    class="font-normal text-muted-foreground"
                                    >-</span
                                >
                            </p>
                        </div>
                        <div>
                            <span
                                class="text-[10px] font-medium tracking-wider text-muted-foreground uppercase"
                            >
                                {{
                                    liability.type === 'credit_card'
                                        ? 'Credit Limit'
                                        : 'Interest Rate'
                                }}
                            </span>
                            <p class="mt-0.5 font-bold text-foreground">
                                <span
                                    v-if="
                                        liability.type === 'credit_card' &&
                                        liability.credit_limit
                                    "
                                >
                                    <MoneyDisplay
                                        :amount="liability.credit_limit"
                                    />
                                </span>
                                <span v-else-if="liability.interest_rate">
                                    {{ liability.interest_rate }}% p.a.
                                </span>
                                <span
                                    v-else
                                    class="font-normal text-muted-foreground"
                                    >-</span
                                >
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Bottom Balance -->
                <div
                    class="mt-5 flex items-end justify-between border-t border-border/60 pt-4"
                >
                    <div>
                        <span
                            class="text-[10px] font-medium tracking-wider text-muted-foreground uppercase"
                        >
                            Current Balance
                        </span>
                        <p
                            class="text-lg font-extrabold tracking-tight text-rose-600 dark:text-rose-400"
                        >
                            <MoneyDisplay :amount="liability.current_balance" />
                        </p>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <span
                            v-if="liability.current_balance === 0"
                            class="rounded-md bg-emerald-500/10 px-2 py-1 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400"
                        >
                            Paid Off
                        </span>
                        <span
                            v-else
                            class="rounded-md bg-primary/10 px-2.5 py-1 text-xs font-semibold text-primary transition-colors group-hover:bg-primary group-hover:text-primary-foreground"
                        >
                            Manage &amp; Pay
                        </span>
                    </div>
                </div>
            </Link>
        </div>

        <!-- Empty State -->
        <EmptyState
            v-else
            :icon="ShieldCheck"
            title="No liabilities recorded"
            :description="
                searchQuery
                    ? 'No debt matches your search query.'
                    : 'You have no outstanding liabilities. Add your credit cards, loans, or installments to keep complete track of your net worth.'
            "
        >
            <template #action>
                <Link
                    href="/liabilities/create"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow transition-colors hover:bg-primary/90"
                >
                    <PlusCircle class="h-4 w-4" />
                    Add Your First Liability
                </Link>
            </template>
        </EmptyState>
    </div>
</template>