<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWhatsAppMessage;
use App\Models\WebhookEvent;
use App\Services\Messaging\Adapters\FonnteAdapter;
use App\Services\Messaging\Contracts\MessagingProviderInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        protected ?MessagingProviderInterface $messagingProvider = null
    ) {
        $this->messagingProvider = $messagingProvider ?? new FonnteAdapter();
    }

    /**
     * Handle incoming WhatsApp Webhook.
     */
    public function handle(Request $request): JsonResponse
    {
        $incoming = $this->messagingProvider->parseWebhook($request);

        // 1. Check idempotency (duplicate message_id)
        $existing = WebhookEvent::where('provider', $this->messagingProvider->getIdentifier())
            ->where('event_id', $incoming->messageId)
            ->first();

        if ($existing) {
            return response()->json([
                'status' => 'duplicate',
                'message' => 'Event already received and processed.',
            ], 200);
        }

        // 2. Persist to webhook_events
        $event = WebhookEvent::create([
            'provider' => $this->messagingProvider->getIdentifier(),
            'event_id' => $incoming->messageId,
            'event_type' => 'message',
            'payload' => $request->all(),
            'status' => 'received',
        ]);

        // 3. Dispatch processing job (run synchronously or queue)
        ProcessWhatsAppMessage::dispatch($incoming, $event->id);

        return response()->json([
            'status' => 'success',
            'event_id' => $event->id,
        ], 200);
    }
}

