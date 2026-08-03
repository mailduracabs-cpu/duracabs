<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Filament\Resources\OrderResource\Widgets\OrderStates;
use App\Models\Order;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Booking')
                ->icon('heroicon-o-plus-circle')
                ->color('primary'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            OrderStates::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(fn (): int => Order::query()->count()),

            'pending' => Tab::make('Pending')
                ->icon('heroicon-o-clock')
                ->badge(fn (): int => Order::query()
                    ->whereIn('status', [
                        'new',
                        'reconfirmation',
                        'modification',
                    ])
                    ->count())
                ->modifyQueryUsing(
                    fn (Builder $query): Builder => $query->whereIn(
                        'status',
                        [
                            'new',
                            'reconfirmation',
                            'modification',
                        ]
                    )
                ),

            'confirmed' => Tab::make('Confirmed')
                ->icon('heroicon-o-check-badge')
                ->badge(fn (): int => Order::query()
                    ->where('status', 'confirm')
                    ->count())
                ->modifyQueryUsing(
                    fn (Builder $query): Builder => $query->where(
                        'status',
                        'confirm'
                    )
                ),

            'running' => Tab::make('Running')
                ->icon('heroicon-o-truck')
                ->badge(fn (): int => Order::query()
                    ->where('status', 'start')
                    ->count())
                ->modifyQueryUsing(
                    fn (Builder $query): Builder => $query->where(
                        'status',
                        'start'
                    )
                ),

            'completed' => Tab::make('Completed')
                ->icon('heroicon-o-clipboard-document-check')
                ->badge(fn (): int => Order::query()
                    ->where('status', 'closed')
                    ->count())
                ->modifyQueryUsing(
                    fn (Builder $query): Builder => $query->where(
                        'status',
                        'closed'
                    )
                ),

            'cancelled' => Tab::make('Cancelled')
                ->icon('heroicon-o-x-circle')
                ->badge(fn (): int => Order::query()
                    ->whereIn('status', ['cancelled', 'refund'])
                    ->count())
                ->modifyQueryUsing(
                    fn (Builder $query): Builder => $query->whereIn(
                        'status',
                        ['cancelled', 'refund']
                    )
                ),
        ];
    }
}