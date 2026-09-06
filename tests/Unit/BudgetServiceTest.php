<?php

namespace Tests\Unit;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use App\Services\AccountService;
use App\Services\BudgetService;
use App\Services\DashboardService;
use App\Services\TransactionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $account;

    private Category $foodCategory;

    private Category $transportCategory;

    private BudgetService $budgetService;

    private TransactionService $transactionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->account = Account::create([
            'user_id' => $this->user->id,
            'name' => 'BCA Account',
            'type' => AccountType::BANK,
            'currency' => 'IDR',
            'initial_balance' => 10000000,
            'current_balance' => 10000000,
        ]);

        $this->foodCategory = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Makanan & Minuman',
            'type' => 'expense',
            'icon' => 'utensils',
            'color' => '#f97316',
        ]);

        $this->transportCategory = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Transportasi',
            'type' => 'expense',
            'icon' => 'car',
            'color' => '#3b82f6',
        ]);

        $this->budgetService = new BudgetService;
        $accountService = new AccountService;
        $dashboardService = new DashboardService($accountService, $this->budgetService);
        $this->transactionService = new TransactionService($accountService, $dashboardService);
    }

    public function test_can_create_budget_with_items(): void
    {
        $budget = $this->budgetService->createBudget(
            $this->user,
            [
                'name' => 'Anggaran November 2026',
                'period_start' => '2026-11-01',
                'period_end' => '2026-11-30',
                'total_budget' => 3000000,
            ],
            [
                ['category_id' => $this->foodCategory->id, 'amount' => 2000000],
                ['category_id' => $this->transportCategory->id, 'amount' => 1000000],
            ]
        );

        $this->assertSame('Anggaran November 2026', $budget->name);
        $this->assertSame(3000000, $budget->total_budget);
        $this->assertCount(2, $budget->items);
    }

    public function test_calculates_budget_progress_and_spent_from_transactions(): void
    {
        $budget = $this->budgetService->createBudget(
            $this->user,
            [
                'name' => 'Anggaran Uji',
                'period_start' => '2026-11-01',
                'period_end' => '2026-11-30',
            ],
            [
                ['category_id' => $this->foodCategory->id, 'amount' => 1000000],
                ['category_id' => $this->transportCategory->id, 'amount' => 500000],
            ]
        );

        // Record 500k in food (50% -> normal)
        $this->transactionService->createExpense([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->foodCategory->id,
            'amount' => 500000,
            'date' => '2026-11-10',
            'description' => 'Makan restoran',
        ]);

        // Record 450k in transport (90% -> danger)
        $this->transactionService->createExpense([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->transportCategory->id,
            'amount' => 450000,
            'date' => '2026-11-15',
            'description' => 'Bensin & Tol',
        ]);

        // Transaction outside budget period should NOT be counted
        $this->transactionService->createExpense([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->foodCategory->id,
            'amount' => 300000,
            'date' => '2026-10-31',
            'description' => 'Makan bulan lalu',
        ]);

        $progress = $this->budgetService->getBudgetWithProgress($budget);

        $this->assertSame(1500000, $progress['total_budget']);
        $this->assertSame(950000, $progress['total_spent']);
        $this->assertSame(550000, $progress['total_remaining']);
        $this->assertSame(63.3, $progress['percentage']);

        // Check food item
        $foodItem = collect($progress['items'])->firstWhere('category_id', $this->foodCategory->id);
        $this->assertSame(500000, $foodItem['spent']);
        $this->assertSame(500000, $foodItem['remaining']);
        $this->assertSame(50.0, $foodItem['percentage']);
        $this->assertSame('normal', $foodItem['status']);

        // Check transport item (450k / 500k = 90% -> danger)
        $transportItem = collect($progress['items'])->firstWhere('category_id', $this->transportCategory->id);
        $this->assertSame(450000, $transportItem['spent']);
        $this->assertSame(50000, $transportItem['remaining']);
        $this->assertSame(90.0, $transportItem['percentage']);
        $this->assertSame('danger', $transportItem['status']);
    }

    public function test_highlights_over_budget_category(): void
    {
        $budget = $this->budgetService->createBudget(
            $this->user,
            [
                'name' => 'Anggaran Over Test',
                'period_start' => '2026-11-01',
                'period_end' => '2026-11-30',
            ],
            [
                ['category_id' => $this->foodCategory->id, 'amount' => 500000],
            ]
        );

        // Record 600k (over 500k limit)
        $this->transactionService->createExpense([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->foodCategory->id,
            'amount' => 600000,
            'date' => '2026-11-12',
            'description' => 'Makan steak mahal',
        ]);

        $progress = $this->budgetService->getBudgetWithProgress($budget);

        $this->assertSame(1, $progress['over_budget_count']);
        $this->assertSame(100000, $progress['total_over_budget']);
        $this->assertSame('danger', $progress['status']);

        $foodItem = $progress['items'][0];
        $this->assertSame(100000, $foodItem['over_budget']);
        $this->assertSame(0, $foodItem['remaining']);
        $this->assertSame('danger', $foodItem['status']);
    }

    public function test_get_current_budget_for_user(): void
    {
        $this->budgetService->createBudget(
            $this->user,
            [
                'name' => 'Anggaran Bulan Ini',
                'period_start' => '2026-11-01',
                'period_end' => '2026-11-30',
            ],
            [
                ['category_id' => $this->foodCategory->id, 'amount' => 1000000],
            ]
        );

        $current = $this->budgetService->getCurrentBudgetForUser($this->user, Carbon::parse('2026-11-15'));
        $this->assertNotNull($current);
        $this->assertSame('Anggaran Bulan Ini', $current['name']);

        $none = $this->budgetService->getCurrentBudgetForUser($this->user, Carbon::parse('2026-12-15'));
        $this->assertNull($none);
    }
}
