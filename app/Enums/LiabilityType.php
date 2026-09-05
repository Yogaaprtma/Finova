<?php

namespace App\Enums;

enum LiabilityType: string
{
    case CREDIT_CARD = 'credit_card';
    case PERSONAL_LOAN = 'personal_loan';
    case INSTALLMENT = 'installment';
    case OTHER_DEBT = 'other_debt';
}
