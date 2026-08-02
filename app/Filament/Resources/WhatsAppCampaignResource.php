<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WhatsAppCampaignResource\Pages;
use App\Models\WhatsAppCampaign;
use App\Models\WhatsAppTemplate;
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
                        ->description('Campaign aur approved template select karein')
                        ->schema([
                            Forms\Components\TextInput::make('campaign_name')
                                ->label('Campaign Name')
                                ->placeholder('Example: DuraCabs Festival Offer')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),

                            Forms\Components\Select::make(
                                'whatsapp_template_id'
                            )
                                ->label('Approved WhatsApp Template')
                                ->relationship(
                                    name: 'template',
                                    titleAttribute: 'name',
                                    modifyQueryUsing: fn (Builder $query): Builder =>
                                        $query
                                            ->where('is_active', true)
                                            ->where(
                                                'status',
                                                WhatsAppTemplate::STATUS_ACTIVE
                                            )
                                            ->where(
                                                'meta_status',
                                                WhatsAppTemplate::META_STATUS_APPROVED
                                            )
                                )
                                ->getOptionLabelFromRecordUsing(
                                    fn (WhatsAppTemplate $record): string =>
                                        $record->name
                                        . ' — '
                                        . $record->template_name
                                )
                                ->searchable([
                                    'name',
                                    'template_name',
                                ])
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (
                                    mixed $state,
                                    Forms\Set $set
                                ): void {
                                    $template = filled($state)
                                        ? WhatsAppTemplate::query()->find($state)
                                        : null;

                                    if (! $template) {
                                        $set('template_name', null);
                                        $set('language', 'en');
                                        $set(
                                            'campaign_type',
                                            WhatsAppCampaign::TYPE_TEMPLATE
                                        );
                                        $set('header_type', 'none');
                                        $set('header_media', null);
                                        $set('body', null);
                                        $set('footer', null);
                                        $set('template_variables', []);
                                        $set('button_payload', []);

                                        return;
                                    }

                                    $category = strtoupper(
                                        (string) $template->category
                                    );

                                    $campaignType = match ($category) {
                                        WhatsAppTemplate::CATEGORY_MARKETING =>
                                            WhatsAppCampaign::TYPE_MARKETING,

                                        WhatsAppTemplate::CATEGORY_UTILITY =>
                                            WhatsAppCampaign::TYPE_UTILITY,

                                        WhatsAppTemplate::CATEGORY_AUTHENTICATION =>
                                            WhatsAppCampaign::TYPE_AUTHENTICATION,

                                        default =>
                                            WhatsAppCampaign::TYPE_TEMPLATE,
                                    };

                                    $variables = [];

                                    foreach (
                                        (array) ($template->variables ?? [])
                                        as $index => $variable
                                    ) {
                                        if (! is_array($variable)) {
                                            continue;
                                        }

                                        $position = (int) (
                                            $variable['position']
                                            ?? $variable['index']
                                            ?? ($index + 1)
                                        );

                                        if ($position <= 0) {
                                            continue;
                                        }

                                        $variables[(string) $position] =
                                            (string) (
                                                $variable['sample']
                                                ?? $variable['key']
                                                ?? '-'
                                            );
                                    }

                                    ksort($variables, SORT_NATURAL);

                                    $set(
                                        'template_name',
                                        $template->template_name
                                    );
                                    $set(
                                        'language',
                                        $template->language ?: 'en'
                                    );
                                    $set('campaign_type', $campaignType);
                                    $set(
                                        'header_type',
                                        $template->header_type ?: 'none'
                                    );
                                    $set(
                                        'header_media',
                                        $template->header_media
                                    );
                                    $set('body', $template->body);
                                    $set('footer', $template->footer);
                                    $set(
                                        'template_variables',
                                        $variables
                                    );
                                    $set(
                                        'button_payload',
                                        $template->buttons ?: []
                                    );
                                })
                                ->helperText(
                                    'Sirf active aur Meta-approved templates yahan dikhengi.'
                                )
                                ->columnSpanFull(),

                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\Placeholder::make(
                                        'selected_template_name'
                                    )
                                        ->label('Meta Template')
                                        ->content(
                                            fn (Forms\Get $get): string =>
                                                filled($get('template_name'))
                                                    ? (string) $get(
                                                        'template_name'
                                                    )
                                                    : 'Template select karein'
                                        ),

                                    Forms\Components\Placeholder::make(
                                        'selected_template_language'
                                    )
                                        ->label('Language')
                                        ->content(
                                            fn (Forms\Get $get): string =>
                                                filled($get('language'))
                                                    ? (string) $get('language')
                                                    : '—'
                                        ),

                                    Forms\Components\Placeholder::make(
                                        'selected_template_type'
                                    )
                                        ->label('Category')
                                        ->content(
                                            fn (Forms\Get $get): string =>
                                                WhatsAppCampaign::campaignTypeOptions()[
                                                    $get('campaign_type')
                                                ]
                                                ?? '—'
                                        ),
                                ]),

                            Forms\Components\Placeholder::make(
                                'selected_template_preview'
                            )
                                ->label('Message Preview')
                                ->content(function (
                                    Forms\Get $get
                                ): string {
                                    $parts = [];

                                    if (
                                        filled($get('header_type'))
                                        && $get('header_type') !== 'none'
                                    ) {
                                        $parts[] = 'Header: '
                                            . ucfirst(
                                                (string) $get('header_type')
                                            );
                                    }

                                    if (filled($get('body'))) {
                                        $parts[] = (string) $get('body');
                                    }

                                    if (filled($get('footer'))) {
                                        $parts[] = (string) $get('footer');
                                    }

                                    return $parts !== []
                                        ? implode("\n\n", $parts)
                                        : 'Approved template select karne ke baad preview yahan dikhega.';
                                })
                                ->columnSpanFull(),

                            Forms\Components\KeyValue::make(
                                'template_variables'
                            )
                                ->label('Template Variable Samples')
                                ->keyLabel('Variable Number')
                                ->valueLabel('Sample Value')
                                ->disabled()
                                ->dehydrated()
                                ->columnSpanFull(),

                            Forms\Components\Hidden::make('template_name')
                                ->dehydrated(),

                            Forms\Components\Hidden::make('language')
                                ->default('en')
                                ->dehydrated(),

                            Forms\Components\Hidden::make('campaign_type')
                                ->default(
                                    WhatsAppCampaign::TYPE_TEMPLATE
                                )
                                ->dehydrated(),

                            Forms\Components\Hidden::make('header_type')
                                ->default('none')
                                ->dehydrated(),

                            Forms\Components\Hidden::make('header_media')
                                ->dehydrated(),

                            Forms\Components\Hidden::make('body')
                                ->dehydrated(),

                            Forms\Components\Hidden::make('footer')
                                ->dehydrated(),

                            Forms\Components\Hidden::make('button_payload')
                                ->dehydrated(),
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