<?php

namespace App\Mail;

use App\Models\PrescriptionItem;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** "Your course of X is about to run out" reminder — see SendRefillRemindersCommand. */
class RefillReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PrescriptionItem $item)
    {
    }

    public function build(): self
    {
        return $this->subject('Reminder: your ' . $this->item->medicine->generic_name . ' is almost finished')
            ->view('emails.refill-reminder');
    }
}
