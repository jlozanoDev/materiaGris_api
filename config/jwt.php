<?php

return [
    'secret' => env('JWT_SECRET'),
    'algo' => env('JWT_ALGO', 'HS256'),
    'access_ttl' => env('JWT_TTL', 15), // minutes
    'refresh_ttl' => env('JWT_REFRESH_TTL', 14), // days
    'cookie_name' => env('JWT_REFRESH_COOKIE', 'refresh_token'),
    'cookie_domain' => env('JWT_COOKIE_DOMAIN', 'materiagris.local'),
];
