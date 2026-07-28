<?php

namespace App\Services\Media;

use App\Enums\MediaType;
use App\Models\AppMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Image;
use Intervention\Image\Laravel\Facades\Image as ImageFacade;
use RuntimeException;
use Throwable;

final class ImageOptimizationService
{
    /**
     * Files written during the current upload.
     *
     * If processing or database creation fails, these files
     * are deleted automatically.
     *
     * @var array<int, string>
     */
    private array $writtenPaths = [];

    /**
     * Upload and process one media file.
     *
     * Images:
     * - Resize once
     * - Convert to WebP
     * - Save only one optimized file
     * - Do not save the uploaded original
     *
     * PDF documents:
     * - Preserve original PDF
     * - Save only one PDF file
     *
     * @param array<string, mixed> $options
     */
    public function upload(
        UploadedFile $file,
        MediaType $mediaType,
        ?string $module = null,
        array $options = [],
        ?string $hash = null,
    ): AppMedia {
        $this->writtenPaths = [];

        $this->validateUploadedFile(
            file: $file,
            mediaType: $mediaType,
        );

        $disk = (string) (
            $options['disk']
            ?? ($mediaType->isDocument() ? 'local' : 'public')
        );

        $uuid = (string) Str::uuid();

        $directory = $this->buildDirectory(
            mediaType: $mediaType,
            module: $module,
            uuid: $uuid,
            customDirectory: $options['directory'] ?? null,
        );

        $originalName = trim(
            $file->getClientOriginalName()
        );

        $uploadedExtension = strtolower(
            $file->getClientOriginalExtension()
                ?: $file->extension()
                ?: 'bin'
        );

        $uploadedMimeType = strtolower(
            (string) (
                $file->getMimeType()
                ?: $file->getClientMimeType()
                ?: 'application/octet-stream'
            )
        );

        $uploadedSize = max(
            0,
            (int) ($file->getSize() ?: 0)
        );

        $name = trim(
            (string) (
                $options['name']
                ?? pathinfo(
                    $originalName,
                    PATHINFO_FILENAME
                )
            )
        );

        if ($name === '') {
            $name = $mediaType->label();
        }

        $slug = Str::slug(
            (string) (
                $options['slug']
                ?? $name
            )
        );

        if ($slug === '') {
            $slug = Str::slug(
                $mediaType->value . '-' . $uuid
            );
        }

        try {
            if ($this->isPdf($uploadedMimeType)) {
                return $this->storePdf(
                    file: $file,
                    mediaType: $mediaType,
                    module: $module,
                    options: $options,
                    hash: $hash,
                    uuid: $uuid,
                    name: $name,
                    slug: $slug,
                    disk: $disk,
                    directory: $directory,
                    originalName: $originalName,
                    uploadedExtension: $uploadedExtension,
                    uploadedMimeType: $uploadedMimeType,
                    uploadedSize: $uploadedSize,
                );
            }

            return $this->storeSingleWebp(
                file: $file,
                mediaType: $mediaType,
                module: $module,
                options: $options,
                hash: $hash,
                uuid: $uuid,
                name: $name,
                slug: $slug,
                disk: $disk,
                directory: $directory,
                originalName: $originalName,
                uploadedExtension: $uploadedExtension,
                uploadedMimeType: $uploadedMimeType,
                uploadedSize: $uploadedSize,
            );
        } catch (Throwable $exception) {
            $this->deleteWrittenFiles($disk);

            throw new RuntimeException(
                'Media processing failed: '
                . $exception->getMessage(),
                previous: $exception,
            );
        }
    }

    /**
     * Convert an existing legacy image into one optimized WebP file.
     *
     * This method does not create an AppMedia record and does not
     * delete the source image. It is intended for one-time migration
     * of existing JPG, JPEG, PNG, WebP or GIF files.
     *
     * The destination path must be relative to the selected Laravel
     * filesystem disk and must use the .webp extension.
     *
     * @param array<string, mixed> $options
     * @return array<string, int|string>
     */
    public function convertExistingImage(
        string $sourcePath,
        string $destinationPath,
        MediaType $mediaType = MediaType::Other,
        array $options = [],
    ): array {
        $sourcePath = trim($sourcePath);

        $destinationPath = trim(
            str_replace('\\', '/', $destinationPath),
            '/',
        );

        if ($sourcePath === '') {
            throw new RuntimeException(
                'The source image path is required.'
            );
        }

        if ($destinationPath === '') {
            throw new RuntimeException(
                'The destination WebP path is required.'
            );
        }

        if (!is_file($sourcePath)) {
            throw new RuntimeException(
                "Source image does not exist: {$sourcePath}"
            );
        }

        if (!is_readable($sourcePath)) {
            throw new RuntimeException(
                "Source image is not readable: {$sourcePath}"
            );
        }

        if (
            strtolower(
                pathinfo(
                    $destinationPath,
                    PATHINFO_EXTENSION,
                )
            ) !== 'webp'
        ) {
            throw new RuntimeException(
                'The destination file must use the .webp extension.'
            );
        }

        $disk = trim(
            (string) (
                $options['disk']
                ?? 'public'
            )
        );

        if ($disk === '') {
            $disk = 'public';
        }

        $quality = $this->resolveQuality(
            mediaType: $mediaType,
            options: $options,
        );

        try {
            $image = ImageFacade::decode(
                $sourcePath
            );

            $sourceWidth = max(
                1,
                (int) $image->width()
            );

            $sourceHeight = max(
                1,
                (int) $image->height()
            );

            $this->resizeImage(
                image: $image,
                mediaType: $mediaType,
            );

            $finalWidth = max(
                1,
                (int) $image->width()
            );

            $finalHeight = max(
                1,
                (int) $image->height()
            );

            $encoded = $image->encode(
                new WebpEncoder(
                    quality: $quality,
                    strip: true,
                )
            );

            $webpBytes = (string) $encoded;

            if ($webpBytes === '') {
                throw new RuntimeException(
                    'The converted WebP image is empty.'
                );
            }

            $isPublic = (bool) (
                $options['is_public']
                ?? true
            );

            $stored = Storage::disk($disk)->put(
                $destinationPath,
                $webpBytes,
                [
                    'visibility' => $isPublic
                        ? 'public'
                        : 'private',
                ]
            );

            if ($stored === false) {
                throw new RuntimeException(
                    "Unable to store converted WebP: {$destinationPath}"
                );
            }

            return [
                'source_path' => $sourcePath,
                'destination_path' => $destinationPath,
                'source_width' => $sourceWidth,
                'source_height' => $sourceHeight,
                'final_width' => $finalWidth,
                'final_height' => $finalHeight,
                'quality' => $quality,
                'optimized_size' => strlen($webpBytes),
            ];
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Existing image conversion failed: '
                . $exception->getMessage(),
                previous: $exception,
            );
        }
    }

    /**
     * Convert an uploaded image into one optimized WebP.
     *
     * @param array<string, mixed> $options
     */
    private function storeSingleWebp(
        UploadedFile $file,
        MediaType $mediaType,
        ?string $module,
        array $options,
        ?string $hash,
        string $uuid,
        string $name,
        string $slug,
        string $disk,
        string $directory,
        string $originalName,
        string $uploadedExtension,
        string $uploadedMimeType,
        int $uploadedSize,
    ): AppMedia {
        $quality = $this->resolveQuality(
            mediaType: $mediaType,
            options: $options,
        );

        $image = ImageFacade::decode(
            $file->getRealPath()
        );

        $sourceWidth = max(
            1,
            (int) $image->width()
        );

        $sourceHeight = max(
            1,
            (int) $image->height()
        );

        $this->resizeImage(
            image: $image,
            mediaType: $mediaType,
        );

        $finalWidth = max(
            1,
            (int) $image->width()
        );

        $finalHeight = max(
            1,
            (int) $image->height()
        );

        $encoded = $image->encode(
            new WebpEncoder(
                quality: $quality,
                strip: true,
            )
        );

        $webpBytes = (string) $encoded;

        if ($webpBytes === '') {
            throw new RuntimeException(
                'The optimized WebP image is empty.'
            );
        }

        /*
         * Only this single file is stored.
         *
         * No original folder.
         * No large folder.
         * No medium folder.
         * No thumbnail folder.
         */
        $webpPath = sprintf(
            '%s/%s.webp',
            $directory,
            $uuid,
        );

        $this->storeBytes(
            disk: $disk,
            path: $webpPath,
            contents: $webpBytes,
            isPublic: (bool) (
                $options['is_public']
                ?? true
            ),
        );

        $optimizedSize = strlen($webpBytes);

        return DB::transaction(
            function () use (
                $uuid,
                $name,
                $slug,
                $mediaType,
                $module,
                $disk,
                $directory,
                $webpPath,
                $originalName,
                $uploadedExtension,
                $uploadedMimeType,
                $uploadedSize,
                $optimizedSize,
                $sourceWidth,
                $sourceHeight,
                $finalWidth,
                $finalHeight,
                $quality,
                $hash,
                $options,
            ): AppMedia {
                return AppMedia::create([
                    'uuid' => $uuid,
                    'name' => $name,
                    'slug' => $slug,

                    'media_type' => $mediaType->value,
                    'module' => $module,

                    'disk' => $disk,
                    'directory' => $directory,

                    /*
                     * Existing database compatibility:
                     *
                     * original_path now contains the only optimized
                     * WebP file. The uploaded JPG/PNG/WebP original
                     * is not stored.
                     */
                    'original_path' => $webpPath,

                    'large_path' => null,
                    'medium_path' => null,
                    'thumbnail_path' => null,

                    /*
                     * Stored file information.
                     */
                    'original_name' => $originalName,
                    'original_extension' => 'webp',
                    'mime_type' => 'image/webp',

                    /*
                     * original_size means uploaded source size.
                     * optimized_size means stored WebP size.
                     */
                    'original_size' => $uploadedSize,
                    'optimized_size' => $optimizedSize,

                    'width' => $finalWidth,
                    'height' => $finalHeight,
                    'quality' => $quality,

                    'file_hash' => $hash,

                    'alt_text' => $options['alt_text']
                        ?? $name,

                    'caption' => $options['caption']
                        ?? null,

                    'is_active' => (bool) (
                        $options['is_active']
                        ?? true
                    ),

                    'is_public' => (bool) (
                        $options['is_public']
                        ?? true
                    ),

                    'reference_count' => max(
                        0,
                        (int) (
                            $options['reference_count']
                            ?? 0
                        )
                    ),

                    'sort_order' => max(
                        0,
                        (int) (
                            $options['sort_order']
                            ?? 0
                        )
                    ),

                    'uploaded_by' => $options['uploaded_by']
                        ?? Auth::id(),

                    'metadata' => array_merge(
                        [
                            'storage_mode' => 'single_file',
                            'generated_format' => 'webp',

                            'uploaded_extension' =>
                                $uploadedExtension,

                            'uploaded_mime_type' =>
                                $uploadedMimeType,

                            'uploaded_size' =>
                                $uploadedSize,

                            'source_width' =>
                                $sourceWidth,

                            'source_height' =>
                                $sourceHeight,

                            'stored_width' =>
                                $finalWidth,

                            'stored_height' =>
                                $finalHeight,

                            'stored_size' =>
                                $optimizedSize,

                            'original_preserved' =>
                                false,

                            'variants_generated' =>
                                false,
                        ],
                        $this->cleanMetadata(
                            (array) (
                                $options['metadata']
                                ?? []
                            )
                        )
                    ),
                ]);
            }
        );
    }

    /**
     * Store a PDF document without conversion.
     *
     * @param array<string, mixed> $options
     */
    private function storePdf(
        UploadedFile $file,
        MediaType $mediaType,
        ?string $module,
        array $options,
        ?string $hash,
        string $uuid,
        string $name,
        string $slug,
        string $disk,
        string $directory,
        string $originalName,
        string $uploadedExtension,
        string $uploadedMimeType,
        int $uploadedSize,
    ): AppMedia {
        if (!$mediaType->isDocument()) {
            throw new RuntimeException(
                'PDF files can only be uploaded as document media.'
            );
        }

        $pdfPath = sprintf(
            '%s/%s.pdf',
            $directory,
            $uuid,
        );

        $this->storeUploadedFile(
            file: $file,
            disk: $disk,
            path: $pdfPath,
            isPublic: (bool) (
                $options['is_public']
                ?? false
            ),
        );

        return DB::transaction(
            function () use (
                $uuid,
                $name,
                $slug,
                $mediaType,
                $module,
                $disk,
                $directory,
                $pdfPath,
                $originalName,
                $uploadedExtension,
                $uploadedMimeType,
                $uploadedSize,
                $hash,
                $options,
            ): AppMedia {
                return AppMedia::create([
                    'uuid' => $uuid,
                    'name' => $name,
                    'slug' => $slug,

                    'media_type' => $mediaType->value,
                    'module' => $module,

                    'disk' => $disk,
                    'directory' => $directory,

                    'original_path' => $pdfPath,

                    'large_path' => null,
                    'medium_path' => null,
                    'thumbnail_path' => null,

                    'original_name' => $originalName,
                    'original_extension' => 'pdf',
                    'mime_type' => 'application/pdf',

                    'original_size' => $uploadedSize,
                    'optimized_size' => $uploadedSize,

                    'width' => null,
                    'height' => null,
                    'quality' => null,

                    'file_hash' => $hash,

                    'alt_text' => $options['alt_text']
                        ?? $name,

                    'caption' => $options['caption']
                        ?? null,

                    'is_active' => (bool) (
                        $options['is_active']
                        ?? true
                    ),

                    'is_public' => (bool) (
                        $options['is_public']
                        ?? false
                    ),

                    'reference_count' => max(
                        0,
                        (int) (
                            $options['reference_count']
                            ?? 0
                        )
                    ),

                    'sort_order' => max(
                        0,
                        (int) (
                            $options['sort_order']
                            ?? 0
                        )
                    ),

                    'uploaded_by' => $options['uploaded_by']
                        ?? Auth::id(),

                    'metadata' => array_merge(
                        [
                            'storage_mode' => 'single_file',
                            'document' => true,
                            'pdf' => true,

                            'uploaded_extension' =>
                                $uploadedExtension,

                            'uploaded_mime_type' =>
                                $uploadedMimeType,

                            'uploaded_size' =>
                                $uploadedSize,

                            'stored_size' =>
                                $uploadedSize,

                            'original_preserved' =>
                                true,

                            'variants_generated' =>
                                false,
                        ],
                        $this->cleanMetadata(
                            (array) (
                                $options['metadata']
                                ?? []
                            )
                        )
                    ),
                ]);
            }
        );
    }

    /**
     * Resize the image according to its media type.
     *
     * Crop-based media receives an exact predictable ratio.
     * Other media preserves the uploaded aspect ratio.
     */
    private function resizeImage(
        Image $image,
        MediaType $mediaType,
    ): void {
        $size = $mediaType->maxSize();

        $width = max(
            1,
            (int) $size['width']
        );

        $height = max(
            1,
            (int) $size['height']
        );

        if ($mediaType->shouldCrop()) {
            $image->coverDown(
                width: $width,
                height: $height,
                position: 'center',
            );

            return;
        }

        $image->scaleDown(
            width: $width,
            height: $height,
        );
    }

    /**
     * Store encoded WebP bytes.
     */
    private function storeBytes(
        string $disk,
        string $path,
        string $contents,
        bool $isPublic,
    ): void {
        $stored = Storage::disk($disk)->put(
            $path,
            $contents,
            [
                'visibility' => $isPublic
                    ? 'public'
                    : 'private',
            ]
        );

        if ($stored === false) {
            throw new RuntimeException(
                "Unable to store optimized media file: {$path}"
            );
        }

        $this->writtenPaths[] = $path;
    }

    /**
     * Store an original non-image document.
     */
    private function storeUploadedFile(
        UploadedFile $file,
        string $disk,
        string $path,
        bool $isPublic,
    ): void {
        $stream = fopen(
            $file->getRealPath(),
            'rb'
        );

        if ($stream === false) {
            throw new RuntimeException(
                'Unable to open the uploaded document.'
            );
        }

        try {
            $stored = Storage::disk($disk)->put(
                $path,
                $stream,
                [
                    'visibility' => $isPublic
                        ? 'public'
                        : 'private',
                ]
            );
        } finally {
            fclose($stream);
        }

        if ($stored === false) {
            throw new RuntimeException(
                "Unable to store document: {$path}"
            );
        }

        $this->writtenPaths[] = $path;
    }

    private function buildDirectory(
        MediaType $mediaType,
        ?string $module,
        string $uuid,
        mixed $customDirectory,
    ): string {
        if (
            is_string($customDirectory)
            && trim($customDirectory) !== ''
        ) {
            return trim(
                str_replace(
                    '\\',
                    '/',
                    $customDirectory
                ),
                '/'
            );
        }

        $moduleDirectory = filled($module)
            ? '/' . Str::slug($module)
            : '';

        return sprintf(
            'app-media/%s%s/%s/%s',
            $mediaType->directory(),
            $moduleDirectory,
            now()->format('Y/m'),
            $uuid,
        );
    }

    /**
     * @param array<string, mixed> $options
     */
    private function resolveQuality(
        MediaType $mediaType,
        array $options,
    ): int {
        return max(
            40,
            min(
                95,
                (int) (
                    $options['quality']
                    ?? $mediaType->quality()
                )
            )
        );
    }

    private function isPdf(
        string $mimeType,
    ): bool {
        return $mimeType === 'application/pdf';
    }

    private function validateUploadedFile(
        UploadedFile $file,
        MediaType $mediaType,
    ): void {
        if (!$file->isValid()) {
            throw new RuntimeException(
                'The uploaded file is invalid.'
            );
        }

        if (!is_file($file->getRealPath())) {
            throw new RuntimeException(
                'The uploaded file could not be found.'
            );
        }

        if (!is_readable($file->getRealPath())) {
            throw new RuntimeException(
                'The uploaded file is not readable.'
            );
        }

        $mimeType = strtolower(
            (string) (
                $file->getMimeType()
                ?: $file->getClientMimeType()
            )
        );

        $imageMimeTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/webp',
            'image/gif',
        ];

        if ($mediaType->isDocument()) {
            $documentMimeTypes = [
                ...$imageMimeTypes,
                'application/pdf',
            ];

            if (
                !in_array(
                    $mimeType,
                    $documentMimeTypes,
                    true
                )
            ) {
                throw new RuntimeException(
                    'Documents must be JPG, PNG, WebP, GIF or PDF.'
                );
            }

            return;
        }

        if (
            !in_array(
                $mimeType,
                $imageMimeTypes,
                true
            )
        ) {
            throw new RuntimeException(
                'Media must be a valid JPG, PNG, WebP or GIF image.'
            );
        }
    }

    /**
     * Remove old variant-related metadata supplied by callers.
     *
     * @param array<string, mixed> $metadata
     * @return array<string, mixed>
     */
    private function cleanMetadata(
        array $metadata,
    ): array {
        unset(
            $metadata['large_size'],
            $metadata['medium_size'],
            $metadata['thumbnail_size'],
            $metadata['variant_paths'],
            $metadata['variants'],
            $metadata['preferred_variant'],
        );

        return $metadata;
    }

    /**
     * Delete files created during an unsuccessful upload.
     */
    private function deleteWrittenFiles(
        string $disk,
    ): void {
        if ($this->writtenPaths === []) {
            return;
        }

        Storage::disk($disk)->delete(
            array_values(
                array_unique(
                    $this->writtenPaths
                )
            )
        );

        $this->writtenPaths = [];
    }
}