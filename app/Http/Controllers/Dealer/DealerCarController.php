<?php
namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dealer\CarUploadRequest;
use App\Http\Resources\CarResource;
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
            data: new CarResource($car),
            reason: "Car uploaded successfully and is pending review. It will be activated within 24 hours if it meets our guidelines."
        );
    }

    public function saveDraft(CarUploadRequest $request): JsonResponse
    {
        $dealer         = $request->user();
        $data           = $request->validated();
        $data['status'] = 'draft';

        $car = $this->carService->createCar($dealer, $data);

        return $this->apiResponse(
            in_error: false,
            message: "Draft saved successfully",
            status_code: self::API_CREATED,
            data: new CarResource($car),
            reason: "Car draft created successfully."
        );
    }

    public function listCars(Request $request): JsonResponse
    {
        $dealer = $request->user();
        $cars   = $dealer->cars()
            ->with(['brand', 'model', 'images'])
            ->whereNull('deleted_at')
            ->paginate(15);

        $items = $cars->getCollection()
            ->load(['brand', 'model', 'images', 'dealer'])
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
        $cars   = $dealer->cars()
            ->with(['brand', 'model', 'images'])
            ->where('status', 'draft')
            ->whereNull('deleted_at')
            ->paginate(15);

        $items = $cars->getCollection()
            ->load(['brand', 'model', 'images', 'dealer'])
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
            message: "Drafts retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $payload,
            reason: "Dealer drafts retrieved successfully."
        );
    }

    public function singleCar(Request $request, $id): JsonResponse
    {
        $dealer = $request->user();
        $car    = $dealer->cars()->with(['brand', 'model', 'images'])->findOrFail($id);

        return $this->apiResponse(
            in_error: false,
            message: "Car retrieved successfully",
            status_code: self::API_SUCCESS,
            data: new CarResource($car)
        );
    }

    public function getDraft(Request $request, $id): JsonResponse
    {
        $dealer = $request->user();
        $car    = $dealer->cars()
            ->with(['brand', 'model', 'images'])
            ->where('status', 'draft')
            ->findOrFail($id);

        return $this->apiResponse(
            in_error: false,
            message: "Draft retrieved successfully",
            status_code: self::API_SUCCESS,
            data: new CarResource($car)
        );
    }

    public function updateCar(CarUploadRequest $request, $id): JsonResponse
    {
        $dealer = $request->user();
        $data   = $request->validated();

        $car = $dealer->cars()->findOrFail($id);

        if ($car->status === 'sold' || $car->status === 'deleted') {
            return $this->apiResponse(
                in_error: true,
                message: "Cannot update sold or deleted car",
                status_code: self::API_BAD_REQUEST
            );
        }

        $car = $this->carService->updateCar($car, $data);

        return $this->apiResponse(
            in_error: false,
            message: "Car updated successfully",
            status_code: self::API_SUCCESS,
            data: new CarResource($car)
        );
    }

    public function deleteCar(Request $request, $id): JsonResponse
    {
        $seller = $request->user();
        $car    = $seller->cars()->findOrFail($id);

        if ($car->status === 'sold') {
            return $this->apiResponse(
                in_error: true,
                message: "Cannot delete sold car",
                status_code: self::API_BAD_REQUEST
            );
        }

        $car->update(['status' => 'deleted']);

        return $this->apiResponse(
            in_error: false,
            message: "Car deleted successfully",
            status_code: self::API_NO_CONTENT
        );
    }

    public function publishDraft(Request $request, $id): JsonResponse
    {
        $dealer = $request->user();

        $car = $dealer->cars()
            ->where('status', 'draft')
            ->findOrFail($id);

        if (! empty($car->dealer_code)) {
            return $this->apiResponse(
                in_error: true,
                message: "This car requires sponsor approval before it can be published.",
                status_code: self::API_BAD_REQUEST
            );
        }

        $subscription = $this->subscriptionService->getCurrentSubscription($dealer);
        if (! $subscription || ! $subscription->plan) {
            return $this->apiResponse(
                in_error: true,
                message: "No active subscription plan. Please subscribe to a plan before publishing.",
                status_code: self::API_FORBIDDEN
            );
        }

        $activeCount = $dealer->cars()
            ->whereIn('status', ['active', 'pending_admin_approval'])
            ->count();

        if ($activeCount >= $subscription->plan->publish_quota) {
            return $this->apiResponse(
                in_error: true,
                message: "Publish quota exceeded for current subscription plan.",
                status_code: self::API_FORBIDDEN
            );
        }

        $car->update([
            'status'          => 'pending_admin_approval',
            'dealer_approval' => true,
        ]);

        return $this->apiResponse(
            in_error: false,
            message: "Car submitted for approval",
            status_code: self::API_SUCCESS,
            data: new CarResource($car->fresh('brand', 'model', 'images')),
            reason: "Car is pending admin approval before it becomes active."
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
