<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResolveIncidentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_cannot_resolve_incident(): void
    {
        $incident = Incident::factory()->enAtencion()->create();

        $response = $this->post("/incidencias/{$incident->folio}/resolver", [
            'solution_description' => 'Se reconfiguró el puerto troncal GigabitEthernet0/12 del switch perimetral.',
        ]);

        $response->assertRedirect('/login');
    }

    public function test_apoc_user_cannot_resolve_incident(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->enAtencion()->create();

        $response = $this->actingAs($apocUser)
            ->post("/incidencias/{$incident->folio}/resolver", [
                'solution_description' => 'Intento de resolución indebida por operador APOC.',
            ]);

        $response->assertForbidden();
    }

    public function test_gts_user_can_resolve_incident_in_attention_with_valid_solution(): void
    {
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->enAtencion($gtsUser)->create();

        $solution = 'Se sustituyó el módulo transceptor SFP óptico 1000BASE-SX averiado en rack IDF Nivel 2. Se realizaron pruebas de ping continuas con 0% de pérdidas y niveles ópticos dentro de norma (-4.2 dBm). Telemetría en consola validada.';

        $response = $this->actingAs($gtsUser)
            ->post("/incidencias/{$incident->folio}/resolver", [
                'solution_description' => $solution,
            ]);

        $response->assertRedirect(route('incidents.show', $incident->folio));
        $response->assertSessionHas('success');

        $incident->refresh();
        $this->assertEquals('RESUELTA', $incident->status->code);
        $this->assertEquals($solution, $incident->solution_description);
        $this->assertNotNull($incident->resolved_at);

        // 1. Auditoría de cambio de estado
        $this->assertDatabaseHas('incident_histories', [
            'incident_id' => $incident->id,
            'action' => 'CAMBIO_ESTADO',
            'user_id' => $gtsUser->id,
            'field_name' => 'status_id',
            'old_value' => 'En Atención',
            'new_value' => 'Resuelta',
        ]);

        // 2. Auditoría inmutable de solución técnica aplicada (RF-05 / RN-02 / RN-04)
        $this->assertDatabaseHas('incident_histories', [
            'incident_id' => $incident->id,
            'action' => 'RESUELTA',
            'user_id' => $gtsUser->id,
            'field_name' => 'solution_description',
            'comment' => $solution,
        ]);
    }

    public function test_admin_user_can_resolve_incident_in_attention(): void
    {
        $adminUser = User::where('email', 'admin@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->enAtencion()->create();

        $solution = 'Resolución administrativa y técnica de emergencia: reinicio controlado del servicio de telemetría y sincronización NTP con servidor de tiempo de torre de control.';

        $response = $this->actingAs($adminUser)
            ->post("/incidencias/{$incident->folio}/resolver", [
                'solution_description' => $solution,
            ]);

        $response->assertRedirect(route('incidents.show', $incident->folio));
        $response->assertSessionHas('success');

        $incident->refresh();
        $this->assertEquals('RESUELTA', $incident->status->code);
        $this->assertNotNull($incident->resolved_at);
        $this->assertEquals($solution, $incident->solution_description);
    }

    public function test_cannot_resolve_incident_in_new_or_assigned_state(): void
    {
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();

        // Estado NUEVA: prohibido resolver directamente
        $incidentNueva = Incident::factory()->nueva()->create();
        $this->actingAs($gtsUser)
            ->post("/incidencias/{$incidentNueva->folio}/resolver", [
                'solution_description' => 'Intento de resolver ticket nuevo sin haber sido asignado ni atendido previamente.',
            ])
            ->assertForbidden();

        // Estado ASIGNADA: prohibido resolver sin antes pasar por EN ATENCIÓN
        $incidentAsignada = Incident::factory()->asignada($gtsUser)->create();
        $this->actingAs($gtsUser)
            ->post("/incidencias/{$incidentAsignada->folio}/resolver", [
                'solution_description' => 'Intento de resolver ticket asignado sin haber iniciado formalmente la atención en sitio.',
            ])
            ->assertForbidden();
    }

    public function test_cannot_resolve_incident_already_resolved_or_closed(): void
    {
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();

        // Ya RESUELTA
        $incidentResuelta = Incident::factory()->resuelta()->create();
        $this->actingAs($gtsUser)
            ->post("/incidencias/{$incidentResuelta->folio}/resolver", [
                'solution_description' => 'Intento de resolver una incidencia que ya se encuentra resuelta.',
            ])
            ->assertForbidden();

        // Ya CERRADA
        $incidentCerrada = Incident::factory()->cerrada()->create();
        $this->actingAs($gtsUser)
            ->post("/incidencias/{$incidentCerrada->folio}/resolver", [
                'solution_description' => 'Intento de resolver una incidencia en estado terminal cerrada.',
            ])
            ->assertForbidden();
    }

    public function test_solution_description_is_required_and_must_have_at_least_15_characters(): void
    {
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->enAtencion($gtsUser)->create();

        // 1. Campo vacío
        $response1 = $this->actingAs($gtsUser)
            ->post("/incidencias/{$incident->folio}/resolver", [
                'solution_description' => '',
            ]);
        $response1->assertSessionHasErrors('solution_description');

        // 2. Campo demasiado corto (< 15 caracteres)
        $response2 = $this->actingAs($gtsUser)
            ->post("/incidencias/{$incident->folio}/resolver", [
                'solution_description' => 'Reparado',
            ]);
        $response2->assertSessionHasErrors('solution_description');

        // Estado debe permanecer EN_ATENCION sin alteraciones
        $incident->refresh();
        $this->assertEquals('EN_ATENCION', $incident->status->code);
        $this->assertNull($incident->resolved_at);
        $this->assertNull($incident->solution_description);
    }
}
