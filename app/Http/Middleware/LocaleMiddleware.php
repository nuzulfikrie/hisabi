<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class LocaleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Priority: user preference > session > browser > default
        $locale = $this->resolveLocale($request);

        Session::put('locale', $locale);
        App::setLocale($locale);

        return $next($request);
    }

    /**
     * Resolve the locale for the request.
     */
    private function resolveLocale(Request $request): string
    {
        // 1. Check authenticated user preference
        if (auth()->check() && auth()->user()->locale) {
            return auth()->user()->locale;
        }

        // 2. Check session
        if (Session::has('locale')) {
            return Session::get('locale');
        }

        // 3. Check browser preference
        $browserLocale = substr($request->server('HTTP_ACCEPT_LANGUAGE', ''), 0, 2);
        if (in_array($browserLocale, \App\Enums\Locale::values())) {
            return $browserLocale;
        }

        // 4. Default
        return config('app.locale');
    }
}
