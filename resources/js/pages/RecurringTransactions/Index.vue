<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Repeat,
    PlusCircle,
    Play,
    Edit,
    Trash2,
    Calendar,
    CheckCircle2,
    Clock,
    ArrowUpRight,
    ArrowDownRight,
    Power,
} from '@lucide/vue';
import CategoryIcon from '@/components/CategoryIcon.vue';
import EmptyState from '@/components/EmptyState.vue';
import MoneyDisplay from '@/components/MoneyDisplay.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Recurring Transactions',
                href: '/recurring-transactions',
            },
        ],
    },
});

const props = defineProps<{
    recurringTransactions: Array<{
        id: string;
        name: string;
        type: 'income' | 'expense';
        amount: number;
        currency: string;
        description: string;
        frequency: string;
        day_of_month: number | null;
        day_of_week: number | null;
        start_date: string;
        end_date: string | null;
        is_active: boolean;
        auto_confirm: boolean;
        last_generated_date: string | null;
        next_due_date: string | null;
        category: {
            id: string;
            name: string;
            icon?: string;
            color?: string;
        } | null;
        account: {
            id: string;
            name: string;
            type?: string;
        } | null;
    }>;
}>();

const formatFrequency = (
    freq: string,
    dom: number | null,
    dow: number | null,
) => {
    const days = [
        'Minggu',
        'Senin',
        'Selasa',
        'Rabu',
        'Kamis',
        'Jumat',
        'Sabtu',
    ];

    switch (freq) {
        case 'daily':
            return 'Setiap Hari';
        case 'weekly':
            return dow !== null ? `Setiap ${days[dow]}` : 'Setiap Minggu';
        case 'biweekly':
            return 'Setiap 2 Minggu';
        case 'monthly':
            return dom !== null ? `Setiap tgl ${dom}` : 'Setiap Bulan';
        case 'yearly':
            return 'Setiap Tahun';
        default:
            return freq;
    }
};

const toggleActive = (id: string) => {
    router.post(
        `/recurring-transactions/${id}/toggle`,
        {},
        {
            preserveScroll: true,
        },
    );
};

const generateNow = (id: string, name: string) => {
    if (
        confirm(
            `Jalankan template "${name}" sekarang untuk membuat transaksi baru?`,
        )
    ) {
        router.post(
            `/recurring-transactions/${id}/generate-now`,
            {},
            {
                preserveScroll: true,
            },
        );
    }
};

const deleteRecurring = (id: string, name: string) => {
    if (confirm(`Apakah Anda yakin ingin menghapus template "${name}"?`)) {
        router.delete(`/recurring-transactions/${id}`);
    }
};
</script>

<template>
    <Head title="Transaksi Berulang (Recurring)" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <!-- Header -->
        <div
            class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
        >
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight md:text-3xl">
                    Transaksi Berulang
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Jadwalkan pemasukan rutin (gaji) atau pengeluaran berkala
                    (sewa, langganan, tagihan).
                </p>
            </div>
            <Link
                href="/recurring-transactions/create"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90"
            >
                <PlusCircle class="h-4 w-4" />
                Buat Template Baru
            </Link>
        </div>

        <!-- Empty State -->
        <EmptyState
            v-if="
                !props.recurringTransactions ||
                props.recurringTransactions.length === 0
            "
            title="Belum Ada Transaksi Berulang"
            description="Otomatiskan pencatatan transaksi rutin Anda seperti gaji bulanan, sewa kost, atau tagihan internet."
        >
            <template #action>
                <Link
                    href="/recurring-transactions/create"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90"
                >
                    <PlusCircle class="h-4 w-4" />
                    Buat Template Sekarang
                </Link>
            </template>
        </EmptyState>

        <!-- Template Cards List -->
        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="item in props.recurringTransactions"
                :key="item.id"
                :class="[
                    'flex flex-col justify-between space-y-4 rounded-2xl border bg-card p-5 shadow-2xs transition-all',
                    item.is_active
                        ? 'border-border hover:border-primary/40'
                        : 'border-dashed bg-muted/30 opacity-65',
                ]"
            >
                <div class="space-y-3">
                    <!-- Top row: Status badges & Toggle -->
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span
                                :class="[
                                    'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-bold',
                                    item.type === 'income'
                                        ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                                        : 'bg-rose-500/10 text-rose-600 dark:text-rose-400',
                                ]"
                            >
                                <component
                                    :is="
                                        item.type === 'income'
                                            ? ArrowUpRight
                                            : ArrowDownRight
                                    "
                                    class="h-3 w-3"
                                />
                                {{
                                    item.type === 'income'
                                        ? 'Pemasukan'
                                        : 'Pengeluaran'
                                }}
                            </span>

                            <span
                                class="rounded-full bg-secondary px-2 py-0.5 text-[11px] font-medium text-secondary-foreground"
                            >
                                {{
                                    formatFrequency(
                                        item.frequency,
                                        item.day_of_month,
                                        item.day_of_week,
                                    )
                                }}
                            </span>
                        </div>

                        <!-- Active Toggle -->
                        <button
                            type="button"
                            @click="toggleActive(item.id)"
                            :title="item.is_active ? 'Nonaktifkan' : 'Aktifkan'"
                            :class="[
                                'rounded-full p-1.5 transition-colors',
                                item.is_active
                                    ? 'bg-emerald-500/10 text-emerald-600 hover:bg-emerald-500/20'
                                    : 'bg-muted text-muted-foreground hover:bg-muted/80',
                            ]"
                        >
                            <Power class="h-3.5 w-3.5" />
                        </button>
                    </div>

                    <!-- Title & Amount -->
                    <div>
                        <h3
                            class="line-clamp-1 text-base font-bold tracking-tight text-foreground"
                        >
                            {{ item.name }}
                        </h3>
                        <p
                            class="mt-0.5 line-clamp-1 text-xs text-muted-foreground"
                        >
                            {{ item.description || item.name }}
                        </p>
                    </div>

                    <div
                        class="text-2xl font-black tracking-tight"
                        :class="
                            item.type === 'income'
                                ? 'text-emerald-600 dark:text-emerald-400'
                                : 'text-foreground'
                        "
                    >
                        {{ item.type === 'income' ? '+' : '-' }}
                        <MoneyDisplay :amount="item.amount" />
                    </div>

                    <!-- Category & Account info -->
                    <div
                        class="space-y-1.5 border-t pt-2 text-xs text-muted-foreground"
                    >
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-1.5 truncate">
                                <CategoryIcon
                                    v-if="item.category"
                                    :icon="item.category.icon || 'circle'"
                                    :color="item.category.color || '#6B7280'"
                                    class="h-3.5 w-3.5 shrink-0"
                                />
                                <span class="truncate">{{
                                    item.category?.name || 'Kategori'
                                }}</span>
                            </span>
                            <span
                                class="max-w-[45%] truncate font-medium text-foreground"
                            >
                                {{ item.account?.name || 'Akun' }}
                            </span>
                        </div>

                        <div
                            class="flex items-center justify-between text-[11px]"
                        >
                            <span class="flex items-center gap-1">
                                <Clock class="h-3 w-3" />
                                Jatuh tempo:
                            </span>
                            <span class="font-semibold text-foreground">
                                {{ item.next_due_date || 'Selesai / Nonaktif' }}
                            </span>
                        </div>

                        <div
                            class="flex items-center justify-between text-[11px]"
                        >
                            <span>Status Eksekusi:</span>
                            <span
                                :class="
                                    item.auto_confirm
                                        ? 'font-medium text-primary'
                                        : 'text-muted-foreground'
                                "
                            >
                                {{
                                    item.auto_confirm
                                        ? 'Auto-Confirm'
                                        : 'Simpan Draft'
                                }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Card Actions -->
                <div
                    class="flex items-center justify-between gap-2 border-t pt-3"
                >
                    <button
                        type="button"
                        @click="generateNow(item.id, item.name)"
                        class="inline-flex items-center gap-1 rounded-md bg-secondary px-2.5 py-1.5 text-xs font-semibold text-secondary-foreground transition-colors hover:bg-secondary/80"
                        title="Buat transaksi sekarang tanpa menunggu jadwal"
                    >
                        <Play class="h-3 w-3 fill-current" />
                        Generate Sekarang
                    </button>

                    <div class="flex items-center gap-1">
                        <Link
                            :href="`/recurring-transactions/${item.id}/edit`"
                            class="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                            title="Edit"
                        >
                            <Edit class="h-3.5 w-3.5" />
                        </Link>
                        <button
                            type="button"
                            @click="deleteRecurring(item.id, item.name)"
                            class="rounded-md p-1.5 text-rose-500 transition-colors hover:bg-rose-50 dark:hover:bg-rose-950/30"
                            title="Hapus"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
