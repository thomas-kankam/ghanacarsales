<?php

namespace App\Observers;

class PaymentObserver
{
     public function created(Payment $payment)
    {
        $reference = $payment->reference_id;
        $user_id   = $payment->user_id;

        // Determine duration in days based on plan_name
        $durationDays = $this->getDurationInDays($payment->plan_name);

        // Calculate end date
        $endDate = $durationDays ? Carbon::parse($payment->created_at)->addDays($durationDays) : null;

        $existing = SubscriptionArchive::query()
            ->where('user_id', $payment->user_id)
            ->where('transaction_reference', $reference)
            ->first();

        if (! $existing) {
            SubscriptionArchive::create([
                'user_id'                => $user_id,
                // 'plan_name' => $payment->plan_name,
                // 'package_name' => $payment->package_name,
                // 'units' => $payment->units,
                // 'price' => $payment->price,
                // 'started_at' => $payment->created_at,
                // 'ended_at' => $payment->expires_at,
                // 'payment_status' => $payment->payment_status,
                // 'transaction_reference' => $payment->transaction_reference
                'subscription_plan_name' => $payment->plan_name,
                'subscription_plan_type' => $payment->package_name,
                // 'duration_type' => 'months', // Assuming duration type is months; adjust as needed
                // 'duration' => 1, // Assuming duration is 1; adjust as needed
                'duration_type'          => 'days',
                'duration'               => $durationDays ?? 0,
                'units'                  => $payment->units,
                'price'                  => $payment->amount,
                'start_at'               => $payment->created_at,
                'end_at'                 => $endDate,
                'payment_status'         => $payment->status,
                'transaction_reference'  => $reference,
            ]);
        } else {

            Log::info("Subscription archive already exists for payment reference: $reference, user_id: $user_id");
        }
    }

    private function getDurationInDays(string $planName): ?int
    {
        if (! $planName) {
            return null;
        }

        $planName = strtolower(trim($planName));

        return match (true) {
            str_contains($planName, '1 month')   => 30,
            str_contains($planName, '2 months')  => 60,
            str_contains($planName, '3 months')  => 90,
            str_contains($planName, 'no expiry') => null,
            default                              => 0, // fallback if not matched
        };
    }
}
