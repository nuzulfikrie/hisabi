<?php

namespace App\Domains\Tag\Services;

use App\Domains\Tag\Models\Tag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class TagService
{
    public function getPaginated(int $userId, int $perPage = 50): LengthAwarePaginator
    {
        return QueryBuilder::for(Tag::class)
            ->allowedFilters([
                AllowedFilter::callback('search', function ($query, $value) {
                    $query->where('name', 'LIKE', "%$value%");
                }),
            ])
            ->allowedSorts(['name', 'created_at'])
            ->defaultSort('name')
            ->forUser($userId)
            ->withCount('transactions')
            ->paginate($perPage);
    }

    public function getAll(int $userId): Collection
    {
        return Tag::forUser($userId)
            ->withCount('transactions')
            ->orderBy('name')
            ->get();
    }

    public function findByUuid(string $uuid, int $userId): Tag
    {
        return Tag::forUser($userId)
            ->withCount('transactions')
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    public function create(array $data, int $userId): Tag
    {
        $data['user_id'] = $userId;
        return Tag::create($data);
    }

    public function update(string $uuid, array $data, int $userId): Tag
    {
        $tag = $this->findByUuid($uuid, $userId);
        $tag->update($data);
        return $tag;
    }

    public function delete(string $uuid, int $userId): Tag
    {
        $tag = $this->findByUuid($uuid, $userId);
        $tag->delete();
        return $tag;
    }

    public function getTaggedTransactions(string $uuid, int $userId, int $perPage = 50): LengthAwarePaginator
    {
        $tag = $this->findByUuid($uuid, $userId);
        return $tag->transactions()
            ->with(['brand.category'])
            ->paginate($perPage);
    }
}
