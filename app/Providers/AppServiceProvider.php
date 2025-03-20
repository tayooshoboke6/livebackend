<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Cache\Repository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Add a fallback method for cache tags when using drivers that don't support tagging
        if (!method_exists(Cache::store(), 'tags')) {
            Repository::macro('tags', function ($names) {
                return $this;
            });
        }
    }
}
