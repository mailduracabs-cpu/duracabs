<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WhatsAppCampaignResource\Pages;
use App\Models\WhatsAppCampaign;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WhatsAppCampaignResource extends Resource
{
    protected static ?string $model = WhatsAppCampaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'WhatsApp';

    protected static ?string $navigationLabel = 'Bulk Campaigns';

    protected static ?string $modelLabel = 'WhatsApp Campaign';

    protected static ?string $pluralModelLabel = 'WhatsApp Campaigns';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Campaign')
                        ->icon('heroicon-o-megaphone')
                        ->description('Campaign ki basic details')
                        ->schema([
                            Forms\Components\TextInput::make('campaign_name')
                                ->label('Campaign Name')
                                ->placeholder('Example: DuraCabs Festival Offer')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),

                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Select::make('campaign_type')
                                        ->label('Campaign Type')
                                        ->options(
                                            WhatsAppCampaign::campaignTypeOptions()
                                        )
                                        ->default(
                                            WhatsAppCampaign::TYPE_TEMPLATE
                                        )
                                        ->required()
                                        ->native(false),

                                    Forms\Components\Select::make('language')
                                        ->label('Template Language')
                                        ->options([
                                            'en_US' => 'English (US)',
                                            'en' => 'English',
                                            'hi' => 'Hindi',
                                            'hi_IN' => 'Hindi (India)',
                                        ])
                                        ->default('en_US')
                                        ->required()
                                        ->searchable()
                                        ->native(false),
                                ]),

                            Forms\Components\TextInput::make('template_name')
                                ->label('Meta Template Name')
                                ->placeholder('Example: hello_world')
                                ->helperText(
                                    'Meta WhatsApp Manager me approved template ka exact name enter karein.'
                                )
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),

                            Forms\Components\Select::make('header_type')
                                ->label('Header Type')
                                ->options([
                                    'none' => 'No Header',
                                    'text' => 'Text',
                                    'image' => 'Image',
                                    'video' => 'Video',
                                    'document' => 'Document',
                                ])
                                ->default('none')
                                ->live()
                                ->native(false),

                            Forms\Components\TextInput::make('header_media')
                                ->label('Header Media URL')
                                ->placeholder(
                                    'https://duracabs.com/storage/...'
                                )
                                ->url()
                                ->maxLength(2048)
                                ->visible(
                                    fn (Forms\Get $get): bool => in_array(
                                        $get('header_type'),
                                        ['image', 'video', 'document'],
                                        true
                                    )
                                ),

                            Forms\Components\Textarea::make('body')
                                ->label('Message Preview')
                                ->placeholder(
                                    'Template message ka preview yahan likhein...'
                                )
                                ->helperText(
                                    'Ye preview ke liye hai. Actual message Meta-approved template se send hoga.'
                                )
                                ->rows(6)
                                ->columnSpanFull(),

                            Forms\Components\Textarea::make('footer')
                                ->label('Footer')
                                ->placeholder('Example: DuraCabs Services')
                                ->rows(2)
                                ->maxLength(500)
                                ->columnSpanFull(),

                            Forms\Components\KeyValue::make(
                                'template_variables'
                            )
                                ->label('Template Variables')
                                ->keyLabel('Variable Number')
                                ->valueLabel('Default Value')
                                ->addActionLabel('Add Variable')
                                ->helperText(
                                    'Example: key 1 aur value Customer Name. Key 2 aur value Offer Amount.'
                                )
                                ->columnSpanFull(),
                        ])
                        ->columns(2),

                    Forms\Components\Wizard\Step::make('Audience')
                        ->icon('heroicon-o-users')
                        ->description('Message recipients select karein')
                        ->schema([
                            Forms\Components\Select::make('audience_type')
                                ->label('Audience Type')
                                ->options(
                                    WhatsAppCampaign::audienceTypeOptions()
                                )
                                ->default(
                                    WhatsAppCampaign::AUDIENCE_MANUAL
                                )
                                ->required()
                                ->live()
                                ->native(false)
                                ->columnSpanFull(),

                            Forms\Components\Textarea::make(
                                'audience_data.manual_numbers'
                            )
                                ->label('WhatsApp Mobile Numbers')
                                ->placeholder(
                                    "917088873331\n919876543210\n919123456789"
                                )
                                ->helperText(
                                    'Har line me ek number country code ke saath enter karein. Plus sign, spaces aur dashes zaroori nahi hain.'
                                )
                                ->rows(12)
                                ->required(
                                    fn (Forms\Get $get): bool =>
                                        $get('audience_type')
                                        === WhatsAppCampaign::AUDIENCE_MANUAL
                                )
                                ->visible(
                                    fn (Forms\Get $get): bool =>
                                        $get('audience_type')
                                        === WhatsAppCampaign::AUDIENCE_MANUAL
                                )
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make(
                                'audience_data.city'
                            )
                                ->label('Customer City')
                                ->placeholder('Example: Agra')
                                ->required(
                                    fn (Forms\Get $get): bool =>
                                        $get('audience_type')
                                        === WhatsAppCampaign::AUDIENCE_CITY
                                )
                                ->visible(
                                    fn (Forms\Get $get): bool =>
                                        $get('audience_type')
                                        === WhatsAppCampaign::AUDIENCE_CITY
                                )
                                ->maxLength(255)
                                ->columnSpanFull(),

                            Forms\Components\Placeholder::make(
                                'selected_customers_note'
                            )
                                ->label('Selected Customers')
                                ->content(
                                    'Customer selection agle phase me customers database ke saath connect ki jayegi.'
                                )
                                ->visible(
                                    fn (Forms\Get $get): bool =>
                                        $get('audience_type')
                                        === WhatsAppCampaign::AUDIENCE_SELECTED_CUSTOMERS
                                )
                                ->columnSpanFull(),

                            Forms\Components\Placeholder::make(
                                'csv_note'
                            )
                                ->label('CSV Upload')
                                ->content(
                                    'CSV upload feature agle phase me add kiya jayega.'
                                )
                                ->visible(
                                    fn (Forms\Get $get): bool =>
                                        $get('audience_type')
                                        === WhatsAppCampaign::AUDIENCE_CSV
                                )
                                ->columnSpanFull(),

                            Forms\Components\Placeholder::make(
                                'automatic_audience_note'
                            )
                                ->label('Automatic Audience')
                                ->content(
                                    'Campaign save hone ke baad selected category ke customers automatically recipients list me add kiye jayenge.'
                                )
                                ->visible(
                                    fn (Forms\Get $get): bool => in_array(
                                        $get('audience_type'),
                                        [
                                            WhatsAppCampaign::AUDIENCE_ALL_CUSTOMERS,
                                            WhatsAppCampaign::AUDIENCE_SELF_DRIVE,
                                            WhatsAppCampaign::AUDIENCE_TAXI,
                                        ],
                                        true
                                    )
                                )
                                ->columnSpanFull(),
                        ]),

                    Forms\Components\Wizard\Step::make('Schedule')
                        ->icon('heroicon-o-clock')
                        ->description('Campaign save ya schedule karein')
                        ->schema([
                            Forms\Components\Select::make('status')
                                ->label('Campaign Status')
                                ->options([
                                    WhatsAppCampaign::STATUS_DRAFT =>
                                        'Save as Draft',

                                    WhatsAppCampaign::STATUS_SCHEDULED =>
                                        'Schedule Campaign',
                                ])
                                ->default(
                                    WhatsAppCampaign::STATUS_DRAFT
                                )
                                ->required()
                                ->live()
                                ->native(false),

                            Forms\Components\DateTimePicker::make(
                                'scheduled_at'
                            )
                                ->label('Scheduled Date & Time')
                                ->seconds(false)
                                ->native(false)
                                ->minDate(now())
                                ->required(
                                    fn (Forms\Get $get): bool =>
                                        $get('status')
                                        === WhatsAppCampaign::STATUS_SCHEDULED
                                )
                                ->visible(
                                    fn (Forms\Get $get): bool =>
                                        $get('status')
                                        === WhatsAppCampaign::STATUS_SCHEDULED
                                ),

                            Forms\Components\Placeholder::make(
                                'campaign_summary'
                            )
                                ->label('Important')
                                ->content(
                                    'Campaign save hone ke baad recipients prepare kiye jayenge. Abhi campaign automatic send nahi hoga.'
                                )
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                ])
                    ->skippable(false)
                    ->persistStepInQueryString()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('campaign_name')
                    ->label('Campaign')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(
                        fn (WhatsAppCampaign $record): string =>
                            $record->template_name
                                ? 'Template: ' . $record->template_name
                                : 'Template not selected'
                    ),

                Tables\Columns\TextColumn::make('campaign_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string =>
                            WhatsAppCampaign::campaignTypeOptions()[$state]
                            ?? ucfirst((string) $state)
                    )
                    ->color(fn (?string $state): string => match ($state) {
                        WhatsAppCampaign::TYPE_MARKETING => 'warning',
                        WhatsAppCampaign::TYPE_UTILITY => 'info',
                        WhatsAppCampaign::TYPE_AUTHENTICATION => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('audience_type')
                    ->label('Audience')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string =>
                            WhatsAppCampaign::audienceTypeOptions()[$state]
                            ?? ucfirst(
                                str_replace('_', ' ', (string) $state)
                            )
                    )
                    ->color('info')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total_recipients')
                    ->label('Recipients')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('sent_count')
                    ->label('Sent')
                    ->numeric()
                    ->sortable()
                    ->color('primary')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('delivered_count')
                    ->label('Delivered')
                    ->numeric()
                    ->sortable()
                    ->color('success')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('read_count')
                    ->label('Read')
                    ->numeric()
                    ->sortable()
                    ->color('success')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('failed_count')
                    ->label('Failed')
                    ->numeric()
                    ->sortable()
                    ->color('danger')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('progress_percentage')
                    ->label('Progress')
                    ->suffix('%')
                    ->alignCenter()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string =>
                            WhatsAppCampaign::statusOptions()[$state]
                            ?? ucfirst((string) $state)
                    )
                    ->color(fn (?string $state): string => match ($state) {
                        WhatsAppCampaign::STATUS_DRAFT => 'gray',
                        WhatsAppCampaign::STATUS_SCHEDULED => 'info',
                        WhatsAppCampaign::STATUS_PROCESSING => 'warning',
                        WhatsAppCampaign::STATUS_COMPLETED => 'success',
                        WhatsAppCampaign::STATUS_CANCELLED => 'gray',
                        WhatsAppCampaign::STATUS_FAILED => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Scheduled')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('Not scheduled')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(WhatsAppCampaign::statusOptions())
                    ->native(false),

                Tables\Filters\SelectFilter::make('campaign_type')
                    ->label('Campaign Type')
                    ->options(WhatsAppCampaign::campaignTypeOptions())
                    ->native(false),

                Tables\Filters\SelectFilter::make('audience_type')
                    ->label('Audience')
                    ->options(WhatsAppCampaign::audienceTypeOptions())
                    ->native(false),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(
                        fn (WhatsAppCampaign $record): bool =>
                            $record->canBeEdited()
                    ),

                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cancel WhatsApp Campaign')
                    ->modalDescription(
                        'Kya aap is campaign ko cancel karna chahte hain?'
                    )
                    ->visible(
                        fn (WhatsAppCampaign $record): bool =>
                            $record->canBeCancelled()
                            && !$record->isDraft()
                    )
                    ->action(function (WhatsAppCampaign $record): void {
                        $record->update([
                            'status' =>
                                WhatsAppCampaign::STATUS_CANCELLED,
                            'cancelled_at' => now(),
                        ]);
                    }),

                Tables\Actions\DeleteAction::make()
                    ->visible(
                        fn (WhatsAppCampaign $record): bool =>
                            $record->isDraft()
                    ),

                Tables\Actions\RestoreAction::make(),

                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No WhatsApp campaigns')
            ->emptyStateDescription(
                'Apna pehla WhatsApp bulk campaign create karein.'
            )
            ->emptyStateIcon('heroicon-o-chat-bubble-left-right')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWhatsAppCampaigns::route('/'),
            'create' => Pages\CreateWhatsAppCampaign::route('/create'),
            'edit' => Pages\EditWhatsAppCampaign::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()
            ->whereIn('status', [
                WhatsAppCampaign::STATUS_DRAFT,
                WhatsAppCampaign::STATUS_SCHEDULED,
                WhatsAppCampaign::STATUS_PROCESSING,
            ])
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }
}