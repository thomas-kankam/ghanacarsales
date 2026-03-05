<?php
namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
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

    public function createPayment(Dealer $dealer, array$data): JsonResponse
    {
        $request->validate([
            'car_ids'       => 'required|array',
            'car_ids.*'     => 'exists:cars,id',
            'duration_days' => 'required|in:30,90',
        ]);

        $dealer  = $request->user();
        $payment = $this->paymentService->createPayment(
            $dealer,
            $request->car_ids,
            $request->duration_days
        );

        // TODO: Integrate with MoMo payment gateway
        // For now, return payment details
        return $this->apiResponse(
            in_error: false,
            message: "Payment created successfully",
            status_code: self::API_CREATED,
            data: [
                'payment'     => $payment,
                'payment_url' => route('payment.momo', ['payment' => $payment->payment_slug]),
            ]
        );
    }

    public function callback(Request $request): JsonResponse
    {
        // TODO: Handle MoMo payment callback
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
            data: ['payment' => $payment]
        );
    }
}
