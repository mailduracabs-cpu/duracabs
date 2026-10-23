<?php

return [
    // Enable only after the stated build is available to customers in Play Store.
    'android' => [
        'enabled' => (bool) env('CUSTOMER_APP_UPDATE_ENABLED', false),
        'package_name' => 'com.duracabs.app',
        'latest_version' => env('CUSTOMER_APP_LATEST_VERSION', '1.0.0'),
        'latest_build' => (int) env('CUSTOMER_APP_LATEST_BUILD', 2),
        // 0 keeps updates optional. Only raise after a fully available release.
        'minimum_build' => (int) env('CUSTOMER_APP_MINIMUM_BUILD', 0),
        'store_url' => 'https://play.google.com/store/apps/details?id=com.duracabs.app',
        'message' => env('CUSTOMER_APP_UPDATE_MESSAGE', 'Update DuraCabs for the latest features, improvements and fixes.'),
    ],
];
