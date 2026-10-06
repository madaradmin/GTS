<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Incident;
use App\Models\Priority;
use App\Models\Role;
use App\Models\Status;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FinalSystemIntegrationAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $apocUser;

    protected User $gtsCoordinator;

    protected User $gtsTechActive;

    protected User $gtsTechUnavailable;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $apocRole = Role::where('slug', 'apoc')->firstOrFail();
        $gtsRole = Role::where('slug', 'gts')->firstOrFail();
        $adminRole = Role::where('slug', 'admin')->firstOrFail();

        $this->apocUser = User::factory()->create([
            'name' => 'Operador APOC Sala',
            'email' => 'apoc.audit@aifa.aero',
            'role_id' => $apocRole->id,
            'is_active' => true,
        ]);

        $this->gtsCoordinator = User::factory()->create([
            'name' => 'Coordinador GTS Guardia',
            'email' => 'gts.coord.audit@aifa.aero',
            'role_id' => $gtsRole->id,
            'is_active' => true,
        ]);

        $this->gtsTechActive = User::factory()->create([
            'name' => 'Técnico GTS Disponible',
            'email' => 'gts.active.audit@aifa.aero',
            'role_id' => $gtsRole->id,
            'is_active' => true,
        ]);

        $this->gtsTechUnavailable = User::factory()->create([
            'name' => 'Técnico GTS Receso',
            'email' => 'gts.unavail.audit@aifa.aero',
            'role_id' => $gtsRole->id,
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'name' => 'Administrador General AIFA',
            'email' => 'admin.audit@aifa.aero',
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 1. NOTIFICACIONES (APOC, GTS, ADMIN)
    |--------------------------------------------------------------------------
    */

    public function test_notifications_lifecycle_and_segregation(): void
    {
        // APOC reporta incidencia -> Notifica a GTS y ADMIN
        $incident = Incident::factory()->nueva()->create([
            'reported_by_user_id' => $this->apocUser->id,
            'title' => 'Cámara de Seguridad Fuera de Línea',
        ]);

        // Simular despacho de notificación de nueva incidencia
        app(NotificationService::class)->notifyIncidentCreated($incident, $this->apocUser);

        // GTS y Admin deben tener notificaciones no leídas
        $gtsUnread = $this->gtsCoordinator->unreadNotifications()->count();
        $adminUnread = $this->adminUser->unreadNotifications()->count();
        $this->assertGreaterThan(0, $gtsUnread);
        $this->assertGreaterThan(0, $adminUnread);

        // Endpoint de notificaciones para GTS
        $response = $this->actingAs($this->gtsCoordinator)->get(route('notifications.index'));
        $response->assertOk();
        $response->assertSee($incident->folio);

        // Marcar todas como leídas
        $postResponse = $this->actingAs($this->gtsCoordinator)->post(route('notifications.mark-all-as-read'));
        $postResponse->assertRedirect();
        $this->assertEquals(0, $this->gtsCoordinator->fresh()->unreadNotifications()->count());

        // Segregación: APOC no puede marcar como leída la notificación privada de ADMIN
        $notification = $this->adminUser->notifications()->first();
        if ($notification) {
            $this->actingAs($this->apocUser)->post(route('notifications.mark-as-read', $notification->id));
            $this->assertNull($notification->fresh()->read_at);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 2. BUSCADOR GLOBAL Y EN SIDEBAR
    |--------------------------------------------------------------------------
    */

    public function test_global_search_and_sidebar_quick_search(): void
    {
        $incident = Incident::factory()->nueva()->create([
            'title' => 'Falla en Torniquete de Pasillo 4',
            'location' => 'Terminal 1 - Pasillo 4',
        ]);

        // Verificación de input de búsqueda en sidebar
        $responseDashboard = $this->actingAs($this->gtsCoordinator)->get(route('dashboard'));
        $responseDashboard->assertOk();
        $responseDashboard->assertSee('name="search"', false);
        $responseDashboard->assertSee('placeholder="Buscar incidencias..."', false);

        // Búsqueda completa GET en índice por término
        $responseIndex = $this->actingAs($this->gtsCoordinator)->get(route('incidents.index', ['search' => 'Torniquete']));
        $responseIndex->assertOk();
        $responseIndex->assertSee($incident->folio);
        $responseIndex->assertSee('Falla en Torniquete de Pasillo 4');
    }

    /*
    |--------------------------------------------------------------------------
    | 3. PRIORIDADES Y CLASIFICACIÓN TÁCTICA
    |--------------------------------------------------------------------------
    */

    public function test_priority_levels_and_badges_render_correctly(): void
    {
        $criticalPriority = Priority::where('slug', 'critica')->firstOrFail();
        $incident = Incident::factory()->create([
            'priority_id' => $criticalPriority->id,
            'title' => 'Incidencia Crítica Perimetral',
        ]);

        $response = $this->actingAs($this->gtsCoordinator)->get(route('incidents.show', $incident->folio));
        $response->assertOk();
        $response->assertSee($criticalPriority->name);
    }

    /*
    |--------------------------------------------------------------------------
    | 4. CATÁLOGOS E INFRAESTRUCTURA (APOC, GTS, ADMIN)
    |--------------------------------------------------------------------------
    */

    public function test_catalogs_telemetry_and_rbac_permissions(): void
    {
        $category = Category::where('is_active', true)->firstOrFail();

        // 1. APOC: Puede consultar telemetría pero NO crear ni cambiar estado operativo
        $responseApoc = $this->actingAs($this->apocUser)->get(route('catalogs.index'));
        $responseApoc->assertOk();
        $responseApoc->assertSee('Infraestructura Tecnológica');

        $responseApocCreate = $this->actingAs($this->apocUser)->post(route('catalogs.store'), [
            'name' => 'Nuevo Subsistema',
            'description' => 'Test',
        ]);
        $responseApocCreate->assertForbidden();

        // 2. GTS: Puede consultar telemetría pero NO crear ni cambiar estado
        $responseGts = $this->actingAs($this->gtsCoordinator)->get(route('catalogs.index'));
        $responseGts->assertOk();

        $responseGtsToggle = $this->actingAs($this->gtsCoordinator)->post(route('catalogs.status', $category), ['status' => 'INACTIVO']);
        $responseGtsToggle->assertForbidden();

        // 3. ADMIN: Puede dar de alta, editar y cambiar estado operativo
        $responseAdmin = $this->actingAs($this->adminUser)->get(route('catalogs.index'));
        $responseAdmin->assertOk();

        $responseAdminToggle = $this->actingAs($this->adminUser)->post(route('catalogs.status', $category), ['status' => 'INACTIVO']);
        $responseAdminToggle->assertRedirect();
        $responseAdminToggle->assertSessionHas('success');
    }

    /*
    |--------------------------------------------------------------------------
    | 5. AUDITORÍA (RUTA, SIDEBAR, PERMISOS)
    |--------------------------------------------------------------------------
    */

    public function test_audit_log_access_and_filtering(): void
    {
        // GTS no puede consultar auditoría (403 Forbidden)
        $responseGts = $this->actingAs($this->gtsCoordinator)->get(route('audit.index'));
        $responseGts->assertForbidden();

        // APOC activo no puede consultar auditoría (403 Forbidden)
        $responseApoc = $this->actingAs($this->apocUser)->get(route('audit.index'));
        $responseApoc->assertForbidden();

        // ADMIN posee acceso exclusivo a auditoría
        $responseAdmin = $this->actingAs($this->adminUser)->get(route('audit.index'));
        $responseAdmin->assertOk();
        $responseAdmin->assertSee('Bitácora General de Auditoría');

        // Usuario inactivo es redirigido al login con error institucional
        $inactiveUser = User::factory()->inactive()->create();
        $responseInactive = $this->actingAs($inactiveUser)->get(route('audit.index'));
        $responseInactive->assertRedirect(route('login'));
        $responseInactive->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    /*
    |--------------------------------------------------------------------------
    | 6. REPORTES (FILTROS, PDF, EMAIL, WHATSAPP)
    |--------------------------------------------------------------------------
    */

    public function test_reports_generation_and_delivery_channels(): void
    {
        $response = $this->actingAs($this->gtsCoordinator)->get(route('reports.index', ['period_preset' => 'today']));
        $response->assertOk();
        $response->assertSee('REPORTE OPERATIVO DE INCIDENCIAS');

        // Exportación a PDF institucional
        $responsePdf = $this->actingAs($this->gtsCoordinator)->get(route('reports.pdf', ['period_preset' => 'today']));
        $responsePdf->assertOk();
        $this->assertEquals('application/pdf', $responsePdf->headers->get('content-type'));

        // Compartición por WhatsApp (generación de enlace compatible sin inventar integraciones)
        $responseWa = $this->actingAs($this->gtsCoordinator)->postJson(route('reports.whatsapp'), [
            'phone' => '5512345678',
            'filters' => ['period_preset' => 'today'],
        ]);
        $responseWa->assertOk();
        $responseWa->assertJsonFragment(['success' => true]);
    }

    /*
    |--------------------------------------------------------------------------
    | 7. DISPONIBILIDAD Y ASIGNACIÓN (PASOS 8 Y 9)
    |--------------------------------------------------------------------------
    */

    public function test_shift_status_route_eliminated_and_standard_assignment(): void
    {
        // 1. Verificar que ruta /profile/shift-status devuelve 404
        $shiftResponse = $this->actingAs($this->gtsTechActive)->post('/profile/shift-status', [
            'status' => 'NO DISPONIBLE',
        ]);
        $shiftResponse->assertStatus(404);

        // 2. Asignación directa y limpia de incidencia a especialista técnico
        $incident = Incident::factory()->nueva()->create();

        $assignResponse = $this->actingAs($this->gtsCoordinator)->post(route('incidents.assign', $incident), [
            'assigned_to_user_id' => $this->gtsTechActive->id,
            'comment' => 'Asignado de guardia preventiva',
        ]);
        $assignResponse->assertRedirect();
        $assignResponse->assertSessionHas('success');
        $this->assertStringNotContainsString('NO DISPONIBLE', session('success'));
    }

    /*
    |--------------------------------------------------------------------------
    | 8. PRUEBAS DE REGRESIÓN: CICLO DE VIDA COMPLETO DE INCIDENCIA
    |--------------------------------------------------------------------------
    */

    public function test_full_incident_lifecycle_regression(): void
    {
        Storage::fake('public');
        $category = Category::where('is_active', true)->firstOrFail();
        $priority = Priority::where('slug', 'alta')->firstOrFail();

        // 1. CREAR: APOC crea incidencia (NUEVA)
        $storeResponse = $this->actingAs($this->apocUser)->post(route('incidents.store'), [
            'title' => 'Falla de Video en Puerta 12',
            'description' => 'La cámara Domo PTZ presenta distorsión en la señal de video.',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'location' => 'Sala de Última Espera - Puerta 12',
            'attachments' => [
                UploadedFile::fake()->create('evidencia1.jpg', 100, 'image/jpeg'),
            ],
        ]);

        $incident = Incident::where('title', 'Falla de Video en Puerta 12')->firstOrFail();
        $storeResponse->assertRedirect(route('incidents.show', $incident->folio));
        $this->assertEquals('NUEVA', $incident->status->code);
        $this->assertCount(1, $incident->attachments);

        // 2. ASIGNAR: GTS asigna especialista (ASIGNADA)
        $assignResponse = $this->actingAs($this->gtsCoordinator)->post(route('incidents.assign', $incident), [
            'assigned_to_user_id' => $this->gtsCoordinator->id,
            'comment' => 'Toma técnica directa para revisión de switch de borde.',
        ]);
        $assignResponse->assertRedirect();
        $incident->refresh();
        $this->assertEquals('ASIGNADA', $incident->status->code);
        $this->assertEquals($this->gtsCoordinator->id, $incident->assigned_to_user_id);

        // 3. ATENCIÓN: Especialista inicia atención técnica (EN_ATENCION)
        $attentionResponse = $this->actingAs($this->gtsCoordinator)->post(route('incidents.start-attention', $incident), [
            'comment' => 'En sitio revisando cableado UTP y alimentación PoE.',
        ]);
        $attentionResponse->assertRedirect();
        $incident->refresh();
        $this->assertEquals('EN_ATENCION', $incident->status->code);

        // 4. NOTA TÉCNICA: Se agrega nota de campo
        $noteResponse = $this->actingAs($this->gtsCoordinator)->post(route('incidents.add-note', $incident), [
            'note' => 'Conector RJ45 reemplazado y crimpado según norma T568B.',
        ]);
        $noteResponse->assertRedirect();

        // 5. RESOLVER: Especialista declara solución técnica (RESUELTA)
        $resolveResponse = $this->actingAs($this->gtsCoordinator)->post(route('incidents.resolve', $incident), [
            'solution_description' => 'Se restableció el enlace de datos. Señal de video RTSP 1080p estable sin pérdida de paquetes.',
        ]);
        $resolveResponse->assertRedirect();
        $incident->refresh();
        $this->assertEquals('RESUELTA', $incident->status->code);

        // Usuario inactivo NO puede cerrar la incidencia
        $inactiveUser = User::factory()->create(['is_active' => false]);
        $invalidCloseResponse = $this->actingAs($inactiveUser)->post(route('incidents.close', $incident), [
            'comment' => 'Intento de cierre por usuario inactivo',
        ]);
        $invalidCloseResponse->assertForbidden();

        // 6. CERRAR: APOC otorga el visto bueno y cierra formalmente (CERRADA)
        $closeResponse = $this->actingAs($this->apocUser)->post(route('incidents.close', $incident), [
            'comment' => 'Servicio verificado en videowall de APOC. Conforme con la resolución técnica.',
        ]);
        $closeResponse->assertRedirect();
        $incident->refresh();
        $this->assertEquals('CERRADA', $incident->status->code);
        $this->assertNotNull($incident->closed_at);

        // 7. AUDITORÍA INMUTABLE: Cada paso debe estar registrado
        $histories = $incident->histories()->pluck('action')->toArray();
        $this->assertContains('CREADA', $histories);
        $this->assertContains('ASIGNADA', $histories);
        $this->assertContains('CAMBIO_ESTADO', $histories);
        $this->assertContains('NOTA_TECNICA', $histories);
        $this->assertContains('RESUELTA', $histories);
        $this->assertContains('CERRADA', $histories);
    }

    /*
    |--------------------------------------------------------------------------
    | 9. INTERFAZ Y DASHBOARD TÁCTICO
    |--------------------------------------------------------------------------
    */

    public function test_dashboard_and_navigation_across_all_profiles(): void
    {
        // APOC Dashboard
        $responseApoc = $this->actingAs($this->apocUser)->get(route('dashboard'));
        $responseApoc->assertOk();
        $responseApoc->assertSee('Acciones rápidas');

        // GTS Dashboard
        $responseGts = $this->actingAs($this->gtsCoordinator)->get(route('dashboard'));
        $responseGts->assertOk();

        // ADMIN Dashboard
        $responseAdmin = $this->actingAs($this->adminUser)->get(route('dashboard'));
        $responseAdmin->assertOk();
    }
}
