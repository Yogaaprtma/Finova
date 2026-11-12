<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, PiggyBank, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import CategoryIcon from '@/components/CategoryIcon.vue';
import MoneyDisplay from '@/components/MoneyDisplay.vue';
import MoneyInput from '@/components/MoneyInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Budgets',
                href: '/budgets',
            },
            {
                title: 'Edit Anggaran',
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
        items: Array<{
            id: string;
            category_id: string;
            limit: number;
        }>;
    };
    categories: Array<{
        id: string;
        name: string;
        icon?: string;
        color?: string;
    }>;
}>();

// Map existing items
const existingItemsMap = new Map(
    props.budget.items.map((i) => [i.category_id, i.limit]),
);

const initialItems = props.categories.map((c) => ({
    category_id: c.id,
    amount: existingItemsMap.get(c.id) || 0,
}));

const form = useForm({
    name: props.budget.name,
    period_start: props.budget.period_start,
    period_end: props.budget.period_end,
    total_budget: props.budget.total_budget,
    items: initialItems,
});

const calculatedTotal = computed(() => {
    return form.items.reduce(
        (sum, item) => sum + (Number(item.amount) || 0),
        0,
    );
});

const getCategory = (id: string) => {
    return props.categories.find((c) => c.id === id);
};

const submit = () => {
    const activeItems = form.items.filter((item) => Number(item.amount) > 0);

    form.transform((data) => ({
        ...data,
        items: activeItems,
        total_budget: calculatedTotal.value,
    })).put(`/budgets/${props.budget.id}`);
};
</script>

<template>
    <Head :title="`Edit Anggaran - ${props.budget.name}`" />

    <div class="mx-auto max-w-3xl space-y-6 p-4 md:p-6">
        <!-- Header -->
        <div class="flex items-center gap-3">
            <Link
                :href="`/budgets/${props.budget.id}`"
                class="rounded-lg border p-2 text-muted-foreground transition-colors hover:bg-muted"
            >
                <ArrowLeft class="h-4 w-4" />
            </Link>
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight">
                    Edit Anggaran
                </h1>
                <p class="text-sm text-muted-foreground">
                    Perbarui nama, periode, atau limit per kategori.
                </p>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Basic Information Card -->
            <div class="space-y-4 rounded-2xl border bg-card p-6 shadow-sm">
                <h2 class="flex items-center gap-2 text-base font-bold">
                    <PiggyBank class="h-5 w-5 text-primary" />
                    Informasi Periode Anggaran
                </h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2 sm:col-span-2">
                        <Label for="name">Nama Anggaran</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            placeholder="Contoh: Anggaran November 2026"
                            required
                        />
                        <p
                            v-if="form.errors.name"
                            class="text-xs font-medium text-rose-500"
                        >
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="period_start">Tanggal Mulai</Label>
                        <Input
                            id="period_start"
                            type="date"
                            v-model="form.period_start"
                            required
                        />
                        <p
                            v-if="form.errors.period_start"
                            class="text-xs font-medium text-rose-500"
                        >
                            {{ form.errors.period_start }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="period_end">Tanggal Selesai</Label>
                        <Input
                            id="period_end"
                            type="date"
                            v-model="form.period_end"
                            required
                        />
                        <p
                            v-if="form.errors.period_end"
                            class="text-xs font-medium text-rose-500"
                        >
                            {{ form.errors.period_end }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Category Limits Card -->
            <div class="space-y-4 rounded-2xl border bg-card p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold">
                            Batas Pengeluaran per Kategori
                        </h2>
                        <p class="text-xs text-muted-foreground">
                            Atur ulang batas belanja untuk kategori yang
                            diinginkan.
                        </p>
                    </div>
                    <div class="text-right">
                        <span class="text-xs text-muted-foreground"
                            >Total Anggaran:</span
                        >
                        <div class="text-lg font-bold text-primary">
                            <MoneyDisplay :amount="calculatedTotal" />
                        </div>
                    </div>
                </div>

                <div class="space-y-3 pt-2">
                    <div
                        v-for="(item, index) in form.items"
                        :key="item.category_id"
                        class="flex flex-col justify-between gap-3 rounded-xl border bg-card/60 p-3.5 transition-colors sm:flex-row sm:items-center"
                    >
                        <div class="flex items-center gap-3">
                            <CategoryIcon
                                v-if="getCategory(item.category_id)"
                                :icon="
                                    getCategory(item.category_id)?.icon ||
                                    'circle'
                                "
                                :color="
                                    getCategory(item.category_id)?.color ||
                                    '#6B7280'
                                "
                                class="h-4 w-4 shrink-0"
                            />
                            <span class="text-sm font-semibold">
                                {{ getCategory(item.category_id)?.name }}
                            </span>
                        </div>

                        <div class="w-full sm:w-56">
                            <MoneyInput
                                v-model="form.items[index].amount"
                                placeholder="Rp 0"
                            />
                        </div>
                    </div>
                </div>

                <p
                    v-if="form.errors.items"
                    class="pt-2 text-xs font-medium text-rose-500"
                >
                    {{ form.errors.items }}
                </p>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3">
                <Link
                    :href="`/budgets/${props.budget.id}`"
                    class="rounded-lg border px-4 py-2 text-sm font-medium transition-colors hover:bg-muted"
                >
                    Batal
                </Link>
                <Button
                    type="submit"
                    :disabled="form.processing || calculatedTotal === 0"
                    class="min-w-32"
                >
                    {{ form.processing ? 'Menyimpan...' : 'Perbarui Anggaran' }}
                </Button>
            </div>
        </form>
    </div>
</template>
