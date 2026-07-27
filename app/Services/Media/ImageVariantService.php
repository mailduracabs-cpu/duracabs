<?php

namespace App\Services\Media;

use App\Enums\MediaType;
use App\Models\AppMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use RuntimeException;
use Throwable;

final class ImageVariantService
{
    /**
     * Generate standard variants from an uploaded file.
     *
     * @param array<string, mixed> $options
     * @return array{
     *     large_path:?string,
     *     medium_path:?string,
     *     thumbnail_path:?string,
     *     optimized_size:int,
     *     metadata:array<string,mixed>
     * }
     */
    public function generateFromUpload(
        UploadedFile $file,
        AppMedia $media,
        array $options = [],
    ): array {
        if (!$file->isValid()) {
            throw new RuntimeException(
                'The source upload is invalid.'
            );
        }

        return $this->generateFromSource(
            source: $file->getRealPath(),
            media: $media,
            options: $options,
        );
    }

    /**
     * Generate standard variants using the stored original.
     *
     * @param array<string, mixed> $options
     * @return array{
     *     large_path:?string,
     *     medium_path:?string,
     *     thumbnail_path:?string,
     *     optimized_size:int,
     *     metadata:array<string,mixed>
     * }
     */
    public function generate(
        AppMedia $media,
        array $options = [],
    ): array {
        $source = $this->readOriginalSource(
            $media
        );

        return $this->generateFromSource(
            source: $source,
            media: $media,
            options: $options,
        );
    }

    /**
     * Regenerate standard variants and update the media record.
     *
     * @param array<string, mixed> $options
     */
    public function regenerate(
        AppMedia $media,
        array $options = [],
    ): AppMedia {
        if ($media->trashed()) {
            throw new RuntimeException(
                'Deleted media variants cannot be regenerated.'
            );
        }

        $this->deleteVariants(
            media: $media,
            preserveOriginal: true,
        );

        $result = $this->generate(
            media: $media,
            options: $options,
        );

        $metadata = array_merge(
            (array) $media->metadata,
            $result['metadata'],
            [
                'variants_regenerated_at' =>
                    now()->toIso8601String(),
            ],
        );

        $media->update([
            'large_path' => $result['large_path'],
            'medium_path' => $result['medium_path'],
            'thumbnail_path' => $result['thumbnail_path'],
            'optimized_size' => $result['optimized_size'],
            'metadata' => $metadata,
        ]);

        return $media->refresh();
    }

    /**
     * Generate one named custom variant.
     *
     * @param array<string, mixed> $options
     */
    public function generateCustomVariant(
        AppMedia $media,
        string $variantName,
        int $width,
        int $height,
        array $options = [],
    ): string {
        if ($media->trashed()) {
            throw new RuntimeException(
                'Deleted media variants cannot be generated.'
            );
        }

        $variantName = $this->sanitizeVariantName(
            $variantName
        );

        if ($variantName === '') {
            throw new RuntimeException(
                'A valid variant name is required.'
            );
        }

        if ($width < 1 || $height < 1) {
            throw new RuntimeException(
                'Variant width and height must be greater than zero.'
            );
        }

        if ($this->isPdf($media)) {
            throw new RuntimeException(
                'Image variants cannot be generated from a PDF.'
            );
        }

        $source = $this->readOriginalSource(
            $media
        );

        $format = $this->resolveFormat(
            media: $media,
            options: $options,
        );

        $quality = $this->resolveQuality(
            media: $media,
            options: $options,
        );

        $mode = strtolower(
            (string) (
                $options['mode']
                ?? 'fit'
            )
        );

        if (!in_array($mode, ['fit', 'crop'], true)) {
            $mode = 'fit';
        }

        $alignment = $this->normalizeAlignment(
            (string) (
                $options['position']
                ?? $options['alignment']
                ?? 'center'
            )
        );

        $directory = $this->variantDirectory(
            media: $media,
            variant: $variantName,
        );

        $path = sprintf(
            '%s/%s.%s',
            $directory,
            $media->uuid,
            $format
        );

        $bytes = $this->processVariant(
            source: $source,
            width: $width,
            height: $height,
            mode: $mode,
            format: $format,
            quality: $quality,
            alignment: $alignment,
        );

        $this->storeVariant(
            disk: $media->disk ?: 'public',
            path: $path,
            bytes: $bytes,
            isPublic: (bool) $media->is_public,
        );

        $metadata = (array) $media->metadata;

        $customVariants = (array) (
            $metadata['custom_variants']
            ?? []
        );

        $customVariants[$variantName] = [
            'path' => $path,
            'width' => $width,
            'height' => $height,
            'mode' => $mode,
            'alignment' => $alignment,
            'format' => $format,
            'quality' => $quality,
            'size' => strlen($bytes),
            'generated_at' => now()->toIso8601String(),
        ];

        $metadata['custom_variants'] = $customVariants;

        $media->update([
            'metadata' => $metadata,
        ]);

        return $path;
    }

    /**
     * Delete generated variants.
     */
    public function deleteVariants(
        AppMedia $media,
        bool $preserveOriginal = true,
    ): void {
        $diskName = $media->disk ?: 'public';
        $disk = Storage::disk($diskName);

        $paths = [
            $media->large_path,
            $media->medium_path,
            $media->thumbnail_path,
        ];

        $metadata = (array) $media->metadata;

        foreach (
            (array) (
                $metadata['custom_variants']
                ?? []
            ) as $variant
        ) {
            if (
                is_array($variant)
                && filled($variant['path'] ?? null)
            ) {
                $paths[] = $variant['path'];
            }
        }

        if (!$preserveOriginal) {
            $paths[] = $media->original_path;
        }

        $paths = collect($paths)
            ->filter(
                static fn (mixed $path): bool =>
                    is_string($path)
                    && trim($path) !== ''
            )
            ->map(
                static fn (string $path): string =>
                    trim(
                        str_replace('\\', '/', $path),
                        '/'
                    )
            )
            ->unique()
            ->values()
            ->all();

        if ($paths !== []) {
            $disk->delete($paths);
        }

        $baseDirectory = trim(
            (string) $media->directory,
            '/'
        );

        if ($baseDirectory !== '') {
            foreach (
                [
                    'large',
                    'medium',
                    'thumbnail',
                ] as $folder
            ) {
                $disk->deleteDirectory(
                    $baseDirectory . '/' . $folder
                );
            }

            foreach (
                array_keys(
                    (array) (
                        $metadata['custom_variants']
                        ?? []
                    )
                ) as $variantName
            ) {
                $safeName = $this->sanitizeVariantName(
                    (string) $variantName
                );

                if ($safeName !== '') {
                    $disk->deleteDirectory(
                        $baseDirectory
                        . '/'
                        . $safeName
                    );
                }
            }
        }
    }

    /**
     * @param string|resource $source
     * @param array<string, mixed> $options
     * @return array{
     *     large_path:?string,
     *     medium_path:?string,
     *     thumbnail_path:?string,
     *     optimized_size:int,
     *     metadata:array<string,mixed>
     * }
     */
    private function generateFromSource(
        mixed $source,
        AppMedia $media,
        array $options,
    ): array {
        if ($this->isPdf($media)) {
            return [
                'large_path' => null,
                'medium_path' => null,
                'thumbnail_path' => null,
                'optimized_size' => (int) $media->original_size,
                'metadata' => [
                    'variants_skipped' => true,
                    'variants_skip_reason' =>
                        'PDF documents are preserved without image variants.',
                ],
            ];
        }

        $type = $this->resolveMediaType(
            $media
        );

        $quality = $this->resolveQuality(
            media: $media,
            options: $options,
        );

        $format = $this->resolveFormat(
            media: $media,
            options: $options,
        );

        $definitions = $this->definitions(
            type: $type,
            options: $options,
        );

        $paths = [
            'large' => null,
            'medium' => null,
            'thumbnail' => null,
        ];

        $variantMetadata = [];
        $optimizedSize = 0;
        $writtenPaths = [];

        try {
            foreach (
                $definitions as
                $variant => $definition
            ) {
                $path = $this->variantPath(
                    media: $media,
                    variant: $variant,
                    format: $format,
                );

                $bytes = $this->processVariant(
                    source: $source,
                    width: $definition['width'],
                    height: $definition['height'],
                    mode: $definition['mode'],
                    format: $format,
                    quality: $quality,
                    alignment: $definition['alignment'],
                );

                $this->storeVariant(
                    disk: $media->disk ?: 'public',
                    path: $path,
                    bytes: $bytes,
                    isPublic: (bool) $media->is_public,
                );

                $writtenPaths[] = $path;
                $paths[$variant] = $path;

                $size = strlen($bytes);
                $optimizedSize += $size;

                $variantMetadata[$variant] = [
                    'path' => $path,
                    'width' => $definition['width'],
                    'height' => $definition['height'],
                    'mode' => $definition['mode'],
                    'alignment' => $definition['alignment'],
                    'format' => $format,
                    'quality' => $quality,
                    'size' => $size,
                ];
            }

            return [
                'large_path' => $paths['large'],
                'medium_path' => $paths['medium'],
                'thumbnail_path' => $paths['thumbnail'],
                'optimized_size' => $optimizedSize,
                'metadata' => [
                    'variant_format' => $format,
                    'variant_quality' => $quality,
                    'variants' => $variantMetadata,
                    'variant_paths' => array_values(
                        array_filter($paths)
                    ),
                    'variants_generated_at' =>
                        now()->toIso8601String(),
                ],
            ];
        } catch (Throwable $exception) {
            if ($writtenPaths !== []) {
                Storage::disk(
                    $media->disk ?: 'public'
                )->delete($writtenPaths);
            }

            Log::error(
                'Image variant generation failed.',
                [
                    'media_id' => $media->id,
                    'media_uuid' => $media->uuid,
                    'written_paths' => $writtenPaths,
                    'exception' => $exception->getMessage(),
                ]
            );

            throw new RuntimeException(
                'Unable to generate image variants: '
                . $exception->getMessage(),
                previous: $exception,
            );
        }
    }

    /**
     * @param string|resource $source
     */
    private function processVariant(
        mixed $source,
        int $width,
        int $height,
        string $mode,
        string $format,
        int $quality,
        string $alignment,
    ): string {
        $image = Image::decode($source);

        if ($mode === 'crop') {
            $image->coverDown(
                width: max(1, $width),
                height: max(1, $height),
                alignment: $this->normalizeAlignment(
                    $alignment
                ),
            );
        } else {
            $image->scaleDown(
                width: max(1, $width),
                height: max(1, $height),
            );
        }

        if ($format === 'jpg') {
            $encoded = $image->encodeUsingFileExtension(
                fileExtension: 'jpg',
                quality: $quality,
                progressive: true,
                strip: true,
            );
        } else {
            $encoded = $image->encodeUsingFileExtension(
                fileExtension: $format,
                quality: $quality,
                strip: true,
            );
        }

        return (string) $encoded;
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, array{
     *     width:int,
     *     height:int,
     *     mode:string,
     *     alignment:string
     * }>
     */
    private function definitions(
        MediaType $type,
        array $options,
    ): array {
        $large = $type->largeSize();
        $medium = $type->mediumSize();
        $thumbnail = $type->thumbnailSize();

        $detailMode = $this->detailMode(
            $type
        );

        return [
            'large' => [
                'width' => max(
                    1,
                    (int) (
                        $options['large_width']
                        ?? $large['width']
                    )
                ),

                'height' => max(
                    1,
                    (int) (
                        $options['large_height']
                        ?? $large['height']
                    )
                ),

                'mode' => $this->normalizeMode(
                    (string) (
                        $options['large_mode']
                        ?? $detailMode
                    )
                ),

                'alignment' => $this->normalizeAlignment(
                    (string) (
                        $options['large_alignment']
                        ?? $options['large_position']
                        ?? 'center'
                    )
                ),
            ],

            'medium' => [
                'width' => max(
                    1,
                    (int) (
                        $options['medium_width']
                        ?? $medium['width']
                    )
                ),

                'height' => max(
                    1,
                    (int) (
                        $options['medium_height']
                        ?? $medium['height']
                    )
                ),

                'mode' => $this->normalizeMode(
                    (string) (
                        $options['medium_mode']
                        ?? $detailMode
                    )
                ),

                'alignment' => $this->normalizeAlignment(
                    (string) (
                        $options['medium_alignment']
                        ?? $options['medium_position']
                        ?? 'center'
                    )
                ),
            ],

            'thumbnail' => [
                'width' => max(
                    1,
                    (int) (
                        $options['thumbnail_width']
                        ?? $thumbnail['width']
                    )
                ),

                'height' => max(
                    1,
                    (int) (
                        $options['thumbnail_height']
                        ?? $thumbnail['height']
                    )
                ),

                'mode' => $this->normalizeMode(
                    (string) (
                        $options['thumbnail_mode']
                        ?? 'crop'
                    )
                ),

                'alignment' => $this->normalizeAlignment(
                    (string) (
                        $options['thumbnail_alignment']
                        ?? $options['thumbnail_position']
                        ?? 'center'
                    )
                ),
            ],
        ];
    }

    private function detailMode(
        MediaType $type,
    ): string {
        return match ($type) {
            MediaType::Banner,
            MediaType::Offer,
            MediaType::Profile,
            MediaType::Icon => 'crop',

            default => 'fit',
        };
    }

    private function resolveMediaType(
        AppMedia $media,
    ): MediaType {
        if ($media->media_type instanceof MediaType) {
            return $media->media_type;
        }

        return MediaType::tryFrom(
            (string) $media->media_type
        ) ?? MediaType::Other;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function resolveQuality(
        AppMedia $media,
        array $options,
    ): int {
        $type = $this->resolveMediaType(
            $media
        );

        return max(
            40,
            min(
                100,
                (int) (
                    $options['quality']
                    ?? $media->quality
                    ?? $type->quality()
                )
            )
        );
    }

    /**
     * @param array<string, mixed> $options
     */
    private function resolveFormat(
        AppMedia $media,
        array $options,
    ): string {
        $requested = strtolower(
            trim(
                (string) (
                    $options['format']
                    ?? ''
                )
            )
        );

        if (
            in_array(
                $requested,
                [
                    'webp',
                    'jpg',
                    'jpeg',
                    'png',
                ],
                true
            )
        ) {
            return $requested === 'jpeg'
                ? 'jpg'
                : $requested;
        }

        $type = $this->resolveMediaType(
            $media
        );

        return $type->supportsWebp()
            ? 'webp'
            : 'jpg';
    }

    /**
     * @return string|resource
     */
    private function readOriginalSource(
        AppMedia $media,
    ): mixed {
        $path = trim(
            (string) $media->original_path
        );

        if ($path === '') {
            throw new RuntimeException(
                'The media original path is missing.'
            );
        }

        $disk = Storage::disk(
            $media->disk ?: 'public'
        );

        if (!$disk->exists($path)) {
            throw new RuntimeException(
                'The original media file does not exist.'
            );
        }

        try {
            return $disk->path($path);
        } catch (Throwable) {
            return $disk->get($path);
        }
    }

    private function variantPath(
        AppMedia $media,
        string $variant,
        string $format,
    ): string {
        return sprintf(
            '%s/%s.%s',
            $this->variantDirectory(
                media: $media,
                variant: $variant,
            ),
            $media->uuid,
            $format
        );
    }

    private function variantDirectory(
        AppMedia $media,
        string $variant,
    ): string {
        $baseDirectory = trim(
            (string) $media->directory,
            '/'
        );

        if ($baseDirectory === '') {
            $baseDirectory = sprintf(
                '%s/other/%s/%s',
                trim(
                    (string) config(
                        'dura-media.base_directory',
                        'app-media'
                    ),
                    '/'
                ),
                now()->format('Y/m'),
                $media->uuid
            );
        }

        return $baseDirectory
            . '/'
            . $this->sanitizeVariantName($variant);
    }

    private function storeVariant(
        string $disk,
        string $path,
        string $bytes,
        bool $isPublic,
    ): void {
        $stored = Storage::disk($disk)->put(
            $path,
            $bytes,
            [
                'visibility' => $isPublic
                    ? 'public'
                    : 'private',
            ],
        );

        if ($stored === false) {
            throw new RuntimeException(
                "Unable to save image variant: {$path}"
            );
        }
    }

    private function isPdf(
        AppMedia $media,
    ): bool {
        return strtolower(
            (string) $media->mime_type
        ) === 'application/pdf';
    }

    private function normalizeMode(
        string $mode,
    ): string {
        return strtolower($mode) === 'crop'
            ? 'crop'
            : 'fit';
    }

    private function normalizeAlignment(
        string $alignment,
    ): string {
        $alignment = strtolower(
            trim($alignment)
        );

        return in_array(
            $alignment,
            [
                'center',
                'top',
                'bottom',
                'left',
                'right',
                'top-left',
                'top-right',
                'bottom-left',
                'bottom-right',
            ],
            true
        )
            ? $alignment
            : 'center';
    }

    private function sanitizeVariantName(
        string $variantName,
    ): string {
        return trim(
            preg_replace(
                '/[^a-z0-9_-]+/',
                '-',
                strtolower($variantName)
            ) ?? '',
            '-_'
        );
    }
}