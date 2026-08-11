<div wire:key="product-detailed-page-component-root">
<div wire:key="product-detailed-page-root" class="premium-motion-page rides-premium-page product-ride-theme w-full max-w-[86rem] px-3 sm:px-5 lg:px-6 mx-auto">
<div class="ride-shell rides-premium-shell font-poppins rounded-3xl py-3 sm:py-5">

    @section('title', $seoTitle)
    @section('description', $seoDescription)
    @section('keywords', $metaKeywords)
    @section('image', $imageMeta)
    @section('canonical', $canonicalUrl)
    @section('robots', $robots)
    @section('og_type', $ogType)

    @push('schema')
        @if (!empty($serviceSchema))
            <script type="application/ld+json">
                {!! json_encode(
                    $serviceSchema,
                    JSON_UNESCAPED_SLASHES
                    | JSON_UNESCAPED_UNICODE
                    | JSON_PRETTY_PRINT
                ) !!}
            </script>
        @endif

        @if (!empty($faqSchema) && !empty($faqSchema['mainEntity']))
            <script type="application/ld+json">
                {!! json_encode(
                    $faqSchema,
                    JSON_UNESCAPED_SLASHES
                    | JSON_UNESCAPED_UNICODE
                    | JSON_PRETTY_PRINT
                ) !!}
            </script>
        @endif

        @if (!empty($breadcrumbSchema) && !empty($breadcrumbSchema['itemListElement']))
            <script type="application/ld+json">
                {!! json_encode(
                    $breadcrumbSchema,
                    JSON_UNESCAPED_SLASHES
                    | JSON_UNESCAPED_UNICODE
                    | JSON_PRETTY_PRINT
                ) !!}
            </script>
        @endif
    @endpush

    <nav class="product-breadcrumb mb-3 mt-2 text-sm text-slate-600" aria-label="Breadcrumb">
        <ol class="flex flex-wrap items-center gap-2">
            <li><a href="{{ url('/') }}" class="hover:text-sky-600">Home</a></li>
            <li aria-hidden="true">/</li>
            <li><a href="{{ url('/routes') }}" class="hover:text-sky-600">Cab Routes</a></li>
            <li aria-hidden="true">/</li>
            <li class="font-semibold text-slate-900" aria-current="page">{{ $routeName }}</li>
        </ol>
    </nav>

    <header class="product-premium-header premium-hero-animate mb-5">
        <h1 class="text-2xl font-extrabold leading-tight text-slate-900 sm:text-3xl">
            {{ $routeName }} {{ $tripLabel }} Booking
        </h1>
        <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600 sm:text-base">{{ $seoDescription }}</p>
    </header>


    {{-- OTP FARE UNLOCK MODAL --}}
    @if ($showOtpModal)
        <div class="fixed inset-0 z-[100] flex items-end justify-center bg-slate-950/55 p-0 sm:items-center sm:px-4" wire:click.self="closeOtpModal">
            <div class="w-full max-w-md overflow-hidden rounded-t-3xl bg-white shadow-2xl sm:rounded-3xl">
                <div class="px-5 py-4 text-white sm:px-6 sm:py-5" style="background:linear-gradient(135deg,#0797ee 0%,#0878df 100%);">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-sky-100">DuraCabs verified fare</p>
                            <h2 class="mt-1 text-xl font-bold sm:text-2xl">Exact fare unlock karein</h2>
                        </div>
                        <button type="button" wire:click="closeOtpModal" class="rounded-full bg-white/15 p-2 hover:bg-white/25" aria-label="Close">✕</button>
                    </div>
                </div>

                <div class="p-5 sm:p-6">
                    @if ($otpStage === 'mobile')
                        <p class="mb-5 text-sm leading-6 text-slate-600">Mobile verify hote hi exact cab prices dikhenge aur aapki enquiry generate ho jayegi.</p>
                        <form wire:submit.prevent="sendFareOtp" class="space-y-4">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-800">Mobile Number</label>
                                <div class="flex overflow-hidden rounded-xl border border-slate-300 focus-within:border-sky-500 focus-within:ring-2 focus-within:ring-sky-100">
                                    <span class="flex items-center bg-slate-50 px-4 font-semibold text-slate-600">+91</span>
                                    <input type="tel" inputmode="numeric" maxlength="10" wire:model="mobileNumber" class="w-full border-0 px-4 py-3 outline-none focus:ring-0" placeholder="9876543210" autofocus>
                                </div>
                                @error('mobileNumber') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            @if ($otpError)<p class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ $otpError }}</p>@endif
                            <button type="submit" class="w-full rounded-xl bg-sky-600 px-5 py-3 font-bold text-white transition hover:bg-sky-700 disabled:opacity-60" wire:loading.attr="disabled" wire:target="sendFareOtp">
                                <span wire:loading.remove wire:target="sendFareOtp">4 Digit OTP Bhejein</span>
                                <span wire:loading wire:target="sendFareOtp">Sending...</span>
                            </button>
                        </form>
                    @else
                        <p class="text-sm text-slate-600">+91 {{ $mobileNumber }} par bheja gaya 4 digit OTP enter karein.</p>
                        <form wire:submit.prevent="verifyFareOtp" class="mt-5 space-y-4">
                            <input type="text" inputmode="numeric" maxlength="4" wire:model="otpCode" x-data x-on:input="if ($el.value.replace(/\D/g,'').length === 4) { $wire.verifyFareOtp() }" class="w-full rounded-xl border border-slate-300 px-4 py-4 text-center text-3xl font-extrabold tracking-[1rem] outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100" placeholder="••••" autofocus>
                            @error('otpCode') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                            @if ($otpError)<p class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ $otpError }}</p>@endif
                            <button type="submit" class="w-full rounded-xl bg-sky-600 px-5 py-3 font-bold text-white hover:bg-sky-700" wire:loading.attr="disabled" wire:target="verifyFareOtp">
                                <span wire:loading.remove wire:target="verifyFareOtp">Verify & Unlock Fare</span>
                                <span wire:loading wire:target="verifyFareOtp">Verifying...</span>
                            </button>
                            <div class="flex items-center justify-between text-sm">
                                <button type="button" wire:click="changeOtpMobile" class="font-semibold text-slate-600 hover:text-sky-700">Mobile badlein</button>
                                <button type="button" wire:click="sendFareOtp" class="font-semibold text-sky-600 hover:text-sky-800">OTP dobara bhejein</button>
                            </div>
                        </form>
                    @endif
                    <div class="mt-5 flex items-center justify-center gap-4 border-t border-slate-100 pt-4 text-xs text-slate-500">
                        <span>✓ Secure verification</span><span>✓ No hidden charges</span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- SAME PAGE EDIT TRIP MODAL --}}
    @if ($showEditTripModal)
        <div class="fixed inset-0 z-[95] flex items-center justify-center overflow-y-auto bg-slate-950/65 px-4 py-8" wire:click.self="closeEditTripModal">
            <div class="w-full max-w-2xl rounded-3xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b px-6 py-5">
                    <div><p class="text-sm font-semibold text-sky-600">Search update</p><h2 class="text-2xl font-bold text-slate-900">Trip Edit Karein</h2></div>
                    <button type="button" wire:click="closeEditTripModal" class="rounded-full bg-slate-100 px-3 py-2 text-slate-600 hover:bg-slate-200">✕</button>
                </div>
                <form wire:submit.prevent="updateTripSearch" class="grid gap-5 p-6 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-semibold">Trip Type</label>
                        <select wire:model.live="editRideType" class="w-full rounded-xl border-slate-300">
                            <option value="one_way">One Way</option>
                            <option value="local">Local</option>
                            <option value="self_drive">Self Drive</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold">Pickup City</label>
                        <select wire:model="editPickupId" class="w-full rounded-xl border-slate-300">
                            <option value="">Select city</option>
                            @foreach ($allCities as $city)<option value="{{ $city->id }}">{{ $city->name }}</option>@endforeach
                        </select>
                        @error('editPickupId')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    @if ($editRideType === 'one_way')
                    <div>
                        <label class="mb-2 block text-sm font-semibold">Drop City</label>
                        <select wire:model="editDropId" class="w-full rounded-xl border-slate-300">
                            <option value="">Select city</option>
                            @foreach ($allCities as $city)<option value="{{ $city->id }}">{{ $city->name }}</option>@endforeach
                        </select>
                        @error('editDropId')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    @endif
                    <div>
                        <label class="mb-2 block text-sm font-semibold">Pickup Date</label>
                        <input type="date" min="{{ now()->toDateString() }}" wire:model="editDate" class="w-full rounded-xl border-slate-300">
                        @error('editDate')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold">Pickup Time</label>
                        <input type="time" wire:model="editTime" class="w-full rounded-xl border-slate-300">
                        @error('editTime')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    @if ($editRideType === 'self_drive')
                    <div><label class="mb-2 block text-sm font-semibold">Drop Date</label><input type="date" wire:model="editEndDate" class="w-full rounded-xl border-slate-300"></div>
                    <div><label class="mb-2 block text-sm font-semibold">Drop Time</label><input type="time" wire:model="editEndTime" class="w-full rounded-xl border-slate-300"></div>
                    @endif
                    <div class="flex gap-3 border-t pt-5 md:col-span-2">
                        <button type="button" wire:click="closeEditTripModal" class="w-full rounded-xl border border-slate-300 px-5 py-3 font-semibold text-slate-700">Cancel</button>
                        <button type="submit" class="w-full rounded-xl bg-sky-600 px-5 py-3 font-bold text-white hover:bg-sky-700">Update Search</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

   

 @if ($tab === 'one_way')
        <div class="fixed  z-50 inset-0 bg-gray-900 bg-opacity-60 overflow-y-auto h-full w-full px-4 ">
            <div class="relative top-40 mx-auto shadow-xl rounded-md bg-white max-w-md">


                <form
                    wire:submit.prevent='submitOneWay([{{ $ride->id }},"{{ $time }}","{{ $tab }}","{{ $date }}","{{ $price }}","{{ $name }}", "{{ $categoryName }}", "{{ $toll }}", "{{ $newVehical }}", "{{ $petFrindly }}", "{{ $roof_career }}"])'>
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="flex">

                            <div class="mt-3 w-full text-center sm:ml-4 sm:mt-0 sm:text-left">
                                <h3 class="text-2xl text-center font-semibold leading-6 text-gray-900" id="modal-title">
                                    Pickup Details
                                </h3>
                                <div class="mt-2 w-full">

                                    <div class="">
                                        <label for="" class="font-semibold">PickUp Date</label>
                                        <input type="date" wire:model.live='date' maxlength="4" placeholder="date"
                                            required
                                            class="arriveDate bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                                    </div>
                                    <div class="mt-3">
                                        <label for="" class="font-semibold">PickUp Time</label>
                                        <input type="time" wire:model.live='time' maxlength="4" placeholder="time"
                                            required
                                            class="arriveTime bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                                    </div>

                                    <button type='submit'
                                        class="main-color mt-4 text-xl w-full text-white p-2 rounded-sm">
                                        Book Now
                                    </button>




                                </div>
                            </div>
                        </div>
                    </div>

                </form>

            </div>
        </div>
    @endif

    @if ($tab === 'local')
        <div class="fixed  z-50 inset-0 bg-gray-900 bg-opacity-60 overflow-y-auto h-full w-full px-4 ">
            <div class="relative top-40 mx-auto shadow-xl rounded-md bg-white max-w-md">

                <form
                    wire:submit.prevent='submitLocal([{{ $ride->id }},"{{ $time }}","{{ $tab }}","{{ $date }}","{{ $plan }}","{{ 1 }}","{{ $price }}","{{ $name }}", "{{ $categoryName }}", "{{ $toll }}", "{{ $newVehical }}", "{{ $petFrindly }}", "{{ $roof_career }}"])'>
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="flex">

                            <div class="mt-3 w-full text-center sm:ml-4 sm:mt-0 sm:text-left">
                                <h3 class="text-2xl text-center font-semibold leading-6 text-gray-900" id="modal-title">
                                    Pickup Details
                                </h3>
                                <div class="mt-2 w-full">

                                    <div class="">
                                        <label class="font-semibold" for="">PickUp Date</label>
                                        <input type="date" wire:model.live='date' maxlength="4" placeholder="date"
                                            required
                                            class="arriveDate bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                                    </div>
                                    <div class="mt-3">
                                        <label class="font-semibold" for="">PickUp Time</label>
                                        <input type="time" wire:model.live='time' maxlength="4" placeholder="time"
                                            required
                                            class="arriveTime bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                                    </div>
                                    <div class="mt-3">
                                        {{ $plan }}
                                        <label class="font-semibold" for="">Select Plans</label>
                                        <select wire:model.live='plan' name="plan" id="plan" required
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                            <option value="4 Hour / 40 Km">4 Hour / 40 Km</option>
                                            <option value="8 Hour / 80 Km">8 Hour / 80 Km</option>
                                            <option value="12 Hour / 120 Km">12 Hour / 120 Km</option>
                                        </select>
                                    </div>
                                    <button type="submit"
                                        class="main-color mt-4 text-xl w-full text-white p-2 rounded-sm">
                                        Book Now
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </form>

            </div>
        </div>
    @endif

    @if ($tab === 'self_drive')
        <div class="fixed z-50 inset-0 bg-gray-900 bg-opacity-60 overflow-y-auto h-full w-full -top-20 ">
            <div class="relative top-40 mx-auto shadow-xl rounded-md bg-white max-w-md">

                <form
                    wire:submit.prevent="submitSelfDrive({{ $selectedVehicle?->id ?? 0 }})">
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="flex">

                            <div class="mt-3 w-full text-center sm:ml-4 sm:mt-0 sm:text-left">
                                <h3 class="text-2xl text-center font-semibold leading-6 text-gray-900" id="modal-title">
                                    Pickup Details
                                </h3>
                                <div class="mt-2 w-full">

                                    <div class="">
                                        <label class="font-semibold w-100" for="">PickUp Date</label>
                                        
                                        <input type="date" wire:model.live='date' maxlength="4" placeholder="date"
                                            required
                                            class="arriveDate bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                                    </div>
                                    <div class="mt-3">
                                        <label class="font-semibold" for="">PickUp Time</label>
                                        <input type="time" wire:model.live='time' maxlength="4" placeholder="time"
                                            required
                                            class="arriveTime bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                                    </div>


                                    <div class="">
                                        <label class="font-semibold" for="">Drop Date</label>
                                        <input type="date" wire:model.live='endDate' maxlength="4"
                                            placeholder="date" required
                                            class="arriveDate bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                                    </div>

                                    <div class="mt-3">
                                        <label class="font-semibold" for="">Drop Time</label>
                                        <input type="time" wire:model.live='endTime' maxlength="4"
                                            placeholder="time" required
                                            class="arriveTime bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                                    </div>


                                    @if ($hours)
                                        <div class="mt-4 rounded-xl bg-emerald-50 p-3 text-center">
                                            <p class="font-extrabold text-emerald-700">
                                                Total Hours: {{ $hours }}
                                            </p>
                                            <p class="mt-1 font-extrabold text-emerald-700">
                                                Estimated Price:
                                                {{ Number::currency(((float) ($selectedVehicle?->hourly_price ?? $ride->price)) * $hours, 'INR') }}
                                            </p>
                                        </div>
                                    @endif

                                    @if ($selfDriveAvailabilityMessage)
                                        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-900">
                                            {{ $selfDriveAvailabilityMessage }}
                                        </div>
                                    @endif

                                    <button type="submit"
                                        wire:loading.attr="disabled"
                                        wire:target="submitSelfDrive"
                                        class="main-color mt-4 text-xl w-full text-white p-2 rounded-sm disabled:opacity-60">
                                        <span wire:loading.remove wire:target="submitSelfDrive">Check Availability & Book</span>
                                        <span wire:loading wire:target="submitSelfDrive">Checking Availability...</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </form>

            </div>
        </div>
    @endif

    {{-- <section class="overflow-hidden bg-white  font-poppins dark:bg-gray-800">
        <div class="max-w-6xl  mx-auto lg:py-4 md:px-4">
            <div class="flex flex-wrap -mx-4">
                <div class="w-full md:w-1/3 md:mb-0 z-0" x-data="{ mainImage: '{{ url('storage') }}/{{ $ride->images[0] }}' }">
                    <div class="sticky top-0 z-50 overflow-hidden ">
                        <div class="relative  lg:h-2/ ">
                            <img x-bind:src="mainImage" alt="" class="object-cover w-full lg:h-full ">
                        </div>
                        <div class="flex-wrap hidden md:flex ">

                            @foreach ($ride->images as $image)
                                <div class="w-1/2 p-2 sm:w-1/4"
                                    x-on:click="mainImage='{{ url('storage') }}/{{ $image }}'">
                                    <img src="{{ url('storage') }}/{{ $image }}" alt="{{ $ride->name }}"
                                        class="object-cover w-full  cursor-pointer hover:border hover:border-blue-500">
                                </div>
                            @endforeach



                        </div>

                    </div>
                </div>
                <div class="w-full px-4 md:w-1/2 ">
                    <div class="lg:pl-20">
                        <div class="mb-2 [&>ul]:list-disc [&>ul]:ml-4">
                            <h2 class="max-w-xl mb-1 text-xl font-bold dark:text-gray-400 md:text-4xl">
                                {{ $ride->name }}</h2>
                            <p class="inline-block mb-1 text-4xl font-bold text-gray-700 dark:text-gray-400 ">
                                <span>{{ Number::currency($ride->price, 'INR') }}</span>
                                <span
                                    class="text-base font-normal text-gray-500 line-through dark:text-gray-400">{{ Number::currency($ride->max_price, 'INR') }}</span>
                            </p>

                        </div>



                        @if ($ride->ride_type === 'one_way')
                            <div class="flex flex-wrap items-center gap-4">
                                <button
                                    wire:click='tabValue(["one_way","{{ $ride->price }}","{{ $ride->name }}","{{ $ride->category->name }}"])'
                                    class="w-full p-4 bg-sky-500 rounded-md lg:w-2/5 dark:text-gray-200 text-gray-50 hover:bg-blue-600 dark:bg-blue-500 dark:hover:bg-blue-700">
                                    <span wire:loading.remove
                                        wire:target='tabValue(["one_way","{{ $ride->price }}","{{ $ride->name }}","{{ $ride->category->name }}"])'>
                                        Book Now
                                    </span>

                                    <span wire:loading
                                        wire:target='tabValue(["one_way","{{ $ride->price }}","{{ $ride->name }}","{{ $ride->category->name }}"])'>
                                        Adding...
                                    </span>

                                </button>
                            </div>
                        @endif

                        @if ($ride->ride_type === 'local')
                            <div class="flex flex-wrap items-center gap-4">
                                <button wire:click='tabValue(["local","{{ $ride->price }}","{{ $ride->name }}","{{ $ride->category->name }}"])'
                                    class="w-full p-4 bg-sky-500 rounded-md lg:w-2/5 dark:text-gray-200 text-gray-50 hover:bg-blue-600 dark:bg-blue-500 dark:hover:bg-blue-700">
                                    <span wire:loading.remove wire:target='tabValue("local")'>
                                        Book Now
                                    </span>

                                   

                                    <span wire:loading wire:target='tabValue("local")'>
                                        Adding...
                                    </span>

                                </button>
                            </div>
                        @endif

                        @if ($ride->ride_type === 'self_drive')
                            <div class="flex flex-wrap items-center gap-4">
                                <button
                                    @if($selectedVehicle)
                                        wire:click='tabValue(["self_drive","{{ (float) $selectedVehicle->hourly_price }}","{{ addslashes($selectedVehicle->display_name) }}","{{ addslashes($selectedVehicle->car_classification ?: 'Self Drive Car') }}","{{ (float) ($selectedVehicle->security_deposit ?? 0) }}"])'
                                    @else
                                        type="button" disabled
                                    @endif
                                    class="w-full p-4 bg-sky-500 rounded-md lg:w-2/5 dark:text-gray-200 text-gray-50 hover:bg-blue-600 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-blue-500 dark:hover:bg-blue-700">
                                    <span wire:loading.remove wire:target='tabValue("self_drive")'>
                                        Book Now
                                    </span>

                                    <span wire:loading wire:target='tabValue("self_drive")'>
                                        Adding...
                                    </span>

                                </button>
                            </div>
                        @endif


                    </div>
                </div>
            </div>

    </section> --}}

    <section class="product-results-section font-poppins rounded-3xl">

        <div class="mx-auto max-w-7xl px-1 sm:px-3">

            
			
			
			
			
			
                <div class="product-trip-route">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="flex shrink-0 items-center">
                            <svg class="h-5 w-5 fill-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                <path d="M135.2 117.4L109.1 192h293.8l-26.1-74.6C372.3 104.6 360.2 96 346.6 96H165.4c-13.6 0-25.7 8.6-30.2 21.4zM39.6 196.8L74.8 96.3C88.3 57.8 124.6 32 165.4 32h181.2c40.8 0 77.1 25.8 90.6 64.3l35.2 100.5C495.6 206.4 512 229.3 512 256v192c0 17.7-14.3 32-32 32h-32c-17.7 0-32-14.3-32-32v-48H96v48c0 17.7-14.3 32-32 32H32c-17.7 0-32-14.3-32-32V256c0-26.7 16.4-49.6 39.6-59.2z" />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <h2 class="text-base font-semibold text-white lg:text-xl">Trip Packages For</h2>

                            <div class="mt-0.5 flex min-w-0 items-center gap-2 text-white">
                                <span class="truncate text-sm lg:text-base">{{ $ride->brand->name }}</span>

                                @if ($ride->ride_type === 'one_way')
                                    <span class="shrink-0 text-lg">→</span>
                                    <span class="truncate text-sm lg:text-base">{{ $cityTo->name }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <button
                        type="button"
                        wire:click="openEditTripModal"
                        title="Edit Trip"
                        aria-label="Edit Trip"
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/15 text-xl text-white transition hover:bg-white/25 md:h-auto md:w-auto md:gap-2 md:rounded-xl md:px-4 md:py-2 md:text-base md:font-semibold"
                    >
                        <span aria-hidden="true">✎</span>
                        <span class="hidden md:inline">Edit Trip</span>
                    </button>
                </div>

                <div class="product-trip-type">
                    <div class="flex shrink-0 items-center">
                        <svg class="h-5 w-5 fill-[#1e9cfd]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                            <path d="M64 32C28.7 32 0 60.7 0 96v320c0 35.3 28.7 64 64 64h320c35.3 0 64-28.7 64-64V96c0-35.3-28.7-64-64-64H64zm273 177L209 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L303 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z" />
                        </svg>
                    </div>

                    <div>
                        <h2 class="text-base font-semibold main-color-text lg:text-xl">Trip Type</h2>
                        <p class="uppercase main-color-text">
                            {{ $ride->ride_type === 'one_way' ? 'Oneway' : ucfirst(str_replace('_', ' ', $ride->ride_type)) }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="product-main-layout lg:flex flex-wrap mb-20 -mx-3">
                <aside class="product-sidebar w-full px-3 lg:w-1/4 lg:block">

                    {{-- <div class="product-help-card rides-side-card p-5 mb-5 bg-white">
                        <h2 class="text-2xl font-medium text-sky-500 dark:text-gray-400">Trip Details</h2>

                        @if ($ride->ride_type === 'one_way')
                            <div class="">
                                <div class="flex mt-3">
                                    <svg width="30" height="20" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                        <path
                                            d="M135.2 117.4L109.1 192l293.8 0-26.1-74.6C372.3 104.6 360.2 96 346.6 96L165.4 96c-13.6 0-25.7 8.6-30.2 21.4zM39.6 196.8L74.8 96.3C88.3 57.8 124.6 32 165.4 32l181.2 0c40.8 0 77.1 25.8 90.6 64.3l35.2 100.5c23.2 9.6 39.6 32.5 39.6 59.2l0 144 0 48c0 17.7-14.3 32-32 32l-32 0c-17.7 0-32-14.3-32-32l0-48L96 400l0 48c0 17.7-14.3 32-32 32l-32 0c-17.7 0-32-14.3-32-32l0-48L0 256c0-26.7 16.4-49.6 39.6-59.2zM128 288a32 32 0 1 0 -64 0 32 32 0 1 0 64 0zm288 32a32 32 0 1 0 0-64 32 32 0 1 0 0 64z" />
                                    </svg>
                                    &nbsp; <p>PickUp City : </p> &nbsp; &nbsp; <span>{{ $ride->brand->name }}</span>

                                </div>
                                <div class="flex mt-3">
                                    <svg width="30" height="20" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                        <path
                                            d="M135.2 117.4L109.1 192l293.8 0-26.1-74.6C372.3 104.6 360.2 96 346.6 96L165.4 96c-13.6 0-25.7 8.6-30.2 21.4zM39.6 196.8L74.8 96.3C88.3 57.8 124.6 32 165.4 32l181.2 0c40.8 0 77.1 25.8 90.6 64.3l35.2 100.5c23.2 9.6 39.6 32.5 39.6 59.2l0 144 0 48c0 17.7-14.3 32-32 32l-32 0c-17.7 0-32-14.3-32-32l0-48L96 400l0 48c0 17.7-14.3 32-32 32l-32 0c-17.7 0-32-14.3-32-32l0-48L0 256c0-26.7 16.4-49.6 39.6-59.2zM128 288a32 32 0 1 0 -64 0 32 32 0 1 0 64 0zm288 32a32 32 0 1 0 0-64 32 32 0 1 0 0 64z" />
                                    </svg>
                                    &nbsp; <p>Drop City : </p> &nbsp; &nbsp; <span>{{ $cityTo->name }}</span>

                                </div>
                            </div>
                        @else
                            <div class="flex mt-3">
                                <svg width="30" height="20" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                    <path
                                        d="M135.2 117.4L109.1 192l293.8 0-26.1-74.6C372.3 104.6 360.2 96 346.6 96L165.4 96c-13.6 0-25.7 8.6-30.2 21.4zM39.6 196.8L74.8 96.3C88.3 57.8 124.6 32 165.4 32l181.2 0c40.8 0 77.1 25.8 90.6 64.3l35.2 100.5c23.2 9.6 39.6 32.5 39.6 59.2l0 144 0 48c0 17.7-14.3 32-32 32l-32 0c-17.7 0-32-14.3-32-32l0-48L96 400l0 48c0 17.7-14.3 32-32 32l-32 0c-17.7 0-32-14.3-32-32l0-48L0 256c0-26.7 16.4-49.6 39.6-59.2zM128 288a32 32 0 1 0 -64 0 32 32 0 1 0 64 0zm288 32a32 32 0 1 0 0-64 32 32 0 1 0 0 64z" />
                                </svg>
                                &nbsp; <p>PickUp City : </p> &nbsp; &nbsp; <span>{{ $ride->brand->name }}</span>

                            </div>
                        @endif



                    </div> --}}


                    <div class="p-4 mb-5 bg-white border border-gray-200 dark:border-gray-900 dark:bg-gray-900">
                        <h2 class="text-xl font-medium text-sky-500 dark:text-gray-400"> Need Help Booking?</h2>
                        <div class="flex mt-3">
                            <p>Call Our Customer Care Executive. We Are Available 24×7 Just Dial.</p>

                        </div>
                        <div class="flex mt-3">
                            <div class="bg-sky-600 p-1 text-sky-300 rounded-lg">
                                <svg width="30" fill="white" height="20" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                    <path
                                        d="M164.9 24.6c-7.7-18.6-28-28.5-47.4-23.2l-88 24C12.1 30.2 0 46 0 64C0 311.4 200.6 512 448 512c18 0 33.8-12.1 38.6-29.5l24-88c5.3-19.4-4.6-39.7-23.2-47.4l-96-40c-16.3-6.8-35.2-2.1-46.3 11.6L304.7 368C234.3 334.7 177.3 277.7 144 207.3L193.3 167c13.7-11.2 18.4-30 11.6-46.3l-40-96z" />
                                </svg>
                            </div>
                            &nbsp; <a href="tel:+91-7088873331"
                                class="text-sky-700 font-bold text-xl">+91-7088873331</a>

                        </div>
                        <div class="flex mt-3">
                            <div class="bg-green-500 p-1 text-sky-300 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" fill="white" height="20"
                                    viewBox="0 0 448 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                    <path
                                        d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z" />
                                </svg>
                            </div>
                            &nbsp; &nbsp; <a href="tel:+91-7088873331"
                                class="text-green-500 font-bold text-xl">+91-7088873331</a>

                        </div>
                    </div>
                </aside>
                <main class="product-content w-full px-3 lg:w-3/4">
                    <div class="mb-4">
                        <div class="product-toolbar surface rides-toolbar flex items-center justify-between px-4 py-3">
                            @if ($ride->ride_type === 'self_drive')
                                <div>
                                    <p class="text-sm font-extrabold text-slate-900">
                                        {{ $selectedVehicle ? 'Linked Self Drive Vehicle' : 'Linked Vehicle Not Found' }}
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        {{ $selectedVehicle ? 'Select pickup and return date/time to check live availability.' : 'Admin me is SEO page ke saath vehicle link check karein.' }}
                                    </p>
                                </div>
                            @else
                                <div>
                                    <p class="text-sm font-extrabold text-slate-900">{{ count($prices) }} Cab Packages Available</p>
                                    <p class="text-xs text-slate-500">Ride page jaisa premium vehicle selection</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="product-package-list rides-results rides-premium-results grid items-center relative">
                        @if ($ride->ride_type === 'self_drive')
                            @if ($selectedVehicle)
                                @php
                                    $vehicleTitle = $selectedVehicle->display_name;
                                    $vehicleImage = $selectedVehicle->front_image_url ?: $imageMeta;
                                    $vehicleClass = $selectedVehicle->car_classification ?: 'Self Drive Car';
                                    $vehiclePrice = max(0, (float) $selectedVehicle->hourly_price);
                                    $vehicleDaily = max(0, (float) $selectedVehicle->daily_price);
                                    $vehicleSecurity = max(0, (float) $selectedVehicle->security_deposit);
                                    $vendorName = $selectedVehicle->transporter?->company_name ?: 'Dura Cabs Partner';
                                @endphp

                                <article wire:key="self-drive-vehicle-{{ $selectedVehicle->id }}" class="ride-package-card product-ride-card">
                                    <div class="ride-package-media">
                                        <span class="ride-package-badge ride-package-badge--green">Exact vehicle</span>
                                        @if ($vehicleImage)
                                            <img src="{{ $vehicleImage }}"
                                                alt="{{ $vehicleTitle }} self drive car"
                                                title="{{ $vehicleTitle }}"
                                                loading="eager">
                                        @else
                                            <div class="flex h-full min-h-40 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                                                <i class="fa-solid fa-car-side text-5xl"></i>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="ride-package-content">
                                        <h3>{{ $vehicleTitle }}</h3>

                                        <div class="ride-package-rating" aria-label="Verified self drive vehicle">
                                            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                                            <span>Linked Vehicle</span>
                                        </div>

                                        <p class="ride-package-model">
                                            {{ $selectedVehicle->vehicle_number ?: 'Registration available at pickup' }}
                                            • {{ $vehicleClass }}
                                        </p>

                                        <div class="ride-package-features">
                                            @if ($selectedVehicle->fuel_type)
                                                <span><i class="fa-solid fa-gas-pump"></i> {{ Str::headline($selectedVehicle->fuel_type) }}</span>
                                            @endif
                                            @if ($selectedVehicle->transmission)
                                                <span><i class="fa-solid fa-gears"></i> {{ Str::headline($selectedVehicle->transmission) }}</span>
                                            @endif
                                            @if ($selectedVehicle->seats)
                                                <span><i class="fa-solid fa-users"></i> {{ $selectedVehicle->seats }} Seats</span>
                                            @endif
                                            <span><i class="fa-solid fa-building"></i> {{ $vendorName }}</span>
                                            <span><i class="fa-solid fa-calendar-check"></i> Availability checked before checkout</span>
                                        </div>

                                        @if ($selectedVehicle->transporter?->pickup_address)
                                            <p class="mt-3 text-xs font-semibold text-slate-500">
                                                <i class="fa-solid fa-location-dot mr-1 text-emerald-600"></i>
                                                {{ $selectedVehicle->transporter->pickup_address }}
                                            </p>
                                        @endif
                                    </div>

                                    <div class="ride-package-price">
                                        @if ($fareUnlocked)
                                            <strong>
                                                {{ Number::currency($vehiclePrice, 'INR') }}
                                                <small>/ hour</small>
                                            </strong>

                                            @if ($vehicleDaily > 0)
                                                <span class="mt-1 block text-xs font-semibold text-slate-500">
                                                    {{ Number::currency($vehicleDaily, 'INR') }} / day
                                                </span>
                                            @endif

                                            @if ($vehicleSecurity > 0)
                                                <span class="mt-1 block text-xs font-semibold text-slate-500">
                                                    Refundable security: {{ Number::currency($vehicleSecurity, 'INR') }}
                                                </span>
                                            @endif

                                            <button type="button"
                                                onclick="showFareSummarySelfDrive('{{ addslashes($ride->name) }}', '{{ addslashes($vehicleTitle) }}', {{ $vehiclePrice }}, {{ $vehiclePrice }}, 1, '{{ $vehicleSecurity }}')"
                                                class="ride-fare-icon-button">
                                                <i class="fa-solid fa-circle-info"></i><span>Fare details</span>
                                            </button>
                                        @else
                                            <button type="button" wire:click="openFareGate" class="ride-price-lock" aria-label="Verify mobile to view exact fare">
                                                <span class="ride-price-lock-main">{{ Number::currency($vehiclePrice, 'INR') }}</span>
                                                <em><i class="fa-solid fa-lock"></i> Verify mobile to view fare</em>
                                            </button>

                                            <button type="button" wire:click="openFareGate" class="ride-fare-icon-button">
                                                <i class="fa-solid fa-lock"></i><span>Unlock fare</span>
                                            </button>
                                        @endif

                                        <button type="button"
                                            wire:click='tabValue(["self_drive","{{ $vehiclePrice }}","{{ addslashes($vehicleTitle) }}","{{ addslashes($vehicleClass) }}","{{ $vehicleSecurity }}"])'
                                            wire:loading.attr="disabled"
                                            class="ride-select-button">
                                            <span wire:loading.remove>Select Vehicle</span>
                                            <span wire:loading>Adding...</span>
                                            <i class="fa-solid fa-arrow-right"></i>
                                        </button>
                                    </div>
                                </article>
                            @else
                                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-center">
                                    <h3 class="text-lg font-black text-amber-900">Linked vehicle details not found</h3>
                                    <p class="mt-2 text-sm font-semibold text-amber-800">
                                        Is SEO page ka vehicle link missing ya deleted ho sakta hai. Admin me linked vehicle select karein.
                                    </p>
                                </div>
                            @endif
                        @else
                            @foreach ($prices as $price)
                                @php
                                    $badgeLabel = $loop->first ? 'Best price' : ($loop->iteration === 2 ? 'Popular' : 'Comfort');
                                    $badgeClass = $loop->first ? 'ride-package-badge--green' : ($loop->iteration === 2 ? 'ride-package-badge--blue' : 'ride-package-badge--purple');
                                @endphp

                                <article wire:key="product-price-{{ $price->id ?? $loop->index }}" class="ride-package-card product-ride-card">
                                    <div class="ride-package-media">
                                        <span class="ride-package-badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                                        <a href="/route/{{ $ride->slug }}" aria-label="View {{ $price->category->name }} details">
                                            <img src="{{ url('storage') }}/{{ $price->category->image }}"
                                                alt="{{ $price->category->name }}"
                                                title="{{ $ride->name }}"
                                                loading="lazy">
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

                                        <p class="ride-package-model">
                                            {{ $price->category->passanger_capacity }}
                                            {{ $price->category->model ?: $ride->name }} or similar
                                        </p>

                                        <div class="ride-package-features">
                                            <span><i class="fa-solid fa-bottle-water"></i> Water Bottle</span>
                                            <span><i class="fa-solid fa-bolt"></i> Instant Booking</span>
                                            <span><i class="fa-solid fa-user-shield"></i> Trusted Driver</span>
                                            <span><i class="fa-solid fa-snowflake"></i> AC</span>
                                        </div>
                                    </div>

                                    <div class="ride-package-price">
                                        @if ($fareUnlocked)
                                            @if ($price->max_price > $price->price)
                                                <del>{{ Number::currency($price->max_price, 'INR') }}</del>
                                            @endif
                                            <strong>{{ Number::currency($price->price, 'INR') }}</strong>
                                        @else
                                            <button type="button" wire:click="openFareGate" class="ride-price-lock" aria-label="Verify mobile to view exact fare">
                                                <span class="ride-price-lock-old">{{ Number::currency($price->max_price, 'INR') }}</span>
                                                <span class="ride-price-lock-main">{{ Number::currency($price->price, 'INR') }}</span>
                                                <em><i class="fa-solid fa-lock"></i> Verify mobile to view fare</em>
                                            </button>
                                        @endif

                                        @if ($fareUnlocked)
                                            @if ($ride->ride_type === 'one_way')
                                                <button type="button"
                                                    onclick="showFareSummaryOneWay('{{ addslashes($ride->name) }}', '{{ addslashes($price->category->name) }}', {{ $price->price }}, {{ $price->max_price }}, {{ $ride->toll_tax ?? 0 }}, {{ $ride->km_limit ?? 0 }}, {{ $ride->hr_limit ?? 0 }}, {{ $ride->extra_km_charge ?? 0 }}, {{ $ride->extra_hr_charge ?? 0 }})"
                                                    class="ride-fare-icon-button">
                                                    <i class="fa-solid fa-circle-info"></i><span>Fare details</span>
                                                </button>
                                            @else
                                                <button type="button"
                                                    onclick="showFareSummaryLocal('{{ addslashes($ride->name) }}', '{{ addslashes($price->category->name) }}', {{ $price->price }}, {{ $price->max_price }}, 1, 'Local Package', {{ $ride->extra_km_charge ?? 0 }}, {{ $ride->extra_hr_charge ?? 0 }}, {{ $ride->driver_allowances ?? 0 }})"
                                                    class="ride-fare-icon-button">
                                                    <i class="fa-solid fa-circle-info"></i><span>Fare details</span>
                                                </button>
                                            @endif
                                        @else
                                            <button type="button" wire:click="openFareGate" class="ride-fare-icon-button">
                                                <i class="fa-solid fa-lock"></i><span>Unlock fare</span>
                                            </button>
                                        @endif

                                        @if ($ride->ride_type === 'one_way')
                                            <button type="button"
                                                wire:click='tabValue(["one_way","{{ $price->price }}","{{ $ride->name }}","{{ $price->category->name }}", "{{ $ride->toll_tax }}","{{ $price->category->new_vehicle }}","{{ $price->category->pet_friendly }}","{{ $price->category->roof_career }}"])'
                                                wire:loading.attr="disabled"
                                                class="ride-select-button">
                                                <span wire:loading.remove>Select Vehicle</span>
                                                <span wire:loading>Adding...</span>
                                                <i class="fa-solid fa-arrow-right"></i>
                                            </button>
                                        @else
                                            <button type="button"
                                                wire:click='tabValue(["local","{{ $price->price }}","{{ $ride->name }}","{{ $price->category->name }}", "{{ $ride->toll_tax }}","{{ $price->category->new_vehicle }}","{{ $price->category->pet_friendly }}","{{ $price->category->roof_career }}"])'
                                                wire:loading.attr="disabled"
                                                class="ride-select-button">
                                                <span wire:loading.remove>Select Vehicle</span>
                                                <span wire:loading>Adding...</span>
                                                <i class="fa-solid fa-arrow-right"></i>
                                            </button>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        @endif
                    </div>
                    </main>
                    {{-- Pagination intentionally hidden on product detail page. --}}
                </div>
            </div>
        </div>
    </section>


    <style>
        /* Dynamic route content comes from the admin editor. These scoped rules
           make old HTML follow the same clean Tailwind-style design language. */
        .route-rich-content {
            color: #475569;
            font-size: 0.975rem;
            line-height: 1.8;
        }
        .route-rich-content > *:first-child { margin-top: 0 !important; }
        .route-rich-content h1,
        .route-rich-content h2,
        .route-rich-content h3,
        .route-rich-content h4,
        .route-rich-content h5,
        .route-rich-content h6 {
            display: flex !important;
            align-items: center !important;
            gap: .75rem !important;
            width: 100% !important;
            margin: 1.75rem 0 .85rem !important;
            padding: .9rem 1rem !important;
            border: 1px solid #bae6fd !important;
            border-radius: 1rem !important;
            background: linear-gradient(135deg, #f0f9ff 0%, #eff6ff 100%) !important;
            color: #0f172a !important;
            font-weight: 800 !important;
            line-height: 1.35 !important;
            box-shadow: 0 8px 24px rgba(14, 165, 233, .07) !important;
        }
        .route-rich-content h1::before,
        .route-rich-content h2::before,
        .route-rich-content h3::before,
        .route-rich-content h4::before,
        .route-rich-content h5::before,
        .route-rich-content h6::before {
            content: "";
            width: .55rem;
            height: .55rem;
            flex: 0 0 auto;
            border-radius: 9999px;
            background: #0284c7;
            box-shadow: 0 0 0 5px #e0f2fe;
        }
        .route-rich-content h1 { font-size: 1.5rem !important; }
        .route-rich-content h2 { font-size: 1.25rem !important; }
        .route-rich-content h3,
        .route-rich-content h4,
        .route-rich-content h5,
        .route-rich-content h6 { font-size: 1.05rem !important; }
        .route-rich-content p {
            margin: .7rem 0 !important;
            color: #475569 !important;
        }
        .route-rich-content strong,
        .route-rich-content b { color: #0f172a !important; font-weight: 750 !important; }
        .route-rich-content a { color: #0284c7 !important; font-weight: 700 !important; text-decoration: none !important; }
        .route-rich-content a:hover { color: #0369a1 !important; text-decoration: underline !important; }
        .route-rich-content ul,
        .route-rich-content ol {
            margin: .85rem 0 !important;
            padding: 1rem 1rem 1rem 2.7rem !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 1rem !important;
            background: #f8fafc !important;
        }
        .route-rich-content ul { list-style: disc !important; }
        .route-rich-content ol { list-style: decimal !important; }
        .route-rich-content li { margin: .4rem 0 !important; padding-left: .25rem !important; }
        .route-rich-content blockquote {
            margin: 1rem 0 !important;
            padding: 1rem 1.1rem !important;
            border: 1px solid #bae6fd !important;
            border-left: 4px solid #0ea5e9 !important;
            border-radius: 0 1rem 1rem 0 !important;
            background: #f0f9ff !important;
            color: #334155 !important;
        }
        .route-rich-content table {
            width: 100% !important;
            margin: 1.25rem 0 !important;
            border-collapse: separate !important;
            border-spacing: 0 !important;
            overflow: hidden !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 1rem !important;
            background: white !important;
        }
        .route-rich-content th { background: #0f172a !important; color: white !important; font-weight: 800 !important; }
        .route-rich-content th,
        .route-rich-content td { padding: .8rem 1rem !important; border-bottom: 1px solid #e2e8f0 !important; text-align: left !important; }
        .route-rich-content tr:last-child td { border-bottom: 0 !important; }
        .route-rich-content img {
            max-width: 100% !important;
            height: auto !important;
            margin: 1.25rem auto !important;
            border-radius: 1rem !important;
            box-shadow: 0 12px 30px rgba(15, 23, 42, .12) !important;
        }
        .route-rich-content hr { margin: 1.5rem 0 !important; border: 0 !important; border-top: 1px solid #e2e8f0 !important; }
        @media (max-width: 640px) {
            .route-rich-content { font-size: .925rem; line-height: 1.7; }
            .route-rich-content h1 { font-size: 1.25rem !important; }
            .route-rich-content h2 { font-size: 1.1rem !important; }
            .route-rich-content h1,
            .route-rich-content h2,
            .route-rich-content h3,
            .route-rich-content h4,
            .route-rich-content h5,
            .route-rich-content h6 { padding: .8rem .85rem !important; border-radius: .85rem !important; }
        }
    </style>

    {{-- Home-page inspired SEO and information content --}}
    <section class="bg-slate-50 py-10 sm:py-14">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">

            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="bg-gradient-to-r from-sky-600 via-blue-600 to-indigo-600 px-5 py-8 text-white sm:px-8">
                    <div class="max-w-3xl">
                        <span class="inline-flex rounded-full bg-white/15 px-3 py-1 text-xs font-bold uppercase tracking-[0.18em] ring-1 ring-white/25">
                            Simple booking process
                        </span>
                        <h2 class="mt-3 text-2xl font-extrabold sm:text-3xl">Book Your Cab in 4 Easy Steps</h2>
                        <p class="mt-2 text-sm leading-6 text-blue-50 sm:text-base">
                            Select your route, compare available cars, enter your details and confirm your booking securely.
                        </p>
                    </div>
                </div>

                <div class="grid gap-4 p-5 sm:grid-cols-2 sm:p-8 lg:grid-cols-4">
                    @php
                        $bookingSteps = [
                            ['01', 'Choose Destination', 'Select your pickup and drop location.'],
                            ['02', 'Choose Your Cab', 'Compare the available vehicle options.'],
                            ['03', 'Fill Your Details', 'Enter traveller and contact information.'],
                            ['04', 'Confirm Booking', 'Complete payment and receive confirmation.'],
                        ];
                    @endphp

                    @foreach ($bookingSteps as $step)
                        <div class="group rounded-2xl border border-slate-200 bg-slate-50 p-5 transition hover:-translate-y-1 hover:border-sky-200 hover:bg-white hover:shadow-lg">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-sky-100 text-sm font-black text-sky-700 ring-1 ring-sky-200">
                                {{ $step[0] }}
                            </div>
                            <h3 class="mt-4 text-base font-extrabold text-slate-900">{{ $step[1] }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $step[2] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="grid gap-8 lg:grid-cols-[minmax(0,1.7fr)_minmax(280px,0.8fr)]">
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
                    <div class="flex flex-wrap items-center gap-2 text-xs font-bold uppercase tracking-wider text-sky-700">
                        <span class="rounded-full bg-sky-50 px-3 py-1 ring-1 ring-sky-100">One Way Taxi</span>
                        <span class="text-slate-300">•</span>
                        <span>Dura Cabs</span>
                    </div>

                    <h2 class="mt-4 text-2xl font-black leading-tight text-slate-950 sm:text-3xl lg:text-4xl">
                        {{ $routeName }} {{ $tripLabel }} – Affordable Booking with Dura Cabs
                    </h2>

                    <div class="route-rich-content mt-7">
                        {!! str($ride->description)->sanitizeHtml() !!}
                    </div>

                    <div class="mt-8 rounded-2xl border border-sky-100 bg-sky-50/70 p-5">
                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-sky-600 shadow-sm ring-1 ring-sky-100">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-extrabold text-slate-900">Quick Route Facts</h2>
                                <p class="text-sm text-slate-600">Useful journey details for {{ $ride->name }}.</p>
                            </div>
                        </div>

                        <dl class="mt-5 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-xl bg-white p-4 ring-1 ring-slate-200">
                                <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Destination</dt>
                                <dd class="mt-1 font-bold text-slate-900">{{ $ride->name }}</dd>
                            </div>
                            <div class="rounded-xl bg-white p-4 ring-1 ring-slate-200">
                                <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Estimated route</dt>
                                <dd class="mt-1 font-bold text-slate-900">{{ $ride->hr_limit }} Hours · {{ $ride->km_limit }} KM</dd>
                            </div>
                        </dl>
                    </div>
                </article>

                <aside class="space-y-5">
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-extrabold text-slate-900">Why book with Dura Cabs?</h2>
                        <div class="mt-5 space-y-4">
                            @foreach ([
                                'Door-to-door pickup and drop',
                                'Experienced and verified chauffeurs',
                                'Transparent fare information',
                                'Flexible payment options',
                                'Customer support for your journey',
                            ] as $benefit)
                                <div class="flex gap-3">
                                    <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </span>
                                    <p class="text-sm font-medium leading-6 text-slate-700">{{ $benefit }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-3xl bg-slate-950 p-6 text-white shadow-xl">
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-sky-300">Need assistance?</p>
                        <h2 class="mt-2 text-xl font-extrabold">We are here to help you book.</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-300">Choose a cab above or contact the Dura Cabs team for booking support.</p>
                        <a href="tel:+917088873331" class="mt-5 inline-flex w-full items-center justify-center rounded-xl bg-sky-500 px-4 py-3 text-sm font-extrabold text-white transition hover:bg-sky-400">
                            Call +91 70888 73331
                        </a>
                    </div>
                </aside>
            </div>

            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-sky-600">Fare guide</p>
                        <h2 class="mt-2 text-2xl font-black text-slate-950">{{ $ride->name }} Cab Fare & Vehicle Options</h2>
                    </div>
                    <p class="max-w-xl text-sm leading-6 text-slate-600">Compare vehicle capacity and estimated fare before selecting your preferred cab.</p>
                </div>

                <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                            <thead class="bg-slate-950 text-white">
                                <tr>
                                    <th class="px-5 py-4 font-bold">Vehicle Type</th>
                                    <th class="px-5 py-4 font-bold">Model</th>
                                    <th class="px-5 py-4 font-bold">Passengers</th>
                                    <th class="px-5 py-4 font-bold">Luggage</th>
                                    <th class="px-5 py-4 font-bold">Rate/KM</th>
                                    <th class="px-5 py-4 font-bold">Estimated Fare</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach ($prices as $item)
                                    <tr class="transition hover:bg-sky-50/60">
                                        <td class="whitespace-nowrap px-5 py-4 font-bold text-slate-900">{{ $item->category->name }}</td>
                                        <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $item->category->model }}</td>
                                        <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $item->category->passanger_capacity }}</td>
                                        <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $item->category->luggage_capacity }} Bags</td>
                                        <td class="whitespace-nowrap px-5 py-4 font-semibold text-slate-700">{{ Number::currency($item->category->km_charge, 'INR') }}</td>
                                        <td class="whitespace-nowrap px-5 py-4 font-extrabold text-sky-700">{{ Number::currency($item->price, 'INR') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
                <div class="max-w-2xl">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-sky-600">Help centre</p>
                    <h2 class="mt-2 text-2xl font-black text-slate-950">Frequently Asked Questions</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Common questions about booking and travelling on this route.</p>
                </div>

                <div class="mt-6 divide-y divide-slate-200 rounded-2xl border border-slate-200 px-5">
                    @foreach ($faqs as $index => $faq)
                        <details class="group py-1">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 py-5 font-bold text-slate-900 outline-none focus-visible:ring-2 focus-visible:ring-sky-500">
                                <span>{{ $index + 1 }}. {{ $faq['question'] }}</span>
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition group-open:rotate-45 group-open:bg-sky-100 group-open:text-sky-700">+</span>
                            </summary>
                            <div class="pb-5 pr-10 text-sm leading-7 text-slate-600">
                                <p>{{ $faq['answer'] }}</p>
                            </div>
                        </details>
                    @endforeach
                </div>
            </section>

            @if ($links->count())
                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-sky-600">Explore more</p>
                        <h2 class="mt-2 text-2xl font-black text-slate-950">Popular One Way Routes from {{ $ride->brand->name }}</h2>
                    </div>

                    <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($links as $link)
                            <a wire:navigate href="{{ $link->url }}" class="group flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm font-bold text-slate-800 transition hover:-translate-y-0.5 hover:border-sky-200 hover:bg-white hover:text-sky-700 hover:shadow-md">
                                <span>{{ $link->title }}</span>
                                <svg class="h-4 w-4 transition group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </section>

    <!-- Enhanced Fare Summary Popup -->
    <div id="fareSummaryModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full mx-4 transform transition-all duration-300 scale-95">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-4 rounded-t-xl">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                        <h3 class="text-xl font-bold">Fare Breakdown</h3>
                    </div>
                    <button onclick="closeFareSummary()" class="text-white hover:text-gray-200 transition duration-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Content -->
            <div class="p-6 space-y-4">
                <!-- Vehicle Information -->
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-700 font-semibold flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
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
                    
                    <div id="driverAllowanceSection" class="flex justify-between items-center py-2 border-b border-gray-100">
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
                        <svg class="w-5 h-5 text-yellow-600 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        <div>
                            <h4 class="font-semibold text-yellow-800 mb-2">Important Information:</h4>
                            <div id="fareNotes" class="text-sm text-yellow-700">
                                Extra Charge After: <span id="extraKmLimit"></span> KMS. will be ₹<span id="extraKmRate"></span>/KM.<br>
                                There will be a night Allowance of ₹0 for the driver. after 8PM<br>
                                <strong>Toll-Tax:</strong> Excluded |
                                <strong>Parking:</strong> Extra (if applicable)
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Footer Note -->
                <div class="text-center text-sm text-gray-500 bg-gray-50 p-3 rounded-lg">
                    <div class="flex items-center justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Extra KM charges would be directly paid to the driver.
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="px-6 pb-6">
                <div class="flex space-x-3">
                    <button onclick="closeFareSummary()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-3 px-4 rounded-lg font-semibold transition duration-200 flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Close
                    </button>
                    {{-- <button onclick="window.print()" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3 px-4 rounded-lg font-semibold transition duration-200 flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H3a2 2 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H7a2 2 0 00-2 2v4a2 2 0 002 2z"></path>
                        </svg>
                        Print
                    </button> --}}
                </div>
            </div>
        </div>
    </div>

    <script>
        function showFareSummaryOneWay(rideName, categoryName, price, maxPrice, tollTax, kmLimit, hrLimit, extra_km_charge, extra_hr_charge) {
            const modal = document.getElementById('fareSummaryModal');
            if (!modal) return;
            
            // Calculate GST on total amount (add GST on top, don't deduct)
            const baseFare = price;
            const gstAmount = (price * 5) / 100;
            const finalTotal = price + gstAmount;
            
            // Update popup content for one way
            document.getElementById('carCategory').textContent = categoryName + ' Or Equivalent';
            document.getElementById('baseFare').textContent = '₹ ' + Math.round(baseFare);
            document.getElementById('gstAmount').textContent = '₹ ' + Math.round(gstAmount);
            document.getElementById('totalPrice').textContent = '₹ ' + Math.round(finalTotal);
            
            // Hide driver allowance for one way
            document.getElementById('driverAllowanceSection').style.display = 'none';
            
            // Show toll tax section
            document.getElementById('tollTaxSection').style.display = 'block';
            document.getElementById('tollTaxStatus').textContent = tollTax > 0 ? 'Included' : 'Excluded';
            
            // Update notes for one way
            document.getElementById('fareNotes').innerHTML = 
                `Extra Charge After: <span id="extraKmLimit">${kmLimit}</span> KMS. will be ₹<span id="extraKmRate">${extra_km_charge}.00</span>/KM.<br>
                Extra Charge After: <span id="extraHrLimit">${hrLimit}</span> HRS. will be ₹<span id="extraHrRate">${extra_hr_charge}.00</span>/HR.<br>
                <strong>Toll-Tax:</strong> ${tollTax > 0 ? 'Included' : 'Excluded'} |
                <strong>Parking:</strong> Extra (if applicable)`;
            
            // Show modal with animation
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.querySelector('.transform').classList.remove('scale-95');
                modal.querySelector('.transform').classList.add('scale-100');
            }, 10);
        }
        
        function showFareSummaryLocal(rideName, categoryName, price, maxPrice, cars, plan, extra_km_charge, extra_hr_charge, driver_allowances) {
            const modal = document.getElementById('fareSummaryModal');
            if (!modal) return;
            
            // Calculate GST
            const gstAmount = (price * 5) / 100;
            const baseFare = price;
            const finalTotal = price + gstAmount;
            
            // Update popup content for local
            document.getElementById('carCategory').textContent = categoryName + ' Or Equivalent';
            document.getElementById('baseFare').textContent = '₹ ' + Math.round(baseFare);
            document.getElementById('gstAmount').textContent = '₹ ' + Math.round(gstAmount);
            document.getElementById('totalPrice').textContent = '₹ ' + Math.round(finalTotal);
            
            // Show driver allowance for local
            document.getElementById('driverAllowanceSection').style.display = 'block';
            document.getElementById('driverAllowance').textContent = 'Included';
            
            // Show toll tax section
            document.getElementById('tollTaxSection').style.display = 'block';
            document.getElementById('tollTaxStatus').textContent = 'Excluded';
            
            // Update notes for local
            document.getElementById('fareNotes').innerHTML = 
                `Package: ${plan}<br>
                Extra KM Charge: ₹${extra_km_charge}.00/KM<br>
                Extra HR Charge: ₹${extra_hr_charge}.00/HR<br>
                <strong>Toll-Tax:</strong> Excluded |
                <strong>Parking:</strong> Extra (if applicable)`;
            
            // Show modal with animation
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.querySelector('.transform').classList.remove('scale-95');
                modal.querySelector('.transform').classList.add('scale-100');
            }, 10);
        }
        
        function showFareSummarySelfDrive(rideName, categoryName, price, maxPrice, days, security) {
            const modal = document.getElementById('fareSummaryModal');
            if (!modal) return;
            
            // Calculate GST
            const gstAmount = (price * 5) / 100;
            const baseFare = price;
            const finalTotal = price + gstAmount;
            
            // Update popup content for self drive
            document.getElementById('carCategory').textContent = categoryName + ' Or Equivalent';
            document.getElementById('baseFare').textContent = '₹ ' + Math.round(baseFare);
            document.getElementById('gstAmount').textContent = '₹ ' + Math.round(gstAmount);
            document.getElementById('totalPrice').textContent = '₹ ' + Math.round(finalTotal);
            
            // Hide driver allowance for self drive
            document.getElementById('driverAllowanceSection').style.display = 'none';
            
            // Show toll tax section
            document.getElementById('tollTaxSection').style.display = 'block';
            document.getElementById('tollTaxStatus').textContent = 'Excluded';
            
            // Update notes for self drive
            document.getElementById('fareNotes').innerHTML = 
                `Self Drive Package: Per Hour Rate<br>
                Security Deposit: ₹${security}<br>
                <strong>Fuel:</strong> Extra |
                <strong>Toll-Tax:</strong> Excluded |
                <strong>Parking:</strong> Extra (if applicable)`;
            
            // Show modal with animation
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.querySelector('.transform').classList.remove('scale-95');
                modal.querySelector('.transform').classList.add('scale-100');
            }, 10);
        }
        
        function closeFareSummary() {
            const modal = document.getElementById('fareSummaryModal');
            if (!modal) return;
            
            // Add closing animation
            modal.querySelector('.transform').classList.remove('scale-100');
            modal.querySelector('.transform').classList.add('scale-95');
            
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.style.display = '';
            }, 200);
        }
        
        // Initialize modal event listeners
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('fareSummaryModal');
            if (!modal) return;
            
            // Close modal when clicking outside
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeFareSummary();
                }
            });
            
            // Close modal with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const modal = document.getElementById('fareSummaryModal');
                    if (modal && !modal.classList.contains('hidden')) {
                        closeFareSummary();
                    }
                }
            });
        });
    </script>
	
	 <style>
        .product-ride-theme{--ride-blue:#0969da;--ride-sky:#0ea5e9;--ride-ink:#0f172a;--ride-muted:#64748b;--ride-line:#e2e8f0;background:linear-gradient(180deg,#f8fbff 0%,#fff 36%,#f8fafc 100%)}
        .product-ride-theme .ride-shell{background:transparent}
        .product-breadcrumb{padding:0 .35rem}
        .product-premium-header{border:1px solid rgba(148,163,184,.22);border-radius:24px;padding:22px;background:linear-gradient(135deg,#ffffff 0%,#f0f8ff 62%,#e8f4ff 100%);box-shadow:0 18px 45px rgba(15,23,42,.08)}
        .product-premium-header h1{letter-spacing:-.03em}.product-premium-header p{max-width:58rem}
        .product-trip-summary{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:18px;align-items:center;padding:18px 20px;border:1px solid rgba(148,163,184,.22);border-radius:22px;background:#fff;box-shadow:0 14px 36px rgba(15,23,42,.07)}
        .product-trip-route{display:flex;align-items:center;justify-content:space-between;gap:18px;min-width:0}.product-trip-route>div:first-child{display:flex;align-items:center;gap:14px;min-width:0}.product-trip-route>div:first-child>div:first-child{width:44px;height:44px;border-radius:15px;background:linear-gradient(135deg,#0ea5e9,#2563eb);display:flex;align-items:center;justify-content:center;box-shadow:0 10px 24px rgba(37,99,235,.24)}
        .product-trip-route h2,.product-trip-route span{color:#0f172a!important}.product-trip-route button{background:#eff6ff!important;color:#0969da!important;border:1px solid #bfdbfe}.product-trip-route button span{color:#0969da!important}
        .product-trip-type{display:flex;align-items:center;gap:12px;min-width:210px;border-left:1px solid #e2e8f0;padding-left:18px}.product-trip-type>div:first-child{width:42px;height:42px;border-radius:14px;background:#ecfdf5;display:flex;align-items:center;justify-content:center}.product-trip-type svg{fill:#16a34a!important}.product-trip-type h2,.product-trip-type p{color:#0f172a!important}
        .product-sidebar .product-help-card{border:1px solid #e2e8f0;border-radius:20px;box-shadow:0 12px 30px rgba(15,23,42,.06);position:sticky;top:90px}.product-help-card h2{font-weight:800;color:#0f172a!important}.product-help-card p{color:#64748b;line-height:1.6}.product-help-card a{font-size:1rem}
        .product-toolbar{border:1px solid #e2e8f0;border-radius:18px;background:#fff;box-shadow:0 10px 28px rgba(15,23,42,.05)}
        .product-package-list{display:grid;gap:16px}
        .product-ride-card{display:grid;grid-template-columns:220px minmax(0,1fr) 210px;overflow:hidden;border:1px solid #dbe7f3;border-radius:22px;background:#fff;box-shadow:0 12px 34px rgba(15,23,42,.07);transition:.22s ease}
        .product-ride-card:hover{transform:translateY(-3px);border-color:#93c5fd;box-shadow:0 20px 44px rgba(37,99,235,.13)}
        .product-ride-card .ride-package-media{position:relative;display:flex;align-items:center;justify-content:center;min-height:205px;padding:22px;background:linear-gradient(145deg,#f8fafc,#eff6ff);border-right:1px solid #edf2f7}
        .product-ride-card .ride-package-media a{display:flex;width:100%;height:100%;align-items:center;justify-content:center}
        .product-ride-card .ride-package-media img{width:100%;height:155px;object-fit:contain;filter:drop-shadow(0 12px 16px rgba(15,23,42,.16))}
        .product-ride-card .ride-package-badge{position:absolute;left:14px;top:14px;z-index:2;border-radius:999px;padding:6px 10px;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.04em}
        .ride-package-badge--green{background:#dcfce7;color:#15803d}.ride-package-badge--blue{background:#dbeafe;color:#1d4ed8}.ride-package-badge--purple{background:#f3e8ff;color:#7e22ce}
        .product-ride-card .ride-package-content{display:flex;min-width:0;flex-direction:column;justify-content:center;padding:22px 24px}
        .product-ride-card .ride-package-content h3{font-size:1.25rem;font-weight:850;line-height:1.25;color:#0f172a}
        .product-ride-card .ride-package-rating{display:flex;align-items:center;gap:3px;margin-top:8px;color:#facc15;font-size:14px}.product-ride-card .ride-package-rating span{margin-left:6px;color:#64748b;font-size:12px;font-weight:700}
        .product-ride-card .ride-package-model{margin-top:8px;color:#64748b;font-size:13px;line-height:1.5}
        .product-ride-card .ride-package-features{display:flex;flex-wrap:wrap;gap:8px;margin-top:16px}.product-ride-card .ride-package-features span{display:inline-flex;align-items:center;gap:6px;border:1px solid #e2e8f0;border-radius:999px;background:#f8fafc;padding:7px 10px;color:#334155;font-size:11px;font-weight:700}.product-ride-card .ride-package-features i{color:#0ea5e9}
        .product-ride-card .ride-package-price{display:flex;flex-direction:column;align-items:stretch;justify-content:center;gap:9px;padding:20px;border-left:1px solid #edf2f7;background:linear-gradient(180deg,#fff,#f8fbff)}
        .product-ride-card .ride-package-price del{color:#94a3b8;text-align:right;font-size:14px}.product-ride-card .ride-package-price>strong{color:#0f172a;text-align:right;font-size:1.45rem;line-height:1.1}.product-ride-card .ride-package-price strong small{font-size:11px;color:#64748b}
        .product-ride-card .ride-fare-icon-button{display:flex;min-height:40px;align-items:center;justify-content:center;gap:7px;border:1px solid #bfdbfe;border-radius:12px;background:#eff6ff;color:#1d4ed8;font-size:12px;font-weight:800}.product-ride-card .ride-fare-icon-button:hover{background:#dbeafe}
        .product-ride-card .ride-select-button{display:flex;min-height:46px;align-items:center;justify-content:center;gap:8px;border:0;border-radius:13px;background:linear-gradient(135deg,#2563eb,#0284c7);padding:12px 14px;color:#fff;font-size:13px;font-weight:850;box-shadow:0 10px 22px rgba(37,99,235,.2)}.product-ride-card .ride-select-button:hover{background:linear-gradient(135deg,#1d4ed8,#0369a1)}
        .ride-price-lock{position:relative;overflow:hidden;border:1px dashed #bfdbfe;border-radius:13px;background:#f8fbff;padding:10px;text-align:center}.ride-price-lock-old,.ride-price-lock-main{display:block;filter:blur(5px);user-select:none}.ride-price-lock-old{color:#94a3b8;text-decoration:line-through;font-size:12px}.ride-price-lock-main{color:#0f172a;font-size:20px;font-weight:850}.ride-price-lock em{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;gap:6px;color:#0369a1;font-size:11px;font-style:normal;font-weight:800}
        .product-results-section+section,.product-ride-theme section.font-poppins{border-radius:22px}.product-ride-theme details{border-color:#e2e8f0}.product-ride-theme h2{letter-spacing:-.02em}
        @media(max-width:1023px){.product-sidebar{display:none}.product-content{width:100%}.product-trip-summary{grid-template-columns:1fr}.product-trip-type{border-left:0;border-top:1px solid #e2e8f0;padding:14px 0 0;min-width:0}.product-ride-card{grid-template-columns:190px minmax(0,1fr) 190px}}
        @media(max-width:700px){.product-ride-theme{padding-left:.5rem!important;padding-right:.5rem!important}.product-premium-header{padding:17px;border-radius:20px}.product-premium-header h1{font-size:1.45rem}.product-trip-summary{padding:14px;border-radius:18px}.product-trip-route{align-items:flex-start}.product-trip-route button{width:42px;height:42px;padding:0!important}.product-trip-route button .hidden{display:none!important}.product-ride-card{grid-template-columns:1fr}.product-ride-card .ride-package-media{min-height:165px;border-right:0;border-bottom:1px solid #eef2f7}.product-ride-card .ride-package-media img{height:130px}.product-ride-card .ride-package-content{padding:17px}.product-ride-card .ride-package-price{border-left:0;border-top:1px solid #edf2f7;padding:16px}.product-ride-card .ride-package-price del,.product-ride-card .ride-package-price>strong{text-align:left}.product-toolbar{display:flex!important}.product-breadcrumb{font-size:.75rem}}
    </style>
    <script>
        function showFareSummaryOneWay(rideName, categoryName, price, maxPrice, tollTax, kmLimit, hrLimit, extra_km_charge, extra_hr_charge) {
            const modal = document.getElementById('fareSummaryModal');
            if (!modal) return;
            
            // Calculate GST on total amount (add GST on top, don't deduct)
            const baseFare = price;
            const gstAmount = (price * 5) / 100;
            const finalTotal = price + gstAmount;
            
            // Update popup content for one way
            document.getElementById('carCategory').textContent = categoryName + ' Or Equivalent';
            document.getElementById('baseFare').textContent = '₹ ' + Math.round(baseFare);
            document.getElementById('gstAmount').textContent = '₹ ' + Math.round(gstAmount);
            document.getElementById('totalPrice').textContent = '₹ ' + Math.round(finalTotal);
            
            // Hide driver allowance for one way
            document.getElementById('driverAllowanceSection').style.display = 'none';
            
            // Show toll tax section
            document.getElementById('tollTaxSection').style.display = 'block';
            document.getElementById('tollTaxStatus').textContent = tollTax > 0 ? 'Included' : 'Excluded';
            
            // Update notes for one way
            document.getElementById('fareNotes').innerHTML = 
                `Extra Charge After: <span id="extraKmLimit">${kmLimit}</span> KMS. will be ₹<span id="extraKmRate">${extra_km_charge}.00</span>/KM.<br>
                Extra Charge After: <span id="extraHrLimit">${hrLimit}</span> HRS. will be ₹<span id="extraHrRate">${extra_hr_charge}.00</span>/HR.<br>
                <strong>Toll-Tax:</strong> ${tollTax > 0 ? 'Included' : 'Excluded'} |
                <strong>Parking:</strong> Extra (if applicable)`;
            
            // Show modal with animation
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.querySelector('.transform').classList.remove('scale-95');
                modal.querySelector('.transform').classList.add('scale-100');
            }, 10);
        }
        
        function showFareSummaryLocal(rideName, categoryName, price, maxPrice, cars, plan, extra_km_charge, extra_hr_charge, driver_allowances) {
            const modal = document.getElementById('fareSummaryModal');
            if (!modal) return;
            
            // Calculate GST
            const gstAmount = (price * 5) / 100;
            const baseFare = price;
            const finalTotal = price + gstAmount;
            
            // Update popup content for local
            document.getElementById('carCategory').textContent = categoryName + ' Or Equivalent';
            document.getElementById('baseFare').textContent = '₹ ' + Math.round(baseFare);
            document.getElementById('gstAmount').textContent = '₹ ' + Math.round(gstAmount);
            document.getElementById('totalPrice').textContent = '₹ ' + Math.round(finalTotal);
            
            // Show driver allowance for local
            document.getElementById('driverAllowanceSection').style.display = 'block';
            document.getElementById('driverAllowance').textContent = 'Included';
            
            // Show toll tax section
            document.getElementById('tollTaxSection').style.display = 'block';
            document.getElementById('tollTaxStatus').textContent = 'Excluded';
            
            // Update notes for local
            document.getElementById('fareNotes').innerHTML = 
                `Package: ${plan}<br>
                Extra KM Charge: ₹${extra_km_charge}.00/KM<br>
                Extra HR Charge: ₹${extra_hr_charge}.00/HR<br>
                <strong>Toll-Tax:</strong> Excluded |
                <strong>Parking:</strong> Extra (if applicable)`;
            
            // Show modal with animation
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.querySelector('.transform').classList.remove('scale-95');
                modal.querySelector('.transform').classList.add('scale-100');
            }, 10);
        }
        
        function showFareSummarySelfDrive(rideName, categoryName, price, maxPrice, days, security) {
            const modal = document.getElementById('fareSummaryModal');
            if (!modal) return;
            
            // Calculate GST
            const gstAmount = (price * 5) / 100;
            const baseFare = price;
            const finalTotal = price + gstAmount;
            
            // Update popup content for self drive
            document.getElementById('carCategory').textContent = categoryName + ' Or Equivalent';
            document.getElementById('baseFare').textContent = '₹ ' + Math.round(baseFare);
            document.getElementById('gstAmount').textContent = '₹ ' + Math.round(gstAmount);
            document.getElementById('totalPrice').textContent = '₹ ' + Math.round(finalTotal);
            
            // Hide driver allowance for self drive
            document.getElementById('driverAllowanceSection').style.display = 'none';
            
            // Show toll tax section
            document.getElementById('tollTaxSection').style.display = 'block';
            document.getElementById('tollTaxStatus').textContent = 'Excluded';
            
            // Update notes for self drive
            document.getElementById('fareNotes').innerHTML = 
                `Self Drive Package: Per Hour Rate<br>
                Security Deposit: ₹${security}<br>
                <strong>Fuel:</strong> Extra |
                <strong>Toll-Tax:</strong> Excluded |
                <strong>Parking:</strong> Extra (if applicable)`;
            
            // Show modal with animation
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.querySelector('.transform').classList.remove('scale-95');
                modal.querySelector('.transform').classList.add('scale-100');
            }, 10);
        }
        
        function closeFareSummary() {
            const modal = document.getElementById('fareSummaryModal');
            if (!modal) return;
            
            // Add closing animation
            modal.querySelector('.transform').classList.remove('scale-100');
            modal.querySelector('.transform').classList.add('scale-95');
            
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.style.display = '';
            }, 200);
        }
        
        // Initialize modal event listeners
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('fareSummaryModal');
            if (!modal) return;
            
            // Close modal when clicking outside
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeFareSummary();
                }
            });
            
            // Close modal with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const modal = document.getElementById('fareSummaryModal');
                    if (modal && !modal.classList.contains('hidden')) {
                        closeFareSummary();
                    }
                }
            });
        });
    </script>
</div>
</div>