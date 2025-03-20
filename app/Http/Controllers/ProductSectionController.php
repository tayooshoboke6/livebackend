<?php

namespace App\Http\Controllers;

use App\Models\ProductSection;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\CacheService;

class ProductSectionController extends Controller
{
    /**
     * Display a listing of the product sections for customers.
     * Only returns active sections with their associated products.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Cache key based on request parameters
        $cacheKey = 'product_sections';
        
        // Add type to cache key if provided
        if ($request->has('type')) {
            $cacheKey .= '_type_' . $request->type;
        }
        
        // Add limit to cache key if provided
        if ($request->has('limit')) {
            $cacheKey .= '_limit_' . $request->limit;
        }
        
        // Cache tags for easier invalidation
        $cacheTags = ['product_sections', 'frontend', 'products'];
        
        // Cache duration - 1 hour
        $cacheDuration = 3600;
        
        // Check if force refresh is requested
        $forceRefresh = $request->has('forceRefresh');
        
        // If force refresh requested, clear the cache
        if ($forceRefresh) {
            CacheService::invalidateByTags($cacheTags);
        }
        
        // Get product sections using stale-while-revalidate pattern
        $productSections = CacheService::staleWhileRevalidate(
            $cacheKey,
            $cacheDuration,
            function () use ($request) {
                $query = ProductSection::where('is_active', true)
                    ->orderBy('display_order', 'asc');
                    
                // Filter by type if provided
                if ($request->has('type')) {
                    $query->where('type', $request->type);
                }
                
                $productSections = $query->get();
                
                // Load products for each section
                foreach ($productSections as $section) {
                    try {
                        // Ensure product_ids is an array
                        $productIds = is_array($section->product_ids) ? $section->product_ids : [];
                        
                        // Only query if we have product IDs
                        if (!empty($productIds)) {
                            $productQuery = Product::whereIn('id', $productIds)
                                ->where('is_active', true);
                                
                            // Apply limit if provided
                            if ($request->has('limit')) {
                                $productQuery->limit($request->limit);
                            }
                            
                            $section->products = $productQuery->get();
                        } else {
                            // If product_ids is empty, try to dynamically populate based on section type
                            $limit = $request->has('limit') ? (int)$request->limit : 8;
                            
                            switch ($section->type) {
                                case 'featured':
                                    $section->products = Product::where('is_active', true)
                                        ->where('is_featured', true)
                                        ->orderBy('created_at', 'desc')
                                        ->limit($limit)
                                        ->get();
                                    break;
                                    
                                case 'hot_deals':
                                    // First try is_hot_deal flag
                                    $query = Product::where('is_active', true)
                                        ->where('is_hot_deal', true);
                                    
                                    // If no products with is_hot_deal, fallback to products with discount
                                    $count = $query->count();
                                    if ($count === 0) {
                                        Log::info("No products with is_hot_deal flag, falling back to products with sale_price");
                                        $query = Product::where('is_active', true)
                                            ->whereNotNull('sale_price')
                                            ->whereRaw('sale_price < base_price');
                                    }
                                    
                                    $section->products = $query
                                        ->orderBy('created_at', 'desc')
                                        ->limit($limit)
                                        ->get();
                                    break;
                                    
                                case 'new_arrivals':
                                    $section->products = Product::where('is_active', true)
                                        ->where('is_new_arrival', true)
                                        ->orderBy('created_at', 'desc')
                                        ->limit($limit)
                                        ->get();
                                    break;
                                    
                                case 'best_sellers':
                                    // First try is_best_seller flag
                                    $query = Product::where('is_active', true)
                                        ->where('is_best_seller', true);
                                    
                                    // If no products with is_best_seller, fallback to products with highest total_sold
                                    $count = $query->count();
                                    if ($count === 0) {
                                        Log::info("No products with is_best_seller flag, falling back to products with highest total_sold");
                                        $query = Product::where('is_active', true)
                                            ->orderBy('total_sold', 'desc');
                                    }
                                    
                                    $section->products = $query
                                        ->limit($limit)
                                        ->get();
                                    break;
                                    
                                default:
                                    // Initialize with empty collection if no product IDs and no special handling
                                    $section->products = collect([]);
                            }
                            
                            // Log the dynamic population
                            Log::info("Dynamically populated section {$section->title} with {$section->products->count()} products");
                        }
                    } catch (\Exception $e) {
                        // Log the error but continue processing other sections
                        Log::error("Error loading products for section {$section->id}: " . $e->getMessage());
                        $section->products = collect([]);
                    }
                }
                
                return $productSections;
            },
            $cacheTags
        );
        
        // Add cache control headers for frontend caching with stale-while-revalidate
        return response()->json([
            'status' => 'success',
            'productSections' => $productSections,
            'cached' => !$forceRefresh && Cache::has($cacheKey),
            'cache_ttl' => $cacheDuration
        ])->withHeaders([
            'Cache-Control' => 'public, max-age=3600, stale-while-revalidate=86400',
            'X-Cache-Status' => !$forceRefresh && Cache::has($cacheKey) ? 'HIT' : 'MISS',
            'ETag' => md5(json_encode($productSections))
        ]);
    }
    
    /**
     * Get products for a specific section type.
     * This is useful for displaying products in a specific category or section.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getProductsByType(Request $request)
    {
        // Log the request for debugging
        Log::info('Products by type request', ['request' => $request->all()]);
        
        // Validate request with more lenient validation
        $request->validate([
            'type' => 'required|string',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);
        
        $limit = $request->limit ?? 10;
        $type = $request->type;
        
        Log::info('Processing products by type', ['type' => $type, 'limit' => $limit]);
        
        return $this->getProductsByTypeInternal($type, $limit);
    }
    
    /**
     * Get products for a specific section type using URL parameter.
     * This is an alternative endpoint that uses a URL parameter instead of a query parameter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $type
     * @return \Illuminate\Http\Response
     */
    public function getProductsByTypeParam(Request $request, $type)
    {
        // Log the request for debugging
        Log::info('Products by type param request', ['type' => $type, 'request' => $request->all()]);
        
        // Validate request
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:50',
        ]);
        
        $limit = $request->limit ?? 10;
        
        Log::info('Processing products by type param', ['type' => $type, 'limit' => $limit]);
        
        return $this->getProductsByTypeInternal($type, $limit);
    }
    
    /**
     * Internal method to get products by type.
     * This is used by both getProductsByType and getProductsByTypeParam.
     *
     * @param  string  $type
     * @param  int  $limit
     * @return \Illuminate\Http\Response
     */
    private function getProductsByTypeInternal($type, $limit)
    {
        try {
            // Cache key based on type and limit
            $cacheKey = "products_by_type_{$type}_limit_{$limit}";
            
            // Check if we have a cached response
            if (Cache::has($cacheKey)) {
                $products = Cache::get($cacheKey);
                Log::info("Cache hit for products by type: {$type}", ['count' => count($products)]);
                
                return response()->json([
                    'products' => $products,
                    'cached' => true,
                    'timestamp' => now()->toIso8601String()
                ])->header('Cache-Control', 'public, max-age=3600');
            }
            
            // Get products based on type
            $query = Product::where('is_active', true);
            
            switch ($type) {
                case 'featured':
                    $query->where('is_featured', true);
                    break;
                case 'new_arrivals':
                    $query->where('is_new_arrival', true);
                    break;
                case 'expiring_soon':
                    $query->whereNotNull('expiry_date')
                        ->whereDate('expiry_date', '>', now())
                        ->whereDate('expiry_date', '<=', now()->addDays(30))
                        ->orderBy('expiry_date', 'asc');
                    break;
                case 'best_sellers':
                    $query->where('total_sold', '>', 0)
                        ->orderBy('total_sold', 'desc');
                    break;
                case 'hot_deals':
                    $query->whereNotNull('sale_price')
                        ->whereRaw('sale_price < base_price')
                        ->orderByRaw('(base_price - sale_price) / base_price DESC');
                    break;
                default:
                    // For custom or other types, use product sections
                    $sections = ProductSection::where('type', $type)
                        ->where('is_active', true)
                        ->get();
                        
                    $productIds = [];
                    foreach ($sections as $section) {
                        $productIds = array_merge($productIds, $section->product_ids ?? []);
                    }
                    
                    if (!empty($productIds)) {
                        $query->whereIn('id', $productIds);
                    }
                    break;
            }
            
            // Add with() to eager load relationships and optimize the query
            $query->with(['category:id,name,slug', 'images' => function($q) {
                $q->where('is_primary', true)->orWhere('sort_order', 0)->select('id', 'product_id', 'image_path', 'is_primary', 'sort_order');
            }]);
            
            $products = $query->limit($limit)->get();
            
            // If no products found for the requested type, provide a fallback
            if ($products->isEmpty() && in_array($type, ['featured', 'best_sellers'])) {
                Log::warning("No products found for type: {$type}, using fallback");
                
                // Fallback: get any active products
                $products = Product::where('is_active', true)
                    ->with(['category:id,name,slug', 'images' => function($q) {
                        $q->where('is_primary', true)->orWhere('sort_order', 0)->select('id', 'product_id', 'image_path', 'is_primary', 'sort_order');
                    }])
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();
            }
            
            // Cache the results
            Cache::put($cacheKey, $products, 3600); // Cache for 1 hour
            
            Log::info('Products by type response', ['type' => $type, 'count' => count($products)]);
            
            return response()->json([
                'products' => $products,
                'cached' => false,
                'timestamp' => now()->toIso8601String()
            ])->header('Cache-Control', 'public, max-age=3600');
            
        } catch (\Exception $e) {
            Log::error("Error fetching products by type: {$type}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return an empty array instead of an error
            return response()->json([
                'products' => [],
                'error' => "Could not retrieve {$type} products",
                'timestamp' => now()->toIso8601String()
            ]);
        }
    }
    
    /**
     * Get featured products.
     * Direct endpoint for featured products.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getFeaturedProducts(Request $request)
    {
        // Log the request for debugging
        Log::info('Featured products request', ['request' => $request->all()]);
        
        // Validate request
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:50',
        ]);
        
        $limit = $request->limit ?? 10;
        
        return $this->getProductsByTypeInternal('featured', $limit);
    }
    
    /**
     * Get new arrivals.
     * Direct endpoint for new arrival products.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getNewArrivals(Request $request)
    {
        // Log the request for debugging
        Log::info('New arrivals request', ['request' => $request->all()]);
        
        // Validate request
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:50',
        ]);
        
        $limit = $request->limit ?? 10;
        
        return $this->getProductsByTypeInternal('new_arrivals', $limit);
    }
    
    /**
     * Get best sellers.
     * Direct endpoint for best seller products.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getBestSellers(Request $request)
    {
        // Log the request for debugging
        Log::info('Best sellers request', ['request' => $request->all()]);
        
        // Validate request
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:50',
        ]);
        
        $limit = $request->limit ?? 10;
        
        return $this->getProductsByTypeInternal('best_sellers', $limit);
    }
}
