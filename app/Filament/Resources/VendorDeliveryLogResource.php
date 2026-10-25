<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VendorDeliveryLogResource\Pages\ListVendorDeliveryLogs;
use App\Models\VendorDeliveryLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class VendorDeliveryLogResource extends Resource
{
    protected static ?string $model = VendorDeliveryLog::class;
    protected static ?string $navigationGroup = 'Fleet Management';
    protected static ?string $navigationLabel = 'Vendor message delivery';
    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    public static function canViewAny(): bool { return auth()->user()?->canUseAdminLogin() ?? false; }
    public static function canCreate(): bool { return false; }
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool { return false; }
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool { return false; }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->columns([
            Tables\Columns\TextColumn::make('payload.title')->label('Message'),
            Tables\Columns\TextColumn::make('channel'),
            Tables\Columns\TextColumn::make('recipient')->searchable(),
            Tables\Columns\TextColumn::make('status')->badge(),
            Tables\Columns\TextColumn::make('attempts'),
            Tables\Columns\TextColumn::make('sent_at')->dateTime(),
            Tables\Columns\TextColumn::make('last_error')->label('Delivery detail')->limit(100)->wrap(),
        ])->actions([
            Tables\Actions\Action::make('retryUnsent')->label('Retry unsent message')
                ->visible(fn ($record) => $record->status === 'failed')->requiresConfirmation()
                ->action(function ($record): void {
                    abort_unless(static::canViewAny(), 403);
                    DB::table('vendor_delivery_logs')->where('id', $record->id)->where('status', 'failed')
                        ->update(['status' => 'pending', 'updated_at' => now()]);
                }),
        ])->bulkActions([]);
    }
    public static function getPages(): array { return ['index' => ListVendorDeliveryLogs::route('/')]; }
}
