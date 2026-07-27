<?php

namespace App\Filament\Resources\SelfDriveBookingResource\Pages;

use App\Filament\Resources\SelfDriveBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSelfDriveBookings extends ListRecords
{
    protected static string $resource = SelfDriveBookingResource::class;

    protected ?string $pollingInterval = '10s';

    protected function getHeaderActions(): array
    {
        return SelfDriveBookingResource::isTransporterPanel()
            ? []
            : [
                Actions\CreateAction::make()
                    ->label('Create Customer Booking')
                    ->icon('heroicon-o-plus-circle'),
            ];
    }
}