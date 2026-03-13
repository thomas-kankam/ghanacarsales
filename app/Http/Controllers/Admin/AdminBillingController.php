<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
// use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBillingController extends Controller
{
    public function payments(Request $request): JsonResponse
    {
        $payments = Payment::with(['dealer', 'paymentItems.car'])
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->get('status'));
            })
            ->orderByDesc('created_at')
            ->paginate((int) $request->get('per_page', 20));

        $items = $payments->getCollection()->map(function ($payment) {
            return [
                ...$payment->only([
                    'id', 'payment_slug', 'dealer_slug', 'plan_name', 'plan_slug', 'status',
                    'reference_id', 'reference', 'plan_price', 'amount', 'payment_method',
                    'phone_number', 'network', 'created_at', 'updated_at',
                ]),
                'dealer' => $payment->dealer ? [
                    'dealer_slug'   => $payment->dealer->dealer_slug,
                    'business_name' => $payment->dealer->business_name,
                    'full_name'     => $payment->dealer->full_name,
                    'email'         => $payment->dealer->email,
                    'dealer_code'   => $payment->dealer->dealer_code,
                ] : null,
                'cars' => $payment->paymentItems->map(function ($item) {
                    $car = $item->car;
                    if (!$car) {
                        return null;
                    }
                    return [
                        'car_slug'           => $car->car_slug,
                        'brand'              => $car->brand,
                        'model'              => $car->model,
                        'year_of_manufacture' => $car->year_of_manufacture,
                        'status'             => $car->status,
                        'price'              => $car->price !== null ? (float) $car->price : null,
                    ];
                })->filter()->values()->all(),
            ];
        });

        $payload = [
            'items' => $items,
            'meta'  => [
                'current_page' => $payments->currentPage(),
                'last_page'    => $payments->lastPage(),
                'per_page'     => $payments->perPage(),
                'total'        => $payments->total(),
            ],
        ];

        return $this->apiResponse(
            in_error: false,
            message: "Payments retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $payload
        );
    }

    // public function subscriptions(Request $request): JsonResponse
    // {
    //     $subscriptions = Subscription::with(['dealer'])
    //         ->when($request->filled('status'), function ($q) use ($request) {
    //             $q->where('status', $request->get('status'));
    //         })
    //         ->orderByDesc('created_at')
    //         ->paginate((int) $request->get('per_page', 20));

    //     return $this->apiResponse(
    //         in_error: false,
    //         message: "Subscriptions retrieved successfully",
    //         status_code: self::API_SUCCESS,
    //         data: $subscriptions
    //     );
    // }
}

