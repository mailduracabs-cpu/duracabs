<?php

namespace App\Filament\Resources\WebsiteSettingResource\Pages;

use App\Filament\Resources\WebsiteSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWebsiteSetting extends EditRecord
{
    protected static string $resource = WebsiteSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', [
            'record' => $this->record,
        ]);
    }
}