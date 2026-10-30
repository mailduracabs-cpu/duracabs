<div class="dc-footer-root">
{{-- App promotion banner above the website footer. Phone display is an illustrative preview. --}}
<section class="dc-download-banner" aria-labelledby="dc-download-title">
    <div class="dc-download-inner">
        <div class="dc-phone-stage" aria-hidden="true">
            <div class="dc-phone">
                <div class="dc-phone-camera"></div>
                <div class="dc-phone-screen">
                    <div class="dc-phone-brand">Dura<span>Cabs</span><small>SELF DRIVE &amp; TAXI</small></div>
                    <div class="dc-phone-welcome">Your journey starts here.</div>
                    <div class="dc-phone-hero"><span>YOUR CITY. YOUR DRIVE.</span><strong>Apni ride,<br>apne andaaz mein.</strong><div class="dc-phone-road">&#128663;</div></div>
                    <div class="dc-phone-services"><span>&#128273;<b>Self Drive</b></span><span>&#128662;<b>Taxi with Driver</b></span></div>
                    <div class="dc-phone-field"><small>PICKUP CITY</small><b>&#128205; Agra</b></div>
                    <div class="dc-phone-field"><small>PLAN YOUR JOURNEY</small><b>Choose your pickup date &rarr;</b></div>
                    <div class="dc-phone-search">Find your ride &rarr;</div>
                    <div class="dc-phone-caption">Illustrative app preview</div>
                </div>
            </div>
        </div>
        <div class="dc-download-copy">
            <span class="dc-download-kicker">CONVENIENCE AT YOUR FINGERTIPS</span>
            <h2 id="dc-download-title">Get the DuraCabs App!</h2>
            <p>Apni ride, ab aur aasaan. Book self-drive cars and taxis, anytime, anywhere.</p>
            <a class="dc-app-play" href="https://play.google.com/store/apps/details?id=com.duracabs.app" target="_blank" rel="noopener noreferrer" aria-label="Download the DuraCabs customer app on Google Play">
                <svg width="27" height="30" viewBox="0 0 24 28" aria-hidden="true"><path fill="#3bccff" d="M2 1.5v25L14 14Z"/><path fill="#32df9a" d="M2 1.5 17 10.8 14 14Z"/><path fill="#ffcc40" d="m17 10.8 5 3.2-5 3.2-3-3.2Z"/><path fill="#ff6178" d="M2 26.5 17 17.2 14 14Z"/></svg>
                <span><small>GET IT ON</small><strong>Google Play</strong></span>
            </a>
            <span class="dc-download-platform">Available for Android</span>
        </div>
    </div>
</section>

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

{{-- Self-contained styles: no Tailwind rebuild or additional assets required. --}}
<style>
    .dc-download-banner{position:relative;margin-top:72px;background:linear-gradient(110deg,#102c48,#0b1b2d);border-radius:28px 28px 0 0;color:#fff;font-family:inherit}
    .dc-download-inner{box-sizing:border-box;max-width:1180px;margin:auto;display:grid;grid-template-columns:380px 1fr;align-items:center;gap:64px;padding:0 32px}
    .dc-phone-stage{position:relative;align-self:stretch;height:330px;clip-path:inset(-90px -40px 0)}
    .dc-phone{box-sizing:border-box;position:absolute;top:-56px;left:20px;width:292px;height:540px;padding:9px;border:3px solid #65717e;border-radius:46px;background:#10141b;box-shadow:0 18px 34px #0005;transform:rotate(-3deg)}
    .dc-phone-camera{position:absolute;top:16px;left:50%;transform:translateX(-50%);width:87px;height:20px;border-radius:16px;background:#10141b;z-index:1}
    .dc-phone-screen{box-sizing:border-box;height:100%;padding:40px 17px 20px;overflow:hidden;border-radius:34px;background:#f1f8fe;color:#15304a}
    .dc-phone-brand{font-weight:900;font-size:24px;letter-spacing:-1px}.dc-phone-brand>span{color:#0284c7}.dc-phone-brand>small{display:block;margin-top:3px;font-size:8px;letter-spacing:2px;color:#64748b}
    .dc-phone-welcome{margin:20px 0 10px;font-size:11px;font-weight:700}
    .dc-phone-hero{position:relative;padding:17px 13px 12px;border-radius:16px;background:linear-gradient(125deg,#cdeeff,#b3dcf5);overflow:hidden}.dc-phone-hero>span{font-size:7px;letter-spacing:1px;font-weight:800;color:#0369a1}.dc-phone-hero>strong{display:block;margin-top:7px;font-size:20px;line-height:1.2}.dc-phone-road{text-align:right;font-size:44px;line-height:1.25}
    .dc-phone-services{display:flex;gap:9px;margin-top:13px}.dc-phone-services>span{flex:1;padding:12px 5px;border:1px solid #d8e9f5;border-radius:12px;background:white;text-align:center;font-size:21px}.dc-phone-services b{display:block;margin-top:5px;font-size:9px}
    .dc-phone-field{margin-top:12px;padding:12px;border:1px solid #d8e9f5;border-radius:11px;background:#fff}.dc-phone-field small{display:block;margin-bottom:6px;font-size:7px;color:#64748b;letter-spacing:1px}.dc-phone-field b{font-size:10px}.dc-phone-search{margin-top:12px;padding:12px;border-radius:11px;background:#0284c7;color:#fff;font-size:11px;text-align:center;font-weight:800}.dc-phone-caption{margin-top:10px;color:#64748b;font-size:8px;text-align:center}
    .dc-download-copy{padding:48px 0}.dc-download-kicker{color:#93c5fd;font-size:12px;font-weight:800;letter-spacing:.1em}.dc-download-copy h2{margin:12px 0 14px;font-size:clamp(28px,4vw,42px);font-weight:800;line-height:1.12;color:#fff}.dc-download-copy p{max-width:480px;margin:0 0 23px;color:#cbd5e1;font-size:15px;line-height:1.7}.dc-download-platform{display:block;margin-top:12px;font-size:12px;color:#94a3b8}
    .dc-app-eyebrow{color:#7dd3fc;font-size:11px;font-weight:800;letter-spacing:.14em}
    .dc-app-footer h2{margin:8px 0;color:#fff;font-size:clamp(20px,3vw,26px);font-weight:800;line-height:1.25}
    .dc-app-footer p{margin:0;color:#cbd5e1;font-size:14px;line-height:1.6}
    .dc-app-play{display:inline-flex;align-items:center;justify-content:center;gap:12px;flex-shrink:0;padding:12px 22px;border:1px solid #64748b;border-radius:12px;background:#050b15;color:#fff;text-decoration:none;line-height:1.2;transition:background .15s}
    .dc-app-play:hover{background:#16243b;color:#fff}
    .dc-app-play small,.dc-app-play strong{display:block;text-align:left}
    .dc-app-play small{font-size:10px;letter-spacing:.09em}
    .dc-app-play strong{margin-top:3px;font-size:21px;font-weight:700}
    .dc-app-play:focus-visible,.dc-app-popup button:focus-visible{outline:3px solid #38bdf8;outline-offset:4px}
    .dc-app-popup{position:fixed;inset:0;box-sizing:border-box;width:calc(100% - 32px);max-width:420px;max-height:calc(100dvh - 32px);margin:auto;padding:36px 28px 24px;border:1px solid #bae6fd;border-radius:24px;background:linear-gradient(155deg,#eff9ff,#fff 65%);color:#0f172a;text-align:center;box-shadow:0 24px 90px #02061755;overflow:auto}
    .dc-app-popup::backdrop{background:rgba(2,6,23,.65)}
    .dc-app-popup .dc-app-close{position:absolute;right:12px;top:10px;width:40px;height:40px;border:0;border-radius:50%;background:transparent;color:#475569;font-size:28px;cursor:pointer}
    .dc-app-popup .dc-app-mark{display:grid;place-items:center;width:66px;height:66px;margin:4px auto 18px;border-radius:20px;background:#0284c7;color:#fff;font-size:22px;font-weight:900;box-shadow:0 10px 24px #0284c733}
    .dc-app-popup h2{margin:8px 0 12px;font-size:29px;line-height:1.2;font-weight:800;color:#0f172a}
    .dc-app-popup p{margin:0 0 22px;font-size:15px;line-height:1.7;color:#475569}
    .dc-app-popup .dc-app-eyebrow{color:#0369a1}
    .dc-app-popup .dc-app-play{box-sizing:border-box;width:100%}
    .dc-app-popup .dc-app-later{display:block;min-height:44px;margin:10px auto 0;padding:10px 20px;border:0;background:transparent;color:#475569;font-size:14px;cursor:pointer;text-decoration:underline;text-underline-offset:3px}
    @media(max-width:760px){.dc-download-banner{margin-top:28px;border-radius:22px 22px 0 0}.dc-download-inner{grid-template-columns:1fr;gap:0;padding:0 22px}.dc-download-copy{grid-row:1;text-align:center;padding:36px 0 26px}.dc-download-copy p{margin-left:auto;margin-right:auto}.dc-download-kicker{font-size:10px}.dc-phone-stage{height:265px;width:100%;clip-path:inset(0 -20px 0)}.dc-phone{top:8px;left:50%;transform:translateX(-50%);width:252px;height:485px;border-radius:38px}.dc-phone-screen{border-radius:26px;padding:36px 14px 20px}}
    @media(prefers-reduced-motion:reduce){.dc-app-play{transition:none}}
</style>

<dialog id="dc-app-promotion" class="dc-app-popup" aria-labelledby="dc-app-popup-title" aria-describedby="dc-app-popup-description">
    <button type="button" class="dc-app-close" data-dc-app-dismiss aria-label="Close app promotion" autofocus>&times;</button>
    <div class="dc-app-mark" aria-hidden="true">DC</div>
    <span class="dc-app-eyebrow">DOWNLOAD THE DURACABS APP</span>
    <h2 id="dc-app-popup-title">Apni ride,<br>ab aur aasaan!</h2>
    <p id="dc-app-popup-description">Self Drive ya Taxi &mdash; apni next ride book karein DuraCabs App se.</p>
    <a class="dc-app-play" data-dc-app-download href="https://play.google.com/store/apps/details?id=com.duracabs.app" target="_blank" rel="noopener noreferrer" aria-label="Download DuraCabs on Google Play">
        <svg width="27" height="30" viewBox="0 0 24 28" fill="currentColor" aria-hidden="true"><path d="M2 1.5v25L22 14 2 1.5Z"/></svg>
        <span><small>DOWNLOAD APP ON</small><strong>Google Play</strong></span>
    </a>
    <button type="button" class="dc-app-later" data-dc-app-dismiss>Not now</button>
</dialog>

@script
<script>
(() => {
    // Register once, including when the footer is rendered through Livewire navigation.
    if (window.duraCabsAppPromotion) {
        window.duraCabsAppPromotion.init();
        return;
    }
    const storageKey = 'duracabs.customerApp.dismissedUntil';
    const cooldown = 24 * 60 * 60 * 1000;
    let memoryUntil = 0;
    let timer;
    let listeners;
    let activeDialog;
    function isDismissed() {
        let savedUntil = 0;
        try { savedUntil = Number(localStorage.getItem(storageKey)) || 0; } catch (_) {}
        return Math.max(memoryUntil, savedUntil) > Date.now();
    }
    function dismiss() {
        memoryUntil = Date.now() + cooldown;
        try { localStorage.setItem(storageKey, String(memoryUntil)); } catch (_) {}
        if (activeDialog && activeDialog.open) activeDialog.close();
    }
    function cleanup() {
        clearTimeout(timer);
        if (listeners) listeners.abort();
        if (activeDialog && activeDialog.open) activeDialog.close();
        activeDialog = null;
    }
    function init() {
        cleanup();
        const dialog = document.getElementById('dc-app-promotion');
        if (!dialog || typeof dialog.showModal !== 'function' || isDismissed()) return;
        activeDialog = dialog;
        listeners = new AbortController();
        const options = { signal: listeners.signal };
        dialog.querySelectorAll('[data-dc-app-dismiss], [data-dc-app-download]').forEach(button => {
            button.addEventListener('click', dismiss, options);
        });
        dialog.addEventListener('cancel', event => { event.preventDefault(); dismiss(); }, options);
        let attempts = 0;
        function attemptOpen() {
            if (!dialog.isConnected || isDismissed() || dialog.open) return;
            if (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) return;
            const visibleModal = Array.from(document.querySelectorAll('dialog[open], [aria-modal="true"]')).some(element => {
                if (element === dialog || element.closest('[hidden], [aria-hidden="true"]')) return false;
                const style = window.getComputedStyle(element);
                return element.getClientRects().length > 0 && style.visibility !== 'hidden' && style.display !== 'none' && style.opacity !== '0';
            });
            const enteringText = document.activeElement && document.activeElement.matches('input, textarea, select, [contenteditable="true"]');
            if (document.visibilityState === 'hidden' || visibleModal || enteringText) {
                if (++attempts < 120) timer = setTimeout(attemptOpen, 1000);
                return;
            }
            try { dialog.showModal(); } catch (_) {
                if (++attempts < 120) timer = setTimeout(attemptOpen, 1000);
            }
        }
        timer = setTimeout(attemptOpen, 5000);
    }
    window.duraCabsAppPromotion = { init };
    document.addEventListener('livewire:navigating', cleanup);
    document.addEventListener('livewire:navigated', init);
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
    else init();
})();
</script>
@endscript
</div>
