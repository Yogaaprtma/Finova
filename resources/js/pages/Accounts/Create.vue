<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, Loader2 } from '@lucide/vue';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Accounts',
                href: '/accounts',
            },
            {
                title: 'Create Account',
                href: '/accounts/create',
            },
        ],
    },
});

const props = defineProps<{
    accountTypes: string[];
}>();

const form = useForm({
    name: '',
    type: 'cash',
    currency: 'IDR',
    initial_balance: 0,
    description: '',
});

const submit = () => {
    form.post('/accounts');
};
</script>

<template>
    <Head title="Create Account" />

    <div
        class="mx-auto flex h-full w-full max-w-3xl flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6 lg:p-8"
    >
        <!-- Header -->
        <div class="mb-2 flex items-center gap-4">
            <Link
                href="/accounts"
                class="inline-flex h-9 w-9 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
            >
                <ArrowLeft class="h-5 w-5" />
            </Link>
            <div>
                <h1 class="text-3xl font-bold tracking-tight">
                    Create Account
                </h1>
                <p class="mt-1 text-muted-foreground">
                    Add a new financial account to track your money.
                </p>
            </div>
        </div>

        <!-- Form Card -->
        <div class="rounded-xl border bg-card text-card-foreground shadow">
            <form @submit.prevent="submit" class="space-y-8 p-6 md:p-8">
                <!-- Account Basic Info -->
                <div class="space-y-4">
                    <h3 class="border-b pb-2 text-lg font-medium">
                        Basic Information
                    </h3>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2 md:col-span-2">
                            <label
                                for="name"
                                class="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                >Account Name</label
                            >
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="e.g., Main BCA, Cash Wallet, Credit Card"
                                required
                            />
                            <p
                                v-if="form.errors.name"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <label
                                for="type"
                                class="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                >Account Type</label
                            >
                            <select
                                id="type"
                                v-model="form.type"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                required
                            >
                                <option
                                    v-for="type in accountTypes"
                                    :key="type"
                                    :value="type"
                                >
                                    {{
                                        type.charAt(0).toUpperCase() +
                                        type.slice(1)
                                    }}
                                </option>
                            </select>
                            <p
                                v-if="form.errors.type"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.type }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <label
                                for="currency"
                                class="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                >Currency</label
                            >
                            <select
                                id="currency"
                                v-model="form.currency"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                required
                            >
                                <option value="IDR">
                                    IDR - Indonesian Rupiah
                                </option>
                                <option value="USD">USD - US Dollar</option>
                                <option value="EUR">EUR - Euro</option>
                                <option value="SGD">
                                    SGD - Singapore Dollar
                                </option>
                            </select>
                            <p
                                v-if="form.errors.currency"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.currency }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Balance Info -->
                <div class="space-y-4">
                    <h3 class="border-b pb-2 text-lg font-medium">
                        Initial Balance
                    </h3>

                    <div class="space-y-2">
                        <label
                            for="initial_balance"
                            class="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                            >Starting Balance (in {{ form.currency }})</label
                        >
                        <div class="relative">
                            <div
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 font-medium text-muted-foreground"
                            >
                                Rp
                            </div>
                            <input
                                id="initial_balance"
                                v-model="form.initial_balance"
                                type="number"
                                class="flex h-12 w-full rounded-md border border-input bg-background py-2 pr-3 pl-10 text-lg font-semibold ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="0"
                                required
                            />
                        </div>
                        <p class="text-xs text-muted-foreground">
                            The current amount of money in this account right
                            now.
                        </p>
                        <p
                            v-if="form.errors.initial_balance"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.initial_balance }}
                        </p>
                    </div>
                </div>

                <!-- Additional Info -->
                <div class="space-y-4">
                    <h3 class="border-b pb-2 text-lg font-medium">
                        Additional Information
                        <span class="text-sm font-normal text-muted-foreground"
                            >(Optional)</span
                        >
                    </h3>

                    <div class="space-y-2">
                        <label
                            for="description"
                            class="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                            >Description or Notes</label
                        >
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            placeholder="Brief description about this account..."
                        ></textarea>
                        <p
                            v-if="form.errors.description"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.description }}
                        </p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-4 border-t pt-6">
                    <Link
                        href="/accounts"
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
                        {{ form.processing ? 'Saving...' : 'Save Account' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
