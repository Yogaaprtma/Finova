<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\NetWorthSnapshot;
use App\Models\User;
use App\Services\AccountService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SnapshotNetWorthCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_snapshot_net_worth_command_records_snapshots_for_users(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $account1 = Account::create([
            'user_id' => $user1->id,
            'name' => 'User 1 Bank',
            'type' => AccountType::BANK,
            'currency' => 'IDR',
            'initial_balance' => 0,
            'current_balance' => 0,
        ]);

        $account2 = Account::create([
            'user_id' => $user2->id,
            'name' => 'User 2 Bank',
            'type' => AccountType::BANK,
            'currency' => 'IDR',
            'initial_balance' => 0,
            'current_balance' => 0,
        ]);

        $txService = new TransactionService(new AccountService);
        $txService->createAdjustment([
            'user_id' => $user1->id,
            'account_id' => $account1->id,
            'amount' => 5000000,
        ]);

        $txService->createAdjustment([
            'user_id' => $user2->id,
            'account_id' => $account2->id,
            'amount' => 12000000,
        ]);

        $this->artisan('finance:snapshot-net-worth')
            ->assertSuccessful();

        $today = now()->toDateString();

        $snapshot1 = NetWorthSnapshot::where('user_id', $user1->id)->whereDate('date', $today)->first();
        $this->assertNotNull($snapshot1);
        $this->assertEquals(5000000, $snapshot1->total_cash);
        $this->assertEquals(5000000, $snapshot1->net_worth);

        $snapshot2 = NetWorthSnapshot::where('user_id', $user2->id)->whereDate('date', $today)->first();
        $this->assertNotNull($snapshot2);
        $this->assertEquals(12000000, $snapshot2->total_cash);
        $this->assertEquals(12000000, $snapshot2->net_worth);
    }
}
