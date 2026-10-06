<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Incident;
use App\Models\Priority;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Paso5RegressionMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /*
    |--------------------------------------------------------------------------
    | 1. PRUEBAS APOC
    |--------------------------------------------------------------------------
    */
    public function test_apoc_profile_regression_suite(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();

        // 1. Dashboard
        $responseDashboard = $this->actingAs($apocUser)->get(route('dashboard'));
        $responseDashboard->assertOk();
        $responseDashboard->assertSee('Dashboard Operativo');
        $responseDashboard->assertSee('Mis incidencias');
        $responseDashboard->assertDontSee(route('audit.index'));
        $responseDashboard->assertDontSee('Disponibilidad GTS');
        $responseDashboard->assertDontSee('Turno: ACTIVO');

        // 2. Registrar incidencia (Pantalla y Envío)
        $responseCreate = $this->actingAs($apocUser)->get(route('incidents.create'));
        $responseCreate->assertOk();
        $responseCreate->assertSee('Nueva Incidencia');

        $category = Category::where('code', 'CCTV')->first() ?? Category::first();
        $priority = Priority::where('code', 'MEDIA')->first() ?? Priority::first();

        $responseStore = $this->actingAs($apocUser)->post(route('incidents.store'), [
            'title' => 'Falla en monitor CCTV APOC',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'location' => 'Sala de Última Espera Puerta 108',
            'description' => 'Falla intermitente en monitor de circuito cerrado APOC.',
        ]);
        $responseStore->assertRedirect();
        $incident = Incident::where('title', 'Falla en monitor CCTV APOC')->first();
        $this->assertNotNull($incident);
        $this->assertEquals($apocUser->id, $incident->reported_by_user_id);

        // 3. Navegación e Incidencias generales
        $responseIndex = $this->actingAs($apocUser)->get(route('incidents.index'));
        $responseIndex->assertOk();

        // 4. Bloqueo estricto de rutas de Auditoría y Disponibilidad eliminada; acceso a Mis Incidencias (FASE 3)
        $this->actingAs($apocUser)->get(route('audit.index'))->assertStatus(403);
        $this->actingAs($apocUser)->get(route('incidents.my'))->assertStatus(200);
        $this->actingAs($apocUser)->post('/profile/shift-status', [
            'status' => 'NO DISPONIBLE',
        ])->assertStatus(404);

        // 5. Notificaciones accesibles
        $this->actingAs($apocUser)->get(route('notifications.index'))->assertOk();
    }

    /*
    |--------------------------------------------------------------------------
    | 2. PRUEBAS GTS
    |--------------------------------------------------------------------------
    */
    public function test_gts_profile_regression_suite(): void
    {
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();

        // 1. Dashboard
        $responseDashboard = $this->actingAs($gtsUser)->get(route('dashboard'));
        $responseDashboard->assertOk();
        $responseDashboard->assertSee('Mis incidencias');
        $responseDashboard->assertDontSee(route('audit.index'));

        // 2. Mis incidencias (Acceso directo a la URL canónica)
        $responseMy = $this->actingAs($gtsUser)->get(route('incidents.my'));
        $responseMy->assertOk();
        $responseMy->assertSee('Mis Incidencias');

        // 3. Ruta de disponibilidad eliminada (404)
        $this->actingAs($gtsUser)->post('/profile/shift-status', [
            'status' => 'NO DISPONIBLE',
        ])->assertStatus(404);

        // 4. Bloqueo estricto de Auditoría
        $this->actingAs($gtsUser)->get(route('audit.index'))->assertStatus(403);

        // 5. Notificaciones accesibles
        $this->actingAs($gtsUser)->get(route('notifications.index'))->assertOk();
    }

    /*
    |--------------------------------------------------------------------------
    | 3. PRUEBAS ADMIN
    |--------------------------------------------------------------------------
    */
    public function test_admin_profile_regression_suite(): void
    {
        $adminUser = User::where('email', 'admin@aifa.aero')->firstOrFail();

        // 1. Dashboard
        $responseDashboard = $this->actingAs($adminUser)->get(route('dashboard'));
        $responseDashboard->assertOk();
        $responseDashboard->assertSee('Auditoría');
        $responseDashboard->assertSee(route('audit.index'));
        $responseDashboard->assertSee('Mis incidencias');

        // 2. Acceso a Auditoría (Bitácora General)
        $responseAudit = $this->actingAs($adminUser)->get(route('audit.index'));
        $responseAudit->assertOk();
        $responseAudit->assertSee('Bitácora General de Auditoría');

        // 3. Catálogos y configuración
        $responseCategories = $this->actingAs($adminUser)->get(route('catalogs.index'));
        $responseCategories->assertOk();

        // 4. Notificaciones
        $this->actingAs($adminUser)->get(route('notifications.index'))->assertOk();
    }

    /*
    |--------------------------------------------------------------------------
    | 4. PRUEBAS DE ESTRUCTURA VISUAL Y ASSETS
    |--------------------------------------------------------------------------
    */
    public function test_visual_assets_and_sidebar_structure(): void
    {
        $adminUser = User::where('email', 'admin@aifa.aero')->firstOrFail();
        $response = $this->actingAs($adminUser)->get(route('dashboard'));
        $response->assertOk();

        // 1. El bloque institucional redundante de AIFA fue removido del sidebar
        $response->assertDontSee('Dirección de Operaciones');
        $response->assertDontSee('Grupo de Seguridad Tecnológica</p>');

        // 2. Se preserva la versión y rol en la barra inferior, eliminando el badge de turno ACTIVO
        $response->assertSee('v1.0.0');
        $response->assertDontSee('🟢 ACTIVO');

        // 3. Regla global de x-cloak presente en CSS fuente
        $cssContent = file_get_contents(resource_path('css/app.css'));
        $this->assertStringContainsString('[x-cloak]', $cssContent);
        $this->assertStringContainsString('display: none !important', $cssContent);
    }
}
