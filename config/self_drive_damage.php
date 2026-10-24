<?php
return [
    'enabled' => (bool) env('SELF_DRIVE_DAMAGE_AI_ENABLED', false),
    'api_key' => env('SELF_DRIVE_DAMAGE_AI_KEY'),
    'model' => env('SELF_DRIVE_DAMAGE_AI_MODEL'),
    // Start from deployment date. Prevent sending historical photos unexpectedly.
    'since' => env('SELF_DRIVE_DAMAGE_AI_SINCE'),
];
