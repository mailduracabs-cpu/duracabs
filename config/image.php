<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Image Driver
    |--------------------------------------------------------------------------
    */

    'driver' => \Intervention\Image\Drivers\Gd\Driver::class,

    /*
    |--------------------------------------------------------------------------
    | Processing Options
    |--------------------------------------------------------------------------
    */

    'options' => [
        'autoOrientation' => true,
        'decodeAnimation' => false,
        'blendingColor' => 'ffffff',

        // Remove EXIF and unnecessary metadata from generated variants.
        'strip' => true,
    ],

];