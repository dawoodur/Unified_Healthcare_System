<?php

namespace App\Http\Controllers\Api\Hospital;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;

/**
 * The React hospital pages reuse the SAME lang/en|bn/*.php files the Blade
 * pages always used — fetched once on app boot and locale-switched via
 * session('locale') (see App\Http\Middleware\SetLocale), exactly like the
 * patient and doctor TranslationsControllers.
 *
 * The whole `dashboard.hospital` group is handed over as one namespace
 * rather than key-by-key: it is already hospital-scoped, so listing its 49
 * keys individually here would only be a second place to keep in sync.
 */
class TranslationsController extends Controller
{
    public function index()
    {
        return response()->json([
            'locale' => App::getLocale(),
            'dashboard' => __('dashboard.hospital'),
        ]);
    }
}
