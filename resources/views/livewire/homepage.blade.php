<div class="background-options premium-home premium-motion-page">
    @php
        $homepageSeo = $seoPage ?? null;

        $homepageTitle = filled($homepageSeo?->meta_title)
            ? $homepageSeo->meta_title
            : 'Premium Cab & Taxi Service in India| Book 24/7 | One Way Cab';

        $homepageDescription = filled($homepageSeo?->meta_description)
            ? $homepageSeo->meta_description
            : 'Book safe & cheap cabs across India with Duracabs. 24/7 online taxi service for airport drop services and more. Trusted by thousands of happy customers!';

        $homepageImage = 'https://www.duracabs.com/img/logo/duracabs_logo.jpeg';

        if (filled($homepageSeo?->image)) {
            $homepageImage = str_starts_with($homepageSeo->image, 'http://')
                || str_starts_with($homepageSeo->image, 'https://')
                    ? $homepageSeo->image
                    : asset('storage/' . ltrim($homepageSeo->image, '/'));
        }
    @endphp

    @section('title', $homepageTitle)
    @section('description', $homepageDescription)
    @section('image', $homepageImage)

    {{-- Hero + booking search --}}
    <section class="premium-home-hero premium-reveal premium-reveal-hero is-visible w-full px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
    <div class="mx-auto max-w-[85rem]">
        <h1 class="premium-hero-title premium-hero-animate hidden text-center font-bold text-white lg:block lg:text-4xl lg:leading-tight">
            Book Outstation Taxi, One Way Cab, Car Rentals Online -
            <span class="text-white">Duracabs</span>
        </h1>

        <div class="mt-0 lg:mt-7">
            <livewire:service-search-panel
                default-tab="one_way"
                :key="'homepage-service-search-panel'"
            />
        </div>
    </div>
</section>

    {{-- Main homepage flow --}}
    <div class="mx-auto w-full max-w-[90rem] px-3 sm:px-5 lg:px-8">
        {{-- Quick actions --}}
        <nav
            class="premium-quick-actions premium-reveal relative z-10 mt-6 grid grid-cols-2 overflow-hidden rounded-2xl bg-white text-center text-sm font-medium text-gray-700 shadow-lg lg:mt-8 lg:grid-cols-4"
            aria-label="Quick actions"
        >
            <a
                href="/vendor-register"
                class="flex h-16 min-w-0 items-center justify-center gap-2 whitespace-nowrap border-b border-r border-gray-200 px-2 text-sm font-semibold text-gray-900 transition hover:bg-sky-50 sm:px-3 sm:text-base lg:border-b-0"
            >
                <i class="fa-solid fa-taxi shrink-0 text-sky-500" aria-hidden="true"></i>
                <span class="truncate">Attach Taxi</span>
            </a>

            <a
                href="/terms-and-conditions#cancellation"
                class="flex h-16 min-w-0 items-center justify-center gap-2 whitespace-nowrap border-b border-gray-200 px-2 text-sm font-semibold text-gray-900 transition hover:bg-sky-50 sm:px-3 sm:text-base lg:border-b-0 lg:border-r"
            >
                <i class="fa-solid fa-ban shrink-0 text-sky-500" aria-hidden="true"></i>
                <span class="truncate">Cancel Ticket</span>
            </a>

            <a
                href="/terms-and-conditions#cancellation"
                class="flex h-16 min-w-0 items-center justify-center gap-2 whitespace-nowrap border-r border-gray-200 px-2 text-sm font-semibold text-gray-900 transition hover:bg-sky-50 sm:px-3 sm:text-base"
            >
                <i class="fa-solid fa-ticket shrink-0 text-sky-500" aria-hidden="true"></i>
                <span class="truncate">Refund Status</span>
            </a>

            <a
                href="https://api.whatsapp.com/send/?phone=917088873331&text=hi%20i%20want%20to%20book%20a%20ride&type=phone_number&app_absent=0"
                target="_blank"
                rel="noopener noreferrer"
                class="flex h-16 min-w-0 items-center justify-center gap-2 whitespace-nowrap px-2 text-sm font-semibold text-gray-900 transition hover:bg-sky-50 sm:px-3 sm:text-base"
            >
                <i class="fa-solid fa-suitcase-rolling shrink-0 text-sky-500" aria-hidden="true"></i>
                <span class="truncate">Plan Your Tour</span>
            </a>
        </nav>

        {{-- Banner service filter tabs --}}
        <div
            x-data="{ activeBannerTab: @js($bannerTab ?? 'one_way'), changingBanner: false }"
            x-on:banner-filter-finished.window="changingBanner = false"
            class="relative z-10"
        >
            <ul
                class="premium-banner-tabs premium-reveal mt-8 flex overflow-hidden rounded-2xl bg-white text-center text-gray-500 shadow-lg lg:mt-10"
                role="tablist"
                aria-label="Filter homepage banners by service"
            >
                @foreach([
                    'one_way' => 'One Way',
                    'return' => 'Round Trip',
                    'local' => 'Local',
                    'self_drive' => 'Self Drive',
                ] as $tabValue => $tabLabel)
                    <li class="min-w-0 flex-1" role="presentation">
                        <button
                            type="button"
                            role="tab"
                            wire:key="homepage-banner-filter-{{ $tabValue }}"
                            x-on:click="
                                if (!changingBanner && activeBannerTab !== @js($tabValue)) {
                                    changingBanner = true;
                                    activeBannerTab = @js($tabValue);

                                    $wire.call('changeBanner', @js($tabValue))
                                        .finally(() => changingBanner = false);
                                }
                            "
                            x-bind:aria-selected="activeBannerTab === @js($tabValue)"
                            x-bind:disabled="changingBanner"
                            x-bind:class="activeBannerTab === @js($tabValue)
                                ? 'bg-sky-50 text-sky-600'
                                : 'bg-white text-gray-700 hover:bg-gray-50'"
                            class="flex min-h-14 w-full items-center justify-center border-r border-gray-200 px-2 py-3 text-xs font-extrabold uppercase transition disabled:cursor-wait disabled:opacity-70 sm:text-sm"
                        >
                            <span>{{ $tabLabel }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>

            <div
                wire:key="homepage-banner-results-{{ $bannerTab ?? 'one_way' }}"
                class="mt-6 lg:mt-8"
            >
                <x-home.premium-banner-only
                    :smart-hero-banners="$smartHeroBanners ?? []"
                    :carousel="$carousel ?? []"
                    :banner-tab="$bannerTab ?? 'one_way'"
                />
            </div>
        </div>
    </div>

<section
    class="dura-section overflow-hidden bg-white"
    aria-labelledby="travel-destinations-heading"
>
    <div class="dura-container">

        {{-- Section heading --}}
        <div class="mb-8 flex flex-col gap-5 sm:mb-10 sm:flex-row sm:items-end sm:justify-between">

            <div>
                <p class="mb-2 text-sm font-bold uppercase tracking-wider text-dura-600">
                    Discover India
                </p>

                <h2
                    id="travel-destinations-heading"
                    class="max-w-3xl text-2xl font-bold tracking-tight text-slate-900
                           sm:text-3xl lg:text-4xl"
                >
                    Top Travel Destinations to Explore in India
                </h2>

                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
                    Explore popular cities, heritage destinations and tourist places
                    with reliable Duracabs taxi services.
                </p>
            </div>

            {{-- Desktop navigation --}}
            <div
                class="hidden items-center gap-2 md:flex"
                aria-label="Travel destination navigation"
            >
                <button
                    type="button"
                    x-on:click="scrollTo(prev)"
                    x-bind:disabled="prev === null"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-full
                           border border-slate-200 bg-white text-slate-700 shadow-dura-sm
                           transition hover:border-dura-200 hover:bg-dura-50
                           hover:text-dura-700
                           disabled:cursor-not-allowed disabled:opacity-40"
                    aria-label="Show previous destinations"
                >
                    <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                </button>

                <button
                    type="button"
                    x-on:click="scrollTo(next)"
                    x-bind:disabled="next === null"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-full
                           border border-slate-200 bg-white text-slate-700 shadow-dura-sm
                           transition hover:border-dura-200 hover:bg-dura-50
                           hover:text-dura-700
                           disabled:cursor-not-allowed disabled:opacity-40"
                    aria-label="Show next destinations"
                >
                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        {{-- Destination carousel --}}
        <div
            x-data="carousel()"
            x-init="init()"
            class="relative"
        >
            <div
                x-ref="container"
                class="no-scrollbar scroll-snap-x flex gap-4 overflow-x-auto
                       pb-4 scroll-smooth sm:gap-5"
                role="list"
                aria-label="Popular travel destinations"
            >
                @forelse ($brands as $brand)

                    @php
                        $destinationUrl = filled($brand->slug)
                            ? url('/' . ltrim($brand->slug, '/'))
                            : url('/');

                        $destinationImage = filled($brand->image)
                            ? url('storage/' . ltrim($brand->image, '/'))
                            : asset('img/home/banner.webp');
                    @endphp

                    <article
                        wire:key="homepage-destination-{{ $brand->id }}"
                        role="listitem"
                        class="dura-card group snap-start w-[82%] shrink-0 overflow-hidden
                               sm:w-[47%] lg:w-[31%] xl:w-[24%]"
                    >
                        <a
                            href="{{ $destinationUrl }}"
                            class="block"
                            aria-label="Explore taxi services in {{ $brand->name }}"
                        >
                            <div class="relative aspect-[4/3] overflow-hidden bg-slate-100">

                                <img
                                    src="{{ $destinationImage }}"
                                    alt="Taxi and cab service in {{ $brand->name }}"
                                    loading="lazy"
                                    decoding="async"
                                    width="480"
                                    height="360"
                                    class="h-full w-full object-cover transition duration-500
                                           group-hover:scale-105"
                                >

                                <div
                                    class="absolute inset-0 bg-gradient-to-t
                                           from-slate-950/80 via-slate-950/15 to-transparent"
                                    aria-hidden="true"
                                ></div>

                                <span
                                    class="absolute left-3 top-3 inline-flex items-center gap-1.5
                                           rounded-full bg-white/95 px-3 py-1.5 text-xs
                                           font-bold text-dura-700 shadow-dura-sm"
                                >
                                    <i
                                        class="fa-solid fa-location-dot"
                                        aria-hidden="true"
                                    ></i>

                                    Top Destination
                                </span>

                                <div class="absolute inset-x-0 bottom-0 p-4 sm:p-5">

                                    <h3
                                        class="line-clamp-2 text-lg font-bold leading-6 text-white
                                               sm:text-xl"
                                    >
                                        {{ $brand->name }}
                                    </h3>

                                    <span
                                        class="mt-2 inline-flex items-center gap-2 text-sm
                                               font-semibold text-white/90"
                                    >
                                        Explore destination

                                        <i
                                            class="fa-solid fa-arrow-right text-xs
                                                   transition-transform duration-200
                                                   group-hover:translate-x-1"
                                            aria-hidden="true"
                                        ></i>
                                    </span>

                                </div>
                            </div>
                        </a>
                    </article>

                @empty

                    <div
                        class="w-full rounded-dura-lg border border-dashed
                               border-slate-300 bg-slate-50 p-8 text-center"
                    >
                        <p class="font-semibold text-slate-700">
                            Travel destinations are currently unavailable.
                        </p>
                    </div>

                @endforelse
            </div>

            {{-- Mobile navigation --}}
            <div class="mt-4 flex justify-center gap-3 md:hidden">

                <button
                    type="button"
                    x-on:click="scrollTo(prev)"
                    x-bind:disabled="prev === null"
                    class="inline-flex h-11 min-w-28 items-center justify-center gap-2
                           rounded-dura border border-slate-200 bg-white px-4
                           text-sm font-bold text-slate-700 shadow-dura-sm
                           disabled:cursor-not-allowed disabled:opacity-40"
                >
                    <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                    Previous
                </button>

                <button
                    type="button"
                    x-on:click="scrollTo(next)"
                    x-bind:disabled="next === null"
                    class="inline-flex h-11 min-w-28 items-center justify-center gap-2
                           rounded-dura border border-slate-200 bg-white px-4
                           text-sm font-bold text-slate-700 shadow-dura-sm
                           disabled:cursor-not-allowed disabled:opacity-40"
                >
                    Next
                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </button>

            </div>
        </div>

    </div>
</section>

<img class="premium-wide-banner premium-reveal mx-auto mt-14 w-[calc(100%-1.5rem)] max-w-[90rem] lg:mt-16" src="/img/home/banner.webp" width="100%" alt="Duracabs – Online Cab Booking for One Way, Round Trip, and Local Taxi Services in India" title="Book One Way, Round Trip, and Local Taxis Online with Duracabs" />
<div class="premium-about-card premium-reveal mx-auto mt-12 w-[calc(100%-1.5rem)] max-w-6xl bg-white p-5 sm:p-8 lg:mt-16">
  <section id="about-duracabs" class="seo-content">
    <h2><strong>Duracabs – India's Trusted Self Drive Car Rental Services</strong></h2>
    <br />
    <div id="content" data-home-read-more-content class="premium-seo-content is-collapsed overflow-hidden transition-all duration-500 ease-in-out">
      <p>Welcome to <strong>Duracabs</strong>, your trustworthy partner for self-driving car rentals in Agra, Mathura, Vrindavan, Bharatpur, Bulandshahr, Palwal, and Bhiwani. We believe that driving through India's endless landscapes is the best way to truly experience its beauty and culture. You can drive anywhere and at any time when you use Duracabs.</p>
        <br>
        <p>There are no drivers or rules, just the thrill of driving. Our clean, comfortable, and well-kept self-drive cars are ready to take you anywhere you want to go, whether it's a weekend trip with friends, a family vacation, a business meeting, or a spiritual tour.</p>
        <br>
        <p>We are a car rental company that offers chauffeur-driven car rentals in more than 50 cities in India. We offer intercity (one-way and round-trip) rentals, local rentals, and airport transfers. In terms of geographic coverage, we are the largest chauffeur-driven car rental company in India during the past ten years.</p>
        <br>
        <p>Here at <strong>Duracabs</strong>, we try to give our customers the most memorable road trips possible. It is our responsibility to ensure that your Journey is safe, easy, and memorable; we understand that each visitor desires convenience and flexibility. Day, Weekly, or monthly rentals are available for our extensive fleet of cars, which includes hatchbacks, sedans, SUVs and luxury vehicles. Before every trip, every vehicle is cleaned and serviced so you can be confident it is safe and you can relax. With our clear pricing, unlimited kilometers and 24/7 roadside support, you can relax and enjoy the road without worrying about extra expenses or breakdowns.</p>
        <br>
        <p>Duracabs is more than simply a car rental service; it's a way for you to explore. You may now <strong>rent a car in Agra, Mathura, or Vrindavan</strong> and take unexpected detours to see hidden places, eat local food, or just enjoy the beautiful scenery along the road. Our cars are your ticket to see everything, from the historic streets of Agra to the holy temples of Mathura and the spiritual beauty of Vrindavan. We offer rent cars without drivers in Bharatpur, Bulandshahr, Palwal, Bhiwani, and other adjacent cities, which gives you complete flexibility to move across regions. whether you are from India or come to india to explore the beuty  from out of india or foreign country.</p>
        <br>
        <p>We also offer trustworthy chauffeur-driven taxi services for people who would prefer not to drive. These include local city rides, airport transfers, railway station pickups, and one-way taxis like Agra to Delhi or Delhi to Vrindavan. We can even set up Tempo Travelers for big groups, so everyone on board will be comfortable. Duracabs gives you a chance to build your business by letting you register as a cab or taxi vendor on our platform. This gives you access to daily customer reservations and dedicated assistance.</p>
        <br>
        <p>Planning your trip has never been easier than with Duracabs. You can book your car directly from our website,<a href="https://www.duracabs.com/"></a>, or you can phone our trip experts at 7088873331 to talk about your plans. We are here to make sure that your trip is smooth, pleasant, and memorable from the minute you pick up your automobile until the time you return it. We don't just rent cars at Duracabs; we make trips that you'll never forget.</p>
        <br>
        <h2><strong>We have a taxi for you wherever you're going.</strong></h2>
        <p>Planning a weekend trip? You can visit all the must-see locations, sample the best local cuisine, and discover the greatest destinations with the aid of our out-of-town taxi services. Did you simply touch down at the train or airport nearest to your final destination? No issue! For the final mile, you can take advantage of our transit pick-up service and airport taxi. Along the way, we'll show you some of the most breathtaking sights while we get you to your destination. Are you going home for a family reunion? Try our recently launched one-way taxi services; for a one-side fare, you can be dropped off in your hometown regardless of where you live. Have you made the decision to explore your city for the entire day on a personal day? You can visit some of the city's most impressive monuments, the greenest parks, and the oldest temples with the aid of our local taxi packages. An empty itinerary won't ever be a concern for you again. Are you a unique traveler? Do you simply decide to take it from there and hit the road? If you simply want to be dropped off somewhere and don't want to look back, we provide one-way drops on a number of routes.</p>
        <h2><strong>About Duracabs</strong></h2>
        <p>At Duracabs, we make finding a <strong>self drive car rental near you</strong> simpler than ever, with our seamless booking platform offering a wide fleet of cars across Agra, Mathura, Vrindavan, Bharatpur, Palwal, Bhiwani and other nearby cities. Whether you are searching for a <strong>self drive car near me</strong> for a quick weekend escape or planning an intercity ride like <strong>Agra to Mathura taxi</strong> or <strong>Jaipur to Agra</strong> road trips, we have the perfect vehicle ready for you. Our self drive cars are available round-the-clock, making us your trusted <strong>24/7 taxi service</strong> and <strong>car rental service in Agra</strong>. You can browse through car photos while selecting your ideal <strong>self drive car hire in Agra</strong> or book cars with drivers for one-way routes like <strong>Jaipur to Delhi</strong> or <strong>Agra to Delhi</strong>. For those looking for flexible business opportunities, Duracabs also offers easy <strong>car rental vendor registration online</strong>, allowing local cab and taxi owners to join our platform and get regular bookings. DURA Cabs Services is a reliable Car rental Company that offers local taxi services, outstation taxis with drivers, and custom holiday travel packages all around India.</p>
        <br>
        <p>We have everything you need, from renting a <strong>tempo traveler in Agra</strong> for a group trip to <strong>renting a car in Palwal for business travel</strong>, from <strong>self-driving cars in Muzaffarnagar to renting a car in Vrindavan for spiritual holidays.</strong></p>
        <br>
        <p>Our services include <strong>self drive cars near me, rental self drive cars near me, self drive car on rent, car for self drive near me, and car rental without driver</strong>, giving you the freedom to explore at your own pace. Duracabs is your one-stop shop for everything from self-driving car rentals to one-side car rentals and single-side taxi services, offering alternatives for online car booking, car rental shops near me, and car rental services near me. We guarantee to provide you with clean, well-maintained cars, honest pricing, and a smooth booking experience regardless of whether you're searching for a small hatchback, a 4x4 for off-road activities, or a luxury self-driven sedan.</p>
        <h2><strong>Reliable Airport Drop Services for Hassle-Free Travel</strong></h2>
        <p>Our airport drop services are designed to make your journey from your home to the airport as easy and stress-free as possible. From late-night arrivals to early-morning flights, our trustworthy and punctual drivers will bring you to the airport in luxury and on time. By providing you with clean vehicles, experienced drivers and real-time tracking, we prioritize your comfort, security and relief from worry. With our <a href="https://www.duracabs.com/">airport drop services</a>, you can travel without worrying from the start. You can reach us at any time, day or night, and we have transparent pricing and booking options. No more parking, traffic, or last-minute delays.</p>
    </div>
    <div class="text-center mt-4">
      <button id="toggleBtn" type="button" data-home-read-more-button class="premium-secondary-button text-white main-color focus:outline-none focus:ring-4 focus:ring-blue-300 font-semibold rounded-full text-sm px-6 py-3 text-center">Read More</button>
    </div>
  </section>


<section
    id="dura-cabs-services"
    class="dura-section bg-slate-50"
    aria-labelledby="dura-services-heading"
>
    <div class="dura-container">

        {{-- Section heading --}}
        <div class="mx-auto mb-8 max-w-3xl text-center sm:mb-10">

            <p class="mb-2 text-sm font-bold uppercase tracking-wider text-dura-600">
                Our cab services
            </p>

            <h2
                id="dura-services-heading"
                class="text-2xl font-bold tracking-tight text-slate-900
                       sm:text-3xl lg:text-4xl"
            >
                Affordable Taxi and Car Rental Services
            </h2>

            <p class="mt-3 text-sm leading-6 text-slate-600 sm:text-base">
                Book reliable one-way, round-trip, local and self-drive cars
                for business, holidays, airport transfers and everyday travel.
            </p>

        </div>

        {{-- Services grid --}}
        <div class="grid gap-5 md:grid-cols-2">

            {{-- One Way --}}
            <article class="dura-card group flex flex-col overflow-hidden sm:flex-row">

                <div
                    class="flex min-h-44 items-center justify-center bg-dura-50
                           p-6 sm:w-2/5"
                >
                    <img
                        src="{{ asset('cab_images/one_way.webp') }}"
                        alt="One way taxi service by Duracabs"
                        loading="lazy"
                        decoding="async"
                        width="240"
                        height="160"
                        class="max-h-36 w-auto object-contain transition-transform
                               duration-300 group-hover:scale-105"
                    >
                </div>

                <div class="flex flex-1 flex-col p-5 sm:p-6">

                    <div
                        class="mb-4 inline-flex h-11 w-11 items-center justify-center
                               rounded-xl bg-dura-50 text-dura-700"
                    >
                        <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i>
                    </div>

                    <h3 class="text-xl font-bold text-slate-900">
                        One Way Taxi
                    </h3>

                    <p class="mt-3 text-sm leading-6 text-slate-600">
                        Pay only for your journey with affordable intercity,
                        airport drop and long-distance one-way taxi services.
                    </p>

                    <div class="mt-auto pt-5">
                        <a
                            href="{{ url('/rides?tab=one_way') }}"
                            class="inline-flex items-center gap-2 text-sm font-bold
                                   text-dura-700 transition hover:text-dura-800"
                        >
                            Book One Way Cab

                            <i
                                class="fa-solid fa-arrow-right text-xs
                                       transition-transform group-hover:translate-x-1"
                                aria-hidden="true"
                            ></i>
                        </a>
                    </div>

                </div>
            </article>

            {{-- Round Trip --}}
            <article class="dura-card group flex flex-col overflow-hidden sm:flex-row">

                <div
                    class="flex min-h-44 items-center justify-center bg-emerald-50
                           p-6 sm:w-2/5"
                >
                    <img
                        src="{{ asset('cab_images/return.webp') }}"
                        alt="Round trip taxi service by Duracabs"
                        loading="lazy"
                        decoding="async"
                        width="240"
                        height="160"
                        class="max-h-36 w-auto object-contain transition-transform
                               duration-300 group-hover:scale-105"
                    >
                </div>

                <div class="flex flex-1 flex-col p-5 sm:p-6">

                    <div
                        class="mb-4 inline-flex h-11 w-11 items-center justify-center
                               rounded-xl bg-emerald-50 text-emerald-700"
                    >
                        <i class="fa-solid fa-rotate" aria-hidden="true"></i>
                    </div>

                    <h3 class="text-xl font-bold text-slate-900">
                        Round Trip Taxi
                    </h3>

                    <p class="mt-3 text-sm leading-6 text-slate-600">
                        Plan outstation return journeys with reliable drivers,
                        comfortable vehicles and transparent fare details.
                    </p>

                    <div class="mt-auto pt-5">
                        <a
                            href="{{ url('/rides?tab=return') }}"
                            class="inline-flex items-center gap-2 text-sm font-bold
                                   text-dura-700 transition hover:text-dura-800"
                        >
                            Book Round Trip

                            <i
                                class="fa-solid fa-arrow-right text-xs
                                       transition-transform group-hover:translate-x-1"
                                aria-hidden="true"
                            ></i>
                        </a>
                    </div>

                </div>
            </article>

            {{-- Local Taxi --}}
            <article class="dura-card group flex flex-col overflow-hidden sm:flex-row">

                <div
                    class="flex min-h-44 items-center justify-center bg-amber-50
                           p-6 sm:w-2/5"
                >
                    <img
                        src="{{ asset('cab_images/local.webp') }}"
                        alt="Local city taxi service by Duracabs"
                        loading="lazy"
                        decoding="async"
                        width="240"
                        height="160"
                        class="max-h-36 w-auto object-contain transition-transform
                               duration-300 group-hover:scale-105"
                    >
                </div>

                <div class="flex flex-1 flex-col p-5 sm:p-6">

                    <div
                        class="mb-4 inline-flex h-11 w-11 items-center justify-center
                               rounded-xl bg-amber-50 text-amber-700"
                    >
                        <i class="fa-solid fa-city" aria-hidden="true"></i>
                    </div>

                    <h3 class="text-xl font-bold text-slate-900">
                        Local Taxi
                    </h3>

                    <p class="mt-3 text-sm leading-6 text-slate-600">
                        Book city taxis for office travel, shopping, meetings,
                        railway pickups, sightseeing and hourly packages.
                    </p>

                    <div class="mt-auto pt-5">
                        <a
                            href="{{ url('/rides?tab=local') }}"
                            class="inline-flex items-center gap-2 text-sm font-bold
                                   text-dura-700 transition hover:text-dura-800"
                        >
                            Book Local Cab

                            <i
                                class="fa-solid fa-arrow-right text-xs
                                       transition-transform group-hover:translate-x-1"
                                aria-hidden="true"
                            ></i>
                        </a>
                    </div>

                </div>
            </article>

            {{-- Self Drive --}}
            <article class="dura-card group flex flex-col overflow-hidden sm:flex-row">

                <div
                    class="flex min-h-44 items-center justify-center bg-violet-50
                           p-6 sm:w-2/5"
                >
                    <img
                        src="{{ asset('cab_images/self_drive.webp') }}"
                        alt="Self drive car rental by Duracabs"
                        loading="lazy"
                        decoding="async"
                        width="240"
                        height="160"
                        class="max-h-36 w-auto object-contain transition-transform
                               duration-300 group-hover:scale-105"
                    >
                </div>

                <div class="flex flex-1 flex-col p-5 sm:p-6">

                    <div
                        class="mb-4 inline-flex h-11 w-11 items-center justify-center
                               rounded-xl bg-violet-50 text-violet-700"
                    >
                        <i class="fa-solid fa-car-side" aria-hidden="true"></i>
                    </div>

                    <h3 class="text-xl font-bold text-slate-900">
                        Self Drive Car
                    </h3>

                    <p class="mt-3 text-sm leading-6 text-slate-600">
                        Rent clean and well-maintained cars without a driver
                        for flexible daily, weekly and long-distance journeys.
                    </p>

                    <div class="mt-auto pt-5">
                        <a
                            href="{{ url('/rides?tab=self_drive') }}"
                            class="inline-flex items-center gap-2 text-sm font-bold
                                   text-dura-700 transition hover:text-dura-800"
                        >
                            Rent Self Drive Car

                            <i
                                class="fa-solid fa-arrow-right text-xs
                                       transition-transform group-hover:translate-x-1"
                                aria-hidden="true"
                            ></i>
                        </a>
                    </div>

                </div>
            </article>

        </div>

        {{-- Bottom CTA --}}
        <div
            class="mt-8 flex flex-col items-center justify-between gap-4
                   rounded-2xl bg-dura-700 px-5 py-6 text-center
                   sm:flex-row sm:px-8 sm:text-left"
        >
            <div>
                <h3 class="text-lg font-bold text-white sm:text-xl">
                    Not sure which service is right for you?
                </h3>

                <p class="mt-1 text-sm text-white/80">
                    Share your travel plan and our team will help you choose.
                </p>
            </div>

            <a
                href="https://api.whatsapp.com/send/?phone=917088873331&text=Hi%2C%20I%20need%20help%20planning%20a%20cab%20trip&type=phone_number&app_absent=0"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex min-h-11 shrink-0 items-center justify-center
                       gap-2 rounded-full bg-white px-6 py-3 text-sm
                       font-bold text-dura-700 transition hover:bg-slate-100"
            >
                <i class="fa-brands fa-whatsapp text-base" aria-hidden="true"></i>
                Plan Your Trip
            </a>
        </div>

    </div>
</section>

<section
    id="why-dura-cabs"
    class="dura-section bg-white"
    aria-labelledby="why-duracabs-heading"
>
    <div class="dura-container">

        <div class="mx-auto mb-8 max-w-3xl text-center sm:mb-10">

            <p class="mb-2 text-sm font-bold uppercase tracking-wider text-dura-600">
                Travel with confidence
            </p>

            <h2
                id="why-duracabs-heading"
                class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl lg:text-4xl"
            >
                Why Choose DURA Cabs?
            </h2>

            <p class="mt-3 text-sm leading-6 text-slate-600 sm:text-base">
                Reliable support, transparent fares and technology-driven cab
                services for safe and convenient journeys.
            </p>

        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

            <article class="dura-card group p-5 sm:p-6">

                <div
                    class="flex h-14 w-14 items-center justify-center rounded-2xl
                           bg-sky-50 text-sky-700 transition
                           group-hover:bg-sky-600 group-hover:text-white"
                >
                    <img
                        src="{{ asset('img/home/24x7.png') }}"
                        alt=""
                        loading="lazy"
                        decoding="async"
                        width="40"
                        height="40"
                        class="h-9 w-9 object-contain"
                    >
                </div>

                <h3 class="mt-5 text-lg font-bold text-slate-900">
                    24/7 Booking Support
                </h3>

                <p class="mt-3 text-sm leading-6 text-slate-600">
                    Get assistance for local rides, airport taxis and
                    outstation bookings at any time, throughout the year.
                </p>

                <div class="mt-5 flex items-center gap-2 text-sm font-semibold text-sky-700">
                    <i class="fa-solid fa-headset" aria-hidden="true"></i>
                    Always available
                </div>
            </article>

            <article class="dura-card group p-5 sm:p-6">

                <div
                    class="flex h-14 w-14 items-center justify-center rounded-2xl
                           bg-violet-50 text-violet-700 transition
                           group-hover:bg-violet-600 group-hover:text-white"
                >
                    <img
                        src="{{ asset('img/home/advance.png') }}"
                        alt=""
                        loading="lazy"
                        decoding="async"
                        width="40"
                        height="40"
                        class="h-9 w-9 object-contain"
                    >
                </div>

                <h3 class="mt-5 text-lg font-bold text-slate-900">
                    Easy Online Booking
                </h3>

                <p class="mt-3 text-sm leading-6 text-slate-600">
                    Book one-way, round-trip, local and airport cabs through
                    a simple and fast online booking experience.
                </p>

                <div class="mt-5 flex items-center gap-2 text-sm font-semibold text-violet-700">
                    <i class="fa-solid fa-mobile-screen-button" aria-hidden="true"></i>
                    Fast and convenient
                </div>
            </article>

            <article class="dura-card group p-5 sm:p-6">

                <div
                    class="flex h-14 w-14 items-center justify-center rounded-2xl
                           bg-emerald-50 text-emerald-700 transition
                           group-hover:bg-emerald-600 group-hover:text-white"
                >
                    <img
                        src="{{ asset('img/home/low_fixed.webp') }}"
                        alt=""
                        loading="lazy"
                        decoding="async"
                        width="40"
                        height="40"
                        class="h-9 w-9 object-contain"
                    >
                </div>

                <h3 class="mt-5 text-lg font-bold text-slate-900">
                    Transparent Fixed Fares
                </h3>

                <p class="mt-3 text-sm leading-6 text-slate-600">
                    View clear fare information before booking and travel
                    without unexpected or hidden charges.
                </p>

                <div class="mt-5 flex items-center gap-2 text-sm font-semibold text-emerald-700">
                    <i class="fa-solid fa-indian-rupee-sign" aria-hidden="true"></i>
                    No hidden charges
                </div>
            </article>

            <article class="dura-card group p-5 sm:p-6">

                <div
                    class="flex h-14 w-14 items-center justify-center rounded-2xl
                           bg-amber-50 text-amber-700 transition
                           group-hover:bg-amber-500 group-hover:text-white"
                >
                    <img
                        src="{{ asset('img/home/tracking.png') }}"
                        alt=""
                        loading="lazy"
                        decoding="async"
                        width="40"
                        height="40"
                        class="h-9 w-9 object-contain"
                    >
                </div>

                <h3 class="mt-5 text-lg font-bold text-slate-900">
                    Trip Tracking Updates
                </h3>

                <p class="mt-3 text-sm leading-6 text-slate-600">
                    Receive important driver, pickup and journey updates for
                    a safer and more dependable travel experience.
                </p>

                <div class="mt-5 flex items-center gap-2 text-sm font-semibold text-amber-700">
                    <i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i>
                    Stay informed
                </div>
            </article>

        </div>

        <div
            class="mt-8 grid gap-4 rounded-2xl border border-slate-200
                   bg-slate-50 p-5 sm:grid-cols-3 sm:p-6"
        >
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white text-dura-700 shadow-dura-sm">
                    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                </div>

                <div>
                    <p class="font-bold text-slate-900">Safe Journeys</p>
                    <p class="text-xs text-slate-500">Customer-first service</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white text-dura-700 shadow-dura-sm">
                    <i class="fa-solid fa-car" aria-hidden="true"></i>
                </div>

                <div>
                    <p class="font-bold text-slate-900">Multiple Car Options</p>
                    <p class="text-xs text-slate-500">For every trip type</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white text-dura-700 shadow-dura-sm">
                    <i class="fa-solid fa-clock" aria-hidden="true"></i>
                </div>

                <div>
                    <p class="font-bold text-slate-900">Quick Booking</p>
                    <p class="text-xs text-slate-500">Available 24 hours</p>
                </div>
            </div>
        </div>

    </div>
</section>
	
	
</section>
<img src="/img/home/banner2.webp" width="100%" alt="Duracabs Banner" />
<div class="p-2 bg-white mt-3">
    
	
	<section
    id="frequently-asked-questions"
    class="dura-section bg-slate-50"
    aria-labelledby="faq-heading"
>
    <div class="dura-container">

        <div class="mx-auto mb-8 max-w-3xl text-center sm:mb-10">

            <p class="mb-2 text-sm font-bold uppercase tracking-wider text-dura-600">
                Need help?
            </p>

            <h2
                id="faq-heading"
                class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl lg:text-4xl"
            >
                Frequently Asked Questions
            </h2>

            <p class="mt-3 text-sm leading-6 text-slate-600 sm:text-base">
                Find quick answers about cab booking, fares, payments,
                self-drive rentals and vendor registration.
            </p>

        </div>

        <div class="mx-auto max-w-4xl space-y-3">

            <details class="group rounded-2xl border border-slate-200 bg-white shadow-dura-sm">
                <summary
                    class="flex cursor-pointer list-none items-center justify-between gap-4
                           px-5 py-4 text-left font-bold text-slate-900
                           sm:px-6 sm:py-5"
                >
                    <span>What payment methods does DURA Cabs accept?</span>

                    <span
                        class="flex h-8 w-8 shrink-0 items-center justify-center
                               rounded-full bg-dura-50 text-dura-700 transition
                               group-open:rotate-45"
                        aria-hidden="true"
                    >
                        <i class="fa-solid fa-plus text-sm"></i>
                    </span>
                </summary>

                <div class="border-t border-slate-100 px-5 py-4 sm:px-6">
                    <p class="text-sm leading-6 text-slate-600 sm:text-base">
                        We accept credit cards, debit cards, net banking, UPI,
                        supported wallets and cash at pickup where available.
                        Payment options are shown during the booking process.
                    </p>
                </div>
            </details>

            <details class="group rounded-2xl border border-slate-200 bg-white shadow-dura-sm">
                <summary
                    class="flex cursor-pointer list-none items-center justify-between gap-4
                           px-5 py-4 text-left font-bold text-slate-900
                           sm:px-6 sm:py-5"
                >
                    <span>What is included in the cab fare?</span>

                    <span
                        class="flex h-8 w-8 shrink-0 items-center justify-center
                               rounded-full bg-dura-50 text-dura-700 transition
                               group-open:rotate-45"
                        aria-hidden="true"
                    >
                        <i class="fa-solid fa-plus text-sm"></i>
                    </span>
                </summary>

                <div class="border-t border-slate-100 px-5 py-4 sm:px-6">
                    <p class="text-sm leading-6 text-slate-600 sm:text-base">
                        Fare details depend on the selected service and package.
                        The booking summary displays the applicable rental fare,
                        taxes and other included charges before confirmation.
                    </p>
                </div>
            </details>

            <details class="group rounded-2xl border border-slate-200 bg-white shadow-dura-sm">
                <summary
                    class="flex cursor-pointer list-none items-center justify-between gap-4
                           px-5 py-4 text-left font-bold text-slate-900
                           sm:px-6 sm:py-5"
                >
                    <span>Can I pay at the pickup location?</span>

                    <span
                        class="flex h-8 w-8 shrink-0 items-center justify-center
                               rounded-full bg-dura-50 text-dura-700 transition
                               group-open:rotate-45"
                        aria-hidden="true"
                    >
                        <i class="fa-solid fa-plus text-sm"></i>
                    </span>
                </summary>

                <div class="border-t border-slate-100 px-5 py-4 sm:px-6">
                    <p class="text-sm leading-6 text-slate-600 sm:text-base">
                        Yes, when cash payment is available for the selected
                        booking, you can choose that option and pay at pickup.
                    </p>
                </div>
            </details>

            <details class="group rounded-2xl border border-slate-200 bg-white shadow-dura-sm">
                <summary
                    class="flex cursor-pointer list-none items-center justify-between gap-4
                           px-5 py-4 text-left font-bold text-slate-900
                           sm:px-6 sm:py-5"
                >
                    <span>Who pays tolls, parking and state permit charges?</span>

                    <span
                        class="flex h-8 w-8 shrink-0 items-center justify-center
                               rounded-full bg-dura-50 text-dura-700 transition
                               group-open:rotate-45"
                        aria-hidden="true"
                    >
                        <i class="fa-solid fa-plus text-sm"></i>
                    </span>
                </summary>

                <div class="border-t border-slate-100 px-5 py-4 sm:px-6">
                    <p class="text-sm leading-6 text-slate-600 sm:text-base">
                        These charges are generally paid by the customer unless
                        they are specifically included in the selected package.
                        Always check the fare summary before confirming.
                    </p>
                </div>
            </details>

            <details class="group rounded-2xl border border-slate-200 bg-white shadow-dura-sm">
                <summary
                    class="flex cursor-pointer list-none items-center justify-between gap-4
                           px-5 py-4 text-left font-bold text-slate-900
                           sm:px-6 sm:py-5"
                >
                    <span>Do DURA Cabs vehicles have FASTag?</span>

                    <span
                        class="flex h-8 w-8 shrink-0 items-center justify-center
                               rounded-full bg-dura-50 text-dura-700 transition
                               group-open:rotate-45"
                        aria-hidden="true"
                    >
                        <i class="fa-solid fa-plus text-sm"></i>
                    </span>
                </summary>

                <div class="border-t border-slate-100 px-5 py-4 sm:px-6">
                    <p class="text-sm leading-6 text-slate-600 sm:text-base">
                        Availability may depend on the assigned vehicle.
                        Toll amounts remain payable according to the booking
                        terms and selected fare package.
                    </p>
                </div>
            </details>

            <details class="group rounded-2xl border border-slate-200 bg-white shadow-dura-sm">
                <summary
                    class="flex cursor-pointer list-none items-center justify-between gap-4
                           px-5 py-4 text-left font-bold text-slate-900
                           sm:px-6 sm:py-5"
                >
                    <span>How can I attach my car with DURA Cabs?</span>

                    <span
                        class="flex h-8 w-8 shrink-0 items-center justify-center
                               rounded-full bg-dura-50 text-dura-700 transition
                               group-open:rotate-45"
                        aria-hidden="true"
                    >
                        <i class="fa-solid fa-plus text-sm"></i>
                    </span>
                </summary>

                <div class="border-t border-slate-100 px-5 py-4 sm:px-6">
                    <p class="text-sm leading-6 text-slate-600 sm:text-base">
                        Visit the vendor registration page, submit your business
                        and vehicle details, and upload the requested documents
                        for verification.
                    </p>

                    <a
                        href="{{ url('/vendor-register') }}"
                        class="mt-3 inline-flex items-center gap-2 text-sm font-bold text-dura-700"
                    >
                        Register as a vendor
                        <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                    </a>
                </div>
            </details>

            <details class="group rounded-2xl border border-slate-200 bg-white shadow-dura-sm">
                <summary
                    class="flex cursor-pointer list-none items-center justify-between gap-4
                           px-5 py-4 text-left font-bold text-slate-900
                           sm:px-6 sm:py-5"
                >
                    <span>What is the minimum age for a self-drive booking?</span>

                    <span
                        class="flex h-8 w-8 shrink-0 items-center justify-center
                               rounded-full bg-dura-50 text-dura-700 transition
                               group-open:rotate-45"
                        aria-hidden="true"
                    >
                        <i class="fa-solid fa-plus text-sm"></i>
                    </span>
                </summary>

                <div class="border-t border-slate-100 px-5 py-4 sm:px-6">
                    <p class="text-sm leading-6 text-slate-600 sm:text-base">
                        The customer must meet the applicable age requirement
                        and hold a valid driving licence. Final eligibility can
                        vary according to vehicle and booking terms.
                    </p>
                </div>
            </details>

            <details class="group rounded-2xl border border-slate-200 bg-white shadow-dura-sm">
                <summary
                    class="flex cursor-pointer list-none items-center justify-between gap-4
                           px-5 py-4 text-left font-bold text-slate-900
                           sm:px-6 sm:py-5"
                >
                    <span>How do I book a taxi with DURA Cabs?</span>

                    <span
                        class="flex h-8 w-8 shrink-0 items-center justify-center
                               rounded-full bg-dura-50 text-dura-700 transition
                               group-open:rotate-45"
                        aria-hidden="true"
                    >
                        <i class="fa-solid fa-plus text-sm"></i>
                    </span>
                </summary>

                <div class="border-t border-slate-100 px-5 py-4 sm:px-6">
                    <p class="text-sm leading-6 text-slate-600 sm:text-base">
                        Select the trip type, enter pickup and destination
                        details, choose the date and time, then continue to view
                        available vehicles and fares.
                    </p>

                    <a
                        href="{{ url('/rides') }}"
                        class="mt-3 inline-flex items-center gap-2 text-sm font-bold text-dura-700"
                    >
                        Book a cab
                        <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                    </a>
                </div>
            </details>

        </div>

        <div
            class="mx-auto mt-8 flex max-w-4xl flex-col items-center justify-between
                   gap-4 rounded-2xl bg-dura-700 px-5 py-6 text-center
                   sm:flex-row sm:px-7 sm:text-left"
        >
            <div>
                <h3 class="text-lg font-bold text-white">
                    Still have a question?
                </h3>

                <p class="mt-1 text-sm text-white/80">
                    Contact our travel support team for booking assistance.
                </p>
            </div>

            <a
                href="https://api.whatsapp.com/send/?phone=917088873331&text=Hi%2C%20I%20need%20help%20with%20a%20Duracabs%20booking&type=phone_number&app_absent=0"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex min-h-11 shrink-0 items-center justify-center
                       gap-2 rounded-full bg-white px-6 py-3 text-sm
                       font-bold text-dura-700 transition hover:bg-slate-100"
            >
                <i class="fa-brands fa-whatsapp text-base" aria-hidden="true"></i>
                Contact Support
            </a>
        </div>

    </div>
</section>
	
	</div>
	
<section
    class="dura-section bg-white"
    aria-labelledby="cab-categories-heading"
>
    <div class="dura-container">

        {{-- Section heading --}}
        <div class="mx-auto mb-8 max-w-2xl text-center sm:mb-10">

            <p class="mb-2 text-sm font-bold uppercase tracking-wider text-dura-600">
                Choose your ride
            </p>

            <h2
                id="cab-categories-heading"
                class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl lg:text-4xl"
            >
                Cab Categories
            </h2>

            <p class="mt-3 text-sm leading-6 text-slate-600 sm:text-base">
                Select the right vehicle for your city ride, airport transfer,
                outstation trip, or family journey.
            </p>

        </div>

        {{-- Category grid --}}
        <div
            class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-4"
            role="list"
            aria-label="Available cab categories"
        >
            @forelse ($categories as $category)

                @php
                    $categoryUrl = url(
                        '/rides?selected_categories[0]=' . $category->id
                    );

                    $categoryImage = filled($category->image)
                        ? url('storage/' . ltrim($category->image, '/'))
                        : asset('img/placeholder/car-category.webp');
                @endphp

                <a
                    href="{{ $categoryUrl }}"
                    wire:key="homepage-category-{{ $category->id }}"
                    role="listitem"
                    class="group dura-card relative flex min-h-[150px] flex-col
                           items-center justify-center overflow-hidden p-4 text-center
                           sm:min-h-[175px] sm:p-5"
                    aria-label="View {{ $category->name }} cabs"
                >
                    {{-- Background decoration --}}
                    <span
                        class="pointer-events-none absolute -right-8 -top-8 h-24 w-24
                               rounded-full bg-dura-50 transition-transform duration-300
                               group-hover:scale-125"
                        aria-hidden="true"
                    ></span>

                    {{-- Vehicle image --}}
                    <div
                        class="relative z-10 flex h-20 w-full items-center justify-center
                               sm:h-24"
                    >
                        <img
                            src="{{ $categoryImage }}"
                            alt="{{ $category->name }} cab category"
                            loading="lazy"
                            decoding="async"
                            width="180"
                            height="100"
                            class="max-h-20 w-auto max-w-full object-contain transition-transform
                                   duration-300 group-hover:scale-105 sm:max-h-24"
                        >
                    </div>

                    {{-- Category name --}}
                    <div class="relative z-10 mt-3">

                        <h3
                            class="text-sm font-bold leading-5 text-slate-900
                                   transition-colors group-hover:text-dura-700
                                   sm:text-base"
                        >
                            {{ $category->name }}
                        </h3>

                        <span
                            class="mt-2 inline-flex items-center gap-1 text-xs
                                   font-semibold text-dura-600 sm:text-sm"
                        >
                            View cabs

                            <i
                                class="fa-solid fa-arrow-right text-[10px]
                                       transition-transform duration-200
                                       group-hover:translate-x-1"
                                aria-hidden="true"
                            ></i>
                        </span>

                    </div>
                </a>

            @empty

                <div
                    class="col-span-full rounded-dura-lg border border-dashed
                           border-slate-300 bg-slate-50 p-8 text-center"
                >
                    <p class="font-semibold text-slate-700">
                        Cab categories are currently unavailable.
                    </p>
                </div>

            @endforelse
        </div>

        {{-- Main booking CTA --}}
        <div class="mt-8 text-center sm:mt-10">

            <a
                href="{{ url('/rides') }}"
                class="dura-btn-primary min-w-[190px]"
            >
                <span>Explore All Cabs</span>

                <i
                    class="fa-solid fa-arrow-right text-xs"
                    aria-hidden="true"
                ></i>
            </a>

        </div>

    </div>
</section>
<section
    class="dura-section bg-slate-50"
    aria-labelledby="customer-reviews-heading"
>
    <div class="dura-container">

        {{-- Section heading --}}
        <div class="mb-8 flex flex-col gap-5 sm:mb-10 sm:flex-row sm:items-end sm:justify-between">

            <div>
                <p class="mb-2 text-sm font-bold uppercase tracking-wider text-dura-600">
                    Trusted by travellers
                </p>

                <h2
                    id="customer-reviews-heading"
                    class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl lg:text-4xl"
                >
                    Customer Reviews
                </h2>

                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
                    See what customers say about their journeys with Duracabs.
                </p>
            </div>

            <button
                type="button"
                wire:click="reviewFunction(true)"
                class="dura-btn-primary shrink-0"
            >
                <i class="fa-solid fa-pen text-xs" aria-hidden="true"></i>
                <span>Write a Review</span>
            </button>

        </div>

        {{-- Reviews carousel --}}
        <div
            x-data="carousel()"
            x-init="init()"
            class="relative"
        >
            <div
                x-ref="container"
                class="no-scrollbar scroll-snap-x flex gap-4 overflow-x-auto pb-4 scroll-smooth sm:gap-5"
                role="list"
                aria-label="Customer reviews"
            >
                @forelse ($reviews as $review)

                    @php
                        $reviewImage = filled($review->image)
                            ? url('storage/' . ltrim($review->image, '/'))
                            : asset('img/placeholder/avatar.webp');

                        $reviewStars = max(
                            0,
                            min(5, (int) $review->star)
                        );
                    @endphp

                    <article
                        role="listitem"
                        wire:key="homepage-review-{{ $review->id ?? md5($review->name . $review->description) }}"
                        class="dura-card snap-start flex w-[88%] shrink-0 flex-col p-5
                               sm:w-[48%] lg:w-[32%]"
                    >
                        {{-- Review header --}}
                        <div class="flex items-start gap-3">

                            <img
                                src="{{ $reviewImage }}"
                                alt="{{ $review->name }}"
                                loading="lazy"
                                decoding="async"
                                width="52"
                                height="52"
                                class="h-13 w-13 shrink-0 rounded-full border border-slate-200 object-cover"
                            >

                            <div class="min-w-0 flex-1">

                                <h3 class="truncate text-base font-bold text-slate-900">
                                    {{ $review->name }}
                                </h3>

                                @if (filled($review->designation))
                                    <p class="mt-0.5 truncate text-xs text-slate-500">
                                        {{ $review->designation }}
                                    </p>
                                @endif

                                <div
                                    class="mt-2 flex items-center gap-1"
                                    aria-label="{{ $reviewStars }} out of 5 stars"
                                >
                                    @for ($star = 1; $star <= 5; $star++)
                                        <i
                                            class="fa-solid fa-star text-sm
                                                {{ $star <= $reviewStars
                                                    ? 'text-amber-400'
                                                    : 'text-slate-200' }}"
                                            aria-hidden="true"
                                        ></i>
                                    @endfor
                                </div>

                            </div>

                            <i
                                class="fa-solid fa-quote-right text-2xl text-dura-100"
                                aria-hidden="true"
                            ></i>
                        </div>

                        {{-- Review content --}}
                        <div class="mt-5 flex-1">

                            <p class="line-clamp-5 text-sm leading-6 text-slate-600">
                                {{ $review->description }}
                            </p>

                        </div>

                        <div class="mt-5 border-t border-slate-100 pt-4">
                            <span class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500">
                                <i
                                    class="fa-solid fa-circle-check text-emerald-500"
                                    aria-hidden="true"
                                ></i>
                                Verified customer
                            </span>
                        </div>
                    </article>

                @empty

                    <div
                        class="w-full rounded-dura-lg border border-dashed border-slate-300
                               bg-white p-8 text-center"
                    >
                        <p class="font-semibold text-slate-700">
                            Customer reviews are currently unavailable.
                        </p>
                    </div>

                @endforelse
            </div>

            {{-- Navigation --}}
            <div class="mt-4 flex items-center justify-between">

                <div class="flex items-center gap-3">

                    <button
                        type="button"
                        x-on:click="scrollTo(prev)"
                        x-bind:disabled="prev === null"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-full
                               border border-slate-200 bg-white text-slate-700 shadow-dura-sm
                               transition hover:bg-dura-50 hover:text-dura-700
                               disabled:cursor-not-allowed disabled:opacity-40"
                        aria-label="Previous customer reviews"
                    >
                        <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                    </button>

                    <button
                        type="button"
                        x-on:click="scrollTo(next)"
                        x-bind:disabled="next === null"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-full
                               border border-slate-200 bg-white text-slate-700 shadow-dura-sm
                               transition hover:bg-dura-50 hover:text-dura-700
                               disabled:cursor-not-allowed disabled:opacity-40"
                        aria-label="Next customer reviews"
                    >
                        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                    </button>

                </div>

                <div class="text-right">

                    <div class="flex justify-end gap-1" aria-label="4.6 out of 5 stars">
                        @for ($star = 1; $star <= 5; $star++)
                            <i
                                class="fa-solid fa-star text-sm text-amber-400"
                                aria-hidden="true"
                            ></i>
                        @endfor
                    </div>

                    <p class="mt-1 text-sm text-slate-600">
                        <strong class="text-slate-900">4.6/5</strong>
                        from 12k reviews
                    </p>

                </div>
            </div>
        </div>

        {{-- Existing review modal remains below --}}
        @if ($showReview)
            {{-- Yahan aapka existing review modal code same rahega --}}
        @endif

    </div>
</section>
    </div>
<section
    class="dura-section overflow-hidden"
    aria-labelledby="popular-self-drive-heading"
>
    <div class="dura-container">

        <div class="mb-7 flex items-end justify-between gap-4">
            <div>
                <p class="mb-2 text-sm font-bold uppercase tracking-wider text-dura-600">
                    Drive yourself
                </p>

                <h2
                    id="popular-self-drive-heading"
                    class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl"
                >
                    Popular Self Drive Cars
                </h2>

                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
                    Choose approved and verified self drive cars for daily, weekly and monthly rental.
                </p>
            </div>

            <div
                class="hidden items-center gap-2 md:flex"
                aria-label="Self drive cars navigation"
            >
                <button
                    type="button"
                    x-on:click="scrollTo(prev)"
                    x-bind:disabled="prev === null"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-full
                           border border-slate-200 bg-white text-slate-700 shadow-dura-sm
                           transition hover:border-dura-200 hover:bg-dura-50 hover:text-dura-700
                           disabled:cursor-not-allowed disabled:opacity-40"
                    aria-label="Show previous self drive cars"
                >
                    <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                </button>

                <button
                    type="button"
                    x-on:click="scrollTo(next)"
                    x-bind:disabled="next === null"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-full
                           border border-slate-200 bg-white text-slate-700 shadow-dura-sm
                           transition hover:border-dura-200 hover:bg-dura-50 hover:text-dura-700
                           disabled:cursor-not-allowed disabled:opacity-40"
                    aria-label="Show next self drive cars"
                >
                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <div
            x-data="carousel()"
            x-init="init()"
            class="relative"
        >
            <div
                x-ref="container"
                class="no-scrollbar scroll-snap-x flex gap-4 overflow-x-auto pb-4 scroll-smooth sm:gap-5"
                role="list"
                aria-label="Popular self drive cars"
            >
                @forelse ($products as $vehicle)
                    @php
                        $vehicleName = $vehicle->display_name;
                        $vehicleUrl = url('/rides?tab=self_drive&vehicle_id=' . $vehicle->id);
                        $vehicleImage = $vehicle->front_image_url
                            ?: asset('img/placeholder/car-route.webp');
                        $dailyPrice = (float) $vehicle->daily_price;
                    @endphp

                    <article
                        wire:key="homepage-self-drive-vehicle-{{ $vehicle->id }}"
                        role="listitem"
                        class="dura-card snap-start flex w-[84%] shrink-0 flex-col
                               overflow-hidden sm:w-[46%] lg:w-[31%] xl:w-[24%]"
                    >
                        <a
                            href="{{ $vehicleUrl }}"
                            class="group relative block aspect-[16/10] overflow-hidden bg-slate-100"
                            aria-label="View self drive car {{ $vehicleName }}"
                        >
                            <img
                                src="{{ $vehicleImage }}"
                                alt="{{ $vehicleName }} self drive car"
                                loading="lazy"
                                decoding="async"
                                width="480"
                                height="300"
                                class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]"
                            >

                            <span
                                class="absolute left-3 top-3 rounded-full bg-white/95 px-3 py-1
                                       text-xs font-bold text-dura-700 shadow-dura-sm"
                            >
                                Self Drive
                            </span>
                        </a>

                        <div class="flex flex-1 flex-col p-4 sm:p-5">
                            <h3 class="min-h-[3rem] text-base font-bold leading-6 text-slate-900">
                                <a
                                    href="{{ $vehicleUrl }}"
                                    class="line-clamp-2 transition hover:text-dura-700"
                                >
                                    {{ $vehicleName }}
                                </a>
                            </h3>

                            <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-2 text-sm text-slate-500">
                                @if (filled($vehicle->transmission))
                                    <span>
                                        <i class="fa-solid fa-gears mr-1 text-dura-600" aria-hidden="true"></i>
                                        {{ ucfirst($vehicle->transmission) }}
                                    </span>
                                @endif

                                @if (filled($vehicle->fuel_type))
                                    <span>
                                        <i class="fa-solid fa-gas-pump mr-1 text-dura-600" aria-hidden="true"></i>
                                        {{ ucfirst($vehicle->fuel_type) }}
                                    </span>
                                @endif

                                @if ((int) $vehicle->seats > 0)
                                    <span>
                                        <i class="fa-solid fa-user-group mr-1 text-dura-600" aria-hidden="true"></i>
                                        {{ (int) $vehicle->seats }} Seats
                                    </span>
                                @endif
                            </div>

                            <div class="mt-auto pt-5">
                                <div class="mb-3">
                                    <span class="block text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Daily rental
                                    </span>

                                    <span class="mt-1 block text-xl font-extrabold text-slate-900">
                                        ₹{{ number_format($dailyPrice) }}
                                        <small class="text-sm font-semibold text-slate-500">/ day</small>
                                    </span>
                                </div>

                                <a
                                    href="{{ $vehicleUrl }}"
                                    class="dura-btn-primary w-full"
                                    aria-label="View self drive car {{ $vehicleName }}"
                                >
                                    <span>View Self Drive Car</span>
                                    <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="w-full rounded-dura-lg border border-dashed border-slate-300 bg-white p-8 text-center">
                        <p class="font-semibold text-slate-700">
                            Self drive cars are currently unavailable.
                        </p>
                    </div>
                @endforelse
            </div>

            <div class="mt-3 flex justify-center gap-3 md:hidden">
                <button
                    type="button"
                    x-on:click="scrollTo(prev)"
                    x-bind:disabled="prev === null"
                    class="inline-flex h-11 min-w-28 items-center justify-center gap-2
                           rounded-dura border border-slate-200 bg-white px-4
                           text-sm font-bold text-slate-700 shadow-dura-sm
                           disabled:cursor-not-allowed disabled:opacity-40"
                >
                    <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                    Previous
                </button>

                <button
                    type="button"
                    x-on:click="scrollTo(next)"
                    x-bind:disabled="next === null"
                    class="inline-flex h-11 min-w-28 items-center justify-center gap-2
                           rounded-dura border border-slate-200 bg-white px-4
                           text-sm font-bold text-slate-700 shadow-dura-sm
                           disabled:cursor-not-allowed disabled:opacity-40"
                >
                    Next
                    <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </div>
</section>
<section
    class="dura-section overflow-hidden bg-slate-50"
    aria-labelledby="popular-tours-heading"
>
    <div class="dura-container">

        {{-- Section heading --}}
        <div class="mb-8 flex flex-col gap-4 sm:mb-10 sm:flex-row sm:items-end sm:justify-between">

            <div>
                <p class="mb-2 text-sm font-bold uppercase tracking-wider text-dura-600">
                    Explore India
                </p>

                <h2
                    id="popular-tours-heading"
                    class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl lg:text-4xl"
                >
                    Popular Tours
                </h2>

                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
                    Discover carefully selected destinations and plan your next journey with Duracabs.
                </p>
            </div>

            <a
                href="{{ url('/tours') }}"
                class="hidden items-center gap-2 text-sm font-bold text-dura-700
                       transition hover:text-dura-800 sm:inline-flex"
            >
                View all tours

                <i
                    class="fa-solid fa-arrow-right text-xs"
                    aria-hidden="true"
                ></i>
            </a>

        </div>

        {{-- Tours carousel --}}
        <div
            x-data="carousel()"
            x-init="init()"
            class="relative"
        >
            <div
                x-ref="container"
                class="no-scrollbar scroll-snap-x flex gap-4 overflow-x-auto
                       pb-4 scroll-smooth sm:gap-5"
                role="list"
                aria-label="Popular tour destinations"
            >
                @forelse ($tours as $tour)

                    @php
                        $tourImage = filled($tour->image)
                            ? url('storage/' . ltrim($tour->image, '/'))
                            : asset('img/placeholder/tour.webp');

                        $tourUrl = filled($tour->url)
                            ? $tour->url
                            : url('/tours');
                    @endphp

                    <article
                        wire:key="homepage-tour-{{ $tour->id ?? md5($tour->name . $tourUrl) }}"
                        role="listitem"
                        class="dura-card group snap-start w-[84%] shrink-0 overflow-hidden
                               sm:w-[47%] lg:w-[31%] xl:w-[24%]"
                    >
                        <a
                            href="{{ $tourUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="block"
                            aria-label="Explore {{ $tour->name }}"
                        >
                            {{-- Tour image --}}
                            <div class="relative aspect-[16/11] overflow-hidden bg-slate-100">

                                <img
                                    src="{{ $tourImage }}"
                                    alt="{{ $tour->name }} tour by Duracabs"
                                    loading="lazy"
                                    decoding="async"
                                    width="480"
                                    height="330"
                                    class="h-full w-full object-cover transition duration-500
                                           group-hover:scale-105"
                                >

                                <div
                                    class="absolute inset-0 bg-gradient-to-t
                                           from-slate-950/75 via-slate-900/10 to-transparent"
                                    aria-hidden="true"
                                ></div>

                                <span
                                    class="absolute left-3 top-3 inline-flex items-center gap-1.5
                                           rounded-full bg-white/95 px-3 py-1.5 text-xs
                                           font-bold text-dura-700 shadow-dura-sm"
                                >
                                    <i
                                        class="fa-solid fa-location-dot"
                                        aria-hidden="true"
                                    ></i>

                                    Popular Tour
                                </span>

                                {{-- Text overlay --}}
                                <div class="absolute inset-x-0 bottom-0 p-4 sm:p-5">

                                    <h3
                                        class="line-clamp-2 text-lg font-bold leading-6 text-white"
                                    >
                                        {{ $tour->name }}
                                    </h3>

                                    <span
                                        class="mt-2 inline-flex items-center gap-2 text-sm
                                               font-semibold text-white/90"
                                    >
                                        Explore destination

                                        <i
                                            class="fa-solid fa-arrow-right text-xs
                                                   transition-transform duration-200
                                                   group-hover:translate-x-1"
                                            aria-hidden="true"
                                        ></i>
                                    </span>

                                </div>
                            </div>
                        </a>
                    </article>

                @empty

                    <div
                        class="w-full rounded-dura-lg border border-dashed
                               border-slate-300 bg-white p-8 text-center"
                    >
                        <p class="font-semibold text-slate-700">
                            Popular tours are currently unavailable.
                        </p>
                    </div>

                @endforelse
            </div>

            {{-- Navigation controls --}}
            <div class="mt-4 flex items-center justify-between">

                <div class="flex gap-3">

                    <button
                        type="button"
                        x-on:click="scrollTo(prev)"
                        x-bind:disabled="prev === null"
                        class="inline-flex h-11 w-11 items-center justify-center
                               rounded-full border border-slate-200 bg-white
                               text-slate-700 shadow-dura-sm transition
                               hover:border-dura-200 hover:bg-dura-50
                               hover:text-dura-700
                               disabled:cursor-not-allowed disabled:opacity-40"
                        aria-label="Show previous tours"
                    >
                        <i
                            class="fa-solid fa-chevron-left"
                            aria-hidden="true"
                        ></i>
                    </button>

                    <button
                        type="button"
                        x-on:click="scrollTo(next)"
                        x-bind:disabled="next === null"
                        class="inline-flex h-11 w-11 items-center justify-center
                               rounded-full border border-slate-200 bg-white
                               text-slate-700 shadow-dura-sm transition
                               hover:border-dura-200 hover:bg-dura-50
                               hover:text-dura-700
                               disabled:cursor-not-allowed disabled:opacity-40"
                        aria-label="Show next tours"
                    >
                        <i
                            class="fa-solid fa-chevron-right"
                            aria-hidden="true"
                        ></i>
                    </button>

                </div>

                <a
                    href="{{ url('/tours') }}"
                    class="inline-flex items-center gap-2 text-sm font-bold
                           text-dura-700 sm:hidden"
                >
                    View all

                    <i
                        class="fa-solid fa-arrow-right text-xs"
                        aria-hidden="true"
                    ></i>
                </a>

            </div>
        </div>

    </div>
</section>