<?php

use App\Console\Commands\GenerateRecurringTransactions;
use App\Console\Commands\SnapshotNetWorth;
use App\Console\Commands\VerifyBalances;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(SnapshotNetWorth::class)->dailyAt('00:00');
Schedule::command(GenerateRecurringTransactions::class)->dailyAt('00:05');
Schedule::command(VerifyBalances::class)->dailyAt('01:00');
