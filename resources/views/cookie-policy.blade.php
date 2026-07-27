<x-layouts.app>
    @section('title', 'Cookie Policy | Dura Cabs')
    @section('description', 'Learn how Dura Cabs uses necessary, preference, analytics and marketing cookies.')

    <article class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-slate-900">Cookie Policy</h1>
        <p class="mt-4 text-slate-600">Last updated: {{ now()->format('d F Y') }}</p>

        <div class="prose prose-slate mt-8 max-w-none">
            <p>Dura Cabs uses cookies and similar browser storage to operate the website, protect bookings, remember preferences and—only after consent—measure website usage or advertising performance.</p>

            <h2>Necessary cookies</h2>
            <p>These cookies support security, CSRF protection, sessions, login, checkout and booking functionality. They cannot be disabled through the consent manager.</p>

            <h2>Preference cookies</h2>
            <p>These remember optional choices such as interface preferences.</p>

            <h2>Analytics cookies</h2>
            <p>These may be used to understand how visitors use the website. Analytics storage remains denied until you grant consent.</p>

            <h2>Marketing cookies</h2>
            <p>These may be used for conversion measurement and advertising. Marketing storage remains denied until you grant consent.</p>

            <h2>Changing your choice</h2>
            <p>You can clear the cookie named <code>{{ config('cookie-consent.cookie_name') }}</code> from your browser to display the consent banner again.</p>
        </div>
    </article>
</x-layouts.app>
