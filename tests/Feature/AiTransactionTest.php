<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\AiParsingLog;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiTransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $account;

    private Account $savingsAccount;

    private Category $foodCategory;

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

        $this->savingsAccount = Account::create([
            'user_id' => $this->user->id,
            'name' => 'Mandiri Savings',
            'type' => AccountType::BANK,
            'currency' => 'IDR',
            'initial_balance' => 5000000,
            'current_balance' => 5000000,
        ]);

        $this->foodCategory = Category::create([
            'user_id' => $this->user->id,
            'name' => 'Food & Dining',
            'type' => 'expense',
            'icon' => 'utensils',
            'color' => '#f97316',
        ]);
    }

    public function test_guest_cannot_parse_ai_transaction(): void
    {
        $response = $this->postJson('/ai/parse', [
            'message' => 'beli makan 25rb',
        ]);

        $response->assertStatus(401);
    }

    public function test_user_can_parse_ai_message_and_creates_log(): void
    {
        $response = $this->actingAs($this->user)->postJson('/ai/parse', [
            'message' => 'beli nasi goreng 25rb pake bca',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.result.amount', 25000);
        $response->assertJsonPath('data.result.transaction_type', 'expense');

        $this->assertDatabaseHas('ai_parsing_logs', [
            'user_id' => $this->user->id,
            'raw_message' => 'beli nasi goreng 25rb pake bca',
            'status' => 'pending',
        ]);
    }

    public function test_user_can_confirm_and_create_expense_transaction(): void
    {
        // First parse to get log_id
        $parseRes = $this->actingAs($this->user)->postJson('/ai/parse', [
            'message' => 'beli makan siang 35rb',
        ]);

        $logId = $parseRes->json('data.log_id');

        // Confirm
        $response = $this->actingAs($this->user)->postJson('/ai/confirm', [
            'type' => 'expense',
            'amount' => 35000,
            'date' => '2026-11-09',
            'description' => 'Makan siang',
            'account_id' => $this->account->id,
            'category_id' => $this->foodCategory->id,
            'log_id' => $logId,
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'type' => 'expense',
            'amount' => 35000,
            'description' => 'Makan siang',
        ]);

        $this->assertDatabaseHas('ai_parsing_logs', [
            'id' => $logId,
            'status' => 'confirmed',
        ]);
    }

    public function test_user_can_confirm_transfer_transaction(): void
    {
        $response = $this->actingAs($this->user)->postJson('/ai/confirm', [
            'type' => 'transfer',
            'amount' => 500000,
            'date' => '2026-11-09',
            'description' => 'Transfer tabungan',
            'from_account_id' => $this->account->id,
            'to_account_id' => $this->savingsAccount->id,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'type' => 'transfer',
            'amount' => 500000,
        ]);
    }

    public function test_user_can_reject_ai_parse_log(): void
    {
        $log = AiParsingLog::create([
            'user_id' => $this->user->id,
            'source' => 'web',
            'raw_message' => 'pesan random',
            'parsed_data' => [],
            'confidence' => 0.2,
            'ai_provider' => 'fallback',
            'ai_model' => 'rule-engine',
            'prompt_version' => 'v1.0',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)->postJson('/ai/reject', [
            'log_id' => $log->id,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('ai_parsing_logs', [
            'id' => $log->id,
            'status' => 'rejected',
        ]);
    }
}
