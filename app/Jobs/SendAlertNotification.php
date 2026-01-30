<?php

namespace App\Jobs;

use App\Models\AlertNotification;
use App\Models\BuyerAlert;
use App\Models\Car;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAlertNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public BuyerAlert $alert,
        public Car $car
    ) {}

    public function handle(): void
    {
        // Check if notification already sent
        $exists = AlertNotification::where('buyer_alert_id', $this->alert->id)
            ->where('car_id', $this->car->id)
            ->exists();

        if ($exists) {
            return;
        }

        // Create notification record
        $notification = AlertNotification::create([
            'buyer_alert_id' => $this->alert->id,
            'car_id' => $this->car->id,
            'is_sent' => false,
        ]);

        try {
            // Send SMS notification
            $this->sendSms();

            // Send email if available
            if ($this->alert->email) {
                $this->sendEmail();
            }

            $notification->update([
                'is_sent' => true,
                'sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send alert notification: " . $e->getMessage());
            throw $e; // Will trigger retry
        }
    }

    protected function sendSms(): void
    {
        // TODO: Integrate with SMS provider
        $message = "New car matching your alert: {$this->car->brand->name} {$this->car->model->name} - GHS {$this->car->price}";
        Log::info("SMS to {$this->alert->mobile_number}: {$message}");
    }

    protected function sendEmail(): void
    {
        // TODO: Create email template
        Mail::raw("New car matching your alert: {$this->car->brand->name} {$this->car->model->name}", function ($message) {
            $message->to($this->alert->email)
                ->subject('New Car Alert - Ghana Car Sales');
        });
    }
}
