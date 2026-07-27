<?php

namespace App\Services\Media;

use App\Models\AppMedia;
use App\Models\MediaUsage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final class MediaDeleteService
{
    /**
     * Delete a media record.
     *
     * New single-file records:
     * - Delete one WebP/PDF file.
     *
     * Legacy records:
     * - Delete original, large, medium and thumbnail files.
     *
     * @param bool $force
     *     false = soft-delete database record
     *     true  = permanently delete database record
     *
     * @param bool $ignoreReferences
     *     false = prevent deletion while media is in use
     *     true  = delete usages and media even when referenced
     */
    public function delete(
        AppMedia $media,
        bool $force = false,
        bool $ignoreReferences = false,
    ): bool {
        try {
            $media->refresh();

            $this->ensureMediaCanBeDeleted(
                media: $media,
                ignoreReferences: $ignoreReferences,
            );

            /*
             * Soft delete only hides the media record.
             *
             * The physical file is preserved so the media can be
             * restored later.
             */
            if (!$force) {
                return $this->softDelete(
                    media: $media,
                    ignoreReferences: $ignoreReferences,
                );
            }

            /*
             * Force delete removes:
             *
             * - Media usages
             * - Physical WebP/PDF file
             * - Legacy variant files
             * - Database media record
             */
            return $this->forceDelete(
                media: $media,
                ignoreReferences: $ignoreReferences,
            );
        } catch (Throwable $exception) {
            Log::error(
                'Media deletion failed.',
                [
                    'media_id' => $media->id,
                    'media_uuid' => $media->uuid,
                    'force' => $force,
                    'ignore_references' => $ignoreReferences,
                    'exception_class' => $exception::class,
                    'exception' => $exception->getMessage(),
                ]
            );

            throw new RuntimeException(
                'Unable to delete media: '
                . $exception->getMessage(),
                previous: $exception,
            );
        }
    }

    /**
     * Soft-delete a media record.
     *
     * Physical files are not deleted.
     */
    private function softDelete(
        AppMedia $media,
        bool $ignoreReferences,
    ): bool {
        return DB::transaction(
            function () use (
                $media,
                $ignoreReferences,
            ): bool {
                if ($ignoreReferences) {
                    $this->deleteUsages($media);
                }

                $this->synchronizeReferenceCount(
                    media: $media,
                );

                $media->forceFill([
                    'is_active' => false,

                    'metadata' => array_merge(
                        (array) $media->metadata,
                        [
                            'soft_deleted_at' =>
                                now()->toIso8601String(),

                            'storage_mode' =>
                                $media->usesSingleFileStorage()
                                    ? 'single_file'
                                    : 'legacy_variants',
                        ]
                    ),
                ])->save();

                $deleted = $media->delete();

                Log::info(
                    'Media soft deleted.',
                    [
                        'media_id' => $media->id,
                        'media_uuid' => $media->uuid,
                        'stored_path' => $media->storedPath(),
                    ]
                );

                return (bool) $deleted;
            }
        );
    }

    /**
     * Permanently delete media and all physical files.
     */
    private function forceDelete(
        AppMedia $media,
        bool $ignoreReferences,
    ): bool {
        /*
         * Capture paths before deleting the database record.
         */
        $disk = trim(
            (string) (
                $media->disk
                ?: 'public'
            )
        );

        if ($disk === '') {
            $disk = 'public';
        }

        $paths = $this->resolveStoredPaths(
            media: $media,
        );

        $mediaId = $media->id;
        $mediaUuid = $media->uuid;

        DB::transaction(
            function () use (
                $media,
                $ignoreReferences,
            ): void {
                if ($ignoreReferences) {
                    $this->deleteUsages($media);
                }

                /*
                 * References must now be zero.
                 */
                $this->synchronizeReferenceCount(
                    media: $media,
                );

                if ($media->hasReferences()) {
                    throw new RuntimeException(
                        'Media is still attached to one or more records.'
                    );
                }

                /*
                 * Database record is deleted before physical files.
                 *
                 * If physical deletion fails, the orphan cleanup
                 * command can find and remove the remaining file.
                 */
                $media->forceDelete();
            }
        );

        $fileResult = $this->deletePhysicalFiles(
            disk: $disk,
            paths: $paths,
        );

        $this->cleanupEmptyDirectories(
            disk: $disk,
            paths: $paths,
        );

        Log::info(
            'Media permanently deleted.',
            [
                'media_id' => $mediaId,
                'media_uuid' => $mediaUuid,
                'disk' => $disk,
                'deleted_paths' => $fileResult['deleted'],
                'missing_paths' => $fileResult['missing'],
                'failed_paths' => $fileResult['failed'],
            ]
        );

        /*
         * Database record is already deleted.
         *
         * Return true even when an already-missing file was detected.
         * Return false only when Storage explicitly failed to delete
         * one or more files.
         */
        return $fileResult['failed'] === [];
    }

    /**
     * Ensure deletion is safe.
     */
    private function ensureMediaCanBeDeleted(
        AppMedia $media,
        bool $ignoreReferences,
    ): void {
        if (!$media->exists) {
            throw new RuntimeException(
                'The media record does not exist.'
            );
        }

        if ($ignoreReferences) {
            return;
        }

        $actualUsageCount = MediaUsage::query()
            ->where(
                'app_media_id',
                $media->id
            )
            ->count();

        if (
            $actualUsageCount > 0
            || max(
                0,
                (int) $media->reference_count
            ) > 0
        ) {
            throw new RuntimeException(
                "This media is currently used by "
                . max(
                    $actualUsageCount,
                    (int) $media->reference_count
                )
                . ' record(s). Detach or replace it before deleting.'
            );
        }
    }

    /**
     * Delete all model usage relationships.
     */
    private function deleteUsages(
        AppMedia $media,
    ): void {
        MediaUsage::query()
            ->where(
                'app_media_id',
                $media->id
            )
            ->delete();

        $media->forceFill([
            'reference_count' => 0,
        ])->save();
    }

    /**
     * Synchronize reference count from actual usage rows.
     */
    private function synchronizeReferenceCount(
        AppMedia $media,
    ): void {
        if (!$media->exists) {
            return;
        }

        $count = MediaUsage::query()
            ->where(
                'app_media_id',
                $media->id
            )
            ->count();

        $media->forceFill([
            'reference_count' => $count,
        ])->save();
    }

    /**
     * Resolve every physical path related to this media.
     *
     * New uploads return one path.
     * Legacy records may return four or more paths.
     *
     * @return array<int, string>
     */
    private function resolveStoredPaths(
        AppMedia $media,
    ): array {
        $paths = $media->allStoredPaths();

        /*
         * Older versions may have stored additional paths inside
         * the metadata JSON.
         */
        $metadata = (array) $media->metadata;

        $metadataPaths = [
            $metadata['path'] ?? null,
            $metadata['stored_path'] ?? null,
            $metadata['webp_path'] ?? null,
            $metadata['original_path'] ?? null,
            $metadata['large_path'] ?? null,
            $metadata['medium_path'] ?? null,
            $metadata['thumbnail_path'] ?? null,
        ];

        $variantPaths = $metadata['variant_paths']
            ?? [];

        if (is_array($variantPaths)) {
            foreach ($variantPaths as $variantPath) {
                if (is_array($variantPath)) {
                    foreach ($variantPath as $nestedPath) {
                        $metadataPaths[] = $nestedPath;
                    }

                    continue;
                }

                $metadataPaths[] = $variantPath;
            }
        }

        $variants = $metadata['variants']
            ?? [];

        if (is_array($variants)) {
            foreach ($variants as $variant) {
                if (is_string($variant)) {
                    $metadataPaths[] = $variant;

                    continue;
                }

                if (is_array($variant)) {
                    $metadataPaths[] =
                        $variant['path']
                        ?? null;
                }
            }
        }

        return array_values(
            array_unique(
                array_filter(
                    array_map(
                        static function (
                            mixed $path,
                        ): string {
                            if (!is_string($path)) {
                                return '';
                            }

                            $path = trim(
                                str_replace(
                                    '\\',
                                    '/',
                                    $path
                                )
                            );

                            /*
                             * Remote URLs are not managed by Laravel
                             * Storage and must not be deleted here.
                             */
                            if (
                                str_starts_with(
                                    $path,
                                    'http://'
                                )
                                || str_starts_with(
                                    $path,
                                    'https://'
                                )
                            ) {
                                return '';
                            }

                            return ltrim(
                                $path,
                                '/'
                            );
                        },
                        [
                            ...$paths,
                            ...$metadataPaths,
                        ]
                    ),
                    static fn (
                        string $path,
                    ): bool => $path !== ''
                )
            )
        );
    }

    /**
     * Delete physical media files.
     *
     * @param array<int, string> $paths
     * @return array{
     *     deleted: array<int, string>,
     *     missing: array<int, string>,
     *     failed: array<int, string>
     * }
     */
    private function deletePhysicalFiles(
        string $disk,
        array $paths,
    ): array {
        $result = [
            'deleted' => [],
            'missing' => [],
            'failed' => [],
        ];

        if ($paths === []) {
            return $result;
        }

        $storage = Storage::disk($disk);

        foreach ($paths as $path) {
            try {
                if (!$storage->exists($path)) {
                    $result['missing'][] = $path;

                    continue;
                }

                $deleted = $storage->delete(
                    $path
                );

                if ($deleted) {
                    $result['deleted'][] = $path;

                    continue;
                }

                $result['failed'][] = $path;
            } catch (Throwable $exception) {
                $result['failed'][] = $path;

                Log::warning(
                    'Physical media file could not be deleted.',
                    [
                        'disk' => $disk,
                        'path' => $path,
                        'exception' =>
                            $exception->getMessage(),
                    ]
                );
            }
        }

        return $result;
    }

    /**
     * Remove empty media folders after files are deleted.
     *
     * The method walks upward only inside app-media and stops
     * before removing the main app-media directory.
     *
     * @param array<int, string> $paths
     */
    private function cleanupEmptyDirectories(
        string $disk,
        array $paths,
    ): void {
        if ($paths === []) {
            return;
        }

        $storage = Storage::disk($disk);

        foreach ($paths as $path) {
            $directory = trim(
                str_replace(
                    '\\',
                    '/',
                    dirname($path)
                ),
                './'
            );

            while (
                $directory !== ''
                && $directory !== '.'
                && $directory !== 'app-media'
                && str_starts_with(
                    $directory,
                    'app-media/'
                )
            ) {
                try {
                    $files = $storage->files(
                        $directory
                    );

                    $directories =
                        $storage->directories(
                            $directory
                        );

                    if (
                        $files !== []
                        || $directories !== []
                    ) {
                        break;
                    }

                    $storage->deleteDirectory(
                        $directory
                    );

                    $directory = trim(
                        str_replace(
                            '\\',
                            '/',
                            dirname($directory)
                        ),
                        './'
                    );
                } catch (Throwable $exception) {
                    Log::debug(
                        'Empty media directory cleanup stopped.',
                        [
                            'disk' => $disk,
                            'directory' => $directory,
                            'exception' =>
                                $exception->getMessage(),
                        ]
                    );

                    break;
                }
            }
        }
    }

    /**
     * Restore a soft-deleted media record.
     *
     * Restore succeeds only when the stored physical file exists.
     */
    public function restore(
        AppMedia $media,
    ): bool {
        try {
            if (!$media->trashed()) {
                return true;
            }

            if (!$media->fileExists()) {
                throw new RuntimeException(
                    'The media file is missing and cannot be restored.'
                );
            }

            return DB::transaction(
                function () use ($media): bool {
                    $restored = $media->restore();

                    if (!$restored) {
                        return false;
                    }

                    $media->forceFill([
                        'is_active' => true,

                        'metadata' => array_merge(
                            (array) $media->metadata,
                            [
                                'restored_at' =>
                                    now()->toIso8601String(),
                            ]
                        ),
                    ])->save();

                    $this->synchronizeReferenceCount(
                        media: $media,
                    );

                    Log::info(
                        'Media restored.',
                        [
                            'media_id' => $media->id,
                            'media_uuid' => $media->uuid,
                            'stored_path' =>
                                $media->storedPath(),
                        ]
                    );

                    return true;
                }
            );
        } catch (Throwable $exception) {
            Log::error(
                'Media restoration failed.',
                [
                    'media_id' => $media->id,
                    'media_uuid' => $media->uuid,
                    'exception' =>
                        $exception->getMessage(),
                ]
            );

            throw new RuntimeException(
                'Unable to restore media: '
                . $exception->getMessage(),
                previous: $exception,
            );
        }
    }

    /**
     * Permanently purge a previously soft-deleted media record.
     */
    public function purge(
        AppMedia $media,
    ): bool {
        if (!$media->trashed()) {
            throw new RuntimeException(
                'Only soft-deleted media can be purged.'
            );
        }

        return $this->delete(
            media: $media,
            force: true,
            ignoreReferences: false,
        );
    }
}