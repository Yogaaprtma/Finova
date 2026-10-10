<?php

namespace App\Services\Ai;

class AiParseResult
{
    /**
     * @param array<int, string> $missingFields
     */
    public function __construct(
        public readonly string $rawMessage,
        public readonly ?string $transactionType = null,    // 'income', 'expense', 'transfer'
        public readonly ?int $amount = null,                 // In smallest currency unit (e.g. 15000)
        public readonly ?string $currency = 'IDR',
        public readonly ?string $date = null,                // 'YYYY-MM-DD'
        public readonly ?string $categorySlug = null,        // Matched category slug
        public readonly ?string $description = null,         // Extracted description
        public readonly ?string $notes = null,
        public readonly ?string $accountHint = null,         // e.g. 'gopay', 'cash', 'bca'
        public readonly float $confidence = 0.0,             // 0.0 to 1.0
        public readonly array $missingFields = [],
        public readonly string $provider = 'gemini',         // 'gemini', 'openai', 'fallback'
        public readonly string $model = 'gemini-2.0-flash',
        public readonly string $promptVersion = 'v1.0',
        public readonly int $responseTimeMs = 0,
        public readonly ?string $reasoning = null,
        public readonly ?string $fromAccountHint = null,
        public readonly ?string $toAccountHint = null,
    ) {}

    public function isHighConfidence(float $threshold = 0.85): bool
    {
        return $this->confidence >= $threshold;
    }

    public function isMediumConfidence(float $lowThreshold = 0.50, float $highThreshold = 0.85): bool
    {
        return $this->confidence >= $lowThreshold && $this->confidence < $highThreshold;
    }

    public function isLowConfidence(float $threshold = 0.50): bool
    {
        return $this->confidence < $threshold;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'raw_message' => $this->rawMessage,
            'transaction_type' => $this->transactionType,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'date' => $this->date,
            'category_slug' => $this->categorySlug,
            'description' => $this->description,
            'notes' => $this->notes,
            'account_hint' => $this->accountHint,
            'from_account_hint' => $this->fromAccountHint,
            'to_account_hint' => $this->toAccountHint,
            'confidence' => $this->confidence,
            'missing_fields' => $this->missingFields,
            'provider' => $this->provider,
            'model' => $this->model,
            'prompt_version' => $this->promptVersion,
            'response_time_ms' => $this->responseTimeMs,
            'reasoning' => $this->reasoning,
        ];
    }
}

