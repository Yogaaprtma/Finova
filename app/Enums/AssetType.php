<?php

namespace App\Enums;

enum AssetType: string
{
    case STOCK = 'stock';
    case MUTUAL_FUND = 'mutual_fund';
    case CRYPTO = 'crypto';
    case OTHER_INVESTMENT = 'other_investment';
    case GOLD = 'gold';
    case PROPERTY = 'property';
    case VEHICLE = 'vehicle';
    case OTHER_ASSET = 'other_asset';

    public function label(): string
    {
        return match ($this) {
            self::STOCK => 'Stocks',
            self::MUTUAL_FUND => 'Mutual Funds',
            self::CRYPTO => 'Cryptocurrency',
            self::OTHER_INVESTMENT => 'Other Investment',
            self::GOLD => 'Gold & Precious Metals',
            self::PROPERTY => 'Property / Real Estate',
            self::VEHICLE => 'Vehicles',
            self::OTHER_ASSET => 'Other Asset',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::STOCK => '#3B82F6',
            self::MUTUAL_FUND => '#8B5CF6',
            self::CRYPTO => '#F59E0B',
            self::GOLD => '#EAB308',
            self::PROPERTY, self::VEHICLE => '#EC4899',
            self::OTHER_INVESTMENT => '#06B6D4',
            self::OTHER_ASSET => '#64748B',
        };
    }

    public function isInvestment(): bool
    {
        return match ($this) {
            self::STOCK,
            self::MUTUAL_FUND,
            self::CRYPTO,
            self::OTHER_INVESTMENT,
            self::GOLD => true,
            default => false,
        };
    }

    public function isValuedManually(): bool
    {
        return match ($this) {
            self::PROPERTY,
            self::VEHICLE,
            self::OTHER_ASSET => true,
            default => false,
        };
    }

    public function quantityPrecision(): int
    {
        return match ($this) {
            self::STOCK => 0,
            self::CRYPTO => 8,
            self::MUTUAL_FUND,
            self::GOLD,
            self::OTHER_INVESTMENT => 4,
            default => 0,
        };
    }

    public function multiplier(): int
    {
        return match ($this) {
            self::STOCK => 100,
            default => 1,
        };
    }
}
