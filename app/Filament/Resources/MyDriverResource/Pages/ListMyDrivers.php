<?php

namespace App\Filament\Resources\MyDriverResource\Pages;

use App\Filament\Resources\MyDriverResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMyDrivers extends ListRecords
{
    protected static string $resource = MyDriverResource::class;

    protected static ?string $title = 'Drivers';

    protected static ?string $navigationLabel = 'Drivers';

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
   

    protected function getHeaderActions(): array
    {
        return [
           Actions\CreateAction::make()
            ->label('Add New Driver') // 👈 change button text here
            ->modalHeading('Add a driver') // 👈 also change modal title if needed
            ->icon('heroicon-o-user-plus'),
        ];
    }

    
    
}
