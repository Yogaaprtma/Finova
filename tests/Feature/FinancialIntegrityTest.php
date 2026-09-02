<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\AssetType;
use App\Enums\EntryType;
use App\Enums\LiabilityType;
use App\Models\Account;
use App\Models\AssetAccount;
use App\Models\Category;
use App\Models\Liability;
use App\Models\User;
use App\Services\AssetService;
use App\Services\DashboardService;
use App\Services\LiabilityService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FinancialIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $checkingAccount;

    private Account $savingsAccount;

    private Category $salaryCategory;

    private Category $foodCategory;

    private TransactionService $transactionService;

    private DashboardService $dashboardService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->checkingAccount = Account::create([
            'user_id' => $this->user->id,
            'name' => 'BCA Checking',
            'type' => AccountType::BANK,
            'currency' => 'IDR',
            'initial_balance' => 10000000,
            'current_balance' => 10000000,
        ]);

        $this->savingsAccount = Account::create([
            'user_id' => $this->user->id,
            'name' => 'Mandiri Savings',
            'type' => AccountType::BANK,
            'currency' => 'IDR',
            'initial_balance' => 5000000,
            'current_balance' => 5000000,
        ]);

        $this->salaryCategory = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Gaji',
            'type' => 'income',
        ]);

        $this->foodCategory = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Makanan',
            'type' => 'expense',
        ]);

        $this->transactionService = app(TransactionService::class);
        $this->dashboardService = app(DashboardService::class);

        // Record initial adjustment entries so all balances match double-entry ledger
        $this->transactionService->createAdjustment([
            'user_id' => $this->user->id,
            'account_id' => $this->checkingAccount->id,
            'amount' => 10000000,
            'description' => 'Initial Balance Checking',
            'date' => '2026-11-01',
            'source' => 'system',
        ]);

        $this->transactionService->createAdjustment([
            'user_id' => $this->user->id,
            'account_id' => $this->savingsAccount->id,
            'amount' => 5000000,
            'description' => 'Initial Balance Savings',
            'date' => '2026-11-01',
            'source' => 'system',
        ]);
    }

    public function test_double_entry_balance_for_all_transaction_types(): void
    {
        // 1. Income
        $incomeTx = $this->transactionService->createIncome([
            'user_id' => $this->user->id,
            'account_id' => $this->checkingAccount->id,
            'category_id' => $this->salaryCategory->id,
            'amount' => 15000000,
            'date' => '2026-11-01',
            'description' => 'Gaji Bulanan',
        ]);

        $incomeCredits = $incomeTx->entries()->where('entry_type', EntryType::CREDIT)->sum('amount');
        $incomeDebits = $incomeTx->entries()->where('entry_type', EntryType::DEBIT)->sum('amount');
        $this->assertSame($incomeCredits, $incomeDebits, 'Income double-entry balanced');

        // 2. Expense
        $expenseTx = $this->transactionService->createExpense([
            'user_id' => $this->user->id,
            'account_id' => $this->checkingAccount->id,
            'category_id' => $this->foodCategory->id,
            'amount' => 500000,
            'date' => '2026-11-02',
            'description' => 'Groceries',
        ]);

        $expenseCredits = $expenseTx->entries()->where('entry_type', EntryType::CREDIT)->sum('amount');
        $expenseDebits = $expenseTx->entries()->where('entry_type', EntryType::DEBIT)->sum('amount');
        $this->assertSame($expenseCredits, $expenseDebits, 'Expense double-entry balanced');

        // 3. Transfer
        $transferTx = $this->transactionService->createTransfer([
            'user_id' => $this->user->id,
            'from_account_id' => $this->checkingAccount->id,
            'to_account_id' => $this->savingsAccount->id,
            'amount' => 2000000,
            'date' => '2026-11-03',
            'description' => 'Transfer ke Tabungan',
        ]);

        $transferCredits = $transferTx->entries()->where('entry_type', EntryType::CREDIT)->sum('amount');
        $transferDebits = $transferTx->entries()->where('entry_type', EntryType::DEBIT)->sum('amount');
        $this->assertSame($transferCredits, $transferDebits, 'Transfer double-entry balanced');
    }

    public function test_transfer_preserves_total_cash_and_net_worth(): void
    {
        $initialNetWorth = $this->dashboardService->getNetWorthData((string) $this->user->id);
        $this->assertSame(15000000, $initialNetWorth['net_worth']);
        $this->assertSame(15000000, $initialNetWorth['total_cash']);

        // Transfer 3,000,000 from BCA to Mandiri
        $this->transactionService->createTransfer([
            'user_id' => $this->user->id,
            'from_account_id' => $this->checkingAccount->id,
            'to_account_id' => $this->savingsAccount->id,
            'amount' => 3000000,
            'date' => '2026-11-05',
            'description' => 'Transfer Internal',
        ]);

        $this->checkingAccount->refresh();
        $this->savingsAccount->refresh();

        $this->assertSame(7000000, $this->checkingAccount->current_balance);
        $this->assertSame(8000000, $this->savingsAccount->current_balance);

        $postNetWorth = $this->dashboardService->getNetWorthData((string) $this->user->id);
        $this->assertSame(15000000, $postNetWorth['net_worth']);
        $this->assertSame(15000000, $postNetWorth['total_cash']);
    }

    public function test_asset_purchase_preserves_net_worth(): void
    {
        $initialNetWorth = $this->dashboardService->getNetWorthData((string) $this->user->id);
        $this->assertSame(15000000, $initialNetWorth['net_worth']);

        $asset = AssetAccount::create([
            'user_id' => $this->user->id,
            'name' => 'BBCA Stock',
            'type' => AssetType::STOCK,
            'ticker' => 'BBCA',
            'currency' => 'IDR',
            'quantity' => 0,
            'avg_purchase_price' => 0,
            'current_price' => 10000,
            'is_active' => true,
        ]);

        $assetService = app(AssetService::class);

        // Buy 4 units (lot multiplier for STOCK is 100: 4 * 10,000 * 100 = 4,000,000) from BCA checking
        $assetService->recordPurchase([
            'user_id' => $this->user->id,
            'asset_account_id' => $asset->id,
            'source_account_id' => $this->checkingAccount->id,
            'quantity' => 4,
            'price_per_unit' => 10000,
            'date' => '2026-11-06',
            'description' => 'Buy BBCA 4 lots',
        ]);

        $this->checkingAccount->refresh();
        $asset->refresh();

        $this->assertSame(6000000, $this->checkingAccount->current_balance);
        $this->assertSame(4000000, (int) $asset->current_value);

        $postNetWorth = $this->dashboardService->getNetWorthData((string) $this->user->id);
        // Total cash = 6m + 5m = 11m, Investments = 4m. Net worth = 15m!
        $this->assertSame(11000000, $postNetWorth['total_cash']);
        $this->assertSame(4000000, $postNetWorth['total_investments']);
        $this->assertSame(15000000, $postNetWorth['net_worth']);
    }

    public function test_liability_payment_preserves_net_worth(): void
    {
        $liability = Liability::create([
            'user_id' => $this->user->id,
            'name' => 'KTA Bank',
            'type' => LiabilityType::PERSONAL_LOAN,
            'initial_balance' => 5000000,
            'current_balance' => 5000000,
            'currency' => 'IDR',
            'start_date' => '2026-01-01',
            'is_active' => true,
        ]);

        // Initial net worth: Cash 15m - Liability 5m = 10m
        $initialNetWorth = $this->dashboardService->getNetWorthData((string) $this->user->id);
        $this->assertSame(10000000, $initialNetWorth['net_worth']);

        $liabilityService = app(LiabilityService::class);

        // Pay 2,000,000 from checking account
        $liabilityService->recordPayment([
            'user_id' => $this->user->id,
            'liability_id' => $liability->id,
            'source_account_id' => $this->checkingAccount->id,
            'amount' => 2000000,
            'date' => '2026-11-07',
            'description' => 'Bayar pokok pinjaman KTA',
        ]);

        $this->checkingAccount->refresh();
        $liability->refresh();

        $this->assertSame(8000000, $this->checkingAccount->current_balance);
        $this->assertSame(3000000, (int) $liability->current_balance);

        // Post net worth: Cash 13m - Liability 3m = 10m (net worth preserved!)
        $postNetWorth = $this->dashboardService->getNetWorthData((string) $this->user->id);
        $this->assertSame(13000000, $postNetWorth['total_cash']);
        $this->assertSame(3000000, $postNetWorth['total_liabilities']);
        $this->assertSame(10000000, $postNetWorth['net_worth']);
    }

    public function test_balance_verification_command_detects_and_fixes_discrepancy(): void
    {
        // Force tamper the current_balance column in DB
        DB::table('accounts')->where('id', $this->checkingAccount->id)->update([
            'current_balance' => 99999999, // Tampered balance
        ]);

        // Running without --fix should detect discrepancy and fail
        $this->artisan('finance:verify-balances')
            ->assertFailed();

        // Running with --fix should fix discrepancy and succeed
        $this->artisan('finance:verify-balances --fix')
            ->assertSuccessful();

        $this->checkingAccount->refresh();
        $this->assertSame(10000000, $this->checkingAccount->current_balance);
    }
}
