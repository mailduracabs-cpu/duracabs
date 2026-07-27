<?php

return [
    'cookie_name' => env('COOKIE_CONSENT_NAME', 'duracabs_cookie_consent'),
    'storage_key' => env('COOKIE_CONSENT_STORAGE_KEY', 'duracabs_cookie_consent'),
    'version' => (string) env('COOKIE_CONSENT_VERSION', '2'),
    'lifetime_days' => (int) env('COOKIE_CONSENT_DAYS', 365),
    'path' => env('COOKIE_CONSENT_PATH', '/'),
    'domain' => env('COOKIE_CONSENT_DOMAIN'),
    'secure' => filter_var(
        env('COOKIE_CONSENT_SECURE', env('APP_ENV') === 'production'),
        FILTER_VALIDATE_BOOL
    ),
    'same_site' => env('COOKIE_CONSENT_SAME_SITE', 'Lax'),

    'categories' => [
        'necessary' => true,
        'preferences' => false,
        'analytics' => false,
        'marketing' => false,
    ],
];
