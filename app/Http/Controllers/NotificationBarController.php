<?php

namespace App\Http\Controllers;

use App\Models\NotificationBar;
use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;

class NotificationBarController extends Controller
{
    /**
     * Cache duration in seconds (1 hour)
     */
    protected $cacheDuration = 3600;
    
    /**
     * Cache key for active notification bar
     */
    protected $cacheKey = 'notification_bar_active';
    
    /**
     * Cache tags for easier invalidation
     */
    protected $cacheTags = ['notification_bars', 'frontend'];

    /**
     * Get the active notification bar for the storefront.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActive()
    {
        // Check if force refresh is requested
        $forceRefresh = request()->has('forceRefresh');
        
        // If force refresh requested, clear the cache
        if ($forceRefresh) {
            CacheService::invalidateByTags($this->cacheTags);
        }
        
        // Get the active notification bar using stale-while-revalidate pattern
        $notificationBar = CacheService::staleWhileRevalidate(
            $this->cacheKey,
            $this->cacheDuration,
            function () {
                return NotificationBar::where('is_active', true)->first();
            },
            $this->cacheTags
        );
        
        // Add cache control headers for frontend caching with stale-while-revalidate
        return response()->json([
            'status' => 'success',
            'notificationBar' => $notificationBar,
            'cached' => !$forceRefresh && Cache::has($this->cacheKey),
            'cache_ttl' => $this->cacheDuration
        ])->withHeaders([
            'Cache-Control' => 'public, max-age=3600, stale-while-revalidate=86400',
            'X-Cache-Status' => !$forceRefresh && Cache::has($this->cacheKey) ? 'HIT' : 'MISS'
        ]);
    }
}
