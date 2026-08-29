<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WhatsAppConversationResource\Pages;
use App\Models\WhatsAppConversation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WhatsAppConversationResource extends Resource
{
    protected static ?string $model = WhatsAppConversation::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'WhatsApp Inbox';

    protected static ?string $modelLabel = 'WhatsApp Conversation';

    protected static ?string $pluralModelLabel = 'WhatsApp Inbox';

    protected static ?string $navigationGroup = 'WhatsApp';

    protected static ?int $navigationSort = 1;

    /**
     * Show total unread messages in sidebar badge.
     */
    public static function getNavigationBadge(): ?string
    {
        $count = WhatsAppConversation::query()
            ->sum('unread_count');

        return $count > 0
            ? (string) $count
            : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    /**
     * Conversation detail form.
     *
     * Actual chat messages/reply UI will be added in the next step.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Customer')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('customer_name')
                            ->label('Customer Name')
                            ->disabled(),

                        Forms\Components\TextInput::make('mobile')
                            ->label('Mobile Number')
                            ->disabled(),

                        Forms\Components\TextInput::make('wa_id')
                            ->label('WhatsApp ID')
                            ->disabled(),

                        Forms\Components\TextInput::make('status')
                            ->label('Conversation Status')
                            ->disabled(),
                    ]),

                Forms\Components\Section::make('Latest Message')
                    ->schema([
                        Forms\Components\Textarea::make('last_message')
                            ->label('Last Message')
                            ->rows(4)
                            ->disabled(),

                        Forms\Components\TextInput::make('last_message_type')
                            ->label('Message Type')
                            ->disabled(),

                        Forms\Components\TextInput::make('last_message_direction')
                            ->label('Direction')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('last_message_at')
                            ->label('Last Message At')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('WhatsApp Service Window')
                    ->columns(2)
                    ->schema([
                        Forms\Components\DateTimePicker::make('last_customer_message_at')
                            ->label('Last Customer Message')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('service_window_expires_at')
                            ->label('24 Hour Window Expires')
                            ->disabled(),

                        Forms\Components\TextInput::make('unread_count')
                            ->label('Unread Messages')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('read_at')
                            ->label('Read At')
                            ->disabled(),
                    ]),
            ]);
    }

    /**
     * WhatsApp Inbox table.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('last_message_at', 'desc')

            ->columns([
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->default('Unknown Customer')
                    ->searchable()
                    ->weight('bold')
                    ->description(function (WhatsAppConversation $record): string {
                        return self::formatPhoneNumber(
                            $record->mobile ?: $record->wa_id
                        );
                    }),

                Tables\Columns\TextColumn::make('last_message')
                    ->label('Last Message')
                    ->placeholder('No message')
                    ->limit(60)
                    ->wrap()
                    ->tooltip(function (WhatsAppConversation $record): ?string {
                        return $record->last_message;
                    }),

                Tables\Columns\TextColumn::make('last_message_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(function (?string $state): string {
                        return match ($state) {
                            'text' => 'Text',
                            'image' => 'Image',
                            'video' => 'Video',
                            'audio' => 'Audio',
                            'document' => 'Document',
                            'location' => 'Location',
                            'contacts' => 'Contact',
                            'interactive' => 'Interactive',
                            'button' => 'Button',
                            'reaction' => 'Reaction',
                            default => ucfirst((string) ($state ?: 'Unknown')),
                        };
                    })
                    ->color(function (?string $state): string {
                        return match ($state) {
                            'text' => 'gray',
                            'image' => 'info',
                            'video' => 'info',
                            'audio' => 'warning',
                            'document' => 'primary',
                            'location' => 'success',
                            default => 'gray',
                        };
                    }),

                Tables\Columns\TextColumn::make('unread_count')
                    ->label('Unread')
                    ->badge()
                    ->alignCenter()
                    ->color(fn ($state): string => ((int) $state > 0)
                        ? 'danger'
                        : 'gray')
                    ->formatStateUsing(fn ($state): string => (string) ((int) $state)),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string => ucfirst($state ?: 'open')
                    )
                    ->color(function (?string $state): string {
                        return match ($state) {
                            'open' => 'success',
                            'closed' => 'gray',
                            'pending' => 'warning',
                            default => 'gray',
                        };
                    }),

                Tables\Columns\TextColumn::make('service_window_expires_at')
                    ->label('24h Window')
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        if (!$state) {
                            return 'Unknown';
                        }

                        return now()->lt($state)
                            ? 'Open'
                            : 'Expired';
                    })
                    ->color(function ($state): string {
                        if (!$state) {
                            return 'gray';
                        }

                        return now()->lt($state)
                            ? 'success'
                            : 'danger';
                    }),

                Tables\Columns\TextColumn::make('last_message_at')
                    ->label('Last Message')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Started')
                    ->dateTime('d M Y, h:i A')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
            ])

            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Conversation Status')
                    ->options([
                        'open' => 'Open',
                        'closed' => 'Closed',
                    ]),

                Tables\Filters\Filter::make('unread')
                    ->label('Unread Only')
                    ->query(
                        fn (Builder $query): Builder =>
                        $query->where('unread_count', '>', 0)
                    ),

                Tables\Filters\Filter::make('service_window_open')
                    ->label('24 Hour Window Open')
                    ->query(
                        fn (Builder $query): Builder =>
                        $query->whereNotNull('service_window_expires_at')
                            ->where(
                                'service_window_expires_at',
                                '>',
                                now()
                            )
                    ),
            ])

            ->actions([
                Tables\Actions\Action::make('markRead')
                    ->label('Mark Read')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(
                        fn (WhatsAppConversation $record): bool =>
                        (int) $record->unread_count > 0
                    )
                    ->action(function (WhatsAppConversation $record): void {
                        $record->markAsRead();
                    }),

                Tables\Actions\EditAction::make()
                    ->label('Open Chat')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('primary'),
            ])

            ->bulkActions([])

            ->emptyStateHeading('No WhatsApp conversations yet')
            ->emptyStateDescription(
                'Customer WhatsApp messages will automatically appear here.'
            )
            ->emptyStateIcon('heroicon-o-chat-bubble-left-right');
    }

    /**
     * Show latest conversations first.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->orderByDesc('last_message_at')
            ->orderByDesc('id');
    }

    public static function getRelations(): array
    {
        return [];
    }

    /**
     * No manual Create page.
     * Conversations are created automatically by WhatsApp webhook.
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWhatsAppConversations::route('/'),
            'edit' => Pages\EditWhatsAppConversation::route('/{record}/edit'),
        ];
    }

    private static function formatPhoneNumber(?string $number): string
    {
        $number = preg_replace('/\D+/', '', (string) $number);

        if (!$number) {
            return 'No mobile number';
        }

        if (str_starts_with($number, '91') && strlen($number) === 12) {
            return '+91 ' .
                substr($number, 2, 5) .
                ' ' .
                substr($number, 7);
        }

        return '+' . $number;
    }
}