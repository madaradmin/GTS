<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\IncidentHistory;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class AuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_is_redirected_to_login_when_accessing_audit_log(): void
    {
        $response = $this->get('/auditoria');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_apoc_user_cannot_access_audit_log(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();

        $response = $this->actingAs($apocUser)->get('/auditoria');

        $response->assertForbidden();
    }

    public function test_authenticated_gts_user_cannot_access_audit_log(): void
    {
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();

        $response = $this->actingAs($gtsUser)->get('/auditoria');

        $response->assertForbidden();
    }

    public function test_authenticated_admin_user_can_access_audit_log(): void
    {
        $adminUser = User::where('email', 'admin@aifa.aero')->firstOrFail();

        $response = $this->actingAs($adminUser)->get('/auditoria');

        $response->assertOk();
        $response->assertViewIs('audit.index');
    }

    public function test_inactive_user_cannot_access_audit_log(): void
    {
        $inactiveUser = User::factory()->create(['is_active' => false]);

        $response = $this->actingAs($inactiveUser)->get('/auditoria');

        $response->assertRedirect('/login');
    }

    public function test_incident_history_is_strictly_immutable_against_updates(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->create();

        $history = IncidentHistory::create([
            'incident_id' => $incident->id,
            'user_id' => $apocUser->id,
            'action' => 'CREADA',
            'comment' => 'Evento original e inmutable.',
            'created_at' => now(),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('La bitácora de auditoría es estrictamente inmutable (Append-Only) por normativa aeronáutica.');

        $history->comment = 'Intento fraudulento de alteración retrospectiva.';
        $history->save();
    }

    public function test_incident_history_is_strictly_immutable_against_deletion(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->create();

        $history = IncidentHistory::create([
            'incident_id' => $incident->id,
            'user_id' => $apocUser->id,
            'action' => 'CREADA',
            'comment' => 'Evento original protegido contra borrado.',
            'created_at' => now(),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('La eliminación de registros de auditoría histórica está terminantemente prohibida.');

        $history->delete();
    }

    public function test_audit_log_filters_by_action(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->create();

        IncidentHistory::create([
            'incident_id' => $incident->id,
            'user_id' => $gtsUser->id,
            'action' => 'RESOLUCION',
            'comment' => 'Solución técnica especial de prueba de filtrado.',
            'created_at' => now(),
        ]);

        IncidentHistory::create([
            'incident_id' => $incident->id,
            'user_id' => $gtsUser->id,
            'action' => 'NOTA_TECNICA',
            'comment' => 'Nota de campo técnica especial oculta en filtro.',
            'created_at' => now(),
        ]);

        $adminUser = User::where('email', 'admin@aifa.aero')->firstOrFail();
        $response = $this->actingAs($adminUser)->get('/auditoria?action=RESOLUCION');

        $response->assertOk();
        $response->assertSee('Solución técnica especial de prueba de filtrado.');
        $response->assertDontSee('Nota de campo técnica especial oculta en filtro.');
    }

    public function test_audit_log_filters_by_user(): void
    {
        $adminUser = User::where('email', 'admin@aifa.aero')->firstOrFail();
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->create();

        IncidentHistory::create([
            'incident_id' => $incident->id,
            'user_id' => $apocUser->id,
            'action' => 'CERRADA',
            'comment' => 'Cierre formal por APOC para prueba de usuario.',
            'created_at' => now(),
        ]);

        IncidentHistory::create([
            'incident_id' => $incident->id,
            'user_id' => $gtsUser->id,
            'action' => 'RESOLUCION',
            'comment' => 'Resolución técnica por GTS para prueba de usuario.',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($adminUser)->get("/auditoria?user_id={$apocUser->id}");

        $response->assertOk();
        $response->assertSee('Cierre formal por APOC para prueba de usuario.');
        $response->assertDontSee('Resolución técnica por GTS para prueba de usuario.');
    }

    public function test_audit_log_filters_by_search_term(): void
    {
        $adminUser = User::where('email', 'admin@aifa.aero')->firstOrFail();
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->create();

        IncidentHistory::create([
            'incident_id' => $incident->id,
            'user_id' => $apocUser->id,
            'action' => 'NOTA_TECNICA',
            'comment' => 'TerminoTermicoCCTV991',
            'created_at' => now(),
        ]);

        IncidentHistory::create([
            'incident_id' => $incident->id,
            'user_id' => $apocUser->id,
            'action' => 'NOTA_TECNICA',
            'comment' => 'Comentario ordinario sin coincidencia.',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($adminUser)->get('/auditoria?search=TerminoTermicoCCTV991');

        $response->assertOk();
        $response->assertSee('TerminoTermicoCCTV991');
        $response->assertDontSee('Comentario ordinario sin coincidencia.');
    }

    public function test_audit_log_filters_by_date_range(): void
    {
        $adminUser = User::where('email', 'admin@aifa.aero')->firstOrFail();
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->create();

        IncidentHistory::create([
            'incident_id' => $incident->id,
            'user_id' => $apocUser->id,
            'action' => 'NOTA_TECNICA',
            'comment' => 'Evento histórico de fecha antigua.',
            'created_at' => '2026-01-10 10:00:00',
        ]);

        IncidentHistory::create([
            'incident_id' => $incident->id,
            'user_id' => $apocUser->id,
            'action' => 'NOTA_TECNICA',
            'comment' => 'Evento en fecha activa del rango pericial.',
            'created_at' => '2026-05-15 12:00:00',
        ]);

        $response = $this->actingAs($adminUser)->get('/auditoria?date_from=2026-05-01&date_to=2026-05-31');

        $response->assertOk();
        $response->assertSee('Evento en fecha activa del rango pericial.');
        $response->assertDontSee('Evento histórico de fecha antigua.');
    }

    public function test_audit_service_statistics_calculation(): void
    {
        $auditService = app(AuditService::class);
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->create();

        IncidentHistory::create([
            'incident_id' => $incident->id,
            'user_id' => $apocUser->id,
            'action' => 'CAMBIO_ESTADO',
            'created_at' => now(),
        ]);

        IncidentHistory::create([
            'incident_id' => $incident->id,
            'user_id' => $apocUser->id,
            'action' => 'RESOLUCION',
            'created_at' => now(),
        ]);

        $stats = $auditService->getStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_events', $stats);
        $this->assertArrayHasKey('state_changes', $stats);
        $this->assertArrayHasKey('technical_notes', $stats);
        $this->assertArrayHasKey('resolutions', $stats);
        $this->assertArrayHasKey('rejections', $stats);
        $this->assertArrayHasKey('closures', $stats);
        $this->assertGreaterThanOrEqual(1, $stats['state_changes']);
        $this->assertGreaterThanOrEqual(1, $stats['resolutions']);
    }

    public function test_sidebar_displays_auditoria_option_and_active_state(): void
    {
        $adminUser = User::where('email', 'admin@aifa.aero')->firstOrFail();

        // Al visitar /auditoria, el sidebar debe mostrar la opción activa
        $response = $this->actingAs($adminUser)->get(route('audit.index'));

        $response->assertOk();
        $response->assertSee(route('audit.index'));
        $response->assertSee('Auditoría');
        $response->assertSee('manage_history');
    }

    public function test_sidebar_does_not_display_auditoria_option_for_apoc_and_gts_profiles(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $adminUser = User::where('email', 'admin@aifa.aero')->firstOrFail();

        // APOC no ve Auditoría en el sidebar
        $responseApoc = $this->actingAs($apocUser)->get(route('dashboard'));
        $responseApoc->assertOk();
        $responseApoc->assertDontSee(route('audit.index'));

        // GTS no ve Auditoría en el sidebar
        $responseGts = $this->actingAs($gtsUser)->get(route('dashboard'));
        $responseGts->assertOk();
        $responseGts->assertDontSee(route('audit.index'));

        // ADMIN sí ve Auditoría en el sidebar
        $responseAdmin = $this->actingAs($adminUser)->get(route('dashboard'));
        $responseAdmin->assertOk();
        $responseAdmin->assertSee(route('audit.index'));
        $responseAdmin->assertSee('Auditoría');
    }

    public function test_audit_index_displays_navigable_breadcrumbs(): void
    {
        $adminUser = User::where('email', 'admin@aifa.aero')->firstOrFail();

        $response = $this->actingAs($adminUser)->get(route('audit.index'));

        $response->assertOk();
        $response->assertSee(route('dashboard'));
        $response->assertSee('Inicio');
        $response->assertSee('Trazabilidad Aeronáutica');
        $response->assertSee('Bitácora Pericial');
    }
}
