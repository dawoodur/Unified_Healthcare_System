<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\QueueStatus;
use App\Services\AppointmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * JSON twin of AppointmentController::doctorAppointments()/updateQueueStatus()/
 * markVisited() for the React Appointments page. Every field
 * doctor/partials/appointment-row.blade.php needs is pre-computed here via
 * the Appointment model's own methods (statusLabel(), statusBadgeClass(),
 * timeRangeLabel(), isJoinableNow()) so the React page has no duplicate
 * status logic — same convention as the patient conversion.
 */
class AppointmentController extends Controller
{
    private const PENDING_STATUSES = ['booked', 'confirmed'];

    public function __construct(private AppointmentService $appointments)
    {
    }

    public function index()
    {
        $this->appointments->expireNoShows();

        $doctorId = Auth::user()->doctor->doctor_id;
        $with = ['patient', 'hospital', 'template', 'prescription', 'payment', 'vital'];

        $pending = Appointment::where('doctor_id', $doctorId)
            ->whereIn('status', self::PENDING_STATUSES)
            ->with($with)->orderBy('appointment_date')->orderBy('appointment_time')->get();

        $completed = Appointment::where('doctor_id', $doctorId)
            ->whereNotIn('status', self::PENDING_STATUSES)
            ->with($with)->orderBy('appointment_date')->orderBy('appointment_time')->get();

        $todayCount = $pending->filter(fn (Appointment $a) => $a->appointment_date->isToday())->count();

        $todaysQueueStatus = QueueStatus::where('doctor_id', $doctorId)
            ->where('queue_date', today()->toDateString())
            ->first();

        return response()->json([
            'pending' => $pending->map(fn (Appointment $a) => $this->serializeRow($a))->values(),
            'completed' => $completed->map(fn (Appointment $a) => $this->serializeRow($a))->values(),
            'today_count' => $todayCount,
            'current_serial' => $todaysQueueStatus->current_serial ?? 0,
        ]);
    }

    public function updateQueueStatus(Request $request)
    {
        $data = $request->validate([
            'current_serial' => ['required', 'integer', 'min:0'],
        ]);

        QueueStatus::updateOrCreate(
            ['doctor_id' => Auth::user()->doctor->doctor_id, 'queue_date' => today()->toDateString()],
            ['current_serial' => $data['current_serial']]
        );

        return response()->json(['message' => 'Queue status updated.', 'current_serial' => $data['current_serial']]);
    }

    public function markVisited(Appointment $appointment)
    {
        if ($appointment->doctor_id !== Auth::user()->doctor->doctor_id) {
            abort(403);
        }

        if (in_array($appointment->status, ['cancelled', 'completed'], true)) {
            return response()->json(['message' => 'This appointment is already ' . $appointment->status . '.'], 422);
        }

        $appointment->update(['status' => 'completed']);

        return response()->json(['message' => 'Marked as visited.']);
    }

    private function serializeRow(Appointment $a): array
    {
        return [
            'appointment_id' => $a->appointment_id,
            'serial_number' => $a->serial_number,
            'patient_name' => $a->patient->full_name,
            'appointment_date' => $a->appointment_date->toDateString(),
            'time_range_label' => $a->timeRangeLabel(),
            'appointment_type' => $a->appointment_type,
            'hospital_name' => $a->hospital->hospital_name ?? null,
            'status' => $a->status,
            'status_label' => $a->statusLabel(),
            'status_badge_class' => $a->statusBadgeClass(),
            'is_no_show' => $a->status === 'no_show',
            'is_joinable_now' => $a->isJoinableNow(),
            'payment' => $a->payment ? [
                'amount' => $a->payment->amount,
                'status' => $a->payment->status,
            ] : null,
            'is_actionable' => !in_array($a->status, ['cancelled', 'completed', 'no_show'], true),
            'is_completed' => $a->status === 'completed',
            'consultation_url' => route('doctor.consultation', $a),
            'prescription_exists' => (bool) $a->prescription,
            'prescription_url' => route('doctor.appointments.prescription.create', $a),
            'vital_exists' => (bool) $a->vital,
            'vitals_url' => route('doctor.appointments.vitals.create', $a),
        ];
    }
}
