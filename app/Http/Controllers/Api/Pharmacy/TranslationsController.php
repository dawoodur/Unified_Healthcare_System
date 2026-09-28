<?php

namespace App\Http\Controllers\Api\Pharmacy;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;

/**
 * The React pharmacy pages reuse the SAME lang/en|bn/*.php files the Blade
 * pages always used — fetched once on app boot and locale-switched via
 * session('locale') (see App\Http\Middleware\SetLocale), exactly like the
 * patient/doctor/hospital TranslationsControllers.
 */
class TranslationsController extends Controller
{
    public function index()
    {
        return response()->json([
            'locale' => App::getLocale(),
            'dashboard' => __('dashboard.pharmacy'),
        ]);
    }
}
