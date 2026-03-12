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
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_values(array_filter(array_map('trim', explode(',', (string) env(
        'CORS_ALLOWED_ORIGINS',
        implode(',', array_filter([
            env('FRONTEND_URL'),
            'http://localhost:5173',
            'http://localhost:5174',
            'http://localhost:5175',
            'http://127.0.0.1:5173',
            'http://127.0.0.1:5174',
            'http://127.0.0.1:5175',
        ]))
    ))))),

    'allowed_origins_patterns' => ['#^http://(localhost|127\.0\.0\.1)(:\d+)?$#'],

    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'Origin', 'X-Requested-With', 'X-XSRF-TOKEN', 'X-CSRF-TOKEN', 'X-Request-Id', 'X-Tenant-Slug'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
