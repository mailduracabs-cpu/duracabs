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
                ->label('Add Driver')
                ->icon('heroicon-o-user-plus'),
        ];
    }
}
