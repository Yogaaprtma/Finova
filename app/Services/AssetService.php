<?php

namespace App\Services;

use App\Enums\EntryType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\AssetAccount;
use App\Models\AssetTransaction;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AssetService
{
    public function __construct(
        private AccountService $accountService,
        private DashboardService $dashboardService,
    ) {}

    /**
     * Get complete portfolio overview with metrics, category tabs, and allocation.
     *
     * @return array<string, mixed>
     */
    public function getPortfolioOverview(string|int $userId): array
    {
        $assets = AssetAccount::where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->withCount('transactions')
            ->get();

        $investments = $assets->filter(fn (AssetAccount $a) => $a->type->isInvestment())->values();
        $otherAssets = $assets->filter(fn (AssetAccount $a) => ! $a->type->isInvestment())->values();

        $totalValue = (int) $assets->sum('current_value');
        $totalCostBasis = (int) $assets->sum('cost_basis');
        $totalUnrealizedPnl = $totalValue - $totalCostBasis;
        $totalPnlPercentage = $totalCostBasis > 0 ? round(($totalUnrealizedPnl / $totalCostBasis) * 100, 2) : 0.0;

        $investmentsValue = (int) $investments->sum('current_value');
        $investmentsCostBasis = (int) $investments->sum('cost_basis');
        $investmentsPnl = $investmentsValue - $investmentsCostBasis;
        $investmentsPnlPercentage = $investmentsCostBasis > 0 ? round(($investmentsPnl / $investmentsCostBasis) * 100, 2) : 0.0;

        $otherAssetsValue = (int) $otherAssets->sum('current_value');

        // Asset Allocation Breakdown
        $allocationGroups = [];
        foreach ($assets as $asset) {
            $typeKey = $asset->type->value;
            if (! isset($allocationGroups[$typeKey])) {
                $allocationGroups[$typeKey] = [
                    'type' => $typeKey,
                    'label' => $asset->type->label(),
                    'color' => $asset->type->color(),
                    'value' => 0,
                    'count' => 0,
                ];
            }
            $allocationGroups[$typeKey]['value'] += $asset->current_value;
            $allocationGroups[$typeKey]['count']++;
        }

        $allocationItems = array_values(array_filter($allocationGroups, fn ($item) => $item['value'] > 0));
        usort($allocationItems, fn ($a, $b) => $b['value'] <=> $a['value']);

        foreach ($allocationItems as &$item) {
            $item['percentage'] = $totalValue > 0 ? round(($item['value'] / $totalValue) * 100, 1) : 0.0;
        }
        unset($item);

        return [
            'metrics' => [
                'total_value' => $totalValue,
                'total_cost_basis' => $totalCostBasis,
                'total_unrealized_pnl' => $totalUnrealizedPnl,
                'total_pnl_percentage' => $totalPnlPercentage,
                'is_positive_pnl' => $totalUnrealizedPnl >= 0,
                'investments_value' => $investmentsValue,
                'investments_cost_basis' => $investmentsCostBasis,
                'investments_pnl' => $investmentsPnl,
                'investments_pnl_percentage' => $investmentsPnlPercentage,
                'other_assets_value' => $otherAssetsValue,
                'assets_count' => $assets->count(),
                'investments_count' => $investments->count(),
                'other_assets_count' => $otherAssets->count(),
            ],
            'allocation' => [
                'items' => $allocationItems,
                'labels' => array_column($allocationItems, 'label'),
                'values' => array_column($allocationItems, 'value'),
                'colors' => array_column($allocationItems, 'color'),
                'total' => $totalValue,
            ],
            'investments' => $investments,
            'other_assets' => $otherAssets,
            'all_assets' => $assets,
        ];
    }

    /**
     * Create a new asset account.
     *
     * @param  array<string, mixed>  $data
     */
    public function createAsset(array $data): AssetAccount
    {
        $asset = DB::transaction(function () use ($data) {
            $quantity = isset($data['quantity']) ? (float) $data['quantity'] : 0.0;
            $avgPrice = isset($data['avg_purchase_price']) ? (float) $data['avg_purchase_price'] : 0.0;
            $currentPrice = isset($data['current_price']) ? (float) $data['current_price'] : $avgPrice;
            $manualValue = isset($data['manual_value']) ? (int) $data['manual_value'] : null;

            return AssetAccount::create([
                'user_id' => $data['user_id'],
                'name' => $data['name'],
                'type' => $data['type'],
                'platform' => $data['platform'] ?? null,
                'ticker' => ! empty($data['ticker']) ? strtoupper((string) $data['ticker']) : null,
                'currency' => $data['currency'] ?? 'IDR',
                'quantity' => $quantity,
                'avg_purchase_price' => $avgPrice,
                'current_price' => $currentPrice,
                'manual_value' => $manualValue,
                'last_price_update' => $currentPrice > 0 ? now() : null,
                'is_active' => $data['is_active'] ?? true,
                'sort_order' => $data['sort_order'] ?? 0,
                'notes' => $data['notes'] ?? null,
            ]);
        });

        $this->dashboardService->clearDashboardCache((string) $data['user_id']);

        return $asset;
    }

    /**
     * Update an asset account.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateAsset(AssetAccount $asset, array $data): AssetAccount
    {
        DB::transaction(function () use ($asset, $data) {
            $updateData = [
                'name' => $data['name'] ?? $asset->name,
                'platform' => array_key_exists('platform', $data) ? $data['platform'] : $asset->platform,
                'ticker' => array_key_exists('ticker', $data) && ! empty($data['ticker'])
                    ? strtoupper((string) $data['ticker'])
                    : (array_key_exists('ticker', $data) ? null : $asset->ticker),
                'currency' => $data['currency'] ?? $asset->currency,
                'is_active' => $data['is_active'] ?? $asset->is_active,
                'sort_order' => $data['sort_order'] ?? $asset->sort_order,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : $asset->notes,
            ];

            if ($asset->type->isValuedManually() && array_key_exists('manual_value', $data)) {
                $updateData['manual_value'] = (int) $data['manual_value'];
            }

            $asset->update($updateData);
        });

        $this->dashboardService->clearDashboardCache((string) $asset->user_id);

        return $asset->fresh();
    }

    /**
     * Fast update for an asset's current market price.
     */
    public function updateCurrentPrice(AssetAccount $asset, float $currentPrice): AssetAccount
    {
        if ($currentPrice < 0) {
            throw new InvalidArgumentException('Current price must be non-negative.');
        }

        $asset->update([
            'current_price' => $currentPrice,
            'last_price_update' => now(),
        ]);

        $this->dashboardService->clearDashboardCache((string) $asset->user_id);

        return $asset->fresh();
    }

    /**
     * Record an asset purchase transaction (Buy).
     * Cash decreases (debit on source account), asset quantity increases.
     *
     * @param  array<string, mixed>  $data
     */
    public function recordPurchase(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $asset = AssetAccount::where('id', $data['asset_account_id'])
                ->where('user_id', $data['user_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $sourceAccount = Account::where('id', $data['source_account_id'])
                ->where('user_id', $data['user_id'])
                ->firstOrFail();

            $qty = (float) $data['quantity'];
            $pricePerUnit = (float) $data['price_per_unit'];
            $fees = isset($data['fees']) ? (int) $data['fees'] : 0;

            if ($qty <= 0 || $pricePerUnit <= 0) {
                throw new InvalidArgumentException('Quantity and price per unit must be greater than zero.');
            }

            $multiplier = $asset->type->multiplier();
            $grossAmount = (int) round($qty * $pricePerUnit * $multiplier);
            $totalCost = $grossAmount + $fees;

            // Weighted average purchase price recalculation
            $oldQty = (float) $asset->quantity;
            $oldAvg = (float) $asset->avg_purchase_price;
            $newQty = $oldQty + $qty;

            $newAvg = $newQty > 0
                ? (($oldQty * $oldAvg) + ($qty * $pricePerUnit)) / $newQty
                : $pricePerUnit;

            $asset->update([
                'quantity' => $newQty,
                'avg_purchase_price' => $newAvg,
                'current_price' => (float) $asset->current_price > 0 ? $asset->current_price : $pricePerUnit,
                'last_price_update' => $asset->last_price_update ?? now(),
            ]);

            $tickerOrName = $asset->ticker ? "{$asset->ticker} ({$asset->name})" : $asset->name;
            $description = $data['description'] ?? "Buy {$qty} {$tickerOrName}";

            $transaction = Transaction::create([
                'user_id' => $data['user_id'],
                'type' => TransactionType::ASSET_PURCHASE,
                'date' => $data['date'] ?? now()->toDateString(),
                'amount' => $totalCost,
                'currency' => $asset->currency,
                'description' => $description,
                'notes' => $data['notes'] ?? null,
                'source' => $data['source'] ?? 'manual',
            ]);

            // Cash account decreases (debit)
            $transaction->entries()->create([
                'account_id' => $sourceAccount->id,
                'entry_type' => EntryType::DEBIT,
                'amount' => $totalCost,
            ]);

            // Asset account increases (credit)
            $transaction->entries()->create([
                'asset_account_id' => $asset->id,
                'entry_type' => EntryType::CREDIT,
                'amount' => $totalCost,
            ]);

            AssetTransaction::create([
                'asset_account_id' => $asset->id,
                'transaction_id' => $transaction->id,
                'type' => 'buy',
                'quantity' => $qty,
                'price_per_unit' => $pricePerUnit,
                'total_amount' => $totalCost,
                'fees' => $fees,
                'created_at' => now(),
            ]);

            $this->accountService->recalculateBalance($sourceAccount);
            $this->dashboardService->clearDashboardCache((string) $data['user_id']);

            return $transaction;
        });
    }

    /**
     * Record an asset sale transaction (Sell).
     * Asset quantity decreases, cash increases (credit on destination account).
     *
     * @param  array<string, mixed>  $data
     */
    public function recordSale(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $asset = AssetAccount::where('id', $data['asset_account_id'])
                ->where('user_id', $data['user_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $destAccount = Account::where('id', $data['destination_account_id'])
                ->where('user_id', $data['user_id'])
                ->firstOrFail();

            $qty = (float) $data['quantity'];
            $pricePerUnit = (float) $data['price_per_unit'];
            $fees = isset($data['fees']) ? (int) $data['fees'] : 0;

            if ($qty <= 0 || $pricePerUnit <= 0) {
                throw new InvalidArgumentException('Quantity and price per unit must be greater than zero.');
            }

            if ($qty > (float) $asset->quantity) {
                throw new InvalidArgumentException('Cannot sell more than the currently held quantity.');
            }

            $multiplier = $asset->type->multiplier();
            $grossProceeds = (int) round($qty * $pricePerUnit * $multiplier);
            $netProceeds = max(0, $grossProceeds - $fees);

            $oldQty = (float) $asset->quantity;
            $newQty = max(0, $oldQty - $qty);

            // Average cost basis remains unchanged on sell
            $asset->update([
                'quantity' => $newQty,
                'current_price' => $pricePerUnit,
                'last_price_update' => now(),
            ]);

            $tickerOrName = $asset->ticker ? "{$asset->ticker} ({$asset->name})" : $asset->name;
            $description = $data['description'] ?? "Sell {$qty} {$tickerOrName}";

            $transaction = Transaction::create([
                'user_id' => $data['user_id'],
                'type' => TransactionType::ASSET_SALE,
                'date' => $data['date'] ?? now()->toDateString(),
                'amount' => $netProceeds,
                'currency' => $asset->currency,
                'description' => $description,
                'notes' => $data['notes'] ?? null,
                'source' => $data['source'] ?? 'manual',
            ]);

            // Asset decreases (debit)
            $transaction->entries()->create([
                'asset_account_id' => $asset->id,
                'entry_type' => EntryType::DEBIT,
                'amount' => $netProceeds,
            ]);

            // Cash account increases (credit)
            $transaction->entries()->create([
                'account_id' => $destAccount->id,
                'entry_type' => EntryType::CREDIT,
                'amount' => $netProceeds,
            ]);

            AssetTransaction::create([
                'asset_account_id' => $asset->id,
                'transaction_id' => $transaction->id,
                'type' => 'sell',
                'quantity' => $qty,
                'price_per_unit' => $pricePerUnit,
                'total_amount' => $netProceeds,
                'fees' => $fees,
                'created_at' => now(),
            ]);

            $this->accountService->recalculateBalance($destAccount);
            $this->dashboardService->clearDashboardCache((string) $data['user_id']);

            return $transaction;
        });
    }

    /**
     * Soft delete an asset account.
     */
    public function deleteAsset(AssetAccount $asset): void
    {
        $userId = $asset->user_id;
        $asset->delete();
        $this->dashboardService->clearDashboardCache((string) $userId);
    }
}
