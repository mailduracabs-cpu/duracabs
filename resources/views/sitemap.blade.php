{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}

<urlset
    xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
>
    @foreach ($entries as $entry)
        <url>
            <loc>{{ $entry['loc'] }}</loc>
            <lastmod>{{ $entry['lastmod'] }}</lastmod>
            <changefreq>{{ $entry['changefreq'] }}</changefreq>
            <priority>{{ $entry['priority'] }}</priority>

            @foreach ($entry['images'] ?? [] as $image)
                <image:image>
                    <image:loc>{{ $image['loc'] }}</image:loc>

                    @if (filled($image['title'] ?? null))
                        <image:title>{{ $image['title'] }}</image:title>
                    @endif

                    @if (filled($image['caption'] ?? null))
                        <image:caption>{{ $image['caption'] }}</image:caption>
                    @endif
                </image:image>
            @endforeach
        </url>
    @endforeach
</urlset>