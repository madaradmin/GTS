<?php

namespace Tests\Feature;

use App\Mail\IncidentReportMail;
use App\Models\Category;
use App\Models\Incident;
use App\Models\IncidentAttachment;
use App\Models\Priority;
use App\Models\Status;
use App\Models\User;
use App\Services\ReportService;
use App\Services\WhatsAppReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ReportSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $apocUser;

    protected User $gtsUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->apocUser = User::whereHas('role', fn ($q) => $q->where('slug', 'apoc'))->firstOrFail();
        $this->gtsUser = User::whereHas('role', fn ($q) => $q->where('slug', 'gts'))->firstOrFail();
    }

    public function test_unauthenticated_user_cannot_access_reports(): void
    {
        $response = $this->get(route('reports.index'));
        $response->assertRedirect(route('login'));

        $responsePdf = $this->get(route('reports.pdf'));
        $responsePdf->assertRedirect(route('login'));
    }

    public function test_inactive_user_cannot_access_reports(): void
    {
        $this->apocUser->update(['is_active' => false]);

        $response = $this->actingAs($this->apocUser)->get(route('reports.index'));
        $response->assertStatus(403);
    }

    public function test_authenticated_user_can_access_reports_index(): void
    {
        $response = $this->actingAs($this->apocUser)->get(route('reports.index'));

        $response->assertStatus(200);
        $response->assertSee('Sistema Profesional de Reportes');
        $response->assertSee('Descargar PDF Institucional');
        $response->assertSee('Enviar por Correo');
        $response->assertSee('WhatsApp');
    }

    public function test_reports_can_be_filtered_by_status_priority_and_category(): void
    {
        $cctv = Category::where('slug', 'cctv')->firstOrFail();
        $access = Category::where('slug', 'access-control')->firstOrFail();
        $critica = Priority::where('slug', 'critica')->firstOrFail();
        $baja = Priority::where('slug', 'baja')->firstOrFail();
        $statusNueva = Status::where('code', 'NUEVA')->firstOrFail();
        $statusCerrada = Status::where('code', 'CERRADA')->firstOrFail();

        // Incidencia 1: CCTV, Crítica, Nueva
        Incident::factory()->create([
            'folio' => 'ST-2026-9001',
            'title' => 'Falla Cámara PTZ Norte',
            'category_id' => $cctv->id,
            'priority_id' => $critica->id,
            'status_id' => $statusNueva->id,
            'reported_by_user_id' => $this->apocUser->id,
        ]);

        // Incidencia 2: Access, Baja, Cerrada
        Incident::factory()->create([
            'folio' => 'ST-2026-9002',
            'title' => 'Torniquete Lento Terminal 1',
            'category_id' => $access->id,
            'priority_id' => $baja->id,
            'status_id' => $statusCerrada->id,
            'reported_by_user_id' => $this->apocUser->id,
        ]);

        // Filtrar por CCTV
        $responseCategory = $this->actingAs($this->gtsUser)->get(route('reports.index', [
            'category_id' => $cctv->id,
        ]));
        $responseCategory->assertStatus(200);
        $responseCategory->assertSee('ST-2026-9001');
        $responseCategory->assertDontSee('ST-2026-9002');

        // Filtrar por Prioridad Baja
        $responsePriority = $this->actingAs($this->gtsUser)->get(route('reports.index', [
            'priority_id' => $baja->id,
        ]));
        $responsePriority->assertStatus(200);
        $responsePriority->assertSee('ST-2026-9002');
        $responsePriority->assertDontSee('ST-2026-9001');
    }

    public function test_report_service_calculates_summary_metrics_accurately(): void
    {
        $statusNueva = Status::where('code', 'NUEVA')->firstOrFail();
        $statusResuelta = Status::where('code', 'RESUELTA')->firstOrFail();
        $statusCerrada = Status::where('code', 'CERRADA')->firstOrFail();
        $category = Category::firstOrFail();
        $priority = Priority::firstOrFail();

        // 1 Nueva
        Incident::factory()->create([
            'status_id' => $statusNueva->id,
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'reported_by_user_id' => $this->apocUser->id,
        ]);

        // 1 Resuelta (con 30 minutos de atención)
        Incident::factory()->create([
            'status_id' => $statusResuelta->id,
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'reported_by_user_id' => $this->apocUser->id,
            'created_at' => Carbon::now()->subMinutes(30),
            'resolved_at' => Carbon::now(),
        ]);

        // 1 Cerrada
        Incident::factory()->create([
            'status_id' => $statusCerrada->id,
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'reported_by_user_id' => $this->apocUser->id,
            'created_at' => Carbon::now()->subMinutes(60),
            'resolved_at' => Carbon::now()->subMinutes(10),
            'closed_at' => Carbon::now(),
        ]);

        /** @var ReportService $reportService */
        $reportService = app(ReportService::class);
        $query = $reportService->buildQuery();
        $summary = $reportService->calculateSummary($query);

        $this->assertGreaterThanOrEqual(3, $summary['total']);
        $this->assertGreaterThanOrEqual(1, $summary['by_status']['NUEVA']);
        $this->assertGreaterThanOrEqual(1, $summary['by_status']['RESUELTA']);
        $this->assertGreaterThanOrEqual(1, $summary['by_status']['CERRADA']);
        $this->assertGreaterThan(0, $summary['resolution_rate']);
    }

    public function test_export_pdf_returns_valid_pdf_download(): void
    {
        $response = $this->actingAs($this->gtsUser)->get(route('reports.pdf'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('attachment;', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('.pdf', $response->headers->get('content-disposition'));
    }

    public function test_send_email_validates_and_dispatches_mailable_with_pdf(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->apocUser)->post(route('reports.email'), [
            'email' => 'comandancia.aifa@aifa.aero',
            'notes' => 'Informe urgente del turno nocturno para revisión pericial.',
            'filters' => [
                'period_preset' => 'today',
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status_message');

        Mail::assertSent(IncidentReportMail::class, function (IncidentReportMail $mail) {
            $this->assertEquals('comandancia.aifa@aifa.aero', $mail->to[0]['address']);
            $this->assertStringContainsString('[GTS AIFA] Reporte Operativo de Incidencias', $mail->envelope()->subject);
            $this->assertEquals('Informe urgente del turno nocturno para revisión pericial.', $mail->notes);
            $this->assertNotEmpty($mail->pdfBinary);

            return true;
        });
    }

    public function test_whatsapp_service_formats_and_dispatches_cleanly(): void
    {
        /** @var WhatsAppReportService $service */
        $service = app(WhatsAppReportService::class);

        $summary = [
            'total' => 12,
            'by_status' => [
                'NUEVA' => 2,
                'ASIGNADA' => 3,
                'EN_ATENCION' => 2,
                'RESUELTA' => 4,
                'CERRADA' => 1,
            ],
            'resolution_rate' => 41.7,
            'avg_resolution_time_formatted' => '22 min',
        ];

        $message = $service->formatReportMessage($summary, ['period_preset' => 'today'], 'REP-20260911-0001');

        $this->assertStringContainsString('AIFA — GTS (Grupo de Seguridad Tecnológica)', $message);
        $this->assertStringContainsString('Total Incidencias:* 12', $message);
        $this->assertStringContainsString('REP-20260911-0001', $message);

        // Prueba de despacho / generación de enlace sin API Cloud configurada
        $result = $service->sendOrGenerate('55 1234 5678', $message);

        $this->assertTrue($result['success']);
        $this->assertEquals('link', $result['mode']);
        $this->assertStringStartsWith('525512345678', $result['phone']);
        $this->assertStringContainsString('https://api.whatsapp.com/send', $result['url']);
    }

    public function test_share_whatsapp_endpoint_works_for_authenticated_users(): void
    {
        $response = $this->actingAs($this->gtsUser)->post(route('reports.whatsapp'), [
            'phone' => '+52 55 9876 5432',
            'filters' => ['period_preset' => 'today'],
        ]);

        $response->assertRedirect();
        $this->assertStringContainsString('api.whatsapp.com', $response->headers->get('Location'));
    }

    public function test_dashboard_quick_action_points_to_reports_index_not_audit(): void
    {
        $response = $this->actingAs($this->apocUser)->get(route('dashboard'));

        $response->assertStatus(200);
        // Debe contener el enlace a reports.index
        $response->assertSee(route('reports.index'));
        // El botón "Descargar reporte" en el HTML debe apuntar a reports.index
        $this->assertStringContainsString('href="'.route('reports.index').'"', $response->getContent());
    }

    public function test_sidebar_contains_reports_link(): void
    {
        $response = $this->actingAs($this->gtsUser)->get(route('reports.index'));

        $response->assertStatus(200);
        $response->assertSee(route('reports.index'));
        $response->assertSee('Descargar reporte');
        $response->assertDontSee('Reporte Diario');
        $response->assertDontSee('Reporte Semanal');
    }

    public function test_reports_renders_successfully_with_incident_attachments(): void
    {
        $incident = Incident::factory()->create([
            'reported_by_user_id' => $this->gtsUser->id,
        ]);

        IncidentAttachment::factory()->create([
            'incident_id' => $incident->id,
            'uploaded_by_user_id' => $this->gtsUser->id,
        ]);

        $responseIndex = $this->actingAs($this->gtsUser)->get(route('reports.index'));
        $responseIndex->assertStatus(200);

        $responsePdf = $this->actingAs($this->gtsUser)->get(route('reports.pdf'));
        $responsePdf->assertStatus(200);
    }
}
