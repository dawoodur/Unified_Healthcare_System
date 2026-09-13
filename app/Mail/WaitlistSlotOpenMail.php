<?php

namespace App\Mail;

use App\Models\AppointmentWaitlist;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** "A spot opened up" alert — see AppointmentService::notifyNextWaitlisted(). */
class WaitlistSlotOpenMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AppointmentWaitlist $entry)
    {
    }

    public function build(): self
    {
        return $this->subject('A slot opened up with Dr. ' . $this->entry->doctor->full_name)
            ->view('emails.waitlist-slot-open');
    }
}
