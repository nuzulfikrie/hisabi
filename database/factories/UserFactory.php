<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
            'status' => UserStatus::ACTIVE,
            'role' => UserRole::USER,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Set the user as an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::ADMIN,
        ]);
    }

    /**
     * Set the user as an accountant.
     */
    public function accountant(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::ACCOUNTANT,
        ]);
    }

    /**
     * Set the user as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::INACTIVE,
        ]);
    }

    /**
     * Set the user as suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::SUSPENDED,
        ]);
    }

    /**
     * Set telegram fields for a linked user.
     */
    public function withTelegram(): static
    {
        return $this->state(fn (array $attributes) => [
            'telegram_chat_id' => (string) $this->faker->unique()->randomNumber(9),
            'telegram_username' => $this->faker->userName(),
            'telegram_verified_at' => now(),
        ]);
    }
}
