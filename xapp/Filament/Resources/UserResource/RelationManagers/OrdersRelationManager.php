<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
               //
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('id')
                    ->label('Order ID')
                    ->searchable(),
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
            ->filters([
                //
            ])
            ->headerActions([
               // Tables\Actions\CreateAction::make(),
            ])
            ->actions([
               Tables\Actions\Action::make('view_record')
                    ->label('View ')
                    ->url(fn($record) => route('success', ['id' => $record->id]))
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-eye'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
