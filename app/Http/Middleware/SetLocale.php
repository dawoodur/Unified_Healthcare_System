<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/**
 * Applies whichever language the visitor picked (see LocaleController) on
 * every request — stored in the session, not tied to their account, so
 * it also works for a guest browsing before logging in. Only English and
 * Bengali translation files exist (resources/lang/en, resources/lang/bn)
 * for now, covering the site header/nav and homepage — not a full
 * translation of every page yet.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = session('locale', config('app.locale'));

        if (in_array($locale, ['en', 'bn'], true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
