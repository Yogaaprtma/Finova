<?php

namespace App\Enums;

enum TransactionType: string
{
    case INCOME = 'income';
    case EXPENSE = 'expense';
    case TRANSFER = 'transfer';
    case ASSET_PURCHASE = 'asset_purchase';
    case ASSET_SALE = 'asset_sale';
    case LIABILITY_PAYMENT = 'liability_payment';
    case LIABILITY_INCREASE = 'liability_increase';
    case ADJUSTMENT = 'adjustment';
}
