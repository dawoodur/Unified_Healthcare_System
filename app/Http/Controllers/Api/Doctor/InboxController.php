<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Conversation;
use App\Models\InboxMessage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InboxController extends Controller
{
    public function index()
    {
        $accountId = (int) Auth::id();

        $conversations = $this->conversationQuery($accountId)
            ->with([
                'participantLow.hospital',
                'participantHigh.hospital',
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->get();

        $conversationIds = $conversations->pluck('conversation_id');

        $serialized = $conversations
            ->map(fn (Conversation $conversation) => $this->serializeConversation($conversation, $accountId))
            ->values();

        $doctor = Auth::user()?->doctor;

        $hospitalAccountIds = $doctor
            ? $doctor->activeHospitals()->pluck('hospitals.account_id')->map(fn ($id) => (int) $id)
            : collect();

        $contacts = Account::whereIn('account_id', $hospitalAccountIds)
            ->where('role', 'hospital')
            ->with('hospital')
            ->get()
            ->map(fn (Account $account) => $this->accountCard($account))
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $unreadMessages = $conversationIds->isEmpty()
            ? 0
            : InboxMessage::whereIn('conversation_id', $conversationIds)
                ->where('sender_account_id', '!=', $accountId)
                ->where('is_read', false)
                ->count();

        $messagesToday = $conversationIds->isEmpty()
            ? 0
            : InboxMessage::whereIn('conversation_id', $conversationIds)
                ->whereDate('sent_at', now()->toDateString())
                ->count();

        return response()->json([
            'current_account_id' => $accountId,
            'stats' => [
                'total_conversations' => $serialized->count(),
                'unread_messages' => $unreadMessages,
                'assigned_contacts' => $contacts->count(),
                'messages_today' => $messagesToday,
            ],
            'conversations' => $serialized,
            'contacts' => $contacts,
        ]);
    }

    public function show(Conversation $conversation)
    {
        $accountId = (int) Auth::id();
        $this->authorizeParticipant($conversation, $accountId);

        $conversation->load([
            'participantLow.hospital',
            'participantHigh.hospital',
        ]);

        InboxMessage::where('conversation_id', $conversation->conversation_id)
            ->where('sender_account_id', '!=', $accountId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $other = $conversation->otherParticipant($accountId);

        $messages = $conversation->messages()
            ->orderBy('message_id')
            ->get(['message_id', 'sender_account_id', 'message_text', 'sent_at'])
            ->map(fn (InboxMessage $message) => [
                'message_id' => (int) $message->message_id,
                'sender_account_id' => (int) $message->sender_account_id,
                'message_text' => $message->message_text,
                'is_own' => (int) $message->sender_account_id === $accountId,
                'sent_at' => $message->sent_at?->toIso8601String(),
                'time_label' => $message->sent_at?->format('g:i A'),
            ])
            ->values();

        return response()->json([
            'conversation_id' => (int) $conversation->conversation_id,
            'other' => $this->accountCard($other),
            'messages' => $messages,
        ]);
    }

    public function start(Request $request)
    {
        $data = $request->validate([
            'account_id' => ['required', 'integer', 'exists:accounts,account_id'],
        ]);

        $accountId = (int) Auth::id();
        $target = Account::with('hospital')->findOrFail((int) $data['account_id']);

        if ($target->role !== 'hospital' || !$this->allowedHospitalAccountIds()->contains((int) $target->account_id)) {
            abort(403, 'You can only start inbox conversations with hospitals currently assigned to you.');
        }

        $conversation = Conversation::between($accountId, (int) $target->account_id);
        $conversation->load([
            'participantLow.hospital',
            'participantHigh.hospital',
        ]);

        return response()->json([
            'conversation_id' => (int) $conversation->conversation_id,
            'conversation' => $this->serializeConversation($conversation, $accountId),
        ]);
    }

    public function send(Request $request, Conversation $conversation)
    {
        $accountId = (int) Auth::id();
        $this->authorizeParticipant($conversation, $accountId);

        $data = $request->validate([
            'message_text' => ['required', 'string', 'max:1000'],
        ]);

        $message = InboxMessage::create([
            'conversation_id' => $conversation->conversation_id,
            'sender_account_id' => $accountId,
            'message_text' => trim($data['message_text']),
        ]);

        $message->refresh();
        $conversation->update(['last_message_at' => $message->sent_at]);

        return response()->json([
            'ok' => true,
            'message' => [
                'message_id' => (int) $message->message_id,
                'sender_account_id' => $accountId,
                'message_text' => $message->message_text,
                'is_own' => true,
                'sent_at' => $message->sent_at?->toIso8601String(),
                'time_label' => $message->sent_at?->format('g:i A'),
            ],
        ]);
    }

    private function conversationQuery(int $accountId): Builder
    {
        return Conversation::query()
            ->where(function (Builder $query) use ($accountId) {
                $query->where('participant_low_id', $accountId)
                    ->orWhere('participant_high_id', $accountId);
            });
    }

    private function serializeConversation(Conversation $conversation, int $accountId): array
    {
        $other = $conversation->otherParticipant($accountId);

        $lastMessage = InboxMessage::where('conversation_id', $conversation->conversation_id)
            ->orderByDesc('message_id')
            ->first();

        $unreadCount = InboxMessage::where('conversation_id', $conversation->conversation_id)
            ->where('sender_account_id', '!=', $accountId)
            ->where('is_read', false)
            ->count();

        return [
            'conversation_id' => (int) $conversation->conversation_id,
            'other' => $this->accountCard($other),
            'unread_count' => $unreadCount,
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            'last_message' => $lastMessage ? [
                'message_text' => $lastMessage->message_text,
                'sender_account_id' => (int) $lastMessage->sender_account_id,
                'is_own' => (int) $lastMessage->sender_account_id === $accountId,
                'sent_at' => $lastMessage->sent_at?->toIso8601String(),
                'time_label' => $lastMessage->sent_at?->format('g:i A'),
            ] : null,
        ];
    }

    private function accountCard(Account $account): array
    {
        $hospital = $account->role === 'hospital' ? $account->hospital : null;

        return [
            'account_id' => (int) $account->account_id,
            'name' => $account->displayName(),
            'uid' => $account->uidTag(),
            'role' => $account->role,
            'role_label' => ucfirst($account->role),
            'photo_url' => $account->photoUrl(),
            'subtitle' => $hospital?->fullAddress() ?: 'Hospital contact',
        ];
    }

    private function allowedHospitalAccountIds()
    {
        $doctor = Auth::user()?->doctor;

        if (!$doctor) {
            return collect();
        }

        return $doctor->activeHospitals()
            ->pluck('hospitals.account_id')
            ->map(fn ($id) => (int) $id);
    }

    private function authorizeParticipant(Conversation $conversation, int $accountId): void
    {
        if (
            (int) $conversation->participant_low_id !== $accountId
            && (int) $conversation->participant_high_id !== $accountId
        ) {
            abort(403);
        }
    }
}
