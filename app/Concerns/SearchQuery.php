<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Builder;

trait SearchQuery
{
    /**
     * Search for any match (LIKE query).
     */
    public function searchAny(Builder $query, ?string $value, string $column): Builder
    {
        return $query->when($value, function ($q) use ($column, $value) {
            return $q->where($column, 'LIKE', '%'.$value.'%');
        });
    }

    /**
     * Search for exact match.
     */
    public function searchEqual(Builder $query, ?string $value, string $column): Builder
    {
        return $query->when($value !== null, function ($q) use ($column, $value) {
            return $q->where($column, '=', $value);
        });
    }

    /**
     * Search for boolean value.
     */
    public function searchBoolean(Builder $query, ?bool $value, string $column): Builder
    {
        return $query->when($value !== null, function ($q) use ($column, $value) {
            return $q->where($column, $value);
        });
    }

    /**
     * Search within date range.
     */
    public function searchWithin(
        Builder $query,
        string $column,
        ?string $startDate,
        ?string $endDate
    ): Builder {
        if (empty($startDate) || empty($endDate)) {
            return $query;
        }

        return $query->whereBetween($column, [$startDate, $endDate]);
    }

    /**
     * Search for records created within date range.
     */
    public function searchCreatedBetween(Builder $query, ?string $startDate, ?string $endDate): Builder
    {
        return $this->searchWithin($query, 'created_at', $startDate, $endDate);
    }

    /**
     * Scope for active records.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', UserStatus::ACTIVE);
    }
}
