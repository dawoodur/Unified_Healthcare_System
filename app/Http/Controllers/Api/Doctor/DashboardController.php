<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\DoctorCalendarNote;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * JSON twin of DashboardController::doctor() for the React dashboard — same
 * queries, same shape, just response()->json() instead of a Blade view.
 * Status labels/classes are computed here (mirroring the @php block in
 * doctor/dashboard.blade.php) so the React page has no status-classification
 * logic of its own to keep in sync.
 */
class DashboardController extends Controller
{
    public function index()
    {
        $doctor = Auth::user()->doctor->load('specialties');
        $doctorId = $doctor->doctor_id;

        $todayAppointments = Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', now()->toDateString())
            ->whereNotIn('status', ['cancelled'])
            ->with('patient.account')
            ->orderBy('appointment_time')
            ->get();

        $pendingAppointmentsCount = Appointment::where('doctor_id', $doctorId)
            ->whereIn('status', ['booked', 'confirmed'])
            ->count();

        $todayVideoCount = $todayAppointments->where('appointment_type', 'online')->count();

        $todayEarnings = Payment::whereHas('appointment', fn ($q) => $q->where('doctor_id', $doctorId)->whereDate('appointment_date', now()->toDateString()))
            ->where('status', 'completed')
            ->sum('amount');

        $visitCountsByPatient = Appointment::where('doctor_id', $doctorId)
            ->whereNotIn('status', ['cancelled'])
            ->selectRaw('patient_id, COUNT(*) as visits')
            ->groupBy('patient_id')
            ->pluck('visits');

        $patientOverview = [
            'new' => $visitCountsByPatient->filter(fn ($v) => $v === 1)->count(),
            'follow_up' => $visitCountsByPatient->filter(fn ($v) => $v === 2)->count(),
            'returning' => $visitCountsByPatient->filter(fn ($v) => $v >= 3)->count(),
        ];

        $calendarAppointmentDates = Appointment::where('doctor_id', $doctorId)
            ->whereNotIn('status', ['cancelled'])
            ->whereBetween('appointment_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->pluck('appointment_date')
            ->map(fn ($date) => $date->toDateString())
            ->unique()
            ->values();

        return response()->json([
            'doctor' => [
                'full_name' => $doctor->full_name,
                'verification_status' => $doctor->verification_status,
                'consultation_fee' => $doctor->consultation_fee,
                'specialties' => $doctor->specialties->pluck('specialty_name')->values(),
            ],
            'stats' => [
                'today_appointments' => $todayAppointments->count(),
                'pending_appointments' => $pendingAppointmentsCount,
                'today_video_count' => $todayVideoCount,
                'today_earnings' => $todayEarnings,
            ],
            'today_appointments' => $todayAppointments->map(fn (Appointment $a) => $this->serializeTodayRow($a))->values(),
            'calendar_appointment_dates' => $calendarAppointmentDates,
            'patient_overview' => $patientOverview,
        ]);
    }

    public function calendarDay(string $date)
    {
        $day = $this->validatedCalendarDate($date);
        $doctorId = Auth::user()->doctor->doctor_id;

        $appointments = Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $day->toDateString())
            ->whereNotIn('status', ['cancelled'])
            ->with(['patient.account', 'hospital', 'template', 'prescription', 'vital'])
            ->orderBy('appointment_time')
            ->get()
            ->map(fn (Appointment $appointment) => $this->serializeCalendarRow($appointment))
            ->values();

        $note = $this->calendarNoteFor($doctorId, $day->toDateString());

        return response()->json([
            'date' => $day->toDateString(),
            'appointments' => $appointments,
            'note' => $note,
        ]);
    }

    public function storeCalendarNote(Request $request, string $date)
    {
        $day = $this->validatedCalendarDate($date);
        $doctorId = Auth::user()->doctor->doctor_id;

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $note = trim((string) ($validated['note'] ?? ''));

        $savedNote = $this->saveCalendarNoteFor(
            $doctorId,
            $day->toDateString(),
            $note !== '' ? $note : null
        );

        return response()->json([
            'date' => $day->toDateString(),
            'note' => $savedNote,
        ]);
    }

    private function calendarNoteFor(int $doctorId, string $date): ?string
    {
        if (Schema::hasTable('doctor_calendar_notes')) {
            return DoctorCalendarNote::where('doctor_id', $doctorId)
                ->whereDate('note_date', $date)
                ->value('note');
        }

        $notes = $this->calendarNoteFile($doctorId);

        return isset($notes[$date]) && is_string($notes[$date])
            ? $notes[$date]
            : null;
    }

    private function saveCalendarNoteFor(int $doctorId, string $date, ?string $note): ?string
    {
        if (Schema::hasTable('doctor_calendar_notes')) {
            if ($note === null) {
                DoctorCalendarNote::where('doctor_id', $doctorId)
                    ->whereDate('note_date', $date)
                    ->delete();

                return null;
            }

            return DoctorCalendarNote::updateOrCreate(
                [
                    'doctor_id' => $doctorId,
                    'note_date' => $date,
                ],
                [
                    'note' => $note,
                ]
            )->note;
        }

        $notes = $this->calendarNoteFile($doctorId);

        if ($note === null) {
            unset($notes[$date]);
        } else {
            $notes[$date] = $note;
        }

        Storage::disk('local')->put(
            "doctor-calendar-notes/doctor-{$doctorId}.json",
            json_encode($notes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        return $note;
    }

    private function calendarNoteFile(int $doctorId): array
    {
        $path = "doctor-calendar-notes/doctor-{$doctorId}.json";

        if (!Storage::disk('local')->exists($path)) {
            return [];
        }

        $decoded = json_decode(Storage::disk('local')->get($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function validatedCalendarDate(string $date): Carbon
    {
        try {
            $day = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
        } catch (\Throwable $e) {
            abort(422, 'Invalid calendar date.');
        }

        if ($day->format('Y-m-d') !== $date) {
            abort(422, 'Invalid calendar date.');
        }

        return $day;
    }

    private function serializeCalendarRow(Appointment $appointment): array
    {
        if ($appointment->status === 'completed') {
            $statusKey = 'completed';
            $statusLabel = 'Completed';
        } elseif ($appointment->status === 'no_show') {
            $statusKey = 'no_show';
            $statusLabel = 'No show';
        } elseif ($appointment->isJoinableNow()) {
            $statusKey = 'in_progress';
            $statusLabel = 'In progress';
        } else {
            $statusKey = 'upcoming';
            $statusLabel = ucfirst($appointment->status);
        }

        return [
            'appointment_id' => $appointment->appointment_id,
            'patient_name' => $appointment->patient->full_name,
            'patient_photo_url' => $appointment->patient->account?->photoUrl(),
            'appointment_type' => $appointment->appointment_type,
            'time_range_label' => $appointment->timeRangeLabel(),
            'serial_number' => $appointment->serial_number,
            'status_key' => $statusKey,
            'status_label' => $statusLabel,
            'hospital_name' => $appointment->hospital?->hospital_name,
            'has_prescription' => $appointment->prescription !== null,
            'has_vitals' => $appointment->vital !== null,
        ];
    }

    private function serializeTodayRow(Appointment $a): array
    {
        if ($a->status === 'completed') {
            $statusKey = 'completed';
        } elseif ($a->status === 'no_show') {
            $statusKey = 'no_show';
        } elseif ($a->isJoinableNow()) {
            $statusKey = 'in_progress';
        } else {
            $statusKey = 'upcoming';
        }

        $statusClass = [
            'completed' => 'text-bg-success',
            'no_show' => 'text-bg-danger',
            'in_progress' => 'text-bg-warning',
            'upcoming' => 'text-bg-light border',
        ][$statusKey];

        return [
            'appointment_id' => $a->appointment_id,
            'time_label' => \Illuminate\Support\Carbon::parse($a->appointment_time)->format('g:i A'),
            'patient_name' => $a->patient->full_name,
            'patient_photo_url' => $a->patient->account?->photoUrl(),
            'appointment_type' => $a->appointment_type,
            'status_key' => $statusKey,
            'status_class' => $statusClass,
        ];
    }
}
