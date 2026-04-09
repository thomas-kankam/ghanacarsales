<?php

namespace App\Services;

use App\Mail\AdminPendingApproval;
use App\Models\Admin;
use App\Models\Car;
use App\Models\Dealer;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\Plan;
use App\Traits\AppNotifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PaymentService
{
    use AppNotifications;

    public function createPayment(
        Dealer $dealer,
        array $carSlugs,
        string $planSlug,
        string $planName,
        float $planPrice,
        ?string $phoneNumber = null,
        ?string $network = null,
        ?string $paymentMethod = null
    ): Payment {
        return DB::transaction(function () use (
            $dealer,
            $carSlugs,
            $planSlug,
            $planName,
            $planPrice,
            $phoneNumber,
            $network,
            $paymentMethod
        ) {

            $cars = Car::whereIn('car_slug', $carSlugs)
                ->where('dealer_slug', $dealer->dealer_slug)
                ->whereIn('status', ['pending_payment', 'pending_approval', 'draft'])
                ->get();

            if ($cars->isEmpty() || $cars->count() !== count(array_unique($carSlugs))) {
                throw new \Exception("No valid cars found");
            }

            $amount = $planPrice * count($cars);
            $payment = Payment::create([
                'payment_slug'   => (string) Str::uuid(),
                'dealer_slug'   => $dealer->dealer_slug,
                'plan_name'     => $planName,
                'plan_slug'     => $planSlug,
                'plan_price'    => $planPrice,
                'amount'        => $amount,
                'payment_method'=> $paymentMethod,
                'status'        => 'pending',
                'phone_number'  => $phoneNumber,
                'network'       => $network,
                'reference_id'  => $this->generateReference(),
            ]);

            foreach ($cars as $car) {
                PaymentItem::create([
                    'payment_slug' => $payment->payment_slug,
                    'car_slug'     => $car->car_slug,
                    'price'        => $planPrice,
                ]);
            }

            return $payment;
        });
    }

    /**
     * Create payment and payment_items for cars. Amount = plan price × number of cars.
     */
    public function createPaymentForCars(
        Dealer $dealer,
        array $cars,
        Plan $plan,
        ?string $phoneNumber = null,
        ?string $network = null,
        ?string $paymentMethod = null
    ): Payment {
        $pricePerCar = (float) $plan->price;
        $totalAmount = $pricePerCar * count($cars);

        return DB::transaction(function () use ($dealer, $cars, $plan, $pricePerCar, $totalAmount, $phoneNumber, $network, $paymentMethod) {
            $payment = Payment::create([
                'payment_slug'   => (string) Str::uuid(),
                'dealer_slug'   => $dealer->dealer_slug,
                'plan_name'     => $plan->plan_name,
                'plan_slug'     => $plan->plan_slug,
                'plan_price'    => $plan->price,
                'amount'        => $totalAmount,
                'reference_id'  => $this->generateReference(),
                'payment_method'=> $paymentMethod,
                'status'        => 'pending',
                'phone_number'  => $phoneNumber,
                'network'       => $network,
            ]);

            foreach ($cars as $car) {
                if ($car instanceof Car) {
                    PaymentItem::create([
                        'payment_slug' => $payment->payment_slug,
                        'car_slug'     => $car->car_slug,
                        'price'        => $pricePerCar,
                    ]);
                }
            }

            return $payment;
        });
    }

    public function processPayment(Payment $payment, ?string $reference_id): bool
    {
        return $this->processPaymentSuccess($payment, $reference_id);
    }

    /**
     * After successful payment: mark payment paid and create one approval per car (admin approves later).
     * Idempotent: safe to call again if payment already paid (no duplicate approvals).
     */
    public function processPaymentSuccess(Payment $payment, ?string $referenceId): bool
    {
        return DB::transaction(function () use ($payment, $referenceId) {
            $payment->refresh();
            if ($payment->status === 'paid') {
                // Log::channel('paystack')->info('Paystack webhook: payment already paid', ['payment' => $payment]);
                return true;
            }

            $payment->update([
                'status'       => 'paid',
                'reference_id' => $referenceId ?? $payment->reference_id,
                'reference'    => $referenceId ?? $payment->reference,
            ]);

            $payment->load('paymentItems.car');
            $dealer = $payment->dealer;
            if (!$dealer) {
                Log::channel('paystack')->info('Paystack webhook: dealer not found', ['payment' => $payment]);
                return true;
            }

            $approvalService = app(ApprovalService::class);
            foreach ($payment->paymentItems as $item) {
                $car = $item->car;
                if ($car) {
                    $car->update(['status' => 'pending_approval']);
                    // Log::channel('paystack')->info('Paystack webhook: car updated to pending_approval', ['car' => $car]);
                    $approvalService->createForCar(
                        $car->car_slug,
                        $dealer,
                        $payment->plan_slug,
                        'pending',
                        null,
                        $payment->payment_slug
                    );
                    // Log::channel('paystack')->info('Paystack webhook: approval created', ['approval' => $approvalService->createForCar(
                    //     $car->car_slug,
                    //     $dealer,
                    //     $payment->plan_slug,
                    //     'pending',
                    //     null,
                    //     $payment->payment_slug
                    // )]);
                }
            }

            $this->notifyAdminsPendingApproval($payment);

            return true;
        });
    }

    protected function notifyAdminsPendingApproval(Payment $payment): void
    {
        try {
            $admins = Admin::query()->where('is_active', true)->get(['name', 'email', 'phone_number']);
            if ($admins->isEmpty()) {
                return;
            }

            $carCount = $payment->paymentItems()->count();
            $body = sprintf(
                "A payment has been completed and listing(s) are now pending approval.\n\nReference: %s\nDealer: %s\nCars: %d\nPlan: %s\nAmount: %s",
                $payment->reference_id ?? $payment->reference ?? 'N/A',
                $payment->dealer_slug,
                $carCount,
                $payment->plan_name ?? $payment->plan_slug ?? 'N/A',
                (string) $payment->amount
            );

            foreach ($admins as $admin) {
                // if (!empty($admin->email)) {
                    self::sendEmail(
                        $admin->email,
                        email_class: "App\Mail\AdminPendingApproval",
                        parameters: [$admin->email, $body]
                    );
                    $this->sendAdminSmsNotification($admin->phone_number, $body);
                // }

                // if (!empty($admin->phone_number)) {
                // }
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to notify admins for pending approval payment.', [
                'payment_slug' => $payment->payment_slug,
                'message' => $e->getMessage(),
            ]);
        }
    }

    protected function sendAdminSmsNotification(string $phoneNumber, string $message): void
    {
        $apiKey = (string) config('services.mnotify.api_key');
        $sender = (string) config('services.mnotify.sender_name', 'GhanaCars');
        $sender = substr($sender, 0, 11);

        if ($apiKey === '') {
            return;
        }

        try {
            Http::withHeaders([
                'Accept' => 'application/json',
            ])->post('https://api.mnotify.com/api/sms/quick', [
                'key' => $apiKey,
                'recipient' => [$phoneNumber],
                'sender' => $sender,
                'message' => $message,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to send admin SMS notification.', [
                'phone_number' => $phoneNumber,
                'message' => $e->getMessage(),
            ]);
        }
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

    protected function generateReference(): string
    {
        do {
            $reference = 'GHCS' . now()->format('YmdHis') . strtoupper(Str::random(6));
        } while (Payment::where('reference_id', $reference)->exists());

        return $reference;
    }
}
