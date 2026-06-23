<?php

namespace App\Filament\Resources\MyDriverResource\Pages;

use App\Filament\Resources\MyDriverResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateMyDriver extends CreateRecord
{
    protected static string $resource = MyDriverResource::class;

     protected static ?string $title = 'Drivers';

    protected static ?string $navigationLabel = 'Drivers';

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';  
    
        protected function getRedirectUrl(): string
{
    return $this->getResource()::getUrl('index');
}

  protected function mutateFormDataBeforeCreate(array $data): array
{
    if(Auth::user()->roles->pluck('name')[0] == 'Transporter'){
        $data['created_by'] = Auth::id();
    }
    
   
    return $data;
}

}
