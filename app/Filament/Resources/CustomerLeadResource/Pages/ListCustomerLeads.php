<?php

namespace App\Filament\Resources\CustomerLeadResource\Pages;

use App\Filament\Resources\CustomerLeadResource;
use App\Filament\Widgets\CustomerLeadStatsWidget;
use Filament\Resources\Pages\ListRecords;

class ListCustomerLeads extends ListRecords
{
    protected static string $resource = CustomerLeadResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CustomerLeadStatsWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return [
            'default' => 1,
            'sm' => 2,
            'lg' => 5,
        ];
    }
}
