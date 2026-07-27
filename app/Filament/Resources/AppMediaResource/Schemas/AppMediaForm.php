<?php

namespace App\Filament\Resources\AppMediaResource\Schemas;

use App\Enums\MediaType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Illuminate\Support\Str;

class AppMediaForm
{
    public static function configure(
        Form $form
    ): Form {
        return $form
            ->schema([
                Forms\Components\Section::make(
                    'Upload Media'
                )
                    ->description(
                        'Upload an image or document. '
                        . 'The file will be resized, '
                        . 'compressed and optimized '
                        . 'automatically after saving.'
                    )
                    ->icon('heroicon-o-cloud-arrow-up')
                    ->schema([
                        Forms\Components\FileUpload::make(
                            'upload'
                        )
                            ->label('Media File')
                            ->helperText(
                                'Images: JPG, PNG, WebP or GIF. '
                                . 'Documents: PDF is also supported.'
                            )
                            ->disk('local')
                            ->directory(
                                'temporary/app-media'
                            )
                            ->visibility('private')
                            ->preserveFilenames()
                            ->storeFileNamesIn(
                                'uploaded_file_name'
                            )
                            ->acceptedFileTypes(
                                self::acceptedFileTypes()
                            )
                            ->maxSize(15360)
                            ->imageEditor(
                                fn (
                                    Get $get
                                ): bool =>
                                    self::isImageType(
                                        $get('media_type')
                                    )
                            )
                            ->imagePreviewHeight('280')
                            ->downloadable()
                            ->openable()
                            ->required(
                                fn (
                                    string $operation
                                ): bool =>
                                    $operation === 'create'
                            )
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make(
                            'uploaded_file_name'
                        )
                            ->dehydrated(false),
                    ]),

                Forms\Components\Section::make(
                    'Media Information'
                )
                    ->description(
                        'Choose where this media will be used.'
                    )
                    ->icon('heroicon-o-information-circle')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        Forms\Components\TextInput::make(
                            'name'
                        )
                            ->label('Media Name')
                            ->placeholder(
                                'Example: Agra Self Drive Banner'
                            )
                            ->required()
                            ->maxLength(255)
                            ->live(
                                onBlur: true
                            )
                            ->afterStateUpdated(
                                function (
                                    ?string $state,
                                    Forms\Set $set
                                ): void {
                                    if (
                                        filled($state)
                                    ) {
                                        $set(
                                            'slug',
                                            Str::slug(
                                                $state
                                            )
                                        );
                                    }
                                }
                            ),

                        Forms\Components\TextInput::make(
                            'slug'
                        )
                            ->label('Slug')
                            ->maxLength(255)
                            ->unique(
                                table: 'app_media',
                                column: 'slug',
                                ignoreRecord: true
                            )
                            ->helperText(
                                'Generated automatically from the name.'
                            ),

                        Forms\Components\Select::make(
                            'media_type'
                        )
                            ->label('Media Type')
                            ->options(
                                MediaType::options()
                            )
                            ->default(
                                MediaType::Other->value
                            )
                            ->required()
                            ->native(false)
                            ->live()
                            ->searchable(),

                        Forms\Components\TextInput::make(
                            'module'
                        )
                            ->label('Module')
                            ->placeholder(
                                'Example: banners, self-drive, tours'
                            )
                            ->maxLength(100)
                            ->datalist([
                                'banners',
                                'self-drive',
                                'with-driver',
                                'vehicles',
                                'vendors',
                                'tours',
                                'destinations',
                                'offers',
                                'services',
                                'profiles',
                                'reviews',
                                'documents',
                            ]),

                        Forms\Components\TextInput::make(
                            'alt_text'
                        )
                            ->label('Alternative Text')
                            ->placeholder(
                                'Describe the image'
                            )
                            ->maxLength(255)
                            ->helperText(
                                'Used for accessibility, SEO '
                                . 'and when the image cannot load.'
                            )
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make(
                            'caption'
                        )
                            ->label('Caption')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make(
                    'Storage and Processing'
                )
                    ->description(
                        'Optimization settings are selected '
                        . 'automatically according to media type.'
                    )
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->columns([
                        'default' => 1,
                        'md' => 3,
                    ])
                    ->collapsed()
                    ->schema([
                        Forms\Components\Select::make(
                            'disk'
                        )
                            ->label('Storage Disk')
                            ->options([
                                'public' =>
                                    'Public Storage',
                            ])
                            ->default('public')
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make(
                            'quality'
                        )
                            ->label('Image Quality')
                            ->numeric()
                            ->minValue(40)
                            ->maxValue(100)
                            ->suffix('%')
                            ->placeholder(
                                'Automatic'
                            )
                            ->helperText(
                                'Leave empty to use the '
                                . 'recommended quality.'
                            ),

                        Forms\Components\TextInput::make(
                            'sort_order'
                        )
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),

                        Forms\Components\Toggle::make(
                            'is_active'
                        )
                            ->label('Active')
                            ->default(true)
                            ->inline(false),

                        Forms\Components\Toggle::make(
                            'is_public'
                        )
                            ->label('Public File')
                            ->default(
                                fn (
                                    Get $get
                                ): bool =>
                                    $get('media_type')
                                    !== MediaType::Document
                                        ->value
                            )
                            ->helperText(
                                'Private is recommended '
                                . 'for vehicle and identity documents.'
                            )
                            ->inline(false),

                        Forms\Components\KeyValue::make(
                            'metadata'
                        )
                            ->label('Additional Metadata')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->addActionLabel(
                                'Add Metadata'
                            )
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make(
                    'Processed File Information'
                )
                    ->description(
                        'These fields are filled automatically '
                        . 'by the Dura Media Engine.'
                    )
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 4,
                    ])
                    ->visible(
                        fn (
                            string $operation
                        ): bool =>
                            $operation === 'edit'
                    )
                    ->collapsed()
                    ->schema([
                        Forms\Components\Placeholder::make(
                            'original_file'
                        )
                            ->label('Original File')
                            ->content(
                                fn (
                                    $record
                                ): string =>
                                    $record?->original_name
                                    ?: 'Not available'
                            ),

                        Forms\Components\Placeholder::make(
                            'resolution'
                        )
                            ->label('Original Resolution')
                            ->content(
                                function (
                                    $record
                                ): string {
                                    if (
                                        !$record?->width ||
                                        !$record?->height
                                    ) {
                                        return 'Not available';
                                    }

                                    return $record->width
                                        . ' × '
                                        . $record->height
                                        . ' px';
                                }
                            ),

                        Forms\Components\Placeholder::make(
                            'original_size_display'
                        )
                            ->label('Original Size')
                            ->content(
                                fn (
                                    $record
                                ): string =>
                                    $record
                                        ?->formatted_original_size
                                    ?: '0 B'
                            ),

                        Forms\Components\Placeholder::make(
                            'optimized_size_display'
                        )
                            ->label('Generated Variants')
                            ->content(
                                fn (
                                    $record
                                ): string =>
                                    $record
                                        ?->formatted_optimized_size
                                    ?: '0 B'
                            ),

                        Forms\Components\Placeholder::make(
                            'compression_saved'
                        )
                            ->label('Storage Reduction')
                            ->content(
                                fn (
                                    $record
                                ): string =>
                                    number_format(
                                        (float) (
                                            $record
                                                ?->saved_percentage
                                            ?? 0
                                        ),
                                        2
                                    ) . '%'
                            ),

                        Forms\Components\Placeholder::make(
                            'reference_count_display'
                        )
                            ->label('Usage Count')
                            ->content(
                                fn (
                                    $record
                                ): string =>
                                    (string) (
                                        $record
                                            ?->reference_count
                                        ?? 0
                                    )
                            ),

                        Forms\Components\Placeholder::make(
                            'file_hash_display'
                        )
                            ->label('SHA-256 Hash')
                            ->content(
                                fn (
                                    $record
                                ): string =>
                                    $record?->file_hash
                                    ?: 'Not available'
                            )
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * @return array<int, string>
     */
    private static function acceptedFileTypes(): array
    {
        return [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
            'application/pdf',
        ];
    }

    private static function isImageType(
        mixed $type
    ): bool {
        if (
            blank($type)
        ) {
            return true;
        }

        return $type !==
            MediaType::Document->value;
    }
}