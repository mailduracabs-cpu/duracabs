<div class="background-options ">
    @section('title', 'Premium Cab & Taxi Service in India| Book 24/7 | One Way Cab')
    @section('description',
    'Book safe & cheap cabs across India with Duracabs. 24/7 online taxi service for airport
    drop services and more. Trusted by thousands of happy customers!')
    @section('image', 'https://www.duracabs.com/img/logo/duracabs_logo.jpeg')
    {{-- Hero Section Start --}}
    <div class="w-full from-blue-200 to-cyan-200 py-5 px-4 sm:px-6 lg:px-8 m-auto" style='background: linear-gradient(180deg,rgb(30, 156, 253) 0%, rgb(255, 255, 255) 98%);'>
        <div class="max-w-[85rem] mx-auto px-0 sm:px-6 lg:px-8">
            <!-- Grid -->
            <div class="grid md:grid-cols-1 gap-0 md:gap-8 xl:gap-20 md:items-center">
                <div>
                    <h1
                        class="lg:block  text-center text-xm mt-9 hidden font-bold text-white sm:text-xl lg:text-4xl lg:leading-tight dark:text-white ">
                        Book Outstation Taxi, One Way Cab, Car Rentals Online - <span class="text-white">Duracabs</span>
                    </h1>
                    <ul
                        class="flex form-margine-negative relative text-sm font-medium text-center text-gray-500 rounded-lg shadow  dark:divide-gray-700 dark:text-gray-400 lg:mt-10 lg:mx-48">
                        <li class="w-1/2 ">

                            <a href="#" wire:click='changeTab("one_way")'
                                class="lg:flex grid grid-rows-1 gap-0 place-items-center  text-center items-center justify-center  w-full lg:p-4 p-1 text-gray-900 bg-gray-100 border-r border-gray-200 dark:border-gray-700 rounded-s-lg active dark:bg-gray-700 dark:text-white"
                                aria-current="page">
                                <div class="flex justify-center">
                                    <img src="/cab_images/one_way.webp"
                                        alt="One Way Taxi Service in India by Duracabs - Affordable Intercity Travel"
                                        title="Book One Way Taxi Service with Duracabs for Affordable Intercity Travel"
                                        class="{{ $selected_tab === 'one_way' ? 'grayscale-0' : 'grayscale' }} mr-2 lg:w-12 w-8" />
                                </div>

                                <h2
                                    class="font-bold lg:text-sm text-xs uppercase {{ $selected_tab === 'one_way' ? 'main-color-text font-extrabold' : '' }} ">
                                    One way cab</h2>

                            </a>
                        </li>
                        <li class="w-1/2 ">
                            <a href="#" wire:click='changeTab("return")'
                                class="lg:flex grid grid-rows-1 gap-0 h-full place-items-center  text-center items-center justify-center  w-full lg:p-4 p-1 text-gray-900 bg-gray-100 border-r border-gray-200 dark:border-gray-700 active dark:bg-gray-700 dark:text-white">

                                <div class="flex justify-center">
                                    <img src="/cab_images/return.webp"
                                        alt="Return Taxi Service by Duracabs – Convenient and Affordable Round Trip Travel"
                                        title="Book Return Taxi Service with Duracabs for Convenient Round Trip Travel"
                                        class="{{ $selected_tab === 'return' ? 'grayscale-0' : 'grayscale' }} mr-2 lg:w-12 w-8" />
                                </div>

                                <h2
                                    class="font-bold lg:text-sm text-xs uppercase {{ $selected_tab === 'return' ? 'main-color-text font-extrabold' : '' }} ">
                                    Round trip</h2>
                            </a>
                        </li>
                        <li class="w-1/2 ">
                            <a href="#" wire:click='changeTab("local")'
                                class="lg:flex grid grid-rows-1 gap-0 place-items-center  h-full text-center items-center justify-center  w-full lg:p-4 p-1 text-gray-900 bg-gray-100 border-r border-gray-200 dark:border-gray-700  active  dark:bg-gray-700 dark:text-white">

                                <div class="flex justify-center">
                                    <img src="/cab_images/local.webp"
                                        alt="Local Taxi Service in India by Duracabs – Convenient City Travel Options"
                                        title="Book Local Taxi Service with Duracabs for Easy and Convenient City Travel"
                                        class="{{ $selected_tab === 'local' ? 'grayscale-0' : 'grayscale' }} mr-2 lg:w-12 w-8" />
                                </div>

                                <h2
                                    class="font-bold lg:text-sm text-xs uppercase {{ $selected_tab === 'local' ? 'main-color-text font-extrabold' : '' }} ">
                                    Local Taxi</h2>

                            </a>
                        </li>
                        <li class="w-1/2 ">
                            <a href="#" wire:click='changeTab("self_drive")'
                                class="lg:flex grid grid-rows-1 gap-0 place-items-center  text-center items-center justify-center  w-full lg:p-4 p-1 text-gray-900 bg-gray-100 border-r border-gray-200 dark:border-gray-700 rounded-e-lg  active  dark:bg-gray-700 dark:text-white">

                                <div class="flex justify-center">
                                    <img src="/cab_images/self_drive.webp"
                                        alt="Self-Drive Car Rental Service by Duracabs – Enjoy the Freedom to Drive Your Own Way"
                                        title="Book Self-Drive Car Rental with Duracabs for a Flexible and Convenient Travel Experience"
                                        class="{{ $selected_tab === 'self_drive' ? 'grayscale-0' : 'grayscale' }} mr-2 lg:w-12 w-8" />
                                </div>

                                <h2
                                    class="font-bold lg:text-sm text-xs uppercase {{ $selected_tab === 'self_drive' ? 'main-color-text font-extrabold' : '' }} ">
                                    Self-drive car</h2>
                            </a>
                        </li>
                    </ul>

                    <div class="fixed {{ $sendOtp ? '' : 'hidden' }}  z-50 inset-0 bg-gray-900 bg-opacity-60 overflow-y-auto h-full w-full px-4 ">
                        <div class="relative top-1 mx-auto shadow-xl rounded-md bg-white max-w-md">
                            <span class="absolute top-0 right-0 p-3 cursor-pointer" wire:click="closeModal()">X</span>
                            <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-xl shadow-gray-100/80">
                                <!-- Header -->
                                <div class="mb-8 text-center">
                                    <div class="mx-auto mb-4 rounded-2xl flex items-center justify-center">
                                        <img class="w-96" src="{{ asset('./img/loginimg.jpg') }}" alt="login" />
                                    </div>
                                    <h2 class="text-2xl font-semibold tracking-tight">Sign in with OTP</h2>
                                    <p class="mt-1 text-sm text-gray-600">No password required — quick & secure.</p>
                                </div>
                                <form wire:submit="sendOtpToBack" autocomplete="off">
                                    <div class="bg-white px-4 pb-4 sm:p-6 sm:pb-4">
                                        <div class="flex">
                                            <div class="mt-3 w-full sm:ml-4 sm:mt-0 text-left">
                                                <h3 class="text-base font-semibold leading-6 text-gray-900"
                                                    id="modal-title">Enter mobile number for OTP</h3>
                                                <div class="mt-2 w-full">
                                                    <input type="number" wire:model.live='mobileNumber' maxlength="14"
                                                        placeholder="Ex: 7088873331" inputmode="numeric"
                                                        class="mt-3 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full ps-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                                                    <p class="mt-2 text-red-900 text-sm">
                                                        {{-- {{ strlen($mobileNumber) == 10 ? '' : 'Enter 10-digit mobile number' }} --}}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                        <button {{ strlen($mobileNumber) == 10 ? '' : 'disabled' }} type="submit"
                                            class="inline-flex cursor-pointer w-full justify-center rounded-md {{ strlen($mobileNumber) == 10 ? 'bg-green-600' : 'bg-gray-600' }}  px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">Send OTP</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="fixed {{ $sendOtpVerify ? '' : 'hidden' }} z-50 inset-0 bg-gray-900 bg-opacity-60 overflow-y-auto h-full w-full px-4 ">
                        <div class="relative top-1 mx-auto shadow-xl rounded-md bg-white max-w-md">
                            <span class="absolute top-0 right-0 p-3 cursor-pointer" wire:click="closeModal()">X</span>
                            <div
                                class="rounded-2xl border border-gray-200 bg-white p-8 shadow-xl shadow-gray-100/80">
                                <!-- Header -->
                                <div class="mb-8 text-center">
                                    <div class="mx-auto rounded-2xl flex items-center justify-center">
                                        <img class="w-96" src="{{ asset('./img/passwordimg.jpg') }}" alt="otp verification" />
                                    </div>
                                    <h2 class="text-2xl font-semibold tracking-tight">Verification by OTP</h2>
                                </div>

                                <form autocomplete="off">
                                    <div class="bg-white px-4 pb-4 sm:px-6 sm:pb-4">
                                        <div class="flex">

                                            <div class="w-full text-center sm:ml-4 sm:mt-0 sm:text-left">
                                                <div class="flex justify-between mb-4">
                                                    <h3 class="text-base font-semibold leading-6 text-gray-900"
                                                        id="modal-title">Please enter OTP</h3>
                                                    <button type="button" wire:click='resendOtp'>Resend OTP</button>
                                                </div>
                                                <div class="my-4 w-full">
                                                    <div class="flex justify-center items-center">
                                                        <input type="password" maxlength="1" wire:model.live="digit1" inputmode="numeric"
                                                            class="passwordHome w-12 h-12 mx-4 text-center text-xl font-bold border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-400 outline-none transition"
                                                            autofocus>
                                                        <input type="password" maxlength="1" wire:model.live="digit2" inputmode="numeric"
                                                            class="passwordHome w-12 h-12 mx-4 text-center text-xl font-bold border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-400 outline-none transition">
                                                        <input type="password" maxlength="1" wire:model.live="digit3" inputmode="numeric"
                                                            class="passwordHome w-12 h-12 mx-4 text-center text-xl font-bold border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-400 outline-none transition">
                                                        <input type="password" maxlength="1" wire:model.live="digit4" inputmode="numeric"
                                                            class="passwordHome w-12 h-12 mx-4 text-center text-xl font-bold border-2 border-gray-300 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-400 outline-none transition">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>


                                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                        @if ($selected_tab == 'one_way')
                                        <button type="button" wire:click='verifySubmitOtp'
                                            class="inline-flex cursor-pointer w-full justify-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">Verify
                                            OTP
                                        </button>
                                        @endif
                                        @if ($selected_tab == 'self_drive')
                                        <button type="button" wire:click='verifySubmitOtpSelfDrive'
                                            class="inline-flex cursor-pointer w-full justify-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">Verify
                                            OTP
                                        </button>
                                        @endif
                                        @if ($selected_tab == 'return')
                                        <button type="button" wire:click='verifySubmitOtpReturn'
                                            class="inline-flex cursor-pointer w-full justify-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">Verify
                                            OTP
                                        </button>
                                        @endif

                                        @if ($selected_tab == 'local')
                                        <button type="button" wire:click='verifySubmitLocal'
                                            class="inline-flex cursor-pointer w-full justify-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">Verify
                                            OTP
                                        </button>
                                        @endif

                                        <button type="button" wire:click='backButton'
                                            class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Back</button>
                                    </div>

                                </form>
                            </div>
                        </div>
                    </div>

                    <script>
                        document.addEventListener("DOMContentLoaded", () => {
                            let inputs = document.querySelectorAll(".passwordHome");
                            inputs.forEach((input, index) => {
                                input.addEventListener("input", () => {
                                    if (input.value.length === 1 && index < inputs.length - 1) {
                                        inputs[index + 1].focus();
                                    }
                                });
                            });
                        });

                        document.addEventListener("DOMContentLoaded", () => {
                            let inputs = document.querySelectorAll(".passwordHome");

                            inputs.forEach((input, index) => {
                                // Typing: go forward
                                input.addEventListener("input", () => {
                                    if (input.value.length === 1 && index < inputs.length - 1) {
                                        inputs[index + 1].focus();
                                    }
                                });

                                // Backspace: go backward
                                input.addEventListener("keydown", (e) => {
                                    if (e.key === "Backspace" && input.value === "" && index > 0) {
                                        inputs[index - 1].focus();
                                    }
                                });
                            });
                        });
                    </script>

                    <!-- SearcForm -->


                    <!-- One Way -->

                    <form wire:submit='searchPackage' autocomplete="off"
                        class="{{ $selected_tab == 'one_way' ? 'block' : 'hidden' }} bg-white rounded-xl mx-0 lg:mx-20 md:pt-14 pt-5 px-2 block  items-center justify-center">

                        <div class="lg:flex grid mx-0 w-full ">
                            <label for="simple-search" class="sr-only">From</label>
                            <div class="relative lg:w-1/4 md:mt-0 mt-4 ">
                                <label for="">
                                    <span class="font-semibold">From City</span>
                                </label>
                                <input type="text" wire:model.live='query_search' placeholder="From City.."
                                    id="simple-search-1"
                                    class="lg:mt-3 bg-gray-50 border {{ $this->hasError('query') ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500' }} lg:py-10 text-black font-extrabold lg:text-2xl text-xm block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                                @if($this->hasError('query'))
                                    <p class="mt-1 text-sm text-red-600">{{ $this->getError('query') }}</p>
                                @endif

                                @if (!empty($query_search))
                                <div class="absolute z-10 w-40 bg-white rounded-t-none shadow-lg list-group ">

                                    @if (!empty($cities_from))

                                    @foreach ($cities_from as $city)
                                    <a wire:click='update1("{{ $city['name'] }}", {{ $city['id'] }})'
                                        class="list-item list-none p-1 bg-sky-500 hover:bg-sky-700  text-slate-100 border-spacing-1">{{ $city['name'] }}</a>
                                    @endforeach
                                    @else
                                    @endif

                                </div>
                                @endif
                            </div>

                            <div class="lg:w-1/4 md:mt-0 mt-3">
                                <label for="">
                                    <span class="font-semibold">To City</span>
                                </label>
                                <input type="text" wire:model.live='query2_search' placeholder="To City.."
                                    id="simple-search-1"
                                    class="lg:mt-3 bg-gray-50 border {{ $this->hasError('query2') ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500' }} lg:py-10 text-black font-extrabold lg:text-2xl text-xm block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                                @if($this->hasError('query2'))
                                    <p class="mt-1 text-sm text-red-600">{{ $this->getError('query2') }}</p>
                                @endif

                                @if (!empty($query2_search))



                                <div class="absolute z-10 w-40 bg-white rounded-t-none shadow-lg list-group ">

                                    @if (!empty($cities_to))

                                    @foreach ($cities_to as $city)
                                    <a wire:click='update2("{{ $city['name'] }}", {{ $city['id'] }})'
                                        class="list-item list-none p-1 bg-sky-500 hover:bg-sky-700 text-slate-100 border-spacing-1">{{ $city['name'] }}</a>
                                    @endforeach
                                    @else
                                    @endif

                                </div>
                                @endif
                            </div>
                            <div class="lg:w-1/4 md:mt-0 mt-3">
                                <label for="">
                                    <span class="font-semibold">PickUp Date</span>
                                </label>
                                <input type="date" wire:model='date' id="simple-search" min="<?php echo \Carbon\Carbon::now()->format('Y-m-d'); ?>"
                                    class="arriveDate lg:mt-3 bg-gray-50 border {{ $this->hasError('date') ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500' }} lg:py-10 text-black font-extrabold lg:text-2xl block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                    placeholder="date" required />
                                @if($this->hasError('date'))
                                    <p class="mt-1 text-sm text-red-600">{{ $this->getError('date') }}</p>
                                @endif

                            </div>

                            <div class="lg:w-1/4 md:mt-0 mt-3">
                                <label for="">
                                    <span class="font-semibold">PickUp Time</span>
                                </label>
                                <input type="time" wire:model='time' id="simple-search"
                                    class="arriveTime lg:mt-3 bg-gray-50 border {{ $this->hasError('time') ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500' }} lg:py-10 text-black font-extrabold lg:text-2xl block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                    placeholder="date" required />
                                @if($this->hasError('time'))
                                    <p class="mt-1 text-sm text-red-600">{{ $this->getError('time') }}</p>
                                @endif

                            </div>
                        </div>

                        @if(!empty($oneWayMsg))
                        <div class="p-4 mb-4 text-sm text-red-800 border border-red-300 rounded-lg bg-red-100 mt-3" role="alert">
                            {{$oneWayMsg}}
                        </div>
                        @endif
                        <div class="w-full flex justify-center form-margine-negative lg:mt-10 mt-2">
                            <button type="submit"
                                class="p-2.5 uppercase  my-2 w-60 text-2xl fon font-bold text-white main-color rounded-full border border-sky-600 hover:bg-sky-600 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-sky-600 dark:hover:bg-sky-700 dark:focus:ring-sky-800">

                                Search

                            </button>
                        </div>

                    </form>


                    <!-- Local -->
                    <form wire:submit='searchPackage' autocomplete="off"
                        class="{{ $selected_tab == 'local' ? 'block' : 'hidden' }} bg-white rounded-xl mx-0 lg:mx-20 md:pt-14 pt-5 px-2 block  items-center justify-center">
                        <div class="lg:flex grid items-center mx-0 w-full ">
                            <label for="simple-search" class="sr-only">From</label>
                            <div class="relative w-full">
                                <div class="lg:mt-3 w-full lg:inline-flex">
                                    <div class="relative lg:w-1/5 md:mt-0 mt-4 ">
                                        <label for="">
                                            <span class="font-semibold">From City</span>
                                        </label>
                                        <input type="text" wire:model.live='queryLocal' placeholder="From City.."
                                            id="simple-search-1"
                                            class="lg:mt-3 bg-gray-50 border {{ $this->hasError('query') ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500' }} lg:py-[40px] text-black font-extrabold lg:text-2xl text-xm block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                                        @if($this->hasError('query'))
                                            <div class="mt-1">
                                                <p class="text-sm text-red-600">{{ $this->getError('query') }}</p>
                                            </div>
                                        @endif

                                        @if (!empty($queryLocal))



                                        <div
                                            class="absolute z-10 w-40 bg-white rounded-t-none shadow-lg list-group rounded-lg">

                                            @if (!empty($cities_from))

                                            @foreach ($cities_from as $city)
                                            <a wire:click='update3("{{ $city['name'] }}", {{ $city['id'] }})'
                                                class="list-item list-none p-1 bg-sky-500 hover:bg-sky-700 text-slate-100 border-spacing-1">{{ $city['name'] }}</a>
                                            @endforeach
                                            @else
                                            @endif

                                        </div>
                                        @endif
                                    </div>

                                    <div class="lg:w-1/5 md:mt-0 mt-3">
                                        <label for="">
                                            <span class="font-semibold">Select Plan</span>
                                        </label>
                                        <select wire:model='plan' name="plan" id="plan"
                                            class="lg:mt-3 bg-gray-50 border {{ $this->hasError('plan') ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500' }} lg:py-[41.5px] text-black font-extrabold lg:text-2xl text-xm block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                            <option value="4 Hour / 40 Km">4 Hour / 40 Km</option>
                                            <option value="8 Hour / 80 Km">8 Hour / 80 Km</option>
                                            <option value="12 Hour / 120 Km">12 Hour / 120 Km</option>
                                        </select>
                                        @if($this->hasError('plan'))
                                            <div class="mt-1">
                                                <p class="text-sm text-red-600">{{ $this->getError('plan') }}</p>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="lg:w-1/5 md:mt-0 mt-3">
                                        <label for="">
                                            <span class="font-semibold">Start Date</span>
                                        </label>
                                        <input type="date" wire:model='date' id="simple-search"
                                            min="<?php echo date('Y-m-d'); ?>"
                                            class="arriveDate lg:mt-3 bg-gray-50 border {{ $this->hasError('date') ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500' }} lg:py-10 text-black font-extrabold lg:text-2xl text-xm block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="date" required />
                                        @if($this->hasError('date'))
                                            <div class="mt-1">
                                                <p class="text-sm text-red-600">{{ $this->getError('date') }}</p>
                                            </div>
                                        @endif

                                    </div>

                                    <div class="lg:w-1/5 md:mt-0 mt-3">
                                        <label for="">
                                            <span class="font-semibold">Start Time</span>
                                        </label>
                                        <input type="time" wire:model='time' id="simple-search"
                                            class="arriveTime lg:mt-3 bg-gray-50 border {{ $this->hasError('time') ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500' }} lg:py-10 text-black font-extrabold lg:text-2xl text-xm block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="date" required />
                                        @if($this->hasError('time'))
                                            <div class="mt-1">
                                                <p class="text-sm text-red-600">{{ $this->getError('time') }}</p>
                                            </div>
                                        @endif

                                    </div>

                                    <div class="lg:w-1/5 md:mt-0 mt-3">
                                        <label for="">
                                            <span class="font-semibold">Number of Cars</span>
                                        </label>
                                        <input type="number" wire:model='car' id="simple-search"
                                            class="lg:mt-3 bg-gray-50 border {{ $this->hasError('car') ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500' }} lg:py-10 text-black font-extrabold lg:text-2xl text-xm block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="No. of cars" required />
                                        @if($this->hasError('car'))
                                            <div class="mt-1">
                                                <p class="text-sm text-red-600">{{ $this->getError('car') }}</p>
                                            </div>
                                        @endif


                                    </div>

                                </div>




                            </div>

                        </div>


                        <div class="w-full flex justify-center form-margine-negative lg:mt-10 mt-2">
                            <button type="submit"
                                class="p-2.5 uppercase  my-2 w-60 text-2xl fon font-bold text-white main-color rounded-full border border-sky-600 hover:bg-sky-600 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-sky-600 dark:hover:bg-sky-700 dark:focus:ring-sky-800">

                                Search

                            </button>
                        </div>
                    </form>


                    <!-- Return -->
                    <form wire:submit='searchPackage' autocomplete="off"
                        class="{{ $selected_tab == 'return' ? 'block' : 'hidden' }} bg-white rounded-xl mx-0 lg:mx-20 md:pt-14 pt-5 px-2 block  items-center justify-center">
                        <div class="lg:flex grid items-center mx-0 w-full ">
                            <label for="simple-search" class="sr-only">From</label>
                            <div class="relative w-full">
                                <div class="lg:mt-3 w-full lg:inline-flex">
                                    <div class="lg:w-1/5 md:mt-0 mt-4 ">
                                        <label for="">
                                            <span class="font-semibold">From City</span>
                                        </label>

                                        <input type="text" wire:model.live='queryFrom_search' placeholder="From City.."
                                            id="simple-search-1"
                                            class="lg:mt-3 bg-gray-50 border {{ $this->hasError('queryFrom') ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500' }} lg:py-[40px] text-black font-extrabold lg:text-2xl text-xm block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                                        @if($this->hasError('queryFrom'))
                                            <p class="mt-1 text-sm text-red-600">{{ $this->getError('queryFrom') }}</p>
                                        @endif

                                        @if (!empty($queryFrom_search))



                                        <div
                                            class="absolute z-10 w-96 bg-white rounded-t-none shadow-lg list-group rounded-lg">

                                            @if (!empty($dataFrom))

                                            @foreach ($dataFrom as $city)
                                            <a wire:click='updateCityFrom("{{ $city['description'] }}")'
                                                class="list-item list-none p-1 bg-sky-500 hover:bg-sky-700 text-slate-100 border-spacing-1 ">{{ $city['description'] }}</a>
                                            @endforeach
                                            @else
                                            @endif

                                        </div>
                                        @endif
                                    </div>

                                    <div class="lg:w-1/5 md:mt-0 mt-3">
                                        <label for="">
                                            <span class="font-semibold">To City</span>
                                        </label>
                                        <input type="text" wire:model.live='queryTo_search' placeholder="To City.."
                                            id="simple-search-1"
                                            class="lg:mt-3 bg-gray-50 border {{ $this->hasError('queryTo') ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500' }} lg:py-[40px] text-black font-extrabold lg:text-2xl text-xm block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                                        @if($this->hasError('queryTo'))
                                            <p class="mt-1 text-sm text-red-600">{{ $this->getError('queryTo') }}</p>
                                        @endif

                                        @if (!empty($queryTo_search))
                                        <div
                                            class="absolute z-10 w-96 bg-white rounded-t-none shadow-lg list-group rounded-lg">

                                            @if (!empty($dataTo))

                                            @foreach ($dataTo as $city)
                                            <a wire:click='updateCityTo("{{ $city['description'] }}")'
                                                class="list-item list-none p-1 bg-sky-500 hover:bg-sky-700 text-slate-100 border-spacing-1">{{ $city['description'] }}</a>
                                            @endforeach
                                            @else
                                            @endif

                                        </div>
                                        @endif
                                    </div>

                                    <div class="lg:w-1/5 md:mt-0 mt-3">
                                        <label for="">
                                            <span class="font-semibold">Start Date</span>
                                        </label>
                                        <input type="date" wire:model='date' id="simple-search"
                                            min="<?php echo date('Y-m-d'); ?>"
                                            class="arriveDate lg:mt-3 bg-gray-50 border {{ $this->hasError('date') ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500' }} lg:py-[40px] text-black font-extrabold lg:text-2xl text-xm block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="date" required />
                                        @if($this->hasError('date'))
                                            <p class="mt-1 text-sm text-red-600">{{ $this->getError('date') }}</p>
                                        @endif
                                    </div>

                                    <div class="lg:w-1/5 md:mt-0 mt-3">
                                        <label for="">
                                            <span class="font-semibold">End Date</span>
                                        </label>
                                        <input type="date" wire:model='dateto' id="simple-search"
                                            min="<?php echo date('Y-m-d'); ?>"
                                            class="arriveDate lg:mt-3 bg-gray-50 border border-gray-300 lg:py-[40px] text-black font-extrabold lg:text-2xl text-xm focus:ring-blue-500 focus:border-blue-500 block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="date" required />
                                    </div>

                                    <div class="lg:w-1/5 md:mt-0 mt-3">
                                        <label for="">
                                            <span class="font-semibold">PickUp Time</span>
                                        </label>
                                        <input type="time" wire:model='time' id="simple-search"
                                            class="arriveTime lg:mt-3 bg-gray-50 border border-gray-300 lg:py-[40px] text-black font-extrabold lg:text-2xl text-xm focus:ring-blue-500 focus:border-blue-500 block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="date" required />
                                    </div>






                                </div>


                            </div>

                        </div>

                        <div class="w-full flex justify-center form-margine-negative lg:mt-10 mt-2">
                            <button type="submit"
                                class="p-2.5 uppercase  my-2 w-60 text-2xl fon font-bold text-white main-color rounded-full border border-sky-600 hover:bg-sky-600 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-sky-600 dark:hover:bg-sky-700 dark:focus:ring-sky-800">

                                Search

                            </button>
                        </div>
                    </form>

                    <!-- Self Drive -->
                    <form wire:submit='searchPackage' autocomplete="off"
                        class="{{ $selected_tab == 'self_drive' ? 'block' : 'hidden' }} bg-white rounded-xl mx-0 lg:mx-20 md:pt-14 pt-5 px-2 block  items-center justify-center">
                        <div class="lg:flex grid items-center mx-0 w-full ">
                            <label for="simple-search" class="sr-only">From</label>
                            <div class="relative w-full">

                                <div class="lg:mt-3 w-full lg:inline-flex">

                                    <div class="lg:w-1/5 md:mt-0 mt-4 ">
                                        <label for="">
                                            <span class="font-semibold">From City</span>
                                        </label>
                                        <input type="text" wire:model.live='querySelfDrive'
                                            placeholder="From City.." id="simple-search-1"
                                            class="lg:mt-3 bg-gray-50 border {{ $this->hasError('query') ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500' }} lg:py-[40px] text-black font-extrabold lg:text-2xl text-xm block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" />
                                        @if($this->hasError('query'))
                                            <div class="mt-1">
                                                <p class="text-sm text-red-600">{{ $this->getError('query') }}</p>
                                            </div>
                                        @endif

                                        @if (!empty($querySelfDrive))



                                        <div
                                            class="absolute z-10 w-40 bg-white rounded-t-none shadow-lg list-group rounded-lg">

                                            @if (!empty($cities_from))

                                            @foreach ($cities_from as $city)
                                            <a wire:click='update4("{{ $city['name'] }}", {{ $city['id'] }})'
                                                class="list-item list-none p-1 bg-sky-500 hover:bg-sky-700 text-slate-100 border-spacing-1">{{ $city['name'] }}</a>
                                            @endforeach
                                            @else
                                            @endif

                                        </div>
                                        @endif
                                    </div>
                                    <div class="lg:w-1/5 md:mt-0 mt-3">
                                        <label for="">
                                            <span class="font-semibold">Start Date</span>
                                        </label>
                                        <input type="date" wire:model='date' id="simple-search" name="book"
                                            min="<?php echo date('Y-m-d'); ?>"
                                            class="arriveDate lg:mt-3 bg-gray-50 border border-gray-300 lg:py-[40px] text-black font-extrabold lg:text-2xl text-xm focus:ring-blue-500 focus:border-blue-500 block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="date" required />

                                    </div>

                                    <div class="lg:w-1/5 md:mt-0 mt-3">
                                        <label for="">
                                            <span class="font-semibold">Start Time</span>
                                        </label>
                                        <input type="time" wire:model='time' id="simple-search"
                                            class="arriveTime lg:mt-3 bg-gray-50 border border-gray-300 lg:py-[40px] text-black font-extrabold lg:text-2xl text-xm focus:ring-blue-500 focus:border-blue-500 block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="date" required />

                                    </div>
                                    <div class="lg:w-1/5 md:mt-0 mt-3">
                                        <label for="">
                                            <span class="font-semibold">End Date</span>
                                        </label>
                                        <input type="date" wire:model='dateto' id="simple-search"
                                            min="<?php echo date('Y-m-d'); ?>"
                                            class="arriveDate lg:mt-3 bg-gray-50 border border-gray-300 lg:py-[40px] text-black font-extrabold lg:text-2xl text-xm focus:ring-blue-500 focus:border-blue-500 block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="date" required />
                                    </div>
                                    <div class="lg:w-1/5 md:mt-0 mt-3">
                                        <label for="">
                                            <span class="font-semibold">End Time</span>
                                        </label>
                                        <input type="time" wire:model='endTime' id="simple-search"
                                            class="arriveTime lg:mt-3 bg-gray-50 border {{ $this->hasError('endTime') ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500' }} lg:py-[40px] text-black font-extrabold lg:text-2xl text-xm block w-full ps-2 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                            placeholder="End Date" required />
                                        @if($this->hasError('endTime'))
                                            <div class="mt-1">
                                                <p class="text-sm text-red-600">{{ $this->getError('endTime') }}</p>
                                            </div>
                                        @endif
                                    </div>



                                </div>


                            </div>

                        </div>

                        <div class="w-full flex justify-center form-margine-negative lg:mt-10 mt-2">
                            <button type="submit"
                                class="p-2.5 uppercase  my-2 w-60 text-2xl fon font-bold text-white main-color rounded-full border border-sky-600 hover:bg-sky-600 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-sky-600 dark:hover:bg-sky-700 dark:focus:ring-sky-800">

                                Search

                            </button>
                        </div>
                    </form>







                    <!-- SearcForm End -->


                    <!-- Buttons -->
                    {{-- <div class="mt-7 grid gap-3 w-full sm:inline-flex">
                        <a class="py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                            href="/register">
                            Get started
                            <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24"
                                height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m9 18 6-6-6-6" />
                            </svg>
                        </a>
                        <a class="py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-white dark:hover:bg-gray-800 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                            href="/contact">
                            Contact sales team
                        </a>
                    </div> --}}
                    <!-- End Buttons -->


                </div>
                <!-- End Col -->

                {{-- <div class="relative ms-4">
                    <img class="w-full rounded-md" src="https://www.duracabs.com/home_back.png" alt="Image Description">
                    <div
                        class="absolute inset-0 -z-[1] bg-gradient-to-tr from-gray-200 via-white/0 to-white/0 w-full h-full rounded-md mt-4 -mb-4 me-4 -ms-4 lg:mt-6 lg:-mb-6 lg:me-6 lg:-ms-6 dark:from-slate-800 dark:via-slate-900/0 dark:to-slate-900/0">
                    </div>

                   
                    <div class="absolute bottom-0 start-0">
                        <svg class="w-2/3 ms-auto h-auto text-white dark:text-slate-900" width="630"
                            height="451" viewBox="0 0 630 451" fill="none" xmlns="http://www.w3.org/2000/svg">

                            <rect x="531" y="328" width="50" height="50" fill="currentColor" />

                            <rect x="234" y="402" width="62" height="49" fill="currentColor" />


                            <rect x="531" y="49" width="99" height="99" fill="currentColor" />
                        </svg>
                    </div>
                    <!-- End SVG-->
                </div> --}}
                <!-- End Col -->
            </div>
            <!-- End Grid -->
        </div>
    </div>


    {{-- Hero Section End --}}

    <div
        class="flex form-margine-negative relative bg-white text-sm font-medium text-center text-gray-500 rounded-lg shadow  dark:divide-gray-700 dark:text-gray-400 mt-10 lg:mx-48 mx-5">
        <a href="/vendor-register"
            class="lg:flex grid grid-rows-1 gap-0 place-items-center  text-base text-center items-center justify-center  w-full lg:p-4 p-1 text-gray-900 bg-gray-100 border-r border-gray-200 dark:border-gray-700 rounded-s-lg focus:ring-4 focus:ring-blue-300 active focus:outline-none dark:bg-gray-700 dark:text-white">
            <svg xmlns="http://www.w3.org/2000/svg" class="mx-2" fill="#1e9cfd" width="20px"
                viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                <path
                    d="M192 0c-17.7 0-32 14.3-32 32l0 32 0 .2c-38.6 2.2-72.3 27.3-85.2 64.1L39.6 228.8C16.4 238.4 0 261.3 0 288L0 432l0 48c0 17.7 14.3 32 32 32l32 0c17.7 0 32-14.3 32-32l0-48 320 0 0 48c0 17.7 14.3 32 32 32l32 0c17.7 0 32-14.3 32-32l0-48 0-144c0-26.7-16.4-49.6-39.6-59.2L437.2 128.3c-12.9-36.8-46.6-62-85.2-64.1l0-.2 0-32c0-17.7-14.3-32-32-32L192 0zM165.4 128l181.2 0c13.6 0 25.7 8.6 30.2 21.4L402.9 224l-293.8 0 26.1-74.6c4.5-12.8 16.6-21.4 30.2-21.4zM96 288a32 32 0 1 1 0 64 32 32 0 1 1 0-64zm288 32a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z" />
            </svg>
            Attach Taxi
        </a>

        <a href="/terms-and-conditions#cancellation"
            class="lg:flex grid grid-rows-1 gap-0 place-items-center text-base text-center items-center justify-center  w-full lg:p-4 p-1 text-gray-900 bg-gray-100 border-r border-gray-200 dark:border-gray-700 rounded-s-lg focus:ring-4 focus:ring-blue-300 active focus:outline-none dark:bg-gray-700 dark:text-white">
            <svg xmlns="http://www.w3.org/2000/svg" class="mx-2" fill="#1e9cfd" width="20px"
                viewBox="0 0 320 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                <path
                    d="M160 0c17.7 0 32 14.3 32 32l0 35.7c1.6 .2 3.1 .4 4.7 .7c.4 .1 .7 .1 1.1 .2l48 8.8c17.4 3.2 28.9 19.9 25.7 37.2s-19.9 28.9-37.2 25.7l-47.5-8.7c-31.3-4.6-58.9-1.5-78.3 6.2s-27.2 18.3-29 28.1c-2 10.7-.5 16.7 1.2 20.4c1.8 3.9 5.5 8.3 12.8 13.2c16.3 10.7 41.3 17.7 73.7 26.3l2.9 .8c28.6 7.6 63.6 16.8 89.6 33.8c14.2 9.3 27.6 21.9 35.9 39.5c8.5 17.9 10.3 37.9 6.4 59.2c-6.9 38-33.1 63.4-65.6 76.7c-13.7 5.6-28.6 9.2-44.4 11l0 33.4c0 17.7-14.3 32-32 32s-32-14.3-32-32l0-34.9c-.4-.1-.9-.1-1.3-.2l-.2 0s0 0 0 0c-24.4-3.8-64.5-14.3-91.5-26.3c-16.1-7.2-23.4-26.1-16.2-42.2s26.1-23.4 42.2-16.2c20.9 9.3 55.3 18.5 75.2 21.6c31.9 4.7 58.2 2 76-5.3c16.9-6.9 24.6-16.9 26.8-28.9c1.9-10.6 .4-16.7-1.3-20.4c-1.9-4-5.6-8.4-13-13.3c-16.4-10.7-41.5-17.7-74-26.3l-2.8-.7s0 0 0 0C119.4 279.3 84.4 270 58.4 253c-14.2-9.3-27.5-22-35.8-39.6c-8.4-17.9-10.1-37.9-6.1-59.2C23.7 116 52.3 91.2 84.8 78.3c13.3-5.3 27.9-8.9 43.2-11L128 32c0-17.7 14.3-32 32-32z" />
            </svg>
            Cancel Ticket
        </a>

        <a href="/terms-and-conditions#cancellation"
            class="lg:flex grid grid-rows-1 gap-0 place-items-center text-base text-center items-center justify-center  w-full lg:p-4 p-1 text-gray-900 bg-gray-100 border-r border-gray-200 dark:border-gray-700 rounded-s-lg focus:ring-4 focus:ring-blue-300 active focus:outline-none dark:bg-gray-700 dark:text-white">
            <svg xmlns="http://www.w3.org/2000/svg" class="mx-2" fill="#1e9cfd" width="20px"
                viewBox="0 0 576 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                <path
                    d="M64 64C28.7 64 0 92.7 0 128l0 64c0 8.8 7.4 15.7 15.7 18.6C34.5 217.1 48 235 48 256s-13.5 38.9-32.3 45.4C7.4 304.3 0 311.2 0 320l0 64c0 35.3 28.7 64 64 64l448 0c35.3 0 64-28.7 64-64l0-64c0-8.8-7.4-15.7-15.7-18.6C541.5 294.9 528 277 528 256s13.5-38.9 32.3-45.4c8.3-2.9 15.7-9.8 15.7-18.6l0-64c0-35.3-28.7-64-64-64L64 64zm64 112l0 160c0 8.8 7.2 16 16 16l288 0c8.8 0 16-7.2 16-16l0-160c0-8.8-7.2-16-16-16l-288 0c-8.8 0-16 7.2-16 16zM96 160c0-17.7 14.3-32 32-32l320 0c17.7 0 32 14.3 32 32l0 192c0 17.7-14.3 32-32 32l-320 0c-17.7 0-32-14.3-32-32l0-192z" />
            </svg>
            Refund Status
        </a>

        <a href="https://api.whatsapp.com/send/?phone=917088873331&text=hi%20i%20want%20to%20book%20a%20ride&type=phone_number&app_absent=0"
            target="_new"
            class="lg:flex grid grid-rows-1 gap-0 place-items-center text-base text-center items-center justify-center  w-full lg:p-4 p-1 text-gray-900 bg-gray-100 border-r border-gray-200 dark:border-gray-700 rounded-e-lg focus:ring-4 focus:ring-blue-300 active focus:outline-none dark:bg-gray-700 dark:text-white">
            <svg xmlns="http://www.w3.org/2000/svg" class="mx-2" fill="#1e9cfd" width="20px"
                viewBox="0 0 384 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                <path
                    d="M144 56c0-4.4 3.6-8 8-8l80 0c4.4 0 8 3.6 8 8l0 72-96 0 0-72zm176 72l-32 0 0-72c0-30.9-25.1-56-56-56L152 0C121.1 0 96 25.1 96 56l0 72-32 0c-35.3 0-64 28.7-64 64L0 416c0 35.3 28.7 64 64 64c0 17.7 14.3 32 32 32s32-14.3 32-32l128 0c0 17.7 14.3 32 32 32s32-14.3 32-32c35.3 0 64-28.7 64-64l0-224c0-35.3-28.7-64-64-64zM112 224l160 0c8.8 0 16 7.2 16 16s-7.2 16-16 16l-160 0c-8.8 0-16-7.2-16-16s7.2-16 16-16zm0 128l160 0c8.8 0 16 7.2 16 16s-7.2 16-16 16l-160 0c-8.8 0-16-7.2-16-16s7.2-16 16-16z" />
            </svg>
            Plan Your Tour
        </a>
    </div>

    <ul
        class="flex form-margine-negative relative text-sm font-medium text-center text-gray-500 rounded-lg shadow  dark:divide-gray-700 dark:text-gray-400 mt-20 lg:mx-48 mx-5">
        <li class="w-1/2 focus-within:z-10">

            <button type="button" href="#slide" wire:click="changeBanner('one_way')"
                class="flex  text-center items-center justify-center  w-full px-2 py-4 text-gray-900 bg-gray-100 border-r border-gray-200 dark:border-gray-700 rounded-s-lg focus:ring-4 focus:ring-blue-300 active focus:outline-none dark:bg-gray-700 dark:text-white"
                aria-current="page">

                <h2
                    class="font-bold lg:text-sm text-xs uppercase {{ $bannerTab === 'one_way' ? 'main-color-text font-extrabold' : '' }}">
                    One Way</h2>

            </button>
        </li>
        <li class="w-1/2 focus-within:z-10">
            <button type="button" wire:click="changeBanner('return')"
                class="flex text-center items-center justify-center  w-full px-2 py-4 bg-white border-r border-gray-200 dark:border-gray-700 hover:text-gray-700 hover:bg-gray-50 focus:ring-4 focus:ring-blue-300 focus:outline-none dark:hover:text-white dark:bg-gray-800 dark:hover:bg-gray-700">

                <h2
                    class="font-bold lg:text-sm text-xs uppercase {{ $bannerTab === 'return' ? 'main-color-text font-extrabold' : '' }} ">
                    Return</h2>
            </button>
        </li>
        <li class="w-1/2 focus-within:z-10">
            <button type="button" href="#slide" wire:click='changeBanner("local")'
                class="inline-block flex text-center items-center justify-center  w-full px-2 py-4 bg-white border-r border-gray-200 dark:border-gray-700 hover:text-gray-700 hover:bg-gray-50 focus:ring-4 focus:ring-blue-300 focus:outline-none dark:hover:text-white dark:bg-gray-800 dark:hover:bg-gray-700">

                <h2
                    class="font-bold lg:text-sm text-xs uppercase  {{ $bannerTab === 'local' ? 'main-color-text font-extrabold' : '' }} ">
                    Local</h2>

            </button>
        </li>
        <li class="w-1/2 focus-within:z-10">
            <button type="button" href="#slide" wire:click='changeBanner("self_drive")'
                class="inline-block flex text-center items-center justify-center  w-full px-0 py-4 bg-white border-s-0 border-gray-200 dark:border-gray-700 rounded-e-lg hover:text-gray-700 hover:bg-gray-50 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:hover:text-white dark:bg-gray-800 dark:hover:bg-gray-700">

                <h2
                    class="font-bold lg:text-sm text-xs uppercase {{ $bannerTab === 'self_drive' ? 'main-color-text font-extrabold' : '' }} ">
                    Self Drive</h2>
            </button>
        </li>
    </ul>

    {{-- Slider Section Start --}}

    <div class="mt-10 lg:mx-20 md:mx-5 mx-2">
        <div class="mt-12 flex mx-auto items-center">
            <div x-data="carousel()" x-init="init()" class="relative overflow-hidden group">
                <div x-ref="container" class="-ml-4 flex overflow-x-scroll scroll-snap-x space-x-4 md:space-y-0 ">

                    @foreach ($carousel as $item)
                    <a href="{{ $item->url }}"
                        class="ml-4 flex-auto flex-grow-0 flex-shrink-0 lg:w-72 w-52 rounded-lg bg-gray-100 items-center justify-center snap-center overflow-hidden shadow-md">
                        <div><img src="{{ url('storage') }}/{{ $item->image }}"
                                alt=" {{ $item->alt }} - Duracabs" title=" {{ $item->title }} - Duracabs">
                        </div>
                        {{-- <div class="px-2 py-3 flex justify-between">
                                <div class="text-lg font-semibold">{{ $item->name }}
                </div>
                <time>3/6/2021</time>
            </div> --}}
            </a>
            @endforeach



        </div>
        <div @click="scrollTo(prev)" x-show="prev !== null"
            class="hidden md:block absolute top-1/2 left-0 bg-white p-2 rounded-full transition-transform ease-in-out transform -translate-x-full -translate-y-1/2 group-hover:translate-x-0 cursor-pointer">
            <div>&lt;</div>
        </div>
        <div @click="scrollTo(next)" x-show="next !== null"
            class="hidden md:block absolute top-1/2 right-0 bg-white p-2 rounded-full transition-transform ease-in-out transform translate-x-full -translate-y-1/2 group-hover:translate-x-0 cursor-pointer">
            <div>&gt;</div>
        </div>
    </div>
</div>
</div>


{{-- Slider Section End --}}

{{-- City Section Start --}}

<section class="mt-10 lg:mx-20 md:mx-5 mx-2">
    <div class="max-w-xl mx-auto">
        <div class="text-center ">
            <div class="relative flex flex-col items-center">
                <h2 class="text-5xl font-bold dark:text-gray-200"> Top Travel Destinations to <span
                        class="text-blue-500">
                        Explore in India
                    </span> </h2>
                <div class="flex w-40 mt-2 mb-6 overflow-hidden rounded">
                    <div class="flex-1 h-2 bg-blue-200">
                    </div>
                    <div class="flex-1 h-2 bg-blue-400">
                    </div>
                    <div class="flex-1 h-2 bg-blue-600">
                    </div>
                </div>
            </div>

        </div>
    </div>
    <div class="mt-10 lg:mx-20 md:mx-5 mx-2">
        <div class="mt-12 flex mx-auto items-center">
            <div x-data="carousel()" x-init="init()" class="relative overflow-hidden group">
                <div x-ref="container" class="-ml-4 flex overflow-x-scroll scroll-snap-x space-x-4 md:space-y-0 ">



                    @foreach ($brands as $brand)
                    <div class="ml-4 flex-auto flex-grow-0 flex-shrink-0 lg:w-96 w-56 rounded-lg bg-gray-100 items-center justify-center snap-center overflow-hidden shadow-md"
                        wire:key='{{ $brand->id }}'>
                        <a href="{{ $brand->slug }}" class="">
                            <img src="{{ url('storage') }}/{{ $brand->image }}" alt="{{ $brand->name }}"
                                alt="Duracabs {{ $brand->slug }}"
                                class="object-cover w-full h-64 rounded-t-lg">
                        </a>
                        <div class="p-5 text-center">
                            <a href="{{ $brand->slug }}"
                                class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-300">
                                {{ $brand->name }}
                            </a>
                        </div>
                    </div>
                    @endforeach




                </div>
                <div @click="scrollTo(prev)" x-show="prev !== null"
                    class="hidden md:block absolute top-1/2 left-0 bg-white p-2 rounded-full transition-transform ease-in-out transform -translate-x-full -translate-y-1/2 group-hover:translate-x-0 cursor-pointer">
                    <div>&lt;</div>
                </div>
                <div @click="scrollTo(next)" x-show="next !== null"
                    class="hidden md:block absolute top-1/2 right-0 bg-white p-2 rounded-full transition-transform ease-in-out transform translate-x-full -translate-y-1/2 group-hover:translate-x-0 cursor-pointer">
                    <div>&gt;</div>
                </div>
            </div>
        </div>
    </div>
</section>
{{-- city Section End --}}

{{-- Category About Us --}}

<img src="/img/home/banner.webp" width="100%"
    alt="Duracabs – Online Cab Booking for One Way, Round Trip, and Local Taxi Services in India"
    title="Book One Way, Round Trip, and Local Taxis Online with Duracabs" />

<div class="lg:mx-40 mx-5 bg-white p-4">
  <section id="about-duracabs" class="seo-content">
    <h2><strong>Duracabs – India's Trusted Self Drive Car Rental Services</strong></h2>
    <br />

    <div id="content" class="overflow-hidden transition-all duration-500 ease-in-out" style="max-height: 400px;">
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
        <p>Planning your trip has never been easier than with Duracabs. You can book your car directly from our website,<a herf="https://www.duracabs.com/"></a>, or you can phone our trip experts at 7088873331 to talk about your plans. We are here to make sure that your trip is smooth, pleasant, and memorable from the minute you pick up your automobile until the time you return it. We don't just rent cars at Duracabs; we make trips that you'll never forget.</p>
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
        <p>Our airport drop services are designed to make your journey from your home to the airport as easy and stress-free as possible. From late-night arrivals to early-morning flights, our trustworthy and punctual drivers will bring you to the airport in luxury and on time. By providing you with clean vehicles, experienced drivers and real-time tracking, we prioritize your comfort, security and relief from worry. With our <a herf="https://www.duracabs.com/">airport drop services</a>, you can travel without worrying from the start. You can reach us at any time, day or night, and we have transparent pricing and booking options. No more parking, traffic, or last-minute delays.</p>
    <!-- Rest of your content continues here -->
    </div>

    <!-- Read More Button -->
    <div class="text-center mt-4">
      <button id="toggleBtn"
        class="text-white main-color hover:bg-sky-800 focus:outline-none focus:ring-4 focus:ring-blue-300 font-medium rounded-full text-sm px-5 py-2.5 text-center">
        Read More
      </button>
    </div>
  </section>
</div>

<script>
  const content = document.getElementById('content');
  const toggleBtn = document.getElementById('toggleBtn');
  let expanded = false;

  toggleBtn.addEventListener('click', () => {
    expanded = !expanded;
    if (expanded) {
      content.style.maxHeight = content.scrollHeight + 'px';
      toggleBtn.textContent = 'Read Less';
    } else {
      content.style.maxHeight = '400px';
      toggleBtn.textContent = 'Read More';
    }
  });
</script>



{{-- Category About Us --}}
{{-- Our Services  --}}


<section id="dura-cabs-services" class="seo-content lg:px-40 my-10 py-10 bg-gray-50 ">
    <h2 class="text-3xl font-semibold p-4">
        Affordable One Way, Round Trip & Local Taxi Services by Duracabs
    </h2>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 md:grid-cols-2">
        <div class="w-full p-4 lg:flex grid grid-rows-1 gap-0 place-items-center">

            <div class="w-full lg:text-left text-center">
                <div class="lg:w-1/4 w-50 mx-auto ">
                    <img src="/cab_images/one_way.webp" alt="Duracabs 24X7" width="100%" class="p-4" />
                </div>
                <h3 class="text-xl font-extrabold main-color-text">
                    One Way Taxi Service in India – Book with Duracabs
                </h3>
                <p>Save money and travel conveniently with <strong>Duracabs' one way taxi service</strong>. Perfect
                    for intercity travel, airport drop-offs, and long-distance rides, you only pay for the journey
                    you need — no return fare required. Available across major cities and routes in India.</p>
                <p><a href="/rides?tab=one_way" class="cta-button">Book your one way taxi now</a> and enjoy a
                    hassle-free
                    ride with Duracabs.</p>
                <p><small>Links:
                        <a href="/rides?tab=one_way"> one way taxi service,</a>
                        <a href="/rides?tab=one_way"> one way cab booking, </a>
                        <a href="/rides?tab=one_way"> intercity taxi India, </a>
                        <a href="/rides?tab=one_way"> affordable taxi </a></small></p>
            </div>
        </div>
        <div class="w-full p-4 lg:flex grid grid-rows-1 gap-0 place-items-center">

            <div class="w-full lg:text-left text-center">
                <div class="lg:w-1/4 w-50 mx-auto ">
                    <img src="/cab_images/return.webp" alt="Duracabs Advance" width="100%" class="p-4" />
                </div>
                <h3 class="text-xl font-extrabold main-color-text">
                    Return Taxi Service – Round Trip Cab with Duracabs
                </h3>
                <p>Enjoy a seamless round trip with <strong>Duracabs' return taxi service</strong>. Whether you're
                    heading outstation for business or leisure, our round trip cabs are reliable, punctual, and
                    affordably priced.</p>
                <p><a href="/rides?tab=return" class="cta-button">Reserve your return taxi today</a> and
                    experience
                    comfort, safety, and timely travel with Duracabs, our round trip cabs are reliable, punctual,
                    and priced right.</p>
                <p><small>Links:
                        <a href="/rides?tab=return" class="cta-button">return taxi booking,</a>
                        <a href="/rides?tab=return" class="cta-button">round trip taxi service, </a>
                        <a href="/rides?tab=return" class="cta-button">outstation cab, </a>
                        <a href="/rides?tab=return" class="cta-button">two-way cab in India</a>
                    </small></p>
            </div>

        </div>
        <div class="w-full p-4 lg:flex grid grid-rows-1 gap-0 place-items-center">

            <div class="w-full lg:text-left text-center">
                <div class="lg:w-1/4 w-50 mx-auto ">
                    <img src="/cab_images/local.webp" alt="Duracabs Low Fixed" width="100%" class="p-4" />
                </div>
                <h3 class="text-xl font-extrabold main-color-text">
                    Reliable Local Taxi Service in India – Book a City Cab for Daily Travel and Business with
                    Duracabs

                </h3>
                <p>Need a quick ride in your city? <strong>Duracabs' local taxi service</strong> makes it easy to
                    book a taxi for daily commuting, shopping trips, business meetings, or city tours. With
                    professional drivers and clean vehicles, we make city travel easy and convenient.</p>
                <p><a href="/rides?tab=local" class="cta-button">Find a local taxi now</a> for reliable,
                    affordable, and timely service wherever you are in India.</p>
                <p><small>Links:
                        <a href="/rides?tab=local" class="cta-button">local taxi service, </a>
                        <a href="/rides?tab=local" class="cta-button">city cab booking, </a>
                        <a href="/rides?tab=local" class="cta-button">book taxi near me, </a>
                        <a href="/rides?tab=local" class="cta-button">intra-city travel</a>
                        India</small></p>
            </div>

        </div>
        <div class="w-full p-4 lg:flex grid grid-rows-1 gap-0 place-items-center">

            <div class="w-full lg:text-left text-center">
                <div class="lg:w-1/4 w-50 mx-auto ">
                    <img src="/cab_images/self_drive.webp" alt="Duracabs Tracking" width="100%"
                        class="p-4" />
                </div>
                <h3 class="text-xl font-extrabold main-color-text">
                    Self Drive Car Rental in India – Rent a Car Without Driver from Duracabs

                </h3>
                <p>Enjoy the freedom to travel on your terms with <strong>Duracabs' self drive car rental</strong>.
                    Choose from a range of well-maintained vehicles, ideal for road trips, business travel, or
                    weekend getaways. No driver required — just you and the open road.</p>
                <p><a href="/rides?tab=self_drive" class="cta-button">Rent your self-drive car now</a> and explore
                    India at your own pace with Duracabs.</p>
                <p><small>Links:
                        <a href="/rides?tab=self_drive" class="cta-button"> self drive car rental India, </a>
                        <a href="/rides?tab=self_drive" class="cta-button">rent a car without driver, </a>
                        <a href="/rides?tab=self_drive" class="cta-button"> self drive cars, </a>
                        <a href="/rides?tab=self_drive" class="cta-button">car hire India </a>

                    </small></p>

            </div>


        </div>


</section>

{{-- end Our Services --}}



{{-- Why Dura Cabs --}}

<section id="why-dura-cabs" class="seo-content lg:mx-40 mx-5 bg-white my-10">
    <h2 class="text-3xl font-semibold p-4">
        Why Choose DURA Cabs?
    </h2>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 md:grid-cols-2">
        <div class="w-full p-4 lg:flex grid grid-rows-1 gap-0 place-items-center">
            <div class="lg:w-2/12 w-20">
                <img src="/img/home/24x7.png" alt="Duracabs 24X7" width="100%" class="p-4" />
            </div>
            <div class="w-full lg:text-left text-center">
                <h3 class="text-xl font-extrabold main-color-text">
                    24/7 Taxi Booking Support
                </h3>
                <p>
                    At DURA Cabs, we offer <strong>24x7 customer support</strong> to assist you with your local,
                    outstation, and airport taxi bookings. Our dedicated call center team is available 365 days a
                    year to handle all your travel needs promptly and efficiently.
                </p>
            </div>
        </div>
        <div class="w-full p-4 lg:flex grid grid-rows-1 gap-0 place-items-center">
            <div class="lg:w-2/12 w-20">
                <img src="/img/home/advance.png" alt="Duracabs Advance" width="100%" class="p-4" />
            </div>
            <div class="w-full lg:text-left text-center">
                <h3 class="text-xl font-extrabold main-color-text">
                    Advanced Taxi Booking Software
                </h3>
                <p>Experience the convenience of booking a <strong>one way cab</strong> or <strong>airport
                        taxi</strong> using our advanced technology platform. Our software ensures smooth booking,
                    real-time updates, and faster service delivery for a hassle-free journey.</p>
            </div>

        </div>
        <div class="w-full p-4 lg:flex grid grid-rows-1 gap-0 place-items-center">
            <div class="lg:w-2/12 w-20">
                <img src="/img/home/low_fixed.webp" alt="Duracabs Low Fixed" width="100%" class="p-4" />
            </div>
            <div class="w-full lg:text-left text-center">
                <h3 class="text-xl font-extrabold main-color-text">
                    Low Fixed Prices, No Hidden Charges

                </h3>
                <p>
                    Enjoy <strong>affordable taxi services</strong> with our low fixed pricing model. Whether it's a
                    <strong>Delhi to Agra taxi</strong> or a <strong>local cab near you</strong>, DURA Cabs
                    guarantees transparent fares without any hidden fees — offering you complete peace of mind.
                </p>
            </div>

        </div>
        <div class="w-full p-4 lg:flex grid grid-rows-1 gap-0 place-items-center">
            <div class="lg:w-2/12 w-20">
                <img src="/img/home/tracking.png" alt="Duracabs Tracking" width="100%" class="p-4" />
            </div>
            <div class="w-full lg:text-left text-center">
                <h3 class="text-xl font-extrabold main-color-text">
                    Live Cab Tracking & Real-Time Updates

                </h3>
                <p>
                    Stay informed with <strong>live taxi tracking</strong>. Our GPS-enabled fleet allows you to
                    monitor your cab's location, get accurate driver details, and estimated pickup times, ensuring a
                    secure and dependable travel experience every time.
                </p>
            </div>

        </div>


    </div>


</section>


<img src="/img/home/banner2.webp" width="100%" alt="Duracabs Banner" />


{{-- Why Dura Cabs --}}

{{-- FAQ --}}
<div class="p-2 bg-white mt-3">
    <section class="py-2">
        <div class="container flex flex-col justify-center p-4 mx-auto md:p-8">
            <h2 class="text-center text-slate-800 text-3xl font-bold mb-5">Frequently Asked Questions - DURA Cabs
            </h2>

            <div class="flex flex-col divide-y sm:px-0 lg:px-2 xl:px-4">

                <details>
                    <summary class="py-2 outline-none cursor-pointer focus:underline main-color-text">
                        1. What modes of payment are accepted at DURA Cabs?
                    </summary>
                    <div class="px-4 pb-4">
                        <p>We accept payments via credit cards, debit cards, net banking, UPI, wallets, and cash on
                            pickup. For a faster experience, you can also pay online when booking <strong>local taxi
                                services</strong>, <strong>self-drive rentals</strong>, or <strong>one way
                                taxis</strong> through <a href="https://www.duracabs.com" target="_blank">DURA
                                Cabs</a>.</p>
                    </div>
                </details>

                <details>
                    <summary class="py-2 outline-none cursor-pointer focus:underline main-color-text">
                        2. What is included in the fare while booking a cab?
                    </summary>
                    <div class="px-4 pb-4">
                        <p>The fare covers your car rental tariff, applicable GST, optional accessories, and a
                            security deposit if required. Whether it's an <strong>outstation taxi service</strong>
                            or a <strong>Delhi airport taxi booking</strong>, all charges are transparently listed.
                        </p>
                    </div>
                </details>

                <details>
                    <summary class="py-2 outline-none cursor-pointer focus:underline main-color-text">
                        3. Can I pay directly at the pickup location?
                    </summary>
                    <div class="px-4 pb-4">
                        <p>Yes! Select the "Cash on Delivery" option during your <strong>online cab booking</strong>
                            process and pay the driver directly at pickup.</p>
                    </div>
                </details>

                <details>
                    <summary class="py-2 outline-none cursor-pointer focus:underline main-color-text">
                        4. Who pays for parking, tolls, and interstate permits?
                    </summary>
                    <div class="px-4 pb-4">
                        <p>Customers are responsible for parking fees, toll charges, and interstate entry permits
                            unless these are pre-included in your <strong>one way cab service</strong> package with
                            DURA Cabs.</p>
                    </div>
                </details>

                <details>
                    <summary class="py-2 outline-none cursor-pointer focus:underline main-color-text">
                        5. Are DURA Cabs equipped with FASTag?
                    </summary>
                    <div class="px-4 pb-4">
                        <p>Yes, all vehicles including our <strong>tempo travellers for rent</strong> and
                            <strong>one way taxis</strong> come with FASTag for seamless toll payments, saving you
                            time on highways like Delhi to Agra expressways.
                        </p>
                    </div>
                </details>

                <details>
                    <summary class="py-2 outline-none cursor-pointer focus:underline main-color-text">
                        6. How can I attach my car with DURA Cabs?
                    </summary>
                    <div class="px-4 pb-4">
                        <p>Attaching your taxi to DURA Cabs is simple! Visit <a href="https://www.duracabs.com"
                                target="_blank">duracabs.com</a> or use our Android app, sign up with your details,
                            upload required documents, and start earning with India's leading <strong>car rental
                                solution</strong>.</p>
                    </div>
                </details>

                <details>
                    <summary class="py-2 outline-none cursor-pointer focus:underline main-color-text">
                        7. What is the minimum age requirement for Self-Drive bookings?
                    </summary>
                    <div class="px-4 pb-4">
                        <p>Members must be at least 21 years old with a valid driving license to book a
                            <strong>self-drive car rental</strong> from DURA Cabs.
                        </p>
                    </div>
                </details>

                <details>
                    <summary class="py-2 outline-none cursor-pointer focus:underline main-color-text">
                        8. How do I book a taxi with DURA Cabs?
                    </summary>
                    <div class="px-4 pb-4">
                        <p>Booking your next ride is easy! Visit <a href="https://www.duracabs.com"
                                target="_blank">duracabs.com</a>, select your trip details, choose from a wide
                            range of cars for <strong>local taxi services</strong>, <strong>one way trips</strong>,
                            or <strong>outstation taxi services</strong> at the best rates.</p>
                    </div>
                </details>

            </div>
        </div>
    </section>
</div>


{{-- FAQ --}}



{{-- Category Section Start --}}
<div class="bg-orange-200 py-20">
    <div class="max-w-xl mx-auto">
        <div class="text-center ">
            <div class="relative flex flex-col items-center">
                <h2 class="text-5xl font-bold dark:text-gray-200"> Cab <span class="text-blue-500"> Categories
                    </span> </h2>
                <div class="flex w-40 mt-2 mb-6 overflow-hidden rounded">
                    <div class="flex-1 h-2 bg-blue-200">
                    </div>
                    <div class="flex-1 h-2 bg-blue-400">
                    </div>
                    <div class="flex-1 h-2 bg-blue-600">
                    </div>
                </div>
            </div>
            {{-- <p class="mb-12 text-base text-center text-gray-500">
                    Lorem ipsum, dolor sit amet consectetur adipisicing elit. Delectus magni eius eaque?
                    Pariatur
                    numquam, odio quod nobis ipsum ex cupiditate?
                </p> --}}
        </div>
    </div>

    <div class="max-w-[85rem] px-4 sm:px-6 lg:px-8 mx-auto">
        <div class="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-6">

            @foreach ($categories as $category)
            <a class="group flex flex-col bg-white border shadow-sm rounded-xl hover:shadow-md transition dark:bg-slate-900 dark:border-gray-800 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                href="rides?selected_categories[0]={{ $category->id }}" wire:key='{{ $category->id }}'>
                <div class="p-4 md:p-5">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center">
                            <img class="h-[2.375rem] w-[2.375rem] rounded-full"
                                src="{{ url('storage') }}/{{ $category->image }}"
                                alt="Duracabs - {{ $category->name }}">
                            <div class="ms-3">
                                <h3
                                    class="group-hover:text-blue-600 font-semibold text-gray-800 dark:group-hover:text-gray-400 dark:text-gray-200">
                                    {{ $category->name }}
                                </h3>
                            </div>
                        </div>
                        <div class="ps-3">
                            <svg class="flex-shrink-0 w-5 h-5" xmlns="http://www.w3.org/2000/svg"
                                width="24" height="24" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="m9 18 6-6-6-6" />
                            </svg>
                        </div>
                    </div>
                </div>
            </a>
            @endforeach


        </div>
    </div>

</div>
{{-- Category Section End --}}





{{-- Coutomour Review Section Start --}}

<section class="py-5 font-poppins dark:bg-gray-800 ">
    <div class="max-w-6xl px-4 py-6 mx-auto lg:py-4 md:px-6">
        <div class="max-w-xl mx-auto">
            <div class="text-center ">
                <div class="relative grid grid-flow-col items-center">
                    <div class="flex flex-col justify-center items-center ">
                        <h2 class="text-5xl font-bold dark:text-gray-200"> Customer <span class="text-blue-500">
                                Reviews
                            </span> </h2>
                        <div class="flex w-40 mt-2 mb-6 overflow-hidden rounded">
                            <div class="flex-1 h-2 bg-blue-200">
                            </div>
                            <div class="flex-1 h-2 bg-blue-400">
                            </div>
                            <div class="flex-1 h-2 bg-blue-600">
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>



        <div class="mt-10 lg:mx-20 mx-0">
            <div class="mt-12 flex mx-auto items-center">
                <div x-data="carousel()" x-init="init()" class="relative overflow-hidden group">
                    <div x-ref="container"
                        class="-ml-4 flex overflow-x-scroll scroll-snap-x space-x-4 md:space-y-0">

                        @foreach ($reviews as $review)
                        <div
                            class="ml-4 flex-auto flex-grow-0 flex-shrink-0 w-80 rounded-lg bg-gray-100 items-center justify-center snap-center overflow-hidden shadow-md">
                            <div
                                class="flex flex-wrap items-center justify-center py-2 pb-4 mb-6 space-x-2 border-b dark:border-gray-700">
                                <div class="flex items-center mb-2 md:mb-0 ">
                                    <div class="flex mr-2 rounded-full">
                                        <img src="{{ url('storage') }}/{{ $review->image }}"
                                            alt="Duracabs {{ $review->name }}"
                                            class="object-cover w-12 h-12 rounded-full">
                                    </div>
                                    <div>
                                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-300">
                                            {{ $review->name }}
                                        </h2>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $review->designation }}
                                        </p>
                                        <div class="flex space-x-1 ">
                                            {!! str_repeat(
                                            ' <svg class="h-4 w-4 text-orange-400 dark:text-gray-200" width="51" height="51"
                                                viewBox="0 0 51 51" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M27.0352 1.6307L33.9181 16.3633C34.2173 16.6768 34.5166 16.9903 34.8158 16.9903L50.0779 19.1845C50.9757 19.1845 51.275 20.4383 50.6764 21.0652L39.604 32.3498C39.3047 32.6632 39.3047 32.9767 39.3047 33.2901L41.998 49.2766C42.2973 50.217 41.1002 50.8439 40.5017 50.5304L26.4367 43.3208C26.1375 43.3208 25.8382 43.3208 25.539 43.3208L11.7732 50.8439C10.8754 51.1573 9.97763 50.5304 10.2769 49.59L12.9702 33.6036C12.9702 33.2901 12.9702 32.9767 12.671 32.6632L1.29923 21.0652C0.700724 20.4383 0.999979 19.4979 1.89775 19.4979L17.1598 17.3037C17.459 17.3037 17.7583 16.9903 18.0575 16.6768L24.9404 1.6307C25.539 0.69032 26.736 0.69032 27.0352 1.6307Z"
                                                    fill="currentColor" />
                                            </svg>',
                                            $review->star,
                                            ) !!}


                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div class="main">
                                <div class="card px-10">

                                    <p
                                        class="mb-6 text-base text-gray-500 dark:text-gray-400 text-ellipsis card mcard">
                                        {{ $review->description }}
                                    </p>
                                    <input type="checkbox" class="check" area-label="Read More" />
                                </div>

                            </div>


                        </div>
                        @endforeach



                    </div>
                    <div @click="scrollTo(prev)" x-show="prev !== null"
                        class="hidden md:block absolute top-1/2 left-0 bg-white p-2 rounded-full transition-transform ease-in-out transform -translate-x-full -translate-y-1/2 group-hover:translate-x-0 cursor-pointer">
                        <div>&lt;</div>
                    </div>
                    <div @click="scrollTo(next)" x-show="next !== null"
                        class="hidden md:block absolute top-1/2 right-0 bg-white p-2 rounded-full transition-transform ease-in-out transform translate-x-full -translate-y-1/2 group-hover:translate-x-0 cursor-pointer">
                        <div>&gt;</div>
                    </div>
                </div>
            </div>
        </div>


        <div class="flex flex-row justify-between items-center">

            <div class="py-5">
                <div class="flex space-x-1">
                    <svg class="h-4 w-4 text-gray-800 dark:text-gray-200" width="51" height="51"
                        viewBox="0 0 51 51" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M27.0352 1.6307L33.9181 16.3633C34.2173 16.6768 34.5166 16.9903 34.8158 16.9903L50.0779 19.1845C50.9757 19.1845 51.275 20.4383 50.6764 21.0652L39.604 32.3498C39.3047 32.6632 39.3047 32.9767 39.3047 33.2901L41.998 49.2766C42.2973 50.217 41.1002 50.8439 40.5017 50.5304L26.4367 43.3208C26.1375 43.3208 25.8382 43.3208 25.539 43.3208L11.7732 50.8439C10.8754 51.1573 9.97763 50.5304 10.2769 49.59L12.9702 33.6036C12.9702 33.2901 12.9702 32.9767 12.671 32.6632L1.29923 21.0652C0.700724 20.4383 0.999979 19.4979 1.89775 19.4979L17.1598 17.3037C17.459 17.3037 17.7583 16.9903 18.0575 16.6768L24.9404 1.6307C25.539 0.69032 26.736 0.69032 27.0352 1.6307Z"
                            fill="currentColor" />
                    </svg>
                    <svg class="h-4 w-4 text-gray-800 dark:text-gray-200" width="51" height="51"
                        viewBox="0 0 51 51" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M27.0352 1.6307L33.9181 16.3633C34.2173 16.6768 34.5166 16.9903 34.8158 16.9903L50.0779 19.1845C50.9757 19.1845 51.275 20.4383 50.6764 21.0652L39.604 32.3498C39.3047 32.6632 39.3047 32.9767 39.3047 33.2901L41.998 49.2766C42.2973 50.217 41.1002 50.8439 40.5017 50.5304L26.4367 43.3208C26.1375 43.3208 25.8382 43.3208 25.539 43.3208L11.7732 50.8439C10.8754 51.1573 9.97763 50.5304 10.2769 49.59L12.9702 33.6036C12.9702 33.2901 12.9702 32.9767 12.671 32.6632L1.29923 21.0652C0.700724 20.4383 0.999979 19.4979 1.89775 19.4979L17.1598 17.3037C17.459 17.3037 17.7583 16.9903 18.0575 16.6768L24.9404 1.6307C25.539 0.69032 26.736 0.69032 27.0352 1.6307Z"
                            fill="currentColor" />
                    </svg>
                    <svg class="h-4 w-4 text-gray-800 dark:text-gray-200" width="51" height="51"
                        viewBox="0 0 51 51" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M27.0352 1.6307L33.9181 16.3633C34.2173 16.6768 34.5166 16.9903 34.8158 16.9903L50.0779 19.1845C50.9757 19.1845 51.275 20.4383 50.6764 21.0652L39.604 32.3498C39.3047 32.6632 39.3047 32.9767 39.3047 33.2901L41.998 49.2766C42.2973 50.217 41.1002 50.8439 40.5017 50.5304L26.4367 43.3208C26.1375 43.3208 25.8382 43.3208 25.539 43.3208L11.7732 50.8439C10.8754 51.1573 9.97763 50.5304 10.2769 49.59L12.9702 33.6036C12.9702 33.2901 12.9702 32.9767 12.671 32.6632L1.29923 21.0652C0.700724 20.4383 0.999979 19.4979 1.89775 19.4979L17.1598 17.3037C17.459 17.3037 17.7583 16.9903 18.0575 16.6768L24.9404 1.6307C25.539 0.69032 26.736 0.69032 27.0352 1.6307Z"
                            fill="currentColor" />
                    </svg>
                    <svg class="h-4 w-4 text-gray-800 dark:text-gray-200" width="51" height="51"
                        viewBox="0 0 51 51" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M27.0352 1.6307L33.9181 16.3633C34.2173 16.6768 34.5166 16.9903 34.8158 16.9903L50.0779 19.1845C50.9757 19.1845 51.275 20.4383 50.6764 21.0652L39.604 32.3498C39.3047 32.6632 39.3047 32.9767 39.3047 33.2901L41.998 49.2766C42.2973 50.217 41.1002 50.8439 40.5017 50.5304L26.4367 43.3208C26.1375 43.3208 25.8382 43.3208 25.539 43.3208L11.7732 50.8439C10.8754 51.1573 9.97763 50.5304 10.2769 49.59L12.9702 33.6036C12.9702 33.2901 12.9702 32.9767 12.671 32.6632L1.29923 21.0652C0.700724 20.4383 0.999979 19.4979 1.89775 19.4979L17.1598 17.3037C17.459 17.3037 17.7583 16.9903 18.0575 16.6768L24.9404 1.6307C25.539 0.69032 26.736 0.69032 27.0352 1.6307Z"
                            fill="currentColor" />
                    </svg>
                    <svg class="h-4 w-4 text-gray-800 dark:text-gray-200" width="51" height="51"
                        viewBox="0 0 51 51" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M27.0352 1.6307L33.9181 16.3633C34.2173 16.6768 34.5166 16.9903 34.8158 16.9903L50.0779 19.1845C50.9757 19.1845 51.275 20.4383 50.6764 21.0652L39.604 32.3498C39.3047 32.6632 39.3047 32.9767 39.3047 33.2901L41.998 49.2766C42.2973 50.217 41.1002 50.8439 40.5017 50.5304L26.4367 43.3208C26.1375 43.3208 25.8382 43.3208 25.539 43.3208L11.7732 50.8439C10.8754 51.1573 9.97763 50.5304 10.2769 49.59L12.9702 33.6036C12.9702 33.2901 12.9702 32.9767 12.671 32.6632L1.29923 21.0652C0.700724 20.4383 0.999979 19.4979 1.89775 19.4979L17.1598 17.3037C17.459 17.3037 17.7583 16.9903 18.0575 16.6768L24.9404 1.6307C25.539 0.69032 26.736 0.69032 27.0352 1.6307Z"
                            fill="currentColor" />
                    </svg>
                </div>

                <p class="mt-3 text-sm text-gray-800 dark:text-gray-200">
                    <span class="font-bold">4.6</span> /5 - from 12k reviews
                </p>

                <div class="mt-5">

                    <svg class="h-auto w-16 text-gray-800 dark:text-white" width="80" height="27"
                        viewBox="0 0 80 27" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M20.558 9.74046H11.576V12.3752H17.9632C17.6438 16.0878 14.5301 17.7245 11.6159 17.7245C7.86341 17.7245 4.58995 14.7704 4.58995 10.6586C4.58995 6.62669 7.70373 3.51291 11.6159 3.51291C14.6498 3.51291 16.4063 5.42908 16.4063 5.42908L18.2426 3.51291C18.2426 3.51291 15.8474 0.878184 11.4961 0.878184C5.94724 0.838264 1.67578 5.50892 1.67578 10.5788C1.67578 15.5289 5.70772 20.3592 11.6558 20.3592C16.8854 20.3592 20.7177 16.8063 20.7177 11.4969C20.7177 10.3792 20.558 9.74046 20.558 9.74046Z"
                            fill="currentColor" />
                        <path
                            d="M27.8621 7.78442C24.1894 7.78442 21.5547 10.6587 21.5547 14.012C21.5547 17.4451 24.1096 20.3593 27.9419 20.3593C31.415 20.3593 34.2094 17.7645 34.2094 14.0918C34.1695 9.94011 30.896 7.78442 27.8621 7.78442ZM27.902 10.2994C29.6984 10.2994 31.415 11.7764 31.415 14.0918C31.415 16.4072 29.7383 17.8842 27.902 17.8842C25.906 17.8842 24.3491 16.2874 24.3491 14.0519C24.3092 11.8962 25.8661 10.2994 27.902 10.2994Z"
                            fill="currentColor" />
                        <path
                            d="M41.5964 7.78442C37.9238 7.78442 35.2891 10.6587 35.2891 14.012C35.2891 17.4451 37.844 20.3593 41.6763 20.3593C45.1493 20.3593 47.9438 17.7645 47.9438 14.0918C47.9038 9.94011 44.6304 7.78442 41.5964 7.78442ZM41.6364 10.2994C43.4328 10.2994 45.1493 11.7764 45.1493 14.0918C45.1493 16.4072 43.4727 17.8842 41.6364 17.8842C39.6404 17.8842 38.0835 16.2874 38.0835 14.0519C38.0436 11.8962 39.6004 10.2994 41.6364 10.2994Z"
                            fill="currentColor" />
                        <path
                            d="M55.0475 7.82434C51.6543 7.82434 49.0195 10.7784 49.0195 14.0918C49.0195 17.8443 52.0934 20.3992 54.9676 20.3992C56.764 20.3992 57.6822 19.7205 58.4407 18.8822V20.1198C58.4407 22.2754 57.1233 23.5928 55.1273 23.5928C53.2111 23.5928 52.2531 22.1557 51.8938 21.3573L49.4587 22.3553C50.297 24.1517 52.0135 26.0279 55.0874 26.0279C58.4407 26.0279 60.9956 23.9122 60.9956 19.481V8.18362H58.3608V9.26147C57.6423 8.38322 56.5245 7.82434 55.0475 7.82434ZM55.287 10.2994C56.9237 10.2994 58.6403 11.7365 58.6403 14.1317C58.6403 16.6068 56.9636 17.9241 55.2471 17.9241C53.4507 17.9241 51.774 16.4471 51.774 14.1716C51.8139 11.6966 53.5305 10.2994 55.287 10.2994Z"
                            fill="currentColor" />
                        <path
                            d="M72.8136 7.78442C69.62 7.78442 66.9453 10.2994 66.9453 14.0519C66.9453 18.004 69.9393 20.3593 73.093 20.3593C75.7278 20.3593 77.4044 18.8822 78.3625 17.6048L76.1669 16.1277C75.608 17.006 74.6499 17.8443 73.093 17.8443C71.3365 17.8443 70.5381 16.8862 70.0192 15.9281L78.4423 12.4152L78.0032 11.3772C77.1649 9.46107 75.2886 7.78442 72.8136 7.78442ZM72.8934 10.2196C74.0511 10.2196 74.8495 10.8184 75.2487 11.5768L69.6599 13.9321C69.3405 12.0958 71.097 10.2196 72.8934 10.2196Z"
                            fill="currentColor" />
                        <path d="M62.9531 19.9999H65.7076V1.47693H62.9531V19.9999Z" fill="currentColor" />
                    </svg>

                </div>
            </div>




            <button wire:click="reviewFunction(true)" class="main-color text-white py-3 px-5 rounded-full">
                Submit Review
            </button>

            @if ($showReview)
            <div class="fixed  z-50 inset-0 bg-gray-900 bg-opacity-60 overflow-y-auto h-full w-full px-4 ">
                <div class="relative top-40 mx-auto shadow-xl rounded-md bg-white max-w-md">

                    <div class="flex justify-end p-2">
                        <button type="button" wire:click="reviewFunction(false)"
                            class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                                xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd"
                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="p-6 pt-0 text-center">

                        <form wire:submit="submitReview()" autocomplete="off">
                            <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                                <div class="flex">

                                    <div class="mt-3 w-full text-center sm:ml-4 sm:mt-0 sm:text-left">
                                        <h3 class="text-base font-semibold leading-6 text-gray-900"
                                            id="modal-title ">Submit Review</h3>
                                        <div class="mt-2 w-full">

                                            <div class="flex justify-center items-center">
                                                <button class=""
                                                    wire:mouseover.prevent="changeStarValue(1)">
                                                    <svg class="h-4 w-4   {{ $reviwerStar > 0 ? 'text-orange-400 !h-6 !w-6' : '' }} "
                                                        width="51" height="51" viewBox="0 0 51 51"
                                                        fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path
                                                            d="M27.0352 1.6307L33.9181 16.3633C34.2173 16.6768 34.5166 16.9903 34.8158 16.9903L50.0779 19.1845C50.9757 19.1845 51.275 20.4383 50.6764 21.0652L39.604 32.3498C39.3047 32.6632 39.3047 32.9767 39.3047 33.2901L41.998 49.2766C42.2973 50.217 41.1002 50.8439 40.5017 50.5304L26.4367 43.3208C26.1375 43.3208 25.8382 43.3208 25.539 43.3208L11.7732 50.8439C10.8754 51.1573 9.97763 50.5304 10.2769 49.59L12.9702 33.6036C12.9702 33.2901 12.9702 32.9767 12.671 32.6632L1.29923 21.0652C0.700724 20.4383 0.999979 19.4979 1.89775 19.4979L17.1598 17.3037C17.459 17.3037 17.7583 16.9903 18.0575 16.6768L24.9404 1.6307C25.539 0.69032 26.736 0.69032 27.0352 1.6307Z"
                                                            fill="currentColor" />
                                                    </svg>
                                                </button>
                                                <button class=""
                                                    wire:mouseover.prevent="changeStarValue(2)">
                                                    <svg class="h-4 w-4  {{ $reviwerStar > 1 ? 'text-orange-400 !h-6 !w-6' : '' }}"
                                                        width="51" height="51" viewBox="0 0 51 51"
                                                        fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path
                                                            d="M27.0352 1.6307L33.9181 16.3633C34.2173 16.6768 34.5166 16.9903 34.8158 16.9903L50.0779 19.1845C50.9757 19.1845 51.275 20.4383 50.6764 21.0652L39.604 32.3498C39.3047 32.6632 39.3047 32.9767 39.3047 33.2901L41.998 49.2766C42.2973 50.217 41.1002 50.8439 40.5017 50.5304L26.4367 43.3208C26.1375 43.3208 25.8382 43.3208 25.539 43.3208L11.7732 50.8439C10.8754 51.1573 9.97763 50.5304 10.2769 49.59L12.9702 33.6036C12.9702 33.2901 12.9702 32.9767 12.671 32.6632L1.29923 21.0652C0.700724 20.4383 0.999979 19.4979 1.89775 19.4979L17.1598 17.3037C17.459 17.3037 17.7583 16.9903 18.0575 16.6768L24.9404 1.6307C25.539 0.69032 26.736 0.69032 27.0352 1.6307Z"
                                                            fill="currentColor" />
                                                    </svg>
                                                </button>
                                                <button class=""
                                                    wire:mouseover.prevent="changeStarValue(3)">
                                                    <svg class="h-4 w-4  {{ $reviwerStar > 2 ? 'text-orange-400 !h-6 !w-6' : '' }}"
                                                        width="51" height="51" viewBox="0 0 51 51"
                                                        fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path
                                                            d="M27.0352 1.6307L33.9181 16.3633C34.2173 16.6768 34.5166 16.9903 34.8158 16.9903L50.0779 19.1845C50.9757 19.1845 51.275 20.4383 50.6764 21.0652L39.604 32.3498C39.3047 32.6632 39.3047 32.9767 39.3047 33.2901L41.998 49.2766C42.2973 50.217 41.1002 50.8439 40.5017 50.5304L26.4367 43.3208C26.1375 43.3208 25.8382 43.3208 25.539 43.3208L11.7732 50.8439C10.8754 51.1573 9.97763 50.5304 10.2769 49.59L12.9702 33.6036C12.9702 33.2901 12.9702 32.9767 12.671 32.6632L1.29923 21.0652C0.700724 20.4383 0.999979 19.4979 1.89775 19.4979L17.1598 17.3037C17.459 17.3037 17.7583 16.9903 18.0575 16.6768L24.9404 1.6307C25.539 0.69032 26.736 0.69032 27.0352 1.6307Z"
                                                            fill="currentColor" />
                                                    </svg>
                                                </button>
                                                <button class=""
                                                    wire:mouseover.prevent="changeStarValue(4)">
                                                    <svg class="h-4 w-4  {{ $reviwerStar > 3 ? 'text-orange-400 !h-6 !w-6' : '' }}"
                                                        width="51" height="51" viewBox="0 0 51 51"
                                                        fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path
                                                            d="M27.0352 1.6307L33.9181 16.3633C34.2173 16.6768 34.5166 16.9903 34.8158 16.9903L50.0779 19.1845C50.9757 19.1845 51.275 20.4383 50.6764 21.0652L39.604 32.3498C39.3047 32.6632 39.3047 32.9767 39.3047 33.2901L41.998 49.2766C42.2973 50.217 41.1002 50.8439 40.5017 50.5304L26.4367 43.3208C26.1375 43.3208 25.8382 43.3208 25.539 43.3208L11.7732 50.8439C10.8754 51.1573 9.97763 50.5304 10.2769 49.59L12.9702 33.6036C12.9702 33.2901 12.9702 32.9767 12.671 32.6632L1.29923 21.0652C0.700724 20.4383 0.999979 19.4979 1.89775 19.4979L17.1598 17.3037C17.459 17.3037 17.7583 16.9903 18.0575 16.6768L24.9404 1.6307C25.539 0.69032 26.736 0.69032 27.0352 1.6307Z"
                                                            fill="currentColor" />
                                                    </svg>
                                                </button>
                                                <button class=""
                                                    wire:mouseover.prevent="changeStarValue(5)">
                                                    <svg class="h-4 w-4  {{ $reviwerStar > 4 ? 'text-orange-400 !h-6 !w-6' : '' }}"
                                                        width="51" height="51" viewBox="0 0 51 51"
                                                        fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path
                                                            d="M27.0352 1.6307L33.9181 16.3633C34.2173 16.6768 34.5166 16.9903 34.8158 16.9903L50.0779 19.1845C50.9757 19.1845 51.275 20.4383 50.6764 21.0652L39.604 32.3498C39.3047 32.6632 39.3047 32.9767 39.3047 33.2901L41.998 49.2766C42.2973 50.217 41.1002 50.8439 40.5017 50.5304L26.4367 43.3208C26.1375 43.3208 25.8382 43.3208 25.539 43.3208L11.7732 50.8439C10.8754 51.1573 9.97763 50.5304 10.2769 49.59L12.9702 33.6036C12.9702 33.2901 12.9702 32.9767 12.671 32.6632L1.29923 21.0652C0.700724 20.4383 0.999979 19.4979 1.89775 19.4979L17.1598 17.3037C17.459 17.3037 17.7583 16.9903 18.0575 16.6768L24.9404 1.6307C25.539 0.69032 26.736 0.69032 27.0352 1.6307Z"
                                                            fill="currentColor" />
                                                    </svg>
                                                </button>


                                            </div>

                                            <div>
                                                <label for="name"
                                                    class="block text-sm mb-2 dark:text-white">Name</label>
                                                <div class="relative">
                                                    <input type="text" id="name" wire:model="name"
                                                        class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400 dark:focus:ring-gray-600"
                                                        aria-describedby="email-error">
                                                    @error('name')
                                                    <div
                                                        class=" absolute inset-y-0 end-0 flex items-center pointer-events-none pe-3">
                                                        <svg class="h-5 w-5 text-red-500" width="16"
                                                            height="16" fill="currentColor"
                                                            viewBox="0 0 16 16" aria-hidden="true">
                                                            <path
                                                                d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z" />
                                                        </svg>
                                                    </div>
                                                    @enderror
                                                </div>
                                                @error('name')
                                                <p class=" text-xs text-red-600 mt-2" id="email-error">
                                                    {{ $message }}
                                                </p>
                                                @enderror
                                            </div>

                                            <div>
                                                <label for="designation"
                                                    class="block text-sm mb-2 dark:text-white">Designation</label>
                                                <div class="relative">
                                                    <input type="text" id="designation"
                                                        wire:model="designation"
                                                        class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400 dark:focus:ring-gray-600"
                                                        aria-describedby="email-error">
                                                    @error('designation')
                                                    <div
                                                        class=" absolute inset-y-0 end-0 flex items-center pointer-events-none pe-3">
                                                        <svg class="h-5 w-5 text-red-500" width="16"
                                                            height="16" fill="currentColor"
                                                            viewBox="0 0 16 16" aria-hidden="true">
                                                            <path
                                                                d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z" />
                                                        </svg>
                                                    </div>
                                                    @enderror
                                                </div>
                                                @error('designation')
                                                <p class=" text-xs text-red-600 mt-2" id="email-error">
                                                    {{ $message }}
                                                </p>
                                                @enderror
                                            </div>

                                            <div>
                                                <label for="description"
                                                    class="block text-sm mb-2 dark:text-white">description</label>
                                                <div class="relative">
                                                    <textarea type="text" id="description" wire:model="description"
                                                        class="py-3 px-4 block w-full border border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400 dark:focus:ring-gray-600"
                                                        aria-describedby="email-error">
                                                            </textarea>
                                                    @error('description')
                                                    <div
                                                        class=" absolute inset-y-0 end-0 flex items-center pointer-events-none pe-3">
                                                        <svg class="h-5 w-5 text-red-500" width="16"
                                                            height="16" fill="currentColor"
                                                            viewBox="0 0 16 16" aria-hidden="true">
                                                            <path
                                                                d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 4a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 4zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z" />
                                                        </svg>
                                                    </div>
                                                    @enderror
                                                </div>
                                                @error('description')
                                                <p class=" text-xs text-red-600 mt-2" id="email-error">
                                                    {{ $message }}
                                                </p>
                                                @enderror
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">

                                <button type="submit"
                                    class="inline-flex cursor-pointer w-full justify-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">
                                    Submit</button>
                            </div>
                        </form>

                    </div>

                </div>
            </div>
            @endif


            <script type="text/javascript">
                window.openModal = function(modalId) {
                    document.getElementById(modalId).style.display = 'block'
                    document.getElementsByTagName('body')[0].classList.add('overflow-y-hidden')
                }

                window.closeModal = function(modalId) {
                    document.getElementById(modalId).style.display = 'none'
                    document.getElementsByTagName('body')[0].classList.remove('overflow-y-hidden')
                }

                window.

                // Close all modals when press ESC
                document.onkeydown = function(event) {
                    event = event || window.event;
                    if (event.keyCode === 27) {
                        document.getElementsByTagName('body')[0].classList.remove('overflow-y-hidden')
                        let modals = document.getElementsByClassName('modal');
                        Array.prototype.slice.call(modals).forEach(i => {
                            i.style.display = 'none'
                        })
                    }
                };
            </script>




        </div>
        <!-- End Review -->

    </div>
</section>
{{-- Coutomour Review Section End --}}

<section class="py-0 lg:mx-20 mx-5">
    <div class="max-w-7xl mx-auto">
        <div class=" ">
            <div class="relative flex flex-col items-left">
                <h2 class="text-2xl font-bold dark:text-gray-200"> Popular<span class="text-blue-500">
                        Routes
                    </span> </h2>
                <div class="flex w-40 mt-2 mb-6 overflow-hidden rounded">
                    <div class="flex-1 h-2 bg-blue-200">
                    </div>
                    <div class="flex-1 h-2 bg-blue-400">
                    </div>
                    <div class="flex-1 h-2 bg-blue-600">
                    </div>
                </div>
            </div>
            <p class="mb-12 text-base  text-gray-500">
                Most Visited routes in India
            </p>
        </div>
    </div>
    <div class="mt-10 lg:mx-20 mx-0">
        <div class="mt-12 flex mx-auto items-center">
            <div x-data="carousel()" x-init="init()" class="relative overflow-hidden group">
                <div x-ref="container" class="-ml-4 flex overflow-x-scroll scroll-snap-x space-x-4 md:space-y-0">



                    @foreach ($products as $product)
                    <div class="ml-4 flex-auto flex-grow-0 flex-shrink-0 lg:w-96 w-52 rounded-lg bg-gray-100 items-center justify-center snap-center overflow-hidden shadow-md"
                        wire:key='{{ $product->id }}'>
                        <a href="/route/{{ $product->slug }}" target="_blank" class="">
                            <img src="{{ url('storage') }}/{{ $product->images[0] }}"
                                alt="{{ $product->name }}" class="object-cover w-full h-52 rounded-t-lg">
                        </a>
                        <div class="px-5 py-2 text-center">
                            <a href="/route/{{ $product->slug }}" target="_blank"
                                class="text-lg font-bold tracking-tight main-color-text dark:text-gray-300">
                                {{ $product->name }}
                            </a>

                        </div>

                    </div>
                    @endforeach




                </div>
                <div @click="scrollTo(prev)" x-show="prev !== null"
                    class="hidden md:block absolute top-1/2 left-0 bg-white p-2 rounded-full transition-transform ease-in-out transform -translate-x-full -translate-y-1/2 group-hover:translate-x-0 cursor-pointer">
                    <div>&lt;</div>
                </div>
                <div @click="scrollTo(next)" x-show="next !== null"
                    class="hidden md:block absolute top-1/2 right-0 bg-white p-2 rounded-full transition-transform ease-in-out transform translate-x-full -translate-y-1/2 group-hover:translate-x-0 cursor-pointer">
                    <div>&gt;</div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-10 lg:mx-20 mx-5 mb-10">
    <div class="max-w-7xl mx-auto">
        <div class=" ">
            <div class="relative flex flex-col items-left">
                <h2 class="text-2xl font-bold dark:text-gray-200"> Popular<span class="text-blue-500">
                        Tours
                    </span> </h2>
                <div class="flex w-40 mt-2 mb-6 overflow-hidden rounded">
                    <div class="flex-1 h-2 bg-blue-200">
                    </div>
                    <div class="flex-1 h-2 bg-blue-400">
                    </div>
                    <div class="flex-1 h-2 bg-blue-600">
                    </div>
                </div>
            </div>
            <p class="mb-12 text-base  text-gray-500">
                Most Visited tours in India
            </p>
        </div>
    </div>
    <div class="mt-10 lg:mx-20 mx-0">
        <div class="mt-12 flex mx-auto items-center">
            <div x-data="carousel()" x-init="init()" class="relative overflow-hidden group">
                <div x-ref="container" class="-ml-4 flex overflow-x-scroll scroll-snap-x space-x-4 md:space-y-0">



                    @foreach ($tours as $tour)
                    <a href="{{ $tour->url }}" target="_blank"
                        class="ml-4 flex-auto flex-grow-0 flex-shrink-0 lg:w-96 w-64 rounded-lg bg-gray-100 items-center justify-center snap-center overflow-hidden shadow-md">
                        <div><img src="{{ url('storage') }}/{{ $tour->image }}"
                                alt="Duracabs {{ $tour->name }}"
                                class="object-cover w-full h-52 rounded-t-lg"></div>
                        <div class="px-5 py-2 text-center">
                            <div class="text-lg font-semibold">{{ $tour->name }}</div>

                        </div>
                    </a>
                    @endforeach




                </div>
                <div @click="scrollTo(prev)" x-show="prev !== null"
                    class="hidden md:block absolute top-1/2 left-0 bg-white p-2 rounded-full transition-transform ease-in-out transform -translate-x-full -translate-y-1/2 group-hover:translate-x-0 cursor-pointer">
                    <div>&lt;</div>
                </div>
                <div @click="scrollTo(next)" x-show="next !== null"
                    class="hidden md:block absolute top-1/2 right-0 bg-white p-2 rounded-full transition-transform ease-in-out transform translate-x-full -translate-y-1/2 group-hover:translate-x-0 cursor-pointer">
                    <div>&gt;</div>
                </div>
            </div>
        </div>
    </div>
</section>


</div>