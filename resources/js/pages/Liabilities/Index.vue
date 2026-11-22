<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ChevronRight,
    Clock,
    Plus,
    Receipt,
    Search as SearchIcon,
    ShieldCheck,
    X,
} from '@lucide/vue';
import { computed, nextTick, ref } from 'vue';
import MoneyDisplay from '@/components/MoneyDisplay.vue';
import {
    getCreditUtilization,
    getLiabilityTintStyle,
    getLiabilityTypeMeta,
    getPayoffProgress,
    getUtilizationStatus,
    translateLiabilityLabel,
} from '@/lib/liabilities';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Utang',
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

type TabKey = 'all' | 'credit_card' | 'non_card';

const activeTab = ref<TabKey>('all');
const searchQuery = ref('');
const tabRefs = ref<Partial<Record<TabKey, HTMLButtonElement>>>({});

const nonCardCount = computed(
    () =>
        props.overview.liabilities.filter((l) => l.type !== 'credit_card')
            .length,
);

const tabs = computed(() => [
    {
        id: 'all' as const,
        label: 'Semua',
        shortLabel: 'Semua',
        count: props.overview.liabilities.length,
    },
    {
        id: 'credit_card' as const,
        label: 'Kartu Kredit',
        shortLabel: 'Kartu Kredit',
        count: props.overview.metrics.credit_cards_count,
    },
    {
        id: 'non_card' as const,
        label: 'Pinjaman & Cicilan',
        shortLabel: 'Pinjaman',
        count: nonCardCount.value,
    },
]);

const selectTab = (key: TabKey, focusTab = false) => {
    activeTab.value = key;

    if (!focusTab) {
        return;
    }

    nextTick(() => {
        const el = tabRefs.value[key];

        if (el) {
            el.focus();
            el.scrollIntoView({ block: 'nearest', inline: 'nearest' });
        }
    });
};

const handleTabKeydown = (event: KeyboardEvent, index: number) => {
    const list = tabs.value;
    let nextIndex: number | null = null;

    if (event.key === 'ArrowRight') {
        nextIndex = (index + 1) % list.length;
    } else if (event.key === 'ArrowLeft') {
        nextIndex = (index - 1 + list.length) % list.length;
    } else if (event.key === 'Home') {
        nextIndex = 0;
    } else if (event.key === 'End') {
        nextIndex = list.length - 1;
    }

    if (nextIndex !== null) {
        event.preventDefault();
        selectTab(list[nextIndex].id, true);
    }
};

const filteredLiabilities = computed(() => {
    let list = props.overview.liabilities;

    if (activeTab.value === 'credit_card') {
        list = list.filter((l) => l.type === 'credit_card');
    } else if (activeTab.value === 'non_card') {
        list = list.filter((l) => l.type !== 'credit_card');
    }

    const q = searchQuery.value.trim().toLowerCase();

    if (!q) {
        return list;
    }

    return list.filter((l) => {
        const typeLabel = getLiabilityTypeMeta(l.type).label.toLowerCase();

        return (
            l.name?.toLowerCase().includes(q) ||
            typeLabel.includes(q) ||
            l.notes?.toLowerCase().includes(q)
        );
    });
});

const breakdownBars = computed(() => {
    const items = props.overview.breakdown?.items ?? [];

    if (props.overview.metrics.total_debt <= 0 || items.length === 0) {
        return [];
    }

    return items.map((item) => ({
        ...item,
        localizedLabel: translateLiabilityLabel(item.label),
    }));
});

const overallPaidPct = computed(() =>
    getPayoffProgress(
        props.overview.metrics.total_original,
        props.overview.metrics.total_debt,
    ),
);

const overallUtilization = computed(() =>
    getUtilizationStatus(props.overview.metrics.credit_utilization),
);

const resetFilters = () => {
    activeTab.value = 'all';
    searchQuery.value = '';
};
</script>

<template>
    <Head title="Utang & Kewajiban" />

    <div
        class="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-5 p-4 md:p-6 lg:gap-6 lg:p-8"
    >
        <!-- Header -->
        <div
            class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center"
        >
            <div class="min-w-0">
                <h1 class="text-2xl font-semibold tracking-tight text-balance">
                    Utang &amp; Kewajiban
                </h1>
                <p class="mt-0.5 text-sm text-pretty text-muted-foreground">
                    Pantau sisa tagihan, pemakaian limit kartu kredit, dan
                    jadwal pelunasan Anda.
                </p>
            </div>

            <Link
                href="/liabilities/create"
                class="inline-flex min-h-11 w-full shrink-0 items-center justify-center gap-1.5 rounded-lg bg-primary px-4 text-sm font-medium whitespace-nowrap text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:ring-offset-2 focus-visible:outline-none active:scale-[0.98] sm:min-h-10 sm:w-auto"
            >
                <Plus class="size-4" aria-hidden="true" />
                Tambah Utang
            </Link>
        </div>

        <!-- Ringkasan & Sebaran Utang -->
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-12 lg:gap-5">
            <!-- Total Debt Hero Card -->
            <section
                aria-label="Ringkasan total utang"
                class="@container flex flex-col justify-between overflow-hidden rounded-xl border bg-card lg:col-span-7"
            >
                <div
                    class="flex flex-1 flex-col justify-between gap-6 p-5 sm:p-6"
                >
                    <div>
                        <div
                            class="flex flex-wrap items-center justify-between gap-2"
                        >
                            <h2
                                class="text-xs font-medium tracking-wider text-muted-foreground uppercase"
                            >
                                Total Sisa Utang
                            </h2>
                            <span
                                v-if="overview.metrics.total_debt === 0"
                                class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:text-emerald-400"
                            >
                                <ShieldCheck
                                    class="size-3.5"
                                    aria-hidden="true"
                                />
                                Bebas Utang
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium text-muted-foreground tabular-nums"
                            >
                                {{ overview.metrics.liabilities_count }}
                                kewajiban aktif
                            </span>
                        </div>

                        <p
                            class="mt-3 text-3xl font-semibold tracking-tight tabular-nums sm:text-4xl lg:text-5xl"
                            :class="
                                overview.metrics.total_debt > 0
                                    ? 'text-rose-600 dark:text-rose-400'
                                    : 'text-foreground'
                            "
                        >
                            <MoneyDisplay
                                :amount="overview.metrics.total_debt"
                            />
                        </p>

                        <!-- Payoff Progress Summary -->
                        <div
                            v-if="overview.metrics.total_original > 0"
                            class="mt-4 space-y-1.5"
                        >
                            <div
                                class="flex flex-wrap items-center justify-between gap-2 text-xs"
                            >
                                <span class="text-muted-foreground">
                                    Progres pelunasan dari pokok awal
                                </span>
                                <span
                                    class="font-medium text-emerald-700 tabular-nums dark:text-emerald-400"
                                >
                                    {{ overallPaidPct }}% terlunasi
                                </span>
                            </div>
                            <progress
                                class="finova-progress block h-2 w-full"
                                max="100"
                                :value="overallPaidPct"
                                :style="{ '--progress-fill': '#10b981' }"
                                :aria-label="`Progres pelunasan total utang ${overallPaidPct} persen`"
                            />
                        </div>
                    </div>

                    <!-- Category Proportion Bar -->
                    <div v-if="breakdownBars.length > 0">
                        <div
                            class="flex h-2 w-full gap-0.5 overflow-hidden rounded-full bg-muted"
                            role="img"
                            :aria-label="`Komposisi utang: ${breakdownBars.map((b) => `${b.localizedLabel} ${b.percentage}%`).join(', ')}`"
                        >
                            <div
                                v-for="bar in breakdownBars"
                                :key="bar.type"
                                class="h-full transition-all duration-300 first:rounded-l-full last:rounded-r-full"
                                :style="{
                                    width: `${Math.max(bar.percentage, 2)}%`,
                                    backgroundColor: bar.color,
                                }"
                            ></div>
                        </div>

                        <div
                            class="mt-2.5 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-muted-foreground"
                        >
                            <span
                                v-for="bar in breakdownBars"
                                :key="`${bar.type}-pill`"
                                class="inline-flex items-center gap-1.5 tabular-nums"
                            >
                                <span
                                    class="size-2 shrink-0 rounded-full"
                                    :style="{ backgroundColor: bar.color }"
                                    aria-hidden="true"
                                ></span>
                                <span class="text-foreground">{{
                                    bar.localizedLabel
                                }}</span>
                                <span>{{ bar.percentage }}%</span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 3-Column Hairline Metrics Strip -->
                <div
                    class="grid grid-cols-2 gap-px border-t bg-border @md:grid-cols-3"
                >
                    <div class="bg-card px-4 py-3.5 sm:px-5">
                        <p class="text-xs font-medium text-muted-foreground">
                            Cicilan Bulanan
                        </p>
                        <p
                            class="mt-1 text-base font-semibold tracking-tight tabular-nums"
                        >
                            <MoneyDisplay
                                :amount="
                                    overview.metrics.total_monthly_obligations
                                "
                            />
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            Estimasi min. / bulan
                        </p>
                    </div>

                    <div class="bg-card px-4 py-3.5 sm:px-5">
                        <p class="text-xs font-medium text-muted-foreground">
                            Pemakaian Kartu
                        </p>
                        <div class="mt-1 flex flex-wrap items-baseline gap-1.5">
                            <p
                                class="text-base font-semibold tracking-tight tabular-nums"
                            >
                                {{ overview.metrics.credit_utilization }}%
                            </p>
                            <span
                                v-if="overview.metrics.total_credit_limit > 0"
                                class="rounded px-1.5 py-0.5 text-[11px] font-medium"
                                :class="overallUtilization.badgeClass"
                            >
                                {{ overallUtilization.label }}
                            </span>
                        </div>
                        <p
                            class="mt-0.5 text-xs text-muted-foreground tabular-nums"
                        >
                            <template
                                v-if="overview.metrics.total_credit_limit > 0"
                            >
                                Limit:
                                <MoneyDisplay
                                    :amount="
                                        overview.metrics.total_credit_limit
                                    "
                                />
                            </template>
                            <template v-else>Belum ada kartu</template>
                        </p>
                    </div>

                    <div
                        class="col-span-2 flex items-center justify-between gap-3 bg-card px-4 py-3.5 sm:px-5 @md:col-span-1 @md:block"
                    >
                        <div>
                            <p
                                class="text-xs font-medium text-muted-foreground"
                            >
                                Total Pokok Awal
                            </p>
                            <p
                                class="mt-0.5 text-xs text-muted-foreground @md:hidden"
                            >
                                Nilai awal tercatat
                            </p>
                        </div>
                        <div class="text-right @md:text-left">
                            <p
                                class="text-base font-semibold tracking-tight tabular-nums @md:mt-1"
                            >
                                <MoneyDisplay
                                    :amount="overview.metrics.total_original"
                                />
                            </p>
                            <p
                                class="mt-0.5 hidden text-xs text-muted-foreground @md:block"
                            >
                                Nilai awal tercatat
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Sebaran Kategori Utang Card -->
            <section
                aria-label="Sebaran kategori utang"
                class="flex flex-col justify-between rounded-xl border bg-card p-5 sm:p-6 lg:col-span-5"
            >
                <div class="mb-4">
                    <h2
                        class="text-base font-semibold tracking-tight text-balance"
                    >
                        Sebaran Kategori Utang
                    </h2>
                    <p class="text-xs text-pretty text-muted-foreground">
                        Proporsi saldo aktif berdasarkan jenis kewajiban
                    </p>
                </div>

                <div
                    v-if="breakdownBars.length > 0"
                    class="flex flex-1 flex-col justify-between gap-5"
                >
                    <div class="space-y-4">
                        <div
                            v-for="item in breakdownBars"
                            :key="item.type"
                            class="space-y-1.5"
                        >
                            <div
                                class="flex items-center justify-between gap-3 text-sm"
                            >
                                <div class="flex min-w-0 items-center gap-2">
                                    <span
                                        class="size-2.5 shrink-0 rounded-full"
                                        :style="{ backgroundColor: item.color }"
                                        aria-hidden="true"
                                    ></span>
                                    <span
                                        class="truncate text-xs font-medium text-foreground sm:text-sm"
                                    >
                                        {{ item.localizedLabel }}
                                    </span>
                                    <span
                                        class="shrink-0 text-xs text-muted-foreground tabular-nums"
                                    >
                                        ({{ item.count }})
                                    </span>
                                </div>
                                <div
                                    class="flex shrink-0 items-center gap-2 text-right tabular-nums"
                                >
                                    <span
                                        class="text-xs font-semibold text-foreground sm:text-sm"
                                    >
                                        <MoneyDisplay :amount="item.value" />
                                    </span>
                                    <span
                                        class="w-11 text-xs font-medium text-muted-foreground"
                                    >
                                        {{ item.percentage }}%
                                    </span>
                                </div>
                            </div>
                            <progress
                                class="finova-progress block h-1.5 w-full"
                                max="100"
                                :value="item.percentage"
                                :style="{ '--progress-fill': item.color }"
                                :aria-label="`Porsi ${item.localizedLabel} sebesar ${item.percentage} persen`"
                            />
                        </div>
                    </div>

                    <!-- Credit Utilization Summary Footer -->
                    <div
                        v-if="overview.metrics.total_credit_limit > 0"
                        class="border-t pt-4"
                    >
                        <div
                            class="flex items-center justify-between gap-2 text-xs"
                        >
                            <span class="text-muted-foreground">
                                Limit Kartu Kredit Terpakai
                            </span>
                            <span
                                class="font-medium text-foreground tabular-nums"
                            >
                                <MoneyDisplay
                                    :amount="overview.metrics.total_credit_used"
                                />
                                /
                                <MoneyDisplay
                                    :amount="
                                        overview.metrics.total_credit_limit
                                    "
                                />
                            </span>
                        </div>
                        <progress
                            class="finova-progress mt-2 block h-1.5 w-full"
                            max="100"
                            :value="
                                Math.min(
                                    100,
                                    overview.metrics.credit_utilization,
                                )
                            "
                            :style="{
                                '--progress-fill': overallUtilization.color,
                            }"
                            :aria-label="`Pemakaian limit kartu kredit ${overview.metrics.credit_utilization} persen`"
                        />
                    </div>
                </div>

                <div
                    v-else
                    class="flex flex-1 flex-col items-center justify-center rounded-lg border border-dashed px-4 py-7 text-center"
                >
                    <Receipt
                        class="mb-2.5 size-7 text-muted-foreground/60"
                        aria-hidden="true"
                    />
                    <p class="text-sm font-medium text-foreground">
                        Tidak ada sebaran utang aktif
                    </p>
                    <p
                        class="mt-1 max-w-xs text-xs text-pretty text-muted-foreground"
                    >
                        Catat kartu kredit, KTA, atau cicilan untuk memantau
                        distribusi kewajiban Anda di sini.
                    </p>
                </div>
            </section>
        </div>

        <!-- Daftar Kewajiban -->
        <section aria-labelledby="daftar-utang-heading" class="space-y-4">
            <div
                class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
            >
                <div class="flex items-center justify-between gap-2">
                    <h2
                        id="daftar-utang-heading"
                        class="text-lg font-semibold tracking-tight text-balance"
                    >
                        Daftar Kewajiban
                    </h2>
                    <span
                        class="inline-flex items-center rounded-full border bg-card px-2.5 py-0.5 text-xs font-medium text-muted-foreground tabular-nums lg:hidden"
                    >
                        {{ filteredLiabilities.length }} akun
                    </span>
                </div>

                <!-- Filter & Search Controls -->
                <div
                    role="search"
                    aria-label="Filter kategori dan pencarian utang"
                    class="flex flex-col gap-2.5 sm:flex-row sm:items-center"
                >
                    <!-- Single-row Accessible Tablist -->
                    <div
                        role="tablist"
                        aria-label="Filter jenis utang"
                        class="flex w-full flex-nowrap items-center overflow-x-auto border-b border-border sm:w-auto sm:gap-1 sm:rounded-lg sm:border sm:bg-muted/40 sm:p-1"
                    >
                        <button
                            v-for="(tab, index) in tabs"
                            :id="`liability-tab-${tab.id}`"
                            :key="tab.id"
                            :ref="
                                (el) => {
                                    if (el)
                                        tabRefs[tab.id] =
                                            el as HTMLButtonElement;
                                }
                            "
                            type="button"
                            role="tab"
                            :aria-selected="activeTab === tab.id"
                            aria-controls="liability-tabpanel"
                            :tabindex="activeTab === tab.id ? 0 : -1"
                            @click="selectTab(tab.id)"
                            @keydown="handleTabKeydown($event, index)"
                            class="relative inline-flex min-h-11 flex-1 shrink-0 items-center justify-center gap-1.5 px-2.5 text-sm font-medium whitespace-nowrap transition-colors focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none sm:min-h-8 sm:flex-none sm:rounded-md sm:px-3"
                            :class="
                                activeTab === tab.id
                                    ? 'text-foreground after:absolute after:inset-x-2 after:bottom-0 after:h-0.5 after:rounded-full after:bg-primary sm:bg-background sm:shadow-xs sm:after:hidden'
                                    : 'text-muted-foreground hover:text-foreground'
                            "
                        >
                            <span class="sm:hidden">{{ tab.shortLabel }}</span>
                            <span class="hidden sm:inline">{{
                                tab.label
                            }}</span>
                            <span
                                class="rounded-full px-1.5 py-0.5 text-[11px] tabular-nums"
                                :class="
                                    activeTab === tab.id
                                        ? 'bg-primary/10 text-primary'
                                        : 'bg-muted text-muted-foreground'
                                "
                            >
                                {{ tab.count }}
                            </span>
                        </button>
                    </div>

                    <!-- Search Field -->
                    <div class="relative w-full sm:w-64">
                        <SearchIcon
                            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <input
                            v-model="searchQuery"
                            type="search"
                            aria-label="Cari utang berdasarkan nama, jenis, atau catatan"
                            placeholder="Cari nama utang atau catatan..."
                            autocomplete="off"
                            class="min-h-11 w-full rounded-lg border border-input bg-card pr-9 pl-9 text-base text-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none sm:min-h-10 sm:text-sm"
                        />
                        <button
                            v-if="searchQuery"
                            type="button"
                            aria-label="Hapus kata kunci pencarian"
                            @click="searchQuery = ''"
                            class="absolute top-1/2 right-1.5 inline-flex size-8 -translate-y-1/2 items-center justify-center rounded-md text-muted-foreground hover:bg-accent hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                        >
                            <X class="size-4" aria-hidden="true" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tab Panel -->
            <div
                id="liability-tabpanel"
                role="tabpanel"
                :aria-labelledby="`liability-tab-${activeTab}`"
            >
                <!-- Cards Grid -->
                <div
                    v-if="filteredLiabilities.length > 0"
                    class="grid grid-cols-1 gap-3.5 md:grid-cols-2 xl:grid-cols-3"
                >
                    <Link
                        v-for="liability in filteredLiabilities"
                        :key="liability.id"
                        :href="`/liabilities/${liability.id}`"
                        :aria-label="`${liability.name} — ${getLiabilityTypeMeta(liability.type).label}`"
                        class="group block rounded-xl border bg-card transition-[border-color,box-shadow] hover:border-primary/40 hover:shadow-xs focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none active:scale-[0.99]"
                    >
                        <article
                            class="@container flex h-full min-w-0 flex-col justify-between p-5"
                        >
                            <div>
                                <!-- Header -->
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div class="flex min-w-0 items-start gap-3">
                                        <div
                                            class="flex size-10 shrink-0 items-center justify-center rounded-xl"
                                            :style="
                                                getLiabilityTintStyle(
                                                    getLiabilityTypeMeta(
                                                        liability.type,
                                                    ).tint,
                                                )
                                            "
                                        >
                                            <component
                                                :is="
                                                    getLiabilityTypeMeta(
                                                        liability.type,
                                                    ).icon
                                                "
                                                class="size-5"
                                                aria-hidden="true"
                                            />
                                        </div>

                                        <div class="min-w-0">
                                            <h3
                                                class="text-sm font-semibold text-balance break-words text-foreground sm:text-base"
                                            >
                                                {{ liability.name }}
                                            </h3>
                                            <p
                                                class="mt-0.5 flex flex-wrap items-center gap-x-1.5 gap-y-0.5 text-xs text-muted-foreground"
                                            >
                                                <span>
                                                    {{
                                                        getLiabilityTypeMeta(
                                                            liability.type,
                                                        ).shortLabel
                                                    }}
                                                </span>
                                                <template
                                                    v-if="
                                                        liability.due_date_day
                                                    "
                                                >
                                                    <span aria-hidden="true"
                                                        >·</span
                                                    >
                                                    <span
                                                        class="inline-flex items-center gap-1 tabular-nums"
                                                    >
                                                        <Clock
                                                            class="size-3"
                                                            aria-hidden="true"
                                                        />
                                                        Tgl
                                                        {{
                                                            liability.due_date_day
                                                        }}
                                                    </span>
                                                </template>
                                                <template
                                                    v-if="
                                                        liability.remaining_terms
                                                    "
                                                >
                                                    <span aria-hidden="true"
                                                        >·</span
                                                    >
                                                    <span class="tabular-nums">
                                                        Sisa
                                                        {{
                                                            liability.remaining_terms
                                                        }}x
                                                    </span>
                                                </template>
                                            </p>
                                        </div>
                                    </div>

                                    <ChevronRight
                                        class="mt-1 size-4 shrink-0 text-muted-foreground/60 transition-transform group-hover:translate-x-0.5 group-hover:text-foreground"
                                        aria-hidden="true"
                                    />
                                </div>

                                <!-- Current Balance -->
                                <div class="mt-4">
                                    <p class="text-xs text-muted-foreground">
                                        Sisa Tagihan
                                    </p>
                                    <div
                                        class="mt-1 flex flex-wrap items-baseline justify-between gap-2"
                                    >
                                        <p
                                            class="text-xl font-semibold tracking-tight tabular-nums sm:text-2xl"
                                            :class="
                                                liability.current_balance > 0
                                                    ? 'text-rose-600 dark:text-rose-400'
                                                    : 'text-emerald-600 dark:text-emerald-400'
                                            "
                                        >
                                            <MoneyDisplay
                                                :amount="
                                                    liability.current_balance
                                                "
                                                :currency="liability.currency"
                                            />
                                        </p>

                                        <span
                                            v-if="
                                                liability.current_balance === 0
                                            "
                                            class="inline-flex items-center rounded-md bg-emerald-500/10 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:text-emerald-400"
                                        >
                                            Lunas
                                        </span>
                                        <span
                                            v-else-if="
                                                liability.type ===
                                                    'credit_card' &&
                                                liability.credit_limit
                                            "
                                            class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium tabular-nums"
                                            :class="
                                                getUtilizationStatus(
                                                    getCreditUtilization(
                                                        liability.current_balance,
                                                        liability.credit_limit,
                                                    ),
                                                ).badgeClass
                                            "
                                        >
                                            {{
                                                getCreditUtilization(
                                                    liability.current_balance,
                                                    liability.credit_limit,
                                                )
                                            }}% limit
                                        </span>
                                        <span
                                            v-else-if="
                                                liability.initial_balance > 0
                                            "
                                            class="text-xs font-medium text-muted-foreground tabular-nums"
                                        >
                                            {{
                                                getPayoffProgress(
                                                    liability.initial_balance,
                                                    liability.current_balance,
                                                )
                                            }}% lunas
                                        </span>
                                    </div>
                                </div>

                                <!-- Progress Bar -->
                                <div
                                    v-if="
                                        liability.type === 'credit_card' &&
                                        liability.credit_limit
                                    "
                                    class="mt-3"
                                >
                                    <progress
                                        class="finova-progress block h-1.5 w-full"
                                        max="100"
                                        :value="
                                            getCreditUtilization(
                                                liability.current_balance,
                                                liability.credit_limit,
                                            )
                                        "
                                        :style="{
                                            '--progress-fill':
                                                getUtilizationStatus(
                                                    getCreditUtilization(
                                                        liability.current_balance,
                                                        liability.credit_limit,
                                                    ),
                                                ).color,
                                        }"
                                        :aria-label="`Pemakaian limit ${liability.name} ${getCreditUtilization(liability.current_balance, liability.credit_limit)} persen`"
                                    />
                                </div>
                                <div
                                    v-else-if="liability.initial_balance > 0"
                                    class="mt-3"
                                >
                                    <progress
                                        class="finova-progress block h-1.5 w-full"
                                        max="100"
                                        :value="
                                            getPayoffProgress(
                                                liability.initial_balance,
                                                liability.current_balance,
                                            )
                                        "
                                        :style="{
                                            '--progress-fill': '#10b981',
                                        }"
                                        :aria-label="`Progres pelunasan ${liability.name} ${getPayoffProgress(liability.initial_balance, liability.current_balance)} persen`"
                                    />
                                </div>
                            </div>

                            <!-- Footer Hairline Metadata -->
                            <div
                                class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t pt-3 text-xs text-muted-foreground tabular-nums"
                            >
                                <span>
                                    Min. bayar:
                                    <span class="font-medium text-foreground">
                                        <MoneyDisplay
                                            v-if="liability.minimum_payment"
                                            :amount="liability.minimum_payment"
                                            :currency="liability.currency"
                                        />
                                        <template v-else>—</template>
                                    </span>
                                </span>

                                <span
                                    v-if="
                                        liability.type === 'credit_card' &&
                                        liability.credit_limit
                                    "
                                >
                                    Limit:
                                    <span class="font-medium text-foreground">
                                        <MoneyDisplay
                                            :amount="liability.credit_limit"
                                            :currency="liability.currency"
                                        />
                                    </span>
                                </span>
                                <span v-else-if="liability.interest_rate">
                                    Bunga:
                                    <span class="font-medium text-foreground">
                                        {{ liability.interest_rate }}%/thn
                                    </span>
                                </span>
                                <span v-else>
                                    Pokok:
                                    <span class="font-medium text-foreground">
                                        <MoneyDisplay
                                            :amount="liability.initial_balance"
                                            :currency="liability.currency"
                                        />
                                    </span>
                                </span>
                            </div>
                        </article>
                    </Link>
                </div>

                <!-- Empty State: Zero Liabilities Overall -->
                <div
                    v-else-if="overview.liabilities.length === 0"
                    class="flex flex-col items-center justify-center rounded-xl border border-dashed bg-card px-6 py-12 text-center"
                >
                    <div
                        class="mb-4 flex size-12 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"
                    >
                        <ShieldCheck class="size-6" aria-hidden="true" />
                    </div>
                    <h3
                        class="text-base font-semibold text-balance text-foreground"
                    >
                        Belum ada catatan utang
                    </h3>
                    <p
                        class="mt-1.5 max-w-md text-sm text-pretty text-muted-foreground"
                    >
                        Tambahkan kartu kredit, pinjaman pribadi (KTA), atau
                        cicilan agar kekayaan bersih dan jadwal pembayaran Anda
                        terpantau rapi.
                    </p>
                    <Link
                        href="/liabilities/create"
                        class="mt-5 inline-flex min-h-11 items-center justify-center gap-1.5 rounded-lg bg-primary px-5 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none sm:min-h-10"
                    >
                        <Plus class="size-4" aria-hidden="true" />
                        Tambah Utang Pertama
                    </Link>
                </div>

                <!-- Empty State: No Filter / Search Match -->
                <div
                    v-else
                    class="flex flex-col items-center justify-center rounded-xl border border-dashed bg-card px-6 py-12 text-center"
                >
                    <SearchIcon
                        class="mb-3 size-8 text-muted-foreground/60"
                        aria-hidden="true"
                    />
                    <h3
                        class="text-base font-semibold text-balance text-foreground"
                    >
                        Tidak ada kewajiban yang cocok
                    </h3>
                    <p
                        class="mt-1 max-w-sm text-sm text-pretty text-muted-foreground"
                    >
                        Coba ubah kata kunci pencarian atau pilih kategori utang
                        lainnya.
                    </p>
                    <button
                        type="button"
                        @click="resetFilters"
                        class="mt-4 inline-flex min-h-10 items-center justify-center rounded-lg border border-input bg-background px-4 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                    >
                        Tampilkan Semua
                    </button>
                </div>
            </div>
        </section>
    </div>
</template>
