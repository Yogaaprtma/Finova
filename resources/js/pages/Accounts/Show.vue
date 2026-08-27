<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Edit, Plus, FileText, ArrowRight } from '@lucide/vue';
import dayjs from 'dayjs';
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

defineProps<{
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

const formatDate = (dateStr: string) => {
    if (!dateStr) {
        return '-';
    }

    return dayjs(dateStr).format('D MMM YYYY');
};
</script>

<template>
    <Head :title="account.name" />

    <div
        class="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-5 p-4 sm:gap-6 md:p-6 lg:p-8"
    >
        <!-- Header -->
        <div
            class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center"
        >
            <div class="flex min-w-0 items-center gap-3 sm:gap-4">
                <Link
                    href="/accounts"
                    class="inline-flex size-9 shrink-0 items-center justify-center rounded-lg border border-input bg-background text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                    aria-label="Back to accounts"
                >
                    <ArrowLeft class="size-4" />
                </Link>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h1
                            class="truncate text-xl font-bold tracking-tight sm:text-2xl md:text-3xl"
                        >
                            {{ account.name }}
                        </h1>
                        <span
                            v-if="!account.is_active"
                            class="shrink-0 rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground"
                        >
                            Inactive
                        </span>
                    </div>
                    <p
                        class="mt-0.5 text-xs font-medium tracking-wider text-muted-foreground uppercase"
                    >
                        {{ account.type }} &bull; {{ account.currency }}
                    </p>
                </div>
            </div>

            <div class="grid w-full grid-cols-2 gap-2 sm:flex sm:w-auto">
                <Link
                    :href="`/accounts/${account.id}/edit`"
                    class="inline-flex h-10 items-center justify-center gap-1.5 rounded-lg border border-input bg-background px-4 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                >
                    <Edit class="size-4" />
                    Edit
                </Link>
                <Link
                    href="/transactions/create"
                    class="inline-flex h-10 items-center justify-center gap-1.5 rounded-lg bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none"
                >
                    <Plus class="size-4" />
                    Transaction
                </Link>
            </div>
        </div>

        <div class="grid gap-5 md:grid-cols-3">
            <!-- Left Column: Balance & Details -->
            <div class="space-y-5 md:col-span-1">
                <!-- Balance Card -->
                <div
                    class="rounded-xl border bg-card p-5 text-card-foreground shadow-sm sm:p-6"
                >
                    <p
                        class="text-xs font-medium tracking-wider text-muted-foreground uppercase"
                    >
                        Current Balance
                    </p>
                    <p
                        class="mt-1.5 text-2xl font-bold tracking-tight tabular-nums sm:text-3xl"
                    >
                        <MoneyDisplay
                            :amount="account.current_balance"
                            :currency="account.currency"
                        />
                    </p>
                </div>

                <!-- Info Card -->
                <div
                    class="rounded-xl border bg-card p-5 text-card-foreground shadow-sm sm:p-6"
                >
                    <h3 class="text-sm font-semibold tracking-tight">
                        Account Details
                    </h3>
                    <dl class="mt-4 divide-y text-sm">
                        <div
                            class="flex items-center justify-between py-2.5 first:pt-0"
                        >
                            <dt class="text-muted-foreground">
                                Initial Balance
                            </dt>
                            <dd class="font-medium tabular-nums">
                                <MoneyDisplay
                                    :amount="account.initial_balance"
                                    :currency="account.currency"
                                />
                            </dd>
                        </div>
                        <div class="flex items-center justify-between py-2.5">
                            <dt class="text-muted-foreground">Created</dt>
                            <dd
                                class="font-medium text-muted-foreground tabular-nums"
                            >
                                {{ formatDate(account.created_at) }}
                            </dd>
                        </div>
                        <div
                            v-if="account.description"
                            class="py-2.5 last:pb-0"
                        >
                            <dt class="mb-1 text-muted-foreground">Notes</dt>
                            <dd
                                class="text-sm font-medium break-words text-foreground"
                            >
                                {{ account.description }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Right Column: Transactions List -->
            <div class="md:col-span-2">
                <div
                    class="flex h-full flex-col rounded-xl border bg-card text-card-foreground shadow-sm"
                >
                    <div
                        class="flex items-center justify-between border-b px-5 py-4 sm:px-6"
                    >
                        <h3 class="text-base font-semibold tracking-tight">
                            Recent Transactions
                        </h3>
                        <Link
                            href="/transactions"
                            class="inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline"
                        >
                            View All
                            <ArrowRight class="size-3.5" />
                        </Link>
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
                        class="flex flex-1 flex-col items-center justify-center p-8 text-center sm:p-12"
                    >
                        <div
                            class="mb-3 flex size-12 items-center justify-center rounded-xl bg-muted text-muted-foreground sm:size-14"
                        >
                            <FileText class="size-6" />
                        </div>
                        <h4 class="text-base font-semibold">
                            No transactions yet
                        </h4>
                        <p class="mt-1 mb-5 text-xs text-muted-foreground">
                            Transactions recorded for this account will appear
                            here.
                        </p>
                        <Link
                            href="/transactions/create"
                            class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg border border-input bg-background px-3.5 text-xs font-medium transition-colors hover:bg-accent"
                        >
                            <Plus class="size-3.5" />
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
                        <nav
                            class="flex flex-wrap items-center justify-center gap-1"
                        >
                            <Link
                                v-for="(link, index) in transactions.links"
                                :key="index"
                                :href="link.url || '#'"
                                :class="[
                                    'inline-flex h-8 items-center justify-center rounded-md px-3 text-xs font-medium transition-colors',
                                    link.active
                                        ? 'bg-primary text-primary-foreground'
                                        : 'text-muted-foreground hover:bg-muted',
                                    !link.url
                                        ? 'cursor-not-allowed opacity-40'
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
