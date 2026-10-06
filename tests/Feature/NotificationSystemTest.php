<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Priority;
use App\Models\User;
use App\Services\IncidentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_cannot_access_notifications_endpoints(): void
    {
        $response = $this->getJson('/notificaciones');
        $response->assertUnauthorized();

        $responsePost = $this->postJson('/notificaciones/some-uuid/leer');
        $responsePost->assertUnauthorized();

        $responseAll = $this->postJson('/notificaciones/leer-todas');
        $responseAll->assertUnauthorized();
    }

    public function test_incident_creation_dispatches_notifications_by_profile(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $adminUser = User::where('email', 'admin@aifa.aero')->firstOrFail();

        $category = Category::firstOrFail();
        $priority = Priority::where('slug', 'alta')->firstOrFail();

        $response = $this->actingAs($apocUser)->post('/incidencias', [
            'title' => 'Falla en terminal de acceso puerta 4',
            'description' => 'Lector biométrico bloqueado sin responder a comandos.',
            'category_id' => $category->id,
            'priority_id' => $priority->id,
            'location' => 'Terminal 1 - Sala C',
        ]);
        $response->assertSessionHasNoErrors();

        // 1. APOC debe recibir confirmación de registro
        $apocNotifications = $apocUser->fresh()->notifications;
        $this->assertCount(1, $apocNotifications);
        $this->assertEquals('incident_created', $apocNotifications->first()->data['type']);
        $this->assertEquals('Incidencia Registrada', $apocNotifications->first()->data['title']);

        // 2. Agentes GTS deben recibir alerta de nueva incidencia
        $gtsNotifications = $gtsUser->fresh()->notifications;
        $this->assertCount(1, $gtsNotifications);
        $this->assertEquals('new_incident_available', $gtsNotifications->first()->data['type']);
        $this->assertEquals('Nueva Incidencia Disponible', $gtsNotifications->first()->data['title']);

        // 3. Administradores deben recibir alerta informativa
        $adminNotifications = $adminUser->fresh()->notifications;
        $this->assertCount(1, $adminNotifications);
        $this->assertEquals('incident_created_admin', $adminNotifications->first()->data['type']);
    }

    public function test_incident_assignment_notifies_technician_and_reporter(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();

        $incidentService = app(IncidentService::class);
        $incident = $incidentService->createIncident([
            'title' => 'Cámara domo perimetral sin video',
            'description' => 'Señal cortada en torre de control.',
            'location' => 'Torre Norte',
            'category_id' => Category::first()->id,
            'priority_id' => Priority::where('slug', 'alta')->first()->id,
        ], $apocUser);

        // Limpiar notificaciones de creación para evaluar asignación
        $apocUser->fresh()->notifications()->delete();
        $gtsUser->fresh()->notifications()->delete();

        // Asignar al técnico GTS
        $this->actingAs($gtsUser)->post("/incidencias/{$incident->folio}/asignar", [
            'assigned_to_user_id' => $gtsUser->id,
            'comment' => 'Tomando atención inmediata.',
        ]);

        // El técnico asignado se autoasignó, por lo que APOC recibe la notificación
        $apocNotifications = $apocUser->fresh()->notifications;
        $this->assertCount(1, $apocNotifications);
        $this->assertEquals('incident_assigned_reporter', $apocNotifications->first()->data['type']);
        $this->assertStringContainsString($gtsUser->name, $apocNotifications->first()->data['message']);
    }

    public function test_user_can_list_notifications_via_json(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();

        // Crear una incidencia para generar notificación
        app(IncidentService::class)->createIncident([
            'title' => 'Prueba de listado',
            'description' => 'Descripción de prueba.',
            'location' => 'Edificio Central',
            'category_id' => Category::first()->id,
            'priority_id' => Priority::first()->id,
        ], $apocUser);

        $response = $this->actingAs($apocUser)->getJson('/notificaciones');

        $response->assertOk();
        $response->assertJsonStructure([
            'unread_count',
            'notifications' => [
                '*' => ['id', 'type', 'title', 'message', 'action_url', 'folio', 'icon', 'level', 'read', 'created_at'],
            ],
        ]);
        $this->assertEquals(1, $response->json('unread_count'));
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();

        app(IncidentService::class)->createIncident([
            'title' => 'Prueba de marcado',
            'description' => 'Descripción de prueba.',
            'location' => 'Edificio Central',
            'category_id' => Category::first()->id,
            'priority_id' => Priority::first()->id,
        ], $apocUser);

        $notification = $apocUser->unreadNotifications()->firstOrFail();

        $response = $this->actingAs($apocUser)->postJson("/notificaciones/{$notification->id}/leer");

        $response->assertOk();
        $response->assertJson(['success' => true, 'unread_count' => 0]);
        $this->assertEquals(0, $apocUser->fresh()->unreadNotifications()->count());
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();

        // Generar 2 notificaciones
        app(IncidentService::class)->createIncident([
            'title' => 'Incidencia 1',
            'description' => 'Descripción 1.',
            'location' => 'Sector A',
            'category_id' => Category::first()->id,
            'priority_id' => Priority::first()->id,
        ], $apocUser);

        app(IncidentService::class)->createIncident([
            'title' => 'Incidencia 2',
            'description' => 'Descripción 2.',
            'location' => 'Sector B',
            'category_id' => Category::first()->id,
            'priority_id' => Priority::first()->id,
        ], $apocUser);

        $this->assertEquals(2, $apocUser->fresh()->unreadNotifications()->count());

        $response = $this->actingAs($apocUser)->postJson('/notificaciones/leer-todas');

        $response->assertOk();
        $response->assertJson(['success' => true, 'unread_count' => 0]);
        $this->assertEquals(0, $apocUser->fresh()->unreadNotifications()->count());
    }

    public function test_user_cannot_mark_other_users_notification(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();

        app(IncidentService::class)->createIncident([
            'title' => 'Incidencia Cruzada',
            'description' => 'Descripción.',
            'location' => 'Sector A',
            'category_id' => Category::first()->id,
            'priority_id' => Priority::first()->id,
        ], $apocUser);

        $gtsNotification = $gtsUser->unreadNotifications()->firstOrFail();

        // APOC intenta marcar como leída la notificación de GTS
        $response = $this->actingAs($apocUser)->postJson("/notificaciones/{$gtsNotification->id}/leer");

        $response->assertOk();
        // La notificación de GTS sigue sin leer
        $this->assertNull($gtsNotification->fresh()->read_at);
        $this->assertEquals(1, $gtsUser->fresh()->unreadNotifications()->count());
    }

    public function test_notifications_dropdown_renders_correctly_in_dashboard(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();

        $response = $this->actingAs($apocUser)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Centro de Notificaciones GTS');
        $response->assertSee('Sin notificaciones pendientes');
    }
}
