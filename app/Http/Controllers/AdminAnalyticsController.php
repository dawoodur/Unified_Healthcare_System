<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Doctor;
use App\Models\MedicineOrder;
use App\Models\MedicineOrderItem;
use App\Models\Payment;
use App\Support\MonthlySeries;

/**
 * A small analytics dashboard for the admin — revenue and registrations
 * over the last 6 months, the busiest doctors, the best-selling
 * medicines, and how orders break down by status. Charts are plain
 * CSS bars (see partials/bar-chart.blade.php) — no charting library.
 */
class AdminAnalyticsController extends Controller
{
    public function index()
    {
        $revenueByMonth = MonthlySeries::fill(
            Payment::where('status', 'completed')
                ->where('paid_at', '>=', now()->startOfMonth()->subMonths(5))
                ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as ym, SUM(amount) as total")
                ->groupBy('ym')
                ->pluck('total', 'ym'),
            fn ($value) => 'BDT ' . number_format((float) $value, 0)
        );

        $registrationsByMonth = MonthlySeries::fill(
            Account::where('created_at', '>=', now()->startOfMonth()->subMonths(5))
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as cnt")
                ->groupBy('ym')
                ->pluck('cnt', 'ym'),
            fn ($value) => (string) $value
        );

        $busiestDoctors = Doctor::withCount('appointments')
            ->orderByDesc('appointments_count')
            ->take(5)
            ->get()
            ->map(fn (Doctor $d) => ['label' => 'Dr. ' . $d->full_name, 'value' => $d->appointments_count, 'formatted' => (string) $d->appointments_count]);

        $topMedicines = MedicineOrderItem::selectRaw('medicine_master_id, SUM(quantity) as total_qty')
            ->groupBy('medicine_master_id')
            ->orderByDesc('total_qty')
            ->take(5)
            ->with('medicine')
            ->get()
            ->map(fn (MedicineOrderItem $row) => ['label' => $row->medicine->generic_name, 'value' => (int) $row->total_qty, 'formatted' => $row->total_qty . ' units']);

        $orderStatusCounts = MedicineOrder::selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->get()
            ->map(fn ($row) => ['label' => ucfirst(str_replace('_', ' ', $row->status)), 'value' => (int) $row->cnt, 'formatted' => (string) $row->cnt]);

        return view('admin.analytics', compact('revenueByMonth', 'registrationsByMonth', 'busiestDoctors', 'topMedicines', 'orderStatusCounts'));
    }
}
