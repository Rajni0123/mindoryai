<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $planName,
        public string $amount,
        public string $transactionId,
        public string $paymentMethod,
        public string $expiryDate,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('noreply@blinkstudy.in', 'BlinkStudy'),
            subject: 'Payment Successful - ' . $this->planName . ' Plan Activated!',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.payment-success');
    }
}
