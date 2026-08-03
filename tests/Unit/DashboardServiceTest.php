<?php

namespace Tests\Unit;

use App\Enums\AccountType;
use App\Enums\AssetType;
use App\Enums\CategoryType;
use App\Enums\LiabilityType;
use App\Models\Account;
use App\Models\AssetAccount;
use App\Models\Category;
use App\Models\Liability;
use App\Models\NetWorthSnapshot;
use App\Models\User;
use App\Services\AccountService;
use App\Services\DashboardService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardService $dashboardService;

    private TransactionService $transactionService;

    private User $user;

    private Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $accountService = new AccountService;
        $this->dashboardService = new DashboardService($accountService);
        $this->transactionService = new TransactionService($accountService, $this->dashboardService);

        $this->user = User::factory()->create();

        $this->account = Account::create([
            'user_id' => $this->user->id,
            'name' => 'Primary Bank',
            'type' => AccountType::BANK,
            'currency' => 'IDR',
            'initial_balance' => 0,
            'current_balance' => 0,
        ]);
    }

    public function test_get_net_worth_calculates_cash_investments_and_liabilities(): void
    {
        // 1. Add cash balance
        $this->transactionService->createAdjustment([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'amount' => 10000000, // 10 million IDR
        ]);

        // 2. Add an investment asset
        AssetAccount::create([
            'user_id' => $this->user->id,
            'name' => 'BBCA Stock',
            'type' => AssetType::STOCK,
            'currency' => 'IDR',
            'quantity' => 100,
            'current_price' => 10000, // 100 * 10,000 = 1,000,000
        ]);

        // 3. Add a liability
        Liability::create([
            'user_id' => $this->user->id,
            'name' => 'Credit Card',
            'type' => LiabilityType::CREDIT_CARD,
            'currency' => 'IDR',
            'initial_balance' => 2000000,
            'current_balance' => 2000000,
        ]);

        $netWorthData = $this->dashboardService->getNetWorthData((string) $this->user->id);

        $this->assertEquals(10000000, $netWorthData['total_cash']);
        $this->assertEquals(1000000, $netWorthData['total_investments']);
        $this->assertEquals(11000000, $netWorthData['total_assets']);
        $this->assertEquals(2000000, $netWorthData['total_liabilities']);
        // Net worth: 11,000,000 - 2,000,000 = 9,000,000
        $this->assertEquals(9000000, $netWorthData['net_worth']);
    }

    public function test_get_net_worth_computes_percentage_change_from_previous_snapshot(): void
    {
        // Previous month snapshot: 8,000,000 net worth
        NetWorthSnapshot::create([
            'user_id' => $this->user->id,
            'date' => now()->subDays(30)->toDateString(),
            'total_cash' => 8000000,
            'total_investments' => 0,
            'total_liabilities' => 0,
            'net_worth' => 8000000,
        ]);

        // Current cash: 10,000,000
        $this->transactionService->createAdjustment([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'amount' => 10000000,
        ]);

        $netWorthData = $this->dashboardService->getNetWorthData((string) $this->user->id);

        $this->assertEquals(10000000, $netWorthData['net_worth']);
        $this->assertEquals(2000000, $netWorthData['change_amount']);
        $this->assertEquals(25.0, $netWorthData['change_percentage']);
        $this->assertTrue($netWorthData['is_positive_change']);
    }

    public function test_get_cash_flow_metrics_and_savings_rate(): void
    {
        $incomeCategory = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Salary',
            'type' => CategoryType::INCOME,
        ]);

        $expenseCategory = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Rent',
            'type' => CategoryType::EXPENSE,
        ]);

        // Income: 10,000,000
        $this->transactionService->createIncome([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $incomeCategory->id,
            'amount' => 10000000,
            'date' => now()->toDateString(),
            'description' => 'Salary',
        ]);

        // Expense: 4,000,000
        $this->transactionService->createExpense([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $expenseCategory->id,
            'amount' => 4000000,
            'date' => now()->toDateString(),
            'description' => 'Rent',
        ]);

        $cashFlow = $this->dashboardService->getCashFlowData((string) $this->user->id, 'this_month');

        $this->assertEquals(10000000, $cashFlow['income']);
        $this->assertEquals(4000000, $cashFlow['expense']);
        $this->assertEquals(6000000, $cashFlow['net']);
        // Savings rate = (6,000,000 / 10,000,000) * 100 = 60.0%
        $this->assertEquals(60.0, $cashFlow['savings_rate']);
        $this->assertCount(6, $cashFlow['chart']['labels']);
    }

    public function test_get_asset_allocation_data(): void
    {
        $allocation = $this->dashboardService->getAssetAllocationData((string) $this->user->id, 5000000);

        $this->assertEquals(5000000, $allocation['total']);
        $this->assertNotEmpty($allocation['items']);
        $this->assertEquals('Cash & Bank', $allocation['items'][0]['label']);
        $this->assertEquals(100.0, $allocation['items'][0]['percentage']);
    }

    public function test_dashboard_data_is_cached_and_cleared(): void
    {
        Cache::flush();

        $data1 = $this->dashboardService->getDashboardData((string) $this->user->id, 'this_month');
        $this->assertNotNull($data1);

        $this->assertTrue(Cache::has("dashboard:{$this->user->id}:this_month"));

        $this->dashboardService->clearDashboardCache((string) $this->user->id);

        $this->assertFalse(Cache::has("dashboard:{$this->user->id}:this_month"));
    }
}
