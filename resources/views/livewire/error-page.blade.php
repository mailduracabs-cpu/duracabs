<div class="min-h-[70vh] bg-slate-50 py-8 px-3 sm:px-6 lg:px-8">

    @section('title', 'Page Not Found | DuraCabs')

    <div class="max-w-6xl mx-auto">

        {{-- Top 404 Section --}}
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">

            <div class="grid lg:grid-cols-2 gap-6 items-center px-5 py-8 md:px-10 md:py-10">

                {{-- Left Content --}}
                <div class="text-center lg:text-left">

                    <div
                        class="inline-flex items-center gap-2 bg-sky-50 text-sky-700 font-semibold text-sm px-4 py-2 rounded-full mb-4">
                        <span>404</span>
                        <span>•</span>
                        <span>Page Not Found</span>
                    </div>

                    <h1 class="text-3xl md:text-4xl font-bold text-slate-900 leading-tight">
                        Page not found?
                        <span class="text-sky-700">
                            Let's get you a cab instead.
                        </span>
                    </h1>

                    <p class="text-slate-600 mt-4 text-base md:text-lg max-w-xl mx-auto lg:mx-0">
                        The page you are looking for may have moved, changed or no longer exists.
                        Don't worry — your DuraCabs ride is still just a few clicks away.
                    </p>

                    {{-- Primary Buttons --}}
                    <div
                        class="flex flex-col sm:flex-row flex-wrap gap-3 mt-6 justify-center lg:justify-start">

                        <a wire:navigate
                           href="/"
                           class="inline-flex items-center justify-center gap-2 bg-sky-700 hover:bg-sky-800 text-white font-semibold px-6 py-3 rounded-xl transition">
                            <span>🏠</span>
                            Back To Home
                        </a>

                        <a href="tel:+917088873331"
                           class="inline-flex items-center justify-center gap-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold px-6 py-3 rounded-xl transition">
                            <span>📞</span>
                            Call Now
                        </a>

                        <a href="https://wa.me/917088873331"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-6 py-3 rounded-xl transition">
                            <span>💬</span>
                            WhatsApp
                        </a>

                    </div>

                    <p class="text-xs text-slate-400 mt-5">
                        Need help finding the right cab? Call or WhatsApp our team.
                    </p>

                </div>

                {{-- 404 Image --}}
                <div class="flex justify-center items-center">

                    <img
                        src="/img/404page.png"
                        alt="DuraCabs Page Not Found"
                        class="w-full max-w-md h-auto object-contain"
                        loading="eager"
                    />

                </div>

            </div>

        </div>


        {{-- Services Section --}}
        <div class="mt-8">

            <div class="text-center mb-5">
                <h2 class="text-2xl font-bold text-slate-900">
                    What are you looking for?
                </h2>

                <p class="text-slate-500 mt-1">
                    Continue your journey with one of our popular cab services.
                </p>
            </div>


            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">

                {{-- One Way --}}
                <a wire:navigate
                   href="/"
                   class="group bg-white border border-slate-200 hover:border-sky-400 hover:shadow-md rounded-2xl p-4 text-center transition">

                    <div
                        class="w-12 h-12 rounded-full bg-sky-50 flex items-center justify-center mx-auto text-2xl group-hover:scale-110 transition">
                        🚕
                    </div>

                    <h3 class="font-semibold text-slate-900 mt-3">
                        One Way Taxi
                    </h3>

                    <p class="text-xs text-slate-500 mt-1">
                        City to city cab
                    </p>
                </a>


                {{-- Round Trip --}}
                <a wire:navigate
                   href="/"
                   class="group bg-white border border-slate-200 hover:border-sky-400 hover:shadow-md rounded-2xl p-4 text-center transition">

                    <div
                        class="w-12 h-12 rounded-full bg-sky-50 flex items-center justify-center mx-auto text-2xl group-hover:scale-110 transition">
                        🔄
                    </div>

                    <h3 class="font-semibold text-slate-900 mt-3">
                        Round Trip
                    </h3>

                    <p class="text-xs text-slate-500 mt-1">
                        Outstation return trip
                    </p>
                </a>


                {{-- Airport --}}
                <a wire:navigate
                   href="/"
                   class="group bg-white border border-slate-200 hover:border-sky-400 hover:shadow-md rounded-2xl p-4 text-center transition">

                    <div
                        class="w-12 h-12 rounded-full bg-sky-50 flex items-center justify-center mx-auto text-2xl group-hover:scale-110 transition">
                        ✈️
                    </div>

                    <h3 class="font-semibold text-slate-900 mt-3">
                        Airport Taxi
                    </h3>

                    <p class="text-xs text-slate-500 mt-1">
                        Airport pickup & drop
                    </p>
                </a>


                {{-- Local --}}
                <a wire:navigate
                   href="/"
                   class="group bg-white border border-slate-200 hover:border-sky-400 hover:shadow-md rounded-2xl p-4 text-center transition">

                    <div
                        class="w-12 h-12 rounded-full bg-sky-50 flex items-center justify-center mx-auto text-2xl group-hover:scale-110 transition">
                        🏙️
                    </div>

                    <h3 class="font-semibold text-slate-900 mt-3">
                        Local Taxi
                    </h3>

                    <p class="text-xs text-slate-500 mt-1">
                        Local city packages
                    </p>
                </a>


                {{-- Self Drive --}}
                <a wire:navigate
                   href="/"
                   class="group bg-white border border-slate-200 hover:border-sky-400 hover:shadow-md rounded-2xl p-4 text-center transition col-span-2 md:col-span-1">

                    <div
                        class="w-12 h-12 rounded-full bg-sky-50 flex items-center justify-center mx-auto text-2xl group-hover:scale-110 transition">
                        🚘
                    </div>

                    <h3 class="font-semibold text-slate-900 mt-3">
                        Self Drive Car
                    </h3>

                    <p class="text-xs text-slate-500 mt-1">
                        Drive your own car
                    </p>
                </a>

            </div>

        </div>


        {{-- Popular Routes --}}
        <div class="mt-8 bg-white border border-slate-200 rounded-3xl p-5 md:p-7">

            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-5">

                <div>
                    <h2 class="text-xl md:text-2xl font-bold text-slate-900">
                        Popular Taxi Routes
                    </h2>

                    <p class="text-sm text-slate-500 mt-1">
                        You might be looking for one of these popular routes.
                    </p>
                </div>

                <a wire:navigate
                   href="/"
                   class="text-sky-700 font-semibold text-sm hover:underline">
                    Explore All →
                </a>

            </div>


            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">

                <a href="/"
                   wire:navigate
                   class="flex justify-between items-center border border-slate-200 rounded-xl px-4 py-3 hover:border-sky-400 hover:bg-sky-50 transition">

                    <span class="font-medium text-slate-700">
                        Agra → Delhi
                    </span>

                    <span class="text-sky-700">
                        →
                    </span>
                </a>


                <a href="/"
                   wire:navigate
                   class="flex justify-between items-center border border-slate-200 rounded-xl px-4 py-3 hover:border-sky-400 hover:bg-sky-50 transition">

                    <span class="font-medium text-slate-700">
                        Agra → Jaipur
                    </span>

                    <span class="text-sky-700">
                        →
                    </span>
                </a>


                <a href="/"
                   wire:navigate
                   class="flex justify-between items-center border border-slate-200 rounded-xl px-4 py-3 hover:border-sky-400 hover:bg-sky-50 transition">

                    <span class="font-medium text-slate-700">
                        Agra → Mathura
                    </span>

                    <span class="text-sky-700">
                        →
                    </span>
                </a>


                <a href="/"
                   wire:navigate
                   class="flex justify-between items-center border border-slate-200 rounded-xl px-4 py-3 hover:border-sky-400 hover:bg-sky-50 transition">

                    <span class="font-medium text-slate-700">
                        Agra → Noida
                    </span>

                    <span class="text-sky-700">
                        →
                    </span>
                </a>


                <a href="/"
                   wire:navigate
                   class="flex justify-between items-center border border-slate-200 rounded-xl px-4 py-3 hover:border-sky-400 hover:bg-sky-50 transition">

                    <span class="font-medium text-slate-700">
                        Agra → Gurgaon
                    </span>

                    <span class="text-sky-700">
                        →
                    </span>
                </a>


                <a href="/"
                   wire:navigate
                   class="flex justify-between items-center border border-slate-200 rounded-xl px-4 py-3 hover:border-sky-400 hover:bg-sky-50 transition">

                    <span class="font-medium text-slate-700">
                        Agra → Lucknow
                    </span>

                    <span class="text-sky-700">
                        →
                    </span>
                </a>

            </div>

        </div>


        {{-- Help Section --}}
        <div
            class="mt-8 bg-slate-900 rounded-3xl px-5 py-7 md:px-8 text-center md:text-left md:flex md:items-center md:justify-between">

            <div>

                <h2 class="text-xl md:text-2xl font-bold text-white">
                    Still can't find what you need?
                </h2>

                <p class="text-slate-300 mt-2">
                    Our DuraCabs team can help you find the right cab and route.
                </p>

            </div>

            <div class="flex flex-col sm:flex-row gap-3 justify-center mt-5 md:mt-0">

                <a href="tel:+917088873331"
                   class="bg-white hover:bg-slate-100 text-slate-900 font-semibold rounded-xl px-5 py-3 transition">
                    📞 +91 70888 73331
                </a>

                <a href="https://wa.me/917088873331"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl px-5 py-3 transition">
                    💬 WhatsApp Us
                </a>

            </div>

        </div>


        {{-- Small Error Message --}}
        <div class="text-center mt-6 pb-3">

            <p class="text-xs text-slate-400">
                Error 404 — The requested page could not be found.
            </p>

        </div>

    </div>

</div>