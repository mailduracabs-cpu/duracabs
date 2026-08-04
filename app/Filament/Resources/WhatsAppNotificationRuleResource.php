<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WhatsAppNotificationRuleResource\Pages;
use App\Models\WhatsAppNotificationRule;
use App\Models\WhatsAppTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WhatsAppNotificationRuleResource extends Resource
{
    protected static ?string $model =
        WhatsAppNotificationRule::class;

    protected static ?string $navigationIcon =
        'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationGroup = 'WhatsApp';

    protected static ?string $navigationLabel =
        'Notification Rules';

    protected static ?string $modelLabel =
        'WhatsApp Notification Rule';

    protected static ?string $pluralModelLabel =
        'WhatsApp Notification Rules';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(
                'Event and Template'
            )
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Rule Name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('event_key')
                        ->label('Event Key')
                        ->helperText(
                            'Example: booking.created'
                        )
                        ->required()
                        ->regex('/^[a-z0-9_.-]+$/')
                        ->unique(
                            table:
                                WhatsAppNotificationRule::class,
                            column: 'event_key',
                            ignoreRecord: true
                        )
                        ->maxLength(160),

                    Forms\Components\Select::make(
                        'template_key'
                    )
                        ->label('WhatsApp Template')
                        ->options(
                            fn (): array =>
                                WhatsAppTemplate::query()
                                    ->orderBy('name')
                                    ->pluck(
                                        'name',
                                        'template_key'
                                    )
                                    ->filter()
                                    ->all()
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->native(false),

                    Forms\Components\Toggle::make(
                        'is_active'
                    )
                        ->label('Rule Active')
                        ->default(true)
                        ->inline(false),

                    Forms\Components\Textarea::make(
                        'description'
                    )
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make(
                'Send Notification To'
            )
                ->description(
                    'Select recipients for this event.'
                )
                ->schema([
                    Forms\Components\Toggle::make(
                        'send_customer'
                    )
                        ->label('Customer'),

                    Forms\Components\Toggle::make(
                        'send_vendor'
                    )
                        ->label('Vendor'),

                    Forms\Components\Toggle::make(
                        'send_driver'
                    )
                        ->label('Driver'),

                    Forms\Components\Toggle::make(
                        'send_admin'
                    )
                        ->label('Admin'),

                    Forms\Components\Toggle::make(
                        'send_sales'
                    )
                        ->label('Sales'),

                    Forms\Components\Toggle::make(
                        'send_operations'
                    )
                        ->label('Operations'),

                    Forms\Components\Toggle::make(
                        'send_accounts'
                    )
                        ->label('Accounts'),

                    Forms\Components\Toggle::make(
                        'send_support'
                    )
                        ->label('Support'),
                ])
                ->columns(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Rule')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(
                        fn (
                            WhatsAppNotificationRule $record
                        ): string => $record->event_key
                    ),

                Tables\Columns\TextColumn::make(
                    'template_key'
                )
                    ->label('Template Key')
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make(
                    'recipients'
                )
                    ->label('Recipients')
                    ->state(
                        fn (
                            WhatsAppNotificationRule $record
                        ): string => implode(
                            ', ',
                            array_map(
                                'ucfirst',
                                $record
                                    ->enabledRecipientTypes()
                            )
                        )
                    )
                    ->placeholder('None')
                    ->wrap(),

                Tables\Columns\ToggleColumn::make(
                    'is_active'
                )
                    ->label('Active'),

                Tables\Columns\TextColumn::make(
                    'updated_at'
                )
                    ->label('Updated')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make(
                    'is_active'
                )
                    ->label('Active Rules'),

                Tables\Filters\SelectFilter::make(
                    'template_key'
                )
                    ->label('Template')
                    ->options(
                        fn (): array =>
                            WhatsAppTemplate::query()
                                ->orderBy('name')
                                ->pluck(
                                    'name',
                                    'template_key'
                                )
                                ->filter()
                                ->all()
                    )
                    ->searchable()
                    ->preload()
                    ->native(false),
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
            ->emptyStateHeading(
                'No WhatsApp notification rules'
            )
            ->emptyStateDescription(
                'Create the first event-to-recipient rule.'
            )
            ->emptyStateIcon(
                'heroicon-o-adjustments-horizontal'
            )
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' =>
                Pages\ListWhatsAppNotificationRules::route(
                    '/'
                ),

            'create' =>
                Pages\CreateWhatsAppNotificationRule::route(
                    '/create'
                ),

            'edit' =>
                Pages\EditWhatsAppNotificationRule::route(
                    '/{record}/edit'
                ),
        ];
    }
}