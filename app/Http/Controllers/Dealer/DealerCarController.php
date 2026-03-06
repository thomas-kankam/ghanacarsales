<?php
namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dealer\CarUploadRequest;
use App\Http\Resources\CarResource;
use App\Models\Car;
use App\Models\Dealer;
use App\Models\Approval;
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

        $isDraft = ($data['status'] ?? '') === 'draft';
        $planSlug = $data['plan_slug'] ?? null;

        if ($isDraft) {
            $data['status'] = 'draft';
            $car = $this->carService->createCar($dealer, $data);
            $car->load('dealer');
            return $this->apiResponse(
                in_error: false,
                message: "Draft saved successfully",
                status_code: self::API_CREATED,
                data: CarTransformer::summary($car),
                reason: "Car saved as draft. No payment created."
            );
        }

        $durationDays = match ($planSlug) {
            'free_trial' => 15,
            '1_month'    => 30,
            '3_months'   => 90,
            default      => 15,
        };
        $planName = match ($planSlug) {
            'free_trial' => 'Free Trial',
            '1_month'    => '1 Month',
            '3_months'   => '3 Months',
            default      => 'Custom',
        };
        $data['plan_slug']     = $planSlug;
        $data['plan_name']    = $planName;
        $data['duration_days'] = $durationDays;

        if ($planSlug === 'free_trial') {
            $data['status'] = 'pending_approval';
            $car = $this->carService->createCar($dealer, $data);
            Approval::create([
                'car_slug'       => $car->car_slug,
                'dealer_slug'    => $dealer->dealer_slug,
                'dealer_code'    => $data['dealer_code'],
                'dealer_name'    => $dealer->full_name ?? $dealer->business_name,
            ]);
            $car->load('dealer');
            return $this->apiResponse(
                in_error: false,
                message: "Car submitted for approval",
                status_code: self::API_CREATED,
                data: CarTransformer::summary($car),
                reason: "Free trial listing submitted. Pending dealer and admin approval before publish."
            );
        }

        $data['status'] = 'pending_payment';
        $car = $this->carService->createCar($dealer, $data);
        $car->load('dealer');
        return $this->apiResponse(
            in_error: false,
            message: "Car uploaded successfully",
            status_code: self::API_CREATED,
            data: array_merge(CarTransformer::summary($car), [
                'next_step' => 'Call POST /api/dealer/payment/create with car_slugs and plan_slug to initiate payment.',
            ]),
            reason: "Car created. Initiate payment to publish."
        );
    }

    public function saveDraft(CarUploadRequest $request): JsonResponse
    {
        $dealer = $request->user();
        $data   = $request->validated();
        $data['status'] = 'draft';
        $car = $this->carService->createCar($dealer, $data);
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
        $cars   = $dealer->cars()->whereNull('deleted_at')->paginate(15);

        $items = $cars->getCollection()
            ->load(['dealer'])
            ->map(fn($car) => CarTransformer::summary($car))
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

        // Ensure car belongs to dealer
        abort_if($car->dealer_slug !== $dealer->dealer_slug, 403);

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

    public function updateCar(CarUploadRequest $request, Car $car): JsonResponse
    {
        $data = $request->validated();

        $car = $this->carService->updateCar($car, $data);

        return $this->apiResponse(
            in_error: false,
            message: "Car updated successfully",
            status_code: self::API_SUCCESS,
            data: CarTransformer::summary($car)
        );
    }

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

    public function approvals(Request $request): JsonResponse
    {
        $dealer = $request->user();

        if (! $dealer->dealer_code) {
            return $this->apiResponse(
                in_error: false,
                message: "No approvals pending",
                status_code: self::API_SUCCESS,
                data: ['items' => [], 'meta' => ['total' => 0]]
            );
        }

        $approvals = \App\Models\Approval::where('dealer_code', $dealer->dealer_code)
            ->where('dealer_approval', false)
            ->whereNull('admin_approval_at')
            ->with('car')
            ->paginate(15);

        $items = $approvals->getCollection()->map(function ($approval) {
            $car = $approval->car;
            return $car ? CarTransformer::summary($car) : null;
        })->filter()->values()->all();

        return $this->apiResponse(
            in_error: false,
            message: "Approval list retrieved successfully",
            status_code: self::API_SUCCESS,
            data: [
                'items' => $items,
                'meta'  => [
                    'current_page' => $approvals->currentPage(),
                    'last_page'    => $approvals->lastPage(),
                    'per_page'     => $approvals->perPage(),
                    'total'        => $approvals->total(),
                ],
            ]
        );
    }

    public function approveCar(Request $request, $id): JsonResponse
    {
        $dealer = $request->user();

        $approval = \App\Models\Approval::where('dealer_code', $dealer->dealer_code)
            ->where('dealer_approval', false)
            ->findOrFail($id);

        $approval->update([
            'dealer_approval'    => true,
            'dealer_approval_at' => now(),
        ]);

        $car = Car::where('car_slug', $approval->car_slug)->first();
        if ($car) {
            $car->update(['status' => 'pending_admin_approval']);
        }

        return $this->apiResponse(
            in_error: false,
            message: "Car approved successfully",
            status_code: self::API_SUCCESS,
            data: $car ? CarTransformer::summary($car->fresh('dealer')) : null
        );
    }

    public function rejectCar(Request $request, $id): JsonResponse
    {
        $dealer = $request->user();

        $approval = \App\Models\Approval::where('dealer_code', $dealer->dealer_code)->findOrFail($id);
        $approval->update(['dealer_approval' => false]);

        $car = Car::where('car_slug', $approval->car_slug)->first();
        if ($car) {
            $car->update(['status' => 'rejected']);
        }

        return $this->apiResponse(
            in_error: false,
            message: "Car rejected successfully",
            status_code: self::API_SUCCESS
        );
    }

    public function dashboardStats(Request $request): JsonResponse
    {
        $dealer = $request->user();
        $dealerSlug = $dealer->dealer_slug;

        $carSlugs = Car::where('dealer_slug', $dealerSlug)->pluck('car_slug');
        $totalViewed = \App\Models\View::whereIn('car_slug', $carSlugs)->count();
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
