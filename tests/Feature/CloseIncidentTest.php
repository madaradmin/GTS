<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CloseIncidentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_cannot_close_incident(): void
    {
        $incident = Incident::factory()->resuelta()->create();

        $response = $this->post("/incidencias/{$incident->folio}/cerrar", [
            'comment' => 'Intento no autenticado de cierre.',
        ]);

        $response->assertRedirect('/login');
    }

    public function test_gts_user_can_give_vobo_and_close_incident(): void
    {
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $voboPerm = \App\Models\Permission::where('slug', 'incidents.vobo_close')->first();
        if ($voboPerm) {
            $gtsUser->role->permissions()->syncWithoutDetaching([$voboPerm->id]);
        }
        $incident = Incident::factory()->resuelta()->create();

        $this->assertNull($incident->closed_at);

        $response = $this->actingAs($gtsUser)
            ->post("/incidencias/{$incident->folio}/cerrar", [
                'comment' => 'Visto bueno técnico y conformidad otorgado por GTS.',
            ]);

        $response->assertRedirect("/incidencias/{$incident->folio}");
        $response->assertSessionHas('success');

        $incident->refresh();
        $this->assertEquals('CERRADA', $incident->status->code);
        $this->assertNotNull($incident->closed_at);

        $this->assertDatabaseHas('incident_histories', [
            'incident_id' => $incident->id,
            'user_id' => $gtsUser->id,
            'action' => 'CAMBIO_ESTADO',
            'new_value' => 'Cerrada',
        ]);
    }

    public function test_apoc_user_can_close_incident_with_comment(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->resuelta()->create();

        $this->assertNull($incident->closed_at);
        $this->assertEquals('RESUELTA', $incident->status->code);

        $comment = 'Se verificó en sitio la correcta sincronización de los monitores FIDS con el servidor central. Servicio operativo al 100%. Visto bueno otorgado por Supervisor APOC.';

        $response = $this->actingAs($apocUser)
            ->post("/incidencias/{$incident->folio}/cerrar", [
                'comment' => $comment,
            ]);

        $response->assertRedirect(route('incidents.show', $incident->folio));
        $response->assertSessionHas('success');

        $incident->refresh();
        $this->assertEquals('CERRADA', $incident->status->code);
        $this->assertNotNull($incident->closed_at);

        // 1. Auditoría del cambio de estado a CERRADA
        $this->assertDatabaseHas('incident_histories', [
            'incident_id' => $incident->id,
            'action' => 'CAMBIO_ESTADO',
            'user_id' => $apocUser->id,
            'field_name' => 'status_id',
            'old_value' => 'Resuelta',
            'new_value' => 'Cerrada',
        ]);

        // 2. Auditoría inmutable de cierre de expediente con conformidad (RF-05 / RN-02 / RN-04)
        $this->assertDatabaseHas('incident_histories', [
            'incident_id' => $incident->id,
            'action' => 'CERRADA',
            'user_id' => $apocUser->id,
            'field_name' => 'closed_at',
            'comment' => $comment,
        ]);
    }

    public function test_apoc_user_can_close_incident_without_comment(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->resuelta()->create();

        $response = $this->actingAs($apocUser)
            ->post("/incidencias/{$incident->folio}/cerrar", []);

        $response->assertRedirect(route('incidents.show', $incident->folio));
        $response->assertSessionHas('success');

        $incident->refresh();
        $this->assertEquals('CERRADA', $incident->status->code);
        $this->assertNotNull($incident->closed_at);

        // Auditoría inmutable con comentario por defecto
        $this->assertDatabaseHas('incident_histories', [
            'incident_id' => $incident->id,
            'action' => 'CERRADA',
            'user_id' => $apocUser->id,
            'field_name' => 'closed_at',
        ]);
    }

    public function test_admin_user_can_close_incident_when_status_is_resolved(): void
    {
        $adminUser = User::where('email', 'admin@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->resuelta()->create();

        $comment = 'Cierre formal autorizado por Jefatura / Administración de TI y Telecomunicaciones.';

        $response = $this->actingAs($adminUser)
            ->post("/incidencias/{$incident->folio}/cerrar", [
                'comment' => $comment,
            ]);

        $response->assertRedirect(route('incidents.show', $incident->folio));
        $response->assertSessionHas('success');

        $incident->refresh();
        $this->assertEquals('CERRADA', $incident->status->code);
        $this->assertNotNull($incident->closed_at);
    }

    public function test_cannot_close_incident_when_incident_is_not_in_resolved_state(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $comment = 'Intento no permitido de cierre anticipado.';

        // 1. En estado NUEVA
        $incidentNueva = Incident::factory()->nueva()->create();
        $this->actingAs($apocUser)
            ->post("/incidencias/{$incidentNueva->folio}/cerrar", ['comment' => $comment])
            ->assertForbidden();

        // 2. En estado ASIGNADA
        $incidentAsignada = Incident::factory()->asignada()->create();
        $this->actingAs($apocUser)
            ->post("/incidencias/{$incidentAsignada->folio}/cerrar", ['comment' => $comment])
            ->assertForbidden();

        // 3. En estado EN ATENCIÓN
        $incidentEnAtencion = Incident::factory()->enAtencion()->create();
        $this->actingAs($apocUser)
            ->post("/incidencias/{$incidentEnAtencion->folio}/cerrar", ['comment' => $comment])
            ->assertForbidden();

        // 4. En estado terminal CERRADA (no se puede volver a cerrar un expediente ya archivado)
        $incidentCerrada = Incident::factory()->cerrada()->create();
        $this->actingAs($apocUser)
            ->post("/incidencias/{$incidentCerrada->folio}/cerrar", ['comment' => $comment])
            ->assertForbidden();
    }

    public function test_closed_incident_is_immutable_against_further_mutations(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $incidentCerrada = Incident::factory()->cerrada()->create();

        // Intento de reasignar
        $this->actingAs($apocUser)
            ->post("/incidencias/{$incidentCerrada->folio}/asignar", [
                'assigned_to' => $gtsUser->id,
            ])
            ->assertForbidden();

        // Intento de iniciar atención técnica
        $this->actingAs($gtsUser)
            ->post("/incidencias/{$incidentCerrada->folio}/iniciar-atencion")
            ->assertForbidden();

        // Intento de agregar nota técnica
        $this->actingAs($gtsUser)
            ->post("/incidencias/{$incidentCerrada->folio}/notas", [
                'content' => 'Nota extemporánea intentada en ticket cerrado.',
            ])
            ->assertForbidden();

        // Intento de resolver
        $this->actingAs($gtsUser)
            ->post("/incidencias/{$incidentCerrada->folio}/resolver", [
                'solution_description' => 'Solución aplicada después del cierre inmutable.',
            ])
            ->assertForbidden();

        // Intento de rechazar solución
        $this->actingAs($apocUser)
            ->post("/incidencias/{$incidentCerrada->folio}/rechazar-solucion", [
                'rejection_reason' => 'Rechazo fuera de ciclo operativo.',
            ])
            ->assertForbidden();
    }

    public function test_comment_validation_fails_if_exceeds_1000_characters(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->resuelta()->create();

        $response = $this->actingAs($apocUser)
            ->post("/incidencias/{$incident->folio}/cerrar", [
                'comment' => str_repeat('A', 1001),
            ]);

        $response->assertSessionHasErrors('comment');

        $incident->refresh();
        $this->assertEquals('RESUELTA', $incident->status->code);
        $this->assertNull($incident->closed_at);
    }
}
