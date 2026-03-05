<?php
namespace App\Services;

use App\Models\Car;
use App\Models\Dealer;
use App\Models\Payment;
use App\Models\PaymentCar;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    public function createPayment(Dealer $dealer, $car): Payment
    {
        return DB::transaction(function () use ($dealer, $car) {
            $cars = Car::whereIn('car_slug', $car_slugs)
                ->where('dealer_slug', $dealer->dealer_slug)
                ->where('status', 'pending')
                ->get();

            if ($cars->isEmpty()) {
                throw new \Exception('No valid cars found for payment');
            }

            $totalAmount = $this->calculateAmount($cars->count(), $durationDays);

            $payment = Payment::create([
                'payment_slug'   => Str::uuid(),
                'dealer_id'      => $dealer->id,
                'payment_type'   => $cars->count() > 1 ? 'cart' : 'single',
                'amount'         => $totalAmount,
                'payment_method' => 'momo',
                'status'         => 'pending',
                'duration_days'  => $durationDays,
            ]);

            return $payment;
        });
    }

    public function processPayment(Payment $payment, string $transactionId): bool
    {
        return DB::transaction(function () use ($payment, $transactionId) {
            $payment->update([
                'status'         => 'completed',
                'transaction_id' => $transactionId,
            ]);

            if ($payment->payment_type === 'subscription') {
                $subscription = Subscription::find($payment->subscription_id);
                if ($subscription) {
                    $subscription->update([
                        'status'          => 'active',
                        'starts_at'       => now(),
                        'ends_at'         => now()->addDays($payment->duration_days),
                        'last_payment_id' => $payment->id,
                    ]);
                }
            } else {
                $carService = new CarService();
                foreach ($payment->paymentCars as $paymentCar) {
                    $carService->activateCar($paymentCar->car, $payment->duration_days);
                }
            }

            return true;
        });
    }

    protected function calculateAmount(int $carCount, int $durationDays): float
    {
                                                       // Pricing logic: adjust as needed
        $basePrice = 50;                               // Base price per car
        $dailyRate = $durationDays === 90 ? 0.5 : 1.0; // 90 days is cheaper per day

        return $carCount * $basePrice * ($durationDays * $dailyRate);
    }

    public function getPaymentSummary(Dealer $dealer): array
    {
        $pendingCars = Car::where('dealer_id', $dealer->id)
            ->where('status', 'pending')
            ->with(['brand', 'model'])
            ->get();

        return [
            'cars'                     => $pendingCars,
            'total_cars'               => $pendingCars->count(),
            'estimated_amount_30_days' => $this->calculateAmount($pendingCars->count(), 30),
            'estimated_amount_90_days' => $this->calculateAmount($pendingCars->count(), 90),
        ];
    }
}
