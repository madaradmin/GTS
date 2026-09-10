<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class IncidentPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_apoc_can_create_incidents_but_gts_cannot(): void
    {
        $apocUser = User::factory()->apoc()->create();
        $gtsUser  = User::factory()->gts()->create();

        $this->assertTrue(Gate::forUser($apocUser)->allows('create', Incident::class));
        $this->assertFalse(Gate::forUser($gtsUser)->allows('create', Incident::class));
    }

    public function test_gts_can_assign_when_incident_is_open(): void
    {
        $gtsUser  = User::factory()->gts()->create();
        $apocUser = User::factory()->apoc()->create();
        $incident = Incident::factory()->nueva()->create();

        $this->assertTrue(Gate::forUser($gtsUser)->allows('assign', $incident));
        $this->assertFalse(Gate::forUser($apocUser)->allows('assign', $incident));
    }

    public function test_gts_can_resolve_when_in_attention_but_apoc_cannot(): void
    {
        $gtsUser  = User::factory()->gts()->create();
        $apocUser = User::factory()->apoc()->create();
        $incident = Incident::factory()->enAtencion($gtsUser)->create();

        // GTS puede resolver en atención
        $this->assertTrue(Gate::forUser($gtsUser)->allows('resolve', $incident));
        // APOC no puede resolver incidencias técnicas
        $this->assertFalse(Gate::forUser($apocUser)->allows('resolve', $incident));
    }

    public function test_apoc_can_close_when_resolved_but_gts_cannot(): void
    {
        $gtsUser  = User::factory()->gts()->create();
        $apocUser = User::factory()->apoc()->create();
        $incident = Incident::factory()->resuelta()->create();

        // APOC tiene la facultad de dar VoBo y cerrar
        $this->assertTrue(Gate::forUser($apocUser)->allows('close', $incident));
        // GTS no puede autocerrar sus propios tickets resueltos
        $this->assertFalse(Gate::forUser($gtsUser)->allows('close', $incident));
    }

    public function test_apoc_can_reject_resolution_when_resolved(): void
    {
        $apocUser = User::factory()->apoc()->create();
        $gtsUser  = User::factory()->gts()->create();
        $incident = Incident::factory()->resuelta()->create();

        $this->assertTrue(Gate::forUser($apocUser)->allows('rejectResolution', $incident));
        $this->assertFalse(Gate::forUser($gtsUser)->allows('rejectResolution', $incident));
    }

    public function test_deletion_is_strictly_prohibited_for_all_users(): void
    {
        $adminUser = User::factory()->admin()->create();
        $incident = Incident::factory()->nueva()->create();

        // Ni siquiera el administrador puede borrar incidencias
        $this->assertFalse(Gate::forUser($adminUser)->allows('delete', $incident));
    }
}
