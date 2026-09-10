<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    TrendingUp,
    Coins,
    Building2,
    Gem,
    Home,
    Car,
    Boxes,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import MoneyInput from '@/components/MoneyInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Portfolio & Assets',
                href: '/assets',
            },
            {
                title: 'Add Asset',
                href: '/assets/create',
            },
        ],
    },
});

const props = defineProps<{
    assetTypes: Array<{
        value: string;
        label: string;
        color: string;
        is_investment: boolean;
        is_valued_manually: boolean;
        precision: number;
    }>;
    accounts: Array<{
        id: string;
        name: string;
        type: string;
        current_balance: number;
    }>;
}>();

const form = useForm({
    type: 'stock',
    name: '',
    platform: '',
    ticker: '',
    currency: 'IDR',
    quantity: undefined as number | undefined,
    avg_purchase_price: null as number | null,
    current_price: null as number | null,
    manual_value: null as number | null,
    notes: '',
});

const selectedType = computed(() => {
    return (
        props.assetTypes.find((t) => t.value === form.type) ||
        props.assetTypes[0]
    );
});

const isManualValue = computed(() => selectedType.value?.is_valued_manually);

const getTypeIcon = (type: string) => {
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

const selectType = (typeValue: string) => {
    form.type = typeValue;

    if (isManualValue.value) {
        form.quantity = undefined;
        form.avg_purchase_price = null;
        form.current_price = null;
    } else {
        form.manual_value = null;
    }
};

const holdingMultiplier = computed(() => {
    return form.type === 'stock' ? 100 : 1;
});

const totalInvestedField = ref<number | null>(null);
let isSyncing = false;

watch(
    [() => form.avg_purchase_price, () => form.quantity, () => form.type],
    ([newAvg, newQty]) => {
        if (isSyncing) {
            return;
        }

        const q = Number(newQty) || 0;
        const avg = Number(newAvg) || 0;
        const mult = form.type === 'stock' ? 100 : 1;

        if (q > 0 && avg > 0) {
            isSyncing = true;
            totalInvestedField.value = Number((q * avg * mult).toFixed(2));
            setTimeout(() => {
                isSyncing = false;
            }, 50);
        } else if (!newAvg) {
            totalInvestedField.value = null;
        }
    },
);

const onTotalInvestedChange = (val: number | null) => {
    if (isSyncing) {
        return;
    }

    totalInvestedField.value = val;
    const q = Number(form.quantity) || 0;
    const mult = form.type === 'stock' ? 100 : 1;
    const totalUnits = q * mult;

    if (val && totalUnits > 0) {
        isSyncing = true;
        // e.g. 12616896.9 / 3000 = 4205.6323
        form.avg_purchase_price = Number((val / totalUnits).toFixed(4));
        setTimeout(() => {
            isSyncing = false;
        }, 50);
    }
};

const calculatedTotalShares = computed(() => {
    const q = Number(form.quantity) || 0;

    return q * holdingMultiplier.value;
});

const calculatedCostBasis = computed(() => {
    if (
        totalInvestedField.value !== null &&
        totalInvestedField.value !== undefined &&
        totalInvestedField.value > 0
    ) {
        return totalInvestedField.value;
    }

    const q = Number(form.quantity) || 0;
    const avg = Number(form.avg_purchase_price) || 0;

    return Math.round(q * avg * holdingMultiplier.value);
});

const calculatedMarketValue = computed(() => {
    const q = Number(form.quantity) || 0;
    const cur =
        Number(form.current_price) || Number(form.avg_purchase_price) || 0;

    return Math.round(q * cur * holdingMultiplier.value);
});

const calculatedPnl = computed(() => {
    return Math.round(calculatedMarketValue.value - calculatedCostBasis.value);
});

const calculatedPnlPercentage = computed(() => {
    if (calculatedCostBasis.value <= 0) {
        return 0;
    }

    return Number(
        ((calculatedPnl.value / calculatedCostBasis.value) * 100).toFixed(2),
    );
});

const submit = () => {
    form.post('/assets');
};
</script>

<template>
    <Head title="Add Asset - Finova" />

    <div class="mx-auto max-w-3xl p-4 md:p-6 lg:p-8">
        <!-- Back Link & Header -->
        <div class="mb-6">
            <Link
                href="/assets"
                class="inline-flex items-center gap-1.5 text-xs font-semibold text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft class="h-3.5 w-3.5" />
                Back to Portfolio
            </Link>
            <h1 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">
                Add New Asset
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Register an investment or physical asset to include in your net
                worth.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Asset Class Selector -->
            <div class="rounded-2xl border bg-card p-5 shadow-sm">
                <Label
                    class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                >
                    1. Select Asset Class
                </Label>
                <div class="mt-3 grid grid-cols-2 gap-2.5 sm:grid-cols-4">
                    <button
                        v-for="item in assetTypes"
                        :key="item.value"
                        type="button"
                        :class="[
                            'flex flex-col items-center gap-2 rounded-xl border p-3.5 text-center transition-all',
                            form.type === item.value
                                ? 'border-primary bg-primary/10 text-primary shadow-sm'
                                : 'border-border/60 bg-muted/30 text-muted-foreground hover:border-primary/40 hover:text-foreground',
                        ]"
                        @click="selectType(item.value)"
                    >
                        <component
                            :is="getTypeIcon(item.value)"
                            class="h-5 w-5"
                        />
                        <span class="text-xs font-semibold">{{
                            item.label
                        }}</span>
                    </button>
                </div>
                <InputError :message="form.errors.type" class="mt-2" />
            </div>

            <!-- Asset Details -->
            <div class="space-y-4 rounded-2xl border bg-card p-5 shadow-sm">
                <Label
                    class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                >
                    2. Asset Identity &amp; Details
                </Label>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <!-- Name -->
                    <div class="space-y-1.5">
                        <Label for="name" class="text-xs font-medium"
                            >Asset Name *</Label
                        >
                        <Input
                            id="name"
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. Bank Mandiri, Bitcoin, Citra Garden House"
                            required
                            class="rounded-xl"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <!-- Platform / Broker / Custodian -->
                    <div class="space-y-1.5">
                        <Label for="platform" class="text-xs font-medium">
                            Platform / Broker / Custodian
                        </Label>
                        <Input
                            id="platform"
                            v-model="form.platform"
                            type="text"
                            placeholder="e.g. Stockbit, Bibit, Indodax, Physical Safe"
                            class="rounded-xl"
                        />
                        <InputError :message="form.errors.platform" />
                    </div>

                    <!-- Ticker (for stocks / crypto) -->
                    <div v-if="!isManualValue" class="space-y-1.5">
                        <Label for="ticker" class="text-xs font-medium">
                            Ticker Symbol
                        </Label>
                        <Input
                            id="ticker"
                            v-model="form.ticker"
                            type="text"
                            placeholder="e.g. BMRI, BTC, BBCA"
                            class="rounded-xl font-mono uppercase"
                        />
                        <InputError :message="form.errors.ticker" />
                    </div>

                    <!-- Currency -->
                    <div class="space-y-1.5">
                        <Label for="currency" class="text-xs font-medium"
                            >Currency</Label
                        >
                        <Input
                            id="currency"
                            v-model="form.currency"
                            type="text"
                            maxlength="3"
                            class="rounded-xl font-mono uppercase"
                        />
                        <InputError :message="form.errors.currency" />
                    </div>
                </div>

                <!-- Manual Value (for Real Assets) -->
                <div v-if="isManualValue" class="space-y-1.5 pt-2">
                    <Label for="manual_value" class="text-xs font-medium">
                        Current Estimated Value (IDR) *
                    </Label>
                    <MoneyInput
                        id="manual_value"
                        v-model="form.manual_value"
                        placeholder="0"
                        class="rounded-xl"
                    />
                    <InputError :message="form.errors.manual_value" />
                </div>

                <!-- Initial Holdings (for Investments) -->
                <div
                    v-else
                    class="grid grid-cols-1 gap-4 pt-2 sm:grid-cols-2 lg:grid-cols-4"
                >
                    <div class="space-y-1.5">
                        <Label for="quantity" class="text-xs font-medium">
                            {{
                                form.type === 'stock'
                                    ? 'Quantity (Lots) *'
                                    : 'Quantity Held *'
                            }}
                        </Label>
                        <Input
                            id="quantity"
                            v-model.number="form.quantity"
                            type="number"
                            step="any"
                            min="0"
                            placeholder="0"
                            class="rounded-xl"
                        />
                        <p class="text-[10px] text-muted-foreground">
                            {{
                                form.type === 'stock'
                                    ? '1 lot = 100 lembar'
                                    : 'Total units'
                            }}
                        </p>
                        <InputError :message="form.errors.quantity" />
                    </div>

                    <div class="space-y-1.5">
                        <Label
                            for="avg_purchase_price"
                            class="text-xs font-medium"
                        >
                            {{
                                form.type === 'stock'
                                    ? 'Avg Buy Price (Per Lembar)'
                                    : 'Avg Buy Price (Per Unit)'
                            }}
                        </Label>
                        <MoneyInput
                            id="avg_purchase_price"
                            v-model="form.avg_purchase_price"
                            :allow-decimal="true"
                            :max-decimals="4"
                            placeholder="0"
                            class="rounded-xl"
                        />
                        <p class="text-[10px] text-muted-foreground">
                            Harga modal per lembar
                        </p>
                        <InputError :message="form.errors.avg_purchase_price" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="total_invested" class="text-xs font-medium">
                            Total Invested (Modal Beli)
                        </Label>
                        <MoneyInput
                            id="total_invested"
                            :model-value="totalInvestedField"
                            :allow-decimal="true"
                            :max-decimals="2"
                            @update:model-value="onTotalInvestedChange"
                            placeholder="0"
                            class="rounded-xl"
                        />
                        <p class="text-[10px] text-muted-foreground">
                            Bisa paste langsung dari Stockbit
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="current_price" class="text-xs font-medium">
                            {{
                                form.type === 'stock'
                                    ? 'Current Market Price (Per Lembar)'
                                    : 'Current Market Price'
                            }}
                        </Label>
                        <MoneyInput
                            id="current_price"
                            v-model="form.current_price"
                            :allow-decimal="true"
                            :max-decimals="4"
                            placeholder="0"
                            class="rounded-xl"
                        />
                        <p class="text-[10px] text-muted-foreground">
                            Harga pasar bursa terkini
                        </p>
                        <InputError :message="form.errors.current_price" />
                    </div>
                </div>

                <!-- Live Portfolio Preview for Investments -->
                <div
                    v-if="!isManualValue && form.quantity"
                    class="rounded-2xl border border-border/80 bg-muted/40 p-4 text-xs shadow-xs"
                >
                    <div
                        class="mb-3 flex items-center justify-between border-b pb-2 text-xs font-medium text-muted-foreground"
                    >
                        <span class="flex items-center gap-1.5">
                            <span
                                class="h-2 w-2 rounded-full bg-primary"
                            ></span>
                            Calculated Portfolio Preview (Sesuai Stockbit)
                        </span>
                        <span
                            v-if="form.type === 'stock'"
                            class="rounded-md bg-primary/10 px-2 py-0.5 font-semibold text-primary"
                        >
                            {{ form.quantity }} lot =
                            {{
                                new Intl.NumberFormat('id-ID').format(
                                    calculatedTotalShares,
                                )
                            }}
                            lembar
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div class="space-y-0.5">
                            <div class="text-[11px] text-muted-foreground">
                                Invested (Modal Beli)
                            </div>
                            <div class="text-sm font-semibold text-foreground">
                                Rp
                                {{
                                    new Intl.NumberFormat('id-ID', {
                                        minimumFractionDigits: 0,
                                        maximumFractionDigits: 1,
                                    }).format(calculatedCostBasis)
                                }}
                            </div>
                        </div>
                        <div class="space-y-0.5">
                            <div class="text-[11px] text-muted-foreground">
                                Market Value
                            </div>
                            <div class="text-sm font-semibold text-foreground">
                                Rp
                                {{
                                    new Intl.NumberFormat('id-ID').format(
                                        calculatedMarketValue,
                                    )
                                }}
                            </div>
                        </div>
                        <div class="space-y-0.5">
                            <div class="text-[11px] text-muted-foreground">
                                Potential P&amp;L
                            </div>
                            <div
                                :class="[
                                    'text-sm font-semibold',
                                    calculatedPnl >= 0
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : 'text-rose-600 dark:text-rose-400',
                                ]"
                            >
                                {{ calculatedPnl < 0 ? '-Rp ' : '+Rp ' }}
                                {{
                                    new Intl.NumberFormat('id-ID').format(
                                        Math.abs(calculatedPnl),
                                    )
                                }}
                            </div>
                        </div>
                        <div class="space-y-0.5">
                            <div class="text-[11px] text-muted-foreground">
                                Percentage
                            </div>
                            <div
                                :class="[
                                    'text-sm font-semibold',
                                    calculatedPnl >= 0
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : 'text-rose-600 dark:text-rose-400',
                                ]"
                            >
                                {{ calculatedPnl >= 0 ? '+' : ''
                                }}{{ calculatedPnlPercentage }}%
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="space-y-1.5 pt-2">
                    <Label for="notes" class="text-xs font-medium"
                        >Notes (Optional)</Label
                    >
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="3"
                        placeholder="Any additional details or strategy notes..."
                        class="w-full rounded-xl border border-input bg-background p-3 text-sm shadow-sm transition-colors focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                    />
                    <InputError :message="form.errors.notes" />
                </div>
            </div>

            <!-- Submit Action Bar -->
            <div class="flex items-center justify-end gap-3">
                <Link
                    href="/assets"
                    class="rounded-xl border px-4 py-2 text-sm font-semibold text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                >
                    Cancel
                </Link>
                <Button
                    type="submit"
                    class="rounded-xl bg-primary px-6 font-semibold text-primary-foreground shadow"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Saving Asset...' : 'Save Asset' }}
                </Button>
            </div>
        </form>
    </div>
</template>
