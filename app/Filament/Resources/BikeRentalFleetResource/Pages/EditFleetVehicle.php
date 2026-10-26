<?php

namespace App\Filament\Resources\BikeRentalFleetResource\Pages;

use App\Filament\Resources\BikeRentalFleetResource;
use App\Filament\Resources\VehicleResource\Pages\EditVehicle;
use App\Models\Vehicle;

class EditFleetVehicle extends EditVehicle
{
    protected static string $resource = BikeRentalFleetResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = parent::mutateFormDataBeforeSave($data);
        // Enforce the selected fleet server-side, beyond the disabled select.
        $data['service_type'] = Vehicle::SERVICE_BIKE_RENTAL;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
