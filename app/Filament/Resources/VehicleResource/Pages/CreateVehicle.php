<?php

namespace App\Filament\Resources\VehicleResource\Pages;

use App\Filament\Resources\VehicleResource;
use Auth;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateVehicle extends CreateRecord
{
    protected static string $resource = VehicleResource::class;

      protected function mutateFormDataBeforeCreate(array $data): array
{
    if(Auth::user()->roles->pluck('name')[0] == 'Transporter'){
        $data['user_id'] = Auth::id();
    }
    
   
    return $data;
}

    
}




