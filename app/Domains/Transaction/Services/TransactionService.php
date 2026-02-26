<?php

namespace App\Domains\Transaction\Services;

use App\Domains\Transaction\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class TransactionService
{
    public function getPaginated(int $perPage = 50): LengthAwarePaginator
    {
        return QueryBuilder::for(Transaction::class)
            ->allowedFilters([
                AllowedFilter::callback('search', function ($query, $value) {
                    $query->where(function($q) use ($value) {
                        $q->where('amount', 'LIKE', "%$value%")
                            ->orWhere('note', 'LIKE', "%$value%")
                            ->orWhereHas('brand', function($builder) use($value) {
                                $builder->where('name', 'LIKE', "%$value%");
                            });
                    });
                }),
                AllowedFilter::exact('brand_id'),
                AllowedFilter::callback('category_id', function ($query, $value) {
                    $query->whereHas('brand', function($builder) use($value) {
                        $builder->where('category_id', $value);
                    });
                }),
                AllowedFilter::callback('date_from', function ($query, $value) {
                    $query->whereDate('created_at', '>=', $value);
                }),
                AllowedFilter::callback('date_to', function ($query, $value) {
                    $query->whereDate('created_at', '<=', $value);
                }),
            ])
            ->allowedIncludes(['brand.category', 'tags'])
            ->allowedSorts(['id', 'amount', 'created_at'])
            ->defaultSort('-id')
            ->with(['brand.category', 'tags'])
            ->paginate($perPage);
    }

    public function create(array $data): Transaction
    {
        $tagUuids = $data['tags'] ?? [];
        unset($data['tags']);

        $transaction = Transaction::query()->create($data);

        if (!empty($tagUuids)) {
            $transaction->syncTags($tagUuids);
        }

        return $transaction->fresh();
    }

    public function update(int $id, array $data): Transaction
    {
        $tagUuids = $data['tags'] ?? [];
        unset($data['tags']);

        $transaction = Transaction::query()->findOrFail($id);
        $transaction->update($data);

        if (array_key_exists('tags', $data) || !empty($tagUuids)) {
            $transaction->syncTags($tagUuids);
        }

        return $transaction->fresh();
    }

    public function delete(int $id): Transaction
    {
        $transaction = Transaction::query()->findOrFail($id);
        $transaction->delete();
        return $transaction;
    }
}

