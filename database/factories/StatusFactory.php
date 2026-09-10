<?php

namespace Database\Factories;

use App\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Status>
 */
class StatusFactory extends Factory
{
    protected $model = Status::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('??????')),
            'name' => fake()->unique()->word(),
            'color' => fake()->hexColor(),
            'step_order' => fake()->unique()->numberBetween(1, 100),
            'is_terminal' => false,
        ];
    }
}
