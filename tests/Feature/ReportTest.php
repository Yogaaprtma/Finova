<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\CategoryType;
use App\Models\Account;
use App\Models\Category;
use App\Models\NetWorthSnapshot;
use App\Models\User;
use App\Services\AccountService;
use App\Services\DashboardService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $account;

    private Category $foodCategory;

    private Category $salaryCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->account = Account::create([
            'user_id' => $this->user->id,
            'name' => 'Main BCA',
            'type' => AccountType::BANK,
            'currency' => 'IDR',
            'initial_balance' => 50000000,
            'current_balance' => 50000000,
        ]);

        $this->foodCategory = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Food & Dining',
            'type' => CategoryType::EXPENSE,
            'color' => '#EF4444',
            'icon' => 'utensils',
        ]);

        $this->salaryCategory = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Monthly Salary',
            'type' => CategoryType::INCOME,
            'color' => '#10B981',
            'icon' => 'briefcase',
        ]);

        $accountService = new AccountService;
        $dashboardService = new DashboardService($accountService);
        $transactionService = new TransactionService($accountService, $dashboardService);

        // Add 1 income and 1 expense in this month
        $transactionService->createIncome([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->salaryCategory->id,
            'amount' => 15000000,
            'date' => now()->toDateString(),
            'description' => 'Salary deposit',
        ]);

        $transactionService->createExpense([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->foodCategory->id,
            'amount' => 3500000,
            'date' => now()->toDateString(),
            'description' => 'Groceries & dining',
        ]);
    }

    public function test_authenticated_user_can_view_income_expense_report(): void
    {
        $response = $this->actingAs($this->user)->get(route('reports.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Index')
            ->where('activeType', 'income_expense')
            ->has('reportData.summary')
            ->where('reportData.summary.total_income', 15000000)
            ->where('reportData.summary.total_expense', 3500000)
            ->where('reportData.summary.net_savings', 11500000)
        );
    }

    public function test_user_can_view_cash_flow_report(): void
    {
        $response = $this->actingAs($this->user)->get(route('reports.index', ['type' => 'cash_flow']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Index')
            ->where('activeType', 'cash_flow')
            ->has('reportData.chart.inflow')
            ->where('reportData.summary.total_inflow', 15000000)
            ->where('reportData.summary.total_outflow', 3500000)
            ->where('reportData.summary.net_cash_flow', 11500000)
        );
    }

    public function test_user_can_view_expense_by_category_report(): void
    {
        $response = $this->actingAs($this->user)->get(route('reports.index', [
            'type' => 'expense_category',
            'period' => 'this_month',
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Index')
            ->where('activeType', 'expense_category')
            ->where('reportData.summary.total_expense', 3500000)
            ->has('reportData.table', 1)
            ->where('reportData.table.0.name', 'Food & Dining')
            ->where('reportData.table.0.amount', 3500000)
            ->where('reportData.table.0.percentage', 100)
        );
    }

    public function test_user_can_view_net_worth_trend_report(): void
    {
        NetWorthSnapshot::create([
            'user_id' => $this->user->id,
            'date' => now()->subMonth()->toDateString(),
            'total_cash' => 45000000,
            'total_investments' => 0,
            'total_liabilities' => 0,
            'net_worth' => 45000000,
        ]);

        $response = $this->actingAs($this->user)->get(route('reports.index', ['type' => 'net_worth']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Index')
            ->where('activeType', 'net_worth')
            ->has('reportData.chart.net_worth')
            ->has('reportData.table')
        );
    }

    public function test_user_can_view_asset_allocation_report(): void
    {
        $response = $this->actingAs($this->user)->get(route('reports.index', ['type' => 'asset_allocation']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/Index')
            ->where('activeType', 'asset_allocation')
            ->has('reportData.allocation')
        );
    }

    public function test_reports_filter_by_custom_date_range(): void
    {
        $response = $this->actingAs($this->user)->get(route('reports.index', [
            'type' => 'income_expense',
            'period' => 'custom',
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('reportData.summary.total_income', 15000000)
        );
    }

    public function test_reports_filter_by_account(): void
    {
        $otherAccount = Account::create([
            'user_id' => $this->user->id,
            'name' => 'Mandiri Account',
            'type' => AccountType::BANK,
            'currency' => 'IDR',
            'initial_balance' => 0,
            'current_balance' => 0,
        ]);

        // Filter by other account which has zero transactions
        $response = $this->actingAs($this->user)->get(route('reports.index', [
            'type' => 'income_expense',
            'account_id' => $otherAccount->id,
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('reportData.summary.total_income', 0)
            ->where('reportData.summary.total_expense', 0)
        );
    }
}

