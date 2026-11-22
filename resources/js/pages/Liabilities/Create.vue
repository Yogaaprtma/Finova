<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Check, Loader2, Plus } from '@lucide/vue';
import { computed } from 'vue';
import MoneyDisplay from '@/components/MoneyDisplay.vue';
import MoneyInput from '@/components/MoneyInput.vue';
import {
    getCreditUtilization,
    getLiabilityTintStyle,
    getLiabilityTypeMeta,
    getPayoffProgress,
    LIABILITY_CURRENCIES,
} from '@/lib/liabilities';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Utang', href: '/liabilities' },
            { title: 'Tambah Utang', href: '/liabilities/create' },
        ],
    },
});

interface LiabilityTypeOption {
    value: string;
    label: string;
    color: string;
    description: string;
}

const props = defineProps<{
    liabilityTypes?: LiabilityTypeOption[];
    accounts?: Array<{
        id: string;
        name: string;
        type: string;
        current_balance: number;
    }>;
}>();

const fallbackTypes: LiabilityTypeOption[] = [
    {
        value: 'credit_card',
        label: 'Credit Card',
        color: '#EF4444',
        description: '',
    },
    {
        value: 'personal_loan',
        label: 'Personal Loan',
        color: '#F97316',
        description: '',
    },
    {
        value: 'installment',
        label: 'Installment / Cicilan',
        color: '#8B5CF6',
        description: '',
    },
    {
        value: 'other_debt',
        label: 'Other Debt',
        color: '#64748B',
        description: '',
    },
];

const typeOptions = computed(() => {
    const source = props.liabilityTypes?.length
        ? props.liabilityTypes
        : fallbackTypes;

    return source.map((item) => ({
        ...item,
        meta: getLiabilityTypeMeta(item.value),
    }));
});

const form = useForm({
    type: typeOptions.value[0]?.value || 'credit_card',
    name: '',
    currency: 'IDR',
    initial_balance: null as number | null,
    current_balance: null as number | null,
    credit_limit: null as number | null,
    interest_rate: '' as number | string,
    minimum_payment: null as number | null,
    due_date_day: '' as number | string,
    start_date: '',
    end_date: '',
    remaining_terms: '' as number | string,
    notes: '',
});

const selectedMeta = computed(() => getLiabilityTypeMeta(form.type));
const isCreditCard = computed(() => form.type === 'credit_card');

const effectiveCurrentBalance = computed(
    () => form.current_balance ?? form.initial_balance ?? 0,
);

const previewUtilization = computed(() =>
    getCreditUtilization(effectiveCurrentBalance.value, form.credit_limit),
);

const previewAvailableCredit = computed(() => {
    if (!form.credit_limit) {
        return 0;
    }

    return Math.max(0, form.credit_limit - effectiveCurrentBalance.value);
});

const previewPaidPct = computed(() =>
    getPayoffProgress(form.initial_balance, effectiveCurrentBalance.value),
);

const parseOptionalNumber = (val: number | string | null | undefined) => {
    if (val === '' || val === null || val === undefined) {
        return null;
    }

    const parsed = Number(val);

    return Number.isFinite(parsed) ? parsed : null;
};

const submit = () => {
    form.transform((data) => {
        const initial = data.initial_balance ?? 0;
        const current = data.current_balance ?? initial;

        return {
            ...data,
            initial_balance: initial,
            current_balance: current,
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
        };
    }).post('/liabilities');
};
</script>

<template>
    <Head title="Tambah Utang Baru" />

    <div
        class="mx-auto flex h-full w-full max-w-3xl flex-1 flex-col gap-4 p-4 md:p-6 lg:gap-5 lg:p-8"
    >
        <!-- Header -->
        <div class="flex items-start gap-3">
            <Link
                href="/liabilities"
                aria-label="Kembali ke daftar utang"
                class="mt-0.5 inline-flex size-11 shrink-0 items-center justify-center rounded-lg border bg-card text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none sm:size-9"
            >
                <ArrowLeft class="size-4" aria-hidden="true" />
            </Link>
            <div class="min-w-0">
                <h1 class="text-2xl font-semibold tracking-tight text-balance">
                    Tambah Utang Baru
                </h1>
                <p class="mt-0.5 text-sm text-pretty text-muted-foreground">
                    Catat kartu kredit, pinjaman, atau cicilan agar saldo dan
                    jadwal pembayaran Anda terpantau jelas.
                </p>
            </div>
        </div>

        <!-- Form Card -->
        <form
            @submit.prevent="submit"
            class="divide-y rounded-xl border bg-card"
        >
            <!-- Section 1: Jenis & Identitas -->
            <div class="space-y-5 p-5 sm:p-6">
                <div>
                    <h2 class="text-sm font-semibold text-foreground">
                        Jenis &amp; Identitas Kewajiban
                    </h2>
                    <p class="mt-0.5 text-xs text-pretty text-muted-foreground">
                        Pilih kategori utang untuk menampilkan kolom limit,
                        tenor, atau bunga yang relevan.
                    </p>
                </div>

                <!-- Choice Cards using semantic radio inputs + :has(:checked) -->
                <fieldset>
                    <legend class="mb-2 text-sm font-medium text-foreground">
                        Kategori Kewajiban
                    </legend>
                    <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-4">
                        <label
                            v-for="opt in typeOptions"
                            :key="opt.value"
                            class="relative flex min-h-[84px] cursor-pointer flex-col items-start justify-between rounded-xl border border-border bg-background p-3 transition-colors hover:bg-accent/50 has-[:checked]:border-primary has-[:checked]:bg-primary/[0.04] has-[:checked]:ring-1 has-[:checked]:ring-primary has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-ring/40"
                        >
                            <input
                                v-model="form.type"
                                type="radio"
                                name="liability_type"
                                :value="opt.value"
                                class="sr-only"
                            />
                            <div
                                class="flex w-full items-center justify-between gap-2"
                            >
                                <span
                                    class="flex size-8 items-center justify-center rounded-lg"
                                    :style="
                                        getLiabilityTintStyle(opt.meta.tint)
                                    "
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
                                {{ opt.meta.shortLabel }}
                            </span>
                        </label>
                    </div>
                    <p
                        id="liability-type-hint"
                        class="mt-2 text-xs text-pretty text-muted-foreground"
                    >
                        {{ selectedMeta.description }}
                    </p>
                    <p
                        v-if="form.errors.type"
                        class="mt-1.5 text-xs text-destructive"
                    >
                        {{ form.errors.type }}
                    </p>
                </fieldset>

                <!-- Nama & Mata Uang -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="sm:col-span-2">
                        <label
                            for="liability-name"
                            class="mb-1.5 block text-sm font-medium text-foreground"
                        >
                            Nama Kewajiban / Kartu
                            <span class="text-destructive">*</span>
                        </label>
                        <input
                            id="liability-name"
                            v-model="form.name"
                            type="text"
                            required
                            maxlength="100"
                            :placeholder="selectedMeta.namePlaceholder"
                            :aria-invalid="Boolean(form.errors.name)"
                            :aria-describedby="
                                form.errors.name
                                    ? 'liability-name-error'
                                    : undefined
                            "
                            class="min-h-11 w-full rounded-lg border border-input bg-background px-3.5 text-base text-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none sm:min-h-10 sm:text-sm"
                        />
                        <p
                            v-if="form.errors.name"
                            id="liability-name-error"
                            class="mt-1.5 text-xs text-destructive"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="liability-currency"
                            class="mb-1.5 block text-sm font-medium text-foreground"
                        >
                            Mata Uang
                        </label>
                        <select
                            id="liability-currency"
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
                        Masukkan nilai pokok awal, sisa tagihan saat ini, serta
                        rincian limit atau jadwal cicilan.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label
                            for="liability-initial-balance"
                            class="mb-1.5 block text-sm font-medium text-foreground"
                        >
                            {{
                                isCreditCard
                                    ? 'Saldo Tagihan Awal'
                                    : 'Pokok Utang Awal'
                            }}
                            <span class="text-destructive">*</span>
                        </label>
                        <MoneyInput
                            id="liability-initial-balance"
                            v-model="form.initial_balance"
                            :currency="form.currency"
                            required
                            placeholder="0"
                        />
                        <p class="mt-1.5 text-xs text-muted-foreground">
                            {{
                                isCreditCard
                                    ? 'Total tagihan awal saat pencatatan dimulai.'
                                    : 'Nilai pinjaman atau total cicilan awal sebelum dibayar.'
                            }}
                        </p>
                        <p
                            v-if="form.errors.initial_balance"
                            class="mt-1.5 text-xs text-destructive"
                        >
                            {{ form.errors.initial_balance }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="liability-current-balance"
                            class="mb-1.5 flex items-center justify-between text-sm font-medium text-foreground"
                        >
                            <span>Sisa Tagihan Saat Ini</span>
                            <span
                                class="text-xs font-normal text-muted-foreground"
                            >
                                Opsional
                            </span>
                        </label>
                        <MoneyInput
                            id="liability-current-balance"
                            v-model="form.current_balance"
                            :currency="form.currency"
                            placeholder="Otomatis sama dengan awal"
                        />
                        <p class="mt-1.5 text-xs text-muted-foreground">
                            Kosongkan jika sama dengan pokok awal.
                        </p>
                        <p
                            v-if="form.errors.current_balance"
                            class="mt-1.5 text-xs text-destructive"
                        >
                            {{ form.errors.current_balance }}
                        </p>
                    </div>

                    <!-- Credit Card Limit -->
                    <div v-if="isCreditCard">
                        <label
                            for="liability-credit-limit"
                            class="mb-1.5 flex items-center justify-between text-sm font-medium text-foreground"
                        >
                            <span>Limit Kredit</span>
                            <span
                                class="text-xs font-normal text-muted-foreground"
                            >
                                Opsional
                            </span>
                        </label>
                        <MoneyInput
                            id="liability-credit-limit"
                            v-model="form.credit_limit"
                            :currency="form.currency"
                            placeholder="cth. 20.000.000"
                        />
                        <p
                            v-if="form.errors.credit_limit"
                            class="mt-1.5 text-xs text-destructive"
                        >
                            {{ form.errors.credit_limit }}
                        </p>
                    </div>

                    <!-- Minimum Payment / Monthly Installment -->
                    <div>
                        <label
                            for="liability-minimum-payment"
                            class="mb-1.5 flex items-center justify-between text-sm font-medium text-foreground"
                        >
                            <span>
                                {{
                                    isCreditCard
                                        ? 'Pembayaran Minimum / Bln'
                                        : 'Cicilan per Bulan'
                                }}
                            </span>
                            <span
                                class="text-xs font-normal text-muted-foreground"
                            >
                                Opsional
                            </span>
                        </label>
                        <MoneyInput
                            id="liability-minimum-payment"
                            v-model="form.minimum_payment"
                            :currency="form.currency"
                            placeholder="cth. 500.000"
                        />
                        <p
                            v-if="form.errors.minimum_payment"
                            class="mt-1.5 text-xs text-destructive"
                        >
                            {{ form.errors.minimum_payment }}
                        </p>
                    </div>

                    <!-- Due Date Day (All Types) -->
                    <div>
                        <label
                            for="liability-due-day"
                            class="mb-1.5 flex items-center justify-between text-sm font-medium text-foreground"
                        >
                            <span>Tanggal Jatuh Tempo (1–31)</span>
                            <span
                                class="text-xs font-normal text-muted-foreground"
                            >
                                Opsional
                            </span>
                        </label>
                        <input
                            id="liability-due-day"
                            v-model="form.due_date_day"
                            type="number"
                            inputmode="numeric"
                            min="1"
                            max="31"
                            placeholder="cth. 15 atau 25"
                            :aria-invalid="Boolean(form.errors.due_date_day)"
                            class="min-h-11 w-full rounded-lg border border-input bg-background px-3.5 text-base text-foreground tabular-nums placeholder:text-muted-foreground focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none sm:min-h-10 sm:text-sm"
                        />
                        <p
                            v-if="form.errors.due_date_day"
                            class="mt-1.5 text-xs text-destructive"
                        >
                            {{ form.errors.due_date_day }}
                        </p>
                    </div>

                    <!-- Non-Credit-Card Fields -->
                    <template v-if="!isCreditCard">
                        <div>
                            <label
                                for="liability-remaining-terms"
                                class="mb-1.5 flex items-center justify-between text-sm font-medium text-foreground"
                            >
                                <span>Sisa Tenor (Bulan)</span>
                                <span
                                    class="text-xs font-normal text-muted-foreground"
                                >
                                    Opsional
                                </span>
                            </label>
                            <input
                                id="liability-remaining-terms"
                                v-model="form.remaining_terms"
                                type="number"
                                inputmode="numeric"
                                min="0"
                                placeholder="cth. 12"
                                :aria-invalid="
                                    Boolean(form.errors.remaining_terms)
                                "
                                class="min-h-11 w-full rounded-lg border border-input bg-background px-3.5 text-base text-foreground tabular-nums placeholder:text-muted-foreground focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none sm:min-h-10 sm:text-sm"
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
                                for="liability-interest-rate"
                                class="mb-1.5 flex items-center justify-between text-sm font-medium text-foreground"
                            >
                                <span>Suku Bunga (% per Tahun)</span>
                                <span
                                    class="text-xs font-normal text-muted-foreground"
                                >
                                    Opsional
                                </span>
                            </label>
                            <input
                                id="liability-interest-rate"
                                v-model="form.interest_rate"
                                type="number"
                                inputmode="decimal"
                                step="0.01"
                                min="0"
                                max="100"
                                placeholder="cth. 12.5"
                                :aria-invalid="
                                    Boolean(form.errors.interest_rate)
                                "
                                class="min-h-11 w-full rounded-lg border border-input bg-background px-3.5 text-base text-foreground tabular-nums placeholder:text-muted-foreground focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none sm:min-h-10 sm:text-sm"
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
                                for="liability-start-date"
                                class="mb-1.5 flex items-center justify-between text-sm font-medium text-foreground"
                            >
                                <span>Tanggal Mulai</span>
                                <span
                                    class="text-xs font-normal text-muted-foreground"
                                >
                                    Opsional
                                </span>
                            </label>
                            <input
                                id="liability-start-date"
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
                                for="liability-end-date"
                                class="mb-1.5 flex items-center justify-between text-sm font-medium text-foreground"
                            >
                                <span>Target Lunas</span>
                                <span
                                    class="text-xs font-normal text-muted-foreground"
                                >
                                    Opsional
                                </span>
                            </label>
                            <input
                                id="liability-end-date"
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

                <!-- Live Preview Strip -->
                <div
                    class="@container overflow-hidden rounded-xl border bg-border"
                >
                    <div class="grid grid-cols-1 gap-px @md:grid-cols-3">
                        <div class="bg-background px-4 py-3">
                            <p class="text-xs text-muted-foreground">
                                Sisa Tagihan Tercatat
                            </p>
                            <p
                                class="mt-0.5 text-sm font-semibold text-rose-600 tabular-nums dark:text-rose-400"
                            >
                                <MoneyDisplay
                                    :amount="effectiveCurrentBalance"
                                    :currency="form.currency"
                                />
                            </p>
                        </div>

                        <div
                            v-if="isCreditCard"
                            class="bg-background px-4 py-3"
                        >
                            <p class="text-xs text-muted-foreground">
                                Rasio Pemakaian Limit
                            </p>
                            <p
                                class="mt-0.5 text-sm font-semibold tabular-nums"
                            >
                                {{
                                    form.credit_limit
                                        ? `${previewUtilization}%`
                                        : '—'
                                }}
                            </p>
                        </div>
                        <div v-else class="bg-background px-4 py-3">
                            <p class="text-xs text-muted-foreground">
                                Progres Terlunasi
                            </p>
                            <p
                                class="mt-0.5 text-sm font-semibold text-emerald-600 tabular-nums dark:text-emerald-400"
                            >
                                {{ previewPaidPct }}%
                            </p>
                        </div>

                        <div
                            v-if="isCreditCard"
                            class="bg-background px-4 py-3"
                        >
                            <p class="text-xs text-muted-foreground">
                                Sisa Limit Tersedia
                            </p>
                            <p
                                class="mt-0.5 text-sm font-semibold tabular-nums"
                            >
                                <MoneyDisplay
                                    v-if="form.credit_limit"
                                    :amount="previewAvailableCredit"
                                    :currency="form.currency"
                                />
                                <template v-else>—</template>
                            </p>
                        </div>
                        <div v-else class="bg-background px-4 py-3">
                            <p class="text-xs text-muted-foreground">
                                Cicilan per Bulan
                            </p>
                            <p
                                class="mt-0.5 text-sm font-semibold tabular-nums"
                            >
                                <MoneyDisplay
                                    v-if="form.minimum_payment"
                                    :amount="form.minimum_payment"
                                    :currency="form.currency"
                                />
                                <template v-else>—</template>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Catatan -->
            <div class="space-y-4 p-5 sm:p-6">
                <div>
                    <label
                        for="liability-notes"
                        class="mb-1.5 flex items-center justify-between text-sm font-medium text-foreground"
                    >
                        <span>Catatan</span>
                        <span class="text-xs font-normal text-muted-foreground">
                            Opsional
                        </span>
                    </label>
                    <textarea
                        id="liability-notes"
                        v-model="form.notes"
                        rows="3"
                        maxlength="1000"
                        placeholder="Tambahkan nomor kontrak, rincian pemberi pinjaman, atau strategi pelunasan..."
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

            <!-- Footer Actions -->
            <div
                class="flex flex-col-reverse gap-2 px-5 py-4 sm:flex-row sm:items-center sm:justify-end sm:px-6"
            >
                <Link
                    href="/liabilities"
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
                    <Plus v-else class="size-4" aria-hidden="true" />
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Utang' }}
                </button>
            </div>
        </form>
    </div>
</template>
