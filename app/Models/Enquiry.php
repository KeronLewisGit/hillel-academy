<?php

namespace App\Models;

use App\Enums\EnquiryKind;
use Database\Factories\EnquiryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Admissions enquiry, tour booking or general message from the website.
 *
 * @property int $id
 * @property EnquiryKind $kind
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $child_name
 * @property string|null $grade
 * @property string|null $entry_year
 * @property int|null $tour_slot_id
 * @property int|null $party_size
 * @property string|null $message
 * @property Carbon|null $handled_at
 * @property Carbon $created_at
 * @property-read TourSlot|null $tourSlot
 */
class Enquiry extends Model
{
    /** @use HasFactory<EnquiryFactory> */
    use HasFactory;

    protected $fillable = ['kind', 'name', 'email', 'phone', 'child_name', 'grade', 'entry_year', 'tour_slot_id', 'party_size', 'message'];

    #[\Override]
    protected function casts(): array
    {
        return ['kind' => EnquiryKind::class, 'handled_at' => 'datetime'];
    }

    /** @return BelongsTo<TourSlot, $this> */
    public function tourSlot(): BelongsTo
    {
        return $this->belongsTo(TourSlot::class);
    }

    /** @param Builder<self> $query */
    public function scopeOpen(Builder $query): void
    {
        $query->whereNull('handled_at');
    }
}
