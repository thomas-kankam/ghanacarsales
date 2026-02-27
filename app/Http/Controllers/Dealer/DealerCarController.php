<?php
namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dealer\CarUploadRequest;
use App\Http\Resources\CarResource;
use App\Services\AlertService;
use App\Services\CarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealerCarController extends Controller
{
    public function __construct(private CarService $carService, private AlertService $alertService)
    {

    }

    public function uploadCar(CarUploadRequest $request): JsonResponse
    {
        $dealer = $request->user();
        $data   = $request->validated();
        // $images = $request->file('images');

        $car = $this->carService->createCar($dealer, $data);

        // Check if this car matches any buyer alerts (only if car is active)
        // Note: New cars start as 'pending', alerts will be checked when status changes to 'active' via Observer

        return $this->apiResponse(
            in_error: false,
            message: "Car uploaded successfully",
            status_code: self::API_CREATED,
            data: new CarResource($car->load(['brand', 'model', 'images']))
        );
    }

    public function listCars(Request $request): JsonResponse
    {
        $seller = $request->user();
        $cars   = $seller->cars()->with(['brand', 'model', 'images'])->paginate(15);

        // return $this->apiResponse(
        //     in_error: false,
        //     message: "Cars retrieved successfully",
        //     data: CarResource::collection($cars)->response()->getData(true)
        // );

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            status_code: self::API_SUCCESS,
            data: $dealer->fresh()->toArray(),
            reason: "Dealer created successfully. $message"
        );
    }

    public function singleCar(Request $request, $id): JsonResponse
    {
        $seller = $request->user();
        $car    = $seller->cars()->with(['brand', 'model', 'images'])->findOrFail($id);

        return $this->apiResponse(
            in_error: false,
            message: "Car retrieved successfully",
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
}
