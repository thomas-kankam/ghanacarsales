<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Mark expired cars daily
        $schedule->job(new \App\Jobs\ExpireCars)->daily();

        // Delete expired cars (5 days after expiry) daily
        $schedule->job(new \App\Jobs\DeleteExpiredCars)->daily();

        // Send expiry reminders (3 days before expiry) daily
        $schedule->job(new \App\Jobs\SendExpiryReminder)->daily();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
