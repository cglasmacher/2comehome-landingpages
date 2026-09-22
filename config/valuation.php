<?php

return [
    // Reihenfolge direkt hier ändern. Der erste gültige Treffer gewinnt.
    // Nur Formel: ['formula']; nur Somantic: ['somantic'].
    'order' => ['somantic', 'pricehubble', 'formula'],

    'providers' => [
        'somantic' => [
            'enabled' => env('SOMANTIC_ENABLED', true),
            'base_url' => env('SOMANTIC_BASE_URL', 'https://www.somantic.net/api'),
            'api_key' => env('SOMANTIC_API_KEY'),
            'timeout' => (int) env('SOMANTIC_TIMEOUT', 10),
        ],
        // Bestehender direkter API-Adapter; kein Abruf des internen onOffice-Services.
        'pricehubble' => [
            'enabled' => env('PRICEHUBBLE_ENABLED', true),
            'base_url' => env('PRICEHUBBLE_BASE_URL'),
            'api_key' => env('PRICEHUBBLE_API_KEY'),
            'timeout' => (int) env('PRICEHUBBLE_TIMEOUT', 10),
        ],
        'formula' => [
            'enabled' => env('VALUATION_FORMULA_ENABLED', true),
        ],
    ],

    // Somantic/Formel nicht als PriceHubble-Ergebnis in MPPricehubble*-Felder schreiben.
    // Diese Werte stehen unabhängig davon in der Objektbeschreibung.
    'onoffice_price_fields_providers' => ['pricehubble'],
];
