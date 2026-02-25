<?php

namespace App\Domains\Tag\Models;

use App\Models\User;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function newFactory(): Factory
    {
        return TagFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function ($tag) {
            if (empty($tag->uuid)) {
                $tag->uuid = (string) Str::uuid();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->belongsToMany(Transaction::class, 'tag_transaction', 'tag_uuid', 'transaction_id')
            ->withTimestamps();
    }

    public function transactionsCount()
    {
        return $this->transactions()->count();
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public static function search(string $query, int $userId): Builder
    {
        return static::forUser($userId)
            ->where('name', 'LIKE', "%$query%");
    }
}
