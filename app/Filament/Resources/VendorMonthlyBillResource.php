<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VendorMonthlyBillResource\Pages\ListVendorMonthlyBills;
use App\Models\VendorMonthlyBill;
use App\Services\VendorBillSettlement;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VendorMonthlyBillResource extends Resource
{
    protected static ?string $model = VendorMonthlyBill::class;
    protected static ?string $navigationGroup = 'Fleet Management';
    protected static ?string $navigationLabel = 'Monthly vendor bills';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function canViewAny(): bool { return auth()->user()?->canUseAdminLogin() ?? false; }
    public static function canCreate(): bool { return false; }
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool { return false; }
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool { return false; }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('bill_date', 'desc')->columns([
            Tables\Columns\TextColumn::make('bill_no')->searchable(),
            Tables\Columns\TextColumn::make('terms.vehicle_number')->label('Vehicle')->searchable(false),
            Tables\Columns\TextColumn::make('transporter_profile_id')->label('Vendor ID'),
            Tables\Columns\TextColumn::make('period_start')->date(),
            Tables\Columns\TextColumn::make('period_end')->date(),
            Tables\Columns\TextColumn::make('bill_date')->date(),
            Tables\Columns\TextColumn::make('amount')->money('INR'),
            Tables\Columns\TextColumn::make('status')->badge(),
            Tables\Columns\TextColumn::make('payment_reference'),
        ])->actions([
            Tables\Actions\Action::make('copy')->label('Bill copy')->url(fn ($record) => route('vendor-lifecycle.admin-bill', $record->id))->openUrlInNewTab(),
            Tables\Actions\Action::make('recordPayment')->label('Record completed payment')
                ->visible(fn ($record) => $record->status === 'pending')
                ->modalDescription('Use only after the vendor has actually been paid. This records a receipt; it does not transfer money.')
                ->form([TextInput::make('reference')->label('Bank UTR / payment receipt reference')->required()->maxLength(150)])
                ->action(function ($record, array $data): void {
                    abort_unless(static::canViewAny(), 403);
                    app(VendorBillSettlement::class)->markPaid($record->id, auth()->user(), $data['reference']);
                }),
        ])->bulkActions([]);
    }

    public static function getPages(): array { return ['index' => ListVendorMonthlyBills::route('/')]; }
}
