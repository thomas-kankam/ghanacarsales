<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\Dealer;
use App\Transformers\CarTransformer;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function topDealers(): JsonResponse
    {
        $dealers = Dealer::query()
            ->withCount([
                'cars as listings_count' => function ($q) {
                    $q->where('status', '!=', 'draft');
                },
            ])
            ->orderByDesc('listings_count')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return $this->apiResponse(
            in_error: false,
            message: "Top dealers retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $dealers
        );
    }

    public function pendingApprovals(): JsonResponse
    {
        $cars = Car::query()
            ->with(['dealer', 'latestApproval', 'paymentItems.payment'])
            ->where('status', 'pending_approval')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $items = $cars->map(fn ($car) => CarTransformer::summary($car))->values()->all();

        return $this->apiResponse(
            in_error: false,
            message: "Pending approvals retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $items
        );
    }

    public function latestRegistrations(): JsonResponse
    {
        $dealers = Dealer::query()
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return $this->apiResponse(
            in_error: false,
            message: "Latest dealer registrations retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $dealers
        );
    }
}

