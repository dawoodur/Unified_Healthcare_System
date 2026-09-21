<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\ConsultationSession;
use App\Services\ConsultationChatService;
use Illuminate\Support\Facades\Auth;

class ConsultationController extends Controller
{
    public function __construct(private ConsultationChatService $chat)
    {
    }

    public function show(Appointment $appointment)
    {
        $doctor = Auth::user()->doctor;

        if ((int) $appointment->doctor_id !== (int) $doctor->doctor_id) {
            abort(403);
        }

        if ($appointment->appointment_type !== 'online') {
            abort(404);
        }

        $session = ConsultationSession::firstOrCreate(
            ['appointment_id' => $appointment->appointment_id],
            ['status' => 'waiting']
        );

        $appointment->load([
            'patient.account',
            'prescription',
        ]);

        $patient = $appointment->patient;

        return response()->json([
            'my_account_id' => (int) Auth::id(),
            'appointment' => [
                'appointment_id' => (int) $appointment->appointment_id,
                'serial_number' => (int) $appointment->serial_number,
                'appointment_type' => $appointment->appointment_type,
                'appointment_type_label' => 'Online consultation',
                'date_label' => $appointment->appointment_date?->format('D, M j, Y'),
                'time_range_label' => $appointment->timeRangeLabel(),
                'status' => $appointment->status,
                'status_label' => $appointment->statusLabel(),
                'is_completed' => $appointment->status === 'completed',
                'prescription_exists' => (bool) $appointment->prescription,
                'prescription_url' => route('doctor.appointments.prescription.create', $appointment),
                'patient' => [
                    'patient_id' => (int) $patient->patient_id,
                    'full_name' => $patient->full_name,
                    'age' => $patient->age,
                    'blood_group' => $patient->blood_group,
                    'gender' => $patient->gender,
                    'gender_label' => ucfirst($patient->gender),
                    'photo_url' => $patient->account?->photoUrl(),
                ],
            ],
            'session' => [
                'session_id' => (int) $session->session_id,
                'status' => $session->status,
                'status_label' => match ($session->status) {
                    'active' => 'Live',
                    'ended' => 'Ended',
                    default => 'Waiting',
                },
                'started_at' => $session->started_at?->toIso8601String(),
                'ended_at' => $session->ended_at?->toIso8601String(),
            ],
            'chat_history' => $this->chat->messages($session),
            'chat_closed' => $session->status === 'ended',
            'max_photo_bytes' => ConsultationChatService::MAX_PHOTO_BYTES,
            'endpoints' => [
                'signal' => route('consultation.signal', $appointment),
                'poll' => route('consultation.poll', $appointment),
                'end' => route('consultation.end', $appointment),
                'chat_send' => route('consultation.chat.send', $appointment),
                'chat_poll' => route('consultation.chat.poll', $appointment),
                'chat_photo' => route('consultation.chat.photo', $appointment),
                'chat_photo_show' => url("/consultation/{$appointment->appointment_id}/chat/photo/__PHOTO__"),
            ],
        ]);
    }
}
