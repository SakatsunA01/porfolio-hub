<?php

use App\Http\Controllers\Api\ComboController;
use App\Http\Controllers\Api\BundleController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\CustomerProfileController;
use App\Http\Controllers\Api\ExtraController;
use App\Http\Controllers\Api\IngredientController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserManagementController;
use Illuminate\Http\Request;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('/auth/login', [AuthController::class, 'login']);

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'delivery-app-backend',
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::get('/combos', [ComboController::class, 'index']);
Route::get('/combos/{combo}', [ComboController::class, 'show']);
Route::get('/bundles', [BundleController::class, 'index']);
Route::get('/bundles/{bundle}', [BundleController::class, 'show']);
Route::get('/ingredients', [IngredientController::class, 'index']);
Route::get('/extras', [ExtraController::class, 'index']);
Route::post('/orders', [OrderController::class, 'store']);
Route::get('/roles', [RoleController::class, 'index']);

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/products', [ProductController::class, 'store']);
    Route::post('/products/bulk/price', [ProductController::class, 'bulkUpdatePrices']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);

    Route::post('/combos', [ComboController::class, 'store']);
    Route::put('/combos/{combo}', [ComboController::class, 'update']);
    Route::delete('/combos/{combo}', [ComboController::class, 'destroy']);

    Route::post('/bundles', [BundleController::class, 'store']);
    Route::put('/bundles/{bundle}', [BundleController::class, 'update']);
    Route::delete('/bundles/{bundle}', [BundleController::class, 'destroy']);

    Route::post('/ingredients', [IngredientController::class, 'store']);
    Route::post('/ingredients/{ingredient}/deactivate-global', [IngredientController::class, 'deactivateGlobal']);
    Route::put('/ingredients/{ingredient}', [IngredientController::class, 'update']);
    Route::delete('/ingredients/{ingredient}', [IngredientController::class, 'destroy']);

    Route::post('/extras', [ExtraController::class, 'store']);
    Route::put('/extras/{extra}', [ExtraController::class, 'update']);
    Route::delete('/extras/{extra}', [ExtraController::class, 'destroy']);

    Route::post('/roles', [RoleController::class, 'store']);
    Route::put('/roles/{role}', [RoleController::class, 'update']);
    Route::delete('/roles/{role}', [RoleController::class, 'destroy']);

    Route::get('/users', [UserManagementController::class, 'index']);
    Route::post('/users', [UserManagementController::class, 'store']);
    Route::put('/users/{user}', [UserManagementController::class, 'update']);
    Route::delete('/users/{user}', [UserManagementController::class, 'destroy']);

    Route::put('/orders/{order}/payment', [OrderController::class, 'updatePayment']);
    Route::put('/orders/{order}', [OrderController::class, 'update']);
    Route::get('/customers', [CustomerProfileController::class, 'index']);
    Route::post('/customers', [CustomerProfileController::class, 'upsert']);
    Route::post('/customers/block', [CustomerProfileController::class, 'block']);
    Route::post('/customers/unblock', [CustomerProfileController::class, 'unblock']);
    Route::get('/audit-logs', [AuditLogController::class, 'index']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus']);
});
