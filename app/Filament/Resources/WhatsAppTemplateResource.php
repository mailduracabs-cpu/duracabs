<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WhatsAppTemplateResource\Pages;
use App\Models\WhatsAppTemplate;
use App\Services\WhatsAppService;
use Filament\Notifications\Notification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class WhatsAppTemplateResource extends Resource
{
    protected static ?string $model = WhatsAppTemplate::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'WhatsApp';
    protected static ?string $navigationLabel = 'Templates';
    protected static ?string $modelLabel = 'WhatsApp Template';
    protected static ?string $pluralModelLabel = 'WhatsApp Templates';
    protected static ?int $navigationSort = 0;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Wizard::make([
                Forms\Components\Wizard\Step::make('Template Details')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Display Name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, Forms\Set $set, Forms\Get $get): void {
                                if (filled($get('template_name'))) {
                                    return;
                                }
                                $set('template_name', Str::of((string) $state)->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString());
                            }),
                        Forms\Components\TextInput::make('template_name')
                            ->label('Meta Template Name')
                            ->required()
                            ->unique(
                                table: WhatsAppTemplate::class,
                                column: 'template_name',
                                ignoreRecord: true
                            )
                            ->regex('/^[a-z0-9_]+$/')
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (
                                ?string $state,
                                Forms\Set $set,
                                Forms\Get $get
                            ): void {
                                if (filled($get('template_key'))) {
                                    return;
                                }

                                $key = preg_replace(
                                    '/_v\\d+$/i',
                                    '',
                                    trim((string) $state)
                                ) ?: trim((string) $state);

                                $key = Str::of($key)
                                    ->lower()
                                    ->replaceMatches('/[^a-z0-9_]+/', '_')
                                    ->trim('_')
                                    ->toString();

                                $set('template_key', $key);
                                $set(
                                    'event_key',
                                    str_replace('_', '.', $key)
                                );
                            }),

                        Forms\Components\TextInput::make('template_key')
                            ->label('Template Key')
                            ->helperText(
                                'Stable code key. Example: booking_received'
                            )
                            ->required()
                            ->regex('/^[a-z0-9_]+$/')
                            ->maxLength(120)
                            ->unique(
                                table: WhatsAppTemplate::class,
                                column: 'template_key',
                                ignoreRecord: true
                            )
                            ->disabledOn('edit')
                            ->dehydrated(),

                        Forms\Components\TextInput::make('event_key')
                            ->label('Event Key')
                            ->helperText(
                                'Notification event. Example: booking.received'
                            )
                            ->required()
                            ->regex('/^[a-z0-9_.-]+$/')
                            ->maxLength(160)
                            ->unique(
                                table: WhatsAppTemplate::class,
                                column: 'event_key',
                                ignoreRecord: true
                            ),
                        Forms\Components\Select::make('category')
                            ->options(WhatsAppTemplate::categories())
                            ->default(WhatsAppTemplate::CATEGORY_UTILITY)
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('language')
                            ->options([
                                'en' => 'English',
                                'en_US' => 'English (US)',
                                'hi' => 'Hindi',
                                'hi_IN' => 'Hindi (India)',
                            ])
                            ->default('en')
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('status')
                            ->options(WhatsAppTemplate::statuses())
                            ->default(WhatsAppTemplate::STATUS_DRAFT)
                            ->required()
                            ->native(false),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->inline(false),
                    ])->columns(2),

                Forms\Components\Wizard\Step::make('Message Builder')
                    ->icon('heroicon-o-chat-bubble-bottom-center-text')
                    ->schema([
                        Forms\Components\Select::make('header_type')
                            ->options(WhatsAppTemplate::headerTypes())
                            ->default('none')
                            ->live()
                            ->native(false),
                        Forms\Components\TextInput::make('header_text')
                            ->maxLength(60)
                            ->visible(fn (Forms\Get $get): bool => $get('header_type') === 'text'),
                        Forms\Components\TextInput::make('header_media')
                            ->label('Header Media URL')
                            ->url()
                            ->maxLength(2048)
                            ->visible(fn (Forms\Get $get): bool => in_array($get('header_type'), ['image', 'video', 'document'], true)),
                        Forms\Components\Textarea::make('body')
                            ->label('Template Body')
                            ->placeholder("Hello {{1}},\n\nYour booking {{2}} has been received.")
                            ->helperText('Variables {{1}}, {{2}}, {{3}} ke format me use karein.')
                            ->rows(10)
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('footer')
                            ->rows(2)
                            ->maxLength(60)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Wizard\Step::make('Variables & Buttons')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->schema([
                        Forms\Components\Repeater::make('variables')
                            ->schema([
                                Forms\Components\TextInput::make('position')->numeric()->minValue(1)->required(),
                                Forms\Components\Select::make('key')->options(self::variableOptions())->searchable()->required()->native(false),
                                Forms\Components\TextInput::make('sample')->required()->maxLength(255),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel('Add Variable')
                            ->reorderable()
                            ->collapsible()
                            ->columnSpanFull(),
                        Forms\Components\Repeater::make('buttons')
                            ->schema([
                                Forms\Components\Select::make('type')
                                    ->options([
                                        'quick_reply' => 'Quick Reply',
                                        'url' => 'Website URL',
                                        'phone_number' => 'Phone Number',
                                    ])
                                    ->live()
                                    ->required()
                                    ->native(false),
                                Forms\Components\TextInput::make('text')->required()->maxLength(25),
                                Forms\Components\TextInput::make('value')->required()->maxLength(2048),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->maxItems(3)
                            ->addActionLabel('Add Button')
                            ->reorderable()
                            ->collapsible()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Wizard\Step::make('Meta Status')
                    ->icon('heroicon-o-cloud')
                    ->schema([
                        Forms\Components\Select::make('meta_status')
                            ->options(WhatsAppTemplate::metaStatuses())
                            ->default(WhatsAppTemplate::META_STATUS_NOT_SUBMITTED)
                            ->disabled()
                            ->dehydrated()
                            ->native(false),
                        Forms\Components\TextInput::make('meta_template_id')->disabled()->dehydrated(),
                        Forms\Components\Textarea::make('meta_rejection_reason')
                            ->rows(4)
                            ->disabled()
                            ->dehydrated()
                            ->columnSpanFull(),
                        Forms\Components\Placeholder::make('template_preview')
                            ->label('Preview')
                            ->content(fn (Forms\Get $get): string => self::buildPreview($get))
                            ->columnSpanFull(),
                    ])->columns(2),
            ])->skippable(false)->persistStepInQueryString()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Template')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(
                        fn (WhatsAppTemplate $record): string =>
                            $record->template_name
                    ),

                Tables\Columns\TextColumn::make('template_key')
                    ->label('Template Key')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('event_key')
                    ->label('Event Key')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => WhatsAppTemplate::categories()[$state] ?? ucfirst(strtolower((string) $state)))
                    ->color(fn (?string $state): string => match ($state) {
                        WhatsAppTemplate::CATEGORY_UTILITY => 'info',
                        WhatsAppTemplate::CATEGORY_MARKETING => 'warning',
                        WhatsAppTemplate::CATEGORY_AUTHENTICATION => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('language')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Local Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => WhatsAppTemplate::statuses()[$state] ?? ucfirst((string) $state)),
                Tables\Columns\TextColumn::make('meta_status')
                    ->label('Meta Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => WhatsAppTemplate::metaStatuses()[$state] ?? ucfirst(str_replace('_', ' ', (string) $state)))
                    ->color(fn (?string $state): string => match ($state) {
                        WhatsAppTemplate::META_STATUS_APPROVED => 'success',
                        WhatsAppTemplate::META_STATUS_PENDING => 'warning',
                        WhatsAppTemplate::META_STATUS_REJECTED => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\ToggleColumn::make('is_active')->label('Active'),
                Tables\Columns\TextColumn::make('last_synced_at')->label('Last Sync')->dateTime('d M Y, h:i A')->placeholder('Never'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')->options(WhatsAppTemplate::categories())->native(false),
                Tables\Filters\SelectFilter::make('status')->options(WhatsAppTemplate::statuses())->native(false),
                Tables\Filters\SelectFilter::make('meta_status')->options(WhatsAppTemplate::metaStatuses())->native(false),
                Tables\Filters\TernaryFilter::make('is_active')->label('Active Templates'),
            ])
            ->actions([
                Tables\Actions\Action::make('submit_to_meta')
                    ->label('Submit to Meta')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Submit template to Meta?')
                    ->modalDescription(
                        'Template Meta WhatsApp review ke liye submit hoga. '
                        . 'Submit hone ke baad template content ko edit na karein.'
                    )
                    ->modalSubmitActionLabel('Submit Now')
                    ->visible(
                        fn (WhatsAppTemplate $record): bool => in_array(
                            $record->meta_status,
                            [
                                WhatsAppTemplate::META_STATUS_NOT_SUBMITTED,
                                WhatsAppTemplate::META_STATUS_REJECTED,
                            ],
                            true
                        )
                    )
                    ->action(function (
                        WhatsAppTemplate $record
                    ): void {
                        $result = WhatsAppService::submitTemplate($record);

                        if ($result['status'] ?? false) {
                            Notification::make()
                                ->title('Template submitted to Meta')
                                ->body(
                                    (string) (
                                        $result['message']
                                        ?? 'Template review ke liye submit ho gaya.'
                                    )
                                )
                                ->success()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Meta submission failed')
                            ->body(
                                (string) (
                                    $result['message']
                                    ?? 'Template Meta ko submit nahi ho saka.'
                                )
                            )
                            ->danger()
                            ->persistent()
                            ->send();
                    }),

                Tables\Actions\Action::make('sync_meta_status')
                    ->label('Sync Status')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->visible(
                        fn (WhatsAppTemplate $record): bool =>
                            $record->meta_status
                                !== WhatsAppTemplate::META_STATUS_NOT_SUBMITTED
                            || filled($record->meta_template_id)
                    )
                    ->action(function (
                        WhatsAppTemplate $record
                    ): void {
                        $result = WhatsAppService::syncTemplateStatus($record);

                        if ($result['status'] ?? false) {
                            Notification::make()
                                ->title('Meta status synced')
                                ->body(
                                    (string) (
                                        $result['message']
                                        ?? 'Latest template status load ho gaya.'
                                    )
                                )
                                ->success()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Status sync failed')
                            ->body(
                                (string) (
                                    $result['message']
                                    ?? 'Meta se template status sync nahi hua.'
                                )
                            )
                            ->danger()
                            ->persistent()
                            ->send();
                    }),

                Tables\Actions\Action::make('send_test')
                    ->label('Send Test')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(
                        fn (WhatsAppTemplate $record): bool =>
                            $record->isReadyToSend()
                    )
                    ->form([
                        Forms\Components\TextInput::make('number')
                            ->label('WhatsApp Number')
                            ->helperText(
                                'Country code ke saath, example 919876543210'
                            )
                            ->required()
                            ->maxLength(20),

                        Forms\Components\Placeholder::make(
                            'test_variable_info'
                        )
                            ->label('Test Variables')
                            ->content(
                                fn (WhatsAppTemplate $record): string =>
                                    self::testVariableSummary($record)
                            ),
                    ])
                    ->action(function (
                        WhatsAppTemplate $record,
                        array $data
                    ): void {
                        $parameters = collect(
                            is_array($record->variables)
                                ? $record->variables
                                : []
                        )
                            ->sortBy(
                                fn (mixed $item): int =>
                                    is_array($item)
                                        ? (int) (
                                            $item['position']
                                            ?? $item['index']
                                            ?? 0
                                        )
                                        : 0
                            )
                            ->map(
                                fn (mixed $item): string =>
                                    is_array($item)
                                        ? trim((string) (
                                            $item['sample']
                                            ?? $item['key']
                                            ?? 'Sample'
                                        ))
                                        : 'Sample'
                            )
                            ->values()
                            ->all();

                        $result = WhatsAppService::sendByKey(
                            templateKey: (string) $record->template_key,
                            number: (string) $data['number'],
                            bodyParameters: $parameters,
                            languageCode: (string) $record->language
                        );

                        if ($result['status'] ?? false) {
                            Notification::make()
                                ->title('Test WhatsApp accepted')
                                ->body(
                                    (string) (
                                        $result['message']
                                        ?? 'Message Meta ne accept kar liya.'
                                    )
                                )
                                ->success()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Test WhatsApp failed')
                            ->body(
                                (string) (
                                    $result['message']
                                    ?? 'Test message send nahi hua.'
                                )
                            )
                            ->danger()
                            ->persistent()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(function (WhatsAppTemplate $record): void {
                        $copy = $record->replicate([
                            'meta_template_id',
                            'submitted_at',
                            'approved_at',
                            'rejected_at',
                            'last_synced_at',
                        ]);
                        $stamp = now()->format('YmdHis');

                        $copy->name = $record->name . ' Copy';
                        $copy->template_name =
                            $record->template_name . '_copy_' . $stamp;
                        $copy->template_key =
                            $record->template_key . '_copy_' . $stamp;
                        $copy->event_key =
                            $record->event_key . '.copy.' . $stamp;
                        $copy->status = WhatsAppTemplate::STATUS_DRAFT;
                        $copy->meta_status = WhatsAppTemplate::META_STATUS_NOT_SUBMITTED;
                        $copy->meta_rejection_reason = null;
                        $copy->is_active = false;
                        $copy->save();

                        Notification::make()
                            ->title('Template duplicated')
                            ->body(
                                'New draft template create ho gaya: '
                                . $copy->template_name
                            )
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No WhatsApp templates')
            ->emptyStateDescription('Apna pehla WhatsApp template create karein.')
            ->emptyStateIcon('heroicon-o-document-text')
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
            'index' => Pages\ListWhatsAppTemplates::route('/'),
            'create' => Pages\CreateWhatsAppTemplate::route('/create'),
            'edit' => Pages\EditWhatsAppTemplate::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()
            ->whereIn('meta_status', [
                WhatsAppTemplate::META_STATUS_NOT_SUBMITTED,
                WhatsAppTemplate::META_STATUS_PENDING,
                WhatsAppTemplate::META_STATUS_REJECTED,
            ])
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    private static function variableOptions(): array
    {
        return [
            'customer_name' => 'Customer Name',
            'customer_mobile' => 'Customer Mobile',
            'booking_id' => 'Booking ID',
            'service_type' => 'Service Type',
            'vehicle_name' => 'Vehicle Name',
            'vehicle_number' => 'Vehicle Number',
            'pickup' => 'Pickup',
            'drop' => 'Drop',
            'route' => 'Route',
            'travel_date' => 'Travel Date',
            'travel_time' => 'Travel Time',
            'driver_name' => 'Driver Name',
            'driver_mobile' => 'Driver Mobile',
            'total_amount' => 'Total Amount',
            'paid_amount' => 'Paid Amount',
            'remaining_amount' => 'Remaining Amount',
            'payment_status' => 'Payment Status',
            'payment_link' => 'Payment Link',
            'invoice_link' => 'Invoice Link',
            'otp' => 'OTP',
            'support_number' => 'Support Number',
        ];
    }

    private static function testVariableSummary(
        WhatsAppTemplate $record
    ): string {
        $variables = collect(
            is_array($record->variables)
                ? $record->variables
                : []
        )
            ->sortBy(
                fn (mixed $item): int =>
                    is_array($item)
                        ? (int) (
                            $item['position']
                            ?? $item['index']
                            ?? 0
                        )
                        : 0
            )
            ->map(function (mixed $item): string {
                if (! is_array($item)) {
                    return 'Sample';
                }

                $position = (int) (
                    $item['position']
                    ?? $item['index']
                    ?? 0
                );

                $key = trim((string) (
                    $item['key'] ?? 'variable'
                ));

                $sample = trim((string) (
                    $item['sample'] ?? 'Sample'
                ));

                return "{{{$position}}} {$key}: {$sample}";
            })
            ->values();

        return $variables->isEmpty()
            ? 'Is template me body variables nahi hain.'
            : $variables->implode("\n");
    }

    private static function buildPreview(Forms\Get $get): string
    {
        $parts = array_filter([
            trim((string) $get('header_text')),
            trim((string) $get('body')),
            trim((string) $get('footer')),
        ]);

        return $parts !== []
            ? implode("\n\n", $parts)
            : 'Template preview yahan dikhega.';
    }
}