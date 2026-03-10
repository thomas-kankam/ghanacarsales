<?php
namespace App\Services;

use App\Models\Approval;
use App\Models\Car;
use App\Models\Dealer;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    public function createPayment(
        Dealer $dealer,
        array $carSlugs,
        string $planSlug,
        string $planName,
        float $plan_price,
        ?string $phoneNumber = null,
        ?string $network = null,
        ?string $payment_method = null,
        ?array $plan_details = null
    ): Payment {

        return DB::transaction(function () use (
            $dealer,
            $carSlugs,
            $planSlug,
            $planName,
            $plan_price,
            $phoneNumber,
            $network,
            $payment_method,
            $plan_details
        ) {

            $cars = Car::whereIn('car_slug', $carSlugs)
                ->where('dealer_slug', $dealer->dealer_slug)
                ->whereIn('status', ['pending_payment', 'pending_approval'])
                ->get();

            if ($cars->isEmpty()) {
                throw new \Exception("No valid cars found");
            }

            $payment = Payment::create([
                'payment_slug'   => Str::uuid(),
                'dealer_slug'    => $dealer->dealer_slug,
                'plan_name'      => $planName,
                'plan_slug'      => $planSlug,
                'plan_price'     => $plan_price,
                'plan_details'   => $plan_details,
                'payment_method' => $payment_method,
                'status'         => 'pending',
                'phone_number'   => $phoneNumber,
                'network'        => $network,
                'reference_id'   => "GHCS" . time() . strtoupper(Str::random(6)),
            ]);

            foreach ($cars as $car) {

                DB::table('payment_cars')->insert([
                    'payment_slug' => $payment->payment_slug,
                    'car_slug'     => $car->car_slug,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }

            return $payment;
        });
    }

    // public function processPayment(Payment $payment, ?string $reference_id): bool
    // {
    //     return DB::transaction(function () use ($payment, $reference_id) {
    //         $payment->update([
    //             'status' => 'paid',
    //             // 'reference_id' => $reference_id,
    //         ]);

    //         // $startDate  = $payment->created_at ?? now();
    //         // $expiryDate = $startDate->copy()->addDays((int) $payment->duration_days);
    //         $carSlugs = $payment->car_slugs ?? [];
    //         foreach ($carSlugs as $carSlug) {
    //             $car = Car::where('car_slug', $carSlug)->where('dealer_slug', $payment->dealer_slug)->first();
    //             if ($car) {
    //                 $dealer = $car->dealer();
    //                 Approval::create([
    //                     'car_slug'    => $car->car_slug,
    //                     'dealer_slug' => $dealer->dealer_slug,
    //                     'dealer_code' => null,
    //                     'type'        => $payment->payment_method,
    //                     'dealer_name' => $dealer->full_name ?? $dealer->business_name,
    //                 ]);
    //                 $this->carService()->activateCar($car, (int) $payment->duration_days);
    //             }
    //         }

    //         // $subscription = Subscription::updateOrCreate(
    //         //     ['dealer_slug' => $payment->dealer_slug],
    //         //     [
    //         //         'subscription_slug' => Str::uuid()->toString(),
    //         //         'plan_slug'         => $payment->plan_slug ?? 'custom',
    //         //         'plan_name'         => $payment->plan_name ?? 'Custom',
    //         //         'duration_days'     => (string) $payment->duration_days,
    //         //         'starts_at'         => $startDate,
    //         //         'expiry_date'       => $expiryDate,
    //         //         'status'            => 'active',
    //         //         'price'             => $payment->amount,
    //         //     ]);

    //         // SubscriptionArchive::updateOrCreate(
    //         //     ['reference_id' => $payment->reference_id],
    //         //     [
    //         //         'dealer_slug'       => $payment->dealer_slug,
    //         //         'subscription_slug' => $subscription->subscription_slug,
    //         //         'plan_slug'         => $payment->plan_slug,
    //         //         'plan_name'         => $payment->plan_name,
    //         //         'duration_days'     => (string) $payment->duration_days,
    //         //         'price'             => $payment->amount,
    //         //         'status'            => 'paid',
    //         //         'starts_at'         => $subscription->starts_at,
    //         //         'expiry_date'       => $subscription->expiry_date,
    //         //     ]);
    //         return true;
    //     });
    // }

    public function processPayment(Payment $payment, ?string $reference_id): bool
    {
        return DB::transaction(function () use ($payment, $reference_id) {
            // Update payment status
            $payment->update([
                'status'       => 'paid',
                'reference_id' => $reference_id, // Uncommented this
            ]);

            $carSlugs = $payment->car_slugs ?? [];

            foreach ($carSlugs as $carSlug) {
                $car = Car::where('car_slug', $carSlug)
                    ->where('dealer_slug', $payment->dealer_slug)
                    ->first();

                if ($car) {
                    // Fix: Remove parentheses to get the dealer model
                    $dealer = $car->dealer;

                    if ($dealer) {
                        Approval::create([
                            'car_slug'     => $car->car_slug,
                            'dealer_slug'  => $dealer->dealer_slug,
                            'status'       => 'pending',
                            'type'         => $payment->payment_method,
                            'dealer_name'  => $dealer->full_name ?? $dealer->business_name,
                            'payment_slug' => $payment->payment_slug,
                        ]);

                        $this->carService()->activateCar($car, (int) $payment->duration_days);
                    }
                }
            }

            return true;
        });
    }

    protected function carService(): CarService
    {
        return app(CarService::class);
    }

    public function getPaymentSummary(Dealer $dealer): array
    {
        $pendingCars = Car::where('dealer_slug', $dealer->dealer_slug)
            ->whereIn('status', ['pending_payment', 'draft'])
            ->get();

        return [
            'cars'                     => $pendingCars,
            'total_cars'               => $pendingCars->count(),
            'estimated_amount_30_days' => $this->calculateAmount($pendingCars->count(), 30),
            'estimated_amount_90_days' => $this->calculateAmount($pendingCars->count(), 90),
        ];
    }

    protected function calculateAmount(int $carCount, int $durationDays): float
    {
        if ($carCount === 0) {
            return 0;
        }
        $basePrice = 50;
        $dailyRate = $durationDays === 90 ? 0.5 : 1.0;
        return round($carCount * $basePrice * ($durationDays * $dailyRate), 2);
    }
}
