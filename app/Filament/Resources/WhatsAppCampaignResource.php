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

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Bulk Campaigns';

    protected static ?string $modelLabel = 'WhatsApp Campaign';

    protected static ?string $pluralModelLabel = 'WhatsApp Campaigns';

    protected static ?string $navigationGroup = 'WhatsApp';

    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Campaign')
                ->description(
                    'Customer group aur approved WhatsApp message select karein.'
                )
                ->schema([
                    Forms\Components\TextInput::make('campaign_name')
                        ->label('Campaign Name')
                        ->placeholder('Example: Weekend Self Drive Offer')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\Select::make('audience_type')
                        ->label('Send To')
                        ->options([
                            WhatsAppCampaign::AUDIENCE_ALL_CUSTOMERS =>
                                'All Registered Customers',

                            WhatsAppCampaign::AUDIENCE_SELF_DRIVE =>
                                'Self Drive Customers',

                            WhatsAppCampaign::AUDIENCE_TAXI =>
                                'With Driver / Taxi Customers',

                            WhatsAppCampaign::AUDIENCE_MANUAL =>
                                'Manual Mobile Numbers',
                        ])
                        ->default(
                            WhatsAppCampaign::AUDIENCE_MANUAL
                        )
                        ->required()
                        ->live()
                        ->helperText(
                            'Ek customer multiple jagah ho to message sirf ek baar queue hoga.'
                        ),

                    Forms\Components\Textarea::make(
                        'audience_data.manual_numbers'
                    )
                        ->label('WhatsApp Mobile Numbers')
                        ->placeholder(
                            "9876543210\n9123456789\n917088873331"
                        )
                        ->helperText(
                            'Ek line me ek number enter karein. 10 digit Indian numbers automatically +91 format me convert ho jayenge.'
                        )
                        ->rows(6)
                        ->visible(
                            fn (Forms\Get $get): bool =>
                                $get('audience_type')
                                === WhatsAppCampaign::AUDIENCE_MANUAL
                        )
                        ->required(
                            fn (Forms\Get $get): bool =>
                                $get('audience_type')
                                === WhatsAppCampaign::AUDIENCE_MANUAL
                        )
                        ->columnSpanFull(),

                    Forms\Components\Select::make(
                        'whatsapp_template_id'
                    )
                        ->label('WhatsApp Message')
                        ->relationship(
                            name: 'template',
                            titleAttribute: 'name',
                            modifyQueryUsing:
                                fn (Builder $query): Builder =>
                                    $query
                                        ->where('is_active', true)
                                        ->where('status', 'active')
                                        ->where(
                                            'meta_status',
                                            WhatsAppTemplate::META_STATUS_APPROVED
                                        )
                        )
                        ->getOptionLabelFromRecordUsing(
                            function (
                                WhatsAppTemplate $record
                            ): string {
                                $label = trim(
                                    (string) $record->name
                                );

                                if ($label === '') {
                                    $label = trim(
                                        (string) $record->template_name
                                    );
                                }

                                return $label;
                            }
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(
                            function (
                                $state,
                                Forms\Set $set
                            ): void {
                                if (! $state) {
                                    return;
                                }

                                $template =
                                    WhatsAppTemplate::query()
                                        ->find($state);

                                if (! $template) {
                                    return;
                                }

                                $set(
                                    'template_name',
                                    $template->template_name
                                );

                                $set(
                                    'language',
                                    $template->language ?: 'en_US'
                                );

                                $category = strtolower(
                                    trim(
                                        (string) $template->category
                                    )
                                );

                                $campaignType = match ($category) {
                                    'marketing' =>
                                        WhatsAppCampaign::TYPE_MARKETING,

                                    'utility' =>
                                        WhatsAppCampaign::TYPE_UTILITY,

                                    'authentication' =>
                                        WhatsAppCampaign::TYPE_AUTHENTICATION,

                                    default =>
                                        WhatsAppCampaign::TYPE_TEMPLATE,
                                };

                                $set(
                                    'campaign_type',
                                    $campaignType
                                );

                                $set(
                                    'header_type',
                                    $template->header_type ?: 'none'
                                );

                                $set(
                                    'header_media',
                                    $template->header_media
                                );

                                $set(
                                    'body',
                                    $template->body
                                );

                                $set(
                                    'footer',
                                    $template->footer
                                );

                                $set(
                                    'button_payload',
                                    $template->buttons ?: []
                                );

                                $variables = [];

                                foreach (
                                    ($template->variables ?: [])
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

                                    $variables[
                                        (string) $position
                                    ] = (string) (
                                        $variable['sample']
                                        ?? $variable['key']
                                        ?? '-'
                                    );
                                }

                                ksort(
                                    $variables,
                                    SORT_NATURAL
                                );

                                $set(
                                    'template_variables',
                                    $variables
                                );
                            }
                        )
                        ->helperText(
                            'Sirf Meta approved aur active templates yahan dikhaye jayenge.'
                        )
                        ->columnSpanFull(),

                    Forms\Components\Placeholder::make(
                        'message_preview'
                    )
                        ->label('Message Preview')
                        ->content(
                            function (
                                Forms\Get $get
                            ): string {
                                $body = trim(
                                    (string) $get('body')
                                );

                                return $body !== ''
                                    ? $body
                                    : 'WhatsApp message select karne ke baad preview yahan dikhega.';
                            }
                        )
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Send')
                ->description(
                    'Campaign save karein. Send Now edit screen se available rahega.'
                )
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label('Send Option')
                        ->options([
                            WhatsAppCampaign::STATUS_DRAFT =>
                                'Save Draft / Send Manually',

                            WhatsAppCampaign::STATUS_SCHEDULED =>
                                'Schedule for Later',
                        ])
                        ->default(
                            WhatsAppCampaign::STATUS_DRAFT
                        )
                        ->required()
                        ->live(),

                    Forms\Components\DateTimePicker::make(
                        'scheduled_at'
                    )
                        ->label('Send Date & Time')
                        ->seconds(false)
                        ->minDate(now())
                        ->visible(
                            fn (Forms\Get $get): bool =>
                                $get('status')
                                === WhatsAppCampaign::STATUS_SCHEDULED
                        )
                        ->required(
                            fn (Forms\Get $get): bool =>
                                $get('status')
                                === WhatsAppCampaign::STATUS_SCHEDULED
                        ),
                ])
                ->columns(2),

            Forms\Components\Hidden::make('template_name'),

            Forms\Components\Hidden::make('language')
                ->default('en_US'),

            Forms\Components\Hidden::make('campaign_type')
                ->default(
                    WhatsAppCampaign::TYPE_TEMPLATE
                ),

            Forms\Components\Hidden::make('header_type'),

            Forms\Components\Hidden::make('header_media'),

            Forms\Components\Hidden::make('body'),

            Forms\Components\Hidden::make('footer'),

            Forms\Components\Hidden::make('button_payload'),

            Forms\Components\Hidden::make(
                'template_variables'
            ),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make(
                    'campaign_name'
                )
                    ->label('Campaign')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make(
                    'audience_type'
                )
                    ->label('Audience')
                    ->formatStateUsing(
                        fn (?string $state): string =>
                            match ($state) {
                                WhatsAppCampaign::AUDIENCE_ALL_CUSTOMERS =>
                                    'All Customers',

                                WhatsAppCampaign::AUDIENCE_SELF_DRIVE =>
                                    'Self Drive',

                                WhatsAppCampaign::AUDIENCE_TAXI =>
                                    'With Driver / Taxi',

                                WhatsAppCampaign::AUDIENCE_MANUAL =>
                                    'Manual Numbers',

                                default =>
                                    ucfirst(
                                        str_replace(
                                            '_',
                                            ' ',
                                            (string) $state
                                        )
                                    ),
                            }
                    )
                    ->badge(),

                Tables\Columns\TextColumn::make(
                    'total_recipients'
                )
                    ->label('Recipients')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make(
                    'sent_count'
                )
                    ->label('Sent')
                    ->numeric(),

                Tables\Columns\TextColumn::make(
                    'delivered_count'
                )
                    ->label('Delivered')
                    ->numeric(),

                Tables\Columns\TextColumn::make(
                    'read_count'
                )
                    ->label('Read')
                    ->numeric(),

                Tables\Columns\TextColumn::make(
                    'failed_count'
                )
                    ->label('Failed')
                    ->numeric()
                    ->color(
                        fn ($state): string =>
                            ((int) $state) > 0
                                ? 'danger'
                                : 'gray'
                    ),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string =>
                            WhatsAppCampaign::statusOptions()[
                                $state
                            ]
                            ?? ucfirst((string) $state)
                    )
                    ->color(
                        fn (?string $state): string =>
                            match ($state) {
                                WhatsAppCampaign::STATUS_DRAFT =>
                                    'gray',

                                WhatsAppCampaign::STATUS_SCHEDULED =>
                                    'warning',

                                WhatsAppCampaign::STATUS_PROCESSING =>
                                    'info',

                                WhatsAppCampaign::STATUS_COMPLETED =>
                                    'success',

                                WhatsAppCampaign::STATUS_CANCELLED,
                                WhatsAppCampaign::STATUS_FAILED =>
                                    'danger',

                                default => 'gray',
                            }
                    ),

                Tables\Columns\TextColumn::make(
                    'scheduled_at'
                )
                    ->label('Scheduled')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('-')
                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),

                Tables\Columns\TextColumn::make(
                    'created_at'
                )
                    ->label('Created')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(
                        WhatsAppCampaign::statusOptions()
                    ),

                Tables\Filters\SelectFilter::make(
                    'audience_type'
                )
                    ->label('Audience')
                    ->options([
                        WhatsAppCampaign::AUDIENCE_ALL_CUSTOMERS =>
                            'All Customers',

                        WhatsAppCampaign::AUDIENCE_SELF_DRIVE =>
                            'Self Drive',

                        WhatsAppCampaign::AUDIENCE_TAXI =>
                            'With Driver / Taxi',

                        WhatsAppCampaign::AUDIENCE_MANUAL =>
                            'Manual Numbers',
                    ]),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(
                        fn (
                            WhatsAppCampaign $record
                        ): bool =>
                            $record->canBeEdited()
                            || $record->isFailed()
                    ),
            ])
            ->bulkActions([])
            ->emptyStateHeading(
                'No WhatsApp campaigns yet'
            )
            ->emptyStateDescription(
                'Customer group select karke apna pehla WhatsApp campaign banayein.'
            )
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Create Campaign'),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' =>
                Pages\ListWhatsAppCampaigns::route('/'),

            'create' =>
                Pages\CreateWhatsAppCampaign::route(
                    '/create'
                ),

            'edit' =>
                Pages\EditWhatsAppCampaign::route(
                    '/{record}/edit'
                ),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}