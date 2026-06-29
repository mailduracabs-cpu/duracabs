<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Forms\Components\DuraImageUpload;
use App\Forms\Components\DuraStatus;
use App\Models\Category;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Cab Categories';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Basic Information')
                    ->description('Cab category ka naam, slug aur image.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Category Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        $set('slug', Str::slug($state));
                                    }),

                                TextInput::make('slug')
                                    ->label('Slug')
                                    ->required()
                                    ->disabled()
                                    ->dehydrated()
                                    ->maxLength(255)
                                    ->unique(Category::class, 'slug', ignoreRecord: true),
                            ]),

                        DuraImageUpload::make('image', 'categories'),
                    ]),

                Section::make('Vehicle Details')
                    ->description('Seats, luggage aur vehicle model details.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('model')
                                    ->label('Model / Similar Cars')
                                    ->placeholder('Dzire, Aura or similar')
                                    ->maxLength(255),

                                TextInput::make('passanger_capacity')
                                    ->label('Passenger Capacity')
                                    ->placeholder('4')
                                    ->maxLength(255),

                                TextInput::make('luggage_capacity')
                                    ->label('Luggage Capacity')
                                    ->placeholder('2 Bags')
                                    ->maxLength(255),

                                TextInput::make('range')
                                    ->label('Range')
                                    ->placeholder('Local / Outstation')
                                    ->maxLength(255),

                                TextInput::make('new_vehicle')
                                    ->label('New Vehicle')
                                    ->placeholder('Yes / No')
                                    ->maxLength(255),

                                TextInput::make('roof_career')
                                    ->label('Roof Carrier')
                                    ->placeholder('Yes / No')
                                    ->maxLength(255),
                            ]),
                    ]),

                Section::make('Pricing & Charges')
                    ->description('App aur website par price dikhane ke liye.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('km_charge')
                                    ->label('KM Charge')
                                    ->numeric()
                                    ->prefix('₹')
                                    ->placeholder('10'),

                                TextInput::make('driver_charge')
                                    ->label('Driver Charge')
                                    ->numeric()
                                    ->prefix('₹')
                                    ->placeholder('300'),

                                TextInput::make('security')
                                    ->label('Security Deposit')
                                    ->numeric()
                                    ->prefix('₹')
                                    ->placeholder('5000'),
                            ]),
                    ]),

                Section::make('Options')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Toggle::make('in_return')
                                    ->label('Available in Return Trip')
                                    ->default(true),

                                Toggle::make('pet_friendly')
                                    ->label('Pet Friendly')
                                    ->default(false),

                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Image')
                    ->square(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('model')
                    ->label('Model')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('passanger_capacity')
                    ->label('Seats')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('km_charge')
                    ->label('₹/KM')
                    ->sortable(),

                Tables\Columns\TextColumn::make('security')
                    ->label('Security')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('in_return')
                    ->label('Return')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('pet_friendly')
                    ->label('Pet')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
