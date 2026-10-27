<?php

namespace App\Console\Commands;

use App\Models\NetWorthSnapshot;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Console\Command;

class SnapshotNetWorth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:snapshot-net-worth';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Take a daily snapshot of users net worth, cash, investments, and liabilities';

    /**
     * Execute the console command.
     */
    public function handle(DashboardService $dashboardService): int
    {
        $today = now()->toDateString();
        $this->info("Taking daily net worth snapshot for {$today}...");

        $count = 0;

        /** @var User $user */
        foreach (User::cursor() as $user) {
            $data = $dashboardService->getNetWorthData((string) $user->id);

            NetWorthSnapshot::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'date' => $today,
                ],
                [
                    'total_cash' => $data['total_cash'],
                    'total_investments' => $data['total_investments'],
                    'total_liabilities' => $data['total_liabilities'],
                    'net_worth' => $data['net_worth'],
                ]
            );

            $count++;
        }

        $this->info("Successfully created/updated net worth snapshots for {$count} users.");

        return Command::SUCCESS;
    }
}
