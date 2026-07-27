/**
 * DuraCabs homepage interactions.
 *
 * File: resources/js/modules/homepage.js
 * This module is safe to run after normal navigation and Livewire morphs.
 */

const HERO_AUTOPLAY_MS = 5500;
const initializedSliders = new WeakMap();

function getScope(root) {
    return root instanceof Element || root instanceof Document ? root : document;
}

function initRipple(root = document) {
    const scope = getScope(root);

    scope.querySelectorAll(
        '.premium-search-button, .premium-secondary-button, .premium-quick-actions a, .premium-slide-button'
    ).forEach((element) => {
        if (element.dataset.rippleReady === 'true') return;
        element.dataset.rippleReady = 'true';

        element.addEventListener('pointerdown', (event) => {
            if (!(element instanceof HTMLElement)) return;

            const rect = element.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const ripple = document.createElement('span');

            ripple.className = 'premium-ripple-effect';
            ripple.style.width = `${size}px`;
            ripple.style.height = `${size}px`;
            ripple.style.left = `${event.clientX - rect.left - size / 2}px`;
            ripple.style.top = `${event.clientY - rect.top - size / 2}px`;

            element.appendChild(ripple);
            window.setTimeout(() => ripple.remove(), 700);
        });
    });
}

function initReadMore(root = document) {
    const scope = getScope(root);

    scope.querySelectorAll('[data-home-read-more-button]').forEach((button) => {
        if (!(button instanceof HTMLButtonElement)) return;
        if (button.dataset.readMoreReady === 'true') return;

        const container = button.closest('section, article, div')?.querySelector('[data-home-read-more-content]')
            ?? scope.querySelector('[data-home-read-more-content]');

        if (!(container instanceof HTMLElement)) return;

        button.dataset.readMoreReady = 'true';

        const update = (expanded) => {
            container.classList.toggle('is-collapsed', !expanded);
            container.classList.toggle('is-expanded', expanded);
            button.textContent = expanded ? 'Read Less' : 'Read More';
            button.setAttribute('aria-expanded', String(expanded));
        };

        update(false);

        button.addEventListener('click', () => {
            update(!container.classList.contains('is-expanded'));
        });
    });
}

function initReveal(root = document) {
    const scope = getScope(root);
    const items = [...scope.querySelectorAll('.premium-reveal:not(.is-visible)')];

    if (!items.length) return;

    if (!('IntersectionObserver' in window)) {
        items.forEach((item) => item.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver((entries, currentObserver) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('is-visible');
            currentObserver.unobserve(entry.target);
        });
    }, {
        threshold: 0.08,
        rootMargin: '0px 0px -30px 0px',
    });

    items.forEach((item) => observer.observe(item));
}

function initPremiumSlider(slider) {
    if (!(slider instanceof HTMLElement)) return;

    // Livewire can preserve the slider wrapper while replacing its children.
    // Always destroy the previous bindings and bind against the current DOM.
    const previousState = initializedSliders.get(slider);
    previousState?.destroy?.();

    const slides = [...slider.querySelectorAll('.premium-slide')]
        .filter((slide) => slide instanceof HTMLElement);
    const previousButton = slider.querySelector('[data-premium-slider-prev]');
    const nextButton = slider.querySelector('[data-premium-slider-next]');
    const dotsContainer = slider.querySelector('[data-premium-slider-dots]');
    const progressBar = slider.querySelector('[data-premium-slider-progress]');

    if (!slides.length) {
        previousButton?.setAttribute('hidden', '');
        nextButton?.setAttribute('hidden', '');
        initializedSliders.delete(slider);
        return;
    }

    previousButton?.removeAttribute('hidden');
    nextButton?.removeAttribute('hidden');

    let currentIndex = Math.max(
        0,
        slides.findIndex((slide) => slide.classList.contains('is-active'))
    );
    let timer = null;
    let progressAnimation = null;
    let touchStartX = 0;
    let touchStartY = 0;
    let destroyed = false;

    const dots = [];
    const controller = new AbortController();
    const listenerOptions = { signal: controller.signal };

    const stopProgress = () => {
        progressAnimation?.cancel();
        progressAnimation = null;

        if (progressBar instanceof HTMLElement) {
            progressBar.style.width = '0%';
        }
    };

    const stopAutoplay = () => {
        if (timer !== null) {
            window.clearInterval(timer);
        }

        timer = null;
        stopProgress();
    };

    const startProgress = () => {
        stopProgress();

        if (
            destroyed
            || !(progressBar instanceof HTMLElement)
            || slides.length < 2
        ) {
            return;
        }

        progressAnimation = progressBar.animate(
            [{ width: '0%' }, { width: '100%' }],
            {
                duration: HERO_AUTOPLAY_MS,
                easing: 'linear',
                fill: 'forwards',
            }
        );
    };

    const show = (requestedIndex, restart = true) => {
        if (destroyed || !slides.length) return;

        currentIndex = (requestedIndex + slides.length) % slides.length;

        slides.forEach((slide, index) => {
            const active = index === currentIndex;

            slide.classList.toggle('is-active', active);
            slide.setAttribute('aria-hidden', String(!active));

            if ('inert' in slide) {
                slide.inert = !active;
            }
        });

        dots.forEach((dot, index) => {
            const active = index === currentIndex;

            dot.classList.toggle('is-active', active);
            dot.setAttribute('aria-current', active ? 'true' : 'false');
        });

        if (restart) {
            startAutoplay();
        }
    };

    const startAutoplay = () => {
        stopAutoplay();

        if (
            destroyed
            || slides.length < 2
            || document.hidden
            || window.matchMedia('(prefers-reduced-motion: reduce)').matches
        ) {
            return;
        }

        startProgress();

        timer = window.setInterval(() => {
            show(currentIndex + 1, false);
            startProgress();
        }, HERO_AUTOPLAY_MS);
    };

    if (dotsContainer instanceof HTMLElement) {
        dotsContainer.replaceChildren();

        slides.forEach((slide, index) => {
            const dot = document.createElement('button');

            dot.type = 'button';
            dot.setAttribute('aria-label', `Show banner ${index + 1}`);
            dot.addEventListener('click', () => show(index), listenerOptions);

            dotsContainer.appendChild(dot);
            dots.push(dot);
        });
    }

    previousButton?.addEventListener(
        'click',
        (event) => {
            event.preventDefault();
            event.stopPropagation();
            show(currentIndex - 1);
        },
        listenerOptions
    );

    nextButton?.addEventListener(
        'click',
        (event) => {
            event.preventDefault();
            event.stopPropagation();
            show(currentIndex + 1);
        },
        listenerOptions
    );

    slider.addEventListener('mouseenter', stopAutoplay, listenerOptions);
    slider.addEventListener('mouseleave', startAutoplay, listenerOptions);
    slider.addEventListener('focusin', stopAutoplay, listenerOptions);
    slider.addEventListener(
        'focusout',
        (event) => {
            if (!slider.contains(event.relatedTarget)) {
                startAutoplay();
            }
        },
        listenerOptions
    );

    slider.addEventListener(
        'keydown',
        (event) => {
            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                show(currentIndex - 1);
            }

            if (event.key === 'ArrowRight') {
                event.preventDefault();
                show(currentIndex + 1);
            }
        },
        listenerOptions
    );

    slider.addEventListener(
        'touchstart',
        (event) => {
            const touch = event.changedTouches[0];

            touchStartX = touch.clientX;
            touchStartY = touch.clientY;
            stopAutoplay();
        },
        { passive: true, signal: controller.signal }
    );

    slider.addEventListener(
        'touchend',
        (event) => {
            const touch = event.changedTouches[0];
            const deltaX = touch.clientX - touchStartX;
            const deltaY = touch.clientY - touchStartY;

            if (
                Math.abs(deltaX) > 45
                && Math.abs(deltaX) > Math.abs(deltaY)
            ) {
                show(currentIndex + (deltaX < 0 ? 1 : -1));
            } else {
                startAutoplay();
            }
        },
        { passive: true, signal: controller.signal }
    );

    document.addEventListener(
        'visibilitychange',
        () => {
            if (document.hidden) {
                stopAutoplay();
            } else {
                startAutoplay();
            }
        },
        listenerOptions
    );

    const destroy = () => {
        if (destroyed) return;

        destroyed = true;
        stopAutoplay();
        controller.abort();
    };

    initializedSliders.set(slider, { destroy });

    show(currentIndex);
}

function initPremiumHero(root = document) {
    const scope = getScope(root);
    const sliders = [];

    if (scope instanceof Element && scope.matches('.premium-slider')) {
        sliders.push(scope);
    }

    scope.querySelectorAll('.premium-slider').forEach((slider) => {
        if (!sliders.includes(slider)) {
            sliders.push(slider);
        }
    });

    sliders.forEach(initPremiumSlider);
}

function initHomepageEnhancements(root = document) {
    initRipple(root);
    initReadMore(root);
    initReveal(root);
    initPremiumHero(root);
}

function bootHomepage(root = document) {
    window.requestAnimationFrame(() => initHomepageEnhancements(root));
}
function registerAlpineCarousel() {
    const register = () => {
        if (!window.Alpine || window.__duraCarouselRegistered) return;

        window.__duraCarouselRegistered = true;

        window.Alpine.data('carousel', () => ({
            prev: null,
            next: null,
            resizeObserver: null,

            init() {
                this.$nextTick(() => {
                    this.updateControls();

                    const container = this.$refs.container;

                    if (!container) return;

                    container.addEventListener(
                        'scroll',
                        () => this.updateControls(),
                        { passive: true }
                    );

                    if ('ResizeObserver' in window) {
                        this.resizeObserver = new ResizeObserver(() => {
                            this.updateControls();
                        });

                        this.resizeObserver.observe(container);
                    }

                    window.addEventListener(
                        'resize',
                        () => this.updateControls(),
                        { passive: true }
                    );
                });
            },

            updateControls() {
                const container = this.$refs.container;

                if (!container) {
                    this.prev = null;
                    this.next = null;
                    return;
                }

                const maxScrollLeft =
                    container.scrollWidth - container.clientWidth;

                this.prev = container.scrollLeft > 4 ? -1 : null;

                this.next =
                    container.scrollLeft < maxScrollLeft - 4
                        ? 1
                        : null;
            },

            scrollTo(direction) {
                const container = this.$refs.container;

                if (!container || direction === null) return;

                const distance = Math.max(
                    Math.round(container.clientWidth * 0.8),
                    280
                );

                container.scrollBy({
                    left: direction * distance,
                    behavior: 'smooth',
                });

                window.setTimeout(() => {
                    this.updateControls();
                }, 450);
            },

            destroy() {
                this.resizeObserver?.disconnect();
                this.resizeObserver = null;
            },
        }));
    };

    document.addEventListener('alpine:init', register, { once: true });

    // Alpine may already be initialized when this module executes.
    register();
}
registerAlpineCarousel();

document.addEventListener('DOMContentLoaded', () => bootHomepage(document), { once: true });
document.addEventListener('livewire:navigated', () => bootHomepage(document));

document.addEventListener('livewire:init', () => {
    if (!window.Livewire?.hook) return;

    window.Livewire.hook('morph.updated', ({ el }) => {
        bootHomepage(el instanceof Element ? el : document);
    });
});

// Some Livewire versions dispatch this event after initialization.
document.addEventListener('livewire:initialized', () => bootHomepage(document));

export { initHomepageEnhancements, initPremiumHero };
