<?php

namespace App\Services\Messaging\DTOs;

class SendResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $messageId = null,
        public readonly ?string $error = null,
        public readonly string $provider = 'fonnte'
    ) {}
}

