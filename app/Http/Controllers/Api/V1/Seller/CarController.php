<?php

namespace App\Http\Controllers\Api\V1\Seller;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Seller\CarUploadRequest;
use App\Http\Resources\CarResource;
use App\Services\AlertService;
use App\Services\CarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CarController extends BaseApiController
{
    public function __construct(
        private CarService $carService,
        private AlertService $alertService
    ) {}

    public function upload(CarUploadRequest $request): JsonResponse
    {
        $seller = $request->user();
        $data = $request->validated();
        $images = $request->file('images');

        $car = $this->carService->createCar($seller, $data, $images);

        // Check if this car matches any buyer alerts (only if car is active)
        // Note: New cars start as 'pending', alerts will be checked when status changes to 'active' via Observer

        return $this->apiResponse(
            in_error: false,
            message: "Car uploaded successfully",
            status_code: self::API_CREATED,
            data: new CarResource($car->load(['brand', 'model', 'images']))
        );
    }

    public function index(Request $request): JsonResponse
    {
        $seller = $request->user();
        $cars = $seller->cars()->with(['brand', 'model', 'images'])->paginate(15);

        return $this->apiResponse(
            in_error: false,
            message: "Cars retrieved successfully",
            data: CarResource::collection($cars)->response()->getData(true)
        );
    }

    public function show(Request $request, $id): JsonResponse
    {
        $seller = $request->user();
        $car = $seller->cars()->with(['brand', 'model', 'images'])->findOrFail($id);

        return $this->apiResponse(
            in_error: false,
            message: "Car retrieved successfully",
            data: new CarResource($car)
        );
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $seller = $request->user();
        $car = $seller->cars()->findOrFail($id);

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
}
