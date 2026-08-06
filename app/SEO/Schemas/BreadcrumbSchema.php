<?php

declare(strict_types=1);

namespace App\SEO\Schemas;

final class BreadcrumbSchema
{
    /**
     * Build a BreadcrumbList schema.
     *
     * Expected item format:
     *
     * [
     *     ['name' => 'Home', 'url' => url('/')],
     *     ['name' => 'Cab Routes', 'url' => url('/routes')],
     *     ['name' => 'Agra to Delhi Taxi', 'url' => $canonicalUrl],
     * ]
     *
     * @param array<int, array<string, mixed>> $items
     *
     * @return array<string, mixed>
     */
    public function build(
        string $pageUrl,
        array $items,
        ?string $breadcrumbId = null
    ): array {
        $pageUrl = $this->normaliseUrl($pageUrl);
        $breadcrumbId ??= $pageUrl . '#breadcrumb';

        $itemListElement = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = trim((string) ($item['name'] ?? ''));
            $url = trim((string) ($item['url'] ?? $item['item'] ?? ''));

            if ($name === '' || $url === '') {
                continue;
            }

            $itemListElement[] = [
                '@type' => 'ListItem',
                'position' => count($itemListElement) + 1,
                'name' => $name,
                'item' => $this->normaliseUrl($url),
            ];
        }

        if ($itemListElement === []) {
            return [];
        }

        return [
            '@type' => 'BreadcrumbList',
            '@id' => $breadcrumbId,
            'itemListElement' => $itemListElement,
        ];
    }

    /**
     * Build breadcrumb schema from a simple name => URL map.
     *
     * Example:
     *
     * [
     *     'Home' => url('/'),
     *     'Cab Routes' => url('/routes'),
     *     'Agra to Delhi Taxi' => $canonicalUrl,
     * ]
     *
     * @param array<string, string> $items
     *
     * @return array<string, mixed>
     */
    public function fromMap(
        string $pageUrl,
        array $items,
        ?string $breadcrumbId = null
    ): array {
        $normalised = [];

        foreach ($items as $name => $url) {
            $normalised[] = [
                'name' => $name,
                'url' => $url,
            ];
        }

        return $this->build(
            pageUrl: $pageUrl,
            items: $normalised,
            breadcrumbId: $breadcrumbId
        );
    }

    /**
     * Build the standard homepage-only breadcrumb.
     *
     * @return array<string, mixed>
     */
    public function home(
        string $homeUrl,
        string $label = 'Home'
    ): array {
        return $this->build(
            pageUrl: $homeUrl,
            items: [
                [
                    'name' => $label,
                    'url' => $homeUrl,
                ],
            ]
        );
    }

    /**
     * Build a common three-level breadcrumb.
     *
     * @return array<string, mixed>
     */
    public function threeLevel(
        string $pageUrl,
        string $sectionName,
        string $sectionUrl,
        string $pageName,
        ?string $homeUrl = null
    ): array {
        $homeUrl ??= rtrim(url('/'), '/') . '/';

        return $this->build(
            pageUrl: $pageUrl,
            items: [
                [
                    'name' => 'Home',
                    'url' => $homeUrl,
                ],
                [
                    'name' => $sectionName,
                    'url' => $sectionUrl,
                ],
                [
                    'name' => $pageName,
                    'url' => $pageUrl,
                ],
            ]
        );
    }

    /**
     * Build a route-page breadcrumb.
     *
     * @return array<string, mixed>
     */
    public function routePage(
        string $pageUrl,
        string $routeName,
        ?string $routesUrl = null,
        ?string $homeUrl = null
    ): array {
        $homeUrl ??= rtrim(url('/'), '/') . '/';
        $routesUrl ??= url('/routes');

        return $this->build(
            pageUrl: $pageUrl,
            items: [
                [
                    'name' => 'Home',
                    'url' => $homeUrl,
                ],
                [
                    'name' => 'Cab Routes',
                    'url' => $routesUrl,
                ],
                [
                    'name' => $routeName,
                    'url' => $pageUrl,
                ],
            ]
        );
    }

    /**
     * Build a service-page breadcrumb.
     *
     * @return array<string, mixed>
     */
    public function servicePage(
        string $pageUrl,
        string $serviceName,
        ?string $servicesUrl = null,
        ?string $homeUrl = null
    ): array {
        $homeUrl ??= rtrim(url('/'), '/') . '/';
        $servicesUrl ??= url('/services');

        return $this->build(
            pageUrl: $pageUrl,
            items: [
                [
                    'name' => 'Home',
                    'url' => $homeUrl,
                ],
                [
                    'name' => 'Services',
                    'url' => $servicesUrl,
                ],
                [
                    'name' => $serviceName,
                    'url' => $pageUrl,
                ],
            ]
        );
    }

    /**
     * Build a product-page breadcrumb.
     *
     * @return array<string, mixed>
     */
    public function productPage(
        string $pageUrl,
        string $productName,
        ?string $productsUrl = null,
        ?string $homeUrl = null
    ): array {
        $homeUrl ??= rtrim(url('/'), '/') . '/';
        $productsUrl ??= url('/products');

        return $this->build(
            pageUrl: $pageUrl,
            items: [
                [
                    'name' => 'Home',
                    'url' => $homeUrl,
                ],
                [
                    'name' => 'Products',
                    'url' => $productsUrl,
                ],
                [
                    'name' => $productName,
                    'url' => $pageUrl,
                ],
            ]
        );
    }

    /**
     * Build a blog-post breadcrumb.
     *
     * @return array<string, mixed>
     */
    public function blogPost(
        string $pageUrl,
        string $postTitle,
        ?string $blogUrl = null,
        ?string $homeUrl = null
    ): array {
        $homeUrl ??= rtrim(url('/'), '/') . '/';
        $blogUrl ??= url('/blog');

        return $this->build(
            pageUrl: $pageUrl,
            items: [
                [
                    'name' => 'Home',
                    'url' => $homeUrl,
                ],
                [
                    'name' => 'Blog',
                    'url' => $blogUrl,
                ],
                [
                    'name' => $postTitle,
                    'url' => $pageUrl,
                ],
            ]
        );
    }

    /**
     * Return only a BreadcrumbList @id reference.
     *
     * @return array{@id: string}
     */
    public function reference(
        string $pageUrl,
        ?string $breadcrumbId = null
    ): array {
        return [
            '@id' => $breadcrumbId ?? $this->id($pageUrl),
        ];
    }

    /**
     * Resolve the default BreadcrumbList identifier.
     */
    public function id(string $pageUrl): string
    {
        return $this->normaliseUrl($pageUrl) . '#breadcrumb';
    }

    /**
     * Normalise absolute and relative URLs.
     */
    private function normaliseUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return rtrim(url('/'), '/') . '/';
        }

        if (
            str_starts_with($url, 'http://')
            || str_starts_with($url, 'https://')
        ) {
            $parts = parse_url($url);
            $path = is_array($parts)
                ? (string) ($parts['path'] ?? '/')
                : '/';

            if ($path === '' || $path === '/') {
                return rtrim($url, '/') . '/';
            }

            return rtrim($url, '/');
        }

        return url('/' . ltrim($url, '/'));
    }
}