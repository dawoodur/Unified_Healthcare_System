<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

/**
 * Read-only appointment volume for a hospital's own doctors — how many
 * onsite visits each one has had here, broken down by status. Scoped to
 * appointments.hospital_id (only ever set for ONSITE appointments — an
 * online appointment isn't tied to any physical hospital), so this is
 * genuinely "activity at your hospital," not just "activity by doctors
 * who happen to also be assigned here" (which could include visits
 * elsewhere, or online sessions this hospital had no part in).
 */
class HospitalStatsController extends Controller
{
    /** Shows appointment counts per doctor, and hospital-wide totals (GET /hospital/appointment-stats). */
    public function index()
    {
        $hospital = Auth::user()->hospital;

        $appointments = $hospital->appointments()->with('doctor')->get();

        $byDoctor = $appointments
            ->groupBy('doctor_id')
            ->map(function ($group) {
                return (object) [
                    'doctor' => $group->first()->doctor,
                    'total' => $group->count(),
                    'completed' => $group->where('status', 'completed')->count(),
                    'booked' => $group->whereIn('status', ['booked', 'confirmed'])->count(),
                    'cancelled' => $group->whereIn('status', ['cancelled', 'no_show'])->count(),
                ];
            })
            ->sortByDesc('total')
            ->values();

        $totals = (object) [
            'total' => $appointments->count(),
            'completed' => $appointments->where('status', 'completed')->count(),
            'booked' => $appointments->whereIn('status', ['booked', 'confirmed'])->count(),
            'cancelled' => $appointments->whereIn('status', ['cancelled', 'no_show'])->count(),
        ];

        return view('hospital.appointment-stats', compact('byDoctor', 'totals'));
    }
}
