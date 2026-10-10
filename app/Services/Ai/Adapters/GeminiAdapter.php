<?php

namespace App\Services\Ai\Adapters;

use App\Services\Ai\AiParseResult;
use App\Services\Ai\Contracts\AiProviderInterface;
use App\Services\Ai\Normalizers\AmountNormalizer;
use App\Services\Ai\Normalizers\DateNormalizer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiAdapter implements AiProviderInterface
{
    protected ?string $apiKey;
    protected string $model;
    protected int $timeout;

    public function __construct(?string $apiKey = null, ?string $model = null, int $timeout = 5)
    {
        $this->apiKey = $apiKey ?? config('finance.ai.providers.gemini.api_key');
        $this->model = $model ?? config('finance.ai.providers.gemini.model', 'gemini-2.0-flash');
        $this->timeout = $timeout;
    }

    public function getIdentifier(): string
    {
        return 'gemini';
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    public function parseTransaction(string $message, array $context = []): AiParseResult
    {
        $startTime = microtime(true);

        if (!$this->isAvailable()) {
            // If not available, fallback to local rule engine
            $fallback = new LocalRegexFallbackAdapter();
            return $fallback->parseTransaction($message, $context);
        }

        $systemPrompt = $this->buildSystemPrompt($context);
        $userPrompt = "Parse this message into a transaction: \"{$message}\"";

        try {
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

            $response = Http::timeout($this->timeout)->post($endpoint, [
                'system_instruction' => [
                    'parts' => [
                        ['text' => $systemPrompt],
                    ],
                ],
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $userPrompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'response_mime_type' => 'application/json',
                    'temperature' => 0.1,
                ],
            ]);

            $responseTimeMs = (int) round((microtime(true) - $startTime) * 1000);

            if (!$response->successful()) {
                Log::warning('Gemini API returned non-200', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                // Fallback to local regex
                $fallback = new LocalRegexFallbackAdapter();
                return $fallback->parseTransaction($message, $context);
            }

            $jsonResponse = $response->json();
            $rawText = $jsonResponse['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
            $parsed = json_decode($rawText, true);

            if (!is_array($parsed)) {
                $fallback = new LocalRegexFallbackAdapter();
                return $fallback->parseTransaction($message, $context);
            }

            return $this->formatResult($message, $parsed, $responseTimeMs);
        } catch (\Throwable $e) {
            Log::warning('Gemini API call failed with exception: ' . $e->getMessage());
            $fallback = new LocalRegexFallbackAdapter();
            return $fallback->parseTransaction($message, $context);
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    protected function buildSystemPrompt(array $context): string
    {
        $categoriesJson = json_encode($context['categories'] ?? [], JSON_UNESCAPED_UNICODE);
        $accountsJson = json_encode($context['accounts'] ?? [], JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
You are a financial transaction parser for an Indonesian personal finance application named Finova.
Your job is to extract structured transaction data from informal Indonesian or English messages.

RULES:
1. Extract: transaction type, amount, description, category, date, account hint.
2. Amount MUST be in Indonesian Rupiah (IDR) as an integer with no decimals.
   - "15rb" / "15k" / "15ribu" = 15000
   - "1.5jt" / "1,5juta" = 1500000
   - "2jt" = 2000000
3. If no date is mentioned, use today's date.
4. Transaction types:
   - "income" (gajian, lembur, thr, bonus, pendapatan)
   - "expense" (beli, bayar makan, belanja, jajan, bensin, parkir, ojol)
   - "transfer" (transfer, tf, top up, topup saldo)
5. If category is unclear, set category_slug to null and list "category" in missing_fields.

AVAILABLE CATEGORIES:
{$categoriesJson}

AVAILABLE ACCOUNTS:
{$accountsJson}

Respond ONLY with valid JSON matching this schema:
{
  "transaction_type": "income" | "expense" | "transfer" | null,
  "amount": integer | null,
  "currency": "IDR",
  "date": "YYYY-MM-DD" | null,
  "category_slug": string | null,
  "description": string | null,
  "notes": string | null,
  "account_hint": string | null,
  "from_account_hint": string | null,
  "to_account_hint": string | null,
  "confidence": float (0.0 to 1.0),
  "missing_fields": string[],
  "reasoning": string
}
PROMPT;
    }

    /**
     * @param array<string, mixed> $parsed
     */
    protected function formatResult(string $message, array $parsed, int $responseTimeMs): AiParseResult
    {
        // If amount was not parsed by AI, try regex normalizer as assist
        $amount = isset($parsed['amount']) && is_numeric($parsed['amount'])
            ? (int) $parsed['amount']
            : AmountNormalizer::extract($message);

        $date = !empty($parsed['date'])
            ? (string) $parsed['date']
            : DateNormalizer::extract($message);

        $confidence = isset($parsed['confidence']) && is_numeric($parsed['confidence'])
            ? (float) $parsed['confidence']
            : 0.70;

        // Apply confidence adjustment from spec
        if ($amount !== null && $amount > 0) {
            $confidence = min(1.0, $confidence + 0.05);
        } else {
            $confidence = max(0.0, $confidence - 0.20);
        }

        return new AiParseResult(
            rawMessage: $message,
            transactionType: $parsed['transaction_type'] ?? null,
            amount: $amount,
            currency: $parsed['currency'] ?? 'IDR',
            date: $date,
            categorySlug: $parsed['category_slug'] ?? null,
            description: $parsed['description'] ?? $message,
            notes: $parsed['notes'] ?? null,
            accountHint: $parsed['account_hint'] ?? null,
            confidence: round($confidence, 2),
            missingFields: (array) ($parsed['missing_fields'] ?? []),
            provider: 'gemini',
            model: $this->model,
            promptVersion: config('finance.ai.prompt_version', 'v1.0'),
            responseTimeMs: $responseTimeMs,
            reasoning: $parsed['reasoning'] ?? null,
            fromAccountHint: $parsed['from_account_hint'] ?? null,
            toAccountHint: $parsed['to_account_hint'] ?? null,
        );
    }
}

