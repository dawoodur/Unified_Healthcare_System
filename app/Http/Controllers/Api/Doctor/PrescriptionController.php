<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\FacilityCategory;
use App\Models\MedicalRecord;
use App\Models\MedicineMaster;
use App\Models\Prescription;
use App\Services\ConsultationSummaryService;
use App\Services\MedicineReminderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PrescriptionController extends Controller
{
    public function __construct(
        private ConsultationSummaryService $consultationSummaries,
        private MedicineReminderService $medicineReminders,
    ) {
    }

    public function show(Appointment $appointment)
    {
        $this->authorizeDoctor($appointment);
        $appointment->load([
            'patient.account',
            'patient.allergies',
            'hospital',
            'template',
            'prescription.items.medicine',
            'prescription.facilityItems.facilityType.category',
        ]);

        if (!$appointment->prescription && $appointment->status !== 'completed') {
            return response()->json([
                'message' => 'Mark this appointment as visited before writing a prescription.',
            ], 422);
        }

        return response()->json($this->payload($appointment));
    }

    public function addMedicine(Request $request, Appointment $appointment)
    {
        $this->authorizeEditable($appointment);

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

        return response()->json(['message' => 'Medicine added to prescription draft.']);
    }

    public function removeMedicine(Appointment $appointment, int $index)
    {
        $this->authorizeEditable($appointment);

        $key = $this->draftKey($appointment);
        $draft = session($key, []);

        if (!array_key_exists($index, $draft)) {
            return response()->json(['message' => 'Medicine draft item not found.'], 404);
        }

        unset($draft[$index]);
        session([$key => array_values($draft)]);

        return response()->json(['message' => 'Medicine removed from prescription draft.']);
    }

    public function addFacility(Request $request, Appointment $appointment)
    {
        $this->authorizeEditable($appointment);

        $data = $request->validate([
            'facility_type_id' => ['required', 'integer', 'exists:facility_types,facility_type_id'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $key = $this->facilityDraftKey($appointment);
        $draft = session($key, []);
        $draft[] = $data;
        session([$key => $draft]);

        return response()->json(['message' => 'Test or procedure added to prescription draft.']);
    }

    public function removeFacility(Appointment $appointment, int $index)
    {
        $this->authorizeEditable($appointment);

        $key = $this->facilityDraftKey($appointment);
        $draft = session($key, []);

        if (!array_key_exists($index, $draft)) {
            return response()->json(['message' => 'Test or procedure draft item not found.'], 404);
        }

        unset($draft[$index]);
        session([$key => array_values($draft)]);

        return response()->json(['message' => 'Test or procedure removed from prescription draft.']);
    }

    public function issue(Request $request, Appointment $appointment)
    {
        $this->authorizeEditable($appointment);

        $data = $request->validate([
            'diagnosis_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $medicineKey = $this->draftKey($appointment);
        $facilityKey = $this->facilityDraftKey($appointment);
        $medicineDraft = session($medicineKey, []);
        $facilityDraft = session($facilityKey, []);

        if (empty($medicineDraft) && empty($facilityDraft)) {
            return response()->json([
                'message' => 'Add at least one medicine, test, or procedure before issuing the prescription.',
            ], 422);
        }

        $prescription = DB::transaction(function () use ($appointment, $data, $medicineDraft, $facilityDraft) {
            $prescription = Prescription::create([
                'appointment_id' => $appointment->appointment_id,
                'doctor_id' => $appointment->doctor_id,
                'patient_id' => $appointment->patient_id,
                'diagnosis_notes' => $data['diagnosis_notes'] ?? null,
            ]);

            foreach ($medicineDraft as $item) {
                $prescriptionItem = $prescription->items()->create($item);
                $this->medicineReminders->scheduleDefaults($prescriptionItem);
            }

            foreach ($facilityDraft as $item) {
                $prescription->facilityItems()->create($item);
            }

            MedicalRecord::create([
                'patient_id' => $appointment->patient_id,
                'record_type' => 'prescription',
                'reference_id' => $prescription->prescription_id,
                'description' => $data['diagnosis_notes'] ?? 'Prescription from Dr. ' . $appointment->doctor->full_name,
                'created_by_account_id' => $appointment->doctor->account_id,
            ]);

            return $prescription;
        });

        session()->forget($medicineKey);
        session()->forget($facilityKey);

        if ($appointment->appointment_type === 'online' && $appointment->consultationSession) {
            $prescription->load(['appointment', 'doctor', 'patient', 'items.medicine', 'facilityItems.facilityType']);
            $summary = $this->consultationSummaries->generate($prescription);

            $appointment->consultationSession->update([
                'summary' => $summary,
                'ended_at' => $appointment->consultationSession->ended_at ?? now(),
                'status' => 'ended',
            ]);
        }

        return response()->json([
            'message' => 'Prescription issued.',
            'prescription_id' => $prescription->prescription_id,
        ]);
    }

    private function payload(Appointment $appointment): array
    {
        $appointment->loadMissing([
            'patient.account',
            'patient.allergies',
            'hospital',
            'template',
            'prescription.items.medicine',
            'prescription.facilityItems.facilityType.category',
        ]);

        $medicines = MedicineMaster::query()
            ->orderBy('generic_name')
            ->get(['medicine_master_id', 'generic_name', 'brand_name', 'form', 'strength']);

        $medicineMap = $medicines->keyBy('medicine_master_id');

        $facilityCategories = FacilityCategory::with(['facilityTypes' => fn ($query) => $query->orderBy('name')])
            ->orderBy('category_name')
            ->get();
        $facilityMap = $facilityCategories->pluck('facilityTypes')->flatten()->keyBy('facility_type_id');

        $medicineDraft = collect(session($this->draftKey($appointment), []))
            ->values()
            ->map(function (array $item, int $index) use ($medicineMap) {
                $medicine = $medicineMap->get((int) $item['medicine_master_id']);
                return [
                    'index' => $index,
                    'medicine_master_id' => (int) $item['medicine_master_id'],
                    'medicine_name' => $this->medicineName($medicine),
                    'for_illness' => $item['for_illness'] ?? null,
                    'dosage' => $item['dosage'] ?? null,
                    'frequency' => $item['frequency'] ?? null,
                    'duration_days' => isset($item['duration_days']) ? (int) $item['duration_days'] : null,
                    'duration_label' => !empty($item['duration_days']) ? ((int) $item['duration_days']) . ' day(s)' : null,
                    'notes' => $item['notes'] ?? null,
                ];
            });

        $facilityDraft = collect(session($this->facilityDraftKey($appointment), []))
            ->values()
            ->map(function (array $item, int $index) use ($facilityMap) {
                $type = $facilityMap->get((int) $item['facility_type_id']);
                return [
                    'index' => $index,
                    'facility_type_id' => (int) $item['facility_type_id'],
                    'name' => $type?->name ?? 'Unknown service',
                    'category' => $type?->category?->category_name,
                    'notes' => $item['notes'] ?? null,
                ];
            });

        return [
            'mode' => $appointment->prescription ? 'issued' : 'draft',
            'appointment' => [
                'appointment_id' => (int) $appointment->appointment_id,
                'appointment_date' => $appointment->appointment_date->toDateString(),
                'date_label' => $appointment->appointment_date->format('D, M j Y'),
                'time_range_label' => $appointment->timeRangeLabel(),
                'appointment_type' => $appointment->appointment_type,
                'hospital_name' => $appointment->hospital?->hospital_name,
                'status' => $appointment->status,
                'status_label' => $appointment->statusLabel(),
                'patient' => [
                    'patient_id' => (int) $appointment->patient->patient_id,
                    'full_name' => $appointment->patient->full_name,
                    'age' => $appointment->patient->age,
                    'gender' => $appointment->patient->gender,
                    'blood_group' => $appointment->patient->blood_group,
                    'photo_url' => $appointment->patient->account?->photoUrl(),
                ],
            ],
            'allergies' => $appointment->patient->allergies->map(fn ($allergy) => [
                'allergy_id' => $allergy->allergy_id,
                'allergen' => $allergy->allergen,
                'reaction' => $allergy->reaction,
            ])->values(),
            'medicines' => $medicines->map(fn ($medicine) => [
                'medicine_master_id' => (int) $medicine->medicine_master_id,
                'generic_name' => $medicine->generic_name,
                'brand_name' => $medicine->brand_name,
                'form' => $medicine->form,
                'strength' => $medicine->strength,
            ])->values(),
            'facility_categories' => $facilityCategories->map(fn ($category) => [
                'category_id' => (int) $category->category_id,
                'category_name' => $category->category_name,
                'types' => $category->facilityTypes->map(fn ($type) => [
                    'facility_type_id' => (int) $type->facility_type_id,
                    'name' => $type->name,
                ])->values(),
            ])->values(),
            'draft' => [
                'medicines' => $medicineDraft,
                'facilities' => $facilityDraft,
            ],
            'prescription' => $appointment->prescription ? $this->serializePrescription($appointment->prescription) : null,
        ];
    }

    private function serializePrescription(Prescription $prescription): array
    {
        return [
            'prescription_id' => (int) $prescription->prescription_id,
            'issued_at' => $prescription->issued_at?->toIso8601String(),
            'issued_at_label' => $prescription->issued_at?->format('M j, Y g:i A'),
            'diagnosis_notes' => $prescription->diagnosis_notes,
            'medicines' => $prescription->items->map(fn ($item) => [
                'prescription_item_id' => (int) $item->prescription_item_id,
                'medicine_master_id' => (int) $item->medicine_master_id,
                'medicine_name' => $this->medicineName($item->medicine),
                'for_illness' => $item->for_illness,
                'dosage' => $item->dosage,
                'frequency' => $item->frequency,
                'duration_days' => $item->duration_days,
                'duration_label' => $item->duration_days ? ((int) $item->duration_days) . ' day(s)' : null,
                'notes' => $item->notes,
            ])->values(),
            'facilities' => $prescription->facilityItems->map(fn ($item) => [
                'prescription_facility_item_id' => (int) $item->prescription_facility_item_id,
                'facility_type_id' => (int) $item->facility_type_id,
                'name' => $item->facilityType?->name ?? 'Unknown service',
                'category' => $item->facilityType?->category?->category_name,
                'notes' => $item->notes,
            ])->values(),
        ];
    }

    private function medicineName($medicine): string
    {
        if (!$medicine) {
            return 'Unknown medicine';
        }

        $name = $medicine->generic_name;
        if ($medicine->brand_name) {
            $name .= ' (' . $medicine->brand_name . ')';
        }
        if ($medicine->strength) {
            $name .= ' · ' . $medicine->strength;
        }

        return $name;
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
        if ((int) $appointment->doctor_id !== (int) Auth::user()->doctor->doctor_id) {
            abort(403);
        }
    }

    private function authorizeEditable(Appointment $appointment): void
    {
        $this->authorizeDoctor($appointment);
        $appointment->loadMissing('prescription');

        if ($appointment->prescription) {
            abort(403, 'This prescription has already been issued and can no longer be changed.');
        }

        if ($appointment->status !== 'completed') {
            abort(422, 'Mark this appointment as visited before writing a prescription.');
        }
    }
}
