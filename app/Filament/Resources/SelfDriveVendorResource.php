<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SelfDriveVendorResource\Pages;
use App\Models\SelfDriveVendor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SelfDriveVendorResource extends Resource
{
    protected static ?string $model = SelfDriveVendor::class;

    protected static ?string $navigationGroup = 'Bike Rental';

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = 'Self Drive Vendors';

    protected static ?string $modelLabel = 'Self Drive Vendor';

    protected static ?string $pluralModelLabel = 'Self Drive Vendors';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Vendor Details')
                    ->schema([
                        Forms\Components\TextInput::make('office_name')
                            ->label('Office / Vendor Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('mobile')
                            ->label('Mobile Number')
                            ->tel()
                            ->required()
                            ->maxLength(15)
                            ->unique(
                                table: 'self_drive_vendors',
                                column: 'mobile',
                                ignoreRecord: true
                            ),

                        Forms\Components\TextInput::make('vendor_id')
                            ->label('Vendor ID / Transporter ID')
                            ->numeric()
                            ->nullable(),

                        Forms\Components\TextInput::make('city_id')
                            ->label('City ID')
                            ->numeric()
                            ->nullable(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Pickup Location')
                    ->schema([
                        Forms\Components\Textarea::make('pickup_address')
                            ->label('Pickup Address')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('latitude')
                            ->label('Latitude')
                            ->numeric()
                            ->nullable(),

                        Forms\Components\TextInput::make('longitude')
                            ->label('Longitude')
                            ->numeric()
                            ->nullable(),

                        Forms\Components\TextInput::make('service_radius_km')
                            ->label('Service Radius KM')
                            ->numeric()
                            ->default(40)
                            ->required(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('office_name')
					->label('Office Name')
					->searchable()
					->sortable(),

                Tables\Columns\TextColumn::make('mobile')
                    ->label('Mobile')
					->searchable()
					->sortable(),

                Tables\Columns\TextColumn::make('pickup_address')
                    ->label('Pickup Address')
                    ->limit(35)
                    ->searchable(),

                Tables\Columns\TextColumn::make('city_id')
                    ->label('City ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('service_radius_km')
                    ->label('Radius KM')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y h:i A')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSelfDriveVendors::route('/'),
            'create' => Pages\CreateSelfDriveVendor::route('/create'),
            'edit' => Pages\EditSelfDriveVendor::route('/{record}/edit'),
        ];
    }
}