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
        $twitterUsername = filled($settings->twitter_username)
            ? '@' . ltrim($settings->twitter_username, '@')
            : null;

        /*
         * Global structured data uses the existing Website Settings record.
         * Do not duplicate Organization/WebSite JSON-LD inside Header Code.
         */
        $homeUrl = rtrim(url('/'), '/') . '/';
        $organizationId = $homeUrl . '#organization';
        $websiteId = $homeUrl . '#website';

        $businessType = in_array(
            $settings->business_type,
            [
                'TaxiService',
                'LocalBusiness',
                'TravelAgency',
                'AutomotiveBusiness',
                'Organization',
            ],
            true
        )
            ? $settings->business_type
            : 'Organization';

        $postalAddress = array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => filled($settings->street_address)
                ? $settings->street_address
                : null,
            'addressLocality' => filled($settings->city)
                ? $settings->city
                : null,
            'addressRegion' => filled($settings->state)
                ? $settings->state
                : null,
            'postalCode' => filled($settings->postal_code)
                ? $settings->postal_code
                : null,
            'addressCountry' => filled($settings->country_code)
                ? strtoupper($settings->country_code)
                : null,
        ], static fn (mixed $value): bool =>
            $value !== null && $value !== '' && $value !== []
        );

        $geoCoordinates = filled($settings->latitude)
            && filled($settings->longitude)
                ? [
                    '@type' => 'GeoCoordinates',
                    'latitude' => (float) $settings->latitude,
                    'longitude' => (float) $settings->longitude,
                ]
                : null;

        $openingHoursSpecification = null;

        if ((bool) $settings->open_24_hours) {
            $openingHoursSpecification = [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => [
                    'Monday',
                    'Tuesday',
                    'Wednesday',
                    'Thursday',
                    'Friday',
                    'Saturday',
                    'Sunday',
                ],
                'opens' => '00:00',
                'closes' => '23:59',
            ];
        } elseif (
            filled($settings->opening_time)
            && filled($settings->closing_time)
        ) {
            $openingHoursSpecification = [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => [
                    'Monday',
                    'Tuesday',
                    'Wednesday',
                    'Thursday',
                    'Friday',
                    'Saturday',
                    'Sunday',
                ],
                'opens' => substr((string) $settings->opening_time, 0, 5),
                'closes' => substr((string) $settings->closing_time, 0, 5),
            ];
        }

        $contactPoints = collect([
            filled($settings->phone)
                ? [
                    '@type' => 'ContactPoint',
                    'telephone' => $settings->phone,
                    'contactType' => 'customer service',
                    'areaServed' => filled($settings->country_code)
                        ? strtoupper($settings->country_code)
                        : 'IN',
                    'availableLanguage' => ['English', 'Hindi'],
                ]
                : null,
            filled($settings->alternate_phone)
                ? [
                    '@type' => 'ContactPoint',
                    'telephone' => $settings->alternate_phone,
                    'contactType' => 'customer service',
                    'areaServed' => filled($settings->country_code)
                        ? strtoupper($settings->country_code)
                        : 'IN',
                    'availableLanguage' => ['English', 'Hindi'],
                ]
                : null,
        ])->filter()->values()->all();

        $aggregateRating = filled($settings->rating_value)
            && (int) $settings->review_count > 0
                ? [
                    '@type' => 'AggregateRating',
                    'ratingValue' => (float) $settings->rating_value,
                    'reviewCount' => (int) $settings->review_count,
                    'bestRating' => filled($settings->best_rating)
                        ? (float) $settings->best_rating
                        : 5,
                ]
                : null;

        $organizationSchema = array_filter([
            '@type' => $businessType,
            '@id' => $organizationId,
            'name' => $businessName,
            'url' => $homeUrl,
            'description' => filled($settings->business_description)
                ? $settings->business_description
                : null,
            'logo' => filled($settings->logo_url)
                ? [
                    '@type' => 'ImageObject',
                    'url' => $settings->logo_url,
                ]
                : null,
            'image' => filled($settings->default_og_image_url)
                ? $settings->default_og_image_url
                : $settings->logo_url,
            'telephone' => filled($settings->phone)
                ? $settings->phone
                : null,
            'email' => filled($settings->email)
                ? $settings->email
                : null,
            'priceRange' => filled($settings->price_range)
                ? $settings->price_range
                : null,
            'address' => count($postalAddress) > 1
                ? $postalAddress
                : null,
            'geo' => $geoCoordinates,
            'hasMap' => filled($settings->google_map_url)
                ? $settings->google_map_url
                : null,
            'openingHoursSpecification' => $openingHoursSpecification,
            'contactPoint' => $contactPoints !== []
                ? $contactPoints
                : null,
            'sameAs' => $settings->socialProfiles() !== []
                ? $settings->socialProfiles()
                : null,
            'areaServed' => [
                '@type' => 'Country',
                'name' => filled($settings->country_code)
                    && strtoupper($settings->country_code) === 'IN'
                        ? 'India'
                        : strtoupper((string) $settings->country_code),
            ],
            'aggregateRating' => $aggregateRating,
        ], static fn (mixed $value): bool =>
            $value !== null && $value !== '' && $value !== []
        );

        $websiteSchema = [
            '@type' => 'WebSite',
            '@id' => $websiteId,
            'url' => $homeUrl,
            'name' => $siteName,
            'description' => filled($settings->default_meta_description)
                ? $settings->default_meta_description
                : $settings->business_description,
            'publisher' => [
                '@id' => $organizationId,
            ],
            'inLanguage' => str_replace('_', '-', app()->getLocale()),
        ];

        $homepageSchema = request()->is('/')
            ? array_filter([
                '@type' => 'WebPage',
                '@id' => $homeUrl . '#webpage',
                'url' => $homeUrl,
                'name' => $metaTitle,
                'description' => $metaDescription,
                'inLanguage' => str_replace('_', '-', app()->getLocale()),
                'isPartOf' => [
                    '@id' => $websiteId,
                ],
                'about' => [
                    '@id' => $organizationId,
                ],
                'publisher' => [
                    '@id' => $organizationId,
                ],
                'primaryImageOfPage' => filled($ogImage)
                    ? [
                        '@id' => $homeUrl . '#primaryimage',
                    ]
                    : null,
                'image' => filled($ogImage)
                    ? [
                        '@type' => 'ImageObject',
                        '@id' => $homeUrl . '#primaryimage',
                        'url' => $ogImage,
                        'contentUrl' => $ogImage,
                        'caption' => $metaTitle,
                    ]
                    : null,
            ], static fn (mixed $value): bool =>
                $value !== null && $value !== '' && $value !== []
            )
            : null;

        $globalSchema = [
            '@context' => 'https://schema.org',
            '@graph' => array_values(array_filter([
                $organizationSchema,
                $websiteSchema,
                $homepageSchema,
            ])),
        ];

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



    {{-- Dynamic global Organization/LocalBusiness and WebSite schema. --}}
    <script type="application/ld+json">
        {!! json_encode(
            $globalSchema,
            JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_PRETTY_PRINT
        ) !!}
    </script>

    {{-- Global custom header code from Website Settings. Trusted admins only. --}}
    @if(filled($settings->header_code))
        {!! $settings->header_code !!}
    @endif

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


    {{-- Global custom footer code from Website Settings. Trusted admins only. --}}
    @if(filled($settings->footer_code))
        {!! $settings->footer_code !!}
    @endif

    @livewireScripts
    @stack('scripts')
</body>
</html>