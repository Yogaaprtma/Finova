<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Services\AccountService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class VerifyBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:verify-balances {--fix : Automatically fix discrepant balances}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify all stored account balances against calculated transaction entries';

    /**
     * Execute the console command.
     */
    public function handle(AccountService $accountService): int
    {
        $shouldFix = (bool) $this->option('fix');
        $this->info('Starting balance verification across all accounts...');

        $checked = 0;
        $discrepancies = 0;
        $discrepancyRows = [];

        foreach (Account::cursor() as $account) {
            $checked++;
            $calculated = $accountService->calculateCurrentBalance($account);
            $stored = (int) $account->current_balance;

            if ($stored !== $calculated) {
                $discrepancies++;
                $diff = $stored - $calculated;

                $discrepancyRows[] = [
                    'id' => substr($account->id, 0, 8).'...',
                    'name' => $account->name,
                    'stored' => number_format($stored, 0, ',', '.'),
                    'calculated' => number_format($calculated, 0, ',', '.'),
                    'difference' => ($diff > 0 ? '+' : '').number_format($diff, 0, ',', '.'),
                    'status' => $shouldFix ? 'Fixed' : 'Discrepancy',
                ];

                Log::warning("Balance discrepancy on Account {$account->id} ({$account->name}): Stored={$stored}, Calculated={$calculated}");

                if ($shouldFix) {
                    $accountService->recalculateBalance($account);
                }
            }
        }

        if ($discrepancies > 0) {
            $this->table(
                ['Account ID', 'Account Name', 'Stored Balance', 'Calculated Balance', 'Difference', 'Action'],
                $discrepancyRows
            );

            $this->warn("Checked {$checked} account(s). Found {$discrepancies} discrepancy/discrepancies.");

            return $shouldFix ? Command::SUCCESS : Command::FAILURE;
        }

        $this->info("Verification complete: Checked {$checked} account(s). No discrepancies found. All balances are consistent!");

        return Command::SUCCESS;
    }
}
