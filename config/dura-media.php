<?php

use App\Enums\MediaType;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Storage
    |--------------------------------------------------------------------------
    */

    'default_disk' => env(
        'DURA_MEDIA_DISK',
        'public'
    ),

    'private_disk' => env(
        'DURA_MEDIA_PRIVATE_DISK',
        'local'
    ),

    'base_directory' => env(
        'DURA_MEDIA_DIRECTORY',
        'app-media'
    ),

    /*
    |--------------------------------------------------------------------------
    | Upload Limits
    |--------------------------------------------------------------------------
    |
    | Values are in kilobytes.
    |
    */

    'max_image_size_kb' => 25 * 1024,

    'max_document_size_kb' => 50 * 1024,

    /*
    |--------------------------------------------------------------------------
    | Accepted MIME Types
    |--------------------------------------------------------------------------
    */

    'image_mime_types' => [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp',
        'image/gif',
    ],

    'document_mime_types' => [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp',
        'image/gif',
        'application/pdf',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cleanup
    |--------------------------------------------------------------------------
    */

    'cleanup' => [
        'unused_after_days' => (int) env(
            'DURA_MEDIA_UNUSED_DAYS',
            30
        ),

        'purge_trashed_after_days' => (int) env(
            'DURA_MEDIA_TRASHED_DAYS',
            7
        ),

        'delete_missing_records' => (bool) env(
            'DURA_MEDIA_DELETE_MISSING',
            false
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    */

    'queue' => [
        'enabled' => (bool) env(
            'DURA_MEDIA_QUEUE_ENABLED',
            false
        ),

        'connection' => env(
            'DURA_MEDIA_QUEUE_CONNECTION',
            null
        ),

        'name' => env(
            'DURA_MEDIA_QUEUE',
            'media'
        ),

        'tries' => 3,

        'timeout' => 300,
    ],

    /*
    |--------------------------------------------------------------------------
    | Variant Profiles
    |--------------------------------------------------------------------------
    */

    'profiles' => [

        MediaType::Banner->value => [
            'quality' => 82,
            'public' => true,
            'large' => [
                'width' => 1600,
                'height' => 650,
                'mode' => 'crop',
            ],
            'medium' => [
                'width' => 1000,
                'height' => 406,
                'mode' => 'crop',
            ],
            'thumbnail' => [
                'width' => 600,
                'height' => 244,
                'mode' => 'crop',
            ],
        ],

        MediaType::Vehicle->value => [
            'quality' => 85,
            'public' => true,
            'large' => [
                'width' => 1400,
                'height' => 933,
                'mode' => 'fit',
            ],
            'medium' => [
                'width' => 900,
                'height' => 600,
                'mode' => 'fit',
            ],
            'thumbnail' => [
                'width' => 420,
                'height' => 280,
                'mode' => 'crop',
            ],
        ],

        MediaType::Tour->value => [
            'quality' => 82,
            'public' => true,
            'large' => [
                'width' => 1400,
                'height' => 900,
                'mode' => 'fit',
            ],
            'medium' => [
                'width' => 900,
                'height' => 579,
                'mode' => 'fit',
            ],
            'thumbnail' => [
                'width' => 420,
                'height' => 270,
                'mode' => 'crop',
            ],
        ],

        MediaType::Destination->value => [
            'quality' => 82,
            'public' => true,
            'large' => [
                'width' => 1400,
                'height' => 933,
                'mode' => 'fit',
            ],
            'medium' => [
                'width' => 900,
                'height' => 600,
                'mode' => 'fit',
            ],
            'thumbnail' => [
                'width' => 420,
                'height' => 280,
                'mode' => 'crop',
            ],
        ],

        MediaType::Offer->value => [
            'quality' => 82,
            'public' => true,
            'large' => [
                'width' => 1200,
                'height' => 675,
                'mode' => 'crop',
            ],
            'medium' => [
                'width' => 800,
                'height' => 450,
                'mode' => 'crop',
            ],
            'thumbnail' => [
                'width' => 450,
                'height' => 253,
                'mode' => 'crop',
            ],
        ],

        MediaType::Profile->value => [
            'quality' => 85,
            'public' => true,
            'large' => [
                'width' => 900,
                'height' => 900,
                'mode' => 'crop',
            ],
            'medium' => [
                'width' => 600,
                'height' => 600,
                'mode' => 'crop',
            ],
            'thumbnail' => [
                'width' => 240,
                'height' => 240,
                'mode' => 'crop',
            ],
        ],

        MediaType::Document->value => [
            'quality' => 90,
            'public' => false,
            'large' => [
                'width' => 2400,
                'height' => 2400,
                'mode' => 'fit',
            ],
            'medium' => [
                'width' => 1800,
                'height' => 1800,
                'mode' => 'fit',
            ],
            'thumbnail' => [
                'width' => 600,
                'height' => 600,
                'mode' => 'fit',
            ],
        ],
    ],
];