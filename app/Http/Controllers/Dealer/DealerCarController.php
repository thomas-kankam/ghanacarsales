<?php
namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dealer\CarUploadRequest;
use App\Http\Resources\CarResource;
use App\Models\Car;
use App\Models\Dealer;
use App\Services\AlertService;
use App\Services\CarService;
use App\Services\SubscriptionService;
use App\Transformers\CarTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealerCarController extends Controller
{
    public function __construct(
        private CarService $carService,
        private AlertService $alertService,
        private SubscriptionService $subscriptionService
    ) {
    }

    public function uploadCar(CarUploadRequest $request): JsonResponse
    {
        $dealer = $request->user();
        $data   = $request->validated();
        // $images = $request->file('images');

        $car = $this->carService->createCar($dealer, $data);
        $car->load('dealer');

        // Check if this car matches any buyer alerts (only if car is active)
        // Note: New cars start as 'pending', alerts will be checked when status changes to 'active' via Observer

        return $this->apiResponse(
            in_error: false,
            message: "Car uploaded successfully",
            status_code: self::API_CREATED,
            data: CarTransformer::summary($car),
            reason: "Car uploaded successfully and is pending review. It will be activated within 24 hours if it meets our guidelines."
        );
    }

    public function saveDraft(CarUploadRequest $request, Car $car): JsonResponse
    {
        $dealer = $request->user();
        $data   = $request->validated();

        $car = $this->carService->createCar($dealer, $data);

        return $this->apiResponse(
            in_error: false,
            message: "Draft saved successfully",
            status_code: self::API_CREATED,
            data: CarTransformer::summary($car),
            reason: "Car draft created successfully."
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

    // public function publishDraft(Request $request, Car $car): JsonResponse
    // {
    //     $dealer = $request->user();

    //     if ($car->dealer_slug !== $dealer->dealer_slug) {
    //         return $this->apiResponse(
    //             in_error: true,
    //             message: "Unauthorized action.",
    //             status_code: self::API_FORBIDDEN,
    //             reason: "Unauthorized action."
    //         );
    //     }

    //     $subscription = $this->subscriptionService->getCurrentSubscription($dealer);
    //     if (! $subscription || ! $subscription->plan) {
    //         return $this->apiResponse(
    //             in_error: true,
    //             message: "No active subscription plan. Please subscribe to a plan before publishing.",
    //             status_code: self::API_FORBIDDEN
    //         );
    //     }

    //     $activeCount = $dealer->cars()
    //         ->whereIn('status', ['active', 'pending_admin_approval'])
    //         ->count();

    //     if ($activeCount >= $subscription->plan->publish_quota) {
    //         return $this->apiResponse(
    //             in_error: true,
    //             message: "Publish quota exceeded for current subscription plan.",
    //             status_code: self::API_FORBIDDEN
    //         );
    //     }

    //     $car->update([
    //         'status' => 'active',
    //     ]);

    //     return $this->apiResponse(
    //         in_error: false,
    //         message: "Car published successfully",
    //         status_code: self::API_SUCCESS,
    //         data: new CarResource($car->fresh('brand', 'model', 'images')),
    //         reason: "Car published successfully."
    //     );
    // }

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
                data: []
            );
        }

        $cars = \App\Models\Car::with(['brand', 'model', 'images', 'dealer'])
            ->where('dealer_code', $dealer->dealer_code)
            ->where('dealer_approval', false)
            ->where('status', 'pending_sponsor_approval')
            ->paginate(15);

        $payload = CarResource::collection($cars)->response()->getData(true);

        return $this->apiResponse(
            in_error: false,
            message: "Approval list retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $payload
        );
    }

    public function approveCar(Request $request, $id): JsonResponse
    {
        $dealer = $request->user();

        $car = \App\Models\Car::where('dealer_code', $dealer->dealer_code)
            ->where('status', 'pending_sponsor_approval')
            ->findOrFail($id);

        $car->update([
            'dealer_approval' => true,
            'status'          => 'pending_admin_approval',
        ]);

        return $this->apiResponse(
            in_error: false,
            message: "Car approved successfully",
            status_code: self::API_SUCCESS,
            data: new CarResource($car->fresh('brand', 'model', 'images', 'dealer'))
        );
    }

    public function rejectCar(Request $request, $id): JsonResponse
    {
        $dealer = $request->user();

        $car = \App\Models\Car::where('dealer_code', $dealer->dealer_code)
            ->where('status', 'pending_sponsor_approval')
            ->findOrFail($id);

        $car->update([
            'dealer_approval' => false,
            'status'          => 'rejected',
        ]);

        return $this->apiResponse(
            in_error: false,
            message: "Car rejected successfully",
            status_code: self::API_SUCCESS
        );
    }
}
