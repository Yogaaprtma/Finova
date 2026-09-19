<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { LayoutGrid, Wallet, ArrowRightLeft, PlusCircle } from '@lucide/vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { dashboard } from '@/routes';

const { isCurrentUrl } = useCurrentUrl();

const isActive = (href: string) => {
    // Basic active state logic
    if (href === String(dashboard())) {
        return isCurrentUrl(href);
    }

    return window.location.pathname.startsWith(href);
};
</script>

<template>
    <div
        class="pb-safe-area-inset-bottom fixed right-0 bottom-0 left-0 z-50 flex items-center justify-around border-t border-border bg-background/80 backdrop-blur-md md:hidden"
    >
        <Link
            :href="dashboard()"
            class="flex min-w-[64px] flex-col items-center justify-center gap-1 px-4 py-2"
            :class="
                isActive(String(dashboard()))
                    ? 'text-primary'
                    : 'text-muted-foreground hover:text-foreground'
            "
        >
            <LayoutGrid
                class="h-6 w-6"
                :class="isActive(String(dashboard())) ? 'stroke-[2.5px]' : ''"
            />
            <span class="text-[10px] font-medium">Home</span>
        </Link>

        <Link
            href="/accounts"
            class="flex min-w-[64px] flex-col items-center justify-center gap-1 px-4 py-2"
            :class="
                isActive('/accounts')
                    ? 'text-primary'
                    : 'text-muted-foreground hover:text-foreground'
            "
        >
            <Wallet
                class="h-6 w-6"
                :class="isActive('/accounts') ? 'stroke-[2.5px]' : ''"
            />
            <span class="text-[10px] font-medium">Accounts</span>
        </Link>

        <!-- Floating Action Button -->
        <div class="relative -top-5 flex justify-center">
            <Link
                href="/transactions/create"
                class="flex h-14 w-14 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg ring-4 shadow-primary/30 ring-background transition-transform hover:scale-105 active:scale-95"
            >
                <PlusCircle class="h-7 w-7" />
            </Link>
        </div>

        <Link
            href="/transactions"
            class="flex min-w-[64px] flex-col items-center justify-center gap-1 px-4 py-2"
            :class="
                isActive('/transactions') && !isActive('/transactions/create')
                    ? 'text-primary'
                    : 'text-muted-foreground hover:text-foreground'
            "
        >
            <ArrowRightLeft
                class="h-6 w-6"
                :class="
                    isActive('/transactions') &&
                    !isActive('/transactions/create')
                        ? 'stroke-[2.5px]'
                        : ''
                "
            />
            <span class="text-[10px] font-medium">History</span>
        </Link>

        <!-- Placeholder for Settings or More -->
        <Link
            href="/settings/profile"
            class="flex min-w-[64px] flex-col items-center justify-center gap-1 px-4 py-2"
            :class="
                isActive('/settings')
                    ? 'text-primary'
                    : 'text-muted-foreground hover:text-foreground'
            "
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                class="h-6 w-6"
                :class="isActive('/settings') ? 'stroke-[2.5px]' : ''"
            >
                <path
                    d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"
                ></path>
                <circle cx="12" cy="12" r="3"></circle>
            </svg>
            <span class="text-[10px] font-medium">Settings</span>
        </Link>
    </div>
</template>
