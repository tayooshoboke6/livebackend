<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class QueryOptimizer
{
    /**
     * Apply optimizations to a query builder
     *
     * @param Builder $query
     * @param string $modelType
     * @param array $options
     * @return Builder
     */
    public static function optimize(Builder $query, string $modelType, array $options = [])
    {
        $modelType = Str::snake(class_basename($modelType));
        
        // Apply eager loading based on model type
        static::applyEagerLoading($query, $modelType);
        
        // Apply select specific columns if needed
        if (isset($options['select']) && $options['select']) {
            $query->select($options['select']);
        } elseif (Config::get('database_optimization.optimization_hints.select_specific_columns', true)) {
            // Apply default column selection strategy
            static::applyColumnSelection($query, $modelType);
        }
        
        // Apply index hints for complex queries
        if (Config::get('database_optimization.optimization_hints.use_index_hints', true)) {
            static::applyIndexHints($query, $modelType, $options);
        }
        
        return $query;
    }
    
    /**
     * Cache query results
     *
     * @param Builder $query
     * @param string $cacheKey
     * @param string $queryType
     * @param callable|null $callback
     * @return mixed
     */
    public static function cacheQuery(Builder $query, string $cacheKey, string $queryType, callable $callback = null)
    {
        $duration = Config::get("database_optimization.cache_duration.{$queryType}", 300);
        
        return Cache::remember($cacheKey, $duration, function () use ($query, $callback) {
            $results = $query->get();
            
            if ($callback) {
                return $callback($results);
            }
            
            return $results;
        });
    }
    
    /**
     * Apply eager loading based on model type
     *
     * @param Builder $query
     * @param string $modelType
     * @return void
     */
    private static function applyEagerLoading(Builder $query, string $modelType)
    {
        $eagerLoad = Config::get("database_optimization.eager_load.{$modelType}", []);
        
        if (!empty($eagerLoad)) {
            $query->with($eagerLoad);
        }
    }
    
    /**
     * Apply column selection strategy
     *
     * @param Builder $query
     * @param string $modelType
     * @return void
     */
    private static function applyColumnSelection(Builder $query, string $modelType)
    {
        // Model-specific column selection strategies
        $columnSelections = [
            'products' => ['id', 'name', 'slug', 'base_price', 'sale_price', 'category_id', 'is_active', 'created_at'],
            'categories' => ['id', 'name', 'slug', 'parent_id', 'is_active'],
            'users' => ['id', 'name', 'email', 'created_at'],
            'orders' => ['id', 'user_id', 'status', 'total', 'created_at'],
        ];
        
        if (isset($columnSelections[$modelType])) {
            $query->select($columnSelections[$modelType]);
        }
    }
    
    /**
     * Apply index hints for complex queries
     *
     * @param Builder $query
     * @param string $modelType
     * @param array $options
     * @return void
     */
    private static function applyIndexHints(Builder $query, string $modelType, array $options)
    {
        // Only apply for specific model types and complex queries
        if (!in_array($modelType, ['products', 'orders', 'users'])) {
            return;
        }
        
        // Check if this is a complex query that would benefit from index hints
        $hasComplexConditions = $query->getQuery()->wheres && count($query->getQuery()->wheres) > 2;
        $hasJoins = $query->getQuery()->joins && count($query->getQuery()->joins) > 0;
        
        if ($hasComplexConditions || $hasJoins) {
            // This is a MySQL-specific optimization
            $table = $query->getModel()->getTable();
            
            // Common index hints for different models
            $indexHints = [
                'products' => 'USE INDEX (products_category_id_index)',
                'orders' => 'USE INDEX (orders_user_id_index)',
                'users' => 'USE INDEX (users_email_index)',
            ];
            
            if (isset($indexHints[$modelType])) {
                // Apply the raw index hint
                $query->from(\DB::raw("`{$table}` {$indexHints[$modelType]}"));
            }
        }
    }
}
