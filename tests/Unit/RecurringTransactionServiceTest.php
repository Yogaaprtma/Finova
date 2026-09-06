<?php

namespace Tests\Unit;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Models\User;
use App\Services\AccountService;
use App\Services\DashboardService;
use App\Services\RecurringTransactionService;
use App\Services\TransactionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringTransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $account;

    private Category $category;

    private RecurringTransactionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->account = Account::create([
            'user_id' => $this->user->id,
            'name' => 'BCA Rekening',
            'type' => AccountType::BANK,
            'currency' => 'IDR',
            'initial_balance' => 5000000,
            'current_balance' => 5000000,
        ]);

        $this->category = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Langganan',
            'type' => 'expense',
            'icon' => 'tv',
            'color' => '#8b5cf6',
        ]);

        $accountService = new AccountService;
        $dashboardService = new DashboardService($accountService);
        $transactionService = new TransactionService($accountService, $dashboardService);
        $this->service = new RecurringTransactionService($transactionService);
    }

    public function test_can_create_recurring_transaction(): void
    {
        $recurring = $this->service->create($this->user, [
            'name' => 'Netflix',
            'type' => 'expense',
            'amount' => 186000,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'frequency' => 'monthly',
            'day_of_month' => 15,
            'start_date' => '2026-01-01',
            'auto_confirm' => true,
        ]);

        $this->assertInstanceOf(RecurringTransaction::class, $recurring);
        $this->assertSame(186000, $recurring->amount);
        $this->assertSame('monthly', $recurring->frequency);
        $this->assertTrue($recurring->is_active);
        $this->assertTrue($recurring->auto_confirm);
    }

    public function test_monthly_frequency_should_generate_on_correct_day(): void
    {
        $recurring = $this->service->create($this->user, [
            'name' => 'Gaji',
            'type' => 'income',
            'amount' => 10000000,
            'account_id' => $this->account->id,
            'frequency' => 'monthly',
            'day_of_month' => 25,
            'start_date' => '2026-01-01',
        ]);

        $this->assertTrue($recurring->shouldGenerateOn(Carbon::parse('2026-11-25')));
        $this->assertFalse($recurring->shouldGenerateOn(Carbon::parse('2026-11-24')));
        $this->assertFalse($recurring->shouldGenerateOn(Carbon::parse('2026-11-26')));
    }

    public function test_weekly_frequency_should_generate_on_correct_day_of_week(): void
    {
        $recurring = $this->service->create($this->user, [
            'name' => 'Uang Jajan Mingguan',
            'type' => 'expense',
            'amount' => 200000,
            'account_id' => $this->account->id,
            'frequency' => 'weekly',
            'day_of_week' => 1, // Senin
            'start_date' => '2026-11-01',
        ]);

        // 2026-11-02 is Monday
        $this->assertTrue($recurring->shouldGenerateOn(Carbon::parse('2026-11-02')));
        // 2026-11-03 is Tuesday
        $this->assertFalse($recurring->shouldGenerateOn(Carbon::parse('2026-11-03')));
    }

    public function test_does_not_generate_if_inactive_or_past_end_date(): void
    {
        $recurring = $this->service->create($this->user, [
            'name' => 'Promo Langganan',
            'type' => 'expense',
            'amount' => 50000,
            'account_id' => $this->account->id,
            'frequency' => 'daily',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-05',
            'is_active' => true,
        ]);

        $this->assertTrue($recurring->shouldGenerateOn(Carbon::parse('2026-11-04')));
        $this->assertFalse($recurring->shouldGenerateOn(Carbon::parse('2026-11-06'))); // Past end date

        $this->service->toggleActive($recurring);
        $this->assertFalse($recurring->fresh()->shouldGenerateOn(Carbon::parse('2026-11-04'))); // Inactive
    }

    public function test_generate_for_template_creates_transaction_and_updates_last_generated_date(): void
    {
        $recurring = $this->service->create($this->user, [
            'name' => 'Internet Indihome',
            'type' => 'expense',
            'amount' => 350000,
            'account_id' => $this->account->id,
            'category_id' => $this->category->id,
            'frequency' => 'monthly',
            'day_of_month' => 10,
            'start_date' => '2026-11-01',
            'auto_confirm' => true,
        ]);

        $tx = $this->service->generateForTemplate($recurring, Carbon::parse('2026-11-10'));

        $this->assertSame('expense', $tx->type->value);
        $this->assertSame(350000, $tx->amount);
        $this->assertSame('recurring', $tx->source->value);
        $this->assertTrue($tx->is_confirmed);

        $recurring->refresh();
        $this->assertSame('2026-11-10', $recurring->last_generated_date->format('Y-m-d'));

        // Should not generate again on the same day
        $this->assertFalse($recurring->shouldGenerateOn(Carbon::parse('2026-11-10')));
    }

    public function test_generate_due_transactions_batch(): void
    {
        $this->service->create($this->user, [
            'name' => 'Spotify',
            'type' => 'expense',
            'amount' => 55000,
            'account_id' => $this->account->id,
            'frequency' => 'daily',
            'start_date' => '2026-11-01',
        ]);

        $count = $this->service->generateDueTransactions(Carbon::parse('2026-11-10'));
        $this->assertGreaterThanOrEqual(1, $count);
    }
}
