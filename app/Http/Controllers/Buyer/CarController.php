<?php
namespace App\Http\Controllers\Buyer;

use App\Http\Requests\Buyer\BuyerSearchRequest;
use App\Http\Resources\CarResource;
use App\Services\CarSearchService;
use Illuminate\Http\JsonResponse;

class CarController extends Controller
{
    public function __construct(private CarSearchService $searchService)
    {

    }

    public function search(BuyerSearchRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $perPage = $request->get('per_page', 15);

        $results = $this->searchService->search($filters, $perPage);

        return $this->apiResponse(
            in_error: false,
            message: "Search results retrieved successfully",
            data: CarResource::collection($results)->response()->getData(true)
        );
    }

    public function show($id): JsonResponse
    {
        $car = \App\Models\Car::with(['brand', 'model', 'images', 'seller'])
            ->where('status', 'active')
            ->findOrFail($id);

        return $this->apiResponse(
            in_error: false,
            message: "Car retrieved successfully",
            data: new CarResource($car)
        );
    }

    public function getDealerCars($sellerId): JsonResponse
    {
        $cars = \App\Models\Car::with(['brand', 'model', 'images'])
            ->where('seller_id', $sellerId)
            ->where('status', 'active')
            ->paginate(15);

        return $this->apiResponse(
            in_error: false,
            message: "Dealer cars retrieved successfully",
            data: CarResource::collection($cars)->response()->getData(true)
        );
    }
}
