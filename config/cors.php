<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Flutter Web (Chrome) enforces the browser same-origin policy, so every
    | /api/* request sends an OPTIONS preflight first. Without this config +
    | the HandleCors global middleware, login and all API calls fail with
    | "Access-Control-Allow-Origin missing". Native Android/iOS ignore CORS,
    | which is why only Chrome was broken.
    |
    | Bearer-token API (Sanctum personal tokens) needs no cookies, so a
    | wildcard origin is safe for local dev. For production, set
    | FRONTEND_URL in .env to your web origin(s), comma-separated.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(array_map(
        'trim',
        explode(',', env('FRONTEND_URL', ''))
    )) ?: ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Sanctum personal-access (Bearer) tokens: no cookies, so no
    // credentials needed. Keep false while allowed_origins is '*'
    // (browsers reject '*' + credentials combos).
    'supports_credentials' => false,

];
