<?php

namespace App\Enums;

enum AccountType: string
{
    case CASH = 'cash';
    case BANK = 'bank';
    case EWALLET = 'ewallet';
}
