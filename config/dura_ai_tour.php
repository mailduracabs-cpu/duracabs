<?php
return [
    'enabled' => (bool) env('DURA_AI_TOUR_ENABLED', false),
    'api_key' => env('DURA_AI_TOUR_API_KEY'),
    'model' => env('DURA_AI_TOUR_MODEL', 'gpt-4.1-mini'),
];
