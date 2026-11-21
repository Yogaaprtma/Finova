<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowLeft,
    ArrowRight,
    Edit,
    FileText,
    Loader2,
    Plus,
    TrendingDown,
    TrendingUp,
} from '@lucide/vue';
import dayjs from 'dayjs';
import 'dayjs/locale/id';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import MoneyDisplay from '@/components/MoneyDisplay.vue';
import TransactionRow from '@/components/TransactionRow.vue';
import { getAccountTintStyle, getAccountTypeMeta } from '@/lib/accounts';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Rekening', href: '/accounts' },
            { title: 'Detail Rekening', href: '#' },
        ],
    },
});

const props = defineProps<{
    account: any;
    transactions: any;
}>();

const typeMeta = computed(() => getAccountTypeMeta(props.account.type));

const currentBalance = computed(() =>
    Number(props.account.current_balance || 0),
);
const initialBalance = computed(() =>
    Number(props.account.initial_balance || 0),
);
const balanceDelta = computed(
    () => currentBalance.value - initialBalance.value,
);

const transactionList = computed<any[]>(() =>
    Array.isArray(props.transactions?.data) ? props.transactions.data : [],
);

const transactionTotal = computed(() =>
    typeof props.transactions?.total === 'number'
        ? props.transactions.total
        : transactionList.value.length,
);

const confirmingTransaction = ref<any | null>(null);
const isDeleting = ref(false);

const formatDate = (dateStr?: string) => {
    if (!dateStr) {
        return '—';
    }

    return dayjs(dateStr).locale('id').format('D MMM YYYY');
};

const formatPaginationLabel = (label: string) =>
    label
        .replace(/&laquo;\s*Previous/gi, '« Sebelumnya')
        .replace(/Next\s*&raquo;/gi, 'Berikutnya »')
        .replace(/&laquo;\s*/g, '« ')
        .replace(/\s*&raquo;/g, ' »')
        .replace(/&amp;/g, '&')
        .trim();

const handleEdit = (transaction: any) => {
    router.get(`/transactions/${transaction.id}/edit`);
};

const promptDeleteTransaction = (transaction: any) => {
    confirmingTransaction.value = transaction;
};

const closeDeleteModal = () => {
    if (!isDeleting.value) {
        confirmingTransaction.value = null;
    }
};

const executeDeleteTransaction = () => {
    if (!confirmingTransaction.value || isDeleting.value) {
        return;
    }

    isDeleting.value = true;
    router.delete(`/transactions/${confirmingTransaction.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            confirmingTransaction.value = null;
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
};

const onKeydown = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && confirmingTransaction.value) {
        closeDeleteModal();
    }
};

onMounted(() => window.addEventListener('keydown', onKeydown));
onUnmounted(() => window.removeEventListener('keydown', onKeydown));
</script>

<template>
    <Head :title="account.name" />

    <div
        class="mx-auto flex h-full w-full max-w-6xl min-w-0 flex-1 flex-col gap-4 overflow-x-clip p-4 md:p-6 lg:gap-5 lg:p-8"
    >
        <!-- Header -->
        <div
            class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex min-w-0 items-center gap-3">
                <Link
                    href="/accounts"
                    aria-label="Kembali ke daftar rekening"
                    class="inline-flex size-10 shrink-0 items-center justify-center rounded-lg border bg-card text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none sm:size-9"
                >
                    <ArrowLeft class="size-4" aria-hidden="true" />
                </Link>

                <span
                    class="hidden size-10 shrink-0 items-center justify-center rounded-xl sm:flex"
                    :style="getAccountTintStyle(typeMeta.tint)"
                >
                    <component
                        :is="typeMeta.icon"
                        class="size-5"
                        aria-hidden="true"
                    />
                </span>

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1
                            class="max-w-full truncate text-xl font-semibold tracking-tight sm:text-2xl"
                        >
                            {{ account.name }}
                        </h1>
                        <span
                            class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-medium"
                            :class="
                                account.is_active
                                    ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400'
                                    : 'bg-muted text-muted-foreground'
                            "
                        >
                            <span
                                class="size-1.5 rounded-full"
                                :class="
                                    account.is_active
                                        ? 'bg-emerald-500'
                                        : 'bg-muted-foreground/60'
                                "
                            ></span>
                            {{ account.is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>
                    <p class="mt-0.5 text-sm text-muted-foreground">
                        {{ typeMeta.label }} · {{ account.currency }}
                    </p>
                </div>
            </div>

            <div
                class="grid w-full grid-cols-2 gap-2 sm:flex sm:w-auto sm:shrink-0"
            >
                <Link
                    :href="`/accounts/${account.id}/edit`"
                    class="inline-flex h-11 items-center justify-center gap-1.5 rounded-lg border border-input bg-background px-4 text-sm font-medium whitespace-nowrap text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none active:scale-[0.98] sm:h-10"
                >
                    <Edit class="size-4" aria-hidden="true" />
                    Edit
                </Link>
                <Link
                    :href="`/transactions/create?account_id=${account.id}`"
                    class="inline-flex h-11 items-center justify-center gap-1.5 rounded-lg bg-primary px-4 text-sm font-medium whitespace-nowrap text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none active:scale-[0.98] sm:h-10"
                >
                    <Plus class="size-4" aria-hidden="true" />
                    Transaksi
                </Link>
            </div>
        </div>

        <!-- Main Layout -->
        <div class="grid min-w-0 grid-cols-1 gap-4 lg:grid-cols-12 lg:gap-5">
            <!-- Left Column: Balance & Details -->
            <div class="min-w-0 space-y-4 lg:col-span-4">
                <section
                    aria-label="Ringkasan rekening"
                    class="min-w-0 divide-y overflow-hidden rounded-xl border bg-card"
                >
                    <!-- Balance Block -->
                    <div class="p-5 sm:p-6">
                        <div class="flex items-center justify-between gap-2">
                            <span
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Saldo Saat Ini
                            </span>
                            <span
                                class="rounded-md border bg-muted/40 px-2 py-0.5 text-[11px] font-medium text-muted-foreground"
                            >
                                {{ account.currency }}
                            </span>
                        </div>

                        <div
                            class="mt-2 text-3xl font-semibold tracking-tight break-words tabular-nums"
                        >
                            <MoneyDisplay
                                :amount="currentBalance"
                                :currency="account.currency"
                            />
                        </div>

                        <div
                            v-if="balanceDelta !== 0"
                            class="mt-3 flex flex-wrap items-center gap-1.5 text-xs text-muted-foreground"
                        >
                            <span
                                class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-medium tabular-nums"
                                :class="
                                    balanceDelta > 0
                                        ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400'
                                        : 'bg-rose-500/10 text-rose-700 dark:text-rose-400'
                                "
                            >
                                <component
                                    :is="
                                        balanceDelta > 0
                                            ? TrendingUp
                                            : TrendingDown
                                    "
                                    class="size-3"
                                    aria-hidden="true"
                                />
                                <MoneyDisplay
                                    :amount="balanceDelta"
                                    :currency="account.currency"
                                    show-sign
                                />
                            </span>
                            <span>vs saldo awal</span>
                        </div>
                    </div>

                    <!-- Hairline Details List -->
                    <dl class="divide-y">
                        <div
                            class="flex items-center justify-between gap-3 px-5 py-3 text-sm sm:px-6"
                        >
                            <dt class="text-muted-foreground">Tipe Rekening</dt>
                            <dd
                                class="flex items-center gap-1.5 font-medium text-foreground"
                            >
                                <span
                                    class="flex size-5 shrink-0 items-center justify-center rounded-md"
                                    :style="getAccountTintStyle(typeMeta.tint)"
                                >
                                    <component
                                        :is="typeMeta.icon"
                                        class="size-3"
                                        aria-hidden="true"
                                    />
                                </span>
                                {{ typeMeta.shortLabel }}
                            </dd>
                        </div>

                        <div
                            class="flex items-center justify-between gap-3 px-5 py-3 text-sm sm:px-6"
                        >
                            <dt class="text-muted-foreground">Saldo Awal</dt>
                            <dd class="font-medium tabular-nums">
                                <MoneyDisplay
                                    :amount="initialBalance"
                                    :currency="account.currency"
                                />
                            </dd>
                        </div>

                        <div
                            class="flex items-center justify-between gap-3 px-5 py-3 text-sm sm:px-6"
                        >
                            <dt class="text-muted-foreground">Dibuat</dt>
                            <dd
                                class="font-medium text-foreground tabular-nums"
                            >
                                {{ formatDate(account.created_at) }}
                            </dd>
                        </div>
                    </dl>

                    <!-- Notes -->
                    <div v-if="account.description" class="px-5 py-4 sm:px-6">
                        <span class="text-xs font-medium text-muted-foreground">
                            Catatan
                        </span>
                        <p
                            class="mt-1.5 text-sm leading-relaxed break-words text-foreground"
                        >
                            {{ account.description }}
                        </p>
                    </div>
                </section>
            </div>

            <!-- Right Column: Transaction History -->
            <div class="min-w-0 lg:col-span-8">
                <section
                    aria-label="Riwayat transaksi"
                    class="flex min-w-0 flex-col overflow-hidden rounded-xl border bg-card"
                >
                    <div
                        class="flex items-center justify-between gap-3 border-b px-5 py-4 sm:px-6"
                    >
                        <div class="flex min-w-0 items-center gap-2">
                            <h2 class="truncate text-base font-semibold">
                                Riwayat Transaksi
                            </h2>
                            <span
                                v-if="transactionTotal > 0"
                                class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground tabular-nums"
                            >
                                {{ transactionTotal }}
                            </span>
                        </div>

                        <Link
                            :href="`/transactions?account_id=${account.id}`"
                            class="-mr-2 inline-flex h-9 shrink-0 items-center gap-1 rounded-md px-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                        >
                            Lihat semua
                            <ArrowRight class="size-3.5" aria-hidden="true" />
                        </Link>
                    </div>

                    <div
                        v-if="transactionList.length > 0"
                        class="min-w-0 divide-y"
                    >
                        <TransactionRow
                            v-for="tx in transactionList"
                            :key="tx.id"
                            :transaction="tx"
                            :fallback-currency="account.currency"
                            @edit="handleEdit"
                            @delete="promptDeleteTransaction"
                        />
                    </div>

                    <div
                        v-else
                        class="flex flex-col items-center justify-center px-5 py-12 text-center"
                    >
                        <div
                            class="mb-3 flex size-11 items-center justify-center rounded-xl bg-muted text-muted-foreground"
                        >
                            <FileText class="size-5" aria-hidden="true" />
                        </div>
                        <p class="text-sm font-medium text-foreground">
                            Belum ada transaksi
                        </p>
                        <p class="mt-1 max-w-xs text-xs text-muted-foreground">
                            Transaksi yang terhubung dengan {{ account.name }}
                            akan muncul di sini.
                        </p>
                        <Link
                            :href="`/transactions/create?account_id=${account.id}`"
                            class="mt-4 inline-flex h-10 items-center gap-1.5 rounded-lg border border-input bg-background px-4 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                        >
                            <Plus class="size-4" aria-hidden="true" />
                            Catat Transaksi
                        </Link>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="
                            transactions?.links && transactions.links.length > 3
                        "
                        class="flex flex-col gap-3 border-t bg-muted/20 px-5 py-3.5 sm:flex-row sm:items-center sm:justify-between sm:px-6"
                    >
                        <span
                            v-if="
                                transactions.from != null &&
                                transactions.to != null &&
                                transactions.total != null
                            "
                            class="text-xs text-muted-foreground tabular-nums"
                        >
                            Menampilkan {{ transactions.from }}–{{
                                transactions.to
                            }}
                            dari
                            {{ transactions.total }}
                        </span>
                        <div class="flex flex-wrap items-center gap-1">
                            <template
                                v-for="(link, index) in transactions.links"
                                :key="index"
                            >
                                <span
                                    v-if="!link.url"
                                    class="inline-flex h-8 min-w-8 items-center justify-center rounded-md px-2.5 text-xs font-medium text-muted-foreground/50"
                                >
                                    {{ formatPaginationLabel(link.label) }}
                                </span>
                                <Link
                                    v-else
                                    :href="link.url"
                                    preserve-scroll
                                    class="inline-flex h-8 min-w-8 items-center justify-center rounded-md px-2.5 text-xs font-medium transition-colors focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                                    :class="
                                        link.active
                                            ? 'bg-primary text-primary-foreground'
                                            : 'border bg-background text-foreground hover:bg-accent'
                                    "
                                >
                                    {{ formatPaginationLabel(link.label) }}
                                </Link>
                            </template>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <!-- Delete Transaction Confirmation Modal -->
        <div
            v-if="confirmingTransaction"
            class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-4 backdrop-blur-[2px] sm:items-center"
            role="dialog"
            aria-modal="true"
            aria-labelledby="delete-tx-title"
            @click.self="closeDeleteModal"
        >
            <div
                class="w-full max-w-md rounded-xl border bg-card p-5 shadow-lg sm:p-6"
            >
                <div class="flex items-start gap-3">
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-full bg-destructive/10 text-destructive"
                    >
                        <AlertTriangle class="size-5" aria-hidden="true" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3
                            id="delete-tx-title"
                            class="text-base font-semibold text-foreground"
                        >
                            Hapus Transaksi
                        </h3>
                        <p
                            class="mt-1 text-sm leading-relaxed text-muted-foreground"
                        >
                            Apakah Anda yakin ingin menghapus transaksi
                            <strong class="font-medium text-foreground">
                                {{ confirmingTransaction.description || 'ini' }}
                            </strong>
                            ? Saldo rekening akan dihitung ulang secara
                            otomatis.
                        </p>
                    </div>
                </div>

                <div
                    class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"
                >
                    <button
                        type="button"
                        :disabled="isDeleting"
                        @click="closeDeleteModal"
                        class="inline-flex h-10 items-center justify-center rounded-lg border border-input bg-background px-4 text-sm font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none disabled:opacity-50"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        :disabled="isDeleting"
                        @click="executeDeleteTransaction"
                        class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-destructive px-4 text-sm font-medium text-white transition-colors hover:bg-destructive/90 focus-visible:ring-2 focus-visible:ring-destructive/40 focus-visible:outline-none disabled:opacity-50"
                    >
                        <Loader2
                            v-if="isDeleting"
                            class="size-4 animate-spin"
                            aria-hidden="true"
                        />
                        Hapus Transaksi
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
