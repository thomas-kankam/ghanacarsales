<?php
namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
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
        $dealer       = $request->user();
        $subscription = $this->subscriptionService->getCurrentSubscription($dealer);
        $data         = $subscription ? $subscription->toArray() : null;

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
}
