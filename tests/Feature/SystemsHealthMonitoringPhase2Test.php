<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemsHealthMonitoringPhase2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Prueba el cálculo de umbrales con diferentes inventarios dinámicos:
     * 100 elementos (100, 95, 94, 80, 79) y 200 elementos (190).
     */
    public function test_systems_health_calculates_dynamic_thresholds_accurately(): void
    {
        $dashboardService = app(DashboardService::class);

        // 1. Caso 100 elementos: 100 activos -> 100% -> ÓPTIMO
        Category::query()->delete();
        for ($i = 1; $i <= 100; $i++) {
            Category::create([
                'name' => "Sistema {$i}",
                'slug' => "sistema-{$i}",
                'status' => 'ACTIVO',
            ]);
        }

        $health = $dashboardService->getSystemsHealth();
        $this->assertEquals(100, $health['total']);
        $this->assertEquals(100, $health['active']);
        $this->assertEquals(100.0, $health['percentage']);
        $this->assertEquals('ÓPTIMO', $health['state']);
        $this->assertEquals('#20C997', $health['color']);

        // 2. Caso 100 elementos: 95 activos -> 95% -> ÓPTIMO
        Category::where('slug', 'like', 'sistema-%')->take(5)->update(['status' => 'INACTIVO']);
        $health = $dashboardService->getSystemsHealth();
        $this->assertEquals(100, $health['total']);
        $this->assertEquals(95, $health['active']);
        $this->assertEquals(95.0, $health['percentage']);
        $this->assertEquals('ÓPTIMO', $health['state']);

        // 3. Caso 100 elementos: 94 activos -> 94% -> INTERMITENTE
        Category::where('status', 'ACTIVO')->take(1)->update(['status' => 'INACTIVO']);
        $health = $dashboardService->getSystemsHealth();
        $this->assertEquals(100, $health['total']);
        $this->assertEquals(94, $health['active']);
        $this->assertEquals(94.0, $health['percentage']);
        $this->assertEquals('INTERMITENTE', $health['state']);
        $this->assertEquals('#E0A21A', $health['color']);

        // 4. Caso 100 elementos: 80 activos -> 80% -> INTERMITENTE
        Category::query()->delete();
        for ($i = 1; $i <= 100; $i++) {
            Category::create([
                'name' => "Sistema B-{$i}",
                'slug' => "sistema-b-{$i}",
                'status' => $i <= 80 ? 'ACTIVO' : 'INACTIVO',
            ]);
        }
        $health = $dashboardService->getSystemsHealth();
        $this->assertEquals(100, $health['total']);
        $this->assertEquals(80, $health['active']);
        $this->assertEquals(80.0, $health['percentage']);
        $this->assertEquals('INTERMITENTE', $health['state']);

        // 5. Caso 100 elementos: 79 activos -> 79% -> CRÍTICO
        Category::where('status', 'ACTIVO')->take(1)->update(['status' => 'FUERA DE SERVICIO']);
        $health = $dashboardService->getSystemsHealth();
        $this->assertEquals(100, $health['total']);
        $this->assertEquals(79, $health['active']);
        $this->assertEquals(79.0, $health['percentage']);
        $this->assertEquals('CRÍTICO', $health['state']);
        $this->assertEquals('#D83B52', $health['color']);

        // 6. Caso 200 elementos: 190 activos -> 95% -> ÓPTIMO (demuestra que no hay números fijos)
        Category::query()->delete();
        for ($i = 1; $i <= 200; $i++) {
            Category::create([
                'name' => "Subsistema {$i}",
                'slug' => "subsistema-{$i}",
                'status' => $i <= 190 ? 'ACTIVO' : 'INACTIVO',
            ]);
        }
        $health = $dashboardService->getSystemsHealth();
        $this->assertEquals(200, $health['total']);
        $this->assertEquals(190, $health['active']);
        $this->assertEquals(95.0, $health['percentage']);
        $this->assertEquals('ÓPTIMO', $health['state']);
    }

    /**
     * Prueba que el inventario se actualiza dinámicamente al agregar, cambiar de estado o eliminar infraestructura.
     */
    public function test_systems_health_recalculates_on_inventory_mutations(): void
    {
        $dashboardService = app(DashboardService::class);

        Category::query()->delete();

        // 1. Inicializar con 10 sistemas activos (100% ÓPTIMO)
        for ($i = 1; $i <= 10; $i++) {
            Category::create([
                'name' => "Equipo {$i}",
                'slug' => "equipo-{$i}",
                'status' => 'ACTIVO',
            ]);
        }
        $health = $dashboardService->getSystemsHealth();
        $this->assertEquals(10, $health['total']);
        $this->assertEquals(10, $health['active']);
        $this->assertEquals(100.0, $health['percentage']);
        $this->assertEquals('ÓPTIMO', $health['state']);

        // 2. Agregar un nuevo elemento inactivo: 10/11 -> 90.9% -> INTERMITENTE
        Category::create([
            'name' => 'Equipo 11 Nuevo',
            'slug' => 'equipo-11',
            'status' => 'INACTIVO',
        ]);
        $health = $dashboardService->getSystemsHealth();
        $this->assertEquals(11, $health['total']);
        $this->assertEquals(10, $health['active']);
        $this->assertEquals(90.9, $health['percentage']);
        $this->assertEquals('INTERMITENTE', $health['state']);

        // 3. Cambiar dos elementos a FUERA DE SERVICIO: 8/11 -> 72.7% -> CRÍTICO
        Category::where('status', 'ACTIVO')->take(2)->update(['status' => 'FUERA DE SERVICIO']);
        $health = $dashboardService->getSystemsHealth();
        $this->assertEquals(11, $health['total']);
        $this->assertEquals(8, $health['active']);
        $this->assertEquals(72.7, $health['percentage']);
        $this->assertEquals('CRÍTICO', $health['state']);

        // 4. Eliminar el equipo inactivo y uno fuera de servicio: 8/9 -> 88.9% -> INTERMITENTE
        Category::where('slug', 'equipo-11')->first()->delete();
        Category::where('status', 'FUERA DE SERVICIO')->first()->delete();
        $health = $dashboardService->getSystemsHealth();
        $this->assertEquals(9, $health['total']);
        $this->assertEquals(8, $health['active']);
        $this->assertEquals(88.9, $health['percentage']);
        $this->assertEquals('INTERMITENTE', $health['state']);
    }

    /**
     * Prueba que ante un inventario vacío no hay división por cero ni se asume 100%.
     */
    public function test_systems_health_handles_empty_inventory_safely(): void
    {
        $dashboardService = app(DashboardService::class);

        Category::query()->delete();

        $health = $dashboardService->getSystemsHealth();

        $this->assertEquals(0, $health['total']);
        $this->assertEquals(0, $health['active']);
        $this->assertEquals(0.0, $health['percentage']);
        $this->assertNull($health['state']);
        $this->assertEmpty($health['systems']);
    }

    /**
     * Prueba que el Dashboard muestra el recuadro dinámico y sus elementos renderizados en Blade.
     */
    public function test_dashboard_renders_systems_health_monitoring_panel_with_real_data(): void
    {
        $admin = User::where('email', 'admin@aifa.aero')->firstOrFail();

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk();
        $response->assertViewHas('systemsHealth');

        $health = $response->viewData('systemsHealth');
        $this->assertGreaterThan(0, $health['total']);
        $this->assertNotNull($health['state']);

        // Verificar textos del panel en el HTML
        $response->assertSee('Estado de sistemas (monitoreo)');
        $response->assertSee($health['state']);
        $response->assertSee("{$health['percentage']}%");
        $response->assertSee('Ver detalle de sistemas');
        $response->assertSee(route('catalogs.index'));

        // Verificar que aparezcan los subsistemas reales de la base de datos
        $firstSystem = Category::orderBy('name')->first();
        if ($firstSystem) {
            $response->assertSee($firstSystem->name);
        }
    }
}
