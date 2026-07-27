<?php

namespace App\Filament\Resources\CouponsResource\Pages;

use App\Filament\Resources\CouponsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCoupons extends ListRecords
{
    protected static string $resource = CouponsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
