<?php

namespace App\Jobs;

use App\Models\AppMedia;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final class OptimizeMediaJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Maximum processing time in seconds.
     */
    public int $timeout = 120;

    /**
     * Seconds to wait between retries.
     *
     * @var array<int, int>
     */
    public array $backoff = [
        10,
        30,
        60,
    ];

    public function __construct(
        public readonly int $mediaId,
    ) {
        $this->onQueue(
            (string) config(
                'dura-media.queue',
                'default'
            )
        );
    }

    /**
     * Verify and normalize an uploaded media record.
     *
     * Image conversion is already completed synchronously inside
     * ImageOptimizationService. This job must not encode the image
     * again because repeated WebP encoding causes quality loss.
     */
    public function handle(): void
    {
        $media = AppMedia::withTrashed()
            ->find($this->mediaId);

        if (!$media instanceof AppMedia) {
            Log::notice(
                'OptimizeMediaJob skipped because media record no longer exists.',
                [
                    'media_id' => $this->mediaId,
                ]
            );

            return;
        }

        if ($media->trashed()) {
            Log::info(
                'OptimizeMediaJob skipped for soft-deleted media.',
                [
                    'media_id' => $media->id,
                    'media_uuid' => $media->uuid,
                ]
            );

            return;
        }

        try {
            $storedPath = trim(
                (string) $media->storedPath()
            );

            if ($storedPath === '') {
                throw new RuntimeException(
                    'The media stored path is empty.'
                );
            }

            /*
             * External URLs are not managed by Laravel Storage.
             * Keep their record unchanged.
             */
            if (
                str_starts_with(
                    $storedPath,
                    'http://'
                )
                || str_starts_with(
                    $storedPath,
                    'https://'
                )
            ) {
                Log::info(
                    'OptimizeMediaJob skipped for externally hosted media.',
                    [
                        'media_id' => $media->id,
                        'media_uuid' => $media->uuid,
                        'stored_path' => $storedPath,
                    ]
                );

                return;
            }

            $diskName = trim(
                (string) (
                    $media->disk
                    ?: 'public'
                )
            );

            if ($diskName === '') {
                $diskName = 'public';
            }

            $disk = Storage::disk(
                $diskName
            );

            if (!$disk->exists($storedPath)) {
                $this->markFileMissing(
                    media: $media,
                    storedPath: $storedPath,
                );

                throw new RuntimeException(
                    "Stored media file does not exist: {$storedPath}"
                );
            }

            $storedSize = max(
                0,
                (int) $disk->size(
                    $storedPath
                )
            );

            if ($storedSize <= 0) {
                throw new RuntimeException(
                    'The stored media file is empty.'
                );
            }

            /*
             * New single-file uploads already have:
             *
             * original_path = uuid.webp
             * large_path = null
             * medium_path = null
             * thumbnail_path = null
             *
             * Normalize metadata and size without re-encoding.
             */
            if ($media->usesSingleFileStorage()) {
                $this->normalizeSingleFileMedia(
                    media: $media,
                    storedPath: $storedPath,
                    storedSize: $storedSize,
                );

                Log::info(
                    'Single-file media verified successfully.',
                    [
                        'media_id' => $media->id,
                        'media_uuid' => $media->uuid,
                        'stored_path' => $storedPath,
                        'stored_size' => $storedSize,
                        'mime_type' => $media->mime_type,
                    ]
                );

                return;
            }

            /*
             * Do not automatically delete or rewrite old variant files.
             *
             * Legacy files will remain available until the separate
             * migration/cleanup step converts or removes them safely.
             */
            $this->markLegacyMediaVerified(
                media: $media,
                storedPath: $storedPath,
                storedSize: $storedSize,
            );

            Log::info(
                'Legacy media verified without generating new variants.',
                [
                    'media_id' => $media->id,
                    'media_uuid' => $media->uuid,
                    'stored_path' => $storedPath,
                    'stored_size' => $storedSize,
                ]
            );
        } catch (Throwable $exception) {
            Log::error(
                'OptimizeMediaJob failed.',
                [
                    'media_id' => $media->id,
                    'media_uuid' => $media->uuid,
                    'attempt' => $this->attempts(),
                    'exception_class' =>
                        $exception::class,
                    'exception' =>
                        $exception->getMessage(),
                ]
            );

            throw $exception;
        }
    }

    /**
     * Normalize a new single-file media record.
     */
    private function normalizeSingleFileMedia(
        AppMedia $media,
        string $storedPath,
        int $storedSize,
    ): void {
        $metadata = $this->cleanMetadata(
            (array) $media->metadata
        );

        $isPdf = strtolower(
            (string) $media->mime_type
        ) === 'application/pdf';

        $extension = strtolower(
            (string) pathinfo(
                $storedPath,
                PATHINFO_EXTENSION
            )
        );

        $updates = [
            /*
             * Ensure the final single file is always held in
             * original_path for database compatibility.
             */
            'original_path' => $storedPath,

            'large_path' => null,
            'medium_path' => null,
            'thumbnail_path' => null,

            'optimized_size' => $storedSize,

            'metadata' => array_merge(
                $metadata,
                [
                    'storage_mode' =>
                        'single_file',

                    'variants_generated' =>
                        false,

                    'stored_path' =>
                        $storedPath,

                    'stored_size' =>
                        $storedSize,

                    'verified_at' =>
                        now()->toIso8601String(),

                    'verified_by' =>
                        self::class,
                ]
            ),
        ];

        if ($isPdf) {
            $updates['original_extension'] =
                $extension !== ''
                    ? $extension
                    : 'pdf';

            $updates['mime_type'] =
                'application/pdf';
        } elseif ($extension === 'webp') {
            $updates['original_extension'] =
                'webp';

            $updates['mime_type'] =
                'image/webp';
        }

        $media->forceFill(
            $updates
        )->save();
    }

    /**
     * Mark legacy media as checked without changing its paths.
     */
    private function markLegacyMediaVerified(
        AppMedia $media,
        string $storedPath,
        int $storedSize,
    ): void {
        $metadata = (array) $media->metadata;

        $media->forceFill([
            'metadata' => array_merge(
                $metadata,
                [
                    'storage_mode' =>
                        'legacy_variants',

                    'variant_generation_disabled' =>
                        true,

                    'legacy_stored_path' =>
                        $storedPath,

                    'legacy_stored_size' =>
                        $storedSize,

                    'verified_at' =>
                        now()->toIso8601String(),

                    'verified_by' =>
                        self::class,
                ]
            ),
        ])->save();
    }

    /**
     * Record that the physical file is missing.
     */
    private function markFileMissing(
        AppMedia $media,
        string $storedPath,
    ): void {
        $media->forceFill([
            'is_active' => false,

            'metadata' => array_merge(
                (array) $media->metadata,
                [
                    'missing_file' =>
                        true,

                    'missing_path' =>
                        $storedPath,

                    'missing_file_detected_at' =>
                        now()->toIso8601String(),

                    'missing_file_detected_by' =>
                        self::class,
                ]
            ),
        ])->save();
    }

    /**
     * Remove metadata belonging to the old variant architecture.
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
            $metadata['preferred_variant_url'],
            $metadata['variant_format'],
            $metadata['variant_quality'],
            $metadata['variants_generated_at'],
        );

        return $metadata;
    }

    /**
     * Handle a permanently failed queue job.
     */
    public function failed(
        Throwable $exception,
    ): void {
        $media = AppMedia::withTrashed()
            ->find($this->mediaId);

        if ($media instanceof AppMedia) {
            $media->forceFill([
                'metadata' => array_merge(
                    (array) $media->metadata,
                    [
                        'verification_failed' =>
                            true,

                        'verification_failed_at' =>
                            now()->toIso8601String(),

                        'verification_error' =>
                            $exception->getMessage(),
                    ]
                ),
            ])->save();
        }

        Log::critical(
            'OptimizeMediaJob permanently failed.',
            [
                'media_id' => $this->mediaId,
                'exception_class' =>
                    $exception::class,
                'exception' =>
                    $exception->getMessage(),
            ]
        );
    }

    /**
     * Prevent duplicate jobs for the same media from overlapping.
     *
     * Laravel uses this value when queue uniqueness is added later.
     */
    public function uniqueId(): string
    {
        return 'dura-media-optimize-'
            . $this->mediaId;
    }
}