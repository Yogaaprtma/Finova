<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CreditCard,
    Banknote,
    Calendar,
    Receipt,
} from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import MoneyInput from '@/components/MoneyInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Liabilities & Debt',
                href: '/liabilities',
            },
            {
                title: 'Add Liability',
                href: '/liabilities/create',
            },
        ],
    },
});

const props = defineProps<{
    liabilityTypes: Array<{
        value: string;
        label: string;
        color: string;
        description: string;
    }>;
    accounts: Array<{
        id: string;
        name: string;
        type: string;
        current_balance: number;
    }>;
}>();

const form = useForm({
    type: 'credit_card',
    name: '',
    currency: 'IDR',
    initial_balance: null as number | null,
    current_balance: null as number | null,
    credit_limit: null as number | null,
    interest_rate: undefined as number | undefined,
    minimum_payment: null as number | null,
    due_date_day: undefined as number | undefined,
    start_date: '',
    end_date: '',
    remaining_terms: undefined as number | undefined,
    notes: '',
});

const selectedType = computed(() => {
    return (
        props.liabilityTypes.find((t) => t.value === form.type) ||
        props.liabilityTypes[0]
    );
});

const getTypeIcon = (type: string) => {
    switch (type) {
        case 'credit_card':
            return CreditCard;
        case 'personal_loan':
            return Banknote;
        case 'installment':
            return Calendar;
        default:
            return Receipt;
    }
};

const selectType = (typeValue: string) => {
    form.type = typeValue;
};

const submit = () => {
    // If current_balance is null, sync it with initial_balance
    if (form.current_balance === null && form.initial_balance !== null) {
        form.current_balance = form.initial_balance;
    }

    form.post('/liabilities');
};
</script>

<template>
    <Head title="Add New Liability" />

    <div
        class="mx-auto flex h-full w-full max-w-4xl flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8"
    >
        <!-- Header -->
        <div class="flex flex-col gap-1">
            <Link
                href="/liabilities"
                class="inline-flex items-center gap-1.5 text-xs font-medium text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft class="h-3.5 w-3.5" />
                Back to Liabilities
            </Link>
            <h1 class="text-3xl font-bold tracking-tight">Add New Liability</h1>
            <p class="text-sm text-muted-foreground">
                Register a credit card, loan, or installment plan to include in
                your financial balance.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Step 1: Select Type -->
            <div class="rounded-2xl border bg-card p-6 shadow-sm">
                <span
                    class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                >
                    1. Select Liability Type
                </span>

                <div
                    class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-4"
                >
                    <button
                        v-for="item in liabilityTypes"
                        :key="item.value"
                        type="button"
                        :class="[
                            'group flex flex-col items-center justify-center gap-2.5 rounded-xl border p-4 text-center transition-all',
                            form.type === item.value
                                ? 'border-primary bg-primary/5 text-primary ring-2 ring-primary/20'
                                : 'border-border bg-background text-muted-foreground hover:border-primary/40 hover:bg-muted/30',
                        ]"
                        @click="selectType(item.value)"
                    >
                        <div
                            :class="[
                                'flex h-11 w-11 items-center justify-center rounded-xl transition-colors',
                                form.type === item.value
                                    ? 'bg-primary text-primary-foreground'
                                    : 'bg-muted text-muted-foreground group-hover:text-foreground',
                            ]"
                        >
                            <component
                                :is="getTypeIcon(item.value)"
                                class="h-5 w-5"
                            />
                        </div>
                        <div>
                            <span
                                class="block text-xs font-bold text-foreground"
                            >
                                {{ item.label }}
                            </span>
                            <span
                                class="mt-0.5 line-clamp-2 text-[10px] text-muted-foreground"
                            >
                                {{ item.description }}
                            </span>
                        </div>
                    </button>
                </div>
                <InputError :message="form.errors.type" class="mt-2" />
            </div>

            <!-- Step 2: Details -->
            <div class="space-y-4 rounded-2xl border bg-card p-6 shadow-sm">
                <span
                    class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                >
                    2. Liability Information
                </span>

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
                            placeholder="e.g. BCA Everyday Card, KTA Mandiri, iPhone 16 Cicilan"
                            required
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <!-- Initial Balance -->
                    <div class="space-y-1.5">
                        <Label
                            for="initial_balance"
                            class="text-xs font-semibold"
                        >
                            Initial / Original Debt *
                        </Label>
                        <MoneyInput
                            id="initial_balance"
                            v-model="form.initial_balance"
                            placeholder="0"
                            currency="IDR"
                        />
                        <p class="text-[11px] text-muted-foreground">
                            Original loan principal or opening card balance
                        </p>
                        <InputError :message="form.errors.initial_balance" />
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
                            placeholder="Leave empty if same as initial"
                            currency="IDR"
                        />
                        <p class="text-[11px] text-muted-foreground">
                            Defaults to original debt if not filled
                        </p>
                        <InputError :message="form.errors.current_balance" />
                    </div>

                    <!-- Credit Card Specifics -->
                    <template v-if="form.type === 'credit_card'">
                        <div class="space-y-1.5">
                            <Label
                                for="credit_limit"
                                class="text-xs font-semibold"
                            >
                                Credit Limit
                            </Label>
                            <MoneyInput
                                id="credit_limit"
                                v-model="form.credit_limit"
                                placeholder="e.g. 20.000.000"
                                currency="IDR"
                            />
                            <InputError :message="form.errors.credit_limit" />
                        </div>

                        <div class="space-y-1.5">
                            <Label
                                for="due_date_day"
                                class="text-xs font-semibold"
                            >
                                Due Date (Day of Month)
                            </Label>
                            <Input
                                id="due_date_day"
                                v-model.number="form.due_date_day"
                                type="number"
                                min="1"
                                max="31"
                                placeholder="e.g. 15 or 25"
                            />
                            <InputError :message="form.errors.due_date_day" />
                        </div>
                    </template>

                    <!-- Minimum Payment -->
                    <div class="space-y-1.5">
                        <Label
                            for="minimum_payment"
                            class="text-xs font-semibold"
                        >
                            Monthly Minimum / Installment Payment
                        </Label>
                        <MoneyInput
                            id="minimum_payment"
                            v-model="form.minimum_payment"
                            placeholder="e.g. 500.000"
                            currency="IDR"
                        />
                        <InputError :message="form.errors.minimum_payment" />
                    </div>

                    <!-- Interest Rate & Remaining Terms (for loans & installments) -->
                    <template v-if="form.type !== 'credit_card'">
                        <div class="space-y-1.5">
                            <Label
                                for="remaining_terms"
                                class="text-xs font-semibold"
                            >
                                Remaining Terms (Months / Cicilan)
                            </Label>
                            <Input
                                id="remaining_terms"
                                v-model.number="form.remaining_terms"
                                type="number"
                                min="0"
                                placeholder="e.g. 12"
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
                                Annual Interest Rate (%)
                            </Label>
                            <Input
                                id="interest_rate"
                                v-model.number="form.interest_rate"
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                placeholder="e.g. 12.5"
                            />
                            <InputError :message="form.errors.interest_rate" />
                        </div>
                    </template>

                    <!-- Notes -->
                    <div class="space-y-1.5 sm:col-span-2">
                        <Label for="notes" class="text-xs font-semibold">
                            Notes (Optional)
                        </Label>
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            rows="2"
                            placeholder="Add account number, lender details, or payoff strategy..."
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-xs shadow-sm focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                        />
                        <InputError :message="form.errors.notes" />
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="flex items-center justify-end gap-3">
                <Link href="/liabilities">
                    <Button variant="outline" type="button">Cancel</Button>
                </Link>
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Saving...' : 'Save Liability' }}
                </Button>
            </div>
        </form>
    </div>
</template>
