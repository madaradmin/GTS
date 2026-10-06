<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Incident;
use App\Models\Priority;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_is_redirected_to_login_when_accessing_dashboard(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_apoc_user_can_access_dashboard(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();

        $response = $this->actingAs($apocUser)->get('/dashboard');

        $response->assertOk();
        $response->assertViewIs('dashboard');
        $response->assertViewHasAll([
            'metrics',
            'recentIncidents',
            'immediateAttention',
            'distribution',
            'activeTechnicians',
            'currentShift',
        ]);
        $response->assertSee('Dashboard Operativo');
        $response->assertSee('Incidencias Abiertas');
        $response->assertSee('Atención Inmediata');
        $response->assertSee('Carga por Subsistema');
    }

    public function test_authenticated_gts_user_can_access_dashboard(): void
    {
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();

        $response = $this->actingAs($gtsUser)->get('/dashboard');

        $response->assertOk();
        $response->assertViewIs('dashboard');
        $response->assertSee('AIFA GTS TÉCNICO');
    }

    public function test_authenticated_admin_user_can_access_dashboard(): void
    {
        $adminUser = User::where('email', 'admin@aifa.aero')->firstOrFail();

        $response = $this->actingAs($adminUser)->get('/dashboard');

        $response->assertOk();
        $response->assertViewIs('dashboard');
    }

    public function test_inactive_user_cannot_access_dashboard(): void
    {
        $inactiveUser = User::factory()->create(['is_active' => false]);

        $response = $this->actingAs($inactiveUser)->get('/dashboard');

        $response->assertForbidden();
    }

    public function test_dashboard_metrics_calculate_accurate_incident_counts(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();

        // 1 Crítica en atención
        $critPriority = Priority::where('slug', 'critica')->firstOrFail();
        Incident::factory()->enAtencion()->create(['priority_id' => $critPriority->id]);

        // 1 Alta nueva
        $altaPriority = Priority::where('slug', 'alta')->firstOrFail();
        Incident::factory()->nueva()->create(['priority_id' => $altaPriority->id]);

        // 1 Media asignada
        $mediaPriority = Priority::where('slug', 'media')->firstOrFail();
        Incident::factory()->asignada()->create(['priority_id' => $mediaPriority->id]);

        // 1 Resuelta
        Incident::factory()->resuelta()->create([
            'resolved_at' => now(),
        ]);

        $dashboardService = app(DashboardService::class);
        $metrics = $dashboardService->getMetrics('today');

        $this->assertEquals(3, $metrics['total_open']); // Nueva + Asignada + En Atención
        $this->assertEquals(1, $metrics['open_critical']);
        $this->assertEquals(1, $metrics['open_high']);
        $this->assertEquals(1, $metrics['in_attention']);
        $this->assertEquals(1, $metrics['pending_vobo']);
        $this->assertEquals(1, $metrics['resolved_in_period']);
    }

    public function test_dashboard_immediate_attention_queue_includes_critical_and_high_incidents(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();

        $critPriority = Priority::where('slug', 'critica')->firstOrFail();
        $altaPriority = Priority::where('slug', 'alta')->firstOrFail();
        $bajaPriority = Priority::where('slug', 'baja')->firstOrFail();

        $critical = Incident::factory()->enAtencion()->create([
            'title' => 'Falla de radar primario',
            'priority_id' => $critPriority->id,
        ]);

        $high = Incident::factory()->nueva()->create([
            'title' => 'Torniquete filtro 3 bloqueado',
            'priority_id' => $altaPriority->id,
        ]);

        $low = Incident::factory()->nueva()->create([
            'title' => 'Lámpara de rack fundida',
            'priority_id' => $bajaPriority->id,
        ]);

        $response = $this->actingAs($apocUser)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Falla de radar primario');
        $response->assertSee('Torniquete filtro 3 bloqueado');
        // El ticket de baja prioridad no debe aparecer en la cola de atención inmediata
        $queue = $response->viewData('immediateAttention');
        $this->assertFalse($queue->contains('id', $low->id));
        $this->assertTrue($queue->contains('id', $critical->id));
        $this->assertTrue($queue->contains('id', $high->id));
    }

    public function test_dashboard_category_distribution_calculates_proportions(): void
    {
        $cctv = Category::where('slug', 'cctv')->firstOrFail();
        $fids = Category::where('slug', 'fids')->firstOrFail();

        // 3 incidentes CCTV activos
        Incident::factory()->count(3)->nueva()->create(['category_id' => $cctv->id]);
        // 1 incidente FIDS activo
        Incident::factory()->count(1)->enAtencion()->create(['category_id' => $fids->id]);

        $dashboardService = app(DashboardService::class);
        $distribution = $dashboardService->getCategoryDistribution();

        $this->assertEquals(4, $distribution['total_active']);

        $cctvData = collect($distribution['categories'])->firstWhere('slug', 'cctv');
        $this->assertEquals(3, $cctvData['count']);
        $this->assertEquals(75, $cctvData['percentage']);

        $fidsData = collect($distribution['categories'])->firstWhere('slug', 'fids');
        $this->assertEquals(1, $fidsData['count']);
        $this->assertEquals(25, $fidsData['percentage']);
    }

    public function test_dashboard_supports_period_filters(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();

        $responseToday = $this->actingAs($apocUser)->get('/dashboard?period=today');
        $responseToday->assertOk();
        $this->assertEquals('today', $responseToday->viewData('metrics')['period']);

        $response24h = $this->actingAs($apocUser)->get('/dashboard?period=24h');
        $response24h->assertOk();
        $this->assertEquals('24h', $response24h->viewData('metrics')['period']);

        $responseWeek = $this->actingAs($apocUser)->get('/dashboard?period=week');
        $responseWeek->assertOk();
        $this->assertEquals('week', $responseWeek->viewData('metrics')['period']);
    }

    public function test_dashboard_mttr_calculation_with_resolved_incidents(): void
    {
        // Incidencia creada hace 30 minutos y resuelta hace 10 minutos -> 20 minutos de MTTR
        Incident::factory()->resuelta()->create([
            'created_at' => now()->subMinutes(30),
            'resolved_at' => now()->subMinutes(10),
        ]);

        $dashboardService = app(DashboardService::class);
        $metrics = $dashboardService->getMetrics('today');

        $this->assertEquals(20, $metrics['mttr_minutes']);
        $this->assertTrue($metrics['mttr_within_target']); // 20 <= 25
    }
}
