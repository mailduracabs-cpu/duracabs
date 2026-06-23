<?php

namespace App\Filament\Resources\InquirysResource\Pages;

use App\Filament\Resources\InquirysResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInquirys extends EditRecord
{
    protected static string $resource = InquirysResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
