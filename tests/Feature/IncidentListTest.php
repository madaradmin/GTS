<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Incident;
use App\Models\Priority;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncidentListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_is_redirected_to_login_when_accessing_incident_list(): void
    {
        $response = $this->get('/Todas las incidencias');

        $response->assertRedirect('/login');
    }

    public function test_legacy_incidencias_url_redirects_to_todas_las_incidencias(): void
    {
        $user = User::where('email', 'apoc@aifa.aero')->firstOrFail();

        $response = $this->actingAs($user)->get('/incidencias?search=INC-2026-0888');

        $response->assertRedirect('/Todas%20las%20incidencias?search=INC-2026-0888');
    }

    public function test_apoc_user_can_access_incident_list_and_sees_new_button(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();

        $response = $this->actingAs($apocUser)->get('/Todas las incidencias');

        $response->assertOk();
        $response->assertSee('Todas las Incidencias');
        $response->assertSee('Nueva Incidencia');
        $response->assertViewHas(['incidents', 'categories', 'priorities', 'statuses', 'metrics']);
    }

    public function test_gts_user_can_access_incident_list_and_sees_new_incident_button(): void
    {
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();

        $response = $this->actingAs($gtsUser)->get('/Todas las incidencias');

        $response->assertOk();
        $response->assertSee('Todas las Incidencias');
        // GTS ahora tiene la habilidad 'create' según IncidentPolicy
        $response->assertSee('>Nueva Incidencia<', false);
    }

    public function test_inactive_user_cannot_access_incident_list(): void
    {
        $inactiveUser = User::factory()->inactive()->create();

        $response = $this->actingAs($inactiveUser)->get('/Todas las incidencias');

        $response->assertForbidden();
    }

    public function test_search_filter_by_folio_returns_matched_incident(): void
    {
        $user = User::where('email', 'apoc@aifa.aero')->firstOrFail();

        $inc1 = Incident::factory()->create([
            'folio' => 'INC-2026-0888',
            'title' => 'Falla en switch PoE Torre de Control',
        ]);
        $inc2 = Incident::factory()->create([
            'folio' => 'INC-2026-0999',
            'title' => 'Cámara térmica perimetral sin video',
        ]);

        $response = $this->actingAs($user)->get('/Todas las incidencias?search=INC-2026-0888');

        $response->assertOk();
        $response->assertSee('INC-2026-0888');
        $response->assertDontSee('INC-2026-0999');
    }

    public function test_search_filter_by_location_or_title(): void
    {
        $user = User::where('email', 'apoc@aifa.aero')->firstOrFail();

        $inc1 = Incident::factory()->create([
            'location' => 'Edificio Terminal Puerta 14B',
            'title' => 'Torniquete atascado',
        ]);
        $inc2 = Incident::factory()->create([
            'location' => 'Plataforma Remota Eco',
            'title' => 'FIDS sin señal de video',
        ]);

        $response = $this->actingAs($user)->get('/Todas las incidencias?search=Puerta 14B');

        $response->assertOk();
        $response->assertSee('Puerta 14B');
        $response->assertDontSee('Plataforma Remota Eco');
    }

    public function test_filter_by_status_returns_only_matching_incidents(): void
    {
        $user = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $statusNueva = Status::where('code', 'NUEVA')->firstOrFail();
        $statusCerrada = Status::where('code', 'CERRADA')->firstOrFail();

        $incNueva = Incident::factory()->create([
            'status_id' => $statusNueva->id,
            'title' => 'Incidencia Nueva Test',
        ]);
        $incCerrada = Incident::factory()->create([
            'status_id' => $statusCerrada->id,
            'title' => 'Incidencia Cerrada Test',
        ]);

        $response = $this->actingAs($user)->get('/Todas las incidencias?status_id='.$statusNueva->id);

        $response->assertOk();
        $response->assertSee('Incidencia Nueva Test');
        $response->assertDontSee('Incidencia Cerrada Test');
    }

    public function test_filter_by_priority_returns_only_matching_incidents(): void
    {
        $user = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $priorityCritica = Priority::where('slug', 'critica')->firstOrFail();
        $priorityBaja = Priority::where('slug', 'baja')->firstOrFail();

        $incCritica = Incident::factory()->create([
            'priority_id' => $priorityCritica->id,
            'title' => 'Servidor Central Caído',
        ]);
        $incBaja = Incident::factory()->create([
            'priority_id' => $priorityBaja->id,
            'title' => 'Lámpara de rack fundida',
        ]);

        $response = $this->actingAs($user)->get('/Todas las incidencias?priority_id='.$priorityCritica->id);

        $response->assertOk();
        $response->assertSee('Servidor Central Caído');
        $response->assertDontSee('Lámpara de rack fundida');
    }

    public function test_filter_by_category_returns_only_matching_incidents(): void
    {
        $user = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $catCctv = Category::where('slug', 'cctv')->firstOrFail();
        $catTetra = Category::where('slug', 'tetra')->firstOrFail();

        $incCctv = Incident::factory()->create([
            'category_id' => $catCctv->id,
            'title' => 'Falla en grabador NVR',
        ]);
        $incTetra = Incident::factory()->create([
            'category_id' => $catTetra->id,
            'title' => 'Interferencia en canal Bravo',
        ]);

        $response = $this->actingAs($user)->get('/Todas las incidencias?category_id='.$catCctv->id);

        $response->assertOk();
        $response->assertSee('Falla en grabador NVR');
        $response->assertDontSee('Interferencia en canal Bravo');
    }

    public function test_pagination_preserves_query_filter_parameters(): void
    {
        $user = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $catCctv = Category::where('slug', 'cctv')->firstOrFail();

        // Crear 17 incidencias en la misma categoría para forzar página 2 (15 por página)
        Incident::factory()->count(17)->create([
            'category_id' => $catCctv->id,
        ]);

        $response = $this->actingAs($user)->get('/Todas las incidencias?category_id='.$catCctv->id.'&page=2');

        $response->assertOk();
        // Verificar que los links de paginación conservan el query parameter category_id
        $response->assertSee('category_id='.$catCctv->id);
    }
}
