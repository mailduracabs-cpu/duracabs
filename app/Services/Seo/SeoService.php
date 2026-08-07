<?php

namespace App\Services\Seo;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use SimpleXMLElement;

class SeoService
{
    private const CACHE_KEY = 'dura_orphan_page_url_index_v1';

    private const CACHE_SECONDS = 3600;

    private const MAX_URLS = 50000;

    private const DEFAULT_LINKS = 16;

    /**
     * Return internal links for the current page.
     *
     * Purpose:
     * - Keep sitemap pages connected with normal HTML <a href> links.
     * - Give every discovered page incoming links from other pages.
     * - Never change existing URLs/slugs.
     * - No database table required.
     */
    public function links(?string $currentUrl = null, int $limit = self::DEFAULT_LINKS): array
    {
        $currentUrl = $this->normalizeUrl($currentUrl ?: url()->current());

        if ($currentUrl === null) {
            return [];
        }

        $limit = max(6, min($limit, 30));

        $allUrls = collect($this->urlIndex())
            ->filter()
            ->unique()
            ->sort()
            ->values();

        if ($allUrls->count() < 2) {
            return [];
        }

        // If the current page is indexable/public but the sitemap cache has not
        // picked it up yet, include it for this request only.
        if (! $allUrls->contains($currentUrl)) {
            $allUrls->push($currentUrl);
            $allUrls = $allUrls->unique()->sort()->values();
        }

        $currentPath = $this->pathFromUrl($currentUrl);
        $currentModule = $this->moduleFromPath($currentPath);

        /*
         * 1) Guaranteed network links.
         *
         * Sorted URLs form a circular network. Every URL links to the next
         * several URLs, which means every discovered URL also receives incoming
         * links from previous URLs. This is the orphan-page safety net.
         */
        $networkLinks = $this->circularLinks(
            urls: $allUrls,
            currentUrl: $currentUrl,
            count: min(8, $limit)
        );

        /*
         * 2) Prefer some same-module URLs for cleaner user experience.
         * These do not replace the circular safety links.
         */
        $relatedLinks = $allUrls
            ->reject(fn (string $url) => $url === $currentUrl)
            ->filter(function (string $url) use ($currentModule): bool {
                return $this->moduleFromPath($this->pathFromUrl($url)) === $currentModule;
            })
            ->take(max(0, $limit - count($networkLinks)))
            ->map(fn (string $url) => $this->makeLink($url))
            ->all();

        return collect(array_merge($networkLinks, $relatedLinks))
            ->unique('url')
            ->reject(fn (array $item) => $item['url'] === $currentUrl)
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * Clear cached sitemap URL index.
     *
     * Run "php artisan optimize:clear" after sitemap changes if you want the
     * new URLs to appear immediately instead of waiting for cache expiry.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Sitemap is the source of truth for dynamic public pages.
     */
    public function urlIndex(): array
    {
        return Cache::remember(
            self::CACHE_KEY,
            self::CACHE_SECONDS,
            function (): array {
                $urls = $this->urlsFromLocalSitemaps();

                return collect($urls)
                    ->map(fn (string $url) => $this->normalizeUrl($url))
                    ->filter()
                    ->filter(fn (string $url) => $this->isPublicHtmlUrl($url))
                    ->unique()
                    ->sort()
                    ->values()
                    ->take(self::MAX_URLS)
                    ->all();
            }
        );
    }

    /**
     * Read the standard sitemap files from /public.
     * Sitemap indexes are followed recursively when their child XML files
     * also exist under /public.
     */
    private function urlsFromLocalSitemaps(): array
    {
        $rootFiles = collect([
            public_path('sitemap.xml'),
            public_path('sitemap_index.xml'),
            public_path('sitemap-index.xml'),
        ])
            ->filter(fn (string $file) => is_file($file))
            ->values()
            ->all();

        if ($rootFiles === []) {
            return [];
        }

        $urls = [];
        $visited = [];

        foreach ($rootFiles as $file) {
            $this->readSitemapFile($file, $urls, $visited);

            if (count($urls) >= self::MAX_URLS) {
                break;
            }
        }

        return array_slice(
            array_values(array_unique($urls)),
            0,
            self::MAX_URLS
        );
    }

    private function readSitemapFile(string $file, array &$urls, array &$visited): void
    {
        $realPath = realpath($file);

        if (
            $realPath === false
            || isset($visited[$realPath])
            || count($urls) >= self::MAX_URLS
        ) {
            return;
        }

        $visited[$realPath] = true;

        $xmlString = @file_get_contents($realPath);

        if (! is_string($xmlString) || trim($xmlString) === '') {
            return;
        }

        libxml_use_internal_errors(true);

        try {
            $xml = new SimpleXMLElement($xmlString);
        } catch (\Throwable) {
            libxml_clear_errors();

            return;
        }

        libxml_clear_errors();

        if ($xml->getName() === 'urlset') {
            foreach ($xml->url as $entry) {
                $loc = trim((string) $entry->loc);

                if ($loc !== '') {
                    $urls[] = $loc;
                }

                if (count($urls) >= self::MAX_URLS) {
                    break;
                }
            }

            return;
        }

        if ($xml->getName() !== 'sitemapindex') {
            return;
        }

        foreach ($xml->sitemap as $entry) {
            $loc = trim((string) $entry->loc);

            if ($loc === '') {
                continue;
            }

            $childPath = $this->localSitemapPath($loc);

            if ($childPath !== null) {
                $this->readSitemapFile($childPath, $urls, $visited);
            }

            if (count($urls) >= self::MAX_URLS) {
                break;
            }
        }
    }

    private function localSitemapPath(string $sitemapUrl): ?string
    {
        $path = parse_url($sitemapUrl, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return null;
        }

        $candidate = public_path(ltrim($path, '/'));

        return is_file($candidate) ? $candidate : null;
    }

    /**
     * Circular linking guarantees incoming links for every URL in the index.
     */
    private function circularLinks(
        Collection $urls,
        string $currentUrl,
        int $count
    ): array {
        $index = $urls->search($currentUrl, true);

        if ($index === false || $urls->count() <= 1) {
            return [];
        }

        $links = [];
        $total = $urls->count();

        for ($offset = 1; $offset <= $count; $offset++) {
            $candidate = $urls[($index + $offset) % $total];

            if ($candidate === $currentUrl) {
                continue;
            }

            $links[] = $this->makeLink($candidate);
        }

        return $links;
    }

    private function makeLink(string $url): array
    {
        return [
            'url' => $url,
            'label' => $this->labelFromUrl($url),
            'module' => $this->moduleFromPath($this->pathFromUrl($url)),
        ];
    }

    private function labelFromUrl(string $url): string
    {
        $path = trim($this->pathFromUrl($url), '/');

        if ($path === '') {
            return config('app.name', 'Dura Cabs');
        }

        $slug = basename($path);
        $slug = preg_replace('/[-_]+/', ' ', $slug) ?: $slug;
        $slug = preg_replace('/\s+/', ' ', $slug) ?: $slug;

        $label = Str::title(trim($slug));

        return trim(str_replace(
            [' Cng ', ' Suv ', ' Muv ', ' Ev '],
            [' CNG ', ' SUV ', ' MUV ', ' EV '],
            ' ' . $label . ' '
        ));
    }

    private function moduleFromPath(string $path): string
    {
        $path = strtolower(trim($path, '/'));

        return match (true) {
            Str::startsWith($path, ['route/', 'routes/']) => 'route',
            Str::contains($path, ['self-drive', 'selfdrive', 'self_drive']) => 'self-drive',
            Str::startsWith($path, ['tour/', 'tours/']) => 'tour',
            Str::startsWith($path, ['blog/', 'blogs/']) => 'blog',
            Str::startsWith($path, ['product/', 'products/']) => 'product',
            Str::startsWith($path, ['city/', 'cities/']) => 'city',
            default => 'page',
        };
    }

    private function normalizeUrl(?string $url): ?string
    {
        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        $url = trim($url);

        if (Str::startsWith($url, '/')) {
            $url = url($url);
        }

        $parts = parse_url($url);

        if (! is_array($parts)) {
            return null;
        }

        $appUrl = (string) config('app.url', url('/'));
        $appHost = strtolower((string) parse_url($appUrl, PHP_URL_HOST));
        $host = strtolower((string) ($parts['host'] ?? $appHost));

        if (
            $appHost !== ''
            && $host !== ''
            && ltrim($host, 'www.') !== ltrim($appHost, 'www.')
        ) {
            return null;
        }

        $scheme = strtolower(
            (string) (
                $parts['scheme']
                ?? parse_url($appUrl, PHP_URL_SCHEME)
                ?? 'https'
            )
        );

        $path = '/' . ltrim((string) ($parts['path'] ?? '/'), '/');

        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return $scheme . '://' . $host . $path;
    }

    private function pathFromUrl(string $url): string
    {
        return trim(
            (string) parse_url($url, PHP_URL_PATH),
            '/'
        );
    }

    private function isPublicHtmlUrl(string $url): bool
    {
        $path = strtolower($this->pathFromUrl($url));

        if ($this->shouldIgnorePath($path)) {
            return false;
        }

        $extension = strtolower(
            pathinfo(
                (string) parse_url($url, PHP_URL_PATH),
                PATHINFO_EXTENSION
            )
        );

        return ! in_array($extension, [
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico',
            'css', 'js', 'json', 'xml', 'pdf', 'zip', 'rar',
            'mp4', 'webm', 'mp3', 'wav', 'woff', 'woff2', 'ttf',
        ], true);
    }

    private function shouldIgnorePath(string $path): bool
    {
        $path = strtolower(trim($path, '/'));

        if ($path === '') {
            return false;
        }

        return Str::startsWith($path, [
            'admin',
            'partner',
            'api',
            'login',
            'logout',
            'register',
            'password',
            'forgot-password',
            'reset-password',
            'email/verify',
            'livewire',
            '_debugbar',
            'telescope',
            'horizon',
            'storage',
            'build',
            'vendor',
            'sanctum',
        ]);
    }
}
