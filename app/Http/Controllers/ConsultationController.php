<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\ChatMessage;
use App\Models\ConsultationSession;
use App\Models\MedicineMaster;
use App\Models\WebrtcSignal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The video-call "room" for one online appointment. Real audio/video is
 * peer-to-peer (WebRTC) directly between the patient's and doctor's
 * browsers — this controller only relays the small handshake messages
 * (offer/answer/ICE candidates) needed to connect them, since a plain
 * PHP/Apache server has no persistent websocket connection to push those
 * instantly. Both browsers poll pollSignals() every ~1.5 seconds instead.
 */
class ConsultationController extends Controller
{
    /** Opens the video call room for one appointment (GET /consultation/{appointment}). */
    public function show(Appointment $appointment)
    {
        $this->authorizeParticipant($appointment);

        if ($appointment->appointment_type !== 'online') {
            abort(404); // onsite appointments have no video room
        }

        $session = ConsultationSession::firstOrCreate(
            ['appointment_id' => $appointment->appointment_id],
            ['status' => 'waiting']
        );

        // Loaded once here so re-opening the room (or just refreshing the
        // page) shows the earlier conversation immediately, instead of
        // waiting for the JS poll loop to slowly re-discover it. Shaped
        // into plain arrays here (not left as a Collection of models) so
        // the view's @json() call is a simple, single-expression dump —
        // no closures/transformations embedded in the Blade directive.
        $chatHistory = $session->messages()
            ->orderBy('message_id')
            ->get(['message_id', 'sender_account_id', 'message_text', 'sent_at'])
            ->map(fn ($m) => [
                'message_id' => $m->message_id,
                'sender_account_id' => $m->sender_account_id,
                'message_text' => $m->message_text,
                'sent_at' => $m->sent_at->format('g:i A'),
            ]);

        $appointment->load(['doctor', 'patient', 'prescription']);

        // The doctor can write the e-prescription right here in the room
        // once the visit is marked completed — same session-draft data
        // PrescriptionController::create() builds for the standalone page,
        // since consultation/room.blade.php embeds the exact same partial.
        // Only computed for the doctor, and only once there's actually a
        // prescription left to write, so a patient loading this page (or a
        // doctor who's already issued one) doesn't pay for an unused query.
        $isDoctor = Auth::user()->role === 'doctor';
        $canWritePrescription = $isDoctor && $appointment->status === 'completed' && !$appointment->prescription;

        $draft = [];
        $medicines = collect();
        $draftMedicines = collect();
        $allergies = collect();

        if ($canWritePrescription) {
            $draft = session("prescription_draft.{$appointment->appointment_id}", []);
            $medicines = MedicineMaster::orderBy('generic_name')->get();
            $draftMedicines = $medicines->keyBy('medicine_master_id');
            $allergies = $appointment->patient->allergies;
        }

        return view('consultation.room', [
            'appointment' => $appointment,
            'session' => $session,
            'chatHistory' => $chatHistory,
            'canWritePrescription' => $canWritePrescription,
            'draft' => $draft,
            'medicines' => $medicines,
            'draftMedicines' => $draftMedicines,
            'allergies' => $allergies,
        ]);
    }

    /** AJAX: the browser posts one handshake message (offer/answer/ICE candidate/hangup). */
    public function postSignal(Request $request, Appointment $appointment)
    {
        $this->authorizeParticipant($appointment);

        $data = $request->validate([
            'signal_type' => ['required', 'in:offer,answer,ice_candidate,hangup'],
            'payload' => ['required', 'string'],
        ]);

        $session = ConsultationSession::where('appointment_id', $appointment->appointment_id)->firstOrFail();

        if ($session->status === 'waiting') {
            $session->update(['status' => 'active', 'started_at' => $session->started_at ?? now()]);
        }

        WebrtcSignal::create([
            'session_id' => $session->session_id,
            'sender_account_id' => Auth::id(),
            'signal_type' => $data['signal_type'],
            'payload' => $data['payload'],
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * AJAX: the browser polls for new messages from the OTHER participant
     * since the last signal_id it already saw (?since=123).
     */
    public function pollSignals(Request $request, Appointment $appointment)
    {
        $this->authorizeParticipant($appointment);

        $session = ConsultationSession::where('appointment_id', $appointment->appointment_id)->first();
        if (!$session) {
            return response()->json(['signals' => []]);
        }

        $since = $request->integer('since', 0);

        $signals = WebrtcSignal::where('session_id', $session->session_id)
            ->where('signal_id', '>', $since)
            ->where('sender_account_id', '!=', Auth::id()) // only what the OTHER person sent
            ->orderBy('signal_id')
            ->get(['signal_id', 'signal_type', 'payload']);

        return response()->json(['signals' => $signals]);
    }

    /** AJAX: send one chat message in this consultation's room. */
    public function sendMessage(Request $request, Appointment $appointment)
    {
        $this->authorizeParticipant($appointment);

        $data = $request->validate([
            'message_text' => ['required', 'string', 'max:1000'],
        ]);

        $session = ConsultationSession::where('appointment_id', $appointment->appointment_id)->firstOrFail();

        $message = ChatMessage::create([
            'session_id' => $session->session_id,
            'sender_account_id' => Auth::id(),
            'message_text' => $data['message_text'],
        ]);

        // sent_at is a database-level default (useCurrent()), not
        // something Eloquent fills in on the in-memory model create()
        // just returned — refresh() re-fetches this row so sent_at is
        // actually populated before we try to format() it below.
        $message->refresh();

        return response()->json(['ok' => true, 'message' => [
            'message_id' => $message->message_id,
            'sender_account_id' => $message->sender_account_id,
            'message_text' => $message->message_text,
            'sent_at' => $message->sent_at->format('g:i A'),
        ]]);
    }

    /**
     * AJAX: the browser polls for every chat message (from either
     * participant) since the last message_id it already has (?since=123)
     * — unlike pollSignals(), this deliberately includes the caller's own
     * messages too, so both sides' chat logs stay in sync from one single
     * source of truth instead of half coming from an optimistic local
     * append and half from polling.
     */
    public function pollMessages(Request $request, Appointment $appointment)
    {
        $this->authorizeParticipant($appointment);

        $session = ConsultationSession::where('appointment_id', $appointment->appointment_id)->first();
        if (!$session) {
            return response()->json(['messages' => []]);
        }

        $since = $request->integer('since', 0);

        $messages = ChatMessage::where('session_id', $session->session_id)
            ->where('message_id', '>', $since)
            ->orderBy('message_id')
            ->get(['message_id', 'sender_account_id', 'message_text', 'sent_at'])
            ->map(fn ($m) => [
                'message_id' => $m->message_id,
                'sender_account_id' => $m->sender_account_id,
                'message_text' => $m->message_text,
                'sent_at' => $m->sent_at->format('g:i A'),
            ]);

        return response()->json(['messages' => $messages]);
    }

    /** Only the specific patient or doctor on this exact appointment may open its room. */
    private function authorizeParticipant(Appointment $appointment): void
    {
        $account = Auth::user();

        $isThisPatient = $account->role === 'patient' && $appointment->patient_id === $account->patient->patient_id;
        $isThisDoctor = $account->role === 'doctor' && $appointment->doctor_id === $account->doctor->doctor_id;

        if (!$isThisPatient && !$isThisDoctor) {
            abort(403);
        }
    }
}
