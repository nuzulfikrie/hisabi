<?php

namespace App\Policies;

use App\Models\TelegramTransaction;
use App\Models\User;

class TelegramTransactionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TelegramTransaction $telegramTransaction): bool
    {
        return $user->id === $telegramTransaction->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false; // Created via Telegram webhook only
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TelegramTransaction $telegramTransaction): bool
    {
        return $user->id === $telegramTransaction->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TelegramTransaction $telegramTransaction): bool
    {
        return $user->id === $telegramTransaction->user_id;
    }
}
