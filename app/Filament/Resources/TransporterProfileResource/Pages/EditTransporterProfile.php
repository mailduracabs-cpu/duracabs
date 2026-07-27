<?php

namespace App\Filament\Resources\TransporterProfileResource\Pages;

use App\Filament\Resources\TransporterProfileResource;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;

class EditTransporterProfile extends EditRecord
{
    protected static string $resource = TransporterProfileResource::class;

    protected static ?string $title = 'Edit Partner';

    protected function getHeaderActions(): array
    {
        if (Filament::getCurrentPanel()?->getId() === 'transporter') {
            return [];
        }

        return [
            Actions\DeleteAction::make()
                ->visible(
                    fn (): bool =>
                        $this->record->canBeDeletedSafely()
                ),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
