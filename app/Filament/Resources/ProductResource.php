<?php

namespace App\Filament\Resources;

use App\Filament\Imports\ProductImporter;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers\LinksRelationManager;
use App\Forms\Components\DuraImageUpload;
use App\Forms\Components\DuraSeo;
use App\Models\Category;
use App\Models\Product;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ImportAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Rides';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                    ->schema([
                        Section::make('Basic Information')
                            ->description('Ride, route, self-drive ya tour product ki basic details.')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Ride / Product Name')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, Set $set) {
                                                $set('slug', Str::slug($state));
                                            }),

                                        TextInput::make('slug')
                                            ->label('Slug')
                                            ->required()
                                            ->maxLength(255)
                                            ->disabled()
                                            ->dehydrated()
                                            ->unique(Product::class, 'slug', ignoreRecord: true),
                                    ]),

                                RichEditor::make('description')
                                    ->label('Description')
                                    ->columnSpanFull()
                                    ->fileAttachmentsDirectory('products')
                                    ->toolbarButtons([
                                        'attachFiles',
                                        'blockquote',
                                        'bold',
                                        'bulletList',
                                        'codeBlock',
                                        'h1',
                                        'h2',
                                        'h3',
                                        'italic',
                                        'link',
                                        'orderedList',
                                        'redo',
                                        'strike',
                                        'underline',
                                        'undo',
                                    ]),
                            ]),

                        Section::make('Images')
                            ->description('Website aur app dono me same gallery images use hongi. Pehli image primary image hogi.')
                            ->schema([
                                DuraImageUpload::make('images', 'products')
                                    ->multiple()
                                    ->maxFiles(8)
                                    ->reorderable()
                                    ->helperText('First image primary image hogi. JPG, PNG, WEBP • Max 4 MB per image.'),
                            ]),

                        Section::make('Price Variants')
                            ->description('Agar ek ride me multiple cab category prices hain to yahan add karein.')
                            ->schema([
                                Repeater::make('prices')
                                    ->relationship()
                                    ->schema([
                                        Select::make('category_id')
                                            ->label('Category')
                                            ->options(fn () => Category::query()->pluck('name', 'id'))
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->columnSpan(['md' => 5]),

                                        TextInput::make('price')
                                            ->label('Sale Price')
                                            ->numeric()
                                            ->default(1)
                                            ->prefix('₹')
                                            ->required()
                                            ->columnSpan(['md' => 3]),

                                        TextInput::make('max_price')
                                            ->label('Max Price')
                                            ->numeric()
                                            ->default(1)
                                            ->prefix('₹')
                                            ->required()
                                            ->columnSpan(['md' => 3]),
                                    ])
                                    ->columns(11)
                                    ->defaultItems(1)
                                    ->columnSpanFull(),
                            ]),

                        DuraSeo::make(),
                    ])
                    ->columnSpan(2),

                Group::make()
                    ->schema([
                        Section::make('Associations')
                            ->description('Booking route, category aur ride type.')
                            ->schema([
                                Select::make('brand_id')
                                    ->label('Booking From')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->relationship('brand', 'name'),

                                Select::make('booking_to')
                                    ->label('Booking To')
                                    ->searchable()
                                    ->preload()
                                    ->relationship('brand', 'name')
                                    ->visible(fn ($get) => in_array($get('ride_type'), ['one_way', 'return'], true)),

                                Select::make('category_id')
                                    ->label('Cab Category')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->relationship('category', 'name'),

                                Select::make('ride_type')
                                    ->label('Ride Type')
                                    ->options([
                                        'one_way' => 'One Way',
                                        'return' => 'Return',
                                        'local' => 'Local',
                                        'self_drive' => 'Self Drive',
                                        'tour' => 'Tour Package',
                                        'airport' => 'Airport Transfer',
                                    ])
                                    ->required()
                                    ->live(),

                                Select::make('plan')
                                    ->label('Local Plan')
                                    ->options([
                                        '4 Hour / 40 Km' => '4 Hour / 40 Km',
                                        '8 Hour / 80 Km' => '8 Hour / 80 Km',
                                        '12 Hour / 120 Km' => '12 Hour / 120 Km',
                                        '24 Hour' => '24 Hour',
                                    ])
                                    ->visible(fn ($get) => in_array($get('ride_type'), ['local', 'self_drive'], true)),
                            ]),

                        Section::make('Main Pricing')
                            ->description('Primary price jo website/app card par dikhega.')
                            ->schema([
                                TextInput::make('price')
                                    ->label('Sale Price')
                                    ->numeric()
                                    ->required()
                                    ->prefix('₹'),

                                TextInput::make('max_price')
                                    ->label('Max / Strike Price')
                                    ->numeric()
                                    ->required()
                                    ->prefix('₹'),
                            ]),

                        Section::make('Limits')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('km_limit')
                                            ->label('KM Limit')
                                            ->numeric()
                                            ->required(),

                                        TextInput::make('hr_limit')
                                            ->label('Hour Limit')
                                            ->numeric()
                                            ->required(),
                                    ]),
                            ]),

                        Section::make('Extra Charges')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('extra_km_charge')
                                            ->label('Extra KM Charge')
                                            ->numeric()
                                            ->required()
                                            ->prefix('₹'),

                                        TextInput::make('extra_hr_charge')
                                            ->label('Extra Hour Charge')
                                            ->numeric()
                                            ->required()
                                            ->prefix('₹'),

                                        TextInput::make('toll_tax')
                                            ->label('Toll Tax')
                                            ->numeric()
                                            ->prefix('₹'),

                                        TextInput::make('border_tax')
                                            ->label('Border Tax')
                                            ->numeric()
                                            ->prefix('₹'),

                                        TextInput::make('driver_allowances')
                                            ->label('Driver Allowance')
                                            ->numeric()
                                            ->prefix('₹'),
                                    ]),
                            ]),

                        Section::make('Visibility')
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),

                                Toggle::make('is_featured')
                                    ->label('Popular / Featured')
                                    ->default(false),

                                Toggle::make('in_stock')
                                    ->label('Available')
                                    ->default(true),

                                Toggle::make('on_sale')
                                    ->label('SEO / Highlight')
                                    ->default(false),
                            ]),
                    ])
                    ->columnSpan(1),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('primary_image')
                    ->label('Image')
                    ->square(),

                TextColumn::make('name')
                    ->label('Ride')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('ride_type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),

                TextColumn::make('price')
                    ->label('Price')
                    ->sortable()
                    ->money('INR'),

                TextColumn::make('max_price')
                    ->label('Max')
                    ->sortable()
                    ->money('INR')
                    ->toggleable(),

                IconColumn::make('is_featured')
                    ->label('Popular')
                    ->boolean(),

                IconColumn::make('on_sale')
                    ->label('SEO')
                    ->boolean()
                    ->toggleable(),

                IconColumn::make('in_stock')
                    ->label('Available')
                    ->boolean(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->sortable()
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->sortable()
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->relationship('category', 'name'),

                SelectFilter::make('ride_type')
                    ->options([
                        'one_way' => 'One Way',
                        'return' => 'Return',
                        'local' => 'Local',
                        'self_drive' => 'Self Drive',
                        'tour' => 'Tour Package',
                        'airport' => 'Airport Transfer',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->headerActions([
                ImportAction::make()
                    ->importer(ProductImporter::class)
                    ->options([
                        'updateExisting' => true,
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
        return [
            LinksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
