<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Jobs\ProcessWhatsAppMessage;
use App\Models\Account;
use App\Models\AiParsingLog;
use App\Models\Category;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Services\Messaging\Contracts\MessagingProviderInterface;
use App\Services\Messaging\DTOs\IncomingMessage;
use App\Services\Messaging\DTOs\SendResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class WhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Account $account;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'whatsapp_number' => '6281234567890',
            'whatsapp_verified' => true,
        ]);

        $this->account = Account::create([
            'user_id' => $this->user->id,
            'name' => 'BCA Checking',
            'type' => AccountType::BANK,
            'currency' => 'IDR',
            'initial_balance' => 10000000,
            'current_balance' => 10000000,
        ]);

        $this->category = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Makanan',
            'type' => 'expense',
            'icon' => 'utensils',
            'color' => '#f97316',
        ]);
    }

    public function test_rejects_webhook_when_secret_configured_and_invalid(): void
    {
        config(['finance.whatsapp.providers.fonnte.webhook_secret' => 'supersecret123']);

        $response = $this->postJson('/api/webhooks/whatsapp', [
            'sender' => '6281234567890',
            'message' => 'beli makan 25rb',
            'id' => 'msg-1',
        ]);

        $response->assertStatus(401);
    }

    public function test_accepts_webhook_when_secret_matches(): void
    {
        config(['finance.whatsapp.providers.fonnte.webhook_secret' => 'supersecret123']);

        $response = $this->postJson('/api/webhooks/whatsapp', [
            'sender' => '6281234567890',
            'message' => 'beli makan 25rb',
            'id' => 'msg-101',
            'secret' => 'supersecret123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('webhook_events', [
            'provider' => 'fonnte',
            'event_id' => 'msg-101',
        ]);
    }

    public function test_webhook_idempotency_prevents_duplicate_processing(): void
    {
        config(['finance.whatsapp.providers.fonnte.webhook_secret' => null]);

        $payload = [
            'sender' => '6281234567890',
            'message' => 'beli makan 25rb',
            'id' => 'msg-duplicate-test',
        ];

        // First call
        $first = $this->postJson('/api/webhooks/whatsapp', $payload);
        $first->assertStatus(200);
        $first->assertJsonPath('status', 'success');

        // Second call with same id
        $second = $this->postJson('/api/webhooks/whatsapp', $payload);
        $second->assertStatus(200);
        $second->assertJsonPath('status', 'duplicate');
    }

    public function test_job_notifies_unregistered_whatsapp_sender(): void
    {
        $mockProvider = new class implements MessagingProviderInterface {
            public array $sent = [];

            public function getIdentifier(): string { return 'mock'; }
            public function sendMessage(string $phoneNumber, string $message): SendResult {
                $this->sent[] = ['to' => $phoneNumber, 'message' => $message];
                return new SendResult(true, 'mock-id', 'mock');
            }
            public function sendWithButtons(string $phoneNumber, string $message, array $buttons): SendResult {
                return $this->sendMessage($phoneNumber, $message);
            }
            public function verifyWebhook(Request $request): bool { return true; }
            public function parseWebhook(Request $request): IncomingMessage {
                return new IncomingMessage('1', '628999999999', 'hello', now()->toISOString(), 'mock');
            }
        };

        $event = WebhookEvent::create([
            'provider' => 'mock',
            'event_id' => 'event-unregistered-1',
            'event_type' => 'message',
            'payload' => [],
            'status' => 'received',
        ]);

        $incoming = new IncomingMessage(
            messageId: 'event-unregistered-1',
            phoneNumber: '08999999999', // Not registered in DB
            text: 'beli makan siang 20rb',
            timestamp: now()->toISOString(),
            provider: 'mock'
        );

        $job = new ProcessWhatsAppMessage($incoming, $event->id);
        $job->handle(app(\App\Services\Ai\AiParserService::class), $mockProvider);

        $this->assertCount(1, $mockProvider->sent);
        $this->assertStringContainsString('belum terhubung dengan akun Finova', $mockProvider->sent[0]['message']);

        $event->refresh();
        $this->assertSame('processed', $event->status);
    }

    public function test_job_processes_high_confidence_transaction_and_saves_it(): void
    {
        $mockProvider = new class implements MessagingProviderInterface {
            public array $sent = [];

            public function getIdentifier(): string { return 'mock'; }
            public function sendMessage(string $phoneNumber, string $message): SendResult {
                $this->sent[] = ['to' => $phoneNumber, 'message' => $message];
                return new SendResult(true, 'mock-id', 'mock');
            }
            public function sendWithButtons(string $phoneNumber, string $message, array $buttons): SendResult {
                return $this->sendMessage($phoneNumber, $message);
            }
            public function verifyWebhook(Request $request): bool { return true; }
            public function parseWebhook(Request $request): IncomingMessage {
                return new IncomingMessage('1', '6281234567890', 'beli kopi 25rb pake bca', now()->toISOString(), 'mock');
            }
        };

        $event = WebhookEvent::create([
            'provider' => 'mock',
            'event_id' => 'event-tx-1',
            'event_type' => 'message',
            'payload' => [],
            'status' => 'received',
        ]);

        $incoming = new IncomingMessage(
            messageId: 'event-tx-1',
            phoneNumber: '081234567890', // Belongs to $this->user
            text: 'beli kopi 25rb pake bca',
            timestamp: now()->toISOString(),
            provider: 'mock'
        );

        $job = new ProcessWhatsAppMessage($incoming, $event->id);
        $job->handle(app(\App\Services\Ai\AiParserService::class), $mockProvider);

        // Assert transaction was created
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'type' => 'expense',
            'amount' => 25000,
            'source' => 'whatsapp',
        ]);

        // Assert WhatsApp confirmation receipt was sent
        $this->assertCount(1, $mockProvider->sent);
        $this->assertStringContainsString('Transaksi Berhasil Dicatat', $mockProvider->sent[0]['message']);
        $this->assertStringContainsString('Rp 25.000', $mockProvider->sent[0]['message']);

        $event->refresh();
        $this->assertSame('processed', $event->status);
    }

    public function test_job_confirms_pending_transaction_on_quick_reply(): void
    {
        $mockProvider = new class implements MessagingProviderInterface {
            public array $sent = [];

            public function getIdentifier(): string { return 'mock'; }
            public function sendMessage(string $phoneNumber, string $message): SendResult {
                $this->sent[] = ['to' => $phoneNumber, 'message' => $message];
                return new SendResult(true, 'mock-id', 'mock');
            }
            public function sendWithButtons(string $phoneNumber, string $message, array $buttons): SendResult {
                return $this->sendMessage($phoneNumber, $message);
            }
            public function verifyWebhook(Request $request): bool { return true; }
            public function parseWebhook(Request $request): IncomingMessage {
                return new IncomingMessage('1', '6281234567890', 'ya', now()->toISOString(), 'mock');
            }
        };

        // Create a pending AiParsingLog from earlier medium confidence message
        $log = AiParsingLog::create([
            'user_id' => $this->user->id,
            'source' => 'whatsapp',
            'raw_message' => 'beli bensin 50000',
            'parsed_data' => [
                'transaction_type' => 'expense',
                'amount' => 50000,
                'date' => '2026-11-09',
                'description' => 'Beli bensin',
            ],
            'confidence' => 0.75,
            'ai_provider' => 'fallback',
            'ai_model' => 'rule-engine',
            'prompt_version' => 'v1.0',
            'status' => 'pending',
        ]);

        $event = WebhookEvent::create([
            'provider' => 'mock',
            'event_id' => 'event-confirm-1',
            'event_type' => 'message',
            'payload' => [],
            'status' => 'received',
        ]);

        $incoming = new IncomingMessage(
            messageId: 'event-confirm-1',
            phoneNumber: '081234567890',
            text: 'ya',
            timestamp: now()->toISOString(),
            provider: 'mock'
        );

        $job = new ProcessWhatsAppMessage($incoming, $event->id);
        $job->handle(app(\App\Services\Ai\AiParserService::class), $mockProvider);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'type' => 'expense',
            'amount' => 50000,
            'description' => 'Beli bensin',
            'source' => 'whatsapp',
        ]);

        $log->refresh();
        $this->assertSame('confirmed', $log->status);

        $this->assertCount(1, $mockProvider->sent);
        $this->assertStringContainsString('Transaksi Berhasil Dikonfirmasi', $mockProvider->sent[0]['message']);
    }
}

