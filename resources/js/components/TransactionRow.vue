<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    ArrowDownRight,
    ArrowRightLeft,
    CircleDollarSign,
    MoreVertical,
    Pencil,
    Trash2,
} from '@lucide/vue';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
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
        class="group flex items-center justify-between border-b px-4 py-4 transition-colors last:border-0 hover:bg-muted/50 sm:px-6"
    >
        <div class="flex min-w-0 items-center gap-4">
            <!-- Icon -->
            <div
                :class="[
                    'flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full',
                    { 'bg-emerald-500/10 text-emerald-500': isIncome },
                    { 'bg-red-500/10 text-red-500': isExpense },
                    { 'bg-blue-500/10 text-blue-500': isTransfer },
                ]"
            >
                <component :is="getIcon()" class="h-5 w-5" />
            </div>

            <!-- Details -->
            <div class="flex min-w-0 flex-col">
                <span class="truncate text-sm font-medium text-foreground">
                    {{ transaction.description || 'Untitled Transaction' }}
                </span>
                <div
                    class="flex items-center gap-2 text-xs text-muted-foreground"
                >
                    <span v-if="transaction.category">{{
                        transaction.category.name
                    }}</span>
                    <span v-else class="italic">Uncategorized</span>
                    <span>&bull;</span>
                    <span>{{ formattedDate }}</span>
                </div>
            </div>
        </div>

        <!-- Amount & Actions -->
        <div class="flex items-center gap-4">
            <div class="text-right">
                <span class="block text-sm font-bold">
                    <MoneyDisplay
                        :amount="displayAmount"
                        :currency="transaction.currency"
                        :showSign="true"
                    />
                </span>
            </div>

            <!-- Mobile Actions (Dropdown) - Desktop (Inline) -->
            <div
                class="flex items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100"
            >
                <button
                    @click="emit('edit', transaction)"
                    class="flex h-8 w-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                >
                    <Pencil class="h-4 w-4" />
                </button>
                <button
                    @click="emit('delete', transaction)"
                    class="flex h-8 w-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-destructive"
                >
                    <Trash2 class="h-4 w-4" />
                </button>
            </div>
        </div>
    </div>
</template>
