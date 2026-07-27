<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Filament\Resources\OrderResource\Widgets\OrderStates;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array{
        return [
            OrderStates::class
        ];
    }
    
    public function getTabs(): array {
        return [
            null => Tab::make('All'),
            'New' => Tab::make()->query(fn ($query) => $query->where('status', 'new')),
            'Reconfirmation / Follow Up' => Tab::make()->query(fn ($query) => $query->where('status', 'reconfirmation')),
            'Confirm' => Tab::make()->query(fn ($query) => $query->where('status', 'confirm')),
            'Modification' => Tab::make()->query(fn ($query) => $query->where('status', 'modification')),
            'Trip Start' => Tab::make()->query(fn ($query) => $query->where('status', 'start')),
            'Cancelled / Not Interested' => Tab::make()->query(fn ($query) => $query->where('status', 'cancelled')),
            'Closed' => Tab::make()->query(fn ($query) => $query->where('status', 'closed')),
            'Refund' => Tab::make()->query(fn ($query) => $query->where('status', 'refund')),
        ];
    }

   
}
