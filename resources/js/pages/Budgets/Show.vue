<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    PiggyBank,
    Calendar,
    AlertTriangle,
    CheckCircle2,
    Edit,
    Trash2,
    ArrowUpRight,
    ArrowDownRight,
    Receipt,
} from '@lucide/vue';
import dayjs from 'dayjs';
import CategoryIcon from '@/components/CategoryIcon.vue';
import MoneyDisplay from '@/components/MoneyDisplay.vue';
import { Button } from '@/components/ui/button';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Budgets',
                href: '/budgets',
            },
            {
                title: 'Detail Anggaran',
                href: '#',
            },
        ],
    },
});

const props = defineProps<{
    budget: {
        id: string;
        name: string;
        period_start: string;
        period_end: string;
        total_budget: number;
        total_spent: number;
        total_remaining: number;
        total_over_budget: number;
        percentage: number;
        status: 'normal' | 'warning' | 'danger';
        over_budget_count: number;
        items: Array<{
            id: string;
            category_id: string;
            category: {
                id: string;
                name: string;
                icon?: string;
                color?: string;
            } | null;
            limit: number;
            spent: number;
            remaining: number;
            over_budget: number;
            percentage: number;
            status: 'normal' | 'warning' | 'danger';
        }>;
    };
    recentTransactions: any[];
}>();

const getProgressColor = (status: 'normal' | 'warning' | 'danger') => {
    switch (status) {
        case 'danger':
            return 'bg-rose-500';
        case 'warning':
            return 'bg-amber-500';
        default:
            return 'bg-emerald-500';
    }
};

const getBadgeClass = (status: 'normal' | 'warning' | 'danger') => {
    switch (status) {
        case 'danger':
            return 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20';
        case 'warning':
            return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20';
        default:
            return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20';
    }
};

const deleteBudget = () => {
    if (
        confirm(
            `Apakah Anda yakin ingin menghapus anggaran "${props.budget.name}"?`,
        )
    ) {
        router.delete(`/budgets/${props.budget.id}`);
    }
};
</script>

<template>
    <Head :title="`Detail Anggaran - ${props.budget.name}`" />

    <div class="mx-auto max-w-4xl space-y-6 p-4 md:p-6">
        <!-- Header -->
        <div
            class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
        >
            <div class="flex items-center gap-3">
                <Link
                    href="/budgets"
                    class="rounded-lg border p-2 text-muted-foreground transition-colors hover:bg-muted"
                >
                    <ArrowLeft class="h-4 w-4" />
                </Link>
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight">
                        {{ props.budget.name }}
                    </h1>
                    <p
                        class="mt-0.5 flex items-center gap-1.5 text-xs text-muted-foreground"
                    >
                        <Calendar class="h-3.5 w-3.5" />
                        Periode: {{ props.budget.period_start }} s/d
                        {{ props.budget.period_end }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <Link
                    :href="`/budgets/${props.budget.id}/edit`"
                    class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-xs font-medium transition-colors hover:bg-muted"
                >
                    <Edit class="h-3.5 w-3.5" />
                    Edit Anggaran
                </Link>
                <button
                    type="button"
                    @click="deleteBudget"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 px-3 py-2 text-xs font-medium text-rose-600 transition-colors hover:bg-rose-50 dark:border-rose-900/50 dark:text-rose-400 dark:hover:bg-rose-950/30"
                >
                    <Trash2 class="h-3.5 w-3.5" />
                    Hapus
                </button>
            </div>
        </div>

        <!-- Over Budget Alert Banner -->
        <div
            v-if="props.budget.over_budget_count > 0"
            class="flex items-center gap-3 rounded-xl border border-rose-500/20 bg-rose-500/10 p-4 text-sm text-rose-600 dark:text-rose-400"
        >
            <AlertTriangle class="h-5 w-5 shrink-0" />
            <div>
                <p class="font-bold">
                    Perhatian: {{ props.budget.over_budget_count }} kategori
                    melebihi batas yang ditentukan!
                </p>
                <p class="text-xs text-rose-600/80 dark:text-rose-400/80">
                    Total belanja berlebih:
                    <MoneyDisplay :amount="props.budget.total_over_budget" />
                </p>
            </div>
        </div>

        <!-- Overall Summary Card -->
        <div class="space-y-4 rounded-2xl border bg-card p-6 shadow-sm">
            <div
                class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center"
            >
                <div>
                    <p
                        class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        Total Pengeluaran
                    </p>
                    <div class="mt-1 text-3xl font-black">
                        <MoneyDisplay :amount="props.budget.total_spent" />
                        <span class="text-sm font-normal text-muted-foreground">
                            /
                            <MoneyDisplay :amount="props.budget.total_budget" />
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <span
                        :class="[
                            'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold',
                            getBadgeClass(props.budget.status),
                        ]"
                    >
                        <component
                            :is="
                                props.budget.status === 'danger'
                                    ? AlertTriangle
                                    : CheckCircle2
                            "
                            class="h-3.5 w-3.5"
                        />
                        {{ props.budget.percentage }}% Terpakai
                    </span>
                    <div class="text-right text-xs">
                        <p class="text-muted-foreground">Sisa Anggaran</p>
                        <p class="font-bold text-foreground">
                            <MoneyDisplay
                                :amount="props.budget.total_remaining"
                            />
                        </p>
                    </div>
                </div>
            </div>

            <!-- Big Bar -->
            <div class="h-3 w-full overflow-hidden rounded-full bg-secondary">
                <div
                    class="h-full rounded-full transition-all duration-500"
                    :class="getProgressColor(props.budget.status)"
                    :style="{
                        width: `${Math.min(100, props.budget.percentage)}%`,
                    }"
                ></div>
            </div>
        </div>

        <!-- Category Progress Breakdown -->
        <div class="space-y-4 rounded-2xl border bg-card p-6 shadow-sm">
            <h2 class="text-base font-bold">Rincian per Kategori</h2>

            <div class="divide-y">
                <div
                    v-for="item in props.budget.items"
                    :key="item.id"
                    class="space-y-2 py-4 first:pt-0 last:pb-0"
                >
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2.5">
                            <CategoryIcon
                                v-if="item.category"
                                :icon="item.category.icon || 'circle'"
                                :color="item.category.color || '#6B7280'"
                                class="h-5 w-5 shrink-0"
                            />
                            <div>
                                <span class="text-sm font-bold">{{
                                    item.category?.name || 'Kategori'
                                }}</span>
                                <span
                                    :class="[
                                        'ml-2 rounded-full border px-2 py-0.5 text-[10px] font-bold',
                                        getBadgeClass(item.status),
                                    ]"
                                >
                                    {{ item.percentage }}%
                                </span>
                            </div>
                        </div>

                        <div class="text-right text-xs font-medium">
                            <span
                                :class="{
                                    'font-bold text-rose-500':
                                        item.status === 'danger',
                                }"
                            >
                                <MoneyDisplay :amount="item.spent" />
                            </span>
                            <span class="text-muted-foreground">
                                / <MoneyDisplay :amount="item.limit"
                            /></span>
                        </div>
                    </div>

                    <div
                        class="h-2 w-full overflow-hidden rounded-full bg-secondary"
                    >
                        <div
                            class="h-full rounded-full transition-all duration-300"
                            :class="getProgressColor(item.status)"
                            :style="{
                                width: `${Math.min(100, item.percentage)}%`,
                            }"
                        ></div>
                    </div>

                    <div
                        class="flex justify-between text-xs text-muted-foreground"
                    >
                        <span
                            v-if="item.over_budget > 0"
                            class="font-semibold text-rose-500"
                        >
                            Over budget:
                            <MoneyDisplay :amount="item.over_budget" />
                        </span>
                        <span v-else>
                            Sisa limit:
                            <MoneyDisplay :amount="item.remaining" />
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Transactions in this Budget Period -->
        <div class="space-y-4 rounded-2xl border bg-card p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="flex items-center gap-2 text-base font-bold">
                        <Receipt class="h-4 w-4 text-primary" />
                        Aktivitas Pengeluaran Terbaru pada Periode Ini
                    </h2>
                    <p class="text-xs text-muted-foreground">
                        Transaksi pengeluaran yang memotong limit anggaran ini.
                    </p>
                </div>
                <Link
                    href="/transactions"
                    class="text-xs font-medium text-primary hover:underline"
                >
                    Semua Transaksi
                </Link>
            </div>

            <div
                v-if="
                    !props.recentTransactions ||
                    props.recentTransactions.length === 0
                "
                class="py-6 text-center text-xs text-muted-foreground"
            >
                Belum ada transaksi pengeluaran tercatat dalam periode anggaran
                ini.
            </div>

            <div v-else class="divide-y">
                <div
                    v-for="tx in props.recentTransactions"
                    :key="tx.id"
                    class="flex items-center justify-between py-3 first:pt-0 last:pb-0"
                >
                    <div class="flex items-center gap-3">
                        <CategoryIcon
                            v-if="tx.category"
                            :icon="tx.category.icon || 'circle'"
                            :color="tx.category.color || '#6B7280'"
                            class="h-4 w-4 shrink-0"
                        />
                        <div>
                            <p class="text-sm font-semibold text-foreground">
                                {{ tx.description }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ tx.category?.name || 'Tanpa Kategori' }} •
                                {{ dayjs(tx.date).format('D MMM YYYY') }}
                            </p>
                        </div>
                    </div>

                    <div class="text-right">
                        <p class="text-sm font-bold text-rose-500">
                            - <MoneyDisplay :amount="tx.amount" />
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
