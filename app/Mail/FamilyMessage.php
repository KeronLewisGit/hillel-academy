<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Every admissions email to a family: a subject and plain paragraphs from a template. */
class FamilyMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $mailSubject, public string $body) {}

    public function envelope(): Envelope
    {
        $replyTo = (string) setting('email_admissions');

        return new Envelope(
            subject: $this->mailSubject,
            replyTo: filter_var($replyTo, FILTER_VALIDATE_EMAIL) ? [new Address($replyTo, 'Hillel Academy Admissions')] : [],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.family-message', with: ['paragraphs' => preg_split('/\R{2,}/', trim($this->body)) ?: []]);
    }
}
