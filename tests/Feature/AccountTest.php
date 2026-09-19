<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Account;
use App\Enums\AccountType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_can_be_created_with_initial_balance_and_correct_current_balance()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/accounts', [
            'name' => 'Test Bank',
            'type' => AccountType::BANK->value,
            'currency' => 'IDR',
            'initial_balance' => 500000,
            'description' => 'Test description',
        ]);

        $response->assertRedirect('/accounts');

        $account = Account::where('user_id', $user->id)->first();
        
        $this->assertNotNull($account);
        $this->assertEquals('Test Bank', $account->name);
        $this->assertEquals(500000, $account->initial_balance);
        
        // The current balance must be exactly 500000, not double counted!
        $this->assertEquals(500000, $account->current_balance);
        
        // It should have exactly one transaction for initial balance
        $this->assertEquals(1, $account->transactions()->count());
        
        // The transaction amount should be 500000
        $transaction = $account->transactions()->first();
        $this->assertEquals(500000, $transaction->amount);
        $this->assertEquals('system', $transaction->source->value);
    }
}
