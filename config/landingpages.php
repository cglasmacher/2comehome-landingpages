<?php

return [
    'public_domain' => env('LANDINGPAGE_PUBLIC_DOMAIN', 'verkauf.2comehome.de'),
    'default_range_percent' => (float) env('LANDINGPAGE_DEFAULT_RANGE_PERCENT', 7.5),
    'calendly_url' => env('CALENDLY_URL'),

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
        'debug' => env('ONOFFICE_DEBUG', true),
        'estate_note_field' => env('ONOFFICE_ESTATE_NOTE_FIELD', 'interne_Bemerkung'),
        'estate_user_id' => env('ONOFFICE_ESTATE_USER_ID'),
        'estate_user_initials' => env('ONOFFICE_ESTATE_USER_INITIALS', 'CG'),
        'country_codes' => ['DE' => 'DEU', 'AT' => 'AUT', 'CH' => 'CHE'],
        'property_types' => [
            'einfamilienhaus' => ['objektart' => 'haus', 'objekttyp' => 'einfamilienhaus'],
            'doppelhaushälfte' => ['objektart' => 'haus', 'objekttyp' => 'doppelhaushaelfte'],
            'reihenhaus' => ['objektart' => 'haus', 'objekttyp' => 'reihenhaus'],
            'wohnung' => ['objektart' => 'wohnung'],
            'maisonette' => ['objektart' => 'wohnung', 'objekttyp' => 'maisonette'],
            'grundstück' => ['objektart' => 'grundstueck'],
        ],
        'estate_status2_label' => env('ONOFFICE_ESTATE_STATUS2_LABEL', 'in akquise'),
        'estate_status2_cache_ttl' => (int) env('ONOFFICE_ESTATE_STATUS2_CACHE_TTL', 86400),
    ],
];
