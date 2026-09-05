<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
        <div class="w-full max-w-md">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-3 transition-transform hover:scale-105 active:scale-95 mb-2"
                    >
                        <div
                            class="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary shadow-sm"
                        >
                            <AppLogoIcon class="size-10 text-primary-foreground" />
                        </div>
                        <h2 class="text-xl font-medium tracking-tight text-foreground mt-2">Finova</h2>
                        <p class="text-sm text-muted-foreground mt-0">Personal Wealth & Finance OS</p>
                    </Link>

                </div>
                
                <div class="rounded-2xl border border-border bg-card p-6 shadow-lg sm:p-8 transition-colors">
                    <div class="space-y-1 text-center mb-6">
                        <h1 class="text-xl font-medium tracking-tight text-foreground">{{ title }}</h1>
                        <p v-if="description" class="text-sm text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
