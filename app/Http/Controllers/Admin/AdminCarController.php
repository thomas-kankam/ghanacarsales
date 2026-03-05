<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Services\CarService;
use App\Transformers\CarTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCarController extends Controller
{
    public function __construct(private CarService $carService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Car::with(['dealer', 'brand', 'model', 'images']);

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $cars = $query->paginate((int) $request->get('per_page', 20));

        $items = collect($cars->items())
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
            message: "Cars retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $payload
        );
    }

    public function show($id): JsonResponse
    {
        $car = Car::with(['dealer', 'brand', 'model', 'images'])->findOrFail($id);

        return $this->apiResponse(
            in_error: false,
            message: "Car retrieved successfully",
            status_code: self::API_SUCCESS,
            data: CarTransformer::summary($car)
        );
    }

    public function approve($id): JsonResponse
    {
        $car = Car::findOrFail($id);

        $this->carService->activateCar($car);

        $car->update([
            'admin_approval' => true,
            'is_published'   => true,
        ]);

        return $this->apiResponse(
            in_error: false,
            message: "Car approved successfully",
            status_code: self::API_SUCCESS,
            data: CarTransformer::summary($car->fresh('dealer', 'brand', 'model', 'images'))
        );
    }

    public function reject(Request $request, $id): JsonResponse
    {
        $car = Car::findOrFail($id);

        $car->update([
            'admin_approval' => false,
            'status'         => 'rejected',
        ]);

        return $this->apiResponse(
            in_error: false,
            message: "Car rejected successfully",
            status_code: self::API_SUCCESS
        );
    }

    public function forceExpire($id): JsonResponse
    {
        $car = Car::findOrFail($id);

        $car->update([
            'status'     => 'expired',
            'expires_at' => now(),
        ]);

        return $this->apiResponse(
            in_error: false,
            message: "Car expired successfully",
            status_code: self::API_SUCCESS
        );
    }

    public function destroy($id): JsonResponse
    {
        $car = Car::findOrFail($id);
        $car->delete();

        return $this->apiResponse(
            in_error: false,
            message: "Car deleted successfully",
            status_code: self::API_NO_CONTENT
        );
    }

    public function trashed(Request $request): JsonResponse
    {
        $cars = Car::onlyTrashed()->paginate((int) $request->get('per_page', 20));

        $items = collect($cars->items())
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
            message: "Trashed cars retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $payload
        );
    }

    public function restore($id): JsonResponse
    {
        $car = Car::onlyTrashed()->findOrFail($id);
        $car->restore();

        return $this->apiResponse(
            in_error: false,
            message: "Car restored successfully",
            status_code: self::API_SUCCESS,
            data: CarTransformer::summary($car->fresh())
        );
    }

    public function forceDelete($id): JsonResponse
    {
        $car = Car::onlyTrashed()->findOrFail($id);
        $car->forceDelete();

        return $this->apiResponse(
            in_error: false,
            message: "Car permanently deleted",
            status_code: self::API_NO_CONTENT
        );
    }
}

