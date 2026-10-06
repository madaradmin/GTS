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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MasterAuditPhase7Test extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $gtsUser;

    protected User $apocUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->adminUser = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->firstOrFail();
        $this->gtsUser = User::whereHas('role', fn ($q) => $q->where('slug', 'gts'))->firstOrFail();
        $this->apocUser = User::whereHas('role', fn ($q) => $q->where('slug', 'apoc'))->firstOrFail();
    }

    /**
     * Punto 1: Al abrir GTS (/) redirige directamente al Login.
     */
    public function test_punto_1_al_abrir_gts_aparece_directamente_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect(route('login'));
        $this->assertFalse(File::exists(resource_path('views/welcome.blade.php')));
    }

    /**
     * Punto 2: Las rutas canónicas funcionan correctamente.
     */
    public function test_punto_2_rutas_canonicas_funcionan_correctamente(): void
    {
        // /Todas las incidencias
        $resAll = $this->actingAs($this->apocUser)->get('/Todas las incidencias');
        $resAll->assertStatus(200);

        // /Mis incidencias (GTS y Admin)
        $resMyGts = $this->actingAs($this->gtsUser)->get('/Mis incidencias');
        $resMyGts->assertStatus(200);

        // /Indicadores
        $resInd = $this->actingAs($this->apocUser)->get('/indicadores');
        $resInd->assertStatus(200);

        // Redirecciones de compatibilidad
        $resLegacy1 = $this->actingAs($this->apocUser)->get('/incidencias');
        $resLegacy1->assertRedirect(route('incidents.index'));

        $resLegacy2 = $this->actingAs($this->gtsUser)->get('/incidencias/mis-incidencias');
        $resLegacy2->assertRedirect(route('incidents.my'));
    }

    /**
     * Punto 3: Las rutas protegidas no pueden utilizarse sin autenticación.
     */
    public function test_punto_3_rutas_protegidas_requieren_autenticacion(): void
    {
        $protectedUrls = [
            '/dashboard',
            '/Todas las incidencias',
            '/Mis incidencias',
            '/auditoria',
            '/reportes',
            '/indicadores',
            '/catalogos',
            '/configuracion/usuarios',
        ];

        foreach ($protectedUrls as $url) {
            $response = $this->get($url);
            $this->assertTrue(
                $response->isRedirect(route('login')) || $response->isForbidden(),
                "La URL {$url} no exigió autenticación adecuadamente."
            );
        }
    }

    /**
     * Punto 4: Auditoría no tiene scroll horizontal en su estructura.
     */
    public function test_punto_4_auditoria_no_tiene_scroll_horizontal(): void
    {
        $auditViewContent = File::get(resource_path('views/audit/index.blade.php'));

        $this->assertStringNotContainsString('overflow-x-auto', $auditViewContent);
        $this->assertStringContainsString('table-fixed', $auditViewContent);
        $this->assertStringContainsString('w-full', $auditViewContent);
    }

    /**
     * Punto 5: Generador de reportes permite seleccionar una o varias incidencias.
     */
    public function test_punto_5_generador_de_reportes_soporta_seleccion_multiple(): void
    {
        $category = Category::firstOrFail();
        $priority = Priority::firstOrFail();
        $status = Status::firstOrFail();

        $inc1 = Incident::factory()->create([
            'folio' => 'INC-2026-7001',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $status->id,
            'reported_by_user_id' => $this->apocUser->id,
        ]);

        $inc2 = Incident::factory()->create([
            'folio' => 'INC-2026-7002',
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

        $reportsIndexContent = File::get(resource_path('views/reports/index.blade.php'));
        $this->assertStringContainsString('selectedIds', $reportsIndexContent);
        $this->assertStringContainsString('toggleAll', $reportsIndexContent);
        $this->assertStringContainsString('toggleOne', $reportsIndexContent);
        $this->assertStringContainsString('table-fixed', $reportsIndexContent);
    }

    /**
     * Punto 6: La búsqueda por folio funciona y no colisiona con columnas SQL.
     */
    public function test_punto_6_busqueda_por_folio_funciona_limpiamente(): void
    {
        $category = Category::firstOrFail();
        $priority = Priority::firstOrFail();
        $status = Status::firstOrFail();

        Incident::factory()->create([
            'folio' => 'INC-2026-7006',
            'title' => 'Incidencia Verificación Punto 6',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $status->id,
            'reported_by_user_id' => $this->apocUser->id,
            'created_at' => Carbon::now()->subDays(20),
        ]);

        $response = $this->actingAs($this->gtsUser)->get(route('reports.index', [
            'search' => 'INC-2026-7006',
            'period_preset' => 'today',
        ]));

        $response->assertStatus(200);
        $response->assertSee('INC-2026-7006');
        $response->assertSee('Incidencia Verificación Punto 6');
    }

    /**
     * Punto 7 y 8: El PDF contiene información completa y cada incidencia inicia en página independiente.
     */
    public function test_punto_7_y_8_pdf_contiene_informacion_completa_y_salto_de_pagina(): void
    {
        $pdfViewContent = File::get(resource_path('views/reports/pdf.blade.php'));

        $this->assertStringContainsString('1. Identificación y Generales', $pdfViewContent);
        $this->assertStringContainsString('2. Clasificación Técnica y Tiempo Estimado', $pdfViewContent);
        $this->assertStringContainsString('3. Ciclo de Atención y Cronometría Operativa', $pdfViewContent);
        $this->assertStringContainsString('4. Descripción Operativa del Reporte Original', $pdfViewContent);
        $this->assertStringContainsString('5. Diagnóstico y Dictamen de Solución Técnica', $pdfViewContent);
        $this->assertStringContainsString('6. Bitácora de Trazabilidad Cronológica', $pdfViewContent);
        $this->assertStringContainsString('7. Evidencias Fotográficas y Documentación Adjunta', $pdfViewContent);
        $this->assertStringContainsString('page-break', $pdfViewContent);
    }

    /**
     * Punto 9: La bitácora de trazabilidad aparece correctamente.
     */
    public function test_punto_9_bitacora_de_trazabilidad_aparece_correctamente(): void
    {
        $category = Category::firstOrFail();
        $priority = Priority::firstOrFail();
        $status = Status::firstOrFail();

        $incident = Incident::factory()->create([
            'folio' => 'INC-2026-7009',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $status->id,
            'reported_by_user_id' => $this->apocUser->id,
        ]);

        IncidentHistory::create([
            'incident_id' => $incident->id,
            'user_id' => $this->apocUser->id,
            'action' => 'CREADA',
            'comment' => 'Evento de auditoría pericial punto 9.',
            'created_at' => Carbon::now(),
        ]);

        /** @var ReportService $service */
        $service = app(ReportService::class);
        $query = $service->buildQuery([], [$incident->id]);
        $loadedIncident = $query->first();

        $this->assertTrue($loadedIncident->relationLoaded('histories'));
        $this->assertEquals('Evento de auditoría pericial punto 9.', $loadedIncident->histories->first()->comment);
    }

    /**
     * Punto 10: WhatsApp utiliza las incidencias seleccionadas.
     */
    public function test_punto_10_whatsapp_utiliza_incidencias_seleccionadas(): void
    {
        $category = Category::firstOrFail();
        $priority = Priority::firstOrFail();
        $status = Status::firstOrFail();

        $incident = Incident::factory()->create([
            'folio' => 'INC-2026-7010',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $status->id,
            'reported_by_user_id' => $this->apocUser->id,
        ]);

        $response = $this->actingAs($this->gtsUser)->post(route('reports.whatsapp'), [
            'phone' => '+52 55 1234 5678',
            'selected_ids' => [$incident->id],
        ]);

        $response->assertRedirect();
        $this->assertStringContainsString('INC-2026-7010', urldecode($response->headers->get('Location')));
    }

    /**
     * Punto 11: No existen referencias a código eliminado en app, routes o views.
     */
    public function test_punto_11_no_existen_referencias_a_codigo_eliminado(): void
    {
        $forbiddenTerms = [
            'welcome.blade.php',
            'reporte-diario',
            'reporte-semanal',
            'daily_report',
            'weekly_report',
        ];

        $filesToCheck = array_merge(
            File::allFiles(app_path()),
            File::allFiles(base_path('routes')),
            File::allFiles(resource_path('views'))
        );

        foreach ($filesToCheck as $file) {
            $content = File::get($file->getRealPath());
            foreach ($forbiddenTerms as $term) {
                $this->assertStringNotContainsString(
                    $term,
                    $content,
                    "Se encontró referencia prohibida '{$term}' en: {$file->getRelativePathname()}"
                );
            }
        }
    }

    /**
     * Punto 12: No existen rutas muertas.
     */
    public function test_punto_12_todas_las_rutas_tienen_controladores_validos(): void
    {
        $routes = Route::getRoutes();

        foreach ($routes as $route) {
            $action = $route->getAction();

            if (isset($action['controller'])) {
                if (str_contains($action['controller'], '@')) {
                    [$controller, $method] = explode('@', $action['controller']);
                } else {
                    $controller = $action['controller'];
                    $method = '__invoke';
                }
                $this->assertTrue(
                    class_exists($controller),
                    "El controlador {$controller} no existe para la ruta {$route->uri()}."
                );
                $this->assertTrue(
                    method_exists($controller, $method),
                    "El método {$method} no existe en {$controller} para la ruta {$route->uri()}."
                );
            }
        }
    }
}
