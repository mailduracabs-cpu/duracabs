<?php

namespace App\Filament\Resources\BikeRentalFleetResource\Pages;

use App\Filament\Resources\BikeRentalFleetResource;
use App\Filament\Resources\VehicleResource\Pages\ListVehicles;
use Filament\Actions;

class ListFleetVehicles extends ListVehicles
{
    protected static string $resource = BikeRentalFleetResource::class;

    public function getTitle(): string
    {
        return 'Bike Rental Fleet';
    }

    public function getBreadcrumb(): string
    {
        return 'Bike Rental Fleet';
    }

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()
            ->label('Add Bike / Scooter')
            ->icon('heroicon-o-plus-circle')];
    }
}
