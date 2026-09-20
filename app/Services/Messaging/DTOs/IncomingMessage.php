<?php

namespace App\Services\Messaging\DTOs;

class IncomingMessage
{
    /**
     * @param array<string, mixed> $rawPayload
     */
    public function __construct(
        public readonly string $messageId,
        public readonly string $phoneNumber,
        public readonly string $text,
        public readonly string $timestamp,
        public readonly string $provider,
        public readonly array $rawPayload = []
    ) {}
}

