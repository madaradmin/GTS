<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Paso6FinalRegressionMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * 1. Perfil APOC: Verificación de Dashboard, 5 KPIs dinámicos, Sidebar sin x-cloak y navegación permitida.
     */
    public function test_apoc_profile_dashboard_and_layout_regression(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();

        $response = $this->actingAs($apocUser)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewIs('dashboard');
        $response->assertViewHasAll([
            'metrics',
            'recentIncidents',
            'immediateAttention',
            'distribution',
            'activeTechnicians',
            'currentShift',
            'prioritiesSummary',
            'recentActivities',
        ]);

        // Verificación de los 5 KPIs
        $response->assertSee('Total incidencias');
        $response->assertSee('Nuevas');
        $response->assertSee('En proceso');
        $response->assertSee('Resueltas');
        $response->assertSee('Críticas');

        // Verificación de Gráficos Operativos en Fila B al 50% / 50% y eliminación de matriz
        $response->assertSee('Incidencias por tipo');
        $response->assertDontSee('Matriz de salud operacional');
        $response->assertSee('Incidencias por prioridad');

        // Verificación del layout: Sidebar flotante con despliegue por hover y zona sensible
        $content = $response->getContent();
        $this->assertStringContainsString('fixed inset-y-0 left-0 z-40 top-16 w-64', $content);
        $this->assertStringContainsString('transition-transform duration-200', $content);
        $this->assertStringContainsString('fixed top-16 bottom-0 left-0 w-3.5', $content);
        $this->assertStringNotContainsString('<aside x-cloak', $content);

        // Menús permitidos para APOC
        $response->assertSee('Registrar incidencia');
        $response->assertSee('Mis incidencias');
        if ($apocUser->hasPermission('incidents.view_all')) {
            $response->assertSee('Todas las incidencias');
        } else {
            $response->assertDontSee('Todas las incidencias');
        }
        $response->assertSee('Descargar reporte');

        // Restricciones: APOC no debe ver menús exclusivos de GTS o Admin
        $response->assertDontSee('Disponibilidad GTS');
        $response->assertDontSee(route('audit.index'));
    }

    /**
     * 2. Perfil GTS: Verificación de Dashboard para Supervisor y Técnico especialista.
     */
    public function test_gts_profile_dashboard_and_layout_regression(): void
    {
        $gtsSupervisor = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $gtsSpecialist = User::where('email', 'gts.mendoza@aifa.aero')->firstOrFail();

        // Supervisor
        $respSupervisor = $this->actingAs($gtsSupervisor)->get(route('dashboard'));
        $respSupervisor->assertOk();
        $respSupervisor->assertSee('Mis incidencias');
        $respSupervisor->assertDontSee('Disponibilidad GTS');
        $respSupervisor->assertDontSee(route('audit.index'));

        // Especialista
        $respSpecialist = $this->actingAs($gtsSpecialist)->get(route('dashboard'));
        $respSpecialist->assertOk();
        $respSpecialist->assertSee('Mis incidencias');
        $respSpecialist->assertDontSee('Disponibilidad GTS');
        $respSpecialist->assertDontSee(route('audit.index'));
    }

    /**
     * 3. Perfil ADMIN: Verificación de Dashboard y acceso completo a módulos institucionales.
     */
    public function test_admin_profile_dashboard_and_layout_regression(): void
    {
        $adminUser = User::where('email', 'admin@aifa.aero')->firstOrFail();

        $response = $this->actingAs($adminUser)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Total incidencias');
        $response->assertSee('Nuevas');
        $response->assertSee('En proceso');
        $response->assertSee('Resueltas');
        $response->assertSee('Críticas');

        // Acceso administrativo
        $response->assertSee(route('audit.index'));
        $response->assertSee(route('catalogs.index'));
    }

    /**
     * 4. Verificación de precisión numérica: Los 3 perfiles ven los mismos valores reales de PostgreSQL.
     */
    public function test_all_profiles_see_consistent_dynamic_metrics(): void
    {
        $apoc = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $gts = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $admin = User::where('email', 'admin@aifa.aero')->firstOrFail();

        $metrics = app(DashboardService::class)->getMetrics('today');

        $rApoc = $this->actingAs($apoc)->get(route('dashboard'));
        $rGts = $this->actingAs($gts)->get(route('dashboard'));
        $rAdmin = $this->actingAs($admin)->get(route('dashboard'));

        $this->assertEquals($metrics['total'], $rApoc->viewData('metrics')['total']);
        $this->assertEquals($metrics['total'], $rGts->viewData('metrics')['total']);
        $this->assertEquals($metrics['total'], $rAdmin->viewData('metrics')['total']);

        $this->assertEquals($metrics['new'], $rApoc->viewData('metrics')['new']);
        $this->assertEquals($metrics['in_process'], $rApoc->viewData('metrics')['in_process']);
        $this->assertEquals($metrics['resolved'], $rApoc->viewData('metrics')['resolved']);
        $this->assertEquals($metrics['critical'], $rApoc->viewData('metrics')['critical']);
    }
}
