<?php

namespace Database\Factories;

use App\Domains\Tag\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        $colors = [
            '#EF4444', // red
            '#F97316', // orange
            '#F59E0B', // amber
            '#84CC16', // lime
            '#10B981', // emerald
            '#06B6D4', // cyan
            '#3B82F6', // blue
            '#8B5CF6', // violet
            '#D946EF', // fuchsia
            '#F43F5E', // rose
        ];

        return [
            'uuid' => (string) Str::uuid(),
            'name' => $this->faker->unique()->word(),
            'color' => $this->faker->randomElement($colors),
            'user_id' => User::factory(),
        ];
    }
}
