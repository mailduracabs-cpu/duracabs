<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BannersResource\Pages;
use App\Forms\Components\DuraImageUpload;
use App\Forms\Components\DuraSeo;
use App\Models\Banners;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BannersResource extends Resource
{
    protected static ?string $model = Banners::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'Home Banners';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Section::make('Banner Information')
                    ->schema([

                        Grid::make(2)
                            ->schema([

                                TextInput::make('name')
                                    ->label('Banner Name')
                                    ->required(),

                                TextInput::make('title')
                                    ->label('Heading')
                                    ->required(),

                                TextInput::make('url')
                                    ->label('Button / Redirect URL'),

                                Select::make('ride_type')
                                    ->label('Banner Type')
                                    ->required()
                                    ->options([
                                        'home' => 'Home',
                                        'one_way' => 'One Way',
                                        'return' => 'Return',
                                        'local' => 'Local',
                                        'airport' => 'Airport',
                                        'self_drive' => 'Self Drive',
                                        'tour' => 'Tour Package',
                                        'offer' => 'Offer',
                                    ]),
                            ]),

                        DuraImageUpload::make(
                            'image',
                            'banners'
                        ),
                    ]),

                DuraSeo::make(),

                Section::make('Status')
                    ->schema([

                        Toggle::make('is_active')
                            ->default(true),

                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table

            ->columns([

                Tables\Columns\ImageColumn::make('image')
                    ->square(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('title')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('ride_type')
                    ->colors([
                        'success',
                    ]),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),

            ])

            ->actions([

                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make(),

            ])

            ->bulkActions([

                Tables\Actions\BulkActionGroup::make([

                    Tables\Actions\DeleteBulkAction::make(),

                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [

            'index' => Pages\ListBanners::route('/'),

            'create' => Pages\CreateBanners::route('/create'),

            'edit' => Pages\EditBanners::route('/{record}/edit'),

        ];
    }
}
