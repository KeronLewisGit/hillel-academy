<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One row of the published tuition table: tuition per term for Jamaican
 * students (J$) and international students (US$).
 *
 * @property int $id
 * @property string $label
 * @property string|null $grades
 * @property int|null $amount_jmd
 * @property int $amount_usd
 * @property string|null $note
 * @property int $sort_order
 */
class FeeBand extends Model
{
    protected $fillable = ['label', 'grades', 'amount_jmd', 'amount_usd', 'note', 'sort_order'];

    #[\Override]
    protected function casts(): array
    {
        return ['amount_jmd' => 'integer', 'amount_usd' => 'integer', 'sort_order' => 'integer'];
    }

    /** @param Builder<self> $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('amount_usd');
    }
}
