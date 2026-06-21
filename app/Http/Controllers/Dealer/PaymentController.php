<?php

namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Mail\AdminPendingApproval;
use App\Models\Admin;
use App\Models\Car;
use App\Models\Payment;
use App\Models\Plan;
use App\Services\ApprovalService;
use App\Services\CarService;
use App\Services\PaymentService;
use App\Services\PaystackService;
use App\Transformers\CarTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
        private PaystackService $paystackService,
        private ApprovalService $approvalService,
        private CarService $carService
    ) {}

    public function getSummary(Request $request): JsonResponse
    {
        $dealer  = $request->user();
        $summary = $this->paymentService->getPaymentSummary($dealer);

        return $this->apiResponse(
            in_error: false,
            message: "Payment summary retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $summary
        );
    }

    public function createPayment(Request $request, Car $_car): JsonResponse
    {
        $data = $request->validate([
            'car_slugs'    => 'required|array',
            'car_slugs.*'  => 'string|exists:cars,car_slug',
            'plan_slug'    => 'required|string',
            'dealer_code'  => 'nullable|string|exists:dealers,dealer_code',
            'status'       => 'nullable|string|in:pending_approval,pending_payment,draft',
            'plan_details' => 'nullable|array',
            'plan_price'   => 'nullable|numeric',
            'phone_number' => 'nullable|string',
            'network'      => 'nullable|string',
            'callback_url' => 'nullable|url',
        ]);

        $dealer = $request->user();
        $plan   = Plan::where('plan_slug', $data['plan_slug'])->first();
        if (! $plan) {
            return $this->apiResponse(
                in_error: true,
                message: "Invalid plan",
                status_code: self::API_BAD_REQUEST,
                reason: "Plan not found.",
                data: []
            );
        }

        $requestedCarSlugs = array_values(array_unique($data['car_slugs']));
        $selectedCars = Car::whereIn('car_slug', $requestedCarSlugs)
            ->where('dealer_slug', $dealer->dealer_slug)
            ->get();

        if ($selectedCars->count() !== count($requestedCarSlugs)) {
            return $this->apiResponse(
                in_error: true,
                message: "Some selected cars are invalid",
                status_code: self::API_BAD_REQUEST,
                reason: "One or more cars do not belong to the authenticated dealer.",
                data: []
            );
        }

        if ($selectedCars->contains(fn($selectedCar) => ! in_array($selectedCar->status, ['pending_payment', 'expired', 'draft', 'published'], true))) {
            return $this->apiResponse(
                in_error: true,
                message: "Some selected cars cannot be moved to payment",
                status_code: self::API_BAD_REQUEST,
                reason: "Paid plans can only be applied to draft, expired, published, or pending-payment cars.",
                data: []
            );
        }

        $payment = DB::transaction(function () use ($dealer, $data, $plan, $requestedCarSlugs) {
            $cars = Car::whereIn('car_slug', $requestedCarSlugs)
                ->where('dealer_slug', $dealer->dealer_slug)
                ->whereIn('status', ['pending_payment', 'expired', 'draft', 'published'])
                ->lockForUpdate()
                ->get();

            // Log::info('PaymentController: cars', ['cars' => $cars]);

            if ($cars->count() !== count($requestedCarSlugs)) {
                throw new \InvalidArgumentException('One or more cars cannot be moved to pending payment.');
            }

            foreach ($cars as $targetCar) {
                $targetCar->update([
                    'status'       => 'pending_payment',
                    'plan_slug'    => $plan->plan_slug,
                    'plan_price'   => $plan->price,
                    'plan_details' => $data['plan_details'] ?? $targetCar->plan_details,
                ]);
            }

            // Log::info('PaymentController: cars updated', ['cars' => $cars]);

            return $this->paymentService->createPaymentForCars(
                $dealer,
                $cars->all(),
                $plan,
                $data['phone_number'] ?? null,
                $data['network'] ?? null,
                $data['payment_method'] ?? 'mobile_money'
            );
        });
        // Log::channel('paystack')->info('PaymentController: payment created', ['payment' => $payment]);

        $this->approvalService->notifyPendingPaymentForPayment($payment);

        $paymentUrl = null;
        // $callbackUrl = $data['callback_url'] ?? rtrim(config('app.url', 'http://127.0.0.1:8000'), '/') . '/api/payment/callback';
        if (config('services.paystack.secret_key')) {
            $result = $this->paystackService->initializeTransaction($payment, $dealer->email);
            if (! empty($result['authorization_url'])) {
                $paymentUrl = $result['authorization_url'];
                Log::channel('paystack')->info('PaymentController: payment URL', ['payment_url' => $paymentUrl]);
            }
        }
        if (! $paymentUrl) {
            $paymentUrl = $this->frontendBaseUrl() . '/payment/check?reference=' . $payment->reference_id;
            // Log::channel('paystack')->info('PaymentController: payment URL', ['payment_url' => $paymentUrl]);
        }

        $cars = $payment->paymentItems()->with('car.dealer')->get()
            ->pluck('car')
            ->filter()
            ->values();

        return $this->apiResponse(
            in_error: false,
            message: "Payment created successfully",
            status_code: self::API_CREATED,
            data: [
                'cars'        => $cars->map(fn($item) => CarTransformer::summary($item))->all(),
                'payment'     => $this->paymentPayloadForFrontend($payment),
                'payment_url' => $paymentUrl,
                'reference'   => $payment->reference_id,
            ],
            reason: "Car created. Complete payment to submit for approval."
        );
    }

    protected function notifyAdminsPendingApproval(Payment $payment): void
    {
        try {
            $admins = Admin::query()->where('is_active', true)->get(['email', 'phone_number', 'name']);
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
                // }

                // if (!empty($admin->phone_number)) {
                self::sendSms($admin->phone_number, $body);
                // }
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to notify admins for pending approval payment.', [
                'payment_slug' => $payment->payment_slug,
                'message' => $e->getMessage(),
            ]);
        }
    }


    /**
     * Safe payload for frontend (no sensitive data).
     */
    protected function paymentPayloadForFrontend(Payment $payment): array
    {
        return [
            'payment_slug' => $payment->payment_slug,
            'reference_id' => $payment->reference_id,
            'amount'       => (float) $payment->amount,
            'plan_slug'    => $payment->plan_slug,
            'plan_name'    => $payment->plan_name,
            'status'       => $payment->status,
        ];
    }

    protected function carsPayloadForPayment(Payment $payment): array
    {
        return $payment->paymentItems()
            ->with('car.dealer')
            ->get()
            ->pluck('car')
            ->filter()
            ->map(fn(Car $car) => CarTransformer::summary($car))
            ->values()
            ->all();
    }

    /**
     * Payment callback (browser redirect from Paystack). Backend resolves payment status (from DB or
     * by verifying with Paystack if webhook not yet received), then redirects to frontend success or failure.
     * Frontend only needs to show the right page; optional: call check_payment for details.
     */
    public function callback(Request $request): RedirectResponse
    {
        $frontend  = $this->frontendBaseUrl();
        $reference = $request->query('reference') ?? $request->query('trxref');
        if (! $reference) {
            return redirect()->away("{$frontend}/app/payment/cancel?" . http_build_query(['reason' => 'missing_reference']));
        }

        $payment = Payment::where('reference_id', $reference)->orWhere('reference', $reference)->first();
        if (! $payment) {
            return redirect()->away("{$frontend}/app/payment/cancel?" . http_build_query(['reference' => $reference, 'reason' => 'not_found']));
        }

        if ($payment->status === 'paid') {
            return redirect()->away("{$frontend}/app/payment/success");
            // return redirect()->away("{$frontend}/payment/success?" . http_build_query(['reference' => $reference]));
        }
        if ($payment->status === 'failed') {
            $this->paymentService->processPaymentFailure($payment);

            return redirect()->away("{$frontend}/app/payment/cancel");
            // return redirect()->away("{$frontend}/payment/failure?" . http_build_query(['reference' => $reference]));
        }

        // Pending: webhook may not have run yet; verify with Paystack and update
        if (config('services.paystack.secret_key')) {
            $verified = $this->paystackService->verifyTransaction($payment->reference_id ?? $payment->reference);
            if ($verified && ($verified['paid'] ?? false)) {
                $this->paymentService->processPaymentSuccess($payment, $payment->reference_id ?? $payment->reference);
                return redirect()->away("{$frontend}/app/payment/success");
                // return redirect()->away("{$frontend}/payment/success?" . http_build_query(['reference' => $reference]));
            }
            if ($verified && ($verified['status'] ?? '') === 'failed') {
                $this->paymentService->processPaymentFailure($payment);
                return redirect()->away("{$frontend}/app/payment/cancel?" . http_build_query(['reference' => $reference]));
                // return redirect()->away("{$frontend}/payment/failure?" . http_build_query(['reference' => $reference]));
            }
        }

        return redirect()->away("{$frontend}/app/payment/callback");
        // return redirect()->away("{$frontend}/payment/callback?" . http_build_query(['reference' => $reference]));
    }

    protected function frontendBaseUrl(): string
    {
        $frontend = trim((string) config('app.frontend_url', ''));

        if ($frontend !== '' && preg_match('#^https?://#i', $frontend)) {
            return rtrim($frontend, '/');
        }

        return 'https://dealer.omnicarsgh.com';
    }

    /**
     * Paystack webhook (server-to-server). Configure in Paystack Dashboard:
     * https://dashboard.paystack.com/#/settings/developer → Webhook URL = https://backend.omnicarsgh.com/api/payment/webhook
     * Production: PAYSTACK_WEBHOOK_SECRET must be set; requests with invalid/missing signature are rejected.
     */
    public function webhook(Request $request): JsonResponse
    {
        $rawPayload = $request->getContent();
        $payload    = json_decode($rawPayload, true);


        if (! $payload || ! is_array($payload)) {
            // Log::channel('single')->warning('Paystack webhook: invalid JSON');
            return response()->json(['status' => 'error', 'message' => 'Invalid data'], 400);
        }

        $signature = $request->header('x-paystack-signature', '');
        if (! $this->paystackService->verifyWebhookSignature($rawPayload, $signature)) {
            // Log::channel('single')->warning('Paystack webhook: invalid or missing signature');
            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 400);
        }

        $event     = $payload['event'] ?? '';
        $reference = $payload['data']['reference'] ?? $payload['reference'] ?? $payload['data']['transaction_id'] ?? null;
        if (! $reference) {
            return response()->json(['status' => 'error', 'message' => 'Missing reference'], 400);
        }

        $payment = Payment::where('reference_id', $reference)->orWhere('reference', $reference)->first();
        if (! $payment) {
            Log::channel('single')->warning('Paystack webhook: payment not found', ['reference' => $reference]);
            return response()->json(['status' => 'error', 'message' => 'Payment not found'], 404);
        }

        if ($payment->status === 'paid') {
            return response()->json(['status' => 'ok', 'message' => 'Already processed'], 200);
        }

        $status = $payload['data']['status'] ?? $payload['status'] ?? null;
        if ($status === 'success' || $event === 'charge.success') {
            try {
                $this->paymentService->processPaymentSuccess($payment, $reference);
            } catch (\Throwable $e) {
                Log::channel('single')->error('Paystack webhook: processPaymentSuccess failed', [
                    'reference' => $reference,
                    'message'   => $e->getMessage(),
                ]);
                return response()->json(['status' => 'error', 'message' => 'Processing failed'], 500);
            }
            return response()->json(['status' => 'ok', 'message' => 'Payment approved'], 200);
        }

        if (in_array($status, ['failed', 'abandoned'], true)) {
            $this->paymentService->processPaymentFailure($payment);
            return response()->json(['status' => 'ok', 'message' => 'Payment failed recorded'], 200);
        }

        return response()->json(['status' => 'ok', 'message' => 'Pending'], 200);
    }

    /**
     * Check payment status by reference. Authorized: dealer must own the payment.
     */
    public function checkPayment(Request $request): JsonResponse
    {
        $request->validate([
            'reference_id' => 'required|string',
        ]);

        $payment = Payment::where('reference_id', $request->reference_id)
            ->orWhere('reference', $request->reference_id)
            ->first();

        if (! $payment) {
            return $this->apiResponse(
                in_error: true,
                message: "Payment not found",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        $dealer = $request->user();
        if ($dealer && $payment->dealer_slug !== $dealer->dealer_slug) {
            return $this->apiResponse(
                in_error: true,
                message: "Unauthorized",
                status_code: self::API_FORBIDDEN,
                data: []
            );
        }

        if ($payment->status === 'paid') {
            return $this->apiResponse(
                in_error: false,
                message: "Payment approved",
                status_code: self::API_SUCCESS,
                data: $this->paymentPayloadForFrontend($payment->fresh())
            );
        }

        if ($payment->status === 'failed') {
            $this->paymentService->processPaymentFailure($payment);

            return $this->apiResponse(
                in_error: true,
                message: "Payment failed",
                status_code: self::API_SUCCESS,
                data: array_merge(
                    $this->paymentPayloadForFrontend($payment->fresh()),
                    ['cars' => $this->carsPayloadForPayment($payment)]
                ),
                reason: "Payment failed. Your listing has been saved as a draft so you can try again."
            );
        }

        if (config('services.paystack.secret_key')) {
            $verified = $this->paystackService->verifyTransaction($payment->reference_id ?? $payment->reference);
            if ($verified && ($verified['paid'] ?? false)) {
                $this->paymentService->processPaymentSuccess($payment, $payment->reference_id ?? $payment->reference);
                return $this->apiResponse(
                    in_error: false,
                    message: "Payment approved",
                    status_code: self::API_SUCCESS,
                    data: $this->paymentPayloadForFrontend($payment->fresh())
                );
            }
            if ($verified && ($verified['status'] ?? '') === 'failed') {
                $this->paymentService->processPaymentFailure($payment);

                return $this->apiResponse(
                    in_error: true,
                    message: "Payment failed",
                    status_code: self::API_SUCCESS,
                    data: array_merge(
                        $this->paymentPayloadForFrontend($payment->fresh()),
                        ['cars' => $this->carsPayloadForPayment($payment)]
                    ),
                    reason: "Payment failed. Your listing has been saved as a draft so you can try again."
                );
            }
        }

        return $this->apiResponse(
            in_error: false,
            message: "Payment pending",
            status_code: self::API_SUCCESS,
            data: $this->paymentPayloadForFrontend($payment->fresh())
        );
    }
}
