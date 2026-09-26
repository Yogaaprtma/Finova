<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService) {}

    /**
     * Display financial reports with interactive charts and tables.
     */
    public function index(Request $request): Response
    {
        $userId = $request->user()->id;
        $activeType = $request->query('type', 'income_expense');

        $filters = [
            'type' => $activeType,
            'period' => $request->query('period', in_array($activeType, ['expense_category']) ? 'this_month' : 'last_6_months'),
            'start_date' => $request->query('start_date'),
            'end_date' => $request->query('end_date'),
            'account_id' => $request->query('account_id'),
        ];

        $reportData = match ($activeType) {
            'cash_flow' => $this->reportService->getCashFlowReport($userId, $filters),
            'expense_category' => $this->reportService->getExpenseByCategoryReport($userId, $filters),
            'net_worth' => $this->reportService->getNetWorthTrendReport($userId, $filters),
            'asset_allocation' => $this->reportService->getAssetAllocationReport($userId),
            default => $this->reportService->getIncomeExpenseReport($userId, $filters),
        };

        $accounts = Account::where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'current_balance']);

        return Inertia::render('Reports/Index', [
            'activeType' => $activeType,
            'reportData' => $reportData,
            'filters' => $filters,
            'accounts' => $accounts,
        ]);
    }
}

