<?php

namespace App\Filament\Resources\AppMediaResource\Tables;

use App\Enums\MediaType;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Table;

class AppMediaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')

            ->columns([

                Tables\Columns\ImageColumn::make('original_url')
                    ->label('')
                    ->getStateUsing(fn ($record) => \App\Support\MediaFormats::browserImage($record->mime_type)
                        ? route('admin.media-files.show', ['media' => $record->getKey()]) : null)
                    ->square()
                    ->size(70)
                    ->defaultImageUrl('data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="70" height="70"><rect width="70" height="70" rx="8" fill="#eef2ff"/><path d="M24 15h16l10 10v30H24z" fill="white" stroke="#475569" stroke-width="2"/><path d="M40 15v12h10M29 36h15M29 43h15" fill="none" stroke="#475569" stroke-width="2"/></svg>'))
                    ->url(fn ($record) => route('admin.media-files.show', ['media' => $record->getKey()])),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(35),

                Tables\Columns\BadgeColumn::make('media_type')
                    ->colors([
                        'primary',
                    ]),

                Tables\Columns\TextColumn::make('module')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('width')
                    ->label('Resolution')
                    ->formatStateUsing(function ($record) {
                        if (!$record->width) {
                            return '-';
                        }

                        return $record->width . ' × ' . $record->height;
                    }),

                Tables\Columns\TextColumn::make('formatted_original_size')
                    ->label('Original'),

                Tables\Columns\TextColumn::make('formatted_optimized_size')
                    ->label('Optimized'),

                Tables\Columns\TextColumn::make('saved_percentage')
                    ->label('Saved')
                    ->suffix('%')
                    ->sortable(),

                Tables\Columns\TextColumn::make('reference_count')
                    ->label('Used')
                    ->badge()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->since()
                    ->sortable()
                    ->toggleable(),

            ])

            ->filters([

                Tables\Filters\SelectFilter::make('media_type')
                    ->options(MediaType::options()),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),

                Tables\Filters\Filter::make('unused')
                    ->query(fn ($query) => $query->where('reference_count', 0))
                    ->label('Unused'),

            ])

           ->actions([

    EditAction::make(),

    Tables\Actions\Action::make('delete_media')
        ->label('Delete')
        ->icon('heroicon-o-trash')
        ->color('danger')
        ->requiresConfirmation()
        ->modalHeading('Delete media permanently?')
        ->modalDescription(
            'This will permanently delete the media record and its stored file. This action cannot be undone.'
        )
        ->modalSubmitActionLabel('Delete permanently')
        ->action(function ($record): void {
            app(\App\Actions\Media\DeleteMedia::class)
                ->forceDelete($record);
        })
        ->successNotificationTitle('Media deleted permanently'),

])
->bulkActions([

    BulkActionGroup::make([

        Tables\Actions\BulkAction::make('delete_media')
            ->label('Delete permanently')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Delete selected media permanently?')
            ->modalDescription(
                'The selected database records and their stored files will be permanently deleted.'
            )
            ->deselectRecordsAfterCompletion()
            ->action(function ($records): void {
                $deleteMedia = app(
                    \App\Actions\Media\DeleteMedia::class
                );

                foreach ($records as $record) {
                    $deleteMedia->forceDelete($record);
                }
            })
            ->successNotificationTitle(
                'Selected media deleted permanently'
            ),

    ]),

]);

            
    }
}