<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class WebsiteSetting extends Model
{
    protected $fillable = [
        'site_name',
        'tagline',
        'logo',
        'favicon',

        'default_meta_title',
        'default_meta_description',
        'default_meta_keywords',
        'default_og_image',
        'robots',
        'twitter_username',

        'business_name',
        'business_type',
        'business_description',
        'phone',
        'alternate_phone',
        'email',

        'street_address',
        'city',
        'state',
        'postal_code',
        'country_code',

        'latitude',
        'longitude',
        'google_map_url',
        'price_range',

        'open_24_hours',
        'opening_time',
        'closing_time',

        'facebook_url',
        'instagram_url',
        'linkedin_url',
        'twitter_url',
        'youtube_url',
        'pinterest_url',

        'rating_value',
        'review_count',
        'best_rating',

        'google_tag_manager_id',
        'google_analytics_id',
        'google_ads_id',

        'google_site_verification',
        'google_site_verification_secondary',
        'bing_site_verification',
        'yandex_verification',
        'pinterest_domain_verification',

        // Global custom code rendered on every public page
        'header_code',
        'footer_code',

        'whatsapp_enabled',
        'whatsapp_default_country_code',
        'whatsapp_default_language',
        'whatsapp_test_number',
        'whatsapp_admin_numbers',
        'whatsapp_sales_numbers',
        'whatsapp_operations_numbers',
        'whatsapp_accounts_numbers',
        'whatsapp_support_numbers',

        'is_active',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',

        'rating_value' => 'decimal:2',
        'best_rating' => 'decimal:2',
        'review_count' => 'integer',

        'open_24_hours' => 'boolean',

        'whatsapp_enabled' => 'boolean',
        'whatsapp_admin_numbers' => 'array',
        'whatsapp_sales_numbers' => 'array',
        'whatsapp_operations_numbers' => 'array',
        'whatsapp_accounts_numbers' => 'array',
        'whatsapp_support_numbers' => 'array',

        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function (): void {
            Cache::forget('website_settings');
        });

        static::deleted(function (): void {
            Cache::forget('website_settings');
        });
    }

    /**
     * Return the active global website settings.
     */
    public static function current(): self
    {
        return Cache::rememberForever(
            'website_settings',
            function (): self {
                return static::query()
                    ->where('is_active', true)
                    ->first()
                    ?? static::query()->first()
                    ?? new static([
                        'site_name' => config('app.name', 'Dura Cabs'),
                        'business_name' => 'Dura Cabs Services',
                        'country_code' => 'IN',
                        'robots' => 'index, follow',
                        'business_type' => 'TaxiService',
                        'open_24_hours' => true,
                        'best_rating' => 5,
                        'whatsapp_enabled' => true,
                        'whatsapp_default_country_code' => '91',
                        'whatsapp_default_language' => 'en',
                        'is_active' => true,
                    ]);
            }
        );
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->logo);
    }

    public function getFaviconUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->favicon);
    }

    public function getDefaultOgImageUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->default_og_image);
    }

    /**
     * Return only official social profile URLs.
     */
    public function socialProfiles(): array
    {
        return collect([
            $this->facebook_url,
            $this->instagram_url,
            $this->linkedin_url,
            $this->twitter_url,
            $this->youtube_url,
            $this->pinterest_url,
        ])
            ->filter(fn ($url): bool => filled($url))
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function whatsappNumbersForGroup(
        string $group
    ): array {
        $field = match ($group) {
            'admin' => 'whatsapp_admin_numbers',
            'sales' => 'whatsapp_sales_numbers',
            'operations' => 'whatsapp_operations_numbers',
            'accounts' => 'whatsapp_accounts_numbers',
            'support' => 'whatsapp_support_numbers',
            default => null,
        };

        if ($field === null) {
            return [];
        }

        $numbers = $this->{$field};

        return collect(is_array($numbers) ? $numbers : [])
            ->map(function (mixed $item): string {
                $number = is_array($item)
                    ? (string) ($item['number'] ?? '')
                    : (string) $item;

                return preg_replace('/\D+/', '', $number) ?? '';
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }


    /**
     * Build the global AggregateRating schema from Website Settings.
     */
    public function aggregateRatingSchema(): ?array
    {
        if (! filled($this->rating_value) || (int) $this->review_count <= 0) {
            return null;
        }

        return [
            '@type' => 'AggregateRating',
            'ratingValue' => (float) $this->rating_value,
            'reviewCount' => (int) $this->review_count,
            'bestRating' => filled($this->best_rating)
                ? (float) $this->best_rating
                : 5,
            'worstRating' => 1,
        ];
    }

    /**
     * Build PostalAddress schema from Website Settings.
     */
    public function postalAddressSchema(): ?array
    {
        $schema = array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => filled($this->street_address) ? $this->street_address : null,
            'addressLocality' => filled($this->city) ? $this->city : null,
            'addressRegion' => filled($this->state) ? $this->state : null,
            'postalCode' => filled($this->postal_code) ? $this->postal_code : null,
            'addressCountry' => filled($this->country_code)
                ? strtoupper((string) $this->country_code)
                : null,
        ], static fn (mixed $value): bool =>
            $value !== null && $value !== '' && $value !== []
        );

        return count($schema) > 1 ? $schema : null;
    }

    /**
     * Build GeoCoordinates schema from Website Settings.
     */
    public function geoCoordinatesSchema(): ?array
    {
        if (! filled($this->latitude) || ! filled($this->longitude)) {
            return null;
        }

        return [
            '@type' => 'GeoCoordinates',
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
        ];
    }

    /**
     * Build opening-hours schema from Website Settings.
     */
    public function openingHoursSchema(): ?array
    {
        $days = [
            'Monday', 'Tuesday', 'Wednesday', 'Thursday',
            'Friday', 'Saturday', 'Sunday',
        ];

        if ((bool) $this->open_24_hours) {
            return [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => $days,
                'opens' => '00:00',
                'closes' => '23:59',
            ];
        }

        if (! filled($this->opening_time) || ! filled($this->closing_time)) {
            return null;
        }

        return [
            '@type' => 'OpeningHoursSpecification',
            'dayOfWeek' => $days,
            'opens' => substr((string) $this->opening_time, 0, 5),
            'closes' => substr((string) $this->closing_time, 0, 5),
        ];
    }

    /**
     * Build ContactPoint schema entries from Website Settings.
     *
     * @return array<int, array<string, mixed>>
     */
    public function contactPointSchemas(): array
    {
        $country = filled($this->country_code)
            ? strtoupper((string) $this->country_code)
            : 'IN';

        return collect([
            filled($this->phone) ? [
                '@type' => 'ContactPoint',
                'telephone' => $this->phone,
                'contactType' => 'customer service',
                'areaServed' => $country,
                'availableLanguage' => ['English', 'Hindi'],
            ] : null,
            filled($this->alternate_phone) ? [
                '@type' => 'ContactPoint',
                'telephone' => $this->alternate_phone,
                'contactType' => 'customer service',
                'areaServed' => $country,
                'availableLanguage' => ['English', 'Hindi'],
            ] : null,
        ])->filter()->values()->all();
    }

    /**
     * Return a safe Schema.org business type.
     */
    public function schemaBusinessType(): string
    {
        $allowed = [
            'TaxiService',
            'LocalBusiness',
            'TravelAgency',
            'AutomotiveBusiness',
            'Organization',
        ];

        return in_array($this->business_type, $allowed, true)
            ? $this->business_type
            : 'Organization';
    }

    /**
     * Build the global Organization / TaxiService schema.
     */
    public function organizationSchema(string $homeUrl, string $organizationId): array
    {
        $homeUrl = rtrim($homeUrl, '/') . '/';
        $contactPoints = $this->contactPointSchemas();
        $socialProfiles = $this->socialProfiles();

        return array_filter([
            '@type' => $this->schemaBusinessType(),
            '@id' => $organizationId,
            'name' => $this->business_name ?: $this->site_name ?: config('app.name', 'Dura Cabs'),
            'url' => $homeUrl,
            'description' => filled($this->business_description) ? $this->business_description : null,
            'logo' => filled($this->logo_url) ? [
                '@type' => 'ImageObject',
                'url' => $this->logo_url,
            ] : null,
            'image' => filled($this->default_og_image_url)
                ? $this->default_og_image_url
                : $this->logo_url,
            'telephone' => filled($this->phone) ? $this->phone : null,
            'email' => filled($this->email) ? $this->email : null,
            'priceRange' => filled($this->price_range) ? $this->price_range : null,
            'address' => $this->postalAddressSchema(),
            'geo' => $this->geoCoordinatesSchema(),
            'hasMap' => filled($this->google_map_url) ? $this->google_map_url : null,
            'openingHoursSpecification' => $this->openingHoursSchema(),
            'contactPoint' => $contactPoints !== [] ? $contactPoints : null,
            'sameAs' => $socialProfiles !== [] ? $socialProfiles : null,
            'areaServed' => [
                '@type' => 'Country',
                'name' => strtoupper((string) $this->country_code) === 'IN'
                    ? 'India'
                    : strtoupper((string) $this->country_code),
            ],
            
        ], static fn (mixed $value): bool =>
            $value !== null && $value !== '' && $value !== []
        );
    }

    /**
     * Build the global WebSite schema.
     */
    public function websiteSchema(
        string $homeUrl,
        string $websiteId,
        string $organizationId
    ): array {
        $homeUrl = rtrim($homeUrl, '/') . '/';

        return array_filter([
            '@type' => 'WebSite',
            '@id' => $websiteId,
            'url' => $homeUrl,
            'name' => $this->site_name ?: config('app.name', 'Dura Cabs'),
            'description' => filled($this->default_meta_description)
                ? $this->default_meta_description
                : $this->business_description,
            'publisher' => ['@id' => $organizationId],
            'inLanguage' => str_replace('_', '-', app()->getLocale()),
        ], static fn (mixed $value): bool =>
            $value !== null && $value !== '' && $value !== []
        );
    }

    private function mediaUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (
            str_starts_with($path, 'http://')
            || str_starts_with($path, 'https://')
            || str_starts_with($path, '//')
        ) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}