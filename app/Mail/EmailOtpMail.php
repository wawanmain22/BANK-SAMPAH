<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\EmailOtp;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $code,
        public readonly string $purpose,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->purpose === EmailOtp::PURPOSE_PASSWORD_RESET
            ? 'Kode Reset Password - Bank Sampah'
            : 'Kode Verifikasi Email - Bank Sampah';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.otp',
            with: [
                'code' => $this->code,
                'purpose' => $this->purpose,
                'purposeLabel' => $this->purpose === EmailOtp::PURPOSE_PASSWORD_RESET
                    ? 'reset password'
                    : 'verifikasi email',
                'ttl' => EmailOtp::TTL_MINUTES,
            ],
        );
    }
}
