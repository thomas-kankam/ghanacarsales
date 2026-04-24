<?php

namespace App\Jobs;

use App\Models\Car;
use App\Traits\AppNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendExpiryReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, AppNotifications;

    public function handle(): void
    {
        $threeDaysFromNow = now()->addDays(3);
        $cars = Car::where('status', 'published')
            ->whereBetween('expiry_date', [$threeDaysFromNow->copy()->startOfDay(), $threeDaysFromNow->copy()->endOfDay()])
            ->with('dealer')
            ->get();
        Log::info("Found {$cars->count()} cars to send expiry reminder");
        foreach ($cars as $car) {
            $dealerName = $car->dealer?->full_name ?? $car->dealer?->business_name ?? 'Unknown dealer';
            Log::info("Car to send expiry reminder: {$car->car_slug} - {$dealerName}");
        }

        foreach ($cars as $car) {
            try {
                $this->sendReminder($car);
            } catch (\Exception $e) {
                Log::error("Failed to send expiry reminder for car {$car->car_slug}: " . $e->getMessage());
            }
        }
    }

    protected function sendReminder(Car $car): void
    {
        $message = "Your car listing expires in 3 days. Renew now to keep it active.";
        $dealer = $car->dealer;
        if (!$dealer) {
            return;
        }
        if ($dealer->phone_number) {
            Log::info("SMS to {$dealer->phone_number}: {$message}");
            // TODO: Integrate SMS gateway (e.g. Twilio)
            self::sendSms($dealer->phone_number, $message);

        }
        if ($dealer->email) {
            // Mail::raw($message, function ($m) use ($dealer) {
            //     $m->to($dealer->email)
            //         ->subject('Car Listing Expiring Soon - Ghana Car Sales');
            // });

            self::sendEmail($dealer->email, email_class: "App\Mail\ExpiryReminderEmail", parameters: [
                $dealer->email,
                $message,
            ]);
        }
    }
}
