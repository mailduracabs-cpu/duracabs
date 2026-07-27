<?php

namespace App\Jobs;

use App\Models\AppMedia;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Legacy compatibility job.
 *
 * Dura Media Engine now stores only one optimized WebP file.
 * This job no longer creates large, medium or thumbnail variants.
 *
 * It is temporarily retained so older dispatch calls do not break.
 */
final class GenerateVariantsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Maximum number of attempts.
     */
    public int $tries = 2;

    /**
     * Maximum execution time.
     */
    public int $timeout = 60;

    /**
     * Delay between retries.
     *
     * @var array<int, int>
     */
    public array $backoff = [
        10,
        30,
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
     * Do not generate image variants.
     *
     * This method only:
     *
     * - Confirms the media record still exists
     * - Marks variant generation as disabled
     * - Sets single-file metadata
     * - Dispatches the verification job
     */
    public function handle(): void
    {
        $media = AppMedia::withTrashed()
            ->find($this->mediaId);

        if (!$media instanceof AppMedia) {
            Log::notice(
                'GenerateVariantsJob skipped because media record does not exist.',
                [
                    'media_id' => $this->mediaId,
                ]
            );

            return;
        }

        if ($media->trashed()) {
            Log::info(
                'GenerateVariantsJob skipped for soft-deleted media.',
                [
                    'media_id' => $media->id,
                    'media_uuid' => $media->uuid,
                ]
            );

            return;
        }

        /*
         * New uploads should already be in single-file mode.
         *
         * Do not clear legacy paths here because old records may still
         * depend on those files until the migration cleanup is run.
         */
        if ($media->usesSingleFileStorage()) {
            $metadata = $this->cleanMetadata(
                (array) $media->metadata
            );

            $media->forceFill([
                'large_path' => null,
                'medium_path' => null,
                'thumbnail_path' => null,

                'metadata' => array_merge(
                    $metadata,
                    [
                        'storage_mode' =>
                            'single_file',

                        'variants_generated' =>
                            false,

                        'variant_generation_disabled' =>
                            true,

                        'variant_job_skipped_at' =>
                            now()->toIso8601String(),

                        'variant_job_skipped_by' =>
                            self::class,
                    ]
                ),
            ])->save();

            /*
             * Only verify the existing file.
             * OptimizeMediaJob will not re-encode the WebP.
             */
            OptimizeMediaJob::dispatch(
                $media->id
            );

            Log::info(
                'Variant generation skipped for single-file media.',
                [
                    'media_id' => $media->id,
                    'media_uuid' => $media->uuid,
                    'stored_path' => $media->storedPath(),
                ]
            );

            return;
        }

        /*
         * Legacy media may still contain original, large, medium and
         * thumbnail paths. Leave those paths unchanged for safety.
         */
        $media->forceFill([
            'metadata' => array_merge(
                (array) $media->metadata,
                [
                    'storage_mode' =>
                        'legacy_variants',

                    'variant_generation_disabled' =>
                        true,

                    'new_variant_generation_skipped' =>
                        true,

                    'variant_job_skipped_at' =>
                        now()->toIso8601String(),

                    'variant_job_skipped_by' =>
                        self::class,
                ]
            ),
        ])->save();

        OptimizeMediaJob::dispatch(
            $media->id
        );

        Log::info(
            'New variant generation disabled for legacy media.',
            [
                'media_id' => $media->id,
                'media_uuid' => $media->uuid,
                'stored_path' => $media->storedPath(),
            ]
        );
    }

    /**
     * Remove old variant metadata from new single-file records.
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
     * Handle permanent job failure.
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
                        'legacy_variant_job_failed' =>
                            true,

                        'legacy_variant_job_failed_at' =>
                            now()->toIso8601String(),

                        'legacy_variant_job_error' =>
                            $exception->getMessage(),
                    ]
                ),
            ])->save();
        }

        Log::error(
            'GenerateVariantsJob compatibility job failed.',
            [
                'media_id' => $this->mediaId,

                'exception_class' =>
                    $exception::class,

                'exception' =>
                    $exception->getMessage(),
            ]
        );
    }

    public function uniqueId(): string
    {
        return 'dura-media-variants-disabled-'
            . $this->mediaId;
    }
}