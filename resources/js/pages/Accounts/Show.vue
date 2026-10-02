<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Edit, Plus, FileText } from '@lucide/vue';
import MoneyDisplay from '@/components/MoneyDisplay.vue';
import TransactionRow from '@/components/TransactionRow.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Accounts',
                href: '/accounts',
            },
            {
                title: 'Account Details',
                href: '#',
            },
        ],
    },
});

const props = defineProps<{
    account: any;
    transactions: any;
}>();

const handleEdit = (transaction: any) => {
    router.get(`/transactions/${transaction.id}/edit`);
};

const handleDelete = (transaction: any) => {
    if (confirm('Are you sure you want to delete this transaction?')) {
        router.delete(`/transactions/${transaction.id}`);
    }
};
</script>

<template>
    <Head :title="account.name" />

    <div
        class="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6 lg:p-8"
    >
        <!-- Header -->
        <div
            class="mb-2 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center"
        >
            <div class="flex items-center gap-4">
                <Link
                    href="/accounts"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                >
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-3xl font-bold tracking-tight">
                            {{ account.name }}
                        </h1>
                        <span
                            v-if="!account.is_active"
                            class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground"
                            >Inactive</span
                        >
                    </div>
                    <p
                        class="mt-1 text-sm font-medium text-muted-foreground uppercase"
                    >
                        {{ account.type }} &bull; {{ account.currency }}
                    </p>
                </div>
            </div>

            <div class="flex w-full gap-2 sm:w-auto">
                <Link
                    :href="`/accounts/${account.id}/edit`"
                    class="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50 sm:flex-none"
                >
                    <Edit class="h-4 w-4" />
                    Edit
                </Link>
                <Link
                    href="/transactions/create"
                    class="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50 sm:flex-none"
                >
                    <Plus class="h-4 w-4" />
                    Transaction
                </Link>
            </div>
        </div>

        <div class="grid gap-6 md:grid-cols-3">
            <!-- Left Column: Balance & Details -->
            <div class="space-y-6 md:col-span-1">
                <!-- Balance Card -->
                <div
                    class="relative overflow-hidden rounded-xl border bg-card text-card-foreground shadow"
                >
                    <div
                        class="pointer-events-none absolute inset-0 bg-gradient-to-br from-primary/10 via-transparent to-transparent"
                    ></div>
                    <div class="relative z-10 flex flex-col gap-2 p-6">
                        <p class="text-sm font-medium text-muted-foreground">
                            Current Balance
                        </p>
                        <h2 class="text-3xl font-bold tracking-tight">
                            <MoneyDisplay
                                :amount="account.current_balance"
                                :currency="account.currency"
                            />
                        </h2>
                    </div>
                </div>

                <!-- Info Card -->
                <div
                    class="rounded-xl border bg-card text-card-foreground shadow"
                >
                    <div class="p-6">
                        <h3 class="mb-4 font-medium">Account Details</h3>
                        <dl class="space-y-4 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-muted-foreground">
                                    Initial Balance
                                </dt>
                                <dd class="font-medium">
                                    <MoneyDisplay
                                        :amount="account.initial_balance"
                                        :currency="account.currency"
                                    />
                                </dd>
                            </div>
                            <div class="flex justify-between border-t pt-4">
                                <dt class="text-muted-foreground">Created</dt>
                                <dd class="font-medium">
                                    {{
                                        new Date(
                                            account.created_at,
                                        ).toLocaleDateString()
                                    }}
                                </dd>
                            </div>
                            <div
                                v-if="account.description"
                                class="border-t pt-4"
                            >
                                <dt class="mb-1 text-muted-foreground">
                                    Notes
                                </dt>
                                <dd class="text-sm font-medium">
                                    {{ account.description }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Right Column: Transactions List -->
            <div class="md:col-span-2">
                <div
                    class="flex h-full flex-col rounded-xl border bg-card text-card-foreground shadow"
                >
                    <div class="flex items-center justify-between border-b p-6">
                        <h3 class="font-medium">Recent Transactions</h3>
                        <Link
                            href="/transactions"
                            class="text-sm text-primary hover:underline"
                            >View All</Link
                        >
                    </div>

                    <div
                        v-if="transactions.data.length > 0"
                        class="flex-1 divide-y"
                    >
                        <TransactionRow
                            v-for="tx in transactions.data"
                            :key="tx.id"
                            :transaction="tx"
                            @edit="handleEdit"
                            @delete="handleDelete"
                        />
                    </div>

                    <div
                        v-else
                        class="flex flex-1 flex-col items-center justify-center p-12 text-center"
                    >
                        <div
                            class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-muted"
                        >
                            <FileText class="h-8 w-8 text-muted-foreground" />
                        </div>
                        <h4 class="mb-1 text-lg font-medium">
                            No transactions yet
                        </h4>
                        <p class="mb-6 text-sm text-muted-foreground">
                            Create a transaction to see it here.
                        </p>
                        <Link
                            href="/transactions/create"
                            class="inline-flex h-9 items-center justify-center gap-2 rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50"
                        >
                            <Plus class="h-4 w-4" />
                            Add Transaction
                        </Link>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="
                            transactions.links && transactions.links.length > 3
                        "
                        class="flex justify-center border-t p-4"
                    >
                        <nav class="flex items-center space-x-1">
                            <!-- Basic pagination for now -->
                            <Link
                                v-for="(link, index) in transactions.links"
                                :key="index"
                                :href="link.url || '#'"
                                :class="[
                                    'rounded-md px-3 py-1 text-sm transition-colors',
                                    link.active
                                        ? 'bg-primary text-primary-foreground'
                                        : 'text-muted-foreground hover:bg-muted',
                                    !link.url
                                        ? 'cursor-not-allowed opacity-50'
                                        : '',
                                ]"
                                v-html="link.label"
                            />
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
