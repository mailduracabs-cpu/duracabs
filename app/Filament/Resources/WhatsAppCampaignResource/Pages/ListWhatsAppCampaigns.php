<?php

namespace App\Filament\Resources\WhatsAppCampaignResource\Pages;

use App\Filament\Resources\WhatsAppCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWhatsAppCampaigns extends ListRecords
{
    protected static string $resource = WhatsAppCampaignResource::class;

    protected static ?string $title = 'WhatsApp Campaigns';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Campaign')
                ->icon('heroicon-o-plus')
                ->color('success'),
        ];
    }
}