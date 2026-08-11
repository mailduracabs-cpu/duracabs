<!DOCTYPE html>
<html class="scroll-smooth focus:scroll-auto" lang="en">

<head>
    <meta charset="utf-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=0">

    <title>Page Not Found | DuraCabs</title>

    {{-- 404 page should not be indexed --}}
    <meta name="robots" content="noindex, follow">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-slate-50 dark:bg-slate-900">

    {{-- Existing DuraCabs Navbar --}}
    @livewire('partials.navbar')


    <main>

        <section class="px-3 sm:px-5 lg:px-8 py-6 md:py-10">

            <div class="max-w-6xl mx-auto">

                {{-- Main 404 Card --}}
                <div class="
                    bg-white
                    dark:bg-slate-800
                    border border-slate-200
                    dark:border-slate-700
                    rounded-3xl
                    overflow-hidden
                    shadow-sm
                ">

                    <div class="
                        grid
                        lg:grid-cols-2
                        items-center
                        gap-5
                        md:gap-8
                        px-5
                        py-7
                        md:px-10
                        md:py-10
                    ">

                        {{-- Left Content --}}
                        <div class="text-center lg:text-left">

                            {{-- Small 404 Badge --}}
                            <div class="
                                inline-flex
                                items-center
                                gap-2
                                bg-sky-50
                                dark:bg-sky-950
                                text-sky-700
                                dark:text-sky-300
                                text-sm
                                font-semibold
                                px-4
                                py-2
                                rounded-full
                            ">
                                <span>404</span>
                                <span>•</span>
                                <span>Page Not Found</span>
                            </div>


                            {{-- Main Heading --}}
                            <h1 class="
                                mt-5
                                text-3xl
                                md:text-4xl
                                lg:text-5xl
                                font-bold
                                tracking-tight
                                text-slate-900
                                dark:text-white
                                leading-tight
                            ">
                                Page not found?
                                <span class="text-sky-700 dark:text-sky-400">
                                    Let's get you a cab instead.
                                </span>
                            </h1>


                            {{-- Description --}}
                            <p class="
                                mt-4
                                text-base
                                md:text-lg
                                text-slate-600
                                dark:text-slate-300
                                leading-relaxed
                                max-w-xl
                                mx-auto
                                lg:mx-0
                            ">
                                The page you were looking for may have moved or is no longer available.
                                Your journey doesn't have to stop here — DuraCabs can still help you
                                find the right cab.
                            </p>


                            {{-- Main CTA Buttons --}}
                            <div class="
                                mt-6
                                flex
                                flex-col
                                sm:flex-row
                                flex-wrap
                                gap-3
                                justify-center
                                lg:justify-start
                            ">

                                {{-- Book Cab --}}
                                <a
                                    wire:navigate
                                    href="/"
                                    class="
                                        inline-flex
                                        items-center
                                        justify-center
                                        gap-2
                                        bg-sky-700
                                        hover:bg-sky-800
                                        text-white
                                        font-semibold
                                        px-6
                                        py-3
                                        rounded-xl
                                        transition
                                        shadow-sm
                                    "
                                >
                                    🚕
                                    Book a Cab
                                </a>


                                {{-- Go Back --}}
                                <button
                                    type="button"
                                    onclick="window.history.back()"
                                    class="
                                        inline-flex
                                        items-center
                                        justify-center
                                        gap-2
                                        bg-white
                                        dark:bg-slate-700
                                        border
                                        border-slate-300
                                        dark:border-slate-600
                                        hover:bg-slate-100
                                        dark:hover:bg-slate-600
                                        text-slate-800
                                        dark:text-white
                                        font-semibold
                                        px-6
                                        py-3
                                        rounded-xl
                                        transition
                                    "
                                >
                                    ←
                                    Go Back
                                </button>

                            </div>


                            {{-- Contact CTAs --}}
                            <div class="
                                mt-4
                                flex
                                flex-col
                                sm:flex-row
                                gap-3
                                justify-center
                                lg:justify-start
                            ">

                                <a
                                    href="tel:+917088873331"
                                    class="
                                        inline-flex
                                        items-center
                                        justify-center
                                        gap-2
                                        text-sm
                                        font-semibold
                                        text-slate-700
                                        dark:text-slate-200
                                        hover:text-sky-700
                                        dark:hover:text-sky-400
                                        transition
                                    "
                                >
                                    ☎
                                    +91 70888 73331
                                </a>


                                <span class="hidden sm:inline text-slate-300">
                                    |
                                </span>


                                <a
                                    href="https://wa.me/917088873331"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="
                                        inline-flex
                                        items-center
                                        justify-center
                                        gap-2
                                        text-sm
                                        font-semibold
                                        text-emerald-600
                                        hover:text-emerald-700
                                        transition
                                    "
                                >
                                    💬
                                    Chat on WhatsApp
                                </a>

                            </div>

                        </div>


                        {{-- Right 404 Image --}}
                        <div class="flex justify-center items-center">

                            <img
                                src="/img/404page.png"
                                alt="DuraCabs Page Not Found"
                                class="
                                    w-full
                                    max-w-sm
                                    md:max-w-md
                                    h-auto
                                    object-contain
                                "
                                loading="eager"
                            >

                        </div>

                    </div>

                </div>



                {{-- Services Recovery Section --}}
                <div class="mt-7 md:mt-9">

                    <div class="text-center mb-5">

                        <h2 class="
                            text-xl
                            md:text-2xl
                            font-bold
                            text-slate-900
                            dark:text-white
                        ">
                            Continue your journey with DuraCabs
                        </h2>

                        <p class="
                            mt-1
                            text-sm
                            md:text-base
                            text-slate-500
                            dark:text-slate-400
                        ">
                            Choose the service you need and we'll help you get moving.
                        </p>

                    </div>


                    <div class="
                        grid
                        grid-cols-2
                        md:grid-cols-3
                        lg:grid-cols-5
                        gap-3
                    ">

                        {{-- One Way --}}
                        <a
                            wire:navigate
                            href="/"
                            class="
                                group
                                bg-white
                                dark:bg-slate-800
                                border
                                border-slate-200
                                dark:border-slate-700
                                hover:border-sky-400
                                hover:shadow-md
                                rounded-2xl
                                p-4
                                text-center
                                transition
                            "
                        >

                            <div class="
                                w-12
                                h-12
                                mx-auto
                                rounded-full
                                bg-sky-50
                                dark:bg-sky-950
                                flex
                                items-center
                                justify-center
                                text-2xl
                                group-hover:scale-110
                                transition
                            ">
                                🚕
                            </div>

                            <h3 class="
                                mt-3
                                font-semibold
                                text-slate-900
                                dark:text-white
                            ">
                                One Way
                            </h3>

                            <p class="
                                mt-1
                                text-xs
                                text-slate-500
                                dark:text-slate-400
                            ">
                                Outstation Taxi
                            </p>

                        </a>


                        {{-- Round Trip --}}
                        <a
                            wire:navigate
                            href="/"
                            class="
                                group
                                bg-white
                                dark:bg-slate-800
                                border
                                border-slate-200
                                dark:border-slate-700
                                hover:border-sky-400
                                hover:shadow-md
                                rounded-2xl
                                p-4
                                text-center
                                transition
                            "
                        >

                            <div class="
                                w-12
                                h-12
                                mx-auto
                                rounded-full
                                bg-sky-50
                                dark:bg-sky-950
                                flex
                                items-center
                                justify-center
                                text-2xl
                                group-hover:scale-110
                                transition
                            ">
                                🔄
                            </div>

                            <h3 class="
                                mt-3
                                font-semibold
                                text-slate-900
                                dark:text-white
                            ">
                                Round Trip
                            </h3>

                            <p class="
                                mt-1
                                text-xs
                                text-slate-500
                                dark:text-slate-400
                            ">
                                Return Journey
                            </p>

                        </a>


                        {{-- Airport --}}
                        <a
                            wire:navigate
                            href="/"
                            class="
                                group
                                bg-white
                                dark:bg-slate-800
                                border
                                border-slate-200
                                dark:border-slate-700
                                hover:border-sky-400
                                hover:shadow-md
                                rounded-2xl
                                p-4
                                text-center
                                transition
                            "
                        >

                            <div class="
                                w-12
                                h-12
                                mx-auto
                                rounded-full
                                bg-sky-50
                                dark:bg-sky-950
                                flex
                                items-center
                                justify-center
                                text-2xl
                                group-hover:scale-110
                                transition
                            ">
                                ✈️
                            </div>

                            <h3 class="
                                mt-3
                                font-semibold
                                text-slate-900
                                dark:text-white
                            ">
                                Airport
                            </h3>

                            <p class="
                                mt-1
                                text-xs
                                text-slate-500
                                dark:text-slate-400
                            ">
                                Pickup & Drop
                            </p>

                        </a>


                        {{-- Local --}}
                        <a
                            wire:navigate
                            href="/"
                            class="
                                group
                                bg-white
                                dark:bg-slate-800
                                border
                                border-slate-200
                                dark:border-slate-700
                                hover:border-sky-400
                                hover:shadow-md
                                rounded-2xl
                                p-4
                                text-center
                                transition
                            "
                        >

                            <div class="
                                w-12
                                h-12
                                mx-auto
                                rounded-full
                                bg-sky-50
                                dark:bg-sky-950
                                flex
                                items-center
                                justify-center
                                text-2xl
                                group-hover:scale-110
                                transition
                            ">
                                🏙️
                            </div>

                            <h3 class="
                                mt-3
                                font-semibold
                                text-slate-900
                                dark:text-white
                            ">
                                Local Taxi
                            </h3>

                            <p class="
                                mt-1
                                text-xs
                                text-slate-500
                                dark:text-slate-400
                            ">
                                City Packages
                            </p>

                        </a>


                        {{-- Self Drive --}}
                        <a
                            wire:navigate
                            href="/"
                            class="
                                group
                                col-span-2
                                md:col-span-1
                                bg-white
                                dark:bg-slate-800
                                border
                                border-slate-200
                                dark:border-slate-700
                                hover:border-sky-400
                                hover:shadow-md
                                rounded-2xl
                                p-4
                                text-center
                                transition
                            "
                        >

                            <div class="
                                w-12
                                h-12
                                mx-auto
                                rounded-full
                                bg-sky-50
                                dark:bg-sky-950
                                flex
                                items-center
                                justify-center
                                text-2xl
                                group-hover:scale-110
                                transition
                            ">
                                🚘
                            </div>

                            <h3 class="
                                mt-3
                                font-semibold
                                text-slate-900
                                dark:text-white
                            ">
                                Self Drive
                            </h3>

                            <p class="
                                mt-1
                                text-xs
                                text-slate-500
                                dark:text-slate-400
                            ">
                                Rent a Car
                            </p>

                        </a>

                    </div>

                </div>



                {{-- Trust / Help Section --}}
                <div class="
                    mt-7
                    md:mt-9
                    bg-slate-900
                    dark:bg-black
                    rounded-3xl
                    px-5
                    py-6
                    md:px-8
                    md:py-7
                    flex
                    flex-col
                    md:flex-row
                    md:items-center
                    md:justify-between
                    gap-5
                ">

                    <div class="text-center md:text-left">

                        <h2 class="
                            text-xl
                            md:text-2xl
                            font-bold
                            text-white
                        ">
                            Can't find what you're looking for?
                        </h2>

                        <p class="mt-1 text-sm text-slate-300">
                            Our team can help you with your cab, route or booking.
                        </p>

                    </div>


                    <div class="
                        flex
                        flex-col
                        sm:flex-row
                        justify-center
                        gap-3
                    ">

                        <a
                            href="tel:+917088873331"
                            class="
                                bg-white
                                hover:bg-slate-100
                                text-slate-900
                                font-semibold
                                px-5
                                py-3
                                rounded-xl
                                text-center
                                transition
                            "
                        >
                            ☎ Call Now
                        </a>


                        <a
                            href="https://wa.me/917088873331"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="
                                bg-emerald-600
                                hover:bg-emerald-700
                                text-white
                                font-semibold
                                px-5
                                py-3
                                rounded-xl
                                text-center
                                transition
                            "
                        >
                            💬 WhatsApp Us
                        </a>

                    </div>

                </div>


                {{-- Technical 404 --}}
                <div class="text-center py-5">

                    <p class="
                        text-xs
                        text-slate-400
                        dark:text-slate-500
                    ">
                        Error 404 — The requested page could not be found.
                    </p>

                </div>

            </div>

        </section>

    </main>


    {{-- Existing DuraCabs Footer --}}
    @livewire('partials.footer')

    @livewireScripts

</body>

</html>