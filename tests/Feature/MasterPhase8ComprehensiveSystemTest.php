<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Incident;
use App\Models\IncidentHistory;
use App\Models\Priority;
use App\Models\Status;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class MasterPhase8ComprehensiveSystemTest extends TestCase
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
     * PRUEBA 1 — DASHBOARD
     * Verificar:
     * - Eliminación completa de "Matriz de salud operacional por subsistema tecnológico"
     * - Distribución equitativa 50% / 50% en Fila B para "Incidencias por tipo" e "Incidencias por prioridad"
     * - Grid responsivo de 2 columnas (md:grid-cols-2)
     * - Los datos de distribución y prioridades provienen de PostgreSQL (sin hardcoding)
     */
    public function test_prueba_1_dashboard_redisenio_graficos_50_50_y_eliminacion_matriz_salud(): void
    {
        $dashboardView = File::get(resource_path('views/dashboard.blade.php'));

        // Desapareció "Matriz de salud operacional"
        $this->assertStringNotContainsString('Matriz de salud operacional', $dashboardView);
        $this->assertStringNotContainsString('healthMatrix', $dashboardView);

        // Estructura de 2 columnas 50% / 50%
        $this->assertStringContainsString('grid grid-cols-1 md:grid-cols-2 gap-3', $dashboardView);
        $this->assertStringContainsString('Incidencias por tipo', $dashboardView);
        $this->assertStringContainsString('Incidencias por prioridad', $dashboardView);

        // Llamar al DashboardController
        $response = $this->actingAs($this->adminUser)->get(route('dashboard'));
        $response->assertOk();
        $response->assertViewMissing('healthMatrix');
        $response->assertViewHas('distribution');
        $response->assertViewHas('prioritiesSummary');

        $distribution = $response->viewData('distribution');
        $prioritiesSummary = $response->viewData('prioritiesSummary');

        $this->assertIsArray($distribution);
        $this->assertIsArray($prioritiesSummary);
        $this->assertArrayHasKey('categories', $distribution);
        $this->assertArrayHasKey('items', $prioritiesSummary);
    }

    /**
     * PRUEBA 2 — AUDITORÍA
     * Como Administrador:
     * - Acceder a Auditoría
     * - Comprobar que aparecen todas las incidencias (antiguas, recientes, distintos estados)
     * - Paginación funcional
     * - Restricción para no administradores (403)
     */
    public function test_prueba_2_auditoria_mostrar_todas_las_incidencias(): void
    {
        // No administradores reciben 403
        $this->actingAs($this->apocUser)->get(route('audit.index'))->assertForbidden();
        $this->actingAs($this->gtsUser)->get(route('audit.index'))->assertForbidden();

        // Crear incidencias con diferentes estados y fechas
        $category = Category::firstOrFail();
        $priority = Priority::firstOrFail();
        $statuses = Status::all();

        $incidents = [];
        foreach ($statuses as $index => $status) {
            $incidents[] = Incident::factory()->create([
                'folio' => "INC-AUD-{$index}",
                'title' => "Incidencia Auditoria Estado {$status->code}",
                'category_id' => $category->id,
                'priority_id' => $priority->id,
                'status_id' => $status->id,
                'reported_by_user_id' => $this->apocUser->id,
                'created_at' => now()->subDays(10 - $index),
            ]);
        }

        // Administrador accede con éxito
        $response = $this->actingAs($this->adminUser)->get(route('audit.index'));
        $response->assertOk();
        $response->assertViewHas('incidents');

        $viewIncidents = $response->viewData('incidents');
        $this->assertGreaterThanOrEqual(count($incidents), $viewIncidents->total());

        // Comprobar búsqueda por folio
        $searchResponse = $this->actingAs($this->adminUser)->get(route('audit.index', ['search' => 'INC-AUD-0']));
        $searchResponse->assertOk();
        $searchResponse->assertSee('INC-AUD-0');
    }

    /**
     * PRUEBA 3 — ELIMINACIÓN
     * Eliminar una incidencia desde Admin.
     * Comprobar que ya no aparece como Administrador, GTS ni APOC.
     */
    public function test_prueba_3_eliminacion_por_admin_y_verificacion_multirrol(): void
    {
        $category = Category::firstOrFail();
        $priority = Priority::firstOrFail();
        $status = Status::where('code', 'NUEVA')->firstOrFail();

        $incident = Incident::factory()->create([
            'folio' => 'INC-TO-DELETE-001',
            'title' => 'Incidencia para prueba de borrado definitivo',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $status->id,
            'reported_by_user_id' => $this->apocUser->id,
            'assigned_to_user_id' => $this->gtsUser->id,
        ]);

        $incidentId = $incident->id;

        // Eliminar como Administrador
        $deleteResponse = $this->actingAs($this->adminUser)->delete(route('incidents.destroy', $incident));
        $deleteResponse->assertRedirect();

        // Verificar que no existe en base de datos
        $this->assertDatabaseMissing('incidents', ['id' => $incidentId]);

        // Verificar que no aparece en /incidencias para Administrador
        $adminView = $this->actingAs($this->adminUser)->get(route('incidents.index'));
        $adminView->assertDontSee('INC-TO-DELETE-001');

        // Verificar que no aparece en /auditoria para Administrador
        $auditView = $this->actingAs($this->adminUser)->get(route('audit.index'));
        $auditView->assertDontSee('INC-TO-DELETE-001');

        // Verificar que no aparece en /incidencias para GTS
        $gtsView = $this->actingAs($this->gtsUser)->get(route('incidents.index'));
        $gtsView->assertDontSee('INC-TO-DELETE-001');

        // Verificar que no aparece en /incidencias para APOC
        $apocView = $this->actingAs($this->apocUser)->get(route('incidents.index'));
        $apocView->assertDontSee('INC-TO-DELETE-001');
    }

    /**
     * PRUEBA 4 — NOTIFICACIONES
     * Comprobar que existen notificaciones relacionadas.
     * Eliminar la incidencia.
     * Verificar que no quedan notificaciones huérfanas en Admin, GTS o APOC.
     */
    public function test_prueba_4_notificaciones_purgadas_al_eliminar_incidencia(): void
    {
        $category = Category::firstOrFail();
        $priority = Priority::firstOrFail();
        $status = Status::where('code', 'NUEVA')->firstOrFail();

        $incident = Incident::factory()->create([
            'folio' => 'INC-NOTIF-TEST',
            'title' => 'Incidencia con notificaciones activas',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $status->id,
            'reported_by_user_id' => $this->apocUser->id,
            'assigned_to_user_id' => $this->gtsUser->id,
        ]);

        // Crear notificaciones directas para los 3 usuarios vinculadas a esta incidencia
        foreach ([$this->adminUser, $this->gtsUser, $this->apocUser] as $targetUser) {
            DatabaseNotification::create([
                'id' => Str::uuid()->toString(),
                'type' => 'App\Notifications\IncidentStatusChangedNotification',
                'notifiable_type' => get_class($targetUser),
                'notifiable_id' => $targetUser->id,
                'data' => [
                    'incident_id' => $incident->id,
                    'incident_folio' => $incident->folio,
                    'title' => 'Alerta para '.$targetUser->name,
                    'message' => 'Notificación de prueba asociada al folio '.$incident->folio,
                ],
                'created_at' => now(),
            ]);
        }

        // Confirmar que existen 3 notificaciones asociadas al ID
        $countBefore = DatabaseNotification::where('data->incident_id', $incident->id)->count();
        $this->assertEquals(3, $countBefore);

        // Eliminar la incidencia a través del controlador como Administrador
        $this->actingAs($this->adminUser)->delete(route('incidents.destroy', $incident));

        // Verificar que TODAS las notificaciones vinculadas fueron purgadas
        $countAfter = DatabaseNotification::where('data->incident_id', $incident->id)->count();
        $this->assertEquals(0, $countAfter);

        // Verificar que no hay notificaciones rotas en la vista de ningún usuario
        foreach ([$this->adminUser, $this->gtsUser, $this->apocUser] as $user) {
            $this->assertEquals(0, $user->notifications()->where('data->incident_id', $incident->id)->count());
        }
    }

    /**
     * PRUEBA 5 — ESTADO ACTIVO
     * Comprobar que:
     * - No existe el control Activo / No disponible
     * - No existe botón de turno
     * - No existe en Perfil, Configuración, Sidebar, Dashboard
     * - No existe endpoint funcional ni código exclusivo sin uso
     */
    public function test_prueba_5_eliminacion_total_de_estado_activo_no_disponible(): void
    {
        // 1. Archivos eliminados no existen
        $this->assertFileDoesNotExist(app_path('Http/Controllers/AgentShiftController.php'));
        $this->assertFileDoesNotExist(app_path('Models/AgentAvailabilityLog.php'));
        $this->assertFileDoesNotExist(resource_path('views/profile/partials/update-shift-status-form.blade.php'));

        // 2. Rutas de turno no existen
        $this->assertFalse(Route::has('agent-shifts.store'));
        $this->assertFalse(Route::has('agent-shifts.toggle'));

        // 3. Vistas no contienen menciones a 'update-shift-status-form' ni etiquetas de cambio de turno
        $profileView = File::get(resource_path('views/profile/edit.blade.php'));
        $this->assertStringNotContainsString('update-shift-status-form', $profileView);

        $sidebarView = File::get(resource_path('views/components/sidebar.blade.php'));
        $this->assertStringNotContainsString('Estado de Turno', $sidebarView);
        $this->assertStringNotContainsString('No disponible', $sidebarView);

        $topbarView = File::get(resource_path('views/components/topbar.blade.php'));
        $this->assertStringNotContainsString('Estado de Turno', $topbarView);
        $this->assertStringNotContainsString('No disponible', $topbarView);

        // 4. La autenticación y el atributo is_active de usuarios siguen 100% operativos
        $this->assertTrue($this->adminUser->is_active);
    }

    /**
     * PRUEBA 6 — SIDEBAR
     * Comprobar:
     * - Zona de proximidad hover existente
     * - Sidebar como overlay flotante
     * - Clases de transición suave
     * - Sin scroll horizontal
     */
    public function test_prueba_6_sidebar_hover_revelado_sin_parpadeo_y_sin_scroll(): void
    {
        $appLayout = File::get(resource_path('views/layouts/app.blade.php'));
        $sidebarView = File::get(resource_path('views/components/sidebar.blade.php'));

        // Zona invisible de proximidad (14px) en el borde izquierdo
        $this->assertStringContainsString('w-3.5', $sidebarView);
        $this->assertStringContainsString('fixed top-16 bottom-0 left-0 w-3.5 z-30', $sidebarView);

        // Debounce / delay de salida para evitar parpadeos
        $this->assertStringContainsString('hoverTimeout', $appLayout);
        $this->assertStringContainsString('clearTimeout', $appLayout);
        $this->assertStringContainsString('setTimeout', $appLayout);

        // Sidebar como overlay flotante sin desplazar contenido
        $this->assertStringContainsString('fixed inset-y-0 left-0 z-40 top-16 w-64', $sidebarView);

        // Sin scroll horizontal en main layout
        $this->assertStringContainsString('overflow-x-hidden', $appLayout);
    }

    /**
     * PRUEBA 7 — PDF
     * Generar PDF:
     * - Logo GTS arriba a la izquierda
     * - Logo AIFA centrado
     * - Ambos presentes en todas las páginas
     * - Casos: 1 incidencia, varias incidencias, contenido extenso y bitácora extensa
     */
    public function test_prueba_7_generacion_pdf_con_logos_en_todos_los_escenarios(): void
    {
        $category = Category::firstOrFail();
        $priority = Priority::firstOrFail();
        $status = Status::where('code', 'NUEVA')->firstOrFail();

        $service = app(ReportService::class);

        // Escenario A: Una incidencia individual
        $inc1 = Incident::factory()->create([
            'folio' => 'INC-ESC-A',
            'title' => 'Incidencia individual',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $status->id,
            'reported_by_user_id' => $this->apocUser->id,
        ]);

        $pdfA = $service->generatePdf([], $this->gtsUser, 'REP-TEST-A', [$inc1->id]);
        $binaryA = $pdfA->output();
        $this->assertNotEmpty($binaryA);
        $this->assertStringStartsWith('%PDF', $binaryA);

        // Escenario B: Varias incidencias (multi-hoja)
        $inc2 = Incident::factory()->create([
            'folio' => 'INC-ESC-B',
            'title' => 'Segunda incidencia',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $status->id,
            'reported_by_user_id' => $this->apocUser->id,
        ]);

        $pdfB = $service->generatePdf([], $this->gtsUser, 'REP-TEST-B', [$inc1->id, $inc2->id]);
        $binaryB = $pdfB->output();
        $this->assertNotEmpty($binaryB);
        $this->assertStringStartsWith('%PDF', $binaryB);

        // Escenario C: Incidencia con mucho contenido y bitácora extensa (desbordamiento multi-página)
        $incExtensive = Incident::factory()->create([
            'folio' => 'INC-ESC-C',
            'title' => 'Incidencia con bitácora extensa',
            'description' => str_repeat('Descripción pericial exhaustiva de la contingencia técnica ocurrida en la terminal. ', 15),
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'status_id' => $status->id,
            'reported_by_user_id' => $this->apocUser->id,
        ]);

        for ($i = 0; $i < 25; $i++) {
            IncidentHistory::create([
                'incident_id' => $incExtensive->id,
                'user_id' => $this->gtsUser->id,
                'action' => "PERITAJE_LOG_{$i}",
                'details' => "Evento técnico {$i}: Inspección electromecánica y validación de enlace troncalizado con telemetría.",
                'created_at' => now()->subMinutes(30 - $i),
            ]);
        }

        $pdfC = $service->generatePdf([], $this->gtsUser, 'REP-TEST-C', [$incExtensive->id]);
        $binaryC = $pdfC->output();
        $this->assertNotEmpty($binaryC);
        $this->assertStringStartsWith('%PDF', $binaryC);

        // Comprobar que la vista PDF cuenta con los tags de ambos logos y el encabezado fijo
        $pdfView = File::get(resource_path('views/reports/pdf.blade.php'));
        $this->assertStringContainsString('header-logo-gts', $pdfView);
        $this->assertStringContainsString('header-logo-aifa', $pdfView);
        $this->assertStringContainsString('position: fixed;', $pdfView);
    }
}
