<?php

namespace Tests\Unit;

use App\Enums\AccountType;
use App\Enums\AssetType;
use App\Enums\EntryType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\User;
use App\Services\AccountService;
use App\Services\AssetService;
use App\Services\DashboardService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class AssetServiceTest extends TestCase
{
    use RefreshDatabase;

    private AssetService $assetService;

    private AccountService $accountService;

    private DashboardService $dashboardService;

    private User $user;

    private Account $cashAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountService = new AccountService;
        $this->dashboardService = new DashboardService($this->accountService);
        $this->assetService = new AssetService($this->accountService, $this->dashboardService);

        $this->user = User::factory()->create();

        $this->cashAccount = Account::create([
            'user_id' => $this->user->id,
            'name' => 'BCA Checking',
            'type' => AccountType::BANK,
            'currency' => 'IDR',
            'initial_balance' => 50000000,
            'current_balance' => 50000000,
        ]);

        $transactionService = new TransactionService($this->accountService, $this->dashboardService);
        $transactionService->createAdjustment([
            'user_id' => $this->user->id,
            'account_id' => $this->cashAccount->id,
            'amount' => 50000000,
            'description' => 'Initial funding',
        ]);
    }

    public function test_can_create_asset_account(): void
    {
        $asset = $this->assetService->createAsset([
            'user_id' => $this->user->id,
            'name' => 'Bank Mandiri Stock',
            'type' => AssetType::STOCK,
            'platform' => 'Stockbit',
            'ticker' => 'BMRI',
            'quantity' => 10,
            'avg_purchase_price' => 5000,
            'current_price' => 5200,
        ]);

        $this->assertDatabaseHas('asset_accounts', [
            'id' => $asset->id,
            'ticker' => 'BMRI',
            'type' => 'stock',
        ]);

        $this->assertEquals(5200000, $asset->current_value);
        $this->assertEquals(5000000, $asset->cost_basis);
        $this->assertEquals(200000, $asset->unrealized_pnl);
        $this->assertEquals(4.0, $asset->unrealized_pnl_percentage);
    }

    public function test_indonesian_stock_calculates_with_100_multiplier_and_decimal_avg_price(): void
    {
        // BMRI: 30 lots, avg price 4205.6323, current market price 3720
        $asset = $this->assetService->createAsset([
            'user_id' => $this->user->id,
            'name' => 'Saham Bank Mandiri',
            'type' => AssetType::STOCK,
            'platform' => 'Stockbit',
            'ticker' => 'BMRI',
            'quantity' => 30,
            'avg_purchase_price' => 4205.6323,
            'current_price' => 3720,
        ]);

        $this->assertEquals(11160000, $asset->current_value); // 30 lots * 100 * 3,720
        $this->assertEquals(12616897, $asset->cost_basis); // round(30 * 100 * 4205.6323)
        $this->assertEquals(-1456897, $asset->unrealized_pnl); // 11,160,000 - 12,616,897
        $this->assertEquals(-11.55, $asset->unrealized_pnl_percentage);
    }

    public function test_can_create_manual_value_asset(): void
    {
        $asset = $this->assetService->createAsset([
            'user_id' => $this->user->id,
            'name' => 'Apartment Tower A',
            'type' => AssetType::PROPERTY,
            'manual_value' => 850000000,
        ]);

        $this->assertEquals(850000000, $asset->current_value);
        $this->assertEquals(850000000, $asset->cost_basis);
        $this->assertEquals(0, $asset->unrealized_pnl);
    }

    public function test_can_update_current_market_price(): void
    {
        $asset = $this->assetService->createAsset([
            'user_id' => $this->user->id,
            'name' => 'Bitcoin',
            'type' => AssetType::CRYPTO,
            'ticker' => 'BTC',
            'quantity' => 0.1,
            'avg_purchase_price' => 1000000000,
            'current_price' => 1000000000,
        ]);

        $updated = $this->assetService->updateCurrentPrice($asset, 1100000000);

        $this->assertEquals(1100000000, (float) $updated->current_price);
        $this->assertEquals(110000000, $updated->current_value);
        $this->assertEquals(10000000, $updated->unrealized_pnl);
        $this->assertEquals(10.0, $updated->unrealized_pnl_percentage);
    }

    public function test_asset_purchase_flow_updates_quantity_and_weighted_average_price(): void
    {
        // 1. Initial holding: 10 lots @ 5,000 = 50,000
        $asset = $this->assetService->createAsset([
            'user_id' => $this->user->id,
            'name' => 'BBCA',
            'type' => AssetType::STOCK,
            'ticker' => 'BBCA',
            'quantity' => 10,
            'avg_purchase_price' => 5000,
            'current_price' => 5000,
        ]);

        // 2. Buy additional 10 lots @ 6,000 = 6,000,000 with 1,000 fees = 6,001,000 total
        $tx = $this->assetService->recordPurchase([
            'user_id' => $this->user->id,
            'source_account_id' => $this->cashAccount->id,
            'asset_account_id' => $asset->id,
            'quantity' => 10,
            'price_per_unit' => 6000,
            'fees' => 1000,
            'date' => now()->toDateString(),
        ]);

        $this->assertEquals(TransactionType::ASSET_PURCHASE, $tx->type);
        $this->assertEquals(6001000, $tx->amount);

        // Verify entries
        $this->assertDatabaseHas('transaction_entries', [
            'transaction_id' => $tx->id,
            'account_id' => $this->cashAccount->id,
            'entry_type' => EntryType::DEBIT->value,
            'amount' => 6001000,
        ]);

        $this->assertDatabaseHas('transaction_entries', [
            'transaction_id' => $tx->id,
            'asset_account_id' => $asset->id,
            'entry_type' => EntryType::CREDIT->value,
            'amount' => 6001000,
        ]);

        $this->assertDatabaseHas('asset_transactions', [
            'transaction_id' => $tx->id,
            'type' => 'buy',
            'quantity' => 10,
            'price_per_unit' => 6000,
            'total_amount' => 6001000,
            'fees' => 1000,
        ]);

        // Cash account balance decreased: 50,000,000 - 6,001,000 = 43,999,000
        $this->cashAccount->refresh();
        $this->assertEquals(43999000, $this->cashAccount->current_balance);

        // Asset updated: quantity = 20, avg = (10*5000 + 10*6000) / 20 = 5500
        $asset->refresh();
        $this->assertEquals(20, (float) $asset->quantity);
        $this->assertEquals(5500, (float) $asset->avg_purchase_price);
    }

    public function test_asset_sale_flow_decreases_quantity_and_increases_cash(): void
    {
        $asset = $this->assetService->createAsset([
            'user_id' => $this->user->id,
            'name' => 'BBRI',
            'type' => AssetType::STOCK,
            'ticker' => 'BBRI',
            'quantity' => 20,
            'avg_purchase_price' => 4500,
            'current_price' => 5000,
        ]);

        // Sell 10 lots @ 5,500 = 5,500,000 gross with 500 fees = 5,499,500 net
        $tx = $this->assetService->recordSale([
            'user_id' => $this->user->id,
            'destination_account_id' => $this->cashAccount->id,
            'asset_account_id' => $asset->id,
            'quantity' => 10,
            'price_per_unit' => 5500,
            'fees' => 500,
            'date' => now()->toDateString(),
        ]);

        $this->assertEquals(TransactionType::ASSET_SALE, $tx->type);
        $this->assertEquals(5499500, $tx->amount);

        // Cash account increased by net proceeds: 50,000,000 + 5,499,500 = 55,499,500
        $this->cashAccount->refresh();
        $this->assertEquals(55499500, $this->cashAccount->current_balance);

        // Asset quantity reduced to 10, avg purchase price unchanged at 4500
        $asset->refresh();
        $this->assertEquals(10, (float) $asset->quantity);
        $this->assertEquals(4500, (float) $asset->avg_purchase_price);
        $this->assertEquals(5500, (float) $asset->current_price);
    }

    public function test_cannot_sell_more_than_held_quantity(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $asset = $this->assetService->createAsset([
            'user_id' => $this->user->id,
            'name' => 'Ethereum',
            'type' => AssetType::CRYPTO,
            'quantity' => 1.5,
            'avg_purchase_price' => 40000000,
        ]);

        $this->assetService->recordSale([
            'user_id' => $this->user->id,
            'destination_account_id' => $this->cashAccount->id,
            'asset_account_id' => $asset->id,
            'quantity' => 2.0, // Selling more than 1.5
            'price_per_unit' => 45000000,
        ]);
    }

    public function test_get_portfolio_overview_aggregates_metrics_correctly(): void
    {
        $this->assetService->createAsset([
            'user_id' => $this->user->id,
            'name' => 'Telkom',
            'type' => AssetType::STOCK,
            'quantity' => 10,
            'avg_purchase_price' => 3000,
            'current_price' => 3500, // value: 35,000, cost: 30,000, pnl: +5,000
        ]);

        $this->assetService->createAsset([
            'user_id' => $this->user->id,
            'name' => 'House',
            'type' => AssetType::PROPERTY,
            'manual_value' => 500000000, // value: 500,000,000, cost: 500,000,000
        ]);

        $overview = $this->assetService->getPortfolioOverview($this->user->id);

        $this->assertEquals(503500000, $overview['metrics']['total_value']);
        $this->assertEquals(503000000, $overview['metrics']['total_cost_basis']);
        $this->assertEquals(500000, $overview['metrics']['total_unrealized_pnl']);
        $this->assertEquals(3500000, $overview['metrics']['investments_value']);
        $this->assertEquals(500000000, $overview['metrics']['other_assets_value']);
        $this->assertCount(2, $overview['all_assets']);
        $this->assertCount(1, $overview['investments']);
        $this->assertCount(1, $overview['other_assets']);
    }
}
