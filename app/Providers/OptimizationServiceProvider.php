<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Builder;
use App\Services\QueryOptimizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Events\QueryExecuted;

class OptimizationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Add a global scope to optimize all queries
        Builder::macro('optimize', function ($options = []) {
            $modelType = get_class($this->getModel());
            return QueryOptimizer::optimize($this, $modelType, $options);
        });

        // Add a macro for caching queries
        Builder::macro('cached', function ($cacheKey, $queryType = 'default', callable $callback = null) {
            return QueryOptimizer::cacheQuery($this, $cacheKey, $queryType, $callback);
        });

        // Enable query caching at the database level
        if (config('database.default') === 'mysql') {
            // Removed query cache settings as they're not supported in newer MySQL versions
            // MySQL 8.0+ has removed the query cache feature
        }

        // Monitor slow queries in development
        if (app()->environment('local', 'development')) {
            Event::listen(QueryExecuted::class, function (QueryExecuted $query) {
                if ($query->time > 100) { // Log queries taking more than 100ms
                    logger()->channel('slow-query')->info('Slow query detected', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time,
                    ]);
                }
            });
        }
    }
}
