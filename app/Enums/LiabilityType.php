<?php

namespace App\Enums;

enum LiabilityType: string
{
    case CREDIT_CARD = 'credit_card';
    case PERSONAL_LOAN = 'personal_loan';
    case INSTALLMENT = 'installment';
    case OTHER_DEBT = 'other_debt';

    public function label(): string
    {
        return match ($this) {
            self::CREDIT_CARD => 'Credit Card',
            self::PERSONAL_LOAN => 'Personal Loan',
            self::INSTALLMENT => 'Installment / Cicilan',
            self::OTHER_DEBT => 'Other Debt',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::CREDIT_CARD => '#EF4444',
            self::PERSONAL_LOAN => '#F97316',
            self::INSTALLMENT => '#8B5CF6',
            self::OTHER_DEBT => '#64748B',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::CREDIT_CARD => 'Revolving credit facility with monthly limit & due date',
            self::PERSONAL_LOAN => 'Fixed-term personal loan or cash loan (KTA)',
            self::INSTALLMENT => 'Fixed monthly installment (gadget, vehicle, BNPL)',
            self::OTHER_DEBT => 'Miscellaneous debt or informal borrowing',
        };
    }
}
