@php
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
@endphp

<div
    id="dura-cookie-consent"
    class="dura-cookie-consent"
    data-cookie-consent='@json($cookieConfig)'
    hidden
>
    <section class="dura-cookie-banner" role="dialog" aria-modal="true" aria-labelledby="dura-cookie-title">
        <div class="dura-cookie-copy">
            <h2 id="dura-cookie-title">Your privacy matters</h2>
            <p>
                Necessary cookies keep Dura Cabs secure and allow bookings to work. Optional cookies help us improve the website and measure advertising.
                <a href="{{ route('cookie-policy') }}">Cookie Policy</a>
            </p>
        </div>

        <div class="dura-cookie-actions">
            <button type="button" class="dura-cookie-button dura-cookie-button--ghost" data-cookie-action="reject">Reject optional</button>
            <button type="button" class="dura-cookie-button dura-cookie-button--secondary" data-cookie-action="customize">Customize</button>
            <button type="button" class="dura-cookie-button dura-cookie-button--primary" data-cookie-action="accept">Accept all</button>
        </div>
    </section>

    <section class="dura-cookie-preferences" data-cookie-preferences hidden role="dialog" aria-modal="true" aria-labelledby="dura-cookie-preferences-title">
        <div class="dura-cookie-preferences__panel">
            <div class="dura-cookie-preferences__header">
                <div>
                    <h2 id="dura-cookie-preferences-title">Cookie preferences</h2>
                    <p>Choose which optional cookies Dura Cabs may use.</p>
                </div>
                <button type="button" class="dura-cookie-close" data-cookie-action="close" aria-label="Close cookie preferences">×</button>
            </div>

            <label class="dura-cookie-row">
                <span><strong>Necessary</strong><small>Required for security, sessions and bookings.</small></span>
                <input type="checkbox" checked disabled>
            </label>
            <label class="dura-cookie-row">
                <span><strong>Preferences</strong><small>Remember optional display and website choices.</small></span>
                <input type="checkbox" data-cookie-category="preferences">
            </label>
            <label class="dura-cookie-row">
                <span><strong>Analytics</strong><small>Measure website usage and performance.</small></span>
                <input type="checkbox" data-cookie-category="analytics">
            </label>
            <label class="dura-cookie-row">
                <span><strong>Marketing</strong><small>Enable advertising and conversion measurement.</small></span>
                <input type="checkbox" data-cookie-category="marketing">
            </label>

            <div class="dura-cookie-preferences__actions">
                <button type="button" class="dura-cookie-button dura-cookie-button--ghost" data-cookie-action="reject">Reject optional</button>
                <button type="button" class="dura-cookie-button dura-cookie-button--primary" data-cookie-action="save">Save preferences</button>
            </div>
        </div>
    </section>
</div>
