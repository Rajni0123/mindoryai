<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $alertType,
        public string $alertTitle,
        public array $details,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Admin] ' . $this->alertTitle,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.admin-alert');
    }
}
