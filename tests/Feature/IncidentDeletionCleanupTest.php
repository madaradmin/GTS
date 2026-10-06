<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Incident;
use App\Models\Priority;
use App\Models\Status;
use App\Models\User;
use App\Notifications\IncidentNotification;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncidentDeletionCleanupTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $apocUser;

    protected User $gtsUser;

    protected Category $category;

    protected Priority $priority;

    protected Status $statusNueva;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->adminUser = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->firstOrFail();
        $this->apocUser = User::whereHas('role', fn ($q) => $q->where('slug', 'apoc'))->firstOrFail();
        $this->gtsUser = User::whereHas('role', fn ($q) => $q->where('slug', 'gts'))->firstOrFail();
        $this->category = Category::where('is_active', true)->firstOrFail();
        $this->priority = Priority::where('is_active', true)->firstOrFail();
        $this->statusNueva = Status::where('code', 'NUEVA')->firstOrFail();
    }

    /**
     * 1. Comprobar que al eliminar una incidencia, desaparece de "Todas las incidencias" para todos los roles.
     */
    public function test_deleted_incident_disappears_from_all_incidents_list_for_all_roles(): void
    {
        $incident = Incident::create([
            'folio' => 'INC-2026-99001',
            'title' => 'Incidencia para prueba de desaparición general',
            'description' => 'Falla en terminal',
            'location' => 'Puerta A1',
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => $this->statusNueva->id,
            'reported_by_user_id' => $this->apocUser->id,
            'assigned_to_user_id' => $this->gtsUser->id,
            'reported_at' => now(),
        ]);

        // Verificar que antes de eliminar, los 3 roles la visualizan en /incidencias
        $this->actingAs($this->apocUser)->get(route('incidents.index'))->assertSee('INC-2026-99001');
        $this->actingAs($this->gtsUser)->get(route('incidents.index'))->assertSee('INC-2026-99001');
        $this->actingAs($this->adminUser)->get(route('incidents.index'))->assertSee('INC-2026-99001');

        // Admin elimina la incidencia
        $this->actingAs($this->adminUser)
            ->delete(route('audit.incidents.destroy', $incident->folio))
            ->assertRedirect(route('audit.index'));

        // Verificar que desapareció para APOC, GTS y ADMIN
        $this->actingAs($this->apocUser)->get(route('incidents.index'))->assertDontSee('INC-2026-99001');
        $this->actingAs($this->gtsUser)->get(route('incidents.index'))->assertDontSee('INC-2026-99001');
        $this->actingAs($this->adminUser)->get(route('incidents.index'))->assertDontSee('INC-2026-99001');
    }

    /**
     * 2. Comprobar que al eliminar una incidencia, desaparece de "Mis incidencias" para GTS y ADMIN.
     */
    public function test_deleted_incident_disappears_from_my_incidents_when_assigned(): void
    {
        $incident = Incident::create([
            'folio' => 'INC-2026-99002',
            'title' => 'Incidencia asignada en mis incidencias',
            'description' => 'Falla en antena',
            'location' => 'Zona Militar',
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => $this->statusNueva->id,
            'reported_by_user_id' => $this->apocUser->id,
            'assigned_to_user_id' => $this->gtsUser->id,
            'reported_at' => now(),
        ]);

        // GTS la ve en Mis Incidencias
        $this->actingAs($this->gtsUser)->get(route('incidents.my'))->assertSee('INC-2026-99002');

        // Admin la elimina
        $this->actingAs($this->adminUser)
            ->delete(route('audit.incidents.destroy', $incident->folio));

        // GTS ya no la ve en Mis Incidencias
        $this->actingAs($this->gtsUser)->get(route('incidents.my'))->assertDontSee('INC-2026-99002');
    }

    /**
     * 3. Comprobar que desaparece del Dashboard (recientes y métricas) para todos los roles.
     */
    public function test_deleted_incident_disappears_from_dashboard_for_all_roles(): void
    {
        $incident = Incident::create([
            'folio' => 'INC-2026-99003',
            'title' => 'Incidencia en Dashboard Reciente',
            'description' => 'Falla en control de acceso',
            'location' => 'Filtro 3',
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => $this->statusNueva->id,
            'reported_by_user_id' => $this->apocUser->id,
            'reported_at' => now(),
        ]);

        // Verificable en Dashboard
        $this->actingAs($this->apocUser)->get(route('dashboard'))->assertSee('INC-2026-99003');

        // Admin elimina
        $this->actingAs($this->adminUser)
            ->delete(route('audit.incidents.destroy', $incident->folio));

        // Desaparece del Dashboard para todos
        $this->actingAs($this->apocUser)->get(route('dashboard'))->assertDontSee('INC-2026-99003');
        $this->actingAs($this->gtsUser)->get(route('dashboard'))->assertDontSee('INC-2026-99003');
        $this->actingAs($this->adminUser)->get(route('dashboard'))->assertDontSee('INC-2026-99003');
    }

    /**
     * 4. Comprobar que desaparece de reportes y consultas de telemetría.
     */
    public function test_deleted_incident_disappears_from_report_builder_and_search(): void
    {
        $incident = Incident::create([
            'folio' => 'INC-2026-99004',
            'title' => 'Incidencia única para reporte y búsqueda',
            'description' => 'Falla en sensor',
            'location' => 'Pista Norte',
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => $this->statusNueva->id,
            'reported_by_user_id' => $this->apocUser->id,
            'reported_at' => now(),
        ]);

        $reportService = app(ReportService::class);
        $this->assertTrue($reportService->buildQuery(['search' => 'INC-2026-99004'])->exists());

        // Admin elimina
        $this->actingAs($this->adminUser)
            ->delete(route('audit.incidents.destroy', $incident->folio));

        // Ya no existe en la consulta del generador de reportes
        $this->assertFalse($reportService->buildQuery(['search' => 'INC-2026-99004'])->exists());

        // La búsqueda en /incidencias no la encuentra
        $this->actingAs($this->adminUser)
            ->get(route('incidents.index', ['search' => 'INC-2026-99004']))
            ->assertDontSee('Incidencia única para reporte y búsqueda')
            ->assertSee('No se encontraron incidencias');
    }

    /**
     * 5. Comprobar que las NOTIFICACIONES vinculadas se purgan para TODOS los roles
     *    y no producen errores al abrir el dropdown ni intentar consultarlas.
     */
    public function test_notifications_are_purged_for_all_roles_and_prevent_broken_alerts(): void
    {
        $incident = Incident::create([
            'folio' => 'INC-2026-0106',
            'title' => 'Incidencia Caso Real 0106',
            'description' => 'Descripción operativa',
            'location' => 'Torre de Control',
            'category_id' => $this->category->id,
            'priority_id' => $this->priority->id,
            'status_id' => $this->statusNueva->id,
            'reported_by_user_id' => $this->apocUser->id,
            'assigned_to_user_id' => $this->gtsUser->id,
            'reported_at' => now(),
        ]);

        // Simular despacho de notificaciones a los 3 roles
        $this->apocUser->notify(new IncidentNotification(
            type: 'incident_created',
            title: 'Incidencia Registrada',
            message: 'La incidencia INC-2026-0106 ha sido despachada.',
            incidentId: $incident->id,
            folio: $incident->folio,
            actionUrl: route('incidents.show', $incident->folio)
        ));

        $this->gtsUser->notify(new IncidentNotification(
            type: 'incident_assigned',
            title: 'Incidencia Asignada',
            message: 'Se te asignó el ticket INC-2026-0106.',
            incidentId: $incident->id,
            folio: $incident->folio,
            actionUrl: route('incidents.show', $incident->folio)
        ));

        $this->adminUser->notify(new IncidentNotification(
            type: 'incident_created_admin',
            title: 'Nueva Incidencia: INC-2026-0106',
            message: 'Apertura de INC-2026-0106.',
            incidentId: $incident->id,
            folio: $incident->folio,
            actionUrl: route('incidents.show', $incident->folio)
        ));

        // Comprobar que las 3 notificaciones existen en base de datos
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $this->apocUser->id]);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $this->gtsUser->id]);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $this->adminUser->id]);

        // Los 3 roles visualizan la alerta en su interfaz
        $this->actingAs($this->apocUser)->get(route('dashboard'))->assertSee('INC-2026-0106');
        $this->actingAs($this->gtsUser)->get(route('dashboard'))->assertSee('INC-2026-0106');
        $this->actingAs($this->adminUser)->get(route('dashboard'))->assertSee('INC-2026-0106');

        // Admin ejecuta la eliminación irreversible
        $response = $this->actingAs($this->adminUser)
            ->delete(route('audit.incidents.destroy', $incident->folio));

        $response->assertRedirect(route('audit.index'));
        $response->assertSessionHas('status_message');

        // VERIFICACIÓN CLAVE: Las notificaciones fueron purgadas de la base de datos
        $this->assertDatabaseMissing('notifications', ['notifiable_id' => $this->apocUser->id]);
        $this->assertDatabaseMissing('notifications', ['notifiable_id' => $this->gtsUser->id]);
        $this->assertDatabaseMissing('notifications', ['notifiable_id' => $this->adminUser->id]);

        // Comprobar que ningún rol visualiza ya la alerta en el dropdown ni produce errores 500
        $this->actingAs($this->apocUser)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('INC-2026-0106');

        $this->actingAs($this->gtsUser)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('INC-2026-0106');

        $this->actingAs($this->adminUser)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('INC-2026-0106');

        // Al intentar acceder por URL al expediente eliminado se obtiene 404 limpio
        $this->actingAs($this->apocUser)->get('/incidencias/INC-2026-0106')->assertNotFound();
    }
}
