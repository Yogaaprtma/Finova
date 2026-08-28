<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { User, ShieldCheck, Palette } from '@lucide/vue';
import type { Component } from 'vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';

type SettingsNavItem = {
    title: string;
    href: string;
    icon: Component;
};

const sidebarNavItems: SettingsNavItem[] = [
    {
        title: 'Profile',
        href: editProfile().url,
        icon: User,
    },
    {
        title: 'Security',
        href: editSecurity().url,
        icon: ShieldCheck,
    },
    {
        title: 'Appearance',
        href: editAppearance().url,
        icon: Palette,
    },
];

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <!--
        Bottom padding on mobile leaves room for a bottom nav / floating
        button and the iOS home indicator (safe-area).
    -->
    <div
        class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-5 px-4 pt-4 pb-[calc(6rem+env(safe-area-inset-bottom))] sm:px-6 sm:pt-6 lg:gap-8 lg:px-8 lg:pt-8 lg:pb-10"
    >
        <!-- Page title -->
        <header>
            <h1 class="text-xl font-semibold tracking-tight sm:text-2xl">
                Settings
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Manage your profile, security, and appearance.
            </p>
        </header>

        <!-- Mobile: segmented control, 3 equal columns (no horizontal scroll) -->
        <nav
            class="grid grid-cols-3 gap-1 rounded-lg bg-muted p-1 lg:hidden"
            aria-label="Settings sections"
        >
            <Link
                v-for="item in sidebarNavItems"
                :key="toUrl(item.href)"
                :href="item.href"
                :aria-current="
                    isCurrentOrParentUrl(item.href) ? 'page' : undefined
                "
                :class="[
                    'flex h-11 items-center justify-center gap-1.5 rounded-md text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-ring/50 focus-visible:outline-none',
                    isCurrentOrParentUrl(item.href)
                        ? 'bg-background text-foreground shadow-xs'
                        : 'text-muted-foreground hover:text-foreground',
                ]"
            >
                <component :is="item.icon" class="size-4 shrink-0" />
                <span class="truncate">{{ item.title }}</span>
            </Link>
        </nav>

        <div class="flex flex-col lg:flex-row lg:items-start lg:gap-10">
            <!-- Desktop: sidebar -->
            <aside class="hidden w-48 shrink-0 lg:block">
                <nav class="space-y-0.5" aria-label="Settings sections">
                    <Link
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        :href="item.href"
                        :aria-current="
                            isCurrentOrParentUrl(item.href) ? 'page' : undefined
                        "
                        :class="[
                            'flex h-9 items-center gap-2.5 rounded-md px-3 text-sm transition-colors focus-visible:ring-2 focus-visible:ring-ring/50 focus-visible:outline-none',
                            isCurrentOrParentUrl(item.href)
                                ? 'bg-muted font-medium text-foreground'
                                : 'text-muted-foreground hover:bg-muted/60 hover:text-foreground',
                        ]"
                    >
                        <component :is="item.icon" class="size-4 shrink-0" />
                        {{ item.title }}
                    </Link>
                </nav>
            </aside>

            <!-- Content -->
            <div class="max-w-3xl min-w-0 flex-1 space-y-6">
                <slot />
            </div>
        </div>
    </div>
</template>