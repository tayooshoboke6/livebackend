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
        // Sample orders array - add your orders here when needed
        $orders = [];
        
        foreach ($orders as $orderData) {
            // Create order
            $order = Order::create($orderData);
            
            // Create order items if provided
            if (isset($orderData['items']) && is_array($orderData['items'])) {
                foreach ($orderData['items'] as $itemData) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $itemData['product_id'],
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                        'subtotal' => $itemData['quantity'] * $itemData['unit_price'],
                    ]);
                }
            }
        }
        
        $this->command->info('Orders seeded successfully with ' . count($orders) . ' orders.');
    }
}
