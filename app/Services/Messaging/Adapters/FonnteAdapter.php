<?php

namespace App\Services\Messaging\Adapters;

use App\Services\Messaging\Contracts\MessagingProviderInterface;
use App\Services\Messaging\DTOs\IncomingMessage;
use App\Services\Messaging\DTOs\SendResult;
use App\Services\Messaging\PhoneNumberNormalizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FonnteAdapter implements MessagingProviderInterface
{
    protected ?string $token;
    protected ?string $webhookSecret;
    protected string $apiUrl;
    protected int $timeout;

    public function __construct(
        ?string $token = null,
        ?string $webhookSecret = null,
        ?string $apiUrl = null,
        int $timeout = 5
    ) {
        $this->token = $token ?? config('finance.whatsapp.providers.fonnte.token');
        $this->webhookSecret = $webhookSecret ?? config('finance.whatsapp.providers.fonnte.webhook_secret');
        $this->apiUrl = $apiUrl ?? config('finance.whatsapp.providers.fonnte.api_url', 'https://api.fonnte.com/send');
        $this->timeout = $timeout;
    }

    public function getIdentifier(): string
    {
        return 'fonnte';
    }

    public function sendMessage(string $phoneNumber, string $message): SendResult
    {
        $normalizedPhone = PhoneNumberNormalizer::normalize($phoneNumber);

        if (empty($this->token)) {
            Log::info("[Fonnte Mock Send] To: {$normalizedPhone} | Message: {$message}");
            return new SendResult(
                success: true,
                messageId: 'mock-' . uniqid(),
                provider: 'fonnte'
            );
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])
                ->timeout($this->timeout)
                ->asForm()
                ->post($this->apiUrl, [
                    'target' => $normalizedPhone,
                    'message' => $message,
                ]);

            if ($response->successful()) {
                $json = $response->json();
                $msgId = $json['id'] ?? $json['message_id'] ?? (string) uniqid();
                return new SendResult(
                    success: true,
                    messageId: (string) $msgId,
                    provider: 'fonnte'
                );
            }

            Log::warning('Fonnte send message returned error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return new SendResult(
                success: false,
                error: $response->body(),
                provider: 'fonnte'
            );
        } catch (\Throwable $e) {
            Log::error('Fonnte send message exception: ' . $e->getMessage());
            return new SendResult(
                success: false,
                error: $e->getMessage(),
                provider: 'fonnte'
            );
        }
    }

    public function sendWithButtons(string $phoneNumber, string $message, array $buttons): SendResult
    {
        // Fonnte button formatting: append choices to message or use button param if supported
        $buttonText = "\n\n" . implode("\n", array_map(fn ($b) => "👉 " . (is_array($b) ? $b['text'] : $b), $buttons));
        return $this->sendMessage($phoneNumber, $message . $buttonText);
    }

    public function verifyWebhook(Request $request): bool
    {
        // If webhook secret is configured, verify token header or payload token
        if (!empty($this->webhookSecret)) {
            $providedToken = $request->header('X-Fonnte-Secret')
                ?? $request->input('secret')
                ?? $request->input('token');

            if ($providedToken !== $this->webhookSecret) {
                return false;
            }
        }

        // Basic payload validation
        return $request->has('sender') && $request->has('message');
    }

    public function parseWebhook(Request $request): IncomingMessage
    {
        $sender = (string) ($request->input('sender') ?? $request->input('from') ?? '');
        $message = (string) ($request->input('message') ?? $request->input('text') ?? '');
        $id = (string) ($request->input('id') ?? $request->input('message_id') ?? md5($sender . $message . now()->timestamp));
        $timestamp = (string) ($request->input('timestamp') ?? now()->toISOString());

        return new IncomingMessage(
            messageId: $id,
            phoneNumber: PhoneNumberNormalizer::normalize($sender),
            text: trim($message),
            timestamp: $timestamp,
            provider: 'fonnte',
            rawPayload: $request->all()
        );
    }
}

