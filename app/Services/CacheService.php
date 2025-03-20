<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Closure;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class CacheService
{
    /**
     * Default cache TTL in seconds
     */
    const DEFAULT_TTL = 3600; // 1 hour
    
    /**
     * Cache TTL for frequently changing data
     */
    const SHORT_TTL = 300; // 5 minutes
    
    /**
     * Cache TTL for semi-static data
     */
    const MEDIUM_TTL = 1800; // 30 minutes
    
    /**
     * Cache TTL for mostly static data
     */
    const LONG_TTL = 86400; // 24 hours
    
    /**
     * Default percentage of TTL remaining before background refresh
     */
    const REFRESH_THRESHOLD_PERCENT = 10;
    
    /**
     * Implements a stale-while-revalidate caching pattern
     * 
     * This pattern returns stale data immediately while refreshing the cache in the background,
     * which prevents users from experiencing delays while waiting for fresh data.
     * 
     * @param string $key The cache key
     * @param int $ttl Time to live in seconds
     * @param Closure $callback Function to generate the data if not cached
     * @param array $tags Optional cache tags for easier invalidation
     * @param int $refreshThresholdPercent Percentage of TTL remaining to trigger background refresh
     * @return mixed The cached or freshly generated data
     */
    public static function staleWhileRevalidate(
        string $key, 
        int $ttl = self::DEFAULT_TTL, 
        Closure $callback, 
        array $tags = [],
        int $refreshThresholdPercent = self::REFRESH_THRESHOLD_PERCENT
    ) {
        // Check if we have the data in cache
        $hasCache = Cache::has($key);
        
        // Get cached data or null if not exists
        $cachedData = $hasCache ? Cache::get($key) : null;
        
        // If we have cached data and it's not expired, just return it
        if ($hasCache) {
            // Get the cache expiration time
            $expiresAt = null;
            
            try {
                // Different cache drivers have different methods to get TTL
                if (method_exists(Cache::store(), 'getTimeTTL')) {
                    $expiresAt = Cache::store()->getTimeTTL($key);
                } else if (method_exists(Cache::store(), 'getTimeToLive')) {
                    $expiresAt = Cache::getTimeToLive($key);
                }
            } catch (\Exception $e) {
                Log::warning("Could not determine cache TTL: " . $e->getMessage());
            }
            
            // If the cache is about to expire (less than threshold% of TTL remaining)
            // or has expired but we still have the data, refresh in background
            if ($expiresAt !== null && $expiresAt < ($ttl * $refreshThresholdPercent / 100)) {
                // Dispatch a job to refresh the cache in the background
                self::refreshCacheInBackground($key, $ttl, $callback, $tags);
            }
            
            // Return the stale data immediately
            return $cachedData;
        }
        
        // If we don't have cached data, generate it and cache it
        $freshData = $callback();
        
        // Store in cache with tags if provided and supported
        try {
            if (!empty($tags) && method_exists(Cache::store(), 'tags')) {
                Cache::tags($tags)->put($key, $freshData, $ttl);
            } else {
                Cache::put($key, $freshData, $ttl);
            }
        } catch (\Exception $e) {
            // Fallback to regular cache if tagging fails
            Cache::put($key, $freshData, $ttl);
            Log::warning("Cache tagging failed, using regular cache: " . $e->getMessage());
        }
        
        return $freshData;
    }
    
    /**
     * Refresh the cache in the background without blocking the user request
     * 
     * @param string $key The cache key
     * @param int $ttl Time to live in seconds
     * @param Closure $callback Function to generate the data
     * @param array $tags Optional cache tags for easier invalidation
     * @return void
     */
    public static function refreshCacheInBackground(string $key, int $ttl, Closure $callback, array $tags = [])
    {
        // Acquire a lock to prevent multiple simultaneous refreshes
        $lockKey = "lock:refresh:{$key}";
        if (!self::acquireLock($lockKey, 60)) {
            return; // Another process is already refreshing
        }
        
        try {
            // Generate fresh data
            $freshData = $callback();
            
            // Store in cache with tags if provided and supported
            try {
                if (!empty($tags) && method_exists(Cache::store(), 'tags')) {
                    Cache::tags($tags)->put($key, $freshData, $ttl);
                } else {
                    Cache::put($key, $freshData, $ttl);
                }
            } catch (\Exception $e) {
                // Fallback to regular cache if tagging fails
                Cache::put($key, $freshData, $ttl);
                Log::warning("Cache tagging failed in background refresh, using regular cache: " . $e->getMessage());
            }
        } finally {
            // Always release the lock
            self::releaseLock($lockKey);
        }
    }
    
    /**
     * Invalidate cache by tags
     * 
     * @param array $tags Tags to invalidate
     * @return void
     */
    public static function invalidateByTags(array $tags)
    {
        try {
            if (method_exists(Cache::store(), 'tags')) {
                Cache::tags($tags)->flush();
            } else {
                // Log that tags are not supported but don't fail
                Log::warning("Cache tagging not supported by current driver. Cannot invalidate by tags: " . implode(', ', $tags));
            }
        } catch (\Exception $e) {
            Log::error("Error invalidating cache by tags: " . $e->getMessage());
        }
    }
    
    /**
     * Acquire a distributed lock using Redis
     * 
     * @param string $key Lock key
     * @param int $ttl Lock TTL in seconds
     * @param int $retries Number of retries
     * @param int $sleep Microseconds to sleep between retries
     * @return bool True if lock was acquired
     */
    private static function acquireLock(string $key, int $ttl = 60, int $retries = 3, int $sleep = 200000)
    {
        $token = Str::random(40);
        
        while ($retries--) {
            // Try to acquire the lock with NX option (only set if not exists)
            $acquired = Redis::set($key, $token, 'EX', $ttl, 'NX');
            
            if ($acquired) {
                // Store the token in memory for release
                app()->instance("lock_token:{$key}", $token);
                return true;
            }
            
            if ($retries > 0) {
                usleep($sleep);
            }
        }
        
        return false;
    }
    
    /**
     * Release a distributed lock
     * 
     * @param string $key Lock key
     * @return bool True if lock was released
     */
    private static function releaseLock(string $key)
    {
        // Get the token used to acquire the lock
        $token = app()->bound("lock_token:{$key}") ? app("lock_token:{$key}") : null;
        
        if (!$token) {
            return false;
        }
        
        // Use Lua script to ensure we only delete our own lock
        $script = <<<'LUA'
        if redis.call('get', KEYS[1]) == ARGV[1] then
            return redis.call('del', KEYS[1])
        else
            return 0
        end
        LUA;
        
        $result = Redis::eval($script, 1, $key, $token);
        
        // Clean up the token from the container
        if (app()->bound("lock_token:{$key}")) {
            app()->forgetInstance("lock_token:{$key}");
        }
        
        return $result === 1;
    }
    
    /**
     * Invalidate cache by key
     * 
     * @param string $key Cache key to invalidate
     * @return void
     */
    public static function invalidateByKey(string $key)
    {
        try {
            Cache::forget($key);
            Log::info("Cache invalidated for key: {$key}");
        } catch (\Exception $e) {
            Log::error("Error invalidating cache for key: {$key} - " . $e->getMessage());
        }
    }
    
    /**
     * Invalidate cache by key pattern (using Redis)
     * 
     * @param string $pattern Key pattern to invalidate (e.g. "users:*")
     * @return int Number of keys invalidated
     */
    public static function invalidateByPattern(string $pattern)
    {
        try {
            $keys = Redis::keys($pattern);
            $count = 0;
            
            foreach ($keys as $key) {
                // Extract the actual key name from Redis key format
                $key = preg_replace('/^' . config('cache.prefix') . ':/', '', $key);
                Cache::forget($key);
                $count++;
            }
            
            Log::info("Cache invalidated for pattern: {$pattern}, {$count} keys affected");
            return $count;
        } catch (\Exception $e) {
            Log::error("Error invalidating cache for pattern: {$pattern} - " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get data with cache or generate it if not cached
     * 
     * Standard caching method without stale-while-revalidate
     * 
     * @param string $key The cache key
     * @param int $ttl Time to live in seconds
     * @param Closure $callback Function to generate the data if not cached
     * @param array $tags Optional cache tags for easier invalidation
     * @return mixed The cached or freshly generated data
     */
    public static function remember(string $key, int $ttl, Closure $callback, array $tags = [])
    {
        if (!empty($tags)) {
            return Cache::tags($tags)->remember($key, $ttl, $callback);
        }
        
        return Cache::remember($key, $ttl, $callback);
    }
    
    /**
     * Generate cache key with prefix
     * 
     * @param string $base Base key name
     * @param array $params Optional parameters to include in the key
     * @return string Generated cache key
     */
    public static function generateKey(string $base, array $params = [])
    {
        if (empty($params)) {
            return $base;
        }
        
        // Sort params by key to ensure consistent cache keys
        ksort($params);
        
        // Create a hash of the parameters
        $paramsHash = md5(json_encode($params));
        
        return "{$base}:{$paramsHash}";
    }
    
    /**
     * Set appropriate cache headers for HTTP response
     * 
     * @param \Illuminate\Http\Response $response The response object
     * @param int $maxAge Max age in seconds for the Cache-Control header
     * @param int $staleWhileRevalidate Time in seconds for stale-while-revalidate directive
     * @return \Illuminate\Http\Response The response with cache headers
     */
    public static function setCacheHeaders($response, int $maxAge = self::DEFAULT_TTL, int $staleWhileRevalidate = 86400)
    {
        return $response->header('Cache-Control', "public, max-age={$maxAge}, stale-while-revalidate={$staleWhileRevalidate}")
                        ->header('Vary', 'Accept-Encoding');
    }
}
