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
    <div
        class="relative flex min-h-svh flex-col items-center justify-center overflow-hidden bg-background p-4 sm:p-6 md:p-10 select-none"
    >
        <!-- Ambient Background Tech Glow & Grid Pattern -->
        <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
            <!-- Top Ambient Glow Aura -->
            <div
                class="absolute -top-40 left-1/2 -translate-x-1/2 h-[500px] w-[700px] rounded-full bg-gradient-to-b from-blue-600/20 via-indigo-600/10 to-transparent blur-[120px]"
            />
            <!-- Bottom Secondary Ambient Glow -->
            <div
                class="absolute -bottom-40 left-1/2 -translate-x-1/2 h-[400px] w-[550px] rounded-full bg-blue-500/10 blur-[100px]"
            />
            <!-- Subtle Radial Grid Texture -->
            <div
                class="absolute inset-0 bg-[radial-gradient(#ffffff0d_1px,transparent_1px)] [background-size:24px_24px] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_45%,#000_70%,transparent_100%)]"
            />
        </div>

        <!-- Master Card Wrapper (Wraps header branding + form login card for mobile & web) -->
        <div class="w-full max-w-[440px]">
            <div
                class="relative overflow-hidden rounded-3xl border border-white/[0.08] bg-card/40 dark:bg-[#121622]/75 p-4 sm:p-7 shadow-2xl backdrop-blur-2xl ring-1 ring-white/[0.05] transition-all duration-300"
            >
                <!-- Subtle Top Card Highlight Gradient Line -->
                <div
                    class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-blue-500/50 to-transparent"
                />

                <!-- Branding Header: Logo, Name & Badge Tagline -->
                <div class="flex flex-col items-center gap-3 pt-2 pb-5 sm:pb-6">
                    <Link
                        :href="home()"
                        class="group flex flex-col items-center gap-3 transition-transform duration-300 hover:scale-105 active:scale-95"
                    >
                        <!-- Glowing Logo Icon -->
                        <div
                            class="relative flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 p-[1px] shadow-lg shadow-blue-500/25 ring-1 ring-white/20 transition-all duration-300 group-hover:shadow-blue-500/40"
                        >
                            <div
                                class="flex h-full w-full items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-blue-700"
                            >
                                <AppLogoIcon
                                    class="size-8 text-white transition-transform duration-300 group-hover:scale-110"
                                />
                            </div>
                        </div>

                        <!-- Brand Title -->
                        <div class="text-center">
                            <h2 class="text-2xl font-bold tracking-tight text-foreground">
                                Finova
                            </h2>
                        </div>
                    </Link>

                    <!-- Tagline Badge -->
                    <div
                        class="inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/10 px-3 py-1 text-xs font-medium text-primary-300 backdrop-blur-md"
                    >
                        <span class="relative flex size-2">
                            <span
                                class="absolute inline-flex h-full w-full animate-ping rounded-full bg-blue-400 opacity-75"
                            />
                            <span class="relative inline-flex size-2 rounded-full bg-blue-500" />
                        </span>
                        <span class="tracking-wide">Personal Wealth &amp; Finance OS</span>
                    </div>
                </div>

                <!-- Inner Form Card -->
                <div
                    class="relative rounded-2xl border border-border/80 bg-card/90 dark:bg-[#1C2130]/95 p-5 sm:p-7 shadow-xl backdrop-blur-md"
                >
                    <!-- Form Header (Title & Description) -->
                    <div class="mb-6 space-y-1 text-center">
                        <h1 class="text-lg sm:text-xl font-semibold tracking-tight text-foreground">
                            {{ title }}
                        </h1>
                        <p v-if="description" class="text-xs sm:text-sm text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>

                    <!-- Page Content (Login / Register Form) -->
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
