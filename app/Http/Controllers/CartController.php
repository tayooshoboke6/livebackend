<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductMeasurement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CartController extends Controller
{
    /**
     * Display a listing of the user's cart items.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $cartItems = $user->cartItems()->with(['product', 'measurement'])->get();
        
        return response()->json([
            'cart_items' => $cartItems,
        ]);
    }
    
    /**
     * Get the count of items in the user's cart.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function count(Request $request)
    {
        $user = $request->user();
        $count = $user->cartItems()->sum('quantity');
        
        return response()->json([
            'count' => $count,
        ]);
    }
    
    /**
     * Add an item to the cart.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addItem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'product_measurement_id' => 'nullable|exists:product_measurements,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $user = $request->user();
        $product = Product::findOrFail($request->product_id);
        
        // Check if product is active
        if (!$product->is_active) {
            return response()->json([
                'message' => 'Product is not available',
            ], 422);
        }
        
        // Check if there's enough stock
        if (!$product->hasEnoughStock($request->quantity, $request->product_measurement_id)) {
            return response()->json([
                'message' => 'Not enough stock available',
            ], 422);
        }
        
        // Check if item already exists in cart
        $cartItem = $user->cartItems()
            ->where('product_id', $product->id)
            ->where('product_measurement_id', $request->product_measurement_id)
            ->first();
        
        if ($cartItem) {
            // Update quantity
            $newQuantity = $cartItem->quantity + $request->quantity;
            
            // Check stock for new quantity
            if (!$product->hasEnoughStock($newQuantity, $request->product_measurement_id)) {
                return response()->json([
                    'message' => 'Not enough stock available',
                ], 422);
            }
            
            $cartItem->update([
                'quantity' => $newQuantity,
            ]);
            
            return response()->json([
                'message' => 'Cart item quantity updated',
                'cart_item' => $cartItem->fresh()->load('product'),
            ]);
        }
        
        // Create new cart item
        $cartItem = CartItem::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => $request->quantity,
            'product_measurement_id' => $request->product_measurement_id,
        ]);
        
        return response()->json([
            'message' => 'Item added to cart',
            'cart_item' => $cartItem->load('product'),
        ]);
    }
    
    /**
     * Update the quantity of a cart item.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateItem(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $user = $request->user();
        $cartItem = $user->cartItems()->findOrFail($id);
        $product = $cartItem->product;
        
        // Check if there's enough stock
        if (!$product->hasEnoughStock($request->quantity, $cartItem->product_measurement_id)) {
            return response()->json([
                'message' => 'Not enough stock available',
            ], 422);
        }
        
        $cartItem->update([
            'quantity' => $request->quantity,
        ]);
        
        return response()->json([
            'message' => 'Cart item updated',
            'cart_item' => $cartItem->fresh()->load('product'),
        ]);
    }
    
    /**
     * Remove a cart item.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function removeItem(Request $request, $id)
    {
        $user = $request->user();
        $cartItem = $user->cartItems()->findOrFail($id);
        
        $cartItem->delete();
        
        return response()->json([
            'message' => 'Cart item removed',
        ]);
    }
    
    /**
     * Clear all items from the cart.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function clearCart(Request $request)
    {
        $user = $request->user();
        $user->cartItems()->delete();
        
        return response()->json([
            'message' => 'Cart cleared',
        ]);
    }
    
    /**
     * Get the user's saved cart data for frontend persistence.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getUserCart(Request $request)
    {
        try {
            // Add debug logging
            Log::info('getUserCart called', [
                'ip' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'referer' => $request->header('Referer'),
                'auth' => $request->user() ? 'authenticated' : 'unauthenticated'
            ]);
            
            // Add rate limiting - if this IP has made too many requests, return an error
            $ipAddress = $request->ip();
            $cacheKey = 'cart_request_' . $ipAddress;
            $requestCount = Cache::get($cacheKey, 0);
            
            // If more than 5 requests in 10 seconds, throttle
            if ($requestCount > 5) {
                Log::warning('Rate limiting cart requests for IP: ' . $ipAddress);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Too many requests',
                ], 429);
            }
            
            // Increment the request count and set expiry
            Cache::put($cacheKey, $requestCount + 1, now()->addSeconds(10));
            
            $user = $request->user();
            
            // Check if user has saved cart data
            if ($user && $user->cart_data) {
                return response()->json([
                    'status' => 'success',
                    'data' => json_decode($user->cart_data),
                ]);
            }
            
            return response()->json([
                'status' => 'success',
                'data' => [],
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getUserCart: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error retrieving cart data',
                'data' => [],
            ], 500);
        }
    }
    
    /**
     * Save the user's cart data for frontend persistence.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function saveUserCart(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated',
                ], 401);
            }
            
            $validator = Validator::make($request->all(), [
                'items' => 'required|array',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid cart data',
                    'errors' => $validator->errors(),
                ], 422);
            }
            
            // Save cart data to user record
            $user->cart_data = json_encode($request->items);
            $user->save();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Cart saved successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error in saveUserCart: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error saving cart data',
            ], 500);
        }
    }
}
