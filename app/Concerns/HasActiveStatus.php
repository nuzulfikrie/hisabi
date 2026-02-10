<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Builder;

trait HasActiveStatus
{
    /**
     * Scope to get only active records.
     */
    public function scopeOnlyActive(Builder $query): Builder
    {
        return $query->where('status', UserStatus::ACTIVE);
    }

    /**
     * Scope to get only inactive records.
     */
    public function scopeOnlyInactive(Builder $query): Builder
    {
        return $query->where('status', UserStatus::INACTIVE);
    }

    /**
     * Check if the model is active.
     */
    public function isActive(): bool
    {
        return $this->status === UserStatus::ACTIVE;
    }

    /**
     * Check if the model is inactive.
     */
    public function isInactive(): bool
    {
        return ! $this->isActive();
    }

    /**
     * Activate the model.
     */
    public function activate(): void
    {
        $this->update(['status' => UserStatus::ACTIVE]);
    }

    /**
     * Deactivate the model.
     */
    public function deactivate(): void
    {
        $this->update(['status' => UserStatus::INACTIVE]);
    }
}
