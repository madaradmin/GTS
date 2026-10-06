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

class ApocRoleVisibilityPhase2Test extends TestCase
{
    use RefreshDatabase;

    protected User $ccoUser1;

    protected User $ccoUser2;

    protected User $csaUser1;

    protected User $csaUser2;

    protected User $gtsUser;

    protected User $adminUser;

    protected Incident $ccoIncident1;

    protected Incident $ccoIncident2;

    protected Incident $csaIncident1;

    protected Incident $csaIncident2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->ccoUser1 = User::factory()->apocCco()->create([
            'name' => 'Juan CCO',
            'username' => 'juan.cco',
        ]);

        $this->ccoUser2 = User::factory()->apocCco()->create([
            'name' => 'Pedro CCO',
            'username' => 'pedro.cco',
        ]);

        $this->csaUser1 = User::factory()->apocCsa()->create([
            'name' => 'Luis CSA',
            'username' => 'luis.csa',
        ]);

        $this->csaUser2 = User::factory()->apocCsa()->create([
            'name' => 'Carlos CSA',
            'username' => 'cros.csa',
        ]);

        $this->gtsUser = User::whereHas('role', fn ($q) => $q->where('slug', 'gts'))->firstOrFail();
        $this->adminUser = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->firstOrFail();

        $category = Category::first();
        $priority = Priority::first();
        $statusNueva = Status::where('code', 'NUEVA')->firstOrFail();

        // Incidencias creadas por operadores CCO
        $this->ccoIncident1 = Incident::factory()->create([
            'folio' => 'INC-2026-0101',
            'title' => 'Falla Radar Torre CCO Juan',
            'description' => 'Falla crítica radar CCO',
            'location' => 'Torre CCO Nivel 4',
            'reported_by_user_id' => $this->ccoUser1->id,
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusNueva->id,
        ]);

        $this->ccoIncident2 = Incident::factory()->create([
            'folio' => 'INC-2026-0102',
            'title' => 'Falla Servidor CCTV CCO Pedro',
            'description' => 'Falla en servidor CCTV CCO',
            'location' => 'Data Center CCO Rack 2',
            'reported_by_user_id' => $this->ccoUser2->id,
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusNueva->id,
        ]);

        // Incidencias creadas por operadores CSA
        $this->csaIncident1 = Incident::factory()->create([
            'folio' => 'INC-2026-0201',
            'title' => 'Falla Banda Equipaje CSA Luis',
            'description' => 'Falla carrusel CSA',
            'location' => 'Banda 4 Terminal CSA',
            'reported_by_user_id' => $this->csaUser1->id,
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusNueva->id,
        ]);

        $this->csaIncident2 = Incident::factory()->create([
            'folio' => 'INC-2026-0202',
            'title' => 'Falla Torniquetes CSA Carlos',
            'description' => 'Torniquetes CSA trabados',
            'location' => 'Acceso Sala A CSA',
            'reported_by_user_id' => $this->csaUser2->id,
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusNueva->id,
        ]);
    }

    public function test_apoc_cco_users_can_see_both_their_own_and_peer_cco_incidents(): void
    {
        // Juan (CCO) debe ver tanto su incidencia como la de Pedro (CCO)
        $responseJuan = $this->actingAs($this->ccoUser1)->get('/Todas las incidencias');
        $responseJuan->assertOk();
        $responseJuan->assertSee($this->ccoIncident1->folio);
        $responseJuan->assertSee($this->ccoIncident2->folio);
        $responseJuan->assertDontSee($this->csaIncident1->folio);
        $responseJuan->assertDontSee($this->csaIncident2->folio);

        $metricsJuan = $responseJuan->viewData('metrics');
        $this->assertEquals(2, $metricsJuan['total']);

        // Pedro (CCO) debe ver tanto su incidencia como la de Juan (CCO)
        $responsePedro = $this->actingAs($this->ccoUser2)->get('/Todas las incidencias');
        $responsePedro->assertOk();
        $responsePedro->assertSee($this->ccoIncident1->folio);
        $responsePedro->assertSee($this->ccoIncident2->folio);
        $responsePedro->assertDontSee($this->csaIncident1->folio);
        $responsePedro->assertDontSee($this->csaIncident2->folio);

        $metricsPedro = $responsePedro->viewData('metrics');
        $this->assertEquals(2, $metricsPedro['total']);
    }

    public function test_apoc_csa_users_can_see_both_their_own_and_peer_csa_incidents(): void
    {
        // Luis (CSA) debe ver tanto su incidencia como la de Carlos (CSA)
        $responseLuis = $this->actingAs($this->csaUser1)->get('/Todas las incidencias');
        $responseLuis->assertOk();
        $responseLuis->assertSee($this->csaIncident1->folio);
        $responseLuis->assertSee($this->csaIncident2->folio);
        $responseLuis->assertDontSee($this->ccoIncident1->folio);
        $responseLuis->assertDontSee($this->ccoIncident2->folio);

        $metricsLuis = $responseLuis->viewData('metrics');
        $this->assertEquals(2, $metricsLuis['total']);

        // Carlos (CSA) debe ver tanto su incidencia como la de Luis (CSA)
        $responseCarlos = $this->actingAs($this->csaUser2)->get('/Todas las incidencias');
        $responseCarlos->assertOk();
        $responseCarlos->assertSee($this->csaIncident1->folio);
        $responseCarlos->assertSee($this->csaIncident2->folio);
        $responseCarlos->assertDontSee($this->ccoIncident1->folio);
        $responseCarlos->assertDontSee($this->ccoIncident2->folio);

        $metricsCarlos = $responseCarlos->viewData('metrics');
        $this->assertEquals(2, $metricsCarlos['total']);
    }

    public function test_apoc_cco_user_can_view_peer_cco_incident_detail_but_not_csa(): void
    {
        // Juan (CCO) puede ver el detalle de la incidencia de Pedro (CCO)
        $responseDetailPedro = $this->actingAs($this->ccoUser1)->get(route('incidents.show', $this->ccoIncident2->folio));
        $responseDetailPedro->assertOk();
        $responseDetailPedro->assertSee('Falla Servidor CCTV CCO Pedro');

        // Juan (CCO) tiene prohibido ver el detalle de la incidencia de Luis (CSA)
        $responseDetailCsa = $this->actingAs($this->ccoUser1)->get(route('incidents.show', $this->csaIncident1->folio));
        $responseDetailCsa->assertForbidden();
    }

    public function test_apoc_peer_within_same_group_can_close_or_reject_resolution(): void
    {
        $statusResuelta = Status::where('code', 'RESUELTA')->firstOrFail();
        $this->ccoIncident1->update(['status_id' => $statusResuelta->id]);

        // Pedro (CCO) puede dar VoBo y cerrar la incidencia de Juan (CCO)
        $responseClose = $this->actingAs($this->ccoUser2)->post(route('incidents.close', $this->ccoIncident1->folio), [
            'comment' => 'Visto bueno otorgado por compañero CCO de guardia.',
        ]);
        $responseClose->assertRedirect(route('incidents.show', $this->ccoIncident1->folio));

        $statusEnAtencion = Status::where('code', 'EN_ATENCION')->firstOrFail();
        $this->ccoIncident2->update(['status_id' => $statusResuelta->id]);

        // Juan (CCO) puede rechazar la solución de la incidencia de Pedro (CCO)
        $responseReject = $this->actingAs($this->ccoUser1)->post(route('incidents.reject-resolution', $this->ccoIncident2->folio), [
            'rejection_reason' => 'Prueba operativa insatisfactoria reportada por CCO.',
        ]);
        $responseReject->assertRedirect(route('incidents.show', $this->ccoIncident2->folio));

        // Pero Luis (CSA) tiene terminantemente prohibido cerrar o rechazar incidencias de CCO
        $this->ccoIncident1->update(['status_id' => $statusResuelta->id]);
        $responseCrossClose = $this->actingAs($this->csaUser1)->post(route('incidents.close', $this->ccoIncident1->folio), [
            'comment' => 'Intento ilegal cross-group',
        ]);
        $responseCrossClose->assertForbidden();
    }

    public function test_apoc_peer_within_same_group_can_download_and_upload_attachments(): void
    {
        $attachment = IncidentAttachment::create([
            'incident_id' => $this->ccoIncident1->id,
            'original_filename' => 'evidencia_juan.jpg',
            'stored_path' => 'attachments/evidencia_juan.jpg',
            'file_size_bytes' => 1024,
            'mime_type' => 'image/jpeg',
            'uploaded_by_user_id' => $this->ccoUser1->id,
        ]);

        // Pedro (CCO) puede acceder a ver/descargar el adjunto de Juan (CCO)
        $responsePeer = $this->actingAs($this->ccoUser2)->get(route('incidents.attachments.download', [
            'incident' => $this->ccoIncident1->folio,
            'attachment' => $attachment->id,
        ]));
        // Debe pasar la autorización de Gate (puede ser 404 si el archivo físico no existe en disco de test, pero no 403 Forbidden)
        $this->assertNotEquals(403, $responsePeer->getStatusCode());

        // Luis (CSA) tiene terminantemente prohibido descargar el adjunto de CCO (403 Forbidden por Policy)
        $responseCross = $this->actingAs($this->csaUser1)->get(route('incidents.attachments.download', [
            'incident' => $this->ccoIncident1->folio,
            'attachment' => $attachment->id,
        ]));
        $responseCross->assertForbidden();
    }

    public function test_gts_and_admin_retain_universal_visibility_across_all_groups(): void
    {
        // Técnico GTS ve todas las 4 incidencias
        $responseGts = $this->actingAs($this->gtsUser)->get('/Todas las incidencias');
        $responseGts->assertOk();
        $responseGts->assertSee($this->ccoIncident1->folio);
        $responseGts->assertSee($this->ccoIncident2->folio);
        $responseGts->assertSee($this->csaIncident1->folio);
        $responseGts->assertSee($this->csaIncident2->folio);

        $metricsGts = $responseGts->viewData('metrics');
        $this->assertEquals(4, $metricsGts['total']);

        // Administrador ve todas las 4 incidencias
        $responseAdmin = $this->actingAs($this->adminUser)->get('/Todas las incidencias');
        $responseAdmin->assertOk();
        $responseAdmin->assertSee($this->ccoIncident1->folio);
        $responseAdmin->assertSee($this->ccoIncident2->folio);
        $responseAdmin->assertSee($this->csaIncident1->folio);
        $responseAdmin->assertSee($this->csaIncident2->folio);

        $metricsAdmin = $responseAdmin->viewData('metrics');
        $this->assertEquals(4, $metricsAdmin['total']);
    }

    public function test_creator_user_id_is_strictly_preserved_for_audit(): void
    {
        $this->assertEquals($this->ccoUser1->id, $this->ccoIncident1->fresh()->reported_by_user_id);
        $this->assertEquals($this->ccoUser2->id, $this->ccoIncident2->fresh()->reported_by_user_id);
        $this->assertEquals($this->csaUser1->id, $this->csaIncident1->fresh()->reported_by_user_id);
        $this->assertEquals($this->csaUser2->id, $this->csaIncident2->fresh()->reported_by_user_id);
    }

    public function test_reports_and_dashboard_include_all_peer_incidents_in_same_role(): void
    {
        // Juan (CCO) en reportes ve ambas incidencias CCO
        $responseReports = $this->actingAs($this->ccoUser1)->get(route('reports.index'));
        $responseReports->assertOk();
        $responseReports->assertSee($this->ccoIncident1->folio);
        $responseReports->assertSee($this->ccoIncident2->folio);
        $responseReports->assertDontSee($this->csaIncident1->folio);
        $responseReports->assertDontSee($this->csaIncident2->folio);

        $incidents = $responseReports->viewData('incidents');
        $this->assertCount(2, $incidents);

        // Juan (CCO) en dashboard ve ambas incidencias CCO
        $responseDashboard = $this->actingAs($this->ccoUser1)->get(route('dashboard'));
        $responseDashboard->assertOk();
        $responseDashboard->assertSee($this->ccoIncident1->folio);
        $responseDashboard->assertSee($this->ccoIncident2->folio);
        $responseDashboard->assertDontSee($this->csaIncident1->folio);
        $responseDashboard->assertDontSee($this->csaIncident2->folio);

        $recentIncidents = $responseDashboard->viewData('recentIncidents');
        $this->assertCount(2, $recentIncidents);
    }
}
