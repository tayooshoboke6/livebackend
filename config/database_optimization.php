<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Database Query Optimization Settings
    |--------------------------------------------------------------------------
    |
    | These settings control various aspects of database query optimization
    | including query caching, eager loading defaults, and index usage.
    |
    */

    // Default eager loading relationships for common models
    'eager_load' => [
        'products' => ['category', 'images', 'measurements'],
        'categories' => ['parent', 'children'],
        'orders' => ['items', 'user', 'address'],
        'users' => ['addresses'],
    ],

    // Cache durations for different query types (in seconds)
    'cache_duration' => [
        'lookup_tables' => 86400, // 24 hours for static lookup tables
        'product_listings' => 3600, // 1 hour for product listings
        'category_trees' => 7200, // 2 hours for category hierarchies
        'user_specific' => 300, // 5 minutes for user-specific data
    ],

    // Query optimization hints
    'optimization_hints' => [
        'use_index_hints' => true, // Add index hints to complex queries
        'select_specific_columns' => true, // Only select needed columns
        'chunk_large_datasets' => true, // Process large datasets in chunks
    ],
];
