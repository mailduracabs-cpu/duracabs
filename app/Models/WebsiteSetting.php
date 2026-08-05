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