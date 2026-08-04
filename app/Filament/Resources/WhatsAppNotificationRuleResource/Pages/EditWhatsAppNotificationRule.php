<?php

namespace App\Filament\Resources\WhatsAppNotificationRuleResource\Pages;

use App\Filament\Resources\WhatsAppNotificationRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWhatsAppNotificationRule extends EditRecord
{
    protected static string $resource =
        WhatsAppNotificationRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}