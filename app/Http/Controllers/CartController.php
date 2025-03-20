<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductMeasurement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
        $user = $request->user();
        
        // Check if user has saved cart data
        if ($user->cart_data) {
            return response()->json([
                'status' => 'success',
                'data' => json_decode($user->cart_data),
            ]);
        }
        
        return response()->json([
            'status' => 'success',
            'data' => [],
        ]);
    }
    
    /**
     * Replace the user's entire cart with new items.
     * Optimized for performance with caching.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function replaceCart(Request $request)
    {
        $user = $request->user();
        
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
        
        // Check if the cart data is actually different to avoid unnecessary DB writes
        $currentCartData = $user->cart_data ? json_decode($user->cart_data, true) : [];
        $newCartData = $request->items;
        
        // Simple hash comparison to avoid unnecessary updates
        $currentHash = md5(json_encode($currentCartData));
        $newHash = md5(json_encode($newCartData));
        
        if ($currentHash === $newHash) {
            // Cart hasn't changed, return success without DB write
            return response()->json([
                'status' => 'success',
                'message' => 'Cart unchanged',
                'cached' => true
            ]);
        }
        
        // Save cart data to user record using a more efficient update
        // This avoids loading the entire user model when we only need to update one field
        \DB::table('users')
            ->where('id', $user->id)
            ->update(['cart_data' => json_encode($request->items)]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Cart replaced successfully',
        ]);
    }
    
    /**
     * Add a single item to the user's cart.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addCartItem(Request $request)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'item' => 'required|array',
            'item.id' => 'required|numeric',
            'item.quantity' => 'required|numeric|min:1',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid item data',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        $newItem = $request->item;
        $currentCart = [];
        
        if ($user->cart_data) {
            $currentCart = json_decode($user->cart_data, true);
        }
        
        // Check if item already exists in cart
        $itemExists = false;
        foreach ($currentCart as &$cartItem) {
            if ($cartItem['id'] == $newItem['id']) {
                // Update quantity
                $cartItem['quantity'] += $newItem['quantity'];
                $itemExists = true;
                break;
            }
        }
        
        // If item doesn't exist, add it
        if (!$itemExists) {
            $currentCart[] = $newItem;
        }
        
        // Save updated cart
        $user->cart_data = json_encode($currentCart);
        $user->save();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Item added to cart successfully',
            'data' => $currentCart,
        ]);
    }
    
    /**
     * Remove an item from the user's cart.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function removeCartItem(Request $request)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid item ID',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        $itemId = $request->id;
        $currentCart = [];
        
        if ($user->cart_data) {
            $currentCart = json_decode($user->cart_data, true);
        }
        
        // Filter out the item to remove
        $updatedCart = array_filter($currentCart, function($item) use ($itemId) {
            return $item['id'] != $itemId;
        });
        
        // Reindex array
        $updatedCart = array_values($updatedCart);
        
        // Save updated cart
        $user->cart_data = json_encode($updatedCart);
        $user->save();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Item removed from cart successfully',
            'data' => $updatedCart,
        ]);
    }
    
    /**
     * Update the quantity of an item in the user's cart.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateCartItem(Request $request)
    {
        $user = $request->user();
        
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric',
            'quantity' => 'required|numeric|min:1',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid update data',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        $itemId = $request->id;
        $newQuantity = $request->quantity;
        $currentCart = [];
        
        if ($user->cart_data) {
            $currentCart = json_decode($user->cart_data, true);
        }
        
        // Update the quantity of the specified item
        $itemFound = false;
        foreach ($currentCart as &$item) {
            if ($item['id'] == $itemId) {
                $item['quantity'] = $newQuantity;
                $itemFound = true;
                break;
            }
        }
        
        if (!$itemFound) {
            return response()->json([
                'status' => 'error',
                'message' => 'Item not found in cart',
            ], 404);
        }
        
        // Save updated cart
        $user->cart_data = json_encode($currentCart);
        $user->save();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Item quantity updated successfully',
            'data' => $currentCart,
        ]);
    }
    
    /**
     * Clear all items from the user's cart.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function clearUserCart(Request $request)
    {
        $user = $request->user();
        
        // Clear cart data
        $user->cart_data = json_encode([]);
        $user->save();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Cart cleared successfully',
        ]);
    }
    
    /**
     * Initialize the user's cart from local storage after login.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function initializeFromLocal(Request $request)
    {
        $user = $request->user();
        
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
            'message' => 'Cart initialized successfully',
        ]);
    }
    
    /**
     * Save the user's cart data for frontend persistence.
     * @deprecated Use replaceCart instead
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function saveUserCart(Request $request)
    {
        return $this->replaceCart($request);
    }
}
