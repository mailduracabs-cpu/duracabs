<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file stores credentials and configuration for third-party
    | services used by the Dura Cabs application.
    |
    */

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
            'bot_user_oauth_token' => env(
                'SLACK_BOT_USER_OAUTH_TOKEN'
            ),

            'channel' => env(
                'SLACK_BOT_USER_DEFAULT_CHANNEL'
            ),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Login
    |--------------------------------------------------------------------------
    */

    'google' => [
        'client_id' => env(
            'GOOGLE_CLIENT_ID',
            '1094737562052-763eluntljjms0s8nb0taf5735me5o56.apps.googleusercontent.com'
        ),

        'client_secret' => env(
            'GOOGLE_CLIENT_SECRET',
            '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'
        ),

        'redirect' => env(
            'GOOGLE_REDIRECT_URI',
            'https://www.duracabs.com/auth/google/callback'
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Search Console
    |--------------------------------------------------------------------------
    |
    | Separate OAuth client for the admin SEO integration. This configuration
    | is intentionally independent from the existing customer Google Login.
    |
    */

    'search_console' => [
        'client_id' => env(
            'GOOGLE_SEARCH_CONSOLE_CLIENT_ID'
        ),

        'client_secret' => env(
            'GOOGLE_SEARCH_CONSOLE_CLIENT_SECRET'
        ),

        'redirect' => env(
            'GOOGLE_SEARCH_CONSOLE_REDIRECT_URI',
            'https://www.duracabs.com/auth/google/search-console/callback'
        ),

        'property' => env(
            'GOOGLE_SEARCH_CONSOLE_PROPERTY',
            'https://www.duracabs.com/'
        ),

        'scopes' => [
            'https://www.googleapis.com/auth/webmasters.readonly',
        ],

        'authorization_url' => 'https://accounts.google.com/o/oauth2/v2/auth',

        'token_url' => 'https://oauth2.googleapis.com/token',

        'api_base_url' => 'https://searchconsole.googleapis.com/webmasters/v3',

        'inspection_api_url' => 'https://searchconsole.googleapis.com/v1/urlInspection/index:inspect',

        'timeout' => (int) env(
            'GOOGLE_SEARCH_CONSOLE_TIMEOUT',
            30
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Samb SMS
    |--------------------------------------------------------------------------
    |
    | Samb SMS is still used for normal SMS and OTP delivery.
    | Only SambCart WhatsApp has been removed.
    |
    */

    'sambsms' => [
        'api_key' => env('SAMB_SMS_API_KEY'),
        'entity_id' => env('SAMB_SMS_ENTITY_ID'),
        'route_id' => env('SAMB_SMS_ROUTE_ID'),
        'sender_id' => env('SAMB_SMS_SENDER_ID'),
        'template_id' => env('SAMB_SMS_TEMPLATE_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Maps
    |--------------------------------------------------------------------------
    */

    'google_maps' => [
        'key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Meta WhatsApp Cloud API
    |--------------------------------------------------------------------------
    |
    | Official Meta WhatsApp Cloud API configuration.
    | SambCart WhatsApp API is no longer used.
    |
    */

    'whatsapp' => [

        'provider' => 'meta',

        'base_url' => env(
            'WHATSAPP_BASE_URL',
            'https://graph.facebook.com'
        ),

        'graph_version' => env(
            'WHATSAPP_GRAPH_VERSION',
            'v23.0'
        ),

        'access_token' => env(
            'WHATSAPP_ACCESS_TOKEN'
        ),

        'phone_number_id' => env(
            'WHATSAPP_PHONE_NUMBER_ID'
        ),

        'business_account_id' => env(
            'WHATSAPP_BUSINESS_ACCOUNT_ID'
        ),

        'app_id' => env(
            'WHATSAPP_APP_ID'
        ),

        'app_secret' => env(
            'WHATSAPP_APP_SECRET'
        ),

        'webhook_verify_token' => env(
            'WHATSAPP_WEBHOOK_VERIFY_TOKEN'
        ),

        /*
        |--------------------------------------------------------------------------
        | Default message settings
        |--------------------------------------------------------------------------
        */

        'default_country_code' => env(
            'WHATSAPP_DEFAULT_COUNTRY_CODE',
            '91'
        ),

        'default_language' => env(
            'WHATSAPP_DEFAULT_LANGUAGE',
            'en'
        ),

        /*
        |--------------------------------------------------------------------------
        | HTTP settings
        |--------------------------------------------------------------------------
        */

        'timeout' => (int) env(
            'WHATSAPP_TIMEOUT',
            30
        ),

        'retry_times' => (int) env(
            'WHATSAPP_RETRY_TIMES',
            2
        ),

        'retry_delay' => (int) env(
            'WHATSAPP_RETRY_DELAY',
            500
        ),

        /*
        |--------------------------------------------------------------------------
        | OTP authentication settings
        |--------------------------------------------------------------------------
        */

        'otp_copy_button' => filter_var(
            env('WHATSAPP_OTP_COPY_BUTTON', false),
            FILTER_VALIDATE_BOOL
        ),

        /*
        |--------------------------------------------------------------------------
        | Approved Meta template names
        |--------------------------------------------------------------------------
        |
        | Add the exact approved template name from Meta Business Manager.
        | Leave the value empty until the template is approved.
        |
        */

        'templates' => [

            'otp' => env(
                'WHATSAPP_TEMPLATE_OTP'
            ),

            'booking_confirmation' => env(
                'WHATSAPP_TEMPLATE_BOOKING_CONFIRMATION'
            ),

            'booking_cancellation' => env(
                'WHATSAPP_TEMPLATE_BOOKING_CANCELLATION'
            ),

            'driver_details' => env(
                'WHATSAPP_TEMPLATE_DRIVER_DETAILS'
            ),

            'car_details' => env(
                'WHATSAPP_TEMPLATE_CAR_DETAILS'
            ),

            'payment_reminder' => env(
                'WHATSAPP_TEMPLATE_PAYMENT_REMINDER'
            ),

            'payment_receipt' => env(
                'WHATSAPP_TEMPLATE_PAYMENT_RECEIPT'
            ),

            'invoice' => env(
                'WHATSAPP_TEMPLATE_INVOICE'
            ),

            'refund' => env(
                'WHATSAPP_TEMPLATE_REFUND'
            ),

            'offer' => env(
                'WHATSAPP_TEMPLATE_OFFER'
            ),

            'reminder' => env(
                'WHATSAPP_TEMPLATE_REMINDER'
            ),

            'review_request' => env(
                'WHATSAPP_TEMPLATE_REVIEW_REQUEST'
            ),

            'self_drive_pickup' => env(
                'WHATSAPP_TEMPLATE_SELF_DRIVE_PICKUP'
            ),

            'self_drive_drop' => env(
                'WHATSAPP_TEMPLATE_SELF_DRIVE_DROP'
            ),



            /*
            |--------------------------------------------------------------------------
            | CRM follow-up templates
            |--------------------------------------------------------------------------
            */

            'search_abandoned' => env(
                'WHATSAPP_TEMPLATE_SEARCH_ABANDONED'
            ),

            'checkout_abandoned' => env(
                'WHATSAPP_TEMPLATE_CHECKOUT_ABANDONED'
            ),

            'payment_failed' => env(
                'WHATSAPP_TEMPLATE_PAYMENT_FAILED'
            ),

            'hot_lead' => env(
                'WHATSAPP_TEMPLATE_HOT_LEAD'
            ),
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | Razorpay
    |--------------------------------------------------------------------------
    |
    | Razorpay credentials are loaded from the environment file.
    |
    */

    'razorpay' => [
        'key' => env('RAZORPAY_KEY_ID'),
        'secret' => env('RAZORPAY_KEY_SECRET'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
    ],


];