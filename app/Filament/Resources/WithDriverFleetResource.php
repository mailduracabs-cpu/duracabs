<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WithDriverFleetResource\Pages;
use App\Models\Vehicle;

class WithDriverFleetResource extends VehicleResource
{
    protected static ?string $fleetServiceType = Vehicle::SERVICE_TAXI;
    protected static ?string $slug = 'with-driver-fleet';
    protected static ?string $navigationLabel = 'With Driver Fleet';
    protected static ?string $modelLabel = 'With Driver Vehicle';
    protected static ?string $pluralModelLabel = 'With Driver Fleet';
    protected static ?int $navigationSort = 4;

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
