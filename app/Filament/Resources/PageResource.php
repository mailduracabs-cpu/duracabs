<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Forms\Components\ContentWriter;
use App\Models\Page;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationGroup = 'Website Management';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Pages';

    protected static ?string $modelLabel = 'Page';

    protected static ?string $pluralModelLabel = 'Pages';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Page Details')
                    ->description(
                        'Page ka naam, URL, city, main image aur publishing details manage karein.',
                    )
                    ->icon('heroicon-o-information-circle')
                    ->columns([
                        'default' => 1,
                        'lg' => 3,
                    ])
                    ->schema([
                        TextInput::make('name')
                            ->label('Page Name')
                            ->placeholder(
                                'Example: Agra to Delhi Taxi Service',
                            )
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(
                                function (
                                    ?string $state,
                                    Set $set,
                                    Get $get,
                                    string $operation,
                                ): void {
                                    if (
                                        $operation === 'edit'
                                        && filled($get('slug'))
                                    ) {
                                        return;
                                    }

                                    $name = trim((string) $state);

                                    if ($name === '') {
                                        return;
                                    }

                                    $set('slug', Str::slug($name));

                                    if (blank($get('meta_title'))) {
                                        $set(
                                            'meta_title',
                                            Str::limit($name, 60, ''),
                                        );
                                    }

                                    if (blank($get('focus_keyword'))) {
                                        $set(
                                            'focus_keyword',
                                            $name,
                                        );
                                    }
                                },
                            ),

                        TextInput::make('slug')
                            ->label('Slug')
                            ->placeholder(
                                'agra-to-delhi-taxi-service',
                            )
                            ->required()
                            ->maxLength(255)
                            ->unique(
                                table: Page::class,
                                column: 'slug',
                                ignoreRecord: true,
                            )
                            ->dehydrateStateUsing(
                                fn (?string $state): string => Str::slug(
                                    (string) $state,
                                ),
                            )
                            ->helperText(
                                'Page URL me ye slug use hoga.',
                            ),

                        Select::make('content_type')
                            ->label('Page Type')
                            ->options([
                                'page' => 'Normal Content Page',
                                'landing_page' => 'Landing Page',
                                'route_page' => 'Taxi Route Page',
                                'city_page' => 'City Page',
                                'service_page' => 'Self Drive Page',
                                'tour_package' => 'Tour Package',
                                'blog' => 'Blog Article',
                                'product' => 'Product',
                            ])
                            ->default('page')
                            ->required()
                            ->native(false)
                            ->live()
                            ->helperText(
                                'Self Drive ka search page banane ke liye Self Drive Page select karein.',
                            ),

                        Select::make('brand_id')
                            ->label('Select City')
                            ->relationship('brand', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false),

                        FileUpload::make('image')
                            ->label('Featured Image')
                            ->image()
                            ->disk('public')
                            ->directory('pages')
                            ->visibility('public')
                            ->imageEditor()
                            ->downloadable()
                            ->openable()
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 2,
                            ]),

                        DateTimePicker::make('published_at')
                            ->label('Publish Date & Time')
                            ->seconds(false)
                            ->native(false)
                            ->helperText(
                                'Blank hone par page immediately available maana jayega.',
                            ),
                    ]),

                Section::make('Self Drive Page Settings')
                    ->description(
                        'Self Drive search page ki heading, search options, rental modes aur frontend sections control karein.',
                    )
                    ->icon('heroicon-o-truck')
                    ->visible(
                        fn (Get $get): bool => $get('content_type') === 'service_page',
                    )
                    ->statePath('self_drive_settings')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ])
                    ->schema([
                        Toggle::make('page_enabled')
                            ->label('Enable Self Drive Page')
                            ->default(true)
                            ->helperText('Off karne par Self Drive special layout load nahi hoga.'),

                        Toggle::make('search_enabled')
                            ->label('Enable Search Engine')
                            ->default(true),

                        Toggle::make('delivery_enabled')
                            ->label('Enable Delivery & Pickup')
                            ->default(true),

                        TextInput::make('hero_title')
                            ->label('Hero Heading')
                            ->placeholder('Self Drive Cars in Your City')
                            ->maxLength(120)
                            ->columnSpan([
                                'default' => 1,
                                'md' => 2,
                            ]),

                        Textarea::make('hero_subtitle')
                            ->label('Hero Subheading')
                            ->placeholder('Choose your car, select date and time, and drive anywhere.')
                            ->rows(3)
                            ->maxLength(300)
                            ->columnSpan([
                                'default' => 1,
                                'md' => 2,
                                'xl' => 3,
                            ]),

                        FileUpload::make('mobile_banner')
                            ->label('Mobile Banner')
                            ->image()
                            ->disk('public')
                            ->directory('pages/self-drive')
                            ->visibility('public')
                            ->imageEditor()
                            ->downloadable()
                            ->openable(),

                        TextInput::make('search_title')
                            ->label('Search Box Heading')
                            ->placeholder('Find your perfect self drive car')
                            ->maxLength(120),

                        TextInput::make('pickup_placeholder')
                            ->label('Pickup Location Placeholder')
                            ->placeholder('Enter pickup location')
                            ->maxLength(120),

                        TextInput::make('search_button_text')
                            ->label('Search Button Text')
                            ->placeholder('Search Cars')
                            ->maxLength(40),

                        CheckboxList::make('rental_modes')
                            ->label('Rental Modes')
                            ->options([
                                'hourly' => 'Hourly',
                                'daily' => 'Daily',
                                'weekly' => 'Weekly',
                                'monthly' => 'Monthly',
                            ])
                            ->default([
                                'hourly',
                                'daily',
                                'weekly',
                                'monthly',
                            ])
                            ->columns(2)
                            ->bulkToggleable()
                            ->columnSpan([
                                'default' => 1,
                                'md' => 2,
                            ]),

                        Select::make('default_rental_mode')
                            ->label('Default Rental Mode')
                            ->options([
                                'hourly' => 'Hourly',
                                'daily' => 'Daily',
                                'weekly' => 'Weekly',
                                'monthly' => 'Monthly',
                            ])
                            ->default('daily')
                            ->native(false),

                        TextInput::make('weekly_discount')
                            ->label('Weekly Discount')
                            ->numeric()
                            ->suffix('%')
                            ->default(20)
                            ->minValue(0)
                            ->maxValue(100),

                        TextInput::make('monthly_discount')
                            ->label('Monthly Discount')
                            ->numeric()
                            ->suffix('%')
                            ->default(30)
                            ->minValue(0)
                            ->maxValue(100),

                        TextInput::make('service_radius_km')
                            ->label('Service Radius')
                            ->numeric()
                            ->suffix('km')
                            ->default(40)
                            ->minValue(1)
                            ->maxValue(500),

                        Toggle::make('show_categories')
                            ->label('Show Categories')
                            ->default(true),

                        Toggle::make('show_featured_vehicles')
                            ->label('Show Featured Cars')
                            ->default(true),

                        Toggle::make('show_offers')
                            ->label('Show Offers')
                            ->default(true),

                        Toggle::make('show_faqs')
                            ->label('Show FAQs')
                            ->default(true),
                    ]),

                ContentWriter::make('content_writer')
                    ->contentField('description')
                    ->columnSpanFull(),

                Section::make('Legacy Page Links')
                    ->description(
                        'Website ke existing frontend ke liye purane links aur fare products. In fields ko abhi preserve kiya gaya hai.',
                    )
                    ->icon('heroicon-o-link')
                    ->collapsed()
                    ->collapsible()
                    ->schema([
                        Repeater::make('links')
                            ->label('Existing Links')
                            ->addActionLabel('Add Link')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(
                                fn (array $state): ?string => filled(
                                    $state['name'] ?? null,
                                )
                                    ? (string) $state['name']
                                    : 'New Link',
                            )
                            ->schema([
                                TextInput::make('name')
                                    ->label('Link Name')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('url')
                                    ->label('URL')
                                    ->required()
                                    ->maxLength(1000),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),

                        Repeater::make('link_products')
                            ->label('Existing Linked Products / Fares')
                            ->addActionLabel('Add Linked Product')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(
                                fn (array $state): ?string => filled(
                                    $state['name'] ?? null,
                                )
                                    ? (string) $state['name']
                                    : 'New Product',
                            )
                            ->schema([
                                TextInput::make('name')
                                    ->label('Name')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('oneway')
                                    ->label('One Way Fare')
                                    ->required()
                                    ->numeric()
                                    ->prefix('₹')
                                    ->minValue(0),

                                TextInput::make('perKM')
                                    ->label('Per KM')
                                    ->required()
                                    ->numeric()
                                    ->prefix('₹')
                                    ->minValue(0),
                            ])
                            ->columns(3)
                            ->columnSpanFull(),
                    ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                ImageColumn::make('image')
                    ->label('Image')
                    ->disk('public')
                    ->square()
                    ->defaultImageUrl(
                        url('/images/placeholder.png'),
                    ),

                TextColumn::make('name')
                    ->label('Page Name')
                    ->searchable()
                    ->sortable()
                    ->description(
                        fn (Page $record): string => (string) $record->slug,
                    )
                    ->wrap(),

                TextColumn::make('brand.name')
                    ->label('City')
                    ->placeholder('All Cities')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('content_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string => match ($state) {
                            'landing_page' => 'Landing Page',
                            'route_page' => 'Route Page',
                            'city_page' => 'City Page',
                            'service_page' => 'Self Drive Page',
                            'tour_package' => 'Tour Package',
                            'blog' => 'Blog',
                            'product' => 'Product',
                            default => 'Page',
                        },
                    )
                    ->color(
                        fn (?string $state): string => match ($state) {
                            'route_page' => 'success',
                            'landing_page' => 'warning',
                            'city_page' => 'info',
                            'blog' => 'primary',
                            default => 'gray',
                        },
                    )
                    ->sortable(),

                TextColumn::make('seo_score')
                    ->label('SEO')
                    ->badge()
                    ->suffix('/100')
                    ->color(
                        fn (?int $state): string => match (true) {
                            (int) $state >= 80 => 'success',
                            (int) $state >= 60 => 'info',
                            (int) $state >= 40 => 'warning',
                            default => 'danger',
                        },
                    )
                    ->sortable(),

                TextColumn::make('readability_score')
                    ->label('Readability')
                    ->badge()
                    ->suffix('/100')
                    ->color(
                        fn (?int $state): string => match (true) {
                            (int) $state >= 80 => 'success',
                            (int) $state >= 60 => 'info',
                            (int) $state >= 40 => 'warning',
                            default => 'danger',
                        },
                    )
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('focus_keyword')
                    ->label('Focus Keyword')
                    ->searchable()
                    ->placeholder('Not set')
                    ->limit(35)
                    ->toggleable(),

                TextColumn::make('robots')
                    ->label('Robots')
                    ->badge()
                    ->color(
                        fn (?string $state): string => Str::contains(
                            strtolower((string) $state),
                            'noindex',
                        )
                            ? 'danger'
                            : 'success',
                    )
                    ->toggleable(
                        isToggledHiddenByDefault: true,
                    ),

                TextColumn::make('published_at')
                    ->label('Published')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('Immediate')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('content_type')
                    ->label('Content Type')
                    ->options([
                        'page' => 'Page',
                        'product' => 'Product',
                        'blog' => 'Blog Article',
                        'landing_page' => 'Landing Page',
                        'route_page' => 'Taxi Route Page',
                        'city_page' => 'City Page',
                        'service_page' => 'Self Drive Page',
                        'tour_package' => 'Tour Package',
                    ]),

                SelectFilter::make('brand_id')
                    ->label('City')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('robots')
                    ->label('Indexing')
                    ->options([
                        'index,follow' => 'Index, Follow',
                        'index,nofollow' => 'Index, No Follow',
                        'noindex,follow' => 'No Index, Follow',
                        'noindex,nofollow' => 'No Index, No Follow',
                    ]),
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
            ->emptyStateHeading('No pages found')
            ->emptyStateDescription(
                'Create your first SEO optimized page.',
            )
            ->emptyStateIcon('heroicon-o-document-text');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}