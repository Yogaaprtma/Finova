<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    PiggyBank,
    PlusCircle,
    AlertTriangle,
    CheckCircle2,
    Calendar,
    ChevronRight,
    Trash2,
    Edit,
} from '@lucide/vue';
import { computed } from 'vue';
import CategoryIcon from '@/components/CategoryIcon.vue';
import EmptyState from '@/components/EmptyState.vue';
import MoneyDisplay from '@/components/MoneyDisplay.vue';
import { Button } from '@/components/ui/button';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Budgets',
                href: '/budgets',
            },
        ],
    },
});

const props = defineProps<{
    budgets: Array<{
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
    }>;
}>();

const activeBudget = computed(() => props.budgets[0] || null);
const previousBudgets = computed(() => props.budgets.slice(1));

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

const deleteBudget = (id: string, name: string) => {
    if (confirm(`Apakah Anda yakin ingin menghapus anggaran "${name}"?`)) {
        router.delete(`/budgets/${id}`);
    }
};
</script>

<template>
    <Head title="Budgets & Spending Limits" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <!-- Header -->
        <div
            class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
        >
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight md:text-3xl">
                    Anggaran & Batas Pengeluaran
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Kendalikan arus kas dengan menetapkan batas pengeluaran
                    bulanan per kategori.
                </p>
            </div>
            <Link
                href="/budgets/create"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90"
            >
                <PlusCircle class="h-4 w-4" />
                Buat Anggaran
            </Link>
        </div>

        <!-- Empty State -->
        <EmptyState
            v-if="!props.budgets || props.budgets.length === 0"
            title="Belum Ada Anggaran Dibuat"
            description="Buat anggaran bulanan untuk mengalokasikan limit belanja dan mencegah overspending."
        >
            <template #action>
                <Link
                    href="/budgets/create"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90"
                >
                    <PlusCircle class="h-4 w-4" />
                    Buat Anggaran Sekarang
                </Link>
            </template>
        </EmptyState>

        <template v-else>
            <!-- Current / Latest Budget Hero Card -->
            <div
                v-if="activeBudget"
                class="space-y-6 rounded-2xl border bg-card p-6 text-card-foreground shadow-sm"
            >
                <div
                    class="flex flex-col justify-between gap-3 border-b pb-4 sm:flex-row sm:items-center"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary"
                        >
                            <PiggyBank class="h-6 w-6" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-xl font-bold tracking-tight">
                                    {{ activeBudget.name }}
                                </h2>
                                <span
                                    class="rounded-full bg-primary/10 px-2.5 py-0.5 text-[11px] font-semibold text-primary"
                                >
                                    Aktif
                                </span>
                            </div>
                            <p
                                class="mt-0.5 flex items-center gap-1.5 text-xs text-muted-foreground"
                            >
                                <Calendar class="h-3.5 w-3.5" />
                                {{ activeBudget.period_start }} s/d
                                {{ activeBudget.period_end }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <Link
                            :href="`/budgets/${activeBudget.id}/edit`"
                            class="inline-flex items-center gap-1 rounded-md border px-3 py-1.5 text-xs font-medium transition-colors hover:bg-muted"
                        >
                            <Edit class="h-3.5 w-3.5" />
                            Edit
                        </Link>
                        <button
                            type="button"
                            @click="
                                deleteBudget(activeBudget.id, activeBudget.name)
                            "
                            class="inline-flex items-center gap-1 rounded-md border border-rose-200 px-3 py-1.5 text-xs font-medium text-rose-600 transition-colors hover:bg-rose-50 dark:border-rose-900/50 dark:text-rose-400 dark:hover:bg-rose-950/30"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                            Hapus
                        </button>
                    </div>
                </div>

                <!-- Over Budget Alert Banner -->
                <div
                    v-if="activeBudget.over_budget_count > 0"
                    class="flex items-center gap-3 rounded-xl border border-rose-500/20 bg-rose-500/10 p-4 text-sm text-rose-600 dark:text-rose-400"
                >
                    <AlertTriangle class="h-5 w-5 shrink-0" />
                    <div>
                        <p class="font-bold">
                            Peringatan:
                            {{ activeBudget.over_budget_count }} kategori telah
                            melebihi batas anggaran!
                        </p>
                        <p
                            class="text-xs text-rose-600/80 dark:text-rose-400/80"
                        >
                            Total kelebihan pengeluaran:
                            <MoneyDisplay
                                :amount="activeBudget.total_over_budget"
                            />
                        </p>
                    </div>
                </div>

                <!-- Overall Spending Progress -->
                <div class="space-y-3">
                    <div
                        class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center"
                    >
                        <div>
                            <span
                                class="text-xs font-medium tracking-wider text-muted-foreground uppercase"
                                >Total Pengeluaran</span
                            >
                            <div class="mt-1 text-3xl font-black">
                                <MoneyDisplay
                                    :amount="activeBudget.total_spent"
                                />
                                <span
                                    class="text-sm font-normal text-muted-foreground"
                                >
                                    /
                                    <MoneyDisplay
                                        :amount="activeBudget.total_budget"
                                    />
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span
                                :class="[
                                    'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold',
                                    getBadgeClass(activeBudget.status),
                                ]"
                            >
                                <component
                                    :is="
                                        activeBudget.status === 'danger'
                                            ? AlertTriangle
                                            : CheckCircle2
                                    "
                                    class="h-3.5 w-3.5"
                                />
                                {{ activeBudget.percentage }}% Terpakai
                            </span>
                            <div class="text-right text-xs">
                                <p class="text-muted-foreground">
                                    Sisa Anggaran
                                </p>
                                <p class="font-bold text-foreground">
                                    <MoneyDisplay
                                        :amount="activeBudget.total_remaining"
                                    />
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div
                        class="h-3.5 w-full overflow-hidden rounded-full bg-secondary"
                    >
                        <div
                            class="h-full rounded-full transition-all duration-500"
                            :class="getProgressColor(activeBudget.status)"
                            :style="{
                                width: `${Math.min(100, activeBudget.percentage)}%`,
                            }"
                        ></div>
                    </div>
                </div>

                <!-- Category Breakdown Grid -->
                <div>
                    <h3
                        class="mb-4 text-sm font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        Alokasi per Kategori ({{ activeBudget.items.length }})
                    </h3>

                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="item in activeBudget.items"
                            :key="item.id"
                            class="space-y-2.5 rounded-xl border bg-card/60 p-4 shadow-2xs transition-all hover:border-primary/40"
                        >
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <div class="flex min-w-0 items-center gap-2">
                                    <CategoryIcon
                                        v-if="item.category"
                                        :icon="item.category.icon || 'circle'"
                                        :color="
                                            item.category.color || '#6B7280'
                                        "
                                        class="h-4 w-4 shrink-0"
                                    />
                                    <span
                                        class="truncate text-sm font-semibold"
                                        >{{
                                            item.category?.name || 'Kategori'
                                        }}</span
                                    >
                                </div>
                                <span
                                    :class="[
                                        'shrink-0 rounded-full border px-2 py-0.5 text-[10px] font-bold',
                                        getBadgeClass(item.status),
                                    ]"
                                >
                                    {{ item.percentage }}%
                                </span>
                            </div>

                            <div
                                class="flex items-baseline justify-between text-xs text-muted-foreground"
                            >
                                <span>
                                    Terpakai:
                                    <strong
                                        :class="{
                                            'font-bold text-rose-500':
                                                item.status === 'danger',
                                        }"
                                    >
                                        <MoneyDisplay :amount="item.spent" />
                                    </strong>
                                </span>
                                <span>
                                    Limit:
                                    <strong class="text-foreground">
                                        <MoneyDisplay :amount="item.limit" />
                                    </strong>
                                </span>
                            </div>

                            <!-- Bar -->
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
                                class="flex justify-between text-[11px] font-medium"
                            >
                                <span
                                    v-if="item.over_budget > 0"
                                    class="text-rose-500"
                                >
                                    Over
                                    <MoneyDisplay :amount="item.over_budget" />
                                </span>
                                <span v-else class="text-muted-foreground">
                                    Sisa
                                    <MoneyDisplay :amount="item.remaining" />
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end border-t pt-4">
                    <Link
                        :href="`/budgets/${activeBudget.id}`"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary hover:underline"
                    >
                        Lihat Riwayat Transaksi Anggaran Ini
                        <ChevronRight class="h-3.5 w-3.5" />
                    </Link>
                </div>
            </div>

            <!-- Previous Budgets Section -->
            <div v-if="previousBudgets.length > 0" class="space-y-4">
                <h3 class="text-lg font-bold tracking-tight">
                    Riwayat Anggaran Sebelumnya
                </h3>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="b in previousBudgets"
                        :key="b.id"
                        class="space-y-3 rounded-xl border bg-card p-5 shadow-2xs transition-colors hover:border-primary/40"
                    >
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-bold">{{ b.name }}</h4>
                            <span
                                :class="[
                                    'rounded-full border px-2 py-0.5 text-[10px] font-bold',
                                    getBadgeClass(b.status),
                                ]"
                            >
                                {{ b.percentage }}%
                            </span>
                        </div>

                        <p class="text-xs text-muted-foreground">
                            {{ b.period_start }} s/d {{ b.period_end }}
                        </p>

                        <div class="space-y-1.5">
                            <div
                                class="flex justify-between text-xs text-muted-foreground"
                            >
                                <span
                                    ><MoneyDisplay :amount="b.total_spent"
                                /></span>
                                <span
                                    ><MoneyDisplay :amount="b.total_budget"
                                /></span>
                            </div>
                            <div
                                class="h-1.5 w-full overflow-hidden rounded-full bg-secondary"
                            >
                                <div
                                    class="h-full rounded-full"
                                    :class="getProgressColor(b.status)"
                                    :style="{
                                        width: `${Math.min(100, b.percentage)}%`,
                                    }"
                                ></div>
                            </div>
                        </div>

                        <div
                            class="flex items-center justify-between border-t pt-2 text-xs"
                        >
                            <Link
                                :href="`/budgets/${b.id}`"
                                class="font-medium text-primary hover:underline"
                            >
                                Buka Detail
                            </Link>
                            <button
                                type="button"
                                @click="deleteBudget(b.id, b.name)"
                                class="text-rose-500 hover:text-rose-700"
                            >
                                Hapus
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
