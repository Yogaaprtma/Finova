<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowDownLeft,
    ArrowLeft,
    ArrowUpRight,
    CheckCircle2,
    Clock,
    Loader2,
    Pencil,
    Plus,
    Trash2,
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
    formatLiabilityDate,
    getCreditUtilization,
    getLiabilityTintStyle,
    getLiabilityTypeMeta,
    getPayoffProgress,
    getUtilizationStatus,
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
        transactions: Array<{
            id: string;
            type: 'payment' | 'increase';
            amount: number;
            created_at: string;
            transaction?: {
                id: string;
                description: string;
                notes: string | null;
                date: string;
            };
        }>;
    };
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
            { title: 'Utang', href: '/liabilities' },
            { title: 'Detail Utang', href: '#' },
        ],
    },
});

const typeMeta = computed(() => getLiabilityTypeMeta(props.liability.type));
const isCreditCard = computed(() => props.liability.type === 'credit_card');

const availableCredit = computed(() => {
    if (!props.liability.credit_limit) {
        return null;
    }

    return Math.max(
        0,
        props.liability.credit_limit - props.liability.current_balance,
    );
});

const utilizationPct = computed(() =>
    getCreditUtilization(
        props.liability.current_balance,
        props.liability.credit_limit,
    ),
);

const utilizationStatus = computed(() =>
    getUtilizationStatus(utilizationPct.value),
);

const payoffPct = computed(() =>
    getPayoffProgress(
        props.liability.initial_balance,
        props.liability.current_balance,
    ),
);

// Modals state
const showPayModal = ref(false);
const showIncreaseModal = ref(false);
const showDeleteModal = ref(false);
const isDeleting = ref(false);

const todayDate = () => dayjs().format('YYYY-MM-DD');

// Pay Form
const payForm = useForm({
    source_account_id: props.accounts?.[0]?.id || '',
    amount: (props.liability.minimum_payment ||
        props.liability.current_balance ||
        null) as number | null,
    date: todayDate(),
    description: `Pembayaran ${props.liability.name}`,
    notes: '',
});

const openPayModal = () => {
    payForm.clearErrors();
    payForm.source_account_id = props.accounts?.[0]?.id || '';
    payForm.amount =
        props.liability.minimum_payment &&
        props.liability.minimum_payment <= props.liability.current_balance
            ? props.liability.minimum_payment
            : props.liability.current_balance || null;
    payForm.date = todayDate();
    payForm.description = `Pembayaran ${props.liability.name}`;
    payForm.notes = '';
    showPayModal.value = true;
};

const estimatedBalanceAfterPay = computed(() =>
    Math.max(0, props.liability.current_balance - (payForm.amount || 0)),
);

const submitPay = () => {
    payForm.post(`/liabilities/${props.liability.id}/pay`, {
        preserveScroll: true,
        onSuccess: () => {
            showPayModal.value = false;
        },
    });
};

// Increase Form
const increaseForm = useForm({
    destination_account_id: '',
    amount: null as number | null,
    date: todayDate(),
    description: '',
    notes: '',
});

const openIncreaseModal = () => {
    increaseForm.clearErrors();
    increaseForm.destination_account_id = '';
    increaseForm.amount = null;
    increaseForm.date = todayDate();
    increaseForm.description = isCreditCard.value
        ? `Pemakaian ${props.liability.name}`
        : `Penambahan ${props.liability.name}`;
    increaseForm.notes = '';
    showIncreaseModal.value = true;
};

const estimatedBalanceAfterIncrease = computed(
    () => props.liability.current_balance + (increaseForm.amount || 0),
);

const submitIncrease = () => {
    increaseForm.post(`/liabilities/${props.liability.id}/increase`, {
        preserveScroll: true,
        onSuccess: () => {
            showIncreaseModal.value = false;
        },
    });
};

// Delete
const confirmDelete = () => {
    isDeleting.value = true;
    router.delete(`/liabilities/${props.liability.id}`, {
        onFinish: () => {
            isDeleting.value = false;
        },
    });
};

const transactions = computed(() => props.liability.transactions ?? []);
</script>

<template>
    <Head :title="liability.name" />

    <div
        class="mx-auto flex h-full w-full max-w-5xl flex-1 flex-col gap-5 p-4 md:p-6 lg:gap-6 lg:p-8"
    >
        <!-- Header & Actions -->
        <div
            class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between"
        >
            <div class="flex min-w-0 items-start gap-3">
                <Link
                    href="/liabilities"
                    aria-label="Kembali ke daftar utang"
                    class="mt-0.5 inline-flex size-11 shrink-0 items-center justify-center rounded-lg border bg-card text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none sm:size-9"
                >
                    <ArrowLeft class="size-4" aria-hidden="true" />
                </Link>

                <div
                    class="flex size-11 shrink-0 items-center justify-center rounded-xl sm:size-12"
                    :style="getLiabilityTintStyle(typeMeta.tint)"
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
                            class="text-2xl font-semibold tracking-tight text-balance break-words"
                        >
                            {{ liability.name }}
                        </h1>
                        <span
                            v-if="liability.current_balance === 0"
                            class="inline-flex items-center rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:text-emerald-400"
                        >
                            Lunas
                        </span>
                        <span
                            v-if="!liability.is_active"
                            class="inline-flex items-center rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium text-muted-foreground"
                        >
                            Nonaktif
                        </span>
                    </div>

                    <p class="mt-0.5 text-sm text-muted-foreground">
                        {{ typeMeta.label }} · {{ liability.currency }}
                        <template v-if="liability.due_date_day">
                            · Jatuh tempo tgl {{ liability.due_date_day }}
                        </template>
                    </p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div
                class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:items-center lg:justify-end"
            >
                <button
                    type="button"
                    @click="openPayModal"
                    class="inline-flex min-h-11 items-center justify-center gap-1.5 rounded-lg bg-primary px-3.5 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none sm:min-h-9"
                >
                    <CheckCircle2 class="size-4" aria-hidden="true" />
                    Bayar Tagihan
                </button>

                <button
                    type="button"
                    @click="openIncreaseModal"
                    class="inline-flex min-h-11 items-center justify-center gap-1.5 rounded-lg border border-input bg-card px-3.5 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none sm:min-h-9"
                >
                    <Plus class="size-4" aria-hidden="true" />
                    {{ typeMeta.increaseActionLabel }}
                </button>

                <Link
                    :href="`/liabilities/${liability.id}/edit`"
                    class="inline-flex min-h-11 items-center justify-center gap-1.5 rounded-lg border border-input bg-card px-3.5 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none sm:min-h-9"
                >
                    <Pencil class="size-4" aria-hidden="true" />
                    Edit
                </Link>

                <button
                    type="button"
                    @click="showDeleteModal = true"
                    class="inline-flex min-h-11 items-center justify-center gap-1.5 rounded-lg border border-rose-200 bg-card px-3.5 text-sm font-medium text-rose-600 transition-colors hover:bg-rose-50 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none sm:min-h-9 dark:border-rose-900/60 dark:text-rose-400 dark:hover:bg-rose-950/40"
                >
                    <Trash2 class="size-4" aria-hidden="true" />
                    Hapus
                </button>
            </div>
        </div>

        <!-- Hero Value & 4-Cell Hairline Strip -->
        <section
            aria-label="Ringkasan kewajiban"
            class="@container overflow-hidden rounded-xl border bg-card"
        >
            <div class="p-5 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2
                        class="text-xs font-medium tracking-wider text-muted-foreground uppercase"
                    >
                        Sisa Tagihan Saat Ini
                    </h2>
                    <span
                        v-if="liability.due_date_day"
                        class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium text-muted-foreground tabular-nums"
                    >
                        <Clock class="size-3.5" aria-hidden="true" />
                        Setiap tgl {{ liability.due_date_day }}
                    </span>
                </div>

                <p
                    class="mt-2.5 text-3xl font-semibold tracking-tight tabular-nums sm:text-4xl"
                    :class="
                        liability.current_balance > 0
                            ? 'text-rose-600 dark:text-rose-400'
                            : 'text-emerald-600 dark:text-emerald-400'
                    "
                >
                    <MoneyDisplay
                        :amount="liability.current_balance"
                        :currency="liability.currency"
                    />
                </p>

                <!-- Credit Utilization Progress Bar -->
                <div
                    v-if="isCreditCard && liability.credit_limit"
                    class="mt-4 space-y-1.5"
                >
                    <div
                        class="flex flex-wrap items-center justify-between gap-2 text-xs"
                    >
                        <span class="text-muted-foreground">
                            Pemakaian limit kartu kredit
                        </span>
                        <span class="flex items-center gap-2 tabular-nums">
                            <span
                                class="rounded px-1.5 py-0.5 text-[11px] font-medium"
                                :class="utilizationStatus.badgeClass"
                            >
                                {{ utilizationStatus.label }}
                            </span>
                            <span class="font-semibold text-foreground">
                                {{ utilizationPct }}%
                            </span>
                        </span>
                    </div>
                    <progress
                        class="finova-progress block h-2 w-full"
                        max="100"
                        :value="utilizationPct"
                        :style="{ '--progress-fill': utilizationStatus.color }"
                        :aria-label="`Pemakaian limit kartu kredit ${utilizationPct} persen`"
                    />
                    <p
                        v-if="availableCredit !== null"
                        class="text-xs text-muted-foreground tabular-nums"
                    >
                        Limit tersedia:
                        <span class="font-medium text-foreground">
                            <MoneyDisplay
                                :amount="availableCredit"
                                :currency="liability.currency"
                            />
                        </span>
                        dari
                        <MoneyDisplay
                            :amount="liability.credit_limit"
                            :currency="liability.currency"
                        />
                    </p>
                </div>

                <!-- Loan Payoff Progress Bar -->
                <div
                    v-else-if="liability.initial_balance > 0"
                    class="mt-4 space-y-1.5"
                >
                    <div
                        class="flex flex-wrap items-center justify-between gap-2 text-xs"
                    >
                        <span class="text-muted-foreground">
                            Progres pelunasan dari pokok awal
                        </span>
                        <span
                            class="font-semibold text-emerald-700 tabular-nums dark:text-emerald-400"
                        >
                            {{ payoffPct }}% terlunasi
                        </span>
                    </div>
                    <progress
                        class="finova-progress block h-2 w-full"
                        max="100"
                        :value="payoffPct"
                        :style="{ '--progress-fill': '#10b981' }"
                        :aria-label="`Progres pelunasan ${payoffPct} persen`"
                    />
                </div>
            </div>

            <!-- 4-Cell Hairline Strip -->
            <div
                class="grid grid-cols-2 gap-px border-t bg-border @xl:grid-cols-4"
            >
                <div class="bg-card px-4 py-3.5 sm:px-5">
                    <p class="text-xs font-medium text-muted-foreground">
                        {{ isCreditCard ? 'Tagihan Awal' : 'Pokok Awal' }}
                    </p>
                    <p
                        class="mt-1 text-base font-semibold tracking-tight tabular-nums"
                    >
                        <MoneyDisplay
                            :amount="liability.initial_balance"
                            :currency="liability.currency"
                        />
                    </p>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        Saldo saat dicatat
                    </p>
                </div>

                <div class="bg-card px-4 py-3.5 sm:px-5">
                    <p class="text-xs font-medium text-muted-foreground">
                        {{ isCreditCard ? 'Bayar Minimum' : 'Cicilan Bulanan' }}
                    </p>
                    <p
                        class="mt-1 text-base font-semibold tracking-tight tabular-nums"
                    >
                        <MoneyDisplay
                            v-if="liability.minimum_payment"
                            :amount="liability.minimum_payment"
                            :currency="liability.currency"
                        />
                        <span v-else class="text-muted-foreground">—</span>
                    </p>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        {{
                            liability.due_date_day
                                ? `Jatuh tempo tgl ${liability.due_date_day}`
                                : 'Tanpa jadwal tetap'
                        }}
                    </p>
                </div>

                <div v-if="isCreditCard" class="bg-card px-4 py-3.5 sm:px-5">
                    <p class="text-xs font-medium text-muted-foreground">
                        Limit Kredit
                    </p>
                    <p
                        class="mt-1 text-base font-semibold tracking-tight tabular-nums"
                    >
                        <MoneyDisplay
                            v-if="liability.credit_limit"
                            :amount="liability.credit_limit"
                            :currency="liability.currency"
                        />
                        <span v-else class="text-muted-foreground">—</span>
                    </p>
                    <p
                        class="mt-0.5 text-xs text-muted-foreground tabular-nums"
                    >
                        <template v-if="availableCredit !== null">
                            Sisa:
                            <MoneyDisplay
                                :amount="availableCredit"
                                :currency="liability.currency"
                            />
                        </template>
                        <template v-else>Limit belum diisi</template>
                    </p>
                </div>

                <div v-else class="bg-card px-4 py-3.5 sm:px-5">
                    <p class="text-xs font-medium text-muted-foreground">
                        Sisa Tenor
                    </p>
                    <p
                        class="mt-1 text-base font-semibold tracking-tight tabular-nums"
                    >
                        <template v-if="liability.remaining_terms !== null">
                            {{ liability.remaining_terms }} bulan
                        </template>
                        <span v-else class="text-muted-foreground">—</span>
                    </p>
                    <p
                        class="mt-0.5 text-xs text-muted-foreground tabular-nums"
                    >
                        {{
                            liability.interest_rate
                                ? `Bunga ${liability.interest_rate}% / tahun`
                                : 'Bunga tidak tercatat'
                        }}
                    </p>
                </div>

                <div v-if="isCreditCard" class="bg-card px-4 py-3.5 sm:px-5">
                    <p class="text-xs font-medium text-muted-foreground">
                        Rasio Pemakaian
                    </p>
                    <p
                        class="mt-1 text-base font-semibold tracking-tight tabular-nums"
                    >
                        {{
                            liability.credit_limit ? `${utilizationPct}%` : '—'
                        }}
                    </p>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        {{
                            liability.credit_limit
                                ? `Status: ${utilizationStatus.label}`
                                : 'Atur limit di menu Edit'
                        }}
                    </p>
                </div>

                <div v-else class="bg-card px-4 py-3.5 sm:px-5">
                    <p class="text-xs font-medium text-muted-foreground">
                        Periode Pelunasan
                    </p>
                    <p class="mt-1 text-base font-semibold tracking-tight">
                        {{
                            liability.end_date
                                ? formatLiabilityDate(liability.end_date)
                                : 'Fleksibel'
                        }}
                    </p>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        {{
                            liability.start_date
                                ? `Mulai ${formatLiabilityDate(liability.start_date)}`
                                : 'Tanggal mulai opsional'
                        }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Catatan -->
        <section
            v-if="liability.notes"
            aria-label="Catatan kewajiban"
            class="rounded-xl border bg-card p-5 sm:p-6"
        >
            <h2 class="text-sm font-semibold text-foreground">Catatan</h2>
            <p
                class="mt-2 text-sm leading-relaxed text-pretty whitespace-pre-line text-muted-foreground"
            >
                {{ liability.notes }}
            </p>
        </section>

        <!-- Riwayat Pembayaran & Tagihan -->
        <section
            aria-labelledby="riwayat-utang-heading"
            class="overflow-hidden rounded-xl border bg-card"
        >
            <div
                class="flex flex-wrap items-center justify-between gap-2 border-b px-5 py-4 sm:px-6"
            >
                <div>
                    <h2
                        id="riwayat-utang-heading"
                        class="text-base font-semibold tracking-tight text-balance"
                    >
                        Riwayat Pembayaran &amp; Tagihan
                    </h2>
                    <p class="text-xs text-pretty text-muted-foreground">
                        Rekam jejak pelunasan dan penambahan saldo pada
                        kewajiban ini.
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
                    v-for="item in transactions"
                    :key="item.id"
                    class="flex flex-col justify-between gap-3 px-5 py-4 sm:flex-row sm:items-center sm:px-6"
                >
                    <div class="flex min-w-0 items-start gap-3">
                        <div
                            class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg"
                            :class="
                                item.type === 'payment'
                                    ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                                    : 'bg-rose-500/10 text-rose-600 dark:text-rose-400'
                            "
                        >
                            <component
                                :is="
                                    item.type === 'payment'
                                        ? ArrowDownLeft
                                        : ArrowUpRight
                                "
                                class="size-4"
                                aria-hidden="true"
                            />
                        </div>

                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-foreground">
                                {{
                                    item.transaction?.description ||
                                    (item.type === 'payment'
                                        ? 'Pembayaran Utang'
                                        : 'Penambahan Tagihan')
                                }}
                            </p>
                            <p
                                class="mt-0.5 text-xs text-muted-foreground tabular-nums"
                            >
                                {{
                                    formatLiabilityDate(
                                        item.transaction?.date ||
                                            item.created_at,
                                        !item.transaction?.date,
                                    )
                                }}
                            </p>
                            <p
                                v-if="item.transaction?.notes"
                                class="mt-1 text-xs text-pretty text-muted-foreground"
                            >
                                {{ item.transaction.notes }}
                            </p>
                        </div>
                    </div>

                    <div class="shrink-0 pl-12 text-left sm:pl-0 sm:text-right">
                        <p
                            class="text-sm font-semibold tabular-nums sm:text-base"
                            :class="
                                item.type === 'payment'
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-rose-600 dark:text-rose-400'
                            "
                        >
                            {{ item.type === 'payment' ? '-' : '+'
                            }}<MoneyDisplay
                                :amount="item.amount"
                                :currency="liability.currency"
                            />
                        </p>
                        <p class="text-[11px] text-muted-foreground">
                            {{
                                item.type === 'payment'
                                    ? 'Utang Berkurang'
                                    : 'Utang Bertambah'
                            }}
                        </p>
                    </div>
                </div>
            </div>

            <div v-else class="px-6 py-10 text-center">
                <p class="text-sm font-medium text-foreground">
                    Belum ada riwayat pembayaran atau tagihan
                </p>
                <p class="mt-1 text-xs text-pretty text-muted-foreground">
                    Klik tombol <strong>Bayar Tagihan</strong> di atas saat Anda
                    melakukan pembayaran cicilan atau kartu kredit.
                </p>
            </div>
        </section>

        <!-- Dialog 1: Bayar Tagihan -->
        <Dialog v-model:open="showPayModal">
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Bayar {{ liability.name }}</DialogTitle>
                    <DialogDescription>
                        Catat pembayaran utang. Saldo rekening sumber yang
                        dipilih akan otomatis berkurang sesuai nominal bayar.
                    </DialogDescription>
                </DialogHeader>

                <div
                    v-if="accounts.length === 0"
                    class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200"
                >
                    <p class="font-medium">Belum ada rekening kas/bank aktif</p>
                    <p class="mt-1 text-xs opacity-90">
                        Tambahkan rekening sumber dana terlebih dahulu untuk
                        mencatat pembayaran tagihan.
                    </p>
                    <Link
                        href="/accounts/create"
                        class="mt-3 inline-flex min-h-9 items-center justify-center rounded-lg bg-primary px-3 text-xs font-medium text-primary-foreground"
                    >
                        Tambah Rekening Sekarang
                    </Link>
                </div>

                <form v-else @submit.prevent="submitPay" class="space-y-4">
                    <div>
                        <label
                            for="pay-source-account"
                            class="mb-1.5 block text-sm font-medium text-foreground"
                        >
                            Rekening Sumber Dana
                            <span class="text-destructive">*</span>
                        </label>
                        <select
                            id="pay-source-account"
                            v-model="payForm.source_account_id"
                            required
                            class="min-h-11 w-full rounded-lg border border-input bg-background px-3 text-base text-foreground focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none sm:min-h-10 sm:text-sm"
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
                            v-if="payForm.errors.source_account_id"
                            class="mt-1.5 text-xs text-destructive"
                        >
                            {{ payForm.errors.source_account_id }}
                        </p>
                    </div>

                    <div>
                        <div
                            class="mb-1.5 flex flex-wrap items-center justify-between gap-2"
                        >
                            <label
                                for="pay-amount"
                                class="text-sm font-medium text-foreground"
                            >
                                Nominal Pembayaran
                                <span class="text-destructive">*</span>
                            </label>
                            <div class="flex items-center gap-2 text-xs">
                                <button
                                    v-if="liability.minimum_payment"
                                    type="button"
                                    @click="
                                        payForm.amount =
                                            liability.minimum_payment
                                    "
                                    class="font-medium text-primary hover:underline"
                                >
                                    Minimum
                                </button>
                                <button
                                    v-if="liability.current_balance > 0"
                                    type="button"
                                    @click="
                                        payForm.amount =
                                            liability.current_balance
                                    "
                                    class="font-medium text-primary hover:underline"
                                >
                                    Lunasi Semua
                                </button>
                            </div>
                        </div>
                        <MoneyInput
                            id="pay-amount"
                            v-model="payForm.amount"
                            :currency="liability.currency"
                            required
                            placeholder="0"
                        />
                        <p
                            v-if="payForm.errors.amount"
                            class="mt-1.5 text-xs text-destructive"
                        >
                            {{ payForm.errors.amount }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="pay-date"
                            class="mb-1.5 block text-sm font-medium text-foreground"
                        >
                            Tanggal Pembayaran
                        </label>
                        <input
                            id="pay-date"
                            v-model="payForm.date"
                            type="date"
                            class="min-h-11 w-full rounded-lg border border-input bg-background px-3.5 text-base text-foreground focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none sm:min-h-10 sm:text-sm"
                        />
                        <p
                            v-if="payForm.errors.date"
                            class="mt-1.5 text-xs text-destructive"
                        >
                            {{ payForm.errors.date }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="pay-notes"
                            class="mb-1.5 block text-sm font-medium text-foreground"
                        >
                            Catatan (Opsional)
                        </label>
                        <input
                            id="pay-notes"
                            v-model="payForm.notes"
                            type="text"
                            placeholder="cth. Cicilan bulan November"
                            class="min-h-11 w-full rounded-lg border border-input bg-background px-3.5 text-base text-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none sm:min-h-10 sm:text-sm"
                        />
                        <p
                            v-if="payForm.errors.notes"
                            class="mt-1.5 text-xs text-destructive"
                        >
                            {{ payForm.errors.notes }}
                        </p>
                    </div>

                    <!-- Preview -->
                    <div
                        class="flex items-center justify-between rounded-lg border px-3.5 py-2.5 text-xs"
                    >
                        <span class="text-muted-foreground">
                            Estimasi Sisa Tagihan Baru
                        </span>
                        <span
                            class="font-semibold text-foreground tabular-nums"
                        >
                            <MoneyDisplay
                                :amount="estimatedBalanceAfterPay"
                                :currency="liability.currency"
                            />
                        </span>
                    </div>

                    <DialogFooter>
                        <button
                            type="button"
                            @click="showPayModal = false"
                            class="inline-flex min-h-11 items-center justify-center rounded-lg border border-input bg-background px-4 text-sm font-medium text-foreground hover:bg-accent sm:min-h-9"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="payForm.processing"
                            class="inline-flex min-h-11 items-center justify-center gap-1.5 rounded-lg bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50 sm:min-h-9"
                        >
                            <Loader2
                                v-if="payForm.processing"
                                class="size-4 animate-spin"
                                aria-hidden="true"
                            />
                            {{
                                payForm.processing
                                    ? 'Memproses...'
                                    : 'Konfirmasi Pembayaran'
                            }}
                        </button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Dialog 2: Catat Pemakaian / Tambah Utang -->
        <Dialog v-model:open="showIncreaseModal">
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        {{ typeMeta.increaseActionLabel }} —
                        {{ liability.name }}
                    </DialogTitle>
                    <DialogDescription>
                        Tambahkan saldo utang dari pemakaian kartu baru atau
                        pencairan pinjaman tambahan.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitIncrease" class="space-y-4">
                    <div>
                        <label
                            for="increase-amount"
                            class="mb-1.5 block text-sm font-medium text-foreground"
                        >
                            Nominal Penambahan
                            <span class="text-destructive">*</span>
                        </label>
                        <MoneyInput
                            id="increase-amount"
                            v-model="increaseForm.amount"
                            :currency="liability.currency"
                            required
                            placeholder="0"
                        />
                        <p
                            v-if="increaseForm.errors.amount"
                            class="mt-1.5 text-xs text-destructive"
                        >
                            {{ increaseForm.errors.amount }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="increase-destination"
                            class="mb-1.5 block text-sm font-medium text-foreground"
                        >
                            Cairkan ke Rekening Bank (Opsional)
                        </label>
                        <select
                            id="increase-destination"
                            v-model="increaseForm.destination_account_id"
                            class="min-h-11 w-full rounded-lg border border-input bg-background px-3 text-base text-foreground focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none sm:min-h-10 sm:text-sm"
                        >
                            <option value="">
                                Tidak ada (pemakaian kartu / non-tunai)
                            </option>
                            <option
                                v-for="acc in accounts"
                                :key="acc.id"
                                :value="acc.id"
                            >
                                {{ acc.name }}
                            </option>
                        </select>
                        <p class="mt-1.5 text-xs text-muted-foreground">
                            Jika dipilih, saldo rekening tujuan akan bertambah
                            otomatis sesuai dana cair.
                        </p>
                        <p
                            v-if="increaseForm.errors.destination_account_id"
                            class="mt-1.5 text-xs text-destructive"
                        >
                            {{ increaseForm.errors.destination_account_id }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="increase-date"
                            class="mb-1.5 block text-sm font-medium text-foreground"
                        >
                            Tanggal Transaksi
                        </label>
                        <input
                            id="increase-date"
                            v-model="increaseForm.date"
                            type="date"
                            class="min-h-11 w-full rounded-lg border border-input bg-background px-3.5 text-base text-foreground focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none sm:min-h-10 sm:text-sm"
                        />
                        <p
                            v-if="increaseForm.errors.date"
                            class="mt-1.5 text-xs text-destructive"
                        >
                            {{ increaseForm.errors.date }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="increase-description"
                            class="mb-1.5 block text-sm font-medium text-foreground"
                        >
                            Keterangan
                        </label>
                        <input
                            id="increase-description"
                            v-model="increaseForm.description"
                            type="text"
                            placeholder="cth. Pembelian tiket pesawat atau pencairan KTA"
                            class="min-h-11 w-full rounded-lg border border-input bg-background px-3.5 text-base text-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none sm:min-h-10 sm:text-sm"
                        />
                        <p
                            v-if="increaseForm.errors.description"
                            class="mt-1.5 text-xs text-destructive"
                        >
                            {{ increaseForm.errors.description }}
                        </p>
                    </div>

                    <!-- Preview -->
                    <div
                        class="flex items-center justify-between rounded-lg border px-3.5 py-2.5 text-xs"
                    >
                        <span class="text-muted-foreground">
                            Estimasi Sisa Tagihan Baru
                        </span>
                        <span
                            class="font-semibold text-rose-600 tabular-nums dark:text-rose-400"
                        >
                            <MoneyDisplay
                                :amount="estimatedBalanceAfterIncrease"
                                :currency="liability.currency"
                            />
                        </span>
                    </div>

                    <DialogFooter>
                        <button
                            type="button"
                            @click="showIncreaseModal = false"
                            class="inline-flex min-h-11 items-center justify-center rounded-lg border border-input bg-background px-4 text-sm font-medium text-foreground hover:bg-accent sm:min-h-9"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="increaseForm.processing"
                            class="inline-flex min-h-11 items-center justify-center gap-1.5 rounded-lg bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50 sm:min-h-9"
                        >
                            <Loader2
                                v-if="increaseForm.processing"
                                class="size-4 animate-spin"
                                aria-hidden="true"
                            />
                            {{
                                increaseForm.processing
                                    ? 'Memproses...'
                                    : 'Simpan Penambahan'
                            }}
                        </button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Dialog 3: Hapus Kewajiban -->
        <Dialog v-model:open="showDeleteModal">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Hapus {{ liability.name }}?</DialogTitle>
                    <DialogDescription>
                        Kewajiban ini akan dihapus dari daftar utang dan
                        perhitungan kekayaan bersih Anda. Saldo rekening kas
                        dari pembayaran sebelumnya tidak ikut berubah.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter>
                    <button
                        type="button"
                        @click="showDeleteModal = false"
                        class="inline-flex min-h-11 items-center justify-center rounded-lg border border-input bg-background px-4 text-sm font-medium text-foreground hover:bg-accent sm:min-h-9"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        :disabled="isDeleting"
                        @click="confirmDelete"
                        class="inline-flex min-h-11 items-center justify-center gap-1.5 rounded-lg bg-destructive px-4 text-sm font-medium text-destructive-foreground hover:bg-destructive/90 disabled:opacity-50 sm:min-h-9"
                    >
                        <Loader2
                            v-if="isDeleting"
                            class="size-4 animate-spin"
                            aria-hidden="true"
                        />
                        {{ isDeleting ? 'Menghapus...' : 'Ya, Hapus Utang' }}
                    </button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
