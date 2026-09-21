<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Payment;
use App\Support\MonthlySeries;
use Illuminate\Support\Facades\Auth;

class AnalyticsController extends Controller
{
    public function index()
    {
        $doctorId = (int) Auth::user()->doctor->doctor_id;
        $periodStart = now()->startOfMonth()->subMonths(5);

        $earningsByMonth = MonthlySeries::fill(
            Payment::where('status', 'completed')
                ->where('paid_at', '>=', $periodStart)
                ->whereHas('appointment', fn ($query) => $query->where('doctor_id', $doctorId))
                ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as ym, SUM(amount) as total")
                ->groupBy('ym')
                ->pluck('total', 'ym'),
            fn ($value) => 'BDT ' . number_format((float) $value, 0)
        )->values();

        $appointmentsByMonth = MonthlySeries::fill(
            Appointment::where('doctor_id', $doctorId)
                ->where('appointment_date', '>=', $periodStart->toDateString())
                ->selectRaw("DATE_FORMAT(appointment_date, '%Y-%m') as ym, COUNT(*) as cnt")
                ->groupBy('ym')
                ->pluck('cnt', 'ym'),
            fn ($value) => (string) $value
        )->values();

        $statusCounts = Appointment::where('doctor_id', $doctorId)
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->get()
            ->map(fn ($row) => [
                'key' => $row->status,
                'label' => ucfirst(str_replace('_', ' ', $row->status)),
                'value' => (int) $row->cnt,
            ])
            ->sortByDesc('value')
            ->values();

        $totalPatients = Appointment::where('doctor_id', $doctorId)
            ->distinct('patient_id')
            ->count('patient_id');

        $completedVisits = Appointment::where('doctor_id', $doctorId)
            ->where('status', 'completed')
            ->count();

        return response()->json([
            'stats' => [
                'earnings_6m' => (float) $earningsByMonth->sum('value'),
                'appointments_6m' => (int) $appointmentsByMonth->sum('value'),
                'total_patients' => (int) $totalPatients,
                'completed_visits' => (int) $completedVisits,
            ],
            'earnings_by_month' => $earningsByMonth,
            'appointments_by_month' => $appointmentsByMonth,
            'status_counts' => $statusCounts,
        ]);
    }
}
