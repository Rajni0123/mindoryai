<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlanExpiredMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public string $planName;

    public function __construct($user, string $planName)
    {
        $this->user = $user;
        $this->planName = $planName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your ' . $this->planName . ' Plan Has Expired - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.plan-expired',
        );
    }
}
