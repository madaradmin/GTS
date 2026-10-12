<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GtsVoBoApprovalPhase4Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->first();
        if ($gtsUser) {
            $voboPerm = \App\Models\Permission::where('slug', 'incidents.vobo_close')->first();
            if ($voboPerm) {
                $gtsUser->role->permissions()->syncWithoutDetaching([$voboPerm->id]);
            }
        }
    }

    /**
     * Prueba que el usuario con rol GTS visualiza el botón de visto bueno y puede cerrar la incidencia resuelta.
     */
    public function test_gts_user_can_view_vobo_button_and_close_resolved_incident(): void
    {
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->resuelta()->create();

        // 1. Verificación visual: El botón "Dar VoBo y Cerrar" es visible para GTS
        $viewResponse = $this->actingAs($gtsUser)->get(route('incidents.show', $incident->folio));
        $viewResponse->assertOk();
        $viewResponse->assertSee('Dar VoBo y Cerrar');

        // 2. Ejecución: GTS otorga visto bueno
        $closeResponse = $this->actingAs($gtsUser)->post(route('incidents.close', $incident->folio), [
            'comment' => 'Visto bueno operacional otorgado por especialista GTS en sitio.',
        ]);

        $closeResponse->assertRedirect(route('incidents.show', $incident->folio));
        $closeResponse->assertSessionHas('success');

        // 3. Persistencia y cambio de estado
        $incident->refresh();
        $this->assertEquals('CERRADA', $incident->status->code);
        $this->assertNotNull($incident->closed_at);

        // 4. Trazabilidad en bitácora de auditoría inmutable
        $this->assertDatabaseHas('incident_histories', [
            'incident_id' => $incident->id,
            'user_id' => $gtsUser->id,
            'action' => 'CAMBIO_ESTADO',
            'new_value' => 'Cerrada',
        ]);

        $this->assertDatabaseHas('incident_histories', [
            'incident_id' => $incident->id,
            'user_id' => $gtsUser->id,
            'action' => 'CERRADA',
            'comment' => 'Visto bueno operacional otorgado por especialista GTS en sitio.',
        ]);
    }

    /**
     * Prueba que GTS NO puede dar visto bueno si la incidencia está en un estado incompatible.
     */
    public function test_gts_cannot_give_vobo_if_incident_is_not_resolved(): void
    {
        $gtsUser = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();

        $nuevaIncident = Incident::factory()->nueva()->create();
        $assignedIncident = Incident::factory()->asignada()->create();
        $inAttentionIncident = Incident::factory()->enAtencion()->create();
        $closedIncident = Incident::factory()->cerrada()->create();

        // Estado NUEVA
        $this->actingAs($gtsUser)
            ->post(route('incidents.close', $nuevaIncident->folio), ['comment' => 'VoBo indebido'])
            ->assertForbidden();

        // Estado ASIGNADA
        $this->actingAs($gtsUser)
            ->post(route('incidents.close', $assignedIncident->folio), ['comment' => 'VoBo indebido'])
            ->assertForbidden();

        // Estado EN_ATENCION
        $this->actingAs($gtsUser)
            ->post(route('incidents.close', $inAttentionIncident->folio), ['comment' => 'VoBo indebido'])
            ->assertForbidden();

        // Estado CERRADA (ya cerrada no puede volver a cerrarse)
        $this->actingAs($gtsUser)
            ->post(route('incidents.close', $closedIncident->folio), ['comment' => 'VoBo indebido'])
            ->assertForbidden();
    }

    /**
     * Prueba que usuarios inactivos o no autorizados son rechazados.
     */
    public function test_unauthorized_or_inactive_user_cannot_give_vobo(): void
    {
        $incident = Incident::factory()->resuelta()->create();

        // Usuario no autenticado
        $this->post(route('incidents.close', $incident->folio), ['comment' => 'VoBo'])
            ->assertRedirect('/login');

        // Usuario inactivo
        $inactiveGts = User::factory()->gts()->create(['is_active' => false]);
        $this->actingAs($inactiveGts)
            ->post(route('incidents.close', $incident->folio), ['comment' => 'VoBo'])
            ->assertForbidden();
    }

    /**
     * Prueba que las reglas de confidencialidad y permisos de APOC-CCO, APOC-CSA y Admin se mantienen.
     */
    public function test_apoc_cco_csa_and_admin_vobo_permissions_are_preserved(): void
    {
        $ccoUser = User::factory()->apocCco()->create();
        $csaUser = User::factory()->apocCsa()->create();
        $admin = User::where('email', 'admin@aifa.aero')->firstOrFail();

        $ccoIncident = Incident::factory()->resuelta()->create(['reported_by_user_id' => $ccoUser->id]);
        $csaIncident = Incident::factory()->resuelta()->create(['reported_by_user_id' => $csaUser->id]);

        // 1. CCO puede dar VoBo a su propia incidencia
        $this->actingAs($ccoUser)
            ->post(route('incidents.close', $ccoIncident->folio), ['comment' => 'VoBo por CCO'])
            ->assertRedirect();
        $this->assertEquals('CERRADA', $ccoIncident->fresh()->status->code);

        // 2. CCO NO puede dar VoBo a incidencia de CSA (confidencialidad estricta)
        $this->actingAs($ccoUser)
            ->post(route('incidents.close', $csaIncident->folio), ['comment' => 'VoBo indebido'])
            ->assertForbidden();

        // 3. CSA puede dar VoBo a su propia incidencia
        $this->actingAs($csaUser)
            ->post(route('incidents.close', $csaIncident->folio), ['comment' => 'VoBo por CSA'])
            ->assertRedirect();
        $this->assertEquals('CERRADA', $csaIncident->fresh()->status->code);

        // 4. Admin puede dar VoBo a cualquier incidencia
        $adminIncident = Incident::factory()->resuelta()->create();
        $this->actingAs($admin)
            ->post(route('incidents.close', $adminIncident->folio), ['comment' => 'VoBo por Admin'])
            ->assertRedirect();
        $this->assertEquals('CERRADA', $adminIncident->fresh()->status->code);
    }
}
