<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\Role;
use App\Models\Status;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TicsSpecialistAssignmentPhase4Test extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $gts;

    protected User $apocCco;

    protected User $apocCsa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->firstOrFail();
        $this->gts = User::whereHas('role', fn ($q) => $q->where('slug', 'gts'))->firstOrFail();

        $ccoRole = Role::firstOrCreate(['slug' => 'apoc-cco'], ['name' => 'APOC-CCO']);
        $this->apocCco = User::firstOrCreate(
            ['username' => 'test.cco.tics'],
            [
                'name' => 'Operador CCO TICS',
                'email' => 'cco.tics@aifa.aero',
                'password' => Hash::make('Password123!'),
                'role_id' => $ccoRole->id,
                'is_active' => true,
            ]
        );

        $csaRole = Role::firstOrCreate(['slug' => 'apoc-csa'], ['name' => 'APOC-CSA']);
        $this->apocCsa = User::firstOrCreate(
            ['username' => 'test.csa.tics'],
            [
                'name' => 'Operador CSA TICS',
                'email' => 'csa.tics@aifa.aero',
                'password' => Hash::make('Password123!'),
                'role_id' => $csaRole->id,
                'is_active' => true,
            ]
        );
    }

    public function test_admin_cannot_assign_incident_to_tics(): void
    {
        $incident = Incident::factory()->nueva()->create();

        $response = $this->actingAs($this->admin)->post(route('incidents.assign', $incident), [
            'assigned_to_user_id' => 'tics',
            'comment' => 'Derivado a TICS por tratarse de problema de enlace externo.',
        ]);

        $response->assertSessionHasErrors('areas.0');
        $incident->refresh();
        $this->assertEquals('NUEVA', $incident->status->code);
    }

    public function test_gts_cannot_assign_incident_to_tics(): void
    {
        $incident = Incident::factory()->nueva()->create();

        $response = $this->actingAs($this->gts)->post(route('incidents.assign', $incident), [
            'assigned_to_user_id' => 'tics',
        ]);

        $response->assertSessionHasErrors('areas.0');
        $incident->refresh();
        $this->assertEquals('NUEVA', $incident->status->code);
    }

    public function test_apoc_cco_and_apoc_csa_cannot_assign_tics(): void
    {
        $incident = Incident::factory()->nueva()->create();

        // APOC-CCO
        $responseCco = $this->actingAs($this->apocCco)->post(route('incidents.assign', $incident), [
            'assigned_to_user_id' => 'tics',
        ]);
        $responseCco->assertStatus(403);

        // APOC-CSA
        $responseCsa = $this->actingAs($this->apocCsa)->post(route('incidents.assign', $incident), [
            'assigned_to_user_id' => 'tics',
        ]);
        $responseCsa->assertStatus(403);
    }

    public function test_apoc_cco_can_view_incident_assigned_to_tics(): void
    {
        $incident = Incident::factory()->nueva()->create([
            'reported_by_user_id' => $this->apocCco->id,
            'assigned_specialist' => 'TICS',
            'assigned_to_user_id' => null,
            'assigned_at' => now(),
            'status_id' => Status::where('code', 'ASIGNADA')->firstOrFail()->id,
        ]);

        $response = $this->actingAs($this->apocCco)->get(route('incidents.show', $incident->folio));

        $response->assertOk();
        $response->assertSee('TICS');
        $response->assertSee('Especialista de Atención');
    }

    public function test_apoc_csa_can_view_incident_assigned_to_tics(): void
    {
        $incident = Incident::factory()->nueva()->create([
            'reported_by_user_id' => $this->apocCsa->id,
            'assigned_specialist' => 'TICS',
            'assigned_to_user_id' => null,
            'assigned_at' => now(),
            'status_id' => Status::where('code', 'ASIGNADA')->firstOrFail()->id,
        ]);

        $response = $this->actingAs($this->apocCsa)->get(route('incidents.show', $incident->folio));

        $response->assertOk();
        $response->assertSee('TICS');
        $response->assertSee('Especialista de Atención');
    }

    public function test_reassignment_from_historical_tics_to_gts_user(): void
    {
        // 1. Incidencia históricamente asignada a TICS
        $incident = Incident::factory()->nueva()->create([
            'assigned_specialist' => 'TICS',
            'assigned_to_user_id' => null,
            'assigned_at' => now(),
            'status_id' => Status::where('code', 'ASIGNADA')->firstOrFail()->id,
        ]);

        $this->assertTrue($incident->isAssignedToTics());

        // 2. Reasignar de TICS a un técnico GTS (Roberto Morales)
        $response = $this->actingAs($this->admin)->post(route('incidents.assign', $incident), [
            'areas' => ['gts'],
            'assigned_to_user_id' => $this->gts->id,
            'comment' => 'Reasignado a GTS para intervención local',
        ]);

        $response->assertRedirect(route('incidents.show', $incident->folio));
        $incident->refresh();
        $this->assertFalse($incident->isAssignedToTics());
        $this->assertNull($incident->assigned_specialist);
        $this->assertEquals($this->gts->id, $incident->assigned_to_user_id);

        $this->assertDatabaseHas('incident_histories', [
            'incident_id' => $incident->id,
            'action' => 'REASIGNADA',
            'old_value' => 'TICS',
            'new_value' => $this->gts->name,
        ]);

        // 3. Intentar reasignar de nuevo a TICS es rechazado
        $responseFail = $this->actingAs($this->admin)->post(route('incidents.assign', $incident), [
            'areas' => ['tics'],
        ]);
        $responseFail->assertSessionHasErrors('areas.0');
    }

    public function test_tics_is_not_a_role_and_not_a_user(): void
    {
        $this->assertDatabaseMissing('roles', ['slug' => 'tics']);
        $this->assertDatabaseMissing('roles', ['name' => 'TICS']);

        $this->assertDatabaseMissing('users', ['username' => 'tics']);
        $this->assertDatabaseMissing('users', ['name' => 'TICS']);
    }

    public function test_tics_does_not_affect_active_technicians_metric(): void
    {
        $dashboardService = app(DashboardService::class);
        $initialTechCount = $dashboardService->getActiveTechniciansCount();

        // Crear incidencias asignadas a TICS
        Incident::factory()->count(3)->create([
            'assigned_specialist' => 'TICS',
            'assigned_to_user_id' => null,
        ]);

        $techCountAfter = $dashboardService->getActiveTechniciansCount();

        $this->assertEquals($initialTechCount, $techCountAfter);
    }

    public function test_validation_rejects_invalid_values_for_assignment(): void
    {
        $incident = Incident::factory()->nueva()->create();

        $response = $this->actingAs($this->admin)->post(route('incidents.assign', $incident), [
            'assigned_to_user_id' => 'invalid_hacker_string',
        ]);

        $response->assertSessionHasErrors(['assigned_to_user_id']);
    }
}
