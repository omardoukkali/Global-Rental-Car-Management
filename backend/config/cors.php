<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Auth is Bearer-token based (Sanctum Personal Access Tokens), so cookies
    | are not involved and supports_credentials is false. In production the
    | Vue SPA is served by the same nginx instance that proxies /api/*, so
    | there is no cross-origin request at all. This config exists primarily
    | for local development where Vite runs on :5173 and Laravel on :8000.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        // Vite dev server (local development)
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        // Docker-mapped port (when running against the containerised API directly)
        'http://localhost:8888',
        'http://127.0.0.1:8888',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // False: token auth, no session cookies cross-origin.
    'supports_credentials' => false,

];
