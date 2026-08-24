<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\LiabilityType;
use App\Models\Account;
use App\Models\Liability;
use App\Models\User;
use App\Services\AccountService;
use App\Services\DashboardService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiabilityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $cashAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->cashAccount = Account::create([
            'user_id' => $this->user->id,
            'name' => 'BCA Account',
            'type' => AccountType::BANK,
            'currency' => 'IDR',
            'initial_balance' => 100000000,
            'current_balance' => 100000000,
        ]);

        $accountService = new AccountService;
        $dashboardService = new DashboardService($accountService);
        $transactionService = new TransactionService($accountService, $dashboardService);

        $transactionService->createAdjustment([
            'user_id' => $this->user->id,
            'account_id' => $this->cashAccount->id,
            'amount' => 100000000,
            'description' => 'Initial funding',
        ]);
    }

    public function test_authenticated_user_can_view_liabilities_index(): void
    {
        $response = $this->actingAs($this->user)->get(route('liabilities.index'));

        $response->assertOk();
    }

    public function test_authenticated_user_can_view_create_liability_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('liabilities.create'));

        $response->assertOk();
    }

    public function test_user_can_create_new_liability(): void
    {
        $response = $this->actingAs($this->user)->post(route('liabilities.store'), [
            'name' => 'BCA Everyday Card',
            'type' => LiabilityType::CREDIT_CARD->value,
            'currency' => 'IDR',
            'initial_balance' => 5000000,
            'current_balance' => 5000000,
            'credit_limit' => 20000000,
            'minimum_payment' => 500000,
            'due_date_day' => 15,
        ]);

        $liability = Liability::where('name', 'BCA Everyday Card')->first();

        $this->assertNotNull($liability);
        $this->assertEquals(5000000, $liability->current_balance);
        $this->assertEquals(20000000, $liability->credit_limit);
        $response->assertRedirect(route('liabilities.show', $liability->id));
    }

    public function test_user_can_view_liability_detail(): void
    {
        $liability = Liability::create([
            'user_id' => $this->user->id,
            'name' => 'Mandiri KTA',
            'type' => LiabilityType::PERSONAL_LOAN,
            'currency' => 'IDR',
            'initial_balance' => 50000000,
            'current_balance' => 45000000,
            'interest_rate' => 11.5,
            'minimum_payment' => 2500000,
            'remaining_terms' => 18,
        ]);

        $response = $this->actingAs($this->user)->get(route('liabilities.show', $liability->id));

        $response->assertOk();
    }

    public function test_user_can_update_liability(): void
    {
        $liability = Liability::create([
            'user_id' => $this->user->id,
            'name' => 'Shopee PayLater',
            'type' => LiabilityType::INSTALLMENT,
            'currency' => 'IDR',
            'initial_balance' => 3000000,
            'current_balance' => 3000000,
        ]);

        $response = $this->actingAs($this->user)->put(route('liabilities.update', $liability->id), [
            'name' => 'SPayLater Updated',
            'current_balance' => 2000000,
            'minimum_payment' => 500000,
            'remaining_terms' => 4,
        ]);

        $response->assertRedirect(route('liabilities.show', $liability->id));
        $this->assertDatabaseHas('liabilities', [
            'id' => $liability->id,
            'name' => 'SPayLater Updated',
            'current_balance' => 2000000,
        ]);
    }

    public function test_user_can_delete_liability(): void
    {
        $liability = Liability::create([
            'user_id' => $this->user->id,
            'name' => 'Old Debt',
            'type' => LiabilityType::OTHER_DEBT,
            'currency' => 'IDR',
            'initial_balance' => 1000000,
            'current_balance' => 1000000,
        ]);

        $response = $this->actingAs($this->user)->delete(route('liabilities.destroy', $liability->id));

        $response->assertRedirect(route('liabilities.index'));
        $this->assertSoftDeleted('liabilities', ['id' => $liability->id]);
    }

    public function test_user_can_record_liability_payment(): void
    {
        $liability = Liability::create([
            'user_id' => $this->user->id,
            'name' => 'BCA Card',
            'type' => LiabilityType::CREDIT_CARD,
            'currency' => 'IDR',
            'initial_balance' => 5000000,
            'current_balance' => 5000000,
            'credit_limit' => 20000000,
            'remaining_terms' => 5,
        ]);

        $response = $this->actingAs($this->user)->post(route('liabilities.pay', $liability->id), [
            'source_account_id' => $this->cashAccount->id,
            'amount' => 2000000,
            'date' => now()->toDateString(),
            'notes' => 'Partial payment',
        ]);

        $response->assertRedirect(route('liabilities.show', $liability->id));

        // Liability current balance should be 3,000,000
        $liability->refresh();
        $this->assertEquals(3000000, $liability->current_balance);
        $this->assertEquals(4, $liability->remaining_terms);

        // Cash account balance should decrease by 2,000,000 (from 100M to 98M)
        $this->cashAccount->refresh();
        $this->assertEquals(98000000, $this->cashAccount->current_balance);

        // Liability transaction audit record created
        $this->assertDatabaseHas('liability_transactions', [
            'liability_id' => $liability->id,
            'type' => 'payment',
            'amount' => 2000000,
        ]);
    }

    public function test_user_can_record_liability_increase(): void
    {
        $liability = Liability::create([
            'user_id' => $this->user->id,
            'name' => 'BCA Card',
            'type' => LiabilityType::CREDIT_CARD,
            'currency' => 'IDR',
            'initial_balance' => 1000000,
            'current_balance' => 1000000,
        ]);

        $response = $this->actingAs($this->user)->post(route('liabilities.increase', $liability->id), [
            'amount' => 1500000,
            'description' => 'Online grocery shopping',
        ]);

        $response->assertRedirect(route('liabilities.show', $liability->id));

        $liability->refresh();
        $this->assertEquals(2500000, $liability->current_balance);

        $this->assertDatabaseHas('liability_transactions', [
            'liability_id' => $liability->id,
            'type' => 'increase',
            'amount' => 1500000,
        ]);
    }

    public function test_loan_disbursement_increases_destination_account_balance(): void
    {
        $liability = Liability::create([
            'user_id' => $this->user->id,
            'name' => 'Bank Mandiri Loan',
            'type' => LiabilityType::PERSONAL_LOAN,
            'currency' => 'IDR',
            'initial_balance' => 0,
            'current_balance' => 0,
        ]);

        $response = $this->actingAs($this->user)->post(route('liabilities.increase', $liability->id), [
            'amount' => 25000000,
            'destination_account_id' => $this->cashAccount->id,
            'description' => 'Loan disbursement into BCA',
        ]);

        $response->assertRedirect(route('liabilities.show', $liability->id));

        $liability->refresh();
        $this->assertEquals(25000000, $liability->current_balance);

        // Account balance increases from 100M to 125M
        $this->cashAccount->refresh();
        $this->assertEquals(125000000, $this->cashAccount->current_balance);
    }

    public function test_dashboard_service_accurately_deducts_liabilities_from_net_worth(): void
    {
        Liability::create([
            'user_id' => $this->user->id,
            'name' => 'Credit Card',
            'type' => LiabilityType::CREDIT_CARD,
            'currency' => 'IDR',
            'initial_balance' => 10000000,
            'current_balance' => 10000000,
            'is_active' => true,
        ]);

        $accountService = new AccountService;
        $dashboardService = new DashboardService($accountService);

        $netWorthData = $dashboardService->getNetWorthData((string) $this->user->id);

        // Total cash = 100M, total liabilities = 10M => net worth = 90M
        $this->assertEquals(100000000, $netWorthData['total_cash']);
        $this->assertEquals(10000000, $netWorthData['total_liabilities']);
        $this->assertEquals(90000000, $netWorthData['net_worth']);
    }
}
