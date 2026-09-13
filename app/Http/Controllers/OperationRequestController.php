<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\HospitalFacility;
use App\Models\OperationRequest;
use App\Models\PaymentMethod;
use App\Services\OperationRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The major-operation request/offer/accept flow — a negotiation between
 * patient and hospital, unlike FacilityBookingController's instant
 * self-service booking. Patient methods and hospital methods both live
 * here (same shape as AppointmentController splitting patient/doctor
 * methods in one file) since they're two sides of the exact same
 * resource. All the actual status-transition logic lives in
 * OperationRequestService — this controller just validates input,
 * checks who's allowed to do what, and calls it.
 */
class OperationRequestController extends Controller
{
    public function __construct(private OperationRequestService $operations)
    {
    }

    // Shared by index()/hospitalIndex() below: which statuses are still
    // "in motion" vs settled one way or another.
    private const PENDING_STATUSES = ['requested', 'offered', 'accepted', 'declined'];

    // ------------------------------------------------------------------
    // Patient side
    // ------------------------------------------------------------------

    /** Patient: their own request list, split into active/history and sorted earliest scheduled date first (GET /patient/operations). */
    public function index()
    {
        $patientId = Auth::user()->patient->patient_id;
        $with = ['hospital', 'facilityType', 'assignedDoctor'];

        $pending = OperationRequest::where('patient_id', $patientId)
            ->whereIn('status', self::PENDING_STATUSES)
            ->with($with)->orderBy('scheduled_date')->orderBy('scheduled_time')->get();

        $completed = OperationRequest::where('patient_id', $patientId)
            ->whereNotIn('status', self::PENDING_STATUSES)
            ->with($with)->orderBy('scheduled_date')->orderBy('scheduled_time')->get();

        return view('patient.operations.index', compact('pending', 'completed'));
    }

    /** Patient: shows the request form for one hospital's Surgery-category offering (GET /patient/operations/create/{offering}). */
    public function create(HospitalFacility $offering)
    {
        $this->authorizeSurgeryOffering($offering);

        return view('patient.operations.create', ['offering' => $offering->load('hospital', 'facilityType')]);
    }

    /** Patient: submits the request (POST /patient/operations/create/{offering}). */
    public function store(Request $request, HospitalFacility $offering)
    {
        $this->authorizeSurgeryOffering($offering);

        $data = $request->validate([
            'patient_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $patient = Auth::user()->patient;
        $this->operations->request($patient, $offering->hospital, $offering, $data['patient_notes'] ?? null);

        return redirect()->route('patient.operations')->with('success', 'Your operation request has been sent to the hospital.');
    }

    /** Patient: shows the accept-and-pay page for an offered request (GET /patient/operations/{operationRequest}/accept). */
    public function showAccept(OperationRequest $operationRequest)
    {
        $this->authorizePatientOwns($operationRequest);

        if ($operationRequest->status !== 'offered') {
            return redirect()->route('patient.operations')->withErrors(['status' => 'This request no longer has an active offer.']);
        }

        $operationRequest->load(['hospital', 'facilityType', 'assignedDoctor']);
        $paymentMethods = PaymentMethod::where('method_name', 'Cash')->get();

        return view('patient.operations.accept', compact('operationRequest', 'paymentMethods'));
    }

    /** Patient: accepts and pays (POST /patient/operations/{operationRequest}/accept). */
    public function accept(Request $request, OperationRequest $operationRequest)
    {
        $this->authorizePatientOwns($operationRequest);

        $data = $request->validate([
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,payment_method_id'],
        ]);

        $paymentMethod = PaymentMethod::findOrFail($data['payment_method_id']);
        $result = $this->operations->accept($operationRequest, $paymentMethod);

        if (!$result['ok']) {
            return redirect()->route('patient.operations')->withErrors(['status' => $result['message']]);
        }

        return redirect()->route('patient.operations')->with('success', $result['message']);
    }

    /** Patient: declines an offer (POST /patient/operations/{operationRequest}/decline). */
    public function decline(OperationRequest $operationRequest)
    {
        $this->authorizePatientOwns($operationRequest);

        $result = $this->operations->decline($operationRequest);

        return redirect()->route('patient.operations')->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    /** Patient: withdraws a request that hasn't been offered yet (POST /patient/operations/{operationRequest}/cancel). */
    public function cancel(OperationRequest $operationRequest)
    {
        $this->authorizePatientOwns($operationRequest);

        $result = $this->operations->cancel($operationRequest);

        return redirect()->route('patient.operations')->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    // ------------------------------------------------------------------
    // Hospital side
    // ------------------------------------------------------------------

    /**
     * Hospital: every request made to them, requests/declines needing a
     * response shown first — same "what needs my attention" idea as
     * other priority-ordered lists in this app, just via a status rank
     * instead of a date.
     */
    public function hospitalIndex()
    {
        $hospitalId = Auth::user()->hospital->hospital_id;
        $with = ['patient', 'facilityType', 'assignedDoctor'];

        $pending = OperationRequest::where('hospital_id', $hospitalId)
            ->whereIn('status', self::PENDING_STATUSES)
            ->with($with)->orderBy('scheduled_date')->orderBy('scheduled_time')->get();

        $completed = OperationRequest::where('hospital_id', $hospitalId)
            ->whereNotIn('status', self::PENDING_STATUSES)
            ->with($with)->orderBy('scheduled_date')->orderBy('scheduled_time')->get();

        return view('hospital.operations.index', compact('pending', 'completed'));
    }

    /** Hospital: shows the offer form (GET /hospital/operations/{operationRequest}/offer). */
    public function offerForm(OperationRequest $operationRequest)
    {
        $this->authorizeHospitalOwns($operationRequest);

        $hospital = Auth::user()->hospital;
        $doctors = $hospital->activeDoctors()->orderBy('full_name')->get();

        $suggestedDate = now()->addDay()->toDateString();
        $suggestedSerial = $this->operations->suggestSerial($hospital, $suggestedDate);

        $operationRequest->load(['patient', 'facilityType']);

        return view('hospital.operations.offer', compact('operationRequest', 'doctors', 'suggestedDate', 'suggestedSerial'));
    }

    /** Hospital: submits (or updates, after a decline) the offer (POST /hospital/operations/{operationRequest}/offer). */
    public function storeOffer(Request $request, OperationRequest $operationRequest)
    {
        $hospital = Auth::user()->hospital;
        $this->authorizeHospitalOwns($operationRequest);

        $data = $request->validate([
            'assigned_doctor_id' => ['required', 'integer'],
            'scheduled_date' => ['required', 'date_format:Y-m-d'],
            'scheduled_time' => ['required', 'date_format:H:i'],
            'serial_number' => ['required', 'integer', 'min:1'],
        ]);

        // The doctor picked must actually be one of THIS hospital's
        // assigned doctors — checked here (not just hidden in the <select>
        // options) so it can't be bypassed by posting a different ID.
        $doctor = $hospital->activeDoctors()->where('doctors.doctor_id', $data['assigned_doctor_id'])->first();
        if (!$doctor) {
            return back()->withErrors(['assigned_doctor_id' => 'Pick one of your hospital\'s assigned doctors.']);
        }

        $result = $this->operations->offer($operationRequest, $doctor, $data['scheduled_date'], $data['scheduled_time'], (int) $data['serial_number']);

        if (!$result['ok']) {
            return back()->withErrors(['status' => $result['message']]);
        }

        return redirect()->route('hospital.operations')->with('success', $result['message']);
    }

    /** Hospital: bumps/reorders the serial number for emergency triage (POST /hospital/operations/{operationRequest}/reprioritize). */
    public function reprioritize(Request $request, OperationRequest $operationRequest)
    {
        $this->authorizeHospitalOwns($operationRequest);

        $data = $request->validate([
            'serial_number' => ['required', 'integer', 'min:1'],
        ]);

        $result = $this->operations->reprioritize($operationRequest, (int) $data['serial_number']);

        return redirect()->route('hospital.operations')->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    /** Hospital: marks the operation performed, optionally attaching a report (POST /hospital/operations/{operationRequest}/complete). */
    public function markCompleted(Request $request, OperationRequest $operationRequest)
    {
        $this->authorizeHospitalOwns($operationRequest);

        $data = $request->validate([
            'report_file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
            'report_notes' => ['nullable', 'string', 'max:255'],
        ]);

        $filePath = $request->hasFile('report_file')
            ? $request->file('report_file')->store('facility-reports', 'local')
            : null;

        $result = $this->operations->markCompleted(
            $operationRequest,
            Auth::user()->hospital->hospital_name,
            Auth::id(),
            $filePath,
            $data['report_notes'] ?? null
        );

        return redirect()->route('hospital.operations')->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    // ------------------------------------------------------------------

    /** Only a Surgery-category offering may be requested through this flow — everything else still uses instant booking. */
    private function authorizeSurgeryOffering(HospitalFacility $offering): void
    {
        $offering->loadMissing('facilityType.category');

        if ($offering->facilityType->category->category_name !== 'Surgery') {
            abort(404);
        }
    }

    private function authorizePatientOwns(OperationRequest $operationRequest): void
    {
        if ($operationRequest->patient_id !== Auth::user()->patient->patient_id) {
            abort(403);
        }
    }

    private function authorizeHospitalOwns(OperationRequest $operationRequest): void
    {
        if ($operationRequest->hospital_id !== Auth::user()->hospital->hospital_id) {
            abort(403);
        }
    }
}
