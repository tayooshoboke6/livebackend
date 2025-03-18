<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout', 'register', 'admin/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => ['http://localhost:3000'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'X-XSRF-TOKEN',
        'X-Requested-With',
        'Content-Type',
        'Accept',
        'Authorization',
        'Origin',
        'X-Custom-Header'
    ],

    'exposed_headers' => ['Authorization'],

    'max_age' => 7200,

    'supports_credentials' => true,

    'access_control_allow_credentials' => true,
];
