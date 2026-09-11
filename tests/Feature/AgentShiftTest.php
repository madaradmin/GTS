<?php

namespace Tests\Feature;

use App\Models\AgentAvailabilityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentShiftTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_gts_agent_profile_displays_shift_availability_section(): void
    {
        $gtsUser = User::factory()->gts()->create([
            'shift_status' => 'ACTIVO',
        ]);

        $response = $this->actingAs($gtsUser)->get(route('profile.edit'));

        $response->assertStatus(200);
        $response->assertSee('Estado de Turno y Disponibilidad Operacional');
        $response->assertSee('🟢 ACTIVO');
        $response->assertSee('⚪ NO DISPONIBLE');
        $response->assertSee('Actualizar Disponibilidad');
    }

    public function test_gts_agent_can_update_shift_status_to_no_disponible(): void
    {
        $gtsUser = User::factory()->gts()->create([
            'shift_status' => 'ACTIVO',
        ]);

        $response = $this->actingAs($gtsUser)->post(route('profile.shift-status'), [
            'status' => 'NO DISPONIBLE',
            'reason' => 'Ventana de descanso / alimentos',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $gtsUser->refresh();
        $this->assertSame('NO DISPONIBLE', $gtsUser->shift_status);
        $this->assertTrue($gtsUser->isShiftUnavailable());
        $this->assertFalse($gtsUser->isShiftActive());
        $this->assertNotNull($gtsUser->shift_status_updated_at);
    }

    public function test_gts_agent_can_update_shift_status_to_activo(): void
    {
        $gtsUser = User::factory()->gts()->create([
            'shift_status' => 'NO DISPONIBLE',
        ]);

        $response = $this->actingAs($gtsUser)->post(route('profile.shift-status'), [
            'status' => 'ACTIVO',
            'reason' => 'Retorno a posición de guardia en terminal',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $gtsUser->refresh();
        $this->assertSame('ACTIVO', $gtsUser->shift_status);
        $this->assertTrue($gtsUser->isShiftActive());
        $this->assertFalse($gtsUser->isShiftUnavailable());
    }

    public function test_updating_shift_status_creates_audit_log_record(): void
    {
        $gtsUser = User::factory()->gts()->create([
            'shift_status' => 'ACTIVO',
        ]);

        $this->actingAs($gtsUser)->post(route('profile.shift-status'), [
            'status' => 'NO DISPONIBLE',
            'reason' => 'Atención técnica en pista 04L',
        ]);

        $this->assertDatabaseHas('agent_availability_logs', [
            'user_id' => $gtsUser->id,
            'status' => 'NO DISPONIBLE',
            'previous_status' => 'ACTIVO',
            'reason' => 'Atención técnica en pista 04L',
        ]);

        $this->assertEquals(1, $gtsUser->availabilityLogs()->count());
        $latestLog = $gtsUser->availabilityLogs()->first();
        $this->assertSame('NO DISPONIBLE', $latestLog->status);
        $this->assertSame('ACTIVO', $latestLog->previous_status);
    }

    public function test_shift_status_update_does_not_modify_account_is_active_or_hr_schedule(): void
    {
        $gtsUser = User::factory()->gts()->create([
            'is_active' => true,
            'shift_status' => 'ACTIVO',
        ]);

        $this->actingAs($gtsUser)->post(route('profile.shift-status'), [
            'status' => 'NO DISPONIBLE',
            'reason' => 'Fin de relevo',
        ]);

        $gtsUser->refresh();

        // La cuenta de usuario sigue activa a nivel sistema (permite login y consultas)
        $this->assertTrue($gtsUser->is_active);
        // Únicamente cambia la disponibilidad operativa de asignación
        $this->assertSame('NO DISPONIBLE', $gtsUser->shift_status);
    }

    public function test_apoc_user_cannot_update_other_agents_shift_status(): void
    {
        $apocUser = User::factory()->apoc()->create();
        $gtsUser = User::factory()->gts()->create();

        $response = $this->actingAs($apocUser)->post(route('profile.shift-status'), [
            'agent_id' => $gtsUser->id,
            'status' => 'NO DISPONIBLE',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_any_gts_agent_shift_status(): void
    {
        $adminUser = User::factory()->admin()->create();
        $gtsUser = User::factory()->gts()->create([
            'shift_status' => 'ACTIVO',
        ]);

        $response = $this->actingAs($adminUser)->post(route('profile.shift-status'), [
            'agent_id' => $gtsUser->id,
            'status' => 'NO DISPONIBLE',
            'reason' => 'Asignado a capacitación institucional',
        ]);

        $response->assertRedirect();

        $gtsUser->refresh();
        $this->assertSame('NO DISPONIBLE', $gtsUser->shift_status);
    }

    public function test_validation_rejects_invalid_shift_status(): void
    {
        $gtsUser = User::factory()->gts()->create();

        $response = $this->actingAs($gtsUser)->post(route('profile.shift-status'), [
            'status' => 'ESTADO_INVALIDO',
        ]);

        $response->assertSessionHasErrors(['status']);
    }

    public function test_json_response_supported_for_quick_switcher(): void
    {
        $gtsUser = User::factory()->gts()->create([
            'shift_status' => 'ACTIVO',
        ]);

        $response = $this->actingAs($gtsUser)->postJson(route('profile.shift-status'), [
            'status' => 'NO DISPONIBLE',
            'reason' => 'Cambio rápido desde topbar',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 'NO DISPONIBLE',
            'is_active' => false,
        ]);
        $response->assertJsonStructure(['success', 'status', 'is_active', 'updated_at', 'message']);
    }

    public function test_topbar_and_sidebar_reflect_shift_status(): void
    {
        $gtsUser = User::factory()->gts()->create([
            'shift_status' => 'ACTIVO',
        ]);

        // Verificación con estado ACTIVO
        $responseActive = $this->actingAs($gtsUser)->get(route('dashboard'));
        $responseActive->assertStatus(200);
        $responseActive->assertSee('ACTIVO');

        // Cambiar a NO DISPONIBLE
        $gtsUser->setShiftStatus('NO DISPONIBLE');

        // Verificación con estado NO DISPONIBLE
        $responseInactive = $this->actingAs($gtsUser)->get(route('dashboard'));
        $responseInactive->assertStatus(200);
        $responseInactive->assertSee('NO DISPONIBLE');
    }
}
