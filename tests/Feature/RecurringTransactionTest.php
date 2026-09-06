<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringTransactionTest extends TestCase
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
            'name' => 'BCA Checking',
            'type' => AccountType::BANK,
            'currency' => 'IDR',
            'initial_balance' => 10000000,
            'current_balance' => 10000000,
        ]);

        $this->category = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Langganan',
            'type' => 'expense',
            'icon' => 'tv',
            'color' => '#8b5cf6',
        ]);
    }

    public function test_guest_cannot_access_recurring_transactions(): void
    {
        $response = $this->get('/recurring-transactions');
        $response->assertRedirect('/login');
    }

    public function test_user_can_view_recurring_transactions_index(): void
    {
        $response = $this->actingAs($this->user)->get('/recurring-transactions');
        $response->assertOk();
    }

    public function test_user_can_create_recurring_transaction(): void
    {
        $response = $this->actingAs($this->user)->post('/recurring-transactions', [
            'name' => 'Gym Membership',
            'type' => 'expense',
            'amount' => 350000,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'frequency' => 'monthly',
            'day_of_month' => 5,
            'start_date' => '2026-11-01',
            'is_active' => true,
            'auto_confirm' => true,
        ]);

        $response->assertRedirect('/recurring-transactions');

        $this->assertDatabaseHas('recurring_transactions', [
            'user_id' => $this->user->id,
            'name' => 'Gym Membership',
            'amount' => 350000,
            'frequency' => 'monthly',
        ]);
    }

    public function test_user_can_update_recurring_transaction(): void
    {
        $recurring = RecurringTransaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'name' => 'Sewa Kantor',
            'type' => 'expense',
            'amount' => 2000000,
            'frequency' => 'monthly',
            'day_of_month' => 1,
            'start_date' => '2026-11-01',
            'description' => 'Sewa lama',
        ]);

        $response = $this->actingAs($this->user)->put("/recurring-transactions/{$recurring->id}", [
            'name' => 'Sewa Kantor Baru',
            'type' => 'expense',
            'amount' => 2500000,
            'account_id' => $this->account->id,
            'frequency' => 'monthly',
            'day_of_month' => 1,
            'start_date' => '2026-11-01',
            'description' => 'Sewa baru',
            'is_active' => true,
            'auto_confirm' => true,
        ]);

        $response->assertRedirect('/recurring-transactions');

        $this->assertDatabaseHas('recurring_transactions', [
            'id' => $recurring->id,
            'name' => 'Sewa Kantor Baru',
            'amount' => 2500000,
        ]);
    }

    public function test_user_can_toggle_active_status(): void
    {
        $recurring = RecurringTransaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'name' => 'Katering',
            'type' => 'expense',
            'amount' => 600000,
            'frequency' => 'monthly',
            'start_date' => '2026-11-01',
            'is_active' => true,
            'description' => 'Katering',
        ]);

        $response = $this->actingAs($this->user)->post("/recurring-transactions/{$recurring->id}/toggle");
        $response->assertStatus(302);

        $this->assertFalse($recurring->fresh()->is_active);

        // Toggle back to active
        $response2 = $this->actingAs($this->user)->post("/recurring-transactions/{$recurring->id}/toggle");
        $response2->assertStatus(302);
        $this->assertTrue($recurring->fresh()->is_active);
    }

    public function test_user_can_delete_recurring_transaction(): void
    {
        $recurring = RecurringTransaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'name' => 'Tagihan Listrik',
            'type' => 'expense',
            'amount' => 400000,
            'frequency' => 'monthly',
            'start_date' => '2026-11-01',
            'description' => 'Listrik',
        ]);

        $response = $this->actingAs($this->user)->delete("/recurring-transactions/{$recurring->id}");
        $response->assertRedirect('/recurring-transactions');

        $this->assertSoftDeleted('recurring_transactions', ['id' => $recurring->id]);
    }

    public function test_user_can_manually_generate_now(): void
    {
        $recurring = RecurringTransaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'name' => 'Tagihan Wifi',
            'type' => 'expense',
            'amount' => 300000,
            'frequency' => 'monthly',
            'start_date' => '2026-11-01',
            'description' => 'Wifi Indihome',
            'auto_confirm' => true,
        ]);

        $response = $this->actingAs($this->user)->post("/recurring-transactions/{$recurring->id}/generate-now");
        $response->assertStatus(302);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'amount' => 300000,
            'source' => 'recurring',
        ]);
    }

    public function test_artisan_command_generates_due_transactions(): void
    {
        RecurringTransaction::create([
            'user_id' => $this->user->id,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'name' => 'Biaya Domain',
            'type' => 'expense',
            'amount' => 150000,
            'frequency' => 'daily',
            'start_date' => '2026-11-01',
            'description' => 'Domain bulanan',
            'is_active' => true,
            'auto_confirm' => true,
        ]);

        $this->artisan('recurring:generate --date=2026-11-10')
            ->assertSuccessful();

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'amount' => 150000,
            'source' => 'recurring',
        ]);
    }
}
