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
                title: 'Edit Account',
                href: '#',
            },
        ],
    },
});

const props = defineProps<{
    account: any;
    accountTypes: string[];
}>();

const form = useForm({
    name: props.account.name,
    type: props.account.type,
    currency: props.account.currency,
    description: props.account.description || '',
    is_active: props.account.is_active,
});

const submit = () => {
    form.put(`/accounts/${props.account.id}`);
};
</script>

<template>
    <Head title="Edit Account" />

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
                <h1 class="text-3xl font-bold tracking-tight">Edit Account</h1>
                <p class="mt-1 text-muted-foreground">
                    Update your account details.
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
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
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

                <!-- Additional Info -->
                <div class="space-y-4">
                    <h3 class="border-b pb-2 text-lg font-medium">
                        Additional Information
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
                        ></textarea>
                        <p
                            v-if="form.errors.description"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.description }}
                        </p>
                    </div>

                    <div class="flex items-center space-x-2 pt-2">
                        <input
                            type="checkbox"
                            id="is_active"
                            v-model="form.is_active"
                            class="h-4 w-4 rounded border-input text-primary focus:ring-primary"
                        />
                        <label
                            for="is_active"
                            class="text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                        >
                            Active Account
                        </label>
                    </div>
                    <p class="pl-6 text-sm text-muted-foreground">
                        Inactive accounts are hidden from most lists but retain
                        their history.
                    </p>
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
                        {{ form.processing ? 'Saving...' : 'Update Account' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
