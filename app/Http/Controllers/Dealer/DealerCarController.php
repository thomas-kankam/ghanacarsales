<?php
namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dealer\CarUploadRequest;
use App\Models\Car;
use App\Models\Dealer;
use App\Models\Plan;
use App\Models\View;
use App\Services\ApprovalService;
use App\Services\CarService;
use App\Services\PaymentService;
use App\Services\PaystackService;
use App\Services\SubscriptionService;
use App\Transformers\CarTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DealerCarController extends Controller
{
    public function __construct(
        private CarService $carService,
        private PaymentService $paymentService,
        private ApprovalService $approvalService,
        private SubscriptionService $subscriptionService,
        private PaystackService $paystackService
    ) {}

    public function uploadCar(CarUploadRequest $request): JsonResponse
    {
        $dealer = $request->user();
        $data   = $request->validated();

        return DB::transaction(function () use ($dealer, $data) {

            $isDraft  = ($data['status'] ?? '') === 'draft';
            $planSlug = $data['plan_slug'] ?? null;

            $plan = Plan::where("plan_slug", $planSlug)->first();

            /*
        |--------------------------------------------------------------------------
        | Draft
        |--------------------------------------------------------------------------
        */

            if ($isDraft) {

                $data['status'] = 'draft';

                $car = $this->carService->createCar($dealer, $data);

                return $this->apiResponse(
                    in_error: false,
                    message: "Draft saved successfully",
                    status_code: self::API_CREATED,
                    data: [
                        'car'     => CarTransformer::summary($car),
                        'payment' => null,
                    ]
                );
            }

            /*
        |--------------------------------------------------------------------------
        | Friend code: car (pending_approval) + payment (0) + payment_items + approval
        |--------------------------------------------------------------------------
        */

            if ($planSlug === 'friend_code') {
                $data['status']       = 'pending_approval';
                $data['plan_slug']    = 'friend_code';
                $data['plan_price']   = 0;
                $data['plan_details'] = $data['plan_details'] ?? null;
                $car                  = $this->carService->createCar($dealer, $data);

                $payment = $this->paymentService->createPaymentForCars(
                    $dealer,
                    [$car],
                    $plan,
                    $data['phone_number'] ?? null,
                    $data['network'] ?? null,
                    'friend_code'
                );
                $payment->update(['amount' => 0, 'plan_price' => 0, 'status' => 'paid']);

                $this->approvalService->createForCar(
                    $car->car_slug,
                    $dealer,
                    'friend_code',
                    'pending',
                    $data['dealer_code'] ?? null,
                    $payment->payment_slug
                );

                return $this->apiResponse(
                    in_error: false,
                    message: "Car submitted for approval",
                    status_code: self::API_CREATED,
                    data: [
                        'car' => CarTransformer::summary($car->load('paymentItems.payment')),
                        // 'payment' => $payment->fresh(),
                    ]
                );
            }

            /*
        |--------------------------------------------------------------------------
        | Paid plan: car (pending_payment) + payment + payment_items
        |--------------------------------------------------------------------------
        */

            $data['status']       = 'pending_payment';
            $data['plan_slug']    = $plan->plan_slug;
            $data['plan_price']   = $plan->price;
            $data['plan_details'] = $data['plan_details'] ?? null;
            $car                  = $this->carService->createCar($dealer, $data);

            $payment = $this->paymentService->createPaymentForCars(
                $dealer,
                [$car],
                $plan,
                $data['phone_number'] ?? null,
                $data['network'] ?? null,
                $data['payment_method'] ?? 'momo'
            );

            $paymentUrl = url("/api/dealer/check_payment?reference_id={$payment->reference_id}");
            // if (config('services.paystack.secret_key')) {
            //     $callbackUrl = url('/api/payment/callback');
            //     $result = $this->paystackService->initializeTransaction($payment, $callbackUrl, $dealer->email);
            //     if (!empty($result['authorization_url'])) {
            //         $paymentUrl = $result['authorization_url'];
            //     }
            // }

            return $this->apiResponse(
                in_error: false,
                message: "Car uploaded successfully",
                status_code: self::API_CREATED,
                data: [
                    'car'         => CarTransformer::summary($car->load('paymentItems.payment')),
                    // 'payment'     => $payment->fresh(),
                    'payment_url' => $paymentUrl,
                ],
                reason: "Car created. Complete payment to submit for approval."
            );
        });
    }

    public function saveDraft(CarUploadRequest $request): JsonResponse
    {
        $dealer         = $request->user();
        $data           = $request->validated();
        $data['status'] = 'draft';
        $car            = $this->carService->createCar($dealer, $data);
        $car->load('dealer');
        return $this->apiResponse(
            in_error: false,
            message: "Draft saved successfully",
            status_code: self::API_CREATED,
            data: CarTransformer::summary($car),
            reason: "Dealer draft created successfully."
        );
    }

    public function listCars(Request $request): JsonResponse
    {
        $dealer = $request->user();

        $cars = $dealer->cars()
            ->with(['paymentItems.payment'])
            ->whereNull('deleted_at')
            ->paginate(15);

        $items = collect($cars->items())
            ->map(function ($car) {
                $data = CarTransformer::summary($car);
                return $data;
            })
            ->values()
            ->all();

        return $this->apiResponse(
            in_error: false,
            message: "Cars retrieved successfully",
            status_code: self::API_SUCCESS,
            data: [
                'items' => $items,
                'meta'  => [
                    'current_page' => $cars->currentPage(),
                    'last_page'    => $cars->lastPage(),
                    'per_page'     => $cars->perPage(),
                    'total'        => $cars->total(),
                ],
            ]
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

        abort_if($car->dealer_slug !== $dealer->dealer_slug, 403);

        $car->load('paymentItems.payment');
        $data = CarTransformer::summary($car);

        return $this->apiResponse(
            in_error: false,
            message: "Car retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $data
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

        // Update all draft cars to pending_approval and create approval for each so admin can approve
        foreach ($draftCars as $car) {
            $car->update(['status' => 'pending_approval']);
            $this->approvalService->createForCar(
                $car->car_slug,
                $dealer,
                'listing_review',
                'pending',
                null,
                null
            );
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

    public function dashboardStats(Request $request): JsonResponse
    {
        $dealer     = $request->user();
        $dealerSlug = $dealer->dealer_slug;

        $carSlugs     = Car::where('dealer_slug', $dealerSlug)->pluck('car_slug');
        $totalViewed  = View::whereIn('car_slug', $carSlugs)->count();
        $expiringSoon = Car::where('dealer_slug', $dealerSlug)
            ->where('status', 'published')
            ->whereBetween('expiry_date', [now(), now()->addDays(3)])
            ->count();
        $totalActiveCars = Car::where('dealer_slug', $dealerSlug)
            ->where('status', 'published')
            ->count();

        return $this->apiResponse(
            in_error: false,
            message: "Dashboard stats retrieved successfully",
            status_code: self::API_SUCCESS,
            data: [
                'total_viewed'      => $totalViewed,
                'expiring_soon'     => $expiringSoon,
                'total_active_cars' => $totalActiveCars,
            ]
        );
    }
}
