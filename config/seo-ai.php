<?php

declare(strict_types=1);

use App\SEO\AI\GeminiProvider;
use App\SEO\AI\OpenAiProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    |
    | Supported values:
    | - gemini
    | - openai
    |
    */

    'default' => env('SEO_AI_PROVIDER', 'gemini'),

    /*
    |--------------------------------------------------------------------------
    | Fallback Providers
    |--------------------------------------------------------------------------
    |
    | Default provider fail hone par future me fallback providers try kiye
    | ja sakte hain. Filhaal empty rakha gaya hai.
    |
    */

    'fallback_providers' => [],

    /*
    |--------------------------------------------------------------------------
    | AI Providers
    |--------------------------------------------------------------------------
    */

    'providers' => [

        /*
        |--------------------------------------------------------------------------
        | Google Gemini
        |--------------------------------------------------------------------------
        */

        'gemini' => [
            'driver' => GeminiProvider::class,

            'api_key' => env('GEMINI_API_KEY'),

            'model' => env(
                'GEMINI_MODEL',
                'gemini-2.5-flash',
            ),

            'endpoint' => env(
                'GEMINI_ENDPOINT',
                'https://generativelanguage.googleapis.com/v1beta/models',
            ),

            'max_output_tokens' => (int) env(
                'GEMINI_MAX_OUTPUT_TOKENS',
                4000,
            ),

            'temperature' => env(
                'GEMINI_TEMPERATURE',
            ),

            'timeout' => (int) env(
                'GEMINI_TIMEOUT',
                120,
            ),

            'connect_timeout' => (int) env(
                'GEMINI_CONNECT_TIMEOUT',
                15,
            ),
        ],

        /*
        |--------------------------------------------------------------------------
        | OpenAI
        |--------------------------------------------------------------------------
        */

        'openai' => [
            'driver' => OpenAiProvider::class,

            'api_key' => env('OPENAI_API_KEY'),

            'model' => env(
                'OPENAI_MODEL',
                'gpt-5-mini',
            ),

            'endpoint' => env(
                'OPENAI_ENDPOINT',
                'https://api.openai.com/v1/responses',
            ),

            'max_output_tokens' => (int) env(
                'OPENAI_MAX_OUTPUT_TOKENS',
                4000,
            ),

            'temperature' => env(
                'OPENAI_TEMPERATURE',
            ),

            'timeout' => (int) env(
                'OPENAI_TIMEOUT',
                120,
            ),

            'connect_timeout' => (int) env(
                'OPENAI_CONNECT_TIMEOUT',
                15,
            ),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SEO Writer Defaults
    |--------------------------------------------------------------------------
    */

    'writer' => [
        'language' => env(
            'SEO_AI_LANGUAGE',
            'English',
        ),

        'tone' => env(
            'SEO_AI_TONE',
            'Professional',
        ),

        'word_count' => (int) env(
            'SEO_AI_WORD_COUNT',
            1000,
        ),

        'country' => env(
            'SEO_AI_COUNTRY',
            'India',
        ),

        'brand_name' => env(
            'SEO_AI_BRAND_NAME',
            'Dura Cabs',
        ),

        'max_faqs' => (int) env(
            'SEO_AI_MAX_FAQS',
            5,
        ),
    ],

];