<?php

namespace App\Enums;

/**
 * The admissions pipeline. Families see a simpler wording (familyLabel) than staff,
 * and colour is only ever paired with an icon and text.
 */
enum AdmissionStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case DocsComplete = 'docs_complete';
    case AssessmentBooked = 'assessment_booked';
    case Assessed = 'assessed';
    case Offered = 'offered';
    case Waitlisted = 'waitlisted';
    case Declined = 'declined';
    case Accepted = 'accepted';
    case Enrolled = 'enrolled';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'In progress',
            self::Submitted => 'Submitted',
            self::DocsComplete => 'Documents complete',
            self::AssessmentBooked => 'Assessment booked',
            self::Assessed => 'Assessed',
            self::Offered => 'Offered',
            self::Waitlisted => 'Waitlisted',
            self::Declined => 'Not offered',
            self::Accepted => 'Offer accepted',
            self::Enrolled => 'Enrolled',
            self::Withdrawn => 'Withdrawn',
        };
    }

    /** What a family reads in the portal. */
    public function familyLabel(): string
    {
        return match ($this) {
            self::Draft => 'Not yet submitted',
            self::Submitted => 'Received, checking documents',
            self::DocsComplete => 'Documents complete, book an assessment',
            self::AssessmentBooked => 'Assessment booked',
            self::Assessed => 'Assessment done, decision pending',
            self::Offered => 'Place offered',
            self::Waitlisted => 'On the waiting list',
            self::Declined => 'Not offered a place',
            self::Accepted => 'Offer accepted',
            self::Enrolled => 'Enrolled',
            self::Withdrawn => 'Withdrawn',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Submitted => 'attention',
            self::Offered, self::Accepted, self::Enrolled => 'good',
            self::Declined => 'critical',
            self::Draft, self::Withdrawn => 'muted',
            default => 'neutral',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Draft => 'pencil',
            self::Submitted => 'inbox',
            self::DocsComplete => 'file',
            self::AssessmentBooked => 'calendar',
            self::Assessed => 'check',
            self::Offered => 'star',
            self::Waitlisted => 'clock',
            self::Declined => 'x',
            self::Accepted => 'check-circle',
            self::Enrolled => 'check-circle',
            self::Withdrawn => 'dot',
        };
    }

    /**
     * Columns on the kanban board, in order.
     *
     * @return list<self>
     */
    public static function board(): array
    {
        return [self::Submitted, self::DocsComplete, self::AssessmentBooked, self::Assessed, self::Offered, self::Waitlisted, self::Accepted, self::Enrolled];
    }

    /**
     * Statuses that use up a seat in the grade's capacity.
     *
     * @return list<self>
     */
    public static function seatHolding(): array
    {
        return [self::Offered, self::Accepted, self::Enrolled];
    }

    public function holdsSeat(): bool
    {
        return in_array($this, self::seatHolding(), true);
    }

    /** Submitted and still moving: not decided, not closed. */
    public function isActive(): bool
    {
        return in_array($this, [self::Submitted, self::DocsComplete, self::AssessmentBooked, self::Assessed, self::Waitlisted, self::Offered, self::Accepted], true);
    }

    /**
     * The family-facing milestones on the portal tracker.
     *
     * @return array<string, string>
     */
    public static function milestones(): array
    {
        return [
            'submitted' => 'Application received',
            'documents' => 'Documents checked',
            'assessment' => 'Entrance assessment',
            'decision' => 'Decision',
            'enrolled' => 'Enrolled',
        ];
    }

    /** How far along the family-facing tracker this status sits (0–4). */
    public function milestone(): int
    {
        return match ($this) {
            self::Draft, self::Withdrawn => -1,
            self::Submitted => 0,
            self::DocsComplete => 1,
            self::AssessmentBooked => 2,
            self::Assessed => 2,
            self::Offered, self::Waitlisted, self::Declined, self::Accepted => 3,
            self::Enrolled => 4,
        };
    }
}
