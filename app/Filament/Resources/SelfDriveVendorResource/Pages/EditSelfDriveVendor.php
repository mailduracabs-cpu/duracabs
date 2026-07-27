<?php

namespace App\Filament\Resources\SelfDriveVendorResource\Pages;

use App\Filament\Resources\SelfDriveVendorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSelfDriveVendor extends EditRecord
{
    protected static string $resource = SelfDriveVendorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
