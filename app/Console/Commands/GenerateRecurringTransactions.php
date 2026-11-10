<?php

namespace App\Console\Commands;

use App\Services\RecurringTransactionService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateRecurringTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recurring:generate {--date= : Target date to evaluate recurring transactions (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate transactions from active recurring templates that are due';

    /**
     * Execute the console command.
     */
    public function handle(RecurringTransactionService $service): int
    {
        $dateOption = $this->option('date');
        $targetDate = $dateOption ? Carbon::parse($dateOption) : Carbon::today();

        $this->info("Evaluating recurring transactions for date: {$targetDate->format('Y-m-d')}...");

        $count = $service->generateDueTransactions($targetDate);

        $this->info("Successfully generated {$count} transaction(s).");

        return Command::SUCCESS;
    }
}
