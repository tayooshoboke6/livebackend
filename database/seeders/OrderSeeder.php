<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get users (excluding admin users)
        $users = User::where('role', '!=', 'admin')->take(10)->get();
        
        if ($users->isEmpty()) {
            $this->command->info('No users found. Please run UserSeeder first.');
            return;
        }
        
        // Get products
        $products = Product::take(20)->get();
        
        if ($products->isEmpty()) {
            $this->command->info('No products found. Please run ProductSeeder first.');
            return;
        }
        
        // Create orders for each user
        foreach ($users as $user) {
            // Create 1-3 orders per user
            $orderCount = rand(1, 3);
            
            for ($i = 0; $i < $orderCount; $i++) {
                // Determine order status
                $statuses = [
                    Order::STATUS_PENDING, 
                    Order::STATUS_PROCESSING, 
                    Order::STATUS_COMPLETED, 
                    Order::STATUS_CANCELLED
                ];
                $status = $statuses[array_rand($statuses)];
                
                // Determine payment status based on order status
                $paymentStatus = Order::PAYMENT_PENDING;
                if ($status === Order::STATUS_COMPLETED) {
                    $paymentStatus = Order::PAYMENT_PAID;
                } elseif ($status === Order::STATUS_CANCELLED) {
                    $paymentStatus = rand(0, 1) ? Order::PAYMENT_FAILED : Order::PAYMENT_PENDING;
                }
                
                // Create the order
                DB::beginTransaction();
                try {
                    $order = Order::create([
                        'user_id' => $user->id,
                        'order_number' => 'ORD-' . strtoupper(Str::random(8)),
                        'status' => $status,
                        'subtotal' => 0, // Will be calculated based on items
                        'discount' => rand(0, 1) ? rand(5, 20) : 0,
                        'tax' => rand(5, 15),
                        'shipping_fee' => rand(5, 15),
                        'grand_total' => 0, // Will be calculated
                        'payment_method' => ['credit_card', 'paypal', 'cash_on_delivery'][array_rand(['credit_card', 'paypal', 'cash_on_delivery'])],
                        'payment_status' => $paymentStatus,
                        'delivery_method' => ['shipping', 'pickup'][array_rand(['shipping', 'pickup'])],
                        'delivery_notes' => rand(0, 1) ? 'Please leave at the door' : null,
                        'shipping_address' => $user->address ?? '123 Main St',
                        'shipping_city' => $user->city ?? 'Anytown',
                        'shipping_state' => $user->state ?? 'CA',
                        'shipping_zip_code' => $user->zip_code ?? '12345',
                        'shipping_phone' => $user->phone ?? '555-123-4567',
                        'created_at' => now()->subDays(rand(1, 60)),
                    ]);
                    
                    // Add 1-5 items to the order
                    $itemCount = rand(1, 5);
                    $subtotal = 0;
                    
                    $orderProducts = $products->random($itemCount);
                    foreach ($orderProducts as $product) {
                        $quantity = rand(1, 3);
                        $unitPrice = $product->price ?? 9.99; // Default price if null
                        $itemSubtotal = $quantity * $unitPrice;
                        $subtotal += $itemSubtotal;
                        
                        OrderItem::create([
                            'order_id' => $order->id,
                            'product_id' => $product->id,
                            'product_name' => $product->name,
                            'quantity' => $quantity,
                            'unit_price' => $unitPrice,
                            'subtotal' => $itemSubtotal,
                            'measurement_unit' => 'piece',
                            'measurement_value' => 1,
                        ]);
                    }
                    
                    // Update order totals
                    $discount = $order->discount;
                    $tax = $order->tax;
                    $shippingFee = $order->shipping_fee;
                    $grandTotal = $subtotal - $discount + $tax + $shippingFee;
                    
                    $order->update([
                        'subtotal' => $subtotal,
                        'grand_total' => $grandTotal,
                    ]);
                    
                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->command->error("Error creating order: {$e->getMessage()}");
                }
            }
        }
        
        $this->command->info('Orders seeded successfully!');
    }
}
