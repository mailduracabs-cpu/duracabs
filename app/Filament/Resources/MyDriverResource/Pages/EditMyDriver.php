<?php

namespace App\Filament\Resources\MyDriverResource\Pages;

use App\Filament\Resources\MyDriverResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMyDriver extends EditRecord
{
    protected static string $resource = MyDriverResource::class;

    protected static ?string $title = 'Drivers';

    protected static ?string $navigationLabel = 'Drivers';

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';   
    
    protected function getRedirectUrl(): string
{
    return $this->getResource()::getUrl('index');
}


    protected function getHeaderActions(): array
    {
        return [
           
        ];
    }
}
