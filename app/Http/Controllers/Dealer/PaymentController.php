<?php
namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Transformers\CarTransformer;
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

    public function createPayment(Request $request, Car $car): JsonResponse
    {
        $data = $request->validate([
            'car_slugs'    => 'required|array',
            'car_slugs.*'  => 'string|exists:cars,car_slug',
            'plan_slug'    => 'required|string|in:free_trial,1_month,3_months',
            'phone_number' => 'nullable|string',
            'network'      => 'nullable|string',
        ]);

        $dealer = $request->user();
        // $plan         = Plan::where('plan_slug', $data['plan_slug'])->first();
        // $durationDays = $plan ? (int) $plan->duration_days : ($data['plan_slug'] === 'free_trial' ? 15 : ($data['plan_slug'] === '3_months' ? 90 : 30));
        // $amount       = $plan ? (float) $plan->price : 0;

        $payment = $this->paymentService->createPayment(
            $dealer,
            $data['car_slugs'],
            $data['plan_name'] ?? null,
            $data['plan_slug'],
            $data['duration_days'] ?? null,
            $data['price'] ?? null,
            $data['features'] ?? null,
            $data['phone_number'] ?? null,
            $data['network'] ?? null,
            $data['payment_method'] ?? null
        );

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
        $response = file_get_contents("php://input");
        // Log::channel("payments")->info("[Payswitch Callback Raw]", ['raw' => $response]);

        // $request->validate([
        //     'transaction_id' => 'required|string',
        //     'payment_slug'   => 'required|exists:payments,payment_slug',
        //     'status'         => 'required|in:success,failed',
        // ]);

        $response = json_decode($response, true);

        if (! $response) {
            // Log::channel("payments")->error("[Invalid Callback Data]");
            return response()->json(['status' => 'error', 'message' => 'Invalid data'], 400);
        }

        // $merchant_data = json_decode($response['merchant_data'] ?? '{}', true);
        // $paymentType   = $merchant_data['payment_type'] ?? null;

        $payment = Payment::where("reference_id", $response['transaction_id'] ?? null)->first();

        if ($request->status === 'success' || 'paid' || 'Approved') {
            $this->paymentService->processPayment($payment, $request->transaction_id);
            return $this->apiResponse(
                in_error: true,
                message: "Payment approved",
                reason: "Payment approved",
                status_code: self::API_SUCCESS,
                data: [$payment->fresh()]
            );
        }

        if (in_array($response["status"], ["Ambiguous", "pending", "Failed"])) {
            $payment->update(['status' => 'failed']);
            return $this->apiResponse(
                in_error: true,
                message: "Payment pending failed",
                reason: "Payment failed",
                status_code: self::API_SUCCESS,
                data: [$payment->fresh()]
            );
        }

        return $this->apiResponse(
            in_error: false,
            message: "Payment pending approval",
            reason: "Payment is still in pending",
            status_code: self::API_SUCCESS,
            data: [$payment->fresh()]
        );
    }

    public function checkPayment(Request $request): JsonResponse
    {
        $request->validate([
            'reference_id' => 'required|string',
        ]);

        $payment = Payment::where('reference_id', $request->reference_id)->firstOrFail();

        if ($payment->status === 'paid') {
            $this->paymentService->processPayment($payment, $request->reference_id);
            return $this->apiResponse(
                in_error: true,
                message: "Payment approved",
                reason: "Payment approved",
                status_code: self::API_SUCCESS,
                data: [$payment->fresh()]
            );
        }

        if ($payment->status === 'failed') {
            $payment->update(['status' => 'failed']);
            return $this->apiResponse(
                in_error: true,
                message: "Payment pending failed",
                reason: "Payment failed",
                status_code: self::API_SUCCESS,
                data: [$payment->fresh()]
            );
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
