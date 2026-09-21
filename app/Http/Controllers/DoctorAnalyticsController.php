<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Payment;
use App\Support\MonthlySeries;
use Illuminate\Support\Facades\Auth;

/**
 * A doctor's own small analytics page — earnings and appointment volume
 * over the last 6 months, and how their appointments break down by
 * status. Same shape/approach as AdminAnalyticsController, just scoped
 * to one doctor's own data instead of the whole platform.
 */
class DoctorAnalyticsController extends Controller
{
    public function index()
    {
        $doctorId = Auth::user()->doctor->doctor_id;

        $earningsByMonth = MonthlySeries::fill(
            Payment::where('status', 'completed')
                ->where('paid_at', '>=', now()->startOfMonth()->subMonths(5))
                ->whereHas('appointment', fn ($q) => $q->where('doctor_id', $doctorId))
                ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as ym, SUM(amount) as total")
                ->groupBy('ym')
                ->pluck('total', 'ym'),
            fn ($value) => 'BDT ' . number_format((float) $value, 0)
        );

        $appointmentsByMonth = MonthlySeries::fill(
            Appointment::where('doctor_id', $doctorId)
                ->where('appointment_date', '>=', now()->startOfMonth()->subMonths(5)->toDateString())
                ->selectRaw("DATE_FORMAT(appointment_date, '%Y-%m') as ym, COUNT(*) as cnt")
                ->groupBy('ym')
                ->pluck('cnt', 'ym'),
            fn ($value) => (string) $value
        );

        $statusCounts = Appointment::where('doctor_id', $doctorId)
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->get()
            ->map(fn ($row) => ['label' => ucfirst(str_replace('_', ' ', $row->status)), 'value' => (int) $row->cnt, 'formatted' => (string) $row->cnt]);

        $totalPatients = Appointment::where('doctor_id', $doctorId)->distinct('patient_id')->count('patient_id');

        return view('doctor.analytics', compact('earningsByMonth', 'appointmentsByMonth', 'statusCounts', 'totalPatients'));
    }
}
