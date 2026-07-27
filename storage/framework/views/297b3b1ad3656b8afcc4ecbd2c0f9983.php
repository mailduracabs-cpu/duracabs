<header
    class="sticky top-0 z-50 w-full border-b border-slate-200/80 bg-white/95 text-sm shadow-sm backdrop-blur"
    data-main-navbar
>
    <nav
        class="mx-auto w-full max-w-[85rem] px-4 sm:px-6 lg:px-8"
        aria-label="Main navigation"
    >
        <div class="flex min-h-16 flex-wrap items-center justify-between gap-3">

            
            <button
                type="button"
                id="mobile-menu-button"
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 text-slate-700 transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-600 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 md:hidden"
                aria-controls="main-navbar"
                aria-label="Open navigation menu"
                aria-expanded="false"
            >
                <i
                    id="mobile-menu-open-icon"
                    class="fa-solid fa-bars"
                    aria-hidden="true"
                ></i>

                <i
                    id="mobile-menu-close-icon"
                    class="fa-solid fa-xmark hidden"
                    aria-hidden="true"
                ></i>
            </button>

            
            <a
                href="/"
                class="flex min-w-0 flex-1 items-center justify-center md:flex-none md:justify-start"
                aria-label="Dura Cabs Home"
            >
                <img
                    src="<?php echo e(asset('img/logo/bluelogo.png')); ?>"
                    alt="Dura Cabs Services"
                    width="200"
                    height="64"
                    class="block h-12 w-auto max-w-[170px] object-contain sm:max-w-[190px]"
                    loading="eager"
                    fetchpriority="high"
                >
            </a>

            
            <a
                href="<?php echo e(auth()->check() ? '/my-account' : '/login'); ?>"
                class="relative inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-600 text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 md:hidden"
                aria-label="Notifications"
            >
                <i class="fa-regular fa-bell" aria-hidden="true"></i>

                <span
                    class="absolute right-2 top-2 h-2 w-2 rounded-full border border-white bg-red-500"
                    aria-hidden="true"
                ></span>
            </a>

            
            <div
                id="main-navbar"
                class="hidden w-full basis-full md:block md:w-auto md:basis-auto"
            >
                <div
                    class="flex flex-col gap-1 border-t border-slate-100 py-3 md:flex-row md:items-center md:gap-1 md:border-0 md:py-0"
                >
                    <?php
                        $navLink = 'inline-flex items-center rounded-lg px-3 py-2.5 font-semibold transition hover:bg-sky-50 hover:text-sky-700';
                        $active = 'bg-sky-50 text-sky-700';
                        $idle = 'text-slate-700';
                    ?>

                    
                    <a
                        href="/"
                        class="<?php echo e($navLink); ?> <?php echo e(request()->is('/') ? $active : $idle); ?>"
                        <?php if(request()->is('/')): ?> aria-current="page" <?php endif; ?>
                    >
                        <i
                            class="fa-solid fa-house mr-2 text-xs"
                            aria-hidden="true"
                        ></i>
                        Home
                    </a>

                    
                    <a
                        href="/checkout"
                        rel="nofollow"
                        class="<?php echo e($navLink); ?> <?php echo e(request()->is('checkout') ? $active : $idle); ?>"
                        <?php if(request()->is('checkout')): ?> aria-current="page" <?php endif; ?>
                    >
                        <i
                            class="fa-solid fa-receipt mr-2 text-xs"
                            aria-hidden="true"
                        ></i>
                        Checkout
                    </a>

                    
                    <a
                        href="/contact-us"
                        class="<?php echo e($navLink); ?> <?php echo e(request()->is('contact-us') ? $active : $idle); ?>"
                        <?php if(request()->is('contact-us')): ?> aria-current="page" <?php endif; ?>
                    >
                        <i
                            class="fa-solid fa-envelope mr-2 text-xs"
                            aria-hidden="true"
                        ></i>
                        Contact
                    </a>

                    
                    <a
                        href="https://api.whatsapp.com/send/?phone=917088873331&text=Hi&type=phone_number&app_absent=0"
                        target="_blank"
                        rel="nofollow noopener noreferrer"
                        class="<?php echo e($navLink); ?> text-emerald-600 hover:bg-emerald-50 hover:text-emerald-700"
                    >
                        <i
                            class="fa-brands fa-whatsapp mr-2 text-base"
                            aria-hidden="true"
                        ></i>
                        WhatsApp
                    </a>

                    
                    <a
                        href="tel:+917088873331"
                        class="hidden items-center rounded-xl border border-sky-200 px-3 py-2.5 font-bold text-sky-700 transition hover:bg-sky-50 lg:inline-flex"
                        aria-label="Call Dura Cabs"
                    >
                        <i
                            class="fa-solid fa-phone mr-2 text-xs"
                            aria-hidden="true"
                        ></i>
                        +91 70888 73331
                    </a>

                    <!--[if BLOCK]><![endif]--><?php if(auth()->guard()->guest()): ?>
                        
                        <a
                            href="/login"
                            class="inline-flex items-center justify-center rounded-xl bg-sky-600 px-4 py-2.5 font-bold text-white shadow-sm transition hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2"
                        >
                            <i
                                class="fa-regular fa-user mr-2"
                                aria-hidden="true"
                            ></i>
                            Login
                        </a>
                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                    <!--[if BLOCK]><![endif]--><?php if(auth()->guard()->check()): ?>
                        
                        <div class="relative" data-user-dropdown>
                            <button
                                type="button"
                                data-user-dropdown-button
                                class="inline-flex w-full items-center justify-between rounded-xl bg-slate-100 px-4 py-2.5 font-bold text-slate-700 transition hover:bg-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 md:w-auto"
                                aria-expanded="false"
                                aria-controls="user-dropdown-menu"
                            >
                                <span class="max-w-40 truncate">
                                    <i
                                        class="fa-regular fa-circle-user mr-2"
                                        aria-hidden="true"
                                    ></i>
                                    <?php echo e(auth()->user()->name); ?>

                                </span>

                                <i
                                    data-user-dropdown-chevron
                                    class="fa-solid fa-chevron-down ml-2 text-[10px] transition-transform"
                                    aria-hidden="true"
                                ></i>
                            </button>

                            <div
                                id="user-dropdown-menu"
                                data-user-dropdown-menu
                                class="z-50 mt-2 hidden min-w-48 rounded-xl border border-slate-200 bg-white p-2 shadow-xl md:absolute md:right-0"
                            >
                                <a
                                    href="/my-orders"
                                    class="flex items-center rounded-lg px-3 py-2.5 text-slate-700 transition hover:bg-slate-100"
                                >
                                    <i
                                        class="fa-solid fa-car-side mr-3 text-sky-600"
                                        aria-hidden="true"
                                    ></i>
                                    My Orders
                                </a>

                                <a
                                    href="/my-account"
                                    class="flex items-center rounded-lg px-3 py-2.5 text-slate-700 transition hover:bg-slate-100"
                                >
                                    <i
                                        class="fa-solid fa-user-gear mr-3 text-sky-600"
                                        aria-hidden="true"
                                    ></i>
                                    My Account
                                </a>

                                <a
                                    href="/logout"
                                    class="flex items-center rounded-lg px-3 py-2.5 text-red-600 transition hover:bg-red-50"
                                >
                                    <i
                                        class="fa-solid fa-right-from-bracket mr-3"
                                        aria-hidden="true"
                                    ></i>
                                    Logout
                                </a>
                            </div>
                        </div>
                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                </div>
            </div>
        </div>
    </nav>
</header>

    <?php
        $__scriptKey = '1347191181-0';
        ob_start();
    ?>
<script>
    (() => {
        /*
         * Event delegation is used here so the navbar continues working
         * after login/logout and after Livewire replaces navbar HTML.
         */
        if (window.__duraNavbarEventsReady) {
            return;
        }

        window.__duraNavbarEventsReady = true;

        const getNavbar = (element = null) => {
            if (element instanceof Element) {
                return element.closest('[data-main-navbar]');
            }

            return document.querySelector('[data-main-navbar]');
        };

        const getParts = (navbar) => ({
            menuButton: navbar?.querySelector('#mobile-menu-button'),
            menu: navbar?.querySelector('#main-navbar'),
            openIcon: navbar?.querySelector('#mobile-menu-open-icon'),
            closeIcon: navbar?.querySelector('#mobile-menu-close-icon'),
            dropdown: navbar?.querySelector('[data-user-dropdown]'),
            dropdownButton: navbar?.querySelector('[data-user-dropdown-button]'),
            dropdownMenu: navbar?.querySelector('[data-user-dropdown-menu]'),
            dropdownChevron: navbar?.querySelector('[data-user-dropdown-chevron]'),
        });

        const closeMobileMenu = (navbar) => {
            const {
                menuButton,
                menu,
                openIcon,
                closeIcon,
            } = getParts(navbar);

            if (!menu || !menuButton) {
                return;
            }

            menu.classList.add('hidden');
            menuButton.setAttribute('aria-expanded', 'false');
            menuButton.setAttribute('aria-label', 'Open navigation menu');

            openIcon?.classList.remove('hidden');
            closeIcon?.classList.add('hidden');
        };

        const openMobileMenu = (navbar) => {
            const {
                menuButton,
                menu,
                openIcon,
                closeIcon,
            } = getParts(navbar);

            if (!menu || !menuButton) {
                return;
            }

            menu.classList.remove('hidden');
            menuButton.setAttribute('aria-expanded', 'true');
            menuButton.setAttribute('aria-label', 'Close navigation menu');

            openIcon?.classList.add('hidden');
            closeIcon?.classList.remove('hidden');
        };

        const closeUserDropdown = (navbar) => {
            const {
                dropdownButton,
                dropdownMenu,
                dropdownChevron,
            } = getParts(navbar);

            dropdownMenu?.classList.add('hidden');
            dropdownButton?.setAttribute('aria-expanded', 'false');
            dropdownChevron?.classList.remove('rotate-180');
        };

        const toggleUserDropdown = (navbar) => {
            const {
                dropdownButton,
                dropdownMenu,
                dropdownChevron,
            } = getParts(navbar);

            if (!dropdownButton || !dropdownMenu) {
                return;
            }

            const isOpen =
                dropdownButton.getAttribute('aria-expanded') === 'true';

            if (isOpen) {
                closeUserDropdown(navbar);
                return;
            }

            dropdownMenu.classList.remove('hidden');
            dropdownButton.setAttribute('aria-expanded', 'true');
            dropdownChevron?.classList.add('rotate-180');
        };

        document.addEventListener('click', (event) => {
            const target = event.target;

            if (!(target instanceof Element)) {
                return;
            }

            const menuButton = target.closest('#mobile-menu-button');

            if (menuButton) {
                const navbar = getNavbar(menuButton);

                if (!navbar) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                const isOpen =
                    menuButton.getAttribute('aria-expanded') === 'true';

                if (isOpen) {
                    closeMobileMenu(navbar);
                } else {
                    openMobileMenu(navbar);
                }

                return;
            }

            const dropdownButton =
                target.closest('[data-user-dropdown-button]');

            if (dropdownButton) {
                const navbar = getNavbar(dropdownButton);

                if (!navbar) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                toggleUserDropdown(navbar);
                return;
            }

            const navbar = getNavbar(target);

            if (navbar) {
                const { dropdown } = getParts(navbar);

                if (dropdown && !dropdown.contains(target)) {
                    closeUserDropdown(navbar);
                }

                const clickedMenuLink = target.closest('#main-navbar a');

                if (clickedMenuLink && window.innerWidth < 768) {
                    closeMobileMenu(navbar);
                }
            } else {
                document
                    .querySelectorAll('[data-main-navbar]')
                    .forEach(closeUserDropdown);
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            document
                .querySelectorAll('[data-main-navbar]')
                .forEach((navbar) => {
                    closeMobileMenu(navbar);
                    closeUserDropdown(navbar);
                });
        });

        window.addEventListener('resize', () => {
            document
                .querySelectorAll('[data-main-navbar]')
                .forEach((navbar) => {
                    const {
                        menuButton,
                        menu,
                        openIcon,
                        closeIcon,
                    } = getParts(navbar);

                    if (window.innerWidth >= 768) {
                        menu?.classList.remove('hidden');
                        menuButton?.setAttribute('aria-expanded', 'false');
                        menuButton?.setAttribute(
                            'aria-label',
                            'Open navigation menu'
                        );
                        openIcon?.classList.remove('hidden');
                        closeIcon?.classList.add('hidden');
                    } else {
                        closeMobileMenu(navbar);
                    }

                    closeUserDropdown(navbar);
                });
        });

        /*
         * Reset visual state after Livewire navigation.
         * Event listeners do not need to be attached again.
         */
        document.addEventListener('livewire:navigated', () => {
            document
                .querySelectorAll('[data-main-navbar]')
                .forEach((navbar) => {
                    closeUserDropdown(navbar);

                    if (window.innerWidth < 768) {
                        closeMobileMenu(navbar);
                    }
                });
        });
    })();
</script>
    <?php
        $__output = ob_get_clean();

        \Livewire\store($this)->push('scripts', $__output, $__scriptKey)
    ?><?php /**PATH C:\xampp\htdocs\duracabs\resources\views/livewire/partials/navbar.blade.php ENDPATH**/ ?>