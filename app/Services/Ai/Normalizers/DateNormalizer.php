<?php

namespace App\Services\Ai\Normalizers;

use Carbon\Carbon;

class DateNormalizer
{
    /**
     * Extract or infer date from natural language expression.
     */
    public static function extract(string $text, ?Carbon $referenceDate = null): ?string
    {
        $ref = $referenceDate ?? Carbon::today();
        $normalized = mb_strtolower(trim($text));

        // 1. Explicit standard dates (YYYY-MM-DD)
        if (preg_match('/\b(\d{4}-\d{2}-\d{2})\b/', $normalized, $matches)) {
            return $matches[1];
        }

        // 2. Explicit slash / dash format (DD/MM/YYYY or DD-MM-YYYY)
        if (preg_match('/\b(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})\b/', $normalized, $matches)) {
            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $year = $matches[3];
            return "{$year}-{$month}-{$day}";
        }

        // 3. Indonesian relative date words
        if (preg_match('/\b(?:kemarin\s*lusa|2\s*hari\s*lalu)\b/i', $normalized)) {
            return $ref->copy()->subDays(2)->format('Y-m-d');
        }

        if (preg_match('/\b(?:kemarin|kemaren)\b/i', $normalized)) {
            return $ref->copy()->subDay()->format('Y-m-d');
        }

        if (preg_match('/\b(?:minggu\s*lalu|1\s*minggu\s*lalu)\b/i', $normalized)) {
            return $ref->copy()->subWeek()->format('Y-m-d');
        }

        if (preg_match('/\b(?:tadi|barusan|hari\s*ini|tadi\s*pagi|tadi\s*siang|tadi\s*malam)\b/i', $normalized)) {
            return $ref->format('Y-m-d');
        }

        // Default to today if no date is specified
        return $ref->format('Y-m-d');
    }
}

