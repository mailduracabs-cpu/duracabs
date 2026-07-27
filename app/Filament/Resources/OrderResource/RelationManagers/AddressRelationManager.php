<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AddressRelationManager extends RelationManager
{
    protected static string $relationship = 'address';

    public function form(Form $form): Form
    {
        return $form
            ->schema([

                TextInput::make('full_name')
                    
                    ->maxLength(255),
                TextInput::make('phone')
                    
                    ->maxLength(255),
                TextInput::make('phone2')
                    
                    ->tel()
                    ->maxLength(20),
                TextInput::make('email')
                    
                    ->type('email')
                    ->maxLength(255),
                TextInput::make('pickup_address')
                    
                    ->maxLength(255),
                TextInput::make('zip_code')
                    
                    ->numeric()
                    ->maxLength(10),
                Textarea::make('street_address')
                    
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('street_address')
            ->columns([
                TextColumn::make('full_name')
                    ->label('Full Name'),
                TextColumn::make('phone'),
                TextColumn::make('phone2'),
                TextColumn::make('email'),
                TextColumn::make('state'),
                TextColumn::make('city'),
                TextColumn::make('pickup_address'),
                TextColumn::make('drop_address'),
                TextColumn::make('number_travellers'),
                TextColumn::make('number_luggage'),
                TextColumn::make('comments'),
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
