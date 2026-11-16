<?php

namespace App\Mail\User;

use Illuminate\Mail\Mailable;
use SensitiveParameter;

/** Deliver a short-lived login code without placing it in a queued payload. */
class TwoFactorLoginCode extends Mailable
{
    /** Create the message with the one-time plaintext code for immediate delivery. */
    public function __construct(#[SensitiveParameter] public readonly string $code) {}

    /** Build the email challenge message. */
    public function build(): self
    {
        return $this->subject('Dein Pfarrplaner-Anmeldecode')
            ->view('mail.user.two-factor-login-code', ['code' => $this->code]);
    }
}
