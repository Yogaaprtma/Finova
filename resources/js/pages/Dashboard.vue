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
import 'dayjs/locale/id';
import { computed } from 'vue';
import AiQuickInput from '@/components/AiQuickInput.vue';
import AssetAllocationDonutChart from '@/components/AssetAllocationDonutChart.vue';
import CashFlowBarChart from '@/components/CashFlowBarChart.vue';
import CategoryIcon from '@/components/CategoryIcon.vue';
import BudgetSummaryCard from '@/components/Dashboard/BudgetSummaryCard.vue';
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

const RECENT_LIMIT = 6;

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

const displayedTransactions = computed(() =>
    normalizedRecentTransactions.value.slice(0, RECENT_LIMIT),
);

const MONTH_NAME_MAP: Record<string, string> = {
    January: 'Januari',
    February: 'Februari',
    March: 'Maret',
    April: 'April',
    May: 'Mei',
    June: 'Juni',
    July: 'Juli',
    August: 'Agustus',
    September: 'September',
    October: 'Oktober',
    November: 'November',
    December: 'Desember',
};

const displayPeriodLabel = computed(() =>
    (props.cashFlow.period_label || '').replace(
        /\b(January|February|March|April|May|June|July|August|September|October|November|December)\b/g,
        (month) => MONTH_NAME_MAP[month] ?? month,
    ),
);

const netWorthParts = computed(() => {
    const nw = props.netWorth;
    const items = [
        {
            key: 'cash',
            label: 'Kas & Bank',
            href: '/accounts',
            raw: nw.total_cash,
            value: Math.max(0, nw.total_cash || 0),
            dot: 'bg-emerald-500',
        },
        {
            key: 'investments',
            label: 'Investasi',
            href: '/assets',
            raw: nw.total_investments,
            value: Math.max(0, nw.total_investments || 0),
            dot: 'bg-primary',
        },
        {
            key: 'liabilities',
            label: 'Utang',
            href: '/liabilities',
            raw: nw.total_liabilities,
            value: Math.abs(nw.total_liabilities || 0),
            dot: 'bg-rose-500',
        },
    ];
    const total = items.reduce((sum, item) => sum + item.value, 0);

    return {
        hasData: total > 0,
        items: items.map((item) => ({
            ...item,
            pct: total > 0 ? (item.value / total) * 100 : 0,
        })),
    };
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
    const d = dayjs(dateStr).locale('id');

    return d.year() === dayjs().year()
        ? d.format('D MMM')
        : d.format('D MMM YYYY');
};

const getTransactionAccountName = (tx: any) => {
    if (tx.type === 'transfer') {
        const from = tx.entries?.find((e: any) => e.entry_type === 'debit')
            ?.account?.name;
        const to = tx.entries?.find((e: any) => e.entry_type === 'credit')
            ?.account?.name;

        return `${from || 'Rekening'} → ${to || 'Rekening'}`;
    }

    const entry = tx.entries?.find((e: any) => e.account_id !== null);

    return entry?.account?.name || 'Rekening';
};

const categoryTint = (tx: any) => {
    const color =
        tx.category?.color || (tx.type === 'transfer' ? '#0ea5e9' : '#3b82f6');

    return {
        backgroundColor: `color-mix(in srgb, ${color} 14%, transparent)`,
        color,
    };
};

const periodTabClass = (active: boolean) => [
    'h-8 flex-1 rounded-md px-3 text-xs font-medium transition-colors focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none sm:flex-none',
    active
        ? 'bg-background text-foreground shadow-xs'
        : 'text-muted-foreground hover:text-foreground',
];
</script>

<template>
    <Head title="Dasbor Keuangan" />

    <div
        class="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-5 p-4 md:p-6 lg:p-8"
    >
        <!-- Header & Quick Actions -->
        <header
            class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center"
        >
            <div>
                <h1
                    class="text-2xl font-semibold tracking-tight sm:text-[26px]"
                >
                    Ringkasan Keuangan
                </h1>
                <p class="mt-0.5 text-sm text-muted-foreground">
                    {{ displayPeriodLabel }} · Pantau kekayaan bersih, arus kas,
                    dan alokasi aset.
                </p>
            </div>

            <div class="grid w-full grid-cols-2 gap-2.5 sm:flex sm:w-auto">
                <Link
                    href="/transactions/create"
                    class="inline-flex h-10 items-center justify-center gap-1.5 rounded-lg bg-primary px-4 text-sm font-medium whitespace-nowrap text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:ring-offset-2 focus-visible:outline-none active:scale-[0.98] sm:h-9"
                >
                    <Plus class="size-4" aria-hidden="true" />
                    Catat Transaksi
                </Link>
                <Link
                    href="/accounts/create"
                    class="inline-flex h-10 items-center justify-center rounded-lg border border-input bg-card px-4 text-sm font-medium whitespace-nowrap transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:ring-offset-2 focus-visible:outline-none active:scale-[0.98] sm:h-9"
                >
                    Tambah Rekening
                </Link>
            </div>
        </header>

        <!-- Row 1: Hero Bento (Net Worth Light/Adaptive Card + Quick Input) -->
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">
            <!-- Net Worth Card -->
            <section
                aria-label="Kekayaan bersih"
                class="@container flex flex-col justify-between gap-5 rounded-xl border bg-card p-5 text-card-foreground sm:p-6 lg:col-span-7"
            >
                <div>
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <h2
                                class="text-xs font-medium tracking-wider text-muted-foreground uppercase"
                            >
                                Total Kekayaan Bersih
                            </h2>
                            <span
                                v-if="netWorth.change_percentage !== 0"
                                :class="[
                                    'inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-[11px] font-medium tabular-nums',
                                    netWorth.is_positive_change
                                        ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400'
                                        : 'border-rose-500/20 bg-rose-500/10 text-rose-700 dark:text-rose-400',
                                ]"
                            >
                                <component
                                    :is="
                                        netWorth.is_positive_change
                                            ? TrendingUp
                                            : TrendingDown
                                    "
                                    class="size-3"
                                    aria-hidden="true"
                                />
                                {{ netWorth.is_positive_change ? '+' : ''
                                }}{{ netWorth.change_percentage }}% vs bulan
                                lalu
                            </span>
                        </div>

                        <Link
                            href="/reports?type=net_worth"
                            class="-mr-2 inline-flex h-8 shrink-0 items-center gap-1 rounded-md px-2 text-xs font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                        >
                            Tren
                            <ArrowRight class="size-3.5" aria-hidden="true" />
                        </Link>
                    </div>

                    <p
                        class="mt-2 text-3xl font-semibold tracking-tight text-foreground tabular-nums sm:text-4xl"
                    >
                        <MoneyDisplay :amount="netWorth.net_worth" />
                    </p>

                    <!-- Asset / Liability Ratio Track -->
                    <div
                        class="mt-4 flex h-1.5 w-full gap-0.5 overflow-hidden rounded-full bg-muted"
                        role="img"
                        :aria-label="`Komposisi: ${netWorthParts.items
                            .map((p) => `${p.label} ${Math.round(p.pct)}%`)
                            .join(', ')}`"
                    >
                        <template v-if="netWorthParts.hasData">
                            <div
                                v-for="part in netWorthParts.items"
                                v-show="part.pct > 0"
                                :key="part.key"
                                :class="[
                                    'h-full transition-all duration-500',
                                    part.dot,
                                ]"
                                :style="{ flex: `${part.pct} 1 0%` }"
                            ></div>
                        </template>
                    </div>
                </div>

                <!-- Inset Breakdown Strip: Rows on narrow containers, 3-col hairline grid on @md+ -->
                <dl
                    class="grid grid-cols-1 gap-px overflow-hidden rounded-xl border bg-border @md:grid-cols-3"
                >
                    <Link
                        v-for="part in netWorthParts.items"
                        :key="part.key"
                        :href="part.href"
                        class="group flex min-w-0 items-center justify-between gap-3 bg-card px-3.5 py-2.5 transition-colors hover:bg-muted/40 @md:block @md:px-4 @md:py-3"
                    >
                        <dt
                            class="flex items-center gap-2 text-xs text-muted-foreground transition-colors group-hover:text-foreground"
                        >
                            <span
                                class="size-2 shrink-0 rounded-full"
                                :class="part.dot"
                                aria-hidden="true"
                            ></span>
                            <span class="truncate">{{ part.label }}</span>
                        </dt>
                        <dd
                            class="flex items-baseline justify-end gap-1.5 text-sm font-semibold text-foreground tabular-nums @md:mt-1 @md:justify-start"
                        >
                            <MoneyDisplay :amount="part.raw" />
                            <span
                                class="text-[11px] font-normal text-muted-foreground"
                            >
                                {{ Math.round(part.pct) }}%
                            </span>
                        </dd>
                    </Link>
                </dl>
            </section>

            <!-- Quick AI Transaction Input -->
            <div class="lg:col-span-5">
                <AiQuickInput
                    :accounts="props.accounts"
                    :categories="props.categories"
                    compact
                />
            </div>
        </div>

        <!-- Row 2: Cash Flow + Monthly Budget Bento -->
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">
            <section
                aria-labelledby="cash-flow-title"
                class="rounded-xl border bg-card p-5 sm:p-6 lg:col-span-8"
            >
                <div
                    class="mb-4 flex flex-col justify-between gap-3 sm:flex-row sm:items-center"
                >
                    <div>
                        <h2
                            id="cash-flow-title"
                            class="text-base font-semibold tracking-tight"
                        >
                            Arus Kas
                        </h2>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{ displayPeriodLabel }} · Ringkasan pemasukan &amp;
                            pengeluaran
                        </p>
                    </div>

                    <div
                        role="group"
                        aria-label="Periode"
                        class="flex w-full items-center rounded-lg bg-muted p-0.5 sm:w-auto"
                    >
                        <button
                            type="button"
                            @click="switchPeriod('this_month')"
                            :aria-pressed="period === 'this_month'"
                            :class="periodTabClass(period === 'this_month')"
                        >
                            Bulan Ini
                        </button>
                        <button
                            type="button"
                            @click="switchPeriod('last_month')"
                            :aria-pressed="period === 'last_month'"
                            :class="periodTabClass(period === 'last_month')"
                        >
                            Bulan Lalu
                        </button>
                    </div>
                </div>

                <!-- Side-by-side KPI column + bar chart at xl, stacked below xl -->
                <div
                    class="grid gap-5 xl:grid-cols-[13.5rem_minmax(0,1fr)] xl:items-start"
                >
                    <dl
                        class="grid grid-cols-2 gap-px overflow-hidden rounded-xl border bg-border sm:grid-cols-4 xl:grid-cols-1"
                    >
                        <div class="min-w-0 bg-card px-3.5 py-3 sm:px-4">
                            <dt class="text-xs text-muted-foreground">
                                Pemasukan
                            </dt>
                            <dd
                                class="mt-0.5 truncate text-sm font-semibold tabular-nums sm:text-base"
                                :class="
                                    cashFlow.income > 0
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : ''
                                "
                            >
                                <MoneyDisplay :amount="cashFlow.income" />
                            </dd>
                        </div>

                        <div class="min-w-0 bg-card px-3.5 py-3 sm:px-4">
                            <dt class="text-xs text-muted-foreground">
                                Pengeluaran
                            </dt>
                            <dd
                                class="mt-0.5 truncate text-sm font-semibold tabular-nums sm:text-base"
                            >
                                <MoneyDisplay :amount="cashFlow.expense" />
                            </dd>
                        </div>

                        <div class="min-w-0 bg-card px-3.5 py-3 sm:px-4">
                            <dt class="text-xs text-muted-foreground">
                                Bersih
                            </dt>
                            <dd
                                class="mt-0.5 truncate text-sm font-semibold tabular-nums sm:text-base"
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

                        <div class="min-w-0 bg-card px-3.5 py-3 sm:px-4">
                            <dt class="text-xs text-muted-foreground">
                                Rasio Tabungan
                            </dt>
                            <dd
                                class="mt-0.5 truncate text-sm font-semibold tabular-nums sm:text-base"
                            >
                                {{ cashFlow.savings_rate }}%
                            </dd>
                        </div>
                    </dl>

                    <CashFlowBarChart :chart-data="cashFlow.chart" />
                </div>
            </section>

            <div class="lg:col-span-4">
                <BudgetSummaryCard :budget-summary="props.budgetSummary" />
            </div>
        </div>

        <!-- Row 3: Asset Allocation & Recent Transactions -->
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">
            <!-- Asset Allocation -->
            <section
                aria-labelledby="allocation-title"
                class="flex flex-col rounded-xl border bg-card p-5 sm:p-6 lg:col-span-5"
            >
                <div class="mb-5 flex items-center justify-between gap-3">
                    <div>
                        <h2
                            id="allocation-title"
                            class="text-base font-semibold tracking-tight"
                        >
                            Alokasi Aset
                        </h2>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            Distribusi saldo kas &amp; portofolio aset
                        </p>
                    </div>
                    <Link
                        href="/assets"
                        class="-mr-2 inline-flex h-8 shrink-0 items-center gap-1 rounded-md px-2 text-xs font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                    >
                        Portofolio
                        <ArrowRight class="size-3.5" aria-hidden="true" />
                    </Link>
                </div>

                <div class="my-auto">
                    <AssetAllocationDonutChart
                        :allocation="assetAllocation"
                        stacked
                    />
                </div>
            </section>

            <!-- Recent Transactions -->
            <section
                aria-labelledby="recent-title"
                class="flex flex-col rounded-xl border bg-card p-5 sm:p-6 lg:col-span-7"
            >
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div>
                        <h2
                            id="recent-title"
                            class="text-base font-semibold tracking-tight"
                        >
                            Transaksi Terbaru
                        </h2>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            Aktivitas keuangan terbaru dari seluruh rekening
                            Anda
                        </p>
                    </div>
                    <Link
                        href="/transactions"
                        class="-mr-2 inline-flex h-8 shrink-0 items-center gap-1 rounded-md px-2 text-xs font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                    >
                        Lihat semua
                        <ArrowRight class="size-3.5" aria-hidden="true" />
                    </Link>
                </div>

                <ul
                    v-if="displayedTransactions.length > 0"
                    class="divide-y divide-border/70"
                >
                    <li
                        v-for="tx in displayedTransactions"
                        :key="tx.id"
                        class="group relative -mx-2.5 flex items-center justify-between gap-3 rounded-xl px-2.5 py-2.5 transition-colors hover:bg-muted/45"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <div
                                class="flex size-10 shrink-0 items-center justify-center rounded-xl"
                                :style="categoryTint(tx)"
                                aria-hidden="true"
                            >
                                <CategoryIcon
                                    :icon="
                                        tx.category?.icon ||
                                        (tx.type === 'transfer'
                                            ? 'arrow-right-left'
                                            : 'banknote')
                                    "
                                    class-name="size-5"
                                />
                            </div>

                            <div class="min-w-0">
                                <Link
                                    :href="`/transactions/${tx.id}`"
                                    class="block truncate text-sm font-medium text-foreground transition-colors group-hover:text-primary after:absolute after:inset-0 after:rounded-xl focus-visible:outline-none focus-visible:after:ring-2 focus-visible:after:ring-ring/40"
                                >
                                    {{ tx.description }}
                                </Link>
                                <p
                                    class="mt-0.5 truncate text-xs text-muted-foreground"
                                >
                                    {{
                                        tx.category?.name ||
                                        (tx.type === 'transfer'
                                            ? 'Transfer'
                                            : tx.type === 'adjustment'
                                              ? 'Penyesuaian'
                                              : 'Tanpa Kategori')
                                    }}
                                    · {{ getTransactionAccountName(tx) }}
                                </p>
                            </div>
                        </div>

                        <div class="shrink-0 text-right tabular-nums">
                            <p
                                class="text-sm font-semibold whitespace-nowrap"
                                :class="{
                                    'text-emerald-600 dark:text-emerald-400':
                                        tx.type === 'income',
                                    'text-sky-600 dark:text-sky-400':
                                        tx.type === 'transfer',
                                }"
                            >
                                <span v-if="tx.type === 'income'">+</span>
                                <span v-else-if="tx.type === 'expense'">-</span>
                                <MoneyDisplay :amount="tx.amount" />
                            </p>
                            <p class="mt-0.5 text-xs text-muted-foreground">
                                {{ formatDate(tx.date) }}
                            </p>
                        </div>
                    </li>
                </ul>

                <div
                    v-else
                    class="my-auto flex flex-col items-center justify-center py-10 text-center"
                >
                    <div
                        class="mb-3 flex size-11 items-center justify-center rounded-xl border bg-muted/50 text-muted-foreground"
                        aria-hidden="true"
                    >
                        <Calendar class="size-5" />
                    </div>
                    <h3 class="text-sm font-semibold text-foreground">
                        Belum ada transaksi
                    </h3>
                    <p class="mt-1 max-w-xs text-xs text-muted-foreground">
                        Aktivitas terbaru akan muncul di sini setelah Anda
                        mencatat pemasukan atau pengeluaran pertama.
                    </p>
                    <Link
                        href="/transactions/create"
                        class="mt-4 inline-flex h-9 items-center justify-center gap-1.5 rounded-lg bg-primary px-3.5 text-xs font-medium text-primary-foreground transition-colors hover:bg-primary/90"
                    >
                        <Plus class="size-3.5" aria-hidden="true" />
                        Catat Transaksi
                    </Link>
                </div>
            </section>
        </div>
    </div>
</template>
