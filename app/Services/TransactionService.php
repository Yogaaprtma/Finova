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
}
