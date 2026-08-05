<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\CategoryType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use App\Services\AccountService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $account;

    private Category $incomeCategory;

    private Category $expenseCategory;

    private TransactionService $transactionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->transactionService = new TransactionService(new AccountService);

        $this->account = Account::create([
            'user_id' => $this->user->id,
            'name' => 'BCA Checking',
            'type' => AccountType::BANK,
            'currency' => 'IDR',
            'initial_balance' => 0,
            'current_balance' => 0,
        ]);

        $this->incomeCategory = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Salary',
            'type' => CategoryType::INCOME,
        ]);

        $this->expenseCategory = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Food & Dining',
            'type' => CategoryType::EXPENSE,
        ]);
    }

    public function test_user_can_view_transactions_index_and_apply_filters(): void
    {
        $this->transactionService->createIncome([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'amount' => 5000000,
            'date' => now()->toDateString(),
            'description' => 'Freelance Payment',
            'category_id' => $this->incomeCategory->id,
        ]);

        $response = $this->actingAs($this->user)->get('/transactions');
        $response->assertOk();

        // Test with search and filters
        $filtered = $this->actingAs($this->user)->get('/transactions?'.http_build_query([
            'search' => 'Freelance',
            'type' => 'income',
            'category_id' => $this->incomeCategory->id,
            'account_id' => $this->account->id,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
        ]));
        $filtered->assertOk();
    }

    public function test_user_can_create_income_transaction(): void
    {
        $response = $this->actingAs($this->user)->post('/transactions', [
            'type' => TransactionType::INCOME->value,
            'amount' => 2000000,
            'date' => now()->toDateString(),
            'description' => 'Project milestone',
            'account_id' => $this->account->id,
            'category_id' => $this->incomeCategory->id,
        ]);

        $response->assertRedirect('/transactions');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'amount' => 2000000,
            'type' => TransactionType::INCOME->value,
            'description' => 'Project milestone',
        ]);

        $this->assertEquals(2000000, $this->account->refresh()->current_balance);
    }

    public function test_user_can_create_expense_transaction(): void
    {
        // Initial funding
        $this->transactionService->createAdjustment([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'amount' => 1000000,
            'description' => 'Initial funding',
        ]);

        $response = $this->actingAs($this->user)->post('/transactions', [
            'type' => TransactionType::EXPENSE->value,
            'amount' => 150000,
            'date' => now()->toDateString(),
            'description' => 'Lunch meeting',
            'account_id' => $this->account->id,
            'category_id' => $this->expenseCategory->id,
        ]);

        $response->assertRedirect('/transactions');

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'amount' => 150000,
            'type' => TransactionType::EXPENSE->value,
            'description' => 'Lunch meeting',
        ]);

        // Balance: 1,000,000 - 150,000 = 850,000
        $this->assertEquals(850000, $this->account->refresh()->current_balance);
    }

    public function test_user_can_create_transfer_transaction(): void
    {
        $this->transactionService->createAdjustment([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'amount' => 1000000,
            'description' => 'Initial funding',
        ]);

        $destAccount = Account::create([
            'user_id' => $this->user->id,
            'name' => 'GoPay',
            'type' => AccountType::CASH,
            'currency' => 'IDR',
            'initial_balance' => 0,
            'current_balance' => 0,
        ]);

        $response = $this->actingAs($this->user)->post('/transactions', [
            'type' => TransactionType::TRANSFER->value,
            'amount' => 250000,
            'date' => now()->toDateString(),
            'description' => 'Topup GoPay',
            'source_account_id' => $this->account->id,
            'destination_account_id' => $destAccount->id,
        ]);

        $response->assertRedirect('/transactions');

        $this->assertEquals(750000, $this->account->refresh()->current_balance);
        $this->assertEquals(250000, $destAccount->refresh()->current_balance);
    }

    public function test_user_can_view_transaction_detail(): void
    {
        $transaction = $this->transactionService->createIncome([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'amount' => 500000,
            'date' => now()->toDateString(),
            'description' => 'Consulting fee',
            'category_id' => $this->incomeCategory->id,
        ]);

        $response = $this->actingAs($this->user)->get("/transactions/{$transaction->id}");
        $response->assertOk();
    }

    public function test_user_can_update_transaction(): void
    {
        $transaction = $this->transactionService->createIncome([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'amount' => 500000,
            'date' => now()->toDateString(),
            'description' => 'Initial amount',
            'category_id' => $this->incomeCategory->id,
        ]);

        $response = $this->actingAs($this->user)->put("/transactions/{$transaction->id}", [
            'type' => TransactionType::INCOME->value,
            'amount' => 800000,
            'date' => now()->toDateString(),
            'description' => 'Updated amount',
            'account_id' => $this->account->id,
            'category_id' => $this->incomeCategory->id,
        ]);

        $response->assertRedirect('/transactions');
        $this->assertEquals(800000, $this->account->refresh()->current_balance);
        $this->assertEquals(800000, $transaction->refresh()->amount);
    }

    public function test_user_can_delete_transaction(): void
    {
        $transaction = $this->transactionService->createIncome([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'amount' => 500000,
            'date' => now()->toDateString(),
            'description' => 'To be deleted',
            'category_id' => $this->incomeCategory->id,
        ]);

        $this->assertEquals(500000, $this->account->refresh()->current_balance);

        $response = $this->actingAs($this->user)->delete("/transactions/{$transaction->id}");

        $response->assertRedirect('/transactions');
        $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
        $this->assertEquals(0, $this->account->refresh()->current_balance);
    }

    public function test_user_cannot_view_or_modify_other_users_transaction(): void
    {
        $otherUser = User::factory()->create();
        $otherAccount = Account::create([
            'user_id' => $otherUser->id,
            'name' => 'Other Bank',
            'type' => AccountType::BANK,
            'currency' => 'IDR',
            'initial_balance' => 0,
            'current_balance' => 0,
        ]);

        $transaction = $this->transactionService->createIncome([
            'user_id' => $otherUser->id,
            'account_id' => $otherAccount->id,
            'amount' => 100000,
            'date' => now()->toDateString(),
            'description' => 'Other secret income',
        ]);

        // Attempt show
        $this->actingAs($this->user)->get("/transactions/{$transaction->id}")->assertForbidden();

        // Attempt update
        $this->actingAs($this->user)->put("/transactions/{$transaction->id}", [
            'type' => TransactionType::INCOME->value,
            'amount' => 999999,
            'date' => now()->toDateString(),
            'description' => 'Hacked',
            'account_id' => $this->account->id,
        ])->assertForbidden();

        // Attempt delete
        $this->actingAs($this->user)->delete("/transactions/{$transaction->id}")->assertForbidden();
    }
}
