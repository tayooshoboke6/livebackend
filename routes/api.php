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
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\DeliveryFeeController;
use App\Http\Controllers\ProductSectionController as PublicProductSectionController;
use App\Http\Controllers\NotificationBarController;
use App\Http\Controllers\Admin\NotificationBarController as AdminNotificationBarController;
use App\Http\Controllers\Admin\MessageCampaignController;
use App\Http\Controllers\UserNotificationController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\UserController;

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

// Delivery Fee Calculation (Public)
Route::post('/delivery-fee/calculate', [DeliveryFeeController::class, 'calculate']);

// Store Locations (Public)
Route::get('/locations', [LocationController::class, 'index']);
Route::get('/locations/nearby', [LocationController::class, 'nearby']);
Route::get('/locations/{location}', [LocationController::class, 'show'])->where('location', '[0-9]+');

// Public notification bar
Route::get('/notification-bar', [NotificationBarController::class, 'getActive']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // User profile
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
    
    // User notifications
    Route::get('/notifications', [UserNotificationController::class, 'index']);
    Route::get('/notifications/unread/count', [UserNotificationController::class, 'getUnreadCount']);
    Route::get('/notifications/{id}', [UserNotificationController::class, 'show']);
    Route::post('/notifications/{id}/read', [UserNotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [UserNotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [UserNotificationController::class, 'destroy']);

    // User Addresses
    Route::get('/users/{userId}/addresses', [AddressController::class, 'index']);
    Route::post('/users/{userId}/addresses', [AddressController::class, 'store']);
    Route::get('/users/{userId}/addresses/{addressId}', [AddressController::class, 'show']);
    Route::put('/users/{userId}/addresses/{addressId}', [AddressController::class, 'update']);
    Route::delete('/users/{userId}/addresses/{addressId}', [AddressController::class, 'destroy']);
    Route::patch('/users/{userId}/addresses/{addressId}/default', [AddressController::class, 'setDefault']);
    
    // Cart
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/add', [CartController::class, 'addItem']);
    Route::put('/cart/update/{item}', [CartController::class, 'updateItem']);
    Route::delete('/cart/remove/{item}', [CartController::class, 'removeItem']);
    Route::delete('/cart/clear', [CartController::class, 'clearCart']);
    
    // User Cart (for frontend persistence) - disabled as we now use localStorage
    // Route::get('/user/cart', [CartController::class, 'getUserCart']);
    // Route::post('/user/cart', [CartController::class, 'saveUserCart']);
    
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
Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    // Debug route to verify admin access
    Route::get('/check-auth', function (Request $request) {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'message' => 'Not authenticated',
                    'token_exists' => !empty($request->bearerToken())
                ], 401);
            }

            if ($user->role !== 'admin') {
                return response()->json([
                    'message' => 'Not an admin',
                    'user_role' => $user->role
                ], 403);
            }

            return response()->json([
                'message' => 'Admin authentication successful',
                'user' => $user,
                'is_admin' => true
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error checking authentication',
                'error' => $e->getMessage()
            ], 500);
        }
    });
    
    // Dashboard Statistics
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);
    Route::get('/dashboard/recent-orders', [DashboardController::class, 'getRecentOrders']);
    
    // Settings Management
    Route::get('/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index']);
    Route::put('/settings', [\App\Http\Controllers\Admin\SettingsController::class, 'update']);
    Route::get('/settings/{key}', [\App\Http\Controllers\Admin\SettingsController::class, 'show']);
    
    // Delivery Settings
    Route::get('/delivery-settings/global', [App\Http\Controllers\Admin\DeliverySettingsController::class, 'getGlobalSettings']);
    Route::put('/delivery-settings/global', [App\Http\Controllers\Admin\DeliverySettingsController::class, 'updateGlobalSettings']);
    Route::get('/delivery-settings/store/{storeId}', [App\Http\Controllers\Admin\DeliverySettingsController::class, 'getStoreSettings']);
    Route::put('/delivery-settings/store/{storeId}', [App\Http\Controllers\Admin\DeliverySettingsController::class, 'updateStoreSettings']);
    
    // Banner Management
    Route::get('/banners', [BannerController::class, 'index']);
    Route::post('/banners', [BannerController::class, 'store']);
    Route::get('/banners/{id}', [BannerController::class, 'show']);
    Route::put('/banners/{id}', [BannerController::class, 'update']);
    Route::delete('/banners/{id}', [BannerController::class, 'destroy']);
    Route::post('/banners/reorder', [BannerController::class, 'reorder']);
    Route::put('/banners/{id}/toggle-status', [BannerController::class, 'toggleStatus']);
    
    // Notification Bar Management
    Route::get('/notification-bar', [AdminNotificationBarController::class, 'index']);
    Route::put('/notification-bar', [AdminNotificationBarController::class, 'update']);
    Route::put('/notification-bar/toggle-status', [AdminNotificationBarController::class, 'toggleStatus']);
    
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
    Route::get('/orders', [AdminOrderController::class, 'index']);
    Route::get('/orders/{id}', [AdminOrderController::class, 'show']);
    Route::patch('/orders/{id}/status', [AdminOrderController::class, 'updateStatus']);
    Route::get('/dashboard/order-stats', [AdminOrderController::class, 'getStats']);
    
    // Coupon Management
    Route::get('/coupons', [CouponController::class, 'index']);
    Route::post('/coupons', [CouponController::class, 'store']);
    Route::put('/coupons/{coupon}', [CouponController::class, 'update']);
    Route::delete('/coupons/{coupon}', [CouponController::class, 'destroy']);
    Route::patch('/coupons/{coupon}/toggle-status', [CouponController::class, 'toggleStatus']);
    
    // Product Section Management
    Route::get('/product-sections', [\App\Http\Controllers\Admin\ProductSectionController::class, 'index']);
    Route::post('/product-sections', [\App\Http\Controllers\Admin\ProductSectionController::class, 'store']);
    Route::get('/product-sections/{id}', [\App\Http\Controllers\Admin\ProductSectionController::class, 'show']);
    Route::put('/product-sections/{id}', [\App\Http\Controllers\Admin\ProductSectionController::class, 'update']);
    Route::delete('/product-sections/{id}', [\App\Http\Controllers\Admin\ProductSectionController::class, 'destroy']);
    Route::patch('/product-sections/{id}/toggle', [\App\Http\Controllers\Admin\ProductSectionController::class, 'toggle']);
    Route::post('/product-sections/reorder', [\App\Http\Controllers\Admin\ProductSectionController::class, 'reorder']);
    
    // Location Management
    Route::get('/locations', [LocationController::class, 'index']);
    Route::get('/locations/{location}', [LocationController::class, 'show']);
    Route::post('/locations', [LocationController::class, 'store']);
    Route::put('/locations/{location}', [LocationController::class, 'update']);
    Route::delete('/locations/{location}', [LocationController::class, 'destroy']);
    Route::put('/locations/radius', [LocationController::class, 'updateRadius']);
    Route::put('/locations/{location}/toggle-status', [LocationController::class, 'toggleStatus']);
    Route::put('/locations/{location}/toggle-pickup', [LocationController::class, 'togglePickup']);
    Route::put('/locations/{location}/toggle-delivery', [LocationController::class, 'toggleDelivery']);
    
    // Payment Management
    Route::get('/orders/{order}/payments', [PaymentController::class, 'adminViewPayment']);
    Route::put('/orders/{order}/payments/status', [PaymentController::class, 'updatePaymentStatus']);
    
    // Message Campaigns
    Route::get('/messages/campaigns', [MessageCampaignController::class, 'index']);
    Route::post('/messages/campaigns', [MessageCampaignController::class, 'store']);
    Route::get('/messages/campaigns/segments', [MessageCampaignController::class, 'getSegments']);
    Route::get('/messages/campaigns/{id}', [MessageCampaignController::class, 'show']);
    Route::put('/messages/campaigns/{id}', [MessageCampaignController::class, 'update']);
    Route::delete('/messages/campaigns/{id}', [MessageCampaignController::class, 'destroy']);
    Route::post('/messages/campaigns/{id}/send', [MessageCampaignController::class, 'send']);
    Route::get('/users/segments', [MessageCampaignController::class, 'getUserSegments']);
    
    // User Management
    Route::get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index']);
    Route::post('/users', [\App\Http\Controllers\Admin\UserController::class, 'store']);
    Route::get('/users/{id}', [\App\Http\Controllers\Admin\UserController::class, 'show']);
    Route::put('/users/{id}', [\App\Http\Controllers\Admin\UserController::class, 'update']);
    Route::delete('/users/{id}', [\App\Http\Controllers\Admin\UserController::class, 'destroy']);
    Route::patch('/users/{id}/status', [\App\Http\Controllers\Admin\UserController::class, 'updateStatus']);
});

// Debug route (temporary)
Route::get('/debug/user', function (Request $request) {
    return response()->json([
        'user' => $request->user(),
        'is_authenticated' => auth()->check(),
        'token' => $request->bearerToken(),
        'headers' => $request->headers->all()
    ]);
})->middleware('auth:sanctum');
