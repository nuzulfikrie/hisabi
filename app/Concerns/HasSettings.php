<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\UserSetting;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasSettings
{
    /**
     * Get all user settings.
     */
    public function settings(): HasMany
    {
        return $this->hasMany(UserSetting::class);
    }

    /**
     * Get a specific setting value.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        $setting = $this->settings()->where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => (bool) $setting->value,
            'json' => json_decode($setting->value, true),
            'number' => (float) $setting->value,
            'integer' => (int) $setting->value,
            default => $setting->value,
        };
    }

    /**
     * Set a specific setting value.
     */
    public function setSetting(string $key, mixed $value, string $type = 'string'): UserSetting
    {
        $valueToStore = match ($type) {
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value),
            default => (string) $value,
        };

        return $this->settings()->updateOrCreate(
            ['key' => $key],
            ['value' => $valueToStore, 'type' => $type]
        );
    }

    /**
     * Check if a setting exists.
     */
    public function hasSetting(string $key): bool
    {
        return $this->settings()->where('key', $key)->exists();
    }

    /**
     * Delete a setting.
     */
    public function deleteSetting(string $key): bool
    {
        return $this->settings()->where('key', $key)->delete() > 0;
    }
}
