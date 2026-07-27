<div class="min-h-screen bg-slate-50 text-slate-900">
    @section('title', $page->meta_title)
    @section('description', $page->meta_description)
    @section('image', $imageMeta)

    @php
        $settings = is_array($selfDriveSettings ?? null) ? $selfDriveSettings : [];

        $pageName = filled($page->name) ? $page->name : 'Self Drive Cars';
        $cityName = data_get($page, 'brand.name');

        $heroTitle = data_get($settings, 'hero_title')
            ?: ($cityName ? "Self Drive Cars in {$cityName}" : $pageName);

        $heroSubtitle = data_get($settings, 'hero_subtitle')
            ?: 'Choose your car, select pickup and return date-time, and enjoy complete driving freedom.';

        $pickupPlaceholder = data_get($settings, 'pickup_placeholder')
            ?: 'Enter pickup city or location';

        $searchButtonText = data_get($settings, 'search_button_text')
            ?: 'Search Cars';

        $rentalModes = collect(
            data_get($settings, 'rental_modes', ['hourly', 'daily', 'weekly', 'monthly'])
        )
            ->filter(fn ($mode) => in_array($mode, ['hourly', 'daily', 'weekly', 'monthly'], true))
            ->values()
            ->all();

        if (empty($rentalModes)) {
            $rentalModes = ['hourly', 'daily', 'weekly', 'monthly'];
        }

        $defaultMode = data_get($settings, 'default_rental_mode', 'daily');

        if (! in_array($defaultMode, $rentalModes, true)) {
            $defaultMode = $rentalModes[0] ?? 'daily';
        }

        $weeklyDiscount = (int) data_get($settings, 'weekly_discount', 20);
        $monthlyDiscount = (int) data_get($settings, 'monthly_discount', 30);
        $serviceRadius = (int) data_get($settings, 'service_radius_km', 40);

        $searchEnabled = (bool) data_get($settings, 'search_enabled', true);
        $deliveryEnabled = (bool) data_get($settings, 'delivery_enabled', true);
        $showCategories = (bool) data_get($settings, 'show_categories', true);
        $showOffers = (bool) data_get($settings, 'show_offers', true);
        $showFaqs = (bool) data_get($settings, 'show_faqs', true);
    @endphp

    <section
        class="relative overflow-visible bg-slate-950"
        x-data="{
            rentalMode: @js($defaultMode),
            deliverySelected: false
        }"
    >
        <div class="absolute inset-0">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-950"></div>
            <div class="absolute -left-20 top-16 h-72 w-72 rounded-full bg-sky-500/20 blur-3xl"></div>
            <div class="absolute right-0 top-0 h-80 w-80 rounded-full bg-emerald-500/20 blur-3xl"></div>
            <div class="absolute bottom-0 left-1/3 h-64 w-64 rounded-full bg-cyan-500/10 blur-3xl"></div>
        </div>

        <div class="relative mx-auto w-full max-w-7xl px-4 pb-36 pt-12 sm:px-6 sm:pb-40 sm:pt-16 lg:px-8 lg:pb-44 lg:pt-20">
            <div class="mx-auto max-w-4xl text-center">
                <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-extrabold uppercase tracking-widest text-emerald-100 backdrop-blur">
                    <i class="fa-solid fa-car-side"></i>
                    Self Drive • Verified Cars • Flexible Plans
                </span>

                <h1 class="mt-6 text-4xl font-black tracking-tight text-white sm:text-5xl lg:text-6xl">
                    {{ $heroTitle }}
                </h1>

                <p class="mx-auto mt-4 max-w-3xl text-sm font-medium leading-7 text-slate-300 sm:text-base">
                    {{ $heroSubtitle }}
                </p>
            </div>
        </div>

        @if ($searchEnabled)
            <div class="relative z-[100] mx-auto -mt-28 w-full max-w-7xl px-4 pb-10 sm:-mt-32 sm:px-6 lg:px-8">
                <form
                    wire:submit.prevent="searchPackage"
                    autocomplete="off"
                    class="relative z-[110] overflow-visible rounded-3xl border border-slate-200 bg-white p-4 shadow-2xl shadow-slate-950/20 sm:p-5"
                >
                    <div class="mx-auto -mt-12 mb-5 flex max-w-3xl overflow-x-auto rounded-2xl border border-slate-200 bg-white p-1.5 shadow-xl">
                        @foreach ($rentalModes as $mode)
                            <button
                                type="button"
                                x-on:click="
                                    rentalMode = @js($mode);
                                    $wire.set('plan', @js($mode), false);
                                "
                                x-bind:class="rentalMode === @js($mode)
                                    ? 'bg-emerald-600 text-white shadow-md'
                                    : 'bg-white text-slate-600 hover:bg-slate-50'"
                                class="relative min-w-28 flex-1 rounded-xl px-4 py-3 text-center transition"
                            >
                                @if ($mode === 'weekly' && $weeklyDiscount > 0)
                                    <span class="absolute -right-1 -top-2 rounded-full bg-rose-500 px-2 py-0.5 text-xs font-black text-white">
                                        {{ $weeklyDiscount }}% OFF
                                    </span>
                                @elseif ($mode === 'monthly' && $monthlyDiscount > 0)
                                    <span class="absolute -right-1 -top-2 rounded-full bg-rose-500 px-2 py-0.5 text-xs font-black text-white">
                                        {{ $monthlyDiscount }}% OFF
                                    </span>
                                @endif

                                <span class="block text-sm font-black">
                                    {{ ucfirst($mode) }}
                                </span>

                                <span
                                    class="mt-0.5 block text-xs font-semibold"
                                    x-bind:class="rentalMode === @js($mode) ? 'text-emerald-100' : 'text-slate-400'"
                                >
                                    @switch($mode)
                                        @case('hourly')
                                            Short rides
                                            @break
                                        @case('daily')
                                            Up to 7 days
                                            @break
                                        @case('weekly')
                                            7+ day rides
                                            @break
                                        @case('monthly')
                                            Long-term
                                            @break
                                    @endswitch
                                </span>
                            </button>
                        @endforeach
                    </div>

                    <div class="grid grid-cols-1 overflow-visible rounded-2xl border border-slate-200 bg-white lg:grid-cols-4">
                        <div class="relative z-[120] border-b border-slate-200 p-4 lg:border-b-0 lg:border-r">
                            <label for="self-drive-city" class="flex items-center gap-2 text-xs font-bold text-slate-500">
                                <i class="fa-solid fa-location-dot text-emerald-600"></i>
                                Pickup City / Location
                            </label>

                            <input
                                id="self-drive-city"
                                type="text"
                                wire:model.live.debounce.350ms="querySelfDrive"
                                placeholder="{{ $pickupPlaceholder }}"
                                autocomplete="off"
                                class="mt-2 w-full border-0 bg-transparent p-0 text-base font-black text-slate-900 outline-none ring-0 placeholder:font-semibold placeholder:text-slate-400 focus:ring-0"
                            >

                            @if (
    mb_strlen(trim((string) $querySelfDrive)) >= 3
    && empty($selfDrivePlaceId)
)
                                <div class="absolute left-0 top-full z-[9999] mt-2 max-h-[320px] w-full min-w-[340px] overflow-y-auto overscroll-contain rounded-2xl border border-slate-200 bg-white p-1.5 shadow-2xl sm:min-w-[420px]">
                                    @if (!empty($cities_from) && count($cities_from) > 0)
                                        @foreach ($cities_from as $city)
                                            <button
                                                type="button"
                                                wire:key="self-drive-city-{{ $city->id }}"
                                                wire:click="updateSelfDriveCity(@js($city->name), @js($city->id))"
                                                class="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left transition hover:bg-emerald-50"
                                            >
                                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-emerald-50 text-emerald-600">
                                                    <i class="fa-solid fa-location-dot text-xs"></i>
                                                </span>

                                                <span class="min-w-0">
                                                    <span class="block truncate text-sm font-bold text-slate-900">
                                                        {{ $city->name }}
                                                    </span>

                                                    <span class="mt-0.5 block text-xs font-semibold text-slate-400">
                                                        Self Drive available
                                                    </span>
                                                </span>
                                            </button>
                                        @endforeach
                                    @else
                                        <div class="px-3 py-4 text-sm font-semibold text-slate-500">
                                            No Self Drive city found.
                                        </div>
                                    @endif
                                </div>
                            @endif

                            @if ($this->hasError('query'))
                                <p class="mt-2 text-xs font-bold text-red-600">
                                    {{ $this->getError('query') }}
                                </p>
                            @endif
                        </div>

                        <div class="border-b border-slate-200 p-4 lg:border-b-0 lg:border-r">
                            <label class="flex items-center gap-2 text-xs font-bold text-slate-500">
                                <i class="fa-regular fa-calendar text-emerald-600"></i>
                                Trip Start
                            </label>

                            <div class="mt-2 grid grid-cols-2 gap-3">
                                <input
                                    type="date"
                                    wire:model="date"
                                    min="{{ date('Y-m-d') }}"
                                    required
                                    class="min-w-0 border-0 bg-transparent p-0 text-sm font-black text-slate-900 outline-none ring-0 focus:ring-0"
                                >

                                <input
                                    type="time"
                                    wire:model="time"
                                    required
                                    class="min-w-0 border-0 bg-transparent p-0 text-sm font-black text-slate-900 outline-none ring-0 focus:ring-0"
                                >
                            </div>

                            @if ($this->hasError('date'))
                                <p class="mt-2 text-xs font-bold text-red-600">{{ $this->getError('date') }}</p>
                            @elseif ($this->hasError('time'))
                                <p class="mt-2 text-xs font-bold text-red-600">{{ $this->getError('time') }}</p>
                            @endif
                        </div>

                        <div class="border-b border-slate-200 p-4 lg:border-b-0 lg:border-r">
                            <label class="flex items-center gap-2 text-xs font-bold text-slate-500">
                                <i class="fa-regular fa-calendar-check text-emerald-600"></i>
                                Trip End
                            </label>

                            <div class="mt-2 grid grid-cols-2 gap-3">
                                <input
                                    type="date"
                                    wire:model="dateto"
                                    min="{{ date('Y-m-d') }}"
                                    required
                                    class="min-w-0 border-0 bg-transparent p-0 text-sm font-black text-slate-900 outline-none ring-0 focus:ring-0"
                                >

                                <input
                                    type="time"
                                    wire:model="endTime"
                                    required
                                    class="min-w-0 border-0 bg-transparent p-0 text-sm font-black text-slate-900 outline-none ring-0 focus:ring-0"
                                >
                            </div>

                            @if ($this->hasError('dateto'))
                                <p class="mt-2 text-xs font-bold text-red-600">{{ $this->getError('dateto') }}</p>
                            @elseif ($this->hasError('endTime'))
                                <p class="mt-2 text-xs font-bold text-red-600">{{ $this->getError('endTime') }}</p>
                            @endif
                        </div>

                        <div class="flex items-center p-3">
                            <button
                                type="submit"
                                wire:loading.attr="disabled"
                                wire:target="searchPackage"
                                class="flex h-14 w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 text-sm font-black uppercase tracking-wide text-white shadow-lg shadow-emerald-600/25 transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-200 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <i wire:loading.remove wire:target="searchPackage" class="fa-solid fa-magnifying-glass"></i>
                                <span wire:loading.remove wire:target="searchPackage">{{ $searchButtonText }}</span>
                                <span wire:loading wire:target="searchPackage">Searching...</span>
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-col gap-3 px-1 sm:flex-row sm:items-center sm:justify-between">
                        @if ($deliveryEnabled)
                            <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-semibold text-slate-600">
                                <input
                                    type="checkbox"
                                    x-model="deliverySelected"
                                    class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                >
                                Delivery & Pick-up from anywhere
                            </label>
                        @else
                            <span></span>
                        @endif

                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs font-bold text-slate-500">
                            <span>
                                <i class="fa-solid fa-circle-check mr-1 text-emerald-600"></i>
                                Verified cars
                            </span>
                            <span>
                                <i class="fa-solid fa-location-crosshairs mr-1 text-sky-500"></i>
                                Up to {{ $serviceRadius }} km
                            </span>
                            <span>
                                <i class="fa-solid fa-headset mr-1 text-amber-500"></i>
                                24×7 support
                            </span>
                        </div>
                    </div>
                </form>
            </div>
        @endif
    </section>

    <main class="relative z-0 mx-auto w-full max-w-7xl space-y-12 px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
        @if ($showCategories)
            <section aria-labelledby="popular-categories-heading">
                <div class="text-center">
                    <p class="text-xs font-black uppercase tracking-widest text-emerald-600">
                        Explore by preference
                    </p>

                    <h2 id="popular-categories-heading" class="mt-2 text-3xl font-black tracking-tight text-slate-900">
                        Find Popular Cars{{ $cityName ? " in {$cityName}" : '' }}
                    </h2>
                </div>

                <div class="mt-6 flex flex-wrap justify-center gap-3">
                    @foreach ([
                        ['fa-star', 'Popular'],
                        ['fa-truck-pickup', 'SUV'],
                        ['fa-car-side', 'Hatchback'],
                        ['fa-car', 'Sedan'],
                        ['fa-van-shuttle', 'MUV / MPV'],
                        ['fa-bolt', 'Electric'],
                    ] as [$icon, $label])
                        <button
                            type="button"
                            class="inline-flex h-11 items-center gap-2 rounded-full border border-slate-200 bg-white px-5 text-xs font-black text-slate-700 shadow-sm transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700"
                        >
                            <i class="fa-solid {{ $icon }} text-emerald-600"></i>
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($showOffers && (!empty($smartHeroBanners) || !empty($carousel)))
            <section aria-labelledby="self-drive-offers-heading">
                <div class="mb-6">
                    <p class="text-xs font-black uppercase tracking-widest text-emerald-600">
                        Featured deals
                    </p>

                    <h2 id="self-drive-offers-heading" class="mt-2 text-3xl font-black tracking-tight text-slate-900">
                        Popular Self Drive Offers
                    </h2>
                </div>

                <x-home.premium-banner-only
                    :smart-hero-banners="$smartHeroBanners ?? []"
                    :carousel="$carousel ?? []"
                    banner-tab="self_drive"
                />
            </section>
        @endif

        <section aria-labelledby="why-self-drive-heading">
            <div class="mx-auto mb-8 max-w-3xl text-center">
                <p class="text-xs font-black uppercase tracking-widest text-emerald-600">
                    Why choose Dura Cabs
                </p>

                <h2 id="why-self-drive-heading" class="mt-2 text-3xl font-black tracking-tight text-slate-900">
                    Self Drive, Your Way
                </h2>

                <p class="mt-3 text-sm leading-6 text-slate-600">
                    Flexible plans, transparent pricing and verified cars for city drives, business travel and long journeys.
                </p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['fa-clock', 'Flexible Rental Plans', 'Choose hourly, daily, weekly or monthly rental according to your travel plan.'],
                    ['fa-shield-halved', 'Verified Vehicles', 'Browse maintained cars supplied by verified self-drive partners.'],
                    ['fa-tags', 'Transparent Pricing', 'See applicable rental pricing and discounts before completing your booking.'],
                    ['fa-headset', '24×7 Assistance', 'Get support for booking, pickup, trip and return-related help.'],
                ] as [$icon, $title, $text])
                    <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:border-emerald-200 hover:shadow-lg">
                        <div class="grid h-12 w-12 place-items-center rounded-2xl bg-emerald-50 text-xl text-emerald-600">
                            <i class="fa-solid {{ $icon }}"></i>
                        </div>

                        <h3 class="mt-5 text-lg font-black text-slate-900">
                            {{ $title }}
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            {{ $text }}
                        </p>
                    </article>
                @endforeach
            </div>
        </section>

        @if (filled($page->description))
            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-gradient-to-r from-emerald-50 via-white to-sky-50 px-5 py-6 sm:px-8">
                    <p class="text-xs font-black uppercase tracking-widest text-emerald-600">
                        Self Drive Guide
                    </p>

                    <h2 class="mt-2 text-3xl font-black tracking-tight text-slate-900">
                        {{ $pageName }}
                    </h2>
                </div>

                <div class="description prose prose-slate max-w-none px-5 py-6 sm:px-8 sm:py-8">
                    {!! str($page->description)->sanitizeHtml() !!}
                </div>
            </section>
        @endif

        @if ($showFaqs && !empty($faqSchema))
            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
                <p class="text-xs font-black uppercase tracking-widest text-emerald-600">
                    Need help?
                </p>

                <h2 class="mt-2 text-3xl font-black tracking-tight text-slate-900">
                    Frequently Asked Questions
                </h2>

                <div class="mt-6 divide-y divide-slate-200">
                    @foreach ($faqSchema as $index => $faq)
                        @php
                            $question = data_get($faq, 'question', data_get($faq, 'name'));
                            $answer = data_get($faq, 'answer', data_get($faq, 'acceptedAnswer.text'));
                        @endphp

                        @if (filled($question) && filled($answer))
                            <details class="group py-4">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-bold text-slate-900">
                                    <span>{{ $index + 1 }}. {{ $question }}</span>

                                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-emerald-50 text-emerald-600 transition group-open:rotate-180">
                                        <i class="fa-solid fa-chevron-down text-xs"></i>
                                    </span>
                                </summary>

                                <div class="pr-10 pt-3 text-sm leading-6 text-slate-600">
                                    {!! str((string) $answer)->sanitizeHtml() !!}
                                </div>
                            </details>
                        @endif
                    @endforeach
                </div>
            </section>
        @endif
    </main>

    @teleport('body')
        <div
            class="fixed inset-0 z-[999999] {{ $sendOtp ? 'flex' : 'hidden' }} items-center justify-center overflow-y-auto bg-slate-950/75 px-4 py-6 backdrop-blur-sm"
            role="dialog"
            aria-modal="true"
            aria-labelledby="self-drive-mobile-title"
        >
            <div class="relative w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl sm:p-8">
                <button
                    type="button"
                    wire:click="closeModal"
                    class="absolute right-4 top-4 grid h-10 w-10 place-items-center rounded-full border border-slate-200 text-xl text-slate-500 transition hover:bg-slate-50 hover:text-slate-900"
                    aria-label="Close"
                >
                    ×
                </button>

                @if (!$sendOtpVerify)
                    <div class="text-center">
                        <div class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-emerald-50 text-2xl text-emerald-600">
                            <i class="fa-solid fa-mobile-screen-button"></i>
                        </div>

                        <h2 id="self-drive-mobile-title" class="mt-5 text-2xl font-black text-slate-900">
                            Continue with mobile
                        </h2>

                        <p class="mt-2 text-sm leading-6 text-slate-500">
                            Enter your 10-digit mobile number to continue your self-drive search.
                        </p>
                    </div>

                    <form wire:submit.prevent="sendOtpToBack" class="mt-6">
                        <label for="self-drive-mobile" class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-600">
                            Mobile Number
                        </label>

                        <div class="flex overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 focus-within:border-emerald-500 focus-within:ring-4 focus-within:ring-emerald-100">
                            <span class="flex items-center border-r border-slate-200 bg-white px-4 text-sm font-black text-slate-700">
                                +91
                            </span>

                            <input
                                id="self-drive-mobile"
                                type="tel"
                                wire:model="mobileNumber"
                                inputmode="numeric"
                                maxlength="10"
                                required
                                placeholder="Enter mobile number"
                                class="h-14 min-w-0 flex-1 border-0 bg-transparent px-4 text-base font-bold text-slate-900 outline-none ring-0 placeholder:text-slate-400 focus:ring-0"
                            >
                        </div>

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="sendOtpToBack"
                            class="mt-5 flex h-14 w-full items-center justify-center rounded-2xl bg-emerald-600 px-5 text-sm font-black uppercase tracking-wide text-white shadow-lg shadow-emerald-600/25 transition hover:bg-emerald-700 disabled:opacity-60"
                        >
                            <span wire:loading.remove wire:target="sendOtpToBack">Send OTP</span>
                            <span wire:loading wire:target="sendOtpToBack">Sending OTP...</span>
                        </button>
                    </form>
                @else
                    <div class="text-center">
                        <div class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-emerald-50 text-2xl text-emerald-600">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>

                        <h2 class="mt-5 text-2xl font-black text-slate-900">
                            Verify OTP
                        </h2>

                        <p class="mt-2 text-sm leading-6 text-slate-500">
                            Enter the 4-digit OTP sent to +91 {{ $mobileNumber }}.
                        </p>
                    </div>

                    <form wire:submit.prevent="verifySubmitOtpSelfDrive" class="mt-6">
                        <input
                            type="text"
                            wire:model="verifyOtp"
                            inputmode="numeric"
                            maxlength="4"
                            required
                            placeholder="Enter 4-digit OTP"
                            class="h-14 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-center text-xl font-black tracking-[0.45em] text-slate-900 outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-100"
                        >

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="verifySubmitOtpSelfDrive"
                            class="mt-5 flex h-14 w-full items-center justify-center rounded-2xl bg-emerald-600 px-5 text-sm font-black uppercase tracking-wide text-white shadow-lg shadow-emerald-600/25 transition hover:bg-emerald-700 disabled:opacity-60"
                        >
                            <span wire:loading.remove wire:target="verifySubmitOtpSelfDrive">Verify & Continue</span>
                            <span wire:loading wire:target="verifySubmitOtpSelfDrive">Verifying...</span>
                        </button>

                        <button
                            type="button"
                            wire:click="resendOtp"
                            class="mt-3 w-full text-sm font-bold text-emerald-700 hover:text-emerald-800"
                        >
                            Resend OTP
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @endteleport

</div>