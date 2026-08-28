<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    ArrowDownRight,
    ArrowRightLeft,
    CircleDollarSign,
    Pencil,
    Trash2,
} from '@lucide/vue';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import { computed } from 'vue';
import MoneyDisplay from '@/components/MoneyDisplay.vue';

dayjs.extend(relativeTime);

const props = defineProps<{
    transaction: any;
}>();

const emit = defineEmits(['edit', 'delete']);

const formattedDate = computed(() => {
    const date = dayjs(props.transaction.date);

    // If it's today or yesterday, show relative time
    if (date.isAfter(dayjs().subtract(2, 'day'))) {
        return date.fromNow();
    }

    // Otherwise show absolute date
    return date.format('D MMM YYYY');
});

const isIncome = computed(() => props.transaction.type === 'income');
const isExpense = computed(() => props.transaction.type === 'expense');
const isTransfer = computed(() => props.transaction.type === 'transfer');

const getIcon = () => {
    if (isIncome.value) {
        return ArrowUpRight;
    }

    if (isExpense.value) {
        return ArrowDownRight;
    }

    if (isTransfer.value) {
        return ArrowRightLeft;
    }

    return CircleDollarSign;
};

// Calculate effective amount for display depending on whether it's income or expense
// Actually, amount is positive for both income and expense in DB, we just display expense with minus sign
const displayAmount = computed(() => {
    if (isExpense.value) {
        return -props.transaction.amount;
    }

    return props.transaction.amount;
});
</script>

<template>
    <div
        class="group flex items-center justify-between gap-3 border-b px-4 py-3.5 transition-colors last:border-0 hover:bg-muted/40 sm:px-6 sm:py-4"
    >
        <div class="flex min-w-0 items-center gap-3 sm:gap-4">
            <!-- Icon -->
            <div
                :class="[
                    'flex size-10 shrink-0 items-center justify-center rounded-xl',
                    {
                        'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400':
                            isIncome,
                    },
                    {
                        'bg-rose-500/10 text-rose-600 dark:text-rose-400':
                            isExpense,
                    },
                    {
                        'bg-blue-500/10 text-blue-600 dark:text-blue-400':
                            isTransfer,
                    },
                ]"
            >
                <component :is="getIcon()" class="size-5" />
            </div>

            <!-- Details -->
            <div class="min-w-0">
                <Link
                    :href="`/transactions/${transaction.id}`"
                    class="block truncate text-sm font-medium text-foreground hover:underline"
                >
                    {{ transaction.description || 'Untitled Transaction' }}
                </Link>
                <p class="truncate text-xs text-muted-foreground">
                    <span v-if="transaction.category">{{
                        transaction.category.name
                    }}</span>
                    <span v-else class="italic">Uncategorized</span>
                    <span> &bull; </span>
                    <span>{{ formattedDate }}</span>
                </p>
            </div>
        </div>

        <!-- Amount & Actions -->
        <div class="flex shrink-0 items-center gap-2 sm:gap-3">
            <div class="text-right tabular-nums">
                <span class="block text-sm font-semibold">
                    <MoneyDisplay
                        :amount="displayAmount"
                        :currency="transaction.currency"
                        :showSign="true"
                    />
                </span>
            </div>

            <!-- Actions: hidden on mobile, visible on desktop hover -->
            <div
                class="hidden items-center gap-0.5 sm:flex sm:opacity-0 sm:transition-opacity sm:group-hover:opacity-100"
            >
                <button
                    type="button"
                    @click="emit('edit', transaction)"
                    aria-label="Edit transaction"
                    class="flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                >
                    <Pencil class="size-4" />
                </button>
                <button
                    type="button"
                    @click="emit('delete', transaction)"
                    aria-label="Delete transaction"
                    class="flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-destructive"
                >
                    <Trash2 class="size-4" />
                </button>
            </div>
        </div>
    </div>
</template>
