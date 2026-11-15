<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ChevronRight,
    PieChart,
    Plus,
    Search,
    TrendingDown,
    TrendingUp,
    X,
} from '@lucide/vue';
import { computed, nextTick, ref } from 'vue';
import AssetAllocationDonutChart from '@/components/AssetAllocationDonutChart.vue';
import MoneyDisplay from '@/components/MoneyDisplay.vue';
import {
    formatAssetDate,
    formatAssetQuantity,
    getAssetTintStyle,
    getAssetTypeMeta,
    translateAssetAllocationLabel,
} from '@/lib/assets';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Aset & Investasi',
                href: '/assets',
            },
        ],
    },
});

const props = defineProps<{
    overview: {
        metrics: {
            total_value: number;
            total_cost_basis: number;
            total_unrealized_pnl: number;
            total_pnl_percentage: number;
            is_positive_pnl: boolean;
            investments_value: number;
            investments_cost_basis: number;
            investments_pnl: number;
            investments_pnl_percentage: number;
            other_assets_value: number;
            assets_count: number;
            investments_count: number;
            other_assets_count: number;
        };
        allocation: {
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
        investments: Array<any>;
        other_assets: Array<any>;
        all_assets: Array<any>;
    };
}>();

type TabKey = 'all' | 'investments' | 'other';

const activeTab = ref<TabKey>('all');
const searchQuery = ref('');
const tabRefs = ref<Partial<Record<TabKey, HTMLButtonElement>>>({});

const tabs = computed(() => [
    {
        id: 'all' as const,
        label: 'Semua Aset',
        count: props.overview.all_assets.length,
    },
    {
        id: 'investments' as const,
        label: 'Investasi',
        count: props.overview.investments.length,
    },
    {
        id: 'other' as const,
        label: 'Aset Riil & Logam Mulia',
        count: props.overview.other_assets.length,
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

const filteredAssets = computed(() => {
    const base =
        activeTab.value === 'all'
            ? props.overview.all_assets
            : activeTab.value === 'investments'
              ? props.overview.investments
              : props.overview.other_assets;

    const query = searchQuery.value.trim().toLowerCase();

    if (!query) {
        return base;
    }

    return base.filter((asset) => {
        const typeLabel = getAssetTypeMeta(asset.type).label.toLowerCase();

        return (
            asset.name?.toLowerCase().includes(query) ||
            asset.ticker?.toLowerCase().includes(query) ||
            asset.platform?.toLowerCase().includes(query) ||
            typeLabel.includes(query)
        );
    });
});

const allocationBars = computed(() => {
    const items = props.overview.allocation?.items ?? [];
    const total = props.overview.metrics.total_value;

    if (total <= 0 || items.length === 0) {
        return [];
    }

    return items.map((item) => ({
        ...item,
        localizedLabel: translateAssetAllocationLabel(item.label),
    }));
});

const formatPnlPercent = (pct: number) => {
    const abs = Math.abs(pct).toLocaleString('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    });

    if (pct > 0) {
        return `+${abs}%`;
    }

    if (pct < 0) {
        return `-${abs}%`;
    }

    return '0%';
};

const pnlBadgeClass = (pnl: number) => {
    if (pnl > 0) {
        return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400';
    }

    if (pnl < 0) {
        return 'bg-rose-500/10 text-rose-700 dark:text-rose-400';
    }

    return 'bg-muted text-muted-foreground';
};

const isManualAsset = (asset: any) => {
    return (
        asset.manual_value !== null &&
        asset.manual_value !== undefined &&
        getAssetTypeMeta(asset.type).isManualDefault
    );
};

const resetFilters = () => {
    activeTab.value = 'all';
    searchQuery.value = '';
};
</script>

<template>
    <Head title="Aset & Investasi" />

    <div
        class="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-5 p-4 md:p-6 lg:gap-6 lg:p-8"
    >
        <!-- Header -->
        <div
            class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center"
        >
            <div class="min-w-0">
                <h1 class="text-2xl font-semibold tracking-tight">
                    Aset &amp; Investasi
                </h1>
                <p class="mt-0.5 text-sm text-muted-foreground">
                    Pantau nilai portofolio, keuntungan belum terealisasi, dan
                    sebaran aset Anda.
                </p>
            </div>

            <Link
                href="/assets/create"
                class="inline-flex h-11 w-full shrink-0 items-center justify-center gap-1.5 rounded-lg bg-primary px-4 text-sm font-medium whitespace-nowrap text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:ring-offset-2 focus-visible:outline-none active:scale-[0.98] sm:h-10 sm:w-auto"
            >
                <Plus class="size-4" aria-hidden="true" />
                Tambah Aset
            </Link>
        </div>

        <!-- Ringkasan Portofolio & Alokasi -->
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-12 lg:gap-5">
            <!-- Portfolio Value Card -->
            <section
                aria-label="Ringkasan nilai portofolio"
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
                                Total Nilai Portofolio
                            </h2>
                            <span
                                class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium text-muted-foreground tabular-nums"
                            >
                                {{ overview.metrics.assets_count }} aset aktif
                            </span>
                        </div>

                        <p
                            class="mt-3 text-3xl font-semibold tracking-tight tabular-nums sm:text-4xl lg:text-5xl"
                        >
                            <MoneyDisplay
                                :amount="overview.metrics.total_value"
                            />
                        </p>

                        <!-- Unrealized P/L Indicator -->
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-medium tabular-nums"
                                :class="
                                    pnlBadgeClass(
                                        overview.metrics.total_unrealized_pnl,
                                    )
                                "
                            >
                                <component
                                    :is="
                                        overview.metrics.total_unrealized_pnl >=
                                        0
                                            ? TrendingUp
                                            : TrendingDown
                                    "
                                    class="size-3.5 shrink-0"
                                    aria-hidden="true"
                                />
                                <MoneyDisplay
                                    :amount="
                                        overview.metrics.total_unrealized_pnl
                                    "
                                    show-sign
                                />
                                <span>
                                    ({{
                                        formatPnlPercent(
                                            overview.metrics
                                                .total_pnl_percentage,
                                        )
                                    }})
                                </span>
                            </span>
                            <span class="text-xs text-muted-foreground">
                                Keuntungan / kerugian belum terealisasi
                            </span>
                        </div>
                    </div>

                    <!-- Proportion Bar -->
                    <div>
                        <div
                            class="flex h-2 w-full gap-0.5 overflow-hidden rounded-full bg-muted"
                            role="img"
                            :aria-label="
                                allocationBars.length > 0
                                    ? `Komposisi aset: ${allocationBars.map((b) => `${b.localizedLabel} ${b.percentage}%`).join(', ')}`
                                    : 'Belum ada komposisi aset'
                            "
                        >
                            <div
                                v-for="bar in allocationBars"
                                :key="bar.type"
                                class="h-full transition-all duration-300 first:rounded-l-full last:rounded-r-full"
                                :style="{
                                    width: `${Math.max(bar.percentage, 2)}%`,
                                    backgroundColor: bar.color,
                                }"
                            ></div>
                        </div>

                        <div
                            v-if="allocationBars.length > 0"
                            class="mt-2.5 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-muted-foreground"
                        >
                            <span
                                v-for="bar in allocationBars.slice(0, 4)"
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
                    class="grid grid-cols-1 gap-px border-t bg-border @md:grid-cols-3"
                >
                    <div class="bg-card px-5 py-3.5">
                        <p class="text-xs font-medium text-muted-foreground">
                            Aset Investasi
                        </p>
                        <p
                            class="mt-1 text-base font-semibold tracking-tight tabular-nums"
                        >
                            <MoneyDisplay
                                :amount="overview.metrics.investments_value"
                            />
                        </p>
                        <p
                            class="mt-0.5 text-xs text-muted-foreground tabular-nums"
                        >
                            {{ overview.metrics.investments_count }} produk
                            <template
                                v-if="overview.metrics.investments_count > 0"
                            >
                                ·
                                <span
                                    :class="
                                        overview.metrics.investments_pnl >= 0
                                            ? 'text-emerald-600 dark:text-emerald-400'
                                            : 'text-rose-600 dark:text-rose-400'
                                    "
                                >
                                    {{
                                        formatPnlPercent(
                                            overview.metrics
                                                .investments_pnl_percentage,
                                        )
                                    }}
                                </span>
                            </template>
                        </p>
                    </div>

                    <div class="bg-card px-5 py-3.5">
                        <p class="text-xs font-medium text-muted-foreground">
                            Aset Riil &amp; Emas
                        </p>
                        <p
                            class="mt-1 text-base font-semibold tracking-tight tabular-nums"
                        >
                            <MoneyDisplay
                                :amount="overview.metrics.other_assets_value"
                            />
                        </p>
                        <p
                            class="mt-0.5 text-xs text-muted-foreground tabular-nums"
                        >
                            {{ overview.metrics.other_assets_count }} aset fisik
                        </p>
                    </div>

                    <div class="bg-card px-5 py-3.5">
                        <p class="text-xs font-medium text-muted-foreground">
                            Modal Diinvestasikan
                        </p>
                        <p
                            class="mt-1 text-base font-semibold tracking-tight tabular-nums"
                        >
                            <MoneyDisplay
                                :amount="overview.metrics.total_cost_basis"
                            />
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            Total harga perolehan
                        </p>
                    </div>
                </div>
            </section>

            <!-- Allocation Breakdown Card -->
            <section
                aria-label="Alokasi portofolio"
                class="flex flex-col justify-between rounded-xl border bg-card p-5 sm:p-6 lg:col-span-5"
            >
                <div class="mb-4 flex items-center justify-between gap-2">
                    <div>
                        <h2 class="text-base font-semibold tracking-tight">
                            Alokasi Portofolio
                        </h2>
                        <p class="text-xs text-muted-foreground">
                            Proporsi nilai per kategori aset
                        </p>
                    </div>
                </div>

                <AssetAllocationDonutChart
                    v-if="overview.allocation.items.length > 0"
                    :allocation="overview.allocation"
                    show-legend
                    stacked
                />

                <div
                    v-else
                    class="flex flex-1 flex-col items-center justify-center rounded-lg border border-dashed py-8 text-center"
                >
                    <PieChart
                        class="mb-2.5 size-7 text-muted-foreground/60"
                        aria-hidden="true"
                    />
                    <p class="text-sm font-medium text-foreground">
                        Belum ada alokasi aset
                    </p>
                    <p class="mt-1 max-w-xs text-xs text-muted-foreground">
                        Tambahkan saham, reksa dana, emas, atau properti untuk
                        melihat distribusi portofolio.
                    </p>
                </div>
            </section>
        </div>

        <!-- Daftar Aset -->
        <section aria-labelledby="daftar-aset-heading" class="space-y-4">
            <div
                class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
            >
                <div>
                    <h2
                        id="daftar-aset-heading"
                        class="text-lg font-semibold tracking-tight"
                    >
                        Daftar Kepemilikan Aset
                    </h2>
                </div>

                <!-- Filter & Search Controls -->
                <search
                    aria-label="Filter kategori dan pencarian aset"
                    class="flex flex-col gap-2.5 sm:flex-row sm:items-center"
                >
                    <!-- Single-row Accessible Tablist -->
                    <div
                        role="tablist"
                        aria-label="Filter jenis aset"
                        class="flex flex-nowrap items-center gap-1 overflow-x-auto border-b border-border sm:rounded-lg sm:border sm:border-b-0 sm:bg-muted/40 sm:p-1"
                    >
                        <button
                            v-for="(tab, index) in tabs"
                            :id="`asset-tab-${tab.id}`"
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
                            :aria-controls="'asset-tabpanel'"
                            :tabindex="activeTab === tab.id ? 0 : -1"
                            @click="selectTab(tab.id)"
                            @keydown="handleTabKeydown($event, index)"
                            class="relative inline-flex h-11 shrink-0 items-center gap-1.5 px-3 text-sm font-medium whitespace-nowrap transition-colors focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none sm:h-8 sm:rounded-md"
                            :class="
                                activeTab === tab.id
                                    ? 'text-foreground after:absolute after:inset-x-2 after:bottom-0 after:h-0.5 after:rounded-full after:bg-primary sm:bg-background sm:shadow-xs sm:after:hidden'
                                    : 'text-muted-foreground hover:text-foreground'
                            "
                        >
                            <span>{{ tab.label }}</span>
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

                    <!-- Search Field (16px minimum on mobile to prevent iOS zoom) -->
                    <div class="relative w-full sm:w-64">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <input
                            v-model="searchQuery"
                            type="search"
                            aria-label="Cari aset berdasarkan nama, kode ticker, atau platform"
                            placeholder="Cari nama, ticker, platform..."
                            autocomplete="off"
                            class="h-11 w-full rounded-lg border border-input bg-card pr-9 pl-9 text-base text-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none sm:h-10 sm:text-sm"
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
                </search>
            </div>

            <!-- Tab Panel Content -->
            <div
                id="asset-tabpanel"
                role="tabpanel"
                :aria-labelledby="`asset-tab-${activeTab}`"
            >
                <!-- Filtered Assets Grid -->
                <div
                    v-if="filteredAssets.length > 0"
                    class="grid grid-cols-1 gap-3.5 md:grid-cols-2 xl:grid-cols-3"
                >
                    <Link
                        v-for="asset in filteredAssets"
                        :key="asset.id"
                        :href="`/assets/${asset.id}`"
                        :aria-label="`${asset.name} — ${getAssetTypeMeta(asset.type).label}`"
                        class="group @container flex min-w-0 flex-col justify-between rounded-xl border bg-card p-5 transition-[border-color,box-shadow] hover:border-primary/40 hover:shadow-xs focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none active:scale-[0.99]"
                    >
                        <div>
                            <!-- Top Row: Icon + Identity + Chevron -->
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <div
                                        class="flex size-10 shrink-0 items-center justify-center rounded-xl"
                                        :style="
                                            getAssetTintStyle(
                                                getAssetTypeMeta(asset.type)
                                                    .tint,
                                            )
                                        "
                                    >
                                        <component
                                            :is="
                                                getAssetTypeMeta(asset.type)
                                                    .icon
                                            "
                                            class="size-5"
                                            aria-hidden="true"
                                        />
                                    </div>

                                    <div class="min-w-0">
                                        <div
                                            class="flex flex-wrap items-center gap-1.5"
                                        >
                                            <h3
                                                class="truncate text-sm font-semibold text-foreground sm:text-base"
                                            >
                                                {{ asset.name }}
                                            </h3>
                                            <span
                                                v-if="asset.ticker"
                                                class="inline-flex shrink-0 items-center rounded border px-1.5 py-0.5 font-mono text-[11px] font-medium text-muted-foreground uppercase"
                                            >
                                                {{ asset.ticker }}
                                            </span>
                                            <span
                                                v-if="!asset.is_active"
                                                class="inline-flex shrink-0 items-center rounded bg-muted px-1.5 py-0.5 text-[11px] font-medium text-muted-foreground"
                                            >
                                                Nonaktif
                                            </span>
                                        </div>

                                        <p
                                            class="mt-0.5 truncate text-xs text-muted-foreground"
                                        >
                                            {{
                                                getAssetTypeMeta(asset.type)
                                                    .label
                                            }}
                                            <span v-if="asset.platform">
                                                · {{ asset.platform }}
                                            </span>
                                        </p>
                                    </div>
                                </div>

                                <ChevronRight
                                    class="mt-1 size-4 shrink-0 text-muted-foreground/60 transition-transform group-hover:translate-x-0.5 group-hover:text-foreground"
                                    aria-hidden="true"
                                />
                            </div>

                            <!-- Value & P/L -->
                            <div class="mt-4">
                                <p class="text-xs text-muted-foreground">
                                    {{
                                        isManualAsset(asset)
                                            ? 'Nilai Taksiran'
                                            : 'Nilai Pasar Saat Ini'
                                    }}
                                </p>
                                <div
                                    class="mt-1 flex flex-wrap items-baseline justify-between gap-2"
                                >
                                    <p
                                        class="text-xl font-semibold tracking-tight tabular-nums sm:text-2xl"
                                    >
                                        <MoneyDisplay
                                            :amount="asset.current_value"
                                            :currency="asset.currency"
                                        />
                                    </p>

                                    <span
                                        v-if="
                                            !isManualAsset(asset) ||
                                            asset.unrealized_pnl !== 0
                                        "
                                        class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-medium tabular-nums"
                                        :class="
                                            pnlBadgeClass(asset.unrealized_pnl)
                                        "
                                    >
                                        <MoneyDisplay
                                            :amount="asset.unrealized_pnl"
                                            :currency="asset.currency"
                                            show-sign
                                        />
                                        <span>
                                            ({{
                                                formatPnlPercent(
                                                    asset.unrealized_pnl_percentage,
                                                )
                                            }})
                                        </span>
                                    </span>
                                    <span
                                        v-else
                                        class="text-xs text-muted-foreground"
                                    >
                                        Penilaian manual
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Bottom Hairline Metadata -->
                        <div
                            class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t pt-3 text-xs text-muted-foreground tabular-nums"
                        >
                            <template v-if="!isManualAsset(asset)">
                                <span>
                                    {{
                                        formatAssetQuantity(
                                            asset.quantity,
                                            asset.type,
                                        )
                                    }}
                                </span>
                                <span>
                                    Harga:
                                    <span class="font-medium text-foreground">
                                        <MoneyDisplay
                                            :amount="asset.current_price"
                                            :currency="asset.currency"
                                        />
                                    </span>
                                    <span class="hidden @xs:inline">
                                        /
                                        {{
                                            getAssetTypeMeta(
                                                asset.type,
                                            ).priceUnitHint.replace(
                                                /^per\s+/i,
                                                '',
                                            )
                                        }}
                                    </span>
                                </span>
                            </template>

                            <template v-else>
                                <span>
                                    Modal:
                                    <span class="font-medium text-foreground">
                                        <MoneyDisplay
                                            :amount="asset.cost_basis"
                                            :currency="asset.currency"
                                        />
                                    </span>
                                </span>
                                <span>
                                    {{
                                        formatAssetDate(asset.last_price_update)
                                    }}
                                </span>
                            </template>
                        </div>
                    </Link>
                </div>

                <!-- Empty State: Zero Assets Overall -->
                <div
                    v-else-if="overview.all_assets.length === 0"
                    class="flex flex-col items-center justify-center rounded-xl border border-dashed bg-card px-6 py-14 text-center"
                >
                    <div
                        class="mb-4 flex size-12 items-center justify-center rounded-xl bg-primary/10 text-primary"
                    >
                        <TrendingUp class="size-6" aria-hidden="true" />
                    </div>
                    <h3 class="text-base font-semibold text-foreground">
                        Portofolio aset masih kosong
                    </h3>
                    <p class="mt-1.5 max-w-md text-sm text-muted-foreground">
                        Mulai catat saham, reksa dana, kripto, emas, atau aset
                        fisik untuk melacak kekayaan bersih Anda secara
                        menyeluruh.
                    </p>
                    <Link
                        href="/assets/create"
                        class="mt-5 inline-flex h-11 items-center justify-center gap-1.5 rounded-lg bg-primary px-5 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none sm:h-10"
                    >
                        <Plus class="size-4" aria-hidden="true" />
                        Tambah Aset Pertama
                    </Link>
                </div>

                <!-- Empty State: No Filter / Search Match -->
                <div
                    v-else
                    class="flex flex-col items-center justify-center rounded-xl border border-dashed bg-card px-6 py-12 text-center"
                >
                    <Search
                        class="mb-3 size-8 text-muted-foreground/60"
                        aria-hidden="true"
                    />
                    <h3 class="text-base font-semibold text-foreground">
                        Tidak ada aset yang cocok
                    </h3>
                    <p class="mt-1 max-w-sm text-sm text-muted-foreground">
                        Coba ubah kata kunci pencarian atau pilih kategori aset
                        lainnya.
                    </p>
                    <button
                        type="button"
                        @click="resetFilters"
                        class="mt-4 inline-flex h-10 items-center justify-center rounded-lg border border-input bg-background px-4 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                    >
                        Tampilkan Semua Aset
                    </button>
                </div>
            </div>
        </section>
    </div>
</template>
