<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Public site data cache
    |--------------------------------------------------------------------------
    |
    | Only shared, non-user-specific data belongs in this cache. Session,
    | checkout, authentication, OTP and booking data must never be cached here.
    |
    */
    'settings_ttl_minutes' => (int) env('SITE_SETTINGS_CACHE_TTL', 60),
];
