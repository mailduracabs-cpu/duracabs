<?php

return [

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => '1094737562052-763eluntljjms0s8nb0taf5735me5o56.apps.googleusercontent.com',
        'client_secret' => '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',
        'redirect' => 'https://www.duracabs.com/auth/google/callback',
    ],

    'sambsms' => [
        'api_key'     => env('SAMB_SMS_API_KEY'),
        'entity_id'   => env('SAMB_SMS_ENTITY_ID'),
        'route_id'    => env('SAMB_SMS_ROUTE_ID'),
        'sender_id'   => env('SAMB_SMS_SENDER_ID'),
        'template_id' => env('SAMB_SMS_TEMPLATE_ID'),
    ],

];
