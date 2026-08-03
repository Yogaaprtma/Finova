<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { Component } from 'vue';

defineProps<{
    title: string;
    description: string;
    icon: Component;
    actionText?: string;
    actionHref?: string;
}>();

const emit = defineEmits<{
    (e: 'action'): void;
}>();
</script>

<template>
    <div
        class="flex flex-1 flex-col items-center justify-center rounded-xl border border-dashed p-12 text-center"
    >
        <div
            class="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-muted"
        >
            <component :is="icon" class="h-10 w-10 text-muted-foreground" />
        </div>
        <h2 class="mb-2 text-2xl font-semibold tracking-tight">{{ title }}</h2>
        <p class="mb-8 max-w-md text-sm text-muted-foreground">
            {{ description }}
        </p>

        <slot name="action">
            <Link
                v-if="actionHref && actionText"
                :href="actionHref"
                class="inline-flex h-11 items-center justify-center gap-2 rounded-md bg-primary px-8 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50"
            >
                {{ actionText }}
            </Link>
            <button
                v-else-if="actionText"
                type="button"
                @click="emit('action')"
                class="inline-flex h-11 items-center justify-center gap-2 rounded-md bg-primary px-8 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
            >
                {{ actionText }}
            </button>
        </slot>
    </div>
</template>
