<?php

namespace App\Models;

use App\Enums\JobStatus;
use Database\Factories\JobPostingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A teaching or staff vacancy on /careers.
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string $section
 * @property string $employment_type
 * @property string $summary
 * @property string $description
 * @property string|null $requirements
 * @property string|null $start_date_text
 * @property Carbon|null $closes_on
 * @property bool $overseas_welcome
 * @property JobStatus $status
 * @property int|null $applications_count
 */
class JobPosting extends Model
{
    /** @use HasFactory<JobPostingFactory> */
    use HasFactory;

    public const array TYPES = ['full_time' => 'Full time', 'part_time' => 'Part time', 'contract' => 'Fixed-term contract'];

    protected $fillable = ['title', 'slug', 'section', 'employment_type', 'summary', 'description', 'requirements', 'start_date_text', 'closes_on', 'overseas_welcome', 'status'];

    #[\Override]
    protected function casts(): array
    {
        return ['closes_on' => 'date', 'overseas_welcome' => 'boolean', 'status' => JobStatus::class];
    }

    #[\Override]
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return HasMany<TeacherApplication, $this> */
    public function applications(): HasMany
    {
        return $this->hasMany(TeacherApplication::class);
    }

    /** @param Builder<self> $query */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', JobStatus::Open)
            ->where(fn (Builder $q) => $q->whereNull('closes_on')->orWhereDate('closes_on', '>=', today()));
    }

    public function isOpen(): bool
    {
        return $this->status === JobStatus::Open && (! $this->closes_on || $this->closes_on->gte(today()));
    }

    public function sectionLabel(): string
    {
        return (string) (config('hillel.sections.'.$this->section.'.name') ?? 'Whole school');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->employment_type] ?? $this->employment_type;
    }

    /** @return list<string> */
    public function requirementList(): array
    {
        return lines_to_array($this->requirements);
    }
}
