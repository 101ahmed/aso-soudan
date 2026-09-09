<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_unique([
        env('FRONTEND_URL'),
        env('APP_URL'),
        'https://asosoudan.fr',
        'https://www.asosoudan.fr',
        'https://aso-soudan.onrender.com',
        env('APP_ENV') === 'production' ? null : 'http://localhost:5173',
        env('APP_ENV') === 'production' ? null : 'http://127.0.0.1:5173',
        env('APP_ENV') === 'production' ? null : 'http://127.0.0.1:5175',
    ]))),

    'allowed_origins_patterns' => env('APP_ENV') === 'production'
        ? []
        : [
            '#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#',
        ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
