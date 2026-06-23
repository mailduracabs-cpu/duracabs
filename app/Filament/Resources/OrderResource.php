<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Filament\Resources\OrderResource\RelationManagers\AddressRelationManager;
use App\Filament\Resources\OrderResource\RelationManagers\InvoicesRelationManager;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vehicle;
use Filament\Forms;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Number;
use Notification;
use Filament\Tables\Actions\Action;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?int $navigationSort = 7;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationLabel = 'Bookings';



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()->schema([
                    Section::make('Order Information')->schema([
                        Card::make()->schema([
                            Select::make('user_id')
                                ->label('Customer')
                                ->relationship('user', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('transporter_id')
                                ->label('Transporter')
                                ->options(
                                    User::role('Transporter') // comes from spatie/laravel-permission
                                        ->pluck('name', 'id')
                                        ->toArray()
                                )
                                ->searchable()
                                ->preload(),

                            Select::make('driver_id')
                                ->label('Driver')
                                ->options(function (callable $get) {
                                    $transporterId = $get('transporter_id');

                                    if (!$transporterId) {
                                        return [];
                                    }

                                    return User::role('Driver')
                                        ->where('created_by', $transporterId)
                                        ->pluck('name', 'id');
                                })
                                ->searchable()
                                ->preload(),

                            Select::make('vehicle_id')
                                ->label('Vehicle')
                                ->options(function (callable $get) {
                                    $transporterId = $get('transporter_id');
                                    if (!$transporterId)
                                        return [];

                                    return Vehicle::where('user_id', $transporterId)
                                        ->pluck('vehicle_number', 'id');
                                })
                                ->searchable()
                                ->preload()

                        ])->columns(),

                        Group::make()->schema([
                            TextInput::make('cityFrom')
                            ,
                            TextInput::make('cityTo')
                            ,
                            TextInput::make('coupon_value')
                            ,
                            TextInput::make('coupon_name')
                            ,
                            TextInput::make('productName')
                            ,
                            DatePicker::make('date'),
                            DatePicker::make('dateTo'),
                            TimePicker::make('time')
                                ->seconds(false)
                                ->datalist([
                                    '04:00',
                                    '04:30',
                                    '5:00',
                                    '5:30',
                                    '6:00',
                                    '6:30',
                                    '7:00',
                                    '7:30',
                                    '08:00',
                                    '08:30',
                                    '9:00',
                                    '9:30',
                                    '10:00',
                                    '10:30',
                                    '11:00',
                                    '11:30',
                                    '12:00',
                                    '12:30',
                                    '13:00',
                                    '13:30',
                                    '14:00',
                                    '14:30',
                                    '15:00',
                                    '15:30',
                                    '16:00',
                                    '16:30',
                                    '17:00',
                                    '17:30',
                                    '18:00',
                                    '18:30',
                                    '19:00',
                                    '19:30',
                                    '20:00',
                                    '20:30',
                                    '21:00',
                                    '21:30',
                                    '22:00',
                                    '22:30',
                                    '23:00',
                                    '23:30',
                                    '24:00',
                                    '24:30',
                                    '1:00',
                                    '1:30',
                                    '2:00',
                                    '2:30',
                                    '3:00',
                                    '3:30',
                                ])
                            ,
                            TimePicker::make('endTime')
                                ->seconds(false)
                                ->datalist([
                                    '04:00',
                                    '04:30',
                                    '5:00',
                                    '5:30',
                                    '6:00',
                                    '6:30',
                                    '7:00',
                                    '7:30',
                                    '08:00',
                                    '08:30',
                                    '9:00',
                                    '9:30',
                                    '10:00',
                                    '10:30',
                                    '11:00',
                                    '11:30',
                                    '12:00',
                                    '12:30',
                                    '13:00',
                                    '13:30',
                                    '14:00',
                                    '14:30',
                                    '15:00',
                                    '15:30',
                                    '16:00',
                                    '16:30',
                                    '17:00',
                                    '17:30',
                                    '18:00',
                                    '18:30',
                                    '19:00',
                                    '19:30',
                                    '20:00',
                                    '20:30',
                                    '21:00',
                                    '21:30',
                                    '22:00',
                                    '22:30',
                                    '23:00',
                                    '23:30',
                                    '24:00',
                                    '24:30',
                                    '1:00',
                                    '1:30',
                                    '2:00',
                                    '2:30',
                                    '3:00',
                                    '3:30',
                                ])
                            ,
                            Select::make('payment_method')
                                ->options([
                                    'RazorPay' => 'RazorPay',
                                    'cod' => 'Pay By Cash',

                                ])
                                ->default('cod')
                            ,
                            Select::make('payment_status')
                                ->options([
                                    'pending' => 'Pending',
                                    'paid' => 'Paid',
                                    'failed' => 'Failed'
                                ])
                                ->default('pending')
                                ->required(),
                            Select::make('currency')
                                ->options([
                                    'inr' => "INR",
                                    'usd' => "USD",
                                    'eur' => "EURO",
                                    'gbp' => "GBP",
                                ])
                                ->default('inr')
                                ->required(),
                            Select::make('taxi_type')
                                ->label('Taxi Type')
                                ->options([
                                    'fedx' => 'SUV',
                                    'ups' => "Sedan",
                                    'dhl' => 'Hetchbeg',
                                    'usps' => 'MUV'
                                ]),
                            Select::make('ride_type')
                                ->options([
                                    'one_way' => 'One Way',
                                    'return' => 'Return',
                                    'local' => 'Local',
                                    'self_drive' => 'Self Drive',
                                ]),
                            Select::make('plan')
                                ->label('Plan')
                                ->options([
                                    '4 Hour / 40 Km' => '4 Hour / 40 Km',
                                    '8 Hour / 80 Km' => "8 Hour / 80 Km",
                                    '12 Hour / 120 Km' => '12 Hour / 120 Km',
                                ]),

                        ])->columns(2),


                        ToggleButtons::make('status')
                            ->inline()
                            ->default('new')
                            ->required()
                            ->options([
                                'new' => "Waiting Responce",
                                'reconfirmation' => 'Reconfirmation / Follow Up',
                                'confirm' => 'Confirm',
                                'modification' => 'Modification',
                                'start' => 'Trip Start',
                                'cancelled' => 'Cancelled / Not Interested',
                                'closed' => 'Closed',
                                'refund' => 'refund'
                            ])
                            ->colors([
                                'new' => "info",
                                'start' => 'warning',
                                'modification' => 'success',
                                'confirm' => 'success',
                                'reconfirmation' => 'success',
                                'cancelled' => 'danger',
                                'closed' => 'success',
                                'refund' => 'info'
                            ])
                            ->icons([
                                'new' => "heroicon-m-sparkles",
                                'start' => 'heroicon-m-truck',
                                'modification' => 'heroicon-m-arrow-path',
                                'confirm' => 'heroicon-m-check-badge',
                                'cancelled' => 'heroicon-m-x-circle',
                                'reconfirmation' => 'heroicon-m-phone-arrow-down-left',
                                'closed' => 'heroicon-m-clipboard-document-check',
                                'refund' => 'heroicon-m-currency-pound'
                            ]),


                        Textarea::make('notes')
                            ->columnSpanFull(),
                        Section::make('Order Items')->schema([
                            Repeater::make('items')
                                ->relationship()
                                ->schema([
                                    Select::make('product_id')
                                        ->relationship('product', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->distinct()
                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                        ->columnSpan(4)
                                        ->reactive()
                                        ->afterStateUpdated(fn($state, Set $set) => $set('unit_ammount', Product::find($state)?->price ?? 0))
                                        ->afterStateUpdated(fn($state, Set $set) => $set('total_ammount', Product::find($state)?->price ?? 0))

                                    ,

                                    TextInput::make('quantity')
                                        ->numeric()
                                        ->required()
                                        ->default(1)
                                        ->minValue(1)
                                        ->columnSpan(2)
                                        ->reactive()
                                        ->afterStateUpdated(fn($state, Set $set, $get) => $set('total_ammount', $state * $get('unit_ammount')))
                                    ,

                                    TextInput::make('unit_ammount')
                                        ->numeric()
                                        ->required()
                                        ->disabled()
                                        ->dehydrated()
                                        ->columnSpan(3),

                                    TextInput::make('total_ammount')
                                        ->numeric()
                                        ->required()
                                        ->dehydrated()
                                        ->columnSpan(3),

                                ])->columns(12),
                            // Placeholder::make('grand_total_placeholder')
                            //     ->label('Grand Total')
                            //     ->content(function (Get $get, Set $set) {
                            //         $total = 0;
                            //         if (!$repeaters = $get('items')) {
                            //             return $total;
                            //         }
                            //         foreach ($repeaters as $key => $repeaters) { {

                            //                 $total += $get("items.{$key}.total_ammount");
                            //             }
                            //         }

                            //         $set('grand_total', $total);
                            //         return Number::currency($total, 'INR');


                            //     }),
                            TextInput::make('grand_total')
                                ->default(0)

                        ])
                    ])
                ])->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('Order ID')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->sortable()
                    ->searchable()
                    ->label('Customer'),
                TextColumn::make('transporter_id')
                    ->label('Transporter')
                    ->sortable()
                     ->formatStateUsing(fn($state) => User::where('id', $state)->value('name'))
                ,
                TextColumn::make('driver_id')
                    ->label('Driver')
                    ->sortable()
                     ->formatStateUsing(fn($state) => User::where('id', $state)->value('name'))
                ,

                TextColumn::make('vehicle_id')
                    ->label('Vehicle')
                    ->sortable()
                   
                     ->formatStateUsing(fn($state) => strtoupper(Vehicle::where('id', $state)->value('vehicle_number')))
                ,
                TextColumn::make('grand_total')
                    ->numeric()
                    ->searchable()
                    ->money('INR')
                    ->label('Grand Total'),
                TextColumn::make('payment_method')
                    ->sortable()
                    ->searchable(),
                
                TextColumn::make("ride_type")
                    ->sortable()
                    ->searchable()
                ,

                SelectColumn::make('status')
                    ->options([
                        'new' => "Waiting Responce",
                        'reconfirmation' => 'Reconfirmation / Follow Up',
                        'confirm' => 'Confirm',
                        'modification' => 'Modification',
                        'start' => 'Trip Start',
                        'cancelled' => 'Cancelled / Not Interested',
                        'closed' => 'Closed',
                        'refund' => 'refund'
                    ])
                    ->searchable()
                    ->sortable()

                ,
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('view_record')
                    ->label('View ')
                    ->url(fn($record) => route('success', ['id' => $record->id]))
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-eye'),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),

                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AddressRelationManager::class,
            InvoicesRelationManager::class
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        //return static::getModel()::count();
        return false;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getModel()::count() > 10 ? 'danger' : 'success';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),

            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {

        $user = auth()->user()->roles->pluck('name')[0];

        if ($user === "Admin") {
            return parent::getEloquentQuery();
        }

        if ($user === "Transporter") {
            return parent::getEloquentQuery()->where('transporter_id', auth()->id());
        }


        return parent::getEloquentQuery()->where('driver_id', auth()->id());

    }
}
