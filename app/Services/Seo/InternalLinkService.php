<?php

namespace App\Services\Seo;

class InternalLinkService
{
    /**
     * Return SEO links for any supported model.
     */
    public static function for($model): array
    {
        return [
            'related_pages' => [],
            'related_routes' => [],
            'related_self_drive' => [],
            'related_tours' => [],
            'related_blogs' => [],
            'related_products' => [],
            'related_cities' => [],
        ];
    }
}
