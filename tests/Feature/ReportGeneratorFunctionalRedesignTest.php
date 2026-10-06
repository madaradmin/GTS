<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Incident;
use App\Models\IncidentHistory;
use App\Models\Priority;
use App\Models\Status;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportGeneratorFunctionalRedesignTest extends TestCase
{
    use RefreshDatabase;

    protected User $gtsUser;

    protected User $apocUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->gtsUser = User::whereHas('role', fn ($q) => $q->where('slug', 'gts'))->firstOrFail();
        $this->apocUser = User::whereHas('role', fn ($q) => $q->where('slug', 'apoc'))->firstOrFail();
    }

    /**
     * CASO 1: Seleccionar una incidencia y generar PDF (1 incidencia = 1 página principal).
     */
    public function test_caso_1_seleccionar_una_incidencia_genera_pdf_con_una_ficha(): void
    {
        $category = Category::firstOrFail();
        $priority = Priority::firstOrFail();
        $status = Status::where('code', 'NUEVA')->firstOrFail();

        $inc1 = Incident::factory()->create([
            'folio' => 'INC-2026-0101',
            'title' => 'Falla en Torniquete T1',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $status->id,
            'reported_by_user_id' => $this->apocUser->id,
        ]);

        $inc2 = Incident::factory()->create([
            'folio' => 'INC-2026-0102',
            'title' => 'Cámara PTZ sin señal',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $status->id,
            'reported_by_user_id' => $this->apocUser->id,
        ]);

        $response = $this->actingAs($this->gtsUser)->get(route('reports.pdf', [
            'selected_ids' => [$inc1->id],
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');

        // Validar que el render de la vista contiene exactamente la incidencia seleccionada
        /** @var ReportService $reportService */
        $reportService = app(ReportService::class);
        $query = $reportService->buildQuery([], [$inc1->id]);
        $incidents = $query->get();

        $this->assertCount(1, $incidents);
        $this->assertEquals('INC-2026-0101', $incidents->first()->folio);
    }

    /**
     * CASO 2: Seleccionar varias incidencias y generar PDF (cada incidencia en su propia hoja con page-break).
     */
    public function test_caso_2_seleccionar_varias_incidencias_inicia_cada_una_en_su_propia_pagina(): void
    {
        $category = Category::firstOrFail();
        $priority = Priority::firstOrFail();
        $status = Status::where('code', 'NUEVA')->firstOrFail();

        $inc1 = Incident::factory()->create([
            'folio' => 'INC-2026-0201',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $status->id,
            'reported_by_user_id' => $this->apocUser->id,
        ]);

        $inc2 = Incident::factory()->create([
            'folio' => 'INC-2026-0202',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $status->id,
            'reported_by_user_id' => $this->apocUser->id,
        ]);

        $response = $this->actingAs($this->gtsUser)->get(route('reports.pdf', [
            'selected_ids' => [$inc1->id, $inc2->id],
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');

        $html = view('reports.pdf', [
            'incidents' => collect([$inc1, $inc2]),
            'summary' => ['total' => 2, 'by_status' => [], 'by_priority' => [], 'resolution_rate' => 0, 'avg_resolution_time_formatted' => 'N/D'],
            'filters' => [],
            'appliedFilters' => ['Selección' => '2 incidencias seleccionadas'],
            'requester' => $this->gtsUser,
            'reportFolio' => 'REP-TEST-CASO2',
            'generatedAt' => '22/09/2026 12:00:00',
        ])->render();

        $this->assertStringContainsString('INC-2026-0201', $html);
        $this->assertStringContainsString('INC-2026-0202', $html);
        $this->assertStringContainsString('page-break', $html);
    }

    /**
     * CASO 3: Buscar INC-2026-0106 y encontrar la incidencia correctamente.
     */
    public function test_caso_3_buscar_folio_inc_2026_0106_encuentra_la_incidencia_correctamente(): void
    {
        $category = Category::firstOrFail();
        $priority = Priority::firstOrFail();
        $status = Status::where('code', 'NUEVA')->firstOrFail();

        $incident = Incident::factory()->create([
            'folio' => 'INC-2026-0106',
            'title' => 'Incidencia Crítica de Audio en Sala B',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $status->id,
            'reported_by_user_id' => $this->apocUser->id,
            'created_at' => Carbon::now()->subDays(10), // Fecha anterior
        ]);

        $response = $this->actingAs($this->gtsUser)->get(route('reports.index', [
            'search' => 'INC-2026-0106',
            'period_preset' => 'today', // Preset que antes la bloqueaba
        ]));

        $response->assertStatus(200);
        $response->assertSee('INC-2026-0106');
        $response->assertSee('Incidencia Crítica de Audio en Sala B');
    }

    /**
     * CASO 4: Buscar un folio inexistente y emitir mensaje controlado sin error 500.
     */
    public function test_caso_4_buscar_folio_inexistente_muestra_mensaje_controlado(): void
    {
        $response = $this->actingAs($this->gtsUser)->get(route('reports.index', [
            'search' => 'INC-2026-9999',
        ]));

        $response->assertStatus(200);
        $response->assertSee('No se encontró ninguna incidencia con ese folio.');
    }

    /**
     * CASO 5: Buscar un folio con formato incorrecto y emitir mensaje controlado.
     */
    public function test_caso_5_buscar_folio_con_formato_incorrecto_muestra_mensaje_controlado(): void
    {
        $response = $this->actingAs($this->gtsUser)->get(route('reports.index', [
            'search' => 'INC-INVALID-FORMAT',
        ]));

        $response->assertStatus(200);
        $response->assertSee('No se encontró una incidencia válida con ese folio.');
    }

    /**
     * CASO 6: Seleccionar incidencia y quitar selección (actualización segura).
     */
    public function test_caso_6_seleccionar_y_quitar_seleccion_actualiza_correctamente(): void
    {
        $category = Category::firstOrFail();
        $priority = Priority::firstOrFail();
        $status = Status::where('code', 'NUEVA')->firstOrFail();

        $inc1 = Incident::factory()->create([
            'folio' => 'INC-2026-0601',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $status->id,
            'reported_by_user_id' => $this->apocUser->id,
        ]);

        $inc2 = Incident::factory()->create([
            'folio' => 'INC-2026-0602',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $status->id,
            'reported_by_user_id' => $this->apocUser->id,
        ]);

        // Si se seleccionaron inicialmente dos incidencias y el usuario deseleccionó inc2:
        $activeSelection = [$inc1->id];

        /** @var ReportService $reportService */
        $reportService = app(ReportService::class);
        $query = $reportService->buildQuery([], $activeSelection);
        $results = $query->get();

        $this->assertCount(1, $results);
        $this->assertEquals('INC-2026-0601', $results->first()->folio);
        $this->assertFalse($results->contains('folio', 'INC-2026-0602'));
    }

    /**
     * CASO 7: Intentar generar reporte sin seleccionar incidencias (solicitud con selected_ids vacío).
     */
    public function test_caso_7_intentar_generar_reporte_sin_seleccion_muestra_mensaje_requerido(): void
    {
        $response = $this->actingAs($this->gtsUser)->get(route('reports.pdf', [
            'selected_ids' => '',
        ]));

        $response->assertRedirect();
        $response->assertSessionHas('search_warning', 'Debe seleccionar al menos una incidencia para generar el reporte.');
    }

    /**
     * CASO 8: Generar reporte con incidencia que tenga bitácora (trazabilidad real en PDF).
     */
    public function test_caso_8_generar_reporte_con_incidencia_que_tenga_bitacora_incluye_trazabilidad(): void
    {
        $category = Category::firstOrFail();
        $priority = Priority::firstOrFail();
        $status = Status::where('code', 'EN_ATENCION')->firstOrFail();

        $incident = Incident::factory()->create([
            'folio' => 'INC-2026-0801',
            'title' => 'Falla en Lector Biométrico Puerta 4',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $status->id,
            'reported_by_user_id' => $this->apocUser->id,
            'assigned_to_user_id' => $this->gtsUser->id,
        ]);

        IncidentHistory::create([
            'incident_id' => $incident->id,
            'user_id' => $this->apocUser->id,
            'action' => 'CREADA',
            'comment' => 'Reporte levantado desde consola APOC.',
            'created_at' => Carbon::now()->subHours(3),
        ]);

        IncidentHistory::create([
            'incident_id' => $incident->id,
            'user_id' => $this->gtsUser->id,
            'action' => 'ASIGNACION',
            'comment' => 'Asignado a especialista de guardia GTS.',
            'created_at' => Carbon::now()->subHours(2),
        ]);

        IncidentHistory::create([
            'incident_id' => $incident->id,
            'user_id' => $this->gtsUser->id,
            'action' => 'INICIO_ATENCION',
            'comment' => 'Revisión física del sensor óptico.',
            'created_at' => Carbon::now()->subHour(),
        ]);

        $html = view('reports.pdf', [
            'incidents' => collect([$incident->loadMissing(['category', 'priority', 'status', 'reportedBy.role', 'assignedTo.role', 'histories.user.role', 'attachments'])]),
            'summary' => ['total' => 1, 'by_status' => [], 'by_priority' => [], 'resolution_rate' => 0, 'avg_resolution_time_formatted' => 'N/D'],
            'filters' => [],
            'appliedFilters' => ['Selección' => '1 incidencia seleccionada'],
            'requester' => $this->gtsUser,
            'reportFolio' => 'REP-TEST-CASO8',
            'generatedAt' => '22/09/2026 12:00:00',
        ])->render();

        $this->assertStringContainsString('6. Bitácora de Trazabilidad Cronológica', $html);
        $this->assertStringContainsString('Reporte levantado desde consola APOC.', $html);
        $this->assertStringContainsString('Asignado a especialista de guardia GTS.', $html);
        $this->assertStringContainsString('Revisión física del sensor óptico.', $html);
        $this->assertStringContainsString('Creación de Expediente', $html);
        $this->assertStringContainsString('Asignación Técnica', $html);
        $this->assertStringContainsString('Inicio de Atención', $html);
    }

    /**
     * CASO 9: Generar reporte con varias incidencias sin mezclar información entre ellas.
     */
    public function test_caso_9_generar_reporte_con_varias_incidencias_no_mezcla_informacion(): void
    {
        $category = Category::firstOrFail();
        $priority = Priority::firstOrFail();
        $status = Status::where('code', 'NUEVA')->firstOrFail();

        $inc1 = Incident::factory()->create([
            'folio' => 'INC-2026-0901',
            'title' => 'Falla en Servidor de Cámaras',
            'description' => 'Servidor Dell PowerEdge sin respuesta de ping.',
            'location' => 'Data Center Principal T1',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $status->id,
            'reported_by_user_id' => $this->apocUser->id,
        ]);

        $inc2 = Incident::factory()->create([
            'folio' => 'INC-2026-0902',
            'title' => 'Falla en Radio VHF Canal 2',
            'description' => 'Ruido estático en frecuencia de aproximación.',
            'location' => 'Torre de Control TWR',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $status->id,
            'reported_by_user_id' => $this->apocUser->id,
        ]);

        $html = view('reports.pdf', [
            'incidents' => collect([
                $inc1->loadMissing(['category', 'priority', 'status', 'reportedBy.role', 'assignedTo.role', 'histories.user.role', 'attachments']),
                $inc2->loadMissing(['category', 'priority', 'status', 'reportedBy.role', 'assignedTo.role', 'histories.user.role', 'attachments']),
            ]),
            'summary' => ['total' => 2, 'by_status' => [], 'by_priority' => [], 'resolution_rate' => 0, 'avg_resolution_time_formatted' => 'N/D'],
            'filters' => [],
            'appliedFilters' => ['Selección' => '2 incidencias seleccionadas'],
            'requester' => $this->gtsUser,
            'reportFolio' => 'REP-TEST-CASO9',
            'generatedAt' => '22/09/2026 12:00:00',
        ])->render();

        // Verificar que cada incidencia mantiene su bloque completo y separado
        $this->assertStringContainsString('EXPEDIENTE: INC-2026-0901', $html);
        $this->assertStringContainsString('Data Center Principal T1', $html);
        $this->assertStringContainsString('EXPEDIENTE: INC-2026-0902', $html);
        $this->assertStringContainsString('Torre de Control TWR', $html);

        // Verificar que la primera ficha tiene salto de página para no colisionar con la segunda
        $this->assertStringContainsString('page-break', $html);
    }

    /**
     * CASO 10: Intentar manipular IDs desde el navegador (backend valida permisos y existencia).
     */
    public function test_caso_10_manipulacion_de_ids_es_validada_por_backend(): void
    {
        $response = $this->actingAs($this->gtsUser)->get(route('reports.pdf', [
            'selected_ids' => [999999, 888888, 'INVENTADO'],
        ]));

        $response->assertRedirect();
        $response->assertSessionHas('search_warning', 'No se encontraron incidencias válidas para los identificadores seleccionados.');
    }

    /**
     * CASO 11: Enviar por WhatsApp y validar que el contenido corresponde exactamente a las seleccionadas.
     */
    public function test_caso_11_envio_whatsapp_corresponde_exactamente_a_las_incidencias_seleccionadas(): void
    {
        $category = Category::firstOrFail();
        $priority = Priority::firstOrFail();
        $status = Status::where('code', 'NUEVA')->firstOrFail();

        $inc1 = Incident::factory()->create([
            'folio' => 'INC-2026-1101',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $status->id,
            'reported_by_user_id' => $this->apocUser->id,
        ]);

        $inc2 = Incident::factory()->create([
            'folio' => 'INC-2026-1102',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $status->id,
            'reported_by_user_id' => $this->apocUser->id,
        ]);

        $response = $this->actingAs($this->gtsUser)->post(route('reports.whatsapp'), [
            'phone' => '+52 55 1234 5678',
            'selected_ids' => [$inc1->id],
        ]);

        $response->assertRedirect();
        $redirectUrl = $response->headers->get('Location');
        $decodedUrl = urldecode($redirectUrl);

        $this->assertStringContainsString('api.whatsapp.com', $redirectUrl);
        $this->assertStringContainsString('INC-2026-1101', $decodedUrl);
        // Debe contener solo la seleccionada y no inc2
        $this->assertStringNotContainsString('INC-2026-1102', $decodedUrl);
    }
}
