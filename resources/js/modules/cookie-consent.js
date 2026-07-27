const safeJsonParse = (value, fallback = null) => {
    try {
        return JSON.parse(value);
    } catch {
        return fallback;
    }
};

const toBase64Url = (value) => {
    const bytes = new TextEncoder().encode(value);
    let binary = '';
    bytes.forEach((byte) => { binary += String.fromCharCode(byte); });
    return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
};

const fromBase64Url = (value) => {
    const normalized = value.replace(/-/g, '+').replace(/_/g, '/');
    const padded = normalized + '='.repeat((4 - (normalized.length % 4)) % 4);
    const binary = atob(padded);
    const bytes = Uint8Array.from(binary, (character) => character.charCodeAt(0));
    return new TextDecoder().decode(bytes);
};

const decodeConsent = (value) => {
    if (!value) return null;

    const decodedValue = decodeURIComponent(value);
    const legacy = safeJsonParse(decodedValue);
    if (legacy) return legacy;

    try {
        return safeJsonParse(fromBase64Url(decodedValue));
    } catch {
        return null;
    }
};

const initCookieConsent = () => {
    const root = document.getElementById('dura-cookie-consent');
    if (!root || root.dataset.initialized === 'true') return;

    root.dataset.initialized = 'true';

    const config = safeJsonParse(root.dataset.cookieConsent, {});
    const preferencesDialog = root.querySelector('[data-cookie-preferences]');
    const categoryInputs = [...root.querySelectorAll('[data-cookie-category]')];
    const cookieName = config.name || 'duracabs_cookie_consent';
    const storageKey = config.storageKey || cookieName;

    const findCookieValue = () => {
        const prefix = `${encodeURIComponent(cookieName)}=`;
        const row = document.cookie.split('; ').find((item) => item.startsWith(prefix));
        return row ? row.slice(prefix.length) : null;
    };

    const normalize = (value) => {
        if (!value || String(value.version) !== String(config.version)) return null;

        return {
            version: String(config.version),
            necessary: true,
            preferences: Boolean(value.preferences),
            analytics: Boolean(value.analytics),
            marketing: Boolean(value.marketing),
            updated_at: value.updated_at || value.updatedAt || null,
        };
    };

    const readConsent = () => {
        const cookieConsent = normalize(decodeConsent(findCookieValue()));
        if (cookieConsent) return cookieConsent;

        try {
            return normalize(safeJsonParse(localStorage.getItem(storageKey)));
        } catch {
            return null;
        }
    };

    const persistConsent = (consent) => {
        const json = JSON.stringify(consent);
        const encoded = toBase64Url(json);
        const days = Math.max(1, Number(config.days || 365));
        const maxAge = Math.round(days * 86400);
        const expires = new Date(Date.now() + maxAge * 1000).toUTCString();
        const sameSite = ['Lax', 'Strict', 'None'].includes(config.sameSite) ? config.sameSite : 'Lax';
        const path = config.path || '/';
        const isHttps = window.location.protocol === 'https:';
        const secure = (Boolean(config.secure) || sameSite === 'None') && isHttps ? '; Secure' : '';
        const domain = config.domain ? `; Domain=${config.domain}` : '';

        document.cookie = `${encodeURIComponent(cookieName)}=${encoded}; Max-Age=${maxAge}; Expires=${expires}; Path=${path}; SameSite=${sameSite}${domain}${secure}`;

        try {
            localStorage.setItem(storageKey, json);
        } catch {
            // Browsers may disable localStorage; the first-party cookie remains primary.
        }

        if (!findCookieValue()) {
            console.warn('[Dura Cabs] Consent cookie could not be written. Check browser privacy settings and COOKIE_CONSENT_DOMAIN.');
        }
    };

    const updateGoogleConsent = (consent) => {
        window.dataLayer = window.dataLayer || [];
        window.gtag = window.gtag || function gtag() { window.dataLayer.push(arguments); };

        window.gtag('consent', 'update', {
            analytics_storage: consent.analytics ? 'granted' : 'denied',
            ad_storage: consent.marketing ? 'granted' : 'denied',
            ad_user_data: consent.marketing ? 'granted' : 'denied',
            ad_personalization: consent.marketing ? 'granted' : 'denied',
            functionality_storage: consent.preferences ? 'granted' : 'denied',
            personalization_storage: consent.preferences ? 'granted' : 'denied',
            security_storage: 'granted',
        });
    };

    const publishConsent = (consent) => {
        window.duraCookieConsent = consent;
        updateGoogleConsent(consent);
        window.dispatchEvent(new CustomEvent('dura:cookie-consent', { detail: consent }));
    };

    const hideDialogs = () => {
        root.hidden = true;
        if (preferencesDialog) preferencesDialog.hidden = true;
        document.documentElement.classList.remove('dura-cookie-modal-open');
    };

    const saveConsent = (values) => {
        const consent = {
            version: String(config.version),
            necessary: true,
            preferences: Boolean(values.preferences),
            analytics: Boolean(values.analytics),
            marketing: Boolean(values.marketing),
            updated_at: new Date().toISOString(),
        };

        persistConsent(consent);
        hideDialogs();
        publishConsent(consent);
    };

    const openPreferences = () => {
        const current = readConsent();
        categoryInputs.forEach((input) => {
            input.checked = Boolean(current?.[input.dataset.cookieCategory]);
        });
        if (preferencesDialog) preferencesDialog.hidden = false;
        document.documentElement.classList.add('dura-cookie-modal-open');
    };

    const current = readConsent();
    if (current) {
        hideDialogs();
        publishConsent(current);
    } else {
        root.hidden = false;
    }

    root.addEventListener('click', (event) => {
        const button = event.target.closest('[data-cookie-action]');
        if (!button) return;

        switch (button.dataset.cookieAction) {
            case 'accept':
                saveConsent({ preferences: true, analytics: true, marketing: true });
                break;
            case 'reject':
                saveConsent({ preferences: false, analytics: false, marketing: false });
                break;
            case 'customize':
                openPreferences();
                break;
            case 'close':
                if (preferencesDialog) preferencesDialog.hidden = true;
                document.documentElement.classList.remove('dura-cookie-modal-open');
                break;
            case 'save':
                saveConsent(Object.fromEntries(
                    categoryInputs.map((input) => [input.dataset.cookieCategory, input.checked]),
                ));
                break;
            default:
                break;
        }
    });

    window.addEventListener('dura:open-cookie-preferences', openPreferences);
};

document.addEventListener('DOMContentLoaded', initCookieConsent);
document.addEventListener('livewire:navigated', initCookieConsent);
