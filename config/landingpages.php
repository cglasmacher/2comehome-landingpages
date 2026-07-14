<?php

return [
    'public_domain' => env('LANDINGPAGE_PUBLIC_DOMAIN', 'verkauf.2comehome.de'),
    'default_range_percent' => (float) env('LANDINGPAGE_DEFAULT_RANGE_PERCENT', 7.5),

    'pricehubble' => [
        'base_url' => env('PRICEHUBBLE_BASE_URL'),
        'api_key' => env('PRICEHUBBLE_API_KEY'),
        'timeout' => (int) env('PRICEHUBBLE_TIMEOUT', 15),
    ],

    'onoffice' => [
        'base_url' => env('ONOFFICE_BASE_URL'),
        'token' => env('ONOFFICE_TOKEN'),
        'secret' => env('ONOFFICE_SECRET'),
        'timeout' => (int) env('ONOFFICE_TIMEOUT', 20),
    ],
];
