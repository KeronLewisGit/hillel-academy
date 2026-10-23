<?php

namespace App\Models;

use App\Enums\AdmissionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * An entrance-assessment session families can book from the portal.
 *
 * @property int $id
 * @property Carbon $starts_at
 * @property int $duration_minutes
 * @property string $location
 * @property int $capacity
 * @property list<string>|null $grades
 * @property string|null $notes
 * @property int|null $bookings_count
 */
class AssessmentSlot extends Model
{
    /** Families cannot book a slot that starts sooner than this. */
    public const int LEAD_HOURS = 24;

    protected $fillable = ['starts_at', 'duration_minutes', 'location', 'capacity', 'grades', 'notes'];

    #[\Override]
    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'grades' => 'array', 'capacity' => 'integer', 'duration_minutes' => 'integer'];
    }

    /**
     * Applications holding a seat (withdrawn or closed applications release it).
     *
     * @return HasMany<Application, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Application::class)->whereNotIn('status', [AdmissionStatus::Withdrawn, AdmissionStatus::Draft]);
    }

    /** @param Builder<self> $query */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where('starts_at', '>=', now()->startOfDay())->orderBy('starts_at');
    }

    /** @param Builder<self> $query */
    public function scopeBookableFor(Builder $query, string $grade): void
    {
        $query->where('starts_at', '>', now()->addHours(self::LEAD_HOURS))
            ->withCount('bookings')
            ->orderBy('starts_at');
    }

    public function suits(string $grade): bool
    {
        return empty($this->grades) || in_array($grade, $this->grades, true);
    }

    public function seatsLeft(): int
    {
        return max(0, $this->capacity - ($this->bookings_count ?? $this->bookings()->count()));
    }

    public function isBookableFor(string $grade): bool
    {
        return $this->starts_at->gt(now()->addHours(self::LEAD_HOURS)) && $this->suits($grade) && $this->seatsLeft() > 0;
    }

    public function endsAt(): Carbon
    {
        return $this->starts_at->copy()->addMinutes($this->duration_minutes);
    }

    public function gradesLabel(): string
    {
        return empty($this->grades) ? 'Any grade' : implode(', ', array_map(grade_label(...), $this->grades));
    }
}
