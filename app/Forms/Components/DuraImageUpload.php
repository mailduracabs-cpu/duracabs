<?php

namespace App\Forms\Components;

use App\Enums\MediaType;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Str;

final class DuraImageUpload
{
    /**
     * Create a temporary Media Engine upload field.
     *
     * The selected file is stored temporarily.
     * The related Filament Create/Edit page must send the temporary
     * path to ProcessMediaUpload or ReplaceMedia.
     */
    public static function make(
        string $name = 'media_upload',
        string $module = 'uploads',
        MediaType|string|null $mediaType = null,
        bool $multiple = false,
    ): FileUpload {
        $resolvedType = self::resolveMediaType(
            mediaType: $mediaType,
            module: $module,
        );

        $temporaryDirectory = sprintf(
            'temporary/dura-media/%s/%s',
            Str::slug($module) ?: 'uploads',
            now()->format('Y/m')
        );

        $maximumSize = $resolvedType->isDocument()
            ? (int) config(
                'dura-media.max_document_size_kb',
                51200
            )
            : (int) config(
                'dura-media.max_image_size_kb',
                25600
            );

        $acceptedTypes = $resolvedType->isDocument()
            ? (array) config(
                'dura-media.document_mime_types',
                self::defaultDocumentMimeTypes()
            )
            : (array) config(
                'dura-media.image_mime_types',
                self::defaultImageMimeTypes()
            );

        $field = FileUpload::make($name)
            ->label(
                $resolvedType->isDocument()
                    ? 'Upload Document'
                    : 'Upload Image'
            )
            ->disk('local')
            ->directory($temporaryDirectory)
            ->visibility('private')
            ->acceptedFileTypes([...$acceptedTypes, 'application/octet-stream', ...($resolvedType->isDocument() ? ['application/zip', 'application/x-zip-compressed'] : [])])
            ->rules([fn () => function ($attribute, $value, $fail) use ($resolvedType): void {
                if ($value instanceof \Illuminate\Http\UploadedFile) {
                    try { \App\Support\MediaFormats::inspect($value, $resolvedType->isDocument(), $attribute); }
                    catch (\Illuminate\Validation\ValidationException $e) { $fail(collect($e->errors())->flatten()->first()); }
                }
            }])
            ->maxSize($maximumSize)
            // Random stored names prevent collisions; keep the display name separately.
            ->storeFileNamesIn(
                $name . '_original_name'
            )
            ->downloadable()
            ->openable()
            ->previewable()
            ->panelLayout('integrated')
            ->loadingIndicatorPosition('left')
            ->removeUploadedFileButtonPosition('right')
            ->uploadButtonPosition('left')
            ->uploadProgressIndicatorPosition('left')
            ->helperText(
                self::helperText(
                    type: $resolvedType,
                    maximumSize: $maximumSize,
                )
            )
            ->columnSpanFull();

        if (!$resolvedType->isDocument()) {
            $field
                ->imageEditor(false)
                ->imagePreviewHeight('220')
                ->panelAspectRatio(
                    self::panelAspectRatio(
                        $resolvedType
                    )
                );
        }

        if ($multiple) {
            $field
                ->multiple()
                ->reorderable()
                ->appendFiles()
                ->maxFiles(20);
        }

        return $field;
    }

    /**
     * Banner upload shortcut.
     */
    public static function banner(
        string $name = 'media_upload',
        string $module = 'banners',
    ): FileUpload {
        return self::make(
            name: $name,
            module: $module,
            mediaType: MediaType::Banner,
        );
    }

    /**
     * Vehicle primary image shortcut.
     */
    public static function vehicle(
        string $name = 'media_upload',
        string $module = 'vehicles',
    ): FileUpload {
        return self::make(
            name: $name,
            module: $module,
            mediaType: MediaType::Vehicle,
        );
    }

    /**
     * Vehicle gallery shortcut.
     */
    public static function vehicleGallery(
        string $name = 'media_uploads',
        string $module = 'vehicle-gallery',
    ): FileUpload {
        return self::make(
            name: $name,
            module: $module,
            mediaType: MediaType::Vehicle,
            multiple: true,
        );
    }

    /**
     * Tour image shortcut.
     */
    public static function tour(
        string $name = 'media_upload',
        string $module = 'tours',
    ): FileUpload {
        return self::make(
            name: $name,
            module: $module,
            mediaType: MediaType::Tour,
        );
    }

    /**
     * Offer image shortcut.
     */
    public static function offer(
        string $name = 'media_upload',
        string $module = 'offers',
    ): FileUpload {
        return self::make(
            name: $name,
            module: $module,
            mediaType: MediaType::Offer,
        );
    }

    /**
     * Profile image shortcut.
     */
    public static function profile(
        string $name = 'media_upload',
        string $module = 'profiles',
    ): FileUpload {
        return self::make(
            name: $name,
            module: $module,
            mediaType: MediaType::Profile,
        );
    }

    /**
     * Private document shortcut.
     */
    public static function document(
        string $name = 'document_upload',
        string $module = 'documents',
    ): FileUpload {
        return self::make(
            name: $name,
            module: $module,
            mediaType: MediaType::Document,
        );
    }

    private static function resolveMediaType(
        MediaType|string|null $mediaType,
        string $module,
    ): MediaType {
        if ($mediaType instanceof MediaType) {
            return $mediaType;
        }

        if (
            is_string($mediaType) &&
            filled($mediaType)
        ) {
            return MediaType::tryFrom($mediaType)
                ?? MediaType::Other;
        }

        $module = strtolower($module);

        return match (true) {
            str_contains($module, 'banner') =>
                MediaType::Banner,

            str_contains($module, 'vehicle'),
            str_contains($module, 'car') =>
                MediaType::Vehicle,

            str_contains($module, 'tour') =>
                MediaType::Tour,

            str_contains($module, 'destination') =>
                MediaType::Destination,

            str_contains($module, 'offer') =>
                MediaType::Offer,

            str_contains($module, 'profile'),
            str_contains($module, 'avatar') =>
                MediaType::Profile,

            str_contains($module, 'document'),
            str_contains($module, 'aadhaar'),
            str_contains($module, 'licence'),
            str_contains($module, 'license'),
            str_contains($module, 'insurance'),
            str_contains($module, 'permit'),
            str_contains($module, 'rc') =>
                MediaType::Document,

            default => MediaType::Other,
        };
    }

    private static function panelAspectRatio(
        MediaType $type,
    ): string {
        return match ($type) {
            MediaType::Banner => '16:6',
            MediaType::Offer => '16:9',

            MediaType::Profile,
            MediaType::Icon => '1:1',

            MediaType::Vehicle,
            MediaType::Destination => '3:2',

            MediaType::Tour => '14:9',

            default => '16:9',
        };
    }

    private static function helperText(
        MediaType $type,
        int $maximumSize,
    ): string {
        $maximumMegabytes = round(
            $maximumSize / 1024,
            1
        );

        if ($type->isDocument()) {
            return sprintf(
                'Images, PDF, Word, Excel, PowerPoint, OpenDocument, RTF, TXT or CSV • Maximum %s MB • Documents are stored privately.',
                $maximumMegabytes
            );
        }

        return sprintf(
            'JPG, PNG, WebP, GIF, HEIC, HEIF, AVIF, BMP or TIFF • Maximum %s MB • Image will be compressed and converted automatically.',
            $maximumMegabytes
        );
    }

    /**
     * @return array<int, string>
     */
    private static function defaultImageMimeTypes(): array
    {
        return \App\Support\MediaFormats::mimeTypes();
    }

    /**
     * @return array<int, string>
     */
    private static function defaultDocumentMimeTypes(): array
    {
        return [
            ...self::defaultImageMimeTypes(),
            'application/pdf',
        ];
    }
}