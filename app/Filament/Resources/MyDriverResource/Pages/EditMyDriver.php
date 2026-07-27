<?php

namespace App\Filament\Resources\MyDriverResource\Pages;

use App\Filament\Resources\MyDriverResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMyDriver extends EditRecord
{
    protected static string $resource = MyDriverResource::class;

    protected static ?string $title = 'Edit Driver';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(
                    fn (): bool =>
                        auth()->user()?->hasRole('Admin') ?? false
                ),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
