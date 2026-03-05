<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBillingController extends Controller
{
    public function payments(Request $request): JsonResponse
    {
        $payments = Payment::with(['dealer', 'plan', 'subscription'])
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->get('status'));
            })
            ->orderByDesc('created_at')
            ->paginate((int) $request->get('per_page', 20));

        return $this->apiResponse(
            in_error: false,
            message: "Payments retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $payments
        );
    }

    public function subscriptions(Request $request): JsonResponse
    {
        $subscriptions = Subscription::with(['dealer', 'plan'])
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->get('status'));
            })
            ->orderByDesc('created_at')
            ->paginate((int) $request->get('per_page', 20));

        return $this->apiResponse(
            in_error: false,
            message: "Subscriptions retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $subscriptions
        );
    }
}

