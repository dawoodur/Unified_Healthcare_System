<?php

namespace App\Http\Controllers;

/**
 * Every converted pharmacy page (see routes/web.php's pharmacy.* group)
 * routes here regardless of which one matched — the URL only decides WHICH
 * route name matched (kept identical to the old Blade routes so
 * route()/routeIs() elsewhere keep working), the actual page is picked
 * client-side by React Router once resources/js/pharmacy/main.jsx mounts
 * into the #pharmacy-app div this view renders.
 *
 * Same shape as DoctorSpaController / PatientSpaController / HospitalSpaController.
 */
class PharmacySpaController extends Controller
{
    public function index()
    {
        return view('pharmacy.spa-shell');
    }
}
