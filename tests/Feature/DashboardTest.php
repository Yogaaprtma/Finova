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
}
