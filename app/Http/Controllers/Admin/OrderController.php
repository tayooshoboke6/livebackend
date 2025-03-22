<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * Display a listing of all orders with filtering options.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            Log::info('Admin orders index called with params: ' . json_encode($request->all()));
            
            $query = Order::with(['user', 'items'])
                ->when($request->has('status') && $request->status, function ($q) use ($request) {
                    return $q->where('status', $request->status);
                })
                ->when($request->has('payment_status') && $request->payment_status, function ($q) use ($request) {
                    return $q->where('payment_status', $request->payment_status);
                })
                ->when($request->has('from_date') && $request->from_date, function ($q) use ($request) {
                    return $q->whereDate('created_at', '>=', $request->from_date);
                })
                ->when($request->has('to_date') && $request->to_date, function ($q) use ($request) {
                    return $q->whereDate('created_at', '<=', $request->to_date);
                })
                ->when($request->has('search') && $request->search, function ($q) use ($request) {
                    $search = $request->search;
                    return $q->where(function ($query) use ($search) {
                        $query->where('order_number', 'like', "%{$search}%")
                            ->orWhereHas('user', function ($userQuery) use ($search) {
                                $userQuery->where('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                            });
                    });
                });

            // Sort orders
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            
            if ($sortBy === 'date') {
                $sortBy = 'created_at';
            }
            
            $query->orderBy($sortBy, $sortOrder);
            
            // Paginate results
            $perPage = $request->input('per_page', 10);
            $orders = $query->paginate($perPage);
            
            // Transform orders for frontend
            $transformedOrders = $orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->user ? $order->user->name : 'Guest',
                    'total' => $order->grand_total,
                    'status' => $order->status,
                    'items_count' => $order->items->count(),
                    'payment_method' => $order->payment_method,
                    'payment_status' => $order->payment_status,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                    'user_id' => $order->user_id
                ];
            });
            
            // Log the response we're sending back
            Log::info('Sending orders response with ' . count($transformedOrders) . ' orders');
            
            // Return in the format expected by the frontend
            return response()->json([
                'status' => 'success',
                'data' => [
                    'data' => $transformedOrders, // Changed to match Laravel's default pagination format
                    'total' => $orders->total(),
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'last_page' => $orders->lastPage(),
                    'from' => $orders->firstItem(),
                    'to' => $orders->lastItem()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching admin orders: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch orders'
            ], 500);
        }
    }

    /**
     * Display the specified order with detailed information.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $order = Order::with(['user', 'items.product'])
                ->findOrFail($id);
            
            // Log the order details for debugging
            Log::info('Admin fetching order details for order #' . $id);
            
            return response()->json([
                'status' => 'success',
                'data' => $order
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching order details: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found'
            ], 404);
        }
    }

    /**
     * Update the order status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|string|in:pending,processing,shipped,delivered,completed,cancelled,refunded',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid status',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $order = Order::findOrFail($id);
            $oldStatus = $order->status;
            $newStatus = $request->status;
            
            // Update payment status based on order status
            $paymentStatus = $order->payment_status;
            if ($newStatus === Order::STATUS_COMPLETED && $paymentStatus === Order::PAYMENT_PENDING) {
                $paymentStatus = Order::PAYMENT_PAID;
            } elseif ($newStatus === Order::STATUS_CANCELLED && $paymentStatus === Order::PAYMENT_PENDING) {
                $paymentStatus = Order::PAYMENT_FAILED;
            } elseif ($newStatus === Order::STATUS_REFUNDED) {
                $paymentStatus = Order::PAYMENT_REFUNDED;
            } elseif (($newStatus === Order::STATUS_SHIPPED || $newStatus === Order::STATUS_DELIVERED) && $paymentStatus === Order::PAYMENT_PENDING) {
                // If order is shipped or delivered, payment should be marked as paid
                $paymentStatus = Order::PAYMENT_PAID;
            }
            
            $order->update([
                'status' => $newStatus,
                'payment_status' => $paymentStatus
            ]);
            
            // Log status change
            Log::info("Order #{$order->order_number} status changed from {$oldStatus} to {$newStatus} by admin");
            
            return response()->json([
                'status' => 'success',
                'message' => 'Order status updated successfully',
                'data' => $order
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating order status: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update order status'
            ], 500);
        }
    }

    /**
     * Get order statistics for the dashboard.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getStats(Request $request)
    {
        try {
            // Default to last 30 days if no date range specified
            $startDate = $request->input('start_date', now()->subDays(30)->toDateString());
            $endDate = $request->input('end_date', now()->toDateString());
            
            // Total sales amount (only from paid orders)
            $totalSales = Order::where('status', '!=', Order::STATUS_CANCELLED)
                ->where('payment_status', 'paid')
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->sum('grand_total');
                
            // Total number of orders
            $totalOrders = Order::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->count();
                
            // Pending orders count
            $pendingOrders = Order::where('status', Order::STATUS_PENDING)
                ->count();
                
            // Orders by status
            $ordersByStatus = Order::select('status', DB::raw('count(*) as count'))
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->groupBy('status')
                ->get();
                
            // Recent orders
            $recentOrders = Order::with('user')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get()
                ->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'customer_name' => $order->user ? $order->user->name : 'Guest',
                        'total' => $order->grand_total,
                        'status' => $order->status,
                        'payment_status' => $order->payment_status,
                        'created_at' => $order->created_at
                    ];
                });
                
            // Total customers
            $totalCustomers = User::where('role', '!=', 'admin')
                ->count();
                
            // New customers in date range
            $newCustomers = User::where('role', '!=', 'admin')
                ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                ->count();
                
            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_sales' => number_format($totalSales, 2),
                    'total_orders' => $totalOrders,
                    'pending_orders' => $pendingOrders,
                    'orders_by_status' => $ordersByStatus,
                    'recent_orders' => $recentOrders,
                    'total_customers' => $totalCustomers,
                    'new_customers' => $newCustomers,
                    'date_range' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting order stats: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get order statistics'
            ], 500);
        }
    }
}
