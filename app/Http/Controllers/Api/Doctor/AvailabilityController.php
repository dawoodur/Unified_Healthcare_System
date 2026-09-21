<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use App\Models\DoctorAvailabilityTemplate;
use App\Models\DoctorLeaveDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * JSON twin of DoctorAvailabilityController's index()/create()/store()/destroy()
 * for the React Availability page. index() also returns activeHospitals +
 * DAY_NAMES so one GET covers both the schedule list and the "add window"
 * form's dropdown data — this phase folds "add window" into the same page.
 */
class AvailabilityController extends Controller
{
    public function index()
    {
        $doctor = Auth::user()->doctor;

        $templates = $doctor->availabilityTemplates()
            ->with('hospital')
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->map(fn (DoctorAvailabilityTemplate $t) => [
                'template_id' => $t->template_id,
                'day_of_week' => $t->day_of_week,
                'day_name' => DoctorAvailabilityTemplate::DAY_NAMES[$t->day_of_week],
                'start_time' => \Illuminate\Support\Carbon::parse($t->start_time)->format('g:i A'),
                'end_time' => \Illuminate\Support\Carbon::parse($t->end_time)->format('g:i A'),
                'mode' => $t->mode,
                'hospital_name' => $t->hospital->hospital_name ?? null,
                'max_patients' => $t->max_patients,
            ])
            ->values();

        $hospitals = $doctor->activeHospitals->map(fn ($h) => [
            'hospital_id' => $h->hospital_id,
            'hospital_name' => $h->hospital_name,
        ])->values();

        return response()->json([
            'templates' => $templates,
            'hospitals' => $hospitals,
            'day_names' => array_values(DoctorAvailabilityTemplate::DAY_NAMES),
        ]);
    }

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

        return response()->json(['message' => 'Availability added.']);
    }

    public function destroy(DoctorAvailabilityTemplate $template)
    {
        if ($template->doctor_id !== Auth::user()->doctor->doctor_id) {
            abort(403);
        }

        $template->update(['is_active' => false]);

        return response()->json(['message' => 'Availability removed.']);
    }

    public function leaveIndex()
    {
        $doctor = Auth::user()->doctor;
        $today = now()->startOfDay();

        $leaveDates = $doctor->leaveDates()
            ->whereDate('leave_date', '>=', $today->toDateString())
            ->orderBy('leave_date')
            ->get()
            ->map(function (DoctorLeaveDate $leave) {
                $date = $leave->leave_date;

                return [
                    'leave_id' => (int) $leave->leave_id,
                    'leave_date' => $date->toDateString(),
                    'weekday' => $date->format('l'),
                    'date_label' => $date->format('d M Y'),
                    'month_short' => strtoupper($date->format('M')),
                    'day_number' => $date->format('d'),
                    'reason' => $leave->reason,
                ];
            })
            ->values();

        $thisMonth = $leaveDates->filter(function (array $leave) use ($today) {
            return str_starts_with($leave['leave_date'], $today->format('Y-m'));
        })->count();

        $reasonsNoted = $leaveDates->filter(fn (array $leave) => filled($leave['reason']))->count();
        $next = $leaveDates->first();

        return response()->json([
            'min_date' => $today->toDateString(),
            'leave_dates' => $leaveDates,
            'stats' => [
                'total' => $leaveDates->count(),
                'this_month' => $thisMonth,
                'reasons_noted' => $reasonsNoted,
                'next_leave_label' => $next ? substr($next['date_label'], 0, 6) : null,
                'next_leave_hint' => $next ? $next['weekday'] . ' · ' . $next['date_label'] : null,
            ],
        ]);
    }

    public function storeLeave(Request $request)
    {
        $data = $request->validate([
            'leave_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $doctor = Auth::user()->doctor;

        if ($doctor->leaveDates()->whereDate('leave_date', $data['leave_date'])->exists()) {
            throw ValidationException::withMessages([
                'leave_date' => ['That date is already blocked off.'],
            ]);
        }

        $leave = $doctor->leaveDates()->create([
            'leave_date' => $data['leave_date'],
            'reason' => filled($data['reason'] ?? null) ? trim($data['reason']) : null,
        ]);

        return response()->json([
            'message' => 'Date blocked off.',
            'leave_id' => (int) $leave->leave_id,
        ], 201);
    }

    public function destroyLeave(DoctorLeaveDate $leaveDate)
    {
        if ($leaveDate->doctor_id !== Auth::user()->doctor->doctor_id) {
            abort(403);
        }

        $leaveDate->delete();

        return response()->json(['message' => 'Date unblocked.']);
    }

}
