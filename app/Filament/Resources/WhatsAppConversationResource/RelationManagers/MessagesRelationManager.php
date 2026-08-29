<?php

namespace App\Filament\Resources\WhatsAppConversationResource\RelationManagers;

use App\Models\WhatsAppMessage;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

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

                Tables\Columns\TextColumn::make('from_number')
                    ->label('From')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('to_number')
                    ->label('To')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime('d M Y, h:i:s A')
                    ->sortable(),
            ])

            ->filters([])

            ->headerActions([])

            ->actions([])

            ->bulkActions([])

            ->emptyStateHeading('No messages yet')
            ->emptyStateDescription(
                'Messages received from this WhatsApp customer will appear here.'
            )
            ->emptyStateIcon('heroicon-o-chat-bubble-left-right');
    }
}