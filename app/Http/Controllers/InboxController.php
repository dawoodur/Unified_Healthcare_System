<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Conversation;
use App\Models\InboxMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * A general-purpose direct-messaging inbox between roles that legitimately
 * need to coordinate outside of a specific appointment — a patient asking
 * a pharmacy about an order, a hospital coordinating with an assigned
 * doctor, a delivery man confirming a pickup. This is deliberately
 * separate from the consultation-room chat (ConsultationController),
 * which only ever exists between one patient and one doctor on one
 * specific online appointment — this one is patient-independent of any
 * appointment and open to whichever role pairs ALLOWED_CHAT_ROLES lists.
 * Admin isn't part of this at all; admin's oversight happens through the
 * admin console, not a chat inbox.
 */
class InboxController extends Controller
{
    /**
     * Who's allowed to start a conversation with whom. Kept as one
     * explicit map (not "everyone can message everyone") because real
     * businesses don't work that way — a doctor coordinates with the
     * hospital they're assigned to, not directly with a pharmacy.
     * Symmetric by construction: if role A lists role B, role B lists
     * role A right back, since a conversation always has two willing
     * sides.
     */
    private const ALLOWED_CHAT_ROLES = [
        'patient' => ['hospital', 'pharmacy'],
        'doctor' => ['hospital'],
        'hospital' => ['patient', 'doctor', 'pharmacy'],
        'pharmacy' => ['hospital', 'patient', 'delivery'],
        'delivery' => ['pharmacy'],
    ];

    /** Every conversation the logged-in account is part of (GET /inbox). */
    public function index()
    {
        $accountId = Auth::id();

        $conversations = Conversation::where('participant_low_id', $accountId)
            ->orWhere('participant_high_id', $accountId)
            ->with(['participantLow', 'participantHigh'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Conversation $conversation) use ($accountId) {
                $conversation->other = $conversation->otherParticipant($accountId);
                $conversation->unread_count = InboxMessage::where('conversation_id', $conversation->conversation_id)
                    ->where('sender_account_id', '!=', $accountId)
                    ->where('is_read', false)
                    ->count();
                $conversation->last_message = InboxMessage::where('conversation_id', $conversation->conversation_id)
                    ->orderByDesc('message_id')
                    ->first();

                return $conversation;
            });

        return view('inbox.index', compact('conversations'));
    }

    /** Shows the "start a new conversation" picker — allowed roles, then a name search within one (GET /inbox/create). */
    public function create(Request $request)
    {
        $myRole = Auth::user()->role;
        $allowedRoles = self::ALLOWED_CHAT_ROLES[$myRole] ?? [];

        $selectedRole = $request->get('role');
        if (!in_array($selectedRole, $allowedRoles, true)) {
            $selectedRole = null;
        }

        $search = trim((string) $request->get('search'));
        $results = collect();

        if ($selectedRole) {
            $results = Account::where('role', $selectedRole)
                ->where('account_id', '!=', Auth::id())
                ->with(['patient', 'doctor', 'hospital', 'pharmacy', 'deliveryAgent'])
                ->get();

            if ($search !== '') {
                $needle = mb_strtolower($search);
                $results = $results->filter(fn (Account $a) => str_contains(mb_strtolower($a->displayName()), $needle));
            }
        }

        return view('inbox.create', compact('allowedRoles', 'selectedRole', 'search', 'results'));
    }

    /** Starts (or opens the existing) conversation with a chosen account (POST /inbox/start). */
    public function store(Request $request)
    {
        $data = $request->validate([
            'account_id' => ['required', 'integer', 'exists:accounts,account_id'],
        ]);

        $target = Account::findOrFail($data['account_id']);
        $this->authorizeRolePair(Auth::user()->role, $target->role);

        $conversation = Conversation::between(Auth::id(), $target->account_id);

        return redirect()->route('inbox.show', $conversation);
    }

    /** One conversation's message thread (GET /inbox/{conversation}). */
    public function show(Conversation $conversation)
    {
        $this->authorizeParticipant($conversation);

        $accountId = Auth::id();
        $other = $conversation->otherParticipant($accountId);

        // Opening the thread is what counts as "read" — mark anything the
        // other person sent that we haven't seen yet.
        InboxMessage::where('conversation_id', $conversation->conversation_id)
            ->where('sender_account_id', '!=', $accountId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $messages = $conversation->messages()
            ->orderBy('message_id')
            ->get(['message_id', 'sender_account_id', 'message_text', 'sent_at'])
            ->map(fn (InboxMessage $m) => [
                'message_id' => $m->message_id,
                'sender_account_id' => $m->sender_account_id,
                'message_text' => $m->message_text,
                'sent_at' => $m->sent_at->format('g:i A'),
            ]);

        return view('inbox.show', compact('conversation', 'other', 'messages'));
    }

    /** AJAX: send one message in this conversation (POST /inbox/{conversation}/send). */
    public function sendMessage(Request $request, Conversation $conversation)
    {
        $this->authorizeParticipant($conversation);

        $data = $request->validate([
            'message_text' => ['required', 'string', 'max:1000'],
        ]);

        $message = InboxMessage::create([
            'conversation_id' => $conversation->conversation_id,
            'sender_account_id' => Auth::id(),
            'message_text' => $data['message_text'],
        ]);

        $message->refresh(); // sent_at is a DB-level default, not set on the in-memory model create() returns
        $conversation->update(['last_message_at' => $message->sent_at]);

        return response()->json(['ok' => true, 'message' => [
            'message_id' => $message->message_id,
            'sender_account_id' => $message->sender_account_id,
            'message_text' => $message->message_text,
            'sent_at' => $message->sent_at->format('g:i A'),
        ]]);
    }

    /**
     * AJAX: poll for every message since ?since=123 — deliberately
     * includes the caller's own messages too (same reasoning as
     * ConsultationController::pollMessages()), so a second open tab or a
     * page refresh always converges on one true history instead of half
     * relying on an optimistic local append.
     */
    public function pollMessages(Request $request, Conversation $conversation)
    {
        $this->authorizeParticipant($conversation);

        $since = $request->integer('since', 0);
        $accountId = Auth::id();

        // Anything new from the other person that arrives via polling
        // counts as read the moment we fetch it, same as opening the thread.
        InboxMessage::where('conversation_id', $conversation->conversation_id)
            ->where('sender_account_id', '!=', $accountId)
            ->where('message_id', '>', $since)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $messages = InboxMessage::where('conversation_id', $conversation->conversation_id)
            ->where('message_id', '>', $since)
            ->orderBy('message_id')
            ->get(['message_id', 'sender_account_id', 'message_text', 'sent_at'])
            ->map(fn (InboxMessage $m) => [
                'message_id' => $m->message_id,
                'sender_account_id' => $m->sender_account_id,
                'message_text' => $m->message_text,
                'sent_at' => $m->sent_at->format('g:i A'),
            ]);

        return response()->json(['messages' => $messages]);
    }

    private function authorizeParticipant(Conversation $conversation): void
    {
        $accountId = Auth::id();

        if ($conversation->participant_low_id !== $accountId && $conversation->participant_high_id !== $accountId) {
            abort(403);
        }
    }

    private function authorizeRolePair(string $myRole, string $targetRole): void
    {
        $allowed = self::ALLOWED_CHAT_ROLES[$myRole] ?? [];

        if (!in_array($targetRole, $allowed, true)) {
            abort(403, 'You are not able to message that role.');
        }
    }
}
