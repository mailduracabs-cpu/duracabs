<?php

namespace App\Filament\Resources\WebsiteSettingResource\Pages;

use App\Filament\Resources\WebsiteSettingResource;
use App\Models\WebsiteSetting;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateWebsiteSetting extends CreateRecord
{
    protected static string $resource = WebsiteSettingResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $existingSetting = WebsiteSetting::query()->first();

        if ($existingSetting) {
            $existingSetting->update($data);

            return $existingSetting;
        }

        return WebsiteSetting::query()->create($data);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', [
            'record' => $this->record,
        ]);
    }
}