<div class="w-full max-w-[70rem]  px-4 sm:px-6 lg:px-8 mx-auto">
    @section('title', 'Duracabs: Trusted Online cab Booking Services in India')
    @section('description',
        'The most reliable taxi booking services in India. For both one-way & round-trip taxis,
        Duracabs offers online booking services. Now it’s time to book your ride!')
    @section('image', 'https://www.duracabs.com/img/logo/duracabs_logo.jpeg')


    <section class=" font-poppins dark:bg-gray-800 rounded-lg">
        <div class="px-4 py-4 mx-auto max-w-7xl lg:py-1 md:px-6">


            @if ($tab)
                <div class="lg:flex mb-5 -mx-3">
                    <div class="w-full pr-2 flex  p-2 main-color rounded-s">

                        <div class="mr-2 flex items-center">
                            <svg class="lg:w-5 w-5" fill="#fff" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                <path
                                    d="M135.2 117.4L109.1 192l293.8 0-26.1-74.6C372.3 104.6 360.2 96 346.6 96L165.4 96c-13.6 0-25.7 8.6-30.2 21.4zM39.6 196.8L74.8 96.3C88.3 57.8 124.6 32 165.4 32l181.2 0c40.8 0 77.1 25.8 90.6 64.3l35.2 100.5c23.2 9.6 39.6 32.5 39.6 59.2l0 144 0 48c0 17.7-14.3 32-32 32l-32 0c-17.7 0-32-14.3-32-32l0-48L96 400l0 48c0 17.7-14.3 32-32 32l-32 0c-17.7 0-32-14.3-32-32l0-48L0 256c0-26.7 16.4-49.6 39.6-59.2zM128 288a32 32 0 1 0 -64 0 32 32 0 1 0 64 0zm288 32a32 32 0 1 0 0-64 32 32 0 1 0 0 64z" />
                            </svg>
                        </div>


                        <div class="">
                            <h2 class="lg:text-xm text-xl font-medium text-white dark:text-gray-400">Trip Packages For
                            </h2>
                            <div class="flex text-white items-center">
                                <p>{{ $nameTo }} </p>
                                @if ($tab == 'one_way')
                                    <svg width="35" height="25" fill="#fff" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 448 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                        <path
                                            d="M438.6 278.6c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L338.8 224 32 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l306.7 0L233.4 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l160-160z" />
                                    </svg>
                                    <p> </p>

                                    {{ $nameFrom }}
                                @endif
                                @if ($tab == 'return')
                                    <svg width="35" height="25" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 448 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                        <path
                                            d="M438.6 278.6c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L338.8 224 32 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l306.7 0L233.4 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l160-160z" />
                                    </svg>
                                    <p> </p>

                                    {{ $cityFrom }}
                                @endif
                            </div>
                        </div>

                    </div>

                    <div class="w-full pr-2 flex  p-2 md:mt-0 mt-2 rounded-r border-2 border-[#1e9cfd]">
                        <div class="mr-2 flex items-center">
                            <svg class="lg:w-5 w-5" fill="#1e9cfd" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 448 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                <path
                                    d="M64 32C28.7 32 0 60.7 0 96L0 416c0 35.3 28.7 64 64 64l320 0c35.3 0 64-28.7 64-64l0-320c0-35.3-28.7-64-64-64L64 32zM337 209L209 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L303 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z" />
                            </svg>
                        </div>

                        <div class="">
                            <h2 class="lg:text-xl text-xm font-medium main-color-text dark:text-gray-400">TripsType
                            </h2>
                            <div class="flex">
                                <p class="uppercase main-color-text">{{ $tab }} </p>
                            </div>
                        </div>
                    </div>
                </div>

            @endif

            @if ($nameTo)
                <!-- Edit Query Button -->
                <div class="w-full flex justify-center mb-6">
                    <button wire:click="showEditQueryModal"
                        class="inline-flex items-center justify-center px-6 py-3 text-sm font-bold text-blue-600 bg-white border-2 border-blue-600 rounded-full hover:bg-blue-600 hover:text-white focus:ring-4 focus:ring-blue-300 transition duration-200 shadow-md">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                            </path>
                        </svg>
                        <span class="uppercase tracking-wide">Edit Search Query</span>
                    </button>
                </div>
            @endif

            @if ($nameTo)
                <div
                    class="p-4 mb-5 bg-white border border-gray-200 dark:border-gray-900 dark:bg-gray-900 lg:hidden block">
                    <h2 class="text-xl font-medium text-sky-500 dark:text-gray-400">Trip Details</h2>
                    <div class="flex mt-3">
                        <svg width="20" height="10" xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                            <path
                                d="M135.2 117.4L109.1 192l293.8 0-26.1-74.6C372.3 104.6 360.2 96 346.6 96L165.4 96c-13.6 0-25.7 8.6-30.2 21.4zM39.6 196.8L74.8 96.3C88.3 57.8 124.6 32 165.4 32l181.2 0c40.8 0 77.1 25.8 90.6 64.3l35.2 100.5c23.2 9.6 39.6 32.5 39.6 59.2l0 144 0 48c0 17.7-14.3 32-32 32l-32 0c-17.7 0-32-14.3-32-32l0-48L96 400l0 48c0 17.7-14.3 32-32 32l-32 0c-17.7 0-32-14.3-32-32l0-48L0 256c0-26.7 16.4-49.6 39.6-59.2zM128 288a32 32 0 1 0 -64 0 32 32 0 1 0 64 0zm288 32a32 32 0 1 0 0-64 32 32 0 1 0 0 64z" />
                        </svg>
                        &nbsp; <p>PickUp City : </p> &nbsp; &nbsp; <span>{{ $nameTo }}</span>

                    </div>



                    @if ($tab === 'one_way')
                        <div class="flex mt-3">
                            <svg width="20" height="15" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                <path
                                    d="M135.2 117.4L109.1 192l293.8 0-26.1-74.6C372.3 104.6 360.2 96 346.6 96L165.4 96c-13.6 0-25.7 8.6-30.2 21.4zM39.6 196.8L74.8 96.3C88.3 57.8 124.6 32 165.4 32l181.2 0c40.8 0 77.1 25.8 90.6 64.3l35.2 100.5c23.2 9.6 39.6 32.5 39.6 59.2l0 144 0 48c0 17.7-14.3 32-32 32l-32 0c-17.7 0-32-14.3-32-32l0-48L96 400l0 48c0 17.7-14.3 32-32 32l-32 0c-17.7 0-32-14.3-32-32l0-48L0 256c0-26.7 16.4-49.6 39.6-59.2zM128 288a32 32 0 1 0 -64 0 32 32 0 1 0 64 0zm288 32a32 32 0 1 0 0-64 32 32 0 1 0 0 64z" />
                            </svg>
                            &nbsp; <p>Drop City : </p> &nbsp; &nbsp; <span>

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
                        <svg width="30" height="20" xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 448 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                            <path
                                d="M128 0c17.7 0 32 14.3 32 32l0 32 128 0 0-32c0-17.7 14.3-32 32-32s32 14.3 32 32l0 32 48 0c26.5 0 48 21.5 48 48l0 48L0 160l0-48C0 85.5 21.5 64 48 64l48 0 0-32c0-17.7 14.3-32 32-32zM0 192l448 0 0 272c0 26.5-21.5 48-48 48L48 512c-26.5 0-48-21.5-48-48L0 192zm64 80l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm128 0l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm144-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM64 400l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm144-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zm112 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16z" />
                        </svg>
                        &nbsp; <p>PickUp Date : </p> &nbsp; &nbsp; <span>{{ $date }}</span>

                    </div>

                    @if ($tab === 'self_drive' || $tab === 'return')
                        <div class="flex mt-3">
                            <svg width="30" height="20" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 448 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                <path
                                    d="M128 0c17.7 0 32 14.3 32 32l0 32 128 0 0-32c0-17.7 14.3-32 32-32s32 14.3 32 32l0 32 48 0c26.5 0 48 21.5 48 48l0 48L0 160l0-48C0 85.5 21.5 64 48 64l48 0 0-32c0-17.7 14.3-32 32-32zM0 192l448 0 0 272c0 26.5-21.5 48-48 48L48 512c-26.5 0-48-21.5-48-48L0 192zm64 80l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm128 0l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm144-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM64 400l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm144-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zm112 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16z" />
                            </svg>
                            &nbsp; <p>Drop Date : </p> &nbsp; &nbsp; <span>{{ $dateto }}</span>

                        </div>
                    @endif

                    @if ($newTime)
                        <div class="flex mt-3">


                            <svg width="30" height="20" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                <path
                                    d="M464 256A208 208 0 1 1 48 256a208 208 0 1 1 416 0zM0 256a256 256 0 1 0 512 0A256 256 0 1 0 0 256zM232 120l0 136c0 8 4 15.5 10.7 20l96 64c11 7.4 25.9 4.4 33.3-6.7s4.4-25.9-6.7-33.3L280 243.2 280 120c0-13.3-10.7-24-24-24s-24 10.7-24 24z" />
                            </svg>
                            &nbsp; <p>Pickup TIme : </p> &nbsp; &nbsp;
                            <span>{{ $newTime->format('h:i A') }}</span>

                        </div>
                    @endif

                    @if ($endTime)
                        <div class="flex mt-3">


                            <svg width="30" height="20" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                <path
                                    d="M464 256A208 208 0 1 1 48 256a208 208 0 1 1 416 0zM0 256a256 256 0 1 0 512 0A256 256 0 1 0 0 256zM232 120l0 136c0 8 4 15.5 10.7 20l96 64c11 7.4 25.9 4.4 33.3-6.7s4.4-25.9-6.7-33.3L280 243.2 280 120c0-13.3-10.7-24-24-24s-24 10.7-24 24z" />
                            </svg>
                            &nbsp; <p>Pickup TIme : </p> &nbsp; &nbsp;
                            <span>{{ $timeEnd->format('h:i A') }}</span>

                        </div>
                    @endif




                    @if ($tab === 'self_drive' || $tab === 'return')
                        <div class="flex mt-3">
                            <svg width="30" height="20" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 448 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                <path
                                    d="M128 0c17.7 0 32 14.3 32 32l0 32 128 0 0-32c0-17.7 14.3-32 32-32s32 14.3 32 32l0 32 48 0c26.5 0 48 21.5 48 48l0 48L0 160l0-48C0 85.5 21.5 64 48 64l48 0 0-32c0-17.7 14.3-32 32-32zM0 192l448 0 0 272c0 26.5-21.5 48-48 48L48 512c-26.5 0-48-21.5-48-48L0 192zm64 80l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm128 0l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm144-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM64 400l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm144-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zm112 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16z" />
                            </svg>
                            &nbsp; <p>Days : </p> &nbsp; &nbsp; <span>{{ $days === 0 ? 1 : $days + 1 }}</span>

                        </div>
                    @endif

                    @if ($tab === 'local')
                        <div class="flex mt-3">
                            <svg width="30" height="20" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 448 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                <path
                                    d="M128 0c17.7 0 32 14.3 32 32l0 32 128 0 0-32c0-17.7 14.3-32 32-32s32 14.3 32 32l0 32 48 0c26.5 0 48 21.5 48 48l0 48L0 160l0-48C0 85.5 21.5 64 48 64l48 0 0-32c0-17.7 14.3-32 32-32zM0 192l448 0 0 272c0 26.5-21.5 48-48 48L48 512c-26.5 0-48-21.5-48-48L0 192zm64 80l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm128 0l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm144-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM64 400l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm144-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zm112 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16z" />
                            </svg>
                            &nbsp; <p>Plan : </p> &nbsp; &nbsp; <span>{{ $plan }}</span>

                        </div>

                        <div class="flex mt-3">
                            <svg width="30" height="20" xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 448 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                <path
                                    d="M128 0c17.7 0 32 14.3 32 32l0 32 128 0 0-32c0-17.7 14.3-32 32-32s32 14.3 32 32l0 32 48 0c26.5 0 48 21.5 48 48l0 48L0 160l0-48C0 85.5 21.5 64 48 64l48 0 0-32c0-17.7 14.3-32 32-32zM0 192l448 0 0 272c0 26.5-21.5 48-48 48L48 512c-26.5 0-48-21.5-48-48L0 192zm64 80l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm128 0l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm144-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM64 400l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm144-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zm112 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16z" />
                            </svg>
                            &nbsp; <p>Cars : </p> &nbsp; &nbsp; <span>{{ $cars }}</span>

                        </div>
                    @endif

                </div>
            @endif



            <div class="lg:flex flex-wrap mb-24 -mx-3">
                <div class="w-full pr-2 lg:w-1/4 lg:block hidden">

                    @if ($nameTo)
                        <div class="p-2 mb-5 bg-white border border-gray-200 dark:border-gray-900 dark:bg-gray-900 ">
                            <h2 class="text-xl font-medium text-sky-500 dark:text-gray-400">Trip Details</h2>
                            <div class="flex mt-3 justify-evenly">
                                <svg width="20" height="15" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 512 512">
                                    <path
                                        d="M135.2 117.4L109.1 192l293.8 0-26.1-74.6C372.3 104.6 360.2 96 346.6 96L165.4 96c-13.6 0-25.7 8.6-30.2 21.4zM39.6 196.8L74.8 96.3C88.3 57.8 124.6 32 165.4 32l181.2 0c40.8 0 77.1 25.8 90.6 64.3l35.2 100.5c23.2 9.6 39.6 32.5 39.6 59.2l0 144 0 48c0 17.7-14.3 32-32 32l-32 0c-17.7 0-32-14.3-32-32l0-48L96 400l0 48c0 17.7-14.3 32-32 32l-32 0c-17.7 0-32-14.3-32-32l0-48L0 256c0-26.7 16.4-49.6 39.6-59.2zM128 288a32 32 0 1 0 -64 0 32 32 0 1 0 64 0zm288 32a32 32 0 1 0 0-64 32 32 0 1 0 0 64z" />
                                </svg>
                                &nbsp; <p class="text-sm">PickUp City: </p> &nbsp; &nbsp; <span
                                    class="text-sm">{{ $nameTo }}</span>

                            </div>



                            @if ($tab === 'one_way')
                                <div class="flex mt-3">
                                    <svg width="20" height="15" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 512 512">
                                        <path
                                            d="M135.2 117.4L109.1 192l293.8 0-26.1-74.6C372.3 104.6 360.2 96 346.6 96L165.4 96c-13.6 0-25.7 8.6-30.2 21.4zM39.6 196.8L74.8 96.3C88.3 57.8 124.6 32 165.4 32l181.2 0c40.8 0 77.1 25.8 90.6 64.3l35.2 100.5c23.2 9.6 39.6 32.5 39.6 59.2l0 144 0 48c0 17.7-14.3 32-32 32l-32 0c-17.7 0-32-14.3-32-32l0-48L96 400l0 48c0 17.7-14.3 32-32 32l-32 0c-17.7 0-32-14.3-32-32l0-48L0 256c0-26.7 16.4-49.6 39.6-59.2zM128 288a32 32 0 1 0 -64 0 32 32 0 1 0 64 0zm288 32a32 32 0 1 0 0-64 32 32 0 1 0 0 64z" />
                                    </svg>
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
                                <svg width="20" height="15" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 448 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                    <path
                                        d="M128 0c17.7 0 32 14.3 32 32l0 32 128 0 0-32c0-17.7 14.3-32 32-32s32 14.3 32 32l0 32 48 0c26.5 0 48 21.5 48 48l0 48L0 160l0-48C0 85.5 21.5 64 48 64l48 0 0-32c0-17.7 14.3-32 32-32zM0 192l448 0 0 272c0 26.5-21.5 48-48 48L48 512c-26.5 0-48-21.5-48-48L0 192zm64 80l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm128 0l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm144-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM64 400l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm144-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zm112 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16z" />
                                </svg>
                                &nbsp; <p class="text-sm">PickUp Date: </p> &nbsp; &nbsp; <span
                                    class="text-sm">{{ $date }}</span>

                            </div>

                            @if ($tab === 'self_drive' || $tab === 'return')
                                <div class="flex mt-3">
                                    <svg width="20" height="15" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 448 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                        <path
                                            d="M128 0c17.7 0 32 14.3 32 32l0 32 128 0 0-32c0-17.7 14.3-32 32-32s32 14.3 32 32l0 32 48 0c26.5 0 48 21.5 48 48l0 48L0 160l0-48C0 85.5 21.5 64 48 64l48 0 0-32c0-17.7 14.3-32 32-32zM0 192l448 0 0 272c0 26.5-21.5 48-48 48L48 512c-26.5 0-48-21.5-48-48L0 192zm64 80l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm128 0l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm144-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM64 400l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm144-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zm112 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16z" />
                                    </svg>
                                    &nbsp; <p class="text-sm">Drop Date: </p> &nbsp; &nbsp; <span
                                        class="text-sm">{{ $dateto }}</span>

                                </div>
                            @endif

                            @if ($newTime)
                                <div class="flex mt-3">


                                    <svg width="20" height="15" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                        <path
                                            d="M464 256A208 208 0 1 1 48 256a208 208 0 1 1 416 0zM0 256a256 256 0 1 0 512 0A256 256 0 1 0 0 256zM232 120l0 136c0 8 4 15.5 10.7 20l96 64c11 7.4 25.9 4.4 33.3-6.7s4.4-25.9-6.7-33.3L280 243.2 280 120c0-13.3-10.7-24-24-24s-24 10.7-24 24z" />
                                    </svg>
                                    &nbsp; <p class="text-sm">Pickup TIme: </p> &nbsp; &nbsp;
                                    <span class="text-sm">{{ $newTime->format('h:i A') }}</span>

                                </div>
                            @endif

                            @if ($endTime)
                                <div class="flex mt-3">


                                    <svg width="20" height="15" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                        <path
                                            d="M464 256A208 208 0 1 1 48 256a208 208 0 1 1 416 0zM0 256a256 256 0 1 0 512 0A256 256 0 1 0 0 256zM232 120l0 136c0 8 4 15.5 10.7 20l96 64c11 7.4 25.9 4.4 33.3-6.7s4.4-25.9-6.7-33.3L280 243.2 280 120c0-13.3-10.7-24-24-24s-24 10.7-24 24z" />
                                    </svg>
                                    &nbsp; <p class="text-sm">Pickup TIme: </p> &nbsp; &nbsp;
                                    <span class="text-sm">{{ $timeEnd->format('h:i A') }}</span>

                                </div>
                            @endif




                            @if ($tab === 'return')
                                <div class="flex mt-3">
                                    <svg width="20" height="15" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 448 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                        <path
                                            d="M128 0c17.7 0 32 14.3 32 32l0 32 128 0 0-32c0-17.7 14.3-32 32-32s32 14.3 32 32l0 32 48 0c26.5 0 48 21.5 48 48l0 48L0 160l0-48C0 85.5 21.5 64 48 64l48 0 0-32c0-17.7 14.3-32 32-32zM0 192l448 0 0 272c0 26.5-21.5 48-48 48L48 512c-26.5 0-48-21.5-48-48L0 192zm64 80l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm128 0l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm144-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM64 400l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm144-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zm112 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16z" />
                                    </svg>
                                    &nbsp; <p class="text-sm">Days: </p> &nbsp; &nbsp;
                                    <span class="text-sm">{{ $days === 0 ? 1 : $days + 1 }}</span>

                                </div>
                            @endif

                            @if ($tab === 'self_drive')
                                <div class="flex mt-3">
                                    <svg width="20" height="15" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 448 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                        <path
                                            d="M128 0c17.7 0 32 14.3 32 32l0 32 128 0 0-32c0-17.7 14.3-32 32-32s32 14.3 32 32l0 32 48 0c26.5 0 48 21.5 48 48l0 48L0 160l0-48C0 85.5 21.5 64 48 64l48 0 0-32c0-17.7 14.3-32 32-32zM0 192l448 0 0 272c0 26.5-21.5 48-48 48L48 512c-26.5 0-48-21.5-48-48L0 192zm64 80l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm128 0l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm144-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM64 400l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm144-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zm112 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16z" />
                                    </svg>
                                    &nbsp; <p class="text-sm">Hours: </p> &nbsp; &nbsp;
                                    <span class="text-sm">{{ $days === 0 ? 1 : $days }}</span>

                                </div>
                            @endif

                            @if ($tab === 'local')
                                <div class="flex mt-3">
                                    <svg width="20" height="15" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 448 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                        <path
                                            d="M128 0c17.7 0 32 14.3 32 32l0 32 128 0 0-32c0-17.7 14.3-32 32-32s32 14.3 32 32l0 32 48 0c26.5 0 48 21.5 48 48l0 48L0 160l0-48C0 85.5 21.5 64 48 64l48 0 0-32c0-17.7 14.3-32 32-32zM0 192l448 0 0 272c0 26.5-21.5 48-48 48L48 512c-26.5 0-48-21.5-48-48L0 192zm64 80l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm128 0l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm144-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM64 400l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm144-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zm112 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16z" />
                                    </svg>
                                    &nbsp; <p class="text-sm">Plan: </p> &nbsp; &nbsp; <span
                                        class="text-sm">{{ $plan }}</span>

                                </div>

                                <div class="flex mt-3">
                                    <svg width="20" height="15" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 448 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                        <path
                                            d="M128 0c17.7 0 32 14.3 32 32l0 32 128 0 0-32c0-17.7 14.3-32 32-32s32 14.3 32 32l0 32 48 0c26.5 0 48 21.5 48 48l0 48L0 160l0-48C0 85.5 21.5 64 48 64l48 0 0-32c0-17.7 14.3-32 32-32zM0 192l448 0 0 272c0 26.5-21.5 48-48 48L48 512c-26.5 0-48-21.5-48-48L0 192zm64 80l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm128 0l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm144-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM64 400l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm144-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zm112 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16z" />
                                    </svg>
                                    &nbsp; <p class="text-sm">Cars: </p> &nbsp; &nbsp; <span
                                        class="text-sm">{{ $cars }}</span>

                                </div>
                            @endif

                        </div>
                    @endif

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

                    @if (!$tab)

                        {{-- <div class="p-4 mb-5 bg-white border border-gray-200 dark:border-gray-900 dark:bg-gray-900">
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
                        <div class="px-3 mb-4">
                            <div
                                class="items-center justify-between hidden px-3 py-2 bg-gray-100 md:flex dark:bg-gray-900 ">
                                <div class="text-2xl"> Search Result ({{ count($categories) }})</div>
                                <div class="flex items-center justify-between">
                                    <select wire:model.live='sort' id=""
                                        class="block w-40 text-base bg-gray-100 cursor-pointer dark:text-gray-400 dark:bg-gray-900">
                                        <option value="latest">Sort by latest</option>
                                        <option value="price">Sort by Price</option>
                                    </select>


                                </div>

                            </div>
                        </div>
                        <div class="lg:flex grid flex-wrap items-center ">

                            @foreach ($categories2 as $category)
                                <div
                                    class="w-full mb-2 bg-white p-1 border border-gray-300 dark:border-gray-700 shadow-2xl shadow-sky-100">
                                    <div class="lg:flex justify-between">

                                        <div class="flex items-center justify-center ">

                                            <img src="{{ url('storage') }}/{{ $category->image }}"
                                                alt="{{ $category->name }}"
                                                class="object-contain lg:w-30 w-full h-16 mx-auto">

                                        </div>
                                        <div class="p-1 text-center">
                                            <div class=" justify-between gap-2 mb-2">
                                                <h3 class="text-xm font-extrabold uppercase dark:text-gray-400">
                                                    {{ $category->name }}
                                                </h3>

                                            </div>
                                            <div
                                                class="lg:flex grid grid-rows-1 gap-0 place-items-center text-center items-end">
                                                <div class="px-2 grid grid-rows-1 gap-0 place-items-center  mt-0 ">

                                                    {{-- <img src="{{ url('storage', $category->category->image) }}"
                                                    alt="{{ $category->category->name }}"
                                                    class="object-contain w-10 h-10 mx-auto "> --}}

                                                    <div class="flex mt-2">
                                                        <svg width="20" height="20" fill="gold"
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            viewBox="0 0 576 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                                            <path
                                                                d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z" />
                                                        </svg>
                                                        <svg width="20" height="20" fill="gold"
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            viewBox="0 0 576 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                                            <path
                                                                d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z" />
                                                        </svg>
                                                        <svg width="20" height="20" fill="gold"
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            viewBox="0 0 576 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                                            <path
                                                                d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z" />
                                                        </svg>
                                                        <svg width="20" height="20" fill="gold"
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            viewBox="0 0 576 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                                            <path
                                                                d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z" />
                                                        </svg>
                                                        <svg width="20" height="20" fill="gold"
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            viewBox="0 0 576 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                                            <path
                                                                d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z" />
                                                        </svg>
                                                    </div>
                                                    {{-- <p class="text-xs text-center mt-2 w-full">
                                                    {{ $category->name }}
                                                        {{ $category->range }}
                                                    </p> --}}
                                                </div>
                                                <div class="">
                                                    <div class="flex mt-2">
                                                        <div class="p-1">
                                                            <img src="{{ url('/cab_images/plastic-bottle.webp') }}"
                                                                alt="{{ $category->name }}"
                                                                class="object-contain w-6 h-6 mx-auto ">
                                                            <p class="text-xs">Water Bottle</p>
                                                        </div>
                                                        <div class="p-1">
                                                            <img src="{{ url('/cab_images/cursor.webp') }}"
                                                                alt="{{ $category->name }}"
                                                                class="object-contain w-6 h-6 mx-auto ">
                                                            <p class="text-xs">Quick Booking</p>
                                                        </div>
                                                        <div class="p-1">
                                                            <img src="{{ url('/cab_images/driver.webp') }}"
                                                                alt="{{ $category->name }}"
                                                                class="object-contain w-6 h-6 mx-auto ">
                                                            <p class="text-xs">Qualified Driver </p>
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>

                                        </div>
                                        <div class="p-3 text-center lg:w-1/4 w-full">

                                            <div class="text-lg text-center">
                                                {{-- <p
                                                class="text-base font-normal text-gray-500 line-through dark:text-gray-400">
                                                
                                                  {{ Number::currency($category->price, 'INR') }}
                                                 </p> --}}
                                                <p class="text-black-600 text-2xl font-bold dark:text-black-600">



                                                    {{ ($kmValue / 1000) * ($days === 0 ? 2 : 2) > ($days === 0 ? 1 : $days + 1) * $category->range
                                                        ? ($kmValue / 1000) * ($days === 0 ? 2 : 2) * $category->km_charge +
                                                            $category->driver_charge * ($days === 0 ? 0 : $days)
                                                        : ($days === 0 ? 1 : $days + 1) * $category->range * $category->km_charge +
                                                            $category->driver_charge * ($days === 1 ? 1 : $days) }}




















                                                    {{-- compare 
                                                v1 : {{$kmValue / 1000 * ($days === 0? 2 : 2)}}
                                                v2 : {{ $category->range * ($days === 0? 1 : ($days + 1)) }}
                                                da : {{$category->driver_charge * ($days === 0? 0 : ($days))}} --}}






                                                </p>
                                            </div>
                                            <div class="flex justify-center p-1 ">

                                                <a wire:click.prevent='addToCartReturn([{{ $category->id }},"{{ $nameTo }}", "{{ $cityFrom }}", "{{ ($kmValue / 1000) * ($days === 0 ? 2 : 2) > ($days === 0 ? 1 : $days + 1) * $category->range
                                                    ? ($kmValue / 1000) * ($days === 0 ? 2 : 2) * $category->km_charge +
                                                        $category->driver_charge * ($days === 0 ? 0 : $days)
                                                    : ($days === 0 ? 1 : $days + 1) * $category->range * $category->km_charge +
                                                        $category->driver_charge * ($days === 1 ? 1 : $days) }}","{{ $date }}","{{ $dateto }}","{{ $time }}","{{ $tab }}","{{ round(($kmValue / 1000) * ($days === 0 ? 2 : 2) > $category->range * ($days === 0 ? 1 : $days + 1) * $category->range ? ($kmValue / 1000) * ($days === 0 ? 2 : 2) : $category->range * ($days === 0 ? 1 : $days + 1)) }}","{{ $category->new_vehicle }}","{{ $category->pet_friendly }}","{{ $category->roof_career }}"])'
                                                    href="#"
                                                    class="text-white text-xm bg-sky-500 hover:bg-sky-900 p-2 shadow-2xl shadow-black rounded-full flex items-center space-x-2 dark:text-gray-400 hover:text-b-500 dark:hover:text-red-300">
                                                    <span>Book Now </span>
                                                </a>



                                            </div>
                                            <button
                                                onclick="showFareSummary({{ $category->id }}, '{{ $category->name }}', {{ ($kmValue / 1000) * ($days === 0 ? 2 : 2) > ($days === 0 ? 1 : $days + 1) * $category->range
                                                    ? ($kmValue / 1000) * ($days === 0 ? 2 : 2) * $category->km_charge +
                                                        $category->driver_charge * ($days === 0 ? 0 : $days)
                                                    : ($days === 0 ? 1 : $days + 1) * $category->range * $category->km_charge +
                                                        $category->driver_charge * ($days === 1 ? 1 : $days) }}, {{ $category->km_charge }}, {{ $category->driver_charge }}, {{ $category->range }}, {{ round(
                                                    ($kmValue / 1000) * ($days === 0 ? 2 : $days + 1) > ($days === 0 ? 1 : $days + 1) * $category->range
                                                        ? ($kmValue / 1000) * ($days === 0 ? 2 : $days + 1)
                                                        : ($days === 0 ? 1 : $days + 1) * $category->range,
                                                ) }}, {{ $days === 0 ? 1 : $days }})"
                                                class="w-full inline-flex items-center justify-center px-3 py-2 text-xs font-semibold text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 hover:text-blue-700 transition duration-200">
                                               
                                                 Fare Summary
                                                        </button>
                                                        <button wire:click="showEditQueryModal"
                                                            class="w-full inline-flex  items-center justify-center px-3 py-2 text-xs font-semibold text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 hover:text-blue-700 transition duration-200 mt-2">
                                                            
                                                            Trip Modification
                                                </button>
                                        </div>

                                    </div>
                                </div>
                            @endforeach

                        </div>
                        <!-- pagination start -->
                        <div class="flex justify-end mt-6">
                            {{ $rides->links() }}
                        </div>
                        <!-- pagination end -->
                    </div>
                @else
                    <div class="w-full px-3 lg:w-3/4">
                        <div class="px-3 mb-4">
                            <div
                                class="items-center justify-between hidden px-3 py-2 bg-gray-100 md:flex dark:bg-gray-900 ">
                                {{-- <div class="text-2xl"> Search Result ({{ count($prices) }})</div> --}}
                                <div class="flex items-center justify-between">
                                    <select wire:model.live='sort' id=""
                                        class="block w-40 text-base bg-gray-100 cursor-pointer dark:text-gray-400 dark:bg-gray-900">
                                        <option value="latest">Sort by latest</option>
                                        <option value="price">Sort by Price</option>
                                    </select>


                                </div>

                            </div>
                        </div>
                        <div class="lg:flex grid flex-wrap items-center ">

                            {{-- @php
            var_dump($rides);
        @endphp --}}

                            @foreach ($rides as $ride)
                                <div class="bg-slate-300 p-2 my-2 w-full">
                                    <div class=" justify-between gap-2 mb-2">
                                        <h3 class="text-xm font-extrabold uppercase dark:text-gray-400">
                                            {{ $ride->name }}
                                        </h3>

                                    </div>

                                    @foreach ($ride->prices as $price)
                                        <div
                                            class="w-full mb-2 bg-white p-1 border border-gray-300 dark:border-gray-700 shadow-2xl shadow-sky-100">
                                            <div class="lg:flex  justify-between  ">
                                                <div class="relative lg:w-1/4">
                                                    <a href="/route/{{ $ride->slug }}"
                                                        class="flex items-center justify-center h-full w-full">
                                                        <img src="{{ url('storage') }}/{{ $price->category->image }}"
                                                            alt="{{ $ride->name }}" title="{{ $ride->name }}"
                                                            class="object-contain lg:w-30 w-full h-16 mx-auto">
                                                    </a>

                                                </div>

                                                <div class="p-1 text-center block items-center ">

                                                    <div
                                                        class="lg:flex grid grid-rows-1 gap-0 place-items-center text-center items-end">
                                                        <div
                                                            class="px-2 grid grid-rows-1 gap-0 place-items-center  mt-0 ">
                                                            <h4 class="text-sm font-medium dark:text-gray-400">
                                                                {{ $price->category->name }}
                                                            </h4>


                                                            <div class="flex mt-2 ">
                                                                <svg width="20" height="20" fill="gold"
                                                                    xmlns="http://www.w3.org/2000/svg"
                                                                    viewBox="0 0 576 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                                                    <path
                                                                        d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z" />
                                                                </svg>
                                                                <svg width="20" height="20" fill="gold"
                                                                    xmlns="http://www.w3.org/2000/svg"
                                                                    viewBox="0 0 576 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                                                    <path
                                                                        d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z" />
                                                                </svg>
                                                                <svg width="20" height="20" fill="gold"
                                                                    xmlns="http://www.w3.org/2000/svg"
                                                                    viewBox="0 0 576 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                                                    <path
                                                                        d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z" />
                                                                </svg>
                                                                <svg width="20" height="20" fill="gold"
                                                                    xmlns="http://www.w3.org/2000/svg"
                                                                    viewBox="0 0 576 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                                                    <path
                                                                        d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z" />
                                                                </svg>
                                                                <svg width="20" height="20" fill="gold"
                                                                    xmlns="http://www.w3.org/2000/svg"
                                                                    viewBox="0 0 576 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                                                    <path
                                                                        d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z" />
                                                                </svg>
                                                            </div>
                                                            <p class="text-[10px] leading-4 text-center w-3/4 mt-2">
                                                                {{ $price->category->passanger_capacity }} seater <br>
                                                                {{ $price->category->model }} modal

                                                            </p>
                                                        </div>
                                                        <div class="">

                                                            <div class="flex mt-2">
                                                                <div class="px-1">
                                                                    <img src="{{ url('/cab_images/plastic-bottle.webp') }}"
                                                                        alt="{{ $ride->name }}"
                                                                        title="{{ $ride->name }}"
                                                                        class="object-contain w-6 h-6 mx-auto ">
                                                                    <p class="text-xs ">Water Bottle</p>
                                                                </div>
                                                                <div class="px-1">
                                                                    <img src="{{ url('/cab_images/cursor.webp') }}"
                                                                        alt="{{ $ride->name }}"
                                                                        title="{{ $ride->name }}"
                                                                        class="object-contain w-6 h-6 mx-auto ">
                                                                    <p class="text-xs ">Quick Booking</p>
                                                                </div>
                                                                <div class="px-1">
                                                                    @if ($tab === 'self_drive')
                                                                        <img src="{{ url('/cab_images/car.webp') }}"
                                                                            alt="{{ $ride->name }}"
                                                                            title="{{ $ride->name }}"
                                                                            class="object-contain w-6 h-6 mx-auto ">
                                                                        <p class="text-xs ">Clean Car </p>
                                                                    @else
                                                                        <img src="{{ url('/cab_images/driver.webp') }}"
                                                                            alt="{{ $ride->name }}"
                                                                            title="{{ $ride->name }}"
                                                                            class="object-contain w-6 h-6 mx-auto ">
                                                                        <p class="text-xs ">Qualified Driver </p>
                                                                    @endif
                                                                </div>
                                                            </div>

                                                        </div>
                                                    </div>

                                                </div>

                                                @if ($tab === 'one_way')
                                                    <div class="p-3 text-center lg:w-1/4 w-full">

                                                        <div class="text-lg text-center">
                                                            <p
                                                                class="text-base font-normal text-gray-500 line-through dark:text-gray-400">
                                                                {{ Number::currency($price->max_price, 'INR') }}</p>
                                                            <p
                                                                class="text-black-600 text-xm font-bold dark:text-black-600">
                                                                {{ Number::currency($price->price, 'INR') }}</p>
                                                        </div>
                                                        <div class="flex justify-center p-1 ">

                                                            <a wire:click.prevent='addToCartOneWay([{{ $ride->id }},"{{ $time }}","{{ $tab }}","{{ $date }}", "{{ $price->price }}","{{ $ride->name }}", "{{ $price->category->name }}", "{{ $ride->toll_tax }}","{{ $price->category->new_vehicle }}","{{ $price->category->pet_friendly }}","{{ $price->category->roof_career }}"])'
                                                                href="#"
                                                                class="text-white w-full text-center text-xm bg-sky-500 hover:bg-sky-900 p-1 shadow-2xl shadow-black rounded-full flex items-center justify-center space-x-2 dark:text-gray-400 hover:text-b-500 dark:hover:text-red-300">
                                                                <span>Book Now</span>
                                                            </a>



                                                        </div>
                                                        <div class="grid justify-center p-1 ">
                                                        <button
                                                            onclick="showFareSummaryOneWay('{{ $ride->name }}', '{{ $price->category->name }}', {{ $price->price }}, {{ $price->max_price }},{{ $ride->toll_tax }},{{ $ride->km_limit }},{{ $ride->hr_limit }},{{ $ride->extra_km_charge }},{{ $ride->extra_hr_charge }})"
                                                            class="w-full inline-flex items-center justify-center px-3 py-2 text-xs font-semibold text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 hover:text-blue-700 transition duration-200 mt-2">
                                                            
                                                             Fare Summary
                                                        </button>
                                                        <button wire:click="showEditQueryModal"
                                                            class="w-full inline-flex  items-center justify-center px-3 py-2 text-xs font-semibold text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 hover:text-blue-700 transition duration-200 mt-2">
                                                            
                                                            Trip Modify
                                                        </button>
                                                        </div>
                                                    </div>
                                                @endif
                                                @if (!$tab)
                                                    <div class="p-3 text-center lg:w-1/3 w-full">
                                                        <div class="text-lg text-center">
                                                            <p
                                                                class="text-base font-normal text-gray-500 line-through dark:text-gray-400">
                                                                {{ Number::currency($price->max_price, 'INR') }}
                                                            </p>
                                                            <p
                                                                class="text-black-600 text-2xl font-bold dark:text-black-600">
                                                                {{ Number::currency($price->price, 'INR') }}
                                                            </p>
                                                        </div>
                                                        <div class="flex justify-center p-1 ">

                                                            <a wire:click.prevent='addToCart({{ $ride->id }})'
                                                                href="#"
                                                                class="text-white text-xm bg-sky-500 hover:bg-sky-900 p-2 shadow-2xl shadow-black rounded-full flex items-center space-x-2 dark:text-gray-400 hover:text-b-500 dark:hover:text-red-300">
                                                                <span>Book Now</span>
                                                            </a>

                                                        </div>
                                                        <button
                                                            onclick="showFareSummaryOneWay('{{ $ride->name }}', '{{ $price->category->name }}', {{ $price->price }}, {{ $price->max_price }})"
                                                            class="text-xs text-blue-600 underline cursor-pointer hover:text-blue-800">Fare
                                                            Summary</button>
                                                    </div>
                                                @endif


                                                @if ($tab === 'self_drive')
                                                    <div class="p-3 text-center lg:w-1/4 w-full">

                                                        <div class="text-lg text-center">
                                                            <p
                                                                class="text-base font-normal text-gray-500 line-through dark:text-gray-400">
                                                                {{ Number::currency($price->max_price * $days, 'INR') }}
                                                            </p>
                                                            <p
                                                                class="text-black-600 text-2xl font-bold dark:text-black-600">
                                                                {{ Number::currency($price->price * $days, 'INR') }}
                                                            </p>
                                                        </div>
                                                        <div class="flex justify-center p-1 ">

                                                            <a wire:click.prevent='addToCartSelfDrive([{{ $ride->id }},"{{ $time }}","{{ $endTime }}","{{ $tab }}","{{ $date }}","{{ $dateto }}",{{ $price->price * $days }},{{ $price->category->security }}])'
                                                                href="#"
                                                                class="text-white text-xm bg-sky-500 hover:bg-sky-900 p-2 shadow-2xl shadow-black rounded-full flex items-center space-x-2 dark:text-gray-400 hover:text-b-500 dark:hover:text-red-300">
                                                                <span>Book Now</span>
                                                            </a>



                                                        </div>
                                                        <button
                                                            onclick="showFareSummarySelfDrive('{{ $ride->name }}', '{{ $price->category->name }}', {{ $price->price * $days }}, {{ $price->max_price * $days }}, {{ $days }},'{{ $price->category->security }}')"
                                                            class="w-full inline-flex items-center justify-center px-3 py-2 text-xs font-semibold text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 hover:text-blue-700 transition duration-200 mt-2">
                                                            
                                                             Fare Summary
                                                        </button>
                                                        <button wire:click="showEditQueryModal"
                                                            class="w-full inline-flex  items-center justify-center px-3 py-2 text-xs font-semibold text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 hover:text-blue-700 transition duration-200 mt-2">
                                                            
                                                            Trip Modify
                                                        </button>
                                                    </div>
                                                @endif

                                                @if ($tab === 'local')
                                                    <div class="p-3 text-center">

                                                        <div class="text-lg text-center">
                                                            <p
                                                                class="text-base font-normal text-gray-500 line-through dark:text-gray-400">
                                                                {{ Number::currency($price->max_price * $cars, 'INR') }}
                                                            </p>
                                                            <p
                                                                class="text-black-600 text-2xl font-bold dark:text-black-600">
                                                                {{ Number::currency($price->price * $cars, 'INR') }}
                                                            </p>
                                                        </div>
                                                        <div class="flex justify-center p-1 ">

                                                            <a wire:click.prevent='addToCartLocal([{{ $ride->id }},"{{ $time }}","{{ $tab }}","{{ $date }}","{{ $plan }}","{{ $cars }}","{{ $price->price * $cars }}","{{ $ride->name }}", "{{ $price->category->name }}","{{ $ride->toll_tax }}","{{ $ride->category->new_vehicle }}","{{ $ride->category->pet_friendly }}","{{ $ride->category->roof_career }}"])'
                                                                href="#"
                                                                class="text-white text-xm bg-sky-500 hover:bg-sky-900 p-2 shadow-2xl shadow-black rounded-full flex items-center space-x-2 dark:text-gray-400 hover:text-b-500 dark:hover:text-red-300">
                                                                <span>Book Now</span>
                                                            </a>



                                                        </div>
                                                        <button
                                                            onclick="showFareSummaryLocal('{{ $ride->name }}', '{{ $price->category->name }}', {{ $price->price * $cars }}, {{ $price->max_price * $cars }}, {{ $cars }}, '{{ $plan }}',{{ $ride->extra_km_charge }},{{ $ride->extra_hr_charge }},{{ $ride->driver_allowances }})"
                                                            class="w-full inline-flex items-center justify-center px-3 py-2 text-xs font-semibold text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 hover:text-blue-700 transition duration-200 mt-2">
                                                            <svg class="w-4 h-4 mr-1.5" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                                                                </path>
                                                            </svg>
                                                              Fare Summary
                                                        </button>
                                                        <button wire:click="showEditQueryModal"
                                                            class="w-full inline-flex  items-center justify-center px-3 py-2 text-xs font-semibold text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 hover:text-blue-700 transition duration-200 mt-2">
                                                            
                                                            Trip Modify
                                                        </button>
                                                    </div>
                                                @endif

                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach





                        </div>
                        <!-- pagination start -->
                        <div class="flex justify-end mt-6">
                            {{ $rides->links() }}
                        </div>
                        <!-- pagination end -->
                    </div>

                @endif



                <div class="w-full pr-2 lg:w-1/4 lg:hidden block">



                    <div class="p-4 mb-5 bg-white border border-gray-200 dark:border-gray-900 dark:bg-gray-900">
                        <h2 class="text-2xl font-medium text-sky-500 dark:text-gray-400"> Need Help Booking?</h2>
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


                    @if (!$tab)

                        <div class="p-4 mb-5 bg-white border border-gray-200 dark:border-gray-900 dark:bg-gray-900">
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

    <!-- Enhanced Fare Summary Popup -->
    <div id="fareSummaryModal"
        class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div
            class="bg-white rounded-xl shadow-2xl max-w-lg w-full mx-4 transform transition-all duration-300 scale-95">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-4 rounded-t-xl">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                            </path>
                        </svg>
                        <h3 class="text-xl font-bold">Fare Breakdown</h3>
                    </div>
                    <button onclick="closeFareSummary()"
                        class="text-white hover:text-gray-200 transition duration-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
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
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                </path>
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
                        <svg class="w-5 h-5 text-yellow-600 mt-0.5 mr-2 flex-shrink-0" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z">
                            </path>
                        </svg>
                        <div>
                            <h4 class="font-semibold text-yellow-800 mb-2">Important Information:</h4>
                            <div id="fareNotes" class="text-sm text-yellow-700">
                                Extra Charge After: <span id="extraKmLimit"></span> KMS. will be ₹<span
                                    id="extraKmRate"></span>/KM.<br>
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Extra KM charges would be directly paid to the driver.
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="px-6 pb-6">
                <div class="flex space-x-3">
                    <button onclick="closeFareSummary()"
                        class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-3 px-4 rounded-lg font-semibold transition duration-200 flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showFareSummary(categoryId, categoryName, totalPrice, kmCharge, driverCharge, range, totalKm, days) {
            const modal = document.getElementById('fareSummaryModal');

            // Calculate driver charge multiplied by days
            const totalDriverCharge = driverCharge * days;
            // Calculate base fare (total price minus total driver charge)
            const baseFare = totalPrice - totalDriverCharge;
            // Calculate GST on total fare (base fare + driver charge)
            const gstAmount = (totalPrice * 5) / 100;
            const finalTotal = totalPrice + gstAmount;

            // Update popup content
            document.getElementById('carCategory').textContent = categoryName + ' Or Equivalent';
            document.getElementById('baseFare').textContent = '₹ ' + Math.round(baseFare);
            document.getElementById('gstAmount').textContent = '₹ ' + Math.round(gstAmount);
            document.getElementById('totalPrice').textContent = '₹ ' + Math.round(finalTotal);
            document.getElementById('extraKmLimit').textContent = totalKm;
            document.getElementById('extraKmRate').textContent = kmCharge + '.00';

            // Show driver allowance for return trips with driver charge multiplied by days
            document.getElementById('driverAllowanceSection').style.display = 'block';
            document.getElementById('driverAllowance').textContent = '₹ ' + totalDriverCharge + ' (' + days + ' day' + (
                days > 1 ? 's' : '') + ')';
            // Show toll tax section for return
            document.getElementById('tollTaxSection').style.display = 'block';
            document.getElementById('tollTaxStatus').textContent = 'Excluded';

            // Reset notes to default for return trips
            document.getElementById('fareNotes').innerHTML =
                `Extra Charge After: <span id="extraKmLimit">${totalKm}</span> KMS. will be ₹<span id="extraKmRate">${kmCharge}.00</span>/KM.<br>
                There will be a night Allowance of ₹0 for the driver. after 8PM<br>
                <strong>Toll-Tax:</strong> Excluded |
                <strong>Parking:</strong> Extra (if applicable)`;

            // Show modal
            modal.classList.remove('hidden');
        }

        function showFareSummaryOneWay(rideName, categoryName, price, maxPrice, tollTax, kmLimit, hrLimit, extra_km_charge,
            extra_hr_charge) {
            console.log('showFareSummaryOneWay called with:', rideName, categoryName, price, maxPrice);
            const modal = document.getElementById('fareSummaryModal');
            console.log('Modal element:', modal);

            if (!modal) {
                console.error('Modal not found!');
                return;
            }

            // Calculate GST on total amount (add GST on top, don't deduct)
            const baseFare = price;
            const gstAmount = (price * 5) / 100;
            const finalTotal = price + gstAmount;

            // Update popup content for one way with null checks
            const carCategory = document.getElementById('carCategory');
            const baseFareEl = document.getElementById('baseFare');
            const gstAmountEl = document.getElementById('gstAmount');
            const totalPriceEl = document.getElementById('totalPrice');
            const extraKmLimitEl = document.getElementById('extraKmLimit');
            const extraKmRateEl = document.getElementById('extraKmRate');
            const driverAllowanceSection = document.getElementById('driverAllowanceSection');
            const tollTaxSection = document.getElementById('tollTaxSection');
            const tollTaxStatus = document.getElementById('tollTaxStatus');

            console.log('Elements check:', {
                carCategory,
                baseFareEl,
                gstAmountEl,
                totalPriceEl,
                extraKmLimitEl,
                extraKmRateEl,
                driverAllowanceSection,
                tollTaxSection,
                tollTaxStatus
            });

            if (carCategory) carCategory.textContent = categoryName + ' Or Equivalent';
            if (baseFareEl) baseFareEl.textContent = '₹ ' + Math.round(baseFare);
            if (gstAmountEl) gstAmountEl.textContent = '₹ ' + Math.round(gstAmount);
            if (totalPriceEl) totalPriceEl.textContent = '₹ ' + Math.round(tollTax == 0 ? finalTotal : finalTotal +
                tollTax);

            // Hide driver allowance for one way trips
            if (driverAllowanceSection) driverAllowanceSection.style.display = 'none';
            // Show toll tax section for one way
            if (tollTaxSection) tollTaxSection.style.display = 'block';
            if (tollTaxStatus) tollTaxStatus.textContent = `${tollTax == 0 ? 'Included' : tollTax }`;

            // Reset notes to default for one way
            const fareNotes = document.getElementById('fareNotes');
            if (fareNotes) {
                fareNotes.innerHTML =
                    `One way trip - One Pickup & One Drop (Extra PickUp & Drop Is Chargeable)<br>
                    <strong>Toll-Tax:</strong> ${tollTax == 0 ? 'Included' : tollTax } |
                    <strong>Parking:</strong> Extra (if applicable)
                    <br>Extra Charge After: <span id="extraKmLimit">${kmLimit}</span> KMS. will be ₹<span id="extraKmRate">${extra_km_charge}.00</span>/KM.<br>
                    Extra Charge After: <span id="extraHrLimit">${hrLimit}</span> HRS. will be ₹<span id="extraHrRate">${extra_hr_charge}.00</span>/HR.
                    `;
            }

            // Show modal
            console.log('About to show modal, current classes:', modal.className);
            modal.classList.remove('hidden');
            console.log('Modal shown, new classes:', modal.className);
        }

        function showFareSummarySelfDrive(rideName, categoryName, price, maxPrice, days, security) {
            console.log('showFareSummarySelfDrive called with:', rideName, categoryName, price, maxPrice, days, security);
            const modal = document.getElementById('fareSummaryModal');

            if (!modal) {
                console.error('Modal not found!');
                return;
            }

            // Calculate GST
            const gstAmount = (price * 5) / 105;
            const baseFare = price;

            // Update popup content for self drive with null checks
            const carCategory = document.getElementById('carCategory');
            const baseFareEl = document.getElementById('baseFare');
            const gstAmountEl = document.getElementById('gstAmount');
            const totalPriceEl = document.getElementById('totalPrice');
            const extraKmLimitEl = document.getElementById('extraKmLimit');
            const extraKmRateEl = document.getElementById('extraKmRate');
            const driverAllowanceSection = document.getElementById('driverAllowanceSection');
            const tollTaxSection = document.getElementById('tollTaxSection');
            const tollTaxStatus = document.getElementById('tollTaxStatus');
            const fareNotes = document.getElementById('fareNotes');

            console.log('Elements check:', {
                carCategory,
                baseFareEl,
                gstAmountEl,
                totalPriceEl,
                extraKmLimitEl,
                extraKmRateEl,
                driverAllowanceSection,
                tollTaxSection,
                tollTaxStatus,
                fareNotes
            });

            if (carCategory) carCategory.textContent = categoryName + ' Or Equivalent';
            if (baseFareEl) baseFareEl.textContent = '₹ ' + Math.round(baseFare);
            if (gstAmountEl) gstAmountEl.textContent = '₹ ' + Math.round(gstAmount);
            if (totalPriceEl) totalPriceEl.textContent = '₹ ' + Math.round(price + gstAmount);
            if (extraKmLimitEl) extraKmLimitEl.textContent = '0';
            if (extraKmRateEl) extraKmRateEl.textContent = '10.00';

            // Hide driver allowance for self drive trips (no driver included)
            if (driverAllowanceSection) driverAllowanceSection.style.display = 'none';
            // Show toll tax section for self drive
            if (tollTaxSection) tollTaxSection.style.display = 'block';
            if (tollTaxStatus) tollTaxStatus.textContent = 'Excluded';

            // Update notes for self drive (remove driver references)
            if (fareNotes) {
                fareNotes.innerHTML =
                    `Self Drive for ${days} hour(s)<br>
                    No driver included - you drive yourself<br>
                    <strong>Toll-Tax:</strong> Excluded |
                    <strong>Parking:</strong> Extra (if applicable) <br/>
                    <strong>Security:</strong> ₹${security} Refundable Deposit`;
            }

            // Ensure modal is properly reset before showing
            modal.style.display = '';
            modal.classList.remove('hidden');
            console.log('Modal shown, classes:', modal.className);
        }

        function showFareSummaryLocal(rideName, categoryName, price, maxPrice, cars, plan, extra_km_charge, extra_hr_charge,
            driver_allowances) {
            console.log('showFareSummaryLocal called with:', rideName, categoryName, price, maxPrice, cars, plan);
            const modal = document.getElementById('fareSummaryModal');

            if (!modal) {
                console.error('Modal not found!');
                return;
            }

            // Calculate GST
            const gstAmount = (price * 5) / 105;
            const baseFare = price;

            // Update popup content for local with null checks
            const carCategory = document.getElementById('carCategory');
            const baseFareEl = document.getElementById('baseFare');
            const gstAmountEl = document.getElementById('gstAmount');
            const totalPriceEl = document.getElementById('totalPrice');
            const extraKmLimitEl = document.getElementById('extraKmLimit');
            const extraKmRateEl = document.getElementById('extraKmRate');
            const driverAllowanceSection = document.getElementById('driverAllowanceSection');
            const tollTaxSection = document.getElementById('tollTaxSection');
            const tollTaxStatus = document.getElementById('tollTaxStatus');
            const fareNotes = document.getElementById('fareNotes');

            console.log('Elements check:', {
                carCategory,
                baseFareEl,
                gstAmountEl,
                totalPriceEl,
                extraKmLimitEl,
                extraKmRateEl,
                driverAllowanceSection,
                tollTaxSection,
                tollTaxStatus,
                fareNotes
            });

            if (carCategory) carCategory.textContent = categoryName + ' Or Equivalent';
            if (baseFareEl) baseFareEl.textContent = '₹ ' + Math.round(baseFare);
            if (gstAmountEl) gstAmountEl.textContent = '₹ ' + Math.round(gstAmount);
            if (totalPriceEl) totalPriceEl.textContent = '₹ ' + Math.round(gstAmount + price);
            if (extraKmLimitEl) extraKmLimitEl.textContent = '0';
            if (extraKmRateEl) extraKmRateEl.textContent = '10.00';

            // Hide driver allowance for local trips
            if (driverAllowanceSection) driverAllowanceSection.style.display = 'none';
            // Show toll tax section for local
            if (tollTaxSection) tollTaxSection.style.display = 'block';
            if (tollTaxStatus) tollTaxStatus.textContent = 'Excluded';

            // Update notes for local
            if (fareNotes) {
                fareNotes.innerHTML =
                    `Local trip with ${plan} plan for ${cars} car(s)<br>
                    <strong>Toll-Tax:</strong> Excluded |
                    <strong>Parking:</strong> Extra (if applicable)
                    <br>Extra Km Charge ₹<span id="extraKmRate">${extra_km_charge}.00</span>/KM.<br>
                    Extra Hour Charge ₹<span id="extraHrRate">${extra_hr_charge}.00</span>/HR.
                    <br>Driver Allowance will be after 8 pm till 4 am ₹<span id="driverAllowance">${driver_allowances == undefined ? "300" : driver_allowances }.00</span>`;
            }

            // Ensure modal is properly reset before showing
            modal.style.display = '';
            modal.classList.remove('hidden');
            console.log('Modal shown, classes:', modal.className);
        }

        function closeFareSummary() {
            const modal = document.getElementById('fareSummaryModal');
            if (!modal) {
                console.error('Modal not found during close!');
                return;
            }

            console.log('Closing modal, current classes:', modal.className);
            modal.classList.add('hidden');
            // Reset modal state completely
            modal.style.display = '';
            console.log('Modal closed, final classes:', modal.className);
        }

        // Initialize modal event listeners only once
        let modalInitialized = false;

        function initializeModalEventListeners() {
            if (modalInitialized) return;

            const modal = document.getElementById('fareSummaryModal');
            if (!modal) {
                console.error('Modal not found for event listener initialization');
                return;
            }

            // Close modal when clicking outside
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    console.log('Modal background clicked, closing...');
                    closeFareSummary();
                }
            });

            // Close modal with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const modal = document.getElementById('fareSummaryModal');
                    if (modal && !modal.classList.contains('hidden')) {
                        console.log('Escape key pressed, closing modal...');
                        closeFareSummary();
                    }
                }
            });

            modalInitialized = true;
            console.log('Modal event listeners initialized');
        }

        // Initialize when DOM is ready
        document.addEventListener('DOMContentLoaded', initializeModalEventListeners);

        // Also initialize immediately if DOM is already loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeModalEventListeners);
        } else {
            initializeModalEventListeners();
        }
    </script>

    <!-- Edit Query Modal -->
    @if ($showEditModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
            wire:click.self="$set('showEditModal', false)">
            <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-lg bg-white">
                <!-- Modal Header -->
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Edit Your Search</h3>
                    <button wire:click="$set('showEditModal', false)" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Simple Tabs (like homepage) -->
                <ul class="flex text-sm font-medium text-center text-gray-500 rounded-lg shadow mb-4">
                    <li class="w-1/4">
                        <button wire:click='changeEditTab("one_way")'
                            class="flex items-center justify-center w-full p-3 text-gray-900 bg-gray-100 border-r border-gray-200 rounded-l-lg {{ $edit_ride_type === 'one_way' ? 'main-color text-white font-extrabold' : 'hover:bg-gray-50' }}">


                            <span class="font-bold text-[9px] lg:text-xs uppercase">One Way</span>
                        </button>
                    </li>
                    <li class="w-1/4">
                        <button wire:click='changeEditTab("return")'
                            class="flex items-center justify-center w-full p-3 text-gray-900 bg-gray-100 border-r border-gray-200 {{ $edit_ride_type === 'return' ? 'main-color text-white font-extrabold' : 'hover:bg-gray-50' }}">

                            <br />
                            <span class="font-bold text-[9px] lg:text-xs uppercase">Return</span>
                        </button>
                    </li>
                    <li class="w-1/4">
                        <button wire:click='changeEditTab("local")'
                            class="flex items-center justify-center w-full p-3 text-gray-900 bg-gray-100 border-r border-gray-200 {{ $edit_ride_type === 'local' ? 'main-color text-white font-extrabold' : 'hover:bg-gray-50' }}">

                            <br />
                            <span class="font-bold text-[9px] lg:text-xs uppercase">Local</span>
                        </button>
                    </li>
                    <li class="w-1/4">
                        <button wire:click='changeEditTab("self_drive")'
                            class="flex items-center justify-center w-full p-3 text-gray-900 bg-gray-100 rounded-r-lg {{ $edit_ride_type === 'self_drive' ? 'main-color text-white font-extrabold' : 'hover:bg-gray-50' }}">

                            <br />
                            <span class="font-bold text-[9px] lg:text-xs uppercase">Self Drive</span>
                        </button>
                    </li>
                </ul>

                <!-- One Way Form (like homepage) -->
                <form wire:submit='updateQuery' autocomplete="off"
                    class="{{ $edit_ride_type == 'one_way' ? 'block' : 'hidden' }} bg-white rounded-xl pt-5 px-2">
                    <div class="lg:flex grid items-center mx-0 w-full">
                        <!-- From City -->
                        <div class="relative lg:w-1/4 md:mt-0 mt-4">
                            <label><span class="font-semibold">From City</span></label>
                            <input type="text" wire:model.live='edit_query_search' placeholder="From City.."
                                class="lg:mt-3 bg-gray-50 border border-gray-300 lg:py-10 text-black font-extrabold lg:text-2xl text-xm focus:ring-blue-500 focus:border-blue-500 block w-full ps-2 p-2.5" />

                            @if (!empty($edit_query_search) && !empty($edit_cities_from))
                                <div class="absolute z-10 w-full bg-white rounded shadow-lg">
                                    @foreach ($edit_cities_from as $city)
                                        <button type="button"
                                            wire:click='editUpdate1("{{ $city['name'] }}", {{ $city['id'] }})'
                                            class="block w-full text-left px-4 py-2 text-sm hover:bg-gray-100 cursor-pointer border-b border-gray-100 last:border-b-0">
                                            {{ $city['name'] }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                            @error('edit_query_search')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- To City -->
                        <div class="relative lg:w-1/4 md:mt-0 mt-4">
                            <label><span class="font-semibold">To City</span></label>
                            <input type="text" wire:model.live='edit_query2_search' placeholder="To City.."
                                class="lg:mt-3 bg-gray-50 border border-gray-300 lg:py-10 text-black font-extrabold lg:text-2xl text-xm focus:ring-blue-500 focus:border-blue-500 block w-full ps-2 p-2.5" />

                            @if (!empty($edit_query2_search) && !empty($edit_cities_to))
                                <div class="absolute z-10 w-full bg-white rounded shadow-lg">
                                    @foreach ($edit_cities_to as $city)
                                        <button type="button"
                                            wire:click='editUpdate2("{{ $city['name'] }}", {{ $city['id'] }})'
                                            class="block w-full text-left px-4 py-2 text-sm hover:bg-gray-100 cursor-pointer border-b border-gray-100 last:border-b-0">
                                            {{ $city['name'] }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                            @error('edit_query2_search')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- PickUp Date -->
                        <div class="relative lg:w-1/4 md:mt-0 mt-4">
                            <label><span class="font-semibold">PickUp Date</span></label>
                            <input type="date" wire:model='edit_date'
                                class="lg:mt-3 bg-gray-50 border border-gray-300 lg:py-10 text-black font-extrabold lg:text-2xl text-xm focus:ring-blue-500 focus:border-blue-500 block w-full ps-2 p-2.5" />
                            @error('edit_date')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- PickUp Time -->
                        <div class="relative lg:w-1/4 md:mt-0 mt-4">
                            <label><span class="font-semibold">PickUp Time</span></label>
                            <input type="time" wire:model='edit_time'
                                class="lg:mt-3 bg-gray-50 border border-gray-300 lg:py-10 text-black font-extrabold lg:text-2xl text-xm focus:ring-blue-500 focus:border-blue-500 block w-full ps-2 p-2.5" />
                            @error('edit_time')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </form>

                <!-- Return Form -->
                <form wire:submit='updateQuery' autocomplete="off"
                    class="{{ $edit_ride_type == 'return' ? 'block' : 'hidden' }}">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <!-- From City -->
                        <div class="relative">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">From City</label>
                            <input type="text" wire:model.live='edit_queryFrom_search' placeholder="From City.."
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />

                            @if (!empty($edit_queryFrom_search) && !empty($edit_dataFrom))
                                <div
                                    class="absolute z-20 w-full bg-white rounded-lg shadow-lg mt-1 max-h-40 overflow-y-auto">
                                    @foreach ($edit_dataFrom as $prediction)
                                        <button type="button"
                                            wire:click='editUpdateCityFrom("{{ $prediction['description'] }}")'
                                            class="block w-full text-left px-4 py-2 text-sm hover:bg-blue-500 hover:text-white cursor-pointer border-b border-gray-100 last:border-b-0">
                                            {{ $prediction['description'] }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                            @error('edit_queryFrom_search')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- To City -->
                        <div class="relative">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">To City</label>
                            <input type="text" wire:model.live='edit_queryTo_search' placeholder="To City.."
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />

                            @if (!empty($edit_queryTo_search) && !empty($edit_dataTo))
                                <div
                                    class="absolute z-20 w-full bg-white rounded-lg shadow-lg mt-1 max-h-40 overflow-y-auto">
                                    @foreach ($edit_dataTo as $prediction)
                                        <button type="button"
                                            wire:click='editUpdateCityTo("{{ $prediction['description'] }}")'
                                            class="block w-full text-left px-4 py-2 text-sm hover:bg-blue-500 hover:text-white cursor-pointer border-b border-gray-100 last:border-b-0">
                                            {{ $prediction['description'] }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                            @error('edit_queryTo_search')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- PickUp Date -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">PickUp Date</label>
                            <input type="date" wire:model='edit_date'
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                            @error('edit_date')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Return Date -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Return Date</label>
                            <input type="date" wire:model='edit_dateto'
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                            @error('edit_dateto')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- PickUp Time -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">PickUp Time</label>
                            <input type="time" wire:model='edit_time'
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                            @error('edit_time')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </form>

                <!-- Local Form -->
                <form wire:submit='updateQuery' autocomplete="off"
                    class="{{ $edit_ride_type == 'local' ? 'block' : 'hidden' }}">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- From City -->
                        <div class="relative">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">From City</label>
                            <input type="text" wire:model.live='edit_queryLocal' placeholder="From City.."
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />

                            @if (!empty($edit_queryLocal) && !empty($edit_cities_from))
                                <div
                                    class="absolute z-20 w-full bg-white rounded-lg shadow-lg mt-1 max-h-40 overflow-y-auto">
                                    @foreach ($edit_cities_from as $city)
                                        <button type="button"
                                            wire:click='editUpdate3("{{ $city['name'] }}", {{ $city['id'] }})'
                                            class="block w-full text-left px-4 py-2 text-sm hover:bg-blue-500 hover:text-white cursor-pointer border-b border-gray-100 last:border-b-0">
                                            {{ $city['name'] }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                            @error('edit_queryLocal')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Select Plan -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Select Plan</label>
                            <select wire:model='edit_plan'
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="4 Hour / 40 Km">4 Hour / 40 Km</option>
                                <option value="8 Hour / 80 Km">8 Hour / 80 Km</option>
                                <option value="12 Hour / 120 Km">12 Hour / 120 Km</option>
                            </select>
                            @error('edit_plan')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Number of Cars -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Number of Cars</label>
                            <input type="number" wire:model='edit_cars' min="1" placeholder="No. of cars"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                            @error('edit_cars')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- PickUp Date -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">PickUp Date</label>
                            <input type="date" wire:model='edit_date'
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                            @error('edit_date')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- PickUp Time -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">PickUp Time</label>
                            <input type="time" wire:model='edit_time'
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                            @error('edit_time')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </form>

                <!-- Self Drive Form -->
                <form wire:submit='updateQuery' autocomplete="off"
                    class="{{ $edit_ride_type == 'self_drive' ? 'block' : 'hidden' }}">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <!-- From City -->
                        <div class="relative">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">From City</label>
                            <input type="text" wire:model.live='edit_querySelfDrive' placeholder="From City.."
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />

                            @if (!empty($edit_querySelfDrive) && !empty($edit_cities_from))
                                <div
                                    class="absolute z-20 w-full bg-white rounded-lg shadow-lg mt-1 max-h-40 overflow-y-auto">
                                    @foreach ($edit_cities_from as $city)
                                        <button type="button"
                                            wire:click='editUpdate4("{{ $city['name'] }}", {{ $city['id'] }})'
                                            class="block w-full text-left px-4 py-2 text-sm hover:bg-blue-500 hover:text-white cursor-pointer border-b border-gray-100 last:border-b-0">
                                            {{ $city['name'] }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                            @error('edit_querySelfDrive')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- PickUp Date -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">PickUp Date</label>
                            <input type="date" wire:model='edit_date'
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                            @error('edit_date')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Return Date -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Return Date</label>
                            <input type="date" wire:model='edit_dateto'
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                            @error('edit_dateto')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- PickUp Time -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">PickUp Time</label>
                            <input type="time" wire:model='edit_time'
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                            @error('edit_time')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- End Time -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">End Time</label>
                            <input type="time" wire:model='edit_endTime'
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                            @error('edit_endTime')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </form>

                <!-- Simple Submit Button (like homepage) -->
                <div class="w-full flex justify-center mt-6">
                    <button type="button" wire:click="updateQuery" wire:loading.attr="disabled"
                        class="p-2.5 uppercase my-2 w-60 text-2xl font-bold text-white main-color rounded-full border border-sky-600 hover:bg-sky-600 focus:ring-4 focus:outline-none focus:ring-blue-300 disabled:opacity-50">
                        <span wire:loading.remove wire:target="updateQuery">Update Search</span>
                        <span wire:loading wire:target="updateQuery">Searching...</span>
                    </button>
                </div>
            </div>
        </div>
</div>
@endif


</div>
