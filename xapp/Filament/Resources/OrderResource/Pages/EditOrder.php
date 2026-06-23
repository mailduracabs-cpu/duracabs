<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Mail\OrderUpdated;
use App\Models\address;
use App\Models\User;
use App\Models\Vehicle;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;



class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function afterSave(): void
    {
        // Check if status has changed - only send email when status is updated
        if ($this->record->wasChanged('status')) {
            // Refresh the record to get latest data
            $this->record->refresh();
            
            // Get user
            $userId = $this->record->user_id;
            $userData = User::where('id', $userId)->firstOrFail();
            $userMail = $userData->email;

            // Get driver and vehicle (if they exist)
            $driverId = $this->record->driver_id ?? null;
            $vehicleId = $this->record->vehicle_id ?? null;
            
            $driverData = null;
            $vehicle = null;

            if ($driverId) {
                $driverData = User::where('id', $driverId)->first();
            }

            if ($vehicleId) {
                $vehicle = Vehicle::where('id', $vehicleId)->first();
            }

            // Get address
            $address = address::where('order_id', $this->record->id)->firstOrFail();

            // Prepare order data array (convert model to array)
            $orderData = $this->record->toArray();

            // Prepare data array for mail
            $data = [];
            
            if ($this->record->status === 'start' && $driverData && $vehicle) {
                $data = [$address, $orderData, $driverData, $vehicle];
            } else {
                $data = [$address, $orderData];
            }

            // Send email to user and address email
            $recipients = array_filter([$userMail, $address->email ?? null]);

            // dd($data, $recipients);
            
            foreach ($recipients as $recipient) {
                if ($recipient) {
                    Mail::to($recipient)->send(new OrderUpdated($data));
                }
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
