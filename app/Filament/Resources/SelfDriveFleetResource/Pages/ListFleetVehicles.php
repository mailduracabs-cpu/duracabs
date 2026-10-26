<?php

namespace App\Filament\Resources\SelfDriveFleetResource\Pages;

use App\Filament\Resources\SelfDriveFleetResource;
use App\Filament\Resources\VehicleResource\Pages\ListVehicles;
use Filament\Actions;

class ListFleetVehicles extends ListVehicles
{
    protected static string $resource = SelfDriveFleetResource::class;

    public function getTitle(): string
    {
        return 'Self Drive Fleet';
    }

    public function getBreadcrumb(): string
    {
        return 'Self Drive Fleet';
    }

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()
            ->label('Add Self Drive Car')
            ->icon('heroicon-o-plus-circle')];
    }
}
