<?php

namespace App\Http\Controllers;

use App\Models\FacilityBooking;
use App\Models\FacilityCategory;
use App\Models\FacilityType;
use App\Models\HospitalFacility;
use App\Models\MedicalRecord;
use App\Services\RewardPointService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Lets a hospital list which facilities it offers (blood tests, surgery,
 * ICU, imaging, etc.) and set its own price for each one. This table of
 * prices is what powers the patient-facing comparison page — see
 * FacilityComparisonController.
 */
class HospitalFacilityController extends Controller
{
    public function __construct(private RewardPointService $rewardPoints)
    {
    }

    /** Shows this hospital's current priced facilities (GET /hospital/facilities). */
    public function index()
    {
        $hospital = Auth::user()->hospital;

        $offerings = $hospital->facilities()->with('facilityType.category')->get()->keyBy('facility_type_id');

        // Grouped by category so the list shows facilities organized the
        // same way patients will browse them.
        $categories = FacilityCategory::with('facilityTypes')->orderBy('category_name')->get();

        return view('hospital.facilities', compact('offerings', 'categories'));
    }

    /** Shows the "set price & quota" form for one facility type (GET /hospital/facilities/{facilityType}/edit). */
    public function create(FacilityType $facilityType)
    {
        $offering = Auth::user()->hospital
            ->facilities()
            ->where('facility_type_id', $facilityType->facility_type_id)
            ->first();

        return view('hospital.facilities-create', compact('facilityType', 'offering'));
    }

    /** Shows the "add a new facility type to the catalog" form (GET /hospital/facilities/types/create). */
    public function createType()
    {
        $categories = FacilityCategory::orderBy('category_name')->get();

        return view('hospital.facility-types-create', compact('categories'));
    }

    /**
     * Adds a brand-new facility type to the shared catalog (POST
     * /hospital/facilities/types) — any hospital can do this, for whenever
     * the facility they want to offer isn't in the list yet. Same idea as
     * PharmacyInventoryController::storeMedicine(): no admin approval step,
     * and an existing case-insensitive match on category+name is reused
     * instead of creating a near-duplicate row.
     */
    public function storeType(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:facility_categories,category_id'],
            'name' => ['required', 'string', 'max:150'],
            'unit_label' => ['nullable', 'string', 'max:50'],
            'is_occupancy' => ['nullable', 'boolean'],
        ]);

        $existing = FacilityType::where('category_id', $data['category_id'])
            ->whereRaw('LOWER(name) = ?', [strtolower($data['name'])])
            ->first();

        if ($existing) {
            return redirect()->route('hospital.facilities.create', $existing)->with('success', "\"{$existing->name}\" is already in the catalog — set your price for it below.");
        }

        $facilityType = FacilityType::create([
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'unit_label' => $data['unit_label'] ?? null,
            'is_occupancy' => $request->boolean('is_occupancy'),
        ]);

        return redirect()->route('hospital.facilities.create', $facilityType)->with('success', 'Facility type added to the catalog — set your price for it below.');
    }

    /**
     * Sets (or updates) this hospital's price + daily booking quota for one
     * facility type (POST /hospital/facilities). One form handles both
     * "add new" and "change an existing listing" — updateOrCreate figures
     * out which.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'facility_type_id' => ['required', 'integer', 'exists:facility_types,facility_type_id'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'daily_capacity' => ['required', 'integer', 'min:1', 'max:9999'],
        ]);

        $hospital = Auth::user()->hospital;

        HospitalFacility::updateOrCreate(
            ['hospital_id' => $hospital->hospital_id, 'facility_type_id' => $data['facility_type_id']],
            ['price' => $data['price'], 'daily_capacity' => $data['daily_capacity']]
        );

        return redirect()->route('hospital.facilities')->with('success', 'Listing saved.');
    }

    /** Removes this hospital's price listing for one facility type (POST /hospital/facilities/{offering}/remove). */
    public function destroy(HospitalFacility $offering)
    {
        if ($offering->hospital_id !== Auth::user()->hospital->hospital_id) {
            abort(403);
        }

        $offering->delete();

        return back()->with('success', 'Removed.');
    }

    /** Shows every patient booking made against this hospital's facilities (GET /hospital/facility-bookings). */
    public function bookings()
    {
        $hospitalId = Auth::user()->hospital->hospital_id;
        $with = ['patient', 'facilityType'];

        $pending = FacilityBooking::where('hospital_id', $hospitalId)
            ->where('status', 'booked')
            ->with($with)->orderBy('booking_date')->orderBy('serial_number')->get();

        $completed = FacilityBooking::where('hospital_id', $hospitalId)
            ->whereIn('status', ['completed', 'cancelled'])
            ->with($with)->orderBy('booking_date')->orderBy('serial_number')->get();

        return view('hospital.facility-bookings', compact('pending', 'completed'));
    }

    /**
     * Marks a booking as completed (POST /hospital/facility-bookings/{booking}/complete).
     * For a bed-type facility (ICU, Cabin, etc.) this IS the discharge —
     * it's what frees the bed up for the next patient, since occupancy
     * bookings stay "booked" (and counted against capacity) until this is
     * clicked; see FacilityBookingService::currentAvailability(). For a
     * same-day facility (an X-Ray, an MRI, a blood test) it's just
     * record-keeping — but the hospital can optionally attach the actual
     * report/result here too, which automatically becomes part of the
     * patient's medical records (same idea as a prescription auto-adding
     * itself — see PrescriptionController::store()). Occupancy discharges
     * don't get this — a bed stay doesn't have a single "report" file the
     * way a test or procedure does.
     */
    public function markCompleted(Request $request, FacilityBooking $booking)
    {
        if ($booking->hospital_id !== Auth::user()->hospital->hospital_id) {
            abort(403);
        }

        if ($booking->status !== 'booked') {
            return back()->withErrors(['status' => 'This booking is already ' . $booking->status . '.']);
        }

        $data = $request->validate([
            'report_file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
            'report_notes' => ['nullable', 'string', 'max:255'],
        ]);

        $booking->update(['status' => 'completed']);
        $booking->loadMissing('facilityType.category', 'patient');

        // Diagnostic Test is the platform's lab-test category. Reward the
        // completed purchase once; non-lab facilities do not earn points.
        if (strcasecmp((string) $booking->facilityType->category?->category_name, 'Diagnostic Test') === 0) {
            $this->rewardPoints->award($booking->patient, 'lab_test_purchase', $booking->facility_booking_id);
        }

        if (!$booking->facilityType->is_occupancy && ($request->hasFile('report_file') || !empty($data['report_notes']))) {
            $filePath = $request->hasFile('report_file')
                ? $request->file('report_file')->store('facility-reports', 'local')
                : null;

            MedicalRecord::create([
                'patient_id' => $booking->patient_id,
                'record_type' => 'facility_report',
                'reference_id' => $booking->facility_booking_id,
                'file_path' => $filePath,
                'description' => trim($booking->facilityType->name . ' — ' . Auth::user()->hospital->hospital_name . ($data['report_notes'] ? ': ' . $data['report_notes'] : '')),
                'created_by_account_id' => Auth::id(),
            ]);
        }

        return back()->with('success', 'Marked as completed.');
    }
}
