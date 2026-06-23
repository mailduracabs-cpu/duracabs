<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MyDriverResource\Pages;
use App\Filament\Resources\MyDriverResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Pages\Page;
use Filament\Forms\Get;
use Illuminate\Support\Facades\Auth;




class MyDriverResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'Drivers';


    public static function form(Form $form): Form
    {
        return $form
           ->schema([
                Card::make()->schema([
                    TextInput::make('name')->required(),

                    TextInput::make('email')->label('Email Address')->maxlength(255)->email()->required()->unique(ignoreRecord: true),
                    DateTimePicker::make('email_verified_at')->label('Email Varified At')->default((now())),
                    TextInput::make('password')
                        ->password()
                        ->dehydrated(fn($state) => filled($state))
                        ->required(fn(Page $livewire): bool => $livewire instanceof CreateRecord),
                    Toggle::make('is_active')->disabled(auth()->user()->roles->pluck('name')[0] != 'Admin' && auth()->user()->roles->pluck('name')[0] != 'Transporter')->label('Is Active')->default(true),
                ])->columns(),
                Card::make()->schema([
                    Select::make('roles')
                        ->relationship('roles', 'name')
                        ->reactive()
                        ->live()
                        ->options(function (Get $get) {
                            $user = Auth::user();

                            if (!$user) {
                                return [];
                            }

                            if ($user->hasRole('Admin')) {
                                return [
                                    1 => 'Admin',
                                    2 => 'Transporter',
                                    4 => 'Driver',
                                    3 => 'Moderator',
                                ];
                            }

                            if ($user->hasRole('Transporter')) {
                                return [
                                    4 => 'Driver',
                                ];
                            }

                            return [];
                        })
                    ,
               
                    Select::make('created_by')
    ->label('Created By')
    ->options(
        User::role('Transporter') // comes from spatie/laravel-permission
            ->pluck('name', 'id')
            ->toArray()
    )
    ->searchable()
    ->preload()
    ->hidden(auth()->user()->roles->pluck('name')[0] != 'Admin')
                    

                     
                    // Select::make('permissions')
                    //     ->multiple()
                    //     ->relationship('permissions', 'name')
                    //     ->preload()

                ])->columns()->hidden(auth()->user()->roles->pluck('name')[0] != 'Transporter' && auth()->user()->roles->pluck('name')[0] != 'Admin'),

                

                Card::make()->schema([
                    TextInput::make('company_name'),
                    TextInput::make('gst_number'),
                    TextInput::make('aadhar_number'),
                    TextInput::make('office_address'),
                    FileUpload::make('gst_image')
                        ->image(),
                    FileUpload::make('aadhar_image')
                        ->image(),

                ])->columns()->hidden(fn(string $operation): bool => $operation === 'create' || $form->model->roles->pluck('name')[0] != 'Transporter'),


                Card::make()->schema([

                    TextInput::make('aadhar_number'),
                    TextInput::make('driving_licence_number'),
                    TextInput::make('mobile')->label('Mobile Number'),

                    FileUpload::make('aadhar_image')
                        ->image(),
                    FileUpload::make('driving_licence_image')
                        ->image(),

                ])->columns()->hidden(auth()->user()->roles->pluck('name')[0] != 'Transporter' && auth()->user()->roles->pluck('name')[0] != 'Admin'),





            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->searchable(),

                TextColumn::make('created_by')
                    ->searchable()
                    ->label('Created By')   
                    ->formatStateUsing(fn ($state) => User::where('id', $state)->value('name'))
                ,

                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->visible(auth()->user()->roles->pluck('name')[0] == 'Admin' )
                   
                    ,
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMyDrivers::route('/'),
            'create' => Pages\CreateMyDriver::route('/create'),
            'edit' => Pages\EditMyDriver::route('/{record}/edit'),
        ];
    }
    

     public static function getEloquentQuery(): Builder
    {
        $user = auth()->user()->roles->pluck('name')[0];

        if ($user === "Transporter") {
            return parent::getEloquentQuery()->whereHas('roles', function (Builder $query) {
                $query->where('name', 'Driver');
            })->where('created_by', auth()->user()->id  );
        } elseif ($user === "Admin") {    
            return parent::getEloquentQuery()->whereHas('roles', function (Builder $query) {
                $query->where('name', 'Driver');
            });
        }

        // Default: return parent query (or restrict as needed)
        return parent::getEloquentQuery()->whereRaw('1 = 0');
    }
}
