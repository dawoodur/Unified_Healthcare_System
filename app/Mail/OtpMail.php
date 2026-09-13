<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Represents one OTP email — its content and subject line. This class
 * doesn't send anything by itself; app/Services/OtpService.php builds one
 * of these (`new OtpMail(...)`) and hands it to Laravel's Mail::send(...).
 *
 * `extends Mailable` is what gives this class all the built-in "this is an
 * email" behavior (subject, view, attachments, etc.) — we only need to fill
 * in the two methods below.
 */
class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * `public string $recipientName` etc. is "constructor property
     * promotion" — a PHP shortcut that both declares the parameter AND
     * saves it as $this->recipientName in one line, instead of writing:
     *   public string $recipientName;
     *   public function __construct(string $recipientName) { $this->recipientName = $recipientName; }
     * Whatever calls `new OtpMail(...)` (see OtpService::issue()) passes in
     * these four values, and they become available to build() below, and to
     * the email template itself (resources/views/emails/otp.blade.php).
     */
    public function __construct(
        public string $recipientName,
        public string $purposeLabel,
        public string $code,
        public int $expiryMinutes,
    ) {
    }

    /** Laravel calls this to figure out the email's subject line and content. */
    public function build(): self
    {
        return $this->subject($this->purposeLabel)
            ->view('emails.otp'); // renders resources/views/emails/otp.blade.php
    }
}
