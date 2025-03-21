<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats()
    {
        // Get date range for filtering
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subDays(30);
        
        try {
            // Check if orders table exists and get its columns
            $orderColumns = Schema::getColumnListing('orders');
            $totalColumn = in_array('total_amount', $orderColumns) ? 'total_amount' : 
                          (in_array('grand_total', $orderColumns) ? 'grand_total' : 'total');
            $statusColumn = in_array('order_status', $orderColumns) ? 'order_status' : 'status';
            
            // Get total sales - use the correct column name
            $totalSales = 0;
            $totalOrders = 0;
            $pendingOrders = 0;
            $ordersByStatus = [];
            $recentOrders = [];
            $salesByDay = [];
            $dailySales = [];
            
            try {
                $totalSales = Order::where($statusColumn, '!=', 'cancelled')
                    ->sum($totalColumn);
                    
                // Get total orders
                $totalOrders = Order::count();
                
                // Get pending orders - use the correct status column
                $pendingOrders = Order::where($statusColumn, 'pending')->count();
            } catch (\Exception $e) {
                \Log::error('Error getting order stats: ' . $e->getMessage());
            }
            
            // Get total customers
            $totalCustomers = 0;
            $newCustomers = 0;
            
            try {
                $totalCustomers = User::where('role', 'customer')->count();
                
                // Get new customers in the last 30 days
                $newCustomers = User::where('role', 'customer')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();
            } catch (\Exception $e) {
                \Log::error('Error getting customer stats: ' . $e->getMessage());
            }
            
            // Get orders by status
            try {
                $statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'completed'];
                $ordersByStatus = [];
                
                foreach ($statuses as $status) {
                    $count = Order::where($statusColumn, $status)->count();
                    $ordersByStatus[] = [
                        'status' => $status,
                        'count' => $count
                    ];
                }
            } catch (\Exception $e) {
                \Log::error('Error getting orders by status: ' . $e->getMessage());
            }
            
            // Get recent orders
            try {
                $recentOrders = Order::with('user')
                    ->orderBy('created_at', 'desc')
                    ->take(5)
                    ->get()
                    ->map(function ($order) use ($statusColumn, $totalColumn) {
                        return [
                            'id' => $order->id,
                            'order_number' => 'ORD-' . $order->id,
                            'customer_name' => $order->user ? $order->user->name : 'Guest',
                            'total' => $order->{$totalColumn},
                            'status' => $order->{$statusColumn},
                            'created_at' => $order->created_at->toISOString(),
                            'updated_at' => $order->updated_at->toISOString()
                        ];
                    })
                    ->toArray();
            } catch (\Exception $e) {
                \Log::error('Error getting recent orders: ' . $e->getMessage());
            }
            
            // Check if products table exists
            $hasProducts = Schema::hasTable('products');
            
            // Get popular products
            $popularProducts = [];
            if ($hasProducts) {
                try {
                    $popularProducts = DB::table('products')
                        ->select('products.id', 'products.name', DB::raw('SUM(order_items.quantity) as total_quantity_sold'))
                        ->join('order_items', 'products.id', '=', 'order_items.product_id')
                        ->groupBy('products.id', 'products.name')
                        ->orderBy('total_quantity_sold', 'desc')
                        ->take(5)
                        ->get()
                        ->toArray();
                } catch (\Exception $e) {
                    \Log::error('Error getting popular products: ' . $e->getMessage());
                }
            }
                
            // Get low stock products
            $lowStockProducts = [];
            if ($hasProducts) {
                $stockColumn = Schema::hasColumn('products', 'stock_quantity') ? 'stock_quantity' : 
                              (Schema::hasColumn('products', 'stock') ? 'stock' : 
                              (Schema::hasColumn('products', 'quantity') ? 'quantity' : 'stock_quantity'));
                              
                try {
                    // Get low stock products
                    $lowStockQuery = \App\Models\Product::where($stockColumn, '<', 10)
                        ->where('is_active', 1);
                    
                    \Log::info('Low stock query: ' . $lowStockQuery->toSql());
                    \Log::info('Stock column being used: ' . $stockColumn);
                    
                    $lowStockProducts = $lowStockQuery
                        ->select('id', 'name', DB::raw($stockColumn . ' as stock'))
                        ->orderBy($stockColumn)
                        ->take(5)
                        ->get();
                    
                    // Map the results to match the expected format in the frontend
                    $lowStockProducts = $lowStockProducts->map(function($product) {
                        // Try to get the product image if available
                        $image = null;
                        try {
                            $productWithImage = \App\Models\Product::with('images')->find($product->id);
                            if ($productWithImage && $productWithImage->images && $productWithImage->images->count() > 0) {
                                $image = $productWithImage->images->first()->url;
                            }
                        } catch (\Exception $e) {
                            \Log::error('Error getting product image: ' . $e->getMessage());
                        }
                        
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'stock' => $product->stock,
                            'image' => $image,
                            'min_stock' => 5  // Default minimum stock threshold
                        ];
                    })->toArray();
                    
                    \Log::info('Low stock products found: ' . count($lowStockProducts));
                    \Log::info('Low stock products: ' . json_encode($lowStockProducts));
                } catch (\Exception $e) {
                    \Log::error('Error fetching low stock products: ' . $e->getMessage());
                    $lowStockProducts = [];
                }
            }
                
            // Get sales by category
            $salesByCategory = [];
            try {
                if (Schema::hasTable('categories') && Schema::hasTable('order_items')) {
                    $salesByCategory = DB::table('categories')
                        ->select('categories.name as category', DB::raw('SUM(order_items.quantity * order_items.unit_price) as total_sales'))
                        ->join('products', 'categories.id', '=', 'products.category_id')
                        ->join('order_items', 'products.id', '=', 'order_items.product_id')
                        ->join('orders', 'order_items.order_id', '=', 'orders.id')
                        ->where('orders.' . $statusColumn, '!=', 'cancelled')
                        ->groupBy('categories.name')
                        ->orderBy('total_sales', 'desc')
                        ->get()
                        ->toArray();
                }
            } catch (\Exception $e) {
                \Log::error('Error getting sales by category: ' . $e->getMessage());
            }
            
            // Get daily sales for the last 7 days - use the correct columns
            $dailySales = [];
            try {
                $dailySales = Order::where($statusColumn, '!=', 'cancelled')
                    ->where('created_at', '>=', Carbon::now()->subDays(7))
                    ->select(
                        DB::raw('DATE(created_at) as date'),
                        DB::raw("SUM($totalColumn) as total_sales")
                    )
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get()
                    ->map(function($item) {
                        return [
                            'date' => Carbon::parse($item->date)->format('Y-m-d'),
                            'total_sales' => (float) $item->total_sales
                        ];
                    })->toArray();
            } catch (\Exception $e) {
                \Log::error('Error getting daily sales: ' . $e->getMessage());
            }
            
            // Fill in missing days with zero sales
            $salesByDay = [];
            try {
                for ($i = 6; $i >= 0; $i--) {
                    $date = Carbon::now()->subDays($i)->format('Y-m-d');
                    $found = false;
                    
                    foreach ($dailySales as $sale) {
                        if ($sale['date'] === $date) {
                            $salesByDay[] = $sale;
                            $found = true;
                            break;
                        }
                    }
                    
                    if (!$found) {
                        $salesByDay[] = [
                            'date' => $date,
                            'total_sales' => 0
                        ];
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Error filling in missing days with zero sales: ' . $e->getMessage());
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'total_sales' => $totalSales,
                    'total_orders' => $totalOrders,
                    'order_count' => $totalOrders,
                    'pending_orders' => $pendingOrders,
                    'total_customers' => $totalCustomers,
                    'new_customers' => $newCustomers,
                    'orders_by_status' => $ordersByStatus,
                    'recent_orders' => $recentOrders,
                    'popular_products' => $popularProducts,
                    'low_stock_products' => $lowStockProducts,
                    'daily_sales' => $dailySales,
                    'sales_by_day' => $salesByDay,
                    'sales_by_category' => $salesByCategory,
                    'date_range' => [
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d'),
                    ],
                ]
            ]);
        } catch (\Exception $e) {
            // Return a graceful error response with empty data
            \Log::error('Dashboard stats error: ' . $e->getMessage());
            
            return response()->json([
                'success' => true,
                'data' => [
                    'total_sales' => 0,
                    'total_orders' => 0,
                    'order_count' => 0,
                    'pending_orders' => 0,
                    'total_customers' => 0,
                    'new_customers' => 0,
                    'orders_by_status' => [],
                    'recent_orders' => [],
                    'popular_products' => [],
                    'low_stock_products' => [],
                    'daily_sales' => [],
                    'sales_by_day' => [],
                    'sales_by_category' => [],
                    'date_range' => [
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d'),
                    ]
                ]
            ]);
        }
    }

    /**
     * Get the last 7 days as formatted strings
     *
     * @return array
     */
    private function getLast7Days()
    {
        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $days[] = Carbon::now()->subDays($i)->format('M d');
        }
        return $days;
    }
}
