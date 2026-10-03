<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        amount: number;
        currency?: string;
        showSign?: boolean;
    }>(),
    {
        currency: 'IDR',
        showSign: false,
    },
);

const formattedAmount = computed(() => {
    const formatter = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: props.currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    });

    // The amount is assumed to be in the base unit (e.g. IDR),
    // wait, in the backend it might be in cents? No, for IDR we use normal numbers, but in the backend the Money object value is integer. Wait, IDR doesn't really have cents in daily usage. The DB schema uses BIGINT. So amount is directly the value.

    const formatted = formatter.format(props.amount);

    if (props.showSign && props.amount > 0) {
        return '+' + formatted;
    }

    return formatted;
});

const isPositive = computed(() => props.amount > 0);
const isNegative = computed(() => props.amount < 0);
</script>

<template>
    <span
        :class="[
            { 'text-emerald-500': showSign && isPositive },
            { 'text-red-500': isNegative },
        ]"
    >
        {{ formattedAmount }}
    </span>
</template>
