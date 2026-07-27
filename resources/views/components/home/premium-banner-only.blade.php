@props([
    'smartHeroBanners' => [],
    'carousel' => [],
    'bannerTab' => 'one_way',
])

@php
    /*
     * The SmartBannerService sends banners for several services together.
     * Filter them here using the selected homepage banner tab.
     */
    $normalizeServiceType = static function ($value): string {
        $value = strtolower(trim((string) $value));
        $value = str_replace(['-', ' '], '_', $value);

        return match ($value) {
            'oneway', 'one_way_trip', 'one_way_taxi' => 'one_way',
            'return', 'round', 'roundtrip', 'round_trip_taxi' => 'round_trip',
            'local_taxi', 'hourly', 'rental' => 'local',
            'selfdrive', 'self_drive_car' => 'self_drive',
            default => $value,
        };
    };

    $selectedBannerService = $normalizeServiceType($bannerTab);

    /*
     * Homepage uses `return`, while smart-banner payloads may use
     * `round_trip`. Treat both as the same filter.
     */
    if ($selectedBannerService === 'return') {
        $selectedBannerService = 'round_trip';
    }

    $smartBanners = collect($smartHeroBanners ?? [])
        ->filter(function ($banner) use ($normalizeServiceType, $selectedBannerService): bool {
            $bannerService = data_get(
                $banner,
                'action.service_type',
                data_get($banner, 'service_type')
            );

            $bannerService = $normalizeServiceType($bannerService);

            if ($bannerService === 'return') {
                $bannerService = 'round_trip';
            }

            return $bannerService === $selectedBannerService;
        })
        ->values();

    /*
     * Legacy banners are already filtered in Homepage::render(), but keep
     * this collection normalized for predictable rendering.
     */
    $fallbackBanners = collect($carousel ?? [])->values();

    $totalBanners = $smartBanners->isNotEmpty()
        ? $smartBanners->count()
        : $fallbackBanners->count();
@endphp

<div class="contents">
    <section
        wire:key="premium-banner-only-{{ $bannerTab }}"
        data-banner-service="{{ $bannerTab }}"
        class="dura-premium-routes relative overflow-hidden rounded-[26px]
               border border-[var(--dura-panel-border,#dbeafe)]
               bg-[var(--dura-panel-bg,#ffffff)]
               px-3 pb-6 pt-8
               shadow-[0_14px_40px_rgba(15,23,42,0.07)]
               sm:px-5 sm:py-6 lg:px-6"
        aria-labelledby="dura-popular-routes-title"
    >
        {{-- Decorative background --}}
        <div
            class="pointer-events-none absolute -right-24 -top-28
                   h-72 w-72 rounded-full
                   bg-[color-mix(in_srgb,var(--dura-primary,#2563eb)_10%,transparent)]
                   blur-3xl"
            aria-hidden="true"
        ></div>

        <div
            class="pointer-events-none absolute -bottom-28 -left-24
                   h-64 w-64 rounded-full
                   bg-[color-mix(in_srgb,var(--dura-primary,#2563eb)_7%,transparent)]
                   blur-3xl"
            aria-hidden="true"
        ></div>

        <div class="relative z-10">
            {{-- Header --}}
            <div class="mb-5 flex items-end justify-between gap-4 sm:mb-6">
                <div class="min-w-0">
                    <div
                        class="mb-2 inline-flex items-center gap-2 rounded-full
                               bg-[var(--dura-primary,#2563eb)]
                               px-3 py-1.5 text-[10px] font-extrabold
                               uppercase tracking-[0.12em] text-white
                               shadow-[0_6px_16px_rgba(37,99,235,0.20)]"
                    >
                        <i
                            class="fa-solid fa-star text-[9px]"
                            aria-hidden="true"
                        ></i>

                        Popular Routes
                    </div>

                    <h2
                        id="dura-popular-routes-title"
                        class="text-[23px] font-black leading-tight
                               tracking-tight
                               text-[var(--dura-heading,#0f172a)]
                               sm:text-3xl"
                    >
                        Popular

                        <span class="text-[var(--dura-primary,#2563eb)]">
                            Outstation Routes
                        </span>
                    </h2>

                    <p
                        class="mt-1.5 max-w-xl text-sm leading-6
                               text-[var(--dura-muted,#64748b)]"
                    >
                        Best prices, safe rides and reliable cab service.
                    </p>
                </div>

                @if($totalBanners > 1)
                    <div class="hidden shrink-0 items-center gap-2 sm:flex">
                        <button
                            type="button"
                            data-dura-banner-prev
                            class="grid h-11 w-11 place-items-center rounded-full
                                   border
                                   border-[var(--dura-panel-border,#dbeafe)]
                                   bg-white
                                   text-[var(--dura-primary,#2563eb)]
                                   shadow-sm transition duration-300
                                   hover:-translate-y-0.5
                                   hover:border-[var(--dura-primary,#2563eb)]
                                   hover:shadow-md
                                   focus-visible:outline-none
                                   focus-visible:ring-2
                                   focus-visible:ring-[var(--dura-primary,#2563eb)]
                                   focus-visible:ring-offset-2"
                            aria-label="Previous routes"
                        >
                            <i
                                class="fa-solid fa-chevron-left text-sm"
                                aria-hidden="true"
                            ></i>
                        </button>

                        <button
                            type="button"
                            data-dura-banner-next
                            class="grid h-11 w-11 place-items-center rounded-full
                                   border
                                   border-[var(--dura-panel-border,#dbeafe)]
                                   bg-white
                                   text-[var(--dura-primary,#2563eb)]
                                   shadow-sm transition duration-300
                                   hover:-translate-y-0.5
                                   hover:border-[var(--dura-primary,#2563eb)]
                                   hover:shadow-md
                                   focus-visible:outline-none
                                   focus-visible:ring-2
                                   focus-visible:ring-[var(--dura-primary,#2563eb)]
                                   focus-visible:ring-offset-2"
                            aria-label="Next routes"
                        >
                            <i
                                class="fa-solid fa-chevron-right text-sm"
                                aria-hidden="true"
                            ></i>
                        </button>
                    </div>
                @endif
            </div>

            {{-- Carousel --}}
            <div
                data-dura-banner-carousel
                data-banner-tab="{{ $bannerTab }}"
                data-total-banners="{{ $totalBanners }}"
                class="relative"
            >
                <div
                    data-dura-banner-track
                    class="dura-banner-scroll flex snap-x snap-mandatory
                           gap-4 overflow-x-auto scroll-smooth pb-2"
                    role="region"
                    aria-label="Popular cab routes"
                    tabindex="0"
                >
                    @forelse($smartBanners as $banner)
                        @php
                            $serviceType = data_get(
                                $banner,
                                'action.service_type',
                                data_get(
                                    $banner,
                                    'service_type',
                                    $bannerTab
                                )
                            );

                            $vehicleImage = data_get(
                                $banner,
                                'vehicle_image'
                            );

                            /*
                             * SmartBannerService already returns
                             * a normalized complete image URL.
                             */
                            $imageUrl = filled($vehicleImage)
                                ? (string) $vehicleImage
                                : null;

                            $routeLabel = data_get(
                                $banner,
                                'route.label'
                            );

                            $fromCity = data_get(
                                $banner,
                                'route.from'
                            );

                            $toCity = data_get(
                                $banner,
                                'route.to'
                            );

                            $formattedFare = data_get(
                                $banner,
                                'formatted_fare'
                            );

                            $displayFare = filled($formattedFare)
                                ? str($formattedFare)
                                    ->replace('Starting from ', '')
                                : null;

                            $vehicleName = data_get(
                                $banner,
                                'vehicle',
                                data_get($banner, 'vehicle_name')
                            );

                            $actionUrl = data_get(
                                $banner,
                                'action.url'
                            );

                            if (blank($actionUrl)) {
                                $actionParameters = array_filter(
                                    (array) data_get(
                                        $banner,
                                        'action.parameters',
                                        []
                                    ),
                                    static fn ($value) => filled($value)
                                );

                                $actionParameters['service_type'] =
                                    $serviceType;

                                $actionUrl = route(
                                    'rides',
                                    $actionParameters
                                );
                            }
                        @endphp

                        <article
    data-dura-banner-card
    class="group relative min-h-[260px] shrink-0
           snap-start overflow-hidden rounded-[22px]
           border border-[var(--dura-panel-border,#dbeafe)]
           bg-white
           shadow-[0_10px_24px_rgba(15,23,42,0.08)]
           transition duration-300
           hover:-translate-y-1
           hover:shadow-[0_20px_45px_rgba(15,23,42,0.12)]
           basis-[92%]
           sm:min-h-[275px] sm:basis-[72%]
           md:basis-[calc((100%-1rem)/2)]
           lg:basis-[calc((100%-2rem)/3)]
           xl:basis-[calc((100%-2rem)/3)]"
>
                            {{-- Curved theme background --}}
                            <div
                                class="pointer-events-none absolute
                                       -right-14 -top-20
                                       h-[260px] w-[190px]
                                       rotate-[10deg] rounded-[46%]
                                       bg-gradient-to-br
                                       from-[var(--dura-primary,#2563eb)]
                                       via-[color-mix(in_srgb,var(--dura-primary,#2563eb)_92%,white)]
                                       to-[color-mix(in_srgb,var(--dura-primary,#2563eb)_82%,black)]
                                       transition duration-500
                                       group-hover:scale-105"
                                aria-hidden="true"
                            ></div>

                            <div
                                class="pointer-events-none absolute right-0
                                       top-0 h-full w-[42%]
                                       bg-gradient-to-l
                                       from-[color-mix(in_srgb,var(--dura-primary,#2563eb)_10%,transparent)]
                                       to-transparent"
                                aria-hidden="true"
                            ></div>

                            <a
                                href="{{ $actionUrl }}"
                                class="relative z-10 flex min-h-[260px]
                                       flex-col p-4 text-inherit no-underline
                                       sm:min-h-[275px]
                                       focus-visible:outline-none
                                       focus-visible:ring-2
                                       focus-visible:ring-inset
                                       focus-visible:ring-[var(--dura-primary,#2563eb)]"
                            >
                                <div class="flex items-start justify-between">
                                    <span
                                        class="inline-flex items-center gap-1.5
                                               rounded-full
                                               bg-[var(--dura-primary,#2563eb)]
                                               px-3 py-1.5 text-[10px]
                                               font-extrabold uppercase
                                               tracking-[0.08em] text-white
                                               shadow-sm"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full
                                                   bg-white"
                                        ></span>

                                        {{ str($serviceType)
                                            ->replace('_', ' ')
                                            ->title() }}
                                    </span>
                                </div>

                                <h3
                                    class="mt-2 max-w-[68%] text-[18px] font-black
       leading-[1.15] tracking-tight
       text-[var(--dura-heading,#0f172a)]"
                                >
                                    @if(filled($fromCity) && filled($toCity))
                                        {{ $fromCity }}

                                        <span
                                            class="mx-1
                                                   text-[var(--dura-primary,#2563eb)]"
                                        >
                                            <i
                                                class="fa-solid
                                                       fa-arrow-right-long
                                                       text-sm"
                                                aria-hidden="true"
                                            ></i>
                                        </span>

                                        {{ $toCity }}
                                    @else
                                        {{ data_get(
                                            $banner,
                                            'title',
                                            'Book Your Ride'
                                        ) }}
                                    @endif
                                </h3>

                                @if(filled($routeLabel))
                                    <div
                                        class="mt-1.5 flex max-w-[68%]
                                               items-center gap-1.5 text-xs
                                               font-medium text-slate-500"
                                    >
                                        <i
                                            class="fa-solid fa-location-dot
                                                   text-[var(--dura-primary,#2563eb)]"
                                            aria-hidden="true"
                                        ></i>

                                        <span class="truncate">
                                            {{ $routeLabel }}
                                        </span>
                                    </div>
                                @endif

                                <div
                                    class="my-3 h-px max-w-[48%]
                                           bg-[var(--dura-panel-border,#dbeafe)]"
                                ></div>

                                <div class="max-w-[58%]">
                                    @if(filled($displayFare))
                                        <span
                                            class="block text-xs font-medium
                                                   text-slate-500"
                                        >
                                            Starting fare
                                        </span>

                                        <strong
                                            class="mt-1 block text-[22px]
                                                   font-black leading-none
                                                   tracking-tight
                                                   text-[var(--dura-primary,#2563eb)]"
                                        >
                                            {{ $displayFare }}
                                        </strong>
                                    @endif

                                    @if(filled($vehicleName))
                                        <span
                                            class="mt-2 inline-flex
                                                   max-w-full items-center
                                                   rounded-full
                                                   bg-[color-mix(in_srgb,var(--dura-primary,#2563eb)_8%,white)]
                                                   px-2.5 py-1 text-[10px]
                                                   font-bold
                                                   text-[var(--dura-primary,#2563eb)]"
                                        >
                                            <span class="truncate">
                                                {{ $vehicleName }}
                                            </span>
                                        </span>
                                    @endif
                                </div>

                                {{-- Vehicle circle --}}
                                <div
                                    class="pointer-events-none absolute
                                           right-1 top-[92px] z-20
                                           h-[150px] w-[150px] sm:right-2"
                                    aria-hidden="true"
                                >
                                    <div
                                        class="dura-orbit-slow absolute inset-0
                                               rounded-full border
                                               border-cyan-200/80"
                                    >
                                        <span
                                            class="absolute left-1/2 top-[-4px]
                                                   h-2.5 w-2.5
                                                   -translate-x-1/2
                                                   rounded-full bg-cyan-300"
                                        ></span>
                                    </div>

                                    <div
                                        class="dura-orbit-reverse absolute
                                               inset-[9px] rounded-full
                                               border
                                               border-[var(--dura-panel-border,#dbeafe)]/80"
                                    ></div>

                                    <div
                                        class="dura-orbit-slow-alt absolute
                                               inset-[18px] rounded-full
                                               border border-white/70"
                                    ></div>

                                    <div
                                        class="absolute inset-[8px] grid
       place-items-center
       overflow-hidden rounded-full"
                                    ></div>

                                    <div
                                        class="absolute inset-[18px] grid
                                               place-items-center
                                               overflow-hidden rounded-full
                                               border-[5px] border-white
                                               bg-white
                                               shadow-[0_12px_28px_rgba(15,23,42,0.16)]"
                                    >
                                        @if(filled($imageUrl))
                                            <img
                                                src="{{ $imageUrl }}"
                                                alt="{{ data_get(
                                                    $banner,
                                                    'title',
                                                    'Duracabs vehicle'
                                                ) }}"
                                                class="dura-car-float
                                                       relative z-10
                                                       h-[100%] w-[100%]
													   object-contain
													      scale-[1.9]
                                                loading="{{ $loop->first
                                                    ? 'eager'
                                                    : 'lazy' }}"
                                                decoding="async"
                                                fetchpriority="{{ $loop->first
                                                    ? 'high'
                                                    : 'low' }}"
                                                onerror="
                                                    this.hidden = true;
                                                    this.nextElementSibling
                                                        .classList
                                                        .remove('hidden');
                                                "
                                            >

                                            <i
                                                class="fa-solid fa-car-side
                                                       hidden text-4xl
                                                       text-[var(--dura-primary,#2563eb)]"
                                                aria-hidden="true"
                                            ></i>
                                        @else
                                            <i
                                                class="fa-solid fa-car-side
                                                       text-4xl
                                                       text-[var(--dura-primary,#2563eb)]"
                                                aria-hidden="true"
                                            ></i>
                                        @endif
                                    </div>
                                </div>

                                <div
                                    class="mt-auto flex items-center gap-2 pt-3"
                                >
                                    <span
                                        class="inline-flex h-9 items-center
                                               justify-center rounded-xl
                                               bg-[var(--dura-primary,#2563eb)]
                                               px-3.5 text-xs font-extrabold
                                               text-white
                                               shadow-[0_8px_18px_rgba(37,99,235,0.26)]
                                               transition duration-300
                                               group-hover:brightness-95"
                                    >
                                        Book Now
                                    </span>

                                    <span
                                        class="grid h-9 w-9 place-items-center
                                               rounded-full border
                                               border-[var(--dura-panel-border,#dbeafe)]
                                               bg-white
                                               text-[var(--dura-primary,#2563eb)]
                                               transition duration-300
                                               group-hover:translate-x-1
                                               group-hover:border-[var(--dura-primary,#2563eb)]"
                                    >
                                        <i
                                            class="fa-solid fa-arrow-right
                                                   text-xs"
                                            aria-hidden="true"
                                        ></i>
                                    </span>
                                </div>
                            </a>

                            <div
                                class="relative z-20 grid grid-cols-3
                                       border-t
                                       border-[var(--dura-panel-border,#dbeafe)]
                                       bg-white/95 px-3 py-2 text-[8px]
                                       font-semibold text-slate-600
                                       backdrop-blur"
                            >
                                <span
                                    class="flex items-center justify-center
                                           gap-1"
                                >
                                    <i
                                        class="fa-solid fa-tags
                                               text-[var(--dura-primary,#2563eb)]"
                                        aria-hidden="true"
                                    ></i>

                                    Best Price
                                </span>

                                <span
                                    class="flex items-center justify-center
                                           gap-1"
                                >
                                    <i
                                        class="fa-solid fa-shield-halved
                                               text-[var(--dura-primary,#2563eb)]"
                                        aria-hidden="true"
                                    ></i>

                                    Verified
                                </span>

                                <span
                                    class="flex items-center justify-center
                                           gap-1"
                                >
                                    <i
                                        class="fa-solid fa-headset
                                               text-[var(--dura-primary,#2563eb)]"
                                        aria-hidden="true"
                                    ></i>

                                    24×7
                                </span>
                            </div>
                        </article>
                    @empty
                        @forelse($fallbackBanners as $item)
                            @php
                                $fallbackImage = data_get($item, 'image');

                                $fallbackUrl = data_get(
                                    $item,
                                    'url',
                                    route('rides')
                                );

                                $fallbackImageUrl = null;

                                if (filled($fallbackImage)) {
                                    $fallbackImage = (string) $fallbackImage;

                                    $fallbackImageUrl =
                                        str_starts_with(
                                            $fallbackImage,
                                            'http://'
                                        )
                                        || str_starts_with(
                                            $fallbackImage,
                                            'https://'
                                        )
                                            ? $fallbackImage
                                            : asset(
                                                'storage/'
                                                . ltrim(
                                                    $fallbackImage,
                                                    '/'
                                                )
                                            );
                                }
                            @endphp

                            <article
                                data-dura-banner-card
                                class="group relative shrink-0 snap-start overflow-hidden
                                       rounded-[22px] border
                                       border-[var(--dura-panel-border,#dbeafe)]
                                       bg-white
                                       shadow-[0_10px_24px_rgba(15,23,42,0.08)]
                                       transition duration-300
                                       hover:-translate-y-1
                                       hover:shadow-[0_20px_45px_rgba(15,23,42,0.12)]
                                       basis-[88%]
                                       sm:basis-[58%]
                                       md:basis-[calc((100%-1rem)/2)]
                                       lg:basis-[calc((100%-2rem)/3)]"
                            >
                                <a
                                    href="{{ $fallbackUrl }}"
                                    class="block h-full overflow-hidden no-underline
                                           focus-visible:outline-none
                                           focus-visible:ring-2
                                           focus-visible:ring-inset
                                           focus-visible:ring-[var(--dura-primary,#2563eb)]"
                                >
                                    @if(filled($fallbackImageUrl))
                                        <div class="relative aspect-[16/9] overflow-hidden bg-slate-100">
                                            <img
                                                src="{{ $fallbackImageUrl }}"
                                                alt="{{ data_get($item, 'alt', 'Duracabs cab booking offer') }}"
                                                class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                                loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                                                decoding="async"
                                            >

                                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/45 via-transparent to-transparent" aria-hidden="true"></div>

                                            <span
                                                class="absolute left-4 top-4 inline-flex items-center gap-1.5 rounded-full
                                                       bg-[var(--dura-primary,#2563eb)] px-3 py-1.5
                                                       text-[10px] font-extrabold uppercase tracking-[0.08em] text-white shadow-sm"
                                            >
                                                <span class="h-1.5 w-1.5 rounded-full bg-white"></span>
                                                {{ str($bannerTab)->replace('_', ' ')->title() }}
                                            </span>
                                        </div>

                                        <div class="flex min-h-[142px] flex-col p-4 sm:p-5">
                                            <h3
                                                class="line-clamp-2 text-[17px] font-black leading-snug tracking-tight
                                                       text-[var(--dura-heading,#0f172a)] sm:text-lg"
                                            >
                                                {{ data_get($item, 'title', 'Book Your Ride with Duracabs') }}
                                            </h3>

                                            <div class="mt-auto flex items-center justify-between gap-3 pt-4">
                                                <span class="text-xs font-semibold text-slate-500">Safe • Reliable • Best Price</span>
                                                <span
                                                    class="inline-flex h-10 shrink-0 items-center gap-2 rounded-xl
                                                           bg-[var(--dura-primary,#2563eb)] px-4 text-xs font-extrabold text-white
                                                           shadow-[0_8px_18px_rgba(37,99,235,0.22)] transition
                                                           group-hover:translate-x-0.5"
                                                >
                                                    Book Now
                                                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                                </span>
                                            </div>
                                        </div>
                                    @else
                                        <div
                                            class="flex min-h-[260px]
                                                   flex-col justify-center
                                                   bg-gradient-to-br
                                                   from-white
                                                   to-[color-mix(in_srgb,var(--dura-primary,#2563eb)_12%,white)]
                                                   p-4 sm:min-h-[275px]"
                                        >
                                            <i
                                                class="fa-solid fa-car-side
                                                       text-5xl
                                                       text-[var(--dura-primary,#2563eb)]"
                                                aria-hidden="true"
                                            ></i>

                                            <h3
                                                class="mt-4 text-2xl
                                                       font-black
                                                       text-[var(--dura-heading,#0f172a)]"
                                            >
                                                {{ data_get(
                                                    $item,
                                                    'title',
                                                    'Book Your Ride with Duracabs'
                                                ) }}
                                            </h3>

                                            <span
                                                class="mt-5 inline-flex h-9
                                                       w-max items-center
                                                       rounded-xl
                                                       bg-[var(--dura-primary,#2563eb)]
                                                       px-3.5 text-xs
                                                       font-extrabold
                                                       text-white"
                                            >
                                                Book Now
                                            </span>
                                        </div>
                                    @endif
                                </a>
                            </article>
                        @empty
                            <article
                                data-dura-banner-card
                                class="relative min-h-[260px] shrink-0
                                       snap-start rounded-[22px] border
                                       border-[var(--dura-panel-border,#dbeafe)]
                                       bg-white p-4 basis-[92%]
                                       sm:min-h-[275px] sm:basis-[72%]
                                       md:basis-[calc((100%-1rem)/2)]
                                       lg:basis-[calc((100%-2rem)/3)]
                                       xl:basis-[calc((100%-3rem)/4)]"
                            >
                                <a
                                    href="{{ route('rides') }}"
                                    class="flex min-h-[260px] flex-col
                                           justify-center no-underline"
                                >
                                    <i
                                        class="fa-solid fa-car-side text-5xl
                                               text-[var(--dura-primary,#2563eb)]"
                                        aria-hidden="true"
                                    ></i>

                                    <h3
                                        class="mt-4 text-2xl font-black
                                               text-[var(--dura-heading,#0f172a)]"
                                    >
                                        Book Your Ride with Duracabs
                                    </h3>

                                    <p class="mt-1.5 text-sm text-slate-500">
                                        Safe, verified and affordable taxi
                                        service.
                                    </p>
                                </a>
                            </article>
                        @endforelse
                    @endforelse
                </div>

                @if($totalBanners > 1)
                    <div
                        class="mt-2 flex items-center justify-between
                               sm:hidden"
                    >
                        <button
                            type="button"
                            data-dura-banner-prev
                            class="grid h-9 w-9 place-items-center
                                   rounded-full border bg-white
                                   text-[var(--dura-primary,#2563eb)]"
                            aria-label="Previous banners"
                        >
                            <i
                                class="fa-solid fa-chevron-left"
                                aria-hidden="true"
                            ></i>
                        </button>

                        <div
                            data-dura-banner-dots
                            class="flex items-center justify-center gap-1.5"
                        ></div>

                        <button
                            type="button"
                            data-dura-banner-next
                            class="grid h-9 w-9 place-items-center
                                   rounded-full border bg-white
                                   text-[var(--dura-primary,#2563eb)]"
                            aria-label="Next banners"
                        >
                            <i
                                class="fa-solid fa-chevron-right"
                                aria-hidden="true"
                            ></i>
                        </button>
                    </div>

                    <div
                        data-dura-banner-dots
                        class="mt-4 hidden items-center justify-center
                               gap-1.5 sm:flex"
                    ></div>
                @endif
            </div>
        </div>
    </section>

    @once
        <script>
            (() => {
                'use strict';

                const autoplayDelay = 5500;

                function visibleCards() {
                    if (window.matchMedia(
                         '(min-width: 1024px)'
                    ).matches) {
                        return 3;
                    }

                    if (window.matchMedia(
                        '(min-width: 768px)'
                    ).matches) {
                        return 3;
                    }

                    
                    return 1;
                }

                function initialise(carousel) {
                    if (
                        !(carousel instanceof HTMLElement)
                        || carousel.dataset.duraReady === 'true'
                    ) {
                        return;
                    }

                    const track = carousel.querySelector(
                        '[data-dura-banner-track]'
                    );

                    const cards = Array.from(
                        carousel.querySelectorAll(
                            '[data-dura-banner-card]'
                        )
                    );

                    const wrapper = carousel.parentElement;

                    const previousButtons = wrapper
                        ? Array.from(
                            wrapper.querySelectorAll(
                                '[data-dura-banner-prev]'
                            )
                        )
                        : [];

                    const nextButtons = wrapper
                        ? Array.from(
                            wrapper.querySelectorAll(
                                '[data-dura-banner-next]'
                            )
                        )
                        : [];

                    const dotContainers = Array.from(
                        carousel.querySelectorAll(
                            '[data-dura-banner-dots]'
                        )
                    );

                    if (
                        !(track instanceof HTMLElement)
                        || cards.length === 0
                    ) {
                        return;
                    }

                    carousel.dataset.duraReady = 'true';

                    let index = 0;
                    let timer = null;
                    let scrollTimer = null;
                    let dotGroups = [];

                    function maximumIndex() {
                        return Math.max(
                            0,
                            cards.length - visibleCards()
                        );
                    }

                    function cardStep() {
                        const firstCard = cards[0];

                        if (!(firstCard instanceof HTMLElement)) {
                            return track.clientWidth;
                        }

                        const styles = getComputedStyle(track);
                        const gap = Number.parseFloat(
                            styles.columnGap
                            || styles.gap
                            || '0'
                        );

                        return firstCard
                            .getBoundingClientRect()
                            .width + gap;
                    }

                    function updateDots() {
                        const activePage = Math.floor(
                            index / Math.max(1, visibleCards())
                        );

                        dotGroups.forEach((dots) => {
                            dots.forEach((dot, dotIndex) => {
                                const active =
                                    dotIndex === activePage;

                                dot.classList.toggle(
                                    'w-5',
                                    active
                                );

                                dot.classList.toggle(
                                    'w-2',
                                    !active
                                );

                                dot.classList.toggle(
                                    'bg-[var(--dura-primary,#2563eb)]',
                                    active
                                );

                                dot.classList.toggle(
                                    'bg-slate-200',
                                    !active
                                );
                            });
                        });
                    }

                    function show(requestedIndex) {
                        const maximum = maximumIndex();

                        if (requestedIndex > maximum) {
                            index = 0;
                        } else if (requestedIndex < 0) {
                            index = maximum;
                        } else {
                            index = requestedIndex;
                        }

                        track.scrollTo({
                            left: index * cardStep(),
                            behavior: 'smooth',
                        });

                        updateDots();
                    }

                    function createDots() {
                        const pages = Math.max(
                            1,
                            Math.ceil(
                                cards.length / visibleCards()
                            )
                        );

                        dotGroups = [];

                        dotContainers.forEach((container) => {
                            container.replaceChildren();

                            const dots = [];

                            for (
                                let page = 0;
                                page < pages;
                                page += 1
                            ) {
                                const dot =
                                    document.createElement('button');

                                dot.type = 'button';
                                dot.className =
                                    'h-2 w-2 rounded-full '
                                    + 'bg-slate-200 transition-all';

                                dot.setAttribute(
                                    'aria-label',
                                    `Show route group ${page + 1}`
                                );

                                dot.addEventListener(
                                    'click',
                                    () => {
                                        show(
                                            Math.min(
                                                page
                                                * visibleCards(),
                                                maximumIndex()
                                            )
                                        );

                                        startAutoplay();
                                    }
                                );

                                container.appendChild(dot);
                                dots.push(dot);
                            }

                            dotGroups.push(dots);
                        });

                        updateDots();
                    }

                    function stopAutoplay() {
                        if (timer !== null) {
                            clearInterval(timer);
                        }

                        timer = null;
                    }

                    function startAutoplay() {
                        stopAutoplay();

                        if (
                            cards.length <= visibleCards()
                            || document.hidden
                        ) {
                            return;
                        }

                        timer = setInterval(() => {
                            show(index + visibleCards());
                        }, autoplayDelay);
                    }

                    previousButtons.forEach((button) => {
                        button.addEventListener('click', () => {
                            show(index - visibleCards());
                            startAutoplay();
                        });
                    });

                    nextButtons.forEach((button) => {
                        button.addEventListener('click', () => {
                            show(index + visibleCards());
                            startAutoplay();
                        });
                    });

                    track.addEventListener(
                        'scroll',
                        () => {
                            clearTimeout(scrollTimer);

                            scrollTimer = setTimeout(() => {
                                const step = cardStep();

                                if (step > 0) {
                                    index = Math.min(
                                        maximumIndex(),
                                        Math.max(
                                            0,
                                            Math.round(
                                                track.scrollLeft / step
                                            )
                                        )
                                    );

                                    updateDots();
                                }
                            }, 100);
                        },
                        { passive: true }
                    );

                    carousel.addEventListener(
                        'mouseenter',
                        stopAutoplay
                    );

                    carousel.addEventListener(
                        'mouseleave',
                        startAutoplay
                    );

                    window.addEventListener('resize', () => {
                        createDots();
                        index = Math.min(
                            index,
                            maximumIndex()
                        );

                        track.scrollTo({
                            left: index * cardStep(),
                            behavior: 'auto',
                        });

                        startAutoplay();
                    });

                    createDots();
                    startAutoplay();
                }

                function boot(root = document) {
                    root
                        .querySelectorAll(
                            '[data-dura-banner-carousel]'
                        )
                        .forEach(initialise);
                }

                if (document.readyState === 'loading') {
                    document.addEventListener(
                        'DOMContentLoaded',
                        () => boot(document),
                        { once: true }
                    );
                } else {
                    boot(document);
                }

                document.addEventListener(
                    'livewire:navigated',
                    () => boot(document)
                );

                document.addEventListener(
                    'livewire:init',
                    () => {
                        if (!window.Livewire?.hook) {
                            return;
                        }

                        window.Livewire.hook(
                            'morph.updated',
                            ({ el }) => {
                                boot(
                                    el instanceof Element
                                        ? el
                                        : document
                                );
                            }
                        );
                    }
                );
            })();
        </script>
    @endonce
</div>