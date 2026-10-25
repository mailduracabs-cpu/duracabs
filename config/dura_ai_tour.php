<?php
return [
    'enabled' => (bool) env('DURA_AI_TOUR_ENABLED', false),
    // Tour reuses seo-ai.providers.gemini from the existing content writer.
    // DURA_AI_TOUR_API_KEY / DURA_AI_TOUR_MODEL are intentionally unused.
];
