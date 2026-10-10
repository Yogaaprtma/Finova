<?php

namespace App\Services\Ai\Adapters;

use App\Services\Ai\AiParseResult;
use App\Services\Ai\Contracts\AiProviderInterface;
use App\Services\Ai\Normalizers\AmountNormalizer;
use App\Services\Ai\Normalizers\DateNormalizer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiAdapter implements AiProviderInterface
{
    protected ?string $apiKey;
    protected string $model;
    protected int $timeout;

    public function __construct(?string $apiKey = null, ?string $model = null, int $timeout = 5)
    {
        $this->apiKey = $apiKey ?? config('finance.ai.providers.openai.api_key');
        $this->model = $model ?? config('finance.ai.providers.openai.model', 'gpt-4o-mini');
        $this->timeout = $timeout;
    }

    public function getIdentifier(): string
    {
        return 'openai';
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    public function parseTransaction(string $message, array $context = []): AiParseResult
    {
        $startTime = microtime(true);

        if (!$this->isAvailable()) {
            $fallback = new LocalRegexFallbackAdapter();
            return $fallback->parseTransaction($message, $context);
        }

        $systemPrompt = $this->buildSystemPrompt($context);

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout($this->timeout)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => "Parse this message into a transaction: \"{$message}\""],
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.1,
                ]);

            $responseTimeMs = (int) round((microtime(true) - $startTime) * 1000);

            if (!$response->successful()) {
                Log::warning('OpenAI API returned non-200', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                $fallback = new LocalRegexFallbackAdapter();
                return $fallback->parseTransaction($message, $context);
            }

            $jsonResponse = $response->json();
            $rawContent = $jsonResponse['choices'][0]['message']['content'] ?? '{}';
            $parsed = json_decode($rawContent, true);

            if (!is_array($parsed)) {
                $fallback = new LocalRegexFallbackAdapter();
                return $fallback->parseTransaction($message, $context);
            }

            return $this->formatResult($message, $parsed, $responseTimeMs);
        } catch (\Throwable $e) {
            Log::warning('OpenAI API call failed with exception: ' . $e->getMessage());
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
4. Transaction types: "income", "expense", "transfer".
5. If category is unclear, set category_slug to null.

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
  "confidence": float,
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
        $amount = isset($parsed['amount']) && is_numeric($parsed['amount'])
            ? (int) $parsed['amount']
            : AmountNormalizer::extract($message);

        $date = !empty($parsed['date'])
            ? (string) $parsed['date']
            : DateNormalizer::extract($message);

        $confidence = isset($parsed['confidence']) && is_numeric($parsed['confidence'])
            ? (float) $parsed['confidence']
            : 0.70;

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
            provider: 'openai',
            model: $this->model,
            promptVersion: config('finance.ai.prompt_version', 'v1.0'),
            responseTimeMs: $responseTimeMs,
            reasoning: $parsed['reasoning'] ?? null,
            fromAccountHint: $parsed['from_account_hint'] ?? null,
            toAccountHint: $parsed['to_account_hint'] ?? null,
        );
    }
}

