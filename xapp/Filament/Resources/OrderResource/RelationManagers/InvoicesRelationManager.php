<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'Invoices';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('ammount')
                    ->required()
                    ->numeric()
                    ->dehydrated()
                    ->label('Ammount'),
                DatePicker::make('date'),
                Select::make('status')
                    ->options([
                        'cash' => 'Cash',
                        'online' => 'Online',
                    ])
                    ->label('Mode')
                    ->required(),
                Select::make('user_id')
                    ->label('Paid To')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                FileUpload::make('image')
                    ->image()
                    ->directory('invoice'),

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('ammount')
            ->columns([
                Tables\Columns\TextColumn::make('ammount'),
                Tables\Columns\TextColumn::make('date'),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('user.name')
                    
                    ->sortable(),
                
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
