<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SiteMapController extends Controller
{
    public function index(): Response
    {
        $data = Cache::remember(
            key: 'seo.sitemap.xml.data',
            ttl: now()->addMinutes(30),
            callback: fn (): array => [
                'staticUrls' => $this->staticUrls(),
                'routes' => $this->routePages(),
                'pages' => $this->contentPages(),
            ],
        );

        return response()
            ->view('sitemap', $data)
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('X-Robots-Tag', 'noindex, follow')
            ->header('Cache-Control', 'public, max-age=1800');
    }

    /**
     * @return array<int, array{
     *     loc: string,
     *     lastmod: string,
     *     changefreq: string,
     *     priority: string
     * }>
     */
    private function staticUrls(): array
    {
        return [
            $this->urlEntry(
                loc: url('/'),
                lastmod: now()->utc()->toAtomString(),
                changefreq: 'daily',
                priority: '1.0',
            ),
            $this->urlEntry(
                loc: url('/about-us'),
                lastmod: now()->utc()->toAtomString(),
                changefreq: 'monthly',
                priority: '0.7',
            ),
            $this->urlEntry(
                loc: url('/contact-us'),
                lastmod: now()->utc()->toAtomString(),
                changefreq: 'monthly',
                priority: '0.7',
            ),
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, Product>
     */
    private function routePages()
    {
        return Product::query()
            ->select([
                'id',
                'name',
                'slug',
                'ride_type',
                'is_active',
                'on_sale',
                'updated_at',
            ])
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->when(
                Schema::hasColumn('products', 'is_active'),
                fn (Builder $query): Builder =>
                    $query->where('is_active', true),
            )
            ->when(
                Schema::hasColumn('products', 'on_sale'),
                fn (Builder $query): Builder =>
                    $query->where('on_sale', true),
            )
            ->when(
                Schema::hasColumn('products', 'robots_index'),
                fn (Builder $query): Builder =>
                    $query->where(function (Builder $robotsQuery): void {
                        $robotsQuery
                            ->whereNull('robots_index')
                            ->orWhere('robots_index', true);
                    }),
            )
            ->latest('updated_at')
            ->get()
            ->unique('slug')
            ->values();
    }

    /**
     * @return \Illuminate\Support\Collection<int, Page>
     */
    private function contentPages()
    {
        return Page::query()
            ->select([
                'id',
                'name',
                'slug',
                'content_type',
                'robots',
                'published_at',
                'updated_at',
            ])
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('robots')
                    ->orWhere('robots', 'not like', '%noindex%');
            })
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->latest('updated_at')
            ->get()
            ->unique('slug')
            ->values();
    }

    public static function productUrl(Product $product): string
    {
        return $product->public_url;
    }

    public static function pageUrl(Page $page): string
    {
        return $page->public_url;
    }

    public static function changeFrequencyForProduct(
        Product $product
    ): string {
        return match ((string) $product->ride_type) {
            'self_drive',
            'bike_rental' => 'daily',
            'one_way',
            'return',
            'local',
            'tour' => 'weekly',
            default => 'weekly',
        };
    }

    public static function priorityForProduct(
        Product $product
    ): string {
        return match ((string) $product->ride_type) {
            'one_way',
            'self_drive',
            'bike_rental' => '0.9',
            'return',
            'local',
            'tour' => '0.8',
            default => '0.7',
        };
    }

    public static function changeFrequencyForPage(Page $page): string
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

    public static function priorityForPage(Page $page): string
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
     * @return array{
     *     loc: string,
     *     lastmod: string,
     *     changefreq: string,
     *     priority: string
     * }
     */
    private function urlEntry(
        string $loc,
        string $lastmod,
        string $changefreq,
        string $priority,
    ): array {
        return [
            'loc' => $loc,
            'lastmod' => $lastmod,
            'changefreq' => $changefreq,
            'priority' => $priority,
        ];
    }
}