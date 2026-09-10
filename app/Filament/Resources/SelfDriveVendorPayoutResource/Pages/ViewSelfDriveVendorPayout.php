<?php

namespace App\Filament\Resources\SelfDriveVendorPayoutResource\Pages;

use App\Filament\Resources\SelfDriveVendorPayoutResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSelfDriveVendorPayout extends ViewRecord
{
    protected static string $resource =
        SelfDriveVendorPayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}