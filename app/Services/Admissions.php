<?php

namespace App\Services;

use App\Enums\AdmissionStatus;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\AssessmentSlot;
use App\Models\GradeCapacity;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The rules of the admissions pipeline in one place, so the dashboard, the board
 * and the family portal can't disagree: seat capacity, waitlist order, when
 * documents count as complete, and who may book or accept what.
 */
class Admissions
{
    public function __construct(private FamilyNotifier $notifier) {}

    public function seats(string $year, string $grade): int
    {
        return (int) GradeCapacity::query()->where('entry_year', $year)->where('grade', $grade)->value('seats');
    }

    /** Open offers, accepted and enrolled: every one holds a seat (expired offers don't). */
    public function seatsHeld(string $year, string $grade, ?int $exceptId = null): int
    {
        return Application::query()->where('entry_year', $year)->where('grade', $grade)
            ->holdingSeat()
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->count();
    }

    public function seatsLeft(string $year, string $grade): int
    {
        return max(0, $this->seats($year, $grade) - $this->seatsHeld($year, $grade));
    }

    /**
     * Seats, holds, waitlist and pipeline per grade for one entry year.
     *
     * @return list<array{grade: string, label: string, section: string, seats: int, offered: int, expired: int, accepted: int, enrolled: int, held: int, left: int, waitlist: int, in_pipeline: int, next_waitlisted: Application|null}>
     */
    public function summary(string $year): array
    {
        $capacities = GradeCapacity::query()->where('entry_year', $year)->pluck('seats', 'grade');
        // toBase(): plain rows, so status stays a string key rather than an enum.
        $counts = Application::query()->where('entry_year', $year)
            ->selectRaw('grade, status, count(*) as total')->groupBy('grade', 'status')->toBase()->get()
            ->groupBy('grade');
        // Offers past their reply-by date no longer hold a seat.
        $expired = Application::query()->where('entry_year', $year)->where('status', AdmissionStatus::Offered)
            ->whereNotNull('offer_expires_at')->where('offer_expires_at', '<=', now())
            ->selectRaw('grade, count(*) as total')->groupBy('grade')->toBase()->pluck('total', 'grade');

        $rows = [];
        foreach ((array) config('hillel.grades') as $grade => $meta) {
            $byStatus = collect($counts->get($grade, []))->pluck('total', 'status');
            $n = fn (AdmissionStatus $s) => (int) ($byStatus[$s->value] ?? 0);
            $lapsed = (int) ($expired[$grade] ?? 0);
            $offered = $n(AdmissionStatus::Offered) - $lapsed;
            $held = $offered + $n(AdmissionStatus::Accepted) + $n(AdmissionStatus::Enrolled);
            $seats = (int) ($capacities[$grade] ?? 0);
            $rows[] = [
                'grade' => $grade,
                'label' => $meta['label'],
                'section' => $meta['section'],
                'seats' => $seats,
                'offered' => $offered,
                'expired' => $lapsed,
                'accepted' => $n(AdmissionStatus::Accepted),
                'enrolled' => $n(AdmissionStatus::Enrolled),
                'held' => $held,
                'left' => max(0, $seats - $held),
                'waitlist' => $n(AdmissionStatus::Waitlisted),
                'in_pipeline' => $n(AdmissionStatus::Submitted) + $n(AdmissionStatus::DocsComplete) + $n(AdmissionStatus::AssessmentBooked) + $n(AdmissionStatus::Assessed),
                'next_waitlisted' => $n(AdmissionStatus::Waitlisted) > 0 ? $this->nextOnWaitlist($year, $grade) : null,
            ];
        }

        return $rows;
    }

    public function nextOnWaitlist(string $year, string $grade): ?Application
    {
        return Application::query()->where('status', AdmissionStatus::Waitlisted)
            ->where('entry_year', $year)->where('grade', $grade)
            ->orderBy('waitlisted_at')->orderBy('id')->first();
    }

    /**
     * Move an application to a new status on behalf of staff. Offers respect the
     * grade's capacity; everything else is a plain move plus the family email.
     *
     * @throws ValidationException when an offer would overfill the grade
     */
    public function move(Application $application, AdmissionStatus $to, ?User $by = null, ?string $note = null): void
    {
        if ($to === AdmissionStatus::Draft) {
            throw ValidationException::withMessages(['status' => 'A submitted application cannot go back to draft.']);
        }

        DB::transaction(function () use ($application, $to, $by, $note) {
            if ($to->holdsSeat() && ! $application->holdsSeat()) {
                // Lock the grade's capacity row so two staff can't both take the last seat.
                $seats = (int) GradeCapacity::query()->where('entry_year', $application->entry_year)
                    ->where('grade', $application->grade)->lockForUpdate()->value('seats');
                $held = $this->seatsHeld($application->entry_year, $application->grade, $application->id);
                if ($held >= $seats) {
                    throw ValidationException::withMessages([
                        'status' => "{$application->grade_label} for {$application->entry_year} is full ({$held} of {$seats} seats held). Waitlist the application or raise the grade’s capacity first.",
                    ]);
                }
            }
            // A closed application gives its assessment seat back.
            if (in_array($to, [AdmissionStatus::Withdrawn, AdmissionStatus::Declined], true) && $application->assessed_at === null) {
                $application->assessment_slot_id = null;
            }
            $application->moveTo($to, $by, $note);
        });

        $this->notifier->statusChanged($application);
    }

    /**
     * Called after staff review a document: once every required document is
     * accepted, a submitted application moves to "Documents complete" by itself.
     */
    public function reviewDocument(ApplicationDocument $document, string $status, ?string $note, User $by): void
    {
        $document->forceFill([
            'status' => $status,
            'review_note' => $status === 'rejected' ? $note : null,
            'reviewed_by' => $by->id,
            'reviewed_at' => now(),
        ])->save();

        $application = $document->application;
        $application->unsetRelation('documents');
        $application->events()->create([
            'user_id' => $by->id,
            'type' => 'document',
            'title' => $document->type->label().($status === 'accepted' ? ' accepted' : ' returned to the family'),
            'body' => $status === 'rejected' ? $note : null,
            'public' => true,
        ]);

        if ($status === 'rejected') {
            $this->notifier->send($application, 'docs_returned', ['{document}' => $document->type->label(), '{reason}' => (string) $note]);
        }

        if ($application->status === AdmissionStatus::Submitted && $application->documentsComplete()) {
            $this->move($application, AdmissionStatus::DocsComplete, $by, 'All required documents accepted.');
        }
    }

    /**
     * Book (or change) an entrance-assessment slot. Locks the slot row so two
     * families can't take the last seat at the same moment.
     *
     * @throws ValidationException
     */
    public function bookAssessment(Application $application, int $slotId, ?User $by = null): AssessmentSlot
    {
        if (! $application->canBookAssessment()) {
            throw ValidationException::withMessages(['slot' => 'This application is not ready for an assessment booking.']);
        }

        $slot = DB::transaction(function () use ($application, $slotId) {
            $slot = AssessmentSlot::query()->lockForUpdate()->withCount('bookings')->find($slotId);
            if (! $slot || ($slot->id !== $application->assessment_slot_id && ! $slot->isBookableFor($application->grade))) {
                throw ValidationException::withMessages(['slot' => 'That time has just been taken or is no longer available. Please choose another.']);
            }
            $application->assessment_slot_id = $slot->id;
            $application->save();

            return $slot;
        });

        $when = $slot->starts_at->format('l j F Y, g:i a');
        if ($application->status === AdmissionStatus::AssessmentBooked) {
            $application->events()->create(['user_id' => $by?->id, 'type' => 'system', 'title' => 'Assessment moved to '.$when, 'public' => true]);
            $this->notifier->send($application, 'assessment_booked');
        } else {
            $application->moveTo(AdmissionStatus::AssessmentBooked, $by, ($by ? 'Booked by staff' : 'Booked by the family').' for '.$when);
            $this->notifier->statusChanged($application);
        }

        return $slot;
    }

    /** The family accepts the place. @throws ValidationException */
    public function acceptOffer(Application $application): void
    {
        if (! $application->offerIsOpen()) {
            throw ValidationException::withMessages(['offer' => 'This offer is no longer open. Please call the Admissions Office.']);
        }
        $application->moveTo(AdmissionStatus::Accepted, null, 'Accepted by the family in the portal.');
        $application->events()->create(['type' => 'system', 'title' => 'You accepted the offer of a place', 'public' => true]);
    }

    /** The family turns the place down; the seat goes back to the grade. */
    public function declineOffer(Application $application): void
    {
        if ($application->status !== AdmissionStatus::Offered) {
            throw ValidationException::withMessages(['offer' => 'There is no open offer on this application.']);
        }
        $application->moveTo(AdmissionStatus::Withdrawn, null, 'Offer declined by the family in the portal.');
        $application->events()->create(['type' => 'system', 'title' => 'You declined the offer of a place', 'public' => true]);
    }
}
