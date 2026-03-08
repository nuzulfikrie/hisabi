<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Enums\UserRole;
use Illuminate\Support\Facades\Auth;

trait InteractsWithRole
{
    /**
     * Check if user has admin role.
     */
    public function hasAdminRole(): bool
    {
        return $this->checkRole(UserRole::ADMIN);
    }

    /**
     * Check if user has user role.
     */
    public function hasUserRole(): bool
    {
        return $this->checkRole(UserRole::USER);
    }

    /**
     * Check if user has accountant role.
     */
    public function hasAccountantRole(): bool
    {
        return $this->checkRole(UserRole::ACCOUNTANT);
    }

    /**
     * Check if user has any of the given roles.
     *
     * @param array<UserRole> $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->checkRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has the specified role.
     */
    protected function checkRole(UserRole $role): bool
    {
        return Auth::check() && Auth::user()->role === $role;
    }

    /**
     * Check if the model has admin role (for User model).
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }
}
