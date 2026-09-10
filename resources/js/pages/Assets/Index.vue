<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    PlusCircle,
    TrendingUp,
    TrendingDown,
    Building2,
    Coins,
    Gem,
    Home,
    Car,
    Boxes,
    Search,
    ArrowUpRight,
    Wallet,
    CircleDollarSign,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import AssetAllocationDonutChart from '@/components/AssetAllocationDonutChart.vue';
import EmptyState from '@/components/EmptyState.vue';
import MoneyDisplay from '@/components/MoneyDisplay.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Portfolio & Assets',
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
        investments: any[];
        other_assets: any[];
        all_assets: any[];
    };
}>();

const activeTab = ref<'all' | 'investments' | 'other'>('all');
const searchQuery = ref('');

const filteredAssets = computed(() => {
    let list = props.overview.all_assets;

    if (activeTab.value === 'investments') {
        list = props.overview.investments;
    } else if (activeTab.value === 'other') {
        list = props.overview.other_assets;
    }

    if (!searchQuery.value.trim()) {
        return list;
    }

    const q = searchQuery.value.toLowerCase().trim();

    return list.filter((a) => {
        return (
            (a.name && a.name.toLowerCase().includes(q)) ||
            (a.ticker && a.ticker.toLowerCase().includes(q)) ||
            (a.platform && a.platform.toLowerCase().includes(q))
        );
    });
});

const getAssetIcon = (type: string) => {
    switch (type) {
        case 'stock':
            return TrendingUp;
        case 'crypto':
            return Coins;
        case 'mutual_fund':
            return Building2;
        case 'gold':
            return Gem;
        case 'property':
            return Home;
        case 'vehicle':
            return Car;
        default:
            return Boxes;
    }
};

const formatQuantity = (qty: number | string, type: string) => {
    const val = Number(qty);

    if (isNaN(val)) {
        return '0';
    }

    if (type === 'crypto') {
        return val.toLocaleString('id-ID', { maximumFractionDigits: 8 });
    }

    if (type === 'stock') {
        return `${val.toLocaleString('id-ID')} lots`;
    }

    return val.toLocaleString('id-ID', { maximumFractionDigits: 4 });
};

const getAssetTypeBadgeClass = (type: string) => {
    switch (type) {
        case 'stock':
            return 'bg-blue-500/10 text-blue-600 dark:text-blue-400';
        case 'crypto':
            return 'bg-amber-500/10 text-amber-600 dark:text-amber-400';
        case 'mutual_fund':
            return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400';
        case 'gold':
            return 'bg-yellow-500/10 text-yellow-600 dark:text-yellow-400';
        case 'property':
            return 'bg-violet-500/10 text-violet-600 dark:text-violet-400';
        case 'vehicle':
            return 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400';
        default:
            return 'bg-muted text-muted-foreground';
    }
};

const getAssetIconBg = (type: string) => {
    switch (type) {
        case 'stock':
            return 'bg-blue-500/10 text-blue-600 dark:text-blue-400';
        case 'crypto':
            return 'bg-amber-500/10 text-amber-600 dark:text-amber-400';
        case 'mutual_fund':
            return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400';
        case 'gold':
            return 'bg-yellow-500/10 text-yellow-600 dark:text-yellow-400';
        case 'property':
            return 'bg-violet-500/10 text-violet-600 dark:text-violet-400';
        case 'vehicle':
            return 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400';
        default:
            return 'bg-primary/10 text-primary';
    }
};
</script>

<template>
    <Head title="Portfolio & Assets" />

    <div
        class="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8"
    >
        <!-- Header -->
        <div
            class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center"
        >
            <div>
                <h1 class="text-3xl font-bold tracking-tight">
                    Portfolio &amp; Assets
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Track your stock, crypto, mutual funds, gold, and real
                    assets in one unified portfolio.
                </p>
            </div>
            <Link
                href="/assets/create"
                class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow transition-colors hover:bg-primary/90 sm:w-auto"
            >
                <PlusCircle class="h-4 w-4" />
                Add Asset
            </Link>
        </div>

        <!-- Metrics Overview Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Total Portfolio Value Card -->
            <div
                class="relative flex flex-col justify-between overflow-hidden rounded-2xl border bg-card p-5 text-card-foreground shadow-sm"
            >
                <div>
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            Total Portfolio Value
                        </span>
                        <div class="rounded-xl bg-primary/10 p-2 text-primary">
                            <Wallet class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-2 text-2xl font-extrabold tracking-tight">
                        <MoneyDisplay :amount="overview.metrics.total_value" />
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-2 text-xs">
                    <span
                        :class="[
                            'inline-flex items-center gap-1 rounded-md px-2 py-0.5 font-bold',
                            overview.metrics.is_positive_pnl
                                ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                                : 'bg-rose-500/10 text-rose-600 dark:text-rose-400',
                        ]"
                    >
                        <TrendingUp
                            v-if="overview.metrics.is_positive_pnl"
                            class="h-3.5 w-3.5"
                        />
                        <TrendingDown v-else class="h-3.5 w-3.5" />
                        <span>
                            {{ overview.metrics.is_positive_pnl ? '+' : ''
                            }}{{ overview.metrics.total_pnl_percentage }}%
                        </span>
                    </span>
                    <span class="font-medium text-muted-foreground">
                        <MoneyDisplay
                            :amount="overview.metrics.total_unrealized_pnl"
                        />
                    </span>
                </div>
            </div>

            <!-- Investment Holdings Card -->
            <div
                class="relative flex flex-col justify-between overflow-hidden rounded-2xl border bg-card p-5 text-card-foreground shadow-sm"
            >
                <div>
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            Investments Value
                        </span>
                        <div
                            class="rounded-xl bg-blue-500/10 p-2 text-blue-600 dark:text-blue-400"
                        >
                            <TrendingUp class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-2 text-2xl font-extrabold tracking-tight">
                        <MoneyDisplay
                            :amount="overview.metrics.investments_value"
                        />
                    </div>
                </div>
                <div
                    class="mt-4 flex items-center justify-between text-xs text-muted-foreground"
                >
                    <span class="font-medium">
                        {{ overview.metrics.investments_count }} active holdings
                    </span>
                    <span
                        :class="[
                            'rounded-md px-2 py-0.5 font-bold',
                            overview.metrics.investments_pnl >= 0
                                ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                                : 'bg-rose-500/10 text-rose-600 dark:text-rose-400',
                        ]"
                    >
                        {{ overview.metrics.investments_pnl >= 0 ? '+' : ''
                        }}{{ overview.metrics.investments_pnl_percentage }}% P/L
                    </span>
                </div>
            </div>

            <!-- Real & Physical Assets Card -->
            <div
                class="relative flex flex-col justify-between overflow-hidden rounded-2xl border bg-card p-5 text-card-foreground shadow-sm"
            >
                <div>
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            Property &amp; Physical
                        </span>
                        <div
                            class="rounded-xl bg-amber-500/10 p-2 text-amber-600 dark:text-amber-400"
                        >
                            <Home class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-2 text-2xl font-extrabold tracking-tight">
                        <MoneyDisplay
                            :amount="overview.metrics.other_assets_value"
                        />
                    </div>
                </div>
                <div class="mt-4 text-xs font-medium text-muted-foreground">
                    {{ overview.metrics.other_assets_count }} fixed / manual
                    assets
                </div>
            </div>

            <!-- Total Cost Basis Card -->
            <div
                class="relative flex flex-col justify-between overflow-hidden rounded-2xl border bg-card p-5 text-card-foreground shadow-sm"
            >
                <div>
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            Total Invested / Cost
                        </span>
                        <div
                            class="rounded-xl bg-purple-500/10 p-2 text-purple-600 dark:text-purple-400"
                        >
                            <CircleDollarSign class="h-4 w-4" />
                        </div>
                    </div>
                    <div class="mt-2 text-2xl font-extrabold tracking-tight">
                        <MoneyDisplay
                            :amount="overview.metrics.total_cost_basis"
                        />
                    </div>
                </div>
                <div class="mt-4 text-xs font-medium text-muted-foreground">
                    Cumulative capital allocated
                </div>
            </div>
        </div>

        <!-- Allocation Chart & Category Breakdown Row -->
        <div
            v-if="overview.metrics.assets_count > 0"
            class="rounded-2xl border bg-card p-6 shadow-sm"
        >
            <div
                class="flex flex-col gap-2 border-b border-border/60 pb-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h3 class="text-base font-bold tracking-tight">
                        Portfolio Allocation
                    </h3>
                    <p class="text-xs text-muted-foreground">
                        Asset class distribution and weighted breakdown
                    </p>
                </div>
                <div
                    class="flex items-center gap-2 self-start rounded-xl bg-muted/50 px-3 py-1.5 text-xs font-medium sm:self-auto"
                >
                    <span class="text-muted-foreground">Total Portfolio:</span>
                    <span class="font-bold text-foreground">
                        <MoneyDisplay :amount="overview.metrics.total_value" />
                    </span>
                </div>
            </div>

            <div
                class="mt-6 grid grid-cols-1 items-center gap-8 lg:grid-cols-12"
            >
                <!-- Donut Chart Left (5 cols) -->
                <div
                    class="flex flex-col items-center justify-center lg:col-span-5 lg:border-r lg:border-border/60 lg:pr-6"
                >
                    <AssetAllocationDonutChart
                        :allocation="overview.allocation"
                        :show-legend="false"
                    />
                </div>

                <!-- Asset Classes Breakdown Right (7 cols) -->
                <div
                    class="flex flex-col justify-center space-y-3.5 lg:col-span-7"
                >
                    <div
                        v-for="item in overview.allocation.items"
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
                                    {{ item.count === 1 ? 'asset' : 'assets' }}
                                </span>
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
                    All Assets ({{ overview.metrics.assets_count }})
                </button>
                <button
                    type="button"
                    :class="[
                        'rounded-lg px-3 py-1.5 text-xs font-semibold transition-all',
                        activeTab === 'investments'
                            ? 'bg-background text-foreground shadow-sm'
                            : 'text-muted-foreground hover:text-foreground',
                    ]"
                    @click="activeTab = 'investments'"
                >
                    Investments ({{ overview.metrics.investments_count }})
                </button>
                <button
                    type="button"
                    :class="[
                        'rounded-lg px-3 py-1.5 text-xs font-semibold transition-all',
                        activeTab === 'other'
                            ? 'bg-background text-foreground shadow-sm'
                            : 'text-muted-foreground hover:text-foreground',
                    ]"
                    @click="activeTab = 'other'"
                >
                    Real Assets ({{ overview.metrics.other_assets_count }})
                </button>
            </div>

            <div class="relative w-full sm:w-64">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search ticker, name, platform..."
                    class="h-9 w-full rounded-xl border border-input bg-background pr-3 pl-9 text-xs shadow-sm transition-colors focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                />
            </div>
        </div>

        <!-- Asset Grid / List -->
        <div
            v-if="filteredAssets.length > 0"
            class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3"
        >
            <Link
                v-for="asset in filteredAssets"
                :key="asset.id"
                :href="`/assets/${asset.id}`"
                class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border bg-card p-5 text-card-foreground shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/50 hover:shadow-md"
            >
                <div>
                    <!-- Card Top Header -->
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div
                                :class="[
                                    'flex h-11 w-11 shrink-0 items-center justify-center rounded-xl transition-transform group-hover:scale-105',
                                    getAssetIconBg(asset.type),
                                ]"
                            >
                                <component
                                    :is="getAssetIcon(asset.type)"
                                    class="h-5 w-5"
                                />
                            </div>
                            <div>
                                <div class="flex items-center gap-1.5">
                                    <h4
                                        class="text-sm font-bold tracking-tight text-foreground transition-colors group-hover:text-primary"
                                    >
                                        {{ asset.name }}
                                    </h4>
                                    <ArrowUpRight
                                        class="h-3.5 w-3.5 text-muted-foreground opacity-0 transition-opacity group-hover:opacity-100"
                                    />
                                </div>
                                <div
                                    class="flex items-center gap-1.5 text-xs text-muted-foreground"
                                >
                                    <span
                                        v-if="asset.ticker"
                                        class="font-semibold text-foreground"
                                    >
                                        {{ asset.ticker }}
                                    </span>
                                    <span v-if="asset.ticker && asset.platform">
                                        •
                                    </span>
                                    <span v-if="asset.platform">
                                        {{ asset.platform }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Asset Type Pill -->
                        <span
                            :class="[
                                'rounded-full px-2.5 py-0.5 text-[10px] font-bold tracking-wider uppercase',
                                getAssetTypeBadgeClass(asset.type),
                            ]"
                        >
                            {{ asset.type }}
                        </span>
                    </div>

                    <!-- Quantities & Prices (for investments) -->
                    <div
                        v-if="asset.manual_value === null"
                        class="mt-4 grid grid-cols-2 gap-2 rounded-xl bg-muted/40 p-3 text-xs"
                    >
                        <div>
                            <span
                                class="text-[10px] font-medium tracking-wider text-muted-foreground uppercase"
                            >
                                Holdings
                            </span>
                            <p class="mt-0.5 font-bold text-foreground">
                                {{ formatQuantity(asset.quantity, asset.type) }}
                            </p>
                        </div>
                        <div>
                            <span
                                class="text-[10px] font-medium tracking-wider text-muted-foreground uppercase"
                            >
                                Market Price
                            </span>
                            <p class="mt-0.5 font-bold text-foreground">
                                <MoneyDisplay :amount="asset.current_price" />
                                <span
                                    v-if="asset.type === 'stock'"
                                    class="text-[10px] font-normal text-muted-foreground"
                                    >/lbr</span
                                >
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Card Bottom Value & P/L -->
                <div
                    class="mt-5 flex items-end justify-between border-t border-border/60 pt-4"
                >
                    <div>
                        <span
                            class="text-[10px] font-medium tracking-wider text-muted-foreground uppercase"
                        >
                            Current Value
                        </span>
                        <p
                            class="text-lg font-extrabold tracking-tight text-foreground"
                        >
                            <MoneyDisplay :amount="asset.current_value" />
                        </p>
                    </div>

                    <div
                        v-if="asset.manual_value === null"
                        class="flex flex-col items-end gap-1"
                    >
                        <span
                            :class="[
                                'inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-bold tabular-nums',
                                asset.unrealized_pnl >= 0
                                    ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                                    : 'bg-rose-500/10 text-rose-600 dark:text-rose-400',
                            ]"
                        >
                            <TrendingUp
                                v-if="asset.unrealized_pnl >= 0"
                                class="h-3 w-3"
                            />
                            <TrendingDown v-else class="h-3 w-3" />
                            <span>
                                {{ asset.unrealized_pnl >= 0 ? '+' : ''
                                }}{{ asset.unrealized_pnl_percentage }}%
                            </span>
                        </span>
                        <span
                            class="text-xs font-semibold text-muted-foreground tabular-nums"
                        >
                            <MoneyDisplay :amount="asset.unrealized_pnl" />
                        </span>
                    </div>

                    <div
                        v-else
                        class="text-right text-xs text-muted-foreground"
                    >
                        <span
                            class="rounded-md bg-muted px-2 py-1 text-[11px] font-medium"
                        >
                            Fixed valuation
                        </span>
                    </div>
                </div>
            </Link>
        </div>

        <!-- Empty State -->
        <EmptyState
            v-else
            :icon="TrendingUp"
            title="No assets found"
            :description="
                searchQuery
                    ? 'No assets match your search criteria. Try a different search keyword.'
                    : 'Start building your wealth dashboard by registering your first stock, crypto, mutual fund, or physical asset.'
            "
        >
            <template #action>
                <Link
                    href="/assets/create"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow transition-colors hover:bg-primary/90"
                >
                    <PlusCircle class="h-4 w-4" />
                    Add Your First Asset
                </Link>
            </template>
        </EmptyState>
    </div>
</template>
