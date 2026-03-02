<?php
namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dealer\SubscribePlanRequest;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(private SubscriptionService $subscriptionService)
    {
    }

    public function plans(): JsonResponse
    {
        $plans = $this->subscriptionService->listActivePlans();

        return $this->apiResponse(
            in_error: false,
            message: "Subscription plans retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $plans
        );
    }

    public function current(Request $request): JsonResponse
    {
        $dealer        = $request->user();
        $subscription  = $this->subscriptionService->getCurrentSubscription($dealer);

        $data = null;
        if ($subscription) {
            $subscription->load('plan');
            $data = $subscription->toArray();
        }

        return $this->apiResponse(
            in_error: false,
            message: "Current subscription retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $data
        );
    }

    public function payments(Request $request): JsonResponse
    {
        $dealer   = $request->user();
        $perPage  = (int) $request->get('per_page', 15);
        $payments = $this->subscriptionService->getPaymentHistory($dealer, $perPage);

        return $this->apiResponse(
            in_error: false,
            message: "Payment history retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $payments
        );
    }

    public function subscribe(SubscribePlanRequest $request): JsonResponse
    {
        $dealer = $request->user();
        $data   = $request->validated();

        $plan = SubscriptionPlan::where('slug', $data['plan_slug'])
            ->where('is_active', true)
            ->firstOrFail();

        $payment = $this->subscriptionService->initiateSubscription(
            $dealer,
            $plan,
            $data['phone_number'],
            $data['payment_method'] ?? 'momo',
            $data['network'] ?? null
        );

        return $this->apiResponse(
            in_error: false,
            message: "Subscription initiated successfully",
            status_code: self::API_CREATED,
            data: [
                'payment'     => $payment,
                'payment_url' => route('payment.momo', ['payment' => $payment->payment_slug]),
            ]
        );
    }
}

