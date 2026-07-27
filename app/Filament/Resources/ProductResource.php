<?php

namespace App\Filament\Resources;

use App\Filament\Imports\ProductImporter;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers\LinksRelationManager;
use App\Forms\Components\DuraImageUpload;
use App\Forms\Components\DuraSeo;
use App\Forms\Components\DuraSeoAiWriter;
use App\Models\Category;
use App\Models\Product;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
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

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationLabel = 'Content Writer';

    protected static ?string $modelLabel = 'Content';

    protected static ?string $pluralModelLabel = 'Content Writer';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                    ->schema([
                        Section::make('SEO Page')
                            ->compact()
                            ->schema([
                                Grid::make([
                                    'default' => 1,
                                    'md' => 3,
                                ])
                                    ->schema([
                                        Select::make('ride_type')
                                            ->label('Page Type')
                                            ->options([
                                                'one_way' => 'One Way',
                                                'return' => 'Round Trip',
                                                'local' => 'Local Package',
                                                'bike_rental' => 'Bike Rental',
                                                'self_drive' => 'Self Drive',
                                            ])
                                            ->default('one_way')
                                            ->required()
                                            ->live(),

                                        TextInput::make('name')
                                            ->label('Page Name')
                                            ->placeholder('Agra to Delhi Cab')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(
                                                function (
                                                    string $operation,
                                                    ?string $state,
                                                    Set $set,
                                                ): void {
                                                    // Existing URLs are protected.
                                                    // Generate the slug only while creating a new record.
                                                    if ($operation !== 'create') {
                                                        return;
                                                    }

                                                    $set(
                                                        'slug',
                                                        Str::slug(
                                                            (string) $state,
                                                        ),
                                                    );
                                                },
                                            ),

                                        TextInput::make('slug')
                                            ->label('Slug')
                                            ->helperText('Existing slug is locked to protect old URLs and SEO.')
                                            ->required()
                                            ->maxLength(255)
                                            ->disabled(
                                                fn (string $operation): bool => $operation === 'edit',
                                            )
                                            ->dehydrated()
                                            ->unique(
                                                Product::class,
                                                'slug',
                                                ignoreRecord: true,
                                            ),
                                    ]),

                                Grid::make([
                                    'default' => 1,
                                    'md' => 3,
                                ])
                                    ->schema([
                                        Select::make('brand_id')
                                            ->label(
                                                fn (Get $get): string => match (
                                                    $get('ride_type')
                                                ) {
                                                    'one_way',
                                                    'return' => 'Start City',
                                                    default => 'City',
                                                },
                                            )
                                            ->relationship('brand', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required(),

                                        Select::make('booking_to')
                                            ->label('Destination City')
                                            ->relationship('brand', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->visible(
                                                fn (Get $get): bool => $get(
                                                    'ride_type',
                                                ) === 'one_way',
                                            )
                                            ->required(
                                                fn (Get $get): bool => $get(
                                                    'ride_type',
                                                ) === 'one_way',
                                            ),

                                        Select::make('plan')
                                            ->label('Local Package')
                                            ->options([
                                                '4 Hours / 40 KM' => '4 Hours / 40 KM',
                                                '4 Hours / 80 KM' => '4 Hours / 80 KM',
                                                '8 Hours / 80 KM' => '8 Hours / 80 KM',
                                                '12 Hours / 120 KM' => '12 Hours / 120 KM',
                                            ])
                                            ->visible(
                                                fn (Get $get): bool => $get(
                                                    'ride_type',
                                                ) === 'local',
                                            )
                                            ->required(
                                                fn (Get $get): bool => $get(
                                                    'ride_type',
                                                ) === 'local',
                                            ),
                                    ]),
                            ]),

                        Section::make('Content')
                            ->compact()
                            ->schema([
                                RichEditor::make('description')
                                    ->label('Page Content')
                                    ->columnSpanFull()
                                    ->fileAttachmentsDirectory('products')
                                    ->live(debounce: 1000)
                                    ->toolbarButtons([
                                        'bold',
                                        'italic',
                                        'underline',
                                        'link',
                                        'h2',
                                        'h3',
                                        'bulletList',
                                        'orderedList',
                                        'blockquote',
                                        'undo',
                                        'redo',
                                    ]),
                            ]),

                        DuraSeoAiWriter::make()
                            ->collapsed(),

                        DuraSeo::make()
                            ->collapsed(),
                    ])
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 2,
                    ]),

                Group::make()
                    ->schema([
                        Section::make('Image')
                            ->compact()
                            ->schema([
                                DuraImageUpload::make(
                                    'images',
                                    'products',
                                )
                                    ->multiple()
                                    ->maxFiles(3)
                                    ->reorderable()
                                    ->imagePreviewHeight('90')
                                    ->panelLayout('compact')
                                    ->helperText(
                                        'Small banner/gallery preview. First image is primary.',
                                    ),
                            ]),

                        Section::make('Publishing')
                            ->compact()
                            ->columns(2)
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),

                                Toggle::make('is_featured')
                                    ->label('Featured')
                                    ->default(false),

                                Toggle::make('in_stock')
                                    ->label('Bookable')
                                    ->default(true),

                                Toggle::make('on_sale')
                                    ->label('SEO Highlight')
                                    ->default(true),
                            ]),

                        Section::make('Legacy Fallback')
                            ->description(
                                'Only used by old frontend code until dynamic fare/vehicle loading is connected.',
                            )
                            ->collapsed()
                            ->compact()
                            ->schema([
                                Select::make('category_id')
                                    ->label('Fallback Cab Category')
                                    ->options(
                                        fn () => Category::query()
                                            ->pluck('name', 'id'),
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('price')
                                            ->label('Fallback Price')
                                            ->numeric()
                                            ->prefix('₹')
                                            ->default(1)
                                            ->required(),

                                        TextInput::make('max_price')
                                            ->label('Fallback Max')
                                            ->numeric()
                                            ->prefix('₹')
                                            ->default(1)
                                            ->required(),
                                    ]),
                            ]),

                        Hidden::make('km_limit')
                            ->default(0),

                        Hidden::make('hr_limit')
                            ->default(0),

                        Hidden::make('extra_km_charge')
                            ->default(0),

                        Hidden::make('extra_hr_charge')
                            ->default(0),

                        Hidden::make('toll_tax')
                            ->default(0),

                        Hidden::make('border_tax')
                            ->default(0),

                        Hidden::make('driver_allowances')
                            ->default(0),
                    ])
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 1,
                    ]),
            ])
            ->columns([
                'default' => 1,
                'lg' => 3,
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                ImageColumn::make('primary_image')
                    ->label('')
                    ->height(38)
                    ->width(56),

                TextColumn::make('name')
                    ->label('SEO Page')
                    ->description(
                        fn (Product $record): string => '/'
                            . ltrim(
                                (string) $record->slug,
                                '/',
                            ),
                    )
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('ride_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string => match ($state) {
                            'one_way' => 'One Way',
                            'return' => 'Round Trip',
                            'local' => 'Local',
                            'bike_rental' => 'Bike Rental',
                            'self_drive' => 'Self Drive',
                            default => Str::headline((string) $state),
                        },
                    )
                    ->color(
                        fn (?string $state): string => match ($state) {
                            'one_way' => 'primary',
                            'return' => 'warning',
                            'local' => 'success',
                            'bike_rental' => 'info',
                            'self_drive' => 'gray',
                            default => 'gray',
                        },
                    )
                    ->sortable(),

                TextColumn::make('brand.name')
                    ->label('City / From')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('bookingTo.name')
                    ->label('To')
                    ->placeholder('—')
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Live')
                    ->boolean(),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('ride_type')
                    ->label('Page Type')
                    ->options([
                        'one_way' => 'One Way',
                        'return' => 'Round Trip',
                        'local' => 'Local Package',
                        'bike_rental' => 'Bike Rental',
                        'self_drive' => 'Self Drive',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
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