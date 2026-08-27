<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Plus,
    Wallet,
    CreditCard,
    Building2,
    Landmark,
    Pencil,
    Trash2,
    ArrowRight,
} from '@lucide/vue';
import { ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import MoneyDisplay from '@/components/MoneyDisplay.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Accounts',
                href: '/accounts',
            },
        ],
    },
});

defineProps<{
    accounts: any[];
    totalBalance: number;
}>();

const deleteTarget = ref<any | null>(null);

const confirmDelete = (account: any) => {
    deleteTarget.value = account;
};

const executeDelete = () => {
    if (!deleteTarget.value) {
        return;
    }

    router.delete(`/accounts/${deleteTarget.value.id}`, {
        onSuccess: () => {
            deleteTarget.value = null;
        },
    });
};

const getIconForType = (type: string) => {
    switch (type) {
        case 'cash':
            return Wallet;
        case 'bank':
            return Landmark;
        case 'credit':
            return CreditCard;
        case 'investment':
            return Building2;
        default:
            return Wallet;
    }
};
</script>

<template>
    <Head title="Accounts" />

    <div
        class="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-5 p-4 sm:gap-6 md:p-6 lg:p-8"
    >
        <!-- Header -->
        <div
            class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center"
        >
            <div>
                <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">
                    Accounts
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Manage your financial accounts and balances.
                </p>
            </div>
            <Link
                href="/accounts/create"
                class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:outline-none sm:w-auto"
            >
                <Plus class="size-4" />
                Add Account
            </Link>
        </div>

        <!-- Total Balance Card -->
        <div
            class="rounded-xl border bg-card p-5 text-card-foreground shadow-sm sm:p-6"
        >
            <div
                class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <p
                        class="text-xs font-medium tracking-wider text-muted-foreground uppercase"
                    >
                        Total Net Balance
                    </p>
                    <h2
                        class="mt-1 text-3xl font-bold tracking-tight tabular-nums sm:text-4xl"
                    >
                        <MoneyDisplay :amount="totalBalance" />
                    </h2>
                </div>
                <div v-if="accounts.length > 0" class="mt-1 sm:mt-0">
                    <span
                        class="inline-flex items-center rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary"
                    >
                        {{ accounts.length }}
                        {{ accounts.length === 1 ? 'Account' : 'Accounts' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Accounts Grid -->
        <div
            v-if="accounts.length > 0"
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
        >
            <div
                v-for="account in accounts"
                :key="account.id"
                class="group flex flex-col justify-between rounded-xl border bg-card text-card-foreground shadow-sm transition-all hover:border-primary/40 hover:shadow-md"
            >
                <div class="p-5 sm:p-6">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div
                            class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                        >
                            <component
                                :is="getIconForType(account.type)"
                                class="size-5"
                            />
                        </div>

                        <!-- Actions: easily clickable on mobile, hover on desktop -->
                        <div class="flex items-center gap-0.5">
                            <Link
                                :href="`/accounts/${account.id}/edit`"
                                class="flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                title="Edit"
                            >
                                <Pencil class="size-4" />
                            </Link>
                            <button
                                type="button"
                                @click="confirmDelete(account)"
                                class="flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-destructive"
                                title="Delete"
                            >
                                <Trash2 class="size-4" />
                            </button>
                        </div>
                    </div>

                    <div class="min-w-0">
                        <h3 class="truncate text-base font-semibold sm:text-lg">
                            <Link
                                :href="`/accounts/${account.id}`"
                                class="hover:underline"
                            >
                                {{ account.name }}
                            </Link>
                        </h3>
                        <span
                            class="mt-1 inline-block rounded-md bg-muted px-2 py-0.5 text-[11px] font-medium tracking-wider text-muted-foreground uppercase"
                        >
                            {{ account.type }}
                        </span>
                    </div>

                    <div class="mt-5">
                        <p class="text-xs font-medium text-muted-foreground">
                            Current Balance
                        </p>
                        <p
                            class="mt-0.5 text-2xl font-bold tracking-tight tabular-nums"
                        >
                            <MoneyDisplay
                                :amount="account.current_balance"
                                :currency="account.currency"
                            />
                        </p>
                    </div>
                </div>

                <div
                    class="flex items-center justify-between border-t bg-muted/20 px-5 py-3 text-xs sm:px-6"
                >
                    <span class="inline-flex items-center gap-1.5 font-medium">
                        <span
                            class="size-2 rounded-full"
                            :class="
                                account.is_active
                                    ? 'bg-emerald-500'
                                    : 'bg-muted-foreground'
                            "
                        ></span>
                        <span
                            :class="
                                account.is_active
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-muted-foreground'
                            "
                        >
                            {{ account.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </span>

                    <Link
                        :href="`/accounts/${account.id}`"
                        class="inline-flex items-center gap-1 font-medium text-primary hover:underline"
                    >
                        View Details
                        <ArrowRight class="size-3.5" />
                    </Link>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <EmptyState
            v-else
            :icon="Wallet"
            title="No accounts found"
            description="You haven't created any financial accounts yet. Add your bank accounts, wallets, or credit cards to start tracking."
            action-text="Add Your First Account"
            action-href="/accounts/create"
        />

        <!-- Confirmation Delete Modal -->
        <div
            v-if="deleteTarget"
            class="fixed inset-0 z-50 flex items-center justify-center bg-background/80 p-4 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-sm rounded-xl border bg-card p-5 text-card-foreground shadow-lg sm:p-6"
            >
                <h3 class="text-base font-semibold sm:text-lg">
                    Delete Account
                </h3>
                <p class="mt-2 text-sm text-muted-foreground">
                    Are you sure you want to delete
                    <span class="font-medium text-foreground"
                        >&ldquo;{{ deleteTarget.name }}&rdquo;</span
                    >?
                </p>
                <div class="mt-6 flex justify-end gap-2.5">
                    <button
                        type="button"
                        @click="deleteTarget = null"
                        class="inline-flex h-9 items-center justify-center rounded-lg border border-input bg-background px-3.5 text-sm font-medium transition-colors hover:bg-muted"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="executeDelete"
                        class="inline-flex h-9 items-center justify-center rounded-lg bg-destructive px-3.5 text-sm font-medium text-destructive-foreground transition-colors hover:bg-destructive/90"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
