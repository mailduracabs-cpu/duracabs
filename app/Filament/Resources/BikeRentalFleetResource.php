<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BikeRentalFleetResource\Pages;
use App\Models\Vehicle;

class BikeRentalFleetResource extends VehicleResource
{
    protected static ?string $fleetServiceType = Vehicle::SERVICE_BIKE_RENTAL;
    protected static ?string $slug = 'bike-rental-fleet';
    protected static ?string $navigationLabel = 'Bike Rental Fleet';
    protected static ?string $modelLabel = 'Bike / Scooter';
    protected static ?string $pluralModelLabel = 'Bike Rental Fleet';
    protected static ?int $navigationSort = 5;

    public static function shouldRegisterNavigation(): bool
    {
        return filament()->getCurrentPanel()?->getId() === 'admin';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFleetVehicles::route('/'),
            'create' => Pages\CreateFleetVehicle::route('/create'),
            'edit' => Pages\EditFleetVehicle::route('/{record}/edit'),
        ];
    }
}
