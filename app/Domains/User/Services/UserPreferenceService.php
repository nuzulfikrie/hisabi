<?php

namespace App\Domains\User\Services;

use App\Domains\User\Models\UserPreference;

class UserPreferenceService
{
    public function getForUser(int $userId): UserPreference
    {
        return UserPreference::firstOrCreate(
            ['user_id' => $userId],
            [
                'currency' => 'USD',
                'date_format' => 'DD/MM/YYYY',
                'theme' => 'system',
                'language' => 'en',
                'default_transaction_type' => 'expense',
                'email_notifications' => true,
                'push_notifications' => true,
            ]
        );
    }

    public function update(int $userId, array $data): UserPreference
    {
        $preference = $this->getForUser($userId);
        $preference->update($data);
        return $preference->fresh();
    }
}
