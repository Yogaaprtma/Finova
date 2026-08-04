<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Edit,
    Trash2,
    Calendar,
    FileText,
    Tag,
    Hash,
    Building,
} from '@lucide/vue';
import dayjs from 'dayjs';
import { ref } from 'vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Transactions',
                href: '/transactions',
            },
            {
                title: 'Detail',
                href: '#',
            },
        ],
    },
});

const props = defineProps<{
    transaction: any;
}>();

const showDeleteModal = ref(false);

const confirmDelete = () => {
    showDeleteModal.value = true;
};

const executeDelete = () => {
    router.delete(`/transactions/${props.transaction.id}`, {
        onSuccess: () => {
            showDeleteModal.value = false;
        },
    });
};

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
};

const getSourceAccount = () => {
    if (props.transaction.type === 'transfer') {
        const entry = props.transaction.entries.find(
            (e: any) => e.entry_type === 'debit',
        );

        return entry?.account;
    }

    const entry = props.transaction.entries.find(
        (e: any) => e.account_id !== null,
    );

    return entry?.account;
};

const getDestAccount = () => {
    if (props.transaction.type === 'transfer') {
        const entry = props.transaction.entries.find(
            (e: any) => e.entry_type === 'credit',
        );

        return entry?.account;
    }

    return null;
};
</script>

<template>
    <Head title="Transaction Details" />

    <div
        class="mx-auto flex h-full w-full max-w-3xl flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6 lg:p-8"
    >
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link
                    href="/transactions"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                >
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">
                        Transaction Details
                    </h1>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <Link
                    :href="`/transactions/${transaction.id}/edit`"
                    class="inline-flex h-9 items-center justify-center gap-2 rounded-md bg-secondary px-4 py-2 text-sm font-medium text-secondary-foreground shadow-sm transition-colors hover:bg-secondary/80 focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                >
                    <Edit class="h-4 w-4" />
                    <span class="hidden sm:inline">Edit</span>
                </Link>
                <button
                    @click="confirmDelete"
                    class="inline-flex h-9 items-center justify-center gap-2 rounded-md bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground shadow-sm transition-colors hover:bg-destructive/90 focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                >
                    <Trash2 class="h-4 w-4" />
                    <span class="hidden sm:inline">Delete</span>
                </button>
            </div>
        </div>

        <!-- Detail Card -->
        <div
            class="overflow-hidden rounded-xl border bg-card text-card-foreground shadow"
        >
            <!-- Top section (Amount and Type) -->
            <div
                class="flex flex-col items-center justify-center border-b bg-muted/30 p-8"
            >
                <div
                    class="mb-2 text-sm font-medium tracking-wider text-muted-foreground uppercase"
                    :class="{
                        'text-emerald-500': transaction.type === 'income',
                        'text-red-500': transaction.type === 'expense',
                        'text-blue-500': transaction.type === 'transfer',
                    }"
                >
                    {{ transaction.type }}
                </div>
                <div class="text-5xl font-bold tracking-tight">
                    {{ formatCurrency(transaction.amount) }}
                </div>
                <div class="mt-4 text-lg font-medium">
                    {{ transaction.description }}
                </div>
            </div>

            <!-- Details List -->
            <div class="p-0">
                <dl class="divide-y divide-border">
                    <!-- Date -->
                    <div class="flex px-6 py-4">
                        <dt
                            class="flex w-1/3 items-center gap-2 text-sm font-medium text-muted-foreground"
                        >
                            <Calendar class="h-4 w-4" /> Date
                        </dt>
                        <dd class="w-2/3 text-sm font-medium text-foreground">
                            {{ dayjs(transaction.date).format('MMMM D, YYYY') }}
                        </dd>
                    </div>

                    <!-- Category -->
                    <div
                        class="flex px-6 py-4"
                        v-if="transaction.type !== 'transfer'"
                    >
                        <dt
                            class="flex w-1/3 items-center gap-2 text-sm font-medium text-muted-foreground"
                        >
                            <Tag class="h-4 w-4" /> Category
                        </dt>
                        <dd
                            class="flex w-2/3 items-center gap-2 text-sm font-medium text-foreground"
                        >
                            <template v-if="transaction.category">
                                <span
                                    class="flex h-6 w-6 items-center justify-center rounded-full text-xs"
                                    :style="{
                                        backgroundColor: `${transaction.category.color}20`,
                                        color: transaction.category.color,
                                    }"
                                >
                                    {{ transaction.category.icon }}
                                </span>
                                {{ transaction.category.name }}
                            </template>
                            <template v-else>
                                <span class="text-muted-foreground italic"
                                    >Uncategorized</span
                                >
                            </template>
                        </dd>
                    </div>

                    <!-- Account -->
                    <div class="flex px-6 py-4">
                        <dt
                            class="flex w-1/3 items-center gap-2 text-sm font-medium text-muted-foreground"
                        >
                            <Building class="h-4 w-4" />
                            {{
                                transaction.type === 'transfer'
                                    ? 'From Account'
                                    : 'Account'
                            }}
                        </dt>
                        <dd class="w-2/3 text-sm font-medium text-foreground">
                            {{ getSourceAccount()?.name || 'Unknown' }}
                        </dd>
                    </div>

                    <!-- Destination Account -->
                    <div
                        class="flex px-6 py-4"
                        v-if="transaction.type === 'transfer'"
                    >
                        <dt
                            class="flex w-1/3 items-center gap-2 text-sm font-medium text-muted-foreground"
                        >
                            <Building class="h-4 w-4" /> To Account
                        </dt>
                        <dd class="w-2/3 text-sm font-medium text-foreground">
                            {{ getDestAccount()?.name || 'Unknown' }}
                        </dd>
                    </div>

                    <!-- Notes -->
                    <div class="flex px-6 py-4" v-if="transaction.notes">
                        <dt
                            class="flex w-1/3 items-center gap-2 text-sm font-medium text-muted-foreground"
                        >
                            <FileText class="h-4 w-4" /> Notes
                        </dt>
                        <dd
                            class="w-2/3 text-sm whitespace-pre-wrap text-foreground"
                        >
                            {{ transaction.notes }}
                        </dd>
                    </div>

                    <!-- Transaction ID -->
                    <div class="flex px-6 py-4">
                        <dt
                            class="flex w-1/3 items-center gap-2 text-sm font-medium text-muted-foreground"
                        >
                            <Hash class="h-4 w-4" /> Reference ID
                        </dt>
                        <dd
                            class="w-2/3 font-mono text-xs text-muted-foreground"
                        >
                            {{ transaction.id }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Confirmation Delete Modal -->
        <div
            v-if="showDeleteModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-background/80 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-md rounded-xl border bg-card p-6 text-card-foreground shadow-lg"
            >
                <h3 class="text-lg font-semibold">Delete Transaction</h3>
                <p class="mt-2 text-sm text-muted-foreground">
                    Are you sure you want to delete this transaction? This
                    action will reverse all double-entry ledger balances.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        @click="showDeleteModal = false"
                        class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-muted"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="executeDelete"
                        class="rounded-md bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground hover:bg-destructive/90"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
