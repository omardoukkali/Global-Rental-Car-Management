<?php

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // Comma-separated list, e.g. "http://localhost:3000,https://app.example.com"
    // 3000 = frontend in Docker, 3333 = Vite dev server on the host
    'allowed_origins' => explode(',', env(
        'CORS_ALLOWED_ORIGINS',
        'http://localhost:3000,http://localhost:3333'
    )),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // The API is stateless (bearer tokens), so no cookies are shared
    'supports_credentials' => false,

];
