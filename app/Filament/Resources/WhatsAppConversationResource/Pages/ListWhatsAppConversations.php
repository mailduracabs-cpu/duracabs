<?php

namespace App\Filament\Resources\WhatsAppConversationResource\Pages;

use App\Filament\Resources\WhatsAppConversationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWhatsAppConversations extends ListRecords
{
    protected static string $resource = WhatsAppConversationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
