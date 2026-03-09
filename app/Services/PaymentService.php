<?php
namespace App\Services;

use App\Models\Car;
use App\Models\Dealer;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionArchive;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    public function createPayment(Dealer $dealer, array $carSlugs, string $planSlug, string $durationDays, string $planName, float $amount, ?string $phoneNumber = null, ?string $network): Payment
    {
        return DB::transaction(function () use ($dealer, $carSlugs, $planSlug, $durationDays, $planName, $amount, $phoneNumber, $network) {
            $cars = Car::whereIn('car_slug', $carSlugs)
                ->where('dealer_slug', $dealer->dealer_slug)
            // ->whereIn('status', ['pending_payment', 'pending_approval'])
                ->get();

            if ($cars->isEmpty()) {
                throw new \InvalidArgumentException('No valid cars found for payment');
            }

            $payment = Payment::create([
                'payment_slug'   => Str::uuid()->toString(),
                'dealer_slug'    => $dealer->dealer_slug,
                'plan_name'      => $planName,
                'plan_slug'      => $planSlug,
                'amount'         => $amount,
                'payment_method' => 'momo',
                'status'         => 'pending',
                'duration_days'  => $durationDays,
                'car_slugs'      => $cars->pluck('car_slug')->values()->all(),
                'phone_number'   => $phoneNumber,
                'network'        => $network,
                'reference_id'   => "GHCS" . time() . strtoupper(Str::random(6)),
            ]);

            return $payment;
        });
    }

    public function processPayment(Payment $payment, string $reference_id): bool
    {
        return DB::transaction(function () use ($payment, $reference_id) {
            $payment->update([
                'status'       => 'paid',
                'reference_id' => $reference_id,
            ]);

            $startDate  = $payment->created_at ?? now();
            $expiryDate = $startDate->copy()->addDays((int) $payment->duration_days);
            $carSlugs   = $payment->car_slugs ?? [];

            foreach ($carSlugs as $carSlug) {
                $car = Car::where('car_slug', $carSlug)->where('dealer_slug', $payment->dealer_slug)->first();
                if ($car) {
                    $this->carService()->activateCar($car, (int) $payment->duration_days);
                }
            }

            $subscription = Subscription::updateOrCreate(
                ['dealer_slug' => $payment->dealer_slug],
                [
                    'subscription_slug' => Str::uuid()->toString(),
                    'plan_slug'         => $payment->plan_slug ?? 'custom',
                    'plan_name'         => $payment->plan_name ?? 'Custom',
                    'duration_days'     => (string) $payment->duration_days,
                    'starts_at'         => $startDate,
                    'expiry_date'       => $expiryDate,
                    'status'            => 'active',
                    'price'             => $payment->amount,
                ]);

            SubscriptionArchive::updateOrCreate(
                ['reference_id' => $payment->reference_id],
                [
                    'dealer_slug'       => $payment->dealer_slug,
                    'subscription_slug' => $subscription->subscription_slug,
                    'plan_slug'         => $payment->plan_slug,
                    'plan_name'         => $payment->plan_name,
                    'duration_days'     => (string) $payment->duration_days,
                    'price'             => $payment->amount,
                    'status'            => 'paid',
                    'starts_at'         => $subscription->starts_at,
                    'expiry_date'       => $subscription->expiry_date,
                ]);
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
