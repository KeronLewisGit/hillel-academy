<?php

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An uploaded admissions document. Files live on the private `local` disk and are
 * only streamed to the owning family or signed-in admissions staff.
 *
 * @property int $id
 * @property int $application_id
 * @property DocumentType $type
 * @property string $path
 * @property string $original_name
 * @property int $size
 * @property string|null $mime
 * @property string $status pending|accepted|rejected
 * @property string|null $review_note
 * @property Carbon|null $reviewed_at
 * @property Carbon $created_at
 * @property-read Application $application
 */
class ApplicationDocument extends Model
{
    /** File types families can send: scans and phone photos. */
    public const array TYPES = ['pdf', 'jpg', 'jpeg', 'png'];

    public const int MAX_KB = 8192;

    protected $fillable = ['type', 'path', 'original_name', 'size', 'mime', 'status', 'review_note', 'reviewed_by', 'reviewed_at'];

    #[\Override]
    protected function casts(): array
    {
        return ['type' => DocumentType::class, 'size' => 'integer', 'reviewed_at' => 'datetime'];
    }

    /** @return BelongsTo<Application, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'accepted' => 'Accepted',
            'rejected' => 'Please resend',
            default => 'Waiting for review',
        };
    }

    public function tone(): string
    {
        return match ($this->status) {
            'accepted' => 'good',
            'rejected' => 'critical',
            default => 'attention',
        };
    }

    public function humanSize(): string
    {
        return $this->size >= 1048576 ? round($this->size / 1048576, 1).' MB' : max(1, (int) round($this->size / 1024)).' KB';
    }
}
