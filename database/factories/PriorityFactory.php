<?php

namespace Database\Factories;

use App\Models\Priority;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Priority>
 */
class PriorityFactory extends Factory
{
    protected $model = Priority::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'slug' => fake()->unique()->slug(1),
            'level' => fake()->unique()->numberBetween(1, 100),
            'color' => fake()->hexColor(),
            'sla_minutes' => fake()->numberBetween(15, 480),
            'is_active' => true,
        ];
    }
}
