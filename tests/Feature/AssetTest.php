<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\AssetType;
use App\Models\Account;
use App\Models\AssetAccount;
use App\Models\User;
use App\Services\AccountService;
use App\Services\DashboardService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetTest extends TestCase
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

    public function test_authenticated_user_can_view_portfolio_index(): void
    {
        $response = $this->actingAs($this->user)->get(route('assets.index'));

        $response->assertOk();
    }

    public function test_authenticated_user_can_view_create_asset_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('assets.create'));

        $response->assertOk();
    }

    public function test_user_can_create_new_asset_account(): void
    {
        $response = $this->actingAs($this->user)->post(route('assets.store'), [
            'name' => 'GoTo Stock',
            'type' => AssetType::STOCK->value,
            'platform' => 'Ajaib',
            'ticker' => 'GOTO',
            'quantity' => 100,
            'avg_purchase_price' => 70,
            'current_price' => 80,
        ]);

        $asset = AssetAccount::where('ticker', 'GOTO')->first();

        $this->assertNotNull($asset);
        $response->assertRedirect(route('assets.show', $asset->id));
        $this->assertEquals('GOTO', $asset->ticker);
    }

    public function test_user_can_view_asset_details(): void
    {
        $asset = AssetAccount::create([
            'user_id' => $this->user->id,
            'name' => 'Ethereum Portfolio',
            'type' => AssetType::CRYPTO,
            'ticker' => 'ETH',
            'quantity' => 1.5,
            'avg_purchase_price' => 45000000,
            'current_price' => 48000000,
        ]);

        $response = $this->actingAs($this->user)->get(route('assets.show', $asset->id));

        $response->assertOk();
    }

    public function test_user_can_quick_update_market_price(): void
    {
        $asset = AssetAccount::create([
            'user_id' => $this->user->id,
            'name' => 'Antam Gold',
            'type' => AssetType::GOLD,
            'quantity' => 10,
            'avg_purchase_price' => 1300000,
            'current_price' => 1300000,
        ]);

        $response = $this->actingAs($this->user)->patch(route('assets.price.update', $asset->id), [
            'current_price' => 1400000,
        ]);

        $response->assertRedirect();
        $asset->refresh();
        $this->assertEquals(1400000, (float) $asset->current_price);
    }

    public function test_user_can_record_asset_purchase(): void
    {
        $asset = AssetAccount::create([
            'user_id' => $this->user->id,
            'name' => 'BBRI Stock',
            'type' => AssetType::STOCK,
            'ticker' => 'BBRI',
            'quantity' => 10,
            'avg_purchase_price' => 5000,
            'current_price' => 5000,
        ]);

        $response = $this->actingAs($this->user)->post(route('assets.buy', $asset->id), [
            'source_account_id' => $this->cashAccount->id,
            'quantity' => 10,
            'price_per_unit' => 5500,
            'fees' => 500,
            'date' => now()->toDateString(),
        ]);

        $response->assertRedirect();
        $asset->refresh();
        $this->assertEquals(20, (float) $asset->quantity);
    }

    public function test_user_can_record_asset_sale(): void
    {
        $asset = AssetAccount::create([
            'user_id' => $this->user->id,
            'name' => 'Solana',
            'type' => AssetType::CRYPTO,
            'ticker' => 'SOL',
            'quantity' => 5,
            'avg_purchase_price' => 2000000,
            'current_price' => 2500000,
        ]);

        $response = $this->actingAs($this->user)->post(route('assets.sell', $asset->id), [
            'destination_account_id' => $this->cashAccount->id,
            'quantity' => 2,
            'price_per_unit' => 3000000,
            'fees' => 10000,
            'date' => now()->toDateString(),
        ]);

        $response->assertRedirect();
        $asset->refresh();
        $this->assertEquals(3, (float) $asset->quantity);
    }

    public function test_user_can_delete_asset(): void
    {
        $asset = AssetAccount::create([
            'user_id' => $this->user->id,
            'name' => 'Old Vehicle',
            'type' => AssetType::VEHICLE,
            'manual_value' => 15000000,
        ]);

        $response = $this->actingAs($this->user)->delete(route('assets.destroy', $asset->id));

        $response->assertRedirect(route('assets.index'));
        $this->assertSoftDeleted('asset_accounts', ['id' => $asset->id]);
    }

    public function test_user_cannot_access_other_users_asset(): void
    {
        $otherUser = User::factory()->create();

        $asset = AssetAccount::create([
            'user_id' => $otherUser->id,
            'name' => 'Other User Asset',
            'type' => AssetType::STOCK,
            'quantity' => 10,
            'current_price' => 1000,
        ]);

        $response = $this->actingAs($this->user)->get(route('assets.show', $asset->id));

        $response->assertNotFound();
    }
}
