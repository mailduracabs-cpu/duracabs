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
                    ->disk('public')
                    ->square()
                    ->size(70)
                    ->defaultImageUrl(asset('images/no-image.png')),

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