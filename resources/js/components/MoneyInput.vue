<script setup lang="ts">
import { computed, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        modelValue: number | string | null;
        currency?: string;
        placeholder?: string;
        disabled?: boolean;
        required?: boolean;
        id?: string;
        size?: 'default' | 'lg';
    }>(),
    {
        currency: 'IDR',
        placeholder: '0',
        disabled: false,
        required: false,
        id: 'amount',
        size: 'default',
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: number | null): void;
}>();

const formatNumber = (val: number | string | null): string => {
    if (val === null || val === undefined || val === '') {
        return '';
    }

    const num =
        typeof val === 'number'
            ? val
            : parseInt(String(val).replace(/\D/g, ''), 10);

    if (isNaN(num)) {
        return '';
    }

    return new Intl.NumberFormat('id-ID').format(num);
};

const displayValue = ref(formatNumber(props.modelValue));

watch(
    () => props.modelValue,
    (newVal) => {
        const formatted = formatNumber(newVal);

        if (formatted !== displayValue.value) {
            displayValue.value = formatted;
        }
    },
);

const onInput = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const rawDigits = input.value.replace(/\D/g, '');

    if (!rawDigits) {
        displayValue.value = '';
        emit('update:modelValue', null);

        return;
    }

    const numericValue = parseInt(rawDigits, 10);
    displayValue.value = formatNumber(numericValue);
    emit('update:modelValue', numericValue);
};
</script>

<template>
    <div class="relative w-full">
        <!-- Large Hero Style (for Transaction Create / Edit) -->
        <div v-if="size === 'lg'" class="relative mx-auto max-w-xs">
            <div
                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-2xl font-semibold text-muted-foreground"
            >
                Rp
            </div>
            <input
                :id="id"
                type="text"
                inputmode="numeric"
                :value="displayValue"
                @input="onInput"
                :placeholder="placeholder"
                :disabled="disabled"
                :required="required"
                class="flex h-16 w-full rounded-md border-0 border-b-2 border-input bg-transparent py-2 pr-3 pl-14 text-center text-4xl font-bold focus-visible:border-primary focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
            />
        </div>

        <!-- Default Input Style (for Account forms, etc.) -->
        <div v-else class="relative">
            <div
                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-medium text-muted-foreground"
            >
                Rp
            </div>
            <input
                :id="id"
                type="text"
                inputmode="numeric"
                :value="displayValue"
                @input="onInput"
                :placeholder="placeholder"
                :disabled="disabled"
                :required="required"
                class="flex h-10 w-full rounded-md border border-input bg-background py-2 pr-3 pl-11 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
            />
        </div>
    </div>
</template>
