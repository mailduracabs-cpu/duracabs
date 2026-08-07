<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Forms\Components\ContentWriter;
use App\Models\Page;
use App\SEO\Services\SeoAnalysisService;
use Filament\Notifications\Notification;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
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
use Illuminate\Support\Facades\Cache;
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
                                'Existing slug ko edit karne se ranked URL affect ho sakta hai.',
                            )
                            ->live(debounce: 500)
                            ->afterStateUpdated(
                                function (
                                    ?string $state,
                                    Get $get,
                                    Set $set,
                                ): void {
                                    $set(
                                        'seo_public_url',
                                        static::previewPageUrl(
                                            (string) $get('content_type'),
                                            (string) $state,
                                        ),
                                    );
                                },
                            ),

                        Select::make('content_type')
                            ->label('URL Type / Page Type')
                            ->options([
                                'page' => 'Pages — Normal Content Page',
                                'landing_page' => 'Pages — Landing Page',
                                'route_page' => 'Pages — Taxi Route Page',
                                'city_page' => 'Pages — City Page',
                                'service_page' => 'Pages — Self Drive Page',
                                'tour_package' => 'Tours — Tour Package',
                                'blog' => 'Blog — Blog Article',
                                'product' => 'Pages — Product',
                            ])
                            ->default('page')
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(
                                function (
                                    ?string $state,
                                    Get $get,
                                    Set $set,
                                ): void {
                                    $set(
                                        'seo_public_url',
                                        static::previewPageUrl(
                                            (string) $state,
                                            (string) $get('slug'),
                                        ),
                                    );
                                },
                            )
                            ->helperText(
                                'Pages types se /pages/, Blog se /blog/, aur Tours se /tour/ URL banega.',
                            ),

                        \Filament\Forms\Components\Placeholder::make('public_url_preview')
                            ->label('Public URL')
                            ->content(
                                fn (Get $get): string =>
                                    static::previewPageUrl(
                                        (string) $get('content_type'),
                                        (string) $get('slug'),
                                    ),
                            )
                            ->helperText(
                                'Existing ranked slug automatically change nahi hoga. Page type change karne par public URL prefix badal sakta hai.',
                            )
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 3,
                            ]),

                        Hidden::make('seo_public_url')
                            ->default(
                                fn (?Page $record): string =>
                                    $record?->public_url
                                    ?? static::previewPageUrl(
                                        'page',
                                        '',
                                    ),
                            )
                            ->dehydrated(false),

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

                Section::make('SEO URL & Canonical')
                    ->description(
                        'URL type select karein, live URL dekhein aur canonical ko auto ya custom mode me manage karein.',
                    )
                    ->icon('heroicon-o-link')
                    ->columns([
                        'default' => 1,
                        'lg' => 2,
                    ])
                    ->schema([
                        \Filament\Forms\Components\Placeholder::make('seo_url_preview')
                            ->label('Live Public URL')
                            ->content(
                                fn (Get $get): string =>
                                    static::previewPageUrl(
                                        (string) $get('content_type'),
                                        (string) $get('slug'),
                                    ),
                            )
                            ->helperText('Ye URL Page Type aur Slug ke hisaab se automatically banta hai.')
                            ->columnSpanFull(),

                        Toggle::make('auto_canonical')
                            ->label('Auto Generate Canonical')
                            ->default(true)
                            ->dehydrated(false)
                            ->live()
                            ->afterStateHydrated(
                                function (Toggle $component, ?Page $record): void {
                                    $component->state(blank($record?->canonical_url));
                                },
                            )
                            ->afterStateUpdated(
                                function (bool $state, Set $set): void {
                                    if ($state) {
                                        $set('canonical_url', null);
                                    }
                                },
                            )
                            ->helperText('ON rakhen to current public URL canonical hoga. OFF par custom canonical enter kar sakte hain.'),

                        \Filament\Forms\Components\Placeholder::make('canonical_preview')
                            ->label('Canonical Preview')
                            ->content(
                                function (Get $get): string {
                                    if ((bool) $get('auto_canonical')) {
                                        return static::previewPageUrl(
                                            (string) $get('content_type'),
                                            (string) $get('slug'),
                                        );
                                    }

                                    return filled($get('canonical_url'))
                                        ? (string) $get('canonical_url')
                                        : 'Custom canonical URL enter karein';
                                },
                            ),

                        TextInput::make('canonical_url')
                            ->label('Custom Canonical URL')
                            ->placeholder('https://www.duracabs.com/pages/example-page')
                            ->url()
                            ->maxLength(1000)
                            ->visible(fn (Get $get): bool => ! (bool) $get('auto_canonical'))
                            ->helperText('Sirf tab use karein jab canonical kisi doosre valid URL par point karna ho.'),

                        Select::make('robots')
                            ->label('Robots')
                            ->options([
                                'index,follow' => 'Index, Follow',
                                'index,nofollow' => 'Index, No Follow',
                                'noindex,follow' => 'No Index, Follow',
                                'noindex,nofollow' => 'No Index, No Follow',
                            ])
                            ->default('index,follow')
                            ->native(false)
                            ->helperText('Normal SEO pages ke liye Index, Follow recommended hai.'),
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


                Section::make('Schema Manager')
                    ->description(
                        'Existing Page model ke Primary, FAQ, Breadcrumb aur Custom JSON-LD fields ko manage karein. Blank fields automatic schema ko use karenge.',
                    )
                    ->icon('heroicon-o-code-bracket')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Select::make('schema_type')
                            ->label('Primary Schema Type')
                            ->options([
                                'WebPage' => 'Web Page',
                                'AboutPage' => 'About Page',
                                'ContactPage' => 'Contact Page',
                                'CollectionPage' => 'Collection Page',
                                'Article' => 'Article',
                                'BlogPosting' => 'Blog Posting',
                                'Service' => 'Service',
                                'Product' => 'Product',
                                'TouristTrip' => 'Tourist Trip',
                            ])
                            ->default('WebPage')
                            ->required()
                            ->native(false)
                            ->helperText(
                                'Page type ke hisaab se select karein. FAQ aur Breadcrumb schemas alag se automatically output honge.',
                            ),

                        Repeater::make('faq_schema')
                            ->label('Page FAQs')
                            ->addActionLabel('Add FAQ')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(
                                fn (array $state): ?string =>
                                    filled($state['question'] ?? null)
                                        ? (string) $state['question']
                                        : 'New FAQ',
                            )
                            ->schema([
                                TextInput::make('question')
                                    ->label('Question')
                                    ->required()
                                    ->maxLength(500),

                                Textarea::make('answer')
                                    ->label('Answer')
                                    ->required()
                                    ->rows(4)
                                    ->columnSpanFull(),
                            ])
                            ->columns(1)
                            ->columnSpanFull()
                            ->helperText(
                                'FAQPage schema tabhi generate hoga jab question aur answer dono filled hon.',
                            ),

                        Repeater::make('breadcrumb_schema')
                            ->label('Custom Breadcrumbs')
                            ->addActionLabel('Add Breadcrumb')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(
                                fn (array $state): ?string =>
                                    filled($state['name'] ?? null)
                                        ? (string) $state['name']
                                        : 'New Breadcrumb',
                            )
                            ->schema([
                                TextInput::make('name')
                                    ->label('Name')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('url')
                                    ->label('Absolute URL')
                                    ->required()
                                    ->url()
                                    ->maxLength(1000),
                            ])
                            ->columns(2)
                            ->columnSpanFull()
                            ->helperText(
                                'Blank chhodne par Home → Current Page breadcrumb automatically generate hoga.',
                            ),

                        Textarea::make('custom_schema')
                            ->label('Custom JSON-LD')
                            ->rows(18)
                            ->extraAttributes([
                                'class' => 'font-mono text-sm',
                                'spellcheck' => 'false',
                            ])
                            ->formatStateUsing(
                                fn (mixed $state): string =>
                                    is_array($state) && $state !== []
                                        ? (string) json_encode(
                                            $state,
                                            JSON_UNESCAPED_SLASHES
                                            | JSON_UNESCAPED_UNICODE
                                            | JSON_PRETTY_PRINT,
                                        )
                                        : '',
                            )
                            ->dehydrateStateUsing(
                                function (?string $state): ?array {
                                    $state = trim((string) $state);

                                    if ($state === '') {
                                        return null;
                                    }

                                    $decoded = json_decode($state, true);

                                    return is_array($decoded)
                                        ? $decoded
                                        : null;
                                },
                            )
                            ->rules([
                                function (): \Closure {
                                    return function (
                                        string $attribute,
                                        mixed $value,
                                        \Closure $fail,
                                    ): void {
                                        $value = trim((string) $value);

                                        if ($value === '') {
                                            return;
                                        }

                                        json_decode($value, true);

                                        if (json_last_error() !== JSON_ERROR_NONE) {
                                            $fail(
                                                'Custom JSON-LD valid JSON hona chahiye.',
                                            );
                                        }
                                    };
                                },
                            ])
                            ->helperText(
                                'Optional. Sirf valid JSON object ya array paste karein. <script> tags paste na karein.',
                            )
                            ->columnSpanFull(),
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

                TextColumn::make('index_readiness')
                    ->label('Index Status')
                    ->badge()
                    ->getStateUsing(
                        fn (Page $record): string =>
                            static::seoIndexData($record)['status_label'],
                    )
                    ->color(
                        fn (Page $record): string =>
                            static::seoIndexData($record)['status_color'],
                    )
                    ->description(
                        fn (Page $record): string =>
                            static::seoIndexDescription($record),
                    ),

                TextColumn::make('sitemap_status')
                    ->label('Sitemap')
                    ->badge()
                    ->getStateUsing(
                        fn (Page $record): string =>
                            static::seoIndexData($record)['sitemap_eligible']
                                ? 'Eligible'
                                : 'Excluded',
                    )
                    ->color(
                        fn (Page $record): string =>
                            static::seoIndexData($record)['sitemap_eligible']
                                ? 'success'
                                : 'gray',
                    )
                    ->toggleable(),

                TextColumn::make('canonical_status')
                    ->label('Canonical')
                    ->badge()
                    ->getStateUsing(
                        function (Page $record): string {
                            $status = static::seoIndexData($record)['canonical_status'];

                            return match ($status) {
                                'self' => 'Valid',
                                'different' => 'Different',
                                default => 'Missing',
                            };
                        },
                    )
                    ->color(
                        function (Page $record): string {
                            $status = static::seoIndexData($record)['canonical_status'];

                            return match ($status) {
                                'self' => 'success',
                                'different' => 'warning',
                                default => 'danger',
                            };
                        },
                    )
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('google_status')
                    ->label('Google')
                    ->badge()
                    ->getStateUsing(fn (): string => 'Not Connected')
                    ->color('gray')
                    ->description('Search Console integration pending')
                    ->toggleable(isToggledHiddenByDefault: true),

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

                Tables\Filters\SelectFilter::make('indexing_status')
                    ->label('Index Readiness')
                    ->options([
                        'ready' => 'Index Ready',
                        'noindex' => 'Noindex',
                        'scheduled' => 'Not Published',
                        'needs_attention' => 'Needs Attention',
                    ])
                    ->query(
                        function ($query, array $data) {
                            return match ($data['value'] ?? null) {
                                'ready' => $query
                                    ->where(function ($builder): void {
                                        $builder
                                            ->whereNull('robots')
                                            ->orWhere('robots', 'not like', '%noindex%');
                                    })
                                    ->whereNotNull('slug')
                                    ->where('slug', '!=', '')
                                    ->whereNotNull('meta_title')
                                    ->where('meta_title', '!=', '')
                                    ->whereNotNull('description')
                                    ->where('description', '!=', '')
                                    ->where(function ($builder): void {
                                        $builder
                                            ->whereNull('published_at')
                                            ->orWhere('published_at', '<=', now());
                                    }),

                                'noindex' => $query->where('robots', 'like', '%noindex%'),

                                'scheduled' => $query
                                    ->whereNotNull('published_at')
                                    ->where('published_at', '>', now()),

                                'needs_attention' => $query
                                    ->where(function ($builder): void {
                                        $builder
                                            ->whereNull('slug')
                                            ->orWhere('slug', '')
                                            ->orWhereNull('meta_title')
                                            ->orWhere('meta_title', '')
                                            ->orWhereNull('description')
                                            ->orWhere('description', '');
                                    }),

                                default => $query,
                            };
                        },
                    ),

                Tables\Filters\SelectFilter::make('seo_score_range')
                    ->label('SEO Score')
                    ->options([
                        'excellent' => '80–100',
                        'good' => '60–79',
                        'needs_work' => '40–59',
                        'poor' => 'Below 40',
                    ])
                    ->query(
                        function ($query, array $data) {
                            return match ($data['value'] ?? null) {
                                'excellent' => $query->whereBetween('seo_score', [80, 100]),
                                'good' => $query->whereBetween('seo_score', [60, 79]),
                                'needs_work' => $query->whereBetween('seo_score', [40, 59]),
                                'poor' => $query->where('seo_score', '<', 40),
                                default => $query,
                            };
                        },
                    ),

                Tables\Filters\TernaryFilter::make('canonical_url')
                    ->label('Canonical URL')
                    ->placeholder('All')
                    ->trueLabel('Canonical Set')
                    ->falseLabel('Canonical Missing')
                    ->queries(
                        true: fn ($query) =>
                            $query->whereNotNull('canonical_url')
                                ->where('canonical_url', '!=', ''),
                        false: fn ($query) =>
                            $query->whereNull('canonical_url')
                                ->orWhere('canonical_url', ''),
                    ),

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
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('open_live_page')
                        ->label('Open Live Page')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(
                            fn (Page $record): string =>
                                static::seoIndexData($record)['page_url'],
                            shouldOpenInNewTab: true,
                        ),

                    Tables\Actions\Action::make('analyze_seo')
                        ->label('Analyze SEO')
                        ->icon('heroicon-o-magnifying-glass-circle')
                        ->color('info')
                        ->action(function (Page $record): void {
                            $analysis = app(SeoAnalysisService::class)
                                ->analyzeToArray(static::seoRecordData($record));

                            $indexing = $analysis['indexing'];

                            $body = sprintf(
                                'SEO Score: %d/100 | Index: %s | Sitemap: %s | Blockers: %d | Warnings: %d',
                                (int) $analysis['score'],
                                (string) $indexing['status_label'],
                                $indexing['sitemap_eligible'] ? 'Eligible' : 'Excluded',
                                count($indexing['blockers']),
                                count($indexing['warnings']),
                            );

                            Notification::make()
                                ->title('SEO analysis completed')
                                ->body($body)
                                ->color((string) $indexing['status_color'])
                                ->send();
                        }),

                    Tables\Actions\Action::make('open_search_console')
                        ->label('Open Search Console')
                        ->icon('heroicon-o-globe-alt')
                        ->url(
                            fn (Page $record): string =>
                                'https://search.google.com/search-console/inspect'
                                . '?resource_id='
                                . urlencode((string) config('app.url'))
                                . '&id='
                                . urlencode(static::seoIndexData($record)['page_url']),
                            shouldOpenInNewTab: true,
                        ),

                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('analyze_selected_seo')
                        ->label('Analyze Selected SEO')
                        ->icon('heroicon-o-chart-bar')
                        ->action(function ($records): void {
                            $service = app(SeoAnalysisService::class);

                            $ready = 0;
                            $needsAttention = 0;
                            $noindex = 0;

                            foreach ($records as $record) {
                                $analysis = $service->analyzeIndexReadiness(
                                    static::seoRecordData($record)
                                );

                                if ($analysis['status'] === 'noindex') {
                                    $noindex++;
                                    continue;
                                }

                                if ($analysis['index_ready']) {
                                    $ready++;
                                } else {
                                    $needsAttention++;
                                }
                            }

                            Notification::make()
                                ->title('SEO bulk analysis completed')
                                ->body(
                                    "Ready: {$ready} | Needs Attention: {$needsAttention} | Noindex: {$noindex}"
                                )
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('generate_missing_canonicals')
                        ->label('Generate Missing Canonicals')
                        ->icon('heroicon-o-link')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalDescription(
                            'Only blank canonical fields will be filled. Existing canonical URLs and slugs will remain unchanged.'
                        )
                        ->action(function ($records): void {
                            $generated = 0;
                            $preserved = 0;

                            foreach ($records as $record) {
                                if (filled($record->canonical_url)) {
                                    $preserved++;
                                    continue;
                                }

                                $record->forceFill([
                                    'canonical_url' => static::previewPageUrl(
                                        (string) $record->content_type,
                                        (string) $record->slug,
                                    ),
                                ])->save();

                                $generated++;
                            }

                            Cache::forget('seo.sitemap.xml.data');

                            Notification::make()
                                ->title('Canonical generation completed')
                                ->body(
                                    "Generated: {$generated} | Existing preserved: {$preserved}"
                                )
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('set_index_follow')
                        ->label('Set Index, Follow')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $updated = 0;

                            foreach ($records as $record) {
                                $record->forceFill([
                                    'robots' => 'index,follow',
                                ])->save();

                                $updated++;
                            }

                            Cache::forget('seo.sitemap.xml.data');

                            Notification::make()
                                ->title('Robots settings updated')
                                ->body("Updated {$updated} pages.")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('sync_sitemap')
                        ->label('Sync Sitemap')
                        ->icon('heroicon-o-arrow-path')
                        ->color('primary')
                        ->action(function ($records): void {
                            Cache::forget('seo.sitemap.xml.data');

                            $eligible = 0;
                            $excluded = 0;

                            foreach ($records as $record) {
                                if (static::seoIndexData($record)['sitemap_eligible']) {
                                    $eligible++;
                                } else {
                                    $excluded++;
                                }
                            }

                            Notification::make()
                                ->title('Sitemap refreshed')
                                ->body(
                                    "Eligible selected pages: {$eligible} | Excluded: {$excluded}"
                                )
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No pages found')
            ->emptyStateDescription(
                'Create your first SEO optimized page.',
            )
            ->emptyStateIcon('heroicon-o-document-text');
    }

    protected static function previewPageUrl(
        string $contentType,
        string $slug
    ): string {
        $baseUrl = rtrim(
            (string) config(
                'services.search_console.property',
                config('app.url')
            ),
            '/'
        );

        $prefix = match ($contentType) {
            'blog' => 'blog',
            'tour_package' => 'tour',
            default => 'pages',
        };

        $cleanSlug = ltrim(trim($slug), '/');

        return $cleanSlug === ''
            ? $baseUrl . '/' . $prefix . '/{slug}'
            : $baseUrl . '/' . $prefix . '/' . $cleanSlug;
    }

    /**
     * @return array<string, mixed>
     */
    protected static function seoIndexData(Page $record): array
    {
        return app(SeoAnalysisService::class)
            ->analyzeIndexReadiness(static::seoRecordData($record));
    }

    /**
     * @return array<string, mixed>
     */
    protected static function seoRecordData(Page $record): array
    {
        return [
            'name' => $record->name,
            'slug' => $record->slug,
            'description' => $record->description,
            'meta_title' => $record->meta_title,
            'meta_description' => $record->meta_description,
            'focus_keyword' => $record->focus_keyword,
            'canonical_url' => $record->canonical_url,
            'robots' => $record->robots,
            'is_active' => true,
            'page_url' => static::previewPageUrl(
                (string) $record->content_type,
                (string) $record->slug,
            ),
            'published_at' => $record->published_at,
            'updated_at' => $record->updated_at,
        ];
    }

    protected static function seoIndexDescription(Page $record): string
    {
        $analysis = static::seoIndexData($record);

        if ($analysis['blockers'] !== []) {
            return (string) ($analysis['blockers'][0]['title']
                ?? 'Indexing issue found');
        }

        if ($analysis['warnings'] !== []) {
            return (string) ($analysis['warnings'][0]['title']
                ?? 'SEO warning found');
        }

        return 'Technically ready for indexing';
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