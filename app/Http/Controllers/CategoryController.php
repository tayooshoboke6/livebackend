<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Services\CacheService;

class CategoryController extends Controller
{
    /**
     * Display a listing of the categories.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $isAdmin = $request->user() && $request->user()->hasRole('admin');
            
            // Get main categories first
            $query = Category::query()
                ->when(!$isAdmin, function ($q) {
                    return $q->where('is_active', true);
                });

            // If we want only main categories
            if ($request->boolean('main_only', false)) {
                $query->whereNull('parent_id');
            } else {
                // Load main categories with their immediate subcategories
                $query->whereNull('parent_id')
                    ->with(['subcategories' => function($q) use ($isAdmin) {
                        if (!$isAdmin) {
                            $q->where('is_active', true);
                        }
                    }]);
            }

            $categories = $query->orderBy('order')->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'message' => 'Categories retrieved successfully',
                'categories' => $categories
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching categories: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created category in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'parent_id' => [
                    'nullable',
                    'exists:categories,id',
                    function ($attribute, $value, $fail) {
                        if ($value !== null) {
                            // Check if the parent is a main category (has no parent)
                            $parent = Category::find($value);
                            if ($parent && $parent->parent_id !== null) {
                                $fail('Subcategories cannot have subcategories. Only main categories can have subcategories.');
                            }
                        }
                    },
                ],
                'image' => 'nullable|image|max:2048',
                'is_active' => 'boolean',
                'is_featured' => 'boolean',
                'order' => 'integer|min:0',
                'color' => 'nullable|string|max:7'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $category = new Category($request->all());
            
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('categories', 'public');
                $category->image = $path;
            }

            $category->save();

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully',
                'category' => $category
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating category: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified category in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Category $category)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'parent_id' => [
                    'nullable',
                    'exists:categories,id',
                    function ($attribute, $value, $fail) use ($category) {
                        if ($value !== null) {
                            // Check if the parent is a main category
                            $parent = Category::find($value);
                            if ($parent && $parent->parent_id !== null) {
                                $fail('Subcategories cannot have subcategories. Only main categories can have subcategories.');
                            }
                            
                            // Check if this category has subcategories
                            if ($category->subcategories()->exists()) {
                                $fail('Cannot make a main category with subcategories into a subcategory.');
                            }
                        }
                    },
                ],
                'image' => 'nullable|image|max:2048',
                'is_active' => 'boolean',
                'is_featured' => 'boolean',
                'order' => 'integer|min:0',
                'color' => 'nullable|string|max:7'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('categories', 'public');
                $category->image = $path;
            }

            $category->update($request->except('image'));

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully',
                'category' => $category
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating category: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified category.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // Allow lookup by ID or slug
        $category = is_numeric($id) 
            ? Category::with('subcategories')->findOrFail($id)
            : Category::with('subcategories')->where('slug', $id)->firstOrFail();

        return response()->json($category);
    }

    /**
     * Remove the specified category from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        // Check if category has subcategories
        if ($category->subcategories()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category with subcategories',
            ], 422);
        }

        // Check if category has products
        if ($category->products()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category with products',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully',
        ]);
    }

    /**
     * Get products for a specific category.
     *
     * @param  string  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function products($id, Request $request)
    {
        // Allow lookup by ID or slug
        $category = is_numeric($id) 
            ? Category::findOrFail($id)
            : Category::where('slug', $id)->firstOrFail();

        $query = $category->products()
            ->with(['category', 'measurements', 'images']) 
            ->when($request->has('search'), function ($q) use ($request) {
                return $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            })
            ->when($request->has('min_price'), function ($q) use ($request) {
                return $q->where('base_price', '>=', $request->min_price);
            })
            ->when($request->has('max_price'), function ($q) use ($request) {
                return $q->where('base_price', '<=', $request->max_price);
            });

        // Default to active products for non-admin users
        if (!$request->has('include_inactive') || !$request->user() || !$request->user()->hasRole('admin')) {
            $query->where('is_active', true);
        }

        // Get paginated results
        $products = $query->paginate($request->input('per_page', 15));

        // Log the response for debugging
        \Log::info('Category products response for category ID: ' . $id, [
            'product_count' => $products->count(),
            'first_product' => $products->count() > 0 ? $products->first()->toArray() : null
        ]);

        // Format the response to match the product detail endpoint format
        return response()->json([
            'success' => true,
            'products' => $products->items(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total()
            ]
        ]);
    }

    /**
     * Get categories in a hierarchical tree structure.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function tree(Request $request)
    {
        try {
            // Simple approach: directly query the database without caching
            // This avoids all caching issues while we fix the underlying problem
            $includeInactive = $request->has('include_inactive') && $request->user() && $request->user()->hasRole('admin');
            
            // Log the request for debugging
            \Log::info('Category tree request received', [
                'include_inactive' => $includeInactive,
                'user_is_admin' => $request->user() && $request->user()->hasRole('admin')
            ]);
            
            $categories = Category::with(['subcategories' => function($query) use ($includeInactive) {
                if (!$includeInactive) {
                    $query->where('is_active', true);
                }
                $query->orderBy('name');
            }])
            ->whereNull('parent_id')
            ->when(!$includeInactive, function ($query) {
                return $query->where('is_active', true);
            })
            ->orderBy('name')
            ->get();
            
            // Log the categories found
            \Log::info('Categories found for tree', [
                'count' => $categories->count(),
                'first_category' => $categories->count() > 0 ? $categories->first()->toArray() : null,
                'all_categories' => $categories->toArray() // Log all categories for debugging
            ]);
            
            $categoriesTree = $this->buildOptimizedCategoryTree($categories);
            
            // Log the optimized tree
            \Log::info('Optimized category tree built', [
                'count' => count($categoriesTree),
                'first_category' => count($categoriesTree) > 0 ? $categoriesTree[0] : null,
                'all_categories' => $categoriesTree // Log all categories in the tree
            ]);
            
            // Return the data directly without nesting it in a "data" property
            return response()->json([
                'status' => 'success',
                'data' => $categoriesTree,
                'timestamp' => now()->toIso8601String(),
                'total_count' => count($categoriesTree),
                'cached' => false
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
        } catch (\Exception $e) {
            \Log::error('Category tree error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Failed to retrieve category tree',
                'error' => $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    /**
     * Build an optimized category tree using eager loaded data.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $categories
     * @return array
     */
    private function buildOptimizedCategoryTree($categories)
    {
        $result = [];

        foreach ($categories as $category) {
            // Build category data
            $categoryData = [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'image' => $category->image,
                'is_active' => $category->is_active,
                'children' => []
            ];

            // Add subcategories if they exist
            if ($category->subcategories && $category->subcategories->count() > 0) {
                $categoryData['children'] = $this->buildOptimizedCategoryTree($category->subcategories);
                $categoryData['has_children'] = true;
            } else {
                $categoryData['has_children'] = false;
            }

            $result[] = $categoryData;
        }

        return $result;
    }

    /**
     * Get product counts for multiple categories in a single batch request
     * This reduces the N+1 query problem on the frontend
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function batchProductCounts(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'category_ids' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid category IDs',
                'errors' => $validator->errors()
            ], 400);
        }

        // Parse the category IDs
        $categoryIds = array_map('intval', explode(',', $request->category_ids));
        
        if (empty($categoryIds)) {
            return response()->json([
                'success' => true,
                'product_counts' => []
            ]);
        }

        // Generate a cache key based on the category IDs
        $cacheKey = 'category_product_counts_' . md5(implode(',', $categoryIds));
        
        // Try to get from cache first (5 minute cache)
        if (Cache::has($cacheKey)) {
            return response()->json([
                'success' => true,
                'product_counts' => Cache::get($cacheKey),
                'from_cache' => true
            ]);
        }

        // If not in cache, fetch from database
        $productCounts = [];
        
        // Use a more efficient query to get product counts for all categories at once
        $categoryCounts = \DB::table('products')
            ->select('category_id', \DB::raw('count(*) as count'))
            ->whereIn('category_id', $categoryIds)
            ->where('is_active', true)
            ->groupBy('category_id')
            ->get();
            
        // Format the results into a map of category ID to count
        foreach ($categoryCounts as $count) {
            $productCounts[$count->category_id] = $count->count;
        }
        
        // Add zero counts for categories with no products
        foreach ($categoryIds as $categoryId) {
            if (!isset($productCounts[$categoryId])) {
                $productCounts[$categoryId] = 0;
            }
        }
        
        // Cache the results for 5 minutes
        Cache::put($cacheKey, $productCounts, now()->addMinutes(5));
        
        return response()->json([
            'success' => true,
            'product_counts' => $productCounts
        ]);
    }

    /**
     * Get a hierarchical tree of categories
     *
     * @return \Illuminate\Http\Response
     */
    public function getCategoryTree()
    {
        try {
            // Direct database query without caching to avoid tagging issues
            $categories = $this->buildOptimizedCategoryTree(
                Category::with(['subcategories' => function($query) {
                    $query->where('is_active', true)
                          ->orderBy('name');
                }])
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
            );
            
            return response()->json([
                'categories' => $categories,
                'timestamp' => now()->toIso8601String(),
                'total_count' => count($categories),
                'cached' => false
            ]);
        } catch (\Exception $e) {
            \Log::error('Category tree error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve category tree',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
