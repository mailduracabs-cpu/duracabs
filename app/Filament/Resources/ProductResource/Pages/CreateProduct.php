<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\SEO\Services\SeoAnalysisService;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $analysis = app(SeoAnalysisService::class)
            ->analyzeToArray([
                'title' => $data['meta_title'] ?? $data['name'] ?? '',
                'meta_description' => $data['meta_description'] ?? '',
                'slug' => $data['slug'] ?? '',
                'focus_keyword' => $data['focus_keyword'] ?? '',
                'description' => $data['description'] ?? '',
            ]);

        $data['seo_score'] = $analysis['score'];
        $data['readability_score'] = $analysis['readability_score'];
        $data['seo_analysis'] = $analysis;

        return $data;
    }
}