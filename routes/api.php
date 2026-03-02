<?php

use App\Http\Controllers\Dealer\DealerAuthController;
use App\Http\Controllers\Dealer\DealerCarController;
use App\Http\Controllers\Dealer\PaymentController;
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
// Route::prefix('v1/admin')->group(function () {
//     Route::post('/register', [\App\Http\Controllers\Api\V1\Admin\AuthController::class, 'register']);
//     Route::post('/login', [\App\Http\Controllers\Api\V1\Admin\AuthController::class, 'login']);
// });

// Dealer routes (dealer.car.com)
Route::prefix('dealer')->group(function () {
    // Public routes
    // Route::post('/send_otp', [DealerAuthController::class, 'sendingOtp'])->middleware('throttle:otp');
    Route::post('/send_otp', [DealerAuthController::class, 'sendingOtp']);
    Route::post('/resend_otp', [DealerAuthController::class, 'reSendOtp']);
    Route::post('/verify_token', [DealerAuthController::class, 'verifyToken']);

    // Protected routes
    Route::middleware(['auth:dealer'])->group(function () {
        Route::post('/register_dealer', [DealerAuthController::class, 'registerDealer']);
        Route::post('/upload_car', [DealerCarController::class, 'uploadCar']);
        Route::get('/get_cars', [DealerCarController::class, 'listCars']);
        Route::get('/single_car/{id}', [DealerCarController::class, 'singleCar']);
        Route::delete('/delete_car/{id}', [DealerCarController::class, 'deleteCar']);

        Route::get('/payment/summary', [PaymentController::class, 'getSummary']);
        Route::post('/payment/create', [PaymentController::class, 'createPayment']);
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
