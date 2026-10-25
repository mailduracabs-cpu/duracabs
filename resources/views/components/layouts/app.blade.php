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

    {{-- Application assets: load Livewire, Vite and page styles once. --}}
    @livewireStyles

    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])

    {{-- Font Awesome --}}
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
        referrerpolicy="no-referrer"
    />


    {{-- Customer app download floating CTA --}}
    <style>
        .dura-app-float{position:fixed;right:20px;bottom:24px;z-index:1050;border:0;border-radius:999px;background:#009fe3;color:#fff;padding:13px 19px;box-shadow:0 8px 26px rgba(0,95,160,.27);display:flex;align-items:center;gap:9px;font-weight:700;font-size:14px;cursor:pointer}
        .dura-app-float:hover{background:#007db7}
        .dura-app-mobile-label{display:none}
        .dura-app-overlay[hidden]{display:none!important}
        .dura-app-overlay{position:fixed;inset:0;z-index:1100;background:rgba(15,23,42,.60);display:flex;align-items:center;justify-content:center;padding:18px}
        .dura-app-dialog{width:100%;max-width:390px;background:#fff;color:#1e293b;border-radius:20px;padding:28px 24px;box-shadow:0 20px 60px rgba(0,0,0,.2);position:relative;text-align:center}
        .dura-app-close{position:absolute;top:12px;right:14px;background:#f1f5f9;color:#334155;border:0;border-radius:50%;width:34px;height:34px;cursor:pointer;font-size:20px}
        .dura-app-symbol{width:66px;height:66px;border-radius:18px;margin:0 auto 14px;display:grid;place-items:center;background:#e0f5ff;color:#009fe3;font-size:30px}
        .dura-app-dialog h2{font-size:23px;line-height:1.3;font-weight:800;margin:0 0 9px}
        .dura-app-dialog p{font-size:14px;line-height:1.6;color:#64748b;margin:0 0 22px}
        .dura-app-store{display:inline-flex;align-items:center;justify-content:center;gap:10px;background:#009fe3;color:#fff;text-decoration:none;padding:13px 20px;border-radius:11px;font-weight:700}
        .dura-app-store:hover{background:#007db7;color:#fff}
        @media(max-width:640px){.dura-app-float{right:14px;bottom:calc(86px + env(safe-area-inset-bottom, 0px));padding:12px 15px;font-size:13px}.dura-app-float .dura-app-desktop-label{display:none}.dura-app-mobile-label{display:inline}.dura-app-dialog{padding:27px 18px}}
    </style>

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
        @if(! $isPartner)
            <x-seo.seo mode="breadcrumb" :page-title="$metaTitle" />
        @endif

        {{ $slot }}

        @if(! $isPartner)
            <x-seo.seo mode="links" />
        @endif
    </main>

    @if(! $isPartner)
        @livewire('partials.footer')
    @endif


    @if(! $isPartner)
        {{-- Floating app download CTA; hidden on partner portal --}}
        <button type="button" class="dura-app-float" id="duraAppOpen" aria-haspopup="dialog" aria-controls="duraAppOverlay" aria-label="Download Duracabs app">
            <i class="fa-solid fa-mobile-screen-button" aria-hidden="true"></i>
            <span class="dura-app-desktop-label">Download App</span><span class="dura-app-mobile-label">Get App</span>
            <i class="fa-solid fa-download" aria-hidden="true"></i>
        </button>
        <div class="dura-app-overlay" id="duraAppOverlay" hidden>
            <div class="dura-app-dialog" role="dialog" aria-modal="true" aria-labelledby="duraAppTitle" aria-describedby="duraAppDescription" tabindex="-1">
                <button type="button" class="dura-app-close" id="duraAppClose" aria-label="Close download popup">&times;</button>
                <div class="dura-app-symbol"><i class="fa-solid fa-car-side" aria-hidden="true"></i></div>
                <h2 id="duraAppTitle">Download Duracabs App</h2>
                <p id="duraAppDescription">Book your next ride easily with the Duracabs Customer App.</p>
                <a class="dura-app-store" href="https://play.google.com/store/apps/details?id=com.duracabs.app" target="_blank" rel="noopener noreferrer">
                    <i class="fa-brands fa-google-play" aria-hidden="true"></i> Get it on Google Play
                </a>
            </div>
        </div>
        <script>
            (function () {
                const openButton = document.getElementById('duraAppOpen');
                const overlay = document.getElementById('duraAppOverlay');
                const closeButton = document.getElementById('duraAppClose');
                const dialog = overlay.querySelector('[role="dialog"]');
                let previousFocus = null;
                function closePopup() {
                    overlay.hidden = true;
                    document.body.style.overflow = '';
                    (previousFocus || openButton).focus();
                }
                openButton.addEventListener('click', function () {
                    previousFocus = document.activeElement;
                    overlay.hidden = false;
                    document.body.style.overflow = 'hidden';
                    closeButton.focus();
                });
                closeButton.addEventListener('click', closePopup);
                overlay.addEventListener('click', function (event) {
                    if (event.target === overlay) closePopup();
                });
                document.addEventListener('keydown', function (event) {
                    if (overlay.hidden) return;
                    if (event.key === 'Escape') closePopup();
                    if (event.key === 'Tab') {
                        const focusable = [closeButton, overlay.querySelector('.dura-app-store')];
                        const current = focusable.indexOf(document.activeElement);
                        if (event.shiftKey && current <= 0) { event.preventDefault(); focusable[1].focus(); }
                        else if (!event.shiftKey && current === 1) { event.preventDefault(); focusable[0].focus(); }
                        else if (current === -1) { event.preventDefault(); focusable[0].focus(); }
                    }
                });
            })();
        </script>
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
