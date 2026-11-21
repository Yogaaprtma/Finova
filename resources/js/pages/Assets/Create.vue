<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Check, Loader2, Plus } from '@lucide/vue';
import { computed, ref } from 'vue';
import MoneyDisplay from '@/components/MoneyDisplay.vue';
import MoneyInput from '@/components/MoneyInput.vue';
import {
    ASSET_CURRENCIES,
    formatStockShares,
    getAssetTintStyle,
    getAssetTypeMeta,
} from '@/lib/assets';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Aset & Investasi', href: '/assets' },
            { title: 'Tambah Aset', href: '/assets/create' },
        ],
    },
});

interface AssetTypeOption {
    value: string;
    label: string;
    color: string;
    is_investment: boolean;
    is_valued_manually: boolean;
    precision: number;
}

const props = defineProps<{
    assetTypes: AssetTypeOption[];
    accounts?: Array<{
        id: string;
        name: string;
        type: string;
        current_balance: number;
    }>;
}>();

const fallbackTypes: AssetTypeOption[] = [
    {
        value: 'stock',
        label: 'Stocks',
        color: '#3B82F6',
        is_investment: true,
        is_valued_manually: false,
        precision: 0,
    },
    {
        value: 'mutual_fund',
        label: 'Mutual Funds',
        color: '#8B5CF6',
        is_investment: true,
        is_valued_manually: false,
        precision: 4,
    },
    {
        value: 'crypto',
        label: 'Cryptocurrency',
        color: '#F59E0B',
        is_investment: true,
        is_valued_manually: false,
        precision: 8,
    },
    {
        value: 'other_investment',
        label: 'Other Investment',
        color: '#06B6D4',
        is_investment: true,
        is_valued_manually: false,
        precision: 4,
    },
    {
        value: 'gold',
        label: 'Gold & Precious Metals',
        color: '#EAB308',
        is_investment: false,
        is_valued_manually: false,
        precision: 4,
    },
    {
        value: 'property',
        label: 'Property / Real Estate',
        color: '#EC4899',
        is_investment: false,
        is_valued_manually: true,
        precision: 0,
    },
    {
        value: 'vehicle',
        label: 'Vehicles',
        color: '#EC4899',
        is_investment: false,
        is_valued_manually: true,
        precision: 0,
    },
    {
        value: 'other_asset',
        label: 'Other Asset',
        color: '#64748B',
        is_investment: false,
        is_valued_manually: true,
        precision: 0,
    },
];

const typeOptions = computed(() => {
    const source = props.assetTypes?.length ? props.assetTypes : fallbackTypes;

    return source.map((item) => ({
        ...item,
        meta: getAssetTypeMeta(item.value),
    }));
});

const initialType = computed(() => typeOptions.value[0]?.value || 'stock');

const form = useForm({
    type: initialType.value,
    name: '',
    platform: '',
    ticker: '',
    currency: 'IDR',
    quantity: '' as number | string,
    avg_purchase_price: 0,
    current_price: 0,
    manual_value: 0,
    notes: '',
});

const totalInvestedField = ref(0);

const selectedOption = computed(
    () =>
        typeOptions.value.find((t) => t.value === form.type) ??
        typeOptions.value[0],
);

const selectedMeta = computed(() => getAssetTypeMeta(form.type));

const isManualAsset = computed(
    () =>
        selectedOption.value?.is_valued_manually ??
        selectedMeta.value.isManualDefault,
);

const isStock = computed(() => form.type === 'stock');

const stockMultiplier = computed(() => (isStock.value ? 100 : 1));

const numericQuantity = computed(() => {
    const parsed = Number(form.quantity);

    return Number.isFinite(parsed) && parsed > 0 ? parsed : 0;
});

const quantityStep = computed(() => {
    if (isStock.value) {
        return '1';
    }

    const precision =
        selectedOption.value?.precision ?? selectedMeta.value.precision;

    return precision > 0
        ? (1 / Math.pow(10, Math.min(precision, 8))).toString()
        : 'any';
});

const selectType = (typeValue: string) => {
    form.type = typeValue;
    const option = typeOptions.value.find((t) => t.value === typeValue);
    const manual =
        option?.is_valued_manually ??
        getAssetTypeMeta(typeValue).isManualDefault;

    if (manual) {
        form.quantity = 1;
        form.current_price = 0;
        totalInvestedField.value = form.avg_purchase_price;
    } else {
        if (form.quantity === 1 && form.manual_value > 0) {
            form.quantity = '';
        }

        form.manual_value = 0;
        totalInvestedField.value = Math.round(
            numericQuantity.value *
                (typeValue === 'stock' ? 100 : 1) *
                form.avg_purchase_price,
        );
    }
};

const handleQuantityChange = () => {
    totalInvestedField.value = Math.round(
        numericQuantity.value * stockMultiplier.value * form.avg_purchase_price,
    );
};

const handleAvgPriceChange = (val: number | null) => {
    const safeVal = val ?? 0;

    form.avg_purchase_price = safeVal;
    totalInvestedField.value = Math.round(
        numericQuantity.value * stockMultiplier.value * safeVal,
    );
};

const handleTotalInvestedChange = (val: number | null) => {
    const safeVal = val ?? 0;

    totalInvestedField.value = safeVal;
    const units = numericQuantity.value * stockMultiplier.value;

    if (units > 0) {
        form.avg_purchase_price = Math.round(safeVal / units);
    }
};

const copyBuyPriceToCurrent = () => {
    if (form.avg_purchase_price > 0) {
        form.current_price = form.avg_purchase_price;
    }
};

const previewMarketValue = computed(() => {
    if (isManualAsset.value) {
        return form.manual_value || 0;
    }

    return Math.round(
        numericQuantity.value *
            stockMultiplier.value *
            (form.current_price || 0),
    );
});

const previewCostBasis = computed(() => {
    if (isManualAsset.value) {
        return form.avg_purchase_price || 0;
    }

    return Math.round(
        numericQuantity.value *
            stockMultiplier.value *
            (form.avg_purchase_price || 0),
    );
});

const previewPnl = computed(
    () => previewMarketValue.value - previewCostBasis.value,
);

const submit = () => {
    form.transform((data) => ({
        ...data,
        ticker: data.ticker ? data.ticker.trim().toUpperCase() : '',
        quantity: isManualAsset.value ? 1 : Number(data.quantity || 0),
        avg_purchase_price: data.avg_purchase_price || 0,
        current_price: isManualAsset.value ? 0 : data.current_price || 0,
        manual_value: isManualAsset.value ? data.manual_value || 0 : 0,
    })).post('/assets');
};
</script>

<template>
    <Head title="Tambah Aset Baru" />

    <div
        class="mx-auto flex h-full w-full max-w-3xl flex-1 flex-col gap-4 p-4 md:p-6 lg:gap-5 lg:p-8"
    >
        <!-- Header -->
        <div class="flex items-center gap-3">
            <Link
                href="/assets"
                aria-label="Kembali ke daftar aset"
                class="inline-flex size-11 shrink-0 items-center justify-center rounded-lg border bg-card text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none sm:size-9"
            >
                <ArrowLeft class="size-4" aria-hidden="true" />
            </Link>
            <div class="min-w-0">
                <h1 class="truncate text-2xl font-semibold tracking-tight">
                    Tambah Aset Baru
                </h1>
                <p class="mt-0.5 truncate text-sm text-muted-foreground">
                    Catat instrumen investasi atau aset riil untuk memantau
                    perkembangan portofolio Anda.
                </p>
            </div>
        </div>

        <!-- Form Card -->
        <form
            @submit.prevent="submit"
            class="divide-y rounded-xl border bg-card"
        >
            <!-- Section 1: Jenis & Identitas Aset -->
            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <h2 class="text-sm font-semibold text-foreground">
                        Jenis &amp; Identitas Aset
                    </h2>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        Pilih kategori aset yang paling sesuai agar satuan dan
                        perhitungan nilai akurat.
                    </p>
                </div>

                <!-- Asset Type Grid -->
                <fieldset>
                    <legend class="mb-2 text-sm font-medium text-foreground">
                        Kategori Aset
                    </legend>
                    <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-4">
                        <button
                            v-for="opt in typeOptions"
                            :key="opt.value"
                            type="button"
                            :aria-pressed="form.type === opt.value"
                            @click="selectType(opt.value)"
                            class="relative flex min-h-[78px] flex-col items-start justify-between rounded-xl border p-3 text-left transition-colors focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                            :class="
                                form.type === opt.value
                                    ? 'border-primary bg-primary/[0.04] ring-1 ring-primary'
                                    : 'border-border bg-background hover:bg-accent/50'
                            "
                        >
                            <div
                                class="flex w-full items-center justify-between gap-2"
                            >
                                <span
                                    class="flex size-8 items-center justify-center rounded-lg"
                                    :style="getAssetTintStyle(opt.meta.tint)"
                                >
                                    <component
                                        :is="opt.meta.icon"
                                        class="size-4"
                                        aria-hidden="true"
                                    />
                                </span>
                                <span
                                    v-if="form.type === opt.value"
                                    class="flex size-4 items-center justify-center rounded-full bg-primary text-primary-foreground"
                                >
                                    <Check
                                        class="size-2.5"
                                        aria-hidden="true"
                                    />
                                </span>
                            </div>
                            <span
                                class="mt-2.5 text-xs font-semibold text-foreground sm:text-sm"
                            >
                                {{ opt.meta.label }}
                            </span>
                        </button>
                    </div>
                    <p
                        id="asset-type-hint"
                        class="mt-2 text-xs text-muted-foreground"
                    >
                        {{ selectedMeta.description }}
                    </p>
                    <p
                        v-if="form.errors.type"
                        id="asset-type-error"
                        class="mt-1.5 text-xs text-destructive"
                    >
                        {{ form.errors.type }}
                    </p>
                </fieldset>

                <!-- Nama & Mata Uang -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="sm:col-span-2">
                        <label
                            for="asset-name"
                            class="mb-1.5 block text-sm font-medium text-foreground"
                        >
                            Nama Aset
                            <span class="text-destructive">*</span>
                        </label>
                        <input
                            id="asset-name"
                            v-model="form.name"
                            type="text"
                            required
                            maxlength="100"
                            placeholder="cth. Bank Central Asia, Bitcoin, Emas Antam"
                            :aria-invalid="Boolean(form.errors.name)"
                            :aria-describedby="
                                form.errors.name
                                    ? 'asset-name-error'
                                    : undefined
                            "
                            class="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-base text-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none sm:h-10 sm:text-sm"
                        />
                        <p
                            v-if="form.errors.name"
                            id="asset-name-error"
                            class="mt-1.5 text-xs text-destructive"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="asset-currency"
                            class="mb-1.5 block text-sm font-medium text-foreground"
                        >
                            Mata Uang
                        </label>
                        <select
                            id="asset-currency"
                            v-model="form.currency"
                            :aria-invalid="Boolean(form.errors.currency)"
                            class="h-11 w-full rounded-lg border border-input bg-background px-3 text-base text-foreground focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none sm:h-10 sm:text-sm"
                        >
                            <option
                                v-for="curr in ASSET_CURRENCIES"
                                :key="curr"
                                :value="curr"
                            >
                                {{ curr }}
                            </option>
                        </select>
                        <p
                            v-if="form.errors.currency"
                            class="mt-1.5 text-xs text-destructive"
                        >
                            {{ form.errors.currency }}
                        </p>
                    </div>
                </div>

                <!-- Platform & Ticker -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label
                            for="asset-platform"
                            class="mb-1.5 flex items-center justify-between text-sm font-medium text-foreground"
                        >
                            <span>
                                {{
                                    isManualAsset
                                        ? 'Lokasi / Penyimpanan'
                                        : 'Platform / Sekuritas'
                                }}
                            </span>
                            <span
                                class="text-xs font-normal text-muted-foreground"
                            >
                                Opsional
                            </span>
                        </label>
                        <input
                            id="asset-platform"
                            v-model="form.platform"
                            type="text"
                            maxlength="100"
                            :placeholder="selectedMeta.platformPlaceholder"
                            :aria-invalid="Boolean(form.errors.platform)"
                            class="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-base text-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none sm:h-10 sm:text-sm"
                        />
                        <p
                            v-if="form.errors.platform"
                            class="mt-1.5 text-xs text-destructive"
                        >
                            {{ form.errors.platform }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="asset-ticker"
                            class="mb-1.5 flex items-center justify-between text-sm font-medium text-foreground"
                        >
                            <span>
                                {{
                                    isManualAsset
                                        ? 'Kode / Nomor Identitas'
                                        : 'Kode Ticker / Simbol'
                                }}
                            </span>
                            <span
                                class="text-xs font-normal text-muted-foreground"
                            >
                                Opsional
                            </span>
                        </label>
                        <input
                            id="asset-ticker"
                            v-model="form.ticker"
                            type="text"
                            maxlength="20"
                            autocapitalize="characters"
                            autocorrect="off"
                            spellcheck="false"
                            :placeholder="selectedMeta.tickerPlaceholder"
                            :aria-invalid="Boolean(form.errors.ticker)"
                            class="h-11 w-full rounded-lg border border-input bg-background px-3.5 font-mono text-base text-foreground uppercase placeholder:font-sans placeholder:text-muted-foreground placeholder:normal-case focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none sm:h-10 sm:text-sm"
                        />
                        <p
                            v-if="form.errors.ticker"
                            class="mt-1.5 text-xs text-destructive"
                        >
                            {{ form.errors.ticker }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Section 2: Jumlah & Valuasi -->
            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <h2 class="text-sm font-semibold text-foreground">
                        Jumlah &amp; Valuasi Awal
                    </h2>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        {{
                            isManualAsset
                                ? 'Masukkan estimasi nilai pasar terkini dan modal awal bila ingin memantau selisih nilai.'
                                : 'Masukkan kepemilikan, modal rata-rata beli, dan harga pasar saat ini.'
                        }}
                    </p>
                </div>

                <!-- Unit-Tracked Fields -->
                <template v-if="!isManualAsset">
                    <div>
                        <label
                            for="asset-quantity"
                            class="mb-1.5 block text-sm font-medium text-foreground"
                        >
                            Jumlah Kepemilikan ({{ selectedMeta.unitLabel }})
                        </label>
                        <input
                            id="asset-quantity"
                            v-model="form.quantity"
                            type="number"
                            inputmode="decimal"
                            :step="quantityStep"
                            min="0"
                            placeholder="0"
                            @input="handleQuantityChange"
                            :aria-invalid="Boolean(form.errors.quantity)"
                            :aria-describedby="
                                [
                                    isStock ? 'asset-quantity-stock-hint' : '',
                                    form.errors.quantity
                                        ? 'asset-quantity-error'
                                        : '',
                                ]
                                    .filter(Boolean)
                                    .join(' ') || undefined
                            "
                            class="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-base text-foreground tabular-nums placeholder:text-muted-foreground focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none sm:h-10 sm:text-sm"
                        />
                        <p
                            v-if="isStock"
                            id="asset-quantity-stock-hint"
                            class="mt-1.5 text-xs text-muted-foreground tabular-nums"
                        >
                            <template v-if="numericQuantity > 0">
                                Setara dengan
                                <strong class="font-medium text-foreground">
                                    {{ formatStockShares(numericQuantity) }}
                                </strong>
                                (1 lot = 100 lembar).
                            </template>
                            <template v-else>
                                Saham Bursa Efek Indonesia menggunakan satuan
                                lot (1 lot = 100 lembar).
                            </template>
                        </p>
                        <p
                            v-if="form.errors.quantity"
                            id="asset-quantity-error"
                            class="mt-1.5 text-xs text-destructive"
                        >
                            {{ form.errors.quantity }}
                        </p>
                    </div>

                    <!-- Harga Beli Rata-rata & Total Modal -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label
                                for="asset-avg-price"
                                class="mb-1.5 block text-sm font-medium text-foreground"
                            >
                                Harga Beli Rata-rata ({{
                                    selectedMeta.priceUnitHint
                                }})
                            </label>
                            <MoneyInput
                                id="asset-avg-price"
                                :model-value="form.avg_purchase_price"
                                :currency="form.currency"
                                placeholder="0"
                                @update:model-value="handleAvgPriceChange"
                            />
                            <p
                                v-if="form.errors.avg_purchase_price"
                                class="mt-1.5 text-xs text-destructive"
                            >
                                {{ form.errors.avg_purchase_price }}
                            </p>
                        </div>

                        <div>
                            <label
                                for="asset-total-invested"
                                class="mb-1.5 block text-sm font-medium text-foreground"
                            >
                                Total Modal Diinvestasikan
                            </label>
                            <MoneyInput
                                id="asset-total-invested"
                                :model-value="totalInvestedField"
                                :currency="form.currency"
                                placeholder="0"
                                @update:model-value="handleTotalInvestedChange"
                            />
                            <p class="mt-1.5 text-xs text-muted-foreground">
                                Isi salah satu — kolom pasangannya terhitung
                                otomatis.
                            </p>
                        </div>
                    </div>

                    <!-- Harga Pasar Saat Ini -->
                    <div>
                        <div
                            class="mb-1.5 flex flex-wrap items-center justify-between gap-2"
                        >
                            <label
                                for="asset-current-price"
                                class="text-sm font-medium text-foreground"
                            >
                                Harga Pasar Saat Ini ({{
                                    selectedMeta.priceUnitHint
                                }})
                            </label>
                            <button
                                v-if="
                                    form.avg_purchase_price > 0 &&
                                    form.current_price !==
                                        form.avg_purchase_price
                                "
                                type="button"
                                @click="copyBuyPriceToCurrent"
                                class="text-xs font-medium text-primary hover:underline focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                            >
                                Samakan dengan harga beli
                            </button>
                        </div>
                        <MoneyInput
                            id="asset-current-price"
                            v-model="form.current_price"
                            :currency="form.currency"
                            placeholder="0"
                        />
                        <p
                            v-if="form.errors.current_price"
                            class="mt-1.5 text-xs text-destructive"
                        >
                            {{ form.errors.current_price }}
                        </p>
                    </div>
                </template>

                <!-- Manual Valuation Fields -->
                <template v-else>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label
                                for="asset-manual-value"
                                class="mb-1.5 block text-sm font-medium text-foreground"
                            >
                                Nilai Taksiran Saat Ini
                                <span class="text-destructive">*</span>
                            </label>
                            <MoneyInput
                                id="asset-manual-value"
                                v-model="form.manual_value"
                                :currency="form.currency"
                                required
                                placeholder="0"
                            />
                            <p
                                v-if="form.errors.manual_value"
                                class="mt-1.5 text-xs text-destructive"
                            >
                                {{ form.errors.manual_value }}
                            </p>
                        </div>

                        <div>
                            <label
                                for="asset-manual-cost"
                                class="mb-1.5 flex items-center justify-between text-sm font-medium text-foreground"
                            >
                                <span>Harga Beli / Modal Awal</span>
                                <span
                                    class="text-xs font-normal text-muted-foreground"
                                >
                                    Opsional
                                </span>
                            </label>
                            <MoneyInput
                                id="asset-manual-cost"
                                v-model="form.avg_purchase_price"
                                :currency="form.currency"
                                placeholder="0"
                            />
                            <p
                                v-if="form.errors.avg_purchase_price"
                                class="mt-1.5 text-xs text-destructive"
                            >
                                {{ form.errors.avg_purchase_price }}
                            </p>
                        </div>
                    </div>
                </template>

                <!-- Live Calculation Preview Strip -->
                <div
                    class="@container overflow-hidden rounded-xl border bg-border"
                >
                    <div class="grid grid-cols-1 gap-px @md:grid-cols-3">
                        <div class="bg-background px-4 py-3">
                            <p class="text-xs text-muted-foreground">
                                Estimasi Nilai Pasar
                            </p>
                            <p
                                class="mt-0.5 text-sm font-semibold tabular-nums"
                            >
                                <MoneyDisplay
                                    :amount="previewMarketValue"
                                    :currency="form.currency"
                                />
                            </p>
                        </div>
                        <div class="bg-background px-4 py-3">
                            <p class="text-xs text-muted-foreground">
                                Total Modal
                            </p>
                            <p
                                class="mt-0.5 text-sm font-semibold tabular-nums"
                            >
                                <MoneyDisplay
                                    :amount="previewCostBasis"
                                    :currency="form.currency"
                                />
                            </p>
                        </div>
                        <div class="bg-background px-4 py-3">
                            <p class="text-xs text-muted-foreground">
                                Estimasi Selisih
                            </p>
                            <p
                                class="mt-0.5 text-sm font-semibold tabular-nums"
                                :class="
                                    previewPnl > 0
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : previewPnl < 0
                                          ? 'text-rose-600 dark:text-rose-400'
                                          : 'text-foreground'
                                "
                            >
                                <MoneyDisplay
                                    :amount="previewPnl"
                                    :currency="form.currency"
                                    show-sign
                                />
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Catatan -->
            <div class="space-y-4 p-5 sm:p-6">
                <div>
                    <label
                        for="asset-notes"
                        class="mb-1.5 flex items-center justify-between text-sm font-medium text-foreground"
                    >
                        <span>Catatan</span>
                        <span class="text-xs font-normal text-muted-foreground">
                            Opsional
                        </span>
                    </label>
                    <textarea
                        id="asset-notes"
                        v-model="form.notes"
                        rows="3"
                        placeholder="Tambahkan strategi investasi, nomor sertifikat, atau target harga..."
                        class="w-full rounded-lg border border-input bg-background px-3.5 py-2.5 text-base text-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none sm:text-sm"
                    ></textarea>
                    <p
                        v-if="form.errors.notes"
                        class="mt-1.5 text-xs text-destructive"
                    >
                        {{ form.errors.notes }}
                    </p>
                </div>
            </div>

            <!-- Actions Footer -->
            <div
                class="flex flex-col-reverse gap-2 px-5 py-4 sm:flex-row sm:items-center sm:justify-end sm:px-6"
            >
                <Link
                    href="/assets"
                    class="inline-flex h-11 items-center justify-center rounded-lg border border-input bg-background px-4 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none sm:h-10"
                >
                    Batal
                </Link>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex h-11 items-center justify-center gap-1.5 rounded-lg bg-primary px-5 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:ring-offset-2 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50 sm:h-10"
                >
                    <Loader2
                        v-if="form.processing"
                        class="size-4 animate-spin"
                        aria-hidden="true"
                    />
                    <Plus v-else class="size-4" aria-hidden="true" />
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Aset' }}
                </button>
            </div>
        </form>
    </div>
</template>
