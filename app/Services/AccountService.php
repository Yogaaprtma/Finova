<?php

namespace App\Services;

use App\Models\Account;
use Illuminate\Support\Facades\DB;

class AccountService
{
    /**
     * Recalculate and update the current balance of an account from its transaction entries.
     */
    public function recalculateBalance(Account $account): void
    {
        $balance = DB::table('transaction_entries as te')
            ->join('transactions as t', 'te.transaction_id', '=', 't.id')
            ->where('te.account_id', $account->id)
            ->whereNull('t.deleted_at')
            ->selectRaw('
                COALESCE(SUM(CASE WHEN te.entry_type = \'credit\' THEN te.amount ELSE 0 END), 0) -
                COALESCE(SUM(CASE WHEN te.entry_type = \'debit\' THEN te.amount ELSE 0 END), 0)
                as calculated_balance
            ')
            ->value('calculated_balance');

        $account->update([
            'current_balance' => (int) $balance
        ]);
    }

    /**
     * Get total cash for a user.
     */
    public function getTotalCash(string $userId): int
    {
        return Account::where('user_id', $userId)
            ->where('is_active', true)
            ->sum('current_balance');
    }
}
