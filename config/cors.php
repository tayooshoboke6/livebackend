<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    */

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie'
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:3000')
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'X-XSRF-TOKEN',
        'X-Requested-With',
        'Content-Type',
        'Accept',
        'Authorization',
        'Origin',
        'X-Custom-Header',
        'X-Cache-Control',
        'Cache-Control'
    ],

    'exposed_headers' => ['Authorization'],

    'max_age' => 7200,

    'supports_credentials' => true,
];
