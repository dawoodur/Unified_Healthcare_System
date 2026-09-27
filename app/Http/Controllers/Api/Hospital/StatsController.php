<?php

namespace App\Http\Controllers\Api\Hospital;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

/** JSON twin of HospitalStatsController — per-doctor appointment breakdown. */
class StatsController extends Controller
{
    public function index()
    {
        $hospital = Auth::user()->hospital;

        $appointments = $hospital->appointments()->with('doctor')->get();

        $byDoctor = $appointments
            ->groupBy('doctor_id')
            ->map(fn ($group) => [
                'doctor_id' => (int) $group->first()->doctor_id,
                'doctor_name' => $group->first()->doctor->full_name,
                'total' => $group->count(),
                'completed' => $group->where('status', 'completed')->count(),
                'booked' => $group->whereIn('status', ['booked', 'confirmed'])->count(),
                'cancelled' => $group->whereIn('status', ['cancelled', 'no_show'])->count(),
            ])
            ->sortByDesc('total')
            ->values();

        return response()->json([
            'by_doctor' => $byDoctor,
            'totals' => [
                'total' => $appointments->count(),
                'completed' => $appointments->where('status', 'completed')->count(),
                'booked' => $appointments->whereIn('status', ['booked', 'confirmed'])->count(),
                'cancelled' => $appointments->whereIn('status', ['cancelled', 'no_show'])->count(),
            ],
        ]);
    }
}
