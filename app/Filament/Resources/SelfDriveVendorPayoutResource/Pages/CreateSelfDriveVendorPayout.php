<?php

namespace App\Filament\Resources\SelfDriveVendorPayoutResource\Pages;

use App\Filament\Resources\SelfDriveVendorPayoutResource;
use App\Services\SelfDriveVendorPayoutService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateSelfDriveVendorPayout extends CreateRecord
{
    protected static string $resource = SelfDriveVendorPayoutResource::class;

    protected static ?string $title = 'Generate Vendor Payout';

    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Generate Payout')
            ->icon('heroicon-o-calculator');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $vendorId = (int) (
            $data['transporter_profile_id']
            ?? 0
        );

        $from = $data['period_from'] ?? null;
        $to = $data['period_to'] ?? null;

        if ($vendorId <= 0) {
            throw ValidationException::withMessages([
                'transporter_profile_id' =>
                    'Please select a vendor.',
            ]);
        }

        if (! $from) {
            throw ValidationException::withMessages([
                'period_from' =>
                    'Please select Period From.',
            ]);
        }

        if (! $to) {
            throw ValidationException::withMessages([
                'period_to' =>
                    'Please select Period To.',
            ]);
        }

        $service = app(
            SelfDriveVendorPayoutService::class
        );

        $payout = $service->generate(
            transporterProfileId: $vendorId,
            from: $from,
            to: $to,
            notes: $data['notes'] ?? null
        );

        Notification::make()
            ->title('Vendor payout generated')
            ->body(
                $payout->payout_no
                . ' • '
                . $payout->total_booking_units
                . ' unit(s) • ₹'
                . number_format(
                    (float) $payout->payout_amount,
                    2
                )
            )
            ->success()
            ->send();

        return $payout;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl(
            'view',
            [
                'record' => $this->record,
            ]
        );
    }
}