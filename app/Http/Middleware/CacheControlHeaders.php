<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CacheControlHeaders
{
    /**
     * Cache durations for different types of resources (in seconds)
     */
    const CACHE_DURATIONS = [
        'static' => 86400,     // 24 hours for static resources
        'categories' => 3600,  // 1 hour for categories
        'products' => 1800,    // 30 minutes for products
        'user' => 300,         // 5 minutes for user data
        'default' => 600       // 10 minutes default
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Only add cache headers to successful JSON responses
        if ($response->isSuccessful() && $this->isJsonResponse($response)) {
            $path = $request->path();
            $duration = $this->getCacheDuration($path);
            
            // Skip cache headers for authenticated requests except GET
            if ($request->user() && $request->method() !== 'GET') {
                $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
                $response->header('Pragma', 'no-cache');
                $response->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
            } else {
                // Add appropriate cache headers based on the resource type
                $response->header('Cache-Control', "public, max-age={$duration}");
                $response->header('Expires', gmdate('D, d M Y H:i:s', time() + $duration) . ' GMT');
            }
            
            // Add ETag for all responses
            if (!$response->headers->has('ETag')) {
                $content = $response->getContent();
                $eTag = md5($content);
                $response->header('ETag', $eTag);
            }
        }
        
        return $response;
    }
    
    /**
     * Check if the response is JSON
     *
     * @param  mixed  $response
     * @return bool
     */
    protected function isJsonResponse($response)
    {
        return $response->headers->has('Content-Type') && 
               strpos($response->headers->get('Content-Type'), 'application/json') !== false;
    }
    
    /**
     * Get appropriate cache duration based on the request path
     *
     * @param  string  $path
     * @return int
     */
    protected function getCacheDuration($path)
    {
        if (strpos($path, 'api/categories') === 0) {
            return self::CACHE_DURATIONS['categories'];
        } elseif (strpos($path, 'api/products') === 0) {
            return self::CACHE_DURATIONS['products'];
        } elseif (strpos($path, 'api/user') === 0 || strpos($path, 'api/profile') === 0) {
            return self::CACHE_DURATIONS['user'];
        } elseif (strpos($path, 'api/static') === 0 || strpos($path, 'api/settings') === 0) {
            return self::CACHE_DURATIONS['static'];
        }
        
        return self::CACHE_DURATIONS['default'];
    }
}
