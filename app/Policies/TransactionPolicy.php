<?php

namespace App\Policies;

use App\Domains\Transaction\Models\Transaction;
use App\Models\User;

class TransactionPolicy
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
    public function view(User $user, Transaction $transaction): bool
    {
        // Since Transaction doesn't have a direct user_id, we need to check
        // if it belongs to a brand that belongs to the user
        // For now, allow all authenticated users to view
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Transaction $transaction): bool
    {
        // For now, allow all authenticated users to update
        // In a multi-tenant system, this would check ownership
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Transaction $transaction): bool
    {
        // For now, allow all authenticated users to delete
        // In a multi-tenant system, this would check ownership
        return true;
    }
}
