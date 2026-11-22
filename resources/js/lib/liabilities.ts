import { Banknote, CalendarClock, CreditCard, Receipt } from '@lucide/vue';
import dayjs from 'dayjs';
import 'dayjs/locale/id';
import type { Component } from 'vue';

export type LiabilityTypeValue =
    'credit_card' | 'personal_loan' | 'installment' | 'other_debt';

export interface LiabilityTypeMeta {
    value: LiabilityTypeValue;
    label: string;
    shortLabel: string;
    description: string;
    namePlaceholder: string;
    increaseActionLabel: string;
    tint: string;
    icon: Component;
}

export const LIABILITY_TYPE_META: Record<
    LiabilityTypeValue,
    LiabilityTypeMeta
> = {
    credit_card: {
        value: 'credit_card',
        label: 'Kartu Kredit',
        shortLabel: 'Kartu Kredit',
        description:
            'Fasilitas kredit bergulir dengan limit dan jatuh tempo bulanan',
        namePlaceholder: 'cth. BCA Everyday Card, Mandiri Visa',
        increaseActionLabel: 'Catat Pemakaian',
        tint: '#EF4444',
        icon: CreditCard,
    },
    personal_loan: {
        value: 'personal_loan',
        label: 'Pinjaman Pribadi (KTA)',
        shortLabel: 'Pinjaman / KTA',
        description: 'Pinjaman tunai bertenor tetap seperti KTA atau KPR',
        namePlaceholder: 'cth. KTA Bank Mandiri, Pinjaman Modal',
        increaseActionLabel: 'Tambah Pinjaman',
        tint: '#F97316',
        icon: Banknote,
    },
    installment: {
        value: 'installment',
        label: 'Cicilan / PayLater',
        shortLabel: 'Cicilan',
        description:
            'Cicilan bulanan tetap untuk gadget, kendaraan, atau PayLater',
        namePlaceholder: 'cth. SPayLater, Cicilan Motor, Kredivo',
        increaseActionLabel: 'Tambah Tagihan',
        tint: '#8B5CF6',
        icon: CalendarClock,
    },
    other_debt: {
        value: 'other_debt',
        label: 'Utang Lainnya',
        shortLabel: 'Lainnya',
        description: 'Pinjaman keluarga, rekan, atau kewajiban finansial lain',
        namePlaceholder: 'cth. Pinjaman Keluarga, Talangan Usaha',
        increaseActionLabel: 'Tambah Utang',
        tint: '#64748B',
        icon: Receipt,
    },
};

export const LIABILITY_LABEL_MAP: Record<string, string> = {
    'Credit Card': 'Kartu Kredit',
    'Personal Loan': 'Pinjaman Pribadi',
    'Installment / Cicilan': 'Cicilan / PayLater',
    Installment: 'Cicilan / PayLater',
    'Other Debt': 'Utang Lainnya',
};

export const LIABILITY_CURRENCIES = ['IDR', 'USD', 'SGD', 'EUR'] as const;

export const getLiabilityTypeMeta = (
    type?: string | null,
): LiabilityTypeMeta => {
    if (type && type in LIABILITY_TYPE_META) {
        return LIABILITY_TYPE_META[type as LiabilityTypeValue];
    }

    return LIABILITY_TYPE_META.other_debt;
};

export const translateLiabilityLabel = (label?: string | null): string => {
    if (!label) {
        return 'Utang Lainnya';
    }

    return LIABILITY_LABEL_MAP[label] ?? label;
};

export const getLiabilityTintStyle = (
    tint?: string | null,
): Record<string, string> => {
    const color = tint || '#64748B';

    return {
        backgroundColor: `color-mix(in srgb, ${color} 14%, transparent)`,
        color,
    };
};

export const getPayoffProgress = (
    initialBalance?: number | null,
    currentBalance?: number | null,
): number => {
    const initial = Number(initialBalance ?? 0);
    const current = Number(currentBalance ?? 0);

    if (initial <= 0) {
        return current <= 0 ? 100 : 0;
    }

    const paid = Math.max(0, initial - current);

    return Math.min(100, Math.max(0, Math.round((paid / initial) * 100)));
};

export const getCreditUtilization = (
    currentBalance?: number | null,
    creditLimit?: number | null,
): number => {
    const limit = Number(creditLimit ?? 0);
    const used = Number(currentBalance ?? 0);

    if (limit <= 0) {
        return 0;
    }

    return Math.min(100, Math.max(0, Math.round((used / limit) * 100)));
};

export const getUtilizationStatus = (
    pct: number,
): { label: string; color: string; badgeClass: string } => {
    if (pct >= 70) {
        return {
            label: 'Tinggi',
            color: '#ef4444',
            badgeClass: 'bg-rose-500/10 text-rose-700 dark:text-rose-400',
        };
    }

    if (pct >= 30) {
        return {
            label: 'Waspada',
            color: '#f59e0b',
            badgeClass: 'bg-amber-500/10 text-amber-700 dark:text-amber-400',
        };
    }

    return {
        label: 'Aman',
        color: '#10b981',
        badgeClass: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400',
    };
};

export const formatLiabilityDate = (
    dateStr?: string | null,
    withTime = false,
): string => {
    if (!dateStr) {
        return '—';
    }

    const parsed = dayjs(dateStr).locale('id');

    if (!parsed.isValid()) {
        return '—';
    }

    return withTime
        ? parsed.format('D MMM YYYY, HH.mm')
        : parsed.format('D MMM YYYY');
};

export const toDateInputValue = (dateStr?: string | null): string => {
    if (!dateStr) {
        return '';
    }

    if (/^\d{4}-\d{2}-\d{2}/.test(dateStr)) {
        return dateStr.slice(0, 10);
    }

    const parsed = dayjs(dateStr);

    return parsed.isValid() ? parsed.format('YYYY-MM-DD') : '';
};
