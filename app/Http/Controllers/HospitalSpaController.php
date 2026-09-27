<?php

namespace App\Http\Controllers;

/**
 * Every converted hospital page (see routes/web.php's hospital.* group)
 * routes here regardless of which one matched — the URL only decides WHICH
 * route name matched (kept identical to the old Blade routes so
 * route()/routeIs() elsewhere keep working), the actual page is picked
 * client-side by React Router once resources/js/hospital/main.jsx mounts
 * into the #hospital-app div this view renders.
 *
 * Same shape as DoctorSpaController / PatientSpaController.
 */
class HospitalSpaController extends Controller
{
    public function index()
    {
        return view('hospital.spa-shell');
    }
}
