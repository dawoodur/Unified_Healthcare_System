<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * One hospital's urgent call for a specific blood type, emailed to every
 * eligible donor of that type — see BloodDonationService::sendEmergencyRequest().
 * Same shape as OtpMail.php: this class only describes the email, it
 * doesn't send anything itself.
 */
class EmergencyBloodRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    // Named $requestMessage, not $message — Laravel reserves the variable
    // name "$message" in every mail view (it auto-injects the underlying
    // Illuminate\Mail\Message object under that name for advanced use
    // cases like inline attachments), so a Mailable property literally
    // called $message gets silently shadowed by that instead of showing
    // up as the string it actually is.
    public function __construct(
        public string $recipientName,
        public string $hospitalName,
        public string $bloodGroup,
        public ?string $requestMessage,
    ) {
    }

    public function build(): self
    {
        return $this->subject("Urgent: {$this->bloodGroup} blood needed at {$this->hospitalName}")
            ->view('emails.emergency-blood-request');
    }
}
