<?php

namespace App\Filament\Resources\SelfDriveVendorPayoutResource\Pages;

use App\Filament\Resources\SelfDriveVendorPayoutResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSelfDriveVendorPayouts extends ListRecords
{
    protected static string $resource = SelfDriveVendorPayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Generate Payout')
                ->icon('heroicon-o-calculator'),
        ];
    }
}