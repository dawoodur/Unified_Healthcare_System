<?php

namespace App\Http\Controllers\Api\Hospital;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\DoctorHospitalApplication;
use App\Models\DoctorHospitalAssignment;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * JSON twin of HospitalDoctorController for the React hospital section.
 *
 * Two ways a doctor ends up on staff, both landing in the same
 * doctor_hospital_assignments table:
 *  - Hospital-initiated: search an approved doctor and assign() them directly.
 *  - Doctor-initiated: the doctor applies from their own Career page
 *    (Api\Doctor\CareerController::apply, doctor_hospital_applications), and
 *    the hospital accepts or rejects it below. Accepting creates the exact
 *    same assignment row assign() does — the application is a request FOR
 *    that assignment, not a separate status with no real effect.
 */
class DoctorController extends Controller
{
    public function __construct(private NotificationService $notifications)
    {
    }

    public function index(Request $request)
    {
        $hospital = Auth::user()->hospital;

        $assigned = $hospital->activeDoctors()->with('specialties')->orderBy('full_name')->get();

        $pendingApplications = DoctorHospitalApplication::where('hospital_id', $hospital->hospital_id)
            ->where('status', 'pending')
            ->with('doctor.specialties')
            ->orderBy('created_at')
            ->get();

        // Only approved doctors not already actively assigned here can be
        // searched. "doctors.doctor_id" is qualified because the pivot
        // doctor_hospital_assignments also has a doctor_id column.
        $results = collect();
        $search = trim((string) $request->get('search', ''));

        if ($search !== '') {
            $alreadyAssignedIds = $hospital->activeDoctors()->pluck('doctors.doctor_id');

            $results = Doctor::where('verification_status', 'approved')
                ->where('full_name', 'like', '%' . $search . '%')
                ->whereNotIn('doctor_id', $alreadyAssignedIds)
                ->with('specialties')
                ->orderBy('full_name')
                ->get();
        }

        return response()->json([
            'assigned' => $assigned->map(fn (Doctor $d) => $this->shape($d)),
            'applications' => $pendingApplications->map(fn (DoctorHospitalApplication $a) => [
                'application_id' => (int) $a->application_id,
                'submitted_label' => $a->created_at?->format('M j, Y'),
                'doctor' => $this->shape($a->doctor),
            ]),
            'search_term' => $search,
            'search_results' => $results->map(fn (Doctor $d) => $this->shape($d)),
        ]);
    }

    public function assign(Doctor $doctor)
    {
        if ($doctor->verification_status !== 'approved') {
            return response()->json(['ok' => false, 'message' => 'Only verified doctors can be assigned.'], 422);
        }

        $hospital = Auth::user()->hospital;

        $this->createOrReactivateAssignment($doctor, $hospital);

        // The direct-assign path can also close out a pending application
        // from the same doctor, so it does not keep sitting there with a
        // now-redundant Accept button once they are already on staff.
        $this->acceptAnyPendingApplication($doctor, $hospital);

        return response()->json(['ok' => true, 'message' => "Dr. {$doctor->full_name} is now assigned to your hospital."]);
    }

    public function revoke(Doctor $doctor)
    {
        $hospital = Auth::user()->hospital;

        DoctorHospitalAssignment::where('hospital_id', $hospital->hospital_id)
            ->where('doctor_id', $doctor->doctor_id)
            ->update(['status' => 'revoked']);

        return response()->json(['ok' => true, 'message' => "Dr. {$doctor->full_name} has been removed from your hospital."]);
    }

    /** Accepts a doctor's application: creates the real staff assignment and tells the doctor. */
    public function acceptApplication(DoctorHospitalApplication $application)
    {
        $hospital = Auth::user()->hospital;

        if ((int) $application->hospital_id !== (int) $hospital->hospital_id) {
            abort(403);
        }

        if ($application->status !== 'pending') {
            return response()->json(['ok' => false, 'message' => 'This application has already been decided.'], 422);
        }

        $doctor = $application->doctor;

        if ($doctor->verification_status !== 'approved') {
            return response()->json(['ok' => false, 'message' => 'This doctor is not yet admin-verified, so they cannot be assigned.'], 422);
        }

        $this->createOrReactivateAssignment($doctor, $hospital);
        $application->update(['status' => 'accepted']);

        $this->notifications->notify(
            $doctor->account,
            'career_application_accepted',
            "{$hospital->hospital_name} accepted your application — you're now on their staff.",
            $application->application_id
        );

        return response()->json(['ok' => true, 'message' => "Dr. {$doctor->full_name}'s application accepted — they're now assigned to your hospital."]);
    }

    /** Rejects a doctor's application. No assignment is touched — this only ever closes out a pending request. */
    public function rejectApplication(DoctorHospitalApplication $application)
    {
        $hospital = Auth::user()->hospital;

        if ((int) $application->hospital_id !== (int) $hospital->hospital_id) {
            abort(403);
        }

        if ($application->status !== 'pending') {
            return response()->json(['ok' => false, 'message' => 'This application has already been decided.'], 422);
        }

        $application->update(['status' => 'rejected']);

        $this->notifications->notify(
            $application->doctor->account,
            'career_application_rejected',
            "{$hospital->hospital_name} did not accept your application this time.",
            $application->application_id
        );

        return response()->json(['ok' => true, 'message' => 'Application rejected.']);
    }

    /**
     * A doctor might have been assigned here before and later revoked — reuse
     * that same row (reactivate it) instead of inserting a second one, which
     * unique(doctor_id, hospital_id) would reject anyway. assigned_at is left
     * alone: the column is useCurrent() on insert, and a reactivated row
     * deliberately keeps its original date.
     */
    private function createOrReactivateAssignment(Doctor $doctor, $hospital): void
    {
        DoctorHospitalAssignment::updateOrCreate(
            ['doctor_id' => $doctor->doctor_id, 'hospital_id' => $hospital->hospital_id],
            ['status' => 'active']
        );
    }

    private function acceptAnyPendingApplication(Doctor $doctor, $hospital): void
    {
        DoctorHospitalApplication::where('doctor_id', $doctor->doctor_id)
            ->where('hospital_id', $hospital->hospital_id)
            ->where('status', 'pending')
            ->update(['status' => 'accepted']);
    }

    private function shape(Doctor $doctor): array
    {
        return [
            'doctor_id' => (int) $doctor->doctor_id,
            'full_name' => $doctor->full_name,
            'specialties' => $doctor->specialties->pluck('specialty_name')->values(),
            'consultation_fee' => $doctor->consultation_fee,
            'verification_status' => $doctor->verification_status,
        ];
    }
}
