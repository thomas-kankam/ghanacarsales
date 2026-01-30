<?php

namespace App\Jobs;

use App\Models\Car;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendExpiryReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Cars expiring in 3 days
        $cars = Car::where('status', 'active')
            ->whereBetween('expires_at', [now()->addDays(3), now()->addDays(3)->addHour()])
            ->with('seller')
            ->get();

        foreach ($cars as $car) {
            try {
                $this->sendReminder($car);
            } catch (\Exception $e) {
                Log::error("Failed to send expiry reminder for car {$car->id}: " . $e->getMessage());
            }
        }
    }

    protected function sendReminder(Car $car): void
    {
        // Send SMS
        $message = "Your car listing expires in 3 days. Renew now to keep it active.";
        Log::info("SMS to {$car->seller->mobile_number}: {$message}");

        // Send email if available
        if ($car->seller->email) {
            Mail::raw($message, function ($message) use ($car) {
                $message->to($car->seller->email)
                    ->subject('Car Listing Expiring Soon - Ghana Car Sales');
            });
        }
    }
}
