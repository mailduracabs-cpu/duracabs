<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Actions\Action;


class LatestOrders extends BaseWidget
{
    protected int | string | array $columnSpan = "full";

    protected static ?int $sort = 2;
    public function table(Table $table): Table
    {
        return $table
            ->query(OrderResource::getEloquentQuery())
            ->defaultPaginationPageOption(5)
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('Order ID')
                    ->searchable(),
                TextColumn::make('user.name'),
                TextColumn::make('grand_total')
                    ->money('INR'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state):string => match($state){
                        'new' => "info",
                        'start' => 'warning',
                        'modification' => 'success',
                        'confirm' => 'success',
                        'reconfirmation'=>'success',
                        'cancelled' => 'danger',
                        'closed' => 'success',
                        'refund'=> 'info'
                    })
                    ->icon(fn (string $state):string => match($state){
                        'new' => "heroicon-m-sparkles",
                                'start' => 'heroicon-m-truck',
                                'modification' => 'heroicon-m-arrow-path',
                                'confirm' => 'heroicon-m-check-badge',
                                'cancelled' => 'heroicon-m-x-circle',
                                'reconfirmation'=>'heroicon-m-phone-arrow-down-left',
                                'closed' => 'heroicon-m-clipboard-document-check',              
                                'refund'=> 'heroicon-m-currency-pound'
                    })
                    ->sortable(),
                    TextColumn::make('payment_method')
                        ->sortable()
                        ->searchable(),
                    TextColumn::make('payment_status')
                        ->sortable()
                        ->badge()
                        ->searchable(),
                    TextColumn::make('created_at')
                        ->label('Order Date')
                        ->dateTime()

            ])
         ->actions([
             Tables\Actions\Action::make('view_record')
                    ->label('View ')
                    ->url(fn($record) => route('success', ['id' => $record->id]))
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-eye'),
         ]);
    }
}
