<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Incident;
use App\Models\Priority;
use App\Models\Status;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndicatorSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $apocUser;

    protected User $gtsUser;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->apocUser = User::whereHas('role', fn ($q) => $q->where('slug', 'apoc'))->firstOrFail();
        $this->gtsUser = User::whereHas('role', fn ($q) => $q->where('slug', 'gts'))->firstOrFail();
        $this->adminUser = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->firstOrFail();
    }

    public function test_unauthenticated_user_cannot_access_indicators(): void
    {
        $response = $this->get(route('indicators.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_inactive_user_cannot_access_indicators(): void
    {
        $this->apocUser->update(['is_active' => false]);

        $response = $this->actingAs($this->apocUser)->get(route('indicators.index'));
        $response->assertStatus(403);
    }

    public function test_all_authenticated_roles_can_access_indicators(): void
    {
        // 1. APOC
        $respApoc = $this->actingAs($this->apocUser)->get(route('indicators.index'));
        $respApoc->assertStatus(200);
        $respApoc->assertSee('Indicadores de Rendimiento Tecnológico');
        $respApoc->assertSee('MTTR (Resolución)');
        $respApoc->assertSee('MTTA (Primera Respuesta)');
        $respApoc->assertSee('Eficiencia de Resolución');
        $respApoc->assertSee('Salud de Infraestructura');

        // 2. GTS
        $respGts = $this->actingAs($this->gtsUser)->get(route('indicators.index'));
        $respGts->assertStatus(200);

        // 3. Admin
        $respAdmin = $this->actingAs($this->adminUser)->get(route('indicators.index'));
        $respAdmin->assertStatus(200);
    }

    public function test_indicators_show_real_zero_metrics_when_empty(): void
    {
        // Purge any existing incidents
        Incident::query()->delete();

        $response = $this->actingAs($this->adminUser)->get(route('indicators.index'));

        $response->assertStatus(200);
        $response->assertSee('0 min');
        $response->assertSee('0%');
        $response->assertDontSee('18 min'); // Ensure no mock fallback
    }

    public function test_indicators_calculate_mttr_and_efficiency_accurately_from_postgresql(): void
    {
        Incident::query()->delete();

        $cctv = Category::where('slug', 'cctv')->firstOrFail();
        $critica = Priority::where('slug', 'critica')->firstOrFail();
        $media = Priority::where('slug', 'media')->firstOrFail();
        $statusResuelta = Status::where('code', 'RESUELTA')->firstOrFail();
        $statusNueva = Status::where('code', 'NUEVA')->firstOrFail();

        $now = Carbon::today('America/Mexico_City')->setHour(14);
        Carbon::setTestNow($now);

        // Incidencia 1: Resuelta en 20 minutos
        Incident::factory()->create([
            'folio' => 'IND-2026-0001',
            'title' => 'Falla Cámara Sala A',
            'category_id' => $cctv->id,
            'priority_id' => $critica->id,
            'status_id' => $statusResuelta->id,
            'reported_by_user_id' => $this->apocUser->id,
            'assigned_to_user_id' => $this->gtsUser->id,
            'created_at' => (clone $now)->subMinutes(50),
            'attended_at' => (clone $now)->subMinutes(45), // 5 min primera respuesta
            'resolved_at' => (clone $now)->subMinutes(30), // 20 min MTTR
        ]);

        // Incidencia 2: Resuelta en 40 minutos
        Incident::factory()->create([
            'folio' => 'IND-2026-0002',
            'title' => 'Falla Switch CCTV B',
            'category_id' => $cctv->id,
            'priority_id' => $critica->id,
            'status_id' => $statusResuelta->id,
            'reported_by_user_id' => $this->apocUser->id,
            'assigned_to_user_id' => $this->gtsUser->id,
            'created_at' => (clone $now)->subMinutes(60),
            'attended_at' => (clone $now)->subMinutes(45), // 15 min primera respuesta
            'resolved_at' => (clone $now)->subMinutes(20), // 40 min MTTR
        ]);

        // Incidencia 3: Nueva sin resolver
        Incident::factory()->create([
            'folio' => 'IND-2026-0003',
            'title' => 'Cámara Desconectada',
            'category_id' => $cctv->id,
            'priority_id' => $media->id,
            'status_id' => $statusNueva->id,
            'reported_by_user_id' => $this->apocUser->id,
            'created_at' => (clone $now)->subMinutes(10),
            'attended_at' => null,
            'resolved_at' => null,
        ]);

        // MTTR esperado: (20 + 40) / 2 = 30 min
        // MTTA esperado: (5 + 15) / 2 = 10 min
        // Eficiencia: 2 de 3 = 66.7%

        $response = $this->actingAs($this->gtsUser)->get(route('indicators.index', ['period_preset' => 'today']));

        $response->assertStatus(200);
        $response->assertSee('30 min'); // MTTR
        $response->assertSee('10 min'); // MTTA
        $response->assertSee('66.7%'); // Eficiencia
        Carbon::setTestNow();
    }

    public function test_sidebar_and_dashboard_links_point_to_indicators_index(): void
    {
        // 1. Sidebar link in /incidencias
        $responseIncidents = $this->actingAs($this->gtsUser)->get(route('incidents.index'));
        $responseIncidents->assertStatus(200);
        $responseIncidents->assertSee(route('indicators.index'));

        // 2. Quick action link in /dashboard
        $responseDashboard = $this->actingAs($this->apocUser)->get(route('dashboard'));
        $responseDashboard->assertStatus(200);
        $responseDashboard->assertSee(route('indicators.index'));
    }
}
