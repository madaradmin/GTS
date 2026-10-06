<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Incident;
use App\Models\Priority;
use App\Models\Status;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Incident>
 */
class IncidentFactory extends Factory
{
    protected $model = Incident::class;

    protected static int $folioCounter = 200;

    public function definition(): array
    {
        $locations = [
            'Terminal 1 - Sala B, Puerta 14',
            'Terminal 1 - Filtro de Seguridad 2',
            'Lado Aire - Pista 04R / Cabecera B',
            'Edificio Terminal - Cuarto IDF Nivel 2',
            'Plataforma Comercial - Posición 22',
            'Reclamo de Equipaje - Carrusel 4',
            'Torre de Control - Centro de Datos Secundario',
            'Aduana de Carga - Acceso Norte Puerta 08',
        ];

        $titles = [
            'Pérdida de señal en cámara domo PTZ',
            'Falla en lector de torniquete biométrico e-Gate',
            'Monitor FIDS sin actualización de vuelos',
            'Alta latencia en switch de borde perimetral',
            'Sensor magnético de puerta de rampa no reporta',
            'Intermitencia de enlace en repetidor TETRA Pista Sur',
            'Falla de inyector PoE en enlace perimetral',
            'Descalibración en lector RFID de acceso a hangares',
        ];

        $year = (int) date('Y');
        $folio = sprintf('INC-%04d-%04d', $year, fake()->unique()->numberBetween(1000, 99999));

        return [
            'folio' => $folio,
            'title' => fake()->randomElement($titles),
            'description' => fake()->paragraph(3),
            'location' => fake()->randomElement($locations),
            'category_id' => Category::inRandomOrder()->value('id') ?? Category::factory(),
            'priority_id' => Priority::inRandomOrder()->value('id') ?? Priority::factory(),
            'status_id' => Status::where('code', 'NUEVA')->value('id') ?? Status::factory(),
            'reported_by_user_id' => User::whereHas('role', fn ($q) => $q->where('slug', 'apoc'))->value('id') ?? User::factory()->apoc(),
            'assigned_to_user_id' => null,
            'solution_description' => null,
            'rejection_reason' => null,
            'reported_at' => now()->subMinutes(fake()->numberBetween(10, 300)),
            'assigned_at' => null,
            'attended_at' => null,
            'resolved_at' => null,
            'closed_at' => null,
        ];
    }

    /**
     * Incidencia en estado NUEVA.
     */
    public function nueva(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_id' => Status::where('code', 'NUEVA')->value('id') ?? Status::factory()->create(['code' => 'NUEVA', 'name' => 'Nueva']),
            'assigned_to_user_id' => null,
        ]);
    }

    /**
     * Incidencia en estado ASIGNADA a especialista GTS.
     */
    public function asignada(?User $specialist = null): static
    {
        return $this->state(function (array $attributes) use ($specialist) {
            $gtsUser = $specialist ?? User::whereHas('role', fn ($q) => $q->where('slug', 'gts'))->first() ?? User::factory()->gts()->create();

            return [
                'status_id' => Status::where('code', 'ASIGNADA')->value('id') ?? Status::factory()->create(['code' => 'ASIGNADA', 'name' => 'Asignada']),
                'assigned_to_user_id' => $gtsUser->id,
                'assigned_at' => now()->subMinutes(60),
            ];
        });
    }

    /**
     * Incidencia en estado EN ATENCIÓN.
     */
    public function enAtencion(?User $specialist = null): static
    {
        return $this->state(function (array $attributes) use ($specialist) {
            $gtsUser = $specialist ?? User::whereHas('role', fn ($q) => $q->where('slug', 'gts'))->first() ?? User::factory()->gts()->create();

            return [
                'status_id' => Status::where('code', 'EN_ATENCION')->value('id') ?? Status::factory()->create(['code' => 'EN_ATENCION', 'name' => 'En Atención']),
                'assigned_to_user_id' => $gtsUser->id,
                'assigned_at' => now()->subMinutes(45),
                'attended_at' => now()->subMinutes(30),
            ];
        });
    }

    /**
     * Incidencia en estado RESUELTA.
     */
    public function resuelta(?string $solution = null): static
    {
        return $this->state(function (array $attributes) use ($solution) {
            $gtsUser = User::whereHas('role', fn ($q) => $q->where('slug', 'gts'))->first() ?? User::factory()->gts()->create();

            return [
                'status_id' => Status::where('code', 'RESUELTA')->value('id') ?? Status::factory()->create(['code' => 'RESUELTA', 'name' => 'Resuelta']),
                'assigned_to_user_id' => $gtsUser->id,
                'assigned_at' => now()->subMinutes(60),
                'attended_at' => now()->subMinutes(45),
                'resolved_at' => now()->subMinutes(15),
                'solution_description' => $solution ?? 'Se reemplazó el hardware averiado y se verificó telemetría normal en consola.',
            ];
        });
    }

    /**
     * Incidencia en estado CERRADA.
     */
    public function cerrada(): static
    {
        return $this->state(function (array $attributes) {
            $gtsUser = User::whereHas('role', fn ($q) => $q->where('slug', 'gts'))->first() ?? User::factory()->gts()->create();

            return [
                'status_id' => Status::where('code', 'CERRADA')->value('id') ?? Status::factory()->create(['code' => 'CERRADA', 'name' => 'Cerrada', 'is_terminal' => true]),
                'assigned_to_user_id' => $gtsUser->id,
                'assigned_at' => now()->subHours(5),
                'attended_at' => now()->subHours(4),
                'resolved_at' => now()->subHours(2),
                'closed_at' => now()->subHour(),
                'solution_description' => 'Servicio reestablecido y pruebas operacionales de campo aprobadas.',
            ];
        });
    }

    /**
     * Incidencia con Prioridad Crítica.
     */
    public function critica(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority_id' => Priority::where('slug', 'critica')->value('id') ?? Priority::factory()->create(['slug' => 'critica', 'name' => 'Crítica', 'level' => 4]),
        ]);
    }
}
