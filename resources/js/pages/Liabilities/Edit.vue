<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import MoneyInput from '@/components/MoneyInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    liability: {
        id: string;
        name: string;
        type: string;
        currency: string;
        initial_balance: number;
        current_balance: number;
        credit_limit: number | null;
        interest_rate: number | null;
        minimum_payment: number | null;
        due_date_day: number | null;
        start_date: string | null;
        end_date: string | null;
        remaining_terms: number | null;
        is_active: boolean;
        notes: string | null;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Liabilities & Debt',
                href: '/liabilities',
            },
            {
                title: 'Edit Liability',
                href: '#',
            },
        ],
    },
});

const form = useForm({
    name: props.liability.name,
    currency: props.liability.currency,
    current_balance: props.liability.current_balance,
    credit_limit: props.liability.credit_limit,
    interest_rate: props.liability.interest_rate || undefined,
    minimum_payment: props.liability.minimum_payment,
    due_date_day: props.liability.due_date_day || undefined,
    start_date: props.liability.start_date || '',
    end_date: props.liability.end_date || '',
    remaining_terms: props.liability.remaining_terms || undefined,
    is_active: props.liability.is_active,
    notes: props.liability.notes || '',
});

const submit = () => {
    form.put(`/liabilities/${props.liability.id}`);
};
</script>

<template>
    <Head :title="`Edit ${liability.name}`" />

    <div
        class="mx-auto flex h-full w-full max-w-4xl flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8"
    >
        <!-- Header -->
        <div class="flex flex-col gap-1">
            <Link
                :href="`/liabilities/${liability.id}`"
                class="inline-flex items-center gap-1.5 text-xs font-medium text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft class="h-3.5 w-3.5" />
                Back to Details
            </Link>
            <h1 class="text-3xl font-bold tracking-tight">Edit Liability</h1>
            <p class="text-sm text-muted-foreground">
                Update account balance, credit limit, or payment schedule.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div class="space-y-4 rounded-2xl border bg-card p-6 shadow-sm">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <!-- Name -->
                    <div class="space-y-1.5 sm:col-span-2">
                        <Label for="name" class="text-xs font-semibold">
                            Liability Name *
                        </Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            type="text"
                            required
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <!-- Current Balance -->
                    <div class="space-y-1.5">
                        <Label
                            for="current_balance"
                            class="text-xs font-semibold"
                        >
                            Current Outstanding Balance
                        </Label>
                        <MoneyInput
                            id="current_balance"
                            v-model="form.current_balance"
                            placeholder="0"
                            currency="IDR"
                        />
                        <InputError :message="form.errors.current_balance" />
                    </div>

                    <!-- Credit Limit -->
                    <div
                        v-if="liability.type === 'credit_card'"
                        class="space-y-1.5"
                    >
                        <Label for="credit_limit" class="text-xs font-semibold">
                            Credit Limit
                        </Label>
                        <MoneyInput
                            id="credit_limit"
                            v-model="form.credit_limit"
                            placeholder="0"
                            currency="IDR"
                        />
                        <InputError :message="form.errors.credit_limit" />
                    </div>

                    <!-- Minimum Payment -->
                    <div class="space-y-1.5">
                        <Label
                            for="minimum_payment"
                            class="text-xs font-semibold"
                        >
                            Minimum Monthly Payment
                        </Label>
                        <MoneyInput
                            id="minimum_payment"
                            v-model="form.minimum_payment"
                            placeholder="0"
                            currency="IDR"
                        />
                        <InputError :message="form.errors.minimum_payment" />
                    </div>

                    <!-- Due Date Day -->
                    <div class="space-y-1.5">
                        <Label for="due_date_day" class="text-xs font-semibold">
                            Due Date (Day of Month)
                        </Label>
                        <Input
                            id="due_date_day"
                            v-model.number="form.due_date_day"
                            type="number"
                            min="1"
                            max="31"
                        />
                        <InputError :message="form.errors.due_date_day" />
                    </div>

                    <!-- Remaining Terms & Interest Rate -->
                    <template v-if="liability.type !== 'credit_card'">
                        <div class="space-y-1.5">
                            <Label
                                for="remaining_terms"
                                class="text-xs font-semibold"
                            >
                                Remaining Terms
                            </Label>
                            <Input
                                id="remaining_terms"
                                v-model.number="form.remaining_terms"
                                type="number"
                                min="0"
                            />
                            <InputError
                                :message="form.errors.remaining_terms"
                            />
                        </div>

                        <div class="space-y-1.5">
                            <Label
                                for="interest_rate"
                                class="text-xs font-semibold"
                            >
                                Interest Rate (%)
                            </Label>
                            <Input
                                id="interest_rate"
                                v-model.number="form.interest_rate"
                                type="number"
                                step="0.01"
                                min="0"
                            />
                            <InputError :message="form.errors.interest_rate" />
                        </div>
                    </template>

                    <!-- Active Status -->
                    <div class="flex items-center gap-2 pt-2 sm:col-span-2">
                        <input
                            id="is_active"
                            v-model="form.is_active"
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                        />
                        <Label
                            for="is_active"
                            class="cursor-pointer text-xs font-semibold"
                        >
                            Mark as Active (Uncheck if fully settled and closed)
                        </Label>
                    </div>

                    <!-- Notes -->
                    <div class="space-y-1.5 sm:col-span-2">
                        <Label for="notes" class="text-xs font-semibold">
                            Notes
                        </Label>
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            rows="2"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-xs shadow-sm focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                        />
                        <InputError :message="form.errors.notes" />
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <Link :href="`/liabilities/${liability.id}`">
                    <Button variant="outline" type="button">Cancel</Button>
                </Link>
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Saving...' : 'Save Changes' }}
                </Button>
            </div>
        </form>
    </div>
</template>
