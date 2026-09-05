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
}
