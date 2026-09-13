<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\FacilityCategory;
use App\Models\MedicalRecord;
use App\Models\MedicineMaster;
use App\Models\Prescription;
use App\Models\RecordAccessGrant;
use App\Services\ConsultationSummaryService;
use App\Services\MedicineReminderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Doctor-side prescription issuance and patient-side prescription viewing
 * — split here the same way AppointmentController mixes patient- and
 * doctor-facing methods for one shared feature. Issuing a prescription
 * uses the same "build up a list in the session, then finalize" shape as
 * CartController: add medicines to a draft one at a time, then submit.
 * This is what CartController::add() gates ordering on — a patient can
 * only add a medicine to their cart if it appears in one of these.
 */
class PrescriptionController extends Controller
{
    public function __construct(
        private ConsultationSummaryService $consultationSummaries,
        private MedicineReminderService $medicineReminders,
    ) {
    }

    // ---- Doctor-side: issuing a prescription ----

    /**
     * Shows the prescription draft for one appointment (GET
     * /doctor/appointments/{appointment}/prescription) — or, if one has
     * already been issued, a READ-ONLY view of it instead. Once issued a
     * prescription can be looked at by the doctor or patient but never
     * edited — there's no route anywhere that mutates an existing one
     * (addItem()/removeItem()/store() below all refuse to touch a draft
     * once $appointment->prescription is set).
     */
    public function create(Appointment $appointment)
    {
        $this->authorizeDoctor($appointment);

        if ($appointment->prescription) {
            $appointment->prescription->load(['items.medicine', 'facilityItems.facilityType.category']);
            return view('doctor.prescription-view', compact('appointment'));
        }

        if ($appointment->status !== 'completed') {
            return redirect()->route('doctor.appointments')->withErrors(['prescription' => 'Mark this appointment as visited before writing a prescription.']);
        }

        $draft = session($this->draftKey($appointment), []);
        $medicines = MedicineMaster::orderBy('generic_name')->get();
        $draftMedicines = $medicines->keyBy('medicine_master_id');
        $allergies = $appointment->patient->allergies;

        $facilityDraft = session($this->facilityDraftKey($appointment), []);
        $facilityCategories = FacilityCategory::with('facilityTypes')->orderBy('category_name')->get();
        $draftFacilityTypes = $facilityCategories->pluck('facilityTypes')->flatten()->keyBy('facility_type_id');

        return view('doctor.prescription-form', compact(
            'appointment', 'draft', 'medicines', 'draftMedicines', 'allergies',
            'facilityDraft', 'facilityCategories', 'draftFacilityTypes'
        ));
    }

    /** Adds one medicine to the draft (POST /doctor/appointments/{appointment}/prescription/add-item). */
    public function addItem(Request $request, Appointment $appointment)
    {
        $this->authorizeDoctor($appointment);

        if ($appointment->prescription) {
            abort(403, 'This prescription has already been issued and can no longer be changed.');
        }

        $data = $request->validate([
            'medicine_master_id' => ['required', 'integer', 'exists:medicine_master,medicine_master_id'],
            'for_illness' => ['nullable', 'string', 'max:150'],
            'dosage' => ['nullable', 'string', 'max:100'],
            'frequency' => ['nullable', 'string', 'max:100'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $key = $this->draftKey($appointment);
        $draft = session($key, []);
        $draft[] = $data;
        session([$key => $draft]);

        return back()->with('success', 'Medicine added to prescription.');
    }

    /** Removes one item from the draft, identified by its position in the list (POST .../remove-item). */
    public function removeItem(Request $request, Appointment $appointment)
    {
        $this->authorizeDoctor($appointment);

        if ($appointment->prescription) {
            abort(403, 'This prescription has already been issued and can no longer be changed.');
        }

        $index = (int) $request->input('index');
        $key = $this->draftKey($appointment);
        $draft = session($key, []);
        unset($draft[$index]);
        session([$key => array_values($draft)]);

        return back()->with('success', 'Removed from draft.');
    }

    /** Adds one test/operation to the facility draft (POST .../prescription/add-facility-item). */
    public function addFacilityItem(Request $request, Appointment $appointment)
    {
        $this->authorizeDoctor($appointment);

        if ($appointment->prescription) {
            abort(403, 'This prescription has already been issued and can no longer be changed.');
        }

        $data = $request->validate([
            'facility_type_id' => ['required', 'integer', 'exists:facility_types,facility_type_id'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $key = $this->facilityDraftKey($appointment);
        $facilityDraft = session($key, []);
        $facilityDraft[] = $data;
        session([$key => $facilityDraft]);

        return back()->with('success', 'Test/operation added to prescription.');
    }

    /** Removes one item from the facility draft (POST .../prescription/remove-facility-item). */
    public function removeFacilityItem(Request $request, Appointment $appointment)
    {
        $this->authorizeDoctor($appointment);

        if ($appointment->prescription) {
            abort(403, 'This prescription has already been issued and can no longer be changed.');
        }

        $index = (int) $request->input('index');
        $key = $this->facilityDraftKey($appointment);
        $facilityDraft = session($key, []);
        unset($facilityDraft[$index]);
        session([$key => array_values($facilityDraft)]);

        return back()->with('success', 'Removed from draft.');
    }

    /** Finalizes the draft into a real prescription (POST /doctor/appointments/{appointment}/prescription). */
    public function store(Request $request, Appointment $appointment)
    {
        $this->authorizeDoctor($appointment);

        if ($appointment->prescription) {
            return back()->withErrors(['prescription' => 'A prescription already exists for this appointment.']);
        }

        $data = $request->validate(['diagnosis_notes' => ['nullable', 'string', 'max:2000']]);

        $key = $this->draftKey($appointment);
        $draft = session($key, []);
        $facilityKey = $this->facilityDraftKey($appointment);
        $facilityDraft = session($facilityKey, []);

        if (empty($draft) && empty($facilityDraft)) {
            return back()->withErrors(['prescription' => 'Add at least one medicine, test, or operation before issuing the prescription.']);
        }

        $prescription = DB::transaction(function () use ($appointment, $data, $draft, $facilityDraft) {
            $prescription = Prescription::create([
                'appointment_id' => $appointment->appointment_id,
                'doctor_id' => $appointment->doctor_id,
                'patient_id' => $appointment->patient_id,
                'diagnosis_notes' => $data['diagnosis_notes'] ?? null,
            ]);

            foreach ($draft as $item) {
                $prescriptionItem = $prescription->items()->create($item);

                // Sets up sensible default "take your medicine" reminder
                // times from the frequency text right away — the patient
                // doesn't have to remember to go set these up themselves
                // (they can still adjust/remove them from My Prescriptions).
                $this->medicineReminders->scheduleDefaults($prescriptionItem);
            }

            foreach ($facilityDraft as $item) {
                $prescription->facilityItems()->create($item);
            }

            // Every prescription automatically becomes part of the
            // patient's medical history — see MedicalRecordController for
            // how a doctor gets (OTP-gated) access to view it later.
            MedicalRecord::create([
                'patient_id' => $appointment->patient_id,
                'record_type' => 'prescription',
                'reference_id' => $prescription->prescription_id,
                'description' => $data['diagnosis_notes'] ?? 'Prescription from Dr. ' . $appointment->doctor->full_name,
                'created_by_account_id' => $appointment->doctor->account_id,
            ]);

            return $prescription;
        });

        session()->forget($key);
        session()->forget($facilityKey);

        // Issuing the prescription is the natural "this visit is wrapped
        // up" moment for an online appointment — auto-generate the
        // consultation summary here rather than adding a separate step
        // the doctor has to remember to trigger.
        if ($appointment->appointment_type === 'online' && $appointment->consultationSession) {
            $prescription->load(['appointment', 'doctor', 'patient', 'items.medicine', 'facilityItems.facilityType']);
            $summary = $this->consultationSummaries->generate($prescription);

            $appointment->consultationSession->update([
                'summary' => $summary,
                'ended_at' => $appointment->consultationSession->ended_at ?? now(),
                'status' => 'ended',
            ]);
        }

        return redirect()->route('doctor.appointments')->with('success', 'Prescription issued.');
    }

    private function draftKey(Appointment $appointment): string
    {
        return "prescription_draft.{$appointment->appointment_id}";
    }

    private function facilityDraftKey(Appointment $appointment): string
    {
        return "prescription_facility_draft.{$appointment->appointment_id}";
    }

    private function authorizeDoctor(Appointment $appointment): void
    {
        if ($appointment->doctor_id !== Auth::user()->doctor->doctor_id) {
            abort(403);
        }
    }

    // ---- Patient-side: viewing prescriptions ----

    /** Patient: view every prescription they've received (GET /patient/prescriptions). */
    public function myPrescriptions(Request $request)
    {
        $patient = Auth::user()->patient;
        $search = trim((string) $request->get('q', ''));
        $status = in_array((string) $request->get('status', 'all'), ['all', 'active', 'completed', 'procedures'], true)
            ? (string) $request->get('status', 'all')
            : 'all';
        $sort = in_array((string) $request->get('sort', 'recent'), ['recent', 'oldest', 'doctor'], true)
            ? (string) $request->get('sort', 'recent')
            : 'recent';

        $query = $patient->prescriptions()->with([
            'doctor.account',
            'doctor.specialties',
            'items.medicine',
            'items.reminderTimes',
            'facilityItems.facilityType.category',
        ]);

        if ($search !== '') {
            $query->where(function ($prescriptionQuery) use ($search) {
                $prescriptionQuery
                    ->where('diagnosis_notes', 'like', "%{$search}%")
                    ->orWhereHas('doctor', fn ($doctorQuery) => $doctorQuery->where('full_name', 'like', "%{$search}%"))
                    ->orWhereHas('items.medicine', function ($medicineQuery) use ($search) {
                        $medicineQuery->where('generic_name', 'like', "%{$search}%")
                            ->orWhere('brand_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('facilityItems.facilityType', fn ($facilityQuery) => $facilityQuery->where('name', 'like', "%{$search}%"));
            });
        }

        if ($sort === 'oldest') {
            $query->orderBy('issued_at');
        } else {
            $query->orderByDesc('issued_at');
        }

        $prescriptions = $query->get();

        $isItemActive = static function (Prescription $prescription, $item): bool {
            if (!$item->duration_days) {
                return true;
            }

            return now()->lte($prescription->issued_at->copy()->addDays((int) $item->duration_days));
        };

        if ($status === 'active') {
            $prescriptions = $prescriptions->filter(
                fn (Prescription $prescription) => $prescription->items->contains(fn ($item) => $isItemActive($prescription, $item))
            )->values();
        } elseif ($status === 'completed') {
            $prescriptions = $prescriptions->filter(function (Prescription $prescription) use ($isItemActive) {
                return $prescription->items->isNotEmpty()
                    && !$prescription->items->contains(fn ($item) => $isItemActive($prescription, $item));
            })->values();
        } elseif ($status === 'procedures') {
            $prescriptions = $prescriptions->filter(fn (Prescription $prescription) => $prescription->facilityItems->isNotEmpty())->values();
        }

        if ($sort === 'doctor') {
            $prescriptions = $prescriptions->sortBy(fn (Prescription $prescription) => strtolower($prescription->doctor->full_name))->values();
        }

        $recentOrders = $patient->medicineOrders()
            ->with('pharmacy')
            ->orderByDesc('created_at')
            ->take(3)
            ->get();

        return view('patient.my-prescriptions', compact('prescriptions', 'search', 'status', 'sort', 'recentOrders'));
    }

    /**
     * Shared: view one specific prescription's full details — the owning
     * patient, or a doctor currently holding an approved record-access
     * grant for that patient, only (GET /prescriptions/{prescription}).
     * This is what a "Prescription" row on the Medical Records page links
     * to, since that list only shows a one-line description otherwise.
     * Read-only, same as everywhere else a prescription is shown.
     */
    public function show(Prescription $prescription)
    {
        $user = Auth::user();

        $isOwningPatient = $user->role === 'patient' && $prescription->patient_id === $user->patient->patient_id;

        $hasActiveGrant = $user->role === 'doctor' && RecordAccessGrant::where('doctor_id', $user->doctor->doctor_id)
            ->where('patient_id', $prescription->patient_id)
            ->where('status', 'approved')
            ->where('expires_at', '>', now())
            ->exists();

        if (!$isOwningPatient && !$hasActiveGrant) {
            abort(403);
        }

        $prescription->load(['items.medicine', 'facilityItems.facilityType.category', 'doctor.account', 'doctor.specialties', 'patient', 'appointment.hospital', 'appointment.consultationSession']);

        return view('prescriptions.show', compact('prescription'));
    }
}
