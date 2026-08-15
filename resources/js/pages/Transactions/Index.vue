<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    PlusCircle,
    Search,
    Filter,
    ArrowRightLeft,
    X,
    AlertCircle,
} from '@lucide/vue';
import dayjs from 'dayjs';
import { computed, ref, reactive } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import TransactionRow from '@/components/TransactionRow.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Transactions',
                href: '/transactions',
            },
        ],
    },
});

const props = defineProps<{
    transactions: any;
    filters?: {
        search?: string;
        type?: string;
        category_id?: string;
        account_id?: string;
        start_date?: string;
        end_date?: string;
    };
    accounts?: { id: string; name: string }[];
    categories?: { id: string; name: string; type: string }[];
}>();

const formFilters = reactive({
    search: props.filters?.search || '',
    type: props.filters?.type || '',
    category_id: props.filters?.category_id || '',
    account_id: props.filters?.account_id || '',
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
});

const showFilterDrawer = ref(false);
const deleteTarget = ref<any | null>(null);

const applyFilters = () => {
    router.get(
        '/transactions',
        {
            search: formFilters.search || undefined,
            type: formFilters.type || undefined,
            category_id: formFilters.category_id || undefined,
            account_id: formFilters.account_id || undefined,
            start_date: formFilters.start_date || undefined,
            end_date: formFilters.end_date || undefined,
        },
        { preserveState: true, replace: true },
    );
};

const resetFilters = () => {
    formFilters.search = '';
    formFilters.type = '';
    formFilters.category_id = '';
    formFilters.account_id = '';
    formFilters.start_date = '';
    formFilters.end_date = '';
    applyFilters();
};

const hasActiveFilters = computed(() => {
    return Boolean(
        formFilters.type ||
        formFilters.category_id ||
        formFilters.account_id ||
        formFilters.start_date ||
        formFilters.end_date ||
        formFilters.search,
    );
});

const handleEdit = (transaction: any) => {
    router.get(`/transactions/${transaction.id}/edit`);
};

const confirmDelete = (transaction: any) => {
    deleteTarget.value = transaction;
};

const executeDelete = () => {
    if (!deleteTarget.value) {
        return;
    }

    router.delete(`/transactions/${deleteTarget.value.id}`, {
        onSuccess: () => {
            deleteTarget.value = null;
        },
    });
};

const groupedTransactions = computed(() => {
    if (!props.transactions || !props.transactions.data) {
        return null;
    }

    const groups: Record<string, any[]> = {};

    props.transactions.data.forEach((tx: any) => {
        const date = dayjs(tx.date).format('MMMM D, YYYY');

        if (!groups[date]) {
            groups[date] = [];
        }

        groups[date].push(tx);
    });

    return groups;
});
</script>

<template>
    <Head title="Transactions" />

    <div
        class="mx-auto flex h-full w-full max-w-5xl flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6 lg:p-8"
    >
        <!-- Header -->
        <div
            class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center"
        >
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Transactions</h1>
                <p class="mt-1 text-muted-foreground">
                    View and manage your income, expenses, and transfers.
                </p>
            </div>
            <Link
                href="/transactions/create"
                class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none sm:w-auto"
            >
                <PlusCircle class="h-4 w-4" />
                Add Transaction
            </Link>
        </div>

        <!-- Filters & Search Toolbar -->
        <div class="flex flex-col gap-3">
            <div class="flex flex-col gap-3 sm:flex-row">
                <div class="relative flex-1">
                    <Search
                        class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <input
                        type="text"
                        v-model="formFilters.search"
                        @keyup.enter="applyFilters"
                        placeholder="Search description or notes..."
                        class="flex h-10 w-full rounded-md border border-input bg-background py-2 pr-3 pl-10 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    />
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="showFilterDrawer = !showFilterDrawer"
                        class="inline-flex h-10 items-center justify-center gap-2 rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-1 focus-visible:ring-ring"
                        :class="
                            hasActiveFilters
                                ? 'border-primary text-primary'
                                : ''
                        "
                    >
                        <Filter class="h-4 w-4" />
                        <span>Filters</span>
                        <span
                            v-if="hasActiveFilters"
                            class="flex h-2 w-2 rounded-full bg-primary"
                        ></span>
                    </button>
                    <button
                        v-if="hasActiveFilters"
                        type="button"
                        @click="resetFilters"
                        class="inline-flex h-10 items-center justify-center gap-1 rounded-md border border-transparent px-3 py-2 text-sm text-muted-foreground hover:bg-muted hover:text-foreground"
                        title="Clear filters"
                    >
                        <X class="h-4 w-4" />
                        <span class="hidden sm:inline">Reset</span>
                    </button>
                </div>
            </div>

            <!-- Extended Filters Panel -->
            <div
                v-if="showFilterDrawer"
                class="grid grid-cols-1 gap-3 rounded-xl border bg-muted/30 p-4 sm:grid-cols-2 lg:grid-cols-4"
            >
                <!-- Type Filter -->
                <div>
                    <label
                        class="mb-1 block text-xs font-medium text-muted-foreground"
                        >Type</label
                    >
                    <select
                        v-model="formFilters.type"
                        @change="applyFilters"
                        class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:ring-1 focus-visible:ring-ring"
                    >
                        <option value="">All Types</option>
                        <option value="expense">Expense</option>
                        <option value="income">Income</option>
                        <option value="transfer">Transfer</option>
                        <option value="adjustment">Adjustment</option>
                    </select>
                </div>

                <!-- Account Filter -->
                <div v-if="accounts && accounts.length > 0">
                    <label
                        class="mb-1 block text-xs font-medium text-muted-foreground"
                        >Account</label
                    >
                    <select
                        v-model="formFilters.account_id"
                        @change="applyFilters"
                        class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:ring-1 focus-visible:ring-ring"
                    >
                        <option value="">All Accounts</option>
                        <option
                            v-for="acc in accounts"
                            :key="acc.id"
                            :value="acc.id"
                        >
                            {{ acc.name }}
                        </option>
                    </select>
                </div>

                <!-- Category Filter -->
                <div v-if="categories && categories.length > 0">
                    <label
                        class="mb-1 block text-xs font-medium text-muted-foreground"
                        >Category</label
                    >
                    <select
                        v-model="formFilters.category_id"
                        @change="applyFilters"
                        class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:ring-1 focus-visible:ring-ring"
                    >
                        <option value="">All Categories</option>
                        <option
                            v-for="cat in categories"
                            :key="cat.id"
                            :value="cat.id"
                        >
                            {{ cat.name }} ({{ cat.type }})
                        </option>
                    </select>
                </div>

                <!-- Start Date -->
                <div>
                    <label
                        class="mb-1 block text-xs font-medium text-muted-foreground"
                        >From Date</label
                    >
                    <input
                        type="date"
                        v-model="formFilters.start_date"
                        @change="applyFilters"
                        class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:ring-1 focus-visible:ring-ring"
                    />
                </div>
            </div>
        </div>

        <!-- Transactions List -->
        <div
            class="flex min-h-[400px] flex-col rounded-xl border bg-card text-card-foreground shadow"
        >
            <div v-if="transactions.data.length > 0" class="divide-y">
                <div
                    v-for="(dayTransactions, date) in groupedTransactions"
                    :key="date"
                >
                    <div
                        class="border-b bg-muted/30 px-6 py-2 text-sm font-medium text-muted-foreground"
                    >
                        {{ date }}
                    </div>
                    <TransactionRow
                        v-for="tx in dayTransactions"
                        :key="tx.id"
                        :transaction="tx"
                        @edit="handleEdit"
                        @delete="confirmDelete"
                    />
                </div>
            </div>

            <!-- Empty State -->
            <EmptyState
                v-else
                :icon="ArrowRightLeft"
                title="No transactions found"
                description="You haven't recorded any transactions matching your current filters. Start tracking your income and expenses."
                action-text="Add Your First Transaction"
                action-href="/transactions/create"
            />

            <!-- Pagination -->
            <div
                v-if="transactions.links && transactions.links.length > 3"
                class="mt-auto flex justify-center border-t p-4"
            >
                <nav class="flex items-center space-x-1">
                    <Link
                        v-for="(link, index) in transactions.links"
                        :key="index"
                        :href="link.url || '#'"
                        :class="[
                            'rounded-md px-3 py-1 text-sm transition-colors',
                            link.active
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-muted',
                            !link.url ? 'cursor-not-allowed opacity-50' : '',
                        ]"
                        v-html="link.label"
                    />
                </nav>
            </div>
        </div>

        <!-- Confirmation Delete Modal -->
        <div
            v-if="deleteTarget"
            class="fixed inset-0 z-50 flex items-center justify-center bg-background/80 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-md rounded-xl border bg-card p-6 text-card-foreground shadow-lg"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-destructive/10 text-destructive"
                    >
                        <AlertCircle class="h-5 w-5" />
                    </div>
                    <h3 class="text-lg font-semibold">Delete Transaction</h3>
                </div>
                <p class="mt-3 text-sm text-muted-foreground">
                    Are you sure you want to delete
                    <span class="font-bold text-foreground"
                        >"{{
                            deleteTarget.description || 'this transaction'
                        }}"</span
                    >? This action will automatically reverse all balance
                    entries across affected accounts.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        @click="deleteTarget = null"
                        class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-muted"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="executeDelete"
                        class="rounded-md bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground hover:bg-destructive/90"
                    >
                        Delete & Recalculate
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
