<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = null;

        if ($request->user()?->locale) {
            $locale = $request->user()->locale;
        } elseif ($cookie = $request->cookie('locale')) {
            $locale = $cookie;
        } else {
            $locale = $request->getPreferredLanguage(['en', 'fr']) ?? 'en';
        }

        if (! in_array($locale, ['en', 'fr'], true)) {
            $locale = 'en';
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
