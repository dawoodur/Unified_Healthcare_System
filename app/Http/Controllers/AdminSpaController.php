<?php

namespace App\Http\Controllers;

/**
 * Every converted admin page (see routes/web.php's admin.* group) routes
 * here regardless of which one matched — the URL only decides WHICH route
 * name matched (kept identical to the old Blade routes so route()/routeIs()
 * elsewhere keep working), the actual page is picked client-side by React
 * Router once resources/js/admin/main.jsx mounts into the #admin-app div
 * this view renders.
 */
class AdminSpaController extends Controller
{
    public function index()
    {
        return view('admin.spa-shell');
    }
}
