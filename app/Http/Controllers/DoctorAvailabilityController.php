<?php

namespace App\Http\Controllers;

use App\Models\DoctorAvailabilityTemplate;
use App\Models\DoctorLeaveDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Lets a logged-in doctor manage their own weekly recurring visiting
 * windows (see DoctorAvailabilityTemplate.php for what a "template"
 * actually is). This is what powers the windows a patient can later book
 * into — see AppointmentService::upcomingWindows().
 *
 * Onsite blocks must be tied to one of the doctor's hospitals — a doctor
 * can only pick from hospitals that have actually assigned them (see
 * HospitalDoctorController — assignment is hospital-initiated, not
 * something a doctor can self-select).
 */
class DoctorAvailabilityController extends Controller
{
    /** Shows the doctor's current weekly schedule (GET /doctor/availability). */
    public function index()
    {
        $doctor = Auth::user()->doctor;

        $templates = $doctor->availabilityTemplates()
            ->with('hospital')
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return view('doctor.availability', compact('templates'));
    }

    /** Shows the "add a visiting window" form (GET /doctor/availability/create). */
    public function create()
    {
        $hospitals = Auth::user()->doctor->activeHospitals;

        return view('doctor.availability-create', compact('hospitals'));
    }

    /** Adds one new weekly availability block. */
    public function store(Request $request)
    {
        $doctor = Auth::user()->doctor;
        $assignedHospitalIds = $doctor->activeHospitals->pluck('hospital_id');

        $data = $request->validate([
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'mode' => ['required', 'in:online,onsite'],
            'max_patients' => ['required', 'integer', 'min:1', 'max:200'],
            // Required only when mode is onsite, and only one of the
            // doctor's own actively-assigned hospitals is acceptable.
            // 'nullable' first so an empty submission (mode=online, no
            // hospital picked) skips the "in" check instead of failing it.
            'hospital_id' => [
                'nullable',
                Rule::requiredIf($request->input('mode') === 'onsite'),
                Rule::in($assignedHospitalIds),
            ],
        ], [
            'hospital_id.required_if' => 'Pick which of your hospitals this onsite window is at.',
            'hospital_id.in' => 'You can only pick a hospital that has assigned you as a doctor.',
        ]);

        $doctor->availabilityTemplates()->create([
            'hospital_id' => $data['mode'] === 'onsite' ? $data['hospital_id'] : null,
            'day_of_week' => $data['day_of_week'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'mode' => $data['mode'],
            'max_patients' => $data['max_patients'],
            'is_active' => true,
        ]);

        return redirect()->route('doctor.availability')->with('success', 'Availability added.');
    }

    /** Turns off one availability block (kept in the database, just no longer offered for booking). */
    public function destroy(DoctorAvailabilityTemplate $template)
    {
        // Make sure a doctor can only ever touch their OWN templates, not
        // guess another doctor's template_id in the URL and delete theirs.
        if ($template->doctor_id !== Auth::user()->doctor->doctor_id) {
            abort(403);
        }

        $template->update(['is_active' => false]);

        return back()->with('success', 'Availability removed.');
    }

    /** Shows the doctor's blocked-off dates (GET /doctor/leave). */
    public function leaveIndex()
    {
        $leaveDates = Auth::user()->doctor
            ->leaveDates()
            ->where('leave_date', '>=', now()->toDateString())
            ->orderBy('leave_date')
            ->get();

        return view('doctor.leave', compact('leaveDates'));
    }

    /** Blocks off one date (POST /doctor/leave). */
    public function storeLeave(Request $request)
    {
        $data = $request->validate([
            'leave_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $doctor = Auth::user()->doctor;

        if ($doctor->leaveDates()->where('leave_date', $data['leave_date'])->exists()) {
            return back()->withErrors(['leave_date' => 'That date is already blocked off.']);
        }

        $doctor->leaveDates()->create($data);

        return redirect()->route('doctor.leave')->with('success', 'Date blocked off — no windows will be offered for booking on it.');
    }

    /** Un-blocks a date (POST /doctor/leave/{leaveDate}/remove). */
    public function destroyLeave(DoctorLeaveDate $leaveDate)
    {
        if ($leaveDate->doctor_id !== Auth::user()->doctor->doctor_id) {
            abort(403);
        }

        $leaveDate->delete();

        return back()->with('success', 'Date un-blocked.');
    }
}
