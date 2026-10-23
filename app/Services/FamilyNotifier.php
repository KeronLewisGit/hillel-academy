<?php

namespace App\Services;

use App\Enums\AdmissionStatus;
use App\Mail\FamilyMessage;
use App\Models\Application;
use App\Models\MessageTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Tells a family what changed: a public line on their portal timeline plus the
 * matching office-editable email template. A failed email is written on the
 * staff timeline instead of breaking the action that caused it.
 */
class FamilyNotifier
{
    private const array STATUS_TEMPLATES = [
        'submitted' => 'received',
        'docs_complete' => 'docs_complete',
        'assessment_booked' => 'assessment_booked',
        'offered' => 'offered',
        'waitlisted' => 'waitlisted',
        'declined' => 'declined',
        'enrolled' => 'enrolled',
    ];

    public function statusChanged(Application $application): void
    {
        $status = $application->status;
        if (in_array($status, [AdmissionStatus::Draft, AdmissionStatus::Assessed, AdmissionStatus::Accepted, AdmissionStatus::Withdrawn], true)) {
            return; // nothing new for the family to act on, or they did it themselves
        }

        $application->events()->create([
            'type' => 'status',
            'title' => $status->familyLabel(),
            'public' => true,
        ]);

        $this->send($application, self::STATUS_TEMPLATES[$status->value]);
    }

    /**
     * Email the family using an office template.
     *
     * @param  array<string, string>  $extra  additional placeholders for this message
     */
    public function send(Application $application, string $key, array $extra = []): void
    {
        $template = MessageTemplate::forKey($key);
        if (! $template) {
            return;
        }
        $this->deliver($application, MessageTemplate::render(strtr($template->subject, $extra), $application), MessageTemplate::render(strtr($template->body, $extra), $application));
    }

    /** A one-off message written by staff (optionally from a template). */
    public function custom(Application $application, string $subject, string $body, User $sender): bool
    {
        $subject = MessageTemplate::render($subject, $application, $sender);
        $body = MessageTemplate::render($body, $application, $sender);
        $sent = $this->deliver($application, $subject, $body, $sender);
        $application->events()->create(['user_id' => $sender->id, 'type' => 'message', 'title' => $subject, 'body' => $body, 'public' => true]);

        return $sent;
    }

    private function deliver(Application $application, string $subject, string $body, ?User $sender = null): bool
    {
        $application->loadMissing('family');
        $to = array_values(array_unique(array_filter([$application->family->email, $application->guardian_email])));

        try {
            Mail::to($to)->send(new FamilyMessage($subject, $body));

            return true;
        } catch (Throwable $e) {
            report($e);
            $application->events()->create([
                'user_id' => $sender?->id,
                'type' => 'system',
                'title' => 'Email to the family failed: '.$subject,
                'body' => $e->getMessage(),
                'public' => false,
            ]);

            return false;
        }
    }
}
