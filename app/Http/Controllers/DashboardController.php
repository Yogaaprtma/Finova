<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboardService) {}

    public function index(Request $request): Response
    {
        $userId = (string) $request->user()->id;
        $period = $request->query('period', 'this_month');

        if (! in_array($period, ['this_month', 'last_month'])) {
            $period = 'this_month';
        }

        $data = $this->dashboardService->getDashboardData($userId, $period);

        return Inertia::render('Dashboard', [
            'netWorth' => $data['net_worth'],
            'cashFlow' => $data['cash_flow'],
            'assetAllocation' => $data['asset_allocation'],
            'recentTransactions' => $data['recent_transactions'],
            'period' => $period,
        ]);
    }
}
