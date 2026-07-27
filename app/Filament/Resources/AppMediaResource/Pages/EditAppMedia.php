<?php

namespace App\Filament\Resources\AppMediaResource\Pages;

use App\Filament\Resources\AppMediaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAppMedia extends EditRecord
{
    protected static string $resource = AppMediaResource::class;

    protected function getHeaderActions(): array
    {
        return [

            Actions\DeleteAction::make(),

        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['upload']);
        unset($data['uploaded_file_name']);

        return $data;
    }
}