<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import {
    PlusCircle,
    Wallet,
    CreditCard,
    Building2,
    Landmark,
    MoreHorizontal,
    Pencil,
    Trash2,
} from '@lucide/vue';
import { dashboard } from '@/routes';
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

const props = defineProps<{
    accounts: any[];
    totalBalance: number;
}>();

const deleteAccount = (id: string) => {
    if (confirm('Are you sure you want to delete this account?')) {
        router.delete(`/accounts/${id}`);
    }
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
        class="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 md:p-6 lg:p-8"
    >
        <!-- Header -->
        <div
            class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center"
        >
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Accounts</h1>
                <p class="mt-1 text-muted-foreground">
                    Manage your financial accounts and balances.
                </p>
            </div>
            <Link
                href="/accounts/create"
                class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50 sm:w-auto"
            >
                <PlusCircle class="h-4 w-4" />
                Add Account
            </Link>
        </div>

        <!-- Total Balance Card -->
        <div
            class="relative overflow-hidden rounded-xl border bg-card text-card-foreground shadow"
        >
            <div
                class="pointer-events-none absolute inset-0 bg-gradient-to-br from-primary/10 via-transparent to-transparent"
            ></div>
            <div class="relative z-10 flex flex-col gap-2 p-6">
                <p class="text-sm font-medium text-muted-foreground">
                    Total Net Balance
                </p>
                <h2 class="text-4xl font-bold tracking-tight">
                    <MoneyDisplay :amount="totalBalance" />
                </h2>
            </div>
        </div>

        <!-- Accounts Grid -->
        <div
            v-if="accounts.length > 0"
            class="grid gap-4 md:grid-cols-2 lg:grid-cols-3"
        >
            <div
                v-for="account in accounts"
                :key="account.id"
                class="group flex flex-col rounded-xl border bg-card text-card-foreground shadow transition-all hover:border-primary/50 hover:shadow-md"
            >
                <div class="flex-1 p-6">
                    <div class="mb-4 flex items-start justify-between">
                        <div
                            class="rounded-lg bg-primary/10 p-2.5 text-primary"
                        >
                            <component
                                :is="getIconForType(account.type)"
                                class="h-5 w-5"
                            />
                        </div>

                        <div
                            class="flex opacity-0 transition-opacity group-hover:opacity-100"
                        >
                            <Link
                                :href="`/accounts/${account.id}/edit`"
                                class="rounded-md p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                title="Edit"
                            >
                                <Pencil class="h-4 w-4" />
                            </Link>
                            <button
                                @click="deleteAccount(account.id)"
                                class="rounded-md p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-destructive"
                                title="Delete"
                            >
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <h3 class="line-clamp-1 text-lg font-semibold">
                        <Link
                            :href="`/accounts/${account.id}`"
                            class="hover:underline"
                        >
                            {{ account.name }}
                        </Link>
                    </h3>
                    <p class="mb-4 text-sm text-muted-foreground uppercase">
                        {{ account.type }}
                    </p>

                    <div class="mt-auto">
                        <p
                            class="mb-1 text-sm font-medium text-muted-foreground"
                        >
                            Current Balance
                        </p>
                        <p class="text-2xl font-bold">
                            <MoneyDisplay
                                :amount="account.current_balance"
                                :currency="account.currency"
                            />
                        </p>
                    </div>
                </div>
                <div
                    class="flex items-center justify-between border-t bg-muted/30 px-6 py-4 text-sm"
                >
                    <span class="text-muted-foreground">
                        <span
                            v-if="account.is_active"
                            class="inline-flex items-center gap-1.5 text-emerald-500"
                        >
                            <span
                                class="h-2 w-2 rounded-full bg-emerald-500"
                            ></span>
                            Active
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center gap-1.5 text-muted-foreground"
                        >
                            <span
                                class="h-2 w-2 rounded-full bg-muted-foreground"
                            ></span>
                            Inactive
                        </span>
                    </span>
                    <Link
                        :href="`/accounts/${account.id}`"
                        class="font-medium text-primary hover:underline"
                    >
                        View Details →
                    </Link>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div
            v-else
            class="flex flex-col items-center justify-center rounded-xl border border-dashed p-12 text-center"
        >
            <div
                class="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-muted"
            >
                <Wallet class="h-10 w-10 text-muted-foreground" />
            </div>
            <h2 class="mb-2 text-2xl font-semibold">No accounts found</h2>
            <p class="mb-8 max-w-md text-muted-foreground">
                You haven't created any financial accounts yet. Add your bank
                accounts, wallets, or credit cards to start tracking.
            </p>
            <Link
                href="/accounts/create"
                class="inline-flex h-11 items-center justify-center gap-2 rounded-md bg-primary px-8 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50"
            >
                <PlusCircle class="h-5 w-5" />
                Add Your First Account
            </Link>
        </div>
    </div>
</template>
