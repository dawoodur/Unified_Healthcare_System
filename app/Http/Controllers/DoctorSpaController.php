<?php

namespace App\Http\Controllers;

/**
 * Every converted doctor page (see routes/web.php's doctor.* group) routes
 * here regardless of which one matched — the URL only decides WHICH route
 * name matched (kept identical to the old Blade routes so route()/routeIs()
 * elsewhere keep working), the actual page is picked client-side by React
 * Router once resources/js/doctor/main.jsx mounts into the #doctor-app div
 * this view renders.
 */
class DoctorSpaController extends Controller
{
    public function index()
    {
        return view('doctor.spa-shell');
    }
}
