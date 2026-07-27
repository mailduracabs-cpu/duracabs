<?php

namespace App\Filament\Widgets;

use App\Models\CustomerSearchActivity;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class AbandonedCustomerLeadsWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Abandoned Leads';

    protected static ?string $description =
        'Customers who showed intent but did not complete booking.';

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '120s';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CustomerSearchActivity::query()
                    ->notConverted()
                    ->where(function ($query): void {
                        $query
                            ->where('is_abandoned', true)
                            ->orWhereNotNull('abandoned_at');
                    })
                    ->latest('abandoned_at')
            )
            ->columns([
                TextColumn::make('customer_display_name')
                    ->label('Customer')
                    ->weight('bold')
                    ->description(
                        fn (CustomerSearchActivity $record): string =>
                            $record->mobile ?: 'No mobile'
                    )
                    ->searchable(['mobile']),

                TextColumn::make('service_label')
                    ->label('Service')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('route_summary')
                    ->label('Route')
                    ->limit(45)
                    ->wrap()
                    ->placeholder('Not available'),

                TextColumn::make('stage')
                    ->label('Last Stage')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string =>
                            str($state ?: 'unknown')
                                ->replace('_', ' ')
                                ->title()
                                ->toString()
                    )
                    ->color('warning'),

                TextColumn::make('grand_total')
                    ->label('Potential Value')
                    ->money('INR')
                    ->placeholder('Not available')
                    ->alignEnd(),

                TextColumn::make('intent_score')
                    ->label('Intent')
                    ->suffix('/100')
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        ((int) $state) >= 80 => 'danger',
                        ((int) $state) >= 60 => 'warning',
                        ((int) $state) >= 30 => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('abandoned_at')
                    ->label('Abandoned')
                    ->dateTime('d M Y, h:i A')
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(
                        fn (CustomerSearchActivity $record): string =>
                            route(
                                'filament.admin.resources.customer-leads.view',
                                ['record' => $record]
                            )
                    ),

                Tables\Actions\EditAction::make()
                    ->url(
                        fn (CustomerSearchActivity $record): string =>
                            route(
                                'filament.admin.resources.customer-leads.edit',
                                ['record' => $record]
                            )
                    ),
            ])
            ->defaultSort('abandoned_at', 'desc')
            ->paginated([5, 10, 25])
            ->emptyStateHeading('No abandoned leads')
            ->emptyStateDescription(
                'Abandoned customer journeys will appear here.'
            )
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
