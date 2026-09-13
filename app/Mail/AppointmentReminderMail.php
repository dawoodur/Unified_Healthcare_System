<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** A "your appointment is tomorrow" reminder — see SendAppointmentRemindersCommand. */
class AppointmentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Appointment $appointment)
    {
    }

    public function build(): self
    {
        return $this->subject('Reminder: your appointment tomorrow')
            ->view('emails.appointment-reminder');
    }
}
