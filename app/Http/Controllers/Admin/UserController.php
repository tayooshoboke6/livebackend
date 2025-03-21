<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $query = User::query();
            
            // Apply filters
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }
            
            if ($request->has('role') && $request->role) {
                $query->where('role', $request->role);
            }
            
            if ($request->has('status')) {
                $status = $request->status === 'active' ? 1 : 0;
                $query->where('is_active', $status);
            }
            
            // Add activity level filtering
            if ($request->has('activity') && $request->activity) {
                $activityLevel = $request->activity;
                
                try {
                    // Use updated_at as a fallback for activity level since last_login_at might not exist
                    if ($activityLevel === 'high') {
                        // High activity: active within the last 7 days
                        $query->where('updated_at', '>=', now()->subDays(7));
                    } elseif ($activityLevel === 'medium') {
                        // Medium activity: active between 7 and 30 days ago
                        $query->where('updated_at', '>=', now()->subDays(30))
                              ->where('updated_at', '<', now()->subDays(7));
                    } elseif ($activityLevel === 'low') {
                        // Low activity: not active for more than 30 days
                        $query->where('updated_at', '<', now()->subDays(30));
                    }
                } catch (\Exception $e) {
                    \Log::error('Error filtering by activity level: ' . $e->getMessage());
                    // If there's an error, don't apply the filter
                }
            }
            
            // Pagination
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);
            
            // Sorting
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            
            // Log the query parameters for debugging
            \Log::info('User query parameters:', [
                'page' => $page,
                'per_page' => $perPage,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
                'role' => $request->role,
                'status' => $request->status,
                'activity' => $request->activity,
                'search' => $request->search
            ]);
            
            $query->orderBy($sortBy, $sortOrder);
            
            $users = $query->paginate($perPage, ['*'], 'page', $page);
            
            // Enhance user data with additional information
            $enhancedUsers = $users->map(function($user) {
                $orderCount = Order::where('user_id', $user->id)->count();
                $lastActive = $user->last_login_at ?? $user->updated_at;
                
                // Determine activity level based on login frequency or order count
                $activityLevel = 'low';
                if ($lastActive && $lastActive->diffInDays(now()) < 7) {
                    $activityLevel = 'high';
                } elseif ($lastActive && $lastActive->diffInDays(now()) < 30) {
                    $activityLevel = 'medium';
                }
                
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => $user->is_active ? 'active' : 'inactive',
                    'location' => $user->city && $user->state ? "{$user->city}, {$user->state}" : 'Not specified',
                    'created_at' => $user->created_at->format('Y-m-d'),
                    'last_active' => $lastActive ? $lastActive->format('Y-m-d') : null,
                    'activity_level' => $activityLevel,
                    'order_count' => $orderCount,
                    'profile_photo' => $user->profile_photo
                ];
            });
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'users' => $enhancedUsers,
                    'total' => $users->total(),
                    'page' => $users->currentPage(),
                    'last_page' => $users->lastPage()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching users: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch users'
            ], 500);
        }
    }
    
    /**
     * Store a newly created user in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'role' => 'required|string|in:admin,store-manager,inventory-manager,cashier,delivery-staff,customer',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'zip_code' => 'nullable|string|max:20',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'phone' => $request->phone,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'zip_code' => $request->zip_code,
                'is_active' => true,
            ]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'User created successfully',
                'data' => $user
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating user: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create user'
            ], 500);
        }
    }
    
    /**
     * Display the specified user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $user = User::findOrFail($id);
            $orderCount = Order::where('user_id', $user->id)->count();
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'phone' => $user->phone,
                    'address' => $user->address,
                    'city' => $user->city,
                    'state' => $user->state,
                    'zip_code' => $user->zip_code,
                    'status' => $user->is_active ? 'active' : 'inactive',
                    'created_at' => $user->created_at->format('Y-m-d'),
                    'order_count' => $orderCount,
                    'profile_photo' => $user->profile_photo
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'User not found'
            ], 404);
        }
    }
    
    /**
     * Update the specified user in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'email' => [
                    'sometimes',
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique('users')->ignore($id)
                ],
                'password' => 'sometimes|nullable|string|min:8',
                'role' => 'sometimes|required|string|in:admin,store-manager,inventory-manager,cashier,delivery-staff,customer',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'zip_code' => 'nullable|string|max:20',
                'is_active' => 'sometimes|boolean'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Log the request data for debugging
            \Log::info('User update request data:', $request->all());
            
            // Create a clean array of only the fields we want to update
            $updateData = [];
            
            // Only include fields that are present in the request
            if ($request->has('name')) $updateData['name'] = $request->name;
            if ($request->has('email')) $updateData['email'] = $request->email;
            if ($request->has('role')) $updateData['role'] = $request->role;
            if ($request->has('phone')) $updateData['phone'] = $request->phone;
            if ($request->has('address')) $updateData['address'] = $request->address;
            if ($request->has('city')) $updateData['city'] = $request->city;
            if ($request->has('state')) $updateData['state'] = $request->state;
            if ($request->has('zip_code')) $updateData['zip_code'] = $request->zip_code;
            if ($request->has('is_active')) $updateData['is_active'] = $request->is_active;
            
            // Handle password separately
            if ($request->has('password') && $request->password) {
                $updateData['password'] = \Hash::make($request->password);
            }
            
            \Log::info('User update data after processing:', $updateData);
            
            $user->update($updateData);
            
            return response()->json([
                'status' => 'success',
                'message' => 'User updated successfully',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            \Log::error('Error updating user: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update user: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Remove the specified user from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Check if user has any orders
            $orderCount = Order::where('user_id', $user->id)->count();
            if ($orderCount > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete user with existing orders. Consider deactivating instead.'
                ], 422);
            }
            
            $user->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'User deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting user: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete user'
            ], 500);
        }
    }
    
    /**
     * Update user status (active/inactive)
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'status' => 'required|string|in:active,inactive',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Log the request data for debugging
            \Log::info('User status update request data:', $request->all());
            
            // Convert status string to boolean
            $is_active = $request->status === 'active';
            
            // Update only the is_active field
            $user->is_active = $is_active;
            $user->save();
            
            return response()->json([
                'status' => 'success',
                'message' => 'User status updated successfully',
                'data' => [
                    'id' => $user->id,
                    'status' => $request->status
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error updating user status: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update user status: ' . $e->getMessage()
            ], 500);
        }
    }
}
