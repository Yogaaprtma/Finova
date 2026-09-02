<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $account;

    private Category $category;

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

        $this->category = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Belanja Bulanan',
            'type' => 'expense',
            'icon' => 'shopping-cart',
            'color' => '#10b981',
        ]);
    }

    public function test_guest_cannot_access_budgets(): void
    {
        $response = $this->get('/budgets');
        $response->assertRedirect('/login');
    }

    public function test_user_can_view_budgets_index(): void
    {
        $response = $this->actingAs($this->user)->get('/budgets');
        $response->assertOk();
    }

    public function test_user_can_create_budget(): void
    {
        $response = $this->actingAs($this->user)->post('/budgets', [
            'name' => 'Anggaran Des 2026',
            'period_start' => '2026-12-01',
            'period_end' => '2026-12-31',
            'total_budget' => 2000000,
            'items' => [
                [
                    'category_id' => $this->category->id,
                    'amount' => 2000000,
                ],
            ],
        ]);

        $this->assertDatabaseHas('budgets', [
            'user_id' => $this->user->id,
            'name' => 'Anggaran Des 2026',
            'total_budget' => 2000000,
        ]);

        $this->assertDatabaseHas('budget_items', [
            'category_id' => $this->category->id,
            'amount' => 2000000,
        ]);
    }

    public function test_cannot_create_duplicate_budget_for_same_period(): void
    {
        Budget::create([
            'user_id' => $this->user->id,
            'name' => 'Anggaran Exist',
            'period_start' => '2026-12-01',
            'period_end' => '2026-12-31',
            'total_budget' => 1000000,
        ]);

        $response = $this->actingAs($this->user)->post('/budgets', [
            'name' => 'Anggaran Duplikat',
            'period_start' => '2026-12-01',
            'period_end' => '2026-12-31',
            'total_budget' => 1000000,
            'items' => [
                ['category_id' => $this->category->id, 'amount' => 1000000],
            ],
        ]);

        $response->assertSessionHasErrors('period_start');
    }

    public function test_user_can_view_budget_show(): void
    {
        $budget = Budget::create([
            'user_id' => $this->user->id,
            'name' => 'Detail Budget Test',
            'period_start' => '2026-11-01',
            'period_end' => '2026-11-30',
            'total_budget' => 1500000,
        ]);

        $response = $this->actingAs($this->user)->get("/budgets/{$budget->id}");
        $response->assertOk();
    }

    public function test_user_can_update_budget(): void
    {
        $budget = Budget::create([
            'user_id' => $this->user->id,
            'name' => 'Budget Lama',
            'period_start' => '2026-11-01',
            'period_end' => '2026-11-30',
            'total_budget' => 1000000,
        ]);

        $response = $this->actingAs($this->user)->put("/budgets/{$budget->id}", [
            'name' => 'Budget Diedit',
            'period_start' => '2026-11-01',
            'period_end' => '2026-11-30',
            'total_budget' => 1500000,
            'items' => [
                ['category_id' => $this->category->id, 'amount' => 1500000],
            ],
        ]);

        $response->assertRedirect("/budgets/{$budget->id}");

        $this->assertDatabaseHas('budgets', [
            'id' => $budget->id,
            'name' => 'Budget Diedit',
            'total_budget' => 1500000,
        ]);
    }

    public function test_user_can_delete_budget(): void
    {
        $budget = Budget::create([
            'user_id' => $this->user->id,
            'name' => 'Budget Hapus',
            'period_start' => '2026-11-01',
            'period_end' => '2026-11-30',
            'total_budget' => 1000000,
        ]);

        $response = $this->actingAs($this->user)->delete("/budgets/{$budget->id}");
        $response->assertRedirect('/budgets');

        $this->assertSoftDeleted('budgets', ['id' => $budget->id]);
    }
}
