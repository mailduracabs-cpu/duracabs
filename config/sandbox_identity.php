<?php
return [
    'enabled' => (bool) env('SANDBOX_KYC_ENABLED', false),
    'environment' => env('SANDBOX_KYC_ENVIRONMENT', 'test'),
    'api_key' => env('SANDBOX_KYC_API_KEY', ''),
    'api_secret' => env('SANDBOX_KYC_API_SECRET', ''),
    'api_version' => '1.0.0',
    'redirect_url' => env('SANDBOX_KYC_REDIRECT_URL', ''),
    // Provider-issued download hosts only; no wildcard or client-supplied URLs.
    'document_hosts' => array_values(array_filter(array_map('trim', explode(',', (string) env(
        'SANDBOX_KYC_DOCUMENT_HOSTS', 'in-co-sandbox-kyc-digilocker-dev.s3.ap-south-1.amazonaws.com'
    ))))),
    'purpose' => 'Dura Cabs taxi partner identity verification',
    'requestor_name' => 'Dura Cabs',
];
