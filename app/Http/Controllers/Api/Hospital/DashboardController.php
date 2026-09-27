<?php

namespace App\Http\Controllers\Api\Hospital;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\DoctorHospitalAssignment;
use App\Models\FacilityBooking;
use App\Models\FacilityType;
use App\Models\HospitalFacility;
use App\Models\Payment;
use App\Support\MonthlySeries;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * JSON twin of what DashboardController::hospital() used to render into
 * hospital/dashboard.blade.php — the queries are unchanged, they just
 * serialise instead of feeding a Blade view (see resources/js/hospital/).
 */
class DashboardController extends Controller
{
    public function index()
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

        // Real specialty mix of who this hospital's assigned doctors have
        // actually seen — distinct patients per specialty, no fabricated split.
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
            ->sortByDesc('time')->take(5)->values()
            ->map(fn ($item) => [
                'message' => $item['message'],
                'icon' => $item['icon'],
                'time_label' => $item['time']?->diffForHumans(),
            ]);

        $recentAppointments = Appointment::where('hospital_id', $hospitalId)
            ->with(['patient.account', 'doctor.account'])
            ->orderByDesc('appointment_date')->orderByDesc('appointment_time')
            ->take(6)->get()
            ->map(fn (Appointment $a) => [
                'appointment_id' => (int) $a->appointment_id,
                'patient_name' => $a->patient->full_name,
                'patient_photo_url' => $a->patient->account?->photoUrl(),
                'doctor_name' => $a->doctor->full_name,
                'date_label' => $a->appointment_date->format('M j, Y'),
                'status' => $a->status,
                'status_label' => $a->statusLabel(),
            ]);

        // Real occupancy facility types (ICU/CCU/General Ward/Cabin), this
        // hospital's own daily_capacity per type, and how many are occupied
        // right now (a booking whose stay window covers today).
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

        return response()->json([
            'hospital' => [
                'hospital_id' => (int) $hospitalId,
                'hospital_name' => $hospital->hospital_name,
                'registration_number' => $hospital->registration_number,
                'city' => $hospital->city,
                'uid_tag' => Auth::user()->uidTag(),
            ],
            'stats' => [
                'total_doctors' => (int) $totalDoctors,
                'total_patients' => (int) $totalPatients,
                'total_appointments' => (int) $totalAppointments,
                'total_revenue' => (float) $totalRevenue,
            ],
            'appointments_by_month' => $appointmentsByMonth,
            'patients_by_month' => $patientsByMonth,
            'department_overview' => $departmentOverview,
            'recent_activity' => $recentActivity,
            'recent_appointments' => $recentAppointments,
            'bed_availability' => [
                'total' => $totalBeds,
                'occupied' => $occupiedBeds,
                'available' => max(0, $totalBeds - $occupiedBeds),
                'icu' => $icuBeds,
            ],
        ]);
    }
}
