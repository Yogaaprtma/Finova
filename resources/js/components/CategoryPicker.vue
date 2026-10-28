<script setup lang="ts">
import { computed } from 'vue';
import CategoryIcon from '@/components/CategoryIcon.vue';

const props = defineProps<{
    modelValue: string | null;
    categories: any[];
    type?: string;
}>();

const emit = defineEmits(['update:modelValue']);

const filteredCategories = computed(() => {
    if (!props.type || props.type === 'transfer') {
        return [];
    }

    return props.categories.filter(
        (c) => c.type === props.type && c.is_active !== false,
    );
});
</script>

<template>
    <div
        v-if="filteredCategories.length > 0"
        class="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-5"
    >
        <button
            type="button"
            v-for="category in filteredCategories"
            :key="category.id"
            @click="emit('update:modelValue', category.id)"
            class="flex flex-col items-center justify-center rounded-xl border p-3 transition-all hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            :class="[
                modelValue === category.id
                    ? 'border-primary bg-primary/10 ring-1 ring-primary'
                    : 'border-input bg-card',
            ]"
        >
            <div
                class="mb-2 flex h-10 w-10 items-center justify-center rounded-full shadow-sm"
                :style="{
                    backgroundColor: category.color
                        ? `${category.color}20`
                        : 'var(--muted)',
                    color: category.color || 'var(--foreground)',
                }"
            >
                <CategoryIcon :icon="category.icon" class-name="h-5 w-5" />
            </div>
            <span
                class="w-full truncate px-1 text-center text-xs font-medium"
                >{{ category.name }}</span
            >
        </button>
    </div>
    <div
        v-else
        class="rounded-xl border border-dashed bg-muted/30 py-4 text-center text-sm text-muted-foreground"
    >
        No active categories found for {{ type }}.
    </div>
</template>
