<?php

namespace App\Services\Ai\Contracts;

use App\Services\Ai\AiParseResult;

interface AiProviderInterface
{
    /**
     * Parse a natural language financial message into structured data.
     *
     * @param string $message Raw user message
     * @param array<string, mixed> $context User context (categories, accounts, normalized_amount, etc.)
     * @return AiParseResult
     */
    public function parseTransaction(string $message, array $context = []): AiParseResult;

    /**
     * Check if this provider has valid credentials / is available.
     */
    public function isAvailable(): bool;

    /**
     * Get the provider identifier name (e.g. 'gemini', 'openai').
     */
    public function getIdentifier(): string;
}

