<?php

namespace App\Filament\Resources\SmartHomeBlockResource\Pages;

use App\Filament\Resources\SmartHomeBlockResource;
use App\Services\SmartBannerService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListSmartHomeBlocks extends ListRecords
{
    protected static string $resource =
        SmartHomeBlockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('clear_cache')
                ->label('Refresh Smart Home')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Refresh Smart Home cache?')
                ->modalDescription(
                    'The latest block, fare and vehicle data will be generated on the next API request.'
                )
                ->action(function (): void {
                    app(SmartBannerService::class)
                        ->clearCache();

                    Notification::make()
                        ->title('Smart Home cache refreshed')
                        ->body(
                            'The next API request will load fresh homepage data.'
                        )
                        ->success()
                        ->send();
                }),

            Actions\CreateAction::make()
                ->label('Create Smart Block')
                ->icon('heroicon-o-plus'),
        ];
    }
}