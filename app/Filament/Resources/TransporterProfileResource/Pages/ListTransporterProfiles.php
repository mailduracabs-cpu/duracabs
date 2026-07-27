<?php

namespace App\Filament\Resources\TransporterProfileResource\Pages;

use App\Filament\Resources\TransporterProfileResource;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;

class ListTransporterProfiles extends ListRecords
{
    protected static string $resource = TransporterProfileResource::class;

    protected function getHeaderActions(): array
    {
        if (Filament::getCurrentPanel()?->getId() === 'transporter') {
            return [];
        }

        return [
            Actions\CreateAction::make()
                ->label('Add Partner')
                ->icon('heroicon-o-user-plus'),
        ];
    }
}
