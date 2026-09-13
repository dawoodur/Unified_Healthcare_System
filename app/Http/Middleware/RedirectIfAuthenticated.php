<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * The opposite check of EnsureRole: this is the 'guest' middleware, used on
 * pages that should only be visible to someone who is NOT logged in (the
 * login page, the registration forms). If you're already logged in and you
 * try to open one of those pages, this sends you to your dashboard instead.
 */
class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            return redirect()->route(Auth::user()->role . '.dashboard');
        }

        return $next($request);
    }
}
