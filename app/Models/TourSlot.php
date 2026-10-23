<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A campus tour time families can book from /visit.
 *
 * @property int $id
 * @property Carbon $starts_at
 * @property int $duration_minutes
 * @property int $capacity families per tour
 * @property string|null $notes
 * @property int|null $bookings_count
 */
class TourSlot extends Model
{
    protected $fillable = ['starts_at', 'duration_minutes', 'capacity', 'notes'];

    #[\Override]
    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'capacity' => 'integer', 'duration_minutes' => 'integer'];
    }

    /** @return HasMany<Enquiry, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(Enquiry::class);
    }

    /**
     * Future tours with a free place, at least a day ahead.
     *
     * @param  Builder<self>  $query
     */
    public function scopeAvailable(Builder $query): void
    {
        $query->where('starts_at', '>', now()->addDay())
            ->whereRaw('capacity > (select count(*) from enquiries where enquiries.tour_slot_id = tour_slots.id)')
            ->orderBy('starts_at');
    }

    /** @param Builder<self> $query */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where('starts_at', '>=', now()->startOfDay())->orderBy('starts_at');
    }

    public function placesLeft(): int
    {
        return max(0, $this->capacity - ($this->bookings_count ?? $this->bookings()->count()));
    }
}
