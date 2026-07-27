<?php

namespace App\Models;

use App\Enums\MediaType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class AppMedia extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'app_media';

    protected $fillable = [
        'uuid',
        'name',
        'slug',

        'media_type',
        'module',

        'disk',
        'directory',

        /*
         * Single-file architecture:
         *
         * original_path contains the final optimized WebP
         * or the final stored document.
         *
         * The variant columns remain temporarily for database
         * compatibility but should always be null for new uploads.
         */
        'original_path',
        'large_path',
        'medium_path',
        'thumbnail_path',

        'original_name',
        'original_extension',
        'mime_type',

        'original_size',
        'optimized_size',

        'width',
        'height',
        'quality',

        'file_hash',

        'alt_text',
        'caption',

        'is_active',
        'is_public',

        'reference_count',
        'sort_order',

        'uploaded_by',

        'metadata',
    ];

    protected $appends = [
        /*
         * Existing API and Filament compatibility.
         *
         * All image URL attributes now resolve to the same
         * single stored file.
         */
        'url',
        'original_url',
        'large_url',
        'medium_url',
        'thumbnail_url',
        'image_url',

        'formatted_original_size',
        'formatted_optimized_size',
        'saved_size',
        'saved_percentage',
    ];

    protected static function booted(): void
    {
        static::creating(
            function (AppMedia $media): void {
                $media->uuid ??= (string) Str::uuid();

                if (
                    blank($media->slug)
                    && filled($media->name)
                ) {
                    $media->slug = Str::slug(
                        $media->name
                    );
                }

                $media->disk ??= 'public';

                $media->reference_count ??= 0;
                $media->sort_order ??= 0;
                $media->is_active ??= true;
                $media->is_public ??= true;
            }
        );
    }

    protected function casts(): array
    {
        return [
            'media_type' => MediaType::class,

            'original_size' => 'integer',
            'optimized_size' => 'integer',

            'width' => 'integer',
            'height' => 'integer',
            'quality' => 'integer',

            'reference_count' => 'integer',
            'sort_order' => 'integer',

            'is_active' => 'boolean',
            'is_public' => 'boolean',

            'metadata' => 'array',
        ];
    }

    public function usages(): HasMany
    {
        return $this->hasMany(
            MediaUsage::class,
            'app_media_id'
        );
    }

    public function scopeActive(
        Builder $query,
    ): Builder {
        return $query->where(
            'is_active',
            true
        );
    }

    public function scopePublic(
        Builder $query,
    ): Builder {
        return $query->where(
            'is_public',
            true
        );
    }

    public function scopeOfType(
        Builder $query,
        MediaType|string $type,
    ): Builder {
        $value = $type instanceof MediaType
            ? $type->value
            : $type;

        return $query->where(
            'media_type',
            $value
        );
    }

    public function scopeForModule(
        Builder $query,
        string $module,
    ): Builder {
        return $query->where(
            'module',
            $module
        );
    }

    /**
     * Increase the number of attached records.
     */
    public function incrementReferenceCount(): void
    {
        $this->increment('reference_count');

        $this->refresh();
    }

    /**
     * Decrease the number of attached records safely.
     */
    public function decrementReferenceCount(): void
    {
        if (
            max(
                0,
                (int) $this->reference_count
            ) <= 0
        ) {
            return;
        }

        $this->decrement('reference_count');

        $this->refresh();
    }

    /**
     * Determine whether this media is still used.
     */
    public function hasReferences(): bool
    {
        return
            max(
                0,
                (int) $this->reference_count
            ) > 0
            || $this->usages()->exists();
    }

    /**
     * Return the final stored file path.
     *
     * New records use only original_path.
     *
     * Legacy fallback is included so old media records continue
     * working until they are migrated or cleaned.
     */
    public function storedPath(): ?string
    {
        $paths = [
            $this->original_path,
            $this->medium_path,
            $this->large_path,
            $this->thumbnail_path,
        ];

        foreach ($paths as $path) {
            $path = trim(
                (string) $path
            );

            if ($path !== '') {
                return $path;
            }
        }

        return null;
    }

    /**
     * Return the absolute URL for the single stored file.
     *
     * The variant argument is accepted only for compatibility.
     * Every variant returns the same optimized WebP/document URL.
     */
    public function urlFor(
        string $variant = 'original',
    ): ?string {
        return $this->storageUrl(
            $this->storedPath()
        );
    }

    /**
     * Check whether the final stored file exists.
     */
    public function fileExists(): bool
    {
        $path = $this->storedPath();

        if (blank($path)) {
            return false;
        }

        if (
            Str::startsWith(
                $path,
                [
                    'http://',
                    'https://',
                ]
            )
        ) {
            /*
             * Remote URLs cannot be checked reliably through
             * Laravel Storage. Treat them as available.
             */
            return true;
        }

        try {
            return Storage::disk(
                $this->disk ?: 'public'
            )->exists($path);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Return every physical path related to this media.
     *
     * New uploads return only one path.
     * Legacy variant paths are included to support cleanup.
     *
     * @return array<int, string>
     */
    public function allStoredPaths(): array
    {
        $paths = [
            $this->original_path,
            $this->large_path,
            $this->medium_path,
            $this->thumbnail_path,
        ];

        return array_values(
            array_unique(
                array_filter(
                    array_map(
                        static fn (
                            mixed $path,
                        ): string => trim(
                            (string) $path
                        ),
                        $paths
                    ),
                    static fn (
                        string $path,
                    ): bool => $path !== ''
                )
            )
        );
    }

    /**
     * Main public URL accessor.
     */
    protected function url(): Attribute
    {
        return Attribute::get(
            fn (): ?string =>
                $this->urlFor('original')
        );
    }

    /**
     * Compatibility accessor.
     */
    protected function originalUrl(): Attribute
    {
        return Attribute::get(
            fn (): ?string =>
                $this->urlFor('original')
        );
    }

    /**
     * Compatibility accessor.
     *
     * No large variant exists anymore.
     */
    protected function largeUrl(): Attribute
    {
        return Attribute::get(
            fn (): ?string =>
                $this->urlFor('original')
        );
    }

    /**
     * Compatibility accessor.
     *
     * No medium variant exists anymore.
     */
    protected function mediumUrl(): Attribute
    {
        return Attribute::get(
            fn (): ?string =>
                $this->urlFor('original')
        );
    }

    /**
     * Compatibility accessor.
     *
     * No thumbnail variant exists anymore.
     */
    protected function thumbnailUrl(): Attribute
    {
        return Attribute::get(
            fn (): ?string =>
                $this->urlFor('original')
        );
    }

    /**
     * Main image URL used by APIs and Filament.
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(
            fn (): ?string =>
                $this->urlFor('original')
        );
    }

    /**
     * Uploaded source file size.
     */
    protected function formattedOriginalSize(): Attribute
    {
        return Attribute::get(
            fn (): string =>
                $this->formatBytes(
                    max(
                        0,
                        (int) $this->original_size
                    )
                )
        );
    }

    /**
     * Final stored WebP/document size.
     */
    protected function formattedOptimizedSize(): Attribute
    {
        return Attribute::get(
            fn (): string =>
                $this->formatBytes(
                    max(
                        0,
                        (int) $this->optimized_size
                    )
                )
        );
    }

    /**
     * Number of bytes saved after optimization.
     */
    protected function savedSize(): Attribute
    {
        return Attribute::get(
            fn (): int => max(
                0,
                (int) $this->original_size
                    - (int) $this->optimized_size
            )
        );
    }

    /**
     * Percentage saved after optimization.
     */
    protected function savedPercentage(): Attribute
    {
        return Attribute::get(
            function (): float {
                $originalSize = max(
                    0,
                    (int) $this->original_size
                );

                $optimizedSize = max(
                    0,
                    (int) $this->optimized_size
                );

                if ($originalSize <= 0) {
                    return 0.0;
                }

                return round(
                    max(
                        0,
                        (
                            (
                                $originalSize
                                - $optimizedSize
                            )
                            / $originalSize
                        ) * 100
                    ),
                    2
                );
            }
        );
    }

    /**
     * Determine whether this record is an image.
     */
    public function isImage(): bool
    {
        return Str::startsWith(
            strtolower(
                (string) $this->mime_type
            ),
            'image/'
        );
    }

    /**
     * Determine whether this record is a PDF.
     */
    public function isPdf(): bool
    {
        return strtolower(
            (string) $this->mime_type
        ) === 'application/pdf';
    }

    /**
     * Determine whether this record uses the new single-file mode.
     */
    public function usesSingleFileStorage(): bool
    {
        $metadata = (array) $this->metadata;

        if (
            ($metadata['storage_mode'] ?? null)
            === 'single_file'
        ) {
            return true;
        }

        return
            filled($this->original_path)
            && blank($this->large_path)
            && blank($this->medium_path)
            && blank($this->thumbnail_path);
    }

    /**
     * Build a URL safely from a storage path.
     */
    private function storageUrl(
        ?string $path,
    ): ?string {
        $path = trim(
            (string) $path
        );

        if ($path === '') {
            return null;
        }

        if (
            Str::startsWith(
                $path,
                [
                    'http://',
                    'https://',
                ]
            )
        ) {
            return $path;
        }

        try {
            return Storage::disk(
                $this->disk ?: 'public'
            )->url($path);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Convert bytes into a readable value.
     */
    private function formatBytes(
        int $bytes,
    ): string {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = [
            'B',
            'KB',
            'MB',
            'GB',
            'TB',
        ];

        $power = min(
            (int) floor(
                log($bytes, 1024)
            ),
            count($units) - 1
        );

        $value = $bytes / (
            1024 ** $power
        );

        return number_format(
            $value,
            $power === 0 ? 0 : 2
        ) . ' ' . $units[$power];
    }
}