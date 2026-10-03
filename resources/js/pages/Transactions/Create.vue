<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Save,
    Loader2,
    ArrowUpRight,
    ArrowDownRight,
    ArrowRightLeft,
} from '@lucide/vue';
import dayjs from 'dayjs';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Transactions',
                href: '/transactions',
            },
            {
                title: 'Add Transaction',
                href: '/transactions/create',
            },
        ],
    },
});

const props = defineProps<{
    accounts: any[];
    categories: any[];
    transactionTypes: string[];
    defaultAccountId?: string;
    type?: string;
}>();

const form = useForm({
    type: props.type || 'expense',
    amount: null,
    date: dayjs().format('YYYY-MM-DD'),
    description: '',
    category_id: '',
    account_id:
        props.defaultAccountId ||
        (props.accounts.length > 0 ? props.accounts[0].id : ''),
    source_account_id: '',
    destination_account_id: '',
    notes: '',
});

const submit = () => {
    form.post('/transactions');
};

const setType = (newType: string) => {
    form.type = newType;
};
</script>

<template>
    <Head title="Add Transaction" />

    <div
        class="mx-auto flex h-full w-full max-w-3xl flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6 lg:p-8"
    >
        <!-- Header -->
        <div class="mb-2 flex items-center gap-4">
            <Link
                href="/transactions"
                class="inline-flex h-9 w-9 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
            >
                <ArrowLeft class="h-5 w-5" />
            </Link>
            <div>
                <h1 class="text-3xl font-bold tracking-tight">
                    Add Transaction
                </h1>
                <p class="mt-1 text-muted-foreground">
                    Record a new income, expense, or transfer.
                </p>
            </div>
        </div>

        <!-- Form Card -->
        <div
            class="overflow-hidden rounded-xl border bg-card text-card-foreground shadow"
        >
            <!-- Type Selector Tabs -->
            <div class="flex gap-2 border-b bg-muted/50 p-2">
                <button
                    type="button"
                    @click="setType('expense')"
                    :class="[
                        'flex flex-1 items-center justify-center gap-2 rounded-md px-4 py-2.5 text-sm font-medium transition-all',
                        form.type === 'expense'
                            ? 'bg-background text-foreground shadow-sm'
                            : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                    ]"
                >
                    <ArrowDownRight class="h-4 w-4 text-red-500" />
                    Expense
                </button>
                <button
                    type="button"
                    @click="setType('income')"
                    :class="[
                        'flex flex-1 items-center justify-center gap-2 rounded-md px-4 py-2.5 text-sm font-medium transition-all',
                        form.type === 'income'
                            ? 'bg-background text-foreground shadow-sm'
                            : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                    ]"
                >
                    <ArrowUpRight class="h-4 w-4 text-emerald-500" />
                    Income
                </button>
                <button
                    type="button"
                    @click="setType('transfer')"
                    :class="[
                        'flex flex-1 items-center justify-center gap-2 rounded-md px-4 py-2.5 text-sm font-medium transition-all',
                        form.type === 'transfer'
                            ? 'bg-background text-foreground shadow-sm'
                            : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                    ]"
                >
                    <ArrowRightLeft class="h-4 w-4 text-blue-500" />
                    Transfer
                </button>
            </div>

            <form @submit.prevent="submit" class="space-y-8 p-6 md:p-8">
                <!-- Amount -->
                <div class="space-y-2">
                    <label
                        for="amount"
                        class="mb-4 block text-center text-sm leading-none font-medium text-muted-foreground"
                        >Amount</label
                    >
                    <div class="relative mx-auto max-w-xs">
                        <div
                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-2xl font-medium text-muted-foreground"
                        >
                            Rp
                        </div>
                        <input
                            id="amount"
                            v-model="form.amount"
                            type="number"
                            class="flex h-16 w-full rounded-md border-0 border-b-2 border-input bg-transparent py-2 pr-3 pl-12 text-center text-4xl font-bold focus-visible:border-primary focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            placeholder="0"
                            required
                        />
                    </div>
                    <p
                        v-if="form.errors.amount"
                        class="text-center text-sm text-destructive"
                    >
                        {{ form.errors.amount }}
                    </p>
                </div>

                <!-- Basic Details -->
                <div class="grid gap-4 pt-4 md:grid-cols-2">
                    <div class="space-y-2 md:col-span-2">
                        <label
                            for="description"
                            class="text-sm leading-none font-medium"
                            >Description</label
                        >
                        <input
                            id="description"
                            v-model="form.description"
                            type="text"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                            placeholder="What was this for?"
                            required
                        />
                        <p
                            v-if="form.errors.description"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.description }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label
                            for="date"
                            class="text-sm leading-none font-medium"
                            >Date</label
                        >
                        <input
                            id="date"
                            v-model="form.date"
                            type="date"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                            required
                        />
                        <p
                            v-if="form.errors.date"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.date }}
                        </p>
                    </div>

                    <div v-if="form.type !== 'transfer'" class="space-y-2">
                        <label
                            for="category_id"
                            class="text-sm leading-none font-medium"
                            >Category</label
                        >
                        <select
                            id="category_id"
                            v-model="form.category_id"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                        >
                            <option value="">Select a category</option>
                            <option
                                v-for="cat in categories"
                                :key="cat.id"
                                :value="cat.id"
                            >
                                {{ cat.name }}
                            </option>
                        </select>
                        <p
                            v-if="form.errors.category_id"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.category_id }}
                        </p>
                    </div>
                </div>

                <!-- Account Selection -->
                <div class="space-y-4 border-t pt-4">
                    <h3
                        class="text-sm font-medium tracking-wider text-muted-foreground uppercase"
                    >
                        Account Details
                    </h3>

                    <!-- Single Account (Income/Expense) -->
                    <div v-if="form.type !== 'transfer'" class="space-y-2">
                        <label
                            for="account_id"
                            class="text-sm leading-none font-medium"
                            >Account</label
                        >
                        <select
                            id="account_id"
                            v-model="form.account_id"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                            required
                        >
                            <option value="" disabled>Select an account</option>
                            <option
                                v-for="acc in accounts"
                                :key="acc.id"
                                :value="acc.id"
                            >
                                {{ acc.name }} ({{ acc.currency }})
                            </option>
                        </select>
                        <p
                            v-if="form.errors.account_id"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.account_id }}
                        </p>
                    </div>

                    <!-- Transfer Accounts -->
                    <div
                        v-if="form.type === 'transfer'"
                        class="grid gap-4 md:grid-cols-2"
                    >
                        <div class="relative space-y-2">
                            <label
                                for="source_account_id"
                                class="text-sm leading-none font-medium text-red-500"
                                >From Account (Source)</label
                            >
                            <select
                                id="source_account_id"
                                v-model="form.source_account_id"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                                required
                            >
                                <option value="" disabled>Select source</option>
                                <option
                                    v-for="acc in accounts"
                                    :key="acc.id"
                                    :value="acc.id"
                                >
                                    {{ acc.name }}
                                </option>
                            </select>
                            <p
                                v-if="form.errors.source_account_id"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.source_account_id }}
                            </p>
                        </div>

                        <div class="relative space-y-2">
                            <div
                                class="absolute top-1/2 -left-4 z-10 mt-3 hidden h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full border bg-muted text-muted-foreground md:flex"
                            >
                                <ArrowRightLeft class="h-4 w-4" />
                            </div>
                            <label
                                for="destination_account_id"
                                class="text-sm leading-none font-medium text-emerald-500"
                                >To Account (Destination)</label
                            >
                            <select
                                id="destination_account_id"
                                v-model="form.destination_account_id"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                                required
                            >
                                <option value="" disabled>
                                    Select destination
                                </option>
                                <option
                                    v-for="acc in accounts"
                                    :key="acc.id"
                                    :value="acc.id"
                                    :disabled="
                                        acc.id === form.source_account_id
                                    "
                                >
                                    {{ acc.name }}
                                </option>
                            </select>
                            <p
                                v-if="form.errors.destination_account_id"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.destination_account_id }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="space-y-2 border-t pt-4">
                    <label
                        for="notes"
                        class="text-sm leading-none font-medium text-muted-foreground"
                        >Additional Notes</label
                    >
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="2"
                        class="flex min-h-[60px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                        placeholder="Optional details..."
                    ></textarea>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-4 border-t pt-6">
                    <Link
                        href="/transactions"
                        class="inline-flex h-10 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-primary px-6 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50"
                    >
                        <Loader2
                            v-if="form.processing"
                            class="h-4 w-4 animate-spin"
                        />
                        <Save v-else class="h-4 w-4" />
                        {{ form.processing ? 'Saving...' : 'Save Transaction' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
