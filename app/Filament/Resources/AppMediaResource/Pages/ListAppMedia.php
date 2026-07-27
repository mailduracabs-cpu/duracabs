<?php

namespace App\Filament\Resources\AppMediaResource\Pages;

use App\Filament\Resources\AppMediaResource;
use App\Filament\Resources\AppMediaResource\Widgets\MediaStatsWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAppMedia extends ListRecords
{
    protected static string $resource = AppMediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Upload Media')
                ->icon('heroicon-o-cloud-arrow-up'),
        ];
    }

    public function getTitle(): string
    {
        return 'Media Library';
    }

    public function getSubheading(): ?string
    {
        return 'Manage images, documents and optimized media files.';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            MediaStatsWidget::class,
        ];
    }
}