<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowDownLeft,
    ArrowLeft,
    ArrowUpRight,
    Loader2,
    Pencil,
    Plus,
    RefreshCw,
    Trash2,
    TrendingDown,
    TrendingUp,
} from '@lucide/vue';
import dayjs from 'dayjs';
import 'dayjs/locale/id';
import { computed, ref } from 'vue';
import MoneyDisplay from '@/components/MoneyDisplay.vue';
import MoneyInput from '@/components/MoneyInput.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    formatAssetDate,
    formatAssetQuantity,
    formatStockShares,
    getAssetTintStyle,
    getAssetTypeMeta,
} from '@/lib/assets';

const props = defineProps<{
    asset: any;
    accounts: Array<{
        id: string;
        name: string;
        type: string;
        current_balance: number;
    }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Aset & Investasi', href: '/assets' },
            { title: 'Detail Aset', href: '#' },
        ],
    },
});

const typeMeta = computed(() => getAssetTypeMeta(props.asset.type));

const isManualAsset = computed(
    () =>
        props.asset.manual_value !== null &&
        props.asset.manual_value !== undefined,
);

const isStock = computed(() => props.asset.type === 'stock');
const stockMultiplier = computed(() => (isStock.value ? 100 : 1));

const formatPnlPercent = (pct: number) => {
    const abs = Math.abs(pct || 0).toLocaleString('id-ID', {
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

// Modals state
const showPriceModal = ref(false);
const showBuyModal = ref(false);
const showSellModal = ref(false);
const showDeleteModal = ref(false);
const isDeleting = ref(false);

const quantityStep = computed(() => {
    if (isStock.value) {
        return '1';
    }

    const p = typeMeta.value.precision;

    return p > 0 ? (1 / Math.pow(10, Math.min(p, 8))).toString() : 'any';
});

const todayDate = () => dayjs().format('YYYY-MM-DD');

// Form 1: Update Harga Pasar
const priceForm = useForm({
    current_price: Number(props.asset.current_price || 0),
});

const openPriceModal = () => {
    priceForm.clearErrors();
    priceForm.current_price = Number(props.asset.current_price || 0);
    showPriceModal.value = true;
};

const previewNewMarketValue = computed(() =>
    Math.round(
        Number(props.asset.quantity || 0) *
            stockMultiplier.value *
            (priceForm.current_price || 0),
    ),
);

const submitPrice = () => {
    priceForm.patch(`/assets/${props.asset.id}/price`, {
        preserveScroll: true,
        onSuccess: () => {
            showPriceModal.value = false;
        },
    });
};

// Form 2: Beli / Tambah Kepemilikan
const buyForm = useForm({
    source_account_id: props.accounts?.[0]?.id || '',
    quantity: '' as number | string,
    price_per_unit: Number(props.asset.current_price || 0),
    fees: 0,
    date: todayDate(),
    description: '',
    notes: '',
});

const openBuyModal = () => {
    buyForm.clearErrors();
    buyForm.source_account_id = props.accounts?.[0]?.id || '';
    buyForm.quantity = '';
    buyForm.price_per_unit = Number(props.asset.current_price || 0);
    buyForm.fees = 0;
    buyForm.date = todayDate();
    buyForm.notes = '';
    showBuyModal.value = true;
};

const buySubtotal = computed(() => {
    const qty = Number(buyForm.quantity || 0);

    if (!Number.isFinite(qty) || qty <= 0) {
        return 0;
    }

    return Math.round(
        qty * stockMultiplier.value * (buyForm.price_per_unit || 0),
    );
});

const buyGrandTotal = computed(() => buySubtotal.value + (buyForm.fees || 0));

const submitBuy = () => {
    buyForm.post(`/assets/${props.asset.id}/buy`, {
        preserveScroll: true,
        onSuccess: () => {
            showBuyModal.value = false;
        },
    });
};

// Form 3: Jual / Cairkan Aset
const sellForm = useForm({
    destination_account_id: props.accounts?.[0]?.id || '',
    quantity: '' as number | string,
    price_per_unit: Number(props.asset.current_price || 0),
    fees: 0,
    date: todayDate(),
    description: '',
    notes: '',
});

const openSellModal = () => {
    sellForm.clearErrors();
    sellForm.destination_account_id = props.accounts?.[0]?.id || '';
    sellForm.quantity = '';
    sellForm.price_per_unit = Number(props.asset.current_price || 0);
    sellForm.fees = 0;
    sellForm.date = todayDate();
    sellForm.notes = '';
    showSellModal.value = true;
};

const fillMaxSellQuantity = () => {
    sellForm.quantity = Number(props.asset.quantity || 0);
};

const sellGrossTotal = computed(() => {
    const qty = Number(sellForm.quantity || 0);

    if (!Number.isFinite(qty) || qty <= 0) {
        return 0;
    }

    return Math.round(
        qty * stockMultiplier.value * (sellForm.price_per_unit || 0),
    );
});

const sellNetProceeds = computed(() =>
    Math.max(0, sellGrossTotal.value - (sellForm.fees || 0)),
);

const submitSell = () => {
    sellForm.post(`/assets/${props.asset.id}/sell`, {
        preserveScroll: true,
        onSuccess: () => {
            showSellModal.value = false;
        },
    });
};

// Delete asset
const confirmDelete = () => {
    isDeleting.value = true;
    router.delete(`/assets/${props.asset.id}`, {
        onFinish: () => {
            isDeleting.value = false;
        },
    });
};

const transactions = computed<any[]>(() => props.asset.transactions ?? []);
</script>

<template>
    <Head :title="asset.name" />

    <div
        class="mx-auto flex h-full w-full max-w-5xl flex-1 flex-col gap-5 p-4 md:p-6 lg:gap-6 lg:p-8"
    >
        <!-- Header & Actions -->
        <div
            class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between"
        >
            <div class="flex min-w-0 items-start gap-3">
                <Link
                    href="/assets"
                    aria-label="Kembali ke daftar aset"
                    class="mt-0.5 inline-flex size-11 shrink-0 items-center justify-center rounded-lg border bg-card text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none sm:size-9"
                >
                    <ArrowLeft class="size-4" aria-hidden="true" />
                </Link>

                <div
                    class="flex size-11 shrink-0 items-center justify-center rounded-xl sm:size-12"
                    :style="getAssetTintStyle(typeMeta.tint)"
                >
                    <component
                        :is="typeMeta.icon"
                        class="size-5 sm:size-6"
                        aria-hidden="true"
                    />
                </div>

                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1
                            class="truncate text-2xl font-semibold tracking-tight"
                        >
                            {{ asset.name }}
                        </h1>
                        <span
                            v-if="asset.ticker"
                            class="inline-flex items-center rounded border bg-card px-2 py-0.5 font-mono text-xs font-medium text-muted-foreground uppercase"
                        >
                            {{ asset.ticker }}
                        </span>
                        <span
                            v-if="!asset.is_active"
                            class="inline-flex items-center rounded bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground"
                        >
                            Nonaktif
                        </span>
                    </div>

                    <p class="mt-0.5 text-sm text-muted-foreground">
                        {{ typeMeta.label }}
                        <span v-if="asset.platform">
                            · {{ asset.platform }}
                        </span>
                        <span> · {{ asset.currency }}</span>
                    </p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div
                class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:items-center lg:justify-end"
            >
                <template v-if="!isManualAsset">
                    <button
                        type="button"
                        @click="openPriceModal"
                        class="inline-flex h-11 items-center justify-center gap-1.5 rounded-lg bg-primary px-3.5 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none sm:h-9"
                    >
                        <RefreshCw class="size-4" aria-hidden="true" />
                        Perbarui Harga
                    </button>

                    <button
                        type="button"
                        @click="openBuyModal"
                        class="inline-flex h-11 items-center justify-center gap-1.5 rounded-lg border border-input bg-card px-3.5 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none sm:h-9"
                    >
                        <Plus
                            class="size-4 text-emerald-600"
                            aria-hidden="true"
                        />
                        Beli
                    </button>

                    <button
                        type="button"
                        :disabled="Number(asset.quantity || 0) <= 0"
                        @click="openSellModal"
                        class="inline-flex h-11 items-center justify-center gap-1.5 rounded-lg border border-input bg-card px-3.5 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50 sm:h-9"
                    >
                        <ArrowUpRight
                            class="size-4 text-rose-600"
                            aria-hidden="true"
                        />
                        Jual
                    </button>
                </template>

                <Link
                    :href="`/assets/${asset.id}/edit`"
                    class="inline-flex h-11 items-center justify-center gap-1.5 rounded-lg border border-input bg-card px-3.5 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none sm:h-9"
                >
                    <Pencil class="size-4" aria-hidden="true" />
                    {{ isManualAsset ? 'Perbarui / Edit' : 'Edit' }}
                </Link>

                <button
                    type="button"
                    @click="showDeleteModal = true"
                    class="inline-flex h-11 items-center justify-center gap-1.5 rounded-lg border border-rose-200 bg-card px-3.5 text-sm font-medium text-rose-600 transition-colors hover:bg-rose-50 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none sm:h-9 dark:border-rose-900/60 dark:text-rose-400 dark:hover:bg-rose-950/40"
                >
                    <Trash2 class="size-4" aria-hidden="true" />
                    Hapus
                </button>
            </div>
        </div>

        <!-- Asset Value & Hairline Stat Card -->
        <section
            aria-label="Ringkasan nilai aset"
            class="@container overflow-hidden rounded-xl border bg-card"
        >
            <div class="p-5 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2
                        class="text-xs font-medium tracking-wider text-muted-foreground uppercase"
                    >
                        {{
                            isManualAsset
                                ? 'Nilai Taksiran Saat Ini'
                                : 'Nilai Pasar Saat Ini'
                        }}
                    </h2>
                    <span class="text-xs text-muted-foreground">
                        Diperbarui:
                        {{ formatAssetDate(asset.last_price_update, true) }}
                    </span>
                </div>

                <p
                    class="mt-2.5 text-3xl font-semibold tracking-tight tabular-nums sm:text-4xl"
                >
                    <MoneyDisplay
                        :amount="asset.current_value"
                        :currency="asset.currency"
                    />
                </p>

                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-medium tabular-nums"
                        :class="pnlBadgeClass(asset.unrealized_pnl)"
                    >
                        <component
                            :is="
                                asset.unrealized_pnl >= 0
                                    ? TrendingUp
                                    : TrendingDown
                            "
                            class="size-3.5"
                            aria-hidden="true"
                        />
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
                    <span class="text-xs text-muted-foreground">
                        Selisih terhadap total modal
                    </span>
                </div>
            </div>

            <!-- 4-Cell Hairline Strip -->
            <div
                class="grid grid-cols-1 gap-px border-t bg-border @md:grid-cols-2 @xl:grid-cols-4"
            >
                <template v-if="!isManualAsset">
                    <div class="bg-card px-5 py-3.5">
                        <p class="text-xs font-medium text-muted-foreground">
                            Kepemilikan
                        </p>
                        <p
                            class="mt-1 text-base font-semibold tracking-tight tabular-nums"
                        >
                            {{
                                formatAssetQuantity(asset.quantity, asset.type)
                            }}
                        </p>
                        <p
                            v-if="isStock"
                            class="mt-0.5 text-xs text-muted-foreground tabular-nums"
                        >
                            {{ formatStockShares(asset.quantity) }}
                        </p>
                    </div>

                    <div class="bg-card px-5 py-3.5">
                        <p class="text-xs font-medium text-muted-foreground">
                            Harga Beli Rata-rata
                        </p>
                        <p
                            class="mt-1 text-base font-semibold tracking-tight tabular-nums"
                        >
                            <MoneyDisplay
                                :amount="asset.avg_purchase_price"
                                :currency="asset.currency"
                            />
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{ typeMeta.priceUnitHint }}
                        </p>
                    </div>

                    <div class="bg-card px-5 py-3.5">
                        <p class="text-xs font-medium text-muted-foreground">
                            Harga Pasar Terkini
                        </p>
                        <p
                            class="mt-1 text-base font-semibold tracking-tight tabular-nums"
                        >
                            <MoneyDisplay
                                :amount="asset.current_price"
                                :currency="asset.currency"
                            />
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{ typeMeta.priceUnitHint }}
                        </p>
                    </div>

                    <div class="bg-card px-5 py-3.5">
                        <p class="text-xs font-medium text-muted-foreground">
                            Total Modal (Cost Basis)
                        </p>
                        <p
                            class="mt-1 text-base font-semibold tracking-tight tabular-nums"
                        >
                            <MoneyDisplay
                                :amount="asset.cost_basis"
                                :currency="asset.currency"
                            />
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            Akumulasi pembelian bersih
                        </p>
                    </div>
                </template>

                <template v-else>
                    <div class="bg-card px-5 py-3.5">
                        <p class="text-xs font-medium text-muted-foreground">
                            Metode Penilaian
                        </p>
                        <p class="mt-1 text-base font-semibold">Manual</p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{ typeMeta.label }}
                        </p>
                    </div>

                    <div class="bg-card px-5 py-3.5">
                        <p class="text-xs font-medium text-muted-foreground">
                            Modal Awal
                        </p>
                        <p
                            class="mt-1 text-base font-semibold tracking-tight tabular-nums"
                        >
                            <MoneyDisplay
                                :amount="asset.cost_basis"
                                :currency="asset.currency"
                            />
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            Harga perolehan tercatat
                        </p>
                    </div>

                    <div class="bg-card px-5 py-3.5">
                        <p class="text-xs font-medium text-muted-foreground">
                            Status
                        </p>
                        <p class="mt-1 text-base font-semibold">
                            {{ asset.is_active ? 'Aktif' : 'Nonaktif' }}
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{
                                asset.is_active
                                    ? 'Masuk perhitungan aset'
                                    : 'Dikecualikan dari portofolio'
                            }}
                        </p>
                    </div>

                    <div class="bg-card px-5 py-3.5">
                        <p class="text-xs font-medium text-muted-foreground">
                            Dicatat Sejak
                        </p>
                        <p class="mt-1 text-base font-semibold">
                            {{ formatAssetDate(asset.created_at) }}
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            Tanggal entri awal
                        </p>
                    </div>
                </template>
            </div>
        </section>

        <!-- Catatan Aset -->
        <section
            v-if="asset.notes"
            aria-label="Catatan aset"
            class="rounded-xl border bg-card p-5 sm:p-6"
        >
            <h2 class="text-sm font-semibold text-foreground">Catatan Aset</h2>
            <p
                class="mt-2 text-sm leading-relaxed whitespace-pre-line text-muted-foreground"
            >
                {{ asset.notes }}
            </p>
        </section>

        <!-- Riwayat Transaksi Aset -->
        <section
            v-if="!isManualAsset || transactions.length > 0"
            aria-labelledby="riwayat-transaksi-heading"
            class="overflow-hidden rounded-xl border bg-card"
        >
            <div
                class="flex flex-wrap items-center justify-between gap-2 border-b px-5 py-4 sm:px-6"
            >
                <div>
                    <h2
                        id="riwayat-transaksi-heading"
                        class="text-base font-semibold tracking-tight"
                    >
                        Riwayat Transaksi Aset
                    </h2>
                    <p class="text-xs text-muted-foreground">
                        Catatan pembelian dan penjualan unit yang terhubung
                        dengan rekening kas Anda.
                    </p>
                </div>
                <span
                    class="rounded-full border px-2.5 py-0.5 text-xs font-medium text-muted-foreground tabular-nums"
                >
                    {{ transactions.length }} transaksi
                </span>
            </div>

            <div v-if="transactions.length > 0" class="divide-y">
                <div
                    v-for="tx in transactions"
                    :key="tx.id"
                    class="flex flex-col justify-between gap-3 px-5 py-4 sm:flex-row sm:items-center sm:px-6"
                >
                    <div class="flex min-w-0 items-start gap-3">
                        <div
                            class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg"
                            :class="
                                tx.type === 'buy'
                                    ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                                    : 'bg-rose-500/10 text-rose-600 dark:text-rose-400'
                            "
                        >
                            <component
                                :is="
                                    tx.type === 'buy'
                                        ? ArrowDownLeft
                                        : ArrowUpRight
                                "
                                class="size-4"
                                aria-hidden="true"
                            />
                        </div>

                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="text-sm font-semibold text-foreground"
                                >
                                    {{
                                        tx.type === 'buy'
                                            ? 'Pembelian Aset'
                                            : 'Penjualan Aset'
                                    }}
                                </span>
                                <span
                                    class="text-xs text-muted-foreground tabular-nums"
                                >
                                    {{
                                        formatAssetDate(
                                            tx.transaction?.date ||
                                                tx.created_at,
                                        )
                                    }}
                                </span>
                            </div>

                            <p
                                class="mt-0.5 text-xs text-muted-foreground tabular-nums"
                            >
                                {{
                                    formatAssetQuantity(tx.quantity, asset.type)
                                }}
                                @
                                <MoneyDisplay
                                    :amount="tx.price_per_unit"
                                    :currency="asset.currency"
                                />
                                <template v-if="tx.fees > 0">
                                    · Biaya:
                                    <MoneyDisplay
                                        :amount="tx.fees"
                                        :currency="asset.currency"
                                    />
                                </template>
                            </p>

                            <p
                                v-if="tx.notes || tx.transaction?.description"
                                class="mt-1 truncate text-xs text-muted-foreground"
                            >
                                {{ tx.notes || tx.transaction?.description }}
                            </p>
                        </div>
                    </div>

                    <div class="shrink-0 pl-12 text-left sm:pl-0 sm:text-right">
                        <p
                            class="text-sm font-semibold tabular-nums sm:text-base"
                            :class="
                                tx.type === 'buy'
                                    ? 'text-foreground'
                                    : 'text-emerald-600 dark:text-emerald-400'
                            "
                        >
                            <MoneyDisplay
                                :amount="tx.total_amount"
                                :currency="asset.currency"
                            />
                        </p>
                        <p class="text-[11px] text-muted-foreground">
                            {{
                                tx.type === 'buy'
                                    ? 'Total Dana Keluar'
                                    : 'Dana Bersih Masuk'
                            }}
                        </p>
                    </div>
                </div>
            </div>

            <div v-else class="px-6 py-10 text-center">
                <p class="text-sm font-medium text-foreground">
                    Belum ada riwayat beli atau jual
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    Gunakan tombol Beli atau Jual di atas untuk mencatat
                    transaksi portofolio yang terhubung ke saldo rekening.
                </p>
            </div>
        </section>

        <!-- Dialog 1: Perbarui Harga Pasar -->
        <Dialog v-model:open="showPriceModal">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Perbarui Harga Pasar</DialogTitle>
                    <DialogDescription>
                        Sesuaikan harga pasar terkini ({{
                            typeMeta.priceUnitHint
                        }}) untuk <strong>{{ asset.name }}</strong> agar
                        kalkulasi nilai portofolio tetap akurat.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitPrice" class="space-y-4">
                    <div>
                        <label
                            for="update-price-input"
                            class="mb-1.5 block text-sm font-medium text-foreground"
                        >
                            Harga Pasar Baru ({{ typeMeta.priceUnitHint }})
                        </label>
                        <MoneyInput
                            id="update-price-input"
                            v-model="priceForm.current_price"
                            :currency="asset.currency"
                            required
                        />
                        <p
                            v-if="priceForm.errors.current_price"
                            class="mt-1.5 text-xs text-destructive"
                        >
                            {{ priceForm.errors.current_price }}
                        </p>
                    </div>

                    <div
                        class="flex items-center justify-between rounded-lg border px-3.5 py-2.5 text-xs"
                    >
                        <span class="text-muted-foreground">
                            Estimasi Nilai Baru ({{
                                formatAssetQuantity(asset.quantity, asset.type)
                            }})
                        </span>
                        <span
                            class="font-semibold text-foreground tabular-nums"
                        >
                            <MoneyDisplay
                                :amount="previewNewMarketValue"
                                :currency="asset.currency"
                            />
                        </span>
                    </div>

                    <DialogFooter>
                        <button
                            type="button"
                            @click="showPriceModal = false"
                            class="inline-flex h-11 items-center justify-center rounded-lg border border-input bg-background px-4 text-sm font-medium text-foreground hover:bg-accent sm:h-9"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="priceForm.processing"
                            class="inline-flex h-11 items-center justify-center gap-1.5 rounded-lg bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50 sm:h-9"
                        >
                            <Loader2
                                v-if="priceForm.processing"
                                class="size-4 animate-spin"
                                aria-hidden="true"
                            />
                            {{
                                priceForm.processing
                                    ? 'Menyimpan...'
                                    : 'Simpan Harga'
                            }}
                        </button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Dialog 2: Beli / Tambah Kepemilikan -->
        <Dialog v-model:open="showBuyModal">
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Beli {{ asset.name }}</DialogTitle>
                    <DialogDescription>
                        Penambahan kepemilikan akan memperbarui harga beli
                        rata-rata secara otomatis dan memotong saldo rekening
                        sumber.
                    </DialogDescription>
                </DialogHeader>

                <div
                    v-if="accounts.length === 0"
                    class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200"
                >
                    <p class="font-medium">Belum ada rekening kas/bank aktif</p>
                    <p class="mt-1 text-xs opacity-90">
                        Buat rekening sumber dana terlebih dahulu sebelum
                        mencatat transaksi pembelian aset.
                    </p>
                    <Link
                        href="/accounts/create"
                        class="mt-3 inline-flex h-9 items-center justify-center rounded-lg bg-primary px-3 text-xs font-medium text-primary-foreground"
                    >
                        Tambah Rekening Sekarang
                    </Link>
                </div>

                <form v-else @submit.prevent="submitBuy" class="space-y-4">
                    <div>
                        <label
                            for="buy-source-account"
                            class="mb-1.5 block text-sm font-medium text-foreground"
                        >
                            Sumber Dana (Rekening)
                            <span class="text-destructive">*</span>
                        </label>
                        <select
                            id="buy-source-account"
                            v-model="buyForm.source_account_id"
                            required
                            class="h-11 w-full rounded-lg border border-input bg-background px-3 text-base text-foreground focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none sm:h-10 sm:text-sm"
                        >
                            <option
                                v-for="acc in accounts"
                                :key="acc.id"
                                :value="acc.id"
                            >
                                {{ acc.name }} (Saldo:
                                {{
                                    new Intl.NumberFormat('id-ID', {
                                        style: 'currency',
                                        currency: 'IDR',
                                        maximumFractionDigits: 0,
                                    }).format(acc.current_balance)
                                }})
                            </option>
                        </select>
                        <p
                            v-if="buyForm.errors.source_account_id"
                            class="mt-1.5 text-xs text-destructive"
                        >
                            {{ buyForm.errors.source_account_id }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label
                                for="buy-quantity"
                                class="mb-1.5 block text-sm font-medium text-foreground"
                            >
                                Jumlah ({{ typeMeta.unitLabel }})
                                <span class="text-destructive">*</span>
                            </label>
                            <input
                                id="buy-quantity"
                                v-model="buyForm.quantity"
                                type="number"
                                inputmode="decimal"
                                :step="quantityStep"
                                min="0"
                                required
                                placeholder="0"
                                class="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-base text-foreground tabular-nums focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none sm:h-10 sm:text-sm"
                            />
                            <p
                                v-if="isStock && Number(buyForm.quantity) > 0"
                                class="mt-1 text-xs text-muted-foreground tabular-nums"
                            >
                                = {{ formatStockShares(buyForm.quantity) }}
                            </p>
                            <p
                                v-if="buyForm.errors.quantity"
                                class="mt-1 text-xs text-destructive"
                            >
                                {{ buyForm.errors.quantity }}
                            </p>
                        </div>

                        <div>
                            <label
                                for="buy-price"
                                class="mb-1.5 block text-sm font-medium text-foreground"
                            >
                                Harga Beli ({{ typeMeta.priceUnitHint }})
                                <span class="text-destructive">*</span>
                            </label>
                            <MoneyInput
                                id="buy-price"
                                v-model="buyForm.price_per_unit"
                                :currency="asset.currency"
                                required
                            />
                            <p
                                v-if="buyForm.errors.price_per_unit"
                                class="mt-1 text-xs text-destructive"
                            >
                                {{ buyForm.errors.price_per_unit }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label
                                for="buy-fees"
                                class="mb-1.5 block text-sm font-medium text-foreground"
                            >
                                Biaya Admin / Broker
                            </label>
                            <MoneyInput
                                id="buy-fees"
                                v-model="buyForm.fees"
                                :currency="asset.currency"
                                placeholder="0"
                            />
                            <p
                                v-if="buyForm.errors.fees"
                                class="mt-1 text-xs text-destructive"
                            >
                                {{ buyForm.errors.fees }}
                            </p>
                        </div>

                        <div>
                            <label
                                for="buy-date"
                                class="mb-1.5 block text-sm font-medium text-foreground"
                            >
                                Tanggal Transaksi
                                <span class="text-destructive">*</span>
                            </label>
                            <input
                                id="buy-date"
                                v-model="buyForm.date"
                                type="date"
                                required
                                class="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-base text-foreground focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none sm:h-10 sm:text-sm"
                            />
                            <p
                                v-if="buyForm.errors.date"
                                class="mt-1 text-xs text-destructive"
                            >
                                {{ buyForm.errors.date }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <label
                            for="buy-notes"
                            class="mb-1.5 block text-sm font-medium text-foreground"
                        >
                            Catatan (Opsional)
                        </label>
                        <input
                            id="buy-notes"
                            v-model="buyForm.notes"
                            type="text"
                            placeholder="cth. Cicilan rutin bulanan (DCA)"
                            class="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-base text-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none sm:h-10 sm:text-sm"
                        />
                    </div>

                    <!-- Summary Box -->
                    <div class="space-y-1.5 rounded-xl border p-3.5 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground">
                                Subtotal Aset
                            </span>
                            <span class="font-medium tabular-nums">
                                <MoneyDisplay
                                    :amount="buySubtotal"
                                    :currency="asset.currency"
                                />
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground">
                                Biaya Transaksi
                            </span>
                            <span class="font-medium tabular-nums">
                                <MoneyDisplay
                                    :amount="buyForm.fees || 0"
                                    :currency="asset.currency"
                                />
                            </span>
                        </div>
                        <div
                            class="flex items-center justify-between border-t pt-2 text-sm font-semibold"
                        >
                            <span>Total Didebit dari Rekening</span>
                            <span class="tabular-nums">
                                <MoneyDisplay
                                    :amount="buyGrandTotal"
                                    :currency="asset.currency"
                                />
                            </span>
                        </div>
                    </div>

                    <DialogFooter>
                        <button
                            type="button"
                            @click="showBuyModal = false"
                            class="inline-flex h-11 items-center justify-center rounded-lg border border-input bg-background px-4 text-sm font-medium text-foreground hover:bg-accent sm:h-9"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="buyForm.processing"
                            class="inline-flex h-11 items-center justify-center gap-1.5 rounded-lg bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50 sm:h-9"
                        >
                            <Loader2
                                v-if="buyForm.processing"
                                class="size-4 animate-spin"
                                aria-hidden="true"
                            />
                            {{
                                buyForm.processing
                                    ? 'Memproses...'
                                    : 'Konfirmasi Pembelian'
                            }}
                        </button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Dialog 3: Jual / Cairkan Aset -->
        <Dialog v-model:open="showSellModal">
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Jual {{ asset.name }}</DialogTitle>
                    <DialogDescription>
                        Hasil penjualan bersih akan langsung ditambahkan ke
                        saldo rekening tujuan pilihan Anda.
                    </DialogDescription>
                </DialogHeader>

                <div
                    v-if="accounts.length === 0"
                    class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200"
                >
                    <p class="font-medium">Belum ada rekening tujuan aktif</p>
                    <p class="mt-1 text-xs opacity-90">
                        Buat rekening tujuan terlebih dahulu untuk menerima dana
                        hasil penjualan aset.
                    </p>
                    <Link
                        href="/accounts/create"
                        class="mt-3 inline-flex h-9 items-center justify-center rounded-lg bg-primary px-3 text-xs font-medium text-primary-foreground"
                    >
                        Tambah Rekening Sekarang
                    </Link>
                </div>

                <form v-else @submit.prevent="submitSell" class="space-y-4">
                    <div>
                        <label
                            for="sell-destination-account"
                            class="mb-1.5 block text-sm font-medium text-foreground"
                        >
                            Rekening Tujuan Dana
                            <span class="text-destructive">*</span>
                        </label>
                        <select
                            id="sell-destination-account"
                            v-model="sellForm.destination_account_id"
                            required
                            class="h-11 w-full rounded-lg border border-input bg-background px-3 text-base text-foreground focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none sm:h-10 sm:text-sm"
                        >
                            <option
                                v-for="acc in accounts"
                                :key="acc.id"
                                :value="acc.id"
                            >
                                {{ acc.name }}
                            </option>
                        </select>
                        <p
                            v-if="sellForm.errors.destination_account_id"
                            class="mt-1.5 text-xs text-destructive"
                        >
                            {{ sellForm.errors.destination_account_id }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <div
                                class="mb-1.5 flex items-center justify-between gap-2"
                            >
                                <label
                                    for="sell-quantity"
                                    class="text-sm font-medium text-foreground"
                                >
                                    Jumlah ({{ typeMeta.unitLabel }})
                                    <span class="text-destructive">*</span>
                                </label>
                                <button
                                    type="button"
                                    @click="fillMaxSellQuantity"
                                    class="text-xs font-medium text-primary hover:underline"
                                >
                                    Jual Semua ({{
                                        formatAssetQuantity(
                                            asset.quantity,
                                            asset.type,
                                            false,
                                        )
                                    }})
                                </button>
                            </div>
                            <input
                                id="sell-quantity"
                                v-model="sellForm.quantity"
                                type="number"
                                inputmode="decimal"
                                :step="quantityStep"
                                min="0"
                                :max="asset.quantity"
                                required
                                placeholder="0"
                                class="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-base text-foreground tabular-nums focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none sm:h-10 sm:text-sm"
                            />
                            <p
                                v-if="isStock && Number(sellForm.quantity) > 0"
                                class="mt-1 text-xs text-muted-foreground tabular-nums"
                            >
                                = {{ formatStockShares(sellForm.quantity) }}
                            </p>
                            <p
                                v-if="sellForm.errors.quantity"
                                class="mt-1 text-xs text-destructive"
                            >
                                {{ sellForm.errors.quantity }}
                            </p>
                        </div>

                        <div>
                            <label
                                for="sell-price"
                                class="mb-1.5 block text-sm font-medium text-foreground"
                            >
                                Harga Jual ({{ typeMeta.priceUnitHint }})
                                <span class="text-destructive">*</span>
                            </label>
                            <MoneyInput
                                id="sell-price"
                                v-model="sellForm.price_per_unit"
                                :currency="asset.currency"
                                required
                            />
                            <p
                                v-if="sellForm.errors.price_per_unit"
                                class="mt-1 text-xs text-destructive"
                            >
                                {{ sellForm.errors.price_per_unit }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label
                                for="sell-fees"
                                class="mb-1.5 block text-sm font-medium text-foreground"
                            >
                                Biaya Admin / Pajak Jual
                            </label>
                            <MoneyInput
                                id="sell-fees"
                                v-model="sellForm.fees"
                                :currency="asset.currency"
                                placeholder="0"
                            />
                            <p
                                v-if="sellForm.errors.fees"
                                class="mt-1 text-xs text-destructive"
                            >
                                {{ sellForm.errors.fees }}
                            </p>
                        </div>

                        <div>
                            <label
                                for="sell-date"
                                class="mb-1.5 block text-sm font-medium text-foreground"
                            >
                                Tanggal Penjualan
                                <span class="text-destructive">*</span>
                            </label>
                            <input
                                id="sell-date"
                                v-model="sellForm.date"
                                type="date"
                                required
                                class="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-base text-foreground focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none sm:h-10 sm:text-sm"
                            />
                            <p
                                v-if="sellForm.errors.date"
                                class="mt-1 text-xs text-destructive"
                            >
                                {{ sellForm.errors.date }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <label
                            for="sell-notes"
                            class="mb-1.5 block text-sm font-medium text-foreground"
                        >
                            Catatan (Opsional)
                        </label>
                        <input
                            id="sell-notes"
                            v-model="sellForm.notes"
                            type="text"
                            placeholder="cth. Ambil untung sebagian (profit taking)"
                            class="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-base text-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none sm:h-10 sm:text-sm"
                        />
                    </div>

                    <!-- Summary Box -->
                    <div class="space-y-1.5 rounded-xl border p-3.5 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground">
                                Hasil Penjualan Kotor
                            </span>
                            <span class="font-medium tabular-nums">
                                <MoneyDisplay
                                    :amount="sellGrossTotal"
                                    :currency="asset.currency"
                                />
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground">
                                Potongan Biaya
                            </span>
                            <span class="font-medium tabular-nums">
                                <MoneyDisplay
                                    :amount="sellForm.fees || 0"
                                    :currency="asset.currency"
                                />
                            </span>
                        </div>
                        <div
                            class="flex items-center justify-between border-t pt-2 text-sm font-semibold"
                        >
                            <span>Dana Bersih Masuk Rekening</span>
                            <span
                                class="text-emerald-600 tabular-nums dark:text-emerald-400"
                            >
                                <MoneyDisplay
                                    :amount="sellNetProceeds"
                                    :currency="asset.currency"
                                />
                            </span>
                        </div>
                    </div>

                    <DialogFooter>
                        <button
                            type="button"
                            @click="showSellModal = false"
                            class="inline-flex h-11 items-center justify-center rounded-lg border border-input bg-background px-4 text-sm font-medium text-foreground hover:bg-accent sm:h-9"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="sellForm.processing"
                            class="inline-flex h-11 items-center justify-center gap-1.5 rounded-lg bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50 sm:h-9"
                        >
                            <Loader2
                                v-if="sellForm.processing"
                                class="size-4 animate-spin"
                                aria-hidden="true"
                            />
                            {{
                                sellForm.processing
                                    ? 'Memproses...'
                                    : 'Konfirmasi Penjualan'
                            }}
                        </button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Dialog 4: Hapus Aset -->
        <Dialog v-model:open="showDeleteModal">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Hapus {{ asset.name }}?</DialogTitle>
                    <DialogDescription>
                        Aset ini akan dihapus dari daftar portofolio Anda. Saldo
                        rekening kas yang terhubung dengan riwayat transaksi
                        sebelumnya tidak akan ikut terhapus.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter>
                    <button
                        type="button"
                        @click="showDeleteModal = false"
                        class="inline-flex h-11 items-center justify-center rounded-lg border border-input bg-background px-4 text-sm font-medium text-foreground hover:bg-accent sm:h-9"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        :disabled="isDeleting"
                        @click="confirmDelete"
                        class="inline-flex h-11 items-center justify-center gap-1.5 rounded-lg bg-destructive px-4 text-sm font-medium text-destructive-foreground hover:bg-destructive/90 disabled:opacity-50 sm:h-9"
                    >
                        <Loader2
                            v-if="isDeleting"
                            class="size-4 animate-spin"
                            aria-hidden="true"
                        />
                        {{ isDeleting ? 'Menghapus...' : 'Ya, Hapus Aset' }}
                    </button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
