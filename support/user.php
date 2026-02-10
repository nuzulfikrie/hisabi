<?php

declare(strict_types=1);

use App\Enums\UserRole;

if (! function_exists('is_role')) {
    /**
     * Check if current user has the specified role.
     */
    function is_role(string $value): bool
    {
        $role = UserRole::tryFrom($value);

        return $role && auth()->user()?->role === $role;
    }
}

if (! function_exists('is_admin')) {
    /**
     * Check if current user is an admin.
     */
    function is_admin(): bool
    {
        return is_role('admin');
    }
}

if (! function_exists('is_user')) {
    /**
     * Check if current user is a regular user.
     */
    function is_user(): bool
    {
        return is_role('user');
    }
}
