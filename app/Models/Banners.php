<?php

namespace App\Models;

use App\Support\DuraImage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Banners extends Model
{
    use HasFactory;

    protected $table = 'banners';

    protected $fillable = [
        'name',
        'url',
        'image',
        'app_media_id',
        'ride_type',
        'alt',
        'title',
        'is_active',
        'redirect_type',
        'redirect_value',
        'redirect_id',
        'coupon_code',
        'priority',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'app_media_id' => 'integer',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'redirect_id' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Media Library relation.
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(
            AppMedia::class,
            'app_media_id'
        );
    }

    /**
     * Return the preferred optimized banner URL.
     *
     * Priority:
     * 1. Medium Media Engine variant
     * 2. Large Media Engine variant
     * 3. Original Media Engine file
     * 4. Legacy image column
     */
    public function getImageUrlAttribute(): ?string
    {
        $media = $this->relationLoaded('media')
            ? $this->getRelation('media')
            : $this->media;

        if ($media instanceof AppMedia) {
            return $media->medium_url
                ?: $media->large_url
                ?: $media->original_url;
        }

        if (blank($this->image)) {
            return null;
        }

        return DuraImage::url(
            $this->image
        );
    }

    /**
     * Alias useful for website and API responses.
     */
    public function getBannerImageAttribute(): ?string
    {
        return $this->image_url;
    }

    /**
     * Return thumbnail where a compact image is needed.
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        $media = $this->relationLoaded('media')
            ? $this->getRelation('media')
            : $this->media;

        if ($media instanceof AppMedia) {
            return $media->thumbnail_url
                ?: $media->medium_url
                ?: $media->original_url;
        }

        return $this->image_url;
    }

    /**
     * Check whether this banner uses the new Media Library.
     */
    public function usesMediaLibrary(): bool
    {
        return filled($this->app_media_id);
    }

    /**
     * Check whether this banner still uses the legacy image field.
     */
    public function usesLegacyImage(): bool
    {
        return blank($this->app_media_id)
            && filled($this->image);
    }

    /**
     * Active banners only.
     */
    public function scopeActive(
        Builder $query
    ): Builder {
        return $query->where(
            'is_active',
            true
        );
    }

    /**
     * Filter banners by ride type.
     */
    public function scopeForRideType(
        Builder $query,
        string $rideType
    ): Builder {
        return $query->where(
            'ride_type',
            $rideType
        );
    }

    /**
     * Banners that are currently inside their display period.
     */
    public function scopeCurrentlyVisible(
        Builder $query
    ): Builder {
        return $query
            ->where(function (Builder $builder): void {
                $builder
                    ->whereNull('start_date')
                    ->orWhereDate(
                        'start_date',
                        '<=',
                        today()
                    );
            })
            ->where(function (Builder $builder): void {
                $builder
                    ->whereNull('end_date')
                    ->orWhereDate(
                        'end_date',
                        '>=',
                        today()
                    );
            });
    }

    /**
     * Active and currently visible banners ordered by priority.
     */
    public function scopePublished(
        Builder $query
    ): Builder {
        return $query
            ->active()
            ->currentlyVisible()
            ->orderByDesc('priority')
            ->orderByDesc('id');
    }
}