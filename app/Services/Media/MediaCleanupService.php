<?php

namespace App\Services\Media;

use App\Models\AppMedia;
use App\Models\MediaUsage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final class MediaCleanupService
{
    public function __construct(
        private readonly MediaDeleteService $mediaDeleteService,
    ) {
    }

    /**
     * Execute the complete media cleanup process.
     *
     * @return array<string, mixed>
     */
    public function cleanup(
        int $unusedDays = 30,
        int $trashedDays = 7,
        bool $deleteMissingRecords = false,
        bool $dryRun = false,
    ): array {
        $startedAt = now();

        $report = [
            'dry_run' => $dryRun,
            'started_at' => $startedAt->toIso8601String(),
            'finished_at' => null,

            'reference_counts_fixed' => 0,
            'unused_media_found' => 0,
            'unused_media_deleted' => 0,

            'trashed_media_found' => 0,
            'trashed_media_deleted' => 0,

            'missing_originals_found' => 0,
            'missing_records_deleted' => 0,

            'orphan_usage_records_deleted' => 0,
            'empty_directories_deleted' => 0,

            'errors' => [],
        ];

        try {
            $report['orphan_usage_records_deleted'] =
                $this->cleanupOrphanUsageRecords($dryRun);

            $report['reference_counts_fixed'] =
                $this->synchronizeAllReferenceCounts($dryRun);

            $unusedResult = $this->cleanupUnusedMedia(
                olderThanDays: $unusedDays,
                dryRun: $dryRun,
            );

            $report['unused_media_found'] =
                $unusedResult['found'];

            $report['unused_media_deleted'] =
                $unusedResult['deleted'];

            $trashedResult = $this->cleanupSoftDeletedMedia(
                olderThanDays: $trashedDays,
                dryRun: $dryRun,
            );

            $report['trashed_media_found'] =
                $trashedResult['found'];

            $report['trashed_media_deleted'] =
                $trashedResult['deleted'];

            $missingResult = $this->cleanupMissingOriginals(
                deleteRecords: $deleteMissingRecords,
                dryRun: $dryRun,
            );

            $report['missing_originals_found'] =
                $missingResult['found'];

            $report['missing_records_deleted'] =
                $missingResult['deleted'];

            $report['empty_directories_deleted'] =
                $this->cleanupEmptyDirectories($dryRun);
        } catch (Throwable $exception) {
            $report['errors'][] = $exception->getMessage();

            Log::error('Dura Media cleanup failed.', [
                'exception' => $exception->getMessage(),
                'report' => $report,
            ]);

            throw new RuntimeException(
                'Media cleanup failed: ' . $exception->getMessage(),
                previous: $exception,
            );
        } finally {
            $report['finished_at'] =
                now()->toIso8601String();
        }

        Log::info('Dura Media cleanup completed.', $report);

        return $report;
    }

    /**
     * Remove usage records whose media record no longer exists.
     */
    public function cleanupOrphanUsageRecords(
        bool $dryRun = false,
    ): int {
        $usageIds = MediaUsage::query()
            ->whereNotExists(function ($query): void {
                $query
                    ->selectRaw('1')
                    ->from('app_media')
                    ->whereColumn(
                        'app_media.id',
                        'media_usages.app_media_id'
                    );
            })
            ->pluck('id');

        if ($usageIds->isEmpty()) {
            return 0;
        }

        if (!$dryRun) {
            MediaUsage::query()
                ->whereIn('id', $usageIds)
                ->delete();
        }

        return $usageIds->count();
    }

    /**
     * Synchronize app_media.reference_count with media_usages.
     */
    public function synchronizeAllReferenceCounts(
        bool $dryRun = false,
    ): int {
        $fixed = 0;

        AppMedia::withTrashed()
            ->select([
                'id',
                'reference_count',
            ])
            ->chunkById(
                200,
                function ($mediaRecords) use (
                    &$fixed,
                    $dryRun
                ): void {
                    foreach ($mediaRecords as $media) {
                        $actualCount = MediaUsage::query()
                            ->where(
                                'app_media_id',
                                $media->id
                            )
                            ->count();

                        if (
                            (int) $media->reference_count ===
                            $actualCount
                        ) {
                            continue;
                        }

                        $fixed++;

                        if (!$dryRun) {
                            AppMedia::withTrashed()
                                ->whereKey($media->id)
                                ->update([
                                    'reference_count' =>
                                        $actualCount,
                                ]);
                        }
                    }
                }
            );

        return $fixed;
    }

    /**
     * Delete active media that is unused and older than the configured age.
     *
     * @return array{found:int,deleted:int}
     */
    public function cleanupUnusedMedia(
        int $olderThanDays = 30,
        bool $dryRun = false,
    ): array {
        $cutoff = now()->subDays(
            max(0, $olderThanDays)
        );

        $found = 0;
        $deleted = 0;

        AppMedia::query()
            ->where('reference_count', 0)
            ->where('created_at', '<=', $cutoff)
            ->whereDoesntHave('usages')
            ->chunkById(
                100,
                function ($mediaRecords) use (
                    &$found,
                    &$deleted,
                    $dryRun
                ): void {
                    foreach ($mediaRecords as $media) {
                        $found++;

                        if ($dryRun) {
                            continue;
                        }

                        try {
                            if (
                                $this->mediaDeleteService
                                    ->deleteIfUnused(
                                        media: $media,
                                        force: true,
                                    )
                            ) {
                                $deleted++;
                            }
                        } catch (Throwable $exception) {
                            Log::warning(
                                'Unable to delete unused media.',
                                [
                                    'media_id' => $media->id,
                                    'media_uuid' => $media->uuid,
                                    'exception' =>
                                        $exception->getMessage(),
                                ]
                            );
                        }
                    }
                }
            );

        return [
            'found' => $found,
            'deleted' => $deleted,
        ];
    }

    /**
     * Permanently delete old soft-deleted media records.
     *
     * @return array{found:int,deleted:int}
     */
    public function cleanupSoftDeletedMedia(
        int $olderThanDays = 7,
        bool $dryRun = false,
    ): array {
        $cutoff = now()->subDays(
            max(0, $olderThanDays)
        );

        $found = 0;
        $deleted = 0;

        AppMedia::onlyTrashed()
            ->where('deleted_at', '<=', $cutoff)
            ->chunkById(
                100,
                function ($mediaRecords) use (
                    &$found,
                    &$deleted,
                    $dryRun
                ): void {
                    foreach ($mediaRecords as $media) {
                        $found++;

                        if ($dryRun) {
                            continue;
                        }

                        try {
                            $this->mediaDeleteService
                                ->forceDelete(
                                    media: $media,
                                    ignoreReferences: false,
                                );

                            $deleted++;
                        } catch (Throwable $exception) {
                            Log::warning(
                                'Unable to purge soft-deleted media.',
                                [
                                    'media_id' => $media->id,
                                    'media_uuid' => $media->uuid,
                                    'exception' =>
                                        $exception->getMessage(),
                                ]
                            );
                        }
                    }
                }
            );

        return [
            'found' => $found,
            'deleted' => $deleted,
        ];
    }

    /**
     * Find records whose original physical file is missing.
     *
     * @return array{found:int,deleted:int}
     */
    public function cleanupMissingOriginals(
        bool $deleteRecords = false,
        bool $dryRun = false,
    ): array {
        $found = 0;
        $deleted = 0;

        AppMedia::withTrashed()
            ->chunkById(
                100,
                function ($mediaRecords) use (
                    &$found,
                    &$deleted,
                    $deleteRecords,
                    $dryRun
                ): void {
                    foreach ($mediaRecords as $media) {
                        $path = trim(
                            (string) $media->original_path
                        );

                        if ($path === '') {
                            $found++;

                            if (
                                $deleteRecords &&
                                !$dryRun &&
                                !$media->hasReferences()
                            ) {
                                $media->forceDelete();
                                $deleted++;
                            }

                            continue;
                        }

                        try {
                            $exists = Storage::disk(
                                $media->disk ?: 'public'
                            )->exists($path);
                        } catch (Throwable $exception) {
                            Log::warning(
                                'Unable to inspect media original.',
                                [
                                    'media_id' => $media->id,
                                    'disk' => $media->disk,
                                    'path' => $path,
                                    'exception' =>
                                        $exception->getMessage(),
                                ]
                            );

                            continue;
                        }

                        if ($exists) {
                            continue;
                        }

                        $found++;

                        if (
                            $deleteRecords &&
                            !$dryRun &&
                            !$media->hasReferences()
                        ) {
                            $media->forceDelete();
                            $deleted++;
                        }
                    }
                }
            );

        return [
            'found' => $found,
            'deleted' => $deleted,
        ];
    }

    /**
     * Remove empty UUID media directories from supported disks.
     */
    public function cleanupEmptyDirectories(
        bool $dryRun = false,
    ): int {
        $deleted = 0;

        foreach ($this->cleanupDisks() as $diskName) {
            try {
                $disk = Storage::disk($diskName);

                if (!$disk->exists('app-media')) {
                    continue;
                }

                $directories = $disk
                    ->allDirectories('app-media');

                usort(
                    $directories,
                    static fn (
                        string $first,
                        string $second
                    ): int =>
                        substr_count($second, '/') <=>
                        substr_count($first, '/')
                );

                foreach ($directories as $directory) {
                    if (!$this->isSafeDirectory($directory)) {
                        continue;
                    }

                    $files = $disk->allFiles($directory);
                    $children = $disk->directories($directory);

                    if ($files !== [] || $children !== []) {
                        continue;
                    }

                    $deleted++;

                    if (!$dryRun) {
                        $disk->deleteDirectory($directory);
                    }
                }
            } catch (Throwable $exception) {
                Log::warning(
                    'Unable to clean empty media directories.',
                    [
                        'disk' => $diskName,
                        'exception' => $exception->getMessage(),
                    ]
                );
            }
        }

        return $deleted;
    }

    /**
     * Return Media Engine storage statistics.
     *
     * @return array<string, int|float>
     */
    public function statistics(): array
    {
        $totalMedia = AppMedia::withTrashed()->count();

        $activeMedia = AppMedia::query()
            ->where('is_active', true)
            ->count();

        $unusedMedia = AppMedia::query()
            ->where('reference_count', 0)
            ->whereDoesntHave('usages')
            ->count();

        $trashedMedia = AppMedia::onlyTrashed()->count();

        $originalBytes = (int) AppMedia::withTrashed()
            ->sum('original_size');

        $optimizedBytes = (int) AppMedia::withTrashed()
            ->sum('optimized_size');

        $savedBytes = max(
            0,
            $originalBytes - $optimizedBytes
        );

        $savedPercentage = $originalBytes > 0
            ? round(
                ($savedBytes / $originalBytes) * 100,
                2
            )
            : 0;

        return [
            'total_media' => $totalMedia,
            'active_media' => $activeMedia,
            'unused_media' => $unusedMedia,
            'trashed_media' => $trashedMedia,
            'original_bytes' => $originalBytes,
            'optimized_bytes' => $optimizedBytes,
            'saved_bytes' => $savedBytes,
            'saved_percentage' => $savedPercentage,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function cleanupDisks(): array
    {
        return collect(
            AppMedia::withTrashed()
                ->whereNotNull('disk')
                ->distinct()
                ->pluck('disk')
                ->all()
        )
            ->push('public')
            ->push('local')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function isSafeDirectory(
        string $directory,
    ): bool {
        $directory = trim(
            str_replace('\\', '/', $directory),
            '/'
        );

        if (
            $directory === '' ||
            $directory === 'app-media'
        ) {
            return false;
        }

        return str_starts_with(
            $directory,
            'app-media/'
        );
    }
}