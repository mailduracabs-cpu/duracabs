<?php

return [
    'timezone' => 'Asia/Kolkata',
    'enabled' => env('VENDOR_LIFECYCLE_ENABLED', false),
    'email_enabled' => env('VENDOR_LIFECYCLE_EMAIL_ENABLED', false),
    'whatsapp_enabled' => env('VENDOR_LIFECYCLE_WHATSAPP_ENABLED', false),
    'agreement_reminder_days' => [30, 0],
    'document_reminder_days' => [30, 15, 7, 1, 0],
    'reminder_since' => env('VENDOR_LIFECYCLE_REMINDER_SINCE'),
    'reminder_template_key' => 'vendor_vehicle_expiry',
    'bill_template_key' => 'vendor_monthly_bill',
    // No automatic bills until admin has explicitly chosen and verified these rules.
    'billing_enabled' => env('VENDOR_MONTHLY_BILLING_ENABLED', false),
    'billing_basis' => env('VENDOR_MONTHLY_BILLING_BASIS'), // joining_date or fixed_day
    'billing_day' => (int) env('VENDOR_MONTHLY_BILLING_DAY', 15),
    'partial_period' => env('VENDOR_MONTHLY_PARTIAL_PERIOD'), // prorate or full
    'billing_since' => env('VENDOR_MONTHLY_BILLING_SINCE'),
];
