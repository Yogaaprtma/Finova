<?php

namespace App\Services\Messaging;

class PhoneNumberNormalizer
{
    /**
     * Normalize an Indonesian/international phone number to E.164 without plus sign (e.g. 628123456789).
     */
    public static function normalize(string $number): string
    {
        // Strip everything except digits
        $digits = preg_replace('/[^\d]/', '', $number);

        if (empty($digits)) {
            return '';
        }

        // If starts with 08..., convert to 628...
        if (str_starts_with($digits, '08')) {
            $digits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62' . $digits;
        }

        return $digits;
    }

    /**
     * Check if a phone number appears to be a valid mobile phone.
     */
    public static function isValid(string $number): bool
    {
        $normalized = self::normalize($number);
        // Indonesian mobile numbers start with 628 and have 10 to 14 digits
        return (bool) preg_match('/^628\d{8,12}$/', $normalized);
    }
}

