<footer class=" ">
    <div class="w-full   px-4 sm:px-6 lg:pt-10 hidden lg:block bg-gray-600">
        <!-- Grid -->
        <h3 class="text-white text-2xl font-bold text-justify ">Car Rental</h3>
<div class="mx-20">
    @foreach ($carRental as $index => $cars)
        <a href="{{ $cars->url }}" 
           class="text-white text-base hover:underline pe-3 car-rental-item {{ $index >= 20 ? 'hidden' : '' }}">
            {{ $cars->title }} |
        </a>
    @endforeach

    @if (count($carRental) > 20)
        <button id="readMoreBtn" 
                class="text-blue-400 hover:underline mt-2"
                onclick="showMoreItems()">Read More</button>
    @endif
</div>

            <div class="mt-5">
    <h3 class="text-white text-2xl font-bold text-justify">Top Routes</h3>
    <div class="mx-20">
        @foreach ($topRoutes as $index => $cars)
            <a href="{{ $cars->url }}" 
               class="text-white text-base hover:underline pe-3 top-route-item {{ $index >= 20 ? 'hidden' : '' }}">
                {{ $cars->title }} |
            </a>
        @endforeach

        @if (count($topRoutes) > 20)
            <button id="readMoreRoutesBtn" 
                    class="text-blue-400 hover:underline mt-2"
                    onclick="showMoreRoutes()">Read More</button>
        @endif
    </div>
</div>

<script>
    function showMoreRoutes() {
        document.querySelectorAll('.top-route-item.hidden').forEach(el => el.classList.remove('hidden'));
        document.getElementById('readMoreRoutesBtn').style.display = 'none';
    }
</script>


          
<div class="w-full   px-4 sm:px-6 lg:pt-10 hidden lg:block bg-gray-600">
        <hr class="my-10" />

        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-6">
            <div class="col-span-1 lg:col-span-2">
                <a class="flex-none text-xl font-semibold text-white dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                    href="/" aria-label="Duracabs" name="Duracabs" alt="Duracabs">
                    <img src="/img/logo/duracabs_logo.svg" width="250" alt="Duracabs Logo" title="Duracabs Logo" />
                </a>
                <h2 class="text-2xl font-extrabold text-white">Dura Cabs Services</h2>
                <div class="flex text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="me-2" fill="#fff" width="10"
                        viewBox="0 0 384 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                        <path
                            d="M215.7 499.2C267 435 384 279.4 384 192C384 86 298 0 192 0S0 86 0 192c0 87.4 117 243 168.3 307.2c12.3 15.3 35.1 15.3 47.4 0zM192 128a64 64 0 1 1 0 128 64 64 0 1 1 0-128z" />
                    </svg>
                    AGRA | DELHI | JAIPUR | CHANDIGARH
                </div>
                <div class="flex text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="me-2" fill="#fff" width="10"
                        viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                        <path
                            d="M164.9 24.6c-7.7-18.6-28-28.5-47.4-23.2l-88 24C12.1 30.2 0 46 0 64C0 311.4 200.6 512 448 512c18 0 33.8-12.1 38.6-29.5l24-88c5.3-19.4-4.6-39.7-23.2-47.4l-96-40c-16.3-6.8-35.2-2.1-46.3 11.6L304.7 368C234.3 334.7 177.3 277.7 144 207.3L193.3 167c13.7-11.2 18.4-30 11.6-46.3l-40-96z" />
                    </svg>
                    <a href="tel:+917088873331">+91-7088873331</a>
                </div>
                <div class="flex text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="me-2" fill="#fff" width="10"
                        viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                        <path
                            d="M48 64C21.5 64 0 85.5 0 112c0 15.1 7.1 29.3 19.2 38.4L236.8 313.6c11.4 8.5 27 8.5 38.4 0L492.8 150.4c12.1-9.1 19.2-23.3 19.2-38.4c0-26.5-21.5-48-48-48L48 64zM0 176L0 384c0 35.3 28.7 64 64 64l384 0c35.3 0 64-28.7 64-64l0-208L294.4 339.2c-22.8 17.1-54 17.1-76.8 0L0 176z" />
                    </svg>
                    <a href="mailto:info@duracabs.com">info@duracabs.com</a>
                </div>


                <div class="footer-about-us text-white mt-4 pr-10">
                    <h2>About Dura Cabs Services</h2>
                    <p>Dura Cabs Services is India’s trusted provider of comprehensive car rental services, offering
                        <strong>local taxi services</strong>, <strong>outstation trips</strong>, <strong>self-drive
                            rentals</strong>, and more. With 24/7 customer support and affordable, transparent pricing,
                        we ensure seamless travel experiences across major cities. Whether you need a <strong>one-way
                            taxi</strong>, <strong>Delhi airport transfer</strong>, or a <strong>tempo traveller
                            rental</strong>, Dura Cabs has you covered for every journey.</p>
                </div>

            </div>
            <!-- End Col -->

            <div class="col-span-1">
                <h4 class="font-semibold text-gray-100">Useful Links</h4>

                <div class="mt-3 grid space-y-3">
                    <p>
                        <a wire:navigate
                            class="inline-flex gap-x-2 text-gray-400 hover:text-gray-200 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                            href="/">Home</a>
                    </p>
                    <p>
                        <a wire:navigate
                            class="inline-flex gap-x-2 text-gray-400 hover:text-gray-200 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                            href="/about-us">About Us</a>
                    </p>
                    <p>
                        <a wire:navigate
                            class="inline-flex gap-x-2 text-gray-400 hover:text-gray-200 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                            href="/contact-us">Contact Us</a>
                    </p>
                    <p>
                        <a wire:navigate
                            class="inline-flex gap-x-2 text-gray-400 hover:text-gray-200 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                            href="/terms-and-conditions">Terms and Conditions</a>
                    </p>
                    <p>
                        <a wire:navigate
                            class="inline-flex gap-x-2 text-gray-400 hover:text-gray-200 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                            href="/terms-and-conditions#requirements">Privacy & Policy</a>
                    </p>
                    <p>
                        <a wire:navigate
                            class="inline-flex gap-x-2 text-gray-400 hover:text-gray-200 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                            href="/terms-and-conditions#cancellation">Refund Policy</a>
                    </p>
                    <p>
                        <a target="_blank"
                            class="inline-flex gap-x-2 text-gray-400 hover:text-gray-200 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                            href="/sitemap.xml">Sitemap XML</a>
                    </p>
                    <p>
                        <a wire:navigate
                            class="inline-flex gap-x-2 text-gray-400 hover:text-gray-200 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                            href="/vendor-register">Vendor Registration</a>
                    </p>
                    <p>
                        <a wire:navigate
                            class="inline-flex gap-x-2 text-gray-400 hover:text-gray-200 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                            href="/login">Sign In / Sign Up</a>
                    </p>
                </div>
            </div>
            <!-- End Col -->

            <div class="col-span-1">
                <h4 class="font-semibold text-gray-100">Top Routes</h4>

                <div class="mt-3 grid space-y-3">
                    <p><a class="inline-flex gap-x-2 text-gray-400 hover:text-gray-200 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                            href="https://www.duracabs.com/route/agra-uttar-pradesh-to-new-delhi-delhi">Agra To Delhi One Way Taxi</a></p>
                    <p><a class="inline-flex gap-x-2 text-gray-400 hover:text-gray-200 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                            href="https://www.duracabs.com/route/new-delhi-delhi-to-agra-uttar-pradesh">Delhi To Agra One Way Taxi</a></p>

                    <p><a class="inline-flex gap-x-2 text-gray-400 hover:text-gray-200 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                            href="https://www.duracabs.com/route/agra-uttar-pradesh-to-jaipur-rajasthan">Agra To Jaipur One Way Taxi</a>
                    </p>
                    <p><a class="inline-flex gap-x-2 text-gray-400 hover:text-gray-200 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                            href="https://www.duracabs.com/route/jaipur-rajasthan-to-new-delhi-delhi">Jaipur To Delhi One Way Taxi</a>
                    </p>
                    <p><a class="inline-flex gap-x-2 text-gray-400 hover:text-gray-200 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                            href="https://www.duracabs.com/route/agra-uttar-pradesh-to-lucknow-uttar-pradesh">Agra To Lucknow One Way Taxi</a>
                    </p>
                    <p><a class="inline-flex gap-x-2 text-gray-400 hover:text-gray-200 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                            href="https://www.duracabs.com/route/agra-uttar-pradesh-to-noida-uttar-pradesh">Agra to Noida One Way Taxi</a>
                    </p>
                    <p><a class="inline-flex gap-x-2 text-gray-400 hover:text-gray-200 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                            href="https://www.duracabs.com/route/agra-uttar-pradesh-to-vrindavan-uttar-pradesh">Agra to Vrindavan One Way Taxi</a>
                    </p>
                    <p><a class="inline-flex gap-x-2 text-gray-400 hover:text-gray-200 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                            href="https://www.duracabs.com/route/agra-uttar-pradesh-to-igi-t3-airport-new-delhi-delhi">Agra To Delhi Airport T3</a>
                    </p>
                </div>
            </div>

            <div class="col-span-1">
                <h4 class="font-semibold text-gray-100">Top Cities</h4>

                <div class="mt-3 grid space-y-3">
                    <p><a class="inline-flex gap-x-2 text-gray-400 hover:text-gray-200 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                            href="https://www.duracabs.com/pages/car-rental-agra">Car Rental Agra</a></p>
                    <p><a class="inline-flex gap-x-2 text-gray-400 hover:text-gray-200 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                            href="https://www.duracabs.com/page/self-drive-service-in-agra">Self Drive Agra</a></p>

                    <p><a class="inline-flex gap-x-2 text-gray-400 hover:text-gray-200 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                            href="https://www.duracabs.com/pages/tempo-traveller-hire-in-agra-on-rent">Tempo Traveller Agra</a></p>
                    <p><a class="inline-flex gap-x-2 text-gray-400 hover:text-gray-200 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                            href="https://www.duracabs.com/pages/car-rental-delhi">Car Rental Delhi</a></p>
                    <p><a class="inline-flex gap-x-2 text-gray-400 hover:text-gray-200 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                            href="https://www.duracabs.com/pages/car-rental-in-jaipur">Car Rental Jaipur</a></p>
                    <p><a class="inline-flex gap-x-2 text-gray-400 hover:text-gray-200 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                            href="https://www.duracabs.com/pages/car-rental-in-lucknow">Car Rental Lucknow</a></p>
                    <p><a class="inline-flex gap-x-2 text-gray-400 hover:text-gray-200 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                            href="https://www.duracabs.com/pages/car-rental-in-chandigarh">Car Rental Chandigarh</a></p>
                    <p><a class="inline-flex gap-x-2 text-gray-400 hover:text-gray-200 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                            href="https://www.duracabs.com/pages/car-rental-in-haridwar">Car Rental Haridwar</a></p>
                    <p><a class="inline-flex gap-x-2 text-gray-400 hover:text-gray-200 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                            href="https://www.duracabs.com/pages/car-rental-in-manali">Car Rental Manali</a></p>
                </div>
            </div>
            <!-- End Col -->

            {{-- <div class="col-span-2">
                <h4 class="font-semibold text-gray-100">Stay up to date</h4>

                <form>
                    <div
                        class="mt-4 flex flex-col items-center gap-2 sm:flex-row sm:gap-3 bg-white rounded-lg p-2 dark:bg-gray-800">
                        <div class="w-full">
                            <input type="text" id="hero-input" name="hero-input"
                                class="py-3 px-4 block w-full border-transparent rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-transparent dark:text-gray-400 dark:focus:ring-gray-600"
                                placeholder="Enter your email">
                        </div>
                        <a class="w-full sm:w-auto whitespace-nowrap p-3 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600"
                            href="#">
                            Subscribe
                        </a>
                    </div>

                </form>
            </div> --}}
            <!-- End Col -->
        </div>
        <!-- End Grid -->

        <div class="mt-5 sm:mt-12 grid gap-y-2 sm:gap-y-0 sm:flex sm:justify-between sm:items-center">
            <div class="flex justify-between items-center">
                <p class="text-sm text-gray-400">Copyright © 2025 DURA CABS SERVICES. All rights reserved.</p>
            </div>
            <!-- End Col -->

            <!-- Social Brands -->
            <div>
                <a class="w-10 h-10 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent text-white hover:bg-white/10 disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:ring-1 focus:ring-gray-600"
                    href="https://www.facebook.com/duracabs/" rel="noindex" target="_blank" aria-label="Facebook">
                    <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="16"
                        height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path
                            d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951z" />
                    </svg>
                </a>
                <a class="w-10 h-10 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent text-white hover:bg-white/10 disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:ring-1 focus:ring-gray-600"
                    href="https://www.instagram.com/duracabs/" rel="noindex" target="_blank" aria-label="Instagram">
                    <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="16"
                        height="16" fill="currentColor"
                        viewBox="0 0 448 512"><!--!Font Awesome Free 6.7.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                        <path
                            d="M224.1 141c-63.6 0-114.9 51.3-114.9 114.9s51.3 114.9 114.9 114.9S339 319.5 339 255.9 287.7 141 224.1 141zm0 189.6c-41.1 0-74.7-33.5-74.7-74.7s33.5-74.7 74.7-74.7 74.7 33.5 74.7 74.7-33.6 74.7-74.7 74.7zm146.4-194.3c0 14.9-12 26.8-26.8 26.8-14.9 0-26.8-12-26.8-26.8s12-26.8 26.8-26.8 26.8 12 26.8 26.8zm76.1 27.2c-1.7-35.9-9.9-67.7-36.2-93.9-26.2-26.2-58-34.4-93.9-36.2-37-2.1-147.9-2.1-184.9 0-35.8 1.7-67.6 9.9-93.9 36.1s-34.4 58-36.2 93.9c-2.1 37-2.1 147.9 0 184.9 1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0 35.9-1.7 67.7-9.9 93.9-36.2 26.2-26.2 34.4-58 36.2-93.9 2.1-37 2.1-147.8 0-184.8zM398.8 388c-7.8 19.6-22.9 34.7-42.6 42.6-29.5 11.7-99.5 9-132.1 9s-102.7 2.6-132.1-9c-19.6-7.8-34.7-22.9-42.6-42.6-11.7-29.5-9-99.5-9-132.1s-2.6-102.7 9-132.1c7.8-19.6 22.9-34.7 42.6-42.6 29.5-11.7 99.5-9 132.1-9s102.7-2.6 132.1 9c19.6 7.8 34.7 22.9 42.6 42.6 11.7 29.5 9 99.5 9 132.1s2.7 102.7-9 132.1z" />
                    </svg>

                </a>
                <a class="w-10 h-10 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent text-white hover:bg-white/10 disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:ring-1 focus:ring-gray-600"
                    href="https://in.pinterest.com/duracabs/" rel="noindex" target="_blank" aria-label="Pinterest">
                    <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="16"
                        height="16" fill="currentColor"
                        viewBox="0 0 496 512"><!--!Font Awesome Free 6.7.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                        <path
                            d="M496 256c0 137-111 248-248 248-25.6 0-50.2-3.9-73.4-11.1 10.1-16.5 25.2-43.5 30.8-65 3-11.6 15.4-59 15.4-59 8.1 15.4 31.7 28.5 56.8 28.5 74.8 0 128.7-68.8 128.7-154.3 0-81.9-66.9-143.2-152.9-143.2-107 0-163.9 71.8-163.9 150.1 0 36.4 19.4 81.7 50.3 96.1 4.7 2.2 7.2 1.2 8.3-3.3 .8-3.4 5-20.3 6.9-28.1 .6-2.5 .3-4.7-1.7-7.1-10.1-12.5-18.3-35.3-18.3-56.6 0-54.7 41.4-107.6 112-107.6 60.9 0 103.6 41.5 103.6 100.9 0 67.1-33.9 113.6-78 113.6-24.3 0-42.6-20.1-36.7-44.8 7-29.5 20.5-61.3 20.5-82.6 0-19-10.2-34.9-31.4-34.9-24.9 0-44.9 25.7-44.9 60.2 0 22 7.4 36.8 7.4 36.8s-24.5 103.8-29 123.2c-5 21.4-3 51.6-.9 71.2C65.4 450.9 0 361.1 0 256 0 119 111 8 248 8s248 111 248 248z" />
                    </svg>
                </a>
                <a class="w-10 h-10 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent text-white hover:bg-white/10 disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:ring-1 focus:ring-gray-600"
                    href="https://x.com/duracabs" rel="noindex" target="_blank" aria-label="Twitter">
                    <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="16"
                        height="16" fill="currentColor"
                        viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                        <path
                            d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z" />
                    </svg>

                </a>

            </div>
            
            <!-- End Social Brands -->
        </div>
    </div>
     </div>
    <div class="lg:hidden  w-full z-50 fixed bottom-0 flex-wrap drop-shadow-2xl">
        <ul
            class="flex form-margine-negative relative bg-white text-sm font-medium text-center text-gray-500  shadow  dark:divide-gray-700 dark:text-gray-400 lg:mt-10 lg:mx-48">
            <li class="w-1/2  ">

                <a href="https://www.duracabs.com/login" wire:click='changeTab("one_way")'
                    class="lg:flex grid grid-rows-1 gap-0 place-items-center  text-center items-center justify-center  w-full lg:p-4 p-1 text-gray-900   dark:border-gray-700 rounded-s-lg  active dark:bg-gray-700 dark:text-white"
                    aria-current="page">
                    <svg class="" fill="#1e9cfd" width="16" height="24"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                        <path
                            d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512l388.6 0c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304l-91.4 0z" />
                    </svg>

                    <h2 class="font-bold lg:text-sm extra-small-text text-slate-500 uppercase ">
                        Account</h2>
                </a>
            </li>
        
            <li class="w-1/2  ">
                <a href="https://api.whatsapp.com/send/?phone=917088873332&text=hi&type=phone_number&app_absent=0"
                    target="_blank"
                    class="lg:flex grid grid-rows-1 gap-0 place-items-center  text-center items-center justify-center  w-full lg:p-4 p-1 text-gray-900   dark:border-gray-700  active dark:bg-gray-700 dark:text-white">

                    <svg fill="#1e9cfd" width="16" height="24" xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 448 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                        <path
                            d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z" />
                    </svg>

                    <h2 class="font-bold lg:text-sm extra-small-text text-slate-500 uppercase  ">
                        Whatsapp</h2>
                </a>
            </li>
            <li class="w-1/2 main-color rounded-2xl footer-margin drop-shadow-2xl">
                <a href="tel:+917088873331"
                    class="lg:flex grid grid-rows-1 gap-0 place-items-center  text-center items-center justify-center  w-full lg:p-4 p-1 text-gray-900   dark:border-gray-700  active dark:bg-gray-700 dark:text-white">

                    <svg fill="#fff" width="16" height="24" xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                        <path
                            d="M164.9 24.6c-7.7-18.6-28-28.5-47.4-23.2l-88 24C12.1 30.2 0 46 0 64C0 311.4 200.6 512 448 512c18 0 33.8-12.1 38.6-29.5l24-88c5.3-19.4-4.6-39.7-23.2-47.4l-96-40c-16.3-6.8-35.2-2.1-46.3 11.6L304.7 368C234.3 334.7 177.3 277.7 144 207.3L193.3 167c13.7-11.2 18.4-30 11.6-46.3l-40-96z" />
                    </svg>

                    <h2 class="font-bold lg:text-sm extra-small-text text-white uppercase  ">
                        Call</h2>
                </a>
            </li>
            <li class="w-1/2  ">
                <a href="https://www.duracabs.com/vendor-register"
                    class="lg:flex grid grid-rows-1 gap-0 place-items-center  text-center items-center justify-center  w-full lg:p-4 p-1 text-gray-900  dark:border-gray-700  active dark:bg-gray-700 dark:text-white">

                    <svg fill="#1e9cfd" width="16" height="24" xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                        <path
                            d="M47.6 300.4L228.3 469.1c7.5 7 17.4 10.9 27.7 10.9s20.2-3.9 27.7-10.9L464.4 300.4c30.4-28.3 47.6-68 47.6-109.5v-5.8c0-69.9-50.5-129.5-119.4-141C347 36.5 300.6 51.4 268 84L256 96 244 84c-32.6-32.6-79-47.5-124.6-39.9C50.5 55.6 0 115.2 0 185.1v5.8c0 41.5 17.2 81.2 47.6 109.5z" />
                    </svg>

                    <h2 class="font-bold lg:text-sm extra-small-text text-slate-500 uppercase">
                        Host</h2>

                </a>
            </li>
            <li class="w-1/2  ">
                <a href="https://tawk.to/chat/6580f88f07843602b803784b/1i37fp9vq" rel="noindex"
                    class="lg:flex grid grid-rows-1 gap-0 place-items-center  text-center items-center justify-center  w-full lg:p-4 p-1 text-gray-900  dark:border-gray-700 rounded-e-lg  active dark:bg-gray-700 dark:text-white">

                    <svg fill="#1e9cfd" width="16" height="24" xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 448 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                        <path
                            d="M123.6 391.3c12.9-9.4 29.6-11.8 44.6-6.4c26.5 9.6 56.2 15.1 87.8 15.1c124.7 0 208-80.5 208-160s-83.3-160-208-160S48 160.5 48 240c0 32 12.4 62.8 35.7 89.2c8.6 9.7 12.8 22.5 11.8 35.5c-1.4 18.1-5.7 34.7-11.3 49.4c17-7.9 31.1-16.7 39.4-22.7zM21.2 431.9c1.8-2.7 3.5-5.4 5.1-8.1c10-16.6 19.5-38.4 21.4-62.9C17.7 326.8 0 285.1 0 240C0 125.1 114.6 32 256 32s256 93.1 256 208s-114.6 208-256 208c-37.1 0-72.3-6.4-104.1-17.9c-11.9 8.7-31.3 20.6-54.3 30.6c-15.1 6.6-32.3 12.6-50.1 16.1c-.8 .2-1.6 .3-2.4 .5c-4.4 .8-8.7 1.5-13.2 1.9c-.2 0-.5 .1-.7 .1c-5.1 .5-10.2 .8-15.3 .8c-6.5 0-12.3-3.9-14.8-9.9c-2.5-6-1.1-12.8 3.4-17.4c4.1-4.2 7.8-8.7 11.3-13.5c1.7-2.3 3.3-4.6 4.8-6.9l.3-.5" />
                    </svg>

                    <h2 class="font-bold lg:text-sm extra-small-text text-slate-500 uppercase ">
                        Live Chat</h2>
                </a>
            </li>
        </ul>
    </div>
</footer>
