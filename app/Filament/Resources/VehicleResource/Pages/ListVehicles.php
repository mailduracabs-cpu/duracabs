<?php

namespace App\Filament\Resources\VehicleResource\Pages;

use App\Filament\Resources\VehicleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVehicles extends ListRecords
{
    protected static string $resource = VehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(
                    VehicleResource::isPartnerPanel()
                        ? 'Add Vehicle'
                        : 'Create Vehicle'
                )
                ->icon('heroicon-o-plus-circle'),
        ];
    }

    public function getTitle(): string
    {
        return VehicleResource::isPartnerPanel()
            ? 'My Vehicles'
            : 'Vehicle Management';
    }

    public function getBreadcrumb(): string
    {
        return VehicleResource::isPartnerPanel()
            ? 'My Vehicles'
            : 'Vehicles';
    }
}