<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncidentRoutesStructureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * 1. Comprobar: Invitados no autenticados son redirigidos a Login desde /Todas las incidencias y /Mis incidencias.
     */
    public function test_guests_are_redirected_to_login(): void
    {
        $responseAll = $this->get('/Todas las incidencias');
        $responseAll->assertRedirect(route('login'));

        $responseMy = $this->get('/Mis incidencias');
        $responseMy->assertRedirect(route('login'));
    }

    /**
     * 2. Comprobar: APOC puede acceder tanto a /Todas las incidencias como a /Mis incidencias (habilitado en FASE 3).
     */
    public function test_apoc_access_permissions_on_both_routes(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();

        // Acceso a /Todas las incidencias
        $responseAll = $this->actingAs($apocUser)->get('/Todas las incidencias');
        $responseAll->assertOk();
        $responseAll->assertSee('Todas las Incidencias');
        $responseAll->assertSee('Nueva Incidencia');

        // Acceso a /Mis incidencias (habilitado para APOC en FASE 3)
        $responseMy = $this->actingAs($apocUser)->get('/Mis incidencias');
        $responseMy->assertOk();
    }

    /**
     * 3. Comprobar: GTS puede acceder a /Todas las incidencias y a /Mis incidencias (filtrando solo sus incidencias asignadas).
     */
    public function test_gts_access_permissions_and_scoping_on_both_routes(): void
    {
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $otherGtsUser = User::where('email', 'gts.mendoza@aifa.aero')->firstOrFail();

        $myIncident = Incident::factory()->create([
            'title' => 'Incidencia Asignada a Morales',
            'assigned_to_user_id' => $gtsUser->id,
        ]);

        $otherIncident = Incident::factory()->create([
            'title' => 'Incidencia Asignada a Ramirez',
            'assigned_to_user_id' => $otherGtsUser->id,
        ]);

        // Acceso a /Todas las incidencias
        $responseAll = $this->actingAs($gtsUser)->get('/Todas las incidencias');
        $responseAll->assertOk();
        $responseAll->assertSee('Todas las Incidencias');
        $responseAll->assertSee('Incidencia Asignada a Morales');
        $responseAll->assertSee('Incidencia Asignada a Ramirez');

        // Acceso a /Mis incidencias
        $responseMy = $this->actingAs($gtsUser)->get('/Mis incidencias');
        $responseMy->assertOk();
        $responseMy->assertSee('Mis Incidencias');
        $responseMy->assertSee('Incidencia Asignada a Morales');
        $responseMy->assertDontSee('Incidencia Asignada a Ramirez');
    }

    /**
     * 4. Comprobar: Administrador tiene acceso tanto a /Todas las incidencias como a /Mis incidencias.
     */
    public function test_admin_has_access_to_both_routes(): void
    {
        $adminUser = User::where('email', 'admin@aifa.aero')->firstOrFail();

        $responseAll = $this->actingAs($adminUser)->get('/Todas las incidencias');
        $responseAll->assertOk();
        $responseAll->assertSee('Todas las Incidencias');

        $responseMy = $this->actingAs($adminUser)->get('/Mis incidencias');
        $responseMy->assertOk();
        $responseMy->assertSee('Mis Incidencias');
    }

    /**
     * 5. Comprobar: Las URLs legadas redirigen transparentemente a las rutas canónicas deseadas.
     */
    public function test_legacy_routes_redirect_to_canonical_urls(): void
    {
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();

        // /incidencias -> /Todas las incidencias
        $responseIncidencias = $this->actingAs($gtsUser)->get('/incidencias?search=Falla');
        $responseIncidencias->assertRedirect('/Todas%20las%20incidencias?search=Falla');

        // /incidencias/mis-incidencias -> /Mis incidencias para GTS
        $responseMisIncidencias = $this->actingAs($gtsUser)->get('/incidencias/mis-incidencias');
        $responseMisIncidencias->assertRedirect('/Mis%20incidencias');

        // /incidencias/mis-incidencias -> /Mis incidencias para APOC (habilitado en FASE 3)
        $responseApocMis = $this->actingAs($apocUser)->get('/incidencias/mis-incidencias');
        $responseApocMis->assertRedirect('/Mis%20incidencias');
    }

    /**
     * 6. Comprobar: Los enlaces en el Sidebar apuntan exactamente a las rutas deseadas.
     */
    public function test_sidebar_renders_correct_urls_and_route_names(): void
    {
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();

        $response = $this->actingAs($gtsUser)->get('/Todas las incidencias');
        $response->assertOk();

        // Verifica que los hrefs contienen las rutas correctas
        $response->assertSee(route('incidents.index'));
        $response->assertSee(route('incidents.my'));
        $this->assertStringContainsString('/Todas%20las%20incidencias', route('incidents.index'));
        $this->assertStringContainsString('/Mis%20incidencias', route('incidents.my'));
    }

    /**
     * 7. Comprobar: El formulario de búsqueda/filtrado y botón limpiar en /Mis incidencias conservan la URL.
     */
    public function test_filters_and_clear_action_on_mis_incidencias_preserve_url(): void
    {
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $statusNueva = Status::where('code', 'NUEVA')->firstOrFail();

        $response = $this->actingAs($gtsUser)->get('/Mis incidencias?status_id='.$statusNueva->id);
        $response->assertOk();
        $response->assertSee('action="'.route('incidents.my').'"', false);
        $response->assertSee('href="'.route('incidents.my').'"', false);
    }

    /**
     * 8. Comprobar: Usuario inactivo no puede acceder a ninguna de las dos rutas.
     */
    public function test_inactive_user_cannot_access_either_route(): void
    {
        $inactiveUser = User::factory()->inactive()->create();

        // En /Todas las incidencias (sin middleware EnsureUserHasRole), IncidentPolicy deniega con 403
        $responseAll = $this->actingAs($inactiveUser)->get('/Todas las incidencias');
        $responseAll->assertForbidden();

        // En /Mis incidencias (protegido por IncidentPolicy::viewAny), el usuario inactivo recibe 403 Forbidden
        $responseMy = $this->actingAs($inactiveUser)->get('/Mis incidencias');
        $responseMy->assertForbidden();
    }
}
