<?php

namespace App\Services;

use App\Enums\EntryType;
use App\Enums\LiabilityType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Liability;
use App\Models\LiabilityTransaction;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LiabilityService
{
    public function __construct(
        private AccountService $accountService,
        private DashboardService $dashboardService,
    ) {}

    /**
     * Get complete liabilities overview with metrics and allocation breakdown.
     *
     * @return array<string, mixed>
     */
    public function getLiabilitiesOverview(string|int $userId): array
    {
        $liabilities = Liability::where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->withCount('transactions')
            ->get();

        $totalDebt = (int) $liabilities->sum('current_balance');
        $totalOriginal = (int) $liabilities->sum('initial_balance');
        $totalMonthlyObligations = (int) $liabilities->sum(function (Liability $l) {
            return $l->minimum_payment ?: 0;
        });

        // Credit Cards
        $creditCards = $liabilities->filter(fn (Liability $l) => $l->type === LiabilityType::CREDIT_CARD);
        $totalCreditLimit = (int) $creditCards->sum('credit_limit');
        $totalCreditUsed = (int) $creditCards->sum('current_balance');
        $creditUtilization = $totalCreditLimit > 0
            ? round(($totalCreditUsed / $totalCreditLimit) * 100, 1)
            : 0.0;

        // Breakdown by type
        $breakdownGroups = [];
        foreach ($liabilities as $liability) {
            $typeKey = $liability->type->value;
            if (! isset($breakdownGroups[$typeKey])) {
                $breakdownGroups[$typeKey] = [
                    'type' => $typeKey,
                    'label' => $liability->type->label(),
                    'color' => $liability->type->color(),
                    'value' => 0,
                    'count' => 0,
                ];
            }
            $breakdownGroups[$typeKey]['value'] += (int) $liability->current_balance;
            $breakdownGroups[$typeKey]['count']++;
        }

        $breakdownItems = array_values(array_filter($breakdownGroups, fn ($item) => $item['value'] > 0));
        usort($breakdownItems, fn ($a, $b) => $b['value'] <=> $a['value']);

        foreach ($breakdownItems as &$item) {
            $item['percentage'] = $totalDebt > 0 ? round(($item['value'] / $totalDebt) * 100, 1) : 0.0;
        }
        unset($item);

        return [
            'metrics' => [
                'total_debt' => $totalDebt,
                'total_original' => $totalOriginal,
                'total_monthly_obligations' => $totalMonthlyObligations,
                'total_credit_limit' => $totalCreditLimit,
                'total_credit_used' => $totalCreditUsed,
                'credit_utilization' => $creditUtilization,
                'liabilities_count' => $liabilities->count(),
                'credit_cards_count' => $creditCards->count(),
            ],
            'breakdown' => [
                'items' => $breakdownItems,
                'labels' => array_column($breakdownItems, 'label'),
                'values' => array_column($breakdownItems, 'value'),
                'colors' => array_column($breakdownItems, 'color'),
                'total' => $totalDebt,
            ],
            'liabilities' => $liabilities,
        ];
    }

    /**
     * Create a new liability.
     *
     * @param  array<string, mixed>  $data
     */
    public function createLiability(array $data): Liability
    {
        $liability = DB::transaction(function () use ($data) {
            $initialBalance = isset($data['initial_balance']) ? (int) $data['initial_balance'] : 0;
            $currentBalance = isset($data['current_balance']) ? (int) $data['current_balance'] : $initialBalance;

            return Liability::create([
                'user_id' => $data['user_id'],
                'name' => $data['name'],
                'type' => $data['type'],
                'currency' => $data['currency'] ?? 'IDR',
                'initial_balance' => $initialBalance,
                'current_balance' => $currentBalance,
                'credit_limit' => isset($data['credit_limit']) ? (int) $data['credit_limit'] : null,
                'interest_rate' => isset($data['interest_rate']) ? (float) $data['interest_rate'] : null,
                'minimum_payment' => isset($data['minimum_payment']) ? (int) $data['minimum_payment'] : null,
                'due_date_day' => isset($data['due_date_day']) ? (int) $data['due_date_day'] : null,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'remaining_terms' => isset($data['remaining_terms']) ? (int) $data['remaining_terms'] : null,
                'is_active' => $data['is_active'] ?? true,
                'sort_order' => $data['sort_order'] ?? 0,
                'notes' => $data['notes'] ?? null,
            ]);
        });

        $this->dashboardService->clearDashboardCache((string) $data['user_id']);

        return $liability;
    }

    /**
     * Update an existing liability.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateLiability(Liability $liability, array $data): Liability
    {
        DB::transaction(function () use ($liability, $data) {
            $updateData = [
                'name' => $data['name'] ?? $liability->name,
                'currency' => $data['currency'] ?? $liability->currency,
                'credit_limit' => array_key_exists('credit_limit', $data) ? ($data['credit_limit'] !== null ? (int) $data['credit_limit'] : null) : $liability->credit_limit,
                'interest_rate' => array_key_exists('interest_rate', $data) ? ($data['interest_rate'] !== null ? (float) $data['interest_rate'] : null) : $liability->interest_rate,
                'minimum_payment' => array_key_exists('minimum_payment', $data) ? ($data['minimum_payment'] !== null ? (int) $data['minimum_payment'] : null) : $liability->minimum_payment,
                'due_date_day' => array_key_exists('due_date_day', $data) ? ($data['due_date_day'] !== null ? (int) $data['due_date_day'] : null) : $liability->due_date_day,
                'start_date' => array_key_exists('start_date', $data) ? $data['start_date'] : $liability->start_date,
                'end_date' => array_key_exists('end_date', $data) ? $data['end_date'] : $liability->end_date,
                'remaining_terms' => array_key_exists('remaining_terms', $data) ? ($data['remaining_terms'] !== null ? (int) $data['remaining_terms'] : null) : $liability->remaining_terms,
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $liability->is_active,
                'sort_order' => array_key_exists('sort_order', $data) ? (int) $data['sort_order'] : $liability->sort_order,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : $liability->notes,
            ];

            if (array_key_exists('current_balance', $data)) {
                $updateData['current_balance'] = (int) $data['current_balance'];
            }

            $liability->update($updateData);
        });

        $this->dashboardService->clearDashboardCache((string) $liability->user_id);

        return $liability->fresh();
    }

    /**
     * Delete a liability (soft delete).
     */
    public function deleteLiability(Liability $liability): void
    {
        $userId = (string) $liability->user_id;
        $liability->delete();
        $this->dashboardService->clearDashboardCache($userId);
    }

    /**
     * Record a liability payment.
     * Cash account balance decreases (debit), liability balance decreases.
     *
     * @param  array<string, mixed>  $data
     */
    public function recordPayment(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $liability = Liability::where('id', $data['liability_id'])
                ->where('user_id', $data['user_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $account = Account::where('id', $data['source_account_id'])
                ->where('user_id', $data['user_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $amount = (int) $data['amount'];
            if ($amount <= 0) {
                throw new InvalidArgumentException('Payment amount must be greater than zero.');
            }

            $date = $data['date'] ?? now()->toDateString();
            $description = $data['description'] ?? "Payment for {$liability->name}";

            // Create Transaction
            $transaction = Transaction::create([
                'user_id' => $data['user_id'],
                'type' => TransactionType::LIABILITY_PAYMENT,
                'date' => $date,
                'amount' => $amount,
                'currency' => $liability->currency,
                'description' => $description,
                'notes' => $data['notes'] ?? null,
                'source' => 'manual',
            ]);

            // Double Entry: Debit source account (decreases balance)
            $transaction->entries()->create([
                'account_id' => $account->id,
                'entry_type' => EntryType::DEBIT,
                'amount' => $amount,
            ]);

            // Virtual credit entry
            $transaction->entries()->create([
                'account_id' => null,
                'entry_type' => EntryType::CREDIT,
                'amount' => $amount,
            ]);

            // Create Liability Transaction link
            LiabilityTransaction::create([
                'liability_id' => $liability->id,
                'transaction_id' => $transaction->id,
                'type' => 'payment',
                'amount' => $amount,
                'created_at' => now(),
            ]);

            // Decrement liability current balance
            $newBalance = max(0, (int) $liability->current_balance - $amount);
            $updateData = ['current_balance' => $newBalance];

            if ($liability->remaining_terms !== null && $liability->remaining_terms > 0) {
                $updateData['remaining_terms'] = $liability->remaining_terms - 1;
            }

            $liability->update($updateData);

            // Recalculate account balance
            $this->accountService->recalculateBalance($account);
            $this->dashboardService->clearDashboardCache((string) $data['user_id']);

            return $transaction;
        });
    }

    /**
     * Record a liability increase (e.g. loan disbursement or new card charge).
     *
     * @param  array<string, mixed>  $data
     */
    public function recordIncrease(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $liability = Liability::where('id', $data['liability_id'])
                ->where('user_id', $data['user_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $amount = (int) $data['amount'];
            if ($amount <= 0) {
                throw new InvalidArgumentException('Amount must be greater than zero.');
            }

            $date = $data['date'] ?? now()->toDateString();
            $description = $data['description'] ?? "Charge / Increase on {$liability->name}";

            $destinationAccount = null;
            if (! empty($data['destination_account_id'])) {
                $destinationAccount = Account::where('id', $data['destination_account_id'])
                    ->where('user_id', $data['user_id'])
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            // Create Transaction
            $transaction = Transaction::create([
                'user_id' => $data['user_id'],
                'type' => TransactionType::LIABILITY_INCREASE,
                'date' => $date,
                'amount' => $amount,
                'currency' => $liability->currency,
                'description' => $description,
                'notes' => $data['notes'] ?? null,
                'source' => 'manual',
            ]);

            if ($destinationAccount) {
                // If loan disbursed into bank account: Credit destination account (increases balance)
                $transaction->entries()->create([
                    'account_id' => $destinationAccount->id,
                    'entry_type' => EntryType::CREDIT,
                    'amount' => $amount,
                ]);
                $transaction->entries()->create([
                    'account_id' => null,
                    'entry_type' => EntryType::DEBIT,
                    'amount' => $amount,
                ]);
            } else {
                // Credit card charge: debit/credit virtual
                $transaction->entries()->create([
                    'account_id' => null,
                    'entry_type' => EntryType::DEBIT,
                    'amount' => $amount,
                ]);
                $transaction->entries()->create([
                    'account_id' => null,
                    'entry_type' => EntryType::CREDIT,
                    'amount' => $amount,
                ]);
            }

            // Create Liability Transaction link
            LiabilityTransaction::create([
                'liability_id' => $liability->id,
                'transaction_id' => $transaction->id,
                'type' => 'increase',
                'amount' => $amount,
                'created_at' => now(),
            ]);

            // Increment liability current balance
            $liability->increment('current_balance', $amount);

            if ($destinationAccount) {
                $this->accountService->recalculateBalance($destinationAccount);
            }

            $this->dashboardService->clearDashboardCache((string) $data['user_id']);

            return $transaction;
        });
    }
}

