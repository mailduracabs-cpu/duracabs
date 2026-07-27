<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WebsiteSettingResource\Pages;
use App\Models\WebsiteSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WebsiteSettingResource extends Resource
{
    protected static ?string $model = WebsiteSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Website Management';

    protected static ?string $navigationLabel = 'Website Settings';

    protected static ?string $modelLabel = 'Website Setting';

    protected static ?string $pluralModelLabel = 'Website Settings';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Website Settings')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('General')
                            ->icon('heroicon-o-globe-alt')
                            ->schema([
                                Forms\Components\Section::make('Website Identity')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('site_name')
                                            ->label('Website Name')
                                            ->required()
                                            ->maxLength(255)
                                            ->default('Dura Cabs'),

                                        Forms\Components\TextInput::make('tagline')
                                            ->label('Tagline')
                                            ->maxLength(255),

                                        Forms\Components\FileUpload::make('logo')
                                            ->label('Website Logo')
                                            ->image()
                                            ->imageEditor()
                                            ->disk('public')
                                            ->directory('website-settings/logo')
                                            ->visibility('public')
                                            ->maxSize(2048),

                                        Forms\Components\FileUpload::make('favicon')
                                            ->label('Favicon')
                                            ->image()
                                            ->disk('public')
                                            ->directory('website-settings/favicon')
                                            ->visibility('public')
                                            ->acceptedFileTypes([
                                                'image/x-icon',
                                                'image/vnd.microsoft.icon',
                                                'image/png',
                                                'image/svg+xml',
                                                'image/webp',
                                            ])
                                            ->maxSize(1024),

                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Settings Active')
                                            ->default(true)
                                            ->helperText(
                                                'Only the active settings record is used on the website.'
                                            ),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('SEO')
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema([
                                Forms\Components\Section::make('Default SEO')
                                    ->description(
                                        'These values are used only when a page-specific SEO value is empty.'
                                    )
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('default_meta_title')
                                            ->label('Default Meta Title')
                                            ->maxLength(255)
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('default_meta_description')
                                            ->label('Default Meta Description')
                                            ->rows(4)
                                            ->maxLength(500)
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('default_meta_keywords')
                                            ->label('Default Meta Keywords')
                                            ->helperText(
                                                'Optional. Use comma-separated keywords.'
                                            )
                                            ->maxLength(500)
                                            ->columnSpanFull(),

                                        Forms\Components\FileUpload::make('default_og_image')
                                            ->label('Default Social Share Image')
                                            ->image()
                                            ->imageEditor()
                                            ->disk('public')
                                            ->directory('website-settings/seo')
                                            ->visibility('public')
                                            ->maxSize(4096),

                                        Forms\Components\Select::make('robots')
                                            ->label('Default Robots')
                                            ->options([
                                                'index, follow' => 'Index, Follow',
                                                'index, nofollow' => 'Index, No Follow',
                                                'noindex, follow' => 'No Index, Follow',
                                                'noindex, nofollow' => 'No Index, No Follow',
                                            ])
                                            ->default('index, follow')
                                            ->required(),

                                        Forms\Components\TextInput::make('twitter_username')
                                            ->label('X / Twitter Username')
                                            ->prefix('@')
                                            ->placeholder('duracabs')
                                            ->maxLength(100),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Business')
                            ->icon('heroicon-o-building-office-2')
                            ->schema([
                                Forms\Components\Section::make('Business Details')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('business_name')
                                            ->label('Business Name')
                                            ->required()
                                            ->maxLength(255),

                                        Forms\Components\Select::make('business_type')
                                            ->label('Schema Business Type')
                                            ->options([
                                                'TaxiService' => 'Taxi Service',
                                                'LocalBusiness' => 'Local Business',
                                                'TravelAgency' => 'Travel Agency',
                                                'AutomotiveBusiness' => 'Automotive Business',
                                                'Organization' => 'Organization',
                                            ])
                                            ->default('TaxiService')
                                            ->required(),

                                        Forms\Components\Textarea::make('business_description')
                                            ->label('Business Description')
                                            ->rows(5)
                                            ->maxLength(1000)
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('phone')
                                            ->label('Primary Phone')
                                            ->tel()
                                            ->maxLength(30),

                                        Forms\Components\TextInput::make('alternate_phone')
                                            ->label('Alternate Phone')
                                            ->tel()
                                            ->maxLength(30),

                                        Forms\Components\TextInput::make('email')
                                            ->label('Business Email')
                                            ->email()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('price_range')
                                            ->label('Price Range')
                                            ->placeholder('₹₹')
                                            ->maxLength(50),
                                    ]),

                                Forms\Components\Section::make('Business Address')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\Textarea::make('street_address')
                                            ->label('Street Address')
                                            ->rows(3)
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('city')
                                            ->maxLength(100),

                                        Forms\Components\TextInput::make('state')
                                            ->maxLength(100),

                                        Forms\Components\TextInput::make('postal_code')
                                            ->label('Postal Code')
                                            ->maxLength(20),

                                        Forms\Components\TextInput::make('country_code')
                                            ->label('Country Code')
                                            ->default('IN')
                                            ->maxLength(5),

                                        Forms\Components\TextInput::make('latitude')
                                            ->numeric()
                                            ->step('0.0000001'),

                                        Forms\Components\TextInput::make('longitude')
                                            ->numeric()
                                            ->step('0.0000001'),

                                        Forms\Components\TextInput::make('google_map_url')
                                            ->label('Google Maps URL')
                                            ->url()
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('Opening Hours')
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\Toggle::make('open_24_hours')
                                            ->label('Open 24 Hours')
                                            ->default(true)
                                            ->live(),

                                        Forms\Components\TimePicker::make('opening_time')
                                            ->label('Opening Time')
                                            ->seconds(false)
                                            ->visible(
                                                fn (Forms\Get $get): bool =>
                                                    ! (bool) $get('open_24_hours')
                                            ),

                                        Forms\Components\TimePicker::make('closing_time')
                                            ->label('Closing Time')
                                            ->seconds(false)
                                            ->visible(
                                                fn (Forms\Get $get): bool =>
                                                    ! (bool) $get('open_24_hours')
                                            ),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Social Profiles')
                            ->icon('heroicon-o-share')
                            ->schema([
                                Forms\Components\Section::make('Official Profiles')
                                    ->description(
                                        'Only official profiles entered here will be included in sameAs schema.'
                                    )
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('facebook_url')
                                            ->label('Facebook URL')
                                            ->url(),

                                        Forms\Components\TextInput::make('instagram_url')
                                            ->label('Instagram URL')
                                            ->url(),

                                        Forms\Components\TextInput::make('linkedin_url')
                                            ->label('LinkedIn URL')
                                            ->url(),

                                        Forms\Components\TextInput::make('twitter_url')
                                            ->label('X / Twitter URL')
                                            ->url(),

                                        Forms\Components\TextInput::make('youtube_url')
                                            ->label('YouTube URL')
                                            ->url(),

                                        Forms\Components\TextInput::make('pinterest_url')
                                            ->label('Pinterest URL')
                                            ->url(),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Ratings')
                            ->icon('heroicon-o-star')
                            ->schema([
                                Forms\Components\Section::make('Schema Rating')
                                    ->description(
                                        'Enter only genuine, verifiable rating information.'
                                    )
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('rating_value')
                                            ->label('Rating Value')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(5)
                                            ->step('0.01'),

                                        Forms\Components\TextInput::make('review_count')
                                            ->label('Review Count')
                                            ->numeric()
                                            ->minValue(0),

                                        Forms\Components\TextInput::make('best_rating')
                                            ->label('Best Rating')
                                            ->numeric()
                                            ->default(5)
                                            ->minValue(1)
                                            ->maxValue(10)
                                            ->step('0.01'),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Analytics')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Forms\Components\Section::make('Tracking IDs')
                                    ->description(
                                        'Enter IDs only. Do not paste complete script tags.'
                                    )
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('google_tag_manager_id')
                                            ->label('Google Tag Manager ID')
                                            ->placeholder('GTM-XXXXXXX')
                                            ->maxLength(50),

                                        Forms\Components\TextInput::make('google_analytics_id')
                                            ->label('Google Analytics ID')
                                            ->placeholder('G-XXXXXXXXXX')
                                            ->maxLength(50),

                                        Forms\Components\TextInput::make('google_ads_id')
                                            ->label('Google Ads ID')
                                            ->placeholder('AW-XXXXXXXXXXX')
                                            ->maxLength(50),
                                    ]),

                                Forms\Components\Section::make('Website Verification')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('google_site_verification')
                                            ->label('Google Verification')
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make(
                                            'google_site_verification_secondary'
                                        )
                                            ->label('Second Google Verification')
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('bing_site_verification')
                                            ->label('Bing Verification')
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('yandex_verification')
                                            ->label('Yandex Verification')
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make(
                                            'pinterest_domain_verification'
                                        )
                                            ->label('Pinterest Verification')
                                            ->maxLength(255),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->label('Logo')
                    ->disk('public'),

                Tables\Columns\TextColumn::make('site_name')
                    ->label('Website')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('business_name')
                    ->label('Business')
                    ->searchable(),

                Tables\Columns\TextColumn::make('city')
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Website settings are not configured')
            ->emptyStateDescription(
                'Create the global website settings record.'
            );
    }

    /**
     * Only one global settings record should exist.
     */
    public static function canCreate(): bool
    {
        return ! WebsiteSetting::query()->exists();
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWebsiteSettings::route('/'),
            'create' => Pages\CreateWebsiteSetting::route('/create'),
            'edit' => Pages\EditWebsiteSetting::route('/{record}/edit'),
        ];
    }
}