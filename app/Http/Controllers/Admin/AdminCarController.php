<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Approval;
use App\Models\Car;
use App\Models\Payment;
use App\Models\Plan;
use App\Services\ApprovalService;
use App\Services\CarService;
use App\Transformers\CarTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCarController extends Controller
{
    public function __construct(
        private CarService $carService,
        private ApprovalService $approvalService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Car::with(['dealer'])
        ->where('status', '!=', 'draft')
        ->whereNull('deleted_at')
        ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $cars = $query->paginate((int) $request->get('per_page', 20));

        $items = collect($cars->items())
            ->map(fn($car) => CarTransformer::summary($car->load(['paymentItems.payment', 'dealer'])))
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
        $car = Car::with(['paymentItems.payment', 'dealer'])->findOrFail($id);

        return $this->apiResponse(
            in_error: false,
            message: "Car retrieved successfully",
            status_code: self::API_SUCCESS,
            data: CarTransformer::summary($car->load(['paymentItems.payment', 'dealer']))
        );
    }

    public function approve(Request $request, $id): JsonResponse
    {
        $car = Car::findOrFail($id);
        $approval = Approval::where('car_slug', $car->car_slug)->whereIn('status', ['pending'])->latest()->first();

        $durationDays = 15;
        $plan = null;
        if ($car->plan_slug) {
            $plan = Plan::where('plan_slug', $car->plan_slug)->first();
        }
        if (!$plan && $approval && $approval->payment_slug) {
            $payment = Payment::where('payment_slug', $approval->payment_slug)->first();
            $plan = $payment ? Plan::where('plan_slug', $payment->plan_slug)->first() : null;
        }
        $durationDays = $plan ? (int) $plan->duration_days : 15;

        $this->carService->activateCar($car, $durationDays);
        if ($approval) {
            $this->approvalService->approve($approval, $request->user()?->admin_slug);
        }

        return $this->apiResponse(
            in_error: false,
            message: "Car approved successfully",
            status_code: self::API_SUCCESS,
            data: CarTransformer::summary($car->fresh()->load(['paymentItems.payment', 'dealer']))
        );
    }

    public function reject(Request $request, $id): JsonResponse
    {
        $car = Car::findOrFail($id);
        $approval = Approval::where('car_slug', $car->car_slug)->whereIn('status', ['pending'])->latest()->first();

        if ($approval) {
            $this->approvalService->reject($approval, $request->input('reason'), $request->user()?->admin_slug);
        }
        $car->update(['status' => 'rejected']);

        $approval = $approval ? $approval->fresh() : null;
        return $this->apiResponse(
            in_error: false,
            message: "Car rejected successfully",
            status_code: self::API_SUCCESS,
            data: [
                'car'               => CarTransformer::summary($car->fresh()->load(['paymentItems.payment', 'dealer'])),
                'rejection_reason'  => $approval?->reason,
            ]
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

    /**
     * Revert car from published/rejected/expired back to pending_approval.
     * Resets the latest approval for this car to pending and sets car status to pending_approval.
     */
    public function revertApproval(Request $request, $id): JsonResponse
    {
        $car = Car::findOrFail($id);
        $approval = Approval::where('car_slug', $car->car_slug)->latest()->first();

        if (!$approval) {
            return $this->apiResponse(
                in_error: true,
                message: "No approval found for this car",
                status_code: self::API_BAD_REQUEST,
                data: []
            );
        }

        $this->approvalService->revertToPending($approval);

        return $this->apiResponse(
            in_error: false,
            message: "Car reverted to pending approval successfully",
            status_code: self::API_SUCCESS,
            data: CarTransformer::summary($car->fresh()->load(['paymentItems.payment', 'dealer']))
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
            ->map(fn($car) => CarTransformer::summary($car->load(['paymentItems.payment', 'dealer'])))
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
            data: CarTransformer::summary($car->fresh()->load(['paymentItems.payment', 'dealer'])   )
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
