<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Filament\Resources\UserResource\RelationManagers\AddressRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\OrdersRelationManager;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Forms\Get;
use Filament\Pages\Page;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Closure;
use Spatie\Permission\Traits\HasRoles;
use Filament\Tables\Filters\SelectFilter;


class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Setting';

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
                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),



            ])
            ->filters([
                SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\DeleteAction::make()
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
            OrdersRelationManager::class,
            AddressRelationManager::class

        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user()->roles->pluck('name')[0];




        if ($user === "Admin") {
            return parent::getEloquentQuery();
        }
        return parent::getEloquentQuery()->where('id', auth()->id());

    }
}
