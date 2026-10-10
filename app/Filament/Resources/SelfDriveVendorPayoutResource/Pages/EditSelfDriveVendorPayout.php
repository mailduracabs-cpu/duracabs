<?php

namespace App\Filament\Resources\SelfDriveVendorPayoutResource\Pages;

use App\Filament\Resources\SelfDriveVendorPayoutResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSelfDriveVendorPayout extends EditRecord
{
    protected static string $resource =
        SelfDriveVendorPayoutResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return ['notes' => $data['notes'] ?? null];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }

    protected function getSaveFormAction(): \Filament\Actions\Action
    {
        return parent::getSaveFormAction()
            ->label('Save Notes');
    }
}