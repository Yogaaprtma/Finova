<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BookOpen,
    FolderGit2,
    LayoutGrid,
    Wallet,
    ArrowRightLeft,
    Tags,
    TrendingUp,
    CreditCard,
    ChartColumn,
    PiggyBank,
    Repeat,
} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Rekening',
        href: '/accounts',
        icon: Wallet,
    },
    {
        title: 'Aset & Investasi',
        href: '/assets',
        icon: TrendingUp,
    },
    {
        title: 'Utang',
        href: '/liabilities',
        icon: CreditCard,
    },
    {
        title: 'Transaksi',
        href: '/transactions',
        icon: ArrowRightLeft,
    },
    {
        title: 'Anggaran',
        href: '/budgets',
        icon: PiggyBank,
    },
    {
        title: 'Transaksi Berulang',
        href: '/recurring-transactions',
        icon: Repeat,
    },
    {
        title: 'Laporan',
        href: '/reports',
        icon: ChartColumn,
    },
    {
        title: 'Kategori',
        href: '/categories',
        icon: Tags,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repositori',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Panduan',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
