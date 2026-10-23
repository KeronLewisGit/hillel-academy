<?php

namespace App\Models;

use App\Enums\TeacherApplicationStatus;
use Database\Factories\TeacherApplicationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference
 * @property int|null $job_posting_id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $phone
 * @property string $country
 * @property string|null $current_position
 * @property string $subjects
 * @property int $years_experience
 * @property list<string>|null $curricula
 * @property string|null $qualifications
 * @property Carbon|null $available_from
 * @property string|null $cover_letter
 * @property string $cv_path
 * @property string $cv_name
 * @property TeacherApplicationStatus $status
 * @property string|null $staff_notes
 * @property Carbon $created_at
 * @property-read string $full_name
 * @property-read JobPosting|null $jobPosting
 */
class TeacherApplication extends Model
{
    /** @use HasFactory<TeacherApplicationFactory> */
    use HasFactory;

    public const string REFERENCE_PREFIX = 'HT';

    public const array CV_TYPES = ['pdf', 'doc', 'docx'];

    /** Curricula a teacher can say they have taught. */
    public const array CURRICULA = ['ib_dp' => 'IB Diploma', 'ib_myp' => 'IB MYP', 'ib_pyp' => 'IB PYP', 'igcse' => 'Cambridge IGCSE', 'a_level' => 'A Level', 'cxc' => 'CXC (CSEC / CAPE)', 'us' => 'US / AP', 'uk_national' => 'UK National Curriculum'];

    protected $fillable = ['job_posting_id', 'first_name', 'last_name', 'email', 'phone', 'country', 'current_position', 'subjects', 'years_experience', 'curricula', 'qualifications', 'available_from', 'cover_letter', 'cv_path', 'cv_name', 'status', 'staff_notes'];

    #[\Override]
    protected function casts(): array
    {
        return ['curricula' => 'array', 'available_from' => 'date', 'status' => TeacherApplicationStatus::class, 'status_changed_at' => 'datetime', 'years_experience' => 'integer'];
    }

    #[\Override]
    protected static function booted(): void
    {
        static::creating(function (self $a): void {
            $a->reference ??= 'pending-'.bin2hex(random_bytes(4));
        });
        static::created(function (self $a): void {
            $a->forceFill(['reference' => sprintf('%s-%s-%04d', self::REFERENCE_PREFIX, $a->created_at->format('y'), $a->id)])->saveQuietly();
        });
    }

    /** @return BelongsTo<JobPosting, $this> */
    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function moveTo(TeacherApplicationStatus $status): void
    {
        if ($this->status !== $status) {
            $this->status = $status;
            $this->forceFill(['status_changed_at' => now()]);
        }
    }

    /** @param Builder<self> $query */
    public function scopeAwaitingReview(Builder $query): void
    {
        $query->where('status', TeacherApplicationStatus::New);
    }
}
