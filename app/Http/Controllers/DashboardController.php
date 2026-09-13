<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Appointment;
use App\Models\DeliveryAgent;
use App\Models\Doctor;
use App\Models\DoctorHospitalAssignment;
use App\Models\FacilityBooking;
use App\Models\FacilityType;
use App\Models\HealthTip;
use App\Models\Hospital;
use App\Models\HospitalFacility;
use App\Models\MedicineOrderItem;
use App\Models\MedicineReminderTime;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Pharmacy;
use App\Support\MonthlySeries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * One method per role's dashboard page. Each method:
 *   1. Looks up that role's profile details for whoever is logged in
 *   2. Hands that data to a Blade view to display
 *
 * Auth::user() = the currently logged-in Account. ->patient / ->doctor /
 * etc. are the relationship methods defined on Account.php — see that file
 * for how those work. Which method runs for which URL is decided in
 * routes/web.php (e.g. Route::get('/dashboard', [DashboardController::class, 'patient'])).
 */
class DashboardController extends Controller
{
    public function patient()
    {
        $patient = Auth::user()->patient;

        // The single soonest still-pending appointment — shown as the
        // dashboard's "Upcoming Appointment" spotlight card, with a Join
        // Video Call button if it's an online visit that's joinable now.
        $upcomingAppointment = Appointment::where('patient_id', $patient->patient_id)
            ->whereIn('status', ['booked', 'confirmed'])
            ->where('appointment_date', '>=', now()->toDateString())
            ->with(['doctor.account', 'template'])
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->first();

        $pendingAppointmentsCount = Appointment::where('patient_id', $patient->patient_id)
            ->whereIn('status', ['booked', 'confirmed'])
            ->count();

        $recentAppointments = Appointment::where('patient_id', $patient->patient_id)
            ->where('status', 'completed')
            ->with('doctor.account')
            ->orderByDesc('appointment_date')
            ->take(3)
            ->get();

        // Every still-active "take your medicine" reminder time this
        // patient has set (see MedicineReminderController), soonest first.
        $medicineReminders = MedicineReminderTime::whereHas(
            'prescriptionItem.prescription',
            fn ($q) => $q->where('patient_id', $patient->patient_id)
        )
            ->with('prescriptionItem.medicine')
            ->orderBy('reminder_time')
            ->get()
            ->filter(fn (MedicineReminderTime $r) => $r->prescriptionItem->isDosingActive())
            ->take(4);

        $recentActivity = Auth::user()->notifications()->orderByDesc('created_at')->take(5)->get();

        $counts = [
            'appointments' => $pendingAppointmentsCount,
            'prescriptions' => $patient->prescriptions()->count(),
            'lab_reports' => $patient->medicalRecords()->where('record_type', 'lab_result')->count(),
            'records' => $patient->medicalRecords()->count(),
        ];

        // "Tip of the day" — deterministic per calendar day (not random
        // per page load), so it doesn't change every time the dashboard
        // refreshes but does rotate day to day.
        $tipCount = HealthTip::count();
        $healthTip = $tipCount > 0
            ? HealthTip::orderBy('tip_id')->skip(now()->dayOfYear % $tipCount)->first()
            : null;

        // compact(...) is shorthand for ['patient' => $patient, ...] — it
        // builds an array using each variable's own name as the key. That
        // array is what becomes available inside the Blade view — so
        // resources/views/patient/dashboard.blade.php can use {{ $patient->full_name }}.
        $availableDashboardTools = ['appointments', 'facilities', 'medicine', 'blood', 'records', 'assistant'];
        $storedDashboardTools = $patient->dashboard_tools
            ? json_decode($patient->dashboard_tools, true)
            : $availableDashboardTools;

        $storedDashboardTools = is_array($storedDashboardTools) ? $storedDashboardTools : $availableDashboardTools;
        $selectedDashboardTools = array_values(array_filter(
            $availableDashboardTools,
            fn ($tool) => $tool === 'appointments' || in_array($tool, $storedDashboardTools, true)
        ));

        return view('patient.dashboard', compact(
            'patient', 'upcomingAppointment', 'recentAppointments', 'medicineReminders', 'recentActivity', 'counts', 'healthTip',
            'selectedDashboardTools'
        ));
    }

    public function updatePatientTools(Request $request)
    {
        $patient = Auth::user()->patient;
        $availableDashboardTools = ['appointments', 'facilities', 'medicine', 'blood', 'records', 'assistant'];

        $requestedTools = $request->input('tools', []);
        $requestedTools = is_array($requestedTools) ? $requestedTools : [];

        $selectedDashboardTools = array_values(array_filter(
            $availableDashboardTools,
            fn ($tool) => $tool === 'appointments' || in_array($tool, $requestedTools, true)
        ));

        $patient->dashboard_tools = json_encode($selectedDashboardTools);
        $patient->save();

        return back()->with('success', __('dashboard.patient.customize_saved'));
    }

    public function doctor()
    {
        // ->load('specialties') tells Eloquent "also fetch this doctor's
        // specialties right now" (see Doctor::specialties() in Doctor.php).
        // Without it, $doctor->specialties would still work when the view
        // asks for it, just as a separate, slightly less efficient query.
        $doctor = Auth::user()->doctor->load('specialties');
        $doctorId = $doctor->doctor_id;

        $todayAppointments = Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', now()->toDateString())
            ->whereNotIn('status', ['cancelled'])
            ->with('patient.account')
            ->orderBy('appointment_time')
            ->get();

        $pendingAppointmentsCount = Appointment::where('doctor_id', $doctorId)
            ->whereIn('status', ['booked', 'confirmed'])
            ->count();

        $todayVideoCount = $todayAppointments->where('appointment_type', 'online')->count();

        $todayEarnings = Payment::whereHas('appointment', fn ($q) => $q->where('doctor_id', $doctorId)->whereDate('appointment_date', now()->toDateString()))
            ->where('status', 'completed')
            ->sum('amount');

        // "Patient Overview" donut — classified purely from real visit
        // counts, not a fabricated split: for every distinct patient this
        // doctor has EVER had a real appointment with, count how many
        // times total. 1 visit ever = New, 2 = Follow Up, 3+ = Returning.
        $visitCountsByPatient = Appointment::where('doctor_id', $doctorId)
            ->whereNotIn('status', ['cancelled'])
            ->selectRaw('patient_id, COUNT(*) as visits')
            ->groupBy('patient_id')
            ->pluck('visits');

        $patientOverview = [
            'new' => $visitCountsByPatient->filter(fn ($v) => $v === 1)->count(),
            'follow_up' => $visitCountsByPatient->filter(fn ($v) => $v === 2)->count(),
            'returning' => $visitCountsByPatient->filter(fn ($v) => $v >= 3)->count(),
        ];

        // Every date (this calendar month) this doctor has at least one
        // non-cancelled appointment — feeds the small read-only month
        // calendar's "busy day" dots (see partials/mini-calendar.blade.php).
        $calendarAppointmentDates = Appointment::where('doctor_id', $doctorId)
            ->whereNotIn('status', ['cancelled'])
            ->whereBetween('appointment_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->pluck('appointment_date')
            ->map(fn ($date) => $date->toDateString())
            ->unique()
            ->values();

        return view('doctor.dashboard', compact(
            'doctor', 'todayAppointments', 'pendingAppointmentsCount', 'todayVideoCount',
            'todayEarnings', 'patientOverview', 'calendarAppointmentDates'
        ));
    }

    public function hospital()
    {
        $hospital = Auth::user()->hospital;
        $hospitalId = $hospital->hospital_id;

        $totalDoctors = $hospital->activeDoctors()->count();
        $totalAppointments = $hospital->appointments()->count();
        $totalPatients = Appointment::where('hospital_id', $hospitalId)->distinct('patient_id')->count('patient_id');

        // Revenue has two real sources for a hospital: completed consultation
        // payments for onsite appointments here, and completed facility
        // bookings (each already stores its own price snapshot).
        $appointmentRevenue = Payment::where('status', 'completed')
            ->whereHas('appointment', fn ($q) => $q->where('hospital_id', $hospitalId))
            ->sum('amount');
        $facilityRevenue = FacilityBooking::where('hospital_id', $hospitalId)->where('status', 'completed')->sum('price');
        $totalRevenue = $appointmentRevenue + $facilityRevenue;

        // "Overview" line chart — real appointment volume and distinct
        // patient counts per month, last 6 months.
        $sinceDate = now()->startOfMonth()->subMonths(5)->toDateString();
        $appointmentsByMonth = MonthlySeries::fill(
            Appointment::where('hospital_id', $hospitalId)
                ->where('appointment_date', '>=', $sinceDate)
                ->selectRaw("DATE_FORMAT(appointment_date, '%Y-%m') as ym, COUNT(*) as cnt")
                ->groupBy('ym')->pluck('cnt', 'ym'),
            fn ($v) => (string) (int) $v
        );
        $patientsByMonth = MonthlySeries::fill(
            Appointment::where('hospital_id', $hospitalId)
                ->where('appointment_date', '>=', $sinceDate)
                ->selectRaw("DATE_FORMAT(appointment_date, '%Y-%m') as ym, COUNT(DISTINCT patient_id) as cnt")
                ->groupBy('ym')->pluck('cnt', 'ym'),
            fn ($v) => (string) (int) $v
        );

        // Department overview — real specialty mix of who this hospital's
        // assigned doctors have actually seen (distinct patients per
        // specialty, joined straight off appointments, no fabricated split).
        $departmentOverview = DB::table('appointments')
            ->join('doctor_specialties', 'appointments.doctor_id', '=', 'doctor_specialties.doctor_id')
            ->join('specialties', 'doctor_specialties.specialty_id', '=', 'specialties.specialty_id')
            ->where('appointments.hospital_id', $hospitalId)
            ->whereNotIn('appointments.status', ['cancelled'])
            ->selectRaw('specialties.specialty_name, COUNT(DISTINCT appointments.patient_id) as patient_count')
            ->groupBy('specialties.specialty_id', 'specialties.specialty_name')
            ->orderByDesc('patient_count')
            ->take(5)
            ->get()
            ->map(fn ($row) => [
                'label' => $row->specialty_name,
                'value' => (int) $row->patient_count,
                'formatted' => $row->patient_count . ' ' . __('dashboard.hospital.department_patients_suffix'),
            ]);

        // Recent activity — merged from three real, hospital-scoped event
        // sources (not a notifications feed, since hospitals don't currently
        // receive notifications for these), newest first.
        $recentActivity = collect()
            ->concat(
                DoctorHospitalAssignment::where('hospital_id', $hospitalId)->where('status', 'active')
                    ->with('doctor')->latest('assigned_at')->take(5)->get()
                    ->map(fn ($a) => ['message' => __('dashboard.hospital.activity_doctor_assigned', ['name' => $a->doctor->full_name]), 'time' => $a->assigned_at, 'icon' => 'bi-person-badge'])
            )
            ->concat(
                Appointment::where('hospital_id', $hospitalId)
                    ->with(['patient', 'doctor'])->latest('created_at')->take(5)->get()
                    ->map(fn ($a) => ['message' => __('dashboard.hospital.activity_new_appointment', ['patient' => $a->patient->full_name, 'doctor' => $a->doctor->full_name]), 'time' => $a->created_at, 'icon' => 'bi-calendar2-plus'])
            )
            ->concat(
                FacilityBooking::where('hospital_id', $hospitalId)
                    ->with(['patient', 'facilityType'])->latest('created_at')->take(5)->get()
                    ->map(fn ($b) => ['message' => __('dashboard.hospital.activity_new_facility_booking', ['type' => $b->facilityType->name, 'patient' => $b->patient->full_name]), 'time' => $b->created_at, 'icon' => 'bi-building'])
            )
            ->sortByDesc('time')->take(5)->values();

        $recentAppointments = Appointment::where('hospital_id', $hospitalId)
            ->with(['patient.account', 'doctor.account'])
            ->orderByDesc('appointment_date')->orderByDesc('appointment_time')
            ->take(6)->get();

        // Bed availability — real occupancy facility types (ICU/CCU/General
        // Ward/Cabin, seeded with is_occupancy=true), this hospital's own
        // daily_capacity per type, and how many are occupied right now
        // (a booking whose stay window covers today).
        $bedFacilityTypeIds = FacilityType::where('is_occupancy', true)->pluck('facility_type_id');
        $totalBeds = (int) HospitalFacility::where('hospital_id', $hospitalId)->whereIn('facility_type_id', $bedFacilityTypeIds)->sum('daily_capacity');
        $occupiedBeds = FacilityBooking::where('hospital_id', $hospitalId)
            ->whereIn('facility_type_id', $bedFacilityTypeIds)
            ->where('status', 'booked')
            ->get()
            ->filter(fn (FacilityBooking $b) => $b->booking_date->lte(now()) && $b->expectedDischargeDate() && $b->expectedDischargeDate()->gte(now()->startOfDay()))
            ->count();
        $icuBedTypeIds = FacilityType::where('is_occupancy', true)->where('name', 'like', '%ICU%')->pluck('facility_type_id');
        $icuBeds = (int) HospitalFacility::where('hospital_id', $hospitalId)->whereIn('facility_type_id', $icuBedTypeIds)->sum('daily_capacity');

        $bedAvailability = [
            'total' => $totalBeds,
            'occupied' => $occupiedBeds,
            'available' => max(0, $totalBeds - $occupiedBeds),
            'icu' => $icuBeds,
        ];

        return view('hospital.dashboard', compact(
            'hospital', 'totalDoctors', 'totalPatients', 'totalAppointments', 'totalRevenue',
            'appointmentsByMonth', 'patientsByMonth', 'departmentOverview', 'recentActivity',
            'recentAppointments', 'bedAvailability'
        ));
    }

    public function pharmacy()
    {
        $pharmacy = Auth::user()->pharmacy;
        $pharmacyId = $pharmacy->pharmacy_id;

        // A batch this shallow is worth flagging before it runs out —
        // same idea as the "New/Follow Up/Returning" cutoffs on the doctor
        // dashboard: a fixed, documented threshold, not a fabricated split.
        $lowStockThreshold = 20;

        $totalMedicines = $pharmacy->medicineStock()->distinct('medicine_master_id')->count('medicine_master_id');
        $lowStockCount = $pharmacy->medicineStock()->where('quantity_available', '>', 0)->where('quantity_available', '<=', $lowStockThreshold)->count();
        $todayOrdersCount = $pharmacy->orders()->whereDate('created_at', now()->toDateString())->count();
        $todaySales = $pharmacy->orders()->whereDate('created_at', now()->toDateString())->where('status', '!=', 'cancelled')->sum('total_amount');

        $outOfStockCount = $pharmacy->medicineStock()->where('quantity_available', 0)->count();
        $inStockCount = $pharmacy->medicineStock()->where('quantity_available', '>', $lowStockThreshold)->count();

        $expiringMedicines = $pharmacy->medicineStock()
            ->with('medicine')
            ->where('expiry_date', '<=', now()->addDays(30)->toDateString())
            ->orderBy('expiry_date')
            ->take(8)
            ->get();

        $topMedicines = MedicineOrderItem::whereHas('order', fn ($q) => $q->where('pharmacy_id', $pharmacyId))
            ->selectRaw('medicine_master_id, SUM(quantity) as total_qty')
            ->groupBy('medicine_master_id')
            ->orderByDesc('total_qty')
            ->take(5)
            ->with('medicine')
            ->get()
            ->map(fn (MedicineOrderItem $row) => [
                'label' => $row->medicine->generic_name,
                'value' => (int) $row->total_qty,
                'formatted' => $row->total_qty . ' ' . __('dashboard.pharmacy.units_suffix'),
            ]);

        return view('pharmacy.dashboard', compact(
            'pharmacy', 'totalMedicines', 'lowStockCount', 'todayOrdersCount', 'todaySales',
            'outOfStockCount', 'inStockCount', 'lowStockThreshold', 'expiringMedicines', 'topMedicines'
        ));
    }

    public function delivery()
    {
        $agent = Auth::user()->deliveryAgent;
        return view('delivery.dashboard', compact('agent'));
    }

    public function admin()
    {
        $admin = Auth::user()->admin;

        // Model::count() = "SELECT COUNT(*) FROM that table" — a quick way
        // to get a total without fetching every row. The last one adds a
        // ->where(...) filter first, same idea as SQL's WHERE clause:
        // "only count doctors whose verification_status is 'pending'."
        $counts = [
            'patients' => Patient::count(),
            'doctors' => Doctor::count(),
            'hospitals' => Hospital::count(),
            'pharmacies' => Pharmacy::count(),
            'delivery_agents' => DeliveryAgent::count(),
            'pending_doctor_verifications' => Doctor::where('verification_status', 'pending')->count(),
        ];

        // "System Overview" line chart — real platform growth: new
        // registrations (any role) and appointments booked, per month.
        $sinceDate = now()->startOfMonth()->subMonths(5)->toDateString();
        $registrationsByMonth = MonthlySeries::fill(
            Account::where('created_at', '>=', $sinceDate)
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as cnt")
                ->groupBy('ym')->pluck('cnt', 'ym'),
            fn ($v) => (string) (int) $v
        );
        $appointmentsByMonth = MonthlySeries::fill(
            Appointment::where('created_at', '>=', $sinceDate)
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as cnt")
                ->groupBy('ym')->pluck('cnt', 'ym'),
            fn ($v) => (string) (int) $v
        );

        $revenueByMonth = MonthlySeries::fill(
            Payment::where('status', 'completed')
                ->where('paid_at', '>=', $sinceDate)
                ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as ym, SUM(amount) as total")
                ->groupBy('ym')->pluck('total', 'ym'),
            fn ($v) => 'BDT ' . number_format((float) $v, 0)
        );

        // Recent activity — merged from real registrations and real
        // completed payments platform-wide, newest first. Admin accounts
        // don't receive notifications for these events today, so this reads
        // straight off the source tables instead of a notifications feed.
        $recentActivity = collect()
            ->concat(
                Account::latest('created_at')->take(5)->get()
                    ->map(fn (Account $acc) => [
                        'message' => __('dashboard.admin.activity_registered', [
                            'role' => __('register.' . $acc->role) !== 'register.' . $acc->role ? __('register.' . $acc->role) : ucfirst($acc->role),
                            'name' => $acc->displayName(),
                        ]),
                        'time' => $acc->created_at,
                        'icon' => 'bi-person-plus',
                    ])
            )
            ->concat(
                Payment::where('status', 'completed')->with('account')->latest('paid_at')->take(5)->get()
                    ->map(fn (Payment $p) => [
                        'message' => __('dashboard.admin.activity_payment', ['amount' => number_format($p->amount, 0), 'name' => $p->account->displayName()]),
                        'time' => $p->paid_at ?? $p->created_at,
                        'icon' => 'bi-cash-coin',
                    ])
            )
            ->sortByDesc('time')->take(6)->values();

        // System health — every figure here is a real, live-checked signal,
        // not a placeholder: the DB connection is actually pinged, disk
        // space is actually read off the server's storage volume, and
        // failed_jobs is Laravel's own real queue-failure table.
        $dbOk = true;
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $dbOk = false;
        }
        $dbSizeMb = (float) (DB::selectOne(
            'SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) as size_mb FROM information_schema.tables WHERE table_schema = DATABASE()'
        )->size_mb ?? 0);

        $storageTotal = @disk_total_space(storage_path()) ?: 0;
        $storageFree = @disk_free_space(storage_path()) ?: 0;
        $storageUsedPercent = $storageTotal > 0 ? (int) round((($storageTotal - $storageFree) / $storageTotal) * 100) : 0;

        $systemHealth = [
            'db_ok' => $dbOk,
            'db_size_mb' => $dbSizeMb,
            'storage_used_percent' => $storageUsedPercent,
            'storage_free_gb' => round($storageFree / 1024 / 1024 / 1024, 1),
            'failed_jobs' => DB::table('failed_jobs')->count(),
        ];

        return view('admin.dashboard', compact(
            'admin', 'counts', 'registrationsByMonth', 'appointmentsByMonth', 'revenueByMonth',
            'recentActivity', 'systemHealth'
        ));
    }
}
