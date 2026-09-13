<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

/**
 * The bell-icon notification list — shared by every role, since
 * notifications are per-account, not per-role (see layouts/app.blade.php
 * for the bell icon + unread badge shown on every page).
 */
class NotificationController extends Controller
{
    /** Shows every notification for the logged-in account, newest first, and marks them all read (GET /notifications). */
    public function index()
    {
        $notifications = Auth::user()->notifications()
            ->orderByDesc('notification_id')
            ->get();

        Auth::user()->notifications()->where('is_read', false)->update(['is_read' => true]);

        return view('notifications.index', compact('notifications'));
    }
}
