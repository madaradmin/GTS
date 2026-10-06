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

    public function test_apoc_gts_and_admin_can_create_incidents(): void
    {
        $apocUser = User::factory()->apoc()->create();
        $gtsUser = User::factory()->gts()->create();
        $adminUser = User::factory()->admin()->create();

        $this->assertTrue(Gate::forUser($apocUser)->allows('create', Incident::class));
        $this->assertTrue(Gate::forUser($gtsUser)->allows('create', Incident::class));
        $this->assertTrue(Gate::forUser($adminUser)->allows('create', Incident::class));
    }

    public function test_gts_can_assign_when_incident_is_open(): void
    {
        $gtsUser = User::factory()->gts()->create();
        $apocUser = User::factory()->apoc()->create();
        $incident = Incident::factory()->nueva()->create();

        $this->assertTrue(Gate::forUser($gtsUser)->allows('assign', $incident));
        $this->assertFalse(Gate::forUser($apocUser)->allows('assign', $incident));
    }

    public function test_gts_can_resolve_when_in_attention_but_apoc_cannot(): void
    {
        $gtsUser = User::factory()->gts()->create();
        $apocUser = User::factory()->apoc()->create();
        $incident = Incident::factory()->enAtencion($gtsUser)->create();

        // GTS puede resolver en atención
        $this->assertTrue(Gate::forUser($gtsUser)->allows('resolve', $incident));
        // APOC no puede resolver incidencias técnicas
        $this->assertFalse(Gate::forUser($apocUser)->allows('resolve', $incident));
    }

    public function test_apoc_and_gts_can_close_when_resolved(): void
    {
        $gtsUser = User::factory()->gts()->create();
        $apocUser = User::factory()->apoc()->create();
        $incident = Incident::factory()->resuelta()->create();

        // APOC y GTS tienen la facultad de dar VoBo y cerrar en estado RESUELTA
        $this->assertTrue(Gate::forUser($apocUser)->allows('close', $incident));
        $this->assertTrue(Gate::forUser($gtsUser)->allows('close', $incident));

        // Usuario inactivo no puede cerrar
        $inactiveUser = User::factory()->gts()->create(['is_active' => false]);
        $this->assertFalse(Gate::forUser($inactiveUser)->allows('close', $incident));
    }

    public function test_apoc_can_reject_resolution_when_resolved(): void
    {
        $apocUser = User::factory()->apoc()->create();
        $gtsUser = User::factory()->gts()->create();
        $incident = Incident::factory()->resuelta()->create();

        $this->assertTrue(Gate::forUser($apocUser)->allows('rejectResolution', $incident));
        $this->assertFalse(Gate::forUser($gtsUser)->allows('rejectResolution', $incident));
    }

    public function test_deletion_is_exclusive_to_administrators(): void
    {
        $adminUser = User::factory()->admin()->create();
        $apocUser = User::factory()->apoc()->create();
        $gtsUser = User::factory()->gts()->create();
        $incident = Incident::factory()->nueva()->create();

        // El Administrador activo tiene permitido eliminar incidencias bajo confirmación
        $this->assertTrue(Gate::forUser($adminUser)->allows('delete', $incident));

        // APOC y GTS tienen terminantemente prohibido eliminar incidencias
        $this->assertFalse(Gate::forUser($apocUser)->allows('delete', $incident));
        $this->assertFalse(Gate::forUser($gtsUser)->allows('delete', $incident));
    }
}
