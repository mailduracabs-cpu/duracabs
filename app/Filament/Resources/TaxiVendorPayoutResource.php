<?php
namespace App\Filament\Resources;
use App\Models\TaxiVendorPayout;
use App\Services\PartnerPayoutPaymentService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class TaxiVendorPayoutResource extends Resource {
    protected static ?string $model = TaxiVendorPayout::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'With Driver';
    protected static ?string $navigationLabel = 'Vendor Payouts';
    public static function paymentForm(): array {
        return [
            Forms\Components\Hidden::make('request_key')->default(fn () => (string)Str::uuid()),
            Forms\Components\TextInput::make('amount')->label('Actual Payment Amount')->numeric()->required()->minValue(0.01)->prefix('₹'),
            Forms\Components\DateTimePicker::make('payment_date')->label('Actual Payment Date / Time')->default(now())->required()->maxDate(now())->seconds(false),
            Forms\Components\Select::make('method')->label('Payment Mode')->options(['cash'=>'Cash','upi'=>'UPI','bank_transfer'=>'Bank Transfer','cheque'=>'Cheque','other'=>'Other'])->required(),
            Forms\Components\TextInput::make('reference')->label('Reference / UTR')->maxLength(255),
            Forms\Components\Textarea::make('notes')->label('Payment Note'),
        ];
    }
    public static function form(Form $form): Form {
        return $form->schema([
            Forms\Components\TextInput::make('payout_no')->disabled(),
            Forms\Components\TextInput::make('order_id')->label('Booking ID')->disabled(),
            Forms\Components\TextInput::make('payout_amount')->label('Total Earnings')->prefix('₹')->disabled(),
            Forms\Components\TextInput::make('paid_amount')->label('Received')->prefix('₹')->disabled(),
            Forms\Components\TextInput::make('remaining_amount')->label('Pending')->prefix('₹')->disabled(),
            Forms\Components\Textarea::make('notes')->disabled(),
        ]);
    }
    public static function table(Table $table): Table {
        return $table->defaultSort('period_from','desc')->columns([
            Tables\Columns\TextColumn::make('payout_no')->searchable(),
            Tables\Columns\TextColumn::make('order.booking_number')->label('Booking'),
            Tables\Columns\TextColumn::make('transporter_profile_id')->label('Partner ID')->sortable(),
            Tables\Columns\TextColumn::make('period_from')->label('Booking Date')->date('d M Y')->sortable(),
            Tables\Columns\TextColumn::make('payout_amount')->label('Total')->money('INR'),
            Tables\Columns\TextColumn::make('paid_amount')->label('Received')->money('INR'),
            Tables\Columns\TextColumn::make('remaining_amount')->label('Pending')->money('INR'),
            Tables\Columns\TextColumn::make('status')->badge(),
        ])->filters([
            Tables\Filters\SelectFilter::make('status')->options(['pending'=>'Pending','partial'=>'Partial','paid'=>'Paid']),
            Tables\Filters\Filter::make('partner')->form([Forms\Components\TextInput::make('partner_id')->label('Partner ID')->numeric()])
                ->query(fn ($query,array $data) => $query->when($data['partner_id'] ?? null,fn ($q,$id) => $q->where('transporter_profile_id',$id))),
        ])->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\Action::make('pay')->label('Pay Vendor')->visible(fn ($record) => $record->status !== 'cancelled' && (float)$record->remaining_amount > 0)
                ->form(self::paymentForm())->action(function ($record,array $data): void {
                    app(PartnerPayoutPaymentService::class)->pay('vendor',$record->id,$data['amount'],$data['method'],
                        $data['reference'] ?? null,$data['payment_date'],$data['notes'] ?? null,$data['request_key']);
                    \Filament\Notifications\Notification::make()->title('Payment recorded')->success()->send();
                }),
        ])->bulkActions([
            Tables\Actions\BulkAction::make('pay_selected')->label('Pay selected bookings')->form(self::paymentForm())
                ->action(function ($records,array $data): void {
                    app(PartnerPayoutPaymentService::class)->paySelected('vendor',$records->modelKeys(),$data);
                    \Filament\Notifications\Notification::make()->title('Payment allocated to oldest selected bookings')->success()->send();
                })->deselectRecordsAfterCompletion(),
        ]);
    }
    public static function canCreate(): bool { return false; }
    public static function getRelations(): array { return [SelfDriveVendorPayoutResource\RelationManagers\PaymentsRelationManager::class]; }
    public static function getPages(): array { return [
        'index'=>TaxiVendorPayoutResource\Pages\ListTaxiVendorPayouts::route('/'),
        'view'=>TaxiVendorPayoutResource\Pages\ViewTaxiVendorPayout::route('/{record}'),
    ]; }
}
