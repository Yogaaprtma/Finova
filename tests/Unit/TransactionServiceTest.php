<?php

namespace Tests\Unit;

use App\Enums\AccountType;
use App\Enums\CategoryType;
use App\Enums\EntryType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use App\Services\AccountService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    private TransactionService $transactionService;

    private User $user;

    private Account $account;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transactionService = new TransactionService(new AccountService);
        $this->user = User::factory()->create();

        $this->account = Account::create([
            'user_id' => $this->user->id,
            'name' => 'Main Bank',
            'type' => AccountType::BANK,
            'currency' => 'IDR',
            'initial_balance' => 0,
            'current_balance' => 0,
        ]);

        $this->category = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Salary',
            'type' => CategoryType::INCOME,
        ]);
    }

    public function test_create_income_balances_debits_and_credits_and_increases_account_balance(): void
    {
        $transaction = $this->transactionService->createIncome([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'account_id' => $this->account->id,
            'amount' => 5000000,
            'date' => now()->toDateString(),
            'description' => 'Monthly Salary',
        ]);

        $this->assertEquals(TransactionType::INCOME, $transaction->type);
        $this->assertEquals(5000000, $transaction->amount);

        // Double-entry balance check: Total Debits == Total Credits
        $totalDebits = $transaction->entries()->where('entry_type', EntryType::DEBIT)->sum('amount');
        $totalCredits = $transaction->entries()->where('entry_type', EntryType::CREDIT)->sum('amount');
        $this->assertEquals(5000000, $totalDebits);
        $this->assertEquals(5000000, $totalCredits);

        // Account balance increases
        $this->account->refresh();
        $this->assertEquals(5000000, $this->account->current_balance);
    }

    public function test_create_expense_balances_debits_and_credits_and_decreases_account_balance(): void
    {
        // First fund the account with adjustment
        $this->transactionService->createAdjustment([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'amount' => 1000000,
            'description' => 'Funding',
        ]);

        $expenseCategory = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Groceries',
            'type' => CategoryType::EXPENSE,
        ]);

        $transaction = $this->transactionService->createExpense([
            'user_id' => $this->user->id,
            'category_id' => $expenseCategory->id,
            'account_id' => $this->account->id,
            'amount' => 250000,
            'date' => now()->toDateString(),
            'description' => 'Supermarket shopping',
        ]);

        $this->assertEquals(TransactionType::EXPENSE, $transaction->type);
        $this->assertEquals(250000, $transaction->amount);

        // Double-entry check
        $totalDebits = $transaction->entries()->where('entry_type', EntryType::DEBIT)->sum('amount');
        $totalCredits = $transaction->entries()->where('entry_type', EntryType::CREDIT)->sum('amount');
        $this->assertEquals(250000, $totalDebits);
        $this->assertEquals(250000, $totalCredits);

        // Account balance decreases from 1,000,000 to 750,000
        $this->account->refresh();
        $this->assertEquals(750000, $this->account->current_balance);
    }

    public function test_create_transfer_transfers_funds_between_accounts(): void
    {
        // First fund the account with adjustment
        $this->transactionService->createAdjustment([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'amount' => 1000000,
            'description' => 'Funding',
        ]);

        $targetAccount = Account::create([
            'user_id' => $this->user->id,
            'name' => 'Cash Wallet',
            'type' => AccountType::CASH,
            'currency' => 'IDR',
            'initial_balance' => 0,
            'current_balance' => 0,
        ]);

        $transaction = $this->transactionService->createTransfer([
            'user_id' => $this->user->id,
            'from_account_id' => $this->account->id,
            'to_account_id' => $targetAccount->id,
            'amount' => 300000,
            'date' => now()->toDateString(),
            'description' => 'ATM Cash Withdrawal',
        ]);

        $this->assertEquals(TransactionType::TRANSFER, $transaction->type);

        // Double-entry check
        $totalDebits = $transaction->entries()->where('entry_type', EntryType::DEBIT)->sum('amount');
        $totalCredits = $transaction->entries()->where('entry_type', EntryType::CREDIT)->sum('amount');
        $this->assertEquals(300000, $totalDebits);
        $this->assertEquals(300000, $totalCredits);

        // Verify both balances
        $this->account->refresh();
        $targetAccount->refresh();
        $this->assertEquals(700000, $this->account->current_balance);
        $this->assertEquals(300000, $targetAccount->current_balance);
    }

    public function test_cannot_transfer_to_the_same_account(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot transfer to the same account.');

        $this->transactionService->createTransfer([
            'user_id' => $this->user->id,
            'from_account_id' => $this->account->id,
            'to_account_id' => $this->account->id,
            'amount' => 100000,
            'date' => now()->toDateString(),
            'description' => 'Invalid transfer',
        ]);
    }

    public function test_create_adjustment_positive_and_negative(): void
    {
        $adj = $this->transactionService->createAdjustment([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'amount' => 150000,
            'description' => 'Initial balance adjustment',
        ]);

        $this->assertEquals(TransactionType::ADJUSTMENT, $adj->type);
        $this->account->refresh();
        $this->assertEquals(150000, $this->account->current_balance);

        // Double-entry check
        $totalDebits = $adj->entries()->where('entry_type', EntryType::DEBIT)->sum('amount');
        $totalCredits = $adj->entries()->where('entry_type', EntryType::CREDIT)->sum('amount');
        $this->assertEquals(150000, $totalDebits);
        $this->assertEquals(150000, $totalCredits);
    }

    public function test_delete_transaction_reverses_balance_effect(): void
    {
        $transaction = $this->transactionService->createIncome([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'account_id' => $this->account->id,
            'amount' => 1000000,
            'date' => now()->toDateString(),
            'description' => 'Bonus',
        ]);

        $this->account->refresh();
        $this->assertEquals(1000000, $this->account->current_balance);

        $this->transactionService->deleteTransaction($transaction);

        $this->account->refresh();
        $this->assertEquals(0, $this->account->current_balance);
    }

    public function test_update_transaction_recalculates_balance(): void
    {
        $transaction = $this->transactionService->createIncome([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'account_id' => $this->account->id,
            'amount' => 500000,
            'date' => now()->toDateString(),
            'description' => 'Original Income',
        ]);

        $this->account->refresh();
        $this->assertEquals(500000, $this->account->current_balance);

        $this->transactionService->updateTransaction($transaction, [
            'amount' => 750000,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'date' => now()->toDateString(),
            'description' => 'Updated Income',
            'type' => TransactionType::INCOME,
        ]);

        $this->account->refresh();
        $this->assertEquals(750000, $this->account->current_balance);
        $this->assertEquals(750000, $transaction->refresh()->amount);
    }
}
