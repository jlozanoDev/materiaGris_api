<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | For local development we allow all origins and methods. Adjust for
    | production to restrict origins and enable credentials as needed.
    |
    */

    'paths' => ['*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    // Allowed origins can be configured via environment variable as a
    // comma-separated list. Example in .env:
    // CORS_ALLOWED_ORIGINS=http://localhost:5173,https://app.materiagris.com
    // If not provided or empty, falls back to the Vite dev server origin for local dev.
    // If set to "*" the wildcard origin will be used.
    'allowed_origins' => (function () {
        $env = trim((string) env('CORS_ALLOWED_ORIGINS', ''));
        if ($env === '' ) {
            // URL por defecto para desarrollo local
            return [
                'http://localhost:5173', // Vite dev server (frontend local con Node)
            ];
        }
        if ($env === '*') {
            return ['*'];
        }
        return array_filter(array_map('trim', explode(',', $env)));
    })(),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'Authorization'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => (function () {
        $env = trim((string) env('CORS_ALLOWED_ORIGINS', ''));
        return $env !== '*';
    })(),
];
