<?php
namespace App\Filament\Resources\SelfDriveVendorPayoutResource\RelationManagers;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
class PaymentsRelationManager extends RelationManager {
    protected static string $relationship = 'payments';
    protected static ?string $title = 'Recorded Payments';
    public function table(Table $table): Table {
        return $table->defaultSort('payment_date','desc')->columns([
            Tables\Columns\TextColumn::make('payment_date')->label('Payment Date')->dateTime('d M Y h:i A')->sortable(),
            Tables\Columns\TextColumn::make('amount')->money('INR'),
            Tables\Columns\TextColumn::make('method')->label('Mode'),
            Tables\Columns\TextColumn::make('reference')->label('Reference / UTR'),
            Tables\Columns\TextColumn::make('notes')->wrap(),
            Tables\Columns\TextColumn::make('created_at')->label('Recorded At')->dateTime('d M Y h:i A'),
        ])->headerActions([])->actions([])->bulkActions([]);
    }
}
