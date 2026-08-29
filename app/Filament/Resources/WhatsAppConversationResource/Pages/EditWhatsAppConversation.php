<?php

namespace App\Filament\Resources\WhatsAppConversationResource\Pages;

use App\Filament\Resources\WhatsAppConversationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWhatsAppConversation extends EditRecord
{
    protected static string $resource = WhatsAppConversationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('backToInbox')
                ->label('Back to Inbox')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(WhatsAppConversationResource::getUrl('index')),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->markAsRead();

        return $data;
    }

    protected function getFormActions(): array
    {
        return [];
    }
}