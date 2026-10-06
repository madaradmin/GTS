<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Incident;
use App\Models\Priority;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardRealTimeMetricsTransitionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Valida que las transiciones de estado en tiempo real impacten exactamente
     * los 5 KPIs del Dashboard (Total, Nuevas, En proceso, Resueltas, Críticas)
     * a través de todo el ciclo de vida: Creación -> Asignación -> Atención -> Resolución.
     */
    public function test_kpis_reflect_realtime_incident_state_transitions(): void
    {
        $dashboardService = app(DashboardService::class);
        $apocOperator = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $gtsSupervisor = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $gtsSpecialist = User::where('email', 'gts.mendoza@aifa.aero')->firstOrFail();
        $category = Category::firstOrFail();
        $criticalPriority = Priority::where('slug', 'critica')->firstOrFail();

        // ---------------------------------------------------------------------
        // ESTADO 0: MÉTRICAS BASE
        // ---------------------------------------------------------------------
        $m0 = $dashboardService->getMetrics('today');
        $initialTotal = $m0['total'];
        $initialNew = $m0['new'];
        $initialInProcess = $m0['in_process'];
        $initialResolved = $m0['resolved'];
        $initialCritical = $m0['critical'];

        // ---------------------------------------------------------------------
        // TRANSICIÓN 1: CREACIÓN DE INCIDENCIA CRÍTICA (NUEVA)
        // ---------------------------------------------------------------------
        $createResponse = $this->actingAs($apocOperator)->post(route('incidents.store'), [
            'title' => 'Falla en Circuito CCTV Sala de Espera A',
            'description' => 'Cámaras perimetrales sin señal de video en consola central APOC.',
            'category_id' => $category->id,
            'priority_id' => $criticalPriority->id,
            'location' => 'Edificio Terminal - Nivel 2',
        ]);
        $createResponse->assertRedirect();

        $incident = Incident::where('category_id', $category->id)->latest('id')->firstOrFail();
        $this->assertEquals('NUEVA', $incident->status->code);

        $m1 = $dashboardService->getMetrics('today');
        $this->assertEquals($initialTotal + 1, $m1['total'], 'Total debe incrementarse en 1 tras la creación.');
        $this->assertEquals($initialNew + 1, $m1['new'], 'Nuevas debe incrementarse en 1 tras la creación.');
        $this->assertEquals($initialInProcess, $m1['in_process'], 'En proceso no debe cambiar en la creación.');
        $this->assertEquals($initialResolved, $m1['resolved'], 'Resueltas no debe cambiar en la creación.');
        $this->assertEquals($initialCritical + 1, $m1['critical'], 'Críticas debe incrementarse en 1 para prioridad crítica.');

        // Verificar renderizado en la vista del Dashboard
        $dashResponse1 = $this->actingAs($apocOperator)->get(route('dashboard'));
        $dashResponse1->assertOk();
        $dashMetrics1 = $dashResponse1->viewData('metrics');
        $this->assertEquals($m1['total'], $dashMetrics1['total']);
        $this->assertEquals($m1['new'], $dashMetrics1['new']);
        $this->assertEquals($m1['critical'], $dashMetrics1['critical']);

        // ---------------------------------------------------------------------
        // TRANSICIÓN 2: ASIGNACIÓN TÉCNICA (ASIGNADA -> EN PROCESO)
        // ---------------------------------------------------------------------
        $assignResponse = $this->actingAs($gtsSupervisor)->post(
            route('incidents.assign', $incident->folio),
            ['assigned_to_user_id' => $gtsSpecialist->id]
        );
        $assignResponse->assertRedirect();

        $incident->refresh();
        $this->assertEquals('ASIGNADA', $incident->status->code);

        $m2 = $dashboardService->getMetrics('today');
        $this->assertEquals($initialTotal + 1, $m2['total'], 'Total se mantiene.');
        $this->assertEquals($initialNew, $m2['new'], 'Nuevas decrece en 1 al asignarse.');
        $this->assertEquals($initialInProcess + 1, $m2['in_process'], 'En proceso se incrementa en 1 (ASIGNADA cuenta como en proceso).');
        $this->assertEquals($initialResolved, $m2['resolved'], 'Resueltas se mantiene.');
        $this->assertEquals($initialCritical + 1, $m2['critical'], 'Críticas activas se mantiene.');

        // ---------------------------------------------------------------------
        // TRANSICIÓN 3: INICIO DE ATENCIÓN EN CAMPO (EN_ATENCION)
        // ---------------------------------------------------------------------
        $startResponse = $this->actingAs($gtsSpecialist)->post(
            route('incidents.start-attention', $incident->folio)
        );
        $startResponse->assertRedirect();

        $incident->refresh();
        $this->assertEquals('EN_ATENCION', $incident->status->code);

        $m3 = $dashboardService->getMetrics('today');
        $this->assertEquals($initialTotal + 1, $m3['total'], 'Total se mantiene.');
        $this->assertEquals($initialNew, $m3['new'], 'Nuevas se mantiene.');
        $this->assertEquals($initialInProcess + 1, $m3['in_process'], 'En proceso se mantiene activo.');
        $this->assertEquals($initialResolved, $m3['resolved'], 'Resueltas se mantiene.');
        $this->assertEquals($initialCritical + 1, $m3['critical'], 'Críticas activas se mantiene.');

        // ---------------------------------------------------------------------
        // TRANSICIÓN 4: RESOLUCIÓN TÉCNICA (RESUELTA)
        // ---------------------------------------------------------------------
        $resolveResponse = $this->actingAs($gtsSpecialist)->post(
            route('incidents.resolve', $incident->folio),
            ['solution_description' => 'Sustitución de puerto PoE en switch de distribución y reanudación de enlace de video.']
        );
        $resolveResponse->assertRedirect();

        $incident->refresh();
        $this->assertEquals('RESUELTA', $incident->status->code);

        $m4 = $dashboardService->getMetrics('today');
        $this->assertEquals($initialTotal + 1, $m4['total'], 'Total se mantiene.');
        $this->assertEquals($initialNew, $m4['new'], 'Nuevas se mantiene en nivel base.');
        $this->assertEquals($initialInProcess, $m4['in_process'], 'En proceso decrece en 1 al resolverse.');
        $this->assertEquals($initialResolved + 1, $m4['resolved'], 'Resueltas se incrementa en 1.');
        $this->assertEquals($initialCritical, $m4['critical'], 'Críticas activas decrece en 1 (ya no está activa).');

        // Verificar vista final del Dashboard
        $dashResponseFinal = $this->actingAs($apocOperator)->get(route('dashboard'));
        $dashResponseFinal->assertOk();
        $dashMetricsFinal = $dashResponseFinal->viewData('metrics');
        $this->assertEquals($m4['total'], $dashMetricsFinal['total']);
        $this->assertEquals($m4['in_process'], $dashMetricsFinal['in_process']);
        $this->assertEquals($m4['resolved'], $dashMetricsFinal['resolved']);
        $this->assertEquals($m4['critical'], $dashMetricsFinal['critical']);
    }
}
