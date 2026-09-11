<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignAccordingToAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Requisito 7: Los agentes disponibles (ACTIVO) deben aparecer primero;
     * los agentes no disponibles (NO DISPONIBLE) deben aparecer en segundo lugar.
     * Dentro de cada grupo, ordenados alfabéticamente por nombre.
     */
    public function test_gts_users_are_ordered_with_active_first_and_unavailable_second_alphabetically(): void
    {
        $gtsRole = Role::where('slug', 'gts')->firstOrFail();

        // Limpiar otros usuarios GTS para prueba precisa
        User::where('role_id', $gtsRole->id)->delete();

        // Crear usuarios en desorden intencional
        $userZackActive = User::factory()->create([
            'name' => 'Zack Operador',
            'role_id' => $gtsRole->id,
            'shift_status' => 'ACTIVO',
            'is_active' => true,
        ]);

        $userAliceActive = User::factory()->create([
            'name' => 'Alice Especialista',
            'role_id' => $gtsRole->id,
            'shift_status' => 'ACTIVO',
            'is_active' => true,
        ]);

        $userBobUnavailable = User::factory()->create([
            'name' => 'Bob Descanso',
            'role_id' => $gtsRole->id,
            'shift_status' => 'NO DISPONIBLE',
            'is_active' => true,
        ]);

        $userAaronUnavailable = User::factory()->create([
            'name' => 'Aaron Receso',
            'role_id' => $gtsRole->id,
            'shift_status' => 'NO DISPONIBLE',
            'is_active' => true,
        ]);

        $incident = Incident::factory()->nueva()->create();

        $response = $this->actingAs($userAliceActive)->get(route('incidents.show', $incident->folio));
        $response->assertOk();

        /** @var \Illuminate\Database\Eloquent\Collection $gtsUsers */
        $gtsUsers = $response->viewData('gtsUsers');

        $this->assertCount(4, $gtsUsers);

        // Grupo 1: Activos en orden A-Z
        $this->assertEquals($userAliceActive->id, $gtsUsers->get(0)->id);
        $this->assertEquals('Alice Especialista', $gtsUsers->get(0)->name);
        $this->assertEquals('ACTIVO', $gtsUsers->get(0)->shift_status);

        $this->assertEquals($userZackActive->id, $gtsUsers->get(1)->id);
        $this->assertEquals('Zack Operador', $gtsUsers->get(1)->name);
        $this->assertEquals('ACTIVO', $gtsUsers->get(1)->shift_status);

        // Grupo 2: No disponibles en orden A-Z
        $this->assertEquals($userAaronUnavailable->id, $gtsUsers->get(2)->id);
        $this->assertEquals('Aaron Receso', $gtsUsers->get(2)->name);
        $this->assertEquals('NO DISPONIBLE', $gtsUsers->get(2)->shift_status);

        $this->assertEquals($userBobUnavailable->id, $gtsUsers->get(3)->id);
        $this->assertEquals('Bob Descanso', $gtsUsers->get(3)->name);
        $this->assertEquals('NO DISPONIBLE', $gtsUsers->get(3)->shift_status);
    }

    /**
     * Requisitos 2, 3 y 5: La vista y modal deben presentar claramente:
     * - Distinción 🟢 Activo / ⚪ No disponible
     * - Información de Nombre, Rol, Estado y Última actualización
     * - Advertencia en tiempo real para agentes NO DISPONIBLES
     */
    public function test_show_view_renders_availability_indicators_and_realtime_warning(): void
    {
        $gtsRole = Role::where('slug', 'gts')->firstOrFail();

        $activeAgent = User::factory()->create([
            'name' => 'Carlos Activo',
            'badge_number' => 'GTS-01',
            'role_id' => $gtsRole->id,
            'shift_status' => 'ACTIVO',
            'shift_status_updated_at' => now()->subHours(2),
            'is_active' => true,
        ]);

        $unavailableAgent = User::factory()->create([
            'name' => 'Daniel Inactivo',
            'badge_number' => 'GTS-09',
            'role_id' => $gtsRole->id,
            'shift_status' => 'NO DISPONIBLE',
            'shift_status_updated_at' => now()->subMinutes(30),
            'is_active' => true,
        ]);

        $incident = Incident::factory()->nueva()->create();

        $response = $this->actingAs($activeAgent)->get(route('incidents.show', $incident->folio));
        $response->assertOk();

        // 1. Grupos y opciones en selector
        $response->assertSee('🟢 Especialistas Activos / Disponibles');
        $response->assertSee('⚪ Especialistas No Disponibles');
        $response->assertSee('🟢 Carlos Activo');
        $response->assertSee('⚪ Daniel Inactivo');

        // 2. Ficha y textos de disponibilidad
        $response->assertSee('Última actualización de turno:');
        $response->assertSee('Advertencia de Asignación Operacional');
        $response->assertSee('El agente seleccionado no está marcado como disponible (estado actual: <strong>NO DISPONIBLE</strong>).', false);
        $response->assertSee('Proceder con Asignación (No Disponible)');
    }

    /**
     * Requisito 4: Agentes no disponibles nunca deben ser preseleccionados por defecto.
     */
    public function test_unavailable_agent_is_never_selected_by_default(): void
    {
        $gtsRole = Role::where('slug', 'gts')->firstOrFail();

        $unavailableAgent = User::factory()->create([
            'name' => 'Tech No Disponible',
            'role_id' => $gtsRole->id,
            'shift_status' => 'NO DISPONIBLE',
            'is_active' => true,
        ]);

        // Incidencia asignada previamente a un técnico que ahora está NO DISPONIBLE
        $incident = Incident::factory()->create([
            'assigned_to_user_id' => $unavailableAgent->id,
        ]);

        $admin = User::where('email', 'admin@aifa.aero')->firstOrFail();

        $response = $this->actingAs($admin)->get(route('incidents.show', $incident->folio));
        $response->assertOk();

        // Verificar que selectedTechId en el modal se inicializa vacío y no con el ID del no disponible
        $response->assertSee("selectedTechId: ''", false);
        $response->assertSee('-- Seleccionar especialista técnico GTS --');
    }

    /**
     * Requisito 6 y 8: Asignación a técnico ACTIVO procede con mensaje de éxito regular.
     */
    public function test_authorized_user_can_assign_active_agent_successfully(): void
    {
        $coordinator = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $technician = User::where('email', 'gts.mendoza@aifa.aero')->firstOrFail();
        $technician->update(['shift_status' => 'ACTIVO']);

        $incident = Incident::factory()->nueva()->create();

        $response = $this->actingAs($coordinator)->post(route('incidents.assign', $incident), [
            'assigned_to_user_id' => $technician->id,
            'comment' => 'Asignación regular a técnico en servicio.',
        ]);

        $response->assertRedirect(route('incidents.show', $incident->folio));
        $response->assertSessionHas('success', "Incidencia {$incident->folio} asignada al especialista {$technician->name} exitosamente.");

        $incident->refresh();
        $this->assertEquals($technician->id, $incident->assigned_to_user_id);
        $this->assertEquals('ASIGNADA', $incident->status->code);
    }

    /**
     * Requisito 6: No se bloquea en forma absoluta la asignación si existe necesidad operacional,
     * pero se notifica al usuario autorizado la nota de disponibilidad.
     */
    public function test_authorized_user_can_assign_unavailable_agent_with_operational_note(): void
    {
        $coordinator = User::where('email', 'gts.morales@aifa.aero')->firstOrFail();
        $technician = User::where('email', 'gts.mendoza@aifa.aero')->firstOrFail();
        $technician->update([
            'shift_status' => 'NO DISPONIBLE',
            'shift_status_updated_at' => now(),
        ]);

        $incident = Incident::factory()->nueva()->create();

        $response = $this->actingAs($coordinator)->post(route('incidents.assign', $incident), [
            'assigned_to_user_id' => $technician->id,
            'comment' => 'Asignación de emergencia autorizada por supervisor de guardia.',
        ]);

        $response->assertRedirect(route('incidents.show', $incident->folio));
        
        // Verificar que el mensaje contiene la nota de turno NO DISPONIBLE
        $response->assertSessionHas('success', "Incidencia {$incident->folio} asignada al especialista {$technician->name} exitosamente. (Nota de disponibilidad: El especialista se encuentra actualmente marcado como NO DISPONIBLE).");

        $incident->refresh();
        $this->assertEquals($technician->id, $incident->assigned_to_user_id);
        $this->assertEquals('ASIGNADA', $incident->status->code);
    }

    /**
     * Rol APOC no tiene permiso de asignación (debe responder 403 Forbidden).
     */
    public function test_apoc_user_is_forbidden_from_assigning(): void
    {
        $apocUser = User::where('email', 'apoc@aifa.aero')->firstOrFail();
        $technician = User::where('email', 'gts.mendoza@aifa.aero')->firstOrFail();
        $incident = Incident::factory()->nueva()->create();

        $response = $this->actingAs($apocUser)->post(route('incidents.assign', $incident), [
            'assigned_to_user_id' => $technician->id,
        ]);

        $response->assertForbidden();
    }
}
