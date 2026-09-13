<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\PatientHealthProfile;
use App\Models\RecordAccessGrant;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Patient-side (own records + approving/denying doctor access requests)
 * and doctor-side (requesting + viewing, once approved) medical record
 * access — mixed in one controller the same way AppointmentController and
 * PrescriptionController mix their two sides of one shared feature.
 *
 * The core rule: a doctor can only see a patient's records while they
 * hold a live "approved" grant (see RecordAccessGrant::isActive()) — the
 * patient is the one who approves it, using an email OTP (OtpService,
 * purpose 'record_access') sent to THEM, not the doctor.
 */
class MedicalRecordController extends Controller
{
    public function __construct(private OtpService $otp)
    {
    }

    // ---- Patient side ----

    /** Patient: own records + every access request, past or pending (GET /patient/records). */
    public function index(Request $request)
    {
        $this->expireStaleGrants();

        $patient = Auth::user()->patient;
        $searchQuery = trim((string) $request->query('q', ''));
        $recordType = (string) $request->query('type', '');
        $sort = $request->query('sort') === 'oldest' ? 'oldest' : 'recent';
        $allowedTypes = ['prescription', 'lab_result', 'diagnosis_note', 'uploaded_document'];

        $recordQuery = $patient->medicalRecords();
        if ($searchQuery !== '') {
            $recordQuery->where('description', 'like', '%' . $searchQuery . '%');
        }
        if (in_array($recordType, $allowedTypes, true)) {
            $recordQuery->where('record_type', $recordType);
        } else {
            $recordType = '';
        }
        $records = $sort === 'oldest'
            ? $recordQuery->orderBy('created_at')->get()
            : $recordQuery->orderByDesc('created_at')->get();

        $recordCounts = $patient->medicalRecords()
            ->selectRaw('record_type, COUNT(*) as total')
            ->groupBy('record_type')
            ->pluck('total', 'record_type');
        $grants = $patient->recordAccessGrants()->with('doctor')->orderByDesc('requested_at')->get();
        $allergies = $patient->allergies()->orderByDesc('created_at')->get();
        $vitals = $patient->vitals()->with('recordedByDoctor')->orderByDesc('recorded_at')->get();
        $latestVital = $vitals->first();
        $healthProfile = $patient->healthProfile;

        return view('patient.records', compact(
            'patient', 'records', 'recordCounts', 'grants', 'allergies', 'vitals', 'latestVital',
            'healthProfile', 'searchQuery', 'recordType', 'sort'
        ));
    }

    /** Patient: shows the "add a record" form (GET /patient/records/create). */
    public function create()
    {
        return view('patient.records-create');
    }

    /** Patient: printable full health summary — everything on file for them (GET /patient/records/print). */
    public function patientPrint()
    {
        return view('records.print', $this->buildSummary(Auth::user()->patient));
    }

    /**
     * Patient: add a record themselves — a lab result, note, uploaded
     * document, or a prescription they got somewhere outside this
     * platform (POST /patient/records). A self-added 'prescription'
     * record is just that — a record. It's NOT the same as a doctor
     * issuing one in-app (see PrescriptionController): it has no linked
     * `prescriptions` row, doesn't show up in "My Prescriptions", and
     * does NOT unlock ordering that medicine — CartController::add()
     * only ever checks Patient::prescribedMedicineIds(), which is built
     * strictly from real, doctor-issued prescription_items. Letting a
     * patient self-unlock ordering would defeat the whole point of that
     * gate.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'record_type' => ['required', Rule::in(['prescription', 'lab_result', 'diagnosis_note', 'uploaded_document'])],
            'description' => ['required', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
        ]);

        // Stored under storage/app/medical-records (NOT public/), same
        // reasoning as doctor certificate uploads — not reachable by just
        // guessing a URL, only through download() below's access check.
        $filePath = $request->hasFile('file')
            ? $request->file('file')->store('medical-records', 'local')
            : null;

        Auth::user()->patient->medicalRecords()->create([
            'record_type' => $data['record_type'],
            'description' => $data['description'],
            'file_path' => $filePath,
            'created_by_account_id' => Auth::id(),
        ]);

        return redirect()->route('patient.records')->with('success', 'Record added.');
    }

    /** Patient: update longer-lived personal health context shown alongside their medical record. */
    public function updateHealthProfile(Request $request)
    {
        $data = $request->validate([
            'cholesterol_status' => ['nullable', 'string', 'max:100'],
            'diabetes_risk' => ['nullable', 'string', 'max:100'],
            'diet_notes' => ['nullable', 'string', 'max:1000'],
            'therapy_notes' => ['nullable', 'string', 'max:1000'],
            'major_health_risks' => ['nullable', 'string', 'max:1000'],
            'chronic_conditions' => ['nullable', 'string', 'max:1000'],
            'lifestyle_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        PatientHealthProfile::updateOrCreate(
            ['patient_id' => Auth::user()->patient->patient_id],
            $data
        );

        return redirect()->route('patient.records')->with('success', __('patient.records.health_info_saved'));
    }

    /** Patient: approve a pending request by entering the code emailed to them (POST /patient/records/grants/{grant}/approve). */
    public function approveGrant(Request $request, RecordAccessGrant $grant)
    {
        $this->authorizePatientGrant($grant);

        if ($grant->status !== 'otp_sent') {
            return back()->withErrors(['grant' => 'This request is no longer awaiting your approval.']);
        }

        $data = $request->validate(['otp_code' => ['required', 'string', 'size:6']]);

        $result = $this->otp->verify(Auth::user(), 'record_access', $data['otp_code']);
        if (!$result['ok']) {
            return back()->withErrors(['otp_code' => $result['message']]);
        }

        // The OTP could in theory belong to a DIFFERENT pending grant (if
        // this patient had two requests in flight) — the code the OTP
        // carries back (reference_id) is what actually ties it to THIS grant.
        if ((int) $result['reference_id'] !== $grant->grant_id) {
            return back()->withErrors(['otp_code' => 'That code does not match this request.']);
        }

        $hours = (int) AppSetting::get('RECORD_ACCESS_GRANT_HOURS', '24');
        $grant->update([
            'status' => 'approved',
            'granted_at' => now(),
            'expires_at' => now()->addHours($hours),
        ]);

        return back()->with('success', "Access approved for {$hours} hours.");
    }

    /** Patient: deny a pending request outright, no code needed (POST /patient/records/grants/{grant}/deny). */
    public function denyGrant(RecordAccessGrant $grant)
    {
        $this->authorizePatientGrant($grant);

        if (!in_array($grant->status, ['requested', 'otp_sent'], true)) {
            return back()->withErrors(['grant' => 'This request can no longer be denied.']);
        }

        $grant->update(['status' => 'denied']);

        return back()->with('success', 'Request denied.');
    }

    // ---- Doctor side ----

    /** Doctor: every patient they've had an appointment with, and the status of any access request (GET /doctor/records). */
    public function patients()
    {
        $this->expireStaleGrants();

        $doctor = Auth::user()->doctor;

        $patientIds = $doctor->appointments()->pluck('patient_id')->unique();
        $patients = Patient::whereIn('patient_id', $patientIds)->orderBy('full_name')->get();

        // Latest grant per patient, so the view can show "no request yet"
        // vs "pending" vs "approved" vs "denied/expired" for each one.
        $latestGrantByPatient = $doctor->recordAccessGrants()
            ->orderByDesc('requested_at')
            ->get()
            ->groupBy('patient_id')
            ->map(fn ($grants) => $grants->first());

        return view('doctor.patient-records', compact('patients', 'latestGrantByPatient'));
    }

    /** Doctor: request access to one patient's records (POST /doctor/records/{patient}/request). */
    public function requestAccess(Patient $patient)
    {
        $doctor = Auth::user()->doctor;

        $hasSeenPatient = $doctor->appointments()->where('patient_id', $patient->patient_id)->exists();
        if (!$hasSeenPatient) {
            abort(403);
        }

        $alreadyPending = RecordAccessGrant::where('doctor_id', $doctor->doctor_id)
            ->where('patient_id', $patient->patient_id)
            ->whereIn('status', ['requested', 'otp_sent'])
            ->exists();
        if ($alreadyPending) {
            return back()->withErrors(['grant' => 'You already have a pending request for this patient.']);
        }

        $grant = RecordAccessGrant::create([
            'patient_id' => $patient->patient_id,
            'doctor_id' => $doctor->doctor_id,
            'status' => 'requested',
        ]);

        $result = $this->otp->issue($patient->account, 'record_access', $grant->grant_id);
        $grant->update(['status' => 'otp_sent', 'otp_id' => $result['otp']->otp_id]);

        return back()->with('success', "Access requested — {$patient->full_name} needs to approve it.");
    }

    /** Doctor: view a patient's records, only while an approved grant is still active (GET /doctor/records/{patient}). */
    public function viewPatientRecords(Patient $patient)
    {
        $grant = $this->activeGrantFor($patient);
        if (!$grant) {
            abort(403, "You don't currently have approved access to this patient's records.");
        }

        $records = $patient->medicalRecords()->orderByDesc('created_at')->get();
        $allergies = $patient->allergies()->orderByDesc('created_at')->get();
        $vitals = $patient->vitals()->with('recordedByDoctor')->orderByDesc('recorded_at')->get();
        $healthProfile = $patient->healthProfile;

        return view('doctor.patient-record-list', compact('patient', 'records', 'grant', 'allergies', 'vitals', 'healthProfile'));
    }

    /** Doctor: printable full health summary for one patient, only while an approved grant is active (GET /doctor/records/{patient}/print). */
    public function doctorPrintPatient(Patient $patient)
    {
        if (!$this->activeGrantFor($patient)) {
            abort(403, "You don't currently have approved access to this patient's records.");
        }

        return view('records.print', array_merge($this->buildSummary($patient), ['printedByDoctor' => Auth::user()->doctor]));
    }

    // ---- Shared: downloading a record's file ----

    /** Downloads a record's file, if the current user is allowed to see it (GET /records/{record}/download). */
    public function download(MedicalRecord $record)
    {
        $user = Auth::user();

        $allowed = ($user->role === 'patient' && $record->patient_id === $user->patient->patient_id)
            || ($user->role === 'doctor' && $this->activeGrantFor($record->patient) !== null);

        if (!$allowed) {
            abort(403);
        }

        if (!$record->file_path || !Storage::disk('local')->exists($record->file_path)) {
            abort(404, 'This record has no file attached.');
        }

        return Storage::disk('local')->download($record->file_path);
    }

    // ---- Helpers ----

    /**
     * Gathers everything shown on the printable EHR summary — one place so
     * both the patient's own print route and the doctor's (grant-gated)
     * print route build the exact same page from the exact same data.
     */
    private function buildSummary(Patient $patient): array
    {
        $patient->load('account');

        return [
            'patient' => $patient,
            'allergies' => $patient->allergies()->orderByDesc('created_at')->get(),
            'vitals' => $patient->vitals()->with('recordedByDoctor')->orderByDesc('recorded_at')->get(),
            'healthProfile' => $patient->healthProfile,
            'prescriptions' => $patient->prescriptions()
                ->with(['doctor', 'items.medicine', 'facilityItems.facilityType.category'])
                ->orderByDesc('issued_at')
                ->get(),
            // 'prescription'-type records are excluded here — the
            // "Current Medications" section below already shows the full
            // detail of every issued prescription, so listing them again
            // in the generic records table would just be a duplicate line.
            'records' => $patient->medicalRecords()->where('record_type', '!=', 'prescription')->orderByDesc('created_at')->get(),
            'printedByDoctor' => null,
        ];
    }

    /** Flips any 'approved' grant whose time has actually run out to 'expired', so lists show the true state. */
    private function expireStaleGrants(): void
    {
        RecordAccessGrant::where('status', 'approved')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);
    }

    private function activeGrantFor(Patient $patient): ?RecordAccessGrant
    {
        return RecordAccessGrant::where('doctor_id', Auth::user()->doctor->doctor_id)
            ->where('patient_id', $patient->patient_id)
            ->where('status', 'approved')
            ->where('expires_at', '>', now())
            ->latest('granted_at')
            ->first();
    }

    private function authorizePatientGrant(RecordAccessGrant $grant): void
    {
        if ($grant->patient_id !== Auth::user()->patient->patient_id) {
            abort(403);
        }
    }
}
