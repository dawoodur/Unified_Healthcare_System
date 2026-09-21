<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    public function index()
    {
        $account = Auth::user();

        $notifications = $account->notifications()
            ->orderByDesc('notification_id')
            ->get();

        $unreadBeforeOpen = $notifications->where('is_read', false)->count();
        $today = $notifications->filter(fn ($notification) => $notification->created_at?->isToday())->count();
        $lastSevenDays = $notifications->filter(
            fn ($notification) => $notification->created_at?->gte(now()->subDays(6)->startOfDay())
        )->count();

        $serialized = $notifications
            ->map(function ($notification) {
                return [
                    'notification_id' => (int) $notification->notification_id,
                    'type' => $notification->type,
                    'type_label' => Str::headline($notification->type ?: 'Notification'),
                    'message' => $notification->message,
                    'reference_id' => $notification->reference_id,
                    'was_unread' => ! (bool) $notification->is_read,
                    'is_today' => (bool) $notification->created_at?->isToday(),
                    'created_at' => $notification->created_at?->toIso8601String(),
                    'when_label' => $notification->created_at?->format('D, M j Y g:i A'),
                ];
            })
            ->values();

        $account->notifications()
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'stats' => [
                'unread_before_open' => $unreadBeforeOpen,
                'today' => $today,
                'last_7_days' => $lastSevenDays,
                'total' => $notifications->count(),
            ],
            'notifications' => $serialized,
        ]);
    }
}
