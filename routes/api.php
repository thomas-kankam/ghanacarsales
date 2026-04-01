<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminBillingController;
use App\Http\Controllers\Admin\AdminCarController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminDealerController;
use App\Http\Controllers\Admin\AdminMetricsController;
use App\Http\Controllers\Admin\AdminPlanController;
use App\Http\Controllers\Buyer\BuyerCarController;
use App\Http\Controllers\Dealer\DealerAuthController;
use App\Http\Controllers\Dealer\DealerCarController;
use App\Http\Controllers\Dealer\PaymentController;
use App\Http\Controllers\Dealer\SubscriptionController;
use App\Http\Controllers\General\PlanController;
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

// Route::post('/cars/{id}/approve', [AdminCarController::class, 'approve']);
// Admin routes (admin.car.com)
Route::prefix('admin')->group(function () {
    Route::post('/register', [AdminAuthController::class, 'register']);
    Route::post('/login', [AdminAuthController::class, 'login']);
    Route::post('/forgot-password', [AdminAuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AdminAuthController::class, 'resetPassword']);

    Route::middleware(['auth:admin'])->group(function () {
        // Admin account
        Route::put('/profile', [AdminAuthController::class, 'profileUpdate']);
        Route::post('/change-password', [AdminAuthController::class, 'changePassword']);

        // Dealers
        Route::get('/dealers', [AdminDealerController::class, 'index']);
        Route::get('/dealers/{id}', [AdminDealerController::class, 'show']);
        Route::post('/dealers/{dealer}/assign-code', [AdminDealerController::class, 'assignCode']);
        Route::post('/dealers/{dealer}/revoke-code', [AdminDealerController::class, 'revokeCode']);
        Route::put('/dealers/{id}', [AdminDealerController::class, 'update']);
        Route::post('/dealers/{id}/activate', [AdminDealerController::class, 'activate']);
        Route::post('/dealers/{id}/deactivate', [AdminDealerController::class, 'deactivate']);
        Route::delete('/dealers/{id}', [AdminDealerController::class, 'destroy']);
        Route::get('/dealers/trashed/list', [AdminDealerController::class, 'trashed']);
        Route::post('/dealers/{id}/restore', [AdminDealerController::class, 'restore']);
        Route::delete('/dealers/{id}/force', [AdminDealerController::class, 'forceDelete']);
        Route::get('/dealer-codes', [AdminDealerController::class, 'dealerCodes']);

        // Cars
        Route::get('/cars', [AdminCarController::class, 'index']);
        Route::get('/cars/{id}', [AdminCarController::class, 'show']);
        Route::post('/cars/{id}/approve', [AdminCarController::class, 'approve']);
        Route::post('/cars/{id}/reject', [AdminCarController::class, 'reject']);
        Route::post('/cars/{id}/revert-approval', [AdminCarController::class, 'revertApproval']);
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
        // Route::get('/subscriptions', [AdminBillingController::class, 'subscriptions']);

        // Metrics & health
        Route::get('/metrics', [AdminMetricsController::class, 'metrics']);
        Route::get('/health', [AdminMetricsController::class, 'health']);

        // Dashboard
        Route::get('/dashboard/top-dealers', [AdminDashboardController::class, 'topDealers']);
        Route::get('/dashboard/pending-approvals', [AdminDashboardController::class, 'pendingApprovals']);
        Route::get('/dashboard/latest-registrations', [AdminDashboardController::class, 'latestRegistrations']);
    });
});

// Dealer routes (dealer.car.com)
Route::prefix('dealer')->group(function () {
    // Public routes
    // Route::post('/send_otp', [DealerAuthController::class, 'sendingOtp'])->middleware('throttle:otp');
    Route::get('/test_sms/{msisdn}', [DealerAuthController::class, 'testSms']);
    Route::get('/test_email/{email}', [DealerAuthController::class, 'testEmail']);
    Route::post('/send_otp', [DealerAuthController::class, 'sendingOtp'])->middleware('throttle:otp');
    Route::post('/resend_otp', [DealerAuthController::class, 'reSendOtp'])->middleware('throttle:otp');
    Route::post('/verify_token', [DealerAuthController::class, 'verifyToken']);
    Route::post('/login_otp', [DealerAuthController::class, 'OtpLogin'])->middleware('throttle:otp');
    Route::post('/login', [DealerAuthController::class, 'verifyLoginOtp']);
    Route::post('/forgot_password', [DealerAuthController::class, 'forgotPassword']);
    Route::post('/verify_reset_password_otp', [DealerAuthController::class, 'verifyResetPasswordOtp']);

    // Protected routes
    Route::middleware(['auth:dealer'])->group(function () {
        // bio data
        Route::post('/profile_update', [DealerAuthController::class, 'updateProfile']);
        Route::post('/logout', [DealerAuthController::class, 'logout']);

        Route::post('/register_dealer', [DealerAuthController::class, 'registerDealer']);

        Route::post('/upload_car', [DealerCarController::class, 'uploadCar']);
        Route::put('/cars/{car}', [DealerCarController::class, 'updateCar']);
        Route::post('/drafts/{car}/publish', [DealerCarController::class, 'publishDraft']);
        Route::get('/get_cars', [DealerCarController::class, 'listCars']);
        Route::get('/single_car/{car}', [DealerCarController::class, 'singleCar']);
        Route::delete('/delete_car/{car}', [DealerCarController::class, 'deleteCar']);

        Route::get('/dashboard_stats', [DealerCarController::class, 'dashboardStats']);
        Route::get('/recent_listings', [DealerCarController::class, 'recentListings']);
        Route::get('/expiring_soon_listings', [DealerCarController::class, 'recentExpiringSoonListings']);

        // Draft workflow
        Route::get('/drafts', [DealerCarController::class, 'listDrafts']);
        Route::get('/drafts/{car}', [DealerCarController::class, 'getDraft']);
        Route::post('/drafts', [DealerCarController::class, 'saveDraft']);

        // publish all drafts
        Route::get('/publish_all_drafts', [DealerCarController::class, 'publishAllDrafts']);

        // Sponsor approvals
        // Route::get('/approvals', [DealerCarController::class, 'approvals']);
        // Route::post('/approvals/{id}/approve', [DealerCarController::class, 'approveCar']);
        // Route::post('/approvals/{id}/reject', [DealerCarController::class, 'rejectCar']);

        Route::get('/payment/summary', [PaymentController::class, 'getSummary']);
        Route::post('/create_payment/{car}', [PaymentController::class, 'createPayment']);
        Route::post('/check_payment', [PaymentController::class, 'checkPayment']);

        // Subscription & plans
        Route::get('/current_subscription', [SubscriptionController::class, 'current']);
        Route::get('/payment_history', [SubscriptionController::class, 'payments']);
    });
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
Route::get('/all_cars', [BuyerCarController::class, 'search']);
Route::get('/cars/{car}', [BuyerCarController::class, 'show']);
Route::get('/dealers/{dealer_slug}/cars', [BuyerCarController::class, 'getDealerCars']);

Route::get('/all_plans', [PlanController::class, 'getPlans']);

// Payment: webhook (server-to-server) and callback (browser redirect → forwards to frontend)
Route::post('/payment/webhook', [PaymentController::class, 'webhook']);
Route::get('/payment/callback', [PaymentController::class, 'callback']);
