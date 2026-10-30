<?php
return [
    'radius_km' => (float) env('PARTNER_MARKET_RADIUS_KM', 30),
    'priority_seconds' => (int) env('PARTNER_MARKET_PRIORITY_SECONDS', 60),
    'location_fresh_seconds' => 300,
    'firebase_credentials' => env('PARTNER_MARKET_FIREBASE_CREDENTIALS', storage_path('app/firebase/firebase-service-account.json')),
    'whatsapp_template_key' => env('PARTNER_MARKET_WHATSAPP_TEMPLATE_KEY', 'partner_market_booking_alert'),
    'whatsapp_enabled' => (bool) env('PARTNER_MARKET_WHATSAPP_ENABLED', false),
];
