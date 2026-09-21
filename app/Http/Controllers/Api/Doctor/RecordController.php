<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\RecordAccessGrant;
use App\Services\OtpService;
use Illuminate\Support\Facades\Auth;

/** JSON API for the React Doctor Records page. */
class RecordController extends Controller
{
    public function __construct(private OtpService $otp)
    {
    }

    public function index()
    {
        $this->expireStaleGrants();

        $doctor = Auth::user()->doctor;

        $appointments = $doctor->appointments()
            ->with('patient')
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->get();

        $latestGrantByPatient = $doctor->recordAccessGrants()
            ->orderByDesc('requested_at')
            ->get()
            ->groupBy('patient_id')
            ->map(fn ($grants) => $grants->first());

        $patients = $appointments
            ->groupBy('patient_id')
            ->map(function ($patientAppointments, $patientId) use ($latestGrantByPatient) {
                $latestAppointment = $patientAppointments->first();
                $patient = $latestAppointment->patient;
                $grant = $latestGrantByPatient->get((int) $patientId);
                $isActive = $grant?->isActive() ?? false;
                $isPending = $grant && in_array($grant->status, ['requested', 'otp_sent'], true);

                $accessGroup = $isActive
                    ? 'active'
                    : ($isPending ? 'pending' : 'needed');

                return [
                    'patient_id' => $patient->patient_id,
                    'full_name' => $patient->full_name,
                    'age' => $patient->age,
                    'gender' => $patient->gender,
                    'blood_group' => $patient->blood_group,
                    'appointment_count' => $patientAppointments->count(),
                    'online_appointments' => $patientAppointments->where('appointment_type', 'online')->count(),
                    'onsite_appointments' => $patientAppointments->where('appointment_type', 'onsite')->count(),
                    'latest_appointment_label' => $latestAppointment->appointment_date
                        ? $latestAppointment->appointment_date->format('M j, Y')
                        : null,
                    'grant_status' => $grant?->status,
                    'access_group' => $accessGroup,
                    'access_label' => $grant?->statusLabel(),
                    'access_expires_at' => $isActive && $grant?->expires_at
                        ? $grant->expires_at->format('M j, g:i A')
                        : null,
                    'view_url' => $isActive ? route('doctor.records.show', $patient) : null,
                ];
            })
            ->sortBy(fn ($patient) => mb_strtolower($patient['full_name']))
            ->values();

        return response()->json([
            'stats' => [
                'total_patients' => $patients->count(),
                'active_access' => $patients->where('access_group', 'active')->count(),
                'awaiting_approval' => $patients->where('access_group', 'pending')->count(),
                'access_needed' => $patients->where('access_group', 'needed')->count(),
            ],
            'patients' => $patients,
        ]);
    }

    public function requestAccess(Patient $patient)
    {
        $doctor = Auth::user()->doctor;

        $hasSeenPatient = $doctor->appointments()
            ->where('patient_id', $patient->patient_id)
            ->exists();

        if (!$hasSeenPatient) {
            abort(403);
        }

        $alreadyPending = RecordAccessGrant::where('doctor_id', $doctor->doctor_id)
            ->where('patient_id', $patient->patient_id)
            ->whereIn('status', ['requested', 'otp_sent'])
            ->exists();

        if ($alreadyPending) {
            return response()->json([
                'message' => 'You already have a pending request for this patient.',
            ], 422);
        }

        $grant = RecordAccessGrant::create([
            'patient_id' => $patient->patient_id,
            'doctor_id' => $doctor->doctor_id,
            'status' => 'requested',
        ]);

        $result = $this->otp->issue($patient->account, 'record_access', $grant->grant_id);
        $grant->update([
            'status' => 'otp_sent',
            'otp_id' => $result['otp']->otp_id,
        ]);

        return response()->json([
            'message' => "Access requested — {$patient->full_name} needs to approve it.",
        ]);
    }

    private function expireStaleGrants(): void
    {
        RecordAccessGrant::where('status', 'approved')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);
    }
}
