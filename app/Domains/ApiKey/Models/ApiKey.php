<?php

namespace App\Domains\ApiKey\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($apiKey) {
            if (empty($apiKey->uuid)) {
                $apiKey->uuid = (string) Str::uuid();
            }
            if (empty($apiKey->key)) {
                $apiKey->key = self::generateKey();
            }
        });
    }

    public static function generateKey(): string
    {
        return 'his_' . Str::random(60);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    public function getMaskedKeyAttribute(): string
    {
        return substr($this->key, 0, 12) . '...' . substr($this->key, -4);
    }
}
