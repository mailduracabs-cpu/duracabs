<?php

namespace App\Filament\Resources\VendorMonthlyBillResource\Pages;

use App\Filament\Resources\VendorMonthlyBillResource;
use Filament\Resources\Pages\ListRecords;

class ListVendorMonthlyBills extends ListRecords
{
    protected static string $resource = VendorMonthlyBillResource::class;
    protected function getHeaderActions(): array { return []; }
}
