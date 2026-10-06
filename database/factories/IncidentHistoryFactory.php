<?php

namespace Database\Factories;

use App\Models\Incident;
use App\Models\IncidentHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IncidentHistory>
 */
class IncidentHistoryFactory extends Factory
{
    protected $model = IncidentHistory::class;

    public function definition(): array
    {
        $actions = ['CREADA', 'ASIGNADA', 'CAMBIO_ESTADO', 'NOTA_TECNICA', 'RESUELTA', 'CERRADA'];

        return [
            'incident_id' => Incident::inRandomOrder()->value('id') ?? Incident::factory(),
            'user_id' => User::inRandomOrder()->value('id') ?? User::factory(),
            'action' => fake()->randomElement($actions),
            'field_name' => 'status_id',
            'old_value' => 'NUEVA',
            'new_value' => 'EN_ATENCION',
            'comment' => fake()->sentence(),
            'created_at' => now(),
        ];
    }
}
