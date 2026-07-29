<?php

namespace App\Models;

use App\Support\DuraImage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    use HasFactory;

    public const SERVICE_WITH_DRIVER = 'with_driver';
    public const SERVICE_WITHOUT_DRIVER = 'without_driver';

    public const VEHICLE_CAR = 'car';
    public const VEHICLE_BIKE = 'bike';
    public const VEHICLE_TEMPO = 'tempo';

    public const CONTENT_TYPE_SEO_PAGE = 'seo_page';
    public const CONTENT_TYPE_STATIC_PAGE = 'static_page';
    public const CONTENT_TYPE_BLOG = 'blog';
    public const CONTENT_TYPE_PRODUCT = 'product';

    public const URL_TYPE_ROUTE = 'route';
    public const URL_TYPE_PAGE = 'page';
    public const URL_TYPE_ROOT = 'root';
	
	
	

    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'slug',

        // Content Writer classification
        'content_type',
        'url_type',

        'images',
        'description',

        // Reusable Content Writer blocks
        'content_links',
        'fare_cards',

        'price',
        'max_price',

        'is_active',
        'is_featured',
        'in_stock',
        'on_sale',

        'service_group',
        'vehicle_type',

        'ride_type',
        'booking_to',
        'km_limit',
        'hr_limit',
        'extra_km_charge',
        'extra_hr_charge',
        'toll_tax',
        'border_tax',
        'driver_allowances',
        'plan',

        'meta_title',
        'meta_description',

        // SEO fields
        'focus_keyword',
        'canonical_url',
        'robots_index',
        'robots_follow',
        'seo_score',
        'readability_score',
        'seo_analysis',

        // Open Graph
        'og_title',
        'og_description',
        'og_image',

        // Twitter
        'twitter_title',
        'twitter_description',
        'twitter_image',
    ];

    protected $casts = [
        'images' => 'array',
        'content_links' => 'array',
        'fare_cards' => 'array',
        'seo_analysis' => 'array',

        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'in_stock' => 'boolean',
        'on_sale' => 'boolean',
        'robots_index' => 'boolean',
        'robots_follow' => 'boolean',

        'seo_score' => 'integer',
        'readability_score' => 'integer',

        'price' => 'decimal:2',
        'max_price' => 'decimal:2',
        'km_limit' => 'decimal:2',
        'hr_limit' => 'decimal:2',
        'extra_km_charge' => 'decimal:2',
        'extra_hr_charge' => 'decimal:2',
        'toll_tax' => 'decimal:2',
        'border_tax' => 'decimal:2',
        'driver_allowances' => 'decimal:2',
    ];

    protected $attributes = [
        'content_type' => self::CONTENT_TYPE_SEO_PAGE,
        'url_type' => self::URL_TYPE_ROUTE,
        'is_active' => true,
        'robots_index' => true,
        'robots_follow' => true,
    ];

    protected $appends = [
        'primary_image',
        'image_url',
        'public_path',
    ];

    public function getPrimaryImageAttribute(): ?string
    {
        return DuraImage::first($this->images);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->primary_image;
    }
	
	public function vehicle(): BelongsTo
{
    return $this->belongsTo(Vehicle::class);
}

    /**
     * Returns only the public URL path.
     * It never changes or regenerates the saved slug.
     */
    public function getPublicPathAttribute(): string
    {
        return match ($this->url_type) {
            self::URL_TYPE_PAGE => '/pages/' . $this->slug,
            self::URL_TYPE_ROOT => '/' . $this->slug,
            default => '/route/' . $this->slug,
        };
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Existing database relation for structured product links.
     * Do not rename this relation to keep old code compatible.
     */
    public function links(): HasMany
    {
        return $this->hasMany(Links::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(Price::class);
    }

    public function selfDrivePrice(): HasOne
    {
        return $this->hasOne(SelfDrivePrice::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeWithDriver($query)
    {
        return $query->where('service_group', self::SERVICE_WITH_DRIVER);
    }

    public function scopeWithoutDriver($query)
    {
        return $query->where('service_group', self::SERVICE_WITHOUT_DRIVER);
    }

    public function scopeSelfDrive($query)
    {
        return $query->where('service_group', self::SERVICE_WITHOUT_DRIVER);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeContentType($query, string $contentType)
    {
        return $query->where('content_type', $contentType);
    }

    public function scopeUrlType($query, string $urlType)
    {
        return $query->where('url_type', $urlType);
    }

    public function isSelfDrive(): bool
    {
        return $this->service_group === self::SERVICE_WITHOUT_DRIVER;
    }

    public function isWithDriver(): bool
    {
        return $this->service_group === self::SERVICE_WITH_DRIVER;
    }

    public function isSeoPage(): bool
    {
        return $this->content_type === self::CONTENT_TYPE_SEO_PAGE;
    }

    public function isStaticPage(): bool
    {
        return $this->content_type === self::CONTENT_TYPE_STATIC_PAGE;
    }

    public function isBlog(): bool
    {
        return $this->content_type === self::CONTENT_TYPE_BLOG;
    }
}