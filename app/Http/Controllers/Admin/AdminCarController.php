<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Approval;
use App\Models\Car;
use App\Models\Payment;
use App\Services\CarService;
use App\Transformers\CarTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminCarController extends Controller
{
    public function __construct(private CarService $carService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Car::with(['dealer']);

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $cars = $query->paginate((int) $request->get('per_page', 20));

        $items = collect($cars->items())
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
            data: $payload
        );
    }

    public function show($id): JsonResponse
    {
        $car = Car::with(['dealer'])->findOrFail($id);

        return $this->apiResponse(
            in_error: false,
            message: "Car retrieved successfully",
            status_code: self::API_SUCCESS,
            data: CarTransformer::summary($car)
        );
    }

    public function approve(Request $request, $id): JsonResponse
    {
        $car          = Car::findOrFail($id);
        $durationDays = (int) ($car->duration_days ?: 15);
        $this->carService->activateCar($car, $durationDays);

        $approval = Approval::where('car_slug', $car->car_slug)->first();
        if ($approval) {
            $approval->update([
                'admin_approval'    => true,
                'admin_approval_at' => now(),
                'admin_slug'        => $request->user()?->admin_slug ?? 'system',
                'status' => ''
            ]);
            // Payment::create([
            //     'payment_slug'   => Str::uuid()->toString(),
            //     'dealer_slug'    => $car->dealer_slug,
            //     'plan_name'      => $car->plan_name ?? 'Friend Code',
            //     'plan_slug'      => $car->plan_slug ?? 'friend_code',
            //     'amount'         => 0,
            //     'payment_method' => 'friend_code',
            //     'status'         => 'paid',
            //     'duration_days'  => $durationDays,
            //     'car_slugs'      => [$car->car_slug],
            // ]);
            // $subscription = Subscription::create([
            //     'dealer_slug'        => $car->dealer_slug,
            //     'subscription_slug'  => Str::uuid()->toString(),
            //     'plan_slug'         => $car->plan_slug ?? 'free_trial',
            //     'plan_name'         => $car->plan_name ?? 'Free Trial',
            //     'duration_days'     => (string) $durationDays,
            //     'starts_at'         => $car->start_date ?? now(),
            //     'expiry_date'       => $car->expiry_date,
            //     'status'            => 'active',
            //     'price'             => 0,
            // ]);
            // SubscriptionArchive::create([
            //     'dealer_slug'        => $car->dealer_slug,
            //     'subscription_slug'  => $subscription->subscription_slug,
            //     'plan_slug'          => $car->plan_slug ?? 'free_trial',
            //     'plan_name'          => $car->plan_name ?? 'Free Trial',
            //     'duration_days'      => (string) $durationDays,
            //     'price'              => 0,
            //     'status'             => 'completed',
            // ]);
        }

        return $this->apiResponse(
            in_error: false,
            message: "Car approved successfully",
            status_code: self::API_SUCCESS,
            data: CarTransformer::summary($car->fresh())
        );
    }

    public function reject(Request $request, $id): JsonResponse
    {
        $car = Car::findOrFail($id);

        $car->update(['status' => 'rejected']);

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
            'status'      => 'expired',
            'expiry_date' => now(),
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
