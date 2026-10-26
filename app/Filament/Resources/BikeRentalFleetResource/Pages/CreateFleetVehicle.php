<?php

namespace App\Filament\Resources\BikeRentalFleetResource\Pages;

use App\Filament\Resources\BikeRentalFleetResource;
use App\Filament\Resources\VehicleResource\Pages\CreateVehicle;
use App\Models\Vehicle;

class CreateFleetVehicle extends CreateVehicle
{
    protected static string $resource = BikeRentalFleetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = parent::mutateFormDataBeforeCreate($data);
        // Enforce the selected fleet server-side, beyond the disabled select.
        $data['service_type'] = Vehicle::SERVICE_BIKE_RENTAL;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
