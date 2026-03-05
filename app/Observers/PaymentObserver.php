<?php

namespace App\Observers;

use App\Models\Payment;

class PaymentObserver
{
    /**
     * Subscription and SubscriptionArchive are created in PaymentService::processPayment
     * when payment callback is received. No action needed on created/updated.
     */
    public function created(Payment $payment): void
    {
        // Handled in PaymentService::processPayment on callback
    }

    public function updated(Payment $payment): void
    {
        // Handled in PaymentService::processPayment on callback
    }
}
