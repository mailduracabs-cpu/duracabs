<?php

namespace App\Filament\Resources\SelfDriveBookingResource\Pages;

use App\Filament\Resources\SelfDriveBookingResource;
use App\Models\SelfDriveBooking;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSelfDriveBooking extends EditRecord
{
    protected static string $resource = SelfDriveBookingResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $total = max(0, (float) ($data['total_amount'] ?? 0));
        $final = max(0, (float) ($data['final_amount'] ?? $total));
        $paid = max(0, (float) ($data['paid_amount'] ?? 0));

        $data['final_amount'] = $final;
        $data['remaining_amount'] = max(0, $final - $paid);
        $data['balance_due'] = $data['remaining_amount'];

        if (($data['payment_status'] ?? null) !== 'refunded') {
            $data['payment_status'] = $paid <= 0
                ? 'pending'
                : ($paid < $final ? 'partial' : 'paid');

            $data['payment_completed_at'] =
                $data['payment_status'] === 'paid'
                    ? ($this->record->payment_completed_at ?? now())
                    : null;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->refreshTripAmounts();
        $this->record->save();

        Notification::make()
            ->title('Booking Updated')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Delete Self Drive Booking')
                ->modalDescription(
                    'Ye booking permanently delete ho jayegi. Is action ko undo nahi kiya ja sakta.'
                ),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
