<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\IncidentHistory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $apocUser;

    protected User $gtsUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->adminUser = User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->firstOrFail();
        $this->apocUser = User::whereHas('role', fn ($q) => $q->where('slug', 'apoc'))->firstOrFail();
        $this->gtsUser = User::whereHas('role', fn ($q) => $q->where('slug', 'gts'))->firstOrFail();
    }

    public function test_guest_cannot_access_user_management(): void
    {
        $response = $this->get(route('users.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_apoc_user_cannot_access_user_management(): void
    {
        $response = $this->actingAs($this->apocUser)->get(route('users.index'));
        $response->assertForbidden();
    }

    public function test_gts_user_cannot_access_user_management(): void
    {
        $response = $this->actingAs($this->gtsUser)->get(route('users.index'));
        $response->assertForbidden();
    }

    public function test_admin_user_can_access_user_management_index(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('users.index'));

        $response->assertStatus(200);
        $response->assertSee('Gestión de Usuarios y Cuadrillas');
        $response->assertSee('Total Usuarios');
        $response->assertSee('Registrar Usuario');
        $response->assertSee($this->apocUser->name);
        $response->assertSee($this->gtsUser->name);
    }

    public function test_admin_can_create_new_user(): void
    {
        $gtsRole = Role::where('slug', 'gts')->firstOrFail();

        $response = $this->actingAs($this->adminUser)->post(route('users.store'), [
            'name' => 'Ing. Fernando Soto',
            'username' => 'fernando.soto',
            'password' => 'SecurePass123!',
            'role_id' => $gtsRole->id,
            'badge_number' => 'GTS-999',
        ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('status_message');

        $this->assertDatabaseHas('users', [
            'name' => 'Ing. Fernando Soto',
            'username' => 'fernando.soto',
            'email' => null,
            'role_id' => $gtsRole->id,
            'badge_number' => 'GTS-999',
            'is_active' => true,
        ]);
    }

    public function test_create_user_modal_does_not_contain_email_field(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('users.index'));
        $response->assertOk();
        $response->assertSee('Alta de Usuario Institucional');
        $response->assertDontSee('Correo Electrónico (Opcional)');
    }

    public function test_admin_can_create_user_without_email(): void
    {
        $gtsRole = Role::where('slug', 'gts')->firstOrFail();

        $response = $this->actingAs($this->adminUser)->post(route('users.store'), [
            'name' => 'Ing. Laura Rios',
            'username' => 'laura.rios',
            'email' => null,
            'password' => 'SecurePass123!',
            'role_id' => $gtsRole->id,
            'badge_number' => 'GTS-333',
        ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('status_message');

        $this->assertDatabaseHas('users', [
            'name' => 'Ing. Laura Rios',
            'username' => 'laura.rios',
            'email' => null,
            'role_id' => $gtsRole->id,
            'badge_number' => 'GTS-333',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_user_details(): void
    {
        $user = User::factory()->gts()->create(['name' => 'Nombre Viejo', 'username' => 'nombre.viejo']);
        $adminRole = Role::where('slug', 'admin')->firstOrFail();

        $response = $this->actingAs($this->adminUser)->put(route('users.update', $user), [
            'name' => 'Nombre Actualizado',
            'username' => 'nombre.actualizado',
            'email' => $user->email,
            'role_id' => $adminRole->id,
            'badge_number' => 'ADM-888',
        ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('status_message');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nombre Actualizado',
            'username' => 'nombre.actualizado',
            'role_id' => $adminRole->id,
            'badge_number' => 'ADM-888',
        ]);
    }

    public function test_admin_can_toggle_user_status(): void
    {
        $targetUser = User::factory()->gts()->create(['is_active' => true]);

        // Desactivar
        $response = $this->actingAs($this->adminUser)->post(route('users.toggle-status', $targetUser));
        $response->assertRedirect(route('users.index'));
        $this->assertFalse($targetUser->fresh()->is_active);

        // Reactivar
        $response2 = $this->actingAs($this->adminUser)->post(route('users.toggle-status', $targetUser));
        $response2->assertRedirect(route('users.index'));
        $this->assertTrue($targetUser->fresh()->is_active);
    }

    public function test_admin_cannot_deactivate_or_delete_self(): void
    {
        // Intento de auto-desactivación
        $responseToggle = $this->actingAs($this->adminUser)->post(route('users.toggle-status', $this->adminUser));
        $responseToggle->assertSessionHas('error');
        $this->assertTrue($this->adminUser->fresh()->is_active);

        // Intento de auto-eliminación
        $responseDelete = $this->actingAs($this->adminUser)->delete(route('users.destroy', $this->adminUser));
        $responseDelete->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->adminUser->id]);
    }

    public function test_admin_cannot_deactivate_the_last_active_admin(): void
    {
        // Asegurar que solo hay un administrador activo
        User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))
            ->where('id', '!=', $this->adminUser->id)
            ->delete();

        $response = $this->actingAs($this->adminUser)->post(route('users.toggle-status', $this->adminUser));
        $response->assertSessionHas('error');
        $this->assertTrue($this->adminUser->fresh()->is_active);
    }

    public function test_permanent_deletion_succeeds_and_reassigns_historical_records(): void
    {
        $gtsUserWithRecords = User::factory()->gts()->create(['is_active' => true]);

        // Asociar incidencias (como asignado y como reportante) e historial
        $incidentAssigned = Incident::factory()->create([
            'assigned_to_user_id' => $gtsUserWithRecords->id,
        ]);

        $incidentReported = Incident::factory()->create([
            'reported_by_user_id' => $gtsUserWithRecords->id,
        ]);

        $history = IncidentHistory::factory()->create([
            'incident_id' => $incidentAssigned->id,
            'user_id' => $gtsUserWithRecords->id,
            'comment' => 'Nota original del técnico.',
        ]);

        $response = $this->actingAs($this->adminUser)->delete(route('users.destroy', $gtsUserWithRecords));

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('status_message');

        // El usuario SÍ fue borrado físicamente
        $this->assertDatabaseMissing('users', ['id' => $gtsUserWithRecords->id]);

        // Las incidencias asignadas se desasignan (assigned_to_user_id => null)
        $this->assertNull($incidentAssigned->fresh()->assigned_to_user_id);

        // Las incidencias reportadas se transfieren al admin
        $this->assertEquals($this->adminUser->id, $incidentReported->fresh()->reported_by_user_id);

        // El historial se transfiere al admin y anota la trazabilidad
        $freshHistory = $history->fresh();
        $this->assertEquals($this->adminUser->id, $freshHistory->user_id);
        $this->assertStringContainsString('[Operador de origen purgado del sistema', $freshHistory->comment);
    }

    public function test_physical_deletion_succeeds_for_clean_user_without_records(): void
    {
        $cleanUser = User::factory()->gts()->create();

        $response = $this->actingAs($this->adminUser)->delete(route('users.destroy', $cleanUser));

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('status_message');

        // El usuario sí fue borrado físicamente
        $this->assertDatabaseMissing('users', ['id' => $cleanUser->id]);
    }

    public function test_user_can_authenticate_using_username(): void
    {
        $user = User::factory()->gts()->create([
            'username' => 'operador.prueba',
            'password' => bcrypt('Password123!'),
            'is_active' => true,
        ]);

        $response = $this->post(route('login'), [
            'username' => 'operador.prueba',
            'password' => 'Password123!',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_login_form_renders_username_field_and_no_email_field(): void
    {
        $response = $this->get(route('login'));
        $response->assertOk();
        $response->assertSee('name="username"', false);
        $response->assertDontSee('name="email"', false);
    }
}
