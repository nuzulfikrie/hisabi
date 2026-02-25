<?php

namespace Database\Factories\Domains\Audit\Models;

use App\Domains\Audit\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $actions = ['create', 'update', 'delete', 'login', 'logout'];
        $entityTypes = ['Transaction', 'Brand', 'Category', 'User', 'Budget', 'Sms', 'Tag'];

        return [
            'id' => $this->faker->uuid(),
            'user_id' => User::factory(),
            'action' => $this->faker->randomElement($actions),
            'entity_type' => $this->faker->randomElement($entityTypes),
            'entity_id' => (string) $this->faker->numberBetween(1, 1000),
            'old_values' => null,
            'new_values' => [
                'name' => $this->faker->word(),
                'amount' => $this->faker->numberBetween(100, 10000),
            ],
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'updated_at' => fn (array $attributes) => $attributes['created_at'],
        ];
    }

    /**
     * Indicate that the audit log is for a create action.
     */
    public function createAction(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'create',
            'old_values' => null,
        ]);
    }

    /**
     * Indicate that the audit log is for an update action.
     */
    public function updateAction(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'update',
            'old_values' => [
                'name' => 'Old Name',
                'amount' => 100,
            ],
            'new_values' => [
                'name' => 'New Name',
                'amount' => 200,
            ],
        ]);
    }

    /**
     * Indicate that the audit log is for a delete action.
     */
    public function deleteAction(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'delete',
            'new_values' => null,
            'old_values' => [
                'name' => 'Deleted Item',
                'amount' => 100,
            ],
        ]);
    }

    /**
     * Indicate that the audit log is for a login action.
     */
    public function loginAction(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => 'login',
            'entity_type' => 'User',
            'old_values' => null,
            'new_values' => null,
        ]);
    }

    /**
     * Indicate that the audit log is for a system action (no user).
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
        ]);
    }
}
