<?php

namespace App\Filament\Resources\SmartHomeBlockResource\Pages;

use App\Filament\Resources\SmartHomeBlockResource;
use App\Services\SmartBannerService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateSmartHomeBlock extends CreateRecord
{
    protected static string $resource =
        SmartHomeBlockResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        app(SmartBannerService::class)
            ->clearCache();

        Notification::make()
            ->title('Smart Home block created')
            ->body(
                'Homepage cache has been refreshed automatically.'
            )
            ->success()
            ->send();
    }
}