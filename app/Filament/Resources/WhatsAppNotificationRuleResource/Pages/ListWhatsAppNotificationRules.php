<?php

namespace App\Filament\Resources\WhatsAppNotificationRuleResource\Pages;

use App\Filament\Resources\WhatsAppNotificationRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWhatsAppNotificationRules extends ListRecords
{
    protected static string $resource =
        WhatsAppNotificationRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}