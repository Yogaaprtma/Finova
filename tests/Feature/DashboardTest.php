<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\CategoryType;
use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use App\Services\AccountService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard_and_receive_data(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'BCA',
            'type' => AccountType::BANK,
            'currency' => 'IDR',
            'initial_balance' => 0,
            'current_balance' => 0,
        ]);

        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'Salary',
            'type' => CategoryType::INCOME,
        ]);

        $txService = new TransactionService(new AccountService);
        $txService->createIncome([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => 5000000,
            'date' => now()->toDateString(),
            'description' => 'Monthly Salary',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('netWorth')
            ->where('netWorth.net_worth', 5000000)
            ->where('netWorth.total_cash', 5000000)
            ->has('cashFlow')
            ->where('cashFlow.income', 5000000)
            ->has('assetAllocation')
            ->has('recentTransactions', 1)
            ->where('period', 'this_month')
        );
    }

    public function test_user_can_query_dashboard_with_last_month_period(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard', ['period' => 'last_month']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('period', 'last_month')
            ->where('cashFlow.period', 'last_month')
        );
    }

    /**
     * Regression: the dashboard payload is cached. config/cache.php sets
     * serializable_classes=false, so caching Eloquent models used to come back
     * as __PHP_Incomplete_Class on the 2nd visit (blank rows, "RpNaN").
     */
    public function test_recent_transactions_stay_intact_when_served_from_file_cache(): void
    {
        config([
            'cache.default' => 'file',
            'cache.stores.file.path' => sys_get_temp_dir().'/finova_dashboard_cache_test',
        ]);
        Cache::flush();

        $user = User::factory()->create();
        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'BNI',
            'type' => AccountType::BANK,
            'currency' => 'IDR',
            'initial_balance' => 0,
            'current_balance' => 0,
        ]);
        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'Food & Dining',
            'type' => CategoryType::EXPENSE,
        ]);

        (new TransactionService(new AccountService))->createExpense([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => 50000,
            'date' => now()->toDateString(),
            'description' => 'Makan Siang Nasi Padang',
        ]);

        // 1st visit warms the cache, 2nd visit is served from it.
        foreach ([1, 2] as $visit) {
            $this->actingAs($user)->get(route('dashboard'))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Dashboard')
                    ->has('recentTransactions', 1)
                    ->where('recentTransactions.0.description', 'Makan Siang Nasi Padang')
                    ->where('recentTransactions.0.amount', 50000)
                    ->where('recentTransactions.0.type', 'expense')
                    ->where('recentTransactions.0.category.name', 'Food & Dining')
                    ->where('recentTransactions.0.entries.0.account.name', 'BNI')
                );
        }

        Cache::flush();
    }
}
