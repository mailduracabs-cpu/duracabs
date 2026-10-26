<?php

namespace App\Filament\Resources\WithDriverFleetResource\Pages;

use App\Filament\Resources\WithDriverFleetResource;
use App\Filament\Resources\VehicleResource\Pages\ListVehicles;
use Filament\Actions;

class ListFleetVehicles extends ListVehicles
{
    protected static string $resource = WithDriverFleetResource::class;

    public function getTitle(): string
    {
        return 'With Driver Fleet';
    }

    public function getBreadcrumb(): string
    {
        return 'With Driver Fleet';
    }

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()
            ->label('Add With Driver Vehicle')
            ->icon('heroicon-o-plus-circle')];
    }
}
