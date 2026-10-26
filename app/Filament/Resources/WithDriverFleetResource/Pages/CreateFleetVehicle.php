<?php

namespace App\Filament\Resources\WithDriverFleetResource\Pages;

use App\Filament\Resources\WithDriverFleetResource;
use App\Filament\Resources\VehicleResource\Pages\CreateVehicle;
use App\Models\Vehicle;

class CreateFleetVehicle extends CreateVehicle
{
    protected static string $resource = WithDriverFleetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = parent::mutateFormDataBeforeCreate($data);
        // Enforce the selected fleet server-side, beyond the disabled select.
        $data['service_type'] = Vehicle::SERVICE_TAXI;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
