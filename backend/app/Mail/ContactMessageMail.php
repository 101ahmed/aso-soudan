<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $payload,
    ) {}

    public function envelope(): Envelope
    {
        $replyTo = $this->payload['email'] ?? null;

        return new Envelope(
            subject: '[Contact Rabta] '.($this->payload['subject'] ?? 'Nouveau message'),
            replyTo: $replyTo ? [new Address($replyTo, $this->payload['name'] ?? $replyTo)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.contact-message',
        );
    }
}
