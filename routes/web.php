<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dealer\PaymentController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('/', function () {
//     $routes = collect(app('router')->getRoutes())
//         ->filter(function ($route) {
//             return str_starts_with($route->uri(), 'api/');
//         })
//         ->map(function ($route) {
//             return [
//                 'method' => implode('|', $route->methods()),
//                 'path'   => '/' . ltrim($route->uri(), '/'),
//                 'name'   => $route->getName(),
//             ];
//         })
//         ->groupBy(function ($route) {
//             if (str_starts_with($route['path'], '/api/admin')) {
//                 return 'Admin';
//             }
//             if (str_starts_with($route['path'], '/api/dealer')) {
//                 return 'Dealer';
//             }
//             return 'Public';
//         })
//         ->toArray();

//     return response()->json([
//         'name'        => config('app.name', 'GhanaCarSales API'),
//         'description' => 'HTTP API endpoints for dealers, buyers, and admins.',
//         'groups'      => $routes,
//     ]);
// });

Route::get("/", function () {
    return response()->json([
        'name'        => config('app.name', 'GhanaCarSales'),
        'description' => 'GhanaCarSales',
        'version'     => '1.0.0',
        'author'      => 'GhanaCarSales',
        'author_url'  => 'https://ghancarsales.com',
        'author_email' => 'info@ghancarsales.com',
        'author_phone' => '+233540000000',
        'author_address' => 'Ghana',
        'author_city' => 'Accra',
        'author_state' => 'Greater Accra',
    ]);
});

// Payment callback (public)
Route::get('/payment/callback', [PaymentController::class, 'callback']);
