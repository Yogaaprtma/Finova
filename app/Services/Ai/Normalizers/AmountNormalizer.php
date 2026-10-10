<?php

namespace App\Services\Ai\Normalizers;

class AmountNormalizer
{
    /**
     * Extract integer IDR amount from natural language text.
     */
    public static function extract(string $text): ?int
    {
        $normalized = mb_strtolower(trim($text));

        // 1. Check special phrases
        if (preg_match('/\b(?:se-?juta|1\s*juta)\b/i', $normalized)) {
            return 1_000_000;
        }
        if (preg_match('/\b(?:setengah\s*juta)\b/i', $normalized)) {
            return 500_000;
        }
        if (preg_match('/\b(?:se-?ratus\s*ribu)\b/i', $normalized)) {
            return 100_000;
        }
        if (preg_match('/\b(?:se-?puluh\s*ribu)\b/i', $normalized)) {
            return 10_000;
        }
        if (preg_match('/\b(?:se-?miliar)\b/i', $normalized)) {
            return 1_000_000_000;
        }

        // 2. Patterns with 'miliar' / 'milyar' / 'm' (billions)
        if (preg_match('/(?:rp\.?\s*)?(\d+(?:[.,]\d+)?)\s*(?:miliar|milyar)\b/i', $normalized, $matches)) {
            $numStr = str_replace(',', '.', $matches[1]);
            return (int) round(((float) $numStr) * 1_000_000_000);
        }

        // 3. Patterns with 'jt' / 'juta' (millions) - e.g. 1.5jt, 1,5juta, 2jt, 10 juta
        if (preg_match('/(?:rp\.?\s*)?(\d+(?:[.,]\d+)?)\s*(?:jt|juta)\b/i', $normalized, $matches)) {
            $numStr = str_replace(',', '.', $matches[1]);
            return (int) round(((float) $numStr) * 1_000_000);
        }

        // 4. Patterns with 'rb' / 'ribu' / 'k' (thousands) - e.g. 15rb, 15ribu, 15k, 500k, 2.5rb
        if (preg_match('/(?:rp\.?\s*)?(\d+(?:[.,]\d+)?)\s*(?:rb|ribu|k)\b/i', $normalized, $matches)) {
            $numStr = str_replace(',', '.', $matches[1]);
            return (int) round(((float) $numStr) * 1_000);
        }

        // 5. Indonesian thousands with dot separator: e.g. 2.000.000, 15.000, Rp 50.000
        if (preg_match('/(?:rp\.?\s*)?(\d{1,3}(?:\.\d{3})+)(?!\d)/i', $normalized, $matches)) {
            $clean = str_replace('.', '', $matches[1]);
            $val = (int) $clean;
            if ($val > 0) {
                return $val;
            }
        }

        // 6. US / standard format with comma separator: e.g. 2,000,000, 15,000
        if (preg_match('/(?:rp\.?\s*)?(\d{1,3}(?:,\d{3})+)(?!\d)/i', $normalized, $matches)) {
            $clean = str_replace(',', '', $matches[1]);
            $val = (int) $clean;
            if ($val > 0) {
                return $val;
            }
        }

        // 7. Plain digits with Rp prefix: e.g. Rp 50000, rp15000
        if (preg_match('/(?:rp\.?\s*)(\d{3,12})\b/i', $normalized, $matches)) {
            return (int) $matches[1];
        }

        // 8. Plain digits of 4-12 digits without prefix: e.g. 15000, 50000, 1000000
        if (preg_match('/\b(\d{4,12})\b/', $normalized, $matches)) {
            return (int) $matches[1];
        }

        // 9. Indonesian words to number (e.g. "dua ratus ribu", "lima puluh ribu")
        $wordAmount = self::parseIndonesianWords($normalized);
        if ($wordAmount !== null) {
            return $wordAmount;
        }

        return null;
    }

    /**
     * Simple Indonesian word-based numbers converter.
     */
    private static function parseIndonesianWords(string $text): ?int
    {
        $words = [
            'satu' => 1, 'dua' => 2, 'tiga' => 3, 'empat' => 4, 'lima' => 5,
            'enam' => 6, 'tujuh' => 7, 'delapan' => 8, 'sembilan' => 9, 'sepuluh' => 10,
            'sebelas' => 11, 'dua belas' => 12, 'tiga belas' => 13, 'empat belas' => 14,
            'lima belas' => 15, 'enam belas' => 16, 'tujuh belas' => 17, 'delapan belas' => 18,
            'sembilan belas' => 19,
        ];

        // Match patterns like: "dua ratus ribu", "lima puluh ribu", "tiga juta"
        if (preg_match('/\b([a-z\s]+?)\s*(ribu|juta|miliar)\b/i', $text, $matches)) {
            $multiplierStr = strtolower($matches[2]);
            $numberWords = trim(strtolower($matches[1]));

            $mult = match ($multiplierStr) {
                'ribu' => 1_000,
                'juta' => 1_000_000,
                'miliar' => 1_000_000_000,
                default => 1,
            };

            $base = 0;
            if (isset($words[$numberWords])) {
                $base = $words[$numberWords];
            } elseif (preg_match('/^(\w+)\s*puluh(?:\s*(\w+))?$/', $numberWords, $m)) {
                $tens = $words[$m[1]] ?? 0;
                $ones = isset($m[2]) ? ($words[$m[2]] ?? 0) : 0;
                $base = ($tens * 10) + $ones;
            } elseif (preg_match('/^(\w+)\s*ratus(?:\s*(.+))?$/', $numberWords, $m)) {
                $hundreds = ($m[1] === 'se' || $m[1] === 'satu') ? 1 : ($words[$m[1]] ?? 0);
                $rest = 0;
                if (!empty($m[2])) {
                    if (isset($words[$m[2]])) {
                        $rest = $words[$m[2]];
                    } elseif (preg_match('/^(\w+)\s*puluh(?:\s*(\w+))?$/', $m[2], $subM)) {
                        $tens = $words[$subM[1]] ?? 0;
                        $ones = isset($subM[2]) ? ($words[$subM[2]] ?? 0) : 0;
                        $rest = ($tens * 10) + $ones;
                    }
                }
                $base = ($hundreds * 100) + $rest;
            }

            if ($base > 0) {
                return $base * $mult;
            }
        }

        return null;
    }
}

