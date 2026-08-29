<?php

namespace App\Filament\Resources\WhatsAppConversationResource\RelationManagers;

use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Throwable;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = 'Chat History';

    protected static ?string $recordTitleAttribute = 'body';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'asc')
            ->paginated(false)

            ->columns([
                Tables\Columns\TextColumn::make('direction')
                    ->label('Direction')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string =>
                        $state === 'outgoing' ? 'Sent' : 'Received'
                    )
                    ->color(
                        fn (?string $state): string =>
                        $state === 'outgoing' ? 'success' : 'info'
                    ),

                Tables\Columns\TextColumn::make('body')
                    ->label('Message')
                    ->placeholder(function (WhatsAppMessage $record): string {
                        return match ($record->type) {
                            'image' => '📷 Image',
                            'video' => '🎥 Video',
                            'audio' => '🎵 Audio',
                            'document' => '📄 Document',
                            'location' => '📍 Location',
                            'contacts' => '👤 Contact',
                            'reaction' => '❤️ Reaction',
                            default => 'No message text',
                        };
                    })
                    ->wrap()
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string =>
                        ucfirst($state ?: 'text')
                    )
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

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string =>
                        ucfirst($state ?: 'received')
                    )
                    ->color(function (?string $state): string {
                        return match ($state) {
                            'sent' => 'primary',
                            'delivered' => 'info',
                            'read' => 'success',
                            'received' => 'success',
                            'failed' => 'danger',
                            default => 'gray',
                        };
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime('d M Y, h:i:s A')
                    ->sortable(),
            ])

            ->filters([])

            ->headerActions([
                Tables\Actions\Action::make('reply')
                    ->label('Reply')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->form([
                        Forms\Components\Textarea::make('message')
                            ->label('Message')
                            ->required()
                            ->rows(5)
                            ->maxLength(4096)
                            ->placeholder('Type your WhatsApp reply...'),
                    ])
                    ->modalHeading('Reply on WhatsApp')
                    ->modalSubmitActionLabel('Send Message')
                    ->action(function (array $data): void {
                        /** @var WhatsAppConversation $conversation */
                        $conversation = $this->getOwnerRecord();

                        $message = trim((string) ($data['message'] ?? ''));

                        if ($message === '') {
                            Notification::make()
                                ->title('Message is required')
                                ->danger()
                                ->send();

                            return;
                        }

                        $number = preg_replace(
                            '/\D+/',
                            '',
                            (string) ($conversation->mobile ?: $conversation->wa_id)
                        );

                        if (!$number) {
                            Notification::make()
                                ->title('Customer WhatsApp number is missing')
                                ->danger()
                                ->send();

                            return;
                        }

                        if (
                            !$conversation->service_window_expires_at ||
                            now()->greaterThan($conversation->service_window_expires_at)
                        ) {
                            Notification::make()
                                ->title('24-hour WhatsApp window expired')
                                ->body(
                                    'A free-form reply cannot be sent now. Send an approved WhatsApp template to reopen the conversation.'
                                )
                                ->warning()
                                ->send();

                            return;
                        }

                        try {
                            $result = WhatsAppService::sendMessage(
                                $number,
                                $message
                            );

                            $success = (bool) (
                                $result['status']
                                ?? $result['success']
                                ?? false
                            );

                            $messageId = $result['message_id'] ?? null;

                            $outgoing = WhatsAppMessage::create([
                                'whats_app_conversation_id' => $conversation->id,
                                'message_id' => $messageId,
                                'direction' => 'outgoing',
                                'type' => 'text',
                                'from_number' => $conversation->display_phone_number
                                    ?: config('services.whatsapp.display_phone_number'),
                                'to_number' => $number,
                                'body' => $message,
                                'content' => [
                                    'body' => $message,
                                ],
                                'status' => $success ? 'sent' : 'failed',
                                'sent_at' => $success ? now() : null,
                                'failed_at' => $success ? null : now(),
                                'admin_id' => Auth::id(),
                                'metadata' => [
                                    'source' => 'filament_admin',
                                ],
                                'raw_payload' => $result['response'] ?? null,
                                'errors' => $success
                                    ? null
                                    : [
                                        'message' => $result['message'] ?? null,
                                        'error' => $result['error'] ?? null,
                                        'status_code' => $result['status_code'] ?? null,
                                    ],
                            ]);

                            if (!$success) {
                                Notification::make()
                                    ->title('WhatsApp message failed')
                                    ->body(
                                        (string) (
                                            $result['message']
                                            ?? $result['error']
                                            ?? 'Meta rejected the WhatsApp message.'
                                        )
                                    )
                                    ->danger()
                                    ->send();

                                return;
                            }

                            $conversation->forceFill([
                                'last_message' => $message,
                                'last_message_type' => 'text',
                                'last_message_direction' => 'outgoing',
                                'last_message_at' => now(),
                            ])->save();

                            Notification::make()
                                ->title('WhatsApp message sent')
                                ->body('Reply has been sent successfully.')
                                ->success()
                                ->send();

                        } catch (Throwable $exception) {
                            WhatsAppMessage::create([
                                'whats_app_conversation_id' => $conversation->id,
                                'direction' => 'outgoing',
                                'type' => 'text',
                                'to_number' => $number,
                                'body' => $message,
                                'content' => [
                                    'body' => $message,
                                ],
                                'status' => 'failed',
                                'failed_at' => now(),
                                'admin_id' => Auth::id(),
                                'metadata' => [
                                    'source' => 'filament_admin',
                                ],
                                'errors' => [
                                    'message' => $exception->getMessage(),
                                ],
                            ]);

                            Notification::make()
                                ->title('WhatsApp send failed')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])

            ->actions([])

            ->bulkActions([])

            ->emptyStateHeading('No messages yet')
            ->emptyStateDescription(
                'Messages received from this WhatsApp customer will appear here.'
            )
            ->emptyStateIcon('heroicon-o-chat-bubble-left-right');
    }
}