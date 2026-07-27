<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SmartHomeBlockResource\Pages;
use App\Models\SmartHomeBlock;
use App\Services\SmartBannerService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


class SmartHomeBlockResource extends Resource
{
    protected static ?string $model = SmartHomeBlock::class;

    protected static ?string $navigationGroup = 'Website Management';

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationLabel = 'Smart Home CMS';

    protected static ?string $modelLabel = 'Smart Home Block';

    protected static ?string $pluralModelLabel = 'Smart Home Blocks';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    /**
     * Homepage block types.
     */
    public static function blockTypeOptions(): array
    {
        return [
            'hero' => 'Hero Banner',
            'popular_route' => 'Popular Route',
            'featured_vehicle' => 'Featured Vehicle',
            'recommended_vehicle' => 'Recommended Vehicle',
            'self_drive' => 'Self Drive',
            'offer' => 'Offer',
            'festival' => 'Festival',
        ];
    }

    /**
     * Supported service types.
     */
    public static function serviceTypeOptions(): array
    {
        return [
            'one_way' => 'One Way',
            'round_trip' => 'Round Trip',
            'local' => 'Local Taxi',
            'airport' => 'Airport',
            'self_drive' => 'Self Drive',
            'bike' => 'Bike Rental',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Block Configuration')
                    ->description(
                        'Choose where this block will appear and which service it represents.'
                    )
                    ->schema([
                        Forms\Components\Select::make('block_type')
                            ->label('Block Type')
                            ->options(static::blockTypeOptions())
                            ->required()
                            ->native(false)
                            ->searchable()
                            ->live(),

                        Forms\Components\Select::make('service_type')
                            ->label('Service Type')
                            ->options(static::serviceTypeOptions())
                            ->required()
                            ->native(false)
                            ->searchable()
                            ->live(),

                        Forms\Components\TextInput::make('priority')
                            ->label('Display Order')
                            ->helperText(
                                'Lower number appears first. Example: 1, 2, 3.'
                            )
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(9999)
                            ->default(1),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->helperText(
                                'Inactive blocks will not appear on website or app.'
                            )
                            ->default(true)
                            ->inline(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Route and Location')
                    ->description(
                        'Select route cities. Drop city is optional for local, self-drive and bike rental.'
                    )
                    ->schema([
                        Forms\Components\Select::make('from_city_id')
    ->label('From City')
    ->options(fn (): array => static::cityOptions())
    ->native(false)
    ->searchable()
    ->preload()
    ->required(
        fn (Forms\Get $get): bool =>
            in_array($get('service_type'), [
                'one_way',
                'round_trip',
                'local',
                'airport',
                'self_drive',
                'bike',
            ], true)
    ),

Forms\Components\Select::make('to_city_id')
    ->label('To City')
    ->options(fn (): array => static::cityOptions())
    ->native(false)
    ->searchable()
    ->preload()
    ->different('from_city_id')
    ->required(
        fn (Forms\Get $get): bool =>
            in_array($get('service_type'), [
                'one_way',
                'round_trip',
                'airport',
            ], true)
    ),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Content')
                    ->description(
                        'Leave title and subtitle empty to generate them automatically.'
                    )
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Custom Title')
                            ->placeholder(
                                'Example: Agra to Delhi Taxi'
                            )
                            ->maxLength(255)
                            ->nullable(),

                        Forms\Components\TextInput::make('subtitle')
                            ->label('Custom Subtitle')
                            ->placeholder(
                                'Example: Reliable cabs at transparent fares'
                            )
                            ->maxLength(255)
                            ->nullable(),

                        Forms\Components\Toggle::make('is_dynamic')
                            ->label('Dynamic Content')
                            ->helperText(
                                'Automatically generate fare, vehicle, image and fallback text.'
                            )
                            ->default(true)
                            ->inline(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Scheduling')
                    ->description(
                        'Both fields are optional. Empty schedule means the block remains available continuously.'
                    )
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Start Date and Time')
                            ->seconds(false)
                            ->native(false)
                            ->nullable(),

                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('End Date and Time')
                            ->seconds(false)
                            ->native(false)
                            ->nullable()
                            ->afterOrEqual('starts_at'),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('priority')
            ->reorderable('priority')
            ->columns([
                Tables\Columns\TextColumn::make('priority')
                    ->label('Order')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('block_type')
                    ->label('Block')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string =>
                            static::blockTypeOptions()[$state]
                            ?? static::formatLabel($state)
                    )
                    ->color(
                        fn (?string $state): string => match ($state) {
                            'hero' => 'primary',
                            'popular_route' => 'success',
                            'featured_vehicle' => 'warning',
                            'recommended_vehicle' => 'info',
                            'self_drive' => 'danger',
                            'offer' => 'success',
                            'festival' => 'warning',
                            default => 'gray',
                        }
                    )
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('service_type')
                    ->label('Service')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string =>
                            static::serviceTypeOptions()[$state]
                            ?? static::formatLabel($state)
                    )
                    ->color(
                        fn (?string $state): string => match ($state) {
                            'one_way' => 'primary',
                            'round_trip' => 'success',
                            'local' => 'warning',
                            'airport' => 'info',
                            'self_drive' => 'danger',
                            'bike' => 'gray',
                            default => 'gray',
                        }
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('route')
                    ->label('Route')
                    ->state(
                        fn (SmartHomeBlock $record): string =>
                            static::routeLabel($record)
                    )
                    ->wrap()
                    ->placeholder('All locations'),

                Tables\Columns\TextColumn::make('title')
                    ->label('Custom Title')
                    ->placeholder('Auto Generated')
                    ->limit(35)
                    ->tooltip(
                        fn (SmartHomeBlock $record): ?string =>
                            $record->title
                    )
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_dynamic')
                    ->label('Dynamic')
                    ->boolean()
                    ->trueIcon('heroicon-o-bolt')
                    ->falseIcon('heroicon-o-pencil-square'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('schedule_status')
                    ->label('Schedule')
                    ->state(
                        fn (SmartHomeBlock $record): string =>
                            static::scheduleStatus($record)
                    )
                    ->badge()
                    ->color(
                        fn (SmartHomeBlock $record): string =>
                            static::scheduleColor($record)
                    ),

                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Starts')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('Immediately')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Ends')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('No expiry')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->dateTimeTooltip('d M Y, h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('block_type')
                    ->label('Block Type')
                    ->options(static::blockTypeOptions())
                    ->multiple(),

                Tables\Filters\SelectFilter::make('service_type')
                    ->label('Service Type')
                    ->options(static::serviceTypeOptions())
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->placeholder('All blocks'),

                Tables\Filters\TernaryFilter::make('is_dynamic')
                    ->label('Content Mode')
                    ->trueLabel('Dynamic only')
                    ->falseLabel('Manual only')
                    ->placeholder('All modes'),

                Tables\Filters\Filter::make('currently_available')
                    ->label('Currently Available')
                    ->query(
                        fn (Builder $query): Builder => $query
                            ->where('is_active', true)
                            ->where(function (Builder $query): void {
                                $query
                                    ->whereNull('starts_at')
                                    ->orWhere('starts_at', '<=', now());
                            })
                            ->where(function (Builder $query): void {
                                $query
                                    ->whereNull('ends_at')
                                    ->orWhere('ends_at', '>=', now());
                            })
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('toggle_status')
                    ->label(
                        fn (SmartHomeBlock $record): string =>
                            $record->is_active
                                ? 'Deactivate'
                                : 'Activate'
                    )
                    ->icon(
                        fn (SmartHomeBlock $record): string =>
                            $record->is_active
                                ? 'heroicon-o-pause-circle'
                                : 'heroicon-o-play-circle'
                    )
                    ->color(
                        fn (SmartHomeBlock $record): string =>
                            $record->is_active
                                ? 'warning'
                                : 'success'
                    )
                    ->requiresConfirmation()
                    ->action(
                        function (SmartHomeBlock $record): void {
                            $record->update([
                                'is_active' => !$record->is_active,
                            ]);

                            app(SmartBannerService::class)
                                ->clearCache();
                        }
                    ),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\ReplicateAction::make()
                        ->excludeAttributes([
                            'created_at',
                            'updated_at',
                        ])
                        ->beforeReplicaSaved(
                            function (SmartHomeBlock $replica): void {
                                $replica->title = $replica->title
                                    ? $replica->title . ' Copy'
                                    : null;

                                $replica->is_active = false;
                            }
                        )
                        ->after(
                            fn (): mixed =>
                                app(SmartBannerService::class)
                                    ->clearCache()
                        ),

                    Tables\Actions\DeleteAction::make()
                        ->after(
                            fn (): mixed =>
                                app(SmartBannerService::class)
                                    ->clearCache()
                        ),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(
                            function ($records): void {
                                $records->each(
                                    fn (SmartHomeBlock $record) =>
                                        $record->update([
                                            'is_active' => true,
                                        ])
                                );

                                app(SmartBannerService::class)
                                    ->clearCache();
                            }
                        )
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-pause')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(
                            function ($records): void {
                                $records->each(
                                    fn (SmartHomeBlock $record) =>
                                        $record->update([
                                            'is_active' => false,
                                        ])
                                );

                                app(SmartBannerService::class)
                                    ->clearCache();
                            }
                        )
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make()
                        ->after(
                            fn (): mixed =>
                                app(SmartBannerService::class)
                                    ->clearCache()
                        ),
                ]),
            ])
            ->emptyStateHeading('No Smart Home blocks found')
            ->emptyStateDescription(
                'Create the first block to start building the dynamic homepage.'
            )
            ->emptyStateIcon('heroicon-o-sparkles');
    }

    /**
     * City options without requiring a City model relationship.
     */
   public static function cityOptions(): array
{
    if (!Schema::hasTable('brands')) {
        return [];
    }

    return DB::table('brands')
        ->where('is_active', true)
        ->whereNotNull('name')
        ->where('name', '!=', '')
        ->orderBy('name')
        ->pluck('name', 'id')
        ->mapWithKeys(
            function (mixed $name, mixed $id): array {
                return [
                    (string) $id => trim((string) $name),
                ];
            }
        )
        ->all();
}

    public static function routeLabel(
        SmartHomeBlock $record
    ): string {
        $cities = static::cityOptions();

        $fromCity = $record->from_city_id
            ? ($cities[(string) $record->from_city_id] ?? null)
            : null;

        $toCity = $record->to_city_id
            ? ($cities[(string) $record->to_city_id] ?? null)
            : null;

        if ($fromCity && $toCity) {
            return "{$fromCity} → {$toCity}";
        }

        if ($fromCity) {
            return $fromCity;
        }

        if ($toCity) {
            return $toCity;
        }

        return 'All locations';
    }

    public static function scheduleStatus(
        SmartHomeBlock $record
    ): string {
        if (!$record->is_active) {
            return 'Inactive';
        }

        if (
            $record->starts_at
            && now()->lt($record->starts_at)
        ) {
            return 'Scheduled';
        }

        if (
            $record->ends_at
            && now()->gt($record->ends_at)
        ) {
            return 'Expired';
        }

        return 'Live';
    }

    public static function scheduleColor(
        SmartHomeBlock $record
    ): string {
        return match (static::scheduleStatus($record)) {
            'Live' => 'success',
            'Scheduled' => 'info',
            'Expired' => 'danger',
            'Inactive' => 'gray',
            default => 'gray',
        };
    }

    public static function formatLabel(
        ?string $value
    ): string {
        return ucwords(
            str_replace(
                ['_', '-'],
                ' ',
                (string) $value
            )
        );
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSmartHomeBlocks::route('/'),
            'create' => Pages\CreateSmartHomeBlock::route(
                '/create'
            ),
            'edit' => Pages\EditSmartHomeBlock::route(
                '/{record}/edit'
            ),
        ];
    }
}