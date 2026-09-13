<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Notification;

/**
 * The one place an in-app notification ever gets created — every feature
 * that wants to alert someone (a new message, an operation offer, expired
 * medicine being pulled from stock, an appointment reminder, ...) calls
 * this instead of inserting into the notifications table directly, so the
 * shape stays consistent everywhere.
 */
class NotificationService
{
    /** Sends one notification to one account. */
    public function notify(Account $account, string $type, string $message, ?int $referenceId = null): Notification
    {
        return Notification::create([
            'account_id' => $account->account_id,
            'type' => $type,
            'message' => $message,
            'reference_id' => $referenceId,
        ]);
    }

    /** Sends the same notification to several accounts at once (e.g. every doctor at a hospital). */
    public function notifyMany(iterable $accounts, string $type, string $message, ?int $referenceId = null): void
    {
        foreach ($accounts as $account) {
            $this->notify($account, $type, $message, $referenceId);
        }
    }
}
