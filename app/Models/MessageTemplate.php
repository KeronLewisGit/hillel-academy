<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Email copy the admissions office edits. Templates with a `key` are sent
 * automatically at that pipeline step; the rest are for one-off messages.
 *
 * @property int $id
 * @property string|null $key
 * @property string $name
 * @property string $subject
 * @property string $body
 */
class MessageTemplate extends Model
{
    /** Keys sent automatically, with when they go out. */
    public const array AUTOMATIC = [
        'received' => 'When a family submits an application',
        'docs_returned' => 'When staff return a document to be resent',
        'docs_complete' => 'When every required document is accepted',
        'assessment_booked' => 'When an assessment is booked (by the family or staff)',
        'offered' => 'When a place is offered',
        'waitlisted' => 'When an application is waitlisted',
        'declined' => 'When a place is not offered',
        'enrolled' => 'When the student is marked enrolled',
    ];

    protected $fillable = ['key', 'name', 'subject', 'body'];

    /** @return list<string> */
    public static function placeholders(): array
    {
        return ['{parent_name}', '{student_first_name}', '{student_name}', '{grade}', '{entry_year}', '{reference}', '{assessment_date}', '{offer_deadline}', '{portal_link}', '{school}', '{admissions_phone}', '{sender}'];
    }

    public static function render(string $text, Application $a, ?User $sender = null): string
    {
        $a->loadMissing('family', 'assessmentSlot');

        return strtr($text, [
            '{parent_name}' => $a->guardian_name ?: $a->family->name,
            '{student_first_name}' => $a->student_first_name,
            '{student_name}' => $a->student_name,
            '{grade}' => $a->grade_label,
            '{entry_year}' => $a->entry_year,
            '{reference}' => $a->reference,
            '{assessment_date}' => $a->assessmentSlot?->starts_at->format('l j F Y \a\t g:i a') ?? '[assessment date]',
            '{offer_deadline}' => $a->offer_expires_at?->format('l j F Y') ?? '[offer deadline]',
            '{portal_link}' => route('family.login'),
            '{school}' => (string) hillel('name'),
            '{admissions_phone}' => (string) setting('phone_admissions'),
            '{sender}' => $sender->name ?? 'The Admissions Office',
        ]);
    }

    public static function forKey(string $key): ?self
    {
        return static::query()->where('key', $key)->first();
    }
}
