<?php
namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Buyer\BuyerSearchRequest;
use App\Http\Resources\CarResource;
use App\Services\CarSearchService;
use App\Transformers\CarTransformer;
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

        $items = $results->getCollection()
            ->load(['brand', 'model', 'images', 'dealer'])
            ->map(fn ($car) => CarTransformer::summary($car))
            ->all();

        $payload = [
            'items' => $items,
            'meta'  => [
                'current_page' => $results->currentPage(),
                'last_page'    => $results->lastPage(),
                'per_page'     => $results->perPage(),
                'total'        => $results->total(),
            ],
        ];

        return $this->apiResponse(
            in_error: false,
            message: "Search results retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $payload
        );
    }

    public function show($id): JsonResponse
    {
        $car = \App\Models\Car::with(['brand', 'model', 'images', 'dealer'])
            ->where('status', 'active')
            ->findOrFail($id);

        return $this->apiResponse(
            in_error: false,
            message: "Car retrieved successfully",
            status_code: self::API_SUCCESS,
            data: CarTransformer::summary($car)
        );
    }

    public function getDealerCars($dealerId): JsonResponse
    {
        $cars = \App\Models\Car::with(['brand', 'model', 'images'])
            ->where('dealer_id', $dealerId)
            ->where('status', 'active')
            ->paginate(15);

        $items = $cars->getCollection()
            ->load(['brand', 'model', 'images', 'dealer'])
            ->map(fn ($car) => CarTransformer::summary($car))
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
            message: "Dealer cars retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $payload
        );
    }
}
