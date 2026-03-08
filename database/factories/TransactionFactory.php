<?php

namespace Database\Factories;

use App\Domains\Brand\Models\Brand;
use App\Domains\Transaction\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'amount' => $this->faker->randomFloat(2, 1, 10000),
            'brand_id' => Brand::factory(),
            'note' => $this->faker->optional()->sentence(),
            'user_id' => User::factory(),
        ];
    }

    /**
     * Set the user for the transaction.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

}
