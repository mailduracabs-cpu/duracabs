<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\SEO\Services\SeoAnalysisService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
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

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}