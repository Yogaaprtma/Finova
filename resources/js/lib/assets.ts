import {
    BarChart3,
    Building2,
    Car,
    Coins,
    Gem,
    Layers,
    Package,
    PieChart,
} from '@lucide/vue';
import dayjs from 'dayjs';
import 'dayjs/locale/id';
import type { Component } from 'vue';

export type AssetTypeValue =
    | 'stock'
    | 'mutual_fund'
    | 'crypto'
    | 'other_investment'
    | 'gold'
    | 'property'
    | 'vehicle'
    | 'other_asset';

export interface AssetTypeMeta {
    value: AssetTypeValue;
    label: string;
    shortLabel: string;
    groupLabel: 'Investasi' | 'Aset Riil';
    description: string;
    tint: string;
    icon: Component;
    unitLabel: string;
    priceUnitHint: string;
    tickerPlaceholder: string;
    platformPlaceholder: string;
    precision: number;
    isManualDefault: boolean;
}

export const ASSET_TYPE_META: Record<AssetTypeValue, AssetTypeMeta> = {
    stock: {
        value: 'stock',
        label: 'Saham',
        shortLabel: 'Saham',
        groupLabel: 'Investasi',
        description: 'Saham bursa (1 lot = 100 lembar)',
        tint: '#3B82F6',
        icon: BarChart3,
        unitLabel: 'lot',
        priceUnitHint: 'per lembar',
        tickerPlaceholder: 'cth. BBCA, BBRI',
        platformPlaceholder: 'cth. Stockbit, Ajaib, IPOT',
        precision: 0,
        isManualDefault: false,
    },
    mutual_fund: {
        value: 'mutual_fund',
        label: 'Reksa Dana',
        shortLabel: 'Reksa Dana',
        groupLabel: 'Investasi',
        description: 'RDPU, obligasi, atau reksa dana saham',
        tint: '#8B5CF6',
        icon: PieChart,
        unitLabel: 'unit',
        priceUnitHint: 'NAB/unit',
        tickerPlaceholder: 'cth. SUCOR-MM, RDPU',
        platformPlaceholder: 'cth. Bibit, Bareksa, Tanamduit',
        precision: 4,
        isManualDefault: false,
    },
    crypto: {
        value: 'crypto',
        label: 'Kripto',
        shortLabel: 'Kripto',
        groupLabel: 'Investasi',
        description: 'Aset kripto spot & staking (hingga 8 desimal)',
        tint: '#F59E0B',
        icon: Coins,
        unitLabel: 'koin',
        priceUnitHint: 'per koin',
        tickerPlaceholder: 'cth. BTC, ETH, SOL',
        platformPlaceholder: 'cth. Pintu, Indodax, Binance',
        precision: 8,
        isManualDefault: false,
    },
    other_investment: {
        value: 'other_investment',
        label: 'Investasi Lainnya',
        shortLabel: 'Inv. Lainnya',
        groupLabel: 'Investasi',
        description: 'SBN, obligasi ritel, P2P, atau deposito terpisah',
        tint: '#06B6D4',
        icon: Layers,
        unitLabel: 'unit',
        priceUnitHint: 'per unit',
        tickerPlaceholder: 'cth. SBN010, FR0100',
        platformPlaceholder: 'cth. Bibit, Bank Mandiri',
        precision: 4,
        isManualDefault: false,
    },
    gold: {
        value: 'gold',
        label: 'Emas & Logam Mulia',
        shortLabel: 'Emas',
        groupLabel: 'Aset Riil',
        description: 'Emas batangan, digital, atau perhiasan',
        tint: '#EAB308',
        icon: Gem,
        unitLabel: 'gr',
        priceUnitHint: 'per gram',
        tickerPlaceholder: 'cth. ANTAM, UBS',
        platformPlaceholder: 'cth. Pegadaian, Treasury, Antam',
        precision: 4,
        isManualDefault: false,
    },
    property: {
        value: 'property',
        label: 'Properti',
        shortLabel: 'Properti',
        groupLabel: 'Aset Riil',
        description: 'Rumah, apartemen, ruko, atau tanah',
        tint: '#EC4899',
        icon: Building2,
        unitLabel: 'unit',
        priceUnitHint: 'nilai taksiran',
        tickerPlaceholder: 'cth. SHM-01 (opsional)',
        platformPlaceholder: 'cth. Jakarta Selatan',
        precision: 0,
        isManualDefault: true,
    },
    vehicle: {
        value: 'vehicle',
        label: 'Kendaraan',
        shortLabel: 'Kendaraan',
        groupLabel: 'Aset Riil',
        description: 'Mobil, motor, atau kendaraan operasional',
        tint: '#EC4899',
        icon: Car,
        unitLabel: 'unit',
        priceUnitHint: 'nilai taksiran',
        tickerPlaceholder: 'cth. B 1234 ABC',
        platformPlaceholder: 'cth. Garasi Pribadi',
        precision: 0,
        isManualDefault: true,
    },
    other_asset: {
        value: 'other_asset',
        label: 'Aset Lainnya',
        shortLabel: 'Aset Lainnya',
        groupLabel: 'Aset Riil',
        description: 'Barang koleksi, jam tangan, atau aset berharga lain',
        tint: '#64748B',
        icon: Package,
        unitLabel: 'unit',
        priceUnitHint: 'nilai taksiran',
        tickerPlaceholder: 'Opsional',
        platformPlaceholder: 'cth. Penyimpanan Pribadi',
        precision: 0,
        isManualDefault: true,
    },
};

export const ALLOCATION_LABEL_MAP: Record<string, string> = {
    'Cash & Bank': 'Kas & Bank',
    Stocks: 'Saham',
    'Mutual Funds': 'Reksa Dana',
    Crypto: 'Kripto',
    Cryptocurrency: 'Kripto',
    'Other Investment': 'Investasi Lainnya',
    'Gold & Precious Metals': 'Emas & Logam Mulia',
    'Property & Vehicles': 'Properti & Kendaraan',
    'Property / Real Estate': 'Properti',
    Vehicles: 'Kendaraan',
    'Other Asset': 'Aset Lainnya',
    Other: 'Lainnya',
    Others: 'Lainnya',
    'No Assets': 'Belum Ada Aset',
};

export const ASSET_CURRENCIES = ['IDR', 'USD', 'SGD', 'EUR'] as const;

export const getAssetTypeMeta = (type?: string | null): AssetTypeMeta => {
    if (type && type in ASSET_TYPE_META) {
        return ASSET_TYPE_META[type as AssetTypeValue];
    }

    return ASSET_TYPE_META.other_asset;
};

export const translateAssetAllocationLabel = (
    label?: string | null,
): string => {
    if (!label) {
        return 'Lainnya';
    }

    return ALLOCATION_LABEL_MAP[label] ?? label;
};

export const getAssetTintStyle = (
    tint?: string | null,
): Record<string, string> => {
    const color = tint || '#64748B';

    return {
        backgroundColor: `color-mix(in srgb, ${color} 14%, transparent)`,
        color,
    };
};

export const formatAssetQuantity = (
    quantity: number | string | null | undefined,
    type?: string | null,
    includeUnit = true,
): string => {
    const meta = getAssetTypeMeta(type);
    const num = Number(quantity ?? 0);
    const safeNum = Number.isFinite(num) ? num : 0;

    const formatted = new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: meta.precision,
    }).format(safeNum);

    return includeUnit ? `${formatted} ${meta.unitLabel}` : formatted;
};

export const formatStockShares = (
    quantity: number | string | null | undefined,
): string => {
    const num = Number(quantity ?? 0);
    const shares = Math.round((Number.isFinite(num) ? num : 0) * 100);

    return `${new Intl.NumberFormat('id-ID').format(shares)} lembar`;
};

export const formatAssetDate = (
    dateStr?: string | null,
    withTime = false,
): string => {
    if (!dateStr) {
        return 'Belum diperbarui';
    }

    const parsed = dayjs(dateStr).locale('id');

    if (!parsed.isValid()) {
        return 'Belum diperbarui';
    }

    return withTime
        ? parsed.format('D MMM YYYY, HH.mm')
        : parsed.format('D MMM YYYY');
};
