<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class PlanExpiryReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public int $daysLeft,
        public string $planName,
        public string $expiryDate,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match(true) {
            $this->daysLeft === 0 => "Your {$this->planName} plan expires today!",
            $this->daysLeft === 1 => "Your {$this->planName} plan expires tomorrow",
            default => "Your {$this->planName} plan expires in {$this->daysLeft} days",
        };

        return new Envelope(
            subject: $subject,
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: [
                'List-Unsubscribe' => '<mailto:' . config('mail.from.address') . '?subject=unsubscribe>',
                'X-Mailer' => 'BlinkStudy Mailer',
                'Precedence' => 'bulk',
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.plan-expiry-reminder',
        );
    }
}
