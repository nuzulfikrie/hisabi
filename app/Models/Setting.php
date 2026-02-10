<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'value',
        'type',
        'group',
        'description',
    ];

    /**
     * Get a setting value by key with proper type casting.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => in_array($setting->value, ['1', 'true', true], true),
            'json' => json_decode($setting->value, true),
            'number' => (float) $setting->value,
            'integer' => (int) $setting->value,
            default => $setting->value,
        };
    }

    /**
     * Set a setting value.
     */
    public static function set(string $key, mixed $value): void
    {
        $setting = static::where('key', $key)->first();

        if ($setting) {
            $valueToStore = match ($setting->type ?? 'string') {
                'boolean' => $value ? '1' : '0',
                'json' => is_array($value) ? json_encode($value) : $value,
                default => (string) $value,
            };

            $setting->update(['value' => $valueToStore]);
        }
    }

    /**
     * Get all settings by group.
     *
     * @return array<string, mixed>
     */
    public static function getGroup(string $group): array
    {
        return static::where('group', $group)
            ->get()
            ->mapWithKeys(fn ($setting) => [$setting->key => $setting->value])
            ->toArray();
    }
}
