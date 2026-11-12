<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    PiggyBank,
    AlertTriangle,
    CheckCircle2,
    ArrowRight,
} from '@lucide/vue';
import CategoryIcon from '@/components/CategoryIcon.vue';
import MoneyDisplay from '@/components/MoneyDisplay.vue';

const props = defineProps<{
    budgetSummary?: {
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
    } | null;
}>();

const getProgressBarColor = (status: 'normal' | 'warning' | 'danger') => {
    switch (status) {
        case 'danger':
            return 'bg-rose-500';
        case 'warning':
            return 'bg-amber-500';
        default:
            return 'bg-emerald-500';
    }
};

const getBadgeStyle = (status: 'normal' | 'warning' | 'danger') => {
    switch (status) {
        case 'danger':
            return 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20';
        case 'warning':
            return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20';
        default:
            return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20';
    }
};
</script>

<template>
    <!-- When no budget exists for this period -->
    <div
        v-if="!budgetSummary"
        class="flex flex-col items-center justify-between gap-4 rounded-xl border border-dashed border-border bg-card/50 p-5 text-card-foreground shadow-sm sm:flex-row"
    >
        <div class="flex items-center gap-3">
            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
            >
                <PiggyBank class="h-5 w-5" />
            </div>
            <div>
                <h4 class="text-sm font-semibold">
                    Anggaran Bulanan Belum Ditetapkan
                </h4>
                <p class="text-xs text-muted-foreground">
                    Atur batas pengeluaran kategori bulan ini untuk melacak dan
                    mengontrol finansial Anda.
                </p>
            </div>
        </div>
        <Link
            href="/budgets/create"
            class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-primary px-3.5 py-2 text-xs font-medium text-primary-foreground transition-colors hover:bg-primary/90"
        >
            Buat Anggaran
            <ArrowRight class="h-3.5 w-3.5" />
        </Link>
    </div>

    <!-- Active Budget Summary Card -->
    <div
        v-else
        class="space-y-4 rounded-xl border bg-card p-5 text-card-foreground shadow-sm"
    >
        <div
            class="flex flex-col justify-between gap-2 sm:flex-row sm:items-center"
        >
            <div class="flex items-center gap-2">
                <div
                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10 text-primary"
                >
                    <PiggyBank class="h-4 w-4" />
                </div>
                <div>
                    <h3 class="text-sm font-bold">{{ budgetSummary.name }}</h3>
                    <p class="text-xs text-muted-foreground">
                        Progres Anggaran Bulan Ini
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span
                    :class="[
                        'inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs font-semibold',
                        getBadgeStyle(budgetSummary.status),
                    ]"
                >
                    <component
                        :is="
                            budgetSummary.status === 'danger'
                                ? AlertTriangle
                                : CheckCircle2
                        "
                        class="h-3.5 w-3.5"
                    />
                    {{ budgetSummary.percentage }}% Terpakai
                </span>
                <Link
                    :href="`/budgets/${budgetSummary.id}`"
                    class="flex items-center gap-1 text-xs font-medium text-primary hover:underline"
                >
                    Detail
                    <ArrowRight class="h-3 w-3" />
                </Link>
            </div>
        </div>

        <!-- Progress bar and spent vs limit -->
        <div class="space-y-2">
            <div class="flex items-baseline justify-between text-xs">
                <span class="text-muted-foreground">
                    Terpakai:
                    <strong class="text-foreground">
                        <MoneyDisplay :amount="budgetSummary.total_spent" />
                    </strong>
                </span>
                <span class="text-muted-foreground">
                    Batas:
                    <strong class="text-foreground">
                        <MoneyDisplay :amount="budgetSummary.total_budget" />
                    </strong>
                </span>
            </div>

            <!-- Overall progress bar -->
            <div class="h-2.5 w-full overflow-hidden rounded-full bg-secondary">
                <div
                    class="h-full rounded-full transition-all duration-500"
                    :class="getProgressBarColor(budgetSummary.status)"
                    :style="{
                        width: `${Math.min(100, budgetSummary.percentage)}%`,
                    }"
                ></div>
            </div>

            <div class="flex justify-between text-xs font-medium">
                <span
                    v-if="budgetSummary.total_over_budget > 0"
                    class="flex items-center gap-1 text-rose-500"
                >
                    <AlertTriangle class="h-3 w-3" />
                    Melebihi anggaran sebesar
                    <MoneyDisplay :amount="budgetSummary.total_over_budget" />
                </span>
                <span v-else class="text-emerald-600 dark:text-emerald-400">
                    Sisa anggaran:
                    <MoneyDisplay :amount="budgetSummary.total_remaining" />
                </span>

                <span
                    v-if="budgetSummary.over_budget_count > 0"
                    class="font-semibold text-rose-500"
                >
                    {{ budgetSummary.over_budget_count }} kategori over-budget
                </span>
            </div>
        </div>

        <!-- Top Categories preview (up to 3) -->
        <div
            v-if="budgetSummary.items && budgetSummary.items.length > 0"
            class="space-y-2.5 border-t pt-2"
        >
            <div
                v-for="item in budgetSummary.items.slice(0, 3)"
                :key="item.id"
                class="space-y-1"
            >
                <div class="flex items-center justify-between text-xs">
                    <div class="flex max-w-[55%] items-center gap-1.5 truncate">
                        <CategoryIcon
                            v-if="item.category"
                            :icon="item.category.icon || 'circle'"
                            :color="item.category.color || '#6B7280'"
                            class="h-3.5 w-3.5 shrink-0"
                        />
                        <span class="truncate font-medium">{{
                            item.category?.name || 'Kategori'
                        }}</span>
                    </div>
                    <div class="text-right text-[11px] text-muted-foreground">
                        <span
                            :class="{
                                'font-semibold text-rose-500':
                                    item.status === 'danger',
                            }"
                        >
                            <MoneyDisplay :amount="item.spent" />
                        </span>
                        / <MoneyDisplay :amount="item.limit" /> ({{
                            item.percentage
                        }}%)
                    </div>
                </div>
                <div
                    class="h-1.5 w-full overflow-hidden rounded-full bg-secondary"
                >
                    <div
                        class="h-full rounded-full transition-all duration-300"
                        :class="getProgressBarColor(item.status)"
                        :style="{ width: `${Math.min(100, item.percentage)}%` }"
                    ></div>
                </div>
            </div>
        </div>
    </div>
</template>
