<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Eye, Loader2, Save } from '@lucide/vue';
import { computed } from 'vue';
import MoneyInput from '@/components/MoneyInput.vue';
import {
    getLiabilityTintStyle,
    getLiabilityTypeMeta,
    LIABILITY_CURRENCIES,
    toDateInputValue,
} from '@/lib/liabilities';

const props = defineProps<{
    liability: {
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
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Utang', href: '/liabilities' },
            { title: 'Edit Utang', href: '#' },
        ],
    },
});

const typeMeta = computed(() => getLiabilityTypeMeta(props.liability.type));
const isCreditCard = computed(() => props.liability.type === 'credit_card');

const form = useForm({
    name: props.liability.name || '',
    currency: props.liability.currency || 'IDR',
    current_balance: props.liability.current_balance as number | null,
    credit_limit: props.liability.credit_limit as number | null,
    interest_rate: (props.liability.interest_rate ?? '') as number | string,
    minimum_payment: props.liability.minimum_payment as number | null,
    due_date_day: (props.liability.due_date_day ?? '') as number | string,
    start_date: toDateInputValue(props.liability.start_date),
    end_date: toDateInputValue(props.liability.end_date),
    remaining_terms: (props.liability.remaining_terms ?? '') as number | string,
    is_active: Boolean(props.liability.is_active),
    notes: props.liability.notes || '',
});

const parseOptionalNumber = (val: number | string | null | undefined) => {
    if (val === '' || val === null || val === undefined) {
        return null;
    }

    const parsed = Number(val);

    return Number.isFinite(parsed) ? parsed : null;
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        current_balance: data.current_balance ?? 0,
        credit_limit: isCreditCard.value ? data.credit_limit : null,
        interest_rate: isCreditCard.value
            ? null
            : parseOptionalNumber(data.interest_rate),
        remaining_terms: isCreditCard.value
            ? null
            : parseOptionalNumber(data.remaining_terms),
        due_date_day: parseOptionalNumber(data.due_date_day),
        start_date: isCreditCard.value ? null : data.start_date || null,
        end_date: isCreditCard.value ? null : data.end_date || null,
    })).put(`/liabilities/${props.liability.id}`);
};
</script>

<template>
    <Head :title="`Edit ${liability.name}`" />

    <div
        class="mx-auto flex h-full w-full max-w-3xl flex-1 flex-col gap-4 p-4 md:p-6 lg:gap-5 lg:p-8"
    >
        <!-- Header -->
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex min-w-0 items-start gap-3">
                <Link
                    :href="`/liabilities/${liability.id}`"
                    aria-label="Kembali ke detail utang"
                    class="mt-0.5 inline-flex size-11 shrink-0 items-center justify-center rounded-lg border bg-card text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none sm:size-9"
                >
                    <ArrowLeft class="size-4" aria-hidden="true" />
                </Link>
                <div class="min-w-0">
                    <h1
                        class="text-2xl font-semibold tracking-tight text-balance"
                    >
                        Edit Utang
                    </h1>
                    <p class="mt-0.5 text-sm text-pretty text-muted-foreground">
                        Perbarui sisa tagihan, limit, jadwal, dan status aktif
                        untuk {{ liability.name }}.
                    </p>
                </div>
            </div>

            <Link
                :href="`/liabilities/${liability.id}`"
                class="inline-flex min-h-11 shrink-0 items-center justify-center gap-1.5 rounded-lg border border-input bg-background px-3.5 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none sm:min-h-9"
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
                    class="flex items-center gap-3 rounded-xl border bg-background p-3.5"
                >
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-xl"
                        :style="getLiabilityTintStyle(typeMeta.tint)"
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
                        <p class="text-xs text-pretty text-muted-foreground">
                            Kategori kewajiban tidak dapat diubah setelah
                            dibuat.
                        </p>
                    </div>
                </div>

                <!-- Name & Currency -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="sm:col-span-2">
                        <label
                            for="edit-liability-name"
                            class="mb-1.5 block text-sm font-medium text-foreground"
                        >
                            Nama Kewajiban / Kartu
                            <span class="text-destructive">*</span>
                        </label>
                        <input
                            id="edit-liability-name"
                            v-model="form.name"
                            type="text"
                            required
                            maxlength="100"
                            :aria-invalid="Boolean(form.errors.name)"
                            class="min-h-11 w-full rounded-lg border border-input bg-background px-3.5 text-base text-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none sm:min-h-10 sm:text-sm"
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
                            for="edit-liability-currency"
                            class="mb-1.5 block text-sm font-medium text-foreground"
                        >
                            Mata Uang
                        </label>
                        <select
                            id="edit-liability-currency"
                            v-model="form.currency"
                            class="min-h-11 w-full rounded-lg border border-input bg-background px-3 text-base text-foreground focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none sm:min-h-10 sm:text-sm"
                        >
                            <option
                                v-for="curr in LIABILITY_CURRENCIES"
                                :key="curr"
                                :value="curr"
                            >
                                {{ curr }}
                            </option>
                            <option
                                v-if="
                                    form.currency &&
                                    !LIABILITY_CURRENCIES.includes(
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
            </div>

            <!-- Section 2: Saldo & Ketentuan -->
            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <h2 class="text-sm font-semibold text-foreground">
                        Saldo &amp; Ketentuan Pembayaran
                    </h2>
                    <p class="mt-0.5 text-xs text-pretty text-muted-foreground">
                        Sesuaikan sisa saldo berjalan, limit kartu, atau sisa
                        tenor cicilan.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label
                            for="edit-liability-current-balance"
                            class="mb-1.5 block text-sm font-medium text-foreground"
                        >
                            Sisa Tagihan Saat Ini
                        </label>
                        <MoneyInput
                            id="edit-liability-current-balance"
                            v-model="form.current_balance"
                            :currency="form.currency"
                            placeholder="0"
                        />
                        <p
                            v-if="form.errors.current_balance"
                            class="mt-1.5 text-xs text-destructive"
                        >
                            {{ form.errors.current_balance }}
                        </p>
                    </div>

                    <div v-if="isCreditCard">
                        <label
                            for="edit-liability-credit-limit"
                            class="mb-1.5 block text-sm font-medium text-foreground"
                        >
                            Limit Kredit
                        </label>
                        <MoneyInput
                            id="edit-liability-credit-limit"
                            v-model="form.credit_limit"
                            :currency="form.currency"
                            placeholder="0"
                        />
                        <p
                            v-if="form.errors.credit_limit"
                            class="mt-1.5 text-xs text-destructive"
                        >
                            {{ form.errors.credit_limit }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="edit-liability-minimum-payment"
                            class="mb-1.5 block text-sm font-medium text-foreground"
                        >
                            {{
                                isCreditCard
                                    ? 'Pembayaran Minimum / Bln'
                                    : 'Cicilan per Bulan'
                            }}
                        </label>
                        <MoneyInput
                            id="edit-liability-minimum-payment"
                            v-model="form.minimum_payment"
                            :currency="form.currency"
                            placeholder="0"
                        />
                        <p
                            v-if="form.errors.minimum_payment"
                            class="mt-1.5 text-xs text-destructive"
                        >
                            {{ form.errors.minimum_payment }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="edit-liability-due-day"
                            class="mb-1.5 block text-sm font-medium text-foreground"
                        >
                            Tanggal Jatuh Tempo (1–31)
                        </label>
                        <input
                            id="edit-liability-due-day"
                            v-model="form.due_date_day"
                            type="number"
                            inputmode="numeric"
                            min="1"
                            max="31"
                            placeholder="cth. 15"
                            class="min-h-11 w-full rounded-lg border border-input bg-background px-3.5 text-base text-foreground tabular-nums focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none sm:min-h-10 sm:text-sm"
                        />
                        <p
                            v-if="form.errors.due_date_day"
                            class="mt-1.5 text-xs text-destructive"
                        >
                            {{ form.errors.due_date_day }}
                        </p>
                    </div>

                    <template v-if="!isCreditCard">
                        <div>
                            <label
                                for="edit-liability-remaining-terms"
                                class="mb-1.5 block text-sm font-medium text-foreground"
                            >
                                Sisa Tenor (Bulan)
                            </label>
                            <input
                                id="edit-liability-remaining-terms"
                                v-model="form.remaining_terms"
                                type="number"
                                inputmode="numeric"
                                min="0"
                                class="min-h-11 w-full rounded-lg border border-input bg-background px-3.5 text-base text-foreground tabular-nums focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none sm:min-h-10 sm:text-sm"
                            />
                            <p
                                v-if="form.errors.remaining_terms"
                                class="mt-1.5 text-xs text-destructive"
                            >
                                {{ form.errors.remaining_terms }}
                            </p>
                        </div>

                        <div>
                            <label
                                for="edit-liability-interest-rate"
                                class="mb-1.5 block text-sm font-medium text-foreground"
                            >
                                Suku Bunga (% per Tahun)
                            </label>
                            <input
                                id="edit-liability-interest-rate"
                                v-model="form.interest_rate"
                                type="number"
                                inputmode="decimal"
                                step="0.01"
                                min="0"
                                max="100"
                                class="min-h-11 w-full rounded-lg border border-input bg-background px-3.5 text-base text-foreground tabular-nums focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none sm:min-h-10 sm:text-sm"
                            />
                            <p
                                v-if="form.errors.interest_rate"
                                class="mt-1.5 text-xs text-destructive"
                            >
                                {{ form.errors.interest_rate }}
                            </p>
                        </div>

                        <div>
                            <label
                                for="edit-liability-start-date"
                                class="mb-1.5 block text-sm font-medium text-foreground"
                            >
                                Tanggal Mulai
                            </label>
                            <input
                                id="edit-liability-start-date"
                                v-model="form.start_date"
                                type="date"
                                class="min-h-11 w-full rounded-lg border border-input bg-background px-3.5 text-base text-foreground focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none sm:min-h-10 sm:text-sm"
                            />
                            <p
                                v-if="form.errors.start_date"
                                class="mt-1.5 text-xs text-destructive"
                            >
                                {{ form.errors.start_date }}
                            </p>
                        </div>

                        <div>
                            <label
                                for="edit-liability-end-date"
                                class="mb-1.5 block text-sm font-medium text-foreground"
                            >
                                Target Lunas
                            </label>
                            <input
                                id="edit-liability-end-date"
                                v-model="form.end_date"
                                type="date"
                                class="min-h-11 w-full rounded-lg border border-input bg-background px-3.5 text-base text-foreground focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none sm:min-h-10 sm:text-sm"
                            />
                            <p
                                v-if="form.errors.end_date"
                                class="mt-1.5 text-xs text-destructive"
                            >
                                {{ form.errors.end_date }}
                            </p>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Section 3: Catatan & Status Aktif -->
            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <label
                        for="edit-liability-notes"
                        class="mb-1.5 flex items-center justify-between text-sm font-medium text-foreground"
                    >
                        <span>Catatan</span>
                        <span class="text-xs font-normal text-muted-foreground">
                            Opsional
                        </span>
                    </label>
                    <textarea
                        id="edit-liability-notes"
                        v-model="form.notes"
                        rows="3"
                        maxlength="1000"
                        placeholder="Tambahkan catatan kontrak, nomor rekening virtual, atau rencana pelunasan..."
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
                            id="active-liability-label"
                            for="active-liability-switch"
                            class="block cursor-pointer text-sm font-medium text-foreground"
                        >
                            Kewajiban Aktif
                        </label>
                        <p
                            class="mt-0.5 text-xs text-pretty text-muted-foreground"
                        >
                            Kewajiban aktif dihitung dalam total utang dan
                            kekayaan bersih. Nonaktifkan jika sudah lunas dan
                            ditutup.
                        </p>
                    </div>
                    <button
                        id="active-liability-switch"
                        type="button"
                        role="switch"
                        :aria-checked="form.is_active"
                        aria-labelledby="active-liability-label"
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

            <!-- Footer Actions -->
            <div
                class="flex flex-col-reverse gap-2 px-5 py-4 sm:flex-row sm:items-center sm:justify-end sm:px-6"
            >
                <Link
                    :href="`/liabilities/${liability.id}`"
                    class="inline-flex min-h-11 items-center justify-center rounded-lg border border-input bg-background px-4 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none sm:min-h-10"
                >
                    Batal
                </Link>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex min-h-11 items-center justify-center gap-1.5 rounded-lg bg-primary px-5 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:ring-offset-2 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50 sm:min-h-10"
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
