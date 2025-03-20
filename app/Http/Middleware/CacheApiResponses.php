<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CacheApiResponses
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Only cache GET requests
        if (!$request->isMethod('GET')) {
            return $next($request);
        }

        // Don't cache if user is not authenticated (for user-specific data)
        // or if the request explicitly asks to bypass cache
        if ($request->has('no_cache') || !$request->user()) {
            return $next($request);
        }

        // Generate a unique cache key based on the full URL and user ID if authenticated
        $cacheKey = 'api_response_' . md5($request->fullUrl() . ($request->user() ? '_user_' . $request->user()->id : ''));
        
        // Determine cache duration based on endpoint patterns
        $cacheDuration = $this->getCacheDuration($request->path());

        // If cache duration is 0, don't cache this endpoint
        if ($cacheDuration === 0) {
            return $next($request);
        }

        // Check if we have a cached response
        if (Cache::has($cacheKey)) {
            $cachedResponse = Cache::get($cacheKey);
            return response()->json($cachedResponse, 200, [
                'X-Cache' => 'HIT',
                'Cache-Control' => 'public, max-age=' . $cacheDuration
            ]);
        }

        // Process the request and cache the response
        $response = $next($request);
        
        // Only cache successful responses
        if ($response->getStatusCode() === 200) {
            $responseData = json_decode($response->getContent(), true);
            Cache::put($cacheKey, $responseData, $cacheDuration);
            
            // Add cache headers
            $response->header('X-Cache', 'MISS');
            $response->header('Cache-Control', 'public, max-age=' . $cacheDuration);
        }

        return $response;
    }

    /**
     * Determine cache duration based on endpoint path
     * 
     * @param string $path
     * @return int Cache duration in seconds
     */
    private function getCacheDuration($path)
    {
        // Long cache (1 hour) for rarely changing data
        $longCachePatterns = [
            'categories',
            'settings',
            'pages',
            'countries',
            'states',
            'cities',
        ];

        // Medium cache (10 minutes) for semi-dynamic data
        $mediumCachePatterns = [
            'products',
            'banners',
            'promotions',
            'featured',
        ];

        // Short cache (2 minutes) for frequently changing data
        $shortCachePatterns = [
            'stock',
            'availability',
            'search',
        ];

        // No cache for user-specific or real-time data
        $noCachePatterns = [
            'cart',
            'user/profile',
            'orders',
            'checkout',
            'payment',
            'notifications',
        ];

        // Check if path matches any no-cache patterns
        foreach ($noCachePatterns as $pattern) {
            if (strpos($path, $pattern) !== false) {
                return 0; // No caching
            }
        }

        // Check if path matches any long-cache patterns
        foreach ($longCachePatterns as $pattern) {
            if (strpos($path, $pattern) !== false) {
                return 3600; // 1 hour
            }
        }

        // Check if path matches any medium-cache patterns
        foreach ($mediumCachePatterns as $pattern) {
            if (strpos($path, $pattern) !== false) {
                return 600; // 10 minutes
            }
        }

        // Check if path matches any short-cache patterns
        foreach ($shortCachePatterns as $pattern) {
            if (strpos($path, $pattern) !== false) {
                return 120; // 2 minutes
            }
        }

        // Default cache duration for other endpoints
        return 300; // 5 minutes
    }
}
