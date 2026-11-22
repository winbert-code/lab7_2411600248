<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;

/**
 * Laravel 13 normally declares scheduled tasks in routes/console.php.
 * This Kernel keeps the classic, explicit schedule definition used by the lab
 * and is invoked from routes/console.php so there is only one source of truth.
 */
class Kernel
{
    public static function schedule(Schedule $schedule): void
    {
        $schedule->command('replenishment:check-reorder')
            ->dailyAt('01:00')
            ->timezone('Asia/Manila')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/replenishment.log'));
    }
}
