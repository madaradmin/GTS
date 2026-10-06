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

class ApocIsolationPhase3Test extends TestCase
{
    use RefreshDatabase;

    protected User $ccoUser;

    protected User $csaUser;

    protected User $gtsUser;

    protected User $adminUser;

    protected Incident $ccoIncident;

    protected Incident $csaIncident;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->ccoUser = User::factory()->apocCco()->create([
            'name' => 'Operador CCO Alpha',
            'username' => 'cco.alpha',
        ]);

        $this->csaUser = User::factory()->apocCsa()->create([
            'name' => 'Operador CSA Beta',
            'username' => 'csa.beta',
        ]);

        $this->gtsUser = User::whereHas('role', fn ($q) => $q->where('slug', 'gts'))->firstOrFail();
        $this->adminUser = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->firstOrFail();

        $category = Category::first();
        $priority = Priority::first();
        $statusNueva = Status::where('code', 'NUEVA')->firstOrFail();

        $this->ccoIncident = Incident::factory()->create([
            'folio' => 'INC-2026-0001',
            'title' => 'Incidencia Exclusiva CCO Radar',
            'description' => 'Falla de radar en torre',
            'location' => 'Torre de Control CCO Nivel 5',
            'reported_by_user_id' => $this->ccoUser->id,
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusNueva->id,
        ]);

        $this->csaIncident = Incident::factory()->create([
            'folio' => 'INC-2026-0002',
            'title' => 'Incidencia Exclusiva CSA Banda Equipaje',
            'description' => 'Falla en carrusel de equipaje',
            'location' => 'Reclamo de Equipaje CSA Banda 3',
            'reported_by_user_id' => $this->csaUser->id,
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $statusNueva->id,
        ]);
    }

    public function test_apoc_cco_only_sees_their_own_incidents_in_incident_list(): void
    {
        $response = $this->actingAs($this->ccoUser)->get('/Todas las incidencias');

        $response->assertOk();
        $response->assertSee($this->ccoIncident->folio);
        $response->assertSee('Incidencia Exclusiva CCO Radar');

        // Confidencialidad estricta: NO debe ver incidencias de CSA
        $response->assertDontSee($this->csaIncident->folio);
        $response->assertDontSee('Incidencia Exclusiva CSA Banda Equipaje');

        // Métricas aisladas para CCO
        $metrics = $response->viewData('metrics');
        $this->assertEquals(1, $metrics['total']);
    }

    public function test_apoc_csa_only_sees_their_own_incidents_in_incident_list(): void
    {
        $response = $this->actingAs($this->csaUser)->get('/Todas las incidencias');

        $response->assertOk();
        $response->assertSee($this->csaIncident->folio);
        $response->assertSee('Incidencia Exclusiva CSA Banda Equipaje');

        // Confidencialidad estricta: NO debe ver incidencias de CCO
        $response->assertDontSee($this->ccoIncident->folio);
        $response->assertDontSee('Incidencia Exclusiva CCO Radar');

        // Métricas aisladas para CSA
        $metrics = $response->viewData('metrics');
        $this->assertEquals(1, $metrics['total']);
    }

    public function test_gts_and_admin_see_all_incidents_from_both_cco_and_csa(): void
    {
        // Técnico GTS
        $responseGts = $this->actingAs($this->gtsUser)->get('/Todas las incidencias');
        $responseGts->assertOk();
        $responseGts->assertSee($this->ccoIncident->folio);
        $responseGts->assertSee($this->csaIncident->folio);

        // Administrador
        $responseAdmin = $this->actingAs($this->adminUser)->get('/Todas las incidencias');
        $responseAdmin->assertOk();
        $responseAdmin->assertSee($this->ccoIncident->folio);
        $responseAdmin->assertSee($this->csaIncident->folio);
    }

    public function test_apoc_cco_cannot_view_csa_incident_detail(): void
    {
        $response = $this->actingAs($this->ccoUser)->get(route('incidents.show', $this->csaIncident->folio));

        $response->assertForbidden();
    }

    public function test_apoc_csa_cannot_view_cco_incident_detail(): void
    {
        $response = $this->actingAs($this->csaUser)->get(route('incidents.show', $this->ccoIncident->folio));

        $response->assertForbidden();
    }

    public function test_apoc_cco_cannot_close_or_reject_csa_incident(): void
    {
        $statusResuelta = Status::where('code', 'RESUELTA')->firstOrFail();
        $this->csaIncident->update(['status_id' => $statusResuelta->id]);

        // Intento de cerrar ticket de CSA por CCO
        $responseClose = $this->actingAs($this->ccoUser)->post(route('incidents.close', $this->csaIncident->folio), [
            'comment' => 'Intento ilegítimo de cierre',
        ]);
        $responseClose->assertForbidden();

        // Intento de rechazar resolución de CSA por CCO
        $responseReject = $this->actingAs($this->ccoUser)->post(route('incidents.reject-resolution', $this->csaIncident->folio), [
            'rejection_reason' => 'Intento ilegítimo de rechazo',
        ]);
        $responseReject->assertForbidden();
    }

    public function test_apoc_cco_cannot_download_attachment_of_csa_incident(): void
    {
        $attachment = IncidentAttachment::create([
            'incident_id' => $this->csaIncident->id,
            'original_filename' => 'evidencia_csa.jpg',
            'stored_path' => 'attachments/evidencia_csa.jpg',
            'file_size_bytes' => 1024,
            'mime_type' => 'image/jpeg',
            'uploaded_by_user_id' => $this->csaUser->id,
        ]);

        $response = $this->actingAs($this->ccoUser)->get(route('incidents.attachments.download', [
            'incident' => $this->csaIncident->folio,
            'attachment' => $attachment->id,
        ]));

        $response->assertForbidden();
    }

    public function test_apoc_cco_report_preview_and_pdf_export_only_includes_own_incidents(): void
    {
        // 1. Vista previa de reportes
        $response = $this->actingAs($this->ccoUser)->get(route('reports.index'));
        $response->assertOk();
        $response->assertSee($this->ccoIncident->folio);
        $response->assertDontSee($this->csaIncident->folio);

        $incidents = $response->viewData('incidents');
        $this->assertCount(1, $incidents);
        $this->assertEquals($this->ccoIncident->id, $incidents->first()->id);

        // 2. Exportación PDF intentando pasar la ID de CSA
        $responsePdf = $this->actingAs($this->ccoUser)->get(route('reports.pdf', [
            'selected_ids' => $this->csaIncident->id,
        ]));

        // No debe retornar PDF de la incidencia de CSA; debe rechazar con aviso o retornar vacío de CSA
        $responsePdf->assertRedirect();
        $responsePdf->assertSessionHas('search_warning');
    }

    public function test_apoc_cco_dashboard_only_reflects_own_incidents(): void
    {
        $response = $this->actingAs($this->ccoUser)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee($this->ccoIncident->folio);
        $response->assertDontSee($this->csaIncident->folio);

        $recentIncidents = $response->viewData('recentIncidents');
        $this->assertCount(1, $recentIncidents);
        $this->assertEquals($this->ccoIncident->id, $recentIncidents->first()->id);
    }

    public function test_global_search_does_not_leak_cross_group_incidents(): void
    {
        // CCO busca por término exclusivo de CSA
        $response = $this->actingAs($this->ccoUser)->get('/Todas las incidencias?search=Equipaje');

        $response->assertOk();
        $response->assertDontSee($this->csaIncident->folio);
        $response->assertDontSee('Incidencia Exclusiva CSA Banda Equipaje');
        $response->assertSee('No se encontraron incidencias');

        $incidents = $response->viewData('incidents');
        $this->assertCount(0, $incidents);
    }
}
