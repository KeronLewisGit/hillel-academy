<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Key/value settings the office edits in the dashboard (phones, fees year, entry years).
 * Defaults live in config('hillel.settings'), so an empty table still renders the site.
 *
 * @property string $key
 * @property string|null $value
 */
class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    /** @return array<string, string|null> */
    public static function allCached(): array
    {
        try {
            /** @var array<string, string|null> */
            return Cache::remember('settings.all', 3600, fn () => static::query()->pluck('value', 'key')->all());
        } catch (\Throwable) {
            return []; // before migrations have run
        }
    }

    public static function read(string $key, mixed $default = null): mixed
    {
        $all = static::allCached();
        $value = $all[$key] ?? null;

        return $value !== null && $value !== '' ? $value : ($default ?? config('hillel.settings.'.$key));
    }

    /** @param array<string, mixed> $values */
    public static function saveMany(array $values): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(['key' => $key], ['value' => $value === null ? null : (string) $value]);
        }
        Cache::forget('settings.all');
    }
}
