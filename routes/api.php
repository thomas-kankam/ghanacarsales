<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\BillingController as AdminBillingController;
use App\Http\Controllers\Admin\CarController as AdminCarController;
use App\Http\Controllers\Admin\DealerController as AdminDealerController;
use App\Http\Controllers\Admin\MetricsController as AdminMetricsController;
use App\Http\Controllers\Admin\PlanController as AdminPlanController;
use App\Http\Controllers\Buyer\CarController as BuyerCarController;
use App\Http\Controllers\Dealer\DealerAuthController;
use App\Http\Controllers\Dealer\DealerCarController;
use App\Http\Controllers\Dealer\PaymentController;
use App\Http\Controllers\Dealer\SubscriptionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Common routes (no authentication required)
// Route::prefix('v1')->group(function () {
//     Route::get('/brands', [\App\Http\Controllers\Api\V1\Common\BrandController::class, 'index']);
// });

// Admin routes (admin.car.com)
Route::prefix('admin')->group(function () {
    Route::post('/register', [AdminAuthController::class, 'register']);
    Route::post('/login', [AdminAuthController::class, 'login']);

    Route::middleware(['auth:admin'])->group(function () {
        // Dealers
        Route::get('/dealers', [AdminDealerController::class, 'index']);
        Route::get('/dealers/{id}', [AdminDealerController::class, 'show']);
        Route::put('/dealers/{id}', [AdminDealerController::class, 'update']);
        Route::delete('/dealers/{id}', [AdminDealerController::class, 'destroy']);
        Route::get('/dealers/trashed/list', [AdminDealerController::class, 'trashed']);
        Route::post('/dealers/{id}/restore', [AdminDealerController::class, 'restore']);
        Route::delete('/dealers/{id}/force', [AdminDealerController::class, 'forceDelete']);

        // Cars
        Route::get('/cars', [AdminCarController::class, 'index']);
        Route::get('/cars/{id}', [AdminCarController::class, 'show']);
        Route::post('/cars/{id}/approve', [AdminCarController::class, 'approve']);
        Route::post('/cars/{id}/reject', [AdminCarController::class, 'reject']);
        Route::post('/cars/{id}/force-expire', [AdminCarController::class, 'forceExpire']);
        Route::delete('/cars/{id}', [AdminCarController::class, 'destroy']);
        Route::get('/cars/trashed/list', [AdminCarController::class, 'trashed']);
        Route::post('/cars/{id}/restore', [AdminCarController::class, 'restore']);
        Route::delete('/cars/{id}/force', [AdminCarController::class, 'forceDelete']);

        // Plans
        Route::get('/plans', [AdminPlanController::class, 'index']);
        Route::post('/plans', [AdminPlanController::class, 'store']);
        Route::put('/plans/{id}', [AdminPlanController::class, 'update']);
        Route::delete('/plans/{id}', [AdminPlanController::class, 'destroy']);

        // Billing
        Route::get('/payments', [AdminBillingController::class, 'payments']);
        Route::get('/subscriptions', [AdminBillingController::class, 'subscriptions']);

        // Metrics & health
        Route::get('/metrics', [AdminMetricsController::class, 'metrics']);
        Route::get('/health', [AdminMetricsController::class, 'health']);
    });
});

// Dealer routes (dealer.car.com)
Route::prefix('dealer')->group(function () {
    // Public routes
    // Route::post('/send_otp', [DealerAuthController::class, 'sendingOtp'])->middleware('throttle:otp');
    Route::post('/testSms', [DealerAuthController::class, 'testSms']);
    Route::post('/send_otp', [DealerAuthController::class, 'sendingOtp']);
    Route::post('/resend_otp', [DealerAuthController::class, 'reSendOtp']);
    Route::post('/verify_token', [DealerAuthController::class, 'verifyToken']);
    Route::post('/login_otp', [DealerAuthController::class, 'OtpLogin']);
    Route::post('/login', [DealerAuthController::class, 'verifyLoginOtp']);

    // Protected routes
    Route::middleware(['auth:dealer'])->group(function () {
        // bio data
        Route::post('/profile_update', [DealerAuthController::class, 'updateProfile']);
        Route::post('/logout', [DealerAuthController::class, 'logout']);

        Route::post('/register_dealer', [DealerAuthController::class, 'registerDealer']);

        // Legacy car routes
        Route::post('/upload_car', [DealerCarController::class, 'uploadCar']);
        Route::put('/update_car/{car}', [DealerCarController::class, 'updateCar']);
        Route::get('/get_cars', [DealerCarController::class, 'listCars']);
        Route::get('/single_car/{car}', [DealerCarController::class, 'singleCar']);
        Route::delete('/delete_car/{car}', [DealerCarController::class, 'deleteCar']);

        // New listings CRUD
        Route::get('/listings', [DealerCarController::class, 'listCars']);
        Route::post('/listings', [DealerCarController::class, 'uploadCar']);
        Route::put('/listings/{car}', [DealerCarController::class, 'updateCar']);

        // Draft workflow
        Route::get('/drafts', [DealerCarController::class, 'listDrafts']);
        Route::get('/drafts/{car}', [DealerCarController::class, 'getDraft']);
        Route::post('/drafts', [DealerCarController::class, 'saveDraft']);
        Route::post('/drafts/{id}/publish', [DealerCarController::class, 'publishDraft']);

        // Sponsor approvals
        Route::get('/approvals', [DealerCarController::class, 'approvals']);
        Route::post('/approvals/{id}/approve', [DealerCarController::class, 'approveCar']);
        Route::post('/approvals/{id}/reject', [DealerCarController::class, 'rejectCar']);

        Route::get('/payment/summary', [PaymentController::class, 'getSummary']);
        Route::post('/payment/create', [PaymentController::class, 'createPayment']);

        // Subscription & plans
        Route::get('/plans', [SubscriptionController::class, 'plans']);
        Route::get('/subscription', [SubscriptionController::class, 'current']);
        Route::get('/payments', [SubscriptionController::class, 'payments']);
        Route::post('/subscribe', [SubscriptionController::class, 'subscribe']);
    });

    // Payment callback (public)
    Route::post('/payment/callback', [PaymentController::class, 'callback']);
});

// Buyer routes (car.com)
// Route::prefix('v1/buyer')->group(function () {
//     // Public routes
//     Route::get('/cars/search', [\App\Http\Controllers\Api\V1\Buyer\CarController::class, 'search'])
//         ->middleware('throttle:search');
//     Route::get('/cars/{id}', [\App\Http\Controllers\Api\V1\Buyer\CarController::class, 'show']);
//     Route::get('/sellers/{sellerId}/cars', [\App\Http\Controllers\Api\V1\Buyer\CarController::class, 'getDealerCars']);

//     // Alert routes
//     Route::post('/alerts', [\App\Http\Controllers\Api\V1\Buyer\AlertController::class, 'create']);
//     Route::post('/alerts/deactivate', [\App\Http\Controllers\Api\V1\Buyer\AlertController::class, 'deactivate']);
// });

// Public buyer catalog
Route::get('/cars', [BuyerCarController::class, 'search'])->middleware('throttle:search');
Route::get('/cars/{id}', [BuyerCarController::class, 'show']);
Route::get('/dealers/{dealerId}/cars', [BuyerCarController::class, 'getDealerCars']);
