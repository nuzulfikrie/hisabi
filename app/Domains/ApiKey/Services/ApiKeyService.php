<?php

namespace App\Domains\ApiKey\Services;

use App\Domains\ApiKey\Models\ApiKey;
use Illuminate\Database\Eloquent\Collection;

class ApiKeyService
{
    public function getAllForUser(int $userId): Collection
    {
        return ApiKey::forUser($userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function create(array $data, int $userId): ApiKey
    {
        $data['user_id'] = $userId;
        return ApiKey::create($data);
    }

    public function delete(string $uuid, int $userId): ApiKey
    {
        $apiKey = ApiKey::forUser($userId)
            ->where('uuid', $uuid)
            ->firstOrFail();
        
        $apiKey->delete();
        return $apiKey;
    }

    public function findByKey(string $key): ?ApiKey
    {
        return ApiKey::where('key', $key)->first();
    }
}
