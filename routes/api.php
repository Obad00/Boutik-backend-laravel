<?php

use App\Http\Controllers\Auth\ShopAuthController;
use App\Http\Controllers\Auth\SuperadminAuthController;
use App\Http\Controllers\CashMovementController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CreditPaymentController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\Superadmin\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['ok' => true]));

Route::post('/auth/shop-login', [ShopAuthController::class, 'login']);
Route::post('/superadmin/login', [SuperadminAuthController::class, 'login']);

Route::middleware('shop.auth')->group(function () {
    Route::post('/auth/verify-admin-pin', [ShopAuthController::class, 'verifyAdminPin']);

    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    Route::post('/products/{product}/adjust-stock', [ProductController::class, 'adjustStock']);

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

    Route::get('/customers', [CustomerController::class, 'index']);
    Route::post('/customers', [CustomerController::class, 'store']);
    Route::put('/customers/{customer}', [CustomerController::class, 'update']);
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy']);

    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);

    Route::get('/stock-movements', [StockMovementController::class, 'index']);
    Route::post('/stock-movements/restock', [StockMovementController::class, 'restock']);

    Route::get('/cash-movements', [CashMovementController::class, 'index']);
    Route::post('/cash-movements', [CashMovementController::class, 'store']);

    Route::get('/credit-payments', [CreditPaymentController::class, 'index']);
    Route::post('/credit-payments', [CreditPaymentController::class, 'store']);

    Route::get('/settings', [SettingController::class, 'show']);
    Route::put('/settings', [SettingController::class, 'update']);
});

Route::middleware('superadmin.auth')->prefix('superadmin')->group(function () {
    Route::get('/shops', [ShopController::class, 'index']);
    Route::post('/shops', [ShopController::class, 'store']);
    Route::put('/shops/{shop}', [ShopController::class, 'update']);
    Route::delete('/shops/{shop}', [ShopController::class, 'destroy']);
});
