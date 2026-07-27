<?php

namespace App\Support;

use App\Models\WebsiteSetting;
use Illuminate\Support\Facades\Cache;

final class SiteCache
{
    public const SETTINGS_KEY = 'site.website_settings.current';

    public static function settings(): WebsiteSetting
    {
        return Cache::remember(
            self::SETTINGS_KEY,
            now()->addMinutes((int) config('site-cache.settings_ttl_minutes', 60)),
            static fn (): WebsiteSetting => WebsiteSetting::current()
        );
    }

    public static function forgetSettings(): void
    {
        Cache::forget(self::SETTINGS_KEY);
    }

    public static function flush(): void
    {
        self::forgetSettings();
    }
}
