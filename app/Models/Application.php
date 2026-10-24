<?php

namespace App\Models;

use App\Enums\AdmissionStatus;
use App\Enums\DocumentType;
use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A family's application for one child, from first draft to enrolment.
 *
 * @property int $id
 * @property string $reference
 * @property int $family_id
 * @property AdmissionStatus $status
 * @property int $step
 * @property string $student_first_name
 * @property string|null $student_last_name
 * @property string|null $student_preferred_name
 * @property Carbon|null $date_of_birth
 * @property string $grade
 * @property string $entry_year
 * @property string $entry_term
 * @property string|null $guardian_name
 * @property string|null $guardian_email
 * @property string|null $guardian_phone
 * @property int|null $assessment_slot_id
 * @property string|null $assessment_notes
 * @property string|null $staff_notes
 * @property int|null $assigned_to
 * @property Carbon|null $submitted_at
 * @property Carbon|null $docs_complete_at
 * @property Carbon|null $assessed_at
 * @property Carbon|null $decision_at
 * @property Carbon|null $waitlisted_at
 * @property Carbon|null $offer_expires_at
 * @property Carbon|null $offer_accepted_at
 * @property Carbon|null $enrolled_at
 * @property Carbon|null $withdrawn_at
 * @property Carbon|null $status_changed_at
 * @property Carbon $created_at
 * @property-read string $student_name
 * @property-read string $grade_label
 * @property-read Family $family
 * @property-read AssessmentSlot|null $assessmentSlot
 * @property-read User|null $assignee
 * @property-read Collection<int, ApplicationDocument> $documents
 * @property-read Collection<int, ApplicationEvent> $events
 */
class Application extends Model
{
    /** @use HasFactory<ApplicationFactory> */
    use HasFactory;

    public const string REFERENCE_PREFIX = 'HA';

    public const int STEPS = 5;

    /** Steps of the online form, in order. */
    public const array STEP_NAMES = [1 => 'Student', 2 => 'Parents & guardians', 3 => 'Schooling', 4 => 'Documents', 5 => 'Review & submit'];

    protected $fillable = [
        'family_id', 'status', 'step', 'student_first_name', 'student_last_name', 'student_preferred_name', 'date_of_birth',
        'gender', 'nationality', 'first_language', 'grade', 'entry_year', 'entry_term', 'previous_school', 'previous_school_country',
        'current_grade', 'support_needs', 'sibling_at_hillel', 'heard_about', 'guardian_name', 'guardian_relationship', 'guardian_email',
        'guardian_phone', 'guardian_address', 'guardian_occupation', 'guardian2_name', 'guardian2_relationship', 'guardian2_email',
        'guardian2_phone', 'staff_notes', 'assigned_to',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'status' => AdmissionStatus::class,
            'step' => 'integer',
            'date_of_birth' => 'date',
            'sibling_at_hillel' => 'boolean',
            'submitted_at' => 'datetime',
            'docs_complete_at' => 'datetime',
            'assessed_at' => 'datetime',
            'decision_at' => 'datetime',
            'waitlisted_at' => 'datetime',
            'offer_expires_at' => 'datetime',
            'offer_accepted_at' => 'datetime',
            'enrolled_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'status_changed_at' => 'datetime',
        ];
    }

    #[\Override]
    protected static function booted(): void
    {
        static::creating(function (self $application): void {
            $application->reference ??= 'pending-'.bin2hex(random_bytes(4));
        });
        static::created(function (self $application): void {
            $application->forceFill([
                'reference' => sprintf('%s-%s-%05d', self::REFERENCE_PREFIX, $application->created_at->format('y'), $application->id),
            ])->saveQuietly();
        });
    }

    #[\Override]
    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    /** @return BelongsTo<Family, $this> */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /** @return HasMany<ApplicationDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class)->latest('id');
    }

    /** @return HasMany<ApplicationEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(ApplicationEvent::class)->latest('id');
    }

    /** @return BelongsTo<AssessmentSlot, $this> */
    public function assessmentSlot(): BelongsTo
    {
        return $this->belongsTo(AssessmentSlot::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function getStudentNameAttribute(): string
    {
        return trim($this->student_first_name.' '.$this->student_last_name);
    }

    public function getGradeLabelAttribute(): string
    {
        return grade_label($this->grade);
    }

    public function isDraft(): bool
    {
        return $this->status === AdmissionStatus::Draft;
    }

    /** @return list<DocumentType> */
    public function requiredDocuments(): array
    {
        return DocumentType::requiredForGrade($this->grade);
    }

    /** The newest file of a type (staff review the latest upload). */
    public function latestDocument(DocumentType $type): ?ApplicationDocument
    {
        return $this->documents->first(fn (ApplicationDocument $d) => $d->type === $type);
    }

    /**
     * Required documents the family still has to send: never uploaded, or the
     * latest upload was returned by staff.
     *
     * @return list<DocumentType>
     */
    public function missingDocuments(): array
    {
        return array_values(array_filter($this->requiredDocuments(), function (DocumentType $type) {
            $doc = $this->latestDocument($type);

            return ! $doc || $doc->status === 'rejected';
        }));
    }

    public function documentsComplete(): bool
    {
        foreach ($this->requiredDocuments() as $type) {
            if ($this->latestDocument($type)?->status !== 'accepted') {
                return false;
            }
        }

        return true;
    }

    public function canBookAssessment(): bool
    {
        return in_array($this->status, [AdmissionStatus::DocsComplete, AdmissionStatus::AssessmentBooked], true)
            && (! $this->assessmentSlot || $this->assessmentSlot->starts_at->isFuture());
    }

    public function offerIsOpen(): bool
    {
        return $this->status === AdmissionStatus::Offered && (! $this->offer_expires_at || $this->offer_expires_at->isFuture());
    }

    /** Offers out (until they expire), accepted and enrolled places use up a seat. */
    public function holdsSeat(): bool
    {
        return $this->status === AdmissionStatus::Offered ? $this->offerIsOpen() : $this->status->holdsSeat();
    }

    /** Families may upload while the school is still collecting paperwork. */
    public function acceptsUploads(): bool
    {
        return in_array($this->status, [AdmissionStatus::Draft, AdmissionStatus::Submitted, AdmissionStatus::DocsComplete, AdmissionStatus::AssessmentBooked, AdmissionStatus::Assessed, AdmissionStatus::Waitlisted, AdmissionStatus::Offered, AdmissionStatus::Accepted], true);
    }

    /**
     * Move along the pipeline, stamping the matching date and writing the timeline.
     * Capacity is checked by App\Services\Admissions before an offer, not here.
     */
    public function moveTo(AdmissionStatus $status, ?User $by = null, ?string $note = null): void
    {
        if ($this->status === $status) {
            return;
        }
        $from = $this->status;
        $this->status = $status;
        $this->status_changed_at = now();
        match ($status) {
            AdmissionStatus::Submitted => $this->submitted_at ??= now(),
            AdmissionStatus::DocsComplete => $this->docs_complete_at ??= now(),
            AdmissionStatus::Assessed => $this->assessed_at ??= now(),
            AdmissionStatus::Waitlisted => $this->waitlisted_at = now(),
            AdmissionStatus::Offered => $this->offer_expires_at = now()->addDays(max(1, (int) setting('offer_days'))),
            AdmissionStatus::Accepted => $this->offer_accepted_at = now(),
            AdmissionStatus::Enrolled => $this->enrolled_at = now(),
            AdmissionStatus::Withdrawn => $this->withdrawn_at = now(),
            default => null,
        };
        if (in_array($status, [AdmissionStatus::Offered, AdmissionStatus::Waitlisted, AdmissionStatus::Declined], true)) {
            $this->decision_at = now();
        }
        $this->save();

        $this->events()->create([
            'user_id' => $by?->id,
            'type' => 'status',
            'title' => $from->label().' → '.$status->label(),
            'body' => $note,
            'public' => false,
        ]);
    }

    /** Place on the grade's waiting list (1 = next in line), or null. */
    public function waitlistPosition(): ?int
    {
        if ($this->status !== AdmissionStatus::Waitlisted) {
            return null;
        }

        return static::query()->where('status', AdmissionStatus::Waitlisted)
            ->where('entry_year', $this->entry_year)->where('grade', $this->grade)
            ->where(fn (Builder $q) => $q->where('waitlisted_at', '<', $this->waitlisted_at)
                ->orWhere(fn (Builder $w) => $w->where('waitlisted_at', $this->waitlisted_at)->where('id', '<', $this->id)))
            ->count() + 1;
    }

    /**
     * Applications using up a seat: see holdsSeat(). An offer past its reply-by date
     * gives the seat back, so expired offers never block the grade.
     *
     * @param  Builder<self>  $query
     */
    public function scopeHoldingSeat(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q->whereIn('status', [AdmissionStatus::Accepted, AdmissionStatus::Enrolled])
            ->orWhere(fn (Builder $o) => $o->where('status', AdmissionStatus::Offered)
                ->where(fn (Builder $e) => $e->whereNull('offer_expires_at')->orWhere('offer_expires_at', '>', now()))));
    }

    /** @param Builder<self> $query */
    public function scopeSubmitted(Builder $query): void
    {
        $query->where('status', '!=', AdmissionStatus::Draft);
    }

    /**
     * Applications with at least one upload staff have not looked at yet.
     *
     * @param  Builder<self>  $query
     */
    public function scopeAwaitingDocumentReview(Builder $query): void
    {
        $query->where('status', '!=', AdmissionStatus::Draft)
            ->whereHas('documents', fn (Builder $d) => $d->where('status', 'pending'));
    }

    /**
     * Filters shared by the admin list, the board and the CSV export.
     *
     * @param  Builder<self>  $query
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): void
    {
        $query
            ->when($filters['status'] ?? null, fn (Builder $q, $v) => $q->where('status', $v))
            ->when($filters['grade'] ?? null, fn (Builder $q, $v) => $q->where('grade', $v))
            ->when($filters['year'] ?? null, fn (Builder $q, $v) => $q->where('entry_year', $v))
            ->when($filters['docs'] ?? null, fn (Builder $q) => $q->awaitingDocumentReview())
            ->when($filters['q'] ?? null, function (Builder $q, $term) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], (string) $term).'%';
                $q->where(fn (Builder $w) => $w->where('student_first_name', 'like', $like)
                    ->orWhere('student_last_name', 'like', $like)
                    ->orWhere('reference', 'like', $like)
                    ->orWhere('guardian_name', 'like', $like)
                    ->orWhere('guardian_email', 'like', $like)
                    ->orWhereHas('family', fn (Builder $f) => $f->where('email', 'like', $like)->orWhere('name', 'like', $like)));
            });
    }
}
