<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\IncidentAssignedArea;
use App\Models\Role;
use App\Models\RoleOperationalScope;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiAreaAssignmentFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $gtsUser1;

    protected User $gtsUser2;

    protected User $apocUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->firstOrFail();
        $this->gtsUser1 = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $this->gtsUser2 = User::where('email', 'gts.mendoza@aifa.aero')->firstOrFail();
        $this->apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
    }

    /**
     * Escenario 1: Asignación a una sola área sin usuario.
     */
    public function test_escenario_1_asignacion_a_una_sola_area_sin_usuario(): void
    {
        $incident = Incident::factory()->nueva()->create();

        $response = $this->actingAs($this->admin)->post(route('incidents.assign', $incident), [
            'areas' => ['gts'],
            'assigned_to_user_id' => '',
            'comment' => 'Asignado a la guardia de GTS sin técnico específico.',
        ]);

        $response->assertRedirect(route('incidents.show', $incident->folio));
        $response->assertSessionHas('success');

        $incident->refresh();
        $this->assertEquals('ASIGNADA', $incident->status->code);
        $this->assertNull($incident->assigned_to_user_id);
        $this->assertNotNull($incident->assigned_at);
        $this->assertTrue($incident->isAssignedToArea('gts'));
        $this->assertCount(1, $incident->assignedAreas);

        $this->assertDatabaseHas('incident_assigned_areas', [
            'incident_id' => $incident->id,
            'area_code' => 'gts',
        ]);

        $this->assertDatabaseHas('incident_histories', [
            'incident_id' => $incident->id,
            'action' => 'CAMBIO_ESTADO',
            'field_name' => 'status_id',
        ]);

        $this->assertDatabaseHas('incident_histories', [
            'incident_id' => $incident->id,
            'action' => 'ASIGNADA',
            'field_name' => 'assigned_specialist',
        ]);
    }

    /**
     * Escenario 2: Asignación a una sola área y un usuario compatible.
     */
    public function test_escenario_2_asignacion_a_una_sola_area_con_usuario_compatible(): void
    {
        $incident = Incident::factory()->nueva()->create();

        $response = $this->actingAs($this->gtsUser1)->post(route('incidents.assign', $incident), [
            'areas' => ['gts'],
            'assigned_to_user_id' => $this->gtsUser2->id,
            'comment' => 'Asignado a Mendoza para atención perimetral.',
        ]);

        $response->assertRedirect(route('incidents.show', $incident->folio));
        $response->assertSessionHas('success');

        $incident->refresh();
        $this->assertEquals('ASIGNADA', $incident->status->code);
        $this->assertEquals($this->gtsUser2->id, $incident->assigned_to_user_id);
        $this->assertNull($incident->assigned_specialist);
        $this->assertTrue($incident->isAssignedToArea('gts'));

        $this->assertDatabaseHas('incident_assigned_areas', [
            'incident_id' => $incident->id,
            'area_code' => 'gts',
        ]);

        $this->assertDatabaseHas('incident_histories', [
            'incident_id' => $incident->id,
            'action' => 'ASIGNADA',
            'field_name' => 'assigned_to_user_id',
            'new_value' => $this->gtsUser2->name,
        ]);
    }

    /**
     * Escenario 3: Asignación a varias áreas sin usuario.
     */
    public function test_escenario_3_asignacion_a_varias_areas_sin_usuario(): void
    {
        $incident = Incident::factory()->nueva()->create();
        $cctvRole = Role::firstOrCreate(['slug' => 'cctv'], ['name' => 'CCTV']);

        $response = $this->actingAs($this->admin)->post(route('incidents.assign', $incident), [
            'areas' => ['gts', 'cctv'],
            'assigned_to_user_id' => null,
            'comment' => 'Coordinación conjunta GTS y CCTV para mantenimiento.',
        ]);

        $response->assertRedirect(route('incidents.show', $incident->folio));
        $response->assertSessionHas('success');

        $incident->refresh();
        $this->assertEquals('ASIGNADA', $incident->status->code);
        $this->assertNull($incident->assigned_to_user_id);
        $this->assertTrue($incident->isAssignedToArea('gts'));
        $this->assertTrue($incident->isAssignedToArea('cctv'));
        $this->assertCount(2, $incident->assignedAreas);

        $this->assertDatabaseHas('incident_assigned_areas', [
            'incident_id' => $incident->id,
            'area_code' => 'gts',
        ]);
        $this->assertDatabaseHas('incident_assigned_areas', [
            'incident_id' => $incident->id,
            'area_code' => 'cctv',
        ]);
    }

    /**
     * Escenario 4: Asignación a varias áreas y un usuario compatible.
     */
    public function test_escenario_4_asignacion_a_varias_areas_con_usuario_compatible(): void
    {
        $incident = Incident::factory()->nueva()->create();
        $cctvRole = Role::firstOrCreate(['slug' => 'cctv'], ['name' => 'CCTV']);

        $response = $this->actingAs($this->admin)->post(route('incidents.assign', $incident), [
            'areas' => ['gts', 'cctv'],
            'assigned_to_user_id' => $this->gtsUser1->id,
            'comment' => 'GTS coordina con apoyo de CCTV.',
        ]);

        $response->assertRedirect(route('incidents.show', $incident->folio));
        $response->assertSessionHas('success');

        $incident->refresh();
        $this->assertEquals('ASIGNADA', $incident->status->code);
        $this->assertEquals($this->gtsUser1->id, $incident->assigned_to_user_id);
        $this->assertTrue($incident->isAssignedToArea('gts'));
        $this->assertTrue($incident->isAssignedToArea('cctv'));
        $this->assertCount(2, $incident->assignedAreas);

        $this->assertDatabaseHas('incident_histories', [
            'incident_id' => $incident->id,
            'action' => 'ASIGNADA',
            'field_name' => 'assigned_to_user_id',
            'new_value' => $this->gtsUser1->name,
        ]);
    }

    /**
     * Restricción: Intentar asignar a un área fuera del alcance operativo debe ser rechazado.
     */
    public function test_rechazo_asignacion_a_area_fuera_del_alcance_operativo(): void
    {
        $incident = Incident::factory()->nueva()->create();

        // GTS no tiene alcance de asignación hacia el rol admin o apoc
        $response = $this->actingAs($this->gtsUser1)->post(route('incidents.assign', $incident), [
            'areas' => ['admin'],
        ]);

        $response->assertSessionHasErrors('areas.0');
        $incident->refresh();
        $this->assertEquals('NUEVA', $incident->status->code);
        $this->assertEmpty($incident->assignedAreas);
    }

    /**
     * Restricción: Intentar asignar un usuario que no pertenece a las áreas seleccionadas debe ser rechazado.
     */
    public function test_rechazo_asignacion_a_usuario_incompatible_con_areas_seleccionadas(): void
    {
        $incident = Incident::factory()->nueva()->create();
        $cctvRole = Role::firstOrCreate(['slug' => 'cctv'], ['name' => 'CCTV']);

        // Se selecciona únicamente el área 'cctv', pero se envía un usuario con rol 'gts'
        $response = $this->actingAs($this->admin)->post(route('incidents.assign', $incident), [
            'areas' => ['cctv'],
            'assigned_to_user_id' => $this->gtsUser1->id,
        ]);

        $response->assertSessionHasErrors('assigned_to_user_id');
        $incident->refresh();
        $this->assertEquals('NUEVA', $incident->status->code);
    }

    /**
     * Reasignación: Modificar áreas o usuario de una incidencia ya asignada actualiza los registros y mantiene el historial.
     */
    public function test_reasignacion_actualiza_areas_y_mantiene_historial(): void
    {
        $cctvRole = Role::firstOrCreate(['slug' => 'cctv'], ['name' => 'CCTV']);

        // 1. Incidencia asignada inicialmente a GTS con técnico
        $incident = Incident::factory()->asignada($this->gtsUser1)->create();
        $incident->assignedAreas()->create([
            'area_code' => 'gts',
            'area_name' => 'GTS',
            'role_id' => $this->gtsUser1->role_id,
        ]);

        // 2. Reasignación a CCTV sin usuario
        $response = $this->actingAs($this->admin)->post(route('incidents.assign', $incident), [
            'areas' => ['cctv'],
            'assigned_to_user_id' => null,
            'comment' => 'Derivando a CCTV por verificación de cámaras.',
        ]);

        $response->assertRedirect(route('incidents.show', $incident->folio));
        $response->assertSessionHas('success');

        $incident->refresh();
        $this->assertNull($incident->assigned_to_user_id);
        $this->assertFalse($incident->isAssignedToArea('gts'));
        $this->assertTrue($incident->isAssignedToArea('cctv'));

        $this->assertDatabaseHas('incident_histories', [
            'incident_id' => $incident->id,
            'action' => 'REASIGNADA',
            'old_value' => $this->gtsUser1->name,
            'new_value' => 'CCTV',
        ]);
    }

    /**
     * Restricción: El área TICS es rechazada estrictamente para asignación.
     */
    public function test_rechazo_estricto_asignacion_a_area_tics(): void
    {
        $incident = Incident::factory()->nueva()->create();

        $response = $this->actingAs($this->admin)->post(route('incidents.assign', $incident), [
            'areas' => ['tics'],
        ]);

        $response->assertSessionHasErrors('areas.0');
    }

    /**
     * Visibilidad: Incidencia asignada a nivel de área es visible para los técnicos de esa área.
     */
    public function test_visibilidad_de_incidencia_asignada_por_area_para_tecnico_del_area(): void
    {
        $incident = Incident::factory()->nueva()->create();
        $incident->status_id = Status::where('code', 'ASIGNADA')->firstOrFail()->id;
        $incident->save();

        $incident->assignedAreas()->create([
            'area_code' => 'gts',
            'area_name' => 'GTS',
            'role_id' => $this->gtsUser1->role_id,
        ]);

        // La incidencia no tiene assigned_to_user_id, pero está visible para gtsUser1 por área
        $visibleIncidents = Incident::query()->visibleToUser($this->gtsUser1)->pluck('id');
        $this->assertTrue($visibleIncidents->contains($incident->id));
    }

    /**
     * Flujo de atención: Un técnico puede iniciar atención en una incidencia asignada solo a su área.
     */
    public function test_tecnico_puede_iniciar_atencion_en_incidencia_asignada_solo_a_su_area(): void
    {
        $incident = Incident::factory()->nueva()->create();

        // Asignar a GTS sin usuario
        $this->actingAs($this->admin)->post(route('incidents.assign', $incident), [
            'areas' => ['gts'],
            'assigned_to_user_id' => null,
        ]);

        $incident->refresh();
        $this->assertEquals('ASIGNADA', $incident->status->code);
        $this->assertNull($incident->assigned_to_user_id);

        // Especialista toma la incidencia iniciando atención
        $response = $this->actingAs($this->gtsUser2)->post(route('incidents.start-attention', $incident));
        $response->assertRedirect(route('incidents.show', $incident->folio));

        $incident->refresh();
        $this->assertEquals('EN_ATENCION', $incident->status->code);
        $this->assertEquals($this->gtsUser2->id, $incident->assigned_to_user_id);
    }
}
