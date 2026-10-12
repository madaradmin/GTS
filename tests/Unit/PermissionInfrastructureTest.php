<?php

namespace Tests\Unit;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    public function test_permissions_are_seeded_with_expected_modules(): void
    {
        $this->assertDatabaseCount('permissions', 25);

        $incidentsPerms = Permission::byModule('incidents')->get();
        $this->assertCount(12, $incidentsPerms);

        $usersPerms = Permission::byModule('users')->get();
        $this->assertCount(4, $usersPerms);

        $rolesPerms = Permission::byModule('roles')->get();
        $this->assertCount(6, $rolesPerms);

        $reportsPerms = Permission::byModule('reports')->get();
        $this->assertCount(2, $reportsPerms);

        $auditPerms = Permission::byModule('audit')->get();
        $this->assertCount(1, $auditPerms);

        $dashboardPerms = Permission::byModule('dashboard')->get();
        $this->assertCount(0, $dashboardPerms);
    }

    public function test_system_roles_have_visual_properties_and_protected_status(): void
    {
        $admin = Role::where('slug', 'admin')->firstOrFail();
        $this->assertTrue($admin->is_protected);
        $this->assertEquals('purple', $admin->color);
        $this->assertEquals('bg-purple-500/10', $admin->badge_bg);
        $this->assertEquals('text-purple-400', $admin->badge_text);
        $this->assertEquals('border-purple-500/20', $admin->badge_border);

        $gts = Role::where('slug', 'gts')->firstOrFail();
        $this->assertFalse($gts->is_protected);
        $this->assertEquals('cyan', $gts->color);

        $cco = Role::where('slug', 'apoc-cco')->firstOrFail();
        $this->assertFalse($cco->is_protected);
        $this->assertEquals('amber', $cco->color);

        $csa = Role::where('slug', 'apoc-csa')->firstOrFail();
        $this->assertFalse($csa->is_protected);
        $this->assertEquals('orange', $csa->color);
    }

    public function test_admin_role_has_full_permission_bypass(): void
    {
        $adminRole = Role::where('slug', 'admin')->firstOrFail();

        $this->assertTrue($adminRole->hasPermission('incidents.create'));
        $this->assertTrue($adminRole->hasPermission('roles.delete'));
        $this->assertTrue($adminRole->hasPermission('any.arbitrary.permission'));

        $adminUser = User::factory()->admin()->create();
        $this->assertTrue($adminUser->hasPermission('incidents.create'));
        $this->assertTrue($adminUser->hasPermission('roles.delete'));
        $this->assertTrue($adminUser->hasPermission('any.arbitrary.permission'));
        $this->assertTrue($adminUser->hasAnyPermission(['incidents.create', 'nonexistent']));
        $this->assertTrue($adminUser->hasAllPermissions(['incidents.create', 'users.create']));
    }

    public function test_gts_role_has_technician_permissions_and_correct_scopes(): void
    {
        $gtsRole = Role::where('slug', 'gts')->firstOrFail();
        $ccoRole = Role::where('slug', 'apoc-cco')->firstOrFail();

        // Permisos técnicos
        $this->assertTrue($gtsRole->hasPermission('incidents.create'));
        $this->assertTrue($gtsRole->hasPermission('incidents.attend'));
        $this->assertTrue($gtsRole->hasPermission('incidents.resolve'));
        $this->assertTrue($gtsRole->hasPermission('incidents.take'));
        $this->assertTrue($gtsRole->hasPermission('incidents.release'));
        $this->assertTrue($gtsRole->hasPermission('incidents.assign'));
        $this->assertTrue($gtsRole->hasPermission('reports.view'));
        $this->assertFalse($gtsRole->hasPermission('dashboard.technical'));

        // No debe tener permisos administrativos o de cierre formal APOC
        $this->assertFalse($gtsRole->hasPermission('incidents.vobo_close'));
        $this->assertFalse($gtsRole->hasPermission('roles.manage_permissions'));
        $this->assertFalse($gtsRole->hasPermission('users.delete'));

        // Scopes operativos: GTS puede ver y atender CCO, pero no asignar a CCO
        $this->assertTrue($gtsRole->canViewRole($ccoRole));
        $this->assertTrue($gtsRole->canViewRole('apoc-cco'));
        $this->assertTrue($gtsRole->canViewRole($ccoRole->id));
        $this->assertTrue($gtsRole->canAttendRole('apoc-cco'));
        $this->assertFalse($gtsRole->canAssignToRole('apoc-cco'));

        // GTS puede asignarse a sí mismo / especialistas GTS
        $this->assertTrue($gtsRole->canAssignToRole($gtsRole));
        $this->assertTrue($gtsRole->canAssignToRole('gts'));

        // Verificación a nivel User
        $gtsUser = User::factory()->gts()->create();
        $this->assertTrue($gtsUser->hasPermission('incidents.attend'));
        $this->assertFalse($gtsUser->hasPermission('incidents.vobo_close'));
        $this->assertTrue($gtsUser->canViewRole('apoc-cco'));
        $this->assertTrue($gtsUser->canAttendRole('apoc-cco'));
        $this->assertFalse($gtsUser->canAssignToRole('apoc-cco'));
        $this->assertTrue($gtsUser->canAssignToRole('gts'));
    }

    public function test_apoc_cco_has_reporting_and_vobo_permissions_isolated_scope(): void
    {
        $ccoRole = Role::where('slug', 'apoc-cco')->firstOrFail();
        $csaRole = Role::where('slug', 'apoc-csa')->firstOrFail();
        $gtsRole = Role::where('slug', 'gts')->firstOrFail();

        // Permisos APOC
        $this->assertTrue($ccoRole->hasPermission('incidents.create'));
        $this->assertTrue($ccoRole->hasPermission('incidents.vobo_close'));
        $this->assertTrue($ccoRole->hasPermission('incidents.reopen'));
        $this->assertTrue($ccoRole->hasPermission('reports.view'));
        $this->assertFalse($ccoRole->hasPermission('dashboard.monitoring'));

        // No debe tener permisos de resolución técnica ni administración
        $this->assertFalse($ccoRole->hasPermission('incidents.attend'));
        $this->assertFalse($ccoRole->hasPermission('incidents.resolve'));
        $this->assertFalse($ccoRole->hasPermission('roles.view'));

        // Scopes operativos: CCO solo ve su propio rol, no atiende ni asigna
        $this->assertTrue($ccoRole->canViewRole('apoc-cco'));
        $this->assertFalse($ccoRole->canViewRole('apoc-csa'));
        $this->assertFalse($ccoRole->canViewRole('gts'));
        $this->assertFalse($ccoRole->canAttendRole('apoc-cco'));
        $this->assertFalse($ccoRole->canAssignToRole('apoc-cco'));

        // Verificación a nivel User
        $ccoUser = User::factory()->apocCco()->create();
        $this->assertTrue($ccoUser->hasPermission('incidents.vobo_close'));
        $this->assertFalse($ccoUser->hasPermission('incidents.attend'));
        $this->assertTrue($ccoUser->canViewRole('apoc-cco'));
        $this->assertFalse($ccoUser->canViewRole('apoc-csa'));
        $this->assertFalse($ccoUser->canAttendRole('apoc-cco'));
        $this->assertFalse($ccoUser->canAssignToRole('apoc-cco'));
    }

    public function test_admin_user_has_operational_scope_bypass(): void
    {
        $adminUser = User::factory()->admin()->create();

        $this->assertTrue($adminUser->canViewRole('apoc-cco'));
        $this->assertTrue($adminUser->canViewRole('apoc-csa'));
        $this->assertTrue($adminUser->canViewRole('gts'));
        $this->assertTrue($adminUser->canViewRole('admin'));

        $this->assertTrue($adminUser->canAttendRole('apoc-cco'));
        $this->assertTrue($adminUser->canAttendRole('gts'));

        $this->assertTrue($adminUser->canAssignToRole('apoc-cco'));
        $this->assertTrue($adminUser->canAssignToRole('gts'));
    }

    public function test_dynamic_permission_grant_and_revoke_on_role(): void
    {
        $gtsRole = Role::where('slug', 'gts')->firstOrFail();
        $this->assertFalse($gtsRole->hasPermission('roles.view'));

        // Conceder permiso
        $gtsRole->givePermission('roles.view');
        $gtsRole->unsetRelation('permissions');
        $this->assertTrue($gtsRole->hasPermission('roles.view'));

        // Revocar permiso
        $gtsRole->revokePermission('roles.view');
        $gtsRole->unsetRelation('permissions');
        $this->assertFalse($gtsRole->hasPermission('roles.view'));
    }
}
