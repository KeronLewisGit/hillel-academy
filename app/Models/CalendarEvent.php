<?php

namespace App\Models;

use App\Enums\EventCategory;
use Database\Factories\CalendarEventFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * An entry in the public school calendar (open mornings, term dates, fixtures).
 *
 * @property int $id
 * @property string $title
 * @property Carbon $starts_at
 * @property Carbon|null $ends_at
 * @property bool $all_day
 * @property string|null $location
 * @property EventCategory $category
 * @property string|null $description
 * @property bool $published
 */
class CalendarEvent extends Model
{
    /** @use HasFactory<CalendarEventFactory> */
    use HasFactory;

    protected $table = 'events';

    protected $fillable = ['title', 'starts_at', 'ends_at', 'all_day', 'location', 'category', 'description', 'published'];

    #[\Override]
    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'all_day' => 'boolean', 'published' => 'boolean', 'category' => EventCategory::class];
    }

    /**
     * Published and not yet over.
     *
     * @param  Builder<self>  $query
     */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where('published', true)
            ->where(fn (Builder $q) => $q->where('starts_at', '>=', now()->startOfDay())->orWhere('ends_at', '>=', now()->startOfDay()))
            ->orderBy('starts_at');
    }

    public function whenLabel(): string
    {
        $end = $this->ends_at;
        if ($this->all_day) {
            return $end && ! $end->isSameDay($this->starts_at)
                ? $this->starts_at->format('j M').' – '.$end->format('j M Y')
                : $this->starts_at->format('l j F Y');
        }

        return $this->starts_at->format('l j F, g:i a').($end ? ' – '.$end->format('g:i a') : '');
    }
}
