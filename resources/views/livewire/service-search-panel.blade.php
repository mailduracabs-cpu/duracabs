@php
    $query_search = $query_search ?? '';
    $query2_search = $query2_search ?? '';
    $cities_from = $cities_from ?? [];
    $cities_to = $cities_to ?? [];

    $queryFrom_search = $queryFrom_search ?? '';
    $queryTo_search = $queryTo_search ?? '';
    $dataFrom = $dataFrom ?? [];
    $dataTo = $dataTo ?? [];
@endphp

<div class="service-search-panel relative" style="z-index: 999999;" x-data="{ activeTab: @js($selected_tab), submitting: false }">
    <div
        class="mx-auto mt-6 w-full max-w-6xl overflow-visible rounded-3xl bg-white shadow-2xl shadow-slate-900/15">

        {{-- SERVICE TABS --}}
        <div
            class="grid grid-cols-2 overflow-hidden rounded-t-3xl border-b border-slate-200 bg-slate-50 md:grid-cols-4">

            {{-- ONE WAY --}}
            <button
                type="button"
                x-on:click="activeTab = 'one_way'; $wire.set('selected_tab', 'one_way', false)"
                class="group relative flex min-h-[72px] items-center justify-center gap-2 border-b border-r border-slate-200 px-3 py-3 transition duration-200 md:border-b-0
                bg-white text-slate-700"
                x-bind:style="activeTab === 'one_way' ? 'background-color:#0ea5e9;color:#ffffff;box-shadow:inset 0 2px 4px rgba(0,0,0,.06)' : 'background-color:#ffffff;color:#334155'"
            >
                <span
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl transition"
                    x-bind:style="activeTab === 'one_way' ? 'background-color:rgba(255,255,255,.20)' : 'background-color:#f0f9ff'"
                >
                    <img
                        src="/cab_images/one_way.webp"
                        alt="One Way Cab"
                        class="h-8 w-8 object-contain transition"
                        x-bind:style="activeTab === 'one_way' ? 'filter:none' : 'filter:grayscale(1)'"
                    >
                </span>

                <span class="text-left">
                    <span
                        class="block text-[10px] font-semibold uppercase tracking-wider opacity-70">
                        Outstation
                    </span>

                    <span class="block text-xs font-extrabold uppercase sm:text-sm">
                        One Way
                    </span>
                </span>

                <span
                    x-show="activeTab === 'one_way'"
                    class="absolute inset-x-5 bottom-0 h-1 rounded-t-full bg-white">
                </span>
            </button>

            {{-- MULTI TRIP --}}
            <button
                type="button"
                x-on:click="activeTab = 'return'; $wire.set('selected_tab', 'return', false)"
                class="group relative flex min-h-[72px] items-center justify-center gap-2 border-b border-slate-200 px-3 py-3 transition duration-200 md:border-b-0 md:border-r
                bg-white text-slate-700"
                x-bind:style="activeTab === 'return' ? 'background-color:#0ea5e9;color:#ffffff;box-shadow:inset 0 2px 4px rgba(0,0,0,.06)' : 'background-color:#ffffff;color:#334155'"
            >
                <span
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl transition"
                    x-bind:style="activeTab === 'return' ? 'background-color:rgba(255,255,255,.20)' : 'background-color:#f0f9ff'"
                >
                    <img
                        src="/cab_images/return.webp"
                        alt="Multi Trip"
                        class="h-8 w-8 object-contain transition"
                        x-bind:style="activeTab === 'return' ? 'filter:none' : 'filter:grayscale(1)'"
                    >
                </span>

                <span class="text-left">
                    <span
                        class="block text-[10px] font-semibold uppercase tracking-wider opacity-70">
                        Outstation
                    </span>

                    <span class="block text-xs font-extrabold uppercase sm:text-sm">
                        Multi Trip
                    </span>
                </span>

                <span
                    x-show="activeTab === 'return'"
                    class="absolute inset-x-5 bottom-0 h-1 rounded-t-full bg-white">
                </span>
            </button>

            {{-- LOCAL TAXI --}}
            <button
                type="button"
                x-on:click="activeTab = 'local'; $wire.set('selected_tab', 'local', false)"
                class="group relative flex min-h-[72px] items-center justify-center gap-2 border-r border-slate-200 px-3 py-3 transition duration-200
                bg-white text-slate-700"
                x-bind:style="activeTab === 'local' ? 'background-color:#0ea5e9;color:#ffffff;box-shadow:inset 0 2px 4px rgba(0,0,0,.06)' : 'background-color:#ffffff;color:#334155'"
            >
                <span
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl transition"
                    x-bind:style="activeTab === 'local' ? 'background-color:rgba(255,255,255,.20)' : 'background-color:#f0f9ff'"
                >
                    <img
                        src="/cab_images/local.webp"
                        alt="Local Taxi"
                        class="h-8 w-8 object-contain transition"
                        x-bind:style="activeTab === 'local' ? 'filter:none' : 'filter:grayscale(1)'"
                    >
                </span>

                <span class="text-left">
                    <span
                        class="block text-[10px] font-semibold uppercase tracking-wider opacity-70">
                        City Ride
                    </span>

                    <span class="block text-xs font-extrabold uppercase sm:text-sm">
                        Local Taxi
                    </span>
                </span>

                <span
                    x-show="activeTab === 'local'"
                    class="absolute inset-x-5 bottom-0 h-1 rounded-t-full bg-white">
                </span>
            </button>

            {{-- SELF DRIVE --}}
            <button
                type="button"
                x-on:click="activeTab = 'self_drive'; $wire.set('selected_tab', 'self_drive', false)"
                class="group relative flex min-h-[72px] items-center justify-center gap-2 px-3 py-3 transition duration-200
                bg-white text-slate-700"
                x-bind:style="activeTab === 'self_drive' ? 'background-color:#0ea5e9;color:#ffffff;box-shadow:inset 0 2px 4px rgba(0,0,0,.06)' : 'background-color:#ffffff;color:#334155'"
            >
                <span
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl transition"
                    x-bind:style="activeTab === 'self_drive' ? 'background-color:rgba(255,255,255,.20)' : 'background-color:#f0f9ff'"
                >
                    <img
                        src="/cab_images/self_drive.webp"
                        alt="Self Drive Car"
                        class="h-8 w-8 object-contain transition"
                        x-bind:style="activeTab === 'self_drive' ? 'filter:none' : 'filter:grayscale(1)'"
                    >
                </span>

                <span class="text-left">
                    <span
                        class="block text-[10px] font-semibold uppercase tracking-wider opacity-70">
                        Drive Yourself
                    </span>

                    <span class="block text-xs font-extrabold uppercase sm:text-sm">
                        Self Drive
                    </span>
                </span>

                <span
                    x-show="activeTab === 'self_drive'"
                    class="absolute inset-x-5 bottom-0 h-1 rounded-t-full bg-white">
                </span>
            </button>
        </div>

        {{-- =====================================================
            ONE WAY FORM
        ====================================================== --}}
        <form
            x-on:submit.prevent="if (!submitting) { submitting = true; $wire.searchPackage().finally(() => submitting = false) }"
            autocomplete="off"
            x-show="activeTab === 'one_way'"

            class="p-4 sm:p-5"
        >
            <div
                class="grid grid-cols-1 items-start gap-3 lg:grid-cols-[1.35fr_42px_1.35fr_1fr_1fr_150px]">

                {{-- FROM CITY --}}
                <div class="relative">
                    <label
                        for="one-way-from"
                        class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600"
                    >
                        From
                    </label>

                    <div class="relative">
                        <span
                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sky-500"
                        >
                            <svg
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 21s7-5.2 7-12a7 7 0 10-14 0c0 6.8 7 12 7 12z"
                                />
                                <circle cx="12" cy="9" r="2.5" />
                            </svg>
                        </span>

                        <input
                            type="text"
                            id="one-way-from"
                            wire:model.live.debounce.350ms="query_search"
                            placeholder="Enter pickup city"
                            class="h-14 w-full rounded-xl border bg-slate-50 pl-11 pr-4 text-sm font-bold text-slate-900 outline-none transition placeholder:font-medium placeholder:text-slate-400 focus:bg-white focus:ring-4
                            {{ $this->hasError('query')
                                ? 'border-red-400 focus:border-red-500 focus:ring-red-100'
                                : 'border-slate-200 focus:border-sky-500 focus:ring-sky-100' }}"
                        >
                    </div>

                    @if ($this->hasError('query'))
                        <p class="mt-1 text-xs font-medium text-red-600">
                            {{ $this->getError('query') }}
                        </p>
                    @endif

                    @if (mb_strlen(trim((string) $query_search)) >= 3)
                        <div
                            data-suggestions class="absolute left-0 top-full z-[1000000] mt-2 max-h-56 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1.5 shadow-2xl"
                        >
                            @if (!empty($cities_from))
                                @foreach ($cities_from as $city)
                                    <button
                                        type="button"
                                        wire:click="selectGooglePlace('one_way_from', '{{ $city['place_id'] }}')"
                                        class="flex w-full items-start gap-3 rounded-lg px-3 py-2.5 text-left text-sm text-slate-700 transition hover:bg-sky-50 hover:text-sky-700"
                                    >
                                        <span
                                            class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-sky-50 text-sky-500"
                                        >
                                            <svg
                                                class="h-4 w-4"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                aria-hidden="true"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M12 21s7-5.2 7-12a7 7 0 10-14 0c0 6.8 7 12 7 12z"
                                                />
                                                <circle cx="12" cy="9" r="2.5" />
                                            </svg>
                                        </span>

                                        <span class="leading-5">
                                            {{ $city['description'] }}
                                        </span>
                                    </button>
                                @endforeach
                            @endif
                        </div>
                    @endif
                </div>

                {{-- WORKING SWAP BUTTON --}}
                <div class="hidden pt-[26px] lg:flex lg:justify-center">
                    <button
                        type="button"
                        title="Swap pickup and destination"
                        x-on:click="
                            const fromSearch = $wire.query_search;
                            const toSearch = $wire.query2_search;

                            const fromName = $wire.query;
                            const toName = $wire.query2;

                            const fromId = $wire.query_id;
                            const toId = $wire.query2_id;

                            $wire.set('query_search', toSearch);
                            $wire.set('query2_search', fromSearch);

                            $wire.set('query', toName);
                            $wire.set('query2', fromName);

                            $wire.set('query_id', toId);
                            $wire.set('query2_id', fromId);

                            $wire.set('cities_from', null);
                            $wire.set('cities_to', null);
                        "
                        class="flex h-11 w-11 items-center justify-center rounded-full border border-sky-200 bg-sky-50 text-sky-600 shadow-sm transition duration-300 hover:rotate-180 hover:border-sky-400 hover:bg-sky-100 focus:outline-none focus:ring-4 focus:ring-sky-100"
                    >
                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.3"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M7 7h11m0 0-3-3m3 3-3 3M17 17H6m0 0 3 3m-3-3 3-3"
                            />
                        </svg>
                    </button>
                </div>

                {{-- TO CITY --}}
                <div class="relative">
                    <label
                        for="one-way-to"
                        class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600"
                    >
                        To
                    </label>

                    <div class="relative">
                        <span
                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-rose-500"
                        >
                            <svg
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 21s7-5.2 7-12a7 7 0 10-14 0c0 6.8 7 12 7 12z"
                                />
                                <circle cx="12" cy="9" r="2.5" />
                            </svg>
                        </span>

                        <input
                            type="text"
                            id="one-way-to"
                            wire:model.live.debounce.350ms="query2_search"
                            placeholder="Enter destination city"
                            class="h-14 w-full rounded-xl border bg-slate-50 pl-11 pr-4 text-sm font-bold text-slate-900 outline-none transition placeholder:font-medium placeholder:text-slate-400 focus:bg-white focus:ring-4
                            {{ $this->hasError('query2')
                                ? 'border-red-400 focus:border-red-500 focus:ring-red-100'
                                : 'border-slate-200 focus:border-sky-500 focus:ring-sky-100' }}"
                        >
                    </div>

                    @if ($this->hasError('query2'))
                        <p class="mt-1 text-xs font-medium text-red-600">
                            {{ $this->getError('query2') }}
                        </p>
                    @endif

                    @if (mb_strlen(trim((string) $query2_search)) >= 3)
                        <div
                            data-suggestions class="absolute left-0 top-full z-[1000000] mt-2 max-h-56 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1.5 shadow-2xl"
                        >
                            @if (!empty($cities_to))
                                @foreach ($cities_to as $city)
                                    <button
                                        type="button"
                                        wire:click="selectGooglePlace('one_way_to', '{{ $city['place_id'] }}')"
                                        class="flex w-full items-start gap-3 rounded-lg px-3 py-2.5 text-left text-sm text-slate-700 transition hover:bg-rose-50 hover:text-rose-700"
                                    >
                                        <span
                                            class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-500"
                                        >
                                            <svg
                                                class="h-4 w-4"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                aria-hidden="true"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M12 21s7-5.2 7-12a7 7 0 10-14 0c0 6.8 7 12 7 12z"
                                                />
                                                <circle cx="12" cy="9" r="2.5" />
                                            </svg>
                                        </span>

                                        <span class="leading-5">
                                            {{ $city['description'] }}
                                        </span>
                                    </button>
                                @endforeach
                            @endif
                        </div>
                    @endif
                </div>

                {{-- PICKUP DATE --}}
                <div>
                    <label
                        for="one-way-date"
                        class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600"
                    >
                        Pickup Date
                    </label>

                    <input
                        type="date"
                        id="one-way-date"
                        wire:model="date"
                        min="{{ \Carbon\Carbon::now()->format('Y-m-d') }}"
                        required
                        class="h-14 w-full rounded-xl border bg-slate-50 px-3 text-sm font-bold text-slate-900 outline-none transition focus:bg-white focus:ring-4
                        {{ $this->hasError('date')
                            ? 'border-red-400 focus:border-red-500 focus:ring-red-100'
                            : 'border-slate-200 focus:border-sky-500 focus:ring-sky-100' }}"
                    >

                    @if ($this->hasError('date'))
                        <p class="mt-1 text-xs font-medium text-red-600">
                            {{ $this->getError('date') }}
                        </p>
                    @endif
                </div>

                {{-- PICKUP TIME --}}
                <div>
                    <label
                        for="one-way-time"
                        class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600"
                    >
                        Pickup Time
                    </label>

                    <input
                        type="time"
                        id="one-way-time"
                        wire:model="time"
                        required
                        class="h-14 w-full rounded-xl border bg-slate-50 px-3 text-sm font-bold text-slate-900 outline-none transition focus:bg-white focus:ring-4
                        {{ $this->hasError('time')
                            ? 'border-red-400 focus:border-red-500 focus:ring-red-100'
                            : 'border-slate-200 focus:border-sky-500 focus:ring-sky-100' }}"
                    >

                    @if ($this->hasError('time'))
                        <p class="mt-1 text-xs font-medium text-red-600">
                            {{ $this->getError('time') }}
                        </p>
                    @endif
                </div>

                {{-- SEARCH BUTTON --}}
                <div class="pt-0 lg:pt-[26px]">
                    <button
                        type="submit"
                        :disabled="submitting"
                        class="flex h-14 w-full items-center justify-center rounded-xl bg-sky-500 px-5 text-sm font-extrabold uppercase tracking-wide text-white shadow-lg shadow-sky-500/25 transition hover:bg-sky-600 focus:outline-none focus:ring-4 focus:ring-sky-200 disabled:cursor-not-allowed disabled:opacity-70"
                    >
                        <span x-show="!submitting">Search</span>
                        <span x-show="submitting" x-cloak>Searching...</span>
                    </button>
                </div>
            </div>

            @if (!empty($oneWayMsg))
                <div
                    class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700"
                >
                    {{ $oneWayMsg }}
                </div>
            @endif
        </form>

        {{-- =====================================================
            MULTI TRIP FORM
        ====================================================== --}}
        <form
            x-on:submit.prevent="if (!submitting) { submitting = true; $wire.searchPackage().finally(() => submitting = false) }"
            autocomplete="off"
            x-show="activeTab === 'return'"
            x-cloak
            class="p-4 sm:p-5"
        >
            <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-sky-100 bg-sky-50/70 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Build Your Multi-City Route</h3>
                    <p class="mt-1 text-xs font-medium leading-5 text-slate-600 sm:text-sm">
                        Add up to 20 destinations. Your cab will return to the pickup city at the end.
                    </p>
                </div>

                <span class="inline-flex w-fit items-center gap-2 rounded-full bg-white px-3 py-2 text-xs font-bold text-sky-700 shadow-sm">
                    <i class="fa-solid fa-route" aria-hidden="true"></i>
                    {{ 1 + count($tripCities ?? []) }} Destination{{ (1 + count($tripCities ?? [])) === 1 ? '' : 's' }}
                </span>
            </div>

            <div class="grid grid-cols-1 items-start gap-3 lg:grid-cols-[1.25fr_1.25fr_1fr_1fr_1fr_150px]">
                {{-- PICKUP CITY --}}
                <div class="relative">
                    <label for="round-from" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">
                        Pickup City
                    </label>

                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sky-500">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-5.2 7-12a7 7 0 10-14 0c0 6.8 7 12 7 12z" />
                                <circle cx="12" cy="9" r="2.5" />
                            </svg>
                        </span>

                        <input
                            type="text"
                            id="round-from"
                            wire:model.live.debounce.350ms="queryFrom_search"
                            placeholder="Enter pickup city"
                            class="h-14 w-full rounded-xl border bg-slate-50 pl-11 pr-4 text-sm font-bold text-slate-900 outline-none transition placeholder:font-medium placeholder:text-slate-400 focus:bg-white focus:ring-4
                            {{ $this->hasError('queryFrom')
                                ? 'border-red-400 focus:border-red-500 focus:ring-red-100'
                                : 'border-slate-200 focus:border-sky-500 focus:ring-sky-100' }}"
                        >
                    </div>

                    @if ($this->hasError('queryFrom'))
                        <p class="mt-1 text-xs font-medium text-red-600">{{ $this->getError('queryFrom') }}</p>
                    @endif

                    @if (mb_strlen(trim((string) $queryFrom_search)) >= 3)
                        <div data-suggestions class="absolute left-0 top-full z-[1000000] mt-2 max-h-56 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1.5 shadow-2xl">
                            @foreach (($dataFrom ?? []) as $city)
                                <button
                                    type="button"
                                    wire:click="selectGooglePlace('round_from', '{{ $city['place_id'] }}')"
                                    class="flex w-full items-start gap-3 rounded-lg px-3 py-2.5 text-left text-sm text-slate-700 transition hover:bg-sky-50 hover:text-sky-700"
                                >
                                    <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-sky-50 text-sky-500">
                                        <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                                    </span>
                                    <span class="leading-5">{{ $city['description'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- FIRST DESTINATION --}}
                <div class="relative">
                    <label for="round-to" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">
                        Destination 1
                    </label>

                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-rose-500">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-5.2 7-12a7 7 0 10-14 0c0 6.8 7 12 7 12z" />
                                <circle cx="12" cy="9" r="2.5" />
                            </svg>
                        </span>

                        <input
                            type="text"
                            id="round-to"
                            wire:model.live.debounce.350ms="queryTo_search"
                            placeholder="Enter first destination"
                            class="h-14 w-full rounded-xl border bg-slate-50 pl-11 pr-4 text-sm font-bold text-slate-900 outline-none transition placeholder:font-medium placeholder:text-slate-400 focus:bg-white focus:ring-4
                            {{ $this->hasError('queryTo')
                                ? 'border-red-400 focus:border-red-500 focus:ring-red-100'
                                : 'border-slate-200 focus:border-sky-500 focus:ring-sky-100' }}"
                        >
                    </div>

                    @if ($this->hasError('queryTo'))
                        <p class="mt-1 text-xs font-medium text-red-600">{{ $this->getError('queryTo') }}</p>
                    @endif

                    @if (mb_strlen(trim((string) $queryTo_search)) >= 3)
                        <div data-suggestions class="absolute left-0 top-full z-[1000000] mt-2 max-h-56 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1.5 shadow-2xl">
                            @foreach (($dataTo ?? []) as $city)
                                <button
                                    type="button"
                                    wire:click="selectGooglePlace('round_to', '{{ $city['place_id'] }}')"
                                    class="flex w-full items-start gap-3 rounded-lg px-3 py-2.5 text-left text-sm text-slate-700 transition hover:bg-rose-50 hover:text-rose-700"
                                >
                                    <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-500">
                                        <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                                    </span>
                                    <span class="leading-5">{{ $city['description'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- START DATE --}}
                <div>
                    <label for="round-start-date" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">Start Date</label>
                    <input
                        type="date"
                        id="round-start-date"
                        wire:model="date"
                        min="{{ date('Y-m-d') }}"
                        required
                        class="h-14 w-full rounded-xl border bg-slate-50 px-3 text-sm font-bold text-slate-900 outline-none transition focus:bg-white focus:ring-4
                        {{ $this->hasError('date') ? 'border-red-400 focus:border-red-500 focus:ring-red-100' : 'border-slate-200 focus:border-sky-500 focus:ring-sky-100' }}"
                    >
                    @if ($this->hasError('date'))
                        <p class="mt-1 text-xs font-medium text-red-600">{{ $this->getError('date') }}</p>
                    @endif
                </div>

                {{-- END DATE --}}
                <div>
                    <label for="round-end-date" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">End Date</label>
                    <input
                        type="date"
                        id="round-end-date"
                        wire:model="dateto"
                        min="{{ $date ?: date('Y-m-d') }}"
                        required
                        class="h-14 w-full rounded-xl border bg-slate-50 px-3 text-sm font-bold text-slate-900 outline-none transition focus:bg-white focus:ring-4
                        {{ $this->hasError('dateto') ? 'border-red-400 focus:border-red-500 focus:ring-red-100' : 'border-slate-200 focus:border-sky-500 focus:ring-sky-100' }}"
                    >
                    @if ($this->hasError('dateto'))
                        <p class="mt-1 text-xs font-medium text-red-600">{{ $this->getError('dateto') }}</p>
                    @endif
                </div>

                {{-- START TIME --}}
                <div>
                    <label for="round-time" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">Start Time</label>
                    <input
                        type="time"
                        id="round-time"
                        wire:model="time"
                        required
                        class="h-14 w-full rounded-xl border bg-slate-50 px-3 text-sm font-bold text-slate-900 outline-none transition focus:bg-white focus:ring-4
                        {{ $this->hasError('time') ? 'border-red-400 focus:border-red-500 focus:ring-red-100' : 'border-slate-200 focus:border-sky-500 focus:ring-sky-100' }}"
                    >
                    @if ($this->hasError('time'))
                        <p class="mt-1 text-xs font-medium text-red-600">{{ $this->getError('time') }}</p>
                    @endif
                </div>

                {{-- SEARCH --}}
                <div class="pt-0 lg:pt-[26px]">
                    <button
                        type="submit"
                        :disabled="submitting"
                        class="flex h-14 w-full items-center justify-center rounded-xl bg-sky-500 px-5 text-sm font-extrabold uppercase text-white shadow-lg shadow-sky-500/25 transition hover:bg-sky-600 focus:outline-none focus:ring-4 focus:ring-sky-200 disabled:cursor-not-allowed disabled:opacity-70"
                    >
                        <span x-show="!submitting">Search</span>
                        <span x-show="submitting" x-cloak>Searching...</span>
                    </button>
                </div>
            </div>

            {{-- ADDITIONAL DESTINATIONS --}}
            <div class="mt-4 space-y-3">
                @foreach (($tripCities ?? []) as $index => $tripCity)
                    <div wire:key="multi-trip-city-{{ $index }}" class="rounded-2xl border border-slate-200 bg-slate-50/70 p-3 sm:p-4">
                        <div class="flex items-start gap-3">
                            <div class="relative min-w-0 flex-1">
                                <label for="trip-city-{{ $index }}" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">
                                    Destination {{ $index + 2 }}
                                </label>

                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-violet-500">
                                        <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                                    </span>
                                    <input
                                        type="text"
                                        id="trip-city-{{ $index }}"
                                        wire:model.live.debounce.350ms="tripCities.{{ $index }}.search"
                                        placeholder="Enter next destination"
                                        class="h-14 w-full rounded-xl border bg-white pl-11 pr-4 text-sm font-bold text-slate-900 outline-none transition placeholder:font-medium placeholder:text-slate-400 focus:ring-4
                                        {{ $this->hasError('tripCities.' . $index)
                                            ? 'border-red-400 focus:border-red-500 focus:ring-red-100'
                                            : 'border-slate-200 focus:border-violet-500 focus:ring-violet-100' }}"
                                    >
                                </div>

                                @if ($this->hasError('tripCities.' . $index))
                                    <p class="mt-1 text-xs font-medium text-red-600">{{ $this->getError('tripCities.' . $index) }}</p>
                                @endif

                                @if (!empty($tripCity['suggestions']))
                                    <div data-suggestions class="absolute left-0 top-full z-[1000000] mt-2 max-h-56 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1.5 shadow-2xl">
                                        @foreach ($tripCity['suggestions'] as $city)
                                            <button
                                                type="button"
                                                wire:click="selectTripCity({{ $index }}, '{{ $city['place_id'] }}')"
                                                class="flex w-full items-start gap-3 rounded-lg px-3 py-2.5 text-left text-sm text-slate-700 transition hover:bg-violet-50 hover:text-violet-700"
                                            >
                                                <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-violet-50 text-violet-500">
                                                    <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                                                </span>
                                                <span class="leading-5">{{ $city['description'] }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <button
                                type="button"
                                wire:click="removeTripCity({{ $index }})"
                                class="mt-[26px] flex h-14 w-14 shrink-0 items-center justify-center rounded-xl border border-red-200 bg-white text-red-500 transition hover:bg-red-50 hover:text-red-600 focus:outline-none focus:ring-4 focus:ring-red-100"
                                aria-label="Remove destination {{ $index + 2 }}"
                                title="Remove destination"
                            >
                                <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <button
                    type="button"
                    wire:click="addTripCity"
                    @disabled(count($tripCities ?? []) >= ($maxTripCities ?? 19))
                    class="inline-flex h-12 items-center justify-center gap-2 rounded-xl border border-sky-200 bg-sky-50 px-5 text-sm font-extrabold text-sky-700 transition hover:border-sky-300 hover:bg-sky-100 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    Add Another City
                </button>

                <div class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 sm:text-sm">
                    <i class="fa-solid fa-rotate-left text-sky-500" aria-hidden="true"></i>
                    Final route automatically returns to {{ $queryFrom ?: 'pickup city' }}.
                </div>
            </div>

            @if ($this->hasError('tripCities'))
                <p class="mt-3 text-xs font-medium text-red-600">{{ $this->getError('tripCities') }}</p>
            @endif

            @if (!empty($oneWayMsg))
                <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                    {{ $oneWayMsg }}
                </div>
            @endif
        </form>

        {{-- =====================================================
            LOCAL TAXI FORM
        ====================================================== --}}
        <form
            x-on:submit.prevent="if (!submitting) { submitting = true; $wire.searchPackage().finally(() => submitting = false) }"
            autocomplete="off"
            x-show="activeTab === 'local'"
            x-cloak
            class="p-4 sm:p-5"
        >
            <div
                class="grid grid-cols-1 items-start gap-3 lg:grid-cols-[1.3fr_1.15fr_1fr_1fr_.8fr_150px]">

                {{-- LOCAL CITY --}}
                <div class="relative">
                    <label
                        for="local-city"
                        class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600"
                    >
                        Pickup City
                    </label>

                    <div class="relative">
                        <span
                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sky-500"
                        >
                            <svg
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 21s7-5.2 7-12a7 7 0 10-14 0c0 6.8 7 12 7 12z"
                                />
                                <circle cx="12" cy="9" r="2.5" />
                            </svg>
                        </span>

                        <input
                            type="text"
                            id="local-city"
                            wire:model.live.debounce.350ms="queryLocal"
                            placeholder="Enter city"
                            class="h-14 w-full rounded-xl border bg-slate-50 pl-11 pr-4 text-sm font-bold text-slate-900 outline-none transition placeholder:font-medium placeholder:text-slate-400 focus:bg-white focus:ring-4
                            {{ $this->hasError('query')
                                ? 'border-red-400 focus:border-red-500 focus:ring-red-100'
                                : 'border-slate-200 focus:border-sky-500 focus:ring-sky-100' }}"
                        >
                    </div>

                    @if ($this->hasError('query'))
                        <p class="mt-1 text-xs font-medium text-red-600">
                            {{ $this->getError('query') }}
                        </p>
                    @endif

                    @if (mb_strlen(trim((string) $queryLocal)) >= 3)
                        <div
                            data-suggestions class="absolute left-0 top-full z-[1000000] mt-2 max-h-56 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1.5 shadow-2xl"
                        >
                            @if (!empty($cities_from))
                                @foreach ($cities_from as $city)
                                    <button
                                        type="button"
                                        wire:click="selectGooglePlace('local', '{{ $city['place_id'] }}')"
                                        class="flex w-full items-start gap-3 rounded-lg px-3 py-2.5 text-left text-sm text-slate-700 transition hover:bg-sky-50 hover:text-sky-700"
                                    >
                                        <span
                                            class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-sky-50 text-sky-500"
                                        >
                                            <svg
                                                class="h-4 w-4"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M12 21s7-5.2 7-12a7 7 0 10-14 0c0 6.8 7 12 7 12z"
                                                />
                                                <circle cx="12" cy="9" r="2.5" />
                                            </svg>
                                        </span>

                                        <span>
                                            {{ $city['description'] }}
                                        </span>
                                    </button>
                                @endforeach
                            @endif
                        </div>
                    @endif
                </div>

                {{-- PLAN --}}
                <div>
                    <label
                        for="local-plan"
                        class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600"
                    >
                        Rental Plan
                    </label>

                    <select
                        id="local-plan"
                        wire:model="plan"
                        class="h-14 w-full rounded-xl border bg-slate-50 px-3 text-sm font-bold text-slate-900 outline-none transition focus:bg-white focus:ring-4
                        {{ $this->hasError('plan')
                            ? 'border-red-400 focus:border-red-500 focus:ring-red-100'
                            : 'border-slate-200 focus:border-sky-500 focus:ring-sky-100' }}"
                    >
                        <option value="4 Hour / 40 Km">
                            4 Hour / 40 Km
                        </option>

                        <option value="8 Hour / 80 Km">
                            8 Hour / 80 Km
                        </option>

                        <option value="12 Hour / 120 Km">
                            12 Hour / 120 Km
                        </option>
                    </select>

                    @if ($this->hasError('plan'))
                        <p class="mt-1 text-xs font-medium text-red-600">
                            {{ $this->getError('plan') }}
                        </p>
                    @endif
                </div>

                {{-- LOCAL DATE --}}
                <div>
                    <label
                        for="local-date"
                        class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600"
                    >
                        Start Date
                    </label>

                    <input
                        type="date"
                        id="local-date"
                        wire:model="date"
                        min="{{ date('Y-m-d') }}"
                        required
                        class="h-14 w-full rounded-xl border bg-slate-50 px-3 text-sm font-bold text-slate-900 outline-none transition focus:bg-white focus:ring-4
                        {{ $this->hasError('date')
                            ? 'border-red-400 focus:border-red-500 focus:ring-red-100'
                            : 'border-slate-200 focus:border-sky-500 focus:ring-sky-100' }}"
                    >

                    @if ($this->hasError('date'))
                        <p class="mt-1 text-xs font-medium text-red-600">
                            {{ $this->getError('date') }}
                        </p>
                    @endif
                </div>

                {{-- LOCAL TIME --}}
                <div>
                    <label
                        for="local-time"
                        class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600"
                    >
                        Start Time
                    </label>

                    <input
                        type="time"
                        id="local-time"
                        wire:model="time"
                        required
                        class="h-14 w-full rounded-xl border bg-slate-50 px-3 text-sm font-bold text-slate-900 outline-none transition focus:bg-white focus:ring-4
                        {{ $this->hasError('time')
                            ? 'border-red-400 focus:border-red-500 focus:ring-red-100'
                            : 'border-slate-200 focus:border-sky-500 focus:ring-sky-100' }}"
                    >

                    @if ($this->hasError('time'))
                        <p class="mt-1 text-xs font-medium text-red-600">
                            {{ $this->getError('time') }}
                        </p>
                    @endif
                </div>

                {{-- NUMBER OF CARS --}}
                <div>
                    <label
                        for="local-cars"
                        class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600"
                    >
                        Cars
                    </label>

                    <input
                        type="number"
                        id="local-cars"
                        wire:model="car"
                        min="1"
                        placeholder="1"
                        required
                        class="h-14 w-full rounded-xl border bg-slate-50 px-3 text-sm font-bold text-slate-900 outline-none transition placeholder:text-slate-400 focus:bg-white focus:ring-4
                        {{ $this->hasError('car')
                            ? 'border-red-400 focus:border-red-500 focus:ring-red-100'
                            : 'border-slate-200 focus:border-sky-500 focus:ring-sky-100' }}"
                    >

                    @if ($this->hasError('car'))
                        <p class="mt-1 text-xs font-medium text-red-600">
                            {{ $this->getError('car') }}
                        </p>
                    @endif
                </div>

                {{-- SEARCH --}}
                <div class="pt-0 lg:pt-[26px]">
                    <button
                        type="submit"
                        :disabled="submitting"
                        class="flex h-14 w-full items-center justify-center rounded-xl bg-sky-500 px-5 text-sm font-extrabold uppercase text-white shadow-lg shadow-sky-500/25 transition hover:bg-sky-600 focus:outline-none focus:ring-4 focus:ring-sky-200 disabled:opacity-70"
                    >
                        <span x-show="!submitting">Search</span>
                        <span x-show="submitting" x-cloak>Searching...</span>
                    </button>
                </div>
            </div>
        </form>

        {{-- =====================================================
            SELF DRIVE FORM
        ====================================================== --}}
        <form
            x-on:submit.prevent="if (!submitting) { submitting = true; $wire.searchPackage().finally(() => submitting = false) }"
            autocomplete="off"
            x-show="activeTab === 'self_drive'"
            x-cloak
            class="p-4 sm:p-5"
        >
            <div
                class="grid grid-cols-1 items-start gap-3 lg:grid-cols-[1.3fr_1fr_1fr_1fr_1fr_150px]">

                {{-- SELF DRIVE CITY --}}
                <div class="relative">
                    <label
                        for="self-drive-city"
                        class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600"
                    >
                        Pickup City
                    </label>

                    <div class="relative">
                        <span
                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sky-500"
                        >
                            <svg
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 21s7-5.2 7-12a7 7 0 10-14 0c0 6.8 7 12 7 12z"
                                />
                                <circle cx="12" cy="9" r="2.5" />
                            </svg>
                        </span>

                        <input
                            type="text"
                            id="self-drive-city"
                            wire:model.live.debounce.350ms="querySelfDrive"
                            placeholder="Enter pickup city"
                            class="h-14 w-full rounded-xl border bg-slate-50 pl-11 pr-4 text-sm font-bold text-slate-900 outline-none transition placeholder:font-medium placeholder:text-slate-400 focus:bg-white focus:ring-4
                            {{ $this->hasError('query')
                                ? 'border-red-400 focus:border-red-500 focus:ring-red-100'
                                : 'border-slate-200 focus:border-sky-500 focus:ring-sky-100' }}"
                        >
                    </div>

                    @if ($this->hasError('query'))
                        <p class="mt-1 text-xs font-medium text-red-600">
                            {{ $this->getError('query') }}
                        </p>
                    @endif

                    @if (mb_strlen(trim((string) $querySelfDrive)) >= 3)
                        <div
                            data-suggestions class="absolute left-0 top-full z-[1000000] mt-2 max-h-56 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1.5 shadow-2xl"
                        >
                            @if (!empty($cities_from))
                                @foreach ($cities_from as $city)
                                    <button
                                        type="button"
                                        wire:click="selectGooglePlace('self_drive', '{{ $city['place_id'] }}')"
                                        class="flex w-full items-start gap-3 rounded-lg px-3 py-2.5 text-left text-sm text-slate-700 transition hover:bg-sky-50 hover:text-sky-700"
                                    >
                                        <span
                                            class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-sky-50 text-sky-500"
                                        >
                                            <svg
                                                class="h-4 w-4"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M12 21s7-5.2 7-12a7 7 0 10-14 0c0 6.8 7 12 7 12z"
                                                />
                                                <circle cx="12" cy="9" r="2.5" />
                                            </svg>
                                        </span>

                                        <span>
                                            {{ $city['description'] }}
                                        </span>
                                    </button>
                                @endforeach
                            @endif
                        </div>
                    @endif
                </div>

                {{-- START DATE --}}
                <div>
                    <label
                        for="self-start-date"
                        class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600"
                    >
                        Start Date
                    </label>

                    <input
                        type="date"
                        id="self-start-date"
                        name="book"
                        wire:model.live="date"
                        min="{{ date('Y-m-d') }}"
                        required
                        class="h-14 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm font-bold text-slate-900 outline-none transition focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100"
                    >
                </div>

                {{-- START TIME --}}
                <div>
                    <label
                        for="self-start-time"
                        class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600"
                    >
                        Start Time
                    </label>

                    <input
                        type="time"
                        id="self-start-time"
                        wire:model.live="time"
                        required
                        class="h-14 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm font-bold text-slate-900 outline-none transition focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100"
                    >
                </div>

                {{-- END DATE --}}
                <div>
                    <label
                        for="self-end-date"
                        class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600"
                    >
                        End Date
                    </label>

                    <input
                        type="date"
                        id="self-end-date"
                        wire:model.live="dateto"
                        min="{{ date('Y-m-d') }}"
                        required
                        class="h-14 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm font-bold text-slate-900 outline-none transition focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100"
                    >
                </div>

                {{-- END TIME --}}
                <div>
                    <label
                        for="self-end-time"
                        class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600"
                    >
                        End Time
                    </label>

                    <input
                        type="time"
                        id="self-end-time"
                        wire:model.live="endTime"
                        required
                        class="h-14 w-full rounded-xl border bg-slate-50 px-3 text-sm font-bold text-slate-900 outline-none transition focus:bg-white focus:ring-4
                        {{ $this->hasError('endTime')
                            ? 'border-red-400 focus:border-red-500 focus:ring-red-100'
                            : 'border-slate-200 focus:border-sky-500 focus:ring-sky-100' }}"
                    >

                    @if ($this->hasError('endTime'))
                        <p class="mt-1 text-xs font-medium text-red-600">
                            {{ $this->getError('endTime') }}
                        </p>
                    @endif
                </div>

                {{-- SEARCH --}}
                <div class="pt-0 lg:pt-[26px]">
                    <button
                        type="submit"
                        :disabled="submitting"
                        class="flex h-14 w-full items-center justify-center rounded-xl bg-sky-500 px-5 text-sm font-extrabold uppercase text-white shadow-lg shadow-sky-500/25 transition hover:bg-sky-600 focus:outline-none focus:ring-4 focus:ring-sky-200 disabled:opacity-70"
                    >
                        <span x-show="!submitting">Search</span>
                        <span x-show="submitting" x-cloak>Searching...</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- =========================================================
        SEND OTP MODAL
    ========================================================== --}}
	@teleport('body')
    <div
        class="fixed inset-0 z-[999999] {{ $sendOtp ? 'flex' : 'hidden' }} items-center justify-center overflow-y-auto bg-slate-950/75 px-3 py-4 backdrop-blur-sm sm:px-6 sm:py-8"
        role="dialog"
        aria-modal="true"
        aria-labelledby="mobile-login-title"
    >
        <div
            class="relative my-auto grid w-full max-w-4xl overflow-hidden rounded-[28px] border border-white/20 bg-white shadow-2xl shadow-slate-950/30 lg:grid-cols-[1.05fr_.95fr]"
        >
            {{-- DESKTOP / TABLET BRAND PANEL --}}
            <div
                class="relative hidden min-h-[560px] overflow-hidden bg-gradient-to-br from-sky-500 via-sky-600 to-indigo-700 p-10 text-white lg:flex lg:flex-col lg:justify-between"
            >
                <div
                    class="absolute -left-20 -top-20 h-64 w-64 rounded-full bg-white/10 blur-2xl"
                ></div>

                <div
                    class="absolute -bottom-24 -right-20 h-72 w-72 rounded-full bg-indigo-300/20 blur-3xl"
                ></div>

                <div class="relative z-10">
                    <div
                        class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-extrabold uppercase tracking-[0.18em] backdrop-blur"
                    >
                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 3l7 4v5c0 4.6-2.9 7.7-7 9-4.1-1.3-7-4.4-7-9V7l7-4z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9 12l2 2 4-4"
                            />
                        </svg>

                        Secure Booking
                    </div>

                    <h2 class="mt-7 max-w-md text-4xl font-black leading-tight">
                        Your ride is just one secure step away.
                    </h2>

                    <p class="mt-4 max-w-md text-base font-medium leading-7 text-sky-100">
                        Verify your mobile number to continue with your cab or self-drive booking.
                    </p>
                </div>

                <div class="relative z-10">
                    <div
                        class="overflow-hidden rounded-3xl border border-white/15 bg-white/10 p-5 backdrop-blur"
                    >
                        <img
                            src="{{ asset('./img/loginimg.jpg') }}"
                            alt="Secure mobile login"
                            class="mx-auto h-56 w-full rounded-2xl bg-white object-contain"
                        >
                    </div>

                    <div class="mt-5 grid grid-cols-3 gap-3">
                        <div class="rounded-2xl border border-white/15 bg-white/10 p-3 text-center backdrop-blur">
                            <svg
                                class="mx-auto h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M5 12h14M12 5l7 7-7 7"
                                />
                            </svg>
                            <p class="mt-2 text-[11px] font-extrabold uppercase tracking-wide">
                                Fast
                            </p>
                        </div>

                        <div class="rounded-2xl border border-white/15 bg-white/10 p-3 text-center backdrop-blur">
                            <svg
                                class="mx-auto h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 3l7 4v5c0 4.6-2.9 7.7-7 9-4.1-1.3-7-4.4-7-9V7l7-4z"
                                />
                            </svg>
                            <p class="mt-2 text-[11px] font-extrabold uppercase tracking-wide">
                                Secure
                            </p>
                        </div>

                        <div class="rounded-2xl border border-white/15 bg-white/10 p-3 text-center backdrop-blur">
                            <svg
                                class="mx-auto h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 17.3l-5.2 3 1.4-5.9-4.6-4 6-.5L12 4.5l2.4 5.4 6 .5-4.6 4 1.4 5.9-5.2-3z"
                                />
                            </svg>
                            <p class="mt-2 text-[11px] font-extrabold uppercase tracking-wide">
                                Trusted
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- MOBILE NUMBER FORM PANEL --}}
            <div class="relative flex min-h-[520px] flex-col justify-center bg-white p-5 sm:p-8 lg:p-10">
                <button
                    type="button"
                    wire:click="closeModal"
                    aria-label="Close login popup"
                    class="absolute right-4 top-4 z-20 flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-2xl font-medium leading-none text-slate-500 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-4 focus:ring-sky-100 sm:right-5 sm:top-5"
                >
                    ×
                </button>

                {{-- MOBILE IMAGE --}}
                <div class="mb-5 text-center lg:hidden">
                    <div
                        class="mx-auto flex h-20 w-20 items-center justify-center overflow-hidden rounded-3xl bg-sky-50 ring-8 ring-sky-50/60 sm:h-24 sm:w-24"
                    >
                        <img
                            src="{{ asset('./img/loginimg.jpg') }}"
                            alt="Secure login"
                            class="h-full w-full object-contain"
                        >
                    </div>
                </div>

                <div class="mx-auto w-full max-w-sm">
                    <div class="text-center lg:text-left">
                        <div
                            class="mb-4 inline-flex items-center gap-2 rounded-full bg-sky-50 px-3 py-1.5 text-[11px] font-extrabold uppercase tracking-[0.14em] text-sky-700"
                        >
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            OTP Protected
                        </div>

                        <h2
                            id="mobile-login-title"
                            class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl"
                        >
                            Continue with mobile
                        </h2>

                        <p class="mt-2 text-sm font-medium leading-6 text-slate-500">
                            Enter your 10-digit mobile number. We will send a one-time password for verification.
                        </p>
                    </div>

                    <form wire:submit="sendOtpToBack" autocomplete="off" class="mt-7">
                        <label
                            for="otp-mobile"
                            class="mb-2 block text-xs font-extrabold uppercase tracking-wider text-slate-600"
                        >
                            Mobile Number
                        </label>

                        <div class="relative">
                            <div
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                            >
                                <span
                                    class="flex h-10 items-center rounded-lg border border-slate-200 bg-white px-3 text-sm font-extrabold text-slate-700 shadow-sm"
                                >
                                    +91
                                </span>
                            </div>

                            <input
                                type="tel"
                                id="otp-mobile"
                                wire:model.blur="mobileNumber"
                                placeholder="Enter mobile number"
                                inputmode="numeric"
                                maxlength="10"
                                autocomplete="tel"
                                class="h-16 w-full rounded-2xl border border-slate-200 bg-slate-50 pl-[82px] pr-4 text-base font-extrabold tracking-wide text-slate-900 outline-none transition placeholder:font-medium placeholder:tracking-normal placeholder:text-slate-400 focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100"
                            >
                        </div>

                        <div class="mt-3 flex items-start gap-2 rounded-xl bg-slate-50 px-3 py-2.5">
                            <svg
                                class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 3l7 4v5c0 4.6-2.9 7.7-7 9-4.1-1.3-7-4.4-7-9V7l7-4z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 12l2 2 4-4"
                                />
                            </svg>

                            <p class="text-xs font-medium leading-5 text-slate-500">
                                Your number is used only for OTP verification and booking updates.
                            </p>
                        </div>

                        <button
    type="submit"
    wire:loading.attr="disabled"
    wire:target="sendOtpToBack"
    {{ strlen($mobileNumber) == 10 ? '' : 'disabled' }}
    class="mt-5 flex h-14 w-full items-center justify-center rounded-xl px-5 text-sm font-extrabold uppercase tracking-wide text-white transition
    disabled:cursor-not-allowed disabled:opacity-70
    {{ strlen($mobileNumber) == 10
        ? 'bg-sky-500 hover:bg-sky-600 focus:ring-4 focus:ring-sky-200'
        : 'bg-slate-400' }}">

    <span wire:loading.remove wire:target="sendOtpToBack">
        Continue
    </span>

    <span wire:loading.flex wire:target="sendOtpToBack" class="items-center gap-2">
        <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity=".25"/>
            <path d="M22 12a10 10 0 00-10-10" stroke="currentColor" stroke-width="3"/>
        </svg>

        Sending OTP...
    </span>
</button>
                    </form>

                    <div class="mt-6 flex items-center justify-center gap-4 text-[11px] font-bold text-slate-400">
                        <span class="flex items-center gap-1.5">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            Secure
                        </span>

                        <span class="h-1 w-1 rounded-full bg-slate-300"></span>

                        <span>No password needed</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endteleport
    {{-- =========================================================
        VERIFY OTP MODAL
    ========================================================== --}}

	@teleport('body')
    <div
        class="fixed inset-0 z-[999999] {{ $sendOtpVerify ? 'flex' : 'hidden' }} items-center justify-center overflow-y-auto bg-slate-950/75 px-3 py-4 backdrop-blur-sm sm:px-6 sm:py-8"
        role="dialog"
        aria-modal="true"
        aria-labelledby="verify-otp-title"
    >

        <div
            class="relative my-auto grid w-full max-w-4xl overflow-hidden rounded-[28px] border border-white/20 bg-white shadow-2xl shadow-slate-950/30 lg:grid-cols-[1.05fr_.95fr]"
        >
            {{-- DESKTOP / TABLET BRAND PANEL --}}
            <div
                class="relative hidden min-h-[560px] overflow-hidden bg-gradient-to-br from-indigo-700 via-sky-600 to-sky-500 p-10 text-white lg:flex lg:flex-col lg:justify-between"
            >
                <div
                    class="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-white/10 blur-2xl"
                ></div>

                <div
                    class="absolute -bottom-24 -left-20 h-72 w-72 rounded-full bg-indigo-300/20 blur-3xl"
                ></div>

                <div class="relative z-10">
                    <div
                        class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-extrabold uppercase tracking-[0.18em] backdrop-blur"
                    >
                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 3l7 4v5c0 4.6-2.9 7.7-7 9-4.1-1.3-7-4.4-7-9V7l7-4z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9 12l2 2 4-4"
                            />
                        </svg>

                        Final Verification
                    </div>

                    <h2 class="mt-7 max-w-md text-4xl font-black leading-tight">
                        Verify once. Travel with confidence.
                    </h2>

                    <p class="mt-4 max-w-md text-base font-medium leading-7 text-sky-100">
                        Enter the four-digit OTP sent to your mobile number to complete your booking securely.
                    </p>
                </div>

                <div class="relative z-10">
                    <div
                        class="overflow-hidden rounded-3xl border border-white/15 bg-white/10 p-5 backdrop-blur"
                    >
                        <img
                            src="{{ asset('./img/passwordimg.jpg') }}"
                            alt="OTP verification"
                            loading="lazy"
                            decoding="async"
                            class="mx-auto h-56 w-full rounded-2xl bg-white object-contain"
                        >
                    </div>

                    <div
                        class="mt-5 flex items-center justify-between rounded-2xl border border-white/15 bg-white/10 px-5 py-4 backdrop-blur"
                    >
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-sky-100">
                                Verification
                            </p>
                            <p class="mt-1 text-sm font-extrabold">
                                4-digit secure OTP
                            </p>
                        </div>

                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/15"
                        >
                            <svg
                                class="h-6 w-6"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                aria-hidden="true"
                            >
                                <rect x="5" y="10" width="14" height="10" rx="2"></rect>
                                <path d="M8 10V7a4 4 0 018 0v3"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- OTP FORM PANEL --}}
            <div class="relative flex min-h-[520px] flex-col justify-center bg-white p-5 sm:p-8 lg:p-10">
                <button
                    type="button"
                    wire:click="closeModal"
                    aria-label="Close OTP popup"
                    class="absolute right-4 top-4 z-20 flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-2xl font-medium leading-none text-slate-500 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-4 focus:ring-sky-100 sm:right-5 sm:top-5"
                >
                    ×
                </button>

                {{-- MOBILE IMAGE --}}
                <div class="mb-5 text-center lg:hidden">
                    <div
                        class="mx-auto flex h-20 w-20 items-center justify-center overflow-hidden rounded-3xl bg-sky-50 ring-8 ring-sky-50/60 sm:h-24 sm:w-24"
                    >
                        <img
                            src="{{ asset('./img/passwordimg.jpg') }}"
                            alt="OTP verification"
                            loading="lazy"
                            decoding="async"
                            class="h-full w-full object-contain"
                        >
                    </div>
                </div>

                <div class="mx-auto w-full max-w-sm">
                    <div class="text-center lg:text-left">
                        <div
                            class="mb-4 inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-[11px] font-extrabold uppercase tracking-[0.14em] text-emerald-700"
                        >
                            <span class="relative flex h-2 w-2">
                                <span
                                    class="absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"
                                ></span>
                                <span
                                    class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"
                                ></span>
                            </span>
                            OTP Sent Successfully
                        </div>

                        <h2
                            id="verify-otp-title"
                            class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl"
                        >
                            Verify your number
                        </h2>

                        <p class="mt-2 text-sm font-medium leading-6 text-slate-500">
                            Enter the four-digit OTP sent to
                            <span class="font-extrabold text-slate-800">
                                +91 {{ $mobileNumber }}
                            </span>
                        </p>
                    </div>

                    <div class="mt-7">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <label
                                class="text-xs font-extrabold uppercase tracking-wider text-slate-600"
                            >
                                Enter OTP
                            </label>

                            <button
                                type="button"
                                wire:click="resendOtp"
                                wire:loading.attr="disabled"
                                wire:target="resendOtp"
                                class="rounded-lg px-2 py-1 text-xs font-extrabold text-sky-600 transition hover:bg-sky-50 hover:text-sky-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <span wire:loading.remove wire:target="resendOtp">
                                    Resend OTP
                                </span>

                                <span wire:loading wire:target="resendOtp">
                                    Sending...
                                </span>
                            </button>
                        </div>

                        <div
                            x-data="{
                                digits: ['', '', '', ''],
                                verifying: false,
                                error: '',

                                moveNext(index, event) {
                                    const value = String(event.target.value || '')
                                        .replace(/\D/g, '')
                                        .slice(-1);

                                    this.digits[index] = value;
                                    event.target.value = value;
                                    this.error = '';

                                    if (value && index < 3) {
                                        this.$nextTick(() => this.$refs['otp' + (index + 1)].focus());
                                    }
                                },

                                keydown(index, event) {
                                    if (event.key === 'Backspace' && !this.digits[index] && index > 0) {
                                        event.preventDefault();
                                        this.digits[index - 1] = '';
                                        this.$nextTick(() => {
                                            const previous = this.$refs['otp' + (index - 1)];
                                            previous.value = '';
                                            previous.focus();
                                        });
                                    }

                                    if (event.key === 'ArrowLeft' && index > 0) {
                                        event.preventDefault();
                                        this.$refs['otp' + (index - 1)].focus();
                                    }

                                    if (event.key === 'ArrowRight' && index < 3) {
                                        event.preventDefault();
                                        this.$refs['otp' + (index + 1)].focus();
                                    }

                                    if (event.key === 'Enter') {
                                        event.preventDefault();
                                        this.submitOtp();
                                    }
                                },

                                pasteOtp(event) {
                                    const pasted = (event.clipboardData || window.clipboardData)
                                        .getData('text')
                                        .replace(/\D/g, '')
                                        .slice(0, 4);

                                    if (pasted.length < 2) {
                                        return;
                                    }

                                    this.$nextTick(() => {
                                        this.digits = ['', '', '', ''];

                                        pasted.split('').forEach((digit, index) => {
                                            this.digits[index] = digit;
                                            this.$refs['otp' + index].value = digit;
                                        });

                                        this.error = '';

                                        const focusIndex = Math.min(pasted.length, 4) - 1;
                                        this.$refs['otp' + focusIndex].focus();
                                    });
                                },

                                async submitOtp() {
                                    if (this.verifying) {
                                        return;
                                    }

                                    const otp = this.digits.join('');

                                    if (!/^\d{4}$/.test(otp)) {
                                        this.error = 'Please enter the complete 4-digit OTP.';
                                        const emptyIndex = this.digits.findIndex(digit => !digit);
                                        this.$nextTick(() => {
                                            this.$refs['otp' + (emptyIndex === -1 ? 0 : emptyIndex)].focus();
                                        });
                                        return;
                                    }

                                    this.error = '';
                                    this.verifying = true;

                                    try {
                                        await $wire.set('verifyOtp', otp);

                                        @if ($selected_tab === 'one_way')
                                            await $wire.call('verifySubmitOtp');
                                        @elseif ($selected_tab === 'return')
                                            await $wire.call('verifySubmitOtpReturn');
                                        @elseif ($selected_tab === 'local')
                                            await $wire.call('verifySubmitLocal');
                                        @elseif ($selected_tab === 'self_drive')
                                            await $wire.call('verifySubmitOtpSelfDrive');
                                        @endif
                                    } catch (error) {
                                        console.error('OTP verification failed:', error);
                                        this.error = 'OTP verification could not be completed. Please try again.';
                                    } finally {
                                        this.verifying = false;
                                    }
                                }
                            }"
                            x-init="$nextTick(() => $refs.otp0.focus())"
                        >
                            <div wire:ignore class="grid grid-cols-4 gap-2.5 sm:gap-3">
                                <input
                                    type="text"
                                    maxlength="1"
                                    inputmode="numeric"
                                    pattern="[0-9]*"
                                    autocomplete="one-time-code"
                                    x-ref="otp0"
                                    x-model="digits[0]"
                                    x-on:input="moveNext(0, $event)"
                                    x-on:keydown="keydown(0, $event)"
                                    x-on:paste="pasteOtp($event)"
                                    aria-label="OTP digit 1"
                                    class="passwordHome h-14 w-full rounded-2xl border-2 border-slate-200 bg-slate-50 text-center text-xl font-black text-slate-900 outline-none transition focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100 sm:h-16 sm:text-2xl"
                                >

                                <input
                                    type="text"
                                    maxlength="1"
                                    inputmode="numeric"
                                    pattern="[0-9]*"
                                    x-ref="otp1"
                                    x-model="digits[1]"
                                    x-on:input="moveNext(1, $event)"
                                    x-on:keydown="keydown(1, $event)"
                                    aria-label="OTP digit 2"
                                    class="passwordHome h-14 w-full rounded-2xl border-2 border-slate-200 bg-slate-50 text-center text-xl font-black text-slate-900 outline-none transition focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100 sm:h-16 sm:text-2xl"
                                >

                                <input
                                    type="text"
                                    maxlength="1"
                                    inputmode="numeric"
                                    pattern="[0-9]*"
                                    x-ref="otp2"
                                    x-model="digits[2]"
                                    x-on:input="moveNext(2, $event)"
                                    x-on:keydown="keydown(2, $event)"
                                    aria-label="OTP digit 3"
                                    class="passwordHome h-14 w-full rounded-2xl border-2 border-slate-200 bg-slate-50 text-center text-xl font-black text-slate-900 outline-none transition focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100 sm:h-16 sm:text-2xl"
                                >

                                <input
                                    type="text"
                                    maxlength="1"
                                    inputmode="numeric"
                                    pattern="[0-9]*"
                                    x-ref="otp3"
                                    x-model="digits[3]"
                                    x-on:input="moveNext(3, $event)"
                                    x-on:keydown="keydown(3, $event)"
                                    aria-label="OTP digit 4"
                                    class="passwordHome h-14 w-full rounded-2xl border-2 border-slate-200 bg-slate-50 text-center text-xl font-black text-slate-900 outline-none transition focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-100 sm:h-16 sm:text-2xl"
                                >
                            </div>

                            <p
                                x-show="error"
                                x-text="error"
                                x-cloak
                                class="mt-3 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-600"
                            ></p>

                            @if (!empty($oneWayMsg))
                                <p class="mt-3 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-600">
                                    {{ $oneWayMsg }}
                                </p>
                            @endif

                            <div class="mt-3 flex items-start gap-2 rounded-xl bg-slate-50 px-3 py-2.5">
                                <svg
                                    class="mt-0.5 h-4 w-4 shrink-0 text-sky-500"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    aria-hidden="true"
                                >
                                    <circle cx="12" cy="12" r="9"></circle>
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 8v4l2.5 1.5"
                                    />
                                </svg>

                                <p class="text-xs font-medium leading-5 text-slate-500">
                                    OTP is time-sensitive. Do not share it with anyone.
                                </p>
                            </div>

                            <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-[1fr_1.45fr]">
                                <button
                                    type="button"
                                    wire:click="backButton"
                                    x-bind:disabled="verifying"
                                    class="order-2 flex h-14 items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-3.5 text-sm font-extrabold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100 disabled:cursor-not-allowed disabled:opacity-60 sm:order-1"
                                >
                                    <svg
                                        class="h-4 w-4"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        aria-hidden="true"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M19 12H5M11 18l-6-6 6-6"
                                        />
                                    </svg>

                                    Change Number
                                </button>

                                <button
                                    type="button"
                                    x-on:click="submitOtp()"
                                    x-bind:disabled="verifying"
                                    class="order-1 flex h-14 items-center justify-center gap-2 rounded-2xl bg-sky-500 px-4 py-3.5 text-sm font-extrabold text-white shadow-lg shadow-sky-500/25 transition hover:bg-sky-600 focus:outline-none focus:ring-4 focus:ring-sky-200 disabled:cursor-not-allowed disabled:opacity-70 sm:order-2"
                                >
                                    <span x-show="!verifying">Verify & Continue</span>
                                    <span x-show="verifying" x-cloak>Verifying...</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <p class="mt-6 text-center text-[11px] font-medium leading-5 text-slate-400">
                        By continuing, you agree to receive booking-related OTP and service updates.
                    </p>

                </div>

            </div>
        </div>
    </div>@endteleport
</div>

@script
<script>
    (() => {
        if (window.__duraServiceSearchLocationRequested) {
            return;
        }

        window.__duraServiceSearchLocationRequested = true;

        if (!('geolocation' in navigator)) {
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                $wire.detectCurrentLocation(
                    Number(position.coords.latitude),
                    Number(position.coords.longitude)
                );
            },
            () => {
                // Permission denied, unavailable, or timed out:
                // keep the city field blank so the customer can choose manually.
            },
            {
                enableHighAccuracy: false,
                timeout: 10000,
                maximumAge: 300000
            }
        );
    })();
</script>
@endscript