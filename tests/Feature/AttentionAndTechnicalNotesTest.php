<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttentionAndTechnicalNotesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_cannot_start_attention_or_add_notes(): void
    {
        $incident = Incident::factory()->asignada()->create();

        $this->post("/incidencias/{$incident->folio}/iniciar-atencion")
            ->assertRedirect('/login');

        $this->post("/incidencias/{$incident->folio}/notas", ['note' => 'Nota de prueba'])
            ->assertRedirect('/login');
    }

    public function test_apoc_user_cannot_start_attention_nor_add_notes(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->asignada()->create();

        $this->actingAs($apocUser)
            ->post("/incidencias/{$incident->folio}/iniciar-atencion")
            ->assertForbidden();

        $incidentEnAtencion = Incident::factory()->enAtencion()->create();

        $this->actingAs($apocUser)
            ->post("/incidencias/{$incidentEnAtencion->folio}/notas", ['note' => 'Intento de nota por APOC'])
            ->assertForbidden();
    }

    public function test_gts_user_can_start_attention_from_assigned_state(): void
    {
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->asignada($gtsUser)->create();

        $response = $this->actingAs($gtsUser)
            ->post("/incidencias/{$incident->folio}/iniciar-atencion", [
                'comment' => 'Iniciando pruebas de continuidad en rack IDF Nivel 2.',
            ]);

        $response->assertRedirect(route('incidents.show', $incident->folio));
        $response->assertSessionHas('success');

        $incident->refresh();
        $this->assertEquals('EN_ATENCION', $incident->status->code);
        $this->assertNotNull($incident->attended_at);

        // Verificar auditoría de cambio de estado
        $this->assertDatabaseHas('incident_histories', [
            'incident_id' => $incident->id,
            'action' => 'CAMBIO_ESTADO',
            'user_id' => $gtsUser->id,
            'new_value' => 'En Atención',
        ]);
    }

    public function test_gts_user_can_start_attention_from_new_state_with_auto_assignment(): void
    {
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->nueva()->create();

        $this->assertNull($incident->assigned_to_user_id);

        $response = $this->actingAs($gtsUser)
            ->post("/incidencias/{$incident->folio}/iniciar-atencion");

        $response->assertRedirect(route('incidents.show', $incident->folio));
        $incident->refresh();

        // Debe auto-asignarse y transicionar a EN_ATENCION
        $this->assertEquals('EN_ATENCION', $incident->status->code);
        $this->assertEquals($gtsUser->id, $incident->assigned_to_user_id);
        $this->assertNotNull($incident->assigned_at);
        $this->assertNotNull($incident->attended_at);

        // Verificar ambas auditorías (ASIGNADA y CAMBIO_ESTADO)
        $this->assertDatabaseHas('incident_histories', [
            'incident_id' => $incident->id,
            'action' => 'ASIGNADA',
            'user_id' => $gtsUser->id,
        ]);
        $this->assertDatabaseHas('incident_histories', [
            'incident_id' => $incident->id,
            'action' => 'CAMBIO_ESTADO',
            'user_id' => $gtsUser->id,
            'new_value' => 'En Atención',
        ]);
    }

    public function test_cannot_start_attention_on_resolved_or_closed_incident(): void
    {
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $incidentResuelta = Incident::factory()->resuelta()->create();
        $incidentCerrada = Incident::factory()->cerrada()->create();

        $this->actingAs($gtsUser)
            ->post("/incidencias/{$incidentResuelta->folio}/iniciar-atencion")
            ->assertForbidden();

        $this->actingAs($gtsUser)
            ->post("/incidencias/{$incidentCerrada->folio}/iniciar-atencion")
            ->assertForbidden();
    }

    public function test_gts_user_can_add_technical_note_when_incident_is_in_attention(): void
    {
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->enAtencion($gtsUser)->create();

        $payload = [
            'note' => 'Se verificó la fuente de poder del switch PoE. Se sustituyó fusible de protección de 2A y se restableció el enlace óptico.',
        ];

        $response = $this->actingAs($gtsUser)
            ->post("/incidencias/{$incident->folio}/notas", $payload);

        $response->assertRedirect(route('incidents.show', $incident->folio));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('incident_histories', [
            'incident_id' => $incident->id,
            'action' => 'NOTA_TECNICA',
            'user_id' => $gtsUser->id,
            'comment' => $payload['note'],
        ]);
    }

    public function test_cannot_add_technical_note_when_incident_is_not_in_attention(): void
    {
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();

        // 1. En estado NUEVA no se admiten notas técnicas de campo
        $incidentNueva = Incident::factory()->nueva()->create();
        $this->actingAs($gtsUser)
            ->post("/incidencias/{$incidentNueva->folio}/notas", ['note' => 'Nota en ticket nuevo'])
            ->assertForbidden();

        // 2. En estado ASIGNADA no se admiten hasta iniciar atención
        $incidentAsignada = Incident::factory()->asignada()->create();
        $this->actingAs($gtsUser)
            ->post("/incidencias/{$incidentAsignada->folio}/notas", ['note' => 'Nota en ticket asignado'])
            ->assertForbidden();
    }

    public function test_technical_note_validation_rules(): void
    {
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->enAtencion($gtsUser)->create();

        // Nota vacía
        $response1 = $this->actingAs($gtsUser)
            ->post("/incidencias/{$incident->folio}/notas", ['note' => '']);
        $response1->assertSessionHasErrors('note');

        // Nota demasiado corta (menos de 5 caracteres)
        $response2 = $this->actingAs($gtsUser)
            ->post("/incidencias/{$incident->folio}/notas", ['note' => 'Hola']);
        $response2->assertSessionHasErrors('note');
    }
}
