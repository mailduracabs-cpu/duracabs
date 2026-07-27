<?php

namespace App\Actions\Media;

use App\Enums\MediaType;
use App\Models\AppMedia;
use App\Models\MediaUsage;
use App\Services\Media\MediaDeleteService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

final class ReplaceMedia
{
    public function __construct(
        private readonly ProcessMediaUpload $processMediaUpload,
        private readonly MediaDeleteService $mediaDeleteService,
    ) {
    }

    /**
     * Replace existing media with a newly uploaded file.
     *
     * Replacement flow:
     *
     * 1. Upload and process the new file first.
     * 2. Store it as one optimized WebP or one document file.
     * 3. Transfer existing media usages to the new media.
     * 4. Set every usage to the single "original" path.
     * 5. Delete the old media only when no references remain.
     *
     * @param UploadedFile|string $newFile
     * @param MediaType|string $mediaType
     * @param array<string, mixed> $options
     */
    public function handle(
        AppMedia $oldMedia,
        UploadedFile|string $newFile,
        MediaType|string $mediaType,
        ?string $module = null,
        array $options = [],
        ?Model $owner = null,
        ?string $fieldName = null,
        string $preferredVariant = 'original',
    ): AppMedia {
        $newMedia = null;

        try {
            $uploadOptions = $this->buildUploadOptions(
                oldMedia: $oldMedia,
                options: $options,
            );

            /*
             * Upload without attaching first.
             *
             * Existing usage records are transferred only after the
             * new file and database record have been created successfully.
             */
            $newMedia = $this->processMediaUpload->upload(
                file: $newFile,
                mediaType: $mediaType,
                module: $module ?? $oldMedia->module,
                options: $uploadOptions,
            );

            /*
             * Duplicate detection may return the same media record.
             *
             * In that case no file replacement or usage transfer
             * is necessary.
             */
            if ($newMedia->is($oldMedia)) {
                $this->normalizeMediaUsages(
                    media: $oldMedia,
                );

                $this->synchronizeReferenceCount(
                    media: $oldMedia,
                );

                Log::info(
                    'Media replacement reused the existing file.',
                    [
                        'media_id' => $oldMedia->id,
                        'media_uuid' => $oldMedia->uuid,
                        'storage_mode' => 'single_file',
                    ]
                );

                return $oldMedia->refresh();
            }

            DB::transaction(
                function () use (
                    $oldMedia,
                    $newMedia,
                    $owner,
                    $fieldName,
                ): void {
                    $this->transferUsages(
                        oldMedia: $oldMedia,
                        newMedia: $newMedia,
                    );

                    if (
                        $owner !== null
                        && filled($fieldName)
                    ) {
                        $this->ensureOwnerUsage(
                            media: $newMedia,
                            owner: $owner,
                            fieldName: trim(
                                (string) $fieldName
                            ),
                        );
                    }

                    $this->synchronizeReferenceCount(
                        media: $oldMedia,
                    );

                    $this->synchronizeReferenceCount(
                        media: $newMedia,
                    );
                }
            );

            $oldMedia->refresh();
            $newMedia->refresh();

            /*
             * Delete the old file and media record only when all
             * usages were transferred successfully.
             */
            if (!$oldMedia->hasReferences()) {
                $this->mediaDeleteService->delete(
                    media: $oldMedia,
                    force: true,
                    ignoreReferences: false,
                );
            }

            Log::info(
                'Media replaced successfully.',
                [
                    'old_media_id' => $oldMedia->id,
                    'old_media_uuid' => $oldMedia->uuid,

                    'new_media_id' => $newMedia->id,
                    'new_media_uuid' => $newMedia->uuid,

                    'new_media_path' =>
                        $newMedia->storedPath(),

                    'storage_mode' =>
                        'single_file',

                    'owner_type' =>
                        $owner?->getMorphClass(),

                    'owner_id' =>
                        $owner?->getKey(),

                    'field_name' =>
                        $fieldName,
                ]
            );

            return $newMedia->refresh();
        } catch (Throwable $exception) {
            /*
             * Remove the newly uploaded media when replacement fails
             * and nothing currently references it.
             */
            if (
                $newMedia instanceof AppMedia
                && !$newMedia->is($oldMedia)
            ) {
                $this->cleanupFailedReplacement(
                    media: $newMedia,
                );
            }

            Log::error(
                'Media replacement failed.',
                [
                    'old_media_id' =>
                        $oldMedia->id,

                    'old_media_uuid' =>
                        $oldMedia->uuid,

                    'new_media_id' =>
                        $newMedia?->id,

                    'owner_type' =>
                        $owner?->getMorphClass(),

                    'owner_id' =>
                        $owner?->getKey(),

                    'field_name' =>
                        $fieldName,

                    'exception_class' =>
                        $exception::class,

                    'exception' =>
                        $exception->getMessage(),
                ]
            );

            throw new RuntimeException(
                'Unable to replace media: '
                . $exception->getMessage(),
                previous: $exception,
            );
        }
    }

    /**
     * Replace media attached to a specific model field.
     *
     * preferredVariant remains in the method signature for
     * backward compatibility. Single-file mode always saves
     * the value "original".
     *
     * @param UploadedFile|string $newFile
     * @param MediaType|string $mediaType
     * @param array<string, mixed> $options
     */
    public function replaceForModel(
        AppMedia $oldMedia,
        UploadedFile|string $newFile,
        MediaType|string $mediaType,
        Model $owner,
        string $fieldName,
        ?string $module = null,
        string $preferredVariant = 'original',
        array $options = [],
    ): AppMedia {
        if (!$owner->exists) {
            throw new RuntimeException(
                'The owner model must be saved before replacing its media.'
            );
        }

        if (blank($fieldName)) {
            throw new RuntimeException(
                'The media field name is required.'
            );
        }

        return $this->handle(
            oldMedia: $oldMedia,
            newFile: $newFile,
            mediaType: $mediaType,
            module: $module,
            options: $options,
            owner: $owner,
            fieldName: trim($fieldName),

            /*
             * Ignore medium, large or thumbnail supplied
             * by legacy callers.
             */
            preferredVariant: 'original',
        );
    }

    /**
     * Compatibility wrapper for older code using replace().
     *
     * This method supports calls such as:
     *
     * app(ReplaceMedia::class)->replace(
     *     model: $record,
     *     field: 'app_media_id',
     *     upload: $temporaryPath,
     *     module: 'banners',
     * );
     *
     * @param UploadedFile|string $upload
     * @param array<string, mixed> $options
     */
    public function replace(
        Model $model,
        string $field,
        UploadedFile|string $upload,
        MediaType|string $mediaType = MediaType::Banner,
        ?string $module = null,
        array $options = [],
    ): AppMedia {
        if (!$model->exists) {
            throw new RuntimeException(
                'The model must be saved before replacing media.'
            );
        }

        $mediaId = $model->getAttribute($field);

        $oldMedia = filled($mediaId)
            ? AppMedia::query()->find($mediaId)
            : null;

        /*
         * No existing media means this is effectively a new upload.
         */
        if (!$oldMedia instanceof AppMedia) {
            $newMedia =
                $this->processMediaUpload
                    ->uploadForModel(
                        file: $upload,
                        mediaType: $mediaType,
                        owner: $model,
                        fieldName: $field,
                        module: $module,
                        preferredVariant: 'original',
                        options: $options,
                    );

            $model->forceFill([
                $field => $newMedia->getKey(),
            ])->save();

            return $newMedia->refresh();
        }

        $newMedia = $this->replaceForModel(
            oldMedia: $oldMedia,
            newFile: $upload,
            mediaType: $mediaType,
            owner: $model,
            fieldName: $field,
            module: $module,
            preferredVariant: 'original',
            options: $options,
        );

        $model->forceFill([
            $field => $newMedia->getKey(),
        ])->save();

        return $newMedia->refresh();
    }

    /**
     * Transfer every usage from old media to new media.
     */
    private function transferUsages(
        AppMedia $oldMedia,
        AppMedia $newMedia,
    ): void {
        $oldUsages = MediaUsage::query()
            ->where(
                'app_media_id',
                $oldMedia->id
            )
            ->get();

        foreach ($oldUsages as $oldUsage) {
            $existingUsage = MediaUsage::query()
                ->where(
                    'app_media_id',
                    $newMedia->id
                )
                ->where(
                    'usable_type',
                    $oldUsage->usable_type
                )
                ->where(
                    'usable_id',
                    $oldUsage->usable_id
                )
                ->where(
                    'field_name',
                    $oldUsage->field_name
                )
                ->first();

            $usageMetadata = array_merge(
                (array) $oldUsage->metadata,
                [
                    'storage_mode' =>
                        'single_file',

                    'preferred_variant' =>
                        'original',

                    'transferred_from_media_id' =>
                        $oldMedia->id,

                    'transferred_from_media_uuid' =>
                        $oldMedia->uuid,

                    'transferred_at' =>
                        now()->toIso8601String(),
                ]
            );

            if ($existingUsage !== null) {
                $existingUsage->update([
                    'preferred_variant' =>
                        'original',

                    'metadata' => array_merge(
                        (array) $existingUsage->metadata,
                        $usageMetadata,
                    ),
                ]);

                $oldUsage->delete();

                continue;
            }

            $oldUsage->update([
                'app_media_id' =>
                    $newMedia->id,

                'preferred_variant' =>
                    'original',

                'metadata' =>
                    $usageMetadata,
            ]);
        }
    }

    /**
     * Ensure that the supplied owner field uses the new media.
     */
    private function ensureOwnerUsage(
        AppMedia $media,
        Model $owner,
        string $fieldName,
    ): void {
        if (!$owner->exists) {
            throw new RuntimeException(
                'The owner model must be saved before replacing its media.'
            );
        }

        if ($fieldName === '') {
            throw new RuntimeException(
                'The media field name is required.'
            );
        }

        MediaUsage::query()->updateOrCreate(
            [
                'app_media_id' =>
                    $media->id,

                'usable_type' =>
                    $owner->getMorphClass(),

                'usable_id' =>
                    $owner->getKey(),

                'field_name' =>
                    $fieldName,
            ],
            [
                'preferred_variant' =>
                    'original',

                'metadata' => [
                    'storage_mode' =>
                        'single_file',

                    'attached_during_replace' =>
                        true,

                    'attached_at' =>
                        now()->toIso8601String(),
                ],
            ]
        );
    }

    /**
     * Convert every existing usage of a media record to
     * the single-file compatibility value.
     */
    private function normalizeMediaUsages(
        AppMedia $media,
    ): void {
        MediaUsage::query()
            ->where(
                'app_media_id',
                $media->id
            )
            ->update([
                'preferred_variant' =>
                    'original',
            ]);
    }

    /**
     * Synchronize one media reference count.
     */
    private function synchronizeReferenceCount(
        AppMedia $media,
    ): void {
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
     * Build upload options for the replacement file.
     *
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function buildUploadOptions(
        AppMedia $oldMedia,
        array $options,
    ): array {
        $oldMetadata = $this->cleanMetadata(
            (array) $oldMedia->metadata
        );

        $newMetadata = $this->cleanMetadata(
            (array) (
                $options['metadata']
                ?? []
            )
        );

        $defaultOptions = [
            'name' =>
                $oldMedia->name,

            'slug' =>
                null,

            'alt_text' =>
                $oldMedia->alt_text,

            'caption' =>
                $oldMedia->caption,

            'sort_order' =>
                $oldMedia->sort_order,

            'is_active' =>
                $oldMedia->is_active,

            'is_public' =>
                $oldMedia->is_public,

            'quality' =>
                $oldMedia->quality,

            'metadata' => array_merge(
                $oldMetadata,
                $newMetadata,
                [
                    'storage_mode' =>
                        'single_file',

                    'variants_generated' =>
                        false,

                    'replaced_media_id' =>
                        $oldMedia->id,

                    'replaced_media_uuid' =>
                        $oldMedia->uuid,

                    'replaced_at' =>
                        now()->toIso8601String(),
                ]
            ),
        ];

        /*
         * Remove metadata before merging to avoid replacing the
         * normalized metadata array with unclean legacy metadata.
         */
        unset($options['metadata']);

        return array_merge(
            $defaultOptions,
            $options,
        );
    }

    /**
     * Remove legacy variant metadata.
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
        );

        return $metadata;
    }

    /**
     * Delete an unused new media record after a failed replacement.
     */
    private function cleanupFailedReplacement(
        AppMedia $media,
    ): void {
        try {
            $media->refresh();

            if ($media->hasReferences()) {
                return;
            }

            $this->mediaDeleteService->delete(
                media: $media,
                force: true,
                ignoreReferences: false,
            );
        } catch (Throwable $cleanupException) {
            Log::warning(
                'Failed to clean replacement media.',
                [
                    'new_media_id' =>
                        $media->id,

                    'exception' =>
                        $cleanupException->getMessage(),
                ]
            );
        }
    }
}