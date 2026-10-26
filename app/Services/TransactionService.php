<?php

namespace App\Services;

use App\Enums\EntryType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TransactionService
{
    public function __construct(private AccountService $accountService)
    {
    }

    public function createIncome(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $transaction = Transaction::create([
                'user_id' => $data['user_id'],
                'category_id' => $data['category_id'],
                'type' => TransactionType::INCOME,
                'date' => $data['date'],
                'amount' => $data['amount'],
                'description' => $data['description'],
                'source' => $data['source'] ?? 'manual',
            ]);

            // Income: Credit to the asset/cash account (increases balance)
            $transaction->entries()->create([
                'account_id' => $data['account_id'],
                'entry_type' => EntryType::CREDIT,
                'amount' => $data['amount'],
            ]);

            // Income: Debit to nothing (or a virtual equity account)
            $transaction->entries()->create([
                'entry_type' => EntryType::DEBIT,
                'amount' => $data['amount'],
            ]);

            $this->accountService->recalculateBalance(Account::find($data['account_id']));

            return $transaction;
        });
    }

    public function createExpense(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $transaction = Transaction::create([
                'user_id' => $data['user_id'],
                'category_id' => $data['category_id'],
                'type' => TransactionType::EXPENSE,
                'date' => $data['date'],
                'amount' => $data['amount'],
                'description' => $data['description'],
                'source' => $data['source'] ?? 'manual',
            ]);

            // Expense: Debit from the asset/cash account (decreases balance)
            $transaction->entries()->create([
                'account_id' => $data['account_id'],
                'entry_type' => EntryType::DEBIT,
                'amount' => $data['amount'],
            ]);

            // Expense: Credit to nothing (or virtual expense account)
            $transaction->entries()->create([
                'entry_type' => EntryType::CREDIT,
                'amount' => $data['amount'],
            ]);

            $this->accountService->recalculateBalance(Account::find($data['account_id']));

            return $transaction;
        });
    }

    public function createTransfer(array $data): Transaction
    {
        if ($data['from_account_id'] === $data['to_account_id']) {
            throw new InvalidArgumentException("Cannot transfer to the same account.");
        }

        return DB::transaction(function () use ($data) {
            $transaction = Transaction::create([
                'user_id' => $data['user_id'],
                'type' => TransactionType::TRANSFER,
                'date' => $data['date'],
                'amount' => $data['amount'],
                'description' => $data['description'],
                'source' => $data['source'] ?? 'manual',
            ]);

            // Debit from source account
            $transaction->entries()->create([
                'account_id' => $data['from_account_id'],
                'entry_type' => EntryType::DEBIT,
                'amount' => $data['amount'],
            ]);

            // Credit to destination account
            $transaction->entries()->create([
                'account_id' => $data['to_account_id'],
                'entry_type' => EntryType::CREDIT,
                'amount' => $data['amount'],
            ]);

            $this->accountService->recalculateBalance(Account::find($data['from_account_id']));
            $this->accountService->recalculateBalance(Account::find($data['to_account_id']));

            return $transaction;
        });
    }

    public function updateTransaction(Transaction $transaction, array $data): Transaction
    {
        return DB::transaction(function () use ($transaction, $data) {
            // Collect old account IDs to recalculate later
            $oldAccountIds = $transaction->entries()->whereNotNull('account_id')->pluck('account_id')->unique()->toArray();

            // Hard delete old entries to recreate them
            $transaction->entries()->delete();

            $type = $data['type'] ?? $transaction->type;
            if (is_string($type)) {
                $type = TransactionType::tryFrom($type);
            }

            $transaction->update([
                'category_id' => array_key_exists('category_id', $data) ? $data['category_id'] : $transaction->category_id,
                'type' => $type,
                'date' => $data['date'] ?? $transaction->date,
                'amount' => $data['amount'] ?? $transaction->amount,
                'description' => $data['description'] ?? $transaction->description,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : $transaction->notes,
            ]);

            $newAccountIds = [];
            $amount = $transaction->amount;

            if ($type === TransactionType::INCOME) {
                $accountId = $data['account_id'] ?? $oldAccountIds[0] ?? null;
                $transaction->entries()->create([
                    'account_id' => $accountId,
                    'entry_type' => EntryType::CREDIT,
                    'amount' => $amount,
                ]);
                $transaction->entries()->create([
                    'entry_type' => EntryType::DEBIT,
                    'amount' => $amount,
                ]);
                if ($accountId) $newAccountIds[] = $accountId;
            } elseif ($type === TransactionType::EXPENSE) {
                $accountId = $data['account_id'] ?? $oldAccountIds[0] ?? null;
                $transaction->entries()->create([
                    'account_id' => $accountId,
                    'entry_type' => EntryType::DEBIT,
                    'amount' => $amount,
                ]);
                $transaction->entries()->create([
                    'entry_type' => EntryType::CREDIT,
                    'amount' => $amount,
                ]);
                if ($accountId) $newAccountIds[] = $accountId;
            } elseif ($type === TransactionType::TRANSFER) {
                $fromAccountId = $data['from_account_id'] ?? $oldAccountIds[0] ?? null;
                $toAccountId = $data['to_account_id'] ?? $oldAccountIds[1] ?? null;
                
                if ($fromAccountId === $toAccountId) {
                    throw new InvalidArgumentException("Cannot transfer to the same account.");
                }
                
                $transaction->entries()->create([
                    'account_id' => $fromAccountId,
                    'entry_type' => EntryType::DEBIT,
                    'amount' => $amount,
                ]);
                $transaction->entries()->create([
                    'account_id' => $toAccountId,
                    'entry_type' => EntryType::CREDIT,
                    'amount' => $amount,
                ]);
                if ($fromAccountId) $newAccountIds[] = $fromAccountId;
                if ($toAccountId) $newAccountIds[] = $toAccountId;
            }

            // Recalculate all affected accounts (old and new)
            $allAccountIds = array_unique(array_merge($oldAccountIds, $newAccountIds));
            foreach ($allAccountIds as $accountId) {
                if ($account = Account::find($accountId)) {
                    $this->accountService->recalculateBalance($account);
                }
            }

            return $transaction;
        });
    }

    public function deleteTransaction(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $accountIds = $transaction->entries()->whereNotNull('account_id')->pluck('account_id')->unique()->toArray();

            $transaction->delete();

            foreach ($accountIds as $accountId) {
                if ($account = Account::find($accountId)) {
                    $this->accountService->recalculateBalance($account);
                }
            }
        });
    }
}
