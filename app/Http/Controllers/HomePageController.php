<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductSectionController;
use App\Http\Controllers\NotificationBarController;

class HomePageController extends Controller
{
    protected $categoryController;
    protected $productController;
    protected $productSectionController;
    protected $notificationBarController;

    /**
     * Constructor to inject dependencies
     */
    public function __construct(
        CategoryController $categoryController,
        ProductController $productController,
        ProductSectionController $productSectionController,
        NotificationBarController $notificationBarController
    ) {
        $this->categoryController = $categoryController;
        $this->productController = $productController;
        $this->productSectionController = $productSectionController;
        $this->notificationBarController = $notificationBarController;
    }

    /**
     * Get all homepage data in a single API call
     * This reduces the number of network requests and improves performance
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Cache key for homepage data
        $cacheKey = 'homepage_data';
        
        // Cache tags for easier invalidation
        $cacheTags = ['homepage', 'frontend', 'products', 'categories'];
        
        // Cache duration - 1 hour
        $cacheDuration = 3600;
        
        // Check if force refresh is requested
        $forceRefresh = request()->has('forceRefresh');
        
        // If force refresh requested, clear the cache
        if ($forceRefresh) {
            \App\Services\CacheService::invalidateByTags($cacheTags);
        }
        
        // Get homepage data using stale-while-revalidate pattern
        $data = \App\Services\CacheService::staleWhileRevalidate(
            $cacheKey,
            $cacheDuration,
            function () {
                // Create a request object to pass to controllers
                $request = new \Illuminate\Http\Request();
                
                // Get categories (tree structure)
                $categoriesResponse = $this->categoryController->tree($request);
                $categoriesData = $categoriesResponse->getData();
                
                // Log the categories response for debugging
                \Log::info('Categories response in homepage controller', [
                    'response' => $categoriesData,
                    'has_data' => isset($categoriesData->data),
                    'data_type' => isset($categoriesData->data) ? gettype($categoriesData->data) : 'undefined',
                    'raw_response' => json_encode($categoriesData)
                ]);
                
                // Extract categories from the response
                $categories = isset($categoriesData->data) ? $categoriesData->data : [];
                
                // If categories is still empty, try to get them directly
                if (empty($categories)) {
                    \Log::info('Categories is empty, trying direct query');
                    $categoriesModel = \App\Models\Category::with(['subcategories' => function($query) {
                        $query->where('is_active', true)->orderBy('name');
                    }])
                    ->whereNull('parent_id')
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get();
                    
                    if ($categoriesModel->count() > 0) {
                        \Log::info('Found categories directly from model', [
                            'count' => $categoriesModel->count()
                        ]);
                        $categories = $categoriesModel->toArray();
                    }
                }
                
                // Get featured products
                $request->merge(['featured' => true, 'limit' => 10]);
                $featuredResponse = $this->productController->index($request);
                $featuredProducts = $featuredResponse->getData()->products ?? [];
                
                // Get new arrivals
                $request = new \Illuminate\Http\Request();
                $request->merge(['type' => 'new', 'limit' => 10]);
                $newArrivalsResponse = $this->productController->getByType($request, 'new');
                $newArrivals = $newArrivalsResponse->getData()->products ?? [];
                
                // Get best sellers
                $request = new \Illuminate\Http\Request();
                $request->merge(['type' => 'bestseller', 'limit' => 10]);
                $bestSellersResponse = $this->productController->getByType($request, 'bestseller');
                $bestSellers = $bestSellersResponse->getData()->products ?? [];
                
                // Get product sections
                $request = new \Illuminate\Http\Request();
                // Force refresh to ensure we get the latest data with our updated logic
                $request->merge(['forceRefresh' => true]);
                $productSectionsResponse = $this->productSectionController->index($request);
                $productSectionsData = $productSectionsResponse->getData();
                
                // Log the product sections response for debugging
                \Log::info('Product sections response in homepage controller', [
                    'response_type' => gettype($productSectionsData),
                    'has_productSections' => isset($productSectionsData->productSections),
                    'data_type' => isset($productSectionsData->productSections) ? gettype($productSectionsData->productSections) : 'undefined'
                ]);
                
                $productSections = isset($productSectionsData->productSections) ? $productSectionsData->productSections : [];
                
                // If product sections is empty or doesn't contain products, try to get them directly
                if (empty($productSections) || !$this->sectionsHaveProducts($productSections)) {
                    \Log::info('Product sections are empty or missing products, trying direct query');
                    
                    // Get product sections with their products directly from the database
                    $productSectionsModel = \App\Models\ProductSection::where('is_active', true)
                        ->orderBy('display_order', 'asc')
                        ->get();
                    
                    if ($productSectionsModel->count() > 0) {
                        \Log::info('Found product sections directly from model', [
                            'count' => $productSectionsModel->count()
                        ]);
                        
                        // Process each section to load its products
                        foreach ($productSectionsModel as $section) {
                            // Get products based on section type
                            switch ($section->type) {
                                case 'featured':
                                    $section->products = \App\Models\Product::where('is_active', true)
                                        ->where('is_featured', true)
                                        ->orderBy('created_at', 'desc')
                                        ->limit(8)
                                        ->get();
                                    break;
                                    
                                case 'hot_deals':
                                    // First try is_hot_deal flag
                                    $query = \App\Models\Product::where('is_active', true)
                                        ->where('is_hot_deal', true);
                                    
                                    // If no products with is_hot_deal, fallback to products with discount
                                    $count = $query->count();
                                    if ($count === 0) {
                                        \Log::info("No products with is_hot_deal flag, falling back to products with sale_price");
                                        $query = \App\Models\Product::where('is_active', true)
                                            ->whereNotNull('sale_price')
                                            ->whereRaw('sale_price < base_price');
                                    }
                                    
                                    $section->products = $query
                                        ->orderBy('created_at', 'desc')
                                        ->limit(8)
                                        ->get();
                                    break;
                                    
                                case 'new_arrivals':
                                    $section->products = \App\Models\Product::where('is_active', true)
                                        ->where('is_new_arrival', true)
                                        ->orderBy('created_at', 'desc')
                                        ->limit(8)
                                        ->get();
                                    break;
                                    
                                case 'best_sellers':
                                    // First try is_best_seller flag
                                    $query = \App\Models\Product::where('is_active', true)
                                        ->where('is_best_seller', true);
                                    
                                    // If no products with is_best_seller, fallback to products with highest total_sold
                                    $count = $query->count();
                                    if ($count === 0) {
                                        \Log::info("No products with is_best_seller flag, falling back to products with highest total_sold");
                                        $query = \App\Models\Product::where('is_active', true)
                                            ->orderBy('total_sold', 'desc');
                                    }
                                    
                                    $section->products = $query
                                        ->limit(8)
                                        ->get();
                                    break;
                                    
                                default:
                                    // If product_ids is set, use those products
                                    if (!empty($section->product_ids)) {
                                        $section->products = \App\Models\Product::whereIn('id', $section->product_ids)
                                            ->where('is_active', true)
                                            ->get();
                                    } else {
                                        $section->products = collect([]);
                                    }
                            }
                            
                            \Log::info("Section {$section->title} has {$section->products->count()} products");
                        }
                        
                        $productSections = $productSectionsModel;
                    }
                }
                
                // Get notification bars
                $notificationBarsResponse = $this->notificationBarController->getActive();
                $notificationBars = [$notificationBarsResponse->getData()->notificationBar];
                
                // Filter out null values from notification bars
                $notificationBars = array_filter($notificationBars);
                
                // Return the compiled data
                return [
                    'categories' => $categories,
                    'featuredProducts' => $featuredProducts,
                    'newArrivals' => $newArrivals,
                    'bestSellers' => $bestSellers,
                    'productSections' => $productSections,
                    'notificationBars' => $notificationBars
                ];
            },
            $cacheTags
        );
        
        // Add cache control headers for frontend caching with stale-while-revalidate
        return response()->json([
            'status' => 'success',
            'data' => $data,
            'cached' => !$forceRefresh && Cache::has($cacheKey),
            'cache_ttl' => $cacheDuration
        ])->withHeaders([
            'Cache-Control' => 'public, max-age=3600, stale-while-revalidate=86400',
            'X-Cache-Status' => !$forceRefresh && Cache::has($cacheKey) ? 'HIT' : 'MISS',
            'ETag' => md5(json_encode($data))
        ]);
    }
    
    /**
     * Get homepage data with specific sections
     * Allows client to request only the data they need
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSpecificSections(Request $request)
    {
        $sections = $request->input('sections', []);
        $responseData = [];
        
        // Cache each section separately with appropriate durations
        if (in_array('categories', $sections)) {
            $responseData['categories'] = Cache::remember('homepage_categories', 3600, function () {
                $categoriesResponse = $this->categoryController->tree(new \Illuminate\Http\Request());
                return $categoriesResponse->getData()->data ?? [];
            });
        }
        
        if (in_array('featured', $sections)) {
            $responseData['featuredProducts'] = Cache::remember('homepage_featured', 900, function () {
                // Get featured products
                $request = new \Illuminate\Http\Request();
                $request->merge(['type' => 'featured', 'limit' => 10]);
                $featuredResponse = $this->productController->getByType($request);
                return $featuredResponse->getData()->products ?? [];
            });
        }
        
        if (in_array('newArrivals', $sections)) {
            $responseData['newArrivals'] = Cache::remember('homepage_new_arrivals', 900, function () {
                // Get new arrivals
                $request = new \Illuminate\Http\Request();
                $request->merge(['type' => 'new_arrivals', 'limit' => 10]);
                $newArrivalsResponse = $this->productController->getByType($request);
                return $newArrivalsResponse->getData()->products ?? [];
            });
        }
        
        if (in_array('notificationBar', $sections)) {
            $responseData['notificationBar'] = Cache::remember('homepage_notification_bar', 900, function () {
                $notificationBarResponse = $this->notificationBarController->getActive();
                return $notificationBarResponse->getData()->notificationBar ?? null;
            });
        }
        
        return response()->json([
            'status' => 'success',
            'data' => $responseData
        ]);
    }
    
    /**
     * Helper method to check if product sections have products
     * 
     * @param array $sections
     * @return bool
     */
    private function sectionsHaveProducts($sections)
    {
        if (empty($sections)) {
            return false;
        }
        
        foreach ($sections as $section) {
            if (isset($section->products) && !empty($section->products) && count($section->products) > 0) {
                return true;
            }
        }
        
        return false;
    }
}
