<?php
namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dealer\CarUploadRequest;
use App\Models\Approval;
use App\Models\Car;
use App\Models\Dealer;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\View;
use App\Services\CarService;
use App\Services\PaymentService;
use App\Services\SubscriptionService;
use App\Transformers\CarTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealerCarController extends Controller
{
    public function __construct(
        private CarService $carService,
        private SubscriptionService $subscriptionService,
        private PaymentService $paymentService
    ) {}

    public function uploadCar(CarUploadRequest $request): JsonResponse
    {
        $dealer = $request->user();
        $data   = $request->validated();

        $isDraft  = ($data['status'] ?? '') === 'draft';
        $planSlug = $data['plan_slug'] ?? null;

        $plan               = Plan::where("plan_slug", $planSlug)->first();
        $data['plan_name']  = $plan->plan_name;
        $data['plan_price'] = $plan->price;

        if ($isDraft) {
            $data['status'] = 'draft';
            $car            = $this->carService->createCar($dealer, $data);
            return $this->apiResponse(
                in_error: false,
                message: "Draft saved successfully",
                status_code: self::API_CREATED,
                data: [
                    'car'     => CarTransformer::summary($car),
                    'payment' => [],
                ],
                reason: "Car saved as draft. No payment created."
            );
        }

        if ($planSlug === 'friend_code') {
            $data['status'] = 'pending_approval';
            $car            = $this->carService->createCar($dealer, $data);
            $payment        = $this->paymentService->createPayment(
                $dealer,
                [$car->car_slug],
                $data['plan_slug'] ?? null,
                $data['plan_name'] ?? null,
                $data['plan_price'] ?? 0.00,
                $data['phone_number'] ?? null,
                $data['network'] ?? null,
                $data['payment_method'] ?? 'friend_code',
                $data['plan_details']
            );
            Approval::create([
                'car_slug'     => $car->car_slug,
                'dealer_slug'  => $dealer->dealer_slug,
                'status'       => 'pending',
                'type'         => "friend_code",
                'dealer_code'  => $data['dealer_code'],
                'payment_slug' => $payment->payment_slug,
                'dealer_name'  => $dealer->full_name ?? $dealer->business_name,
            ]);
            return $this->apiResponse(
                in_error: false,
                message: "Car submitted for approval",
                status_code: self::API_CREATED,
                data: [
                    'car'     => CarTransformer::summary($car),
                    'payment' => $payment,
                ],
                reason: "Free trial listing submitted. Pending dealer and admin approval before publish."
            );
        }

        $data['status'] = 'pending_payment';
        $car            = $this->carService->createCar($dealer, $data);
        $payment        = $this->paymentService->createPayment(
            $dealer,
            [$car->car_slug],
            $data['plan_slug'] ?? null,
            $data['plan_name'] ?? null,
            $data['plan_price'] ?? 0.00,
            $data['phone_number'] ?? null,
            $data['network'] ?? null,
            $data['payment_method'] ?? null,
            $data['plan_details'] ?? null
        );
        return $this->apiResponse(
            in_error: false,
            message: "Car uploaded successfully",
            status_code: self::API_CREATED,
            data: [
                'car'     => CarTransformer::summary($car),
                'payment' => $payment,
                // 'payment_url' => url("/api/dealer/check_payment?reference_id={$payment->reference_id}"),
            ],
            reason: "Car created. Initiate payment to publish."
        );
    }

    public function saveDraft(CarUploadRequest $request): JsonResponse
    {
        $dealer         = $request->user();
        $data           = $request->validated();
        $data['status'] = 'draft';
        $car            = $this->carService->createCar($dealer, $data);
        $car->load('dealer');
        return $this->apiResponse(
            in_error: false,
            message: "Draft saved successfully",
            status_code: self::API_CREATED,
            data: CarTransformer::summary($car),
            reason: "Dealer draft created successfully."
        );
    }

    public function listCars(Request $request): JsonResponse
    {
        $dealer = $request->user();

        // Get paginated cars
        $cars = $dealer->cars()
            ->whereNull('deleted_at')
            ->paginate(15);

        // Get all car slugs from the current page
        $carSlugs = $cars->getCollection()->pluck('car_slug')->toArray();

        // Eager load payments for all these cars in a single query
        $payments = Payment::where(function ($query) use ($carSlugs) {
            foreach ($carSlugs as $slug) {
                $query->orWhereJsonContains('car_slugs', $slug);
            }
        })->get();

        // Group payments by car_slug for easy access
        $paymentsByCar = [];
        foreach ($payments as $payment) {
            foreach ($payment->car_slugs as $carSlug) {
                if (in_array($carSlug, $carSlugs)) {
                    if (! isset($paymentsByCar[$carSlug])) {
                        $paymentsByCar[$carSlug] = [];
                    }
                    $paymentsByCar[$carSlug][] = $payment;
                }
            }
        }

        // Attach payment info to each car
        $cars->getCollection()->each(function ($car) use ($paymentsByCar) {
            $car->payment_info   = $paymentsByCar[$car->car_slug] ?? [];
            $car->latest_payment = $paymentsByCar[$car->car_slug][0] ?? null;
        });

        // Transform cars
        $items = $cars->getCollection()
            ->map(fn($car) => CarTransformer::summary($car))
            ->values()
            ->all();

        $payload = [
            'items' => $items,
            'meta'  => [
                'current_page' => $cars->currentPage(),
                'last_page'    => $cars->lastPage(),
                'per_page'     => $cars->perPage(),
                'total'        => $cars->total(),
            ],
        ];

        return $this->apiResponse(
            in_error: false,
            message: "Cars retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $payload,
            reason: "Dealer cars retrieved successfully."
        );
    }

    public function listDrafts(Request $request): JsonResponse
    {
        $dealer = $request->user();
        $cars   = $dealer->cars()->where('status', 'draft')->whereNull('deleted_at')->paginate(15);

        $items = $cars->getCollection()->load('dealer')->map(fn($car) => CarTransformer::summary($car))->all();

        $payload = [
            'items' => $items,
            'meta'  => [
                'current_page' => $cars->currentPage(),
                'last_page'    => $cars->lastPage(),
                'per_page'     => $cars->perPage(),
                'total'        => $cars->total(),
            ],
        ];

        return $this->apiResponse(
            in_error: false,
            message: "Drafts retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $payload,
            reason: "Dealer drafts retrieved successfully."
        );
    }

    public function singleCar(Request $request, Car $car): JsonResponse
    {
        $dealer = $request->user();

        abort_if($car->dealer_slug !== $dealer->dealer_slug, 403);

        // Load dealer and manually add payment info
        $car->load(['dealer']);

        // Add payment info as a custom attribute
        $car->payment_info = Payment::whereJsonContains('car_slugs', $car->car_slug)->first();

        return $this->apiResponse(
            in_error: false,
            message: "Car retrieved successfully",
            status_code: self::API_SUCCESS,
            data: CarTransformer::summary($car)
        );
    }

    public function getDraft(Request $request, Car $car): JsonResponse
    {
        $dealer = $request->user();

        return $this->apiResponse(
            in_error: false,
            message: "Draft retrieved successfully",
            status_code: self::API_SUCCESS,
            data: CarTransformer::summary($car)
        );
    }

// public function updateCar(CarUploadRequest $request, Car $car): JsonResponse
// {
//     $data = $request->validated();

//     $car = $this->carService->updateCar($car, $data);

//     return $this->apiResponse(
//         in_error: false,
//         message: "Car updated successfully",
//         status_code: self::API_SUCCESS,
//         data: CarTransformer::summary($car)
//     );
// }

// public function updateCar(CarUploadRequest $request, Car $car): JsonResponse
// {
//     $data   = $request->validated();
//     $dealer = $request->user();

//     // Check if plan is being changed
//     if (isset($data['plan_slug']) && $data['plan_slug'] !== $car->plan_slug) {
//         // Handle plan change logic
//         $plan = Plan::where("plan_slug", $data['plan_slug'])->first();

//         if (! $plan) {
//             return $this->apiResponse(
//                 in_error: true,
//                 message: "Invalid plan selected",
//                 status_code: self::API_BAD_REQUEST,
//                 reason: "The specified plan does not exist"
//             );
//         }

//         // If changing to free trial
//         if ($data['plan_slug'] === 'free_trial') {
//             $data['status'] = 'pending_approval';
//             // Create approval record if needed
//             Approval::updateOrCreate(
//                 ['car_slug' => $car->car_slug],
//                 [
//                     'dealer_slug' => $car->dealer_slug,
//                     'dealer_code' => $data['dealer_code'] ?? null,
//                     'dealer_name' => $car->dealer->full_name ?? $car->dealer->business_name,
//                 ]
//             );
//             return $this->apiResponse(
//                 in_error: false,
//                 message: "Car submitted for approval",
//                 status_code: self::API_CREATED,
//                 data: CarTransformer::summary($car),
//                 reason: "Free trial listing submitted. Pending dealer and admin approval before publish."
//             );
//         } else {
//             // If changing to paid plan, might need new payment
//             $data['status'] = 'pending_payment';
//             // You might want to create a new payment here
//             $payment = $this->paymentService->createPayment(
//                 $dealer,
//                 [$car->car_slug],
//                 $data['plan_slug'] ?? null,
//                 $data['plan_name'] ?? null,
//                 $data['duration_days'] ?? null,
//                 $data['price'] ?? null,
//                 $data['features'] ?? null,
//                 $data['phone_number'] ?? null,
//                 $data['network'] ?? null,
//                 $data['payment_method'] ?? null
//             );
//             return $this->apiResponse(
//                 in_error: false,
//                 message: "Car uploaded successfully",
//                 status_code: self::API_CREATED,
//                 data: [
//                     'car'         => CarTransformer::summary($car),
//                     'payment'     => $payment,
//                     'payment_url' => url("/api/dealer/check_payment?reference_id={$payment->reference_id}"),
//                 ],
//                 reason: "Car created. Initiate payment to publish."
//             );
//         }
//     }

//     $car = $this->carService->updateCar($car, $data);

//     return $this->apiResponse(
//         in_error: false,
//         message: "Car updated successfully",
//         status_code: self::API_SUCCESS,
//         data: CarTransformer::summary($car)
//     );
// }

    public function deleteCar(Request $request, Car $car): JsonResponse
    {
        $dealer = $request->user();

        if ($car->dealer_slug !== $dealer->dealer_slug) {
            return $this->apiResponse(
                in_error: true,
                message: "Unauthorized action.",
                status_code: self::API_FORBIDDEN,
                reason: "Unauthorized action."
            );
        }

        $car->delete();

        return self::apiResponse(
            in_error: false,
            message: "Car deleted successfully",
            status_code: self::API_SUCCESS,
            reason: "Car deleted successfully.",
            data: []
        );
    }

    public function publishAllDrafts(): JsonResponse
    {
        $dealer_slug = auth()->user()->dealer_slug;

        // Find the dealer first
        $dealer = Dealer::where('dealer_slug', $dealer_slug)->first();

        if (! $dealer) {
            return $this->apiResponse(
                in_error: true,
                message: "Dealer not found",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        // Get all draft cars for this dealer
        $draftCars = Car::where('dealer_slug', $dealer_slug)
            ->where('status', 'draft')
            ->get();

        if ($draftCars->isEmpty()) {
            return $this->apiResponse(
                in_error: true,
                message: "No draft cars found for this dealer",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        // Update all draft cars to pending_approval
        foreach ($draftCars as $car) {
            $car->update(['status' => 'pending']);
        }

        // Transform the updated cars for response
        $updatedCars = $draftCars->map(fn($car) => CarTransformer::summary($car))->toArray();

        return $this->apiResponse(
            in_error: false,
            message: "All draft cars published successfully",
            status_code: self::API_SUCCESS,
            data: $updatedCars,
        );
    }

// public function approvals(Request $request): JsonResponse
// {
//     $dealer = $request->user();

//     if (! $dealer->dealer_code) {
//         return $this->apiResponse(
//             in_error: false,
//             message: "No approvals pending",
//             status_code: self::API_SUCCESS,
//             data: ['items' => [], 'meta' => ['total' => 0]]
//         );
//     }

//     $approvals = Approval::where('dealer_code', $dealer->dealer_code)
//         ->where('dealer_approval', false)
//         ->whereNull('admin_approval_at')
//         ->with(['car', 'dealer'])
//         ->paginate(15);

//     $items = $approvals->getCollection()->map(function ($approval) {
//         $car = $approval->car;
//         return $car ? CarTransformer::summary($car) : null;
//     })->filter()->values()->all();

//     return $this->apiResponse(
//         in_error: false,
//         message: "Approval list retrieved successfully",
//         status_code: self::API_SUCCESS,
//         data: [
//             'items' => $items,
//             'meta'  => [
//                 'current_page' => $approvals->currentPage(),
//                 'last_page'    => $approvals->lastPage(),
//                 'per_page'     => $approvals->perPage(),
//                 'total'        => $approvals->total(),
//             ],
//         ]
//     );
// }

// public function approveCar(Request $request, $id): JsonResponse
// {
//     $dealer = $request->user();

//     $approval = Approval::where('dealer_code', $dealer->dealer_code)
//         ->where('dealer_approval', false)
//         ->findOrFail($id);

//     $approval->update([
//         'dealer_approval'    => true,
//         'dealer_approval_at' => now(),
//     ]);

//     $car = Car::where('car_slug', $approval->car_slug)->first();
//     if ($car) {
//         $car->update(['status' => 'pending_admin_approval']);
//     }

//     return $this->apiResponse(
//         in_error: false,
//         message: "Car approved successfully",
//         status_code: self::API_SUCCESS,
//         data: $car ? CarTransformer::summary($car->fresh('dealer')) : null
//     );
// }

// public function rejectCar(Request $request, $id): JsonResponse
// {
//     $dealer = $request->user();

//     $approval = Approval::where('dealer_code', $dealer->dealer_code)->findOrFail($id);
//     $approval->update(['dealer_approval' => false]);

//     $car = Car::where('car_slug', $approval->car_slug)->first();
//     if ($car) {
//         $car->update(['status' => 'rejected']);
//     }

//     return $this->apiResponse(
//         in_error: false,
//         message: "Car rejected successfully",
//         status_code: self::API_SUCCESS,
//         reason: "Car rejected",
//         data: []
//     );
// }

    public function dashboardStats(Request $request): JsonResponse
    {
        $dealer     = $request->user();
        $dealerSlug = $dealer->dealer_slug;

        $carSlugs     = Car::where('dealer_slug', $dealerSlug)->pluck('car_slug');
        $totalViewed  = View::whereIn('car_slug', $carSlugs)->count();
        $expiringSoon = Car::where('dealer_slug', $dealerSlug)
            ->where('status', 'published')
            ->whereBetween('expiry_date', [now(), now()->addDays(3)])
            ->count();
        $totalActiveCars = Car::where('dealer_slug', $dealerSlug)
            ->where('status', 'published')
            ->count();

        return $this->apiResponse(
            in_error: false,
            message: "Dashboard stats retrieved successfully",
            status_code: self::API_SUCCESS,
            data: [
                'total_viewed'      => $totalViewed,
                'expiring_soon'     => $expiringSoon,
                'total_active_cars' => $totalActiveCars,
            ]
        );
    }
}
