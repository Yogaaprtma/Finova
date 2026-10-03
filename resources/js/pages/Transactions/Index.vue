<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { PlusCircle, Search, Filter, FileText } from '@lucide/vue';
import { ref, watch } from 'vue';

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
}>();

const searchQuery = ref('');

// Simple search for now, full server-side filtering in Phase 5
const search = () => {
    router.get(
        '/transactions',
        { search: searchQuery.value },
        { preserveState: true, replace: true },
    );
};

const handleEdit = (transaction: any) => {
    router.get(`/transactions/${transaction.id}/edit`);
};

const handleDelete = (transaction: any) => {
    if (
        confirm(
            'Are you sure you want to delete this transaction? (Note: Balance reversal is coming in Phase 5)',
        )
    ) {
        router.delete(`/transactions/${transaction.id}`);
    }
};
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
                class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50 sm:w-auto"
            >
                <PlusCircle class="h-4 w-4" />
                Add Transaction
            </Link>
        </div>

        <!-- Filters & Search -->
        <div class="flex flex-col gap-4 sm:flex-row">
            <div class="relative flex-1">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <input
                    type="text"
                    v-model="searchQuery"
                    @keyup.enter="search"
                    placeholder="Search transactions..."
                    class="flex h-10 w-full rounded-md border border-input bg-background py-2 pr-3 pl-10 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                />
            </div>
            <button
                class="inline-flex h-10 items-center justify-center gap-2 rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
            >
                <Filter class="h-4 w-4" />
                Filters
            </button>
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
                        @delete="handleDelete"
                    />
                </div>
            </div>

            <!-- Fallback if we don't group (using raw list) -->
            <div
                v-else-if="transactions.data.length > 0 && !groupedTransactions"
                class="divide-y"
            >
                <TransactionRow
                    v-for="tx in transactions.data"
                    :key="tx.id"
                    :transaction="tx"
                    @edit="handleEdit"
                    @delete="handleDelete"
                />
            </div>

            <!-- Empty State -->
            <div
                v-if="transactions.data.length === 0"
                class="flex flex-1 flex-col items-center justify-center p-12 text-center"
            >
                <div
                    class="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-muted"
                >
                    <FileText class="h-10 w-10 text-muted-foreground" />
                </div>
                <h2 class="mb-2 text-2xl font-semibold">
                    No transactions found
                </h2>
                <p class="mb-8 max-w-md text-muted-foreground">
                    You haven't recorded any transactions yet. Start tracking
                    your income and expenses.
                </p>
                <Link
                    href="/transactions/create"
                    class="inline-flex h-11 items-center justify-center gap-2 rounded-md bg-primary px-8 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50"
                >
                    <PlusCircle class="h-5 w-5" />
                    Add Your First Transaction
                </Link>
            </div>

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
    </div>
</template>

<script lang="ts">
import { computed } from 'vue';
import dayjs from 'dayjs';
import TransactionRow from '@/components/TransactionRow.vue';

export default {
    computed: {
        groupedTransactions() {
            if (!this.transactions || !this.transactions.data) {
                return null;
            }

            const groups: Record<string, any[]> = {};

            this.transactions.data.forEach((tx: any) => {
                const date = dayjs(tx.date).format('MMMM D, YYYY');

                if (!groups[date]) {
                    groups[date] = [];
                }

                groups[date].push(tx);
            });

            return groups;
        },
    },
};
</script>
