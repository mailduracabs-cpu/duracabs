<?php

namespace App\Filament\Resources;

use App\Actions\Media\DeleteMedia;
use App\Filament\Resources\BannersResource\Pages;
use App\Forms\Components\DuraImageUpload;
use App\Forms\Components\DuraSeo;
use App\Models\AppMedia;
use App\Models\Banners;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Log;
use Throwable;

class BannersResource extends Resource
{
    protected static ?string $model = Banners::class;

    protected static ?string $navigationGroup = 'Website Management';

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'Home Banners';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Banner Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Banner Name')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('title')
                                    ->label('Heading')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('url')
                                    ->label('Button / Redirect URL')
                                    ->maxLength(500),

                                Select::make('ride_type')
                                    ->label('Banner Type')
                                    ->required()
                                    ->native(false)
                                    ->options([
                                        'home' => 'Home',
                                        'one_way' => 'One Way',
                                        'return' => 'Return',
                                        'local' => 'Local',
                                        'airport' => 'Airport',
                                        'self_drive' => 'Self Drive',
                                        'tour' => 'Tour Package',
                                        'offer' => 'Offer',
                                    ]),
                            ]),

                        /*
                        |--------------------------------------------------------------------------
                        | Existing banner preview
                        |--------------------------------------------------------------------------
                        |
                        | Edit page par current Media Library ya legacy image
                        | yahan display hogi.
                        |
                        */

                        Placeholder::make('existing_banner_preview')
                            ->label('Current Banner Image')
                            ->content(
                                function (?Banners $record): HtmlString {
                                    if (!$record instanceof Banners) {
                                        return new HtmlString(
                                            '<span class="text-sm text-gray-500">
                                                No existing image.
                                            </span>'
                                        );
                                    }

                                    $imageUrl = $record->image_url;

                                    if (blank($imageUrl)) {
                                        return new HtmlString(
                                            '<span class="text-sm text-gray-500">
                                                No existing image.
                                            </span>'
                                        );
                                    }

                                    $safeUrl = e($imageUrl);
                                    $safeName = e(
                                        $record->name ?? 'Banner'
                                    );

                                    return new HtmlString(
                                        <<<HTML
                                        <div style="
                                            width: 100%;
                                            max-width: 900px;
                                            overflow: hidden;
                                            border: 1px solid #e5e7eb;
                                            border-radius: 12px;
                                            background: #f9fafb;
                                        ">
                                            <img
                                                src="{$safeUrl}"
                                                alt="{$safeName}"
                                                style="
                                                    display: block;
                                                    width: 100%;
                                                    max-height: 320px;
                                                    object-fit: contain;
                                                "
                                            >
                                        </div>
                                        HTML
                                    );
                                }
                            )
                            ->visible(
                                fn (?Banners $record): bool =>
                                    $record instanceof Banners
                                    && filled($record->image_url)
                            )
                            ->columnSpanFull(),

                        /*
                        |--------------------------------------------------------------------------
                        | New/replacement image
                        |--------------------------------------------------------------------------
                        |
                        | Create page par required hai.
                        | Edit page par optional hai; file select karne par
                        | existing media replace hogi.
                        |
                        */

                        DuraImageUpload::banner(
                            name: 'media_upload',
                            module: 'banners',
                        )
                            ->label('Replace Banner Image')
                            ->required(
                                fn (string $operation): bool =>
                                    $operation === 'create'
                            )
                            ->helperText(
                                fn (string $operation): string =>
                                    $operation === 'edit'
                                        ? 'Leave this empty to keep the current banner image. Upload a new file only when you want to replace it.'
                                        : 'JPG, PNG, WebP or GIF • Maximum 25 MB • Image will be compressed and converted automatically.'
                            ),

                        Hidden::make('app_media_id'),
                    ]),

                DuraSeo::make(),

                Section::make('Status')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Banner')
                    ->square()
                    ->size(70),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('ride_type')
                    ->label('Type')
                    ->formatStateUsing(
                        fn (?string $state): string =>
                            match ($state) {
                                'home' => 'Home',
                                'one_way' => 'One Way',
                                'return' => 'Return',
                                'local' => 'Local',
                                'airport' => 'Airport',
                                'self_drive' => 'Self Drive',
                                'tour' => 'Tour Package',
                                'offer' => 'Offer',
                                default => ucfirst(
                                    str_replace(
                                        '_',
                                        ' ',
                                        (string) $state
                                    )
                                ),
                            }
                    )
                    ->colors([
                        'success',
                    ]),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make()
                    ->after(
                        function (Banners $record): void {
                            static::deleteBannerMedia($record);
                        }
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(
                            function (Collection $records): void {
                                foreach ($records as $record) {
                                    if ($record instanceof Banners) {
                                        static::deleteBannerMedia(
                                            $record
                                        );
                                    }
                                }
                            }
                        ),
                ]),
            ]);
    }

    /**
     * Permanently delete the banner's media record and files.
     */
    protected static function deleteBannerMedia(
        Banners $record
    ): void {
        $mediaId = $record->getRawOriginal(
            'app_media_id'
        );

        if (blank($mediaId)) {
            Log::warning(
                'Banner deleted without an app_media_id.',
                [
                    'banner_id' => $record->getKey(),
                    'banner_name' => $record->name,
                    'legacy_image' => $record->getRawOriginal(
                        'image'
                    ),
                ]
            );

            return;
        }

        $media = AppMedia::withTrashed()
            ->find($mediaId);

        if (!$media instanceof AppMedia) {
            Log::warning(
                'Banner media record was not found during deletion.',
                [
                    'banner_id' => $record->getKey(),
                    'app_media_id' => $mediaId,
                ]
            );

            return;
        }

        try {
            app(DeleteMedia::class)->forceDelete(
                media: $media,
                ignoreReferences: true,
            );
        } catch (Throwable $exception) {
            Log::error(
                'Banner deleted but its media cleanup failed.',
                [
                    'banner_id' => $record->getKey(),
                    'app_media_id' => $mediaId,
                    'exception' => $exception->getMessage(),
                ]
            );
        }
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBanners::route('/'),

            'create' => Pages\CreateBanners::route('/create'),

            'edit' => Pages\EditBanners::route('/{record}/edit'),
        ];
    }
}