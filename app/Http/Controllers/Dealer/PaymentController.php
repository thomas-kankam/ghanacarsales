<?php
namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\Payment;
use App\Models\Plan;
use App\Services\PaymentService;
use App\Services\PaystackService;
use App\Transformers\CarTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
        private PaystackService $paystackService
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

    public function createPayment(Request $request, Car $car): JsonResponse
    {
        $data = $request->validate([
            'car_slugs'    => 'required|array',
            'car_slugs.*'  => 'string|exists:cars,car_slug',
            'plan_slug'    => 'required|string|in:friend_code,1_month,3_months',
            'phone_number' => 'nullable|string',
            'network'      => 'nullable|string',
        ]);

        $dealer = $request->user();
        $plan = Plan::where('plan_slug', $data['plan_slug'])->first();
        if (!$plan) {
            return $this->apiResponse(
                in_error: true,
                message: "Invalid plan",
                status_code: self::API_BAD_REQUEST,
                reason: "Plan not found.",
                data: []
            );
        }

        $payment = $this->paymentService->createPayment(
            $dealer,
            $data['car_slugs'],
            $data['plan_slug'],
            $plan->plan_name,
            (float) $plan->price,
            $data['phone_number'] ?? null,
            $data['network'] ?? null,
            $data['payment_method'] ?? 'momo'
        );

        $car->load('paymentItems.payment');
        return $this->apiResponse(
            in_error: false,
            message: "Payment created successfully",
            status_code: self::API_CREATED,
            data: [
                'car'         => CarTransformer::summary($car),
                'payment'     => $payment,
                'payment_url' => url("/api/dealer/check_payment?reference_id={$payment->reference_id}"),
            ]
        );
    }

    public function callback(Request $request): JsonResponse
    {
        $rawPayload = $request->getContent();
        $payload = json_decode($rawPayload, true);

        if (!$payload) {
            Log::warning('Payment callback: invalid JSON');
            return response()->json(['status' => 'error', 'message' => 'Invalid data'], 400);
        }

        $signature = $request->header('x-paystack-signature', '');
        if ($signature && !$this->paystackService->verifyWebhookSignature($rawPayload, $signature)) {
            Log::warning('Payment callback: invalid Paystack signature');
            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 400);
        }

        $reference = $payload['data']['reference'] ?? $payload['reference'] ?? $payload['transaction_id'] ?? null;
        if (!$reference) {
            return response()->json(['status' => 'error', 'message' => 'Missing reference'], 400);
        }

        $payment = Payment::where('reference_id', $reference)->orWhere('reference', $reference)->first();
        if (!$payment) {
            Log::warning('Payment callback: payment not found', ['reference' => $reference]);
            return response()->json(['status' => 'error', 'message' => 'Payment not found'], 404);
        }

        if ($payment->status === 'paid') {
            return response()->json(['status' => 'ok', 'message' => 'Already processed'], 200);
        }

        $status = $payload['data']['status'] ?? $payload['status'] ?? null;
        if ($status === 'success' || ($payload['event'] ?? '') === 'charge.success') {
            $this->paymentService->processPaymentSuccess($payment, $reference);
            return response()->json(['status' => 'ok', 'message' => 'Payment approved'], 200);
        }

        if (in_array($status, ['failed', 'abandoned'], true)) {
            $payment->update(['status' => 'failed']);
            return response()->json(['status' => 'ok', 'message' => 'Payment failed recorded'], 200);
        }

        return response()->json(['status' => 'ok', 'message' => 'Pending'], 200);
    }

    public function checkPayment(Request $request): JsonResponse
    {
        $request->validate([
            'reference_id' => 'required|string',
        ]);

        $payment = Payment::where('reference_id', $request->reference_id)->firstOrFail();

        if ($payment->status === 'paid') {
            return $this->apiResponse(
                in_error: false,
                message: "Payment approved",
                reason: "Payment approved",
                status_code: self::API_SUCCESS,
                data: [$payment->fresh()]
            );
        }

        if ($payment->status === 'failed') {
            return $this->apiResponse(
                in_error: true,
                message: "Payment failed",
                reason: "Payment failed",
                status_code: self::API_SUCCESS,
                data: [$payment->fresh()]
            );
        }

        if (config('services.paystack.secret_key')) {
            $verified = $this->paystackService->verifyTransaction($payment->reference_id ?? $payment->reference);
            if ($verified && ($verified['paid'] ?? false)) {
                $this->paymentService->processPaymentSuccess($payment, $payment->reference_id ?? $payment->reference);
                return $this->apiResponse(
                    in_error: false,
                    message: "Payment approved",
                    reason: "Payment approved",
                    status_code: self::API_SUCCESS,
                    data: [$payment->fresh()]
                );
            }
            if ($verified && ($verified['status'] ?? '') === 'failed') {
                $payment->update(['status' => 'failed']);
                return $this->apiResponse(
                    in_error: true,
                    message: "Payment failed",
                    reason: "Payment failed",
                    status_code: self::API_SUCCESS,
                    data: [$payment->fresh()]
                );
            }
        }

        return $this->apiResponse(
            in_error: false,
            message: "Payment pending approval",
            reason: "Payment is still in pending",
            status_code: self::API_SUCCESS,
            data: [$payment->fresh()]
        );
    }
}
