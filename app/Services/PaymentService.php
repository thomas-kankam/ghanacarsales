<?php

namespace App\Services;

use App\Models\Car;
use App\Models\Payment;
use App\Models\PaymentCar;
use App\Models\Seller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    public function createPayment(Seller $seller, array $carIds, int $durationDays = 30): Payment
    {
        return DB::transaction(function () use ($seller, $carIds, $durationDays) {
            $cars = Car::whereIn('id', $carIds)
                ->where('seller_id', $seller->id)
                ->where('status', 'pending')
                ->get();

            if ($cars->isEmpty()) {
                throw new \Exception('No valid cars found for payment');
            }

            $totalAmount = $this->calculateAmount($cars->count(), $durationDays);

            $payment = Payment::create([
                'payment_slug' => Str::uuid(),
                'seller_id' => $seller->id,
                'payment_type' => $cars->count() > 1 ? 'cart' : 'single',
                'amount' => $totalAmount,
                'payment_method' => 'momo',
                'status' => 'pending',
                'duration_days' => $durationDays,
            ]);

            foreach ($cars as $car) {
                PaymentCar::create([
                    'payment_id' => $payment->id,
                    'car_id' => $car->id,
                ]);
            }

            return $payment->load('paymentCars.car');
        });
    }

    public function processPayment(Payment $payment, string $transactionId): bool
    {
        return DB::transaction(function () use ($payment, $transactionId) {
            $payment->update([
                'status' => 'completed',
                'transaction_id' => $transactionId,
            ]);

            $carService = new CarService();
            foreach ($payment->paymentCars as $paymentCar) {
                $carService->activateCar($paymentCar->car, $payment->duration_days);
            }

            return true;
        });
    }

    protected function calculateAmount(int $carCount, int $durationDays): float
    {
        // Pricing logic: adjust as needed
        $basePrice = 50; // Base price per car
        $dailyRate = $durationDays === 90 ? 0.5 : 1.0; // 90 days is cheaper per day
        
        return $carCount * $basePrice * ($durationDays * $dailyRate);
    }

    public function getPaymentSummary(Seller $seller): array
    {
        $pendingCars = Car::where('seller_id', $seller->id)
            ->where('status', 'pending')
            ->with(['brand', 'model'])
            ->get();

        return [
            'cars' => $pendingCars,
            'total_cars' => $pendingCars->count(),
            'estimated_amount_30_days' => $this->calculateAmount($pendingCars->count(), 30),
            'estimated_amount_90_days' => $this->calculateAmount($pendingCars->count(), 90),
        ];
    }
}
