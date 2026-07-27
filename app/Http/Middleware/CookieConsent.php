<?php

namespace App\Http\Middleware;

use App\Support\CookieConsent as CookieConsentSupport;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

final class CookieConsent
{
    public function handle(Request $request, Closure $next): Response
    {
        $consent = CookieConsentSupport::fromRequest($request);

        $request->attributes->set('cookie_consent', $consent);
        View::share('cookieConsent', $consent);

        return $next($request);
    }
}
