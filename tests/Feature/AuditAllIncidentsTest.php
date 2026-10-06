<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Incident;
use App\Models\Priority;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditAllIncidentsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $apocUser;

    protected User $gtsUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->adminUser = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->firstOrFail();
        $this->apocUser = User::whereHas('role', fn ($q) => $q->where('slug', 'apoc'))->firstOrFail();
        $this->gtsUser = User::whereHas('role', fn ($q) => $q->where('slug', 'gts'))->firstOrFail();
    }

    /**
     * 1. Comprobar que Auditoría es exclusiva del Administrador.
     */
    public function test_audit_module_is_strictly_exclusive_to_admin(): void
    {
        // Invitado
        $this->get(route('audit.index'))->assertRedirect(route('login'));

        // APOC (403)
        $this->actingAs($this->apocUser)->get(route('audit.index'))->assertForbidden();

        // GTS (403)
        $this->actingAs($this->gtsUser)->get(route('audit.index'))->assertForbidden();

        // ADMIN (200)
        $this->actingAs($this->adminUser)->get(route('audit.index'))->assertOk();
    }

    /**
     * 2. Comprobar que Auditoría consulta y muestra TODAS las incidencias del sistema
     *    sin importar su estado (NUEVA, ASIGNADA, EN_ATENCION, RESUELTA, CERRADA).
     */
    public function test_audit_shows_all_incidents_across_all_operational_statuses(): void
    {
        $category = Category::where('is_active', true)->firstOrFail();
        $priority = Priority::where('is_active', true)->firstOrFail();

        $statusNueva = Status::where('code', 'NUEVA')->firstOrFail();
        $statusAsignada = Status::where('code', 'ASIGNADA')->firstOrFail();
        $statusAtencion = Status::where('code', 'EN_ATENCION')->firstOrFail();
        $statusResuelta = Status::where('code', 'RESUELTA')->firstOrFail();
        $statusCerrada = Status::where('code', 'CERRADA')->firstOrFail();

        // Crear una incidencia en cada estado
        $incNueva = Incident::create([
            'folio' => 'INC-AUD-NUEVA-01',
            'title' => 'Incidencia de prueba nueva en auditoría',
            'description' => 'Descripción nueva',
            'location' => 'Puerta 01',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusNueva->id,
            'reported_by_user_id' => $this->apocUser->id,
            'reported_at' => now(),
        ]);

        $incAsignada = Incident::create([
            'folio' => 'INC-AUD-ASIG-02',
            'title' => 'Incidencia asignada en auditoría',
            'description' => 'Descripción asignada',
            'location' => 'Puerta 02',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusAsignada->id,
            'reported_by_user_id' => $this->apocUser->id,
            'assigned_to_user_id' => $this->gtsUser->id,
            'reported_at' => now(),
        ]);

        $incAtencion = Incident::create([
            'folio' => 'INC-AUD-ATEN-03',
            'title' => 'Incidencia en atención en auditoría',
            'description' => 'Descripción en atención',
            'location' => 'Puerta 03',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusAtencion->id,
            'reported_by_user_id' => $this->apocUser->id,
            'assigned_to_user_id' => $this->gtsUser->id,
            'reported_at' => now(),
        ]);

        $incResuelta = Incident::create([
            'folio' => 'INC-AUD-RES-04',
            'title' => 'Incidencia resuelta en auditoría',
            'description' => 'Descripción resuelta',
            'location' => 'Puerta 04',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusResuelta->id,
            'reported_by_user_id' => $this->apocUser->id,
            'assigned_to_user_id' => $this->gtsUser->id,
            'solution_description' => 'Solventado por cuadrilla',
            'reported_at' => now(),
            'resolved_at' => now(),
        ]);

        $incCerrada = Incident::create([
            'folio' => 'INC-AUD-CERR-05',
            'title' => 'Incidencia cerrada en auditoría',
            'description' => 'Descripción cerrada terminal',
            'location' => 'Puerta 05',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusCerrada->id,
            'reported_by_user_id' => $this->apocUser->id,
            'assigned_to_user_id' => $this->gtsUser->id,
            'solution_description' => 'Cierre con VoBo',
            'reported_at' => now(),
            'closed_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('audit.index'));
        $response->assertOk();

        // Verificar que TODOS los folios están presentes en la vista
        $response->assertSee('INC-AUD-NUEVA-01');
        $response->assertSee('INC-AUD-ASIG-02');
        $response->assertSee('INC-AUD-ATEN-03');
        $response->assertSee('INC-AUD-RES-04');
        $response->assertSee('INC-AUD-CERR-05');

        // Verificar los nombres de estado presentes
        $response->assertSee($statusNueva->name);
        $response->assertSee($statusCerrada->name);
    }

    /**
     * 3. Comprobar que todas las incidencias listadas en Auditoría tienen botón de eliminación.
     */
    public function test_all_incidents_in_audit_have_admin_deletion_trigger(): void
    {
        $incident = Incident::factory()->create([
            'folio' => 'INC-DEL-AUD-99',
            'title' => 'Incidencia lista para purga administrativa',
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('audit.index'));
        $response->assertOk();

        $response->assertSee('INC-DEL-AUD-99');
        $response->assertSee('Eliminar');
        $response->assertSee(route('audit.incidents.destroy', 'INC-DEL-AUD-99'));
    }

    /**
     * 4. Comprobar que la búsqueda en Auditoría localiza incidencias por folio, título o ubicación.
     */
    public function test_audit_search_filters_incidents_accurately(): void
    {
        $incidentMatch = Incident::factory()->create([
            'folio' => 'INC-MATCH-12345',
            'title' => 'Radar Secundario de Pista con Falla Crítica',
            'location' => 'Cabecera 04L',
        ]);

        $incidentOther = Incident::factory()->create([
            'folio' => 'INC-OTHER-67890',
            'title' => 'Falla en iluminaria pasillo C',
            'location' => 'Terminal 2',
        ]);

        // Búsqueda por folio
        $respFolio = $this->actingAs($this->adminUser)->get(route('audit.index', ['search' => 'INC-MATCH-12345']));
        $respFolio->assertOk();
        $respFolio->assertSee('INC-MATCH-12345');
        $respFolio->assertDontSee('INC-OTHER-67890');

        // Búsqueda por título
        $respTitle = $this->actingAs($this->adminUser)->get(route('audit.index', ['search' => 'Radar Secundario']));
        $respTitle->assertOk();
        $respTitle->assertSee('INC-MATCH-12345');
        $respTitle->assertDontSee('INC-OTHER-67890');
    }

    /**
     * 5. Comprobar que el conmutador de pestañas permite alternar entre Expedientes y Bitácora de Eventos.
     */
    public function test_audit_index_features_both_system_records_and_event_history_tabs(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('audit.index'));
        $response->assertOk();

        // Pestañas
        $response->assertSee('Expedientes del Sistema');
        $response->assertSee('Bitácora de Trazabilidad e Historial');

        // Indicador de conjunto completo
        $response->assertSee('Conjunto completo del sistema sin exclusión de estados ni restricciones de usuario');
    }

    /**
     * 6. Comprobar paginación funcional sin omitir registros.
     */
    public function test_audit_pagination_retains_query_filters(): void
    {
        Incident::factory()->count(25)->create();

        $response = $this->actingAs($this->adminUser)->get(route('audit.index', ['incidents_page' => 2]));
        $response->assertOk();
        $response->assertViewHas('incidents');
        $this->assertEquals(2, $response->viewData('incidents')->currentPage());
    }
}
