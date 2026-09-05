<?php

namespace App\Enums;

enum TransactionSource: string
{
    case MANUAL = 'manual';
    case AI_WEB = 'ai_web';
    case WHATSAPP = 'whatsapp';
    case RECURRING = 'recurring';
    case IMPORT = 'import';
}
