<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SelfDriveFleetResource\Pages;
use App\Models\Vehicle;

class SelfDriveFleetResource extends VehicleResource
{
    protected static ?string $fleetServiceType = Vehicle::SERVICE_SELF_DRIVE;
    protected static ?string $slug = 'self-drive-fleet';
    protected static ?string $navigationLabel = 'Self Drive Fleet';
    protected static ?string $modelLabel = 'Self Drive Car';
    protected static ?string $pluralModelLabel = 'Self Drive Fleet';
    protected static ?int $navigationSort = 3;

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
