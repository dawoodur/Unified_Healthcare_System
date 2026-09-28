<?php

namespace App\Http\Controllers;

/**
 * Every converted delivery page (see routes/web.php's delivery.* group)
 * routes here regardless of which one matched — the URL only decides WHICH
 * route name matched (kept identical to the old Blade routes so
 * route()/routeIs() elsewhere keep working), the actual page is picked
 * client-side by React Router once resources/js/delivery/main.jsx mounts
 * into the #delivery-app div this view renders.
 */
class DeliverySpaController extends Controller
{
    public function index()
    {
        return view('delivery.spa-shell');
    }
}
