<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_guest_is_redirected_to_login_when_accessing_roles(): void
    {
        $response = $this->get(route('roles.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_user_cannot_access_role_management(): void
    {
        $gtsUser = User::factory()->gts()->create();

        $response = $this->actingAs($gtsUser)->get(route('roles.index'));
        $response->assertForbidden();
    }

    public function test_admin_can_view_roles_index_with_metrics_and_badges(): void
    {
        $adminUser = User::factory()->admin()->create();

        $response = $this->actingAs($adminUser)->get(route('roles.index'));
        $response->assertOk();
        $response->assertSee('Gestión de Roles y Permisos');
        $response->assertSee('ADMINISTRADOR');
        $response->assertSee('GTS');
        $response->assertSee('APOC-CCO');
        $response->assertSee('APOC-CSA');
        $response->assertSee('Nuevo Rol');
    }

    public function test_admin_can_create_a_new_custom_role(): void
    {
        $adminUser = User::factory()->admin()->create();

        $payload = [
            'name' => 'Soporte TICS',
            'slug' => 'soporte-tics',
            'description' => 'Personal técnico de soporte informático en terminal.',
            'color' => 'emerald',
        ];

        $response = $this->actingAs($adminUser)->post(route('roles.store'), $payload);

        $newRole = Role::where('slug', 'soporte-tics')->firstOrFail();
        $response->assertRedirect(route('roles.manage', $newRole));
        $response->assertSessionHas('status_message');

        $this->assertEquals('Soporte TICS', $newRole->name);
        $this->assertEquals('emerald', $newRole->color);
        $this->assertEquals('bg-emerald-500/10', $newRole->badge_bg);
        $this->assertEquals('text-emerald-400', $newRole->badge_text);
        $this->assertFalse($newRole->is_protected);
    }

    public function test_cannot_create_role_with_duplicate_or_invalid_slug(): void
    {
        $adminUser = User::factory()->admin()->create();

        // 1. Slug duplicado
        $responseDuplicate = $this->actingAs($adminUser)->post(route('roles.store'), [
            'name' => 'Otro Administrador',
            'slug' => 'admin',
            'color' => 'purple',
        ]);
        $responseDuplicate->assertSessionHasErrors('slug');

        // 2. Slug con mayúsculas y espacios
        $responseInvalid = $this->actingAs($adminUser)->post(route('roles.store'), [
            'name' => 'Rol Invalido',
            'slug' => 'Rol Invalido 123!',
            'color' => 'blue',
        ]);
        $responseInvalid->assertSessionHasErrors('slug');
    }

    public function test_admin_can_view_role_configuration_screen(): void
    {
        $adminUser = User::factory()->admin()->create();
        $gtsRole = Role::where('slug', 'gts')->firstOrFail();

        $response = $this->actingAs($adminUser)->get(route('roles.manage', $gtsRole));
        $response->assertOk();
        $response->assertSee('Matriz de Permisos Granulares');
        $response->assertSee('Matriz de Alcances Operativos');
        $response->assertSee('GTS');
    }

    public function test_admin_can_update_role_details(): void
    {
        $adminUser = User::factory()->admin()->create();
        $customRole = Role::create([
            'name' => 'Guardia Nocturna',
            'slug' => 'guardia-nocturna',
            'description' => 'Turno nocturno',
            'color' => 'slate',
            'is_protected' => false,
        ]);

        $response = $this->actingAs($adminUser)->put(route('roles.update', $customRole), [
            'name' => 'Guardia Nocturna Especial',
            'slug' => 'guardia-nocturna-esp',
            'description' => 'Turno nocturno actualizado',
            'color' => 'rose',
        ]);

        $response->assertSessionHas('status_message');
        $customRole->refresh();

        $this->assertEquals('Guardia Nocturna Especial', $customRole->name);
        $this->assertEquals('guardia-nocturna-esp', $customRole->slug);
        $this->assertEquals('rose', $customRole->color);
        $this->assertEquals('bg-rose-500/10', $customRole->badge_bg);
    }

    public function test_protected_role_slug_cannot_be_changed(): void
    {
        $adminUser = User::factory()->admin()->create();
        $adminRole = Role::where('slug', 'admin')->firstOrFail();

        $response = $this->actingAs($adminUser)->put(route('roles.update', $adminRole), [
            'name' => 'ADMINISTRADOR DEL SISTEMA',
            'slug' => 'nuevo-slug-admin',
            'description' => 'Descripción actualizada',
            'color' => 'purple',
        ]);

        $response->assertSessionHas('status_message');
        $adminRole->refresh();

        $this->assertEquals('admin', $adminRole->slug);
        $this->assertEquals('ADMINISTRADOR DEL SISTEMA', $adminRole->name);
    }

    public function test_cannot_delete_protected_admin_role(): void
    {
        $adminUser = User::factory()->admin()->create();
        $adminRole = Role::where('slug', 'admin')->firstOrFail();

        $response = $this->actingAs($adminUser)->delete(route('roles.destroy', $adminRole));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('roles', ['id' => $adminRole->id, 'slug' => 'admin']);
    }

    public function test_can_delete_role_with_assigned_users_and_safely_unassigns_them(): void
    {
        $adminUser = User::factory()->admin()->create();
        $customRole = Role::create([
            'name' => 'Operador Auxiliar',
            'slug' => 'operador-auxiliar',
            'color' => 'blue',
            'is_protected' => false,
        ]);

        $user = User::factory()->create(['role_id' => $customRole->id]);

        $response = $this->actingAs($adminUser)->delete(route('roles.destroy', $customRole));
        $response->assertSessionHas('status_message');
        $this->assertDatabaseMissing('roles', ['id' => $customRole->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'role_id' => null]);
    }

    public function test_can_delete_unprotected_role_with_zero_users(): void
    {
        $adminUser = User::factory()->admin()->create();
        $customRole = Role::create([
            'name' => 'Rol Temporal',
            'slug' => 'rol-temporal',
            'color' => 'slate',
            'is_protected' => false,
        ]);

        $response = $this->actingAs($adminUser)->delete(route('roles.destroy', $customRole));
        $response->assertRedirect(route('roles.index'));
        $response->assertSessionHas('status_message');
        $this->assertDatabaseMissing('roles', ['id' => $customRole->id]);
    }

    public function test_admin_can_update_role_permissions(): void
    {
        $adminUser = User::factory()->admin()->create();
        $customRole = Role::create([
            'name' => 'Auditor Externo',
            'slug' => 'auditor-externo',
            'color' => 'blue',
            'is_protected' => false,
        ]);

        $perm1 = Permission::where('slug', 'audit.view')->firstOrFail();
        $perm2 = Permission::where('slug', 'reports.view')->firstOrFail();

        $response = $this->actingAs($adminUser)->put(route('roles.update-permissions', $customRole), [
            'permissions' => [$perm1->id, $perm2->id],
        ]);

        $response->assertSessionHas('status_message');
        $this->assertTrue($customRole->fresh()->hasPermission('audit.view'));
        $this->assertTrue($customRole->fresh()->hasPermission('reports.view'));
        $this->assertFalse($customRole->fresh()->hasPermission('incidents.create'));
    }

    public function test_admin_can_update_role_operational_scopes(): void
    {
        $adminUser = User::factory()->admin()->create();
        $customRole = Role::create([
            'name' => 'Soporte Especial',
            'slug' => 'soporte-especial',
            'color' => 'cyan',
            'is_protected' => false,
        ]);

        $ccoRole = Role::where('slug', 'apoc-cco')->firstOrFail();
        $csaRole = Role::where('slug', 'apoc-csa')->firstOrFail();

        $response = $this->actingAs($adminUser)->put(route('roles.update-scopes', $customRole), [
            'view_scopes' => [$ccoRole->id, $csaRole->id],
            'attend_scopes' => [$ccoRole->id],
            'assign_scopes' => [$customRole->id],
        ]);

        $response->assertSessionHas('status_message');

        $this->assertTrue($customRole->fresh()->canViewRole('apoc-cco'));
        $this->assertTrue($customRole->fresh()->canViewRole('apoc-csa'));
        $this->assertTrue($customRole->fresh()->canAttendRole('apoc-cco'));
        $this->assertFalse($customRole->fresh()->canAttendRole('apoc-csa'));
        $this->assertTrue($customRole->fresh()->canAssignToRole('soporte-especial'));
        $this->assertFalse($customRole->fresh()->canAssignToRole('apoc-cco'));
    }
}
