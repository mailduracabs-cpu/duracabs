<?php

namespace App\Actions\Media;

use App\Models\AppMedia;
use App\Services\Media\MediaDeleteService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class DeleteMedia
{
    public function __construct(
        private readonly MediaDeleteService $mediaDeleteService,
    ) {
    }

    /**
     * Delete a media record.
     *
     * Default behavior:
     * - Prevent deletion when media is still in use.
     * - Soft-delete the database record.
     * - Keep the physical file for restore.
     *
     * Permanent behavior:
     * - Delete the physical WebP/document.
     * - Delete legacy variant files when present.
     * - Permanently remove the database record.
     */
    public function handle(
        AppMedia|int|string $media,
        bool $force = false,
        bool $ignoreReferences = false,
    ): bool {
        $resolvedMedia = $this->resolveMedia(
            media: $media,
        );

        try {
            $result = $this->mediaDeleteService->delete(
                media: $resolvedMedia,
                force: $force,
                ignoreReferences: $ignoreReferences,
            );

            Log::info(
                'DeleteMedia action completed.',
                [
                    'media_id' => $resolvedMedia->id,
                    'media_uuid' => $resolvedMedia->uuid,

                    'stored_path' =>
                        $resolvedMedia->storedPath(),

                    'storage_mode' =>
                        $resolvedMedia
                            ->usesSingleFileStorage()
                            ? 'single_file'
                            : 'legacy_variants',

                    'force' => $force,

                    'ignore_references' =>
                        $ignoreReferences,

                    'result' => $result,
                ]
            );

            return $result;
        } catch (Throwable $exception) {
            Log::error(
                'DeleteMedia action failed.',
                [
                    'media_id' =>
                        $resolvedMedia->id,

                    'media_uuid' =>
                        $resolvedMedia->uuid,

                    'force' => $force,

                    'ignore_references' =>
                        $ignoreReferences,

                    'exception_class' =>
                        $exception::class,

                    'exception' =>
                        $exception->getMessage(),
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
     * Soft-delete media.
     *
     * Physical file remains available for restore.
     */
    public function softDelete(
        AppMedia|int|string $media,
    ): bool {
        return $this->handle(
            media: $media,
            force: false,
            ignoreReferences: false,
        );
    }

    /**
     * Permanently delete unused media.
     *
     * Deletion will fail when references still exist.
     */
    public function forceDelete(
        AppMedia|int|string $media,
    ): bool {
        return $this->handle(
            media: $media,
            force: true,
            ignoreReferences: false,
        );
    }

    /**
     * Permanently delete media and all its usage rows.
     *
     * Use this only for administrative cleanup.
     */
    public function forceDeleteWithReferences(
        AppMedia|int|string $media,
    ): bool {
        return $this->handle(
            media: $media,
            force: true,
            ignoreReferences: true,
        );
    }

    /**
     * Remove media from one model field.
     *
     * This method:
     *
     * 1. Removes the MediaUsage relationship.
     * 2. Clears the owner's media ID field when applicable.
     * 3. Recalculates reference_count.
     * 4. Deletes the media permanently only when no usage remains
     *    and deleteUnusedMedia is true.
     */
    public function detachFromModel(
        AppMedia|int|string $media,
        Model $owner,
        string $fieldName,
        bool $deleteUnusedMedia = true,
    ): bool {
        $resolvedMedia = $this->resolveMedia(
            media: $media,
        );

        if (!$owner->exists) {
            throw new InvalidArgumentException(
                'The related model must be saved before media can be detached.'
            );
        }

        $fieldName = trim($fieldName);

        if ($fieldName === '') {
            throw new InvalidArgumentException(
                'The media field name is required.'
            );
        }

        try {
            $remainingReferences = DB::transaction(
                function () use (
                    $resolvedMedia,
                    $owner,
                    $fieldName,
                ): int {
                    $resolvedMedia->usages()
                        ->where(
                            'usable_type',
                            $owner->getMorphClass()
                        )
                        ->where(
                            'usable_id',
                            $owner->getKey()
                        )
                        ->where(
                            'field_name',
                            $fieldName
                        )
                        ->delete();

                    /*
                     * Clear direct foreign-key-style fields such as:
                     *
                     * app_media_id
                     * image_media_id
                     * banner_media_id
                     *
                     * This check prevents unrelated string image paths
                     * from being overwritten.
                     */
                    if (
                        $this->fieldContainsMediaId(
                            owner: $owner,
                            fieldName: $fieldName,
                            media: $resolvedMedia,
                        )
                    ) {
                        $owner->forceFill([
                            $fieldName => null,
                        ])->save();
                    }

                    $count = $resolvedMedia
                        ->usages()
                        ->count();

                    $resolvedMedia->forceFill([
                        'reference_count' => $count,
                    ])->save();

                    return $count;
                }
            );

            if (
                $deleteUnusedMedia
                && $remainingReferences === 0
            ) {
                return $this->forceDelete(
                    media: $resolvedMedia,
                );
            }

            Log::info(
                'Media detached from model.',
                [
                    'media_id' =>
                        $resolvedMedia->id,

                    'media_uuid' =>
                        $resolvedMedia->uuid,

                    'owner_type' =>
                        $owner->getMorphClass(),

                    'owner_id' =>
                        $owner->getKey(),

                    'field_name' =>
                        $fieldName,

                    'remaining_references' =>
                        $remainingReferences,

                    'media_deleted' => false,
                ]
            );

            return true;
        } catch (Throwable $exception) {
            Log::error(
                'Unable to detach media from model.',
                [
                    'media_id' =>
                        $resolvedMedia->id,

                    'media_uuid' =>
                        $resolvedMedia->uuid,

                    'owner_type' =>
                        $owner->getMorphClass(),

                    'owner_id' =>
                        $owner->getKey(),

                    'field_name' =>
                        $fieldName,

                    'exception_class' =>
                        $exception::class,

                    'exception' =>
                        $exception->getMessage(),
                ]
            );

            throw new RuntimeException(
                'Unable to detach media: '
                . $exception->getMessage(),
                previous: $exception,
            );
        }
    }

    /**
     * Remove a media ID stored directly on a model field.
     *
     * This compatibility method is useful for Filament pages that
     * currently store app_media.id in a column without creating a
     * MediaUsage row.
     */
    public function deleteForModelField(
        Model $owner,
        string $fieldName,
        bool $force = true,
    ): bool {
        if (!$owner->exists) {
            throw new InvalidArgumentException(
                'The related model must be saved before media can be deleted.'
            );
        }

        $fieldName = trim($fieldName);

        if ($fieldName === '') {
            throw new InvalidArgumentException(
                'The media field name is required.'
            );
        }

        $mediaId = $owner->getAttribute(
            $fieldName
        );

        if (blank($mediaId)) {
            return true;
        }

        $media = AppMedia::withTrashed()
            ->find($mediaId);

        /*
         * Clear an invalid media ID even when its media record
         * no longer exists.
         */
        if (!$media instanceof AppMedia) {
            $owner->forceFill([
                $fieldName => null,
            ])->save();

            return true;
        }

        return DB::transaction(
            function () use (
                $owner,
                $fieldName,
                $media,
                $force,
            ): bool {
                $owner->forceFill([
                    $fieldName => null,
                ])->save();

                $media->usages()
                    ->where(
                        'usable_type',
                        $owner->getMorphClass()
                    )
                    ->where(
                        'usable_id',
                        $owner->getKey()
                    )
                    ->where(
                        'field_name',
                        $fieldName
                    )
                    ->delete();

                $referenceCount =
                    $media->usages()->count();

                $media->forceFill([
                    'reference_count' =>
                        $referenceCount,
                ])->save();

                if ($referenceCount > 0) {
                    return true;
                }

                return $this->handle(
                    media: $media,
                    force: $force,
                    ignoreReferences: false,
                );
            }
        );
    }

    /**
     * Restore a soft-deleted media record.
     */
    public function restore(
        AppMedia|int|string $media,
    ): bool {
        $resolvedMedia = $this->resolveMedia(
            media: $media,
            withTrashed: true,
        );

        return $this->mediaDeleteService->restore(
            media: $resolvedMedia,
        );
    }

    /**
     * Permanently purge a soft-deleted media record.
     */
    public function purge(
        AppMedia|int|string $media,
    ): bool {
        $resolvedMedia = $this->resolveMedia(
            media: $media,
            withTrashed: true,
        );

        return $this->mediaDeleteService->purge(
            media: $resolvedMedia,
        );
    }

    /**
     * Resolve media by model, numeric ID, UUID or slug.
     */
    private function resolveMedia(
        AppMedia|int|string $media,
        bool $withTrashed = false,
    ): AppMedia {
        if ($media instanceof AppMedia) {
            return $media;
        }

        $query = $withTrashed
            ? AppMedia::withTrashed()
            : AppMedia::query();

        if (is_int($media)) {
            $resolved = $query->find($media);

            if (!$resolved instanceof AppMedia) {
                throw new InvalidArgumentException(
                    "Media record not found: {$media}"
                );
            }

            return $resolved;
        }

        $value = trim($media);

        if ($value === '') {
            throw new InvalidArgumentException(
                'A valid media ID, UUID or slug is required.'
            );
        }

        $resolved = $query
            ->where(function ($builder) use (
                $value,
            ): void {
                if (ctype_digit($value)) {
                    $builder->where(
                        'id',
                        (int) $value
                    )->orWhere(
                        'uuid',
                        $value
                    )->orWhere(
                        'slug',
                        $value
                    );

                    return;
                }

                $builder->where(
                    'uuid',
                    $value
                )->orWhere(
                    'slug',
                    $value
                );
            })
            ->first();

        if (!$resolved instanceof AppMedia) {
            throw new InvalidArgumentException(
                "Media record not found: {$value}"
            );
        }

        return $resolved;
    }

    /**
     * Determine whether the supplied model field currently contains
     * the media record's ID.
     */
    private function fieldContainsMediaId(
        Model $owner,
        string $fieldName,
        AppMedia $media,
    ): bool {
        $attributes = $owner->getAttributes();

        if (!array_key_exists(
            $fieldName,
            $attributes
        )) {
            return false;
        }

        $currentValue = $owner->getAttribute(
            $fieldName
        );

        if ($currentValue === null) {
            return false;
        }

        return (string) $currentValue
            === (string) $media->getKey();
    }
}