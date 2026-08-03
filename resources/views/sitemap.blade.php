{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @foreach ($staticUrls as $entry)
        <url>
            <loc>{{ $entry['loc'] }}</loc>
            <lastmod>{{ $entry['lastmod'] }}</lastmod>
            <changefreq>{{ $entry['changefreq'] }}</changefreq>
            <priority>{{ $entry['priority'] }}</priority>
        </url>
    @endforeach

    @foreach ($routes as $route)
        <url>
            <loc>{{ \App\Http\Controllers\SiteMapController::productUrl($route) }}</loc>
            <lastmod>{{ optional($route->updated_at)->utc()->toAtomString() }}</lastmod>
            <changefreq>{{ \App\Http\Controllers\SiteMapController::changeFrequencyForProduct($route) }}</changefreq>
            <priority>{{ \App\Http\Controllers\SiteMapController::priorityForProduct($route) }}</priority>
        </url>
    @endforeach

    @foreach ($pages as $page)
        <url>
            <loc>{{ \App\Http\Controllers\SiteMapController::pageUrl($page) }}</loc>
            <lastmod>{{ optional($page->updated_at)->utc()->toAtomString() }}</lastmod>
            <changefreq>{{ \App\Http\Controllers\SiteMapController::changeFrequencyForPage($page) }}</changefreq>
            <priority>{{ \App\Http\Controllers\SiteMapController::priorityForPage($page) }}</priority>
        </url>
    @endforeach
</urlset>