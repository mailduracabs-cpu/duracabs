<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleResource\Pages;
use App\Filament\Resources\VehicleResource\RelationManagers;
use App\Models\User;
use App\Models\Vehicle;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;


class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Toggle::make('is_active')->disabled(auth()->user()->roles->pluck('name')[0] != 'Admin'),
                Select::make('user_id')
                    ->label('Transporter')
                    ->options(
                            User::role('Transporter') // comes from spatie/laravel-permission
                                ->pluck('name', 'id')
                                ->toArray()
                        )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->hidden(auth()->user()->roles->pluck('name')[0] != 'Admin')
                    ,
                TextInput::make('vehicle_number')
                    ->maxLength(255)
                    ->default(null),
                TextInput::make('chassis_number')
                    ->maxLength(255)
                    ->default(null),
                TextInput::make('insurance_number')
                    ->maxLength(255)
                    ->default(null),
                TextInput::make('owner_name')
                    ->maxLength(255)
                    ->default(null),
                TextInput::make('car_company_name')
                    ->maxLength(255)
                    ->default(null),
                TextInput::make('car_classification')
                    ->maxLength(255)
                    ->default(null),
                TextInput::make('car_color')
                    ->maxLength(255)
                    ->default(null),
                TextInput::make('insurance_company_name')
                    ->maxLength(255)
                    ->default(null),
                FileUpload::make('rc_image')
                    ->image(),
                FileUpload::make('insurance_image')
                    ->image(),
                FileUpload::make('polution_image')
                    ->image(),
                
                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('vehicle_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('owner_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('car_company_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('car_classification')
                    ->searchable(),
                Tables\Columns\TextColumn::make('car_color')
                    ->searchable(),
                Tables\Columns\TextColumn::make('insurance_company_name')
                    ->searchable(),
                 Tables\Columns\TextColumn::make('user_id')
                    ->label('Transporter')
                    ->sortable()
                     ->formatStateUsing(fn($state) => User::where('id', $state)->value('name'))
                    ,
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
{
    
    $user = auth()->user()->roles->pluck('name')[0];
       
    if ($user === "Admin") {
        return parent::getEloquentQuery();
    }
    return parent::getEloquentQuery()->where('user_id', auth()->id());
    
}


}
