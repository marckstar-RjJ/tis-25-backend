<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout', 'registro'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [env('CORS_ALLOWED_ORIGINS', 'https://tis-25-frontend.netlify.app')],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
