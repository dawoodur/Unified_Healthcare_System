<?php

namespace App\Http\Controllers;

/** The EN / বাংলা toggle in the site header — see SetLocale middleware for how the choice is actually applied. */
class LocaleController extends Controller
{
    /** Remembers the chosen language in the session and sends the browser back where it was (GET /locale/{locale}). */
    public function switch(string $locale)
    {
        if (in_array($locale, ['en', 'bn'], true)) {
            session(['locale' => $locale]);
        }

        return back();
    }
}
