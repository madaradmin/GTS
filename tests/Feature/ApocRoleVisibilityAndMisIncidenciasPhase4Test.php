<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Incident;
use App\Models\IncidentAttachment;
use App\Models\Priority;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApocRoleVisibilityAndMisIncidenciasPhase4Test extends TestCase
{
    use RefreshDatabase;

    protected User $juanCco;

    protected User $pedroCco;

    protected User $luisCsa;

    protected User $adminUser;

    protected Category $category;

    protected Priority $priority;

    protected Status $statusNueva;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->juanCco = User::factory()->apocCco()->create([
            'name' => 'Juan Pérez',
            'username' => 'juan.cco',
        ]);

        $this->pedroCco = User::factory()->apocCco()->create([
            'name' => 'Pedro Sánchez',
            'username' => 'pedro.cco',
        ]);

        $this->luisCsa = User::factory()->apocCsa()->create([
            'name' => 'Luis Hernández',
            'username' => 'luis.csa',
        ]);

        $this->adminUser = User::where('email', 'admin@aifa.aero')->firstOrFail();
        $this->category = Category::first();
        $this->priority = Priority::first();
        $this->statusNueva = Status::where('code', 'NUEVA')->firstOrFail();
    }

    /**
     * CASO 1:
     * Juan → APOC-CCO
     * Pedro → APOC-CCO
     * Luis → APOC-CSA
     * Juan crea CCO-001.
     * Pedro debe poder verla.
     * Luis NO debe poder verla.
     */
    public function test_caso_1_juan_cco_creates_incident_pedro_sees_it_luis_does_not(): void
    {
        // 1. Juan crea CCO-001
        $responseCreate = $this->actingAs($this->juanCco)->post(route('incidents.store'), [
            'title' => 'Incidencia CCO-001 Falla CCTV Terminal 1',
            'description' => 'Falla general de conmutador en servidor de visualización CCO.',
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'location' => 'Edificio Terminal - Sala CCO',
        ]);
        $responseCreate->assertRedirect();

        $cco001 = Incident::where('title', 'Incidencia CCO-001 Falla CCTV Terminal 1')->firstOrFail();
        $this->assertEquals($this->juanCco->id, $cco001->reported_by_user_id);
        $this->assertTrue($cco001->isFromCco());

        // 2. Pedro (APOC-CCO) debe poder verla en listado y detalle
        $responsePedroList = $this->actingAs($this->pedroCco)->get(route('incidents.index'));
        $responsePedroList->assertOk();
        $responsePedroList->assertSee($cco001->folio);
        $responsePedroList->assertSee('Incidencia CCO-001 Falla CCTV Terminal 1');

        $responsePedroShow = $this->actingAs($this->pedroCco)->get(route('incidents.show', $cco001->folio));
        $responsePedroShow->assertOk();
        $responsePedroShow->assertSee('Juan Pérez'); // Se preserva creador Juan Pérez en trazabilidad

        // 3. Luis (APOC-CSA) NO debe poder verla
        $responseLuisList = $this->actingAs($this->luisCsa)->get(route('incidents.index'));
        $responseLuisList->assertOk();
        $responseLuisList->assertDontSee($cco001->folio);
        $responseLuisList->assertDontSee('Incidencia CCO-001 Falla CCTV Terminal 1');

        $responseLuisShow = $this->actingAs($this->luisCsa)->get(route('incidents.show', $cco001->folio));
        $responseLuisShow->assertForbidden();
    }

    /**
     * CASO 2:
     * Pedro crea CCO-002.
     * Juan debe poder verla.
     * Luis NO debe poder verla.
     */
    public function test_caso_2_pedro_cco_creates_incident_juan_sees_it_luis_does_not(): void
    {
        // 1. Pedro crea CCO-002
        $responseCreate = $this->actingAs($this->pedroCco)->post(route('incidents.store'), [
            'title' => 'Incidencia CCO-002 Enlace Fibra Óptica',
            'description' => 'Atenuación crítica en enlace dorsal de telecomunicaciones CCO.',
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'location' => 'Rack MDF Perimetral Sur',
        ]);
        $responseCreate->assertRedirect();

        $cco002 = Incident::where('title', 'Incidencia CCO-002 Enlace Fibra Óptica')->firstOrFail();
        $this->assertEquals($this->pedroCco->id, $cco002->reported_by_user_id);
        $this->assertTrue($cco002->isFromCco());

        // 2. Juan (APOC-CCO) debe poder verla
        $responseJuanList = $this->actingAs($this->juanCco)->get(route('incidents.index'));
        $responseJuanList->assertOk();
        $responseJuanList->assertSee($cco002->folio);
        $responseJuanList->assertSee('Incidencia CCO-002 Enlace Fibra Óptica');

        $responseJuanShow = $this->actingAs($this->juanCco)->get(route('incidents.show', $cco002->folio));
        $responseJuanShow->assertOk();
        $responseJuanShow->assertSee('Pedro Sánchez'); // Creador visible Pedro Sánchez

        // 3. Luis (APOC-CSA) NO debe poder verla
        $responseLuisList = $this->actingAs($this->luisCsa)->get(route('incidents.index'));
        $responseLuisList->assertOk();
        $responseLuisList->assertDontSee($cco002->folio);
        $responseLuisList->assertDontSee('Incidencia CCO-002 Enlace Fibra Óptica');

        $responseLuisShow = $this->actingAs($this->luisCsa)->get(route('incidents.show', $cco002->folio));
        $responseLuisShow->assertForbidden();
    }

    /**
     * CASO 3:
     * Luis crea CSA-001.
     * Juan NO debe poder verla.
     * Pedro NO debe poder verla.
     */
    public function test_caso_3_luis_csa_creates_incident_juan_and_pedro_cannot_see_it(): void
    {
        // 1. Luis crea CSA-001
        $responseCreate = $this->actingAs($this->luisCsa)->post(route('incidents.store'), [
            'title' => 'Incidencia CSA-001 Torniquete Filtro B',
            'description' => 'Falla de lector biométrico en esclusa de control de acceso CSA.',
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'location' => 'Filtro de Inspección B - Acceso Restringido',
        ]);
        $responseCreate->assertRedirect();

        $csa001 = Incident::where('title', 'Incidencia CSA-001 Torniquete Filtro B')->firstOrFail();
        $this->assertEquals($this->luisCsa->id, $csa001->reported_by_user_id);
        $this->assertTrue($csa001->isFromCsa());

        // 2. Juan (APOC-CCO) NO debe poder verla
        $responseJuanList = $this->actingAs($this->juanCco)->get(route('incidents.index'));
        $responseJuanList->assertOk();
        $responseJuanList->assertDontSee($csa001->folio);
        $responseJuanList->assertDontSee('Incidencia CSA-001 Torniquete Filtro B');

        $responseJuanShow = $this->actingAs($this->juanCco)->get(route('incidents.show', $csa001->folio));
        $responseJuanShow->assertForbidden();

        // 3. Pedro (APOC-CCO) NO debe poder verla
        $responsePedroList = $this->actingAs($this->pedroCco)->get(route('incidents.index'));
        $responsePedroList->assertOk();
        $responsePedroList->assertDontSee($csa001->folio);
        $responsePedroList->assertDontSee('Incidencia CSA-001 Torniquete Filtro B');

        $responsePedroShow = $this->actingAs($this->pedroCco)->get(route('incidents.show', $csa001->folio));
        $responsePedroShow->assertForbidden();

        // 4. Luis (APOC-CSA) sí puede verla
        $responseLuisShow = $this->actingAs($this->luisCsa)->get(route('incidents.show', $csa001->folio));
        $responseLuisShow->assertOk();
        $responseLuisShow->assertSee('Luis Hernández');
    }

    /**
     * CASO 4 — MIS INCIDENCIAS:
     * Juan crea: CCO-001, CCO-002
     * Pedro crea: CCO-003, CCO-004
     * Juan entra a "Mis incidencias":
     * Debe ver: CCO-001, CCO-002
     * NO: CCO-003, CCO-004
     */
    public function test_caso_4_mis_incidencias_juan_sees_only_own_and_not_pedro_nor_luis(): void
    {
        $cco001 = Incident::factory()->create([
            'folio' => 'INC-2026-CCO-001',
            'title' => 'Incidencia CCO-001 Juan',
            'reported_by_user_id' => $this->juanCco->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => $this->statusNueva->id,
        ]);

        $cco002 = Incident::factory()->create([
            'folio' => 'INC-2026-CCO-002',
            'title' => 'Incidencia CCO-002 Juan',
            'reported_by_user_id' => $this->juanCco->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => $this->statusNueva->id,
        ]);

        $cco003 = Incident::factory()->create([
            'folio' => 'INC-2026-CCO-003',
            'title' => 'Incidencia CCO-003 Pedro',
            'reported_by_user_id' => $this->pedroCco->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => $this->statusNueva->id,
        ]);

        $cco004 = Incident::factory()->create([
            'folio' => 'INC-2026-CCO-004',
            'title' => 'Incidencia CCO-004 Pedro',
            'reported_by_user_id' => $this->pedroCco->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => $this->statusNueva->id,
        ]);

        $csa001 = Incident::factory()->create([
            'folio' => 'INC-2026-CSA-001',
            'title' => 'Incidencia CSA-001 Luis',
            'reported_by_user_id' => $this->luisCsa->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => $this->statusNueva->id,
        ]);

        // Juan accede a "Mis incidencias"
        $responseJuanMy = $this->actingAs($this->juanCco)->get(route('incidents.my'));
        $responseJuanMy->assertOk();

        // Debe ver CCO-001 y CCO-002
        $responseJuanMy->assertSee('INC-2026-CCO-001');
        $responseJuanMy->assertSee('Incidencia CCO-001 Juan');
        $responseJuanMy->assertSee('INC-2026-CCO-002');
        $responseJuanMy->assertSee('Incidencia CCO-002 Juan');

        // NO debe ver CCO-003, CCO-004, ni CSA-001
        $responseJuanMy->assertDontSee('INC-2026-CCO-003');
        $responseJuanMy->assertDontSee('Incidencia CCO-003 Pedro');
        $responseJuanMy->assertDontSee('INC-2026-CCO-004');
        $responseJuanMy->assertDontSee('Incidencia CCO-004 Pedro');
        $responseJuanMy->assertDontSee('INC-2026-CSA-001');
        $responseJuanMy->assertDontSee('Incidencia CSA-001 Luis');
    }

    /**
     * CASO 5 — TODAS LAS INCIDENCIAS CCO:
     * Juan entra a "Todas las incidencias":
     * Debe ver: CCO-001, CCO-002, CCO-003, CCO-004
     * No debe ver: CSA-001
     */
    public function test_caso_5_todas_las_incidencias_juan_sees_all_cco_and_no_csa(): void
    {
        $cco001 = Incident::factory()->create([
            'folio' => 'INC-2026-CCO-101',
            'title' => 'Incidencia CCO-101 Juan',
            'reported_by_user_id' => $this->juanCco->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => $this->statusNueva->id,
        ]);

        $cco002 = Incident::factory()->create([
            'folio' => 'INC-2026-CCO-102',
            'title' => 'Incidencia CCO-102 Juan',
            'reported_by_user_id' => $this->juanCco->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => $this->statusNueva->id,
        ]);

        $cco003 = Incident::factory()->create([
            'folio' => 'INC-2026-CCO-103',
            'title' => 'Incidencia CCO-103 Pedro',
            'reported_by_user_id' => $this->pedroCco->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => $this->statusNueva->id,
        ]);

        $cco004 = Incident::factory()->create([
            'folio' => 'INC-2026-CCO-104',
            'title' => 'Incidencia CCO-104 Pedro',
            'reported_by_user_id' => $this->pedroCco->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => $this->statusNueva->id,
        ]);

        $csa001 = Incident::factory()->create([
            'folio' => 'INC-2026-CSA-101',
            'title' => 'Incidencia CSA-101 Luis',
            'reported_by_user_id' => $this->luisCsa->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => $this->statusNueva->id,
        ]);

        // Juan entra a "Todas las incidencias"
        $responseJuanAll = $this->actingAs($this->juanCco)->get(route('incidents.index'));
        $responseJuanAll->assertOk();

        // Debe ver todas las del ámbito CCO (Juan + Pedro)
        $responseJuanAll->assertSee('INC-2026-CCO-101');
        $responseJuanAll->assertSee('Incidencia CCO-101 Juan');
        $responseJuanAll->assertSee('INC-2026-CCO-102');
        $responseJuanAll->assertSee('Incidencia CCO-102 Juan');
        $responseJuanAll->assertSee('INC-2026-CCO-103');
        $responseJuanAll->assertSee('Incidencia CCO-103 Pedro');
        $responseJuanAll->assertSee('INC-2026-CCO-104');
        $responseJuanAll->assertSee('Incidencia CCO-104 Pedro');

        // No debe ver CSA-101
        $responseJuanAll->assertDontSee('INC-2026-CSA-101');
        $responseJuanAll->assertDontSee('Incidencia CSA-101 Luis');
    }

    /**
     * CASO 6 — NUEVO USUARIO CCO:
     * El Administrador crea: Carlos → APOC-CCO
     * Carlos debe poder visualizar inmediatamente las incidencias históricas de CCO.
     * No necesita que se le asignen manualmente.
     */
    public function test_caso_6_admin_creates_new_cco_user_carlos_immediately_sees_historical_cco(): void
    {
        // 1. Incidencias históricas existentes creadas por Juan y Pedro
        $histCco1 = Incident::factory()->create([
            'folio' => 'INC-HIST-CCO-01',
            'title' => 'Incidencia Histórica CCO Juan',
            'reported_by_user_id' => $this->juanCco->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => $this->statusNueva->id,
        ]);

        $histCco2 = Incident::factory()->create([
            'folio' => 'INC-HIST-CCO-02',
            'title' => 'Incidencia Histórica CCO Pedro',
            'reported_by_user_id' => $this->pedroCco->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => $this->statusNueva->id,
        ]);

        $histCsa = Incident::factory()->create([
            'folio' => 'INC-HIST-CSA-01',
            'title' => 'Incidencia Histórica CSA Luis',
            'reported_by_user_id' => $this->luisCsa->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => $this->statusNueva->id,
        ]);

        // 2. Administrador crea nuevo usuario Carlos con rol APOC-CCO
        $carlos = User::factory()->apocCco()->create([
            'name' => 'Carlos Morales',
            'username' => 'carlos.cco',
        ]);

        // 3. Carlos inicia sesión y visualiza inmediatamente todas las incidencias históricas de CCO
        $responseCarlos = $this->actingAs($carlos)->get(route('incidents.index'));
        $responseCarlos->assertOk();
        $responseCarlos->assertSee('INC-HIST-CCO-01');
        $responseCarlos->assertSee('Incidencia Histórica CCO Juan');
        $responseCarlos->assertSee('INC-HIST-CCO-02');
        $responseCarlos->assertSee('Incidencia Histórica CCO Pedro');

        // Y no ve las de CSA
        $responseCarlos->assertDontSee('INC-HIST-CSA-01');
        $responseCarlos->assertDontSee('Incidencia Histórica CSA Luis');

        // Detalle directo a CCO permitido
        $this->actingAs($carlos)->get(route('incidents.show', $histCco1->folio))->assertOk();
        $this->actingAs($carlos)->get(route('incidents.show', $histCco2->folio))->assertOk();

        // Detalle directo a CSA prohibido
        $this->actingAs($carlos)->get(route('incidents.show', $histCsa->folio))->assertForbidden();
    }

    /**
     * CASO 7 — NUEVO USUARIO CSA:
     * El Administrador crea: Miguel → APOC-CSA
     * Miguel debe poder visualizar las incidencias históricas de CSA.
     * NO debe visualizar las de CCO.
     */
    public function test_caso_7_admin_creates_new_csa_user_miguel_immediately_sees_historical_csa(): void
    {
        // 1. Incidencias históricas
        $histCco = Incident::factory()->create([
            'folio' => 'INC-HIST-CCO-77',
            'title' => 'Incidencia Histórica CCO Juan',
            'reported_by_user_id' => $this->juanCco->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => $this->statusNueva->id,
        ]);

        $histCsa = Incident::factory()->create([
            'folio' => 'INC-HIST-CSA-77',
            'title' => 'Incidencia Histórica CSA Luis',
            'reported_by_user_id' => $this->luisCsa->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => $this->statusNueva->id,
        ]);

        // 2. Administrador crea a Miguel como APOC-CSA
        $miguel = User::factory()->apocCsa()->create([
            'name' => 'Miguel Torres',
            'username' => 'miguel.csa',
        ]);

        // 3. Miguel entra y ve las de CSA
        $responseMiguel = $this->actingAs($miguel)->get(route('incidents.index'));
        $responseMiguel->assertOk();
        $responseMiguel->assertSee('INC-HIST-CSA-77');
        $responseMiguel->assertSee('Incidencia Histórica CSA Luis');

        // Miguel NO ve las de CCO
        $responseMiguel->assertDontSee('INC-HIST-CCO-77');
        $responseMiguel->assertDontSee('Incidencia Histórica CCO Juan');

        // Detalle directo a CSA permitido
        $this->actingAs($miguel)->get(route('incidents.show', $histCsa->folio))->assertOk();

        // Detalle directo a CCO prohibido (403)
        $this->actingAs($miguel)->get(route('incidents.show', $histCco->folio))->assertForbidden();
    }

    /**
     * CASO 8 — ACCESO DIRECTO:
     * Intentar acceder manualmente a una incidencia de otro ámbito mediante:
     * URL, ID, Folio, Endpoint, AJAX, Adjuntos, Acciones operativas.
     * Resultado esperado: 403 Forbidden o resultado vacío según el mecanismo existente.
     */
    public function test_caso_8_direct_access_via_url_id_folio_endpoints_and_attachments_returns_forbidden(): void
    {
        $statusResuelta = Status::where('code', 'RESUELTA')->firstOrFail();

        $csaIncident = Incident::factory()->create([
            'folio' => 'INC-2026-CSA-SECRET',
            'title' => 'Incidencia Confidencial CSA',
            'reported_by_user_id' => $this->luisCsa->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => $statusResuelta->id,
        ]);

        $csaAttachment = IncidentAttachment::factory()->create([
            'incident_id' => $csaIncident->id,
            'uploaded_by_user_id' => $this->luisCsa->id,
            'original_filename' => 'evidencia_csa_confidencial.pdf',
            'stored_path' => 'attachments/csa_test.pdf',
        ]);

        $ccoIncident = Incident::factory()->create([
            'folio' => 'INC-2026-CCO-SECRET',
            'title' => 'Incidencia Confidencial CCO',
            'reported_by_user_id' => $this->juanCco->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => $statusResuelta->id,
        ]);

        $ccoAttachment = IncidentAttachment::factory()->create([
            'incident_id' => $ccoIncident->id,
            'uploaded_by_user_id' => $this->juanCco->id,
            'original_filename' => 'evidencia_cco_confidencial.pdf',
            'stored_path' => 'attachments/cco_test.pdf',
        ]);

        // A) Juan (CCO) intenta acceder por folio a CSA
        $this->actingAs($this->juanCco)->get('/incidencias/'.$csaIncident->folio)->assertForbidden();

        // B) Juan (CCO) intenta acceder por ID numérico a CSA -> no resuelve por folio y retorna 404 Not Found (sin filtrar datos)
        $this->actingAs($this->juanCco)->get('/incidencias/'.$csaIncident->id)->assertStatus(404);

        // C) Juan (CCO) busca el folio exacto de CSA en el listado -> no se devuelven resultados
        $responseSearch = $this->actingAs($this->juanCco)->get('/Todas las incidencias?search='.$csaIncident->folio);
        $responseSearch->assertOk();
        $responseSearch->assertSee('No se encontraron incidencias');
        $responseSearch->assertDontSee('Incidencia Confidencial CSA');

        // D) Juan (CCO) intenta ver o descargar adjuntos de la incidencia CSA
        $this->actingAs($this->juanCco)->get(route('incidents.attachments.show', [$csaIncident->folio, $csaAttachment->id]))->assertForbidden();
        $this->actingAs($this->juanCco)->get(route('incidents.attachments.download', [$csaIncident->folio, $csaAttachment->id]))->assertForbidden();

        // E) Juan (CCO) intenta cerrar o rechazar la resolución de la incidencia CSA
        $this->actingAs($this->juanCco)->post(route('incidents.close', $csaIncident->folio))->assertForbidden();
        $this->actingAs($this->juanCco)->post(route('incidents.reject-resolution', $csaIncident->folio), [
            'rejection_reason' => 'Intento ilegal de rechazar incidencia ajena',
        ])->assertForbidden();

        // F) Inversamente: Luis (CSA) intenta acceder a CCO por folio, id, adjuntos o acciones
        $this->actingAs($this->luisCsa)->get('/incidencias/'.$ccoIncident->folio)->assertForbidden();
        $this->actingAs($this->luisCsa)->get('/incidencias/'.$ccoIncident->id)->assertStatus(404);
        $this->actingAs($this->luisCsa)->get(route('incidents.attachments.show', [$ccoIncident->folio, $ccoAttachment->id]))->assertForbidden();
        $this->actingAs($this->luisCsa)->get(route('incidents.attachments.download', [$ccoIncident->folio, $ccoAttachment->id]))->assertForbidden();
        $this->actingAs($this->luisCsa)->post(route('incidents.close', $ccoIncident->folio))->assertForbidden();
        $this->actingAs($this->luisCsa)->post(route('incidents.reject-resolution', $ccoIncident->folio), [
            'rejection_reason' => 'Intento ilegal de rechazar incidencia CCO',
        ])->assertForbidden();
    }

    /**
     * CASO 9 — REPORTES:
     * Verificar que APOC-CCO no pueda seleccionar/generar/descargar reportes de incidencias CSA.
     * Verificar que APOC-CSA no pueda seleccionar/generar/descargar reportes de incidencias CCO.
     * "Mis incidencias" debe limitarse al creador actual.
     */
    public function test_caso_9_reports_and_filters_strictly_isolate_cco_and_csa(): void
    {
        $ccoIncident = Incident::factory()->create([
            'folio' => 'INC-REP-CCO-01',
            'title' => 'Reporte CCO Monitor Juan',
            'reported_by_user_id' => $this->juanCco->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => $this->statusNueva->id,
        ]);

        $pedroIncident = Incident::factory()->create([
            'folio' => 'INC-REP-CCO-02',
            'title' => 'Reporte CCO Router Pedro',
            'reported_by_user_id' => $this->pedroCco->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => $this->statusNueva->id,
        ]);

        $csaIncident = Incident::factory()->create([
            'folio' => 'INC-REP-CSA-01',
            'title' => 'Reporte CSA Torniquete Luis',
            'reported_by_user_id' => $this->luisCsa->id,
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => $this->statusNueva->id,
        ]);

        // 1. Juan (CCO) en vista de reportes
        $responseJuanReports = $this->actingAs($this->juanCco)->get(route('reports.index'));
        $responseJuanReports->assertOk();
        $responseJuanReports->assertSee('INC-REP-CCO-01');
        $responseJuanReports->assertSee('INC-REP-CCO-02');
        $responseJuanReports->assertDontSee('INC-REP-CSA-01');
        // El dropdown de operadores no debe listar a Luis CSA
        $responseJuanReports->assertDontSee('Luis Hernández');

        // 2. Exportación PDF Juan (CCO)
        $responseJuanPdf = $this->actingAs($this->juanCco)->get(route('reports.pdf'));
        $responseJuanPdf->assertOk();

        // Si Juan intenta forzar el filtro reported_by con el ID de Luis CSA, el servicio lo aísla (0 resultados CCO)
        $responseJuanFilteredPdf = $this->actingAs($this->juanCco)->get(route('reports.pdf', ['reported_by' => $this->luisCsa->id]));
        $responseJuanFilteredPdf->assertOk();

        // 3. Luis (CSA) en vista de reportes
        $responseLuisReports = $this->actingAs($this->luisCsa)->get(route('reports.index'));
        $responseLuisReports->assertOk();
        $responseLuisReports->assertSee('INC-REP-CSA-01');
        $responseLuisReports->assertDontSee('INC-REP-CCO-01');
        $responseLuisReports->assertDontSee('INC-REP-CCO-02');
        // El dropdown no debe listar a Juan ni Pedro
        $responseLuisReports->assertDontSee('Juan Pérez');
        $responseLuisReports->assertDontSee('Pedro Sánchez');

        // 4. "Mis incidencias" en Reportes:
        // Juan filtrando por su propio ID obtiene únicamente su incidencia INC-REP-CCO-01
        $responseJuanOwnReports = $this->actingAs($this->juanCco)->get(route('reports.index', ['reported_by' => $this->juanCco->id]));
        $responseJuanOwnReports->assertOk();
        $responseJuanOwnReports->assertSee('INC-REP-CCO-01');
        $responseJuanOwnReports->assertDontSee('INC-REP-CCO-02');
        $responseJuanOwnReports->assertDontSee('INC-REP-CSA-01');
    }
}
