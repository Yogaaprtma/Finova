<?php

namespace App\Jobs;

use App\Models\AiParsingLog;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Services\Ai\AiParserService;
use App\Services\Messaging\Adapters\FonnteAdapter;
use App\Services\Messaging\Contracts\MessagingProviderInterface;
use App\Services\Messaging\DTOs\IncomingMessage;
use App\Services\Messaging\PhoneNumberNormalizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public IncomingMessage $incomingMessage,
        public string $webhookEventId
    ) {}

    public function handle(
        AiParserService $aiParserService,
        ?MessagingProviderInterface $messagingProvider = null
    ): void {
        $provider = $messagingProvider ?? new FonnteAdapter();
        $event = WebhookEvent::find($this->webhookEventId);

        if (!$event) {
            return;
        }

        $event->update(['status' => 'processing']);

        $phone = PhoneNumberNormalizer::normalize($this->incomingMessage->phoneNumber);
        $text = trim($this->incomingMessage->text);

        try {
            // 1. Look up user by normalized phone number
            $user = User::where('whatsapp_number', $phone)
                ->orWhere('whatsapp_number', '0' . substr($phone, 2))
                ->orWhere('whatsapp_number', '+' . $phone)
                ->first();

            if (!$user) {
                $provider->sendMessage(
                    $phone,
                    "👋 *Halo dari Finova!*\n\nNomor WhatsApp Anda ({$phone}) belum terhubung dengan akun Finova.\n\nSilakan tautkan nomor Anda melalui menu *Settings* di aplikasi Finova: " . config('app.url', 'http://localhost:8000')
                );

                $event->update([
                    'status' => 'processed',
                    'processed_at' => now(),
                ]);
                return;
            }

            // 2. Check for quick confirmation reply ("ya", "ok", "setuju")
            if (in_array(strtolower($text), ['ya', 'ok', 'oke', 'setuju', 'konfirmasi', 'yes'])) {
                $pendingLog = AiParsingLog::where('user_id', $user->id)
                    ->where('status', 'pending')
                    ->where('source', 'whatsapp')
                    ->latest('created_at')
                    ->first();

                if ($pendingLog && !empty($pendingLog->parsed_data)) {
                    $pData = $pendingLog->parsed_data;
                    $txData = [
                        'type' => $pData['transaction_type'] ?? 'expense',
                        'amount' => $pData['amount'] ?? 0,
                        'date' => $pData['date'] ?? now()->format('Y-m-d'),
                        'description' => $pData['description'] ?? 'Transaksi via WhatsApp',
                        'source' => 'whatsapp',
                        'account_id' => $user->default_account_id ?? $user->accounts()->first()?->id,
                        'from_account_id' => $user->default_account_id ?? $user->accounts()->first()?->id,
                        'to_account_id' => $user->accounts()->count() > 1 ? $user->accounts()->skip(1)->first()?->id : $user->default_account_id,
                    ];

                    $aiParserService->confirm($user, $txData, $pendingLog->id);

                    $provider->sendMessage(
                        $phone,
                        "✅ *Transaksi Berhasil Dikonfirmasi!*\n\n" .
                        "📌 *Tipe*: " . ucfirst($txData['type']) . "\n" .
                        "💰 *Nominal*: Rp " . number_format($txData['amount'], 0, ',', '.') . "\n" .
                        "📝 *Deskripsi*: " . $txData['description'] . "\n\n" .
                        "Lihat catatan di: " . config('app.url') . "/transactions"
                    );

                    $event->update([
                        'status' => 'processed',
                        'processed_at' => now(),
                    ]);
                    return;
                }
            }

            // 3. Parse message using AiParserService
            $parseResultData = $aiParserService->parse($text, $user, 'whatsapp');
            $result = $parseResultData['result'];
            $logId = $parseResultData['log_id'];

            // 4. Handle based on confidence level
            if ($result->isHighConfidence()) {
                // High Confidence (>= 0.85): Auto-create confirmed transaction
                $txData = [
                    'type' => $result->transactionType ?? 'expense',
                    'amount' => $result->amount ?? 0,
                    'date' => $result->date ?? now()->format('Y-m-d'),
                    'description' => $result->description ?? $text,
                    'notes' => $result->notes,
                    'category_id' => $parseResultData['matched_category_id'],
                    'account_id' => $parseResultData['matched_account_id'],
                    'from_account_id' => $parseResultData['matched_from_account_id'],
                    'to_account_id' => $parseResultData['matched_to_account_id'],
                    'source' => 'whatsapp',
                ];

                $aiParserService->confirm($user, $txData, $logId);

                $formattedAmount = 'Rp ' . number_format($result->amount ?? 0, 0, ',', '.');
                $reply = "✅ *Transaksi Berhasil Dicatat!*\n\n" .
                    "📌 *Tipe*: " . ucfirst($result->transactionType ?? 'expense') . "\n" .
                    "💰 *Nominal*: {$formattedAmount}\n" .
                    "📝 *Deskripsi*: " . ($result->description ?? $text) . "\n" .
                    "📅 *Tanggal*: " . ($result->date ?? now()->format('Y-m-d')) . "\n\n" .
                    "Cek di: " . config('app.url') . "/dashboard";

                $provider->sendMessage($phone, $reply);
            } elseif ($result->isMediumConfidence()) {
                // Medium Confidence (0.50 - 0.84): Ask user to review / confirm
                $formattedAmount = $result->amount ? ('Rp ' . number_format($result->amount, 0, ',', '.')) : '-';
                $reply = "📝 *Konfirmasi Transaksi Finova*\n\n" .
                    "Finova membaca transaksi berikut:\n" .
                    "• Tipe: " . ucfirst($result->transactionType ?? 'expense') . "\n" .
                    "• Nominal: {$formattedAmount}\n" .
                    "• Deskripsi: " . ($result->description ?? $text) . "\n\n" .
                    "👉 Balas *'ya'* untuk konfirmasi, atau buka link berikut untuk mengedit:\n" .
                    config('app.url') . "/dashboard";

                $provider->sendMessage($phone, $reply);
            } else {
                // Low Confidence (< 0.50): Ask for clarification
                $reply = "❓ *Maaf, Finova belum memahami pesan Anda*\n\n" .
                    "Mohon tuliskan transaksi dengan lebih detail, contohnya:\n" .
                    "• _beli nasi goreng 15rb pake gopay_\n" .
                    "• _gajian 8jt masuk bca_\n" .
                    "• _transfer bca ke mandiri 500rb_";

                $provider->sendMessage($phone, $reply);
            }

            $event->update([
                'status' => 'processed',
                'processed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Error processing WhatsApp message: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            $event->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'processed_at' => now(),
            ]);
        }
    }
}

