<?php
namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Buyer\BuyerSearchRequest;
use App\Models\Car;
use App\Models\View;
use App\Services\CarSearchService;
use App\Transformers\CarTransformer;
use Illuminate\Http\JsonResponse;

class BuyerCarController extends Controller
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
            ->load(['dealer'])
            ->map(fn($car) => CarTransformer::summary($car))
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
            reason: "Action successful",
            status_code: self::API_SUCCESS,
            data: $payload
        );
    }

    public function show(Car $car): JsonResponse
    {
        if ($car->status !== 'published') {
            abort(404);
        }
        View::create([
            'car_slug' => $car->car_slug,
        ]);
        $car->load('dealer');
        return $this->apiResponse(
            in_error: false,
            message: "Car retrieved successfully",
            reason: "Action successfully",
            status_code: self::API_SUCCESS,
            data: CarTransformer::summary($car)
        );
    }

    public function getDealerCars($dealer_slug): JsonResponse
    {
        $cars = Car::where('dealer_slug', $dealer_slug)
            ->where('status', 'published')
            ->with('dealer')
            ->paginate(15);

        $items = $cars->getCollection()
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
            message: "Dealer cars retrieved successfully",
            reason: "Dealer cars action successfully",
            status_code: self::API_SUCCESS,
            data: $payload
        );
    }
}
