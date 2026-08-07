<!DOCTYPE html>
<html
    class="scroll-smooth focus:scroll-auto"
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
>
<head>
    @php
        use App\SEO\Services\SeoSchemaService;

        $settings = \App\Support\SiteCache::settings();

        /*
        |--------------------------------------------------------------------------
        | Page-level SEO values
        |--------------------------------------------------------------------------
        |
        | Child Blade/Livewire views may override these values through sections.
        |
        */

        $pageTitle = trim($__env->yieldContent('title'));
        $pageDescription = trim($__env->yieldContent('description'));
        $pageKeywords = trim($__env->yieldContent('keywords'));
        $pageImage = trim($__env->yieldContent('image'));
        $pageCanonical = trim($__env->yieldContent('canonical'));
        $pageRobots = trim($__env->yieldContent('robots'));
        $pageType = trim($__env->yieldContent('og_type'));

        /*
        |--------------------------------------------------------------------------
        | Global business/site settings
        |--------------------------------------------------------------------------
        */

        $siteName = $settings->site_name
            ?: config('app.name', 'Dura Cabs');

        $businessName = $settings->business_name
            ?: $siteName;

        /*
        |--------------------------------------------------------------------------
        | Meta title and description
        |--------------------------------------------------------------------------
        */

        $metaTitle = $pageTitle !== ''
            ? $pageTitle
            : ($settings->default_meta_title ?: $siteName);

        $metaDescription = $pageDescription !== ''
            ? $pageDescription
            : (
                $settings->default_meta_description
                ?: $settings->business_description
            );

        $metaKeywords = $pageKeywords !== ''
            ? $pageKeywords
            : $settings->default_meta_keywords;

        /*
        |--------------------------------------------------------------------------
        | Canonical and robots
        |--------------------------------------------------------------------------
        */

        $canonicalUrl = $pageCanonical !== ''
            ? $pageCanonical
            : url()->current();

        $robots = $pageRobots !== ''
            ? $pageRobots
            : (
                $settings->robots
                ?: 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1'
            );

        $ogType = $pageType !== ''
            ? $pageType
            : 'website';

        /*
        |--------------------------------------------------------------------------
        | Media URL resolver
        |--------------------------------------------------------------------------
        */

        $resolveMediaUrl = static function (?string $value): ?string {
            if (blank($value)) {
                return null;
            }

            $value = trim($value);

            if (
                str_starts_with($value, 'http://')
                || str_starts_with($value, 'https://')
                || str_starts_with($value, '//')
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

        $favicon = $settings->favicon_url
            ?: asset('img/logo/favicon_duracabs.ico');

        $twitterUsername = filled($settings->twitter_username)
            ? '@' . ltrim($settings->twitter_username, '@')
            : null;

        /*
        |--------------------------------------------------------------------------
        | Centralized global structured data
        |--------------------------------------------------------------------------
        |
        | Organization / TaxiService
        | WebSite
        | Homepage WebPage
        |
        | Page-specific Service, FAQ, Breadcrumb, Product and other schemas
        | continue to be rendered through @stack('schema').
        |
        */

        $homeUrl = rtrim(url('/'), '/') . '/';

        $schemaService = app(SeoSchemaService::class);

        $globalSchema = $schemaService->globalGraph(
            settings: $settings,
            homeUrl: $homeUrl,
            includeHomepage: false,

            /*
             * SearchAction will remain disabled until the exact public
             * search route and query parameter are confirmed.
             */
            searchTarget: null
        );
    @endphp

    {{-- External asset connection --}}
    <link
        rel="preconnect"
        href="https://cdnjs.cloudflare.com"
        crossorigin
    >

    <link
        rel="preload"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
        as="style"
        onload="this.onload=null;this.rel='stylesheet'"
    >

    <noscript>
        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
        >
    </noscript>

    {{-- Core metadata --}}
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>{{ $metaTitle }}</title>

    <meta
        name="description"
        content="{{ $metaDescription }}"
    >

    @if(filled($metaKeywords))
        <meta
            name="keywords"
            content="{{ $metaKeywords }}"
        >
    @endif

    <meta
        name="robots"
        content="{{ $robots }}"
    >

    <meta
        name="author"
        content="{{ $businessName }}"
    >

    <meta
        name="application-name"
        content="{{ $siteName }}"
    >

    <meta
        name="theme-color"
        content="#ffffff"
    >

    {{-- Canonical and favicon --}}
    <link
        rel="canonical"
        href="{{ $canonicalUrl }}"
    >

    <link
        rel="icon"
        href="{{ $favicon }}"
    >

    {{-- Search-engine verification --}}
    @if(filled($settings->google_site_verification))
        <meta
            name="google-site-verification"
            content="{{ $settings->google_site_verification }}"
        >
    @endif

    @if(filled($settings->google_site_verification_secondary))
        <meta
            name="google-site-verification"
            content="{{ $settings->google_site_verification_secondary }}"
        >
    @endif

    @if(filled($settings->bing_site_verification))
        <meta
            name="msvalidate.01"
            content="{{ $settings->bing_site_verification }}"
        >
    @endif

    @if(filled($settings->yandex_verification))
        <meta
            name="yandex-verification"
            content="{{ $settings->yandex_verification }}"
        >
    @endif

    @if(filled($settings->pinterest_domain_verification))
        <meta
            name="p:domain_verify"
            content="{{ $settings->pinterest_domain_verification }}"
        >
    @endif

    {{-- Open Graph --}}
    <meta
        property="og:locale"
        content="{{ str_replace('-', '_', app()->getLocale()) }}"
    >

    <meta
        property="og:type"
        content="{{ $ogType }}"
    >

    <meta
        property="og:site_name"
        content="{{ $siteName }}"
    >

    <meta
        property="og:title"
        content="{{ $metaTitle }}"
    >

    <meta
        property="og:description"
        content="{{ $metaDescription }}"
    >

    <meta
        property="og:url"
        content="{{ $canonicalUrl }}"
    >

    @if(filled($ogImage))
        <meta
            property="og:image"
            content="{{ $ogImage }}"
        >

        <meta
            property="og:image:alt"
            content="{{ $metaTitle }}"
        >
    @endif

    {{-- Twitter Card --}}
    <meta
        name="twitter:card"
        content="summary_large_image"
    >

    @if(filled($twitterUsername))
        <meta
            name="twitter:site"
            content="{{ $twitterUsername }}"
        >
    @endif

    <meta
        name="twitter:title"
        content="{{ $metaTitle }}"
    >

    <meta
        name="twitter:description"
        content="{{ $metaDescription }}"
    >

    @if(filled($ogImage))
        <meta
            name="twitter:image"
            content="{{ $ogImage }}"
        >

        <meta
            name="twitter:image:alt"
            content="{{ $metaTitle }}"
        >
    @endif

    {{-- Application assets --}}
    @livewireStyles

    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])

    @stack('styles')

    {{-- Consent Mode defaults --}}
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            window.dataLayer.push(arguments);
        }

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
        /*
        |--------------------------------------------------------------------------
        | Tracking configuration - ADMIN SETTINGS ONLY
        |--------------------------------------------------------------------------
        |
        | No tracking ID is hardcoded in this layout.
        |
        | Priority:
        | 1. Google Tag Manager ID from Website Settings > Analytics.
        | 2. If GTM is empty, Google Analytics / Google Ads IDs from the same
        |    admin screen are used as a fallback.
        |
        | When GTM is present, direct gtag.js loading is disabled to avoid
        | duplicate GA4 / Google Ads events when those tags are managed in GTM.
        |
        */

        $googleTagManagerId = filled($settings->google_tag_manager_id)
            ? trim((string) $settings->google_tag_manager_id)
            : null;

        $gtagIds = $googleTagManagerId
            ? collect()
            : collect([
                $settings->google_analytics_id,
                $settings->google_ads_id,
            ])
                ->filter()
                ->map(fn ($id) => trim((string) $id))
                ->unique()
                ->values();
    @endphp

    {{-- Consent-aware tracking loader. All IDs come from Website Settings. --}}
    <script>
        window.duraTrackingConfig = @json([
            'gtmId' => $googleTagManagerId,
            'gtagIds' => $gtagIds,
        ]);

        window.addEventListener(
            'dura:cookie-consent',
            function (event) {
                const consent = event.detail || {};

                if (!consent.analytics && !consent.marketing) {
                    return;
                }

                if (window.__duraTrackingLoaded) {
                    return;
                }

                window.__duraTrackingLoaded = true;

                const config = window.duraTrackingConfig || {};

                if (config.gtmId) {
                    window.dataLayer.push({
                        'gtm.start': Date.now(),
                        event: 'gtm.js'
                    });

                    const gtmScript = document.createElement('script');

                    gtmScript.async = true;
                    gtmScript.src =
                        'https://www.googletagmanager.com/gtm.js?id='
                        + encodeURIComponent(config.gtmId);

                    document.head.appendChild(gtmScript);

                    return;
                }

                if (
                    Array.isArray(config.gtagIds)
                    && config.gtagIds.length
                ) {
                    const gtagScript = document.createElement('script');

                    gtagScript.async = true;
                    gtagScript.src =
                        'https://www.googletagmanager.com/gtag/js?id='
                        + encodeURIComponent(config.gtagIds[0]);

                    document.head.appendChild(gtagScript);

                    gtag('js', new Date());

                    config.gtagIds.forEach(function (id) {
                        gtag('config', id);
                    });
                }
            }
        );
    </script>

    {{--
        Central global JSON-LD.

        Do not duplicate Organization, TaxiService or WebSite JSON-LD
        inside Website Settings > Header Code.
    --}}
    @if(
        isset($globalSchema['@graph'])
        && is_array($globalSchema['@graph'])
        && $globalSchema['@graph'] !== []
    )
        <script type="application/ld+json">
            {!! $schemaService->toJson(
                schemas: $globalSchema,
                pretty: app()->isLocal()
            ) !!}
        </script>
    @endif

    {{-- Trusted global custom header code --}}
    @if(filled($settings->header_code))
        {!! $settings->header_code !!}
    @endif

    {{--
        Page-specific schemas:

        - Service
        - Product
        - FAQPage
        - BreadcrumbList
        - Article
        - ItemList
        - Other module-specific entities
    --}}
    @stack('schema')
</head>

<body class="bg-white dark:bg-slate-700">
    @php
        $isPartner = request()->is('partner/*');
    @endphp

    @if(! $isPartner)
        @livewire('partials.navbar')
    @endif

    <main
        class="{{ $isPartner
            ? 'min-h-screen'
            : 'min-h-screen pb-20 lg:pb-0'
        }}"
    >
        {{ $slot }}
    </main>

    @if(! $isPartner)
        @livewire('partials.footer')
    @endif

    <x-cookie-consent />

    {{-- Trusted global custom footer code --}}
    @if(filled($settings->footer_code))
        {!! $settings->footer_code !!}
    @endif

    @livewireScripts

    @stack('scripts')
</body>
</html>