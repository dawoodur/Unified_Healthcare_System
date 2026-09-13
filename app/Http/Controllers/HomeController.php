<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

/**
 * Handles the site's front page ("/"). See routes/web.php — the line
 * Route::get('/', [HomeController::class, 'index']) is what connects the
 * homepage URL to the index() method below.
 */
class HomeController extends Controller
{
    public function index()
    {
        // Auth::check() = "is someone currently logged in?"
        if (Auth::check()) {
            // If so, skip the marketing homepage and send them straight to
            // their own dashboard. Auth::user() gets the logged-in Account,
            // ->role is e.g. "patient", and route('patient.dashboard') is
            // the named route defined in routes/web.php for that role.
            // ('patient' . '.dashboard' just glues those two strings together.)
            return redirect()->route(Auth::user()->role . '.dashboard');
        }

        // Not logged in: show the plain welcome page instead.
        // return view('welcome') renders resources/views/welcome.blade.php.
        return view('welcome');
    }
}
