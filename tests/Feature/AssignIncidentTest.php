<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignIncidentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_cannot_assign_incident(): void
    {
        $incident = Incident::factory()->nueva()->create();

        $response = $this->post("/incidencias/{$incident->folio}/asignar", [
            'assigned_to_user_id' => 1,
        ]);

        $response->assertRedirect('/login');
    }

    public function test_apoc_user_cannot_assign_incident(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->nueva()->create();

        $response = $this->actingAs($apocUser)->post("/incidencias/{$incident->folio}/asignar", [
            'assigned_to_user_id' => $gtsUser->id,
        ]);

        $response->assertForbidden();
    }

    public function test_inactive_gts_user_cannot_assign_incident(): void
    {
        $inactiveGts = User::factory()->gts()->inactive()->create();
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->nueva()->create();

        $response = $this->actingAs($inactiveGts)->post("/incidencias/{$incident->folio}/asignar", [
            'assigned_to_user_id' => $gtsUser->id,
        ]);

        $response->assertForbidden();
    }

    public function test_gts_user_can_assign_technician_to_new_incident(): void
    {
        $coordinator = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $technician = User::where('email', 'gts.mendoza@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->nueva()->create();

        $response = $this->actingAs($coordinator)->post("/incidencias/{$incident->folio}/asignar", [
            'assigned_to_user_id' => $technician->id,
            'comment' => 'Atender de inmediato según protocolo perimetral.',
        ]);

        $response->assertRedirect(route('incidents.show', $incident->folio));
        $response->assertSessionHas('success');

        $incident->refresh();

        // Verificar transición automática de NUEVA a ASIGNADA (RN-02 / RN-03)
        $this->assertEquals('ASIGNADA', $incident->status->code);
        $this->assertEquals($technician->id, $incident->assigned_to_user_id);
        $this->assertNotNull($incident->assigned_at);

        // Verificar auditoría de cambio de estado y asignación (RN-04)
        $this->assertDatabaseHas('incident_histories', [
            'incident_id' => $incident->id,
            'action' => 'CAMBIO_ESTADO',
            'user_id' => $coordinator->id,
        ]);

        $this->assertDatabaseHas('incident_histories', [
            'incident_id' => $incident->id,
            'action' => 'ASIGNADA',
            'user_id' => $coordinator->id,
            'new_value' => $technician->name,
        ]);
    }

    public function test_gts_user_can_self_assign_incident(): void
    {
        $technician = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->nueva()->create();

        // El técnico toma la incidencia directamente
        $response = $this->actingAs($technician)->post("/incidencias/{$incident->folio}/asignar", [
            'assigned_to_user_id' => $technician->id,
            'comment' => 'Toma técnica por guardia en consola C2.',
        ]);

        $response->assertRedirect(route('incidents.show', $incident->folio));
        $response->assertSessionHas('success');

        $incident->refresh();
        $this->assertEquals('ASIGNADA', $incident->status->code);
        $this->assertEquals($technician->id, $incident->assigned_to_user_id);

        $this->assertDatabaseHas('incident_histories', [
            'incident_id' => $incident->id,
            'action' => 'ASIGNADA',
            'user_id' => $technician->id,
            'new_value' => $technician->name,
        ]);
    }

    public function test_gts_user_can_reassign_already_assigned_incident(): void
    {
        $tech1 = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $tech2 = User::where('email', 'gts.mendoza@aifa.aero')->firstOrFail();

        $incident = Incident::factory()->asignada($tech1)->create();

        $response = $this->actingAs($tech1)->post("/incidencias/{$incident->folio}/asignar", [
            'assigned_to_user_id' => $tech2->id,
            'comment' => 'Reasignación por cambio de turno.',
        ]);

        $response->assertRedirect(route('incidents.show', $incident->folio));
        $response->assertSessionHas('success');

        $incident->refresh();
        $this->assertEquals($tech2->id, $incident->assigned_to_user_id);

        $this->assertDatabaseHas('incident_histories', [
            'incident_id' => $incident->id,
            'action' => 'REASIGNADA',
            'old_value' => $tech1->name,
            'new_value' => $tech2->name,
        ]);
    }

    public function test_cannot_assign_non_gts_user(): void
    {
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->nueva()->create();

        $response = $this->actingAs($gtsUser)->post("/incidencias/{$incident->folio}/asignar", [
            'assigned_to_user_id' => $apocUser->id,
        ]);

        $response->assertSessionHasErrors('assigned_to_user_id');
        $incident->refresh();
        $this->assertNull($incident->assigned_to_user_id);
    }

    public function test_cannot_assign_resolved_or_closed_incident(): void
    {
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $tech = User::where('email', 'gts.mendoza@aifa.aero')->firstOrFail();

        $incidentResuelta = Incident::factory()->resuelta()->create();
        $incidentCerrada = Incident::factory()->cerrada()->create();

        // 1. Asignar en RESUELTA debe arrojar 403 Forbidden
        $response1 = $this->actingAs($gtsUser)->post("/incidencias/{$incidentResuelta->folio}/asignar", [
            'assigned_to_user_id' => $tech->id,
        ]);
        $response1->assertForbidden();

        // 2. Asignar en CERRADA debe arrojar 403 Forbidden
        $response2 = $this->actingAs($gtsUser)->post("/incidencias/{$incidentCerrada->folio}/asignar", [
            'assigned_to_user_id' => $tech->id,
        ]);
        $response2->assertForbidden();
    }
}
