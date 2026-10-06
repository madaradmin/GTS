<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\Priority;
use App\Models\Role;
use App\Models\User;
use App\Services\IndicatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PriorityColorDifferentiatingPhase3Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Prueba que el color de la prioridad Media en el dashboard es claramente distinguible,
     * utiliza el verde institucional (#10B981) y se muestra idéntico para todos los roles:
     * ADMIN, GTS, APOC-CCO y APOC-CSA.
     */
    public function test_dashboard_renders_green_color_for_media_priority_across_all_roles(): void
    {
        // 1. Obtener o crear usuarios para cada rol
        $admin = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->firstOrFail();
        $gts = User::whereHas('role', fn ($q) => $q->where('slug', 'gts'))->firstOrFail();

        $ccoRole = Role::firstOrCreate(['slug' => 'apoc-cco'], ['name' => 'APOC-CCO', 'description' => 'Centro de Control Operativo']);
        $ccoUser = User::firstOrCreate(
            ['username' => 'test.cco'],
            [
                'name' => 'Operador CCO Test',
                'email' => 'cco.test@aifa.aero',
                'password' => Hash::make('Password123!'),
                'role_id' => $ccoRole->id,
                'is_active' => true,
            ]
        );

        $csaRole = Role::firstOrCreate(['slug' => 'apoc-csa'], ['name' => 'APOC-CSA', 'description' => 'Centro de Seguridad Aeroportuaria']);
        $csaUser = User::firstOrCreate(
            ['username' => 'test.csa'],
            [
                'name' => 'Operador CSA Test',
                'email' => 'csa.test@aifa.aero',
                'password' => Hash::make('Password123!'),
                'role_id' => $csaRole->id,
                'is_active' => true,
            ]
        );

        // 2. Crear incidencias con cada prioridad
        $mediaPriority = Priority::where('slug', 'media')->firstOrFail();
        $altaPriority = Priority::where('slug', 'alta')->firstOrFail();
        $critPriority = Priority::where('slug', 'critica')->firstOrFail();
        $bajaPriority = Priority::where('slug', 'baja')->firstOrFail();

        Incident::factory()->nueva()->create(['priority_id' => $mediaPriority->id]);
        Incident::factory()->nueva()->create(['priority_id' => $altaPriority->id]);
        Incident::factory()->nueva()->create(['priority_id' => $critPriority->id]);
        Incident::factory()->nueva()->create(['priority_id' => $bajaPriority->id]);

        $rolesToTest = [
            'ADMIN' => $admin,
            'GTS' => $gts,
            'APOC-CCO' => $ccoUser,
            'APOC-CSA' => $csaUser,
        ];

        foreach ($rolesToTest as $roleName => $user) {
            $response = $this->actingAs($user)->get('/dashboard');
            $response->assertOk();

            // Confirmar que el color anterior dorado/amarillo ya NO existe en el gráfico
            $response->assertDontSee('#FACC15');
            $response->assertDontSee('#D99216');

            // Confirmar que el verde institucional (#10B981) se renderiza para Media
            $response->assertSee('#10B981');

            // Confirmar que las demás prioridades conservan sus colores
            $response->assertSee('#F59E0B'); // Alta (Naranja)
            $response->assertSee('#D83B52'); // Crítica (Rojo)
            $response->assertSee('#1E5FA7'); // Baja (Azul)
        }
    }

    /**
     * Prueba que el componente x-badge-priority renderiza verde esmeralda (#10B981 / #34D399)
     * para la prioridad Media de forma consistente y compartida.
     */
    public function test_badge_priority_component_renders_green_for_media(): void
    {
        $mediaPriority = Priority::where('slug', 'media')->firstOrFail();

        $view = $this->blade('<x-badge-priority :priority="$priority" />', ['priority' => $mediaPriority]);

        $view->assertSee('Media');
        $view->assertSee('#10B981');
        $view->assertSee('#34D399');
        $view->assertSee('rgba(16,185,129,0.15)');
        $view->assertDontSee('#E0A21A');
        $view->assertDontSee('#E2B24C');
    }

    /**
     * Prueba que IndicatorService retorna el verde (#10B981) para la prioridad media.
     */
    public function test_indicator_service_returns_green_for_media_priority(): void
    {
        $service = app(IndicatorService::class);
        $data = $service->getMetrics();

        $this->assertArrayHasKey('by_priority', $data);
        $this->assertArrayHasKey('media', $data['by_priority']);
        $this->assertEquals('#10B981', $data['by_priority']['media']['color']);
    }

    /**
     * Prueba que la vista de crear incidencia muestra el color verde (#10B981) para Media.
     */
    public function test_incident_create_view_displays_green_for_media_priority(): void
    {
        $user = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->firstOrFail();

        $response = $this->actingAs($user)->get(route('incidents.create'));

        $response->assertOk();
        $response->assertSee('text-[#10B981]');
        $response->assertSee('bg-[#10B981]');
    }
}
