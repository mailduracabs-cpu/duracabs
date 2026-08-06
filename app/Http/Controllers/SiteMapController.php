<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class SiteMapController extends Controller
{
    private const CACHE_KEY = 'seo.sitemap.v2.entries';

    private const CACHE_MINUTES = 30;

    /**
     * Render the public XML sitemap.
     */
    public function index(): Response
    {
        $entries = Cache::remember(
            self::CACHE_KEY,
            now()->addMinutes(self::CACHE_MINUTES),
            fn (): array => $this->buildEntries(),
        );

        return response()
            ->view('sitemap', [
                'entries' => $entries,
            ])
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('X-Robots-Tag', 'noindex, follow')
            ->header(
                'Cache-Control',
                'public, max-age=' . (self::CACHE_MINUTES * 60),
            );
    }

    /**
     * Build, normalize and deduplicate every sitemap URL.
     *
     * @return array<int, array{
     *     loc:string,
     *     lastmod:string,
     *     changefreq:string,
     *     priority:string,
     *     images:array<int, array{loc:string, title:?string, caption:?string}>
     * }>
     */
    private function buildEntries(): array
    {
        $entries = [
            ...$this->staticEntries(),
            ...$this->productEntries(),
            ...$this->pageEntries(),
        ];

        $unique = [];

        foreach ($entries as $entry) {
            $loc = $this->normalizeUrl((string) ($entry['loc'] ?? ''));

            if ($loc === '') {
                continue;
            }

            $entry['loc'] = $loc;
            $entry['lastmod'] = $this->normalizeLastModified(
                $entry['lastmod'] ?? null,
            );
            $entry['changefreq'] = $this->normalizeChangeFrequency(
                (string) ($entry['changefreq'] ?? 'weekly'),
            );
            $entry['priority'] = $this->normalizePriority(
                $entry['priority'] ?? '0.5',
            );
            $entry['images'] = $this->normalizeImages(
                is_array($entry['images'] ?? null)
                    ? $entry['images']
                    : [],
            );

            $unique[$loc] = $entry;
        }

        return array_values($unique);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function staticEntries(): array
    {
        $lastmod = $this->applicationLastModified();

        return [
            $this->entry(
                loc: url('/'),
                lastmod: $lastmod,
                changefreq: 'daily',
                priority: '1.0',
            ),
            $this->entry(
                loc: url('/about-us'),
                lastmod: $lastmod,
                changefreq: 'monthly',
                priority: '0.7',
            ),
            $this->entry(
                loc: url('/contact-us'),
                lastmod: $lastmod,
                changefreq: 'monthly',
                priority: '0.7',
            ),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function productEntries(): array
    {
        $columns = [
            'id',
            'name',
            'slug',
            'ride_type',
            'updated_at',
        ];

        foreach (['is_active', 'on_sale', 'robots_index', 'image', 'images'] as $column) {
            if (Schema::hasColumn('products', $column)) {
                $columns[] = $column;
            }
        }

        return Product::query()
            ->select(array_values(array_unique($columns)))
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->when(
                Schema::hasColumn('products', 'is_active'),
                fn (Builder $query): Builder => $query->where('is_active', true),
            )
            ->when(
                Schema::hasColumn('products', 'on_sale'),
                fn (Builder $query): Builder => $query->where('on_sale', true),
            )
            ->when(
                Schema::hasColumn('products', 'robots_index'),
                fn (Builder $query): Builder => $query->where(
                    function (Builder $robotsQuery): void {
                        $robotsQuery
                            ->whereNull('robots_index')
                            ->orWhere('robots_index', true);
                    },
                ),
            )
            ->latest('updated_at')
            ->get()
            ->map(function (Product $product): array {
                return $this->entry(
                    loc: $this->productUrl($product),
                    lastmod: $product->updated_at,
                    changefreq: $this->changeFrequencyForProduct($product),
                    priority: $this->priorityForProduct($product),
                    images: $this->productImages($product),
                );
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pageEntries(): array
    {
        $columns = [
            'id',
            'name',
            'slug',
            'content_type',
            'robots',
            'published_at',
            'updated_at',
        ];

        if (Schema::hasColumn('pages', 'image')) {
            $columns[] = 'image';
        }

        return Page::query()
            ->select($columns)
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->where(
                function (Builder $query): void {
                    $query
                        ->whereNull('robots')
                        ->orWhereRaw('LOWER(robots) NOT LIKE ?', ['%noindex%']);
                },
            )
            ->where(
                function (Builder $query): void {
                    $query
                        ->whereNull('published_at')
                        ->orWhere('published_at', '<=', now());
                },
            )
            ->latest('updated_at')
            ->get()
            ->map(function (Page $page): array {
                return $this->entry(
                    loc: $this->pageUrl($page),
                    lastmod: $page->updated_at,
                    changefreq: $this->changeFrequencyForPage($page),
                    priority: $this->priorityForPage($page),
                    images: $this->pageImages($page),
                );
            })
            ->all();
    }

    public function productUrl(Product $product): string
    {
        $url = filled($product->public_url ?? null)
            ? (string) $product->public_url
            : route('route.show', [
                'slug' => trim((string) $product->slug, '/'),
            ]);

        return $this->normalizeUrl($url);
    }

    public function pageUrl(Page $page): string
    {
        $url = filled($page->public_url ?? null)
            ? (string) $page->public_url
            : url('/pages/' . trim((string) $page->slug, '/'));

        return $this->normalizeUrl($url);
    }

    private function changeFrequencyForProduct(Product $product): string
    {
        return match ((string) $product->ride_type) {
            'self_drive', 'bike_rental' => 'daily',
            'one_way', 'return', 'round_trip', 'local', 'tour', 'airport' => 'weekly',
            default => 'weekly',
        };
    }

    private function priorityForProduct(Product $product): string
    {
        return match ((string) $product->ride_type) {
            'one_way', 'self_drive', 'bike_rental' => '0.9',
            'return', 'round_trip', 'local', 'tour', 'airport' => '0.8',
            default => '0.7',
        };
    }

    private function changeFrequencyForPage(Page $page): string
    {
        return match ((string) $page->content_type) {
            'blog' => 'weekly',
            'landing_page',
            'route_page',
            'city_page',
            'service_page',
            'tour_package' => 'weekly',
            default => 'monthly',
        };
    }

    private function priorityForPage(Page $page): string
    {
        return match ((string) $page->content_type) {
            'landing_page',
            'route_page',
            'city_page',
            'service_page' => '0.8',
            'tour_package',
            'product' => '0.7',
            'blog' => '0.6',
            default => '0.5',
        };
    }

    /**
     * @return array<int, array{loc:string, title:?string, caption:?string}>
     */
    private function productImages(Product $product): array
    {
        $images = [];

        if (filled($product->image ?? null)) {
            $images[] = [
                'loc' => $this->normalizeMediaUrl((string) $product->image),
                'title' => $this->nullableText($product->name ?? null),
                'caption' => $this->nullableText($product->name ?? null),
            ];
        }

        $gallery = $product->images ?? [];

        if (is_string($gallery)) {
            $decoded = json_decode($gallery, true);
            $gallery = is_array($decoded) ? $decoded : [];
        }

        if (is_array($gallery)) {
            foreach ($gallery as $image) {
                if (! is_string($image) || trim($image) === '') {
                    continue;
                }

                $images[] = [
                    'loc' => $this->normalizeMediaUrl($image),
                    'title' => $this->nullableText($product->name ?? null),
                    'caption' => $this->nullableText($product->name ?? null),
                ];
            }
        }

        return $this->normalizeImages($images);
    }

    /**
     * @return array<int, array{loc:string, title:?string, caption:?string}>
     */
    private function pageImages(Page $page): array
    {
        if (blank($page->resolved_image_url ?? null)) {
            return [];
        }

        return [[
            'loc' => $this->normalizeMediaUrl(
                (string) $page->resolved_image_url,
            ),
            'title' => $this->nullableText($page->name ?? null),
            'caption' => $this->nullableText($page->seo_title ?? $page->name ?? null),
        ]];
    }

    /**
     * @param array<int, array<string, mixed>> $images
     * @return array<int, array{loc:string, title:?string, caption:?string}>
     */
    private function normalizeImages(array $images): array
    {
        $normalized = [];

        foreach ($images as $image) {
            if (! is_array($image)) {
                continue;
            }

            $loc = $this->normalizeMediaUrl(
                (string) ($image['loc'] ?? ''),
            );

            if ($loc === '') {
                continue;
            }

            $normalized[$loc] = [
                'loc' => $loc,
                'title' => $this->nullableText($image['title'] ?? null),
                'caption' => $this->nullableText($image['caption'] ?? null),
            ];
        }

        return array_values($normalized);
    }

    private function normalizeMediaUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        if (Str::startsWith($url, ['http://', 'https://', '//'])) {
            return $this->normalizeUrl($url);
        }

        if (Str::startsWith($url, ['/storage/', 'storage/'])) {
            return $this->normalizeUrl(
                url('/' . ltrim($url, '/')),
            );
        }

        return $this->normalizeUrl(
            url('/storage/' . ltrim($url, '/')),
        );
    }

    private function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        if (Str::startsWith($url, '//')) {
            $url = 'https:' . $url;
        }

        if (! Str::startsWith($url, ['http://', 'https://'])) {
            $url = url('/' . ltrim($url, '/'));
        }

        $parts = parse_url($url);

        if (! is_array($parts) || blank($parts['host'] ?? null)) {
            return '';
        }

        $scheme = $this->productionScheme();
        $host = strtolower((string) $parts['host']);
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = '/' . ltrim((string) ($parts['path'] ?? '/'), '/');

        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return $scheme . '://' . $host . $port . $path;
    }

    private function productionScheme(): string
    {
        $configuredUrl = (string) config(
            'services.search_console.property',
            config('app.url'),
        );

        return Str::startsWith($configuredUrl, 'https://')
            ? 'https'
            : (parse_url($configuredUrl, PHP_URL_SCHEME) ?: 'https');
    }

    private function normalizeLastModified(mixed $value): string
    {
        try {
            return Carbon::parse($value ?: now())
                ->utc()
                ->toAtomString();
        } catch (\Throwable) {
            return now()->utc()->toAtomString();
        }
    }

    private function normalizeChangeFrequency(string $frequency): string
    {
        $allowed = [
            'always',
            'hourly',
            'daily',
            'weekly',
            'monthly',
            'yearly',
            'never',
        ];

        $frequency = strtolower(trim($frequency));

        return in_array($frequency, $allowed, true)
            ? $frequency
            : 'weekly';
    }

    private function normalizePriority(mixed $priority): string
    {
        $priority = is_numeric($priority)
            ? (float) $priority
            : 0.5;

        $priority = min(1, max(0, $priority));

        return number_format($priority, 1, '.', '');
    }

    private function applicationLastModified(): string
    {
        $candidates = [
            base_path('composer.lock'),
            resource_path('views/layouts/app.blade.php'),
            base_path('routes/web.php'),
        ];

        $timestamps = collect($candidates)
            ->filter(fn (string $path): bool => is_file($path))
            ->map(fn (string $path): int => (int) filemtime($path))
            ->filter();

        $timestamp = $timestamps->max() ?: now()->timestamp;

        return Carbon::createFromTimestamp($timestamp)
            ->utc()
            ->toAtomString();
    }

    /**
     * @return array<string, mixed>
     */
    private function entry(
        string $loc,
        mixed $lastmod,
        string $changefreq,
        string $priority,
        array $images = [],
    ): array {
        return [
            'loc' => $loc,
            'lastmod' => $lastmod,
            'changefreq' => $changefreq,
            'priority' => $priority,
            'images' => $images,
        ];
    }

    private function nullableText(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim(strip_tags((string) $value));

        return $value !== '' ? $value : null;
    }
}