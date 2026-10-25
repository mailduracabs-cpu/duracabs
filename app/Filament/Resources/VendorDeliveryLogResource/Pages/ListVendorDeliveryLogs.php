<?php

namespace App\Filament\Resources\VendorDeliveryLogResource\Pages;

use App\Filament\Resources\VendorDeliveryLogResource;
use Filament\Resources\Pages\ListRecords;

class ListVendorDeliveryLogs extends ListRecords
{
    protected static string $resource = VendorDeliveryLogResource::class;
    protected function getHeaderActions(): array { return []; }
}
