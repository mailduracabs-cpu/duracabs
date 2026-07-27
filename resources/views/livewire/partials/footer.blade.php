<footer class="bg-slate-950 pb-20 text-slate-300 lg:pb-0">
    <div class="mx-auto max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-5">
            <section class="sm:col-span-2" aria-labelledby="footer-brand-title">
                <a href="/" class="inline-flex" aria-label="Dura Cabs home">
                    <img src="/img/logo/duracabs_logo.svg" width="190" height="48" class="h-11 w-auto brightness-0 invert" alt="Dura Cabs Services" loading="lazy" decoding="async">
                </a>
                <h2 id="footer-brand-title" class="mt-4 text-xl font-extrabold text-white">Dura Cabs Services</h2>
                <p class="mt-3 max-w-md text-sm leading-6 text-slate-400">Trusted taxi, outstation, local, round-trip and self-drive car rental services with transparent pricing and 24/7 customer support.</p>
                <address class="mt-5 space-y-3 text-sm not-italic">
                    <p><i class="fa-solid fa-location-dot mr-3 w-4 text-sky-400" aria-hidden="true"></i>Agra, Delhi, Jaipur & Chandigarh</p>
                    <p><a href="tel:+917088873331" class="transition hover:text-white"><i class="fa-solid fa-phone mr-3 w-4 text-sky-400" aria-hidden="true"></i>+91 70888 73331</a></p>
                    <p><a href="mailto:info@duracabs.com" class="transition hover:text-white"><i class="fa-solid fa-envelope mr-3 w-4 text-sky-400" aria-hidden="true"></i>info@duracabs.com</a></p>
                </address>
                <div class="mt-5 flex gap-2" aria-label="Social media links">
                    @foreach ([
                        ['https://www.facebook.com/duracabs/','fa-facebook-f','Facebook'],
                        ['https://www.instagram.com/duracabs/','fa-instagram','Instagram'],
                        ['https://in.pinterest.com/duracabs/','fa-pinterest-p','Pinterest'],
                        ['https://x.com/duracabs','fa-x-twitter','X']
                    ] as [$url,$icon,$label])
                        <a href="{{ $url }}" target="_blank" rel="nofollow noopener noreferrer" aria-label="{{ $label }}" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-slate-300 transition hover:border-sky-400 hover:bg-sky-500 hover:text-white">
                            <i class="fa-brands {{ $icon }}" aria-hidden="true"></i>
                        </a>
                    @endforeach
                </div>
            </section>

            <nav aria-labelledby="footer-links-title">
                <h2 id="footer-links-title" class="font-bold text-white">Useful Links</h2>
                <ul class="mt-4 space-y-3 text-sm">
                    <li><a href="/" wire:navigate class="hover:text-white">Home</a></li>
                    <li><a href="/about-us" wire:navigate class="hover:text-white">About Us</a></li>
                    <li><a href="/contact-us" wire:navigate class="hover:text-white">Contact Us</a></li>
                    <li><a href="/vendor-register" wire:navigate class="hover:text-white">Vendor Registration</a></li>
                    <li><a href="/login" wire:navigate class="hover:text-white">Sign In / Sign Up</a></li>
                    <li><a href="/sitemap.xml" class="hover:text-white">Sitemap</a></li>
                </ul>
            </nav>

            <nav aria-labelledby="footer-policy-title">
                <h2 id="footer-policy-title" class="font-bold text-white">Policies</h2>
                <ul class="mt-4 space-y-3 text-sm">
                    <li><a href="/terms-and-conditions" wire:navigate class="hover:text-white">Terms & Conditions</a></li>
                    <li><a href="/terms-and-conditions#requirements" wire:navigate class="hover:text-white">Privacy Policy</a></li>
                    <li><a href="/terms-and-conditions#cancellation" wire:navigate class="hover:text-white">Refund Policy</a></li>
                </ul>
            </nav>

            <nav aria-labelledby="footer-cities-title">
                <h2 id="footer-cities-title" class="font-bold text-white">Popular Cities</h2>
                <ul class="mt-4 space-y-3 text-sm">
                    <li><a href="/pages/car-rental-agra" class="hover:text-white">Car Rental Agra</a></li>
                    <li><a href="/pages/car-rental-delhi" class="hover:text-white">Car Rental Delhi</a></li>
                    <li><a href="/pages/car-rental-in-jaipur" class="hover:text-white">Car Rental Jaipur</a></li>
                    <li><a href="/pages/car-rental-in-lucknow" class="hover:text-white">Car Rental Lucknow</a></li>
                    <li><a href="/pages/car-rental-in-chandigarh" class="hover:text-white">Car Rental Chandigarh</a></li>
                </ul>
            </nav>
        </div>

        <div class="mt-10 grid gap-4 border-t border-white/10 pt-6 lg:grid-cols-2">
            @if (!empty($topRoutes) && count($topRoutes))
                <details class="group rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                    <summary class="flex cursor-pointer list-none items-center justify-between font-bold text-white">
                        <span><i class="fa-solid fa-route mr-2 text-sky-400" aria-hidden="true"></i>Top Routes</span>
                        <i class="fa-solid fa-chevron-down text-xs transition group-open:rotate-180" aria-hidden="true"></i>
                    </summary>
                    <div class="mt-4 flex flex-wrap gap-x-3 gap-y-2 text-sm">
                        @foreach ($topRoutes as $cars)
                            <a href="{{ $cars->url }}" class="hover:text-sky-300">{{ $cars->title }}</a>
                        @endforeach
                    </div>
                </details>
            @endif

            @if (!empty($carRental) && count($carRental))
                <details class="group rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                    <summary class="flex cursor-pointer list-none items-center justify-between font-bold text-white">
                        <span><i class="fa-solid fa-car mr-2 text-sky-400" aria-hidden="true"></i>Car Rental Locations</span>
                        <i class="fa-solid fa-chevron-down text-xs transition group-open:rotate-180" aria-hidden="true"></i>
                    </summary>
                    <div class="mt-4 flex flex-wrap gap-x-3 gap-y-2 text-sm">
                        @foreach ($carRental as $cars)
                            <a href="{{ $cars->url }}" class="hover:text-sky-300">{{ $cars->title }}</a>
                        @endforeach
                    </div>
                </details>
            @endif
        </div>

        <div class="mt-8 flex flex-col gap-3 border-t border-white/10 pt-6 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ now()->year }} Dura Cabs Services. All rights reserved.</p>
            <p>Safe rides. Transparent fares. 24/7 support.</p>
        </div>
    </div>

    <nav class="fixed inset-x-0 bottom-0 z-50 border-t border-slate-200 bg-white/95 px-2 pb-[env(safe-area-inset-bottom)] shadow-[0_-8px_30px_rgba(15,23,42,.12)] backdrop-blur lg:hidden" aria-label="Mobile quick actions">
        <ul class="grid grid-cols-5">
            @php
                $mobileAction = 'flex min-h-16 flex-col items-center justify-center gap-1 text-[10px] font-bold uppercase tracking-wide text-slate-500 transition hover:text-sky-600';
            @endphp
            <li><a href="/login" wire:navigate class="{{ $mobileAction }}"><i class="fa-regular fa-user text-lg" aria-hidden="true"></i><span>Account</span></a></li>
            <li><a href="https://api.whatsapp.com/send/?phone=917088873332&text=Hi&type=phone_number&app_absent=0" target="_blank" rel="nofollow noopener noreferrer" class="{{ $mobileAction }}"><i class="fa-brands fa-whatsapp text-xl" aria-hidden="true"></i><span>WhatsApp</span></a></li>
            <li class="relative"><a href="tel:+917088873331" class="absolute left-1/2 top-[-14px] flex h-16 w-16 -translate-x-1/2 flex-col items-center justify-center gap-1 rounded-full border-4 border-white bg-sky-600 text-[10px] font-bold uppercase text-white shadow-lg"><i class="fa-solid fa-phone text-lg" aria-hidden="true"></i><span>Call</span></a></li>
            <li><a href="/vendor-register" wire:navigate class="{{ $mobileAction }}"><i class="fa-solid fa-car-side text-lg" aria-hidden="true"></i><span>Host</span></a></li>
            <li><a href="https://tawk.to/chat/6580f88f07843602b803784b/1i37fp9vq" target="_blank" rel="nofollow noopener noreferrer" class="{{ $mobileAction }}"><i class="fa-regular fa-comments text-lg" aria-hidden="true"></i><span>Chat</span></a></li>
        </ul>
    </nav>
</footer>
