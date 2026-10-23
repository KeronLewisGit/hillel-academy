<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** The single-use sign-in link for the family portal (also used to resume an application). */
class FamilyLoginLink extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $name, public string $url, public bool $newApplication = false) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->newApplication ? 'Continue your Hillel Academy application' : 'Your Hillel Academy family portal link');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.family-login-link');
    }
}
