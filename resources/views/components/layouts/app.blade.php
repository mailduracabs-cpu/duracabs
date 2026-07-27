<!DOCTYPE html>
<html class="scroll-smooth focus:scroll-auto" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @php
        $settings = \App\Support\SiteCache::settings();

        $pageTitle = trim($__env->yieldContent('title'));
        $pageDescription = trim($__env->yieldContent('description'));
        $pageKeywords = trim($__env->yieldContent('keywords'));
        $pageImage = trim($__env->yieldContent('image'));
        $pageCanonical = trim($__env->yieldContent('canonical'));
        $pageRobots = trim($__env->yieldContent('robots'));
        $pageType = trim($__env->yieldContent('og_type'));

        $siteName = $settings->site_name ?: config('app.name', 'Dura Cabs');
        $businessName = $settings->business_name ?: $siteName;

        $metaTitle = $pageTitle !== ''
            ? $pageTitle
            : ($settings->default_meta_title ?: $siteName);

        $metaDescription = $pageDescription !== ''
            ? $pageDescription
            : ($settings->default_meta_description ?: $settings->business_description);

        $metaKeywords = $pageKeywords !== ''
            ? $pageKeywords
            : $settings->default_meta_keywords;

        $canonicalUrl = $pageCanonical !== ''
            ? $pageCanonical
            : url()->current();

        $robots = $pageRobots !== ''
            ? $pageRobots
            : ($settings->robots ?: 'index, follow');

        $ogType = $pageType !== '' ? $pageType : 'website';

        $resolveMediaUrl = static function (?string $value): ?string {
            if (blank($value)) {
                return null;
            }

            if (
                str_starts_with($value, 'http://') ||
                str_starts_with($value, 'https://') ||
                str_starts_with($value, '//')
            ) {
                return $value;
            }

            return asset(ltrim($value, '/'));
        };

        $ogImage = $resolveMediaUrl(
            $pageImage !== ''
                ? $pageImage
                : $settings->default_og_image_url
        );

        $favicon = $settings->favicon_url ?: asset('img/logo/favicon_duracabs.ico');
        $logo = $settings->logo_url ?: $ogImage;

        $twitterUsername = filled($settings->twitter_username)
            ? '@' . ltrim($settings->twitter_username, '@')
            : null;

        $sameAs = $settings->socialProfiles();

        $schemaType = in_array(
            $settings->business_type,
            ['TaxiService', 'LocalBusiness', 'TravelAgency', 'AutomotiveBusiness', 'Organization'],
            true
        ) ? $settings->business_type : 'TaxiService';

        $businessSchema = [
            '@context' => 'https://schema.org',
            '@type' => $schemaType,
            '@id' => url('/') . '#business',
            'name' => $businessName,
            'url' => url('/'),
            'description' => $settings->business_description ?: $metaDescription,
            'telephone' => $settings->phone,
            'email' => $settings->email,
            'priceRange' => $settings->price_range,
            'image' => $logo,
            'logo' => $logo,
            'sameAs' => $sameAs,
        ];

        $address = array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => $settings->street_address,
            'addressLocality' => $settings->city,
            'addressRegion' => $settings->state,
            'postalCode' => $settings->postal_code,
            'addressCountry' => $settings->country_code ?: 'IN',
        ]);

        if (count($address) > 1) {
            $businessSchema['address'] = $address;
        }

        if (filled($settings->latitude) && filled($settings->longitude)) {
            $businessSchema['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $settings->latitude,
                'longitude' => (float) $settings->longitude,
            ];
        }

        if (filled($settings->google_map_url)) {
            $businessSchema['hasMap'] = $settings->google_map_url;
        }

        if (
            filled($settings->rating_value) &&
            filled($settings->review_count) &&
            (int) $settings->review_count > 0
        ) {
            $businessSchema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (float) $settings->rating_value,
                'reviewCount' => (int) $settings->review_count,
                'bestRating' => (float) ($settings->best_rating ?: 5),
            ];
        }

        $days = [
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday',
            'Sunday',
        ];

        if ($settings->open_24_hours) {
            $businessSchema['openingHoursSpecification'] = [[
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => $days,
                'opens' => '00:00',
                'closes' => '23:59',
            ]];
        } elseif (filled($settings->opening_time) && filled($settings->closing_time)) {
            $businessSchema['openingHoursSpecification'] = [[
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => $days,
                'opens' => substr((string) $settings->opening_time, 0, 5),
                'closes' => substr((string) $settings->closing_time, 0, 5),
            ]];
        }

        $businessSchema = array_filter(
            $businessSchema,
            static fn ($value) => !is_null($value) && $value !== '' && $value !== []
        );

        $websiteSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => url('/') . '#website',
            'url' => url('/'),
            'name' => $siteName,
            'description' => $settings->default_meta_description,
            'publisher' => [
                '@id' => url('/') . '#business',
            ],
        ];

        $breadcrumbSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => url('/'),
                ],
            ],
        ];

        if ($canonicalUrl !== url('/')) {
            $breadcrumbSchema['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => $metaTitle,
                'item' => $canonicalUrl,
            ];
        }
    @endphp
<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>

<link rel="preload"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
      as="style"
      onload="this.onload=null;this.rel='stylesheet'">

<noscript>
<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</noscript>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>{{ $metaTitle }}</title>

    <meta name="description" content="{{ $metaDescription }}">
    @if(filled($metaKeywords))
        <meta name="keywords" content="{{ $metaKeywords }}">
    @endif
    <meta name="robots" content="{{ $robots }}">
    <meta name="author" content="{{ $businessName }}">
    <meta name="application-name" content="{{ $siteName }}">
    <meta name="theme-color" content="#ffffff">

    <link rel="canonical" href="{{ $canonicalUrl }}">
    <link rel="icon" href="{{ $favicon }}">

    @if(filled($settings->google_site_verification))
        <meta name="google-site-verification" content="{{ $settings->google_site_verification }}">
    @endif
    @if(filled($settings->google_site_verification_secondary))
        <meta name="google-site-verification" content="{{ $settings->google_site_verification_secondary }}">
    @endif
    @if(filled($settings->bing_site_verification))
        <meta name="msvalidate.01" content="{{ $settings->bing_site_verification }}">
    @endif
    @if(filled($settings->yandex_verification))
        <meta name="yandex-verification" content="{{ $settings->yandex_verification }}">
    @endif
    @if(filled($settings->pinterest_domain_verification))
        <meta name="p:domain_verify" content="{{ $settings->pinterest_domain_verification }}">
    @endif

    <meta property="og:locale" content="{{ str_replace('-', '_', app()->getLocale()) }}">
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    @if(filled($ogImage))
        <meta property="og:image" content="{{ $ogImage }}">
    @endif

    <meta name="twitter:card" content="summary_large_image">
    @if(filled($twitterUsername))
        <meta name="twitter:site" content="{{ $twitterUsername }}">
    @endif
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    @if(filled($ogImage))
        <meta name="twitter:image" content="{{ $ogImage }}">
    @endif

    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    @stack('meta')

    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('consent', 'default', {
            analytics_storage: 'denied',
            ad_storage: 'denied',
            ad_user_data: 'denied',
            ad_personalization: 'denied',
            functionality_storage: 'denied',
            security_storage: 'granted',
            wait_for_update: 500
        });
    </script>

    @php
        $gtagIds = collect([
            $settings->google_analytics_id,
            $settings->google_ads_id,
        ])->filter()->unique()->values();
    @endphp

    <script>
        window.duraTrackingConfig = @json([
            'gtmId' => $settings->google_tag_manager_id,
            'gtagIds' => $gtagIds,
        ]);

        window.addEventListener('dura:cookie-consent', function (event) {
            const consent = event.detail || {};
            if (!consent.analytics && !consent.marketing) return;
            if (window.__duraTrackingLoaded) return;
            window.__duraTrackingLoaded = true;

            const config = window.duraTrackingConfig || {};
            if (config.gtmId) {
                window.dataLayer.push({'gtm.start': Date.now(), event: 'gtm.js'});
                const script = document.createElement('script');
                script.async = true;
                script.src = 'https://www.googletagmanager.com/gtm.js?id=' + encodeURIComponent(config.gtmId);
                document.head.appendChild(script);
            }

            if (Array.isArray(config.gtagIds) && config.gtagIds.length) {
                const script = document.createElement('script');
                script.async = true;
                script.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(config.gtagIds[0]);
                document.head.appendChild(script);
                gtag('js', new Date());
                config.gtagIds.forEach((id) => gtag('config', id));
            }
        });
    </script>

    <script type="application/ld+json">
        {!! json_encode($businessSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>

    <script type="application/ld+json">
        {!! json_encode($websiteSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>

    <script type="application/ld+json">
        {!! json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>

    @stack('schema')
</head>

<body class="bg-white dark:bg-slate-700">

    @php
        $isPartner = request()->is('partner/*');
    @endphp

    @if(!$isPartner)
        @livewire('partials.navbar')
    @endif

    <main class="{{ $isPartner ? 'min-h-screen' : 'min-h-screen pb-20 lg:pb-0' }}">
        {{ $slot }}
    </main>

    @if(!$isPartner)
        @livewire('partials.footer')
    @endif

    <x-cookie-consent />

    @livewireScripts
    @stack('scripts')
</body>
</html>