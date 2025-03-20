<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cloudinary Configuration
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials and configuration for the Cloudinary
    | PHP SDK. The SDK is used for uploading, transforming, and delivering media
    | assets from the Cloudinary service.
    |
    */

    'cloud_name' => env('CLOUDINARY_CLOUD_NAME', ''),
    'api_key' => env('CLOUDINARY_API_KEY', ''),
    'api_secret' => env('CLOUDINARY_SECRET_KEY', ''),
    'secure' => env('CLOUDINARY_SECURE', true),
    'url' => [
        'secure' => env('CLOUDINARY_SECURE', true)
    ],
    'scaling' => [
        'format' => 'auto',
        'quality' => 'auto'
    ],
];
