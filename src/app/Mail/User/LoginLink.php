<?php

namespace App\Mail\User;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Crypt;
use SensitiveParameter;

/** Deliver a short-lived passwordless sign-in link immediately. */
class LoginLink extends Mailable
{
    public readonly string $encryptedToken;

    /** Create a sign-in message with the nonce protected for queued storage. */
    public function __construct(#[SensitiveParameter] string $token)
    {
        $this->encryptedToken = Crypt::encryptString($token);
    }

    /** Build the sign-in message and its token-bearing URL. */
    public function build(): self
    {
        return $this->subject('Dein Pfarrplaner-Anmeldelink')
            ->view('mail.user.login-link', [
                'url' => route('login.link.open', ['token' => Crypt::decryptString($this->encryptedToken)]),
            ]);
    }
}
