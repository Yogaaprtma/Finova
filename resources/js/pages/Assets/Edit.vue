<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Eye, Loader2, Save } from '@lucide/vue';
import { computed } from 'vue';
import MoneyDisplay from '@/components/MoneyDisplay.vue';
import MoneyInput from '@/components/MoneyInput.vue';
import {
    ASSET_CURRENCIES,
    formatAssetQuantity,
    getAssetTintStyle,
    getAssetTypeMeta,
} from '@/lib/assets';

const props = defineProps<{
    asset: any;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Aset & Investasi', href: '/assets' },
            { title: 'Edit Aset', href: '#' },
        ],
    },
});

const typeMeta = computed(() => getAssetTypeMeta(props.asset.type));

const isManualAsset = computed(
    () =>
        props.asset.manual_value !== null &&
        props.asset.manual_value !== undefined,
);

const form = useForm({
    name: props.asset.name || '',
    platform: props.asset.platform || '',
    ticker: props.asset.ticker || '',
    currency: props.asset.currency || 'IDR',
    manual_value: props.asset.manual_value ?? 0,
    is_active: Boolean(props.asset.is_active),
    notes: props.asset.notes || '',
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        ticker: data.ticker ? data.ticker.trim().toUpperCase() : '',
    })).put(`/assets/${props.asset.id}`);
};
</script>

<template>
    <Head :title="`Edit ${asset.name}`" />

    <div
        class="mx-auto flex h-full w-full max-w-3xl flex-1 flex-col gap-4 p-4 md:p-6 lg:gap-5 lg:p-8"
    >
        <!-- Header -->
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex min-w-0 items-center gap-3">
                <Link
                    :href="`/assets/${asset.id}`"
                    aria-label="Kembali ke detail aset"
                    class="inline-flex size-11 shrink-0 items-center justify-center rounded-lg border bg-card text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none sm:size-9"
                >
                    <ArrowLeft class="size-4" aria-hidden="true" />
                </Link>
                <div class="min-w-0">
                    <h1 class="truncate text-2xl font-semibold tracking-tight">
                        Edit Aset
                    </h1>
                    <p class="mt-0.5 truncate text-sm text-muted-foreground">
                        Perbarui identitas, catatan, dan status aktif untuk
                        {{ asset.name }}.
                    </p>
                </div>
            </div>

            <Link
                :href="`/assets/${asset.id}`"
                class="inline-flex h-11 shrink-0 items-center justify-center gap-1.5 rounded-lg border border-input bg-background px-3.5 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none sm:h-9"
            >
                <Eye class="size-4" aria-hidden="true" />
                Lihat Detail
            </Link>
        </div>

        <!-- Form Card -->
        <form
            @submit.prevent="submit"
            class="divide-y rounded-xl border bg-card"
        >
            <!-- Section 1: Identity -->
            <div class="space-y-5 p-5 sm:p-6">
                <!-- Category Info Banner -->
                <div
                    class="flex items-center justify-between gap-3 rounded-xl border bg-background p-3.5"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <div
                            class="flex size-10 shrink-0 items-center justify-center rounded-xl"
                            :style="getAssetTintStyle(typeMeta.tint)"
                        >
                            <component
                                :is="typeMeta.icon"
                                class="size-5"
                                aria-hidden="true"
                            />
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-foreground">
                                {{ typeMeta.label }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                Kategori aset tidak dapat diubah setelah dibuat.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Nama & Mata Uang -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="sm:col-span-2">
                        <label
                            for="edit-asset-name"
                            class="mb-1.5 block text-sm font-medium text-foreground"
                        >
                            Nama Aset
                            <span class="text-destructive">*</span>
                        </label>
                        <input
                            id="edit-asset-name"
                            v-model="form.name"
                            type="text"
                            required
                            maxlength="100"
                            :aria-invalid="Boolean(form.errors.name)"
                            class="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-base text-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none sm:h-10 sm:text-sm"
                        />
                        <p
                            v-if="form.errors.name"
                            class="mt-1.5 text-xs text-destructive"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="edit-asset-currency"
                            class="mb-1.5 block text-sm font-medium text-foreground"
                        >
                            Mata Uang
                        </label>
                        <select
                            id="edit-asset-currency"
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
                            <option
                                v-if="
                                    form.currency &&
                                    !ASSET_CURRENCIES.includes(
                                        form.currency as any,
                                    )
                                "
                                :value="form.currency"
                            >
                                {{ form.currency }}
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
                            for="edit-asset-platform"
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
                            id="edit-asset-platform"
                            v-model="form.platform"
                            type="text"
                            maxlength="100"
                            :placeholder="typeMeta.platformPlaceholder"
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
                            for="edit-asset-ticker"
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
                            id="edit-asset-ticker"
                            v-model="form.ticker"
                            type="text"
                            maxlength="20"
                            autocapitalize="characters"
                            autocorrect="off"
                            spellcheck="false"
                            :placeholder="typeMeta.tickerPlaceholder"
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

            <!-- Section 2: Valuasi -->
            <div class="space-y-4 p-5 sm:p-6">
                <div v-if="isManualAsset">
                    <label
                        for="edit-asset-manual-value"
                        class="mb-1.5 block text-sm font-medium text-foreground"
                    >
                        Nilai Taksiran Saat Ini
                        <span class="text-destructive">*</span>
                    </label>
                    <MoneyInput
                        id="edit-asset-manual-value"
                        v-model="form.manual_value"
                        :currency="form.currency"
                        required
                    />
                    <p class="mt-1.5 text-xs text-muted-foreground">
                        Perbarui estimasi nilai aset fisik sesuai harga pasar
                        atau appraisal terkini.
                    </p>
                    <p
                        v-if="form.errors.manual_value"
                        class="mt-1.5 text-xs text-destructive"
                    >
                        {{ form.errors.manual_value }}
                    </p>
                </div>

                <div
                    v-else
                    class="flex flex-col justify-between gap-3 rounded-xl border bg-background p-4 sm:flex-row sm:items-center"
                >
                    <div>
                        <p class="text-sm font-medium text-foreground">
                            Nilai Pasar Saat Ini
                        </p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            Kepemilikan
                            <span class="font-medium text-foreground">
                                {{
                                    formatAssetQuantity(
                                        asset.quantity,
                                        asset.type,
                                    )
                                }}
                            </span>
                            · Kelola beli, jual, dan pembaruan harga dari
                            halaman detail aset.
                        </p>
                    </div>
                    <div class="text-left sm:text-right">
                        <MoneyDisplay
                            :amount="asset.current_value"
                            :currency="asset.currency"
                            class="text-lg font-semibold tracking-tight tabular-nums"
                        />
                    </div>
                </div>
            </div>

            <!-- Section 3: Catatan & Status Aktif -->
            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <label
                        for="edit-asset-notes"
                        class="mb-1.5 flex items-center justify-between text-sm font-medium text-foreground"
                    >
                        <span>Catatan</span>
                        <span class="text-xs font-normal text-muted-foreground">
                            Opsional
                        </span>
                    </label>
                    <textarea
                        id="edit-asset-notes"
                        v-model="form.notes"
                        rows="3"
                        placeholder="Tambahkan catatan investasi, target harga, atau keterangan aset..."
                        class="w-full rounded-lg border border-input bg-background px-3.5 py-2.5 text-base text-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none sm:text-sm"
                    ></textarea>
                    <p
                        v-if="form.errors.notes"
                        class="mt-1.5 text-xs text-destructive"
                    >
                        {{ form.errors.notes }}
                    </p>
                </div>

                <!-- Active Switch -->
                <div
                    class="flex items-center justify-between gap-4 rounded-xl border bg-background p-4"
                >
                    <div class="min-w-0">
                        <label
                            id="active-asset-label"
                            for="active-asset-switch"
                            class="block cursor-pointer text-sm font-medium text-foreground"
                        >
                            Aset Aktif
                        </label>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            Aset aktif dihitung dalam total portofolio dan
                            ringkasan kekayaan bersih di dasbor.
                        </p>
                    </div>
                    <button
                        id="active-asset-switch"
                        type="button"
                        role="switch"
                        :aria-checked="form.is_active"
                        aria-labelledby="active-asset-label"
                        @click="form.is_active = !form.is_active"
                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:ring-offset-2 focus-visible:outline-none"
                        :class="
                            form.is_active
                                ? 'bg-primary'
                                : 'bg-muted-foreground/30'
                        "
                    >
                        <span
                            class="pointer-events-none inline-block size-5 rounded-full bg-background shadow-xs transition-transform"
                            :class="
                                form.is_active
                                    ? 'translate-x-5'
                                    : 'translate-x-0'
                            "
                        ></span>
                    </button>
                </div>
            </div>

            <!-- Actions Footer -->
            <div
                class="flex flex-col-reverse gap-2 px-5 py-4 sm:flex-row sm:items-center sm:justify-end sm:px-6"
            >
                <Link
                    :href="`/assets/${asset.id}`"
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
                    <Save v-else class="size-4" aria-hidden="true" />
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                </button>
            </div>
        </form>
    </div>
</template>
