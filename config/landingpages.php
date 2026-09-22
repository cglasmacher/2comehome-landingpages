<?php

return [
    'public_domain' => env('LANDINGPAGE_PUBLIC_DOMAIN', 'verkauf.2comehome.de'),
    'default_range_percent' => (float) env('LANDINGPAGE_DEFAULT_RANGE_PERCENT', 7.5),
    'lead_notification_email' => env('LEAD_NOTIFICATION_EMAIL', 'c.glasmacher@2comehome.de'),
    'calendly_url' => env('CALENDLY_URL'),

    'property_types' => [
        'einfamilienhaus' => [
            'label' => 'Einfamilienhaus',
            'icon' => 'home',
            'onoffice' => ['objektart' => 'haus', 'objekttyp' => 'einfamilienhaus'],
        ],
        'doppelhaushälfte' => [
            'label' => 'Doppelhaushälfte',
            'icon' => 'home',
            'onoffice' => ['objektart' => 'haus', 'objekttyp' => 'doppelhaushaelfte'],
        ],
        'reihenhaus' => [
            'label' => 'Reihenhaus',
            'icon' => 'home',
            'onoffice' => ['objektart' => 'haus', 'objekttyp' => 'reihenhaus'],
        ],
        'wohnung' => [
            'label' => 'Wohnung',
            'icon' => 'building',
            'onoffice' => ['objektart' => 'wohnung'],
        ],
        'maisonette' => [
            'label' => 'Maisonette',
            'icon' => 'building',
            'onoffice' => ['objektart' => 'wohnung', 'objekttyp' => 'maisonette'],
        ],
        'grundstück' => [
            'label' => 'Grundstück',
            'icon' => 'land',
            'onoffice' => ['objektart' => 'grundstueck'],
        ],
    ],

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
        'estate_note_field' => env('ONOFFICE_ESTATE_NOTE_FIELD', 'InterneBemerkung'),
        'estate_description_field' => env('ONOFFICE_ESTATE_DESCRIPTION_FIELD', 'objektbeschreibung'),
        // Exact API names from onoffice:diagnose; optional, never infer write access from labels.
        'estate_valuation_fields' => [
            'estimated_value' => env('ONOFFICE_ESTATE_VALUE_FIELD'),
            'range_low' => env('ONOFFICE_ESTATE_MIN_FIELD'),
            'range_high' => env('ONOFFICE_ESTATE_MAX_FIELD'),
        ],
        'estate_user_id' => env('ONOFFICE_ESTATE_USER_ID'),
        'estate_user_initials' => env('ONOFFICE_ESTATE_USER_INITIALS', 'CG'),
        'country_codes' => ['DE' => 'DEU', 'AT' => 'AUT', 'CH' => 'CHE'],
        'estate_status2_label' => env('ONOFFICE_ESTATE_STATUS2_LABEL', 'in akquise'),
        'estate_status2_cache_ttl' => (int) env('ONOFFICE_ESTATE_STATUS2_CACHE_TTL', 86400),
    ],
];
