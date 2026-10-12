<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RoleOperationalScope;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_admin_cannot_delete_administrador_role(): void
    {
        $adminUser = User::factory()->admin()->create();
        $adminRole = Role::where('slug', 'admin')->firstOrFail();

        $response = $this->actingAs($adminUser)->delete(route('roles.destroy', $adminRole));

        $response->assertRedirect(route('roles.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('roles', ['id' => $adminRole->id, 'slug' => 'admin']);
    }

    public function test_admin_can_delete_gts_role(): void
    {
        $adminUser = User::factory()->admin()->create();
        $gtsRole = Role::where('slug', 'gts')->firstOrFail();

        $response = $this->actingAs($adminUser)->delete(route('roles.destroy', $gtsRole));

        $response->assertRedirect(route('roles.index'));
        $response->assertSessionHas('status_message');
        $this->assertDatabaseMissing('roles', ['slug' => 'gts']);
    }

    public function test_admin_can_delete_apoc_cco_role(): void
    {
        $adminUser = User::factory()->admin()->create();
        $ccoRole = Role::where('slug', 'apoc-cco')->firstOrFail();

        $response = $this->actingAs($adminUser)->delete(route('roles.destroy', $ccoRole));

        $response->assertRedirect(route('roles.index'));
        $response->assertSessionHas('status_message');
        $this->assertDatabaseMissing('roles', ['slug' => 'apoc-cco']);
    }

    public function test_admin_can_delete_apoc_csa_role(): void
    {
        $adminUser = User::factory()->admin()->create();
        $csaRole = Role::where('slug', 'apoc-csa')->firstOrFail();

        $response = $this->actingAs($adminUser)->delete(route('roles.destroy', $csaRole));

        $response->assertRedirect(route('roles.index'));
        $response->assertSessionHas('status_message');
        $this->assertDatabaseMissing('roles', ['slug' => 'apoc-csa']);
    }

    public function test_admin_can_delete_custom_and_future_roles(): void
    {
        $adminUser = User::factory()->admin()->create();

        $customRole = Role::create([
            'name' => 'Soporte Especial TICS',
            'slug' => 'soporte-especial-tics',
            'color' => 'emerald',
            'is_protected' => false,
        ]);

        $response = $this->actingAs($adminUser)->delete(route('roles.destroy', $customRole));

        $response->assertRedirect(route('roles.index'));
        $response->assertSessionHas('status_message');
        $this->assertDatabaseMissing('roles', ['id' => $customRole->id]);
    }

    public function test_deleting_role_safely_unassigns_users_preserving_accounts_and_incidents(): void
    {
        $adminUser = User::factory()->admin()->create();

        $role = Role::create([
            'name' => 'Brigada Emergencias',
            'slug' => 'brigada-emergencias',
            'color' => 'rose',
            'is_protected' => false,
        ]);

        // Crear usuario con este rol
        $user = User::factory()->create([
            'role_id' => $role->id,
            'name' => 'Operador Brigada',
            'username' => 'brigada.operador',
        ]);

        // Crear una incidencia asociada
        $incident = Incident::factory()->create([
            'reported_by_user_id' => $user->id,
            'assigned_to_user_id' => $user->id,
        ]);

        $response = $this->actingAs($adminUser)->delete(route('roles.destroy', $role));

        $response->assertRedirect(route('roles.index'));
        $response->assertSessionHas('status_message');

        // El rol debe eliminarse de la base de datos
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);

        // El usuario debe conservarse intacto pero con role_id = null
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'username' => 'brigada.operador',
            'role_id' => null,
        ]);

        // La incidencia histórica debe mantenerse intacta
        $this->assertDatabaseHas('incidents', [
            'id' => $incident->id,
            'reported_by_user_id' => $user->id,
            'assigned_to_user_id' => $user->id,
        ]);
    }

    public function test_deleting_role_cleans_up_permissions_and_scopes_without_orphans(): void
    {
        $adminUser = User::factory()->admin()->create();

        $role = Role::create([
            'name' => 'Rol Alcances Test',
            'slug' => 'rol-alcances-test',
            'color' => 'blue',
            'is_protected' => false,
        ]);

        $perm = Permission::first();
        if ($perm) {
            $role->permissions()->attach($perm->id);
        }

        $adminRole = Role::where('slug', 'admin')->firstOrFail();
        RoleOperationalScope::create([
            'source_role_id' => $role->id,
            'target_role_id' => $adminRole->id,
            'scope_type' => 'view',
        ]);
        RoleOperationalScope::create([
            'source_role_id' => $adminRole->id,
            'target_role_id' => $role->id,
            'scope_type' => 'assign',
        ]);

        $response = $this->actingAs($adminUser)->delete(route('roles.destroy', $role));
        $response->assertRedirect(route('roles.index'));

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
        $this->assertDatabaseMissing('role_permissions', ['role_id' => $role->id]);
        $this->assertDatabaseMissing('role_operational_scopes', ['source_role_id' => $role->id]);
        $this->assertDatabaseMissing('role_operational_scopes', ['target_role_id' => $role->id]);
    }

    public function test_non_admin_cannot_delete_roles(): void
    {
        $gtsUser = User::factory()->gts()->create();

        $customRole = Role::create([
            'name' => 'Auxiliar Temporal',
            'slug' => 'auxiliar-temporal',
            'color' => 'slate',
            'is_protected' => false,
        ]);

        $response = $this->actingAs($gtsUser)->delete(route('roles.destroy', $customRole));
        $response->assertForbidden();

        $this->assertDatabaseHas('roles', ['id' => $customRole->id]);
    }

    public function test_guest_cannot_delete_roles(): void
    {
        $customRole = Role::create([
            'name' => 'Auxiliar Temporal',
            'slug' => 'auxiliar-temporal',
            'color' => 'slate',
            'is_protected' => false,
        ]);

        $response = $this->delete(route('roles.destroy', $customRole));
        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('roles', ['id' => $customRole->id]);
    }

    public function test_roles_index_view_shows_delete_button_for_all_roles_except_admin(): void
    {
        $adminUser = User::factory()->admin()->create();

        $response = $this->actingAs($adminUser)->get(route('roles.index'));
        $response->assertOk();

        $adminRole = Role::where('slug', 'admin')->firstOrFail();
        $gtsRole = Role::where('slug', 'gts')->firstOrFail();
        $ccoRole = Role::where('slug', 'apoc-cco')->firstOrFail();
        $csaRole = Role::where('slug', 'apoc-csa')->firstOrFail();

        // ADMINISTRADOR muestra el candado
        $response->assertSee('Rol de Administrador protegido: no se puede eliminar');

        // GTS, APOC-CCO y APOC-CSA tienen el botón de eliminar activo
        $response->assertSee("openDelete({$gtsRole->id}", false);
        $response->assertSee("openDelete({$ccoRole->id}", false);
        $response->assertSee("openDelete({$csaRole->id}", false);
    }
}
