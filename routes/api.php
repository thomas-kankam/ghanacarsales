<?php

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
Route::prefix('v1')->group(function () {
    Route::get('/brands', [\App\Http\Controllers\Api\V1\Common\BrandController::class, 'index']);
});

// Admin routes (admin.car.com)
Route::prefix('v1/admin')->group(function () {
    Route::post('/register', [\App\Http\Controllers\Api\V1\Admin\AuthController::class, 'register']);
    Route::post('/login', [\App\Http\Controllers\Api\V1\Admin\AuthController::class, 'login']);
});

// Seller routes (seller.car.com)
Route::prefix('v1/seller')->group(function () {
    // Public routes
    Route::post('/send-otp', [\App\Http\Controllers\Api\V1\Seller\AuthController::class, 'sendOtp'])
        ->middleware('throttle:otp');
    Route::post('/verify-otp', [\App\Http\Controllers\Api\V1\Seller\AuthController::class, 'verifyOtpAndRegister']);

    // Protected routes
    Route::middleware(['auth:seller'])->group(function () {
        Route::post('/cars', [\App\Http\Controllers\Api\V1\Seller\CarController::class, 'upload']);
        Route::get('/cars', [\App\Http\Controllers\Api\V1\Seller\CarController::class, 'index']);
        Route::get('/cars/{id}', [\App\Http\Controllers\Api\V1\Seller\CarController::class, 'show']);
        Route::delete('/cars/{id}', [\App\Http\Controllers\Api\V1\Seller\CarController::class, 'destroy']);

        Route::get('/payment/summary', [\App\Http\Controllers\Api\V1\Seller\PaymentController::class, 'getSummary']);
        Route::post('/payment/create', [\App\Http\Controllers\Api\V1\Seller\PaymentController::class, 'createPayment']);
    });

    // Payment callback (public)
    Route::post('/payment/callback', [\App\Http\Controllers\Api\V1\Seller\PaymentController::class, 'callback']);
});

// Buyer routes (car.com)
Route::prefix('v1/buyer')->group(function () {
    // Public routes
    Route::get('/cars/search', [\App\Http\Controllers\Api\V1\Buyer\CarController::class, 'search'])
        ->middleware('throttle:search');
    Route::get('/cars/{id}', [\App\Http\Controllers\Api\V1\Buyer\CarController::class, 'show']);
    Route::get('/sellers/{sellerId}/cars', [\App\Http\Controllers\Api\V1\Buyer\CarController::class, 'getDealerCars']);

    // Alert routes
    Route::post('/alerts', [\App\Http\Controllers\Api\V1\Buyer\AlertController::class, 'create']);
    Route::post('/alerts/deactivate', [\App\Http\Controllers\Api\V1\Buyer\AlertController::class, 'deactivate']);
});
