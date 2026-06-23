<?php

namespace App\Filament\Resources\CouponsResource\Pages;

use App\Filament\Resources\CouponsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCoupons extends EditRecord
{
    protected static string $resource = CouponsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
