<?php

namespace App\Services;

use App\Enums\AssetType;
use App\Enums\TransactionType;
use App\Models\AssetAccount;
use App\Models\Liability;
use App\Models\NetWorthSnapshot;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    public function __construct(private AccountService $accountService) {}

    /**
     * Get complete cached dashboard data for a user and period.
     *
     * @return array<string, mixed>
     */
    public function getDashboardData(string $userId, string $period = 'this_month'): array
    {
        $cacheKey = "dashboard:{$userId}:{$period}";

        return Cache::remember($cacheKey, 300, function () use ($userId, $period) {
            $netWorth = $this->getNetWorthData($userId);
            $cashFlow = $this->getCashFlowData($userId, $period);
            $assetAllocation = $this->getAssetAllocationData($userId, $netWorth['total_cash']);
            $recentTransactions = $this->getRecentTransactions($userId, 10);

            return [
                'net_worth' => $netWorth,
                'cash_flow' => $cashFlow,
                'asset_allocation' => $assetAllocation,
                'recent_transactions' => $recentTransactions,
            ];
        });
    }

    /**
     * Clear cached dashboard data for a user.
     */
    public function clearDashboardCache(string $userId): void
    {
        Cache::forget("dashboard:{$userId}:this_month");
        Cache::forget("dashboard:{$userId}:last_month");
    }

    /**
     * Calculate net worth and breakdown.
     *
     * @return array<string, mixed>
     */
    public function getNetWorthData(string $userId): array
    {
        $totalCash = $this->accountService->getTotalCash($userId);

        $assets = AssetAccount::where('user_id', $userId)
            ->where('is_active', true)
            ->get();

        $totalInvestments = 0;
        foreach ($assets as $asset) {
            if ($asset->manual_value !== null) {
                $totalInvestments += (int) $asset->manual_value;
            } else {
                $totalInvestments += (int) round((float) $asset->quantity * (float) $asset->current_price);
            }
        }

        $totalLiabilities = (int) Liability::where('user_id', $userId)
            ->where('is_active', true)
            ->sum('current_balance');

        $totalAssets = $totalCash + $totalInvestments;
        $netWorth = $totalAssets - $totalLiabilities;

        // Change vs last month
        $previousSnapshot = NetWorthSnapshot::where('user_id', $userId)
            ->where('date', '<=', now()->subDays(25)->toDateString())
            ->latest('date')
            ->first();

        if (! $previousSnapshot) {
            $previousSnapshot = NetWorthSnapshot::where('user_id', $userId)
                ->where('date', '<', now()->toDateString())
                ->oldest('date')
                ->first();
        }

        $changeAmount = 0;
        $changePercentage = 0.0;
        $isPositiveChange = true;

        if ($previousSnapshot) {
            $prevNetWorth = (int) $previousSnapshot->net_worth;
            if ($prevNetWorth !== 0) {
                $changeAmount = $netWorth - $prevNetWorth;
                $changePercentage = round(($changeAmount / abs($prevNetWorth)) * 100, 1);
                $isPositiveChange = $changeAmount >= 0;
            } elseif ($netWorth > 0) {
                $changeAmount = $netWorth;
                $changePercentage = 100.0;
                $isPositiveChange = true;
            }
        }

        return [
            'net_worth' => $netWorth,
            'total_assets' => $totalAssets,
            'total_cash' => $totalCash,
            'total_investments' => $totalInvestments,
            'total_liabilities' => $totalLiabilities,
            'change_amount' => $changeAmount,
            'change_percentage' => $changePercentage,
            'is_positive_change' => $isPositiveChange,
        ];
    }

    /**
     * Get cash flow metrics and monthly history chart data.
     *
     * @return array<string, mixed>
     */
    public function getCashFlowData(string $userId, string $period = 'this_month'): array
    {
        if ($period === 'last_month') {
            $startDate = now()->subMonth()->startOfMonth()->toDateString();
            $endDate = now()->subMonth()->endOfMonth()->toDateString();
            $periodLabel = now()->subMonth()->format('F Y');
        } else {
            $startDate = now()->startOfMonth()->toDateString();
            $endDate = now()->endOfMonth()->toDateString();
            $periodLabel = now()->format('F Y');
        }

        $income = (int) Transaction::where('user_id', $userId)
            ->where('type', TransactionType::INCOME)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        $expense = (int) Transaction::where('user_id', $userId)
            ->where('type', TransactionType::EXPENSE)
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        $net = $income - $expense;
        $savingsRate = $income > 0 ? max(0, min(100, round(($net / $income) * 100, 1))) : 0.0;

        // 6-month historical chart data
        $chartData = $this->getSixMonthHistory($userId);

        return [
            'period' => $period,
            'period_label' => $periodLabel,
            'income' => $income,
            'expense' => $expense,
            'net' => $net,
            'savings_rate' => $savingsRate,
            'chart' => $chartData,
        ];
    }

    /**
     * Get 6-month historical cash flow data for bar chart.
     *
     * @return array<string, mixed>
     */
    public function getSixMonthHistory(string $userId): array
    {
        $months = [];
        $incomeData = [];
        $expenseData = [];
        $netData = [];

        for ($i = 5; $i >= 0; $i--) {
            $target = now()->subMonths($i);
            $start = $target->copy()->startOfMonth()->toDateString();
            $end = $target->copy()->endOfMonth()->toDateString();

            $inc = (int) Transaction::where('user_id', $userId)
                ->where('type', TransactionType::INCOME)
                ->whereBetween('date', [$start, $end])
                ->sum('amount');

            $exp = (int) Transaction::where('user_id', $userId)
                ->where('type', TransactionType::EXPENSE)
                ->whereBetween('date', [$start, $end])
                ->sum('amount');

            $months[] = $target->format('M Y');
            $incomeData[] = $inc;
            $expenseData[] = $exp;
            $netData[] = $inc - $exp;
        }

        return [
            'labels' => $months,
            'income' => $incomeData,
            'expense' => $expenseData,
            'net' => $netData,
        ];
    }

    /**
     * Get asset allocation breakdown for donut chart.
     *
     * @return array<string, mixed>
     */
    public function getAssetAllocationData(string $userId, int $totalCash): array
    {
        $assets = AssetAccount::where('user_id', $userId)
            ->where('is_active', true)
            ->get();

        $stocks = 0;
        $mutualFunds = 0;
        $crypto = 0;
        $gold = 0;
        $property = 0;
        $other = 0;

        foreach ($assets as $asset) {
            $value = $asset->manual_value !== null
                ? (int) $asset->manual_value
                : (int) round((float) $asset->quantity * (float) $asset->current_price);

            match ($asset->type) {
                AssetType::STOCK => $stocks += $value,
                AssetType::MUTUAL_FUND => $mutualFunds += $value,
                AssetType::CRYPTO => $crypto += $value,
                AssetType::GOLD => $gold += $value,
                AssetType::PROPERTY, AssetType::VEHICLE => $property += $value,
                default => $other += $value,
            };
        }

        $categories = [
            ['label' => 'Cash & Bank', 'value' => $totalCash, 'color' => '#10B981'],
            ['label' => 'Stocks', 'value' => $stocks, 'color' => '#3B82F6'],
            ['label' => 'Mutual Funds', 'value' => $mutualFunds, 'color' => '#8B5CF6'],
            ['label' => 'Crypto', 'value' => $crypto, 'color' => '#F59E0B'],
            ['label' => 'Gold & Precious Metals', 'value' => $gold, 'color' => '#EAB308'],
            ['label' => 'Property & Vehicles', 'value' => $property, 'color' => '#EC4899'],
            ['label' => 'Other', 'value' => $other, 'color' => '#64748B'],
        ];

        // Filter out zero categories unless all are 0
        $nonZero = array_values(array_filter($categories, fn ($cat) => $cat['value'] > 0));
        $totalVal = array_sum(array_column($categories, 'value'));

        if ($totalVal === 0) {
            return [
                'labels' => ['No Assets'],
                'values' => [1],
                'colors' => ['#334155'],
                'items' => [],
                'total' => 0,
            ];
        }

        $items = array_map(function ($cat) use ($totalVal) {
            $cat['percentage'] = $totalVal > 0 ? round(($cat['value'] / $totalVal) * 100, 1) : 0;

            return $cat;
        }, $nonZero);

        return [
            'labels' => array_column($items, 'label'),
            'values' => array_column($items, 'value'),
            'colors' => array_column($items, 'color'),
            'items' => $items,
            'total' => $totalVal,
        ];
    }

    /**
     * Get recent transactions with relationships.
     */
    public function getRecentTransactions(string $userId, int $limit = 10): Collection
    {
        return Transaction::where('user_id', $userId)
            ->with(['category', 'entries.account'])
            ->latest('date')
            ->latest('id')
            ->limit($limit)
            ->get();
    }
}
