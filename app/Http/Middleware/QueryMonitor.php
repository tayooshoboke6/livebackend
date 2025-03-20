<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class QueryMonitor
{
    /**
     * Threshold in milliseconds for slow queries
     */
    protected $slowQueryThreshold = 100; // 100ms

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip in production unless debugging is enabled
        if (app()->environment('production') && !config('app.debug')) {
            return $next($request);
        }

        // Store the start time
        $startTime = microtime(true);
        
        // Initialize query count and time
        $queryCount = 0;
        $queryTime = 0;
        $slowQueries = [];
        
        // Listen for queries
        DB::listen(function ($query) use (&$queryCount, &$queryTime, &$slowQueries) {
            $queryCount++;
            
            // Calculate query execution time in milliseconds
            $time = $query->time;
            $queryTime += $time;
            
            // Log slow queries
            if ($time >= $this->slowQueryThreshold) {
                $slowQueries[] = [
                    'sql' => $this->formatSql($query->sql, $query->bindings),
                    'time' => $time,
                    'connection' => $query->connection->getName(),
                ];
            }
        });
        
        // Process the request
        $response = $next($request);
        
        // Calculate total request time
        $requestTime = (microtime(true) - $startTime) * 1000;
        
        // Add performance headers
        $response->header('X-Query-Count', $queryCount);
        $response->header('X-Query-Time', round($queryTime, 2) . 'ms');
        $response->header('X-Request-Time', round($requestTime, 2) . 'ms');
        
        // Log slow requests (taking more than 500ms)
        if ($requestTime > 500 || !empty($slowQueries)) {
            $this->logSlowRequest($request, $queryCount, $queryTime, $requestTime, $slowQueries);
            
            // Cache the slow endpoint for optimization focus
            $this->cacheSlowEndpoint($request->path(), $queryCount, $requestTime);
        }
        
        return $response;
    }
    
    /**
     * Format SQL query with bindings for better readability
     *
     * @param string $sql
     * @param array $bindings
     * @return string
     */
    protected function formatSql($sql, $bindings)
    {
        $sql = str_replace(['%', '?'], ['%%', '%s'], $sql);
        
        foreach ($bindings as $key => $binding) {
            if (is_string($binding)) {
                $bindings[$key] = "'" . addslashes($binding) . "'";
            } elseif (is_bool($binding)) {
                $bindings[$key] = $binding ? '1' : '0';
            } elseif (is_null($binding)) {
                $bindings[$key] = 'NULL';
            }
        }
        
        return vsprintf($sql, $bindings);
    }
    
    /**
     * Log slow request details
     *
     * @param Request $request
     * @param int $queryCount
     * @param float $queryTime
     * @param float $requestTime
     * @param array $slowQueries
     * @return void
     */
    protected function logSlowRequest($request, $queryCount, $queryTime, $requestTime, $slowQueries)
    {
        Log::channel('query')->warning('Slow API request detected', [
            'endpoint' => $request->method() . ' ' . $request->path(),
            'params' => $request->all(),
            'query_count' => $queryCount,
            'query_time' => round($queryTime, 2) . 'ms',
            'request_time' => round($requestTime, 2) . 'ms',
            'slow_queries' => $slowQueries,
        ]);
    }
    
    /**
     * Cache slow endpoints for optimization focus
     *
     * @param string $path
     * @param int $queryCount
     * @param float $requestTime
     * @return void
     */
    protected function cacheSlowEndpoint($path, $queryCount, $requestTime)
    {
        $key = 'slow_endpoints';
        $slowEndpoints = Cache::get($key, []);
        
        // Add or update the endpoint stats
        if (isset($slowEndpoints[$path])) {
            $slowEndpoints[$path]['count']++;
            $slowEndpoints[$path]['total_time'] += $requestTime;
            $slowEndpoints[$path]['avg_time'] = $slowEndpoints[$path]['total_time'] / $slowEndpoints[$path]['count'];
            $slowEndpoints[$path]['query_count'] = max($slowEndpoints[$path]['query_count'], $queryCount);
        } else {
            $slowEndpoints[$path] = [
                'count' => 1,
                'total_time' => $requestTime,
                'avg_time' => $requestTime,
                'query_count' => $queryCount,
                'first_seen' => now()->toDateTimeString(),
            ];
        }
        
        // Keep only the top 50 slowest endpoints
        uasort($slowEndpoints, function ($a, $b) {
            return $b['avg_time'] <=> $a['avg_time'];
        });
        
        $slowEndpoints = array_slice($slowEndpoints, 0, 50, true);
        
        // Cache for 24 hours
        Cache::put($key, $slowEndpoints, 60 * 24);
    }
}
