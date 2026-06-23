<?php

namespace App\Filament\Resources;

use App\Filament\Imports\ProductImporter;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Filament\Resources\ProductResource\RelationManagers\LinksRelationManager;
use App\Models\Category;
use App\Models\Product;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Tables\Actions\ImportAction;
use Filament\Actions\Imports\Jobs\ImportCsv;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Mohamedsabil83\FilamentFormsTinyeditor\Components\TinyEditor;


class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?int $navigationSort = 6;
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Rides';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()->schema([
                    Section::make('Product Information')->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, Set $set) {

                                $set('slug', Str::slug($state));
                            })
                        ,



                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->disabled()
                            ->dehydrated()
                            ->unique(Product::class, 'slug', ignoreRecord: true),

                        TextInput::make('meta_title')
                            ->maxLength(255),
                        TextInput::make('meta_description')
                            ->maxLength(255),

                        Select::make('brand_id')
                            ->required()
                            ->label('Booking From')
                            ->searchable()
                            ->preload()
                            ->relationship('brand', 'name'),
                        Select::make('booking_to')
                            ->required()
                            ->label('Booking To')
                            ->searchable()
                            ->preload()
                            ->relationship('brand', 'name')
                            ->hidden(fn(string $operation): bool => $operation === 'local'),
                         RichEditor::make('description')
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

                    Section::make('Images')->schema([
                        FileUpload::make('images')
                            ->multiple()
                            ->directory('products')
                            ->maxFiles(5)
                            ->reorderable()
                    ]),

                    Repeater::make('prices')
                        ->relationship()
                        ->schema([
                            select::make('category_id')
                                ->label('Category')
                                ->options(Category::query()->pluck('name','id'))
                                ->required()
                                ->reactive()
                                ->columnSpan(['md'=>5]),
                            TextInput::make('price')
                                ->numeric()
                                ->default(1)
                                ->columnSpan(['md'=>2])
                                ->required(),
                             TextInput::make('max_price')
                                ->numeric()
                                ->default(1)
                                ->columnSpan(['md'=>3])
                                ->required()
                        ])
                        ->defaultItems(1)
                        ->columnSpan('full')
                ])->columnSpan(2),
                Group::make()->schema([
                    Section::make('Associations')->schema([
                        TextInput::make('price')
                            ->numeric()
                            ->required()
                            ->prefix('INR'),
                        TextInput::make('max_price')
                            ->numeric()
                            ->required()
                            ->prefix('INR'),

                        Select::make('category_id')
                            ->required()
                            ->label('Cab Category')
                            ->searchable()
                            ->preload()
                            ->relationship('category', 'name'),
                        Select::make('ride_type')
                            ->options([
                                'one_way' => 'One Way',
                                'return' => 'Return',
                                'local' => 'Local',
                                'self_drive' => 'Self Drive',
                            ])
                            ->required()
                            ->live(),
                        Select::make('plan')
                            ->label('Plan')
                            ->options([
                                '4 Hour / 40 Km' => '4 Hour / 40 Km',
                                '8 Hour / 80 Km' => "8 Hour / 80 Km",
                                '12 Hour / 120 Km' => '12 Hour / 120 Km',
                            ]),

                    ]),
                    Section::make('Limit')->schema([
                        TextInput::make('km_limit')
                            ->numeric()
                            ->label('KM Limit')
                            ->required(),
                        TextInput::make('hr_limit')
                            ->label('Hour Limit')
                            ->numeric()
                            ->required(),

                    ]),

                    Section::make('Extra Charge')->schema([
                        TextInput::make('extra_km_charge')
                            ->numeric()
                            ->label('Extra KM Charge')
                            ->required()
                            ->prefix('INR'),
                        TextInput::make('extra_hr_charge')
                            ->label('Extra Hour Charge')
                            ->numeric()
                            ->required()
                            ->prefix('INR'),

                    ]),
                    Section::make('Tax & Allowances')->schema([
                        TextInput::make('toll_tax')
                            ->numeric()
                            ->label('Toll Tax')
                            ->prefix('INR'),
                        TextInput::make('border_tax')
                            ->label('Border Tax')
                            ->numeric()
                            ->prefix('INR'),
                        TextInput::make(name: 'driver_allowances')
                            ->label('Driver Allowances')
                            ->numeric()
                            ->prefix('INR'),
                        Toggle::make('is_featured')
                            ->label('Popular')
                            ->required()
                            ->default(false),
                        Toggle::make('on_sale')
                            ->label('SEO')
                            ->required()
                            ->default(false)

                    ])
                ])->columnSpan(1)
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                
                TextColumn::make('price')
                    ->sortable()
                    ->money('INR'),
                IconColumn::make('is_featured')
                    ->boolean(),
                IconColumn::make('on_sale')
                    ->boolean(),
                IconColumn::make('in_stock')
                    ->boolean(),
                IconColumn::make('is_active')
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

            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                ])
            ])
            ->headerActions([
                ImportAction::make()
                    ->importer(ProductImporter::class)
                    ->options([
                        'updateExisting' => true,
                    ])
                    ->job(ImportCsv::class)
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
            LinksRelationManager::class
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
