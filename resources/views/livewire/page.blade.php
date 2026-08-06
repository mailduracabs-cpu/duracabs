<div class="min-h-screen bg-slate-50 text-slate-900">
    {{-- Page SEO is emitted once by resources/views/layouts/app.blade.php. --}}
    @section('title', trim((string) data_get($page, 'meta_title', data_get($page, 'name', ''))))
    @section('description', trim((string) data_get($page, 'meta_description', '')))
    @section('keywords', trim((string) data_get($page, 'meta_keywords', '')))
    @section('image', $imageMeta)
    @section('canonical', filled(data_get($page, 'canonical_url'))
        ? trim((string) data_get($page, 'canonical_url'))
        : url()->current())
    @section('robots', filled(data_get($page, 'robots'))
        ? trim((string) data_get($page, 'robots'))
        : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1')
    @section('og_type', 'website')

   
       @push('schema')
    @php
        $renderedSchemas = [];

        $renderSchema = function ($schema) use (&$renderedSchemas) {
            if (empty($schema) || !is_array($schema)) {
                return;
            }

            $uniqueKey = data_get($schema, '@id')
                ?: data_get($schema, '@type')
                ?: md5(json_encode($schema));

            if (isset($renderedSchemas[$uniqueKey])) {
                return;
            }

            $renderedSchemas[$uniqueKey] = true;

            echo '<script type="application/ld+json">';
            echo json_encode(
                $schema,
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_PRETTY_PRINT
            );
            echo '</script>';
        };
    @endphp

    {{-- Dynamic Page Schema --}}
    @php($renderSchema($breadcrumbSchema))
    @php($renderSchema($pageSchema))
    @php($renderSchema($faqSchema))

    {{-- Custom Schemas --}}
    @foreach($customSchemas ?? [] as $schema)
        @php($renderSchema($schema))
    @endforeach

    {{-- Legacy Database Schemas --}}
    @foreach($page->all_json_ld ?? [] as $schema)
        @php($renderSchema($schema))
    @endforeach
@endpush

    @php
        $pageName = filled($page->name) ? $page->name : 'your city';
        $brandName = data_get($page, 'brand.name', $pageName);
        $bannerTabValue = $bannerTab ?? 'one_way';
    @endphp

    {{-- Hero + shared booking search --}}
    <section
        class="relative overflow-visible pb-16 pt-10 sm:pb-20 sm:pt-14 lg:pb-28"
        style="background:
            radial-gradient(circle at 15% 20%, rgba(255,255,255,.22), transparent 28%),
            radial-gradient(circle at 85% 15%, rgba(103,232,249,.28), transparent 30%),
            linear-gradient(135deg, #0284c7 0%, #0ea5e9 48%, #22d3ee 100%);"
    >
        <div class="pointer-events-none absolute -left-24 -top-24 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -right-24 top-8 h-80 w-80 rounded-full bg-cyan-200/20 blur-3xl"></div>

        <div class="relative mx-auto w-full max-w-[85rem] px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-3xl text-center">
                <span class="inline-flex items-center gap-2 rounded-full border border-white/30 bg-white/15 px-4 py-2 text-[11px] font-extrabold uppercase tracking-[0.16em] text-white shadow-sm backdrop-blur">
                    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                    Reliable • Affordable • 24×7 Support
                </span>

                <h1 class="mt-5 text-3xl font-black tracking-tight text-white sm:text-4xl lg:text-5xl">
                    Premium Car Rental in {{ $pageName }}
                </h1>

                <p class="mx-auto mt-4 max-w-2xl text-sm font-medium leading-6 text-sky-50 sm:text-base">
                    Book one way, round trip, local taxi and self-drive cars from one simple search.
                </p>
            </div>

            <div class="relative z-20 mt-8">
                @include('livewire.service-search-panel', [
                    'searchPanelMode' => 'home',
                ])
            </div>
        </div>
    </section>

    {{-- Edit search action --}}
    <div class="relative z-30 -mt-6 flex justify-center px-4">
        <button
            type="button"
            wire:click="showEditQueryModal"
            class="inline-flex min-h-12 items-center justify-center gap-2 rounded-full border-2 border-sky-500 bg-white px-6 text-sm font-extrabold uppercase tracking-wide text-sky-600 shadow-lg transition hover:bg-sky-500 hover:text-white focus:outline-none focus:ring-4 focus:ring-sky-200"
        >
            <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
            Edit Search Query
        </button>
    </div>

    <main class="mx-auto w-full max-w-[90rem] space-y-10 px-3 py-10 sm:px-5 sm:py-12 lg:space-y-14 lg:px-8 lg:py-16">
        {{-- Shared homepage banner --}}
        <section
            x-data="{ activeBannerTab: @js($bannerTabValue), changingBanner: false }"
            x-on:banner-filter-finished.window="changingBanner = false"
            aria-label="Popular cab routes"
        >
            <ul class="grid grid-cols-2 overflow-hidden rounded-2xl border border-slate-200 bg-white text-center shadow-sm md:grid-cols-4" role="tablist">
                @foreach([
                    'one_way' => 'One Way',
                    'return' => 'Round Trip',
                    'local' => 'Local',
                    'self_drive' => 'Self Drive',
                ] as $tabValue => $tabLabel)
                    <li class="min-w-0" role="presentation">
                        <button
                            type="button"
                            role="tab"
                            wire:key="page-banner-filter-{{ $tabValue }}"
                            x-on:click="
                                if (!changingBanner && activeBannerTab !== @js($tabValue)) {
                                    changingBanner = true;
                                    activeBannerTab = @js($tabValue);
                                    $wire.call('changeBanner', @js($tabValue)).finally(() => changingBanner = false);
                                }
                            "
                            x-bind:aria-selected="activeBannerTab === @js($tabValue)"
                            x-bind:disabled="changingBanner"
                            x-bind:class="activeBannerTab === @js($tabValue)
                                ? 'bg-sky-50 text-sky-600'
                                : 'bg-white text-slate-700 hover:bg-slate-50'"
                            class="flex min-h-14 w-full items-center justify-center border-b border-r border-slate-200 px-2 py-3 text-xs font-extrabold uppercase transition disabled:cursor-wait disabled:opacity-70 md:border-b-0 last:border-r-0 sm:text-sm"
                        >
                            {{ $tabLabel }}
                        </button>
                    </li>
                @endforeach
            </ul>

            <div wire:key="page-banner-results-{{ $bannerTabValue }}" class="mt-6">
                <x-home.premium-banner-only
                    :smart-hero-banners="$smartHeroBanners ?? []"
                    :carousel="$carousel ?? []"
                    :banner-tab="$bannerTabValue"
                />
            </div>
        </section>

        {{-- SEO content --}}
        <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm" aria-labelledby="page-content-heading">
            <div class="border-b border-slate-200 bg-gradient-to-r from-sky-50 via-white to-cyan-50 px-5 py-6 sm:px-8">
                <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-sky-600">Explore with Duracabs</p>
                <h2 id="page-content-heading" class="mt-2 text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">
                    Car Rental and Taxi Service in {{ $pageName }}
                </h2>
            </div>

            <div
                x-data="{ expanded: false }"
                class="px-5 py-6 sm:px-8 sm:py-8"
            >
                <div
                    x-bind:class="expanded ? '' : 'max-h-[420px] overflow-hidden'"
                    class="description prose prose-slate max-w-none transition-all duration-500
                           prose-headings:font-black prose-headings:tracking-tight prose-headings:text-slate-900
                           prose-h2:mt-8 prose-h2:text-2xl prose-h3:text-xl
                           prose-p:leading-7 prose-p:text-slate-600
                           prose-a:font-bold prose-a:text-sky-600 prose-a:no-underline hover:prose-a:text-sky-700
                           prose-strong:text-slate-900 prose-li:text-slate-600"
                >
                    {!! str($page->description)->sanitizeHtml() !!}
                </div>

                <div class="mt-6 flex justify-center">
                    <button
                        type="button"
                        x-on:click="expanded = !expanded"
                        class="inline-flex h-11 items-center gap-2 rounded-full bg-sky-500 px-5 text-sm font-extrabold text-white shadow-lg shadow-sky-500/20 transition hover:bg-sky-600 focus:outline-none focus:ring-4 focus:ring-sky-200"
                    >
                        <span x-text="expanded ? 'Show Less' : 'Read More'"></span>
                        <i class="fa-solid fa-chevron-down text-xs transition" x-bind:class="expanded ? 'rotate-180' : ''" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </section>

        {{-- Benefits --}}
        <section aria-labelledby="benefits-heading">
            <div class="mx-auto mb-7 max-w-3xl text-center">
                <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-sky-600">Why travellers choose us</p>
                <h2 id="benefits-heading" class="mt-2 text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">
                    Simple, Safe and Reliable Travel
                </h2>
            </div>

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach([
                    ['icon' => 'fa-headset', 'title' => '24×7 Support', 'text' => 'Our booking team is available to help before, during and after your journey.'],
                    ['icon' => 'fa-location-dot', 'title' => 'Doorstep Pickup', 'text' => 'Choose your preferred pickup and drop location for a smooth travel experience.'],
                    ['icon' => 'fa-tags', 'title' => 'Clear Pricing', 'text' => 'See relevant fare information before booking without confusing hidden calculations.'],
                    ['icon' => 'fa-shield-halved', 'title' => 'Verified Service', 'text' => 'Travel with maintained vehicles, verified partners and dependable customer support.'],
                ] as $benefit)
                    <article class="group rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:border-sky-200 hover:shadow-lg">
                        <div class="grid h-12 w-12 place-items-center rounded-2xl bg-sky-50 text-xl text-sky-600 transition group-hover:bg-sky-500 group-hover:text-white">
                            <i class="fa-solid {{ $benefit['icon'] }}" aria-hidden="true"></i>
                        </div>
                        <h3 class="mt-5 text-lg font-black text-slate-900">{{ $benefit['title'] }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $benefit['text'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        {{-- Fare table --}}
        @if ((int) $page->brand_id !== 112 && filled($page->link_products))
            <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm" aria-labelledby="fare-heading">
                <div class="flex flex-col gap-2 border-b border-slate-200 px-5 py-6 sm:px-8">
                    <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-sky-600">Estimated fares</p>
                    <h2 id="fare-heading" class="text-2xl font-black text-slate-900">
                        Popular Cab Routes from {{ $brandName }}
                    </h2>
                    <p class="text-sm text-slate-600">Final pricing may vary by vehicle, travel date, route and selected package.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-900 text-xs font-extrabold uppercase tracking-wide text-white">
                            <tr>
                                <th class="px-5 py-4 sm:px-8">Route</th>
                                <th class="px-5 py-4">One Way</th>
                                <th class="px-5 py-4 sm:pr-8">Round Trip / Km</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach ($page->link_products as $item)
                                <tr wire:key="fare-row-{{ $loop->index }}" class="transition hover:bg-sky-50/60">
                                    <td class="px-5 py-4 font-bold text-slate-900 sm:px-8">{{ data_get($item, 'name') }}</td>
                                    <td class="px-5 py-4 font-semibold text-slate-700">{{ data_get($item, 'oneway') }}</td>
                                    <td class="px-5 py-4 font-semibold text-slate-700 sm:pr-8">{{ data_get($item, 'perKM') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        {{-- Reviews --}}
        <section aria-labelledby="reviews-heading">
            <div class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-sky-600">Customer experiences</p>
                    <h2 id="reviews-heading" class="mt-2 text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">
                        Customer Reviews
                    </h2>
                </div>

                <button
                    type="button"
                    wire:click="reviewFunction(true)"
                    class="inline-flex h-11 items-center justify-center gap-2 rounded-full bg-sky-500 px-5 text-sm font-extrabold text-white shadow-lg shadow-sky-500/20 transition hover:bg-sky-600 focus:outline-none focus:ring-4 focus:ring-sky-200"
                >
                    <i class="fa-solid fa-star" aria-hidden="true"></i>
                    Submit Review
                </button>
            </div>

            <div class="flex snap-x snap-mandatory gap-4 overflow-x-auto pb-3">
                @forelse ($reviews as $review)
                    <article wire:key="review-{{ $review->id ?? $loop->index }}" class="w-[88%] shrink-0 snap-start rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:w-[48%] lg:w-[32%]">
                        <div class="flex items-center gap-3">
                            <img
                                src="{{ url('storage/' . ltrim((string) $review->image, '/')) }}"
                                alt="{{ $review->name }}"
                                loading="lazy"
                                decoding="async"
                                class="h-12 w-12 rounded-full bg-slate-100 object-cover"
                                onerror="this.onerror=null;this.src='{{ asset('img/placeholder/user.webp') }}';"
                            >
                            <div class="min-w-0">
                                <h3 class="truncate font-black text-slate-900">{{ $review->name }}</h3>
                                <p class="truncate text-xs font-medium text-slate-500">{{ $review->designation }}</p>
                            </div>
                        </div>

                        <div class="mt-4 flex gap-1 text-amber-400" aria-label="{{ (int) $review->star }} star rating">
                            @for ($star = 1; $star <= 5; $star++)
                                <i class="fa-{{ $star <= (int) $review->star ? 'solid' : 'regular' }} fa-star" aria-hidden="true"></i>
                            @endfor
                        </div>

                        <p class="mt-4 line-clamp-5 text-sm leading-6 text-slate-600">{{ $review->description }}</p>
                    </article>
                @empty
                    <div class="w-full rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm font-semibold text-slate-600">
                        Customer reviews are currently unavailable.
                    </div>
                @endforelse
            </div>
        </section>

        {{-- Popular route products --}}
        @if (filled($products))
            <section aria-labelledby="popular-routes-heading">
                <div class="mb-7">
                    <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-sky-600">Explore more journeys</p>
                    <h2 id="popular-routes-heading" class="mt-2 text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">
                        More Popular Routes
                    </h2>
                    <p class="mt-2 text-sm text-slate-600">Discover frequently booked routes across India.</p>
                </div>

                <div class="flex snap-x snap-mandatory gap-4 overflow-x-auto pb-3">
                    @foreach ($products as $product)
                        @php
                            $productImages = is_array($product->images)
                                ? $product->images
                                : (json_decode($product->images ?? '[]', true) ?: []);
                            $firstProductImage = $productImages[0] ?? null;
                            $productImageUrl = filled($firstProductImage)
                                ? url('storage/' . ltrim($firstProductImage, '/'))
                                : asset('img/placeholder/car-category.webp');
                        @endphp

                        <article wire:key="popular-route-{{ $product->id }}" class="group w-[82%] shrink-0 snap-start overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-lg sm:w-[47%] lg:w-[31%] xl:w-[24%]">
                            <a href="{{ url('/route/' . ltrim($product->slug, '/')) }}" class="block">
                                <div class="relative aspect-[16/10] overflow-hidden bg-slate-100">
                                    <img
                                        src="{{ $productImageUrl }}"
                                        alt="{{ $product->name }}"
                                        loading="lazy"
                                        decoding="async"
                                        class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                        onerror="this.onerror=null;this.src='{{ asset('img/placeholder/car-category.webp') }}';"
                                    >
                                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/70 via-transparent to-transparent"></div>
                                    <span class="absolute left-3 top-3 rounded-full bg-white/95 px-3 py-1.5 text-[10px] font-extrabold uppercase text-sky-700 shadow-sm">
                                        Popular Route
                                    </span>
                                </div>
                                <div class="flex items-center justify-between gap-3 p-4">
                                    <h3 class="line-clamp-2 font-black text-slate-900">{{ $product->name }}</h3>
                                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-sky-50 text-sky-600 transition group-hover:bg-sky-500 group-hover:text-white">
                                        <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                                    </span>
                                </div>
                            </a>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Why choose us details --}}
        @if ((int) $page->brand_id !== 112)
            <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm sm:p-8" aria-labelledby="why-heading">
                <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-sky-600">Trusted local travel partner</p>
                <h2 id="why-heading" class="mt-2 text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">
                    Why Choose Duracabs in {{ $brandName }}?
                </h2>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    @foreach([
                        ['Excellent cleanliness', 'Clean and well-maintained cars are provided for a comfortable journey.'],
                        ['Sanitised vehicles', 'Vehicles are cleaned and sanitised regularly before customer trips.'],
                        ['Quick pickup and drop', 'Door-to-door service helps you travel from your preferred location.'],
                        ['Wide vehicle choice', 'Choose hatchbacks, sedans, SUVs and larger vehicles according to your group and budget.'],
                        ['Experienced chauffeurs', 'Professional drivers help make intercity, local and sightseeing journeys smoother.'],
                        ['24×7 customer support', 'Contact our team whenever you need booking or trip assistance.'],
                    ] as [$title, $text])
                        <div class="flex gap-3 rounded-2xl bg-slate-50 p-4">
                            <span class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-600">
                                <i class="fa-solid fa-check text-xs" aria-hidden="true"></i>
                            </span>
                            <div>
                                <h3 class="font-black text-slate-900">{{ $title }}</h3>
                                <p class="mt-1 text-sm leading-6 text-slate-600">{{ $text }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- FAQs --}}
        <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm sm:p-8" aria-labelledby="faq-heading">
            <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-sky-600">Need help?</p>
            <h2 id="faq-heading" class="mt-2 text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">
                Frequently Asked Questions
            </h2>

            <div class="mt-6 divide-y divide-slate-200">
                @foreach([
                    ['What is the minimum taxi price in ' . $pageName . '?', 'The final charge depends on the selected vehicle, route, date and trip type. Current fare details are shown during the booking process.'],
                    ['How can I book a taxi in ' . $pageName . '?', 'Use the search form on this page, select your service and travel details, then continue with mobile verification and booking.'],
                    ['Which documents are required?', 'Driver-based taxi bookings generally require customer contact details. Self-drive bookings may require a valid driving licence and identity documents.'],
                    ['Can I extend, cancel or modify my booking?', 'Booking changes depend on availability and the applicable cancellation or modification policy. Contact customer support for assistance.'],
                    ['Is a security deposit required?', 'A refundable security deposit may apply to self-drive vehicles. The applicable amount is displayed with the vehicle and booking details.'],
                ] as $index => [$question, $answer])
                    <details class="group py-4">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-bold text-slate-900 focus:outline-none">
                            <span>{{ $index + 1 }}. {{ $question }}</span>
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-sky-50 text-sky-600 transition group-open:rotate-180">
                                <i class="fa-solid fa-chevron-down text-xs" aria-hidden="true"></i>
                            </span>
                        </summary>
                        <p class="pr-10 pt-3 text-sm leading-6 text-slate-600">{{ $answer }}</p>
                    </details>
                @endforeach
            </div>
        </section>

        {{-- Serviceable cities --}}
        @if (filled($page->links))
            <section aria-labelledby="cities-heading">
                <div class="mb-6">
                    <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-sky-600">Nearby and connected locations</p>
                    <h2 id="cities-heading" class="mt-2 text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">
                        Serviceable Cities
                    </h2>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                    @foreach ($page->links as $item)
                        <a
                            wire:key="service-city-{{ $loop->index }}"
                            href="{{ data_get($item, 'url', '#') }}"
                            class="group flex min-h-14 items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 font-bold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-sky-300 hover:text-sky-700 hover:shadow-md"
                        >
                            <span>{{ data_get($item, 'name') }}</span>
                            <i class="fa-solid fa-arrow-right text-xs text-sky-500 transition group-hover:translate-x-1" aria-hidden="true"></i>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </main>

    {{-- Review modal --}}
    @if ($showReview)
        @teleport('body')
            <div class="fixed inset-0 z-[999999] flex items-center justify-center overflow-y-auto bg-slate-950/75 px-4 py-8 backdrop-blur-sm" wire:click.self="reviewFunction(false)">
                <div class="relative w-full max-w-lg rounded-[28px] border border-slate-200 bg-white p-5 shadow-2xl sm:p-8">
                    <button type="button" wire:click="reviewFunction(false)" class="absolute right-4 top-4 grid h-10 w-10 place-items-center rounded-full border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-900" aria-label="Close review form">×</button>

                    <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-sky-600">Share your experience</p>
                    <h2 class="mt-2 text-2xl font-black text-slate-900">Submit Review</h2>

                    <form wire:submit="submitReview" autocomplete="off" class="mt-6 space-y-4">
                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">Rating</label>
                            <div class="flex gap-2">
                                @for ($star = 1; $star <= 5; $star++)
                                    <button type="button" wire:click="changeStarValue({{ $star }})" class="text-2xl {{ $reviwerStar >= $star ? 'text-amber-400' : 'text-slate-300' }}" aria-label="{{ $star }} star rating">
                                        <i class="fa-solid fa-star" aria-hidden="true"></i>
                                    </button>
                                @endfor
                            </div>
                        </div>

                        <div>
                            <label for="review-name" class="mb-2 block text-sm font-bold text-slate-700">Name</label>
                            <input id="review-name" type="text" wire:model="name" class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold outline-none transition focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100">
                            @error('name') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="review-designation" class="mb-2 block text-sm font-bold text-slate-700">Designation</label>
                            <input id="review-designation" type="text" wire:model="designation" class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold outline-none transition focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100">
                            @error('designation') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="review-description" class="mb-2 block text-sm font-bold text-slate-700">Review</label>
                            <textarea id="review-description" wire:model="description" rows="5" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold outline-none transition focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100"></textarea>
                            @error('description') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <button type="submit" wire:loading.attr="disabled" wire:target="submitReview" class="flex h-12 w-full items-center justify-center rounded-xl bg-sky-500 px-5 text-sm font-extrabold uppercase text-white shadow-lg shadow-sky-500/20 transition hover:bg-sky-600 disabled:cursor-not-allowed disabled:opacity-60">
                            <span wire:loading.remove wire:target="submitReview">Submit Review</span>
                            <span wire:loading wire:target="submitReview">Submitting...</span>
                        </button>
                    </form>
                </div>
            </div>
        @endteleport
    @endif

    {{-- Edit query modal --}}
    @if ($showEditModal)
        @teleport('body')
            <div class="fixed inset-0 z-[999999] flex items-center justify-center overflow-y-auto bg-slate-950/75 px-4 py-8 backdrop-blur-sm" wire:click.self="$set('showEditModal', false)">
                <div class="relative w-full max-w-5xl rounded-[28px] border border-slate-200 bg-white p-5 shadow-2xl sm:p-8">
                    <button type="button" wire:click="$set('showEditModal', false)" class="absolute right-4 top-4 grid h-10 w-10 place-items-center rounded-full border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-900" aria-label="Close edit search">×</button>

                    <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-sky-600">Update journey</p>
                    <h2 class="mt-2 text-2xl font-black text-slate-900">Edit Your Search</h2>

                    <div class="mt-6 grid grid-cols-2 overflow-hidden rounded-2xl border border-slate-200 md:grid-cols-4">
                        @foreach([
                            'one_way' => 'One Way',
                            'return' => 'Round Trip',
                            'local' => 'Local',
                            'self_drive' => 'Self Drive',
                        ] as $editTab => $editLabel)
                            <button
                                type="button"
                                wire:click="changeEditTab('{{ $editTab }}')"
                                class="min-h-12 border-b border-r border-slate-200 px-3 text-xs font-extrabold uppercase transition md:border-b-0 {{ $edit_ride_type === $editTab ? 'bg-sky-500 text-white' : 'bg-white text-slate-700 hover:bg-slate-50' }}"
                            >
                                {{ $editLabel }}
                            </button>
                        @endforeach
                    </div>

                    <div class="mt-6">
                        @if ($edit_ride_type === 'one_way')
                            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                                <div class="relative">
                                    <label class="mb-2 block text-sm font-bold text-slate-700">From City</label>
                                    <input type="text" wire:model.live="edit_query_search" class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold outline-none focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100">
                                    @if (!empty($edit_query_search) && !empty($edit_cities_from))
                                        <div class="absolute z-30 mt-2 max-h-48 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 shadow-xl">
                                            @foreach ($edit_cities_from as $city)
                                                <button type="button" wire:click="editUpdate1(@js($city['name']), {{ $city['id'] }})" class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-sky-50">{{ $city['name'] }}</button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <div class="relative">
                                    <label class="mb-2 block text-sm font-bold text-slate-700">To City</label>
                                    <input type="text" wire:model.live="edit_query2_search" class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold outline-none focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100">
                                    @if (!empty($edit_query2_search) && !empty($edit_cities_to))
                                        <div class="absolute z-30 mt-2 max-h-48 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 shadow-xl">
                                            @foreach ($edit_cities_to as $city)
                                                <button type="button" wire:click="editUpdate2(@js($city['name']), {{ $city['id'] }})" class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-sky-50">{{ $city['name'] }}</button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-700">Pickup Date</label>
                                    <input type="date" wire:model="edit_date" min="{{ date('Y-m-d') }}" class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold outline-none focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-700">Pickup Time</label>
                                    <input type="time" wire:model="edit_time" class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold outline-none focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100">
                                </div>
                            </div>
                        @elseif ($edit_ride_type === 'return')
                            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                                <div class="relative">
                                    <label class="mb-2 block text-sm font-bold text-slate-700">From City</label>
                                    <input type="text" wire:model.live="edit_queryFrom_search" class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold outline-none focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100">
                                    @if (!empty($edit_queryFrom_search) && !empty($edit_dataFrom))
                                        <div class="absolute z-30 mt-2 max-h-48 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 shadow-xl">
                                            @foreach ($edit_dataFrom as $prediction)
                                                <button type="button" wire:click="editUpdateCityFrom(@js($prediction['description']))" class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-sky-50">{{ $prediction['description'] }}</button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <div class="relative">
                                    <label class="mb-2 block text-sm font-bold text-slate-700">To City</label>
                                    <input type="text" wire:model.live="edit_queryTo_search" class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold outline-none focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100">
                                    @if (!empty($edit_queryTo_search) && !empty($edit_dataTo))
                                        <div class="absolute z-30 mt-2 max-h-48 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 shadow-xl">
                                            @foreach ($edit_dataTo as $prediction)
                                                <button type="button" wire:click="editUpdateCityTo(@js($prediction['description']))" class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-sky-50">{{ $prediction['description'] }}</button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <div><label class="mb-2 block text-sm font-bold text-slate-700">Pickup Date</label><input type="date" wire:model="edit_date" min="{{ date('Y-m-d') }}" class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold outline-none focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100"></div>
                                <div><label class="mb-2 block text-sm font-bold text-slate-700">Return Date</label><input type="date" wire:model="edit_dateto" min="{{ date('Y-m-d') }}" class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold outline-none focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100"></div>
                                <div><label class="mb-2 block text-sm font-bold text-slate-700">Pickup Time</label><input type="time" wire:model="edit_time" class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold outline-none focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100"></div>
                            </div>
                        @elseif ($edit_ride_type === 'local')
                            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                                <div class="relative">
                                    <label class="mb-2 block text-sm font-bold text-slate-700">Pickup City</label>
                                    <input type="text" wire:model.live="edit_queryLocal" class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold outline-none focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100">
                                    @if (!empty($edit_queryLocal) && !empty($edit_cities_from))
                                        <div class="absolute z-30 mt-2 max-h-48 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 shadow-xl">
                                            @foreach ($edit_cities_from as $city)
                                                <button type="button" wire:click="editUpdate3(@js($city['name']), {{ $city['id'] }})" class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-sky-50">{{ $city['name'] }}</button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-700">Rental Plan</label>
                                    <select wire:model="edit_plan" class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold outline-none focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100">
                                        <option value="4 Hour / 40 Km">4 Hour / 40 Km</option>
                                        <option value="8 Hour / 80 Km">8 Hour / 80 Km</option>
                                        <option value="12 Hour / 120 Km">12 Hour / 120 Km</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-700">Cars</label>
                                    <input type="number" wire:model="edit_cars" min="1" class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold outline-none focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100">
                                </div>
                                <div><label class="mb-2 block text-sm font-bold text-slate-700">Pickup Date</label><input type="date" wire:model="edit_date" min="{{ date('Y-m-d') }}" class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold outline-none focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100"></div>
                                <div><label class="mb-2 block text-sm font-bold text-slate-700">Pickup Time</label><input type="time" wire:model="edit_time" class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold outline-none focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100"></div>
                            </div>
                        @else
                            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                                <div class="relative">
                                    <label class="mb-2 block text-sm font-bold text-slate-700">Pickup City</label>
                                    <input type="text" wire:model.live="edit_querySelfDrive" class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold outline-none focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100">
                                    @if (!empty($edit_querySelfDrive) && !empty($edit_cities_from))
                                        <div class="absolute z-30 mt-2 max-h-48 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 shadow-xl">
                                            @foreach ($edit_cities_from as $city)
                                                <button type="button" wire:click="editUpdate4(@js($city['name']), {{ $city['id'] }})" class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-sky-50">{{ $city['name'] }}</button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <div><label class="mb-2 block text-sm font-bold text-slate-700">Pickup Date</label><input type="date" wire:model="edit_date" min="{{ date('Y-m-d') }}" class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold outline-none focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100"></div>
                                <div><label class="mb-2 block text-sm font-bold text-slate-700">Return Date</label><input type="date" wire:model="edit_dateto" min="{{ date('Y-m-d') }}" class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold outline-none focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100"></div>
                                <div><label class="mb-2 block text-sm font-bold text-slate-700">Pickup Time</label><input type="time" wire:model="edit_time" class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold outline-none focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100"></div>
                                <div><label class="mb-2 block text-sm font-bold text-slate-700">End Time</label><input type="time" wire:model="edit_endTime" class="h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm font-semibold outline-none focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100"></div>
                            </div>
                        @endif
                    </div>

                    <div class="mt-7 flex justify-end gap-3">
                        <button type="button" wire:click="$set('showEditModal', false)" class="h-12 rounded-xl border border-slate-200 bg-white px-5 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Cancel</button>
                        <button type="button" wire:click="updateQuery" wire:loading.attr="disabled" wire:target="updateQuery" class="h-12 rounded-xl bg-sky-500 px-6 text-sm font-extrabold uppercase text-white shadow-lg shadow-sky-500/20 transition hover:bg-sky-600 disabled:cursor-not-allowed disabled:opacity-60">
                            <span wire:loading.remove wire:target="updateQuery">Update Search</span>
                            <span wire:loading wire:target="updateQuery">Searching...</span>
                        </button>
                    </div>
                </div>
            </div>
        @endteleport
    @endif
</div>