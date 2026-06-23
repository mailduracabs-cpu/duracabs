<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Services\WhatsAppService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;


    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'User Updated';
    }

    protected function afterSave(): void
    {
        // Check if is_active was changed from inactive to active
        if ($this->record->wasChanged('is_active') && $this->record->is_active) {
            // Check if user is a vendor/transporter (role 2)
            $user = $this->record;
            $user->load('roles');
            
            $isVendor = $user->roles->contains('id', 2) || $user->hasRole('Transporter');
            
            // Send WhatsApp message if user is a vendor and has mobile number
            if ($isVendor && $user->mobile && $user->email) {
                // Get password - if it's not changed, we can't send it
                // For security, we'll send a message without password or use a default message
                $message = "*Dear Vendor,*\n\n";
                $message .= "We are happy to inform you that your *vendor registration request has been approved.* 🎉\n\n";
                $message .= "Kindly find your login details below:\n\n";
                $message .= "🆔 *User ID:* " . $user->email . "\n\n";
                $message .= "🔑 *Password:* Please contact admin to get your password or use 'Forgot Password' feature.\n\n";
                $message .= "👉 *Click and Login:* www.duracabs.com/admin\n\n";
                $message .= "Thank you for joining *Dura Cabs Vendor Network!* 🚖\n\n";
                $message .= "For any assistance, please contact us at *+91 70888 73331*.";
                
                try {
                    WhatsAppService::send($user->mobile, $message);
                } catch (\Exception $e) {
                    \Log::error('WhatsApp vendor activation notification failed: ' . $e->getMessage());
                }
            }
        }
    }
}
