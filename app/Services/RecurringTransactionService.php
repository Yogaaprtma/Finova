<?php

namespace App\Services;

use App\Enums\TransactionSource;
use App\Models\RecurringTransaction;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecurringTransactionService
{
    public function __construct(
        private TransactionService $transactionService
    ) {}

    /**
     * Get all recurring transactions for a user.
     *
     * @return Collection<int, RecurringTransaction>
     */
    public function getForUser(User|int|string $user): Collection
    {
        $userId = $user instanceof User ? $user->id : $user;

        return RecurringTransaction::with(['category', 'account'])
            ->where('user_id', $userId)
            ->orderBy('is_active', 'desc')
            ->orderBy('name')
            ->get();
    }

    /**
     * Create a new recurring transaction template.
     */
    public function create(User|int|string $user, array $data): RecurringTransaction
    {
        $userId = $user instanceof User ? $user->id : $user;

        return RecurringTransaction::create([
            'user_id' => $userId,
            'category_id' => $data['category_id'] ?? null,
            'account_id' => $data['account_id'],
            'name' => $data['name'],
            'type' => $data['type'],
            'amount' => (int) $data['amount'],
            'currency' => $data['currency'] ?? 'IDR',
            'description' => $data['description'] ?? $data['name'],
            'frequency' => $data['frequency'],
            'day_of_month' => $data['day_of_month'] ?? null,
            'day_of_week' => $data['day_of_week'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'auto_confirm' => (bool) ($data['auto_confirm'] ?? false),
        ]);
    }

    /**
     * Update an existing recurring transaction template.
     */
    public function update(RecurringTransaction $recurring, array $data): RecurringTransaction
    {
        $recurring->update([
            'category_id' => array_key_exists('category_id', $data) ? $data['category_id'] : $recurring->category_id,
            'account_id' => $data['account_id'] ?? $recurring->account_id,
            'name' => $data['name'] ?? $recurring->name,
            'type' => $data['type'] ?? $recurring->type,
            'amount' => isset($data['amount']) ? (int) $data['amount'] : $recurring->amount,
            'currency' => $data['currency'] ?? $recurring->currency,
            'description' => $data['description'] ?? $recurring->description,
            'frequency' => $data['frequency'] ?? $recurring->frequency,
            'day_of_month' => array_key_exists('day_of_month', $data) ? $data['day_of_month'] : $recurring->day_of_month,
            'day_of_week' => array_key_exists('day_of_week', $data) ? $data['day_of_week'] : $recurring->day_of_week,
            'start_date' => $data['start_date'] ?? $recurring->start_date,
            'end_date' => array_key_exists('end_date', $data) ? $data['end_date'] : $recurring->end_date,
            'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : $recurring->is_active,
            'auto_confirm' => isset($data['auto_confirm']) ? (bool) $data['auto_confirm'] : $recurring->auto_confirm,
        ]);

        return $recurring->fresh(['category', 'account']);
    }

    /**
     * Delete a recurring transaction template.
     */
    public function delete(RecurringTransaction $recurring): bool
    {
        return (bool) $recurring->delete();
    }

    /**
     * Toggle active state of a recurring template.
     */
    public function toggleActive(RecurringTransaction $recurring): RecurringTransaction
    {
        $recurring->update([
            'is_active' => ! $recurring->is_active,
        ]);

        return $recurring->fresh();
    }

    /**
     * Generate all due recurring transactions across all active users.
     *
     * @return int Number of transactions generated
     */
    public function generateDueTransactions(?CarbonInterface $targetDate = null): int
    {
        $target = $targetDate ? Carbon::parse($targetDate) : Carbon::today();
        $count = 0;

        $activeTemplates = RecurringTransaction::where('is_active', true)->get();

        foreach ($activeTemplates as $template) {
            if ($template->shouldGenerateOn($target)) {
                try {
                    $this->generateForTemplate($template, $target);
                    $count++;
                } catch (\Throwable $e) {
                    Log::error("Failed to generate transaction for recurring template {$template->id}: ".$e->getMessage(), [
                        'exception' => $e,
                    ]);
                }
            }
        }

        return $count;
    }

    /**
     * Generate transaction for a specific template.
     */
    public function generateForTemplate(RecurringTransaction $recurring, ?CarbonInterface $date = null): Transaction
    {
        $genDate = $date ? Carbon::parse($date)->format('Y-m-d') : Carbon::today()->format('Y-m-d');

        return DB::transaction(function () use ($recurring, $genDate) {
            $txData = [
                'user_id' => $recurring->user_id,
                'account_id' => $recurring->account_id,
                'category_id' => $recurring->category_id,
                'amount' => $recurring->amount,
                'currency' => $recurring->currency,
                'date' => $genDate,
                'description' => $recurring->description ?: $recurring->name,
                'notes' => 'Auto-generated dari template berulang: '.$recurring->name,
                'source' => TransactionSource::RECURRING->value,
                'is_confirmed' => (bool) $recurring->auto_confirm,
            ];

            if ($recurring->type === 'income') {
                $transaction = $this->transactionService->createIncome($txData);
            } else {
                $transaction = $this->transactionService->createExpense($txData);
            }

            $recurring->update([
                'last_generated_date' => $genDate,
            ]);

            return $transaction;
        });
    }
}
