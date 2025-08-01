<?php

return [
    'paths' => ['*', 'api/*', 'sanctum/csrf-cookie', 'login', 'logout', 'register', 'registro'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_origins' => [
        'https://olimpiadas-sansi.netlify.app',
        'http://localhost:5173',
        'http://localhost:5175',
        'http://localhost:5174',
        'http://127.0.0.1:44443',
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        'http://localhost:8000',
        'http://127.0.0.1:8000'
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
