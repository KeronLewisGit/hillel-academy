<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Seats the school can offer in a grade for an entry year.
 *
 * @property int $id
 * @property string $entry_year
 * @property string $grade
 * @property int $seats
 */
class GradeCapacity extends Model
{
    protected $fillable = ['entry_year', 'grade', 'seats'];

    #[\Override]
    protected function casts(): array
    {
        return ['seats' => 'integer'];
    }
}
