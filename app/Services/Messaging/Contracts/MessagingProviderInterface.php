<?php

namespace App\Services\Messaging\Contracts;

use App\Services\Messaging\DTOs\IncomingMessage;
use App\Services\Messaging\DTOs\SendResult;
use Illuminate\Http\Request;

interface MessagingProviderInterface
{
    /**
     * Send a text message to a phone number.
     */
    public function sendMessage(string $phoneNumber, string $message): SendResult;

    /**
     * Send a message with quick reply buttons if supported.
     *
     * @param array<int, string|array{id: string, text: string}> $buttons
     */
    public function sendWithButtons(string $phoneNumber, string $message, array $buttons): SendResult;

    /**
     * Verify the webhook request signature or token.
     */
    public function verifyWebhook(Request $request): bool;

    /**
     * Parse incoming webhook payload into a standardized IncomingMessage object.
     */
    public function parseWebhook(Request $request): IncomingMessage;

    /**
     * Get the provider identifier name (e.g. 'fonnte', 'meta').
     */
    public function getIdentifier(): string;
}

