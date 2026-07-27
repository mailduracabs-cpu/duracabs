<?php

namespace App\Filament\Resources\SmartHomeBlockResource\Pages;

use App\Filament\Resources\SmartHomeBlockResource;
use App\Services\SmartBannerService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSmartHomeBlock extends EditRecord
{
    protected static string $resource =
        SmartHomeBlockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->after(function (): void {
                    app(SmartBannerService::class)
                        ->clearCache();
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        app(SmartBannerService::class)
            ->clearCache();

        Notification::make()
            ->title('Smart Home block updated')
            ->body(
                'Homepage cache has been refreshed automatically.'
            )
            ->success()
            ->send();
    }
}