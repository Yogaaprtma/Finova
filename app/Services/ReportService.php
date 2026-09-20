<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\NetWorthSnapshot;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    public function __construct(
        private AssetService $assetService,
        private DashboardService $dashboardService,
    ) {}

    /**
     * Parse date range string or custom dates into Carbon instances.
     *
     * @return array{start_date: Carbon, end_date: Carbon, period: string, label: string}
     */
    public function parsePeriod(?string $period, ?string $customStart = null, ?string $customEnd = null): array
    {
        $period = $period ?: 'last_6_months';
        $now = Carbon::now();

        switch ($period) {
            case 'this_month':
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfMonth();
                $label = 'This Month (' . $now->translatedFormat('F Y') . ')';
                break;

            case 'last_month':
                $start = $now->copy()->subMonth()->startOfMonth();
                $end = $now->copy()->subMonth()->endOfMonth();
                $label = 'Last Month (' . $start->translatedFormat('F Y') . ')';
                break;

            case 'last_3_months':
                $start = $now->copy()->subMonths(2)->startOfMonth();
                $end = $now->copy()->endOfMonth();
                $label = 'Last 3 Months (' . $start->format('M Y') . ' - ' . $end->format('M Y') . ')';
                break;

            case 'last_6_months':
                $start = $now->copy()->subMonths(5)->startOfMonth();
                $end = $now->copy()->endOfMonth();
                $label = 'Last 6 Months (' . $start->format('M Y') . ' - ' . $end->format('M Y') . ')';
                break;

            case 'ytd':
                $start = $now->copy()->startOfYear();
                $end = $now->copy()->endOfMonth();
                $label = 'Year to Date (' . $now->format('Y') . ')';
                break;

            case 'last_year':
                $start = $now->copy()->subYear()->startOfYear();
                $end = $now->copy()->subYear()->endOfYear();
                $label = 'Last Year (' . $start->format('Y') . ')';
                break;

            case 'all_time':
                $start = Carbon::parse('2020-01-01')->startOfDay();
                $end = $now->copy()->endOfMonth();
                $label = 'All Time';
                break;

            case 'custom':
                $start = $customStart ? Carbon::parse($customStart)->startOfDay() : $now->copy()->startOfMonth();
                $end = $customEnd ? Carbon::parse($customEnd)->endOfDay() : $now->copy()->endOfMonth();
                $label = $start->format('d M Y') . ' - ' . $end->format('d M Y');
                break;

            default:
                $start = $now->copy()->subMonths(5)->startOfMonth();
                $end = $now->copy()->endOfMonth();
                $label = 'Last 6 Months';
                $period = 'last_6_months';
                break;
        }

        return [
            'start_date' => $start,
            'end_date' => $end,
            'period' => $period,
            'label' => $label,
        ];
    }

    /**
     * Generate Monthly Income vs Expense Report.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function getIncomeExpenseReport(string|int $userId, array $params = []): array
    {
        $periodData = $this->parsePeriod($params['period'] ?? 'last_6_months', $params['start_date'] ?? null, $params['end_date'] ?? null);
        $startDate = $periodData['start_date'];
        $endDate = $periodData['end_date'];
        $accountId = ! empty($params['account_id']) ? $params['account_id'] : null;

        $transactionsQuery = Transaction::where('user_id', $userId)
            ->where('date', '>=', $startDate->toDateString())
            ->where('date', '<=', $endDate->toDateString() . ' 23:59:59')
            ->whereIn('type', [TransactionType::INCOME, TransactionType::EXPENSE])
            ->when($accountId, function ($query) use ($accountId) {
                $query->whereHas('entries', function ($q) use ($accountId) {
                    $q->where('account_id', $accountId);
                });
            })
            ->with('category')
            ->orderBy('date');

        $transactions = $transactionsQuery->get();

        // Build continuous month slots between startDate and endDate
        $months = [];
        $cursor = $startDate->copy()->startOfMonth();
        $endCursor = $endDate->copy()->startOfMonth();

        while ($cursor->lte($endCursor)) {
            $key = $cursor->format('Y-m');
            $months[$key] = [
                'month' => $key,
                'label' => $cursor->translatedFormat('M Y'),
                'income' => 0,
                'expense' => 0,
                'net' => 0,
                'savings_rate' => 0.0,
            ];
            $cursor->addMonth();
        }

        $totalIncome = 0;
        $totalExpense = 0;

        foreach ($transactions as $tx) {
            $txDate = Carbon::parse($tx->date);
            $monthKey = $txDate->format('Y-m');

            if (! isset($months[$monthKey])) {
                $months[$monthKey] = [
                    'month' => $monthKey,
                    'label' => $txDate->translatedFormat('M Y'),
                    'income' => 0,
                    'expense' => 0,
                    'net' => 0,
                    'savings_rate' => 0.0,
                ];
            }

            $amount = (int) $tx->amount;

            if ($tx->type === TransactionType::INCOME) {
                $months[$monthKey]['income'] += $amount;
                $totalIncome += $amount;
            } elseif ($tx->type === TransactionType::EXPENSE) {
                $months[$monthKey]['expense'] += $amount;
                $totalExpense += $amount;
            }
        }

        // Calculate net and savings rate for each month
        foreach ($months as &$m) {
            $m['net'] = $m['income'] - $m['expense'];
            $m['savings_rate'] = $m['income'] > 0
                ? round(($m['net'] / $m['income']) * 100, 1)
                : 0.0;
        }
        unset($m);

        $monthList = array_values($months);
        $monthCount = max(1, count($monthList));
        $netSavings = $totalIncome - $totalExpense;
        $overallSavingsRate = $totalIncome > 0 ? round(($netSavings / $totalIncome) * 100, 1) : 0.0;
        $avgMonthlyExpense = (int) round($totalExpense / $monthCount);
        $avgMonthlyIncome = (int) round($totalIncome / $monthCount);

        return [
            'period' => $periodData,
            'summary' => [
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'net_savings' => $netSavings,
                'savings_rate' => $overallSavingsRate,
                'avg_monthly_income' => $avgMonthlyIncome,
                'avg_monthly_expense' => $avgMonthlyExpense,
                'month_count' => $monthCount,
            ],
            'chart' => [
                'labels' => array_column($monthList, 'label'),
                'income' => array_column($monthList, 'income'),
                'expense' => array_column($monthList, 'expense'),
                'net' => array_column($monthList, 'net'),
            ],
            'table' => array_reverse($monthList), // latest first
        ];
    }

    /**
     * Generate Cash Flow Report (Inflows vs Outflows over time).
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function getCashFlowReport(string|int $userId, array $params = []): array
    {
        $periodData = $this->parsePeriod($params['period'] ?? 'last_6_months', $params['start_date'] ?? null, $params['end_date'] ?? null);
        $startDate = $periodData['start_date'];
        $endDate = $periodData['end_date'];
        $accountId = ! empty($params['account_id']) ? $params['account_id'] : null;

        // Cash flow focuses on actual cash inflow vs outflow (Income vs Expense)
        $transactions = Transaction::where('user_id', $userId)
            ->where('date', '>=', $startDate->toDateString())
            ->where('date', '<=', $endDate->toDateString() . ' 23:59:59')
            ->whereIn('type', [TransactionType::INCOME, TransactionType::EXPENSE])
            ->when($accountId, function ($query) use ($accountId) {
                $query->whereHas('entries', function ($q) use ($accountId) {
                    $q->where('account_id', $accountId);
                });
            })
            ->orderBy('date')
            ->get();

        // Group by month
        $months = [];
        $cursor = $startDate->copy()->startOfMonth();
        $endCursor = $endDate->copy()->startOfMonth();

        while ($cursor->lte($endCursor)) {
            $key = $cursor->format('Y-m');
            $months[$key] = [
                'month' => $key,
                'label' => $cursor->translatedFormat('M Y'),
                'inflow' => 0,
                'outflow' => 0,
                'net_flow' => 0,
                'cumulative_flow' => 0,
            ];
            $cursor->addMonth();
        }

        $totalInflow = 0;
        $totalOutflow = 0;

        foreach ($transactions as $tx) {
            $monthKey = Carbon::parse($tx->date)->format('Y-m');
            if (! isset($months[$monthKey])) {
                continue;
            }

            $amount = (int) $tx->amount;
            if ($tx->type === TransactionType::INCOME) {
                $months[$monthKey]['inflow'] += $amount;
                $totalInflow += $amount;
            } elseif ($tx->type === TransactionType::EXPENSE) {
                $months[$monthKey]['outflow'] += $amount;
                $totalOutflow += $amount;
            }
        }

        $cumulative = 0;
        foreach ($months as &$m) {
            $m['net_flow'] = $m['inflow'] - $m['outflow'];
            $cumulative += $m['net_flow'];
            $m['cumulative_flow'] = $cumulative;
        }
        unset($m);

        $monthList = array_values($months);

        return [
            'period' => $periodData,
            'summary' => [
                'total_inflow' => $totalInflow,
                'total_outflow' => $totalOutflow,
                'net_cash_flow' => $totalInflow - $totalOutflow,
                'cumulative_flow' => $cumulative,
            ],
            'chart' => [
                'labels' => array_column($monthList, 'label'),
                'inflow' => array_column($monthList, 'inflow'),
                'outflow' => array_column($monthList, 'outflow'),
                'net_flow' => array_column($monthList, 'net_flow'),
                'cumulative_flow' => array_column($monthList, 'cumulative_flow'),
            ],
            'table' => array_reverse($monthList),
        ];
    }

    /**
     * Generate Expense by Category Report.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function getExpenseByCategoryReport(string|int $userId, array $params = []): array
    {
        $periodData = $this->parsePeriod($params['period'] ?? 'this_month', $params['start_date'] ?? null, $params['end_date'] ?? null);
        $startDate = $periodData['start_date'];
        $endDate = $periodData['end_date'];
        $accountId = ! empty($params['account_id']) ? $params['account_id'] : null;

        $transactions = Transaction::where('user_id', $userId)
            ->where('date', '>=', $startDate->toDateString())
            ->where('date', '<=', $endDate->toDateString() . ' 23:59:59')
            ->where('type', TransactionType::EXPENSE)
            ->when($accountId, function ($query) use ($accountId) {
                $query->whereHas('entries', function ($q) use ($accountId) {
                    $q->where('account_id', $accountId);
                });
            })
            ->with('category')
            ->get();

        $totalExpense = (int) $transactions->sum('amount');

        $categories = [];
        foreach ($transactions as $tx) {
            $cat = $tx->category;
            $catId = $cat ? $cat->id : 'uncategorized';
            $catName = $cat ? $cat->name : 'Uncategorized';
            $catColor = $cat ? $cat->color : '#94A3B8';
            $catIcon = $cat ? $cat->icon : 'circle';

            if (! isset($categories[$catId])) {
                $categories[$catId] = [
                    'id' => $catId,
                    'name' => $catName,
                    'color' => $catColor,
                    'icon' => $catIcon,
                    'amount' => 0,
                    'count' => 0,
                    'percentage' => 0.0,
                ];
            }

            $categories[$catId]['amount'] += (int) $tx->amount;
            $categories[$catId]['count']++;
        }

        $items = array_values($categories);
        usort($items, fn ($a, $b) => $b['amount'] <=> $a['amount']);

        foreach ($items as &$item) {
            $item['percentage'] = $totalExpense > 0
                ? round(($item['amount'] / $totalExpense) * 100, 1)
                : 0.0;
        }
        unset($item);

        return [
            'period' => $periodData,
            'summary' => [
                'total_expense' => $totalExpense,
                'categories_count' => count($items),
                'transactions_count' => $transactions->count(),
            ],
            'chart' => [
                'labels' => array_column($items, 'name'),
                'values' => array_column($items, 'amount'),
                'colors' => array_column($items, 'color'),
                'total' => $totalExpense,
            ],
            'table' => $items,
        ];
    }

    /**
     * Generate Net Worth Trend Report (using NetWorthSnapshot and real-time state).
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function getNetWorthTrendReport(string|int $userId, array $params = []): array
    {
        $periodData = $this->parsePeriod($params['period'] ?? 'last_6_months', $params['start_date'] ?? null, $params['end_date'] ?? null);
        $startDate = $periodData['start_date'];
        $endDate = $periodData['end_date'];

        $snapshots = NetWorthSnapshot::where('user_id', $userId)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('date')
            ->get();

        // Get current live net worth from DashboardService
        $liveNetWorth = $this->dashboardService->getNetWorthData((string) $userId);

        $points = [];
        foreach ($snapshots as $snap) {
            $date = Carbon::parse($snap->date);
            $points[] = [
                'date' => $snap->date,
                'label' => $date->format('d M Y'),
                'net_worth' => (int) $snap->net_worth,
                'total_cash' => (int) $snap->total_cash,
                'total_investments' => (int) $snap->total_investments,
                'total_liabilities' => (int) $snap->total_liabilities,
                'total_assets' => (int) ($snap->total_cash + $snap->total_investments),
            ];
        }

        // If today is not in snapshots, append live data
        $todayStr = now()->toDateString();
        $hasToday = ! empty($points) && end($points)['date'] === $todayStr;

        if (! $hasToday) {
            $points[] = [
                'date' => $todayStr,
                'label' => now()->format('d M Y') . ' (Today)',
                'net_worth' => (int) $liveNetWorth['net_worth'],
                'total_cash' => (int) $liveNetWorth['total_cash'],
                'total_investments' => (int) $liveNetWorth['total_investments'],
                'total_liabilities' => (int) $liveNetWorth['total_liabilities'],
                'total_assets' => (int) $liveNetWorth['total_assets'],
            ];
        }

        $latestPoint = end($points) ?: [
            'net_worth' => 0,
            'total_cash' => 0,
            'total_investments' => 0,
            'total_liabilities' => 0,
            'total_assets' => 0,
        ];

        $earliestPoint = reset($points) ?: $latestPoint;
        $netWorthGrowth = $latestPoint['net_worth'] - $earliestPoint['net_worth'];
        $growthPct = $earliestPoint['net_worth'] !== 0
            ? round(($netWorthGrowth / abs($earliestPoint['net_worth'])) * 100, 1)
            : 0.0;

        return [
            'period' => $periodData,
            'summary' => [
                'current_net_worth' => $latestPoint['net_worth'],
                'total_assets' => $latestPoint['total_assets'],
                'total_cash' => $latestPoint['total_cash'],
                'total_investments' => $latestPoint['total_investments'],
                'total_liabilities' => $latestPoint['total_liabilities'],
                'net_worth_growth' => $netWorthGrowth,
                'growth_percentage' => $growthPct,
                'points_count' => count($points),
            ],
            'chart' => [
                'labels' => array_column($points, 'label'),
                'net_worth' => array_column($points, 'net_worth'),
                'total_assets' => array_column($points, 'total_assets'),
                'total_cash' => array_column($points, 'total_cash'),
                'total_investments' => array_column($points, 'total_investments'),
                'total_liabilities' => array_column($points, 'total_liabilities'),
            ],
            'table' => array_reverse($points),
        ];
    }

    /**
     * Generate Asset Allocation Report.
     *
     * @return array<string, mixed>
     */
    public function getAssetAllocationReport(string|int $userId): array
    {
        return $this->assetService->getPortfolioOverview($userId);
    }
}
