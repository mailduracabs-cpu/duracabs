<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Mail\OrderUpdated;
use App\Models\address;
use App\Models\User;
use App\Models\Vehicle;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\EditAction;
use Illuminate\Support\Facades\Mail;



class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function beforeSave($options = array()): void
    
    {

        //dd($this->data,$options);

        // user

        $userId= $this->data['user_id'];
        $userData = User::where('id', $userId)->firstOrFail();
        $userMail = $userData->email;

        //driver
        $driverId= $this->data['driver_id'];
        $vehcileId= $this->data['vehicle_id'];
        $driverData = User::where('id', $driverId)->firstOrFail();
        $vehicle = Vehicle::where('id', $vehcileId )->firstOrFail();

        

        // Address

        $address = address::where('order_id',$this->data['id'])->firstOrFail();

        $data=[];
        
        if($this->data['status'] === 'start'){

            $data= [$address,$this->data,$driverData,$vehicle];

            
        } else {
            
            $data= [$address,$this->data];
        }


        
        

       


        foreach ([$userMail,$address['email']] as $recipient) {
            Mail::to($recipient)->send(new OrderUpdated(($data)));
        }

      //  Mail::to($userMail)->send(new OrderUpdated($this->data['status']));

    
        
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
