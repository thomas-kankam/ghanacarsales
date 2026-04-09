<?php
namespace App\Http\Controllers\Dealer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dealer\CarUploadRequest;
use App\Models\Car;
use App\Models\Dealer;
use App\Models\Payment;
use App\Models\Plan;
use App\Services\ApprovalService;
use App\Services\CarService;
use App\Services\PaymentService;
use App\Services\PaystackService;
use App\Services\SubscriptionService;
use App\Transformers\CarTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
                if (empty($data['dealer_code'])) {
                    return $this->apiResponse(
                        in_error: true,
                        message: "Dealer code is required",
                        status_code: self::API_BAD_REQUEST,
                        reason: "dealer_code is required for friend code flow.",
                        data: []
                    );
                }

                if ($reason = $this->approvalService->friendCodeDealerCodeError($dealer, $data['dealer_code'])) {
                    return $this->apiResponse(
                        in_error: true,
                        message: 'Invalid dealer code',
                        status_code: self::API_BAD_REQUEST,
                        reason: $reason,
                        data: []
                    );
                }

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
                    message: "Car submitted for friend code approval",
                    status_code: self::API_CREATED,
                    data: [
                        'car'         => CarTransformer::summary($car->load('dealer')),
                        'payment'     => $this->paymentPayloadForFrontend($payment),
                    ],
                    reason: "Car submitted for friend code approval"
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
            // Log::channel('paystack')->info('DealerCarController: payment created', ['payment' => $payment]);

            $paymentUrl = null;
            if (config('services.paystack.secret_key')) {
                $result = $this->paystackService->initializeTransaction($payment, $dealer->email);
                if (!empty($result['authorization_url'])) {
                    $paymentUrl = $result['authorization_url'] ?? null;
                    Log::channel('paystack')->info('DealerCarController: payment URL', ['payment_url' => $paymentUrl]);
                }
            }
            // if (! $paymentUrl) {
            //     $paymentUrl = config('app.frontend_url', 'https://dealer.omnicarsgh.com') . '/app/payment/check?reference=' . $payment->reference_id;
            //     // Log::channel('paystack')->info('DealerCarController: payment URL', ['payment_url' => $paymentUrl]);
            // }

            $car->load('dealer');
            return $this->apiResponse(
                in_error: false,
                message: "Car uploaded successfully",
                status_code: self::API_CREATED,
                data: [
                    'car'         => CarTransformer::summary($car->load('dealer')),
                    'payment'     => $this->paymentPayloadForFrontend($payment),
                    'payment_url' => $paymentUrl,
                    'reference'   => $payment->reference_id,
                ],
                reason: "Car created. Complete payment to submit for approval."
            );
        });
    }

    protected function paymentPayloadForFrontend(Payment $payment): array
    {
        return [
            'payment_slug' => $payment->payment_slug,
            'reference_id' => $payment->reference_id,
            'amount'       => (float) $payment->amount,
            'plan_slug'    => $payment->plan_slug,
            'plan_name'    => $payment->plan_name,
            'status'       => $payment->status,
        ];
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
            ->with('dealer')
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

        $car->load('dealer');
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

        abort_if($car->dealer_slug !== $dealer->dealer_slug || $car->status !== 'draft', 403);

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


    public function dashboardStats(Request $request): JsonResponse
    {
        $dealer     = $request->user();
        $dealerSlug = $dealer->dealer_slug;

        $activeCount = Car::where('dealer_slug', $dealerSlug)
            ->where('status', 'published')
            ->count();

        $pendingApprovalCount = Car::where('dealer_slug', $dealerSlug)
            ->where('status', 'pending_approval')
            ->count();

        $pendingPaymentCount = Car::where('dealer_slug', $dealerSlug)
            ->where('status', 'pending_payment')
            ->count();

        $draftsCount = Car::where('dealer_slug', $dealerSlug)
            ->where('status', 'draft')
            ->count();

        return $this->apiResponse(
            in_error: false,
            message: "Dashboard stats retrieved successfully",
            status_code: self::API_SUCCESS,
            data: [
                'active_count'            => $activeCount,
                'pending_approval_count'  => $pendingApprovalCount,
                'pending_payment_count'   => $pendingPaymentCount,
                'drafts_count'            => $draftsCount,
            ]
        );
    }

    /**
     * Dealer recent listings (latest 6, excluding draft).
     */
    public function recentListings(Request $request): JsonResponse
    {
        $dealer = $request->user();

        $cars = Car::where('dealer_slug', $dealer->dealer_slug)
            ->where('status', '!=', 'draft')
            ->with(['dealer', 'paymentItems.payment', 'latestApproval'])
            ->latest()
            ->limit(6)
            ->get();

        $items = $cars->map(fn($car) => CarTransformer::summary($car))->values()->all();

        return $this->apiResponse(
            in_error: false,
            message: "Recent listings retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $items
        );
    }

    /**
     * Dealer recent expiring-soon listings (latest 6 by nearest expiry date).
     */
    public function recentExpiringSoonListings(Request $request): JsonResponse
    {
        $dealer = $request->user();

        $cars = Car::where('dealer_slug', $dealer->dealer_slug)
            ->where('status', 'published')
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays(7)])
            ->with(['dealer', 'paymentItems.payment', 'latestApproval'])
            ->orderBy('expiry_date')
            ->limit(6)
            ->get();

        $items = $cars->map(fn($car) => CarTransformer::summary($car))->values()->all();

        return $this->apiResponse(
            in_error: false,
            message: "Expiring soon listings retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $items
        );
    }

    /**
     * Publish a draft car: update car and create payment + approval (no new car).
     * Body: plan_slug (friend_code|1_month|3_months), phone_number?, network?, dealer_code? (friend_code), callback_url? (frontend success URL for redirect).
     */
    public function publishDraft(Request $request, Car $car): JsonResponse
    {
        $dealer = $request->user();
        abort_if($car->dealer_slug !== $dealer->dealer_slug, 403);
        abort_if($car->status !== 'draft', 422, 'Car must be draft to publish.');

        $data = $request->validate([
            'plan_slug'    => 'required|string|in:friend_code,1_month,3_months',
            'phone_number' => 'nullable|string',
            'network'      => 'nullable|string',
            'dealer_code'  => 'nullable|string|exists:dealers,dealer_code',
            'region'       => 'nullable|string|max:120',
            'location'     => 'nullable|string|max:255',
            'callback_url' => 'nullable|url',
        ]);

        $plan = Plan::where('plan_slug', $data['plan_slug'])->first();
        if (!$plan) {
            return $this->apiResponse(in_error: true, message: "Invalid plan", status_code: self::API_BAD_REQUEST, data: []);
        }

        if ($data['plan_slug'] === 'friend_code') {
            if (empty($data['dealer_code'])) {
                return $this->apiResponse(
                    in_error: true,
                    message: "Dealer code is required",
                    status_code: self::API_BAD_REQUEST,
                    reason: "dealer_code is required for friend code flow.",
                    data: []
                );
            }

            if ($err = $this->approvalService->friendCodeDealerCodeError($dealer, $data['dealer_code'])) {
                return $this->apiResponse(
                    in_error: true,
                    message: 'Invalid dealer code',
                    status_code: self::API_BAD_REQUEST,
                    reason: $err,
                    data: []
                );
            }
        }

        return DB::transaction(function () use ($dealer, $car, $plan, $data, $region, $location) {
            if ($data['plan_slug'] === 'friend_code') {

                $car->update([
                    'status'       => 'pending_approval',
                    'plan_slug'    => 'friend_code',
                    'plan_price'   => 0,
                    'plan_details' => $car->plan_details ?? null,
                    'region'       => $data['region'],
                    'location'     => $data['location'],
                ]);
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
                    status_code: self::API_SUCCESS,
                    data: [
                        'car'         => CarTransformer::summary($car->load('dealer')),
                        'payment'     => $this->paymentPayloadForFrontend($payment),
                    ],
                    reason: "Car submitted for friend code approval"
                );
            }

            $car->update([
                'status'       => 'pending_payment',
                'plan_slug'    => $plan->plan_slug,
                'plan_price'   => $plan->price,
                'plan_details' => $car->plan_details ?? null,
                'region'       => $data['region'],
                'location'     => $data['location'],
            ]);
            $payment = $this->paymentService->createPaymentForCars(
                $dealer,
                [$car],
                $plan,
                $data['phone_number'] ?? null,
                $data['network'] ?? null,
                'momo'
            );

            $paymentUrl = null;
            if (config('services.paystack.secret_key')) {
                $result = $this->paystackService->initializeTransaction($payment, $dealer->email);
                if (!empty($result['authorization_url'])) {
                    $paymentUrl = $result['authorization_url'];
                }
            }

            return $this->apiResponse(
                in_error: false,
                message: "Car ready for payment",
                status_code: self::API_SUCCESS,
                data: [
                    'car'         => CarTransformer::summary($car->load('dealer')),
                    'payment'     => $this->paymentPayloadForFrontend($payment),
                    'payment_url' => $paymentUrl,
                    'reference'   => $payment->reference_id,
                ],
                reason: "Car ready for payment. Complete payment to submit for approval."
            );
        });
    }

    /**
     * Update car. Allowed only when status is draft or pending_approval.
     */
    public function updateCar(CarUploadRequest $request, Car $car): JsonResponse
    {
        $dealer = $request->user();
        abort_if($car->dealer_slug !== $dealer->dealer_slug, 403);
        abort_if(!in_array($car->status, ['draft', 'pending_approval'], true), 403, 'You can only edit draft or pending-approval cars.');

        $data = $request->validated();
        unset($data['status'], $data['plan_slug'], $data['plan_price'], $data['plan_details']);
        $this->carService->updateCar($car, $data);

        return $this->apiResponse(
            in_error: false,
            message: "Car updated successfully",
            status_code: self::API_SUCCESS,
            data: CarTransformer::summary($car->fresh('dealer'))
        );
    }
}
