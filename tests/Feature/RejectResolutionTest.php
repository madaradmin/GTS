<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RejectResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_cannot_reject_resolution(): void
    {
        $incident = Incident::factory()->resuelta()->create();

        $response = $this->post("/incidencias/{$incident->folio}/rechazar-solucion", [
            'rejection_reason' => 'Se verificaron los monitores FIDS y siguen sin sincronizar vuelos de salida.',
        ]);

        $response->assertRedirect('/login');
    }

    public function test_gts_user_cannot_reject_resolution(): void
    {
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->resuelta()->create();

        $response = $this->actingAs($gtsUser)
            ->post("/incidencias/{$incident->folio}/rechazar-solucion", [
                'rejection_reason' => 'Intento indebido de auto-rechazo por parte del técnico GTS.',
            ]);

        $response->assertForbidden();
    }

    public function test_apoc_user_can_reject_resolution_when_incident_is_resolved(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->resuelta()->create();

        $this->assertNotNull($incident->resolved_at);

        $rejectionReason = 'Se realizaron pruebas operativas de pase de abordar en e-Gates Puerta 14 y el sensor óptico continúa marcando timeout tras el tercer intento. La falla mecánica persiste en horas pico.';

        $response = $this->actingAs($apocUser)
            ->post("/incidencias/{$incident->folio}/rechazar-solucion", [
                'rejection_reason' => $rejectionReason,
            ]);

        $response->assertRedirect(route('incidents.show', $incident->folio));
        $response->assertSessionHas('success');

        $incident->refresh();
        $this->assertEquals('EN_ATENCION', $incident->status->code);
        $this->assertEquals($rejectionReason, $incident->rejection_reason);
        $this->assertNull($incident->resolved_at);

        // 1. Auditoría de cambio de estado de regreso a EN_ATENCION
        $this->assertDatabaseHas('incident_histories', [
            'incident_id' => $incident->id,
            'action' => 'CAMBIO_ESTADO',
            'user_id' => $apocUser->id,
            'field_name' => 'status_id',
            'old_value' => 'Resuelta',
            'new_value' => 'En Atención',
        ]);

        // 2. Auditoría inmutable de rechazo con el motivo detallado (RF-05 / RN-02 / RN-04)
        $this->assertDatabaseHas('incident_histories', [
            'incident_id' => $incident->id,
            'action' => 'RECHAZO_SOLUCION',
            'user_id' => $apocUser->id,
            'field_name' => 'rejection_reason',
            'comment' => $rejectionReason,
        ]);
    }

    public function test_admin_user_can_reject_resolution_when_incident_is_resolved(): void
    {
        $adminUser = User::where('email', 'admin@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->resuelta()->create();

        $rejectionReason = 'Auditoría técnica rechaza la solución: los niveles de ruido en la fibra perimetral exceden el umbral de tolerancia. Requiere nuevo fusionado.';

        $response = $this->actingAs($adminUser)
            ->post("/incidencias/{$incident->folio}/rechazar-solucion", [
                'rejection_reason' => $rejectionReason,
            ]);

        $response->assertRedirect(route('incidents.show', $incident->folio));
        $response->assertSessionHas('success');

        $incident->refresh();
        $this->assertEquals('EN_ATENCION', $incident->status->code);
        $this->assertNull($incident->resolved_at);
        $this->assertEquals($rejectionReason, $incident->rejection_reason);
    }

    public function test_cannot_reject_resolution_when_incident_is_not_in_resolved_state(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $reason = 'Intento de rechazo en un estado no permitido en el ciclo de vida.';

        // 1. En estado NUEVA
        $incidentNueva = Incident::factory()->nueva()->create();
        $this->actingAs($apocUser)
            ->post("/incidencias/{$incidentNueva->folio}/rechazar-solucion", ['rejection_reason' => $reason])
            ->assertForbidden();

        // 2. En estado ASIGNADA
        $incidentAsignada = Incident::factory()->asignada()->create();
        $this->actingAs($apocUser)
            ->post("/incidencias/{$incidentAsignada->folio}/rechazar-solucion", ['rejection_reason' => $reason])
            ->assertForbidden();

        // 3. En estado EN ATENCIÓN
        $incidentEnAtencion = Incident::factory()->enAtencion()->create();
        $this->actingAs($apocUser)
            ->post("/incidencias/{$incidentEnAtencion->folio}/rechazar-solucion", ['rejection_reason' => $reason])
            ->assertForbidden();

        // 4. En estado terminal CERRADA
        $incidentCerrada = Incident::factory()->cerrada()->create();
        $this->actingAs($apocUser)
            ->post("/incidencias/{$incidentCerrada->folio}/rechazar-solucion", ['rejection_reason' => $reason])
            ->assertForbidden();
    }

    public function test_rejection_reason_is_required_and_must_have_at_least_10_characters(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->resuelta()->create();

        // 1. Campo vacío
        $response1 = $this->actingAs($apocUser)
            ->post("/incidencias/{$incident->folio}/rechazar-solucion", [
                'rejection_reason' => '',
            ]);
        $response1->assertSessionHasErrors('rejection_reason');

        // 2. Campo demasiado corto (< 10 caracteres)
        $response2 = $this->actingAs($apocUser)
            ->post("/incidencias/{$incident->folio}/rechazar-solucion", [
                'rejection_reason' => 'No sirve',
            ]);
        $response2->assertSessionHasErrors('rejection_reason');

        // El estado debe permanecer en RESUELTA sin alteraciones
        $incident->refresh();
        $this->assertEquals('RESUELTA', $incident->status->code);
        $this->assertNotNull($incident->resolved_at);
        $this->assertNull($incident->rejection_reason);
    }
}
