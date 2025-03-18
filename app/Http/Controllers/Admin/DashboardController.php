<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats()
    {
        // This is a placeholder with mock data for now
        // In a real implementation, we would query the database for actual stats
        
        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_sales' => 125000.50,
                    'total_orders' => 324,
                    'total_customers' => 156,
                    'total_products' => 87,
                    'low_stock_products' => 12,
                ],
                'recent_orders' => [
                    [
                        'id' => 1001,
                        'customer_name' => 'John Doe',
                        'total' => 5250.75,
                        'status' => 'completed',
                        'date' => Carbon::now()->subDays(1)->toDateTimeString(),
                    ],
                    [
                        'id' => 1002,
                        'customer_name' => 'Jane Smith',
                        'total' => 3120.50,
                        'status' => 'processing',
                        'date' => Carbon::now()->subDays(2)->toDateTimeString(),
                    ],
                    [
                        'id' => 1003,
                        'customer_name' => 'Michael Johnson',
                        'total' => 7890.25,
                        'status' => 'pending',
                        'date' => Carbon::now()->subDays(3)->toDateTimeString(),
                    ],
                ],
                'sales_chart' => [
                    'labels' => $this->getLast7Days(),
                    'data' => [12500, 8700, 15600, 9800, 11200, 13500, 16700],
                ],
                'top_products' => [
                    [
                        'id' => 101,
                        'name' => 'Premium Water Bottle',
                        'sales_count' => 87,
                        'revenue' => 13050,
                    ],
                    [
                        'id' => 102,
                        'name' => 'Organic Coffee Beans',
                        'sales_count' => 65,
                        'revenue' => 9750,
                    ],
                    [
                        'id' => 103,
                        'name' => 'Fresh Milk (1L)',
                        'sales_count' => 54,
                        'revenue' => 8100,
                    ],
                ],
                'order_status_distribution' => [
                    'labels' => ['Pending', 'Processing', 'Completed', 'Cancelled'],
                    'data' => [45, 65, 210, 4],
                ],
            ]
        ]);
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
