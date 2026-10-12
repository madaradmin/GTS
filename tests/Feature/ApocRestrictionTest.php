<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApocRestrictionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_apoc_user_does_not_see_shift_controls_in_views_and_sees_mis_incidencias(): void
    {
        $apocUser = User::factory()->apoc()->create();

        // 1. Dashboard View
        $responseDashboard = $this->actingAs($apocUser)->get(route('dashboard'));
        $responseDashboard->assertOk();
        $responseDashboard->assertSee('Mis incidencias');
        $responseDashboard->assertDontSee('Disponibilidad GTS');
        $responseDashboard->assertDontSee('Turno: ACTIVO');
        $responseDashboard->assertSee('Registrar incidencia');
        if ($apocUser->hasPermission('incidents.view_all')) {
            $responseDashboard->assertSee('Todas las incidencias');
        } else {
            $responseDashboard->assertDontSee('Todas las incidencias');
        }

        // 2. Profile View
        $responseProfile = $this->actingAs($apocUser)->get(route('profile.edit'));
        $responseProfile->assertOk();
        $responseProfile->assertDontSee('Disponibilidad Operativa');
        $responseProfile->assertDontSee('Estado de Turno');
    }

    public function test_shift_status_route_does_not_exist(): void
    {
        $apocUser = User::factory()->apoc()->create();

        // Ruta de turno ya no existe
        $response = $this->actingAs($apocUser)->post('/profile/shift-status', [
            'status' => 'NO DISPONIBLE',
        ]);

        $response->assertStatus(404);
    }

    public function test_apoc_can_access_mis_incidencias_but_not_technical_assignments(): void
    {
        $apocUser = User::factory()->apoc()->create();

        // 1. Acceso permitido por ruta dedicada Mis Incidencias
        $responseDirect = $this->actingAs($apocUser)->get(route('incidents.my'));
        $responseDirect->assertOk();
        $responseDirect->assertSee('Mis Incidencias');

        // 2. Acceso permitido por query parameter reported_by
        $responseQuery = $this->actingAs($apocUser)->get(route('incidents.index', ['reported_by' => $apocUser->id]));
        $responseQuery->assertOk();

        // 3. Prohibido filtrar por asignaciones técnicas (exclusivas de GTS)
        $responseAssigned = $this->actingAs($apocUser)->get(route('incidents.index', ['assigned_to' => $apocUser->id]));
        $responseAssigned->assertStatus(403);
    }

    public function test_gts_user_retains_mis_incidencias(): void
    {
        $gtsUser = User::factory()->gts()->create();

        // 1. Visualización en Sidebar
        $response = $this->actingAs($gtsUser)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('Mis incidencias');

        $responseProfile = $this->actingAs($gtsUser)->get(route('profile.edit'));
        $responseProfile->assertOk();
        $responseProfile->assertDontSee('Disponibilidad Operativa');

        // 2. Acceso a ruta Mis Incidencias
        $responseMy = $this->actingAs($gtsUser)->get(route('incidents.my'));
        $responseMy->assertOk();
        $responseMy->assertSee('Mis Incidencias');
    }

    public function test_admin_user_retains_universal_access_to_all_features(): void
    {
        $adminUser = User::factory()->admin()->create();

        $responseDashboard = $this->actingAs($adminUser)->get(route('dashboard'));
        $responseDashboard->assertOk();
        $responseDashboard->assertSee('Mis incidencias');

        $responseMy = $this->actingAs($adminUser)->get(route('incidents.my'));
        $responseMy->assertOk();
        $responseMy->assertSee('Mis Incidencias');
    }

    public function test_auditoria_is_strictly_exclusive_to_admin(): void
    {
        $apocUser = User::factory()->apoc()->create();
        $gtsUser = User::factory()->gts()->create();
        $adminUser = User::factory()->admin()->create();

        // 1. APOC no ve la opción en sidebar ni topbar y recibe 403 al acceder
        $responseApocNav = $this->actingAs($apocUser)->get(route('dashboard'));
        $responseApocNav->assertDontSee(route('audit.index'));
        $responseApoc = $this->actingAs($apocUser)->get(route('audit.index'));
        $responseApoc->assertStatus(403);

        // 2. GTS no ve la opción en sidebar ni topbar y recibe 403 al acceder
        $responseGtsNav = $this->actingAs($gtsUser)->get(route('dashboard'));
        $responseGtsNav->assertDontSee(route('audit.index'));
        $responseGts = $this->actingAs($gtsUser)->get(route('audit.index'));
        $responseGts->assertStatus(403);

        // 3. ADMIN sí ve la opción y puede acceder
        $responseAdminNav = $this->actingAs($adminUser)->get(route('dashboard'));
        $responseAdminNav->assertSee(route('audit.index'));
        $responseAdminNav->assertSee('Auditoría');
        $responseAdmin = $this->actingAs($adminUser)->get(route('audit.index'));
        $responseAdmin->assertOk();
    }

    public function test_sidebar_footer_does_not_contain_aifa_letterhead(): void
    {
        $adminUser = User::factory()->admin()->create();

        $response = $this->actingAs($adminUser)->get(route('dashboard'));
        $response->assertOk();

        // El membrete institucional AIFA fue retirado del sidebar
        $response->assertDontSee('Dirección de Operaciones');
        // Conserva la versión del sistema
        $response->assertSee('v1.0.0');
    }
}
