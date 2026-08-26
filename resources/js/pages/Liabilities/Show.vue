<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CreditCard,
    Banknote,
    Calendar,
    Receipt,
    PlusCircle,
    CheckCircle2,
    Clock,
    Pencil,
    Trash2,
    Percent,
    Wallet,
} from '@lucide/vue';
import dayjs from 'dayjs';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import MoneyDisplay from '@/components/MoneyDisplay.vue';
import MoneyInput from '@/components/MoneyInput.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
        transactions: Array<{
            id: string;
            type: 'payment' | 'increase';
            amount: number;
            created_at: string;
            transaction?: {
                id: string;
                description: string;
                notes: string | null;
                date: string;
            };
        }>;
    };
    accounts: Array<{
        id: string;
        name: string;
        type: string;
        current_balance: number;
    }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Liabilities & Debt',
                href: '/liabilities',
            },
            {
                title: 'Liability Details',
                href: '#',
            },
        ],
    },
});

// Modals State
const showPayModal = ref(false);
const showIncreaseModal = ref(false);
const showDeleteConfirm = ref(false);

// Forms
const payForm = useForm({
    source_account_id: props.accounts[0]?.id || '',
    amount: (props.liability.minimum_payment ||
        props.liability.current_balance) as number | null,
    date: dayjs().format('YYYY-MM-DD'),
    description: `Payment for ${props.liability.name}`,
    notes: '',
});

const increaseForm = useForm({
    destination_account_id: '' as string,
    amount: null as number | null,
    date: dayjs().format('YYYY-MM-DD'),
    description: `Charge / Loan Draw on ${props.liability.name}`,
    notes: '',
});

const submitPay = () => {
    payForm.post(`/liabilities/${props.liability.id}/pay`, {
        onSuccess: () => {
            showPayModal.value = false;
            payForm.reset();
        },
    });
};

const submitIncrease = () => {
    increaseForm.post(`/liabilities/${props.liability.id}/increase`, {
        onSuccess: () => {
            showIncreaseModal.value = false;
            increaseForm.reset();
        },
    });
};

const deleteLiability = () => {
    router.delete(`/liabilities/${props.liability.id}`);
};

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

const availableCredit = computed(() => {
    if (!props.liability.credit_limit) {
        return null;
    }

    return Math.max(
        0,
        props.liability.credit_limit - props.liability.current_balance,
    );
});

const utilizationPct = computed(() => {
    if (!props.liability.credit_limit || props.liability.credit_limit === 0) {
        return 0;
    }

    return Math.min(
        100,
        Math.round(
            (props.liability.current_balance / props.liability.credit_limit) *
                100,
        ),
    );
});
</script>

<template>
    <Head :title="`${liability.name} - Liability Detail`" />

    <div
        class="mx-auto flex h-full w-full max-w-5xl flex-1 flex-col gap-6 p-4 md:p-6 lg:p-8"
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

            <div
                class="mt-2 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center"
            >
                <div class="flex items-center gap-3.5">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-500/10 text-rose-600 dark:text-rose-400"
                    >
                        <component
                            :is="getTypeIcon(liability.type)"
                            class="h-6 w-6"
                        />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-2xl font-bold tracking-tight">
                                {{ liability.name }}
                            </h1>
                            <span
                                v-if="
                                    !liability.is_active ||
                                    liability.current_balance === 0
                                "
                                class="rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-[10px] font-bold text-emerald-600 dark:text-emerald-400"
                            >
                                Paid Off
                            </span>
                        </div>
                        <span
                            class="text-xs font-semibold text-muted-foreground uppercase"
                        >
                            {{ liability.type.replace('_', ' ') }}
                        </span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-wrap items-center gap-2">
                    <Button
                        variant="default"
                        size="sm"
                        class="gap-1.5 bg-emerald-600 text-white hover:bg-emerald-700"
                        @click="showPayModal = true"
                    >
                        <CheckCircle2 class="h-4 w-4" />
                        Make Payment
                    </Button>

                    <Button
                        variant="outline"
                        size="sm"
                        class="gap-1.5"
                        @click="showIncreaseModal = true"
                    >
                        <PlusCircle class="h-4 w-4" />
                        Add Charge / Draw
                    </Button>

                    <Link :href="`/liabilities/${liability.id}/edit`">
                        <Button
                            variant="ghost"
                            size="icon"
                            title="Edit Liability"
                        >
                            <Pencil class="h-4 w-4" />
                        </Button>
                    </Link>

                    <Button
                        variant="ghost"
                        size="icon"
                        class="text-rose-600 hover:bg-rose-50 hover:text-rose-700"
                        title="Delete Liability"
                        @click="showDeleteConfirm = true"
                    >
                        <Trash2 class="h-4 w-4" />
                    </Button>
                </div>
            </div>
        </div>

        <!-- Main Stats Card -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Current Balance Card -->
            <div class="rounded-2xl border bg-card p-5 shadow-sm">
                <span
                    class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                >
                    Current Balance
                </span>
                <div
                    class="mt-2 text-2xl font-extrabold tracking-tight text-rose-600 dark:text-rose-400"
                >
                    <MoneyDisplay :amount="liability.current_balance" />
                </div>
                <div class="mt-3 text-xs text-muted-foreground">
                    Initial:
                    <MoneyDisplay :amount="liability.initial_balance" />
                </div>
            </div>

            <!-- Minimum Payment -->
            <div class="rounded-2xl border bg-card p-5 shadow-sm">
                <span
                    class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                >
                    Minimum Payment
                </span>
                <div
                    class="mt-2 text-2xl font-extrabold tracking-tight text-foreground"
                >
                    <MoneyDisplay
                        v-if="liability.minimum_payment"
                        :amount="liability.minimum_payment"
                    />
                    <span
                        v-else
                        class="text-lg font-normal text-muted-foreground"
                        >Not specified</span
                    >
                </div>
                <div class="mt-3 text-xs text-muted-foreground">
                    <span v-if="liability.due_date_day"
                        >Due on day {{ liability.due_date_day }} of month</span
                    >
                    <span v-else>No fixed due date</span>
                </div>
            </div>

            <!-- Credit Limit / Terms -->
            <div class="rounded-2xl border bg-card p-5 shadow-sm">
                <span
                    class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                >
                    {{
                        liability.type === 'credit_card'
                            ? 'Credit Limit'
                            : 'Remaining Terms'
                    }}
                </span>
                <div
                    class="mt-2 text-2xl font-extrabold tracking-tight text-foreground"
                >
                    <span
                        v-if="
                            liability.type === 'credit_card' &&
                            liability.credit_limit
                        "
                    >
                        <MoneyDisplay :amount="liability.credit_limit" />
                    </span>
                    <span v-else-if="liability.remaining_terms !== null">
                        {{ liability.remaining_terms }} terms left
                    </span>
                    <span
                        v-else
                        class="text-lg font-normal text-muted-foreground"
                        >-</span
                    >
                </div>
                <div class="mt-3 text-xs text-muted-foreground">
                    <span
                        v-if="
                            liability.type === 'credit_card' &&
                            availableCredit !== null
                        "
                    >
                        Available: <MoneyDisplay :amount="availableCredit" />
                    </span>
                    <span v-else-if="liability.interest_rate">
                        Interest: {{ liability.interest_rate }}% p.a.
                    </span>
                </div>
            </div>

            <!-- Credit Utilization / Dates -->
            <div class="rounded-2xl border bg-card p-5 shadow-sm">
                <span
                    class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                >
                    {{
                        liability.type === 'credit_card'
                            ? 'Utilization'
                            : 'Schedule'
                    }}
                </span>
                <div
                    class="mt-2 text-2xl font-extrabold tracking-tight text-foreground"
                >
                    <span v-if="liability.type === 'credit_card'">
                        {{ utilizationPct }}%
                    </span>
                    <span v-else-if="liability.end_date" class="text-lg">
                        until {{ liability.end_date }}
                    </span>
                    <span
                        v-else
                        class="text-lg font-normal text-muted-foreground"
                        >Open-ended</span
                    >
                </div>
                <div class="mt-3 text-xs text-muted-foreground">
                    <div
                        v-if="
                            liability.type === 'credit_card' &&
                            liability.credit_limit
                        "
                        class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-muted"
                    >
                        <div
                            class="h-full rounded-full bg-rose-500"
                            :style="{ width: `${utilizationPct}%` }"
                        />
                    </div>
                    <span v-else-if="liability.start_date"
                        >Started: {{ liability.start_date }}</span
                    >
                </div>
            </div>
        </div>

        <!-- Notes if any -->
        <div
            v-if="liability.notes"
            class="rounded-2xl border bg-card p-4 text-xs text-muted-foreground shadow-sm"
        >
            <span class="font-semibold text-foreground">Notes:</span>
            {{ liability.notes }}
        </div>

        <!-- Repayment / Transaction History -->
        <div class="rounded-2xl border bg-card p-6 shadow-sm">
            <div
                class="flex items-center justify-between border-b border-border/60 pb-4"
            >
                <div>
                    <h3 class="text-base font-bold tracking-tight">
                        Payment &amp; Transaction History
                    </h3>
                    <p class="text-xs text-muted-foreground">
                        Audit trail of payments and charges applied to this
                        liability.
                    </p>
                </div>
                <span class="text-xs font-medium text-muted-foreground">
                    {{ liability.transactions?.length || 0 }} records
                </span>
            </div>

            <div
                v-if="
                    liability.transactions && liability.transactions.length > 0
                "
                class="mt-4 divide-y divide-border/50"
            >
                <div
                    v-for="item in liability.transactions"
                    :key="item.id"
                    class="flex items-center justify-between py-3.5"
                >
                    <div class="flex items-center gap-3">
                        <div
                            :class="[
                                'flex h-9 w-9 items-center justify-center rounded-xl text-xs font-bold',
                                item.type === 'payment'
                                    ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                                    : 'bg-rose-500/10 text-rose-600 dark:text-rose-400',
                            ]"
                        >
                            {{ item.type === 'payment' ? 'PAY' : 'NEW' }}
                        </div>
                        <div>
                            <p class="text-xs font-bold text-foreground">
                                {{
                                    item.transaction?.description ||
                                    (item.type === 'payment'
                                        ? 'Repayment'
                                        : 'Charge / Increase')
                                }}
                            </p>
                            <span class="text-[11px] text-muted-foreground">
                                {{
                                    dayjs(item.created_at).format(
                                        'DD MMM YYYY, HH:mm',
                                    )
                                }}
                            </span>
                        </div>
                    </div>

                    <div class="text-right">
                        <p
                            :class="[
                                'text-sm font-extrabold tabular-nums',
                                item.type === 'payment'
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-rose-600 dark:text-rose-400',
                            ]"
                        >
                            {{ item.type === 'payment' ? '-' : '+'
                            }}<MoneyDisplay :amount="item.amount" />
                        </p>
                        <span class="text-[10px] text-muted-foreground">
                            {{
                                item.type === 'payment'
                                    ? 'Debt Reduced'
                                    : 'Debt Added'
                            }}
                        </span>
                    </div>
                </div>
            </div>

            <div v-else class="py-10 text-center text-xs text-muted-foreground">
                No payment or charge transactions recorded yet. Click "Make
                Payment" above once you make a transfer.
            </div>
        </div>

        <!-- Pay Modal -->
        <Dialog :open="showPayModal" @update:open="showPayModal = $event">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Make a Payment</DialogTitle>
                    <DialogDescription>
                        Record a repayment towards {{ liability.name }}. This
                        will deduct money from your selected source account.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitPay" class="space-y-4">
                    <div class="space-y-1.5">
                        <Label class="text-xs font-semibold"
                            >Pay from Account *</Label
                        >
                        <select
                            v-model="payForm.source_account_id"
                            required
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <option
                                v-for="acc in accounts"
                                :key="acc.id"
                                :value="acc.id"
                            >
                                {{ acc.name }} (Saldo: Rp
                                {{
                                    acc.current_balance.toLocaleString('id-ID')
                                }})
                            </option>
                        </select>
                        <InputError
                            :message="payForm.errors.source_account_id"
                        />
                    </div>

                    <div class="space-y-1.5">
                        <Label class="text-xs font-semibold"
                            >Payment Amount (IDR) *</Label
                        >
                        <MoneyInput
                            v-model="payForm.amount"
                            placeholder="0"
                            currency="IDR"
                        />
                        <InputError :message="payForm.errors.amount" />
                    </div>

                    <div class="space-y-1.5">
                        <Label class="text-xs font-semibold"
                            >Payment Date</Label
                        >
                        <Input v-model="payForm.date" type="date" />
                        <InputError :message="payForm.errors.date" />
                    </div>

                    <div class="space-y-1.5">
                        <Label class="text-xs font-semibold">Notes</Label>
                        <Input
                            v-model="payForm.notes"
                            type="text"
                            placeholder="e.g. Monthly statement payment"
                        />
                        <InputError :message="payForm.errors.notes" />
                    </div>

                    <DialogFooter class="gap-2 sm:gap-0">
                        <Button
                            variant="outline"
                            type="button"
                            @click="showPayModal = false"
                        >
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="payForm.processing">
                            {{
                                payForm.processing
                                    ? 'Processing...'
                                    : 'Confirm Payment'
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Increase Modal -->
        <Dialog
            :open="showIncreaseModal"
            @update:open="showIncreaseModal = $event"
        >
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Add Charge or Loan Draw</DialogTitle>
                    <DialogDescription>
                        Increase your outstanding debt on
                        {{ liability.name }} (e.g. new purchase or cash loan
                        disbursement).
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitIncrease" class="space-y-4">
                    <div class="space-y-1.5">
                        <Label class="text-xs font-semibold"
                            >Charge / Draw Amount (IDR) *</Label
                        >
                        <MoneyInput
                            v-model="increaseForm.amount"
                            placeholder="0"
                            currency="IDR"
                        />
                        <InputError :message="increaseForm.errors.amount" />
                    </div>

                    <div class="space-y-1.5">
                        <Label class="text-xs font-semibold"
                            >Disburse into Bank Account (Optional)</Label
                        >
                        <select
                            v-model="increaseForm.destination_account_id"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-xs shadow-sm focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <option value="">
                                None (Credit Card purchase or non-cash)
                            </option>
                            <option
                                v-for="acc in accounts"
                                :key="acc.id"
                                :value="acc.id"
                            >
                                {{ acc.name }}
                            </option>
                        </select>
                        <p class="text-[11px] text-muted-foreground">
                            If selected, this account will receive the cash
                            balance.
                        </p>
                        <InputError
                            :message="
                                increaseForm.errors.destination_account_id
                            "
                        />
                    </div>

                    <div class="space-y-1.5">
                        <Label class="text-xs font-semibold">Date</Label>
                        <Input v-model="increaseForm.date" type="date" />
                        <InputError :message="increaseForm.errors.date" />
                    </div>

                    <div class="space-y-1.5">
                        <Label class="text-xs font-semibold">Description</Label>
                        <Input
                            v-model="increaseForm.description"
                            type="text"
                            placeholder="e.g. Flight ticket booking"
                        />
                        <InputError
                            :message="increaseForm.errors.description"
                        />
                    </div>

                    <DialogFooter class="gap-2 sm:gap-0">
                        <Button
                            variant="outline"
                            type="button"
                            @click="showIncreaseModal = false"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            :disabled="increaseForm.processing"
                        >
                            {{
                                increaseForm.processing
                                    ? 'Processing...'
                                    : 'Confirm Increase'
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Delete Modal -->
        <Dialog
            :open="showDeleteConfirm"
            @update:open="showDeleteConfirm = $event"
        >
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Delete Liability</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to remove {{ liability.name }}?
                        This will remove it from your balance calculations.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter class="gap-2 sm:gap-0">
                    <Button
                        variant="outline"
                        type="button"
                        @click="showDeleteConfirm = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        variant="destructive"
                        type="button"
                        @click="deleteLiability"
                    >
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
