<div
    class="premium-motion-page rides-premium-page {{ ($tab ?? null) === 'self_drive' ? 'self-drive-mode' : '' }} w-full max-w-[86rem] px-3 sm:px-5 lg:px-6 mx-auto"
    id="rides-page"
    x-data="{
        otpSeconds: 0,
        galleryOpen: false,
        galleryImages: [],
        galleryIndex: 0,
        galleryVehicleName: '',
        galleryTouchStartX: 0,

        selfDriveFareOpen: false,
        selfDriveFare: {},

        openSelfDriveFare(details) {
            this.selfDriveFare = details || {};
            this.selfDriveFareOpen = true;
            document.documentElement.classList.add('overflow-hidden');
            document.body.classList.add('overflow-hidden');

            this.$nextTick(() => {
                this.$refs.selfDriveFareCloseButton?.focus();
            });
        },

        closeSelfDriveFare() {
            this.selfDriveFareOpen = false;
            document.documentElement.classList.remove('overflow-hidden');
            document.body.classList.remove('overflow-hidden');
        },

        formatInr(amount) {
            return new Intl.NumberFormat('en-IN', {
                style: 'currency',
                currency: 'INR',
                maximumFractionDigits: 0
            }).format(Number(amount || 0));
        },

        openGallery(images, vehicleName, startIndex = 0) {
            if (!Array.isArray(images) || images.length === 0) {
                return;
            }

            this.galleryImages = images;
            this.galleryVehicleName = vehicleName || 'Vehicle Gallery';
            this.galleryIndex = Math.min(
                Math.max(Number(startIndex) || 0, 0),
                images.length - 1
            );
            this.galleryOpen = true;

            document.documentElement.classList.add('overflow-hidden');
            document.body.classList.add('overflow-hidden');

            this.$nextTick(() => {
                this.$refs.galleryCloseButton?.focus();
            });
        },

        closeGallery() {
            this.galleryOpen = false;
            document.documentElement.classList.remove('overflow-hidden');
            document.body.classList.remove('overflow-hidden');
        },

        nextGalleryImage() {
            if (this.galleryImages.length < 2) {
                return;
            }

            this.galleryIndex =
                (this.galleryIndex + 1) % this.galleryImages.length;
        },

        previousGalleryImage() {
            if (this.galleryImages.length < 2) {
                return;
            }

            this.galleryIndex =
                (this.galleryIndex - 1 + this.galleryImages.length)
                % this.galleryImages.length;
        },

        startGalleryTouch(event) {
            this.galleryTouchStartX =
                event.changedTouches?.[0]?.clientX ?? 0;
        },

        endGalleryTouch(event) {
            const touchEndX =
                event.changedTouches?.[0]?.clientX ?? 0;

            const difference = touchEndX - this.galleryTouchStartX;

            if (Math.abs(difference) < 45) {
                return;
            }

            if (difference < 0) {
                this.nextGalleryImage();
            } else {
                this.previousGalleryImage();
            }
        }
    }"
    x-on:rides-otp-sent.window="
        otpSeconds = $event.detail.seconds;

        const timer = setInterval(() => {
            otpSeconds--;

            if (otpSeconds <= 0) {
                clearInterval(timer);
            }
        }, 1000);
    "
    x-on:keydown.escape.window="if (galleryOpen) closeGallery(); if (selfDriveFareOpen) closeSelfDriveFare()"
    x-on:keydown.arrow-right.window="if (galleryOpen) nextGalleryImage()"
    x-on:keydown.arrow-left.window="if (galleryOpen) previousGalleryImage()"
>
    {{-- Search-result SEO is emitted once by resources/views/layouts/app.blade.php. --}}
    @section('title', trim((string) ($pageTitle ?? 'Available Cabs | Dura Cabs')))
    @section('description', trim((string) ($pageDescription ?? 'Compare available cabs and continue your booking with Dura Cabs.')))
    @section('keywords', trim((string) ($pageKeywords ?? '')))
    @section('image', $pageImage ?? '')
    @section('canonical', url()->current())
    @section('robots', ($isRouteHub ?? false) ? 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1' : 'noindex, follow')
    @section('og_type', 'website')

    @php
        /*
         * Self-drive duration is calculated from the actual pickup/drop
         * date and time. The old $days URL value is used only as a safe
         * fallback when any date/time value is missing or invalid.
         */
        $selfDriveHours = 1;
        $selfDriveRentalDays = 1;

        if (($tab ?? null) === 'self_drive') {
            try {
                $pickupDateTime = \Carbon\Carbon::createFromFormat(
                    '!Y-m-d H:i',
                    trim((string) $date . ' ' . (string) $time)
                );

                $dropDateTime = \Carbon\Carbon::createFromFormat(
                    '!Y-m-d H:i',
                    trim((string) $dateto . ' ' . (string) $endTime)
                );

                if ($dropDateTime->greaterThan($pickupDateTime)) {
                    $durationMinutes = $pickupDateTime->diffInMinutes(
                        $dropDateTime,
                        true
                    );

                    // Any partial hour is billed as one complete hour.
                    $selfDriveHours = max(
                        1,
                        (int) ceil($durationMinutes / 60)
                    );
                }
            } catch (\Throwable $e) {
                $selfDriveHours = max(1, (int) ($days ?: 1));
            }

            $selfDriveRentalDays = max(
                1,
                (int) ceil($selfDriveHours / 24)
            );
        }
    @endphp

    {{-- =========================================================
        CLEAN /routes SEO HUB
        - Only the clean /routes URL is indexable.
        - Filter/search URLs remain noindex, follow.
        - Links below are real <a href> links for crawler discovery.
    ========================================================== --}}
    @if ($isRouteHub ?? false)
        <section class="mb-6 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="bg-gradient-to-br from-sky-50 via-white to-blue-50 px-5 py-8 sm:px-8 sm:py-10">
                <div class="mx-auto max-w-4xl text-center">
                    <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-sky-700">Cab Route Directory</p>
                    <h1 class="mt-3 text-3xl font-black leading-tight text-slate-950 sm:text-4xl">
                        Popular One Way Cab Routes in India
                    </h1>
                    <p class="mx-auto mt-4 max-w-3xl text-sm leading-7 text-slate-600 sm:text-base">
                        Explore popular city-to-city taxi routes with Dura Cabs. Choose an origin city below to discover active one way cab routes, compare available vehicle options and continue to the individual route page for fare and booking details.
                    </p>
                </div>
            </div>

            @if (($routeHubGroups ?? collect())->isNotEmpty())
                <div class="p-5 sm:p-8">
                    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($routeHubGroups as $group)
                            <section class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-[11px] font-extrabold uppercase tracking-[0.14em] text-sky-700">From</p>
                                        <h2 class="mt-1 text-xl font-black text-slate-900">
                                            {{ $group['city_name'] }} Cab Routes
                                        </h2>
                                    </div>
                                    <span class="shrink-0 rounded-full bg-white px-3 py-1 text-xs font-extrabold text-slate-600 ring-1 ring-slate-200">
                                        {{ $group['route_count'] }} routes
                                    </span>
                                </div>

                                <div class="mt-4 grid gap-2">
                                    @foreach ($group['routes'] as $hubRoute)
                                        <a
                                            href="{{ $hubRoute['url'] }}"
                                            class="group flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:border-sky-200 hover:text-sky-700 hover:shadow-sm"
                                        >
                                            <span class="min-w-0 truncate">{{ $hubRoute['name'] }}</span>
                                            <i class="fa-solid fa-arrow-right shrink-0 text-xs text-slate-400 transition group-hover:translate-x-0.5 group-hover:text-sky-600" aria-hidden="true"></i>
                                        </a>
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    </div>

                    <div class="mt-7 rounded-2xl border border-sky-100 bg-sky-50/70 px-5 py-4 text-sm leading-6 text-slate-600">
                        Dura Cabs route pages are connected through this directory and related-route links, helping travellers and search engines discover useful city-to-city taxi options without changing any existing route URL.
                    </div>
                </div>
            @else
                <div class="p-6 text-center text-sm text-slate-600">
                    Route directory is being updated. Please use the booking search above to find an available cab.
                </div>
            @endif
        </section>
    @endif

    <section class="ride-shell rides-premium-shell font-poppins rounded-3xl py-3 sm:py-5">
        <div class="mx-auto max-w-7xl px-1 sm:px-3">
            @if ($nameTo)
                <section class="ride-summary-card premium-hero-animate mb-5">
                    <div class="ride-summary-route">
                        <div class="ride-route-markers" aria-hidden="true">
                            <span class="ride-route-dot ride-route-dot--start"></span>
                            <span class="ride-route-line"></span>
                            <span class="ride-route-dot ride-route-dot--end"></span>
                        </div>
                        <div class="min-w-0">
                            <p class="ride-summary-city">{{ $nameTo }}</p>
                            @if (in_array($tab, ['one_way', 'return'], true))
                                <span class="ride-summary-to">TO</span>
                                <p class="ride-summary-city">{{ $tab === 'return' ? $cityFrom : $nameFrom }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="ride-summary-meta">
                        <div class="ride-summary-item">
                            <i class="fa-solid fa-location-dot"></i>
                            <span><small>Trip Type</small><strong>{{ str($tab ?: 'one_way')->replace('_', ' ')->title() }}</strong></span>
                        </div>
                        @if ($date)
                            <div class="ride-summary-item">
                                <i class="fa-regular fa-calendar"></i>
                                <span><small>Date</small><strong>{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</strong></span>
                            </div>
                        @endif
                        @if ($newTime)
                            <div class="ride-summary-item">
                                <i class="fa-regular fa-clock"></i>
                                <span><small>Time</small><strong>{{ $newTime->format('h:i A') }}</strong></span>
                            </div>
                        @endif
                        <div class="ride-summary-item">
                            <i class="fa-regular fa-user"></i>
                            <span><small>Passengers</small><strong>{{ max(1, (int) ($cars ?: 1)) }} Passenger</strong></span>
                        </div>
                    </div>

                    <div class="ride-summary-actions">
                        <button type="button" wire:click="showEditQueryModal" class="ride-summary-edit">
                            <i class="fa-solid fa-pen-to-square"></i><span>Edit Trip</span>
                        </button>
                        @unless ($fareUnlocked)
                            <button
    type="button"
    wire:click="openFareGate"
    class="ride-fare-icon-button"
    aria-label="Unlock exact fare details"
    title="Unlock exact fare details">
    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
    <span class="sr-only">Unlock exact fare details</span>
</button>
                        @endunless
                    </div>
                </section>
            @endif

            @if (($tab ?? null) === 'return' && !empty($multiCityRoute))
                <section class="mb-5 overflow-hidden rounded-3xl border border-emerald-200 bg-gradient-to-br from-emerald-50 via-white to-sky-50 shadow-sm">
                    <div class="flex flex-col gap-4 p-5 sm:p-6">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-emerald-700">
                                    Multi-City Round Trip
                                </p>
                                <h2 class="mt-1 text-xl font-black text-slate-900">
                                    Complete Journey Route
                                </h2>
                            </div>

                            <span class="inline-flex w-max items-center gap-2 rounded-full bg-emerald-100 px-3 py-2 text-xs font-extrabold text-emerald-800">
                                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                                Return to Pickup Included
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            @foreach ($multiCityRoute as $routeCity)
                                <span class="max-w-full truncate rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-extrabold text-slate-800 shadow-sm">
                                    {{ $routeCity }}
                                </span>

                                @if (!$loop->last)
                                    <i class="fa-solid fa-arrow-right text-xs text-emerald-600" aria-hidden="true"></i>
                                @endif
                            @endforeach
                        </div>

                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div class="rounded-2xl bg-white p-3 shadow-sm ring-1 ring-slate-100">
                                <small class="block text-[11px] font-bold uppercase tracking-wide text-slate-500">
                                    Total Stops
                                </small>
                                <strong class="mt-1 block text-lg text-slate-900">
                                    {{ max(1, count($multiCityRoute) - 1) }}
                                </strong>
                            </div>

                            <div class="rounded-2xl bg-white p-3 shadow-sm ring-1 ring-slate-100">
                                <small class="block text-[11px] font-bold uppercase tracking-wide text-slate-500">
                                    Total Distance
                                </small>
                                <strong class="mt-1 block text-lg text-slate-900">
                                    {{ number_format(max(0, (float) $kmValue / 1000), 0) }} KM
                                </strong>
                            </div>

                            <div class="rounded-2xl bg-white p-3 shadow-sm ring-1 ring-slate-100">
                                <small class="block text-[11px] font-bold uppercase tracking-wide text-slate-500">
                                    Total Days
                                </small>
                                <strong class="mt-1 block text-lg text-slate-900">
                                    {{ max(1, (int) $days) }} Days
                                </strong>
                            </div>

                            <div class="rounded-2xl bg-white p-3 shadow-sm ring-1 ring-slate-100">
                                <small class="block text-[11px] font-bold uppercase tracking-wide text-slate-500">
                                    Return Trip
                                </small>
                                <strong class="mt-1 block text-lg text-emerald-700">
                                    Included
                                </strong>
                            </div>
                        </div>
                    </div>
                </section>
            @endif

            <div wire:loading.flex wire:target="sort,selected_categories,selected_brands,price_range" class="surface mb-4 items-center gap-3 px-4 py-3 text-sm font-semibold text-blue-700">
                <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
                Updating available vehicles…
            </div>
            <div class="lg:flex flex-wrap mb-24 -mx-3">
                <div class="rides-layout-sidebar w-full pr-2 lg:w-1/4 lg:block hidden">

                    @if ($nameTo)
                        <div class="rides-side-card p-4 mb-5 bg-white border border-gray-200 dark:border-gray-900 dark:bg-gray-900 ">
                            <h2 class="text-xl font-medium text-sky-500 dark:text-gray-400">Trip Details</h2>
                            <div class="flex mt-3 justify-evenly">
                                <i class="fa-solid fa-car-side" aria-hidden="true"></i>
                                &nbsp; <p class="text-sm">PickUp City: </p> &nbsp; &nbsp; <span
                                    class="text-sm">{{ $nameTo }}</span>

                            </div>



                            @if ($tab === 'one_way')
                                <div class="flex mt-3">
                                    <i class="fa-solid fa-car-side" aria-hidden="true"></i>
                                    &nbsp; <p class="text-sm">Drop City: </p> &nbsp; &nbsp; <span class="text-sm">

                                        @if ($tab == 'one_way')
                                            {{ $nameFrom }}
                                        @endif



                                        @if ($tab == 'return')
                                            {{ $cityFrom }}
                                        @endif
                                    </span>

                                </div>
                            @endif

                            <div class="flex mt-3">
                                <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                                &nbsp; <p class="text-sm">PickUp Date: </p> &nbsp; &nbsp; <span
                                    class="text-sm">{{ $date }}</span>

                            </div>

                            @if ($tab === 'self_drive' || $tab === 'return')
                                <div class="flex mt-3">
                                    <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                                    &nbsp; <p class="text-sm">Drop Date: </p> &nbsp; &nbsp; <span
                                        class="text-sm">{{ $dateto }}</span>

                                </div>
                            @endif

                            @if ($newTime)
                                <div class="flex mt-3">


                                    <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                    &nbsp; <p class="text-sm">Pickup TIme: </p> &nbsp; &nbsp;
                                    <span class="text-sm">{{ $newTime->format('h:i A') }}</span>

                                </div>
                            @endif

                            @if ($endTime)
                                <div class="flex mt-3">


                                    <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                    &nbsp; <p class="text-sm">Pickup TIme: </p> &nbsp; &nbsp;
                                    <span class="text-sm">{{ $timeEnd->format('h:i A') }}</span>

                                </div>
                            @endif




                            @if ($tab === 'return')
                                <div class="flex mt-3">
                                    <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                                    &nbsp; <p class="text-sm">Days: </p> &nbsp; &nbsp;
                                    <span class="text-sm">{{ $days === 0 ? 1 : $days + 1 }}</span>

                                </div>
                            @endif

                            @if ($tab === 'self_drive')
                                <div class="flex mt-3">
                                    <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                                    &nbsp; <p class="text-sm">Hours: </p> &nbsp; &nbsp;
                                    <span class="text-sm">{{ $selfDriveHours }}</span>

                                </div>
                            @endif

                            @if ($tab === 'local')
                                <div class="flex mt-3">
                                    <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                                    &nbsp; <p class="text-sm">Plan: </p> &nbsp; &nbsp; <span
                                        class="text-sm">{{ $plan }}</span>

                                </div>

                                <div class="flex mt-3">
                                    <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                                    &nbsp; <p class="text-sm">Cars: </p> &nbsp; &nbsp; <span
                                        class="text-sm">{{ $cars }}</span>

                                </div>
                            @endif

                        </div>
                    @endif

                    <div class="rides-side-card p-4 mb-5 bg-white border border-gray-200 dark:border-gray-900 dark:bg-gray-900">
                        <h2 class="text-xl font-medium text-sky-500 dark:text-gray-400"> Need Booking Assistance?</h2>
                        <div class="flex mt-3">
                            <p>Contact our customer assistance team. Support is available 24×7.</p>

                        </div>
                        <div class="flex mt-3">
                            <div class="bg-sky-600 p-1 text-sky-300 rounded-lg">
                                <i class="fa-solid fa-phone" aria-hidden="true"></i>
                            </div>
                            &nbsp; <a href="tel:+91-7088873331"
                                class="text-sky-700 font-bold text-xl">+91-7088873331</a>

                        </div>
                        <div class="flex mt-3">
                            <div class="bg-green-500 p-1 text-sky-300 rounded-full">
                                <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
                            </div>
                            &nbsp; &nbsp; <a href="tel:+91-7088873331"
                                class="text-green-500 font-bold text-xl">+91-7088873331</a>

                        </div>
                    </div>

                    @if (!$tab)

                        {{-- <div class="rides-side-card p-4 mb-5 bg-white border border-gray-200 dark:border-gray-900 dark:bg-gray-900">
                            <h2 class="text-2xl font-bold dark:text-gray-400">Cab Categories</h2>
                            {{json_encode($selected_categories)}}
                            <div class="w-16 pb-2 mb-6 border-b border-rose-600 dark:border-gray-400"></div>

                            <ul>
                                @foreach ($categories as $category)
                                    <li class="mb-4" wire:key='{{ $category->id }}'>
                                        <label for="{{ $category->slug }}"
                                            class="flex items-center dark:text-gray-400 ">
                                            <input type="checkbox" wire:model.live='selected_categories'
                                                id="{{ $category->slug }}" value="{{ $category->id }}"
                                                class="w-4 h-4 mr-2">
                                            <span class="text-lg">{{ $category->name }} </span>
                                        </label>
                                    </li>
                                @endforeach

                            </ul>

                        </div> --}}
                        <div class="p-4 mb-5 bg-white border border-gray-200 dark:bg-gray-900 dark:border-gray-900">
                            <h2 class="text-2xl font-bold dark:text-gray-400">Destination</h2>
                            <input type="text" wire:model.live='query2' placeholder="Search City.."
                                id="simple-search-1"
                                class="lg:mt-3 bg-gray-50 border border-gray-300 text-black font-extrabold  text-xm focus:ring-blue-500 focus:border-blue-500 block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                            <div class="w-16 pb-2 mb-6 border-b border-rose-600 dark:border-gray-400"></div>
                            <ul>
                                @foreach ($brands as $brand)
                                    <li class="mb-4" wire:key='{{ $brand->id }}'>
                                        <label for="{{ $brand->slug }}"
                                            class="flex items-center dark:text-gray-300">
                                            <input type="checkbox" wire:model.live='selected_brands'
                                                id='{{ $brand->slug }}' value="{{ $brand->id }}"
                                                class="w-4 h-4 mr-2">
                                            <span class="text-lg dark:text-gray-400">{{ $brand->name }}</span>
                                        </label>
                                    </li>
                                @endforeach

                            </ul>

                        </div>


                        <div class="p-4 mb-5 bg-white border border-gray-200 dark:bg-gray-900 dark:border-gray-900">
                            <h2 class="text-2xl font-bold dark:text-gray-400">Price</h2>
                            <div class="w-16 pb-2 mb-6 border-b border-rose-600 dark:border-gray-400"></div>
                            <div>
                                <div class="font-semibold">{{ Number::currency($price_range, 'INR') }}</div>
                                <input type="range" wire:model.live='price_range'
                                    class="w-full h-1 mb-4 bg-blue-100 rounded appearance-none cursor-pointer"
                                    max="50000" value="100" step="10">
                                <div class="flex justify-between ">
                                    <span
                                        class="inline-block text-lg font-bold text-blue-400 ">{{ Number::currency(1000, 'INR') }}</span>
                                    <span
                                        class="inline-block text-lg font-bold text-blue-400 ">{{ Number::currency(50000, 'INR') }}</span>
                                </div>
                            </div>
                        </div>

                    @endif

                </div>
                @if ($tab === 'return')

                    <div class="w-full px-3 lg:w-3/4">
                        <div class="surface rides-toolbar mb-4 flex flex-col gap-3 p-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-bold text-slate-800">
                                    {{ $categories2->count() }} vehicle categories available
                                </p>
                                <p class="text-xs text-slate-500">
                                    Compare Normal and All Inclusive fares before selecting your vehicle.
                                </p>
                            </div>

                            <label class="flex items-center gap-2 text-sm font-semibold text-slate-600">
                                <span>Sort</span>
                                <select
                                    wire:model.live="sort"
                                    class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="price">Price: low to high</option>
                                    <option value="latest">Latest first</option>
                                </select>
                            </label>
                        </div>

                        <div class="rides-results rides-premium-results relative grid items-center">
                            @unless ($fareUnlocked)
                                <div class="absolute inset-0 z-30 flex min-h-[420px] items-start justify-center rounded-2xl bg-white/90 px-4 pt-10 backdrop-blur-sm">
                                    <div class="surface max-w-md p-6 text-center">
                                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-blue-100 text-2xl">
                                            ₹
                                        </div>
                                        <h3 class="mt-4 text-xl font-extrabold text-slate-900">
                                            Unlock exact cab fares
                                        </h3>
                                        <p class="mt-2 text-sm leading-6 text-slate-600">
                                            Verify your mobile number with a 4-digit OTP to view transparent Normal and All Inclusive fares.
                                        </p>
                                        <button
                                            type="button"
                                            wire:click="openFareGate"
                                            class="mt-5 inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-blue-600 px-5 py-3 font-extrabold text-white hover:bg-blue-700"
                                        >
                                            View Exact Fares
                                        </button>
                                    </div>
                                </div>
                            @endunless

                            @foreach ($categories2 as $category)
                                @php
                                    $actualRouteKm = max(
                                        0,
                                        (float) $kmValue / 1000
                                    );

                                    $tripDays = max(
                                        1,
                                        (int) $days
                                    );

                                    $minimumKmPerDay = max(
                                        0,
                                        (float) ($category->range ?? 0)
                                    );

                                    $minimumBillableKm =
                                        $tripDays * $minimumKmPerDay;

                                    $billableKm = max(
                                        $actualRouteKm,
                                        $minimumBillableKm
                                    );

                                    $kmRate = max(
                                        0,
                                        (float) ($category->km_charge ?? 0)
                                    );

                                    $driverAllowancePerDay = max(
                                        0,
                                        (float) ($category->driver_charge ?? 0)
                                    );

                                    $vehicleFare = round(
                                        $billableKm * $kmRate
                                    );

                                    $driverAllowance = round(
                                        $tripDays * $driverAllowancePerDay
                                    );

                                    $normalGrandTotal =
                                        $vehicleFare + $driverAllowance;

                                    /*
                                     * Final approved business rule:
                                     * All Inclusive = ₹3 per billable kilometre.
                                     * It includes Driver Allowance, Toll Tax and State Tax.
                                     * Parking remains payable directly as per actual charges.
                                     */
                                    $allInclusiveRate = 3;

                                    $allInclusiveCharge = round(
                                        $billableKm * $allInclusiveRate
                                    );

                                    $allInclusiveGrandTotal =
                                        $vehicleFare + $allInclusiveCharge;
                                @endphp

                                <article
                                    wire:key="return-category-{{ $category->id }}"
                                    class="ride-package-card"
                                    x-data="{
                                        allInclusive: false,
                                        fareDetailsOpen: false,
                                        normalTotal: {{ $normalGrandTotal }},
                                        inclusiveTotal: {{ $allInclusiveGrandTotal }}
                                    }"
                                >
                                    <div class="ride-package-media">
                                        <span class="ride-package-badge ride-package-badge--green">
                                            Best Price
                                        </span>

                                        <img
                                            src="{{ url('storage') }}/{{ $category->image }}"
                                            alt="{{ $category->name }}"
                                            loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                                            fetchpriority="{{ $loop->first ? 'high' : 'auto' }}"
                                            decoding="async"
                                            width="420"
                                            height="240"
                                        >
                                    </div>

                                    <div class="ride-package-content">
                                        <div class="ride-package-title-row">
                                            <div>
                                                <h3>{{ $category->name }}</h3>

                                                <div class="ride-package-rating" aria-label="5 star rated">
                                                    @for ($star = 0; $star < 5; $star++)
                                                        <i class="fa-solid fa-star" aria-hidden="true"></i>
                                                    @endfor
                                                    <span>5.0</span>
                                                </div>
                                            </div>
                                        </div>

                                        <p class="ride-package-model">
                                            Comfortable AC cab with a professional driver or a similar vehicle.
                                        </p>

                                        @if (!empty($multiCityRoute))
                                            <div class="mt-3 rounded-2xl border border-emerald-100 bg-emerald-50/60 p-3">
                                                <p class="text-[11px] font-extrabold uppercase tracking-wide text-emerald-700">
                                                    Journey Route
                                                </p>

                                                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                                    @foreach ($multiCityRoute as $routeCity)
                                                        <span class="max-w-[150px] truncate rounded-lg bg-white px-2 py-1 text-xs font-bold text-slate-700 shadow-sm">
                                                            {{ $routeCity }}
                                                        </span>

                                                        @if (!$loop->last)
                                                            <i class="fa-solid fa-arrow-right text-[10px] text-emerald-600" aria-hidden="true"></i>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        <div class="ride-package-features">
                                            <span>
                                                <i class="fa-solid fa-route"></i>
                                                {{ number_format($actualRouteKm, 0) }} KM Route
                                            </span>
                                            <span>
                                                <i class="fa-regular fa-calendar"></i>
                                                {{ $tripDays }} Days
                                            </span>
                                            <span>
                                                <i class="fa-solid fa-road"></i>
                                                {{ number_format($billableKm, 0) }} Billable KM
                                            </span>
                                            <span>
                                                <i class="fa-solid fa-snowflake"></i>
                                                AC
                                            </span>
                                        </div>
                                    </div>

                                    <div class="ride-package-price">
                                        <div class="mb-4 w-full rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                            <div class="flex items-center justify-between gap-3">
                                                <div>
                                                    <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">
                                                        Fare Type
                                                    </p>

                                                    <strong
                                                        class="mt-1 block text-sm"
                                                        x-text="allInclusive ? 'All Inclusive' : 'Normal Fare'"
                                                    ></strong>
                                                </div>

                                                <button
                                                    type="button"
                                                    role="switch"
                                                    x-on:click="allInclusive = !allInclusive"
                                                    x-bind:aria-checked="allInclusive"
                                                    class="relative inline-flex h-8 w-16 shrink-0 items-center rounded-full transition focus:outline-none focus:ring-4 focus:ring-emerald-100"
                                                    x-bind:class="allInclusive ? 'bg-emerald-500' : 'bg-slate-300'"
                                                    aria-label="Switch between Normal and All Inclusive fare"
                                                >
                                                    <span
                                                        class="inline-block h-6 w-6 transform rounded-full bg-white shadow transition"
                                                        x-bind:class="allInclusive ? 'translate-x-9' : 'translate-x-1'"
                                                    ></span>
                                                </button>
                                            </div>

                                            <div class="mt-2 flex justify-between text-[11px] font-bold">
                                                <span x-bind:class="!allInclusive ? 'text-slate-900' : 'text-slate-400'">
                                                    Normal
                                                </span>
                                                <span x-bind:class="allInclusive ? 'text-emerald-700' : 'text-slate-400'">
                                                    All Inclusive
                                                </span>
                                            </div>
                                        </div>

                                        <p class="ride-package-price-label">
                                            Estimated Trip Fare
                                        </p>

                                        <strong
                                            x-text="formatInr(allInclusive ? inclusiveTotal : normalTotal)"
                                        ></strong>

                                        <p class="mt-1 text-xs font-semibold text-slate-500">
                                            <span x-show="!allInclusive">
                                                Driver allowance included in displayed total
                                            </span>

                                            <span x-show="allInclusive" x-cloak class="text-emerald-700">
                                                Driver allowance, toll tax and state tax included
                                            </span>
                                        </p>

                                        <button
                                            type="button"
                                            x-on:click="fareDetailsOpen = !fareDetailsOpen"
                                            class="ride-fare-icon-button"
                                            aria-label="View fare details"
                                            title="Fare details"
                                        >
                                            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                                            <span x-text="fareDetailsOpen ? 'Hide Fare Details' : 'View Fare Details'"></span>
                                        </button>

                                        <div
                                            x-show="fareDetailsOpen"
                                            x-collapse
                                            class="mt-3 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white text-left shadow-sm"
                                        >
                                            <div class="border-b border-slate-100 bg-slate-50 px-4 py-3">
                                                <p class="text-sm font-black text-slate-900">
                                                    Fare Summary
                                                    <span x-show="allInclusive" x-cloak class="text-emerald-700">
                                                        (All Inclusive)
                                                    </span>
                                                </p>
                                            </div>

                                            <div class="space-y-0 px-4 py-2 text-sm">
                                                <div class="flex justify-between gap-4 border-b border-slate-100 py-2.5">
                                                    <span class="font-semibold text-slate-600">KM Rate</span>
                                                    <strong>₹{{ number_format($kmRate, 0) }} / KM</strong>
                                                </div>

                                                <div class="flex justify-between gap-4 border-b border-slate-100 py-2.5">
                                                    <span class="font-semibold text-slate-600">Actual Route Distance</span>
                                                    <strong>{{ number_format($actualRouteKm, 0) }} KM</strong>
                                                </div>

                                                <div class="flex justify-between gap-4 border-b border-slate-100 py-2.5">
                                                    <span class="font-semibold text-slate-600">Total Days</span>
                                                    <strong>{{ $tripDays }} Days</strong>
                                                </div>

                                                <div class="flex justify-between gap-4 border-b border-slate-100 py-2.5">
                                                    <span class="font-semibold text-slate-600">Minimum Billing</span>
                                                    <strong>
                                                        {{ number_format($minimumKmPerDay, 0) }} KM × {{ $tripDays }}
                                                    </strong>
                                                </div>

                                                <div class="flex justify-between gap-4 border-b border-slate-100 py-2.5">
                                                    <span class="font-semibold text-slate-600">Billable KM</span>
                                                    <strong>{{ number_format($billableKm, 0) }} KM</strong>
                                                </div>

                                                <div class="flex justify-between gap-4 border-b border-slate-100 py-2.5">
                                                    <span class="font-semibold text-slate-600">Vehicle Fare</span>
                                                    <strong>{{ Number::currency($vehicleFare, 'INR') }}</strong>
                                                </div>

                                                <div
                                                    x-show="!allInclusive"
                                                    class="flex justify-between gap-4 border-b border-slate-100 py-2.5"
                                                >
                                                    <span class="font-semibold text-slate-600">Driver Allowance</span>
                                                    <strong>{{ Number::currency($driverAllowance, 'INR') }}</strong>
                                                </div>

                                                <div
                                                    x-show="!allInclusive"
                                                    class="space-y-2 border-b border-slate-100 py-2.5 text-xs"
                                                >
                                                    <div class="flex justify-between gap-4">
                                                        <span class="font-semibold text-slate-600">Toll Tax</span>
                                                        <strong class="text-amber-700">As Actual (Pay Direct)</strong>
                                                    </div>
                                                    <div class="flex justify-between gap-4">
                                                        <span class="font-semibold text-slate-600">State Tax</span>
                                                        <strong class="text-amber-700">As Actual (Pay Direct)</strong>
                                                    </div>
                                                    <div class="flex justify-between gap-4">
                                                        <span class="font-semibold text-slate-600">Parking Charges</span>
                                                        <strong class="text-amber-700">As Actual (Pay Direct)</strong>
                                                    </div>
                                                </div>

                                                <div
                                                    x-show="allInclusive"
                                                    x-cloak
                                                    class="border-b border-slate-100 py-2.5"
                                                >
                                                    <div class="flex justify-between gap-4">
                                                        <span class="font-semibold text-slate-600">
                                                            All Inclusive Charge
                                                        </span>
                                                        <strong class="text-emerald-700">
                                                            {{ Number::currency($allInclusiveCharge, 'INR') }}
                                                        </strong>
                                                    </div>

                                                    <p class="mt-1 text-[11px] font-semibold leading-5 text-emerald-700">
                                                        {{ number_format($billableKm, 0) }} KM × ₹{{ $allInclusiveRate }}.
                                                        Includes Driver Allowance, Toll Tax and State Tax.
                                                    </p>
                                                </div>

                                                <div
                                                    x-show="allInclusive"
                                                    x-cloak
                                                    class="flex justify-between gap-4 border-b border-slate-100 py-2.5"
                                                >
                                                    <span class="font-semibold text-slate-600">Parking Charges</span>
                                                    <strong class="text-amber-700">As Actual (Pay Direct)</strong>
                                                </div>
                                            </div>

                                            <div class="flex items-center justify-between gap-4 bg-slate-950 px-4 py-3 text-white">
                                                <span class="font-extrabold">Grand Total</span>
                                                <strong
                                                    class="text-lg font-black"
                                                    x-text="formatInr(allInclusive ? inclusiveTotal : normalTotal)"
                                                ></strong>
                                            </div>
                                        </div>

                                        <a
                                            href="#"
                                            x-on:click.prevent="
                                                $wire.addToCartReturn([
                                                    {{ $category->id }},
                                                    @js($nameTo),
                                                    @js($cityFrom),
                                                    allInclusive ? inclusiveTotal : normalTotal,
                                                    @js($date),
                                                    @js($dateto),
                                                    @js($time),
                                                    @js($tab),
                                                    {{ round($billableKm) }},
                                                    {{ (int) $category->new_vehicle }},
                                                    {{ (int) $category->pet_friendly }},
                                                    {{ (int) $category->roof_career }},
                                                    allInclusive ? 'all_inclusive' : 'normal',
                                                    {{ $vehicleFare }},
                                                    allInclusive ? {{ $allInclusiveCharge }} : {{ $driverAllowance }}
                                                ])
                                            "
                                            class="ride-select-button"
                                        >
                                            <span>Select Vehicle</span>
                                            <i class="fa-solid fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>

                @else
                    <div class="rides-layout-content w-full px-3 lg:w-3/4">
                        <div class="rides-filter-bar mb-4">
                            <label class="rides-filter-control">
                                <i class="fa-solid fa-arrow-down-wide-short"></i>
                                <span><small>Sort By</small>
                                    <select wire:model.live="sort">
                                        <option value="price">Price (Low to High)</option>
                                        <option value="latest">Latest First</option>
                                    </select>
                                </span>
                            </label>
                            <div class="rides-filter-control rides-filter-static">
                                <i class="fa-solid fa-car-side"></i>
                                <span><small>Vehicle Type</small><strong>All</strong></span>
                            </div>
                            <label class="rides-filter-control rides-price-filter">
                                <i class="fa-solid fa-tag"></i>
                                <span><small>Price Range</small><strong>{{ Number::currency($price_range, 'INR') }}</strong></span>
                                <input type="range" wire:model.live="price_range" min="1000" max="50000" step="500">
                            </label>
                            <button type="button" class="rides-reset-filter" wire:click="$set('price_range', 50000)">
                                <i class="fa-solid fa-rotate-right"></i><span>Reset Filters</span>
                            </button>
                            <strong class="rides-result-count">{{ $rides->total() }} Packages Found</strong>
                        </div>
                        <div class="w-full">

                            @if ($tab === 'self_drive')
                                <div class="mb-3 flex w-full items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
                                    <div class="min-w-0">
                                        <h2 class="truncate text-sm font-extrabold text-slate-900 sm:text-base">Available Self-Drive Vehicles</h2>
                                        <p class="text-xs text-slate-500">{{ $rides->total() }} vehicle(s) found</p>
                                    </div>
                                </div>

                                @if ($selfDrivePeriodInvalid ?? false)
                                    <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 p-5 text-red-800 shadow-sm">
                                        <div class="flex items-start gap-3">
                                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-100 text-xl text-red-600">
                                                <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <h3 class="text-lg font-extrabold">Invalid rental period</h3>
                                                <p class="mt-1 text-sm leading-6">Drop date and time must be later than pickup date and time.</p>
                                                <button type="button" wire:click="showEditQueryModal"
                                                    class="mt-4 inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-red-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-red-700">
                                                    <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                                                    Modify Rental Schedule
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                

@elseif ($selectedVehicleBooked ?? false)
    <div class="mb-4 rounded-2xl border border-amber-300 bg-amber-50 p-5 text-amber-900 shadow-sm">
        <div class="flex items-start gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-amber-100 text-xl text-amber-700">
                <i class="fa-solid fa-calendar-xmark" aria-hidden="true"></i>
            </div>

            <div class="min-w-0">
                <h3 class="text-lg font-extrabold">
                    This car is already booked for the selected date &amp; time.
                </h3>

                <p class="mt-1 text-sm leading-6">
                    Please change your pickup or drop date and time to check this vehicle again.
                </p>

                <div class="mt-4 flex flex-wrap gap-3">
                    <button type="button"
                        wire:click="showEditQueryModal"
                        class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-blue-700">
                        <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
                        Modify Rental Schedule
                    </button>

                    <button type="button"
                        wire:click="viewOtherSelfDriveCars"
                        class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-amber-400 bg-white px-5 py-2.5 text-sm font-bold text-amber-800 transition hover:bg-amber-100">
                        <i class="fa-solid fa-car-side" aria-hidden="true"></i>
                        Browse Similar Vehicles
                    </button>
                </div>
            </div>
        </div>
    </div>
@else
    <div class="sd-list">
        @forelse ($rides as $vehicle)
            @php
                $hourlyPrice = max(
                    0,
                    (float) ($vehicle->hourly_price ?? 0)
                );

                $minimumBookingHours = max(
                    1,
                    (int) ($vehicle->minimum_booking_hours ?? 1)
                );

                $billableHours = max(
                    $selfDriveHours,
                    $minimumBookingHours
                );

                $securityDeposit = max(
                    0,
                    (float) ($vehicle->security_deposit ?? 0)
                );

                $rentalTotal = $hourlyPrice * $billableHours;
                $hasValidPrice = $hourlyPrice > 0;

                $vehicleImage = $vehicle->front_image_url
                    ?: asset('cab_images/default-car.png');

                $vehicleGallery = collect($vehicle->gallery_images ?? [])
                    ->filter(
                        fn (array $image): bool =>
                            filled($image['url'] ?? null)
                    )
                    ->values()
                    ->all();

                if (empty($vehicleGallery)) {
                    $vehicleGallery = [
                        [
                            'key' => 'front',
                            'title' => 'Front View',
                            'url' => $vehicleImage,
                        ],
                    ];
                }

                $vendorName = data_get($vehicle, 'transporter.business_name')
                    ?: data_get($vehicle, 'transporter.company_name')
                    ?: data_get($vehicle, 'transporter.name')
                    ?: 'DuraCabs Partner';

                $vendorDistance = data_get($vehicle, 'vendor_distance')
                    ?? data_get($vehicle, 'distance_km')
                    ?? data_get($vehicle, 'distance');

                $distanceLabel = is_numeric($vendorDistance)
                    ? number_format((float) $vendorDistance, 1) . ' km from Pick-up Location'
                    : 'Pick-up at Partner Location';
            @endphp

            <article wire:key="self-drive-vehicle-{{ $vehicle->id }}"
                class="sd-premium-card">

                <div class="sd-premium-media group">
                    <button
                        type="button"
                        class="absolute inset-0 z-[2] cursor-zoom-in"
                        x-on:click="openGallery(
                            @js($vehicleGallery),
                            @js($vehicle->display_name),
                            0
                        )"
                        aria-label="View the complete vehicle gallery for {{ $vehicle->display_name }}"
                    >
                        <span class="sr-only">
                            View the complete vehicle gallery for {{ $vehicle->display_name }}
                        </span>
                    </button>

                    <img
                        src="{{ $vehicleImage }}"
                        alt="{{ $vehicle->display_name }}"
                        title="Open the vehicle gallery for {{ $vehicle->display_name }} photos"
                        loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                        fetchpriority="{{ $loop->first ? 'high' : 'auto' }}"
                        decoding="async"
                        width="420"
                        height="240"
                        class="transition duration-500 group-hover:scale-105"
                        onerror="this.onerror=null;this.src='{{ asset('cab_images/default-car.png') }}';"
                    >

                    <div
                        class="pointer-events-none absolute bottom-3 left-3 z-[4] inline-flex items-center gap-2 rounded-full border border-white/30 bg-slate-950/75 px-3 py-2 text-xs font-extrabold text-white shadow-lg backdrop-blur-md"
                    >
                        <i class="fa-solid fa-images" aria-hidden="true"></i>
                        <span>View Gallery</span>
                        <span class="rounded-full bg-white/20 px-1.5 py-0.5">
                            {{ count($vehicleGallery) }}
                        </span>
                    </div>

                    <div class="sd-premium-badges pointer-events-none z-[4]">
                        <span class="sd-premium-badge sd-premium-badge-blue">
                            <i class="fa-solid fa-car-side"></i>
                            Self-Drive Rental
                        </span>

                        @if ($vehicle->is_verified ?? false)
                            <span class="sd-premium-badge sd-premium-badge-green">
                                <i class="fa-solid fa-circle-check"></i>
                                Verified Partner
                            </span>
                        @endif
                    </div>

                    <span class="sd-premium-year">
                        {{ (int) ($vehicle->manufacture_year ?? 0) > 0
                            ? $vehicle->manufacture_year
                            : 'Premium' }}
                    </span>
                </div>

                <div class="sd-premium-body">
                    <div class="sd-premium-heading">
                        <div>
                            <p class="sd-premium-eyebrow">{{ $vendorName }}</p>
                            <h3>{{ $vehicle->display_name }}</h3>
                        </div>

                        <div class="sd-premium-rating">
                            <i class="fa-solid fa-star"></i>
                            <strong>4.8</strong>
                            <span>Highly Rated</span>
                        </div>
                    </div>

                    <p class="sd-premium-location">
                        <i class="fa-solid fa-location-dot"></i>
                        {{ $distanceLabel }}
                    </p>

                    <div class="sd-premium-specs">
                        <span>
                            <i class="fa-solid fa-gas-pump"></i>
                            <small>Fuel Type</small>
                            <strong>
                                {{ filled($vehicle->fuel_type)
                                    ? ucfirst($vehicle->fuel_type)
                                    : 'N/A' }}
                            </strong>
                        </span>

                        <span>
                            <i class="fa-solid fa-gears"></i>
                            <small>Transmission Type</small>
                            <strong>
                                {{ filled($vehicle->transmission)
                                    ? ucfirst($vehicle->transmission)
                                    : 'N/A' }}
                            </strong>
                        </span>

                        <span>
                            <i class="fa-solid fa-users"></i>
                            <small>Seating Capacity</small>
                            <strong>{{ $vehicle->seats ?: 'N/A' }}</strong>
                        </span>

                        <span>
                            <i class="fa-regular fa-clock"></i>
                            <small>Rental Duration</small>
                            <strong>{{ $selfDriveHours }} hrs</strong>
                        </span>
                    </div>

                    <div class="sd-premium-policy">
                        <span>
                            <i class="fa-solid fa-shield-halved"></i>
                            Security {{ Number::currency($securityDeposit, 'INR') }}
                        </span>

                        <span>
                            <i class="fa-solid fa-bolt"></i>
                            Instant Confirmation Available
                        </span>

                        <span>
                            <i class="fa-solid fa-headset"></i>
                            24×7 Customer Assistance
                        </span>
                    </div>
                </div>

                <div class="sd-premium-booking">
                    <p class="sd-premium-price-label">Rental Charges</p>

                    @if ($hasValidPrice)
                        <div class="sd-premium-price">
                            {{ Number::currency($rentalTotal, 'INR') }}
                        </div>

                        <p class="sd-premium-rate">
                            {{ Number::currency($hourlyPrice, 'INR') }} / hour
                        </p>

                        <div class="sd-premium-billing">
                            <span>
                                Selected
                                <strong>{{ $selfDriveHours }} hrs</strong>
                            </span>

                            <span>
                                Billable
                                <strong>{{ $billableHours }} hrs</strong>
                            </span>
                        </div>

                        @if ($selfDriveHours < $minimumBookingHours)
                            <p class="sd-premium-minimum">
                                <i class="fa-solid fa-circle-info"></i>
                                Minimum {{ $minimumBookingHours }} hour billing applies
                            </p>
                        @endif
                    @else
                        <div class="sd-premium-unavailable">
                            Currently Unavailable
                        </div>

                        <p class="sd-premium-rate">
                            Pricing Available on Request
                        </p>
                    @endif

                    <button type="button"
                        wire:click="addToCartSelfDrive({{ $vehicle->id }})"
                        wire:loading.attr="disabled"
                        wire:target="addToCartSelfDrive({{ $vehicle->id }})"
                        @disabled(!$hasValidPrice)
                        class="sd-premium-cta">

                        <span wire:loading.remove
                            wire:target="addToCartSelfDrive({{ $vehicle->id }})">

                            {{ $hasValidPrice ? 'Continue with this Vehicle' : 'Currently Unavailable' }}

                            @if ($hasValidPrice)
                                <i class="fa-solid fa-arrow-right"></i>
                            @endif
                        </span>

                        <span wire:loading
                            wire:target="addToCartSelfDrive({{ $vehicle->id }})">

                            <i class="fa-solid fa-spinner fa-spin"></i>
                            Processing...
                        </span>
                    </button>

                    <div class="mt-3 flex flex-wrap items-center justify-center gap-2">
                        <span class="sd-premium-safe !m-0 inline-flex items-center gap-1.5">
                            <i class="fa-solid fa-lock"></i>
                            Secure Reservation
                        </span>

                        @if ($hasValidPrice)
                            <button
                                type="button"
                                x-on:click="openSelfDriveFare(@js([
                                    'vehicleName' => $vehicle->display_name,
                                    'hourlyPrice' => $hourlyPrice,
                                    'selectedHours' => $selfDriveHours,
                                    'minimumHours' => $minimumBookingHours,
                                    'billableHours' => $billableHours,
                                    'rentalTotal' => $rentalTotal,
                                    'securityDeposit' => $securityDeposit,
                                    'payableAtBooking' => $rentalTotal + $securityDeposit,
                                ]))"
                                class="inline-flex h-7 items-center justify-center gap-1.5 rounded-full border border-sky-200 bg-sky-50 px-2.5 text-[11px] font-extrabold text-sky-700 transition hover:border-sky-300 hover:bg-sky-100 focus:outline-none focus:ring-4 focus:ring-sky-100"
                                aria-label="View fare details for {{ $vehicle->display_name }}"
                                title="Rental Cost Breakdown"
                            >
                                <i class="fa-solid fa-circle-info text-xs" aria-hidden="true"></i>
                                <span>Rental Cost Breakdown</span>
                            </button>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="w-full rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-blue-50 text-2xl">
                    🚗
                </div>

                <h3 class="mt-4 text-xl font-extrabold text-slate-900">
                    No Self-Drive Vehicles Available
                </h3>

                <p class="mt-2 text-sm text-slate-600">
                    Please modify your pick-up location, rental schedule, or filters to view available vehicles.
                </p>

                <button type="button"
                    wire:click="showEditQueryModal"
                    class="mt-5 rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white hover:bg-blue-700">
                    Modify Search
                </button>
            </div>
        @endforelse
    </div>
@endif

								
                            @else
                            @foreach ($rides as $ride)
                                @foreach ($ride->prices as $price)
                                    @php
                                        $displayPrice = $tab === 'local' ? $price->price * max(1, (int) $cars) : $price->price;
                                        $displayMaxPrice = $tab === 'local' ? $price->max_price * max(1, (int) $cars) : $price->max_price;
                                        $badgeLabel = $loop->parent->first && $loop->first ? 'Best price' : ($loop->parent->iteration === 2 ? 'Popular' : 'Comfort');
                                        $badgeClass = $loop->parent->first && $loop->first ? 'ride-package-badge--green' : ($loop->parent->iteration === 2 ? 'ride-package-badge--blue' : 'ride-package-badge--purple');
                                    @endphp
                                    <article wire:key="ride-{{ $ride->id }}-price-{{ $price->id ?? $loop->index }}" class="ride-package-card">
                                        <div class="ride-package-media">
                                            <span class="ride-package-badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                                            <a href="/route/{{ $ride->slug }}" aria-label="View {{ $ride->name }} route details">
                                                <img src="{{ url('storage') }}/{{ $price->category->image }}" alt="{{ $price->category->name }}" loading="lazy">
                                            </a>
                                        </div>
                                        <div class="ride-package-content">
                                            <h3>{{ $price->category->name }}</h3>
                                            <div class="ride-package-rating" aria-label="5 star rated">
                                                @for ($star = 0; $star < 5; $star++)
                                                    <i class="fa-solid fa-star" aria-hidden="true"></i>
                                                @endfor
                                                <span>5.0</span>
                                            </div>
                                            <p class="ride-package-model">{{ $price->category->model ?: $ride->name }} or similar</p>
                                            <div class="ride-package-features">
                                                <span><i class="fa-solid fa-bottle-water"></i>Water Bottle</span>
                                                <span><i class="fa-solid fa-bolt"></i>Instant Booking</span>
                                                <span><i class="fa-solid fa-user-shield"></i>Trusted Driver</span>
                                                <span><i class="fa-solid fa-snowflake"></i>AC</span>
                                            </div>
                                        </div>
                                        <div class="ride-package-price">
                                            @if ($displayMaxPrice > $displayPrice)
                                                <del>{{ Number::currency($displayMaxPrice, 'INR') }}</del>
                                            @endif
                                            <strong>{{ Number::currency($displayPrice, 'INR') }}</strong>
                                            @if ($tab === 'local')
                                                <button type="button"
                                                    onclick="showFareSummaryLocal('{{ addslashes($ride->name) }}', '{{ addslashes($price->category->name) }}', {{ $displayPrice }}, {{ $displayMaxPrice }}, {{ max(1, (int) $cars) }}, '{{ addslashes((string) $plan) }}', {{ $ride->extra_km_charge ?? 0 }}, {{ $ride->extra_hr_charge ?? 0 }}, {{ $ride->driver_allowances ?? 0 }})"
                                                    class="ride-fare-icon-button" aria-label="View fare details" title="Fare details">
                                                    <i class="fa-solid fa-circle-info"></i><span>Fare details</span>
                                                </button>
                                                <a href="#" wire:click.prevent='addToCartLocal([{{ $ride->id }},"{{ $time }}","{{ $tab }}","{{ $date }}","{{ $plan }}","{{ $cars }}","{{ $displayPrice }}","{{ $ride->name }}", "{{ $price->category->name }}","{{ $ride->toll_tax }}","{{ $ride->category->new_vehicle }}","{{ $ride->category->pet_friendly }}","{{ $ride->category->roof_career }}"])' class="ride-select-button">
                                                    <span>Select Vehicle</span><i class="fa-solid fa-arrow-right"></i>
                                                </a>
                                            @else
                                                <button type="button"
                                                    onclick="showFareSummaryOneWay('{{ addslashes($ride->name) }}', '{{ addslashes($price->category->name) }}', {{ $displayPrice }}, {{ $displayMaxPrice }}, {{ $ride->toll_tax ?? 0 }}, {{ $ride->km_limit ?? 0 }}, {{ $ride->hr_limit ?? 0 }}, {{ $ride->extra_km_charge ?? 0 }}, {{ $ride->extra_hr_charge ?? 0 }})"
                                                    class="ride-fare-icon-button" aria-label="View fare details" title="Fare details">
                                                    <i class="fa-solid fa-circle-info"></i><span>Fare details</span>
                                                </button>
                                                <a href="#" wire:click.prevent='addToCartOneWay([{{ $ride->id }},"{{ $time }}","{{ $tab }}","{{ $date }}","{{ $displayPrice }}","{{ $ride->name }}", "{{ $price->category->name }}","{{ $ride->toll_tax }}","{{ $ride->category->new_vehicle }}","{{ $ride->category->pet_friendly }}","{{ $ride->category->roof_career }}"])' class="ride-select-button">
                                                    <span>Select Vehicle</span><i class="fa-solid fa-arrow-right"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </article>
                                @endforeach
                            @endforeach
                            @endif





                        </div>
                        <!-- pagination start -->
                        <div class="flex justify-end mt-6">
                            {{ $rides->links() }}
                        </div>
                        <!-- pagination end -->
                    </div>

                @endif



                <div class="w-full pr-2 lg:w-1/4 lg:hidden block">



                    <div class="rides-side-card p-4 mb-5 bg-white border border-gray-200 dark:border-gray-900 dark:bg-gray-900">
                        <h2 class="text-2xl font-medium text-sky-500 dark:text-gray-400"> Need Booking Assistance?</h2>
                        <div class="flex mt-3">
                            <p>Contact our customer assistance team. Support is available 24×7.</p>

                        </div>
                        <div class="flex mt-3">
                            <div class="bg-sky-600 p-1 text-sky-300 rounded-lg">
                                <i class="fa-solid fa-phone" aria-hidden="true"></i>
                            </div>
                            &nbsp; <a href="tel:+91-7088873331"
                                class="text-sky-700 font-bold text-xl">+91-7088873331</a>

                        </div>
                        <div class="flex mt-3">
                            <div class="bg-green-500 p-1 text-sky-300 rounded-full">
                                <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
                            </div>
                            &nbsp; &nbsp; <a href="tel:+91-7088873331"
                                class="text-green-500 font-bold text-xl">+91-7088873331</a>

                        </div>
                    </div>


                    @if (!$tab)

                        <div class="rides-side-card p-4 mb-5 bg-white border border-gray-200 dark:border-gray-900 dark:bg-gray-900">
                            <h2 class="text-2xl font-bold dark:text-gray-400">Cab Categories</h2>
                            {{-- {{json_encode($selected_categories)}} --}}
                            <div class="w-16 pb-2 mb-6 border-b border-rose-600 dark:border-gray-400"></div>

                            <ul>
                                @foreach ($categories as $category)
                                    <li class="mb-4" wire:key='{{ $category->id }}'>
                                        <label for="{{ $category->slug }}"
                                            class="flex items-center dark:text-gray-400 ">
                                            <input type="checkbox" wire:model.live='selected_categories'
                                                id="{{ $category->slug }}" value="{{ $category->id }}"
                                                class="w-4 h-4 mr-2">
                                            <span class="text-lg">{{ $category->name }} </span>
                                        </label>
                                    </li>
                                @endforeach

                            </ul>

                        </div>
                        <div class="p-4 mb-5 bg-white border border-gray-200 dark:bg-gray-900 dark:border-gray-900">
                            <h2 class="text-2xl font-bold dark:text-gray-400">Destination</h2>
                            <input type="text" wire:model.live='query2' placeholder="Search City.."
                                id="simple-search-1"
                                class="lg:mt-3 bg-gray-50 border border-gray-300 text-black font-extrabold  text-xm focus:ring-blue-500 focus:border-blue-500 block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                            <div class="w-16 pb-2 mb-6 border-b border-rose-600 dark:border-gray-400"></div>
                            <ul>
                                @foreach ($brands as $brand)
                                    <li class="mb-4" wire:key='{{ $brand->id }}'>
                                        <label for="{{ $brand->slug }}"
                                            class="flex items-center dark:text-gray-300">
                                            <input type="checkbox" wire:model.live='selected_brands'
                                                id='{{ $brand->slug }}' value="{{ $brand->id }}"
                                                class="w-4 h-4 mr-2">
                                            <span class="text-lg dark:text-gray-400">{{ $brand->name }}</span>
                                        </label>
                                    </li>
                                @endforeach

                            </ul>

                        </div>


                        <div class="p-4 mb-5 bg-white border border-gray-200 dark:bg-gray-900 dark:border-gray-900">
                            <h2 class="text-2xl font-bold dark:text-gray-400">Price</h2>
                            <div class="w-16 pb-2 mb-6 border-b border-rose-600 dark:border-gray-400"></div>
                            <div>
                                <div class="font-semibold">{{ Number::currency($price_range, 'INR') }}</div>
                                <input type="range" wire:model.live='price_range'
                                    class="w-full h-1 mb-4 bg-blue-100 rounded appearance-none cursor-pointer"
                                    max="50000" value="100" step="10">
                                <div class="flex justify-between ">
                                    <span
                                        class="inline-block text-lg font-bold text-blue-400 ">{{ Number::currency(1000, 'INR') }}</span>
                                    <span
                                        class="inline-block text-lg font-bold text-blue-400 ">{{ Number::currency(50000, 'INR') }}</span>
                                </div>
                            </div>
                        </div>

                    @endif


                </div>

            </div>
        </div>
        @unless ($isRouteHub ?? false)
            <section class="max-w-4xl mx-auto px-4 py-10 bg-white">
                <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-6">
                    Explore Flexible Ride Options with Duracabs – From Local Cabs to Intercity One Way Taxi Services
                </h1>

                <p class="text-base md:text-lg text-gray-700 mb-4">
                    Duracabs brings you an extensive selection of ride categories to suit your travel needs—whether you're
                    looking for a convenient
                    <strong class="font-semibold">taxi service</strong> nearby or planning a long-distance journey like a
                    <strong class="font-semibold">Delhi to Agra taxi</strong> or
                    <strong class="font-semibold">Agra to Delhi taxi</strong>. Our platform makes
                    <strong class="font-semibold">online cab booking</strong> fast, easy, and reliable.
                </p>
            </section>
        @endunless

    </section>
    <section class="max-w-5xl mx-auto px-4 py-10 bg-white">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">
            Flexible Travel Options: From Car Rentals to Airport Taxis & Tempo Travellers
        </h2>


        <p class="text-base md:text-lg text-gray-700 mb-4">
            Need more freedom? Try our affordable
            <strong class="font-semibold">car rental</strong> options or choose a
            <strong class="font-semibold">one way cab</strong> to skip return trip costs. For airport transfers, our
            <strong class="font-semibold">Delhi airport taxi booking</strong> ensures timely pickup and drop. Traveling
            in a group?
            Book a spacious <strong class="font-semibold">tempo traveller for rent</strong> for a hassle-free
            experience.
        </p>

        <h2 class="text-2xl font-semibold text-gray-800 mb-4">
            Clean, GPS-Enabled Fleet for Reliable One Way Taxi Services and Local Travel
        </h2>

        <p class="text-base md:text-lg text-gray-700">
            Every vehicle in our fleet is clean, GPS-enabled, and ready for your journey. From solo city rides to
            cross-state travel,
            Duracabs is your smart choice for
            <strong class="font-semibold">one way taxi services</strong> and reliable
            <strong class="font-semibold">taxi service near me</strong>.
        </p>
    </section>

    <!-- Enhanced Fare details Popup -->
    <div id="fareSummaryModal"
        class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div
            class="bg-white rounded-xl shadow-2xl max-w-lg w-full mx-4 transform transition-all duration-300 scale-95">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-4 rounded-t-xl">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="fa-solid fa-calculator" aria-hidden="true"></i>
                        <h3 class="text-xl font-bold">Fare Breakdown</h3>
                    </div>
                    <button onclick="closeFareSummary()"
                        class="text-white hover:text-gray-200 transition duration-200">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div class="p-6 space-y-4">
                <!-- Vehicle Information -->
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-700 font-semibold flex items-center">
                            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                            Vehicle Category:
                        </span>
                        <span id="carCategory" class="font-bold text-blue-700"></span>
                    </div>
                </div>

                <!-- Fare Breakdown -->
                <div class="space-y-3">
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-600 font-medium">Base Fare:</span>
                        <span id="baseFare" class="font-semibold text-gray-900"></span>
                    </div>

                    <div id="driverAllowanceSection"
                        class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-600 font-medium">Driver Allowance:</span>
                        <span id="driverAllowance" class="font-semibold text-gray-900">Included</span>
                    </div>

                    <div id="tollTaxSection" class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-600 font-medium">Toll Tax:</span>
                        <span id="tollTaxStatus" class="font-semibold text-red-600">Excluded</span>
                    </div>

                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-600 font-medium">GST (5%):</span>
                        <span id="gstAmount" class="font-semibold text-gray-900"></span>
                    </div>
                </div>

                <!-- Total -->
                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-bold text-gray-800">Total Amount:</span>
                        <span id="totalPrice" class="text-2xl font-bold text-green-600"></span>
                    </div>
                </div>

                <!-- Important Notes -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                        <div>
                            <h4 class="font-semibold text-yellow-800 mb-2">Important Information:</h4>
                            <div id="fareNotes" class="text-sm text-yellow-700">
                                Excess distance charges apply after <span id="extraKmLimit"></span> km at ₹<span
                                    id="extraKmRate"></span>/km.<br>
                                Night allowance after 8:00 PM: ₹0<br>
                                <strong>Toll-Tax:</strong> Excluded |
                                <strong>Parking:</strong> Extra (if applicable)
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Note -->
                <div class="text-center text-sm text-gray-500 bg-gray-50 p-3 rounded-lg">
                    <div class="flex items-center justify-center">
                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                        Excess distance charges, where applicable, are payable directly to the service provider.
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="px-6 pb-6">
                <div class="flex space-x-3">
                    <button onclick="closeFareSummary()"
                        class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-3 px-4 rounded-lg font-semibold transition duration-200 flex items-center justify-center">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Edit Query Modal -->
    @if ($showOtpModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/60 px-4" wire:click.self="closeOtpModal">
            <div class="w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl">
                <div class="bg-gradient-to-r from-blue-700 to-sky-500 px-6 py-5 text-white">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[.18em] text-blue-100">DuraCabs secure verification</p>
                            <h3 class="mt-1 text-2xl font-extrabold">{{ $otpStage === 'mobile' ? 'Unlock exact fare' : 'Enter 4 digit OTP' }}</h3>
                        </div>
                        <button type="button" wire:click="closeOtpModal" class="rounded-full bg-white/15 p-2 text-white hover:bg-white/25" aria-label="Close">✕</button>
                    </div>
                </div>

                <div class="p-6">
                    @if ($otpStage === 'mobile')
                        <p class="mb-5 text-sm leading-6 text-slate-600">Enter your mobile number to view live cab prices and availability.</p>
                        <label class="mb-2 block text-sm font-bold text-slate-800">Mobile number</label>
                        <div class="flex overflow-hidden rounded-xl border border-slate-300 focus-within:border-blue-500 focus-within:ring-4 focus-within:ring-blue-100">
                            <span class="flex items-center bg-slate-50 px-4 font-bold text-slate-600">+91</span>
                            <input type="tel" maxlength="10" inputmode="numeric" wire:model.live="mobileNumber" wire:keydown.enter="sendFareOtp" class="min-h-12 w-full border-0 px-4 text-lg font-bold tracking-wide outline-none focus:ring-0" placeholder="9876543210" autofocus>
                        </div>
                        @error('mobileNumber')<p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>@enderror

                        <button type="button" wire:click="sendFareOtp" wire:loading.attr="disabled" class="mt-5 inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-blue-600 px-5 py-3 font-extrabold text-white hover:bg-blue-700 disabled:opacity-60">
                            <span wire:loading.remove wire:target="sendFareOtp">Send 4 digit OTP</span>
                            <span wire:loading wire:target="sendFareOtp">Sending…</span>
                        </button>
                    @else
                        <p class="text-sm text-slate-600">OTP sent to <strong>+91 {{ $mobileNumber }}</strong></p>
                        <button type="button" wire:click="$set('otpStage', 'mobile')" class="mt-1 text-sm font-bold text-blue-600">Change number</button>

                        <label class="mb-2 mt-5 block text-sm font-bold text-slate-800">4 digit OTP</label>
                        <input type="text" maxlength="4" inputmode="numeric" autocomplete="one-time-code" wire:model.live="otpCode" wire:keydown.enter="verifyFareOtp" class="min-h-14 w-full rounded-xl border border-slate-300 px-4 text-center text-3xl font-extrabold tracking-[.65em] outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100" placeholder="••••" autofocus>
                        @error('otpCode')<p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>@enderror

                        @if ($otpError)<div class="mt-3 rounded-xl bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ $otpError }}</div>@endif

                        <button type="button" wire:click="verifyFareOtp" wire:loading.attr="disabled" class="mt-5 inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-blue-600 px-5 py-3 font-extrabold text-white hover:bg-blue-700 disabled:opacity-60">
                            <span wire:loading.remove wire:target="verifyFareOtp">Verify & view fares</span>
                            <span wire:loading wire:target="verifyFareOtp">Verifying…</span>
                        </button>

                        <div class="mt-4 text-center text-sm text-slate-500">
                            <template x-if="otpSeconds > 0"><span>Resend OTP in <strong x-text="otpSeconds"></strong>s</span></template>
                            <button x-show="otpSeconds <= 0" type="button" wire:click="resendFareOtp" class="font-bold text-blue-600">Resend OTP</button>
                        </div>
                    @endif
                    <p class="mt-5 text-center text-xs leading-5 text-slate-500">By continuing, you agree to receive booking assistance related to this trip.</p>
                </div>
            </div>
        </div>
    @endif

    @if ($showEditModal)
        <div class="fixed inset-0 z-50 flex min-h-full items-start justify-center overflow-y-auto bg-slate-950/55 p-3 pt-8 backdrop-blur-sm sm:p-6 sm:pt-14"
            wire:click.self="$set('showEditModal', false)">
            <div class="relative w-full max-w-5xl rounded-3xl border border-white/70 bg-white p-4 shadow-2xl sm:p-6">
                <!-- Modal Header -->
                <div class="mb-5 flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div><p class="text-xs font-bold uppercase tracking-wider text-sky-600">Trip search</p><h3 class="mt-1 text-xl font-extrabold text-slate-900">Edit Trip</h3><p class="mt-1 text-sm text-slate-500">Update only the details for your current service.</p></div>
                    <button wire:click="$set('showEditModal', false)" type="button" aria-label="Close edit trip" class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:bg-slate-200 hover:text-slate-800">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>

                {{-- Single shared search engine: compact ride-edit mode. --}}
                @include('livewire.service-search-panel', [
                    'searchPanelMode' => 'ride_edit',
                ])
            </div>
        </div>
@endif


{{-- Self-Drive Rental Fare Summary --}}
<template x-teleport="body">
    <div
        x-cloak
        x-show="selfDriveFareOpen"
        x-transition.opacity.duration.180ms
        class="fixed inset-0 z-[999998] flex items-end justify-center bg-slate-950/65 p-0 backdrop-blur-sm sm:items-center sm:p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="self-drive-fare-title"
        x-on:click.self="closeSelfDriveFare()"
    >
        <div
            x-show="selfDriveFareOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-y-full opacity-0 sm:translate-y-3 sm:scale-95"
            x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-y-0 opacity-100 sm:scale-100"
            x-transition:leave-end="translate-y-full opacity-0 sm:translate-y-3 sm:scale-95"
            class="w-full max-w-[460px] overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl"
        >
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 bg-white px-4 py-3">
                <div class="min-w-0">
                    <h2 id="self-drive-fare-title" class="flex items-center gap-2 text-base font-black text-slate-900">
                        <span class="grid h-7 w-7 place-items-center rounded-full bg-sky-100 text-xs text-sky-700">
                            <i class="fa-solid fa-receipt" aria-hidden="true"></i>
                        </span>
                        Rental Cost Breakdown
                    </h2>
                    <p class="mt-0.5 truncate pl-9 text-[11px] font-semibold text-slate-500" x-text="selfDriveFare.vehicleName"></p>
                </div>

                <button
                    x-ref="selfDriveFareCloseButton"
                    type="button"
                    x-on:click="closeSelfDriveFare()"
                    class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-slate-100 text-sm text-slate-600 transition hover:bg-slate-200 hover:text-slate-900 focus:outline-none focus:ring-4 focus:ring-slate-100"
                    aria-label="Close rental cost breakdown"
                >
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>

            <div class="max-h-[68vh] overflow-y-auto p-4">
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white text-sm">
                    <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-3 py-2.5">
                        <span class="font-semibold text-slate-600">Base Rental Charge</span>
                        <strong class="text-slate-950" x-text="formatInr(selfDriveFare.rentalTotal)"></strong>
                    </div>

                    <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-3 py-2.5">
                        <span class="font-semibold text-slate-600">Refundable Security Deposit</span>
                        <strong class="text-amber-700" x-text="formatInr(selfDriveFare.securityDeposit)"></strong>
                    </div>

                    <div class="flex items-center justify-between gap-4 bg-slate-950 px-3 py-3 text-white">
                        <span class="font-extrabold">Estimated Total Payable</span>
                        <strong class="text-lg font-black" x-text="formatInr(selfDriveFare.payableAtBooking)"></strong>
                    </div>
                </div>

                <p class="mt-2 text-center text-[11px] font-semibold text-slate-500">
                    <span x-text="selfDriveFare.billableHours || 0"></span> hrs ×
                    <span x-text="formatInr(selfDriveFare.hourlyPrice)"></span>/hr
                </p>

                <template x-if="Number(selfDriveFare.selectedHours || 0) < Number(selfDriveFare.minimumHours || 0)">
                    <div class="mt-3 flex items-start gap-2 rounded-xl border border-sky-200 bg-sky-50 px-3 py-2.5 text-xs font-semibold leading-5 text-sky-900">
                        <i class="fa-solid fa-circle-info mt-0.5 shrink-0" aria-hidden="true"></i>
                        <p>
                            A minimum rental duration of <span x-text="selfDriveFare.minimumHours"></span> hours applies.
                        </p>
                    </div>
                </template>

                <div class="mt-3 rounded-xl bg-slate-50 px-3 py-2.5 text-[11px] font-medium leading-5 text-slate-600">
                    <p><i class="fa-solid fa-circle-check mr-1 text-emerald-600"></i>The security deposit is refundable after the vehicle return inspection is completed.</p>
                    <p><i class="fa-solid fa-circle-info mr-1 text-sky-600"></i>Additional charges, where applicable, will be calculated in accordance with the rental agreement and booking terms.</p>
                </div>

                <button
                    type="button"
                    x-on:click="closeSelfDriveFare()"
                    class="mt-3 inline-flex h-10 w-full items-center justify-center rounded-xl bg-sky-600 px-4 text-sm font-extrabold text-white transition hover:bg-sky-700"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</template>

{{-- Self-Drive Rental Vehicle Gallery --}}
<template x-teleport="body">
    <div
        x-cloak
        x-show="galleryOpen"
        x-transition.opacity.duration.200ms
        class="fixed inset-0 z-[999999] flex items-center justify-center bg-slate-950/95"
        role="dialog"
        aria-modal="true"
        aria-label="Vehicle gallery"
        x-on:click.self="closeGallery()"
    >
        <div class="relative flex h-full w-full flex-col overflow-hidden">
            <div
                class="relative z-20 flex shrink-0 items-center justify-between gap-4 border-b border-white/10 bg-slate-950/80 px-4 py-3 text-white backdrop-blur-md sm:px-6"
            >
                <div class="min-w-0">
                    <h2
                        class="truncate text-base font-extrabold sm:text-xl"
                        x-text="galleryVehicleName"
                    ></h2>

                    <p class="mt-0.5 text-xs font-semibold text-slate-300">
                        <span x-text="galleryIndex + 1"></span>
                        /
                        <span x-text="galleryImages.length"></span>

                        <span
                            x-show="galleryImages[galleryIndex]?.title"
                            class="ml-2"
                        >
                            •
                            <span
                                x-text="galleryImages[galleryIndex]?.title"
                            ></span>
                        </span>
                    </p>
                </div>

                <button
                    x-ref="galleryCloseButton"
                    type="button"
                    x-on:click="closeGallery()"
                    class="grid h-11 w-11 shrink-0 place-items-center rounded-full border border-white/20 bg-white/10 text-xl text-white transition hover:bg-white/20 focus:outline-none focus:ring-4 focus:ring-white/20"
                    aria-label="Close gallery"
                >
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>

            <div
                class="relative flex min-h-0 flex-1 items-center justify-center overflow-hidden px-3 py-4 sm:px-16 sm:py-6"
                x-on:touchstart.passive="startGalleryTouch($event)"
                x-on:touchend.passive="endGalleryTouch($event)"
            >
                <template
                    x-for="(image, index) in galleryImages"
                    :key="image.key || image.url || index"
                >
                    <img
                        x-show="galleryIndex === index"
                        x-transition.opacity.duration.200ms
                        :src="image.url"
                        :alt="`${galleryVehicleName} - ${image.title || 'Vehicle photo'}`"
                        class="max-h-full max-w-full select-none rounded-2xl object-contain shadow-2xl"
                        draggable="false"
                    >
                </template>

                <button
                    x-show="galleryImages.length > 1"
                    type="button"
                    x-on:click.stop="previousGalleryImage()"
                    class="absolute left-2 top-1/2 z-10 grid h-11 w-11 -translate-y-1/2 place-items-center rounded-full border border-white/20 bg-slate-950/70 text-lg text-white shadow-xl backdrop-blur transition hover:bg-white hover:text-slate-950 sm:left-6 sm:h-12 sm:w-12"
                    aria-label="Previous photo"
                >
                    <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                </button>

                <button
                    x-show="galleryImages.length > 1"
                    type="button"
                    x-on:click.stop="nextGalleryImage()"
                    class="absolute right-2 top-1/2 z-10 grid h-11 w-11 -translate-y-1/2 place-items-center rounded-full border border-white/20 bg-slate-950/70 text-lg text-white shadow-xl backdrop-blur transition hover:bg-white hover:text-slate-950 sm:right-6 sm:h-12 sm:w-12"
                    aria-label="Next photo"
                >
                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </button>
            </div>

            <div
                x-show="galleryImages.length > 0"
                class="relative z-20 shrink-0 border-t border-white/10 bg-slate-950/85 px-3 py-3 backdrop-blur-md sm:px-6"
            >
                <div class="mx-auto flex max-w-5xl gap-3 overflow-x-auto pb-1">
                    <template
                        x-for="(image, index) in galleryImages"
                        :key="`thumbnail-${image.key || image.url || index}`"
                    >
                        <button
                            type="button"
                            x-on:click="galleryIndex = index"
                            class="relative h-16 w-24 shrink-0 overflow-hidden rounded-xl border-2 bg-slate-900 transition sm:h-20 sm:w-28"
                            :class="galleryIndex === index
                                ? 'border-sky-400 ring-2 ring-sky-400/30'
                                : 'border-white/15 opacity-65 hover:border-white/50 hover:opacity-100'"
                            :aria-label="`View ${image.title || 'photo'}`"
                        >
                            <img
                                :src="image.url"
                                :alt="image.title || 'Vehicle photo'"
                                class="h-full w-full object-cover"
                                loading="lazy"
                            >

                            <span
                                class="absolute inset-x-0 bottom-0 truncate bg-slate-950/75 px-1.5 py-1 text-[10px] font-bold text-white"
                                x-text="image.title || `Photo ${index + 1}`"
                            ></span>
                        </button>
                    </template>
                </div>

                <p class="mt-2 text-center text-[11px] font-semibold text-slate-400">
                    Swipe on mobile devices or use the arrow keys
                </p>
            </div>
        </div>
    </div>
</template>

</div>