<?php
namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
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

    public function createPayment(Request $request): JsonResponse
    {
        $data = $request->validate([
            'car_slugs'     => 'required|array',
            'car_slugs.*'   => 'string|exists:cars,car_slug',
            'plan_slug'     => 'required|string|in:free_trial,1_month,3_months',
            'phone_number'  => 'nullable|string',
        ]);

        $dealer = $request->user();
        $plan = Plan::where('plan_slug', $data['plan_slug'])->first();
        $durationDays = $plan ? (int) $plan->duration_days : ($data['plan_slug'] === 'free_trial' ? 15 : ($data['plan_slug'] === '3_months' ? 90 : 30));
        $amount = $plan ? (float) $plan->price : 0;

        $payment = $this->paymentService->createPayment(
            $dealer,
            $data['car_slugs'],
            $data['plan_slug'],
            $durationDays,
            $amount,
            $data['phone_number'] ?? null
        );

        return $this->apiResponse(
            in_error: false,
            message: "Payment created successfully",
            status_code: self::API_CREATED,
            data: [
                'payment'     => $payment,
                'payment_url' => url("/api/dealer/payment/callback?payment_slug={$payment->payment_slug}"),
            ]
        );
    }

    public function callback(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_id' => 'required|string',
            'payment_slug'   => 'required|exists:payments,payment_slug',
            'status'         => 'required|in:success,failed',
        ]);

        $payment = \App\Models\Payment::where('payment_slug', $request->payment_slug)->firstOrFail();

        if ($request->status === 'success') {
            $this->paymentService->processPayment($payment, $request->transaction_id);
        } else {
            $payment->update(['status' => 'failed']);
        }

        return $this->apiResponse(
            in_error: false,
            message: "Payment processed",
            status_code: self::API_SUCCESS,
            data: ['payment' => $payment->fresh()]
        );
    }
}
