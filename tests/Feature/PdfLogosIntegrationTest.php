<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Incident;
use App\Models\Priority;
use App\Models\Status;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PdfLogosIntegrationTest extends TestCase
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
     * Verifica que los dos archivos físicos de logotipo existen en public/images.
     */
    public function test_archivos_de_logotipos_existen_en_disco(): void
    {
        $this->assertFileExists(public_path('images/gts-logo.png'));
        $this->assertFileExists(public_path('images/aifa-logo.png'));
    }

    /**
     * Verifica que la plantilla Blade contiene el encabezado institucional fijo y los tags de ambos logos.
     */
    public function test_plantilla_blade_contiene_encabezado_fijo_y_logotipos(): void
    {
        $blade = File::get(resource_path('views/reports/pdf.blade.php'));

        $this->assertStringContainsString('header {', $blade);
        $this->assertStringContainsString('position: fixed;', $blade);
        $this->assertStringContainsString('header-logo-gts', $blade);
        $this->assertStringContainsString('header-logo-aifa', $blade);
        $this->assertStringContainsString('alt="Logo GTS"', $blade);
        $this->assertStringContainsString('alt="Logo AIFA"', $blade);
        $this->assertStringContainsString('Aeropuerto Internacional Felipe Ángeles', $blade);
        $this->assertStringContainsString('GTS — Grupo de Seguridad Tecnológica', $blade);
    }

    /**
     * Verifica que ReportService codifica e inyecta ambos logotipos en base64 para Dompdf.
     */
    public function test_report_service_inyecta_logotipos_en_pdf(): void
    {
        $category = Category::firstOrFail();
        $priority = Priority::firstOrFail();
        $status = Status::where('code', 'NUEVA')->firstOrFail();

        $incident = Incident::factory()->create([
            'folio' => 'INC-2026-LOGO1',
            'title' => 'Incidencia de prueba con logos',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $status->id,
            'reported_by_user_id' => $this->apocUser->id,
        ]);

        $service = app(ReportService::class);
        $pdf = $service->generatePdf([], $this->gtsUser, 'REP-LOGO-001', [$incident->id]);

        $binary = $pdf->output();

        $this->assertNotEmpty($binary);
        $this->assertStringStartsWith('%PDF', $binary);
    }

    /**
     * Verifica que la ruta web /reportes/pdf genera y descarga un PDF válido con HTTP 200.
     */
    public function test_ruta_reportes_pdf_descarga_documento_valido_con_logos(): void
    {
        $category = Category::firstOrFail();
        $priority = Priority::firstOrFail();
        $status = Status::where('code', 'NUEVA')->firstOrFail();

        Incident::factory()->create([
            'folio' => 'INC-2026-LOGO2',
            'title' => 'Incidencia para endpoint PDF',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $status->id,
            'reported_by_user_id' => $this->apocUser->id,
        ]);

        $response = $this->actingAs($this->gtsUser)->get(route('reports.pdf'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    /**
     * Verifica que un reporte con múltiples incidencias genera un PDF con todas las páginas estructuradas.
     */
    public function test_reporte_con_multiples_incidencias_conserva_encabezado(): void
    {
        $category = Category::firstOrFail();
        $priority = Priority::firstOrFail();
        $status = Status::where('code', 'NUEVA')->firstOrFail();

        $inc1 = Incident::factory()->create([
            'folio' => 'INC-2026-MULTI1',
            'title' => 'Incidencia M1',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $status->id,
            'reported_by_user_id' => $this->apocUser->id,
        ]);

        $inc2 = Incident::factory()->create([
            'folio' => 'INC-2026-MULTI2',
            'title' => 'Incidencia M2',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $status->id,
            'reported_by_user_id' => $this->apocUser->id,
        ]);

        $service = app(ReportService::class);
        $pdf = $service->generatePdf([], $this->gtsUser, 'REP-MULTI-PAGE', [$inc1->id, $inc2->id]);
        $binary = $pdf->output();

        $this->assertNotEmpty($binary);
        $this->assertStringStartsWith('%PDF', $binary);
    }
}
