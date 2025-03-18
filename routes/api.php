<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Admin\CategoryAdminController;
use App\Http\Controllers\Admin\ProductSectionController;
use App\Http\Controllers\ProductSectionController as PublicProductSectionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/google', [SocialAuthController::class, 'googleAuth']);
Route::post('/auth/apple', [SocialAuthController::class, 'appleAuth']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Test endpoint for social auth
Route::post('/auth/google/test', [SocialAuthController::class, 'testGoogleAuth']);

// Flutterwave webhook (must be public)
Route::post('/webhooks/flutterwave', [PaymentController::class, 'handleWebhook']);

// Payment callback routes (must be public)
Route::get('/payments/callback', [PaymentController::class, 'handleCallback'])->name('payment.callback');
Route::get('/payment/callback', [PaymentController::class, 'handleCallback'])->name('payment.callback.alt');
Route::get('/payments/callback/{status}', [PaymentController::class, 'handleCallback'])->name('payment.callback.status');
Route::get('/payment/callback/{status}', [PaymentController::class, 'handleCallback'])->name('payment.callback.alt.status');

// Products & Categories (Public)
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/tree', [CategoryController::class, 'tree']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);
Route::get('/categories/{category}/products', [CategoryController::class, 'products']);

// Product Sections (Public)
Route::get('/product-sections', [PublicProductSectionController::class, 'index']);
Route::get('/products/by-type', [PublicProductSectionController::class, 'getProductsByType']);
Route::get('/products/by-type/{type}', [PublicProductSectionController::class, 'getProductsByTypeParam']);

// Coupons (Public validation)
Route::post('/coupons/validate', [CouponController::class, 'validateCoupon']);

// Store Locations (Public)
Route::get('/locations', [LocationController::class, 'index']);
Route::get('/locations/nearby', [LocationController::class, 'nearby']);
Route::get('/locations/{location}', [LocationController::class, 'show'])->where('location', '[0-9]+');

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // User profile
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Cart
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/add', [CartController::class, 'addItem']);
    Route::put('/cart/update/{item}', [CartController::class, 'updateItem']);
    Route::delete('/cart/remove/{item}', [CartController::class, 'removeItem']);
    Route::delete('/cart/clear', [CartController::class, 'clearCart']);
    
    // Checkout & Orders
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);
    
    // Payments
    Route::get('/payments/methods', [PaymentController::class, 'getPaymentMethods']);
    Route::post('/orders/{order}/payment', [PaymentController::class, 'processPayment']);
    Route::get('/payments/{payment}/verify', [PaymentController::class, 'verifyPayment']);
    
    // Store pickup details (only after payment)
    Route::get('/orders/{order}/pickup-details', [OrderController::class, 'pickupDetails']);
});

// Admin routes
Route::middleware(['auth:sanctum', \App\Http\Middleware\CheckRole::class . ':admin'])->prefix('admin')->group(function () {
    // Debug route to verify admin access
    Route::get('/check-auth', function (Request $request) {
        return response()->json([
            'message' => 'Admin authentication successful',
            'user' => $request->user(),
            'is_admin' => $request->user()->role === 'admin',
        ]);
    });
    
    // Product Management
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    
    // Category Management
    Route::get('/categories/stock-data', [\App\Http\Controllers\Admin\CategoryAdminController::class, 'getCategoryStockData']);
    Route::apiResource('/categories', \App\Http\Controllers\Admin\CategoryAdminController::class);
    Route::get('/categories-tree', [\App\Http\Controllers\Admin\CategoryAdminController::class, 'tree']);
    Route::post('/categories-reorder', [\App\Http\Controllers\Admin\CategoryAdminController::class, 'reorder']);
    
    // Order Management
    Route::get('/orders', [OrderController::class, 'adminIndex']);
    Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus']);
    
    // Coupon Management
    Route::get('/coupons', [CouponController::class, 'index']);
    Route::post('/coupons', [CouponController::class, 'store']);
    Route::put('/coupons/{coupon}', [CouponController::class, 'update']);
    Route::delete('/coupons/{coupon}', [CouponController::class, 'destroy']);
    
    // Product Section Management
    Route::get('/product-sections', [\App\Http\Controllers\Admin\ProductSectionController::class, 'index']);
    Route::post('/product-sections', [\App\Http\Controllers\Admin\ProductSectionController::class, 'store']);
    Route::get('/product-sections/{id}', [\App\Http\Controllers\Admin\ProductSectionController::class, 'show']);
    Route::put('/product-sections/{id}', [\App\Http\Controllers\Admin\ProductSectionController::class, 'update']);
    Route::delete('/product-sections/{id}', [\App\Http\Controllers\Admin\ProductSectionController::class, 'destroy']);
    Route::patch('/product-sections/{id}/toggle', [\App\Http\Controllers\Admin\ProductSectionController::class, 'toggle']);
    Route::post('/product-sections/reorder', [\App\Http\Controllers\Admin\ProductSectionController::class, 'reorder']);
    
    // Location Management
    Route::post('/locations', [LocationController::class, 'store']);
    Route::put('/locations/{location}', [LocationController::class, 'update']);
    Route::delete('/locations/{location}', [LocationController::class, 'destroy']);
    Route::put('/locations/radius', [LocationController::class, 'updateRadius']);
    
    // Payment Management
    Route::get('/orders/{order}/payments', [PaymentController::class, 'adminViewPayment']);
    Route::put('/orders/{order}/payments/status', [PaymentController::class, 'updatePaymentStatus']);
});
