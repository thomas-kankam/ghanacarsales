<?php

namespace App\Console;

use App\Jobs\SendExpiryReminder;
use App\Services\CarService;
use App\Traits\AppNotifications;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    use AppNotifications;
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Run inline so cron does not depend on a queue worker or Redis (avoids Connection refused on shared hosts).
        $schedule->call(fn () => app(CarService::class)->markExpiredCars())
            ->name('expire-cars')
            ->daily();

        $schedule->call(function () {
            $count = app(CarService::class)->deleteExpiredCars();
            Log::info("Deleted {$count} expired cars");
        })
            ->name('delete-expired-cars')
            ->daily();

        $schedule->call(static function (): void {
            (new SendExpiryReminder)->handle();
        })
            ->name('send-expiry-reminders')
            ->daily();

        // send a test sms to 0556906969 to see if cron is working
        $schedule->call(static function (): void {
            self::sendSms('0556906969', 'This is a test SMS from OmniCarsGH');
        })
            ->name('send-test-sms')
            ->daily()
            ->withoutOverlapping();
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
