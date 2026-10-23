<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * One office alert class for every website submission (enquiry, tour, application,
 * teacher application): a subject, a few labelled lines and a dashboard link.
 */
class OfficeAlert extends Notification
{
    use Queueable;

    /** @param array<string, string|null> $lines */
    public function __construct(public string $subject, public array $lines, public string $url, public string $action = 'Open in the dashboard', public ?string $replyTo = null) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject($this->subject)->greeting($this->subject);
        foreach ($this->lines as $label => $value) {
            if ($value !== null && $value !== '') {
                $mail->line($label.': '.$value);
            }
        }
        if ($this->replyTo && filter_var($this->replyTo, FILTER_VALIDATE_EMAIL)) {
            $mail->replyTo($this->replyTo);
        }

        return $mail->action($this->action, $this->url);
    }
}
