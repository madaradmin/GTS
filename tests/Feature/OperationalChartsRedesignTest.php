<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Incident;
use App\Models\Priority;
use App\Models\Status;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalChartsRedesignTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->user = User::where('username', 'apoc')->firstOrFail();
    }

    /**
     * 1. Valida que la Matriz de Salud Operacional ha sido completamente eliminada del Dashboard y del Backend.
     */
    public function test_operational_health_matrix_is_completely_eliminated(): void
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('Matriz de salud operacional');
        $response->assertDontSee('healthMatrix');
        $response->assertViewMissing('healthMatrix');

        // Verificar que el método backend en DashboardService ha sido eliminado
        $this->assertFalse(
            method_exists(DashboardService::class, 'getOperationalHealthMatrix'),
            'El método getOperationalHealthMatrix debe estar eliminado de DashboardService.'
        );
    }

    /**
     * 2. Valida que la fila de gráficos utiliza una distribución 50% / 50% con grid-cols-1 md:grid-cols-2.
     */
    public function test_charts_row_uses_two_column_fifty_fifty_grid_layout(): void
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $content = $response->getContent();

        // Contenedor grid responsivo de 2 columnas al 50% / 50%
        $this->assertStringContainsString('grid grid-cols-1 md:grid-cols-2 gap-3', $content);

        // Bloque 1: Incidencias por tipo (50%)
        $this->assertStringContainsString('Incidencias por tipo', $content);
        $this->assertStringContainsString('Categorías AIFA', $content);

        // Bloque 2: Incidencias por prioridad (50%)
        $this->assertStringContainsString('Incidencias por prioridad', $content);
        $this->assertStringContainsString('Severidad OACI', $content);
    }

    /**
     * 3. Valida que los datos de ambos gráficos se nutren fielmente de PostgreSQL en tiempo real.
     */
    public function test_charts_data_remains_dynamic_and_reflects_postgresql_records(): void
    {
        $category = Category::where('slug', 'cctv')->firstOrFail();
        $priority = Priority::where('slug', 'alta')->firstOrFail();
        $status = Status::where('code', 'NUEVA')->firstOrFail();

        // Crear una incidencia para alterar el balance de datos
        Incident::factory()->create([
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $status->id,
            'reported_by_user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('distribution');
        $response->assertViewHas('prioritiesSummary');

        $distribution = $response->viewData('distribution');
        $prioritiesSummary = $response->viewData('prioritiesSummary');

        $this->assertGreaterThanOrEqual(1, $distribution['total_active']);
        $this->assertGreaterThanOrEqual(1, $prioritiesSummary['total']);
    }
}
