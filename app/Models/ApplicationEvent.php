<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Timeline entry on an application. `public` entries also show in the family portal.
 *
 * @property int $id
 * @property int $application_id
 * @property int|null $user_id
 * @property string $type status|note|message|document|system
 * @property string|null $title
 * @property string|null $body
 * @property bool $public
 * @property Carbon $created_at
 * @property-read User|null $user
 */
class ApplicationEvent extends Model
{
    protected $fillable = ['user_id', 'type', 'title', 'body', 'public'];

    #[\Override]
    protected function casts(): array
    {
        return ['public' => 'boolean'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
