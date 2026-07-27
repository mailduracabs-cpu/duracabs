<?php

namespace App\Services\Media;

use App\Models\AppMedia;
use App\Models\MediaUsage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class MediaUsageService
{
    /**
     * Attach media to a model field.
     *
     * @param array<string, mixed> $metadata
     */
    public function attach(
        AppMedia $media,
        Model $owner,
        string $fieldName,
        string $preferredVariant = 'medium',
        array $metadata = [],
    ): MediaUsage {
        $this->validateOwner($owner);

        $fieldName = $this->validateFieldName(
            $fieldName
        );

        return DB::transaction(
            function () use (
                $media,
                $owner,
                $fieldName,
                $preferredVariant,
                $metadata,
            ): MediaUsage {
                $usage = MediaUsage::query()
                    ->firstOrCreate(
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
                                $preferredVariant,

                            'metadata' => $metadata,
                        ]
                    );

                if (!$usage->wasRecentlyCreated) {
                    $usage->update([
                        'preferred_variant' =>
                            $preferredVariant,

                        'metadata' => $metadata,
                    ]);
                }

                $this->synchronizeReferenceCount(
                    $media
                );

                return $usage->refresh();
            }
        );
    }

    /**
     * Attach multiple media records to one model field.
     *
     * @param iterable<int, AppMedia> $mediaRecords
     * @return Collection<int, MediaUsage>
     */
    public function attachMany(
        iterable $mediaRecords,
        Model $owner,
        string $fieldName,
        string $preferredVariant = 'medium',
        array $metadata = [],
    ): Collection {
        $usages = collect();

        foreach ($mediaRecords as $media) {
            if (!$media instanceof AppMedia) {
                continue;
            }

            $usages->push(
                $this->attach(
                    media: $media,
                    owner: $owner,
                    fieldName: $fieldName,
                    preferredVariant:
                        $preferredVariant,
                    metadata: $metadata,
                )
            );
        }

        return $usages;
    }

    /**
     * Detach one media record from one model field.
     */
    public function detach(
        AppMedia $media,
        Model $owner,
        string $fieldName,
    ): bool {
        $this->validateOwner($owner);

        $fieldName = $this->validateFieldName(
            $fieldName
        );

        return DB::transaction(
            function () use (
                $media,
                $owner,
                $fieldName,
            ): bool {
                $deleted = MediaUsage::query()
                    ->where(
                        'app_media_id',
                        $media->id
                    )
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

                if ($deleted > 0) {
                    $this->synchronizeReferenceCount(
                        $media
                    );

                    return true;
                }

                return false;
            }
        );
    }

    /**
     * Detach all media from one model field.
     */
    public function detachField(
        Model $owner,
        string $fieldName,
    ): int {
        $this->validateOwner($owner);

        $fieldName = $this->validateFieldName(
            $fieldName
        );

        $mediaIds = MediaUsage::query()
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
            ->pluck('app_media_id')
            ->unique();

        $deleted = MediaUsage::query()
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

        AppMedia::withTrashed()
            ->whereIn('id', $mediaIds)
            ->get()
            ->each(
                fn (AppMedia $media) =>
                    $this->synchronizeReferenceCount(
                        $media
                    )
            );

        return $deleted;
    }

    /**
     * Detach all media usages from a model.
     */
    public function detachAll(
        Model $owner,
    ): int {
        $this->validateOwner($owner);

        $mediaIds = MediaUsage::query()
            ->where(
                'usable_type',
                $owner->getMorphClass()
            )
            ->where(
                'usable_id',
                $owner->getKey()
            )
            ->pluck('app_media_id')
            ->unique();

        $deleted = MediaUsage::query()
            ->where(
                'usable_type',
                $owner->getMorphClass()
            )
            ->where(
                'usable_id',
                $owner->getKey()
            )
            ->delete();

        AppMedia::withTrashed()
            ->whereIn('id', $mediaIds)
            ->get()
            ->each(
                fn (AppMedia $media) =>
                    $this->synchronizeReferenceCount(
                        $media
                    )
            );

        return $deleted;
    }

    /**
     * Get media used by a model.
     *
     * @return Collection<int, AppMedia>
     */
    public function mediaFor(
        Model $owner,
        ?string $fieldName = null,
    ): Collection {
        $this->validateOwner($owner);

        $query = MediaUsage::query()
            ->with('media')
            ->where(
                'usable_type',
                $owner->getMorphClass()
            )
            ->where(
                'usable_id',
                $owner->getKey()
            );

        if (filled($fieldName)) {
            $query->where(
                'field_name',
                trim($fieldName)
            );
        }

        return $query
            ->get()
            ->pluck('media')
            ->filter(
                fn ($media): bool =>
                    $media instanceof AppMedia
            )
            ->values();
    }

    /**
     * Get all usage records for media.
     *
     * @return Collection<int, MediaUsage>
     */
    public function usagesFor(
        AppMedia $media,
    ): Collection {
        return MediaUsage::query()
            ->where(
                'app_media_id',
                $media->id
            )
            ->get();
    }

    /**
     * Check whether media is attached to a model field.
     */
    public function isAttached(
        AppMedia $media,
        Model $owner,
        string $fieldName,
    ): bool {
        return MediaUsage::query()
            ->where(
                'app_media_id',
                $media->id
            )
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
                trim($fieldName)
            )
            ->exists();
    }

    /**
     * Synchronize reference count.
     */
    public function synchronizeReferenceCount(
        AppMedia $media,
    ): int {
        $count = MediaUsage::query()
            ->where(
                'app_media_id',
                $media->id
            )
            ->count();

        AppMedia::withTrashed()
            ->whereKey($media->id)
            ->update([
                'reference_count' => $count,
            ]);

        $media->reference_count = $count;

        return $count;
    }

    private function validateOwner(
        Model $owner,
    ): void {
        if (!$owner->exists) {
            throw new RuntimeException(
                'The media owner must be saved before attaching media.'
            );
        }
    }

    private function validateFieldName(
        string $fieldName,
    ): string {
        $fieldName = trim($fieldName);

        if ($fieldName === '') {
            throw new RuntimeException(
                'A valid media field name is required.'
            );
        }

        return $fieldName;
    }
}