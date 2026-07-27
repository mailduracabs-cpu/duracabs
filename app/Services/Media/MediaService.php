<?php

namespace App\Services\Media;

use App\Enums\MediaType;
use App\Models\AppMedia;
use App\Models\MediaUsage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

final class MediaService
{
    public function __construct(
        private readonly ImageOptimizationService $optimizer,
        private readonly ImageHashService $hashService,
    ) {
    }

    /**
     * Upload a media file or reuse an existing duplicate.
     *
     * Images are stored as one optimized WebP file.
     * Documents are stored as one original document file.
     *
     * @param array<string, mixed> $options
     */
    public function upload(
        UploadedFile $file,
        MediaType $type,
        ?string $module = null,
        array $options = [],
    ): AppMedia {
        $hash = $this->hashService->generate($file);

        /*
         * Reuse an existing active media record only when:
         * - uploaded file content is identical,
         * - media type is identical,
         * - module is identical.
         *
         * This prevents a banner upload from accidentally reusing
         * a vehicle or private-document media record.
         */
        $existing = AppMedia::query()
            ->where('file_hash', $hash)
            ->where('media_type', $type->value)
            ->where(function ($query) use ($module): void {
                if (filled($module)) {
                    $query->where('module', $module);
                } else {
                    $query->whereNull('module');
                }
            })
            ->whereNull('deleted_at')
            ->first();

        if ($existing !== null) {
            /*
             * Confirm that the single stored file still exists.
             * A database record whose file is missing must not be reused.
             */
            if ($existing->fileExists()) {
                return $existing;
            }

            /*
             * Preserve the broken record for audit history but prevent
             * it from being returned as valid media.
             */
            $existing->update([
                'is_active' => false,
                'metadata' => array_merge(
                    (array) $existing->metadata,
                    [
                        'missing_file_detected_at' =>
                            now()->toIso8601String(),
                    ]
                ),
            ]);
        }

        return $this->optimizer->upload(
            file: $file,
            mediaType: $type,
            module: $module,
            options: $options,
            hash: $hash,
        );
    }

    /**
     * Upload a file and attach it to an Eloquent model.
     *
     * @param array<string, mixed> $options
     */
    public function uploadAndAttach(
        UploadedFile $file,
        MediaType $type,
        Model $usable,
        string $fieldName,
        ?string $module = null,
        string $preferredVariant = 'original',
        array $options = [],
    ): AppMedia {
        if (!$usable->exists) {
            throw new RuntimeException(
                'The related model must be saved before media can be attached.'
            );
        }

        return DB::transaction(
            function () use (
                $file,
                $type,
                $usable,
                $fieldName,
                $module,
                $options,
            ): AppMedia {
                $media = $this->upload(
                    file: $file,
                    type: $type,
                    module: $module,
                    options: $options,
                );

                $this->attach(
                    media: $media,
                    usable: $usable,
                    fieldName: $fieldName,
                    /*
                     * Single-file mode always uses original.
                     * The original_path column now contains the final
                     * optimized WebP file, not the uploaded JPG/PNG.
                     */
                    preferredVariant: 'original',
                    metadata: array_merge(
                        (array) (
                            $options['usage_metadata']
                            ?? []
                        ),
                        [
                            'storage_mode' => 'single_file',
                        ]
                    ),
                );

                return $media->refresh();
            }
        );
    }

    /**
     * Register that a model uses a media record.
     *
     * preferred_variant is retained temporarily for database
     * compatibility, but its value is always "original".
     *
     * @param array<string, mixed> $metadata
     */
    public function attach(
        AppMedia $media,
        Model $usable,
        string $fieldName,
        string $preferredVariant = 'original',
        array $metadata = [],
    ): MediaUsage {
        if (!$usable->exists) {
            throw new RuntimeException(
                'The related model must be saved before media can be attached.'
            );
        }

        if (blank($fieldName)) {
            throw new RuntimeException(
                'A media field name is required.'
            );
        }

        return DB::transaction(
            function () use (
                $media,
                $usable,
                $fieldName,
                $metadata,
            ): MediaUsage {
                /*
                 * In single-file architecture there is no medium,
                 * large or thumbnail variant.
                 */
                $usage = MediaUsage::query()
                    ->firstOrCreate(
                        [
                            'app_media_id' => $media->id,

                            'usable_type' =>
                                $usable->getMorphClass(),

                            'usable_id' =>
                                $usable->getKey(),

                            'field_name' => $fieldName,
                        ],
                        [
                            'preferred_variant' => 'original',

                            'metadata' => array_merge(
                                $metadata,
                                [
                                    'storage_mode' =>
                                        'single_file',
                                ]
                            ),
                        ]
                    );

                if ($usage->wasRecentlyCreated) {
                    $media->incrementReferenceCount();
                } else {
                    $usage->update([
                        'preferred_variant' => 'original',

                        'metadata' => array_merge(
                            (array) $usage->metadata,
                            $metadata,
                            [
                                'storage_mode' =>
                                    'single_file',
                            ]
                        ),
                    ]);
                }

                return $usage->refresh();
            }
        );
    }

    /**
     * Remove one model-to-media usage relationship.
     */
    public function detach(
        AppMedia $media,
        Model $usable,
        string $fieldName,
    ): bool {
        return DB::transaction(
            function () use (
                $media,
                $usable,
                $fieldName,
            ): bool {
                $deleted = MediaUsage::query()
                    ->where(
                        'app_media_id',
                        $media->id
                    )
                    ->where(
                        'usable_type',
                        $usable->getMorphClass()
                    )
                    ->where(
                        'usable_id',
                        $usable->getKey()
                    )
                    ->where(
                        'field_name',
                        $fieldName
                    )
                    ->delete();

                if ($deleted <= 0) {
                    return false;
                }

                /*
                 * Recalculate instead of blindly decrementing.
                 * This prevents reference_count from becoming inaccurate.
                 */
                $this->syncReferenceCount($media);

                return true;
            }
        );
    }

    /**
     * Replace a model's existing media.
     *
     * New media is created and attached first. The old media usage
     * is removed only after the new upload succeeds.
     *
     * @param array<string, mixed> $options
     */
    public function replaceForModel(
        ?AppMedia $oldMedia,
        UploadedFile $newFile,
        MediaType $type,
        Model $usable,
        string $fieldName,
        ?string $module = null,
        string $preferredVariant = 'original',
        array $options = [],
    ): AppMedia {
        if (!$usable->exists) {
            throw new RuntimeException(
                'The related model must be saved before media can be replaced.'
            );
        }

        try {
            return DB::transaction(
                function () use (
                    $oldMedia,
                    $newFile,
                    $type,
                    $usable,
                    $fieldName,
                    $module,
                    $options,
                ): AppMedia {
                    $newMedia = $this->upload(
                        file: $newFile,
                        type: $type,
                        module: $module,
                        options: $options,
                    );

                    /*
                     * When the uploaded file is identical to the current
                     * media, keep the same record and simply ensure that
                     * its usage entry is correct.
                     */
                    if (
                        $oldMedia !== null
                        && $oldMedia->is($newMedia)
                    ) {
                        $this->attach(
                            media: $newMedia,
                            usable: $usable,
                            fieldName: $fieldName,
                            preferredVariant: 'original',
                            metadata: (array) (
                                $options['usage_metadata']
                                ?? []
                            ),
                        );

                        return $newMedia->refresh();
                    }

                    /*
                     * Remove the old usage before adding the replacement
                     * usage for the same model field.
                     */
                    if ($oldMedia !== null) {
                        $this->detach(
                            media: $oldMedia,
                            usable: $usable,
                            fieldName: $fieldName,
                        );
                    }

                    $this->attach(
                        media: $newMedia,
                        usable: $usable,
                        fieldName: $fieldName,
                        preferredVariant: 'original',
                        metadata: (array) (
                            $options['usage_metadata']
                            ?? []
                        ),
                    );

                    return $newMedia->refresh();
                }
            );
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Unable to replace media: '
                . $exception->getMessage(),
                previous: $exception,
            );
        }
    }

    /**
     * Synchronize the stored reference count with actual usages.
     */
    public function syncReferenceCount(
        AppMedia $media,
    ): AppMedia {
        $count = $media->usages()->count();

        $media->forceFill([
            'reference_count' => $count,
        ])->save();

        return $media->refresh();
    }

    /**
     * Upload a banner as one optimized WebP.
     *
     * @param array<string, mixed> $options
     */
    public function uploadBanner(
        UploadedFile $file,
        array $options = [],
    ): AppMedia {
        return $this->upload(
            file: $file,
            type: MediaType::Banner,
            module: 'banners',
            options: array_merge(
                [
                    'is_public' => true,
                ],
                $options,
            ),
        );
    }

    /**
     * Upload a vehicle image as one optimized WebP.
     *
     * @param array<string, mixed> $options
     */
    public function uploadVehicle(
        UploadedFile $file,
        array $options = [],
    ): AppMedia {
        return $this->upload(
            file: $file,
            type: MediaType::Vehicle,
            module: 'vehicles',
            options: array_merge(
                [
                    'is_public' => true,
                ],
                $options,
            ),
        );
    }

    /**
     * Upload a tour image as one optimized WebP.
     *
     * @param array<string, mixed> $options
     */
    public function uploadTour(
        UploadedFile $file,
        array $options = [],
    ): AppMedia {
        return $this->upload(
            file: $file,
            type: MediaType::Tour,
            module: 'tours',
            options: array_merge(
                [
                    'is_public' => true,
                ],
                $options,
            ),
        );
    }

    /**
     * Upload a destination image as one optimized WebP.
     *
     * @param array<string, mixed> $options
     */
    public function uploadDestination(
        UploadedFile $file,
        array $options = [],
    ): AppMedia {
        return $this->upload(
            file: $file,
            type: MediaType::Destination,
            module: 'destinations',
            options: array_merge(
                [
                    'is_public' => true,
                ],
                $options,
            ),
        );
    }

    /**
     * Upload an offer image as one optimized WebP.
     *
     * @param array<string, mixed> $options
     */
    public function uploadOffer(
        UploadedFile $file,
        array $options = [],
    ): AppMedia {
        return $this->upload(
            file: $file,
            type: MediaType::Offer,
            module: 'offers',
            options: array_merge(
                [
                    'is_public' => true,
                ],
                $options,
            ),
        );
    }

    /**
     * Upload a service image as one optimized WebP.
     *
     * @param array<string, mixed> $options
     */
    public function uploadService(
        UploadedFile $file,
        array $options = [],
    ): AppMedia {
        return $this->upload(
            file: $file,
            type: MediaType::Service,
            module: 'services',
            options: array_merge(
                [
                    'is_public' => true,
                ],
                $options,
            ),
        );
    }

    /**
     * Upload a profile image as one optimized WebP.
     *
     * @param array<string, mixed> $options
     */
    public function uploadProfile(
        UploadedFile $file,
        array $options = [],
    ): AppMedia {
        return $this->upload(
            file: $file,
            type: MediaType::Profile,
            module: 'profiles',
            options: array_merge(
                [
                    'is_public' => true,
                ],
                $options,
            ),
        );
    }

    /**
     * Upload a review image as one optimized WebP.
     *
     * @param array<string, mixed> $options
     */
    public function uploadReview(
        UploadedFile $file,
        array $options = [],
    ): AppMedia {
        return $this->upload(
            file: $file,
            type: MediaType::Review,
            module: 'reviews',
            options: array_merge(
                [
                    'is_public' => true,
                ],
                $options,
            ),
        );
    }

    /**
     * Upload an icon as one optimized WebP.
     *
     * @param array<string, mixed> $options
     */
    public function uploadIcon(
        UploadedFile $file,
        array $options = [],
    ): AppMedia {
        return $this->upload(
            file: $file,
            type: MediaType::Icon,
            module: 'icons',
            options: array_merge(
                [
                    'is_public' => true,
                ],
                $options,
            ),
        );
    }

    /**
     * Upload a document.
     *
     * PDF:
     * - saved as one PDF file.
     *
     * Image document:
     * - converted to one optimized WebP.
     *
     * Documents are private by default.
     *
     * @param array<string, mixed> $options
     */
    public function uploadDocument(
        UploadedFile $file,
        ?string $module = 'documents',
        array $options = [],
    ): AppMedia {
        return $this->upload(
            file: $file,
            type: MediaType::Document,
            module: $module,
            options: array_merge(
                [
                    'is_public' => false,
                    'disk' => 'local',
                ],
                $options,
            ),
        );
    }
}