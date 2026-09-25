<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TwoFactorCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $code,
        public string $action = 'login'
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->action) {
            'password' => 'Գաղտնաբառի Փոփոխման 2FA Կոդ — QRMenu',
            'email' => 'Էլ․ Փոստի Փոփոխման 2FA Կոդ — QRMenu',
            'setup' => '2FA Ակտիվացման Ստուգիչ Կոդ — QRMenu',
            default => 'Ձեր Մուտքի Երկփուլային Նույնականացման Կոդը (2FA) — QRMenu',
        };

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.two_factor_code',
        );
    }
}
