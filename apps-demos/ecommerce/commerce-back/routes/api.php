<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\AdminOrderController;
use App\Http\Controllers\Api\AdminStoreSettingsController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\MercadoPagoWebhookController;
use App\Http\Controllers\Api\StorefrontOrderController;
use App\Http\Controllers\Api\StorefrontCatalogController;
use App\Http\Controllers\ProductController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

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

Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:auth-login');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:auth-login');
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth:sanctum');
Route::post('/webhooks/mercado-pago', MercadoPagoWebhookController::class)->middleware('throttle:mp-webhook');
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'commerce-backend',
        'timestamp' => now()->toIso8601String(),
    ]);
});
Route::get('/store/products', [StorefrontCatalogController::class, 'index']);
Route::get('/store/products/{product}', [StorefrontCatalogController::class, 'show']);
Route::get('/store/categories', [StorefrontCatalogController::class, 'categories']);
Route::get('/store/settings', [StorefrontCatalogController::class, 'settings']);
Route::get('/store/search', [StorefrontCatalogController::class, 'search']);
Route::get('/store/social-proof', [StorefrontCatalogController::class, 'socialProof']);
Route::post('/orders/preview', [StorefrontOrderController::class, 'preview'])->middleware('throttle:checkout');
Route::post('/orders/address-validate', [StorefrontOrderController::class, 'validateAddress'])->middleware('throttle:checkout');
Route::post('/orders', [StorefrontOrderController::class, 'store'])->middleware('throttle:checkout');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->prefix('account')->group(function () {
    Route::get('/orders', [AccountController::class, 'orders']);
    Route::get('/orders/{order}', [AccountController::class, 'showOrder']);
    Route::put('/profile', [AccountController::class, 'updateProfile']);
});

Route::middleware(['auth:sanctum', 'admin', 'throttle:admin-write'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/dashboard', AdminDashboardController::class);

        Route::apiResource('products', ProductController::class);

        Route::get('/orders', [AdminOrderController::class, 'index']);
        Route::get('/store-settings', [AdminStoreSettingsController::class, 'show']);
        Route::put('/store-settings', [AdminStoreSettingsController::class, 'update']);
        Route::post('/store-settings', [AdminStoreSettingsController::class, 'update']);
});
