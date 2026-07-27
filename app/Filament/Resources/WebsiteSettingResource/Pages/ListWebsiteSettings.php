<?php

namespace App\Filament\Resources\WebsiteSettingResource\Pages;

use App\Filament\Resources\WebsiteSettingResource;
use App\Models\WebsiteSetting;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWebsiteSettings extends ListRecords
{
    protected static string $resource = WebsiteSettingResource::class;

    protected function getHeaderActions(): array
    {
        if (WebsiteSetting::query()->exists()) {
            return [];
        }

        return [
            Actions\CreateAction::make()
                ->label('Configure Website'),
        ];
    }
}