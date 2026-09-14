<?php

return [
    'auto_apply' => (bool) env('COMPETITOR_PRICE_AUTO_APPLY', false),
    'undercut_amount' => (float) env('COMPETITOR_PRICE_UNDERCUT', 50),
    'floor_reduction' => (float) env('COMPETITOR_PRICE_FLOOR_REDUCTION', 50),
    'timeout_seconds' => (int) env('COMPETITOR_PRICE_TIMEOUT', 15),
    'request_delay_ms' => (int) env('COMPETITOR_PRICE_REQUEST_DELAY_MS', 750),
    'user_agent' => env(
        'COMPETITOR_PRICE_USER_AGENT',
        'DuraCabs-Public-Fare-Monitor/1.0 (+https://www.duracabs.com)'
    ),
    'providers' => [
        'savaari' => [
            'enabled' => (bool) env('COMPETITOR_SAVAARI_ENABLED', true),
            'url' => 'https://www.savaari.com/%s/%s-to-%s-cabs',
        ],
        'gozo' => [
            'enabled' => (bool) env('COMPETITOR_GOZO_ENABLED', true),
            'url' => 'https://www.gozocabs.com/book-taxi/%s-%s',
        ],
        // Set an exact public route template only after verifying its HTML.
        'makemytrip' => [
            'enabled' => (bool) env('COMPETITOR_MMT_ENABLED', false),
            'url' => env('COMPETITOR_MMT_ROUTE_TEMPLATE'),
        ],
    ],
];
