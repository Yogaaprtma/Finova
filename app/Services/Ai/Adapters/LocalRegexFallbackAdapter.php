<?php

namespace App\Services\Ai\Adapters;

use App\Services\Ai\AiParseResult;
use App\Services\Ai\Contracts\AiProviderInterface;
use App\Services\Ai\Normalizers\AmountNormalizer;
use App\Services\Ai\Normalizers\DateNormalizer;

class LocalRegexFallbackAdapter implements AiProviderInterface
{
    public function getIdentifier(): string
    {
        return 'fallback';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function parseTransaction(string $message, array $context = []): AiParseResult
    {
        $startTime = microtime(true);
        $trimmed = trim($message);
        $lower = mb_strtolower($trimmed);

        if (empty($trimmed)) {
            return new AiParseResult(
                rawMessage: $message,
                transactionType: null,
                amount: null,
                confidence: 0.0,
                missingFields: ['amount', 'type', 'category'],
                provider: 'fallback',
                model: 'rule-engine-v1',
                reasoning: 'Empty message provided',
            );
        }

        $amount = AmountNormalizer::extract($trimmed);
        $date = DateNormalizer::extract($trimmed);

        // 1. Determine Transaction Type
        $type = null;
        if (preg_match('/\b(?:transfer|tf|top\s*up|topup|isi\s*saldo|isi\s*gopay|isi\s*ovo|isi\s*dana)\b/i', $lower)) {
            $type = 'transfer';
        } elseif (preg_match('/\b(?:gaji|gajian|lembur|overtime|bonus|thr|pendapatan|terima|dapat|cair|upah|dividenda?)\b/i', $lower)) {
            $type = 'income';
        } elseif (preg_match('/\b(?:beli|bayar|makan|sarapan|jajan|ngopi|belanja|parkir|bensin|ojol|grab|gojek|tiket|sewa|tagihan|cicilan)\b/i', $lower)) {
            $type = 'expense';
        }

        // 2. Determine Category Slug from Context / Common mappings
        $categorySlug = null;
        $categories = $context['categories'] ?? [];
        $categoryMap = [
            'food' => ['makan', 'sarapan', 'siang', 'malam', 'nasi', 'kopi', 'ngopi', 'jajan', 'snack', 'restoran', 'cafe', 'mcd', 'kfc'],
            'transportation' => ['bensin', 'bensin motor', 'pertalite', 'pertamax', 'ojol', 'grab', 'gojek', 'parkir', 'tol', 'kereta', 'krl', 'mrt', 'angkot'],
            'shopping' => ['belanja', 'baju', 'sepatu', 'shopee', 'tokopedia', 'lazada', 'tiktok shop', 'beli tas'],
            'bills' => ['listrik', 'pln', 'air', 'pdam', 'wifi', 'indihome', 'pulsa', 'kuota', 'tagihan', 'iuran', 'bpjs'],
            'salary' => ['gaji', 'gajian', 'payroll', 'upah bulanan'],
            'bonus' => ['thr', 'bonus', 'insentif', 'lembur', 'overtime'],
            'health' => ['obat', 'dokter', 'klinik', 'apotek', 'rumah sakit', 'vitamin'],
            'entertainment' => ['nonton', 'bioskop', 'netflix', 'spotify', 'game', 'steam'],
        ];

        foreach ($categoryMap as $slug => $keywords) {
            foreach ($keywords as $kw) {
                if (preg_match('/\b' . preg_quote($kw, '/') . '\b/i', $lower)) {
                    $categorySlug = $slug;
                    break 2;
                }
            }
        }

        // Match against user's actual categories if available
        if (!empty($categories) && is_array($categories)) {
            $matchedUserCategory = null;
            foreach ($categories as $cat) {
                $cName = mb_strtolower($cat['name'] ?? '');
                $cSlug = $cat['slug'] ?? '';
                if (!empty($cName) && preg_match('/\b' . preg_quote($cName, '/') . '\b/i', $lower)) {
                    $matchedUserCategory = $cSlug;
                    break;
                }
            }
            if ($matchedUserCategory) {
                $categorySlug = $matchedUserCategory;
            }
        }

        // 3. Extract Account hints
        $accountHint = null;
        $fromAccountHint = null;
        $toAccountHint = null;

        // E-wallet topup or transfer pattern: "transfer bca ke mandiri 1jt"
        if ($type === 'transfer') {
            if (preg_match('/(?:transfer|tf)\s+([a-z0-9]+)\s+ke\s+([a-z0-9]+)/i', $lower, $m)) {
                $fromAccountHint = $m[1];
                $toAccountHint = $m[2];
            } elseif (preg_match('/(?:top\s*up|topup|isi)\s+([a-z0-9]+)/i', $lower, $m)) {
                $toAccountHint = $m[1];
            }
        }

        if (preg_match('/\b(bca|mandiri|bri|bni|bsi|jago|jenius|gopay|ovo|dana|shopeepay|cash|tunai)\b/i', $lower, $accM)) {
            $accountHint = $accM[1];
        }

        // 4. Clean description
        $description = $trimmed;
        // If message is e.g. "beli nasi goreng 15rb", extract "nasi goreng" or clean verb
        if ($type === 'expense' && preg_match('/(?:beli|bayar|makan)\s+(.*?)(?:\s+(?:pake|pakai|via|di|sebesar|\d|rp))/i', $trimmed, $dM)) {
            $candidateDesc = trim($dM[1]);
            if (!empty($candidateDesc)) {
                $description = ucfirst($candidateDesc);
            }
        }

        // 5. Calculate Confidence
        $missingFields = [];
        $confidence = 0.50;

        if ($amount !== null && $amount > 0) {
            $confidence += 0.25;
        } else {
            $missingFields[] = 'amount';
            $confidence -= 0.30;
        }

        if ($type !== null) {
            $confidence += 0.15;
        } else {
            $missingFields[] = 'transaction_type';
            $confidence -= 0.20;
        }

        if ($categorySlug !== null) {
            $confidence += 0.10;
        } elseif ($type !== 'transfer') {
            $missingFields[] = 'category';
        }

        $confidence = max(0.0, min(1.0, round($confidence, 2)));
        $responseTimeMs = (int) round((microtime(true) - $startTime) * 1000);

        return new AiParseResult(
            rawMessage: $message,
            transactionType: $type,
            amount: $amount,
            currency: 'IDR',
            date: $date,
            categorySlug: $categorySlug,
            description: $description,
            notes: null,
            accountHint: $accountHint,
            confidence: $confidence,
            missingFields: $missingFields,
            provider: 'fallback',
            model: 'rule-engine-v1',
            promptVersion: 'v1.0',
            responseTimeMs: $responseTimeMs,
            reasoning: 'Parsed using local Indonesian financial rule engine',
            fromAccountHint: $fromAccountHint,
            toAccountHint: $toAccountHint,
        );
    }
}

