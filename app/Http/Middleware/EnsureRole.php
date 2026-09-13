<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * "Middleware" is code that runs BEFORE a controller, and can decide to
 * either let the request continue, or stop it and send back a different
 * response instead (like a redirect). This one enforces "you must be
 * logged in, AND you must be the right role" for a page.
 *
 * It's wired up in two places:
 *   - app/Http/Kernel.php registers the short name 'role' for this class
 *   - routes/web.php uses that name, e.g. ->middleware(['auth', 'role:patient'])
 *     The ":patient" part becomes the $role parameter below.
 */
class EnsureRole
{
    /**
     * $next is "the rest of the request" (whatever middleware/controller
     * would normally run next). Calling $next($request) means "everything
     * checked out, let it continue as normal." NOT calling it — returning
     * something else instead — stops the request right here.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // $request->user() = the currently logged-in Account (or null).
        $account = $request->user();

        if (!$account || !$account->is_active) {
            // Not logged in, or their account was deactivated — force them
            // out and send to the login page instead of the page they wanted.
            auth()->logout();
            return redirect()->route('login');
        }

        if ($account->role !== $role) {
            // Logged in, but as the WRONG role for this page (e.g. a
            // pharmacy account trying to open /admin/dashboard). Don't show
            // an error — just send them to their own dashboard instead.
            return redirect()->route($account->role . '.dashboard');
        }

        // Everything checked out — let the actual page load normally.
        return $next($request);
    }
}
