import { Landmark, Smartphone, Wallet } from '@lucide/vue';
import type { Component } from 'vue';

export type AccountTypeMeta = {
    value: string;
    label: string;
    shortLabel: string;
    description: string;
    icon: Component;
    tint: string;
};

export const ACCOUNT_TYPE_MAP: Record<string, AccountTypeMeta> = {
    bank: {
        value: 'bank',
        label: 'Rekening Bank',
        shortLabel: 'Bank',
        description: 'Rekening tabungan, giro, atau payroll',
        icon: Landmark,
        tint: '#3b63f6',
    },
    cash: {
        value: 'cash',
        label: 'Uang Tunai',
        shortLabel: 'Tunai',
        description: 'Dompet fisik atau uang kas tunai',
        icon: Wallet,
        tint: '#10b981',
    },
    ewallet: {
        value: 'ewallet',
        label: 'Dompet Digital',
        shortLabel: 'E-Wallet',
        description: 'GoPay, OVO, DANA, ShopeePay, dan sejenisnya',
        icon: Smartphone,
        tint: '#0ea5e9',
    },
};

export const ACCOUNT_CURRENCIES = [
    { value: 'IDR', label: 'IDR - Rupiah Indonesia', symbol: 'Rp' },
    { value: 'USD', label: 'USD - Dolar AS', symbol: '$' },
    { value: 'EUR', label: 'EUR - Euro', symbol: '€' },
    { value: 'SGD', label: 'SGD - Dolar Singapura', symbol: 'S$' },
] as const;

export function getAccountTypeMeta(type?: string | null): AccountTypeMeta {
    const key = (type || '').toLowerCase();

    if (ACCOUNT_TYPE_MAP[key]) {
        return ACCOUNT_TYPE_MAP[key];
    }

    const formatted = key
        ? key.charAt(0).toUpperCase() + key.slice(1)
        : 'Rekening';

    return {
        value: key || 'other',
        label: formatted,
        shortLabel: formatted,
        description: 'Rekening keuangan',
        icon: Wallet,
        tint: '#64748b',
    };
}

export function getCurrencySymbol(currency?: string | null): string {
    const code = (currency || 'IDR').toUpperCase();
    const match = ACCOUNT_CURRENCIES.find((c) => c.value === code);

    return match ? match.symbol : code;
}

export function getAccountTintStyle(tint: string): Record<string, string> {
    return {
        backgroundColor: `color-mix(in srgb, ${tint} 14%, transparent)`,
        color: tint,
    };
}
