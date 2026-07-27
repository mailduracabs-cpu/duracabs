<?php
    $cookieConfig = [
        'name' => config('cookie-consent.cookie_name'),
        'storageKey' => config('cookie-consent.storage_key'),
        'version' => (string) config('cookie-consent.version'),
        'days' => (int) config('cookie-consent.lifetime_days'),
        'path' => (string) config('cookie-consent.path', '/'),
        'domain' => config('cookie-consent.domain'),
        'secure' => (bool) config('cookie-consent.secure'),
        'sameSite' => (string) config('cookie-consent.same_site', 'Lax'),
    ];
?>

<div
    id="dura-cookie-consent"
    class="dura-cookie-consent"
    data-cookie-consent='<?php echo json_encode($cookieConfig, 15, 512) ?>'
    data-cookie-no-auto-popup="true"
>
    
    <section
        class="dura-cookie-banner"
        data-cookie-banner
        hidden
        aria-hidden="true"
    >
        <button
            type="button"
            data-cookie-action="reject"
            tabindex="-1"
        >
            Reject optional
        </button>

        <button
            type="button"
            data-cookie-action="customize"
            tabindex="-1"
        >
            Customize
        </button>

        <button
            type="button"
            data-cookie-action="accept"
            tabindex="-1"
        >
            Accept all
        </button>
    </section>

    
    <button
        type="button"
        class="dura-cookie-settings-link"
        data-cookie-settings-open
        aria-haspopup="dialog"
        aria-controls="dura-cookie-preferences"
    >
        🍪 Cookie Settings
    </button>

    
    <section
        id="dura-cookie-preferences"
        class="dura-cookie-preferences"
        data-cookie-preferences
        hidden
        role="dialog"
        aria-modal="true"
        aria-labelledby="dura-cookie-preferences-title"
    >
        <div class="dura-cookie-preferences__panel">
            <div class="dura-cookie-preferences__header">
                <div>
                    <h2 id="dura-cookie-preferences-title">Cookie Preferences</h2>
                    <p>Choose which optional cookies Dura Cabs may use.</p>
                </div>

                <button
                    type="button"
                    class="dura-cookie-close"
                    data-cookie-action="close"
                    aria-label="Close cookie preferences"
                >
                    ×
                </button>
            </div>

            <label class="dura-cookie-row dura-cookie-row--required">
                <span>
                    <strong>Necessary</strong>
                    <small>Required for security, sessions and bookings.</small>
                </span>

                <input
                    type="checkbox"
                    checked
                    disabled
                    aria-label="Necessary cookies are always enabled"
                >
            </label>

            <label class="dura-cookie-row">
                <span>
                    <strong>Preferences</strong>
                    <small>Remember optional display and website choices.</small>
                </span>

                <input
                    type="checkbox"
                    data-cookie-category="preferences"
                >
            </label>

            <label class="dura-cookie-row">
                <span>
                    <strong>Analytics</strong>
                    <small>Measure website usage and performance.</small>
                </span>

                <input
                    type="checkbox"
                    data-cookie-category="analytics"
                >
            </label>

            <label class="dura-cookie-row">
                <span>
                    <strong>Marketing</strong>
                    <small>Enable advertising and conversion measurement.</small>
                </span>

                <input
                    type="checkbox"
                    data-cookie-category="marketing"
                >
            </label>

            <div class="dura-cookie-preferences__actions">
                <button
                    type="button"
                    class="dura-cookie-button dura-cookie-button--ghost"
                    data-cookie-action="reject"
                >
                    Necessary Only
                </button>

                <button
                    type="button"
                    class="dura-cookie-button dura-cookie-button--primary"
                    data-cookie-action="save"
                >
                    Save Preferences
                </button>
            </div>
        </div>
    </section>
</div>

<style>
    .dura-cookie-banner[hidden],
    .dura-cookie-preferences[hidden] {
        display: none !important;
    }

    /*
     * The large cookie popup is permanently disabled.
     * This rule also prevents older JavaScript from showing it.
     */
    #dura-cookie-consent .dura-cookie-banner {
        display: none !important;
    }

    .dura-cookie-settings-link {
        position: fixed;
        right: 14px;
        bottom: 8px;
        z-index: 99998;
        padding: 4px 7px;
        border: 0;
        border-radius: 7px;
        background: rgba(255, 255, 255, .88);
        color: #64748b;
        box-shadow: 0 4px 14px rgba(15, 23, 42, .08);
        cursor: pointer;
        font: inherit;
        font-size: 11px;
        font-weight: 700;
        line-height: 1.3;
        opacity: .82;
        backdrop-filter: blur(8px);
        transition:
            color .2s ease,
            opacity .2s ease,
            transform .2s ease;
    }

    .dura-cookie-settings-link:hover,
    .dura-cookie-settings-link:focus-visible {
        color: #0284c7;
        opacity: 1;
        outline: none;
        transform: translateY(-1px);
    }

    .dura-cookie-preferences {
        position: fixed;
        inset: 0;
        z-index: 1000000;
        display: grid;
        place-items: center;
        padding: 16px;
        background: rgba(15, 23, 42, .54);
        backdrop-filter: blur(5px);
        animation: duraCookieFadeIn .2s ease-out both;
    }

    .dura-cookie-preferences__panel {
        width: min(520px, 100%);
        max-height: min(82vh, 680px);
        overflow-y: auto;
        border: 1px solid rgba(148, 163, 184, .25);
        border-radius: 18px;
        background: #ffffff;
        padding: 18px;
        box-shadow: 0 24px 70px rgba(15, 23, 42, .28);
        animation: duraCookiePanelIn .24s ease-out both;
    }

    .dura-cookie-preferences__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 14px;
    }

    .dura-cookie-preferences__header h2 {
        margin: 0;
        color: #0f172a;
        font-size: 20px;
        font-weight: 800;
        line-height: 1.2;
    }

    .dura-cookie-preferences__header p {
        margin: 5px 0 0;
        color: #64748b;
        font-size: 13px;
        line-height: 1.45;
    }

    .dura-cookie-close {
        display: inline-grid;
        flex: 0 0 auto;
        width: 32px;
        height: 32px;
        place-items: center;
        border: 0;
        border-radius: 9px;
        background: #f1f5f9;
        color: #64748b;
        cursor: pointer;
        font-size: 21px;
        line-height: 1;
        transition:
            background .2s ease,
            color .2s ease;
    }

    .dura-cookie-close:hover,
    .dura-cookie-close:focus-visible {
        background: #e2e8f0;
        color: #0f172a;
        outline: none;
    }

    .dura-cookie-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 13px 0;
        border-top: 1px solid #e2e8f0;
        cursor: pointer;
    }

    .dura-cookie-row--required {
        cursor: default;
    }

    .dura-cookie-row span {
        display: grid;
        gap: 3px;
        min-width: 0;
    }

    .dura-cookie-row strong {
        color: #0f172a;
        font-size: 14px;
        font-weight: 800;
    }

    .dura-cookie-row small {
        color: #64748b;
        font-size: 12px;
        line-height: 1.4;
    }

    .dura-cookie-row input[type="checkbox"] {
        width: 19px;
        height: 19px;
        flex: 0 0 auto;
        accent-color: #0284c7;
        cursor: pointer;
    }

    .dura-cookie-row input:disabled {
        cursor: not-allowed;
        opacity: .7;
    }

    .dura-cookie-preferences__actions {
        display: flex;
        justify-content: flex-end;
        gap: 9px;
        padding-top: 15px;
        border-top: 1px solid #e2e8f0;
    }

    .dura-cookie-button {
        min-height: 40px;
        border-radius: 10px;
        padding: 0 15px;
        cursor: pointer;
        font: inherit;
        font-size: 12px;
        font-weight: 800;
        transition:
            background .2s ease,
            border-color .2s ease,
            transform .2s ease;
    }

    .dura-cookie-button:hover {
        transform: translateY(-1px);
    }

    .dura-cookie-button--ghost {
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #334155;
    }

    .dura-cookie-button--ghost:hover {
        background: #f8fafc;
        border-color: #94a3b8;
    }

    .dura-cookie-button--primary {
        border: 1px solid #0284c7;
        background: #0284c7;
        color: #ffffff;
    }

    .dura-cookie-button--primary:hover {
        border-color: #0369a1;
        background: #0369a1;
    }

    @keyframes duraCookieFadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes duraCookiePanelIn {
        from {
            opacity: 0;
            transform: translateY(12px) scale(.985);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    @media (max-width: 640px) {
        .dura-cookie-settings-link {
            right: 8px;
            bottom: 5px;
        }

        .dura-cookie-preferences {
            align-items: end;
            padding: 10px;
        }

        .dura-cookie-preferences__panel {
            width: 100%;
            max-height: 88vh;
            border-radius: 18px 18px 12px 12px;
            padding: 16px;
        }

        .dura-cookie-preferences__actions {
            flex-direction: column-reverse;
        }

        .dura-cookie-button {
            width: 100%;
        }
    }
</style>

<script>
    (() => {
        const initialiseDuraCookieSettings = () => {
            const root = document.getElementById('dura-cookie-consent');

            if (!root || root.dataset.settingsInitialised === 'true') {
                return;
            }

            root.dataset.settingsInitialised = 'true';

            const banner = root.querySelector('[data-cookie-banner]');
            const preferences = root.querySelector('[data-cookie-preferences]');
            const openButton = root.querySelector('[data-cookie-settings-open]');
            const closeButton = preferences?.querySelector('[data-cookie-action="close"]');
            const saveButton = preferences?.querySelector('[data-cookie-action="save"]');
            const rejectButton = preferences?.querySelector('[data-cookie-action="reject"]');

            /*
             * Never show the automatic consent banner.
             * Necessary cookies continue to work.
             * Optional categories stay off unless the visitor enables them.
             */
            const permanentlyHideBanner = () => {
                if (!banner) {
                    return;
                }

                banner.hidden = true;
                banner.setAttribute('aria-hidden', 'true');
                banner.style.setProperty('display', 'none', 'important');
            };

            const openPreferences = () => {
                permanentlyHideBanner();

                if (!preferences) {
                    return;
                }

                preferences.hidden = false;
                document.documentElement.style.overflow = 'hidden';

                window.requestAnimationFrame(() => {
                    closeButton?.focus();
                });
            };

            const closePreferences = () => {
                if (!preferences) {
                    return;
                }

                preferences.hidden = true;
                document.documentElement.style.removeProperty('overflow');
                openButton?.focus();
            };

            permanentlyHideBanner();

            /*
             * Older/external consent scripts may try to unhide the banner.
             * Keep watching it and immediately hide it again.
             */
            if (banner) {
                const bannerObserver = new MutationObserver(permanentlyHideBanner);

                bannerObserver.observe(banner, {
                    attributes: true,
                    attributeFilter: ['hidden', 'class', 'style'],
                });
            }

            openButton?.addEventListener('click', openPreferences);
            closeButton?.addEventListener('click', closePreferences);

            /*
             * Existing cookie-consent JavaScript still handles saving/rejecting.
             * We only close the preferences panel after that action.
             */
            saveButton?.addEventListener('click', () => {
                window.setTimeout(closePreferences, 0);
            });

            rejectButton?.addEventListener('click', () => {
                window.setTimeout(closePreferences, 0);
            });

            preferences?.addEventListener('click', (event) => {
                if (event.target === preferences) {
                    closePreferences();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (
                    event.key === 'Escape' &&
                    preferences &&
                    !preferences.hidden
                ) {
                    closePreferences();
                }
            });
        };

        if (document.readyState === 'loading') {
            document.addEventListener(
                'DOMContentLoaded',
                initialiseDuraCookieSettings,
                { once: true }
            );
        } else {
            initialiseDuraCookieSettings();
        }

        document.addEventListener(
            'livewire:navigated',
            initialiseDuraCookieSettings
        );
    })();
</script><?php /**PATH C:\xampp\htdocs\duracabs\resources\views/components/cookie-consent.blade.php ENDPATH**/ ?>