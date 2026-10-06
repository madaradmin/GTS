<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftStatusEliminationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * 1. La ruta /profile/shift-status ha sido eliminada por completo y retorna 404 para cualquier rol.
     */
    public function test_shift_status_endpoint_returns_404_for_all_roles(): void
    {
        $admin = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->first();
        $gts = User::whereHas('role', fn ($q) => $q->where('slug', 'gts'))->first();
        $apoc = User::whereHas('role', fn ($q) => $q->where('slug', 'apoc'))->first();

        $this->actingAs($admin)->post('/profile/shift-status', ['status' => 'ACTIVO'])->assertNotFound();
        $this->actingAs($gts)->post('/profile/shift-status', ['status' => 'NO DISPONIBLE'])->assertNotFound();
        $this->actingAs($apoc)->post('/profile/shift-status', ['status' => 'ACTIVO'])->assertNotFound();
    }

    /**
     * 2. El Topbar no contiene botones, selectores ni dropdowns de turno o disponibilidad.
     */
    public function test_topbar_does_not_contain_shift_controls_or_labels(): void
    {
        $gts = User::whereHas('role', fn ($q) => $q->where('slug', 'gts'))->first();

        $response = $this->actingAs($gts)->get(route('dashboard'));
        $response->assertOk();

        // No debe existir el selector rápido de turno
        $response->assertDontSee('Disponibilidad GTS');
        $response->assertDontSee('Alternar disponibilidad');
        $response->assertDontSee('Turno: ACTIVO');
        $response->assertDontSee('Turno: NO DISPONIBLE');
    }

    /**
     * 3. El Sidebar no contiene indicadores ni badges de turno (🟢 ACTIVO / ⚪ NO DISP.).
     */
    public function test_sidebar_does_not_contain_shift_indicators(): void
    {
        $gts = User::whereHas('role', fn ($q) => $q->where('slug', 'gts'))->first();

        $response = $this->actingAs($gts)->get(route('dashboard'));
        $response->assertOk();

        $response->assertDontSee('🟢 ACTIVO');
        $response->assertDontSee('⚪ NO DISP.');
        $response->assertDontSee('Disponibilidad de Turno');
    }

    /**
     * 4. La vista de Perfil no contiene el formulario ni sección de Estado de Turno.
     */
    public function test_profile_edit_does_not_render_shift_form_or_history(): void
    {
        $gts = User::whereHas('role', fn ($q) => $q->where('slug', 'gts'))->first();

        $response = $this->actingAs($gts)->get(route('profile.edit'));
        $response->assertOk();

        $response->assertDontSee('Estado de Turno y Disponibilidad Operacional');
        $response->assertDontSee('Control de Guardia');
        $response->assertDontSee('Historial Reciente de Guardia y Disponibilidad');
    }

    /**
     * 5. La tabla de Usuarios en Configuración no contiene la columna 'Turno'.
     */
    public function test_users_index_table_does_not_contain_turno_column(): void
    {
        $admin = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->first();

        $response = $this->actingAs($admin)->get(route('users.index'));
        $response->assertOk();

        $response->assertDontSee('<th class="py-3 px-4 text-center">Turno</th>', false);
        $response->assertDontSee('NO DISP.');
    }

    /**
     * 6. La vista de Incidencia y modal de asignación no tienen avisos ni partición de agentes por turno.
     */
    public function test_incident_show_and_assign_modal_do_not_contain_shift_status_warnings(): void
    {
        $admin = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->first();
        $gts = User::whereHas('role', fn ($q) => $q->where('slug', 'gts'))->first();

        $incident = Incident::factory()->nueva()->create();

        $response = $this->actingAs($admin)->get(route('incidents.show', $incident->folio));
        $response->assertOk();

        // Sin badges ni advertencias de turno
        $response->assertDontSee('Advertencia de Asignación Operacional');
        $response->assertDontSee('El agente seleccionado no está marcado como disponible');
        $response->assertDontSee('Especialistas Activos / Disponibles');
        $response->assertDontSee('Especialistas No Disponibles');
        $response->assertDontSee('Proceder con Asignación (No Disponible)');

        // La asignación funciona limpiamente sin notas de turno
        $assignResponse = $this->actingAs($admin)->post(route('incidents.assign', $incident), [
            'assigned_to_user_id' => $gts->id,
            'comment' => 'Asignación directa limpia',
        ]);
        $assignResponse->assertRedirect();
        $assignResponse->assertSessionHas('success');
        $this->assertStringNotContainsString('NO DISPONIBLE', session('success'));
    }

    /**
     * 7. El campo is_active en User sigue funcionando estrictamente para autenticación y control de acceso.
     */
    public function test_is_active_remains_intact_for_user_authentication(): void
    {
        $user = User::factory()->gts()->create(['is_active' => true]);
        $this->assertTrue($user->is_active);

        // Al alternar estado de cuenta
        $user->is_active = false;
        $user->save();
        $this->assertFalse($user->fresh()->is_active);
    }
}
